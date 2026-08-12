<?php
/**
 * Fonctions utilitaires pour l'API
 */

// ===== FONCTIONS DE SESSION =====

function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    startSession();
    if (!isLoggedIn()) return null;
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND actif = 1");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getCurrentUserId() {
    startSession();
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    startSession();
    return $_SESSION['user_role'] ?? null;
}

// ===== FONCTIONS DE RÉPONSE =====

function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function sendSuccess($data = null, $message = 'success') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response = array_merge($response, $data);
    }
    sendJson($response);
}

function sendError($message, $statusCode = 400) {
    sendJson(['error' => $message], $statusCode);
}

function sendUnauthorized($message = 'Non authentifié') {
    sendJson(['error' => $message, 'code' => 'UNAUTHORIZED'], 401);
}

function sendForbidden($message = 'Accès non autorisé') {
    sendJson(['error' => $message], 403);
}

// ===== FONCTIONS DE SÉCURITÉ =====

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function generateOrderId() {
    return 'UVM-' . strtoupper(substr(generateToken(4), 0, 7));
}

// ===== FONCTIONS DE FORMATAGE =====

function formatPrice($price, $devise = 'FC') {
    if ($devise === 'USD') {
        return '$ ' . number_format($price, 2);
    }
    return number_format($price, 0, ',', ' ') . ' FC';
}

function formatDate($date) {
    if (!$date) return '';
    $dt = new DateTime($date);
    return $dt->format('d/m/Y');
}

function formatDateTime($date) {
    if (!$date) return '';
    $dt = new DateTime($date);
    return $dt->format('d/m/Y H:i');
}

// ===== FONCTIONS DE VALIDATION =====

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^\+?[0-9]{8,15}$/', $phone);
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

// ===== FONCTIONS UTILITAIRES =====

function getCategories() {
    return fetchAll("SELECT * FROM categories ORDER BY nom");
}

function getCategoryName($id) {
    $cat = fetchOne("SELECT nom FROM categories WHERE id = ?", [$id]);
    return $cat ? $cat['nom'] : '';
}

function getUserById($id) {
    return fetchOne("SELECT id, nom, email, role, photo, telephone, quartier, avenue FROM users WHERE id = ? AND actif = 1", [$id]);
}

function getVendeurById($id) {
    return fetchOne("SELECT * FROM vendeurs WHERE id = ?", [$id]);
}

function getLivreurById($id) {
    return fetchOne("SELECT * FROM livreurs WHERE id = ?", [$id]);
}

// ===== GESTION DES IMAGES =====

function saveImage($base64Data, $folder = 'produits') {
    if (empty($base64Data)) return null;
    
    // Vérifier si c'est du base64
    if (strpos($base64Data, 'data:image') === 0) {
        // Extraire le type et les données
        list($type, $data) = explode(';', $base64Data);
        list(, $extension) = explode('/', $type);
        list(, $data) = explode(',', $data);
        
        // Décoder les données
        $data = base64_decode($data);
        if ($data === false) return null;
        
        // Générer un nom de fichier unique
        $filename = uniqid() . '.' . $extension;
        $path = "../uploads/{$folder}/";
        
        // Créer le dossier si nécessaire
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        
        // Sauvegarder le fichier
        $fullPath = $path . $filename;
        file_put_contents($fullPath, $data);
        
        // Retourner le chemin relatif
        return "uploads/{$folder}/" . $filename;
    }
    
    return $base64Data;
}

// ===== MIDDLEWARE =====

function requireLogin() {
    if (!isLoggedIn()) {
        sendUnauthorized('Veuillez vous connecter');
    }
}

function requireRole($roles) {
    requireLogin();
    $role = getCurrentUserRole();
    if (!in_array($role, (array)$roles)) {
        sendForbidden('Accès réservé aux ' . implode(' ou ', (array)$roles));
    }
}

function requireAdmin() {
    requireRole('admin');
}

function requireVendeur() {
    requireRole(['vendeur', 'admin']);
}

function requireVendeurOrAdmin() {
    requireRole(['vendeur', 'admin']);
}
?>