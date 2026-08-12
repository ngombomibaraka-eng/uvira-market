<?php
/**
 * Routes de gestion des commandes
 */

// ===== CRÉER UNE COMMANDE =====
function handleCreateCommande($data) {
    requireLogin();
    $userId = getCurrentUserId();
    $items = $data['items'] ?? [];
    $livraison = $data['livraison'] ?? [];
    
    if (empty($items)) {
        sendError('Panier vide');
    }
    
    $pdo = getDB();
    
    // Vérifier que tous les produits sont du même vendeur
    $vendeurs = [];
    foreach ($items as $item) {
        $stmt = $pdo->prepare("SELECT vendeur_id FROM produits WHERE id = ?");
        $stmt->execute([$item['produit_id']]);
        $prod = $stmt->fetch();
        if ($prod) {
            $vendeurs[] = $prod['vendeur_id'];
        }
    }
    
    $vendeurs = array_unique($vendeurs);
    if (count($vendeurs) > 1) {
        sendError('Les produits doivent être du même vendeur');
    }
    
    if (empty($vendeurs)) {
        sendError('Aucun produit valide');
    }
    
    $vendeurId = $vendeurs[0];
    $orderId = generateOrderId();
    
    $totalFc = 0;
    $totalUsd = 0;
    
    // Créer la commande
    $stmt = $pdo->prepare("
        INSERT INTO commandes (id, acheteur_id, vendeur_id, livraison_quartier, livraison_avenue, livraison_adresse, livraison_note)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $orderId,
        $userId,
        $vendeurId,
        $livraison['quartier'] ?? '',
        $livraison['avenue'] ?? '',
        $livraison['adresse'] ?? '',
        $livraison['note'] ?? ''
    ]);
    
    // Ajouter les articles
    foreach ($items as $item) {
        $stmt = $pdo->prepare("
            SELECT id, nom, prix, prix_promo, devise, unite FROM produits WHERE id = ?
        ");
        $stmt->execute([$item['produit_id']]);
        $prod = $stmt->fetch();
        
        if (!$prod) continue;
        
        $prix = $prod['prix_promo'] ?: $prod['prix'];
        
        $stmt = $pdo->prepare("
            INSERT INTO commande_articles (commande_id, produit_id, nom, qty, prix, devise, unite)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$orderId, $prod['id'], $prod['nom'], $item['qty'], $prix, $prod['devise'], $prod['unite']]);
        
        if ($prod['devise'] === 'FC') {
            $totalFc += $prix * $item['qty'];
        } else {
            $totalUsd += $prix * $item['qty'];
        }
        
        // Mettre à jour le stock
        $stmt = $pdo->prepare("UPDATE produits SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$item['qty'], $prod['id']]);
    }
    
    // Mettre à jour les totaux
    $stmt = $pdo->prepare("UPDATE commandes SET total_fc = ?, total_usd = ? WHERE id = ?");
    $stmt->execute([$totalFc, $totalUsd, $orderId]);
    
    // Vider le panier
    $stmt = $pdo->prepare("DELETE FROM panier WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    sendSuccess([
        'commande_id' => $orderId,
        'total_fc' => $totalFc,
        'total_usd' => $totalUsd
    ]);
}

// ===== LISTE DES COMMANDES =====
function handleGetCommandes() {
    requireLogin();
    $userId = getCurrentUserId();
    $role = getCurrentUserRole();
    $pdo = getDB();
    
    if ($role === 'admin') {
        $sql = "
            SELECT c.*, u.nom as acheteur_nom, u2.nom as vendeur_nom, l.nom as livreur_nom
            FROM commandes c
            LEFT JOIN users u ON c.acheteur_id = u.id
            LEFT JOIN users u2 ON c.vendeur_id = u2.id
            LEFT JOIN livreurs l ON c.livreur_id = l.id
            ORDER BY c.date DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } elseif ($role === 'vendeur') {
        $sql = "
            SELECT c.*, u.nom as acheteur_nom, u2.nom as vendeur_nom, l.nom as livreur_nom
            FROM commandes c
            LEFT JOIN users u ON c.acheteur_id = u.id
            LEFT JOIN users u2 ON c.vendeur_id = u2.id
            LEFT JOIN livreurs l ON c.livreur_id = l.id
            WHERE c.vendeur_id = ?
            ORDER BY c.date DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    } else {
        $sql = "
            SELECT c.*, u.nom as acheteur_nom, u2.nom as vendeur_nom, l.nom as livreur_nom
            FROM commandes c
            LEFT JOIN users u ON c.acheteur_id = u.id
            LEFT JOIN users u2 ON c.vendeur_id = u2.id
            LEFT JOIN livreurs l ON c.livreur_id = l.id
            WHERE c.acheteur_id = ?
            ORDER BY c.date DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    }
    
    $commandes = $stmt->fetchAll();
    
    // Récupérer les articles pour chaque commande
    foreach ($commandes as &$cmd) {
        $stmt2 = $pdo->prepare("SELECT * FROM commande_articles WHERE commande_id = ?");
        $stmt2->execute([$cmd['id']]);
        $cmd['items'] = $stmt2->fetchAll();
    }
    
    sendJson($commandes);
}

// ===== METTRE À JOUR UNE COMMANDE =====
function handleUpdateCommande($commandeId, $data) {
    requireLogin();
    $userId = getCurrentUserId();
    $role = getCurrentUserRole();
    $pdo = getDB();
    
    $statut = $data['statut'] ?? null;
    $livreurId = $data['livreur_id'] ?? null;
    
    if (!$statut && $livreurId === null) {
        sendError('Au moins un champ à mettre à jour');
    }
    
    // Vérifier la commande
    $stmt = $pdo->prepare("SELECT vendeur_id, statut FROM commandes WHERE id = ?");
    $stmt->execute([$commandeId]);
    $cmd = $stmt->fetch();
    
    if (!$cmd) {
        sendError('Commande non trouvée', 404);
    }
    
    if ($role !== 'admin' && $cmd['vendeur_id'] != $userId) {
        sendForbidden('Non autorisé');
    }
    
    $updates = [];
    $params = [];
    
    if ($statut) {
        $updates[] = "statut = ?";
        $params[] = $statut;
    }
    
    if ($livreurId !== null) {
        $updates[] = "livreur_id = ?";
        $params[] = $livreurId;
    }
    
    if (empty($updates)) {
        sendSuccess();
    }
    
    $params[] = $commandeId;
    $stmt = $pdo->prepare("UPDATE commandes SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($params);
    
    // Ajouter un message système si statut changé
    if ($statut) {
        $labels = [
            'confirmee' => 'confirmée',
            'en_preparation' => 'en préparation',
            'en_livraison' => 'en livraison',
            'livree' => 'livrée',
            'annulee' => 'annulée'
        ];
        $msg = "Commande $commandeId " . ($labels[$statut] ?? $statut);
        
        // Récupérer les participants
        $stmt2 = $pdo->prepare("SELECT acheteur_id, vendeur_id, livreur_id FROM commandes WHERE id = ?");
        $stmt2->execute([$commandeId]);
        $cmdData = $stmt2->fetch();
        
        $participants = [$cmdData['acheteur_id'], $cmdData['vendeur_id']];
        if ($cmdData['livreur_id']) {
            $participants[] = $cmdData['livreur_id'];
        }
        
        $stmt2 = $pdo->prepare("
            INSERT INTO messages (conv_id, type, order_id, from_id, from_nom, participants, texte, system, date)
            VALUES (?, 'commande', ?, ?, 'Système', ?, ?, 1, NOW())
        ");
        $stmt2->execute([
            'cmd-' . $commandeId,
            $commandeId,
            $userId,
            json_encode($participants),
            $msg
        ]);
    }
    
    sendSuccess();
}

// ===== SUPPRIMER UNE COMMANDE =====
function handleDeleteCommande($commandeId) {
    requireAdmin();
    $pdo = getDB();
    
    $stmt = $pdo->prepare("DELETE FROM commande_articles WHERE commande_id = ?");
    $stmt->execute([$commandeId]);
    
    $stmt = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
    $stmt->execute([$commandeId]);
    
    sendSuccess();
}
?>