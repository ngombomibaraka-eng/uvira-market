<?php
/**
 * Routes de messagerie
 */

// ===== RÉCUPÉRER LES MESSAGES =====
function handleGetMessages() {
    requireLogin();
    $userId = getCurrentUserId();
    $convId = $_GET['conv_id'] ?? null;
    $pdo = getDB();
    
    if ($convId) {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE conv_id = ? ORDER BY date");
        $stmt->execute([$convId]);
        $messages = $stmt->fetchAll();
    } else {
        // Récupérer les conversations
        $stmt = $pdo->prepare("
            SELECT DISTINCT conv_id, type, order_id, participants, MAX(date) as last_date
            FROM messages
            WHERE participants LIKE ?
            GROUP BY conv_id
            ORDER BY last_date DESC
        ");
        $stmt->execute(['%' . $userId . '%']);
        $messages = $stmt->fetchAll();
    }
    
    // Traiter les champs JSON
    foreach ($messages as &$msg) {
        if (isset($msg['lu'])) {
            $msg['lu'] = json_decode($msg['lu'], true) ?: [];
        }
        if (isset($msg['participants'])) {
            $msg['participants'] = json_decode($msg['participants'], true) ?: [];
        }
    }
    
    sendJson($messages);
}

// ===== ENVOYER UN MESSAGE =====
function handleSendMessage($data) {
    requireLogin();
    $userId = getCurrentUserId();
    $convId = $data['conv_id'] ?? '';
    $texte = $data['texte'] ?? '';
    $participants = $data['participants'] ?? [$userId];
    $orderId = $data['order_id'] ?? null;
    $type = $data['type'] ?? 'prive';
    $mediaType = $data['media_type'] ?? null;
    $mediaData = $data['media_data'] ?? null;
    
    if (empty($convId)) {
        sendError('Conversation ID requis');
    }
    
    if (empty($texte) && empty($mediaData)) {
        sendError('Message ou média requis');
    }
    
    if (!in_array($userId, $participants)) {
        $participants[] = $userId;
    }
    
    $pdo = getDB();
    
    // Récupérer le nom et la photo de l'utilisateur
    $stmt = $pdo->prepare("SELECT nom, photo FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (conv_id, type, order_id, from_id, from_nom, from_photo, participants, texte, media_type, media_data, date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $convId,
        $type,
        $orderId,
        $userId,
        $user['nom'] ?? '',
        $user['photo'] ?? '',
        json_encode($participants),
        $texte,
        $mediaType,
        $mediaData
    ]);
    
    sendSuccess(['id' => lastInsertId()]);
}

// ===== SUPPRIMER UN MESSAGE =====
function handleDeleteMessage($msgId) {
    requireLogin();
    $userId = getCurrentUserId();
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT from_id FROM messages WHERE id = ?");
    $stmt->execute([$msgId]);
    $msg = $stmt->fetch();
    
    if (!$msg) {
        sendError('Message non trouvé', 404);
    }
    
    if ($msg['from_id'] != $userId) {
        sendForbidden('Non autorisé');
    }
    
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$msgId]);
    
    sendSuccess();
}

// ===== MARQUER COMME LU =====
function handleMarkRead($data) {
    requireLogin();
    $userId = getCurrentUserId();
    $convId = $data['conv_id'] ?? '';
    
    if (empty($convId)) {
        sendError('Conversation ID requis');
    }
    
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT lu FROM messages WHERE conv_id = ? AND from_id != ?");
    $stmt->execute([$convId, $userId]);
    $messages = $stmt->fetchAll();
    
    foreach ($messages as $msg) {
        $lu = json_decode($msg['lu'], true) ?: [];
        if (!in_array($userId, $lu)) {
            $lu[] = $userId;
            $stmt2 = $pdo->prepare("UPDATE messages SET lu = ? WHERE conv_id = ? AND from_id != ?");
            $stmt2->execute([json_encode($lu), $convId, $userId]);
        }
    }
    
    sendSuccess();
}
?>