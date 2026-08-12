<?php
/**
 * Routes de gestion des favoris
 */

// ===== AJOUTER/SUPPRIMER UN FAVORI =====
function handleToggleFavori($produitId) {
    requireLogin();
    $userId = getCurrentUserId();
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT * FROM favoris WHERE user_id = ? AND produit_id = ?");
    $stmt->execute([$userId, $produitId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE user_id = ? AND produit_id = ?");
        $stmt->execute([$userId, $produitId]);
        $liked = false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO favoris (user_id, produit_id) VALUES (?, ?)");
        $stmt->execute([$userId, $produitId]);
        $liked = true;
    }
    
    sendJson(['liked' => $liked]);
}
?>