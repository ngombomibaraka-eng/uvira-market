<?php
/**
 * Routes d'authentification
 */

// ===== INSCRIPTION =====
function handleRegister($data) {
    $nom = trim($data['nom'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'acheteur';
    $telephone = trim($data['telephone'] ?? '');
    
    // Validation
    if (empty($nom) || empty($email) || empty($password)) {
        sendError('Nom, email et mot de passe requis');
    }
    
    if (!validateEmail($email)) {
        sendError('Email invalide');
    }
    
    if (!validatePassword($password)) {
        sendError('Mot de passe minimum 6 caractères');
    }
    
    $pdo = getDB();
    
    // Vérifier si l'email existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        sendError('Cet email est déjà utilisé');
    }
    
    // Hasher le mot de passe
    $passwordHash = hashPassword($password);
    
    // Insérer l'utilisateur
    $stmt = $pdo->prepare("
        INSERT INTO users (nom, email, password_hash, role, telephone, date_inscription)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$nom, $email, $passwordHash, $role, $telephone]);
    $userId = lastInsertId();
    
    // Si vendeur, créer le profil vendeur
    if ($role === 'vendeur') {
        $boutique = trim($data['boutique'] ?? 'Boutique de ' . $nom);
        $devise = $data['devise'] ?? 'FC';
        $stmt = $pdo->prepare("INSERT INTO vendeurs (id, boutique, devise, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $boutique, $devise, $userId]);
    }
    
    // Créer la session
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_nom'] = $nom;
    $_SESSION['user_role'] = $role;
    
    sendSuccess([
        'user' => [
            'id' => $userId,
            'nom' => $nom,
            'email' => $email,
            'role' => $role,
            'telephone' => $telephone
        ]
    ]);
}

// ===== CONNEXION =====
function handleLogin($data) {
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendError('Email et mot de passe requis');
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("
        SELECT id, nom, email, password_hash, role, photo, telephone, quartier, avenue, adresse, actif
        FROM users WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || $user['actif'] == 0) {
        sendError('Email ou mot de passe incorrect', 401);
    }
    
    if (!verifyPassword($password, $user['password_hash'])) {
        sendError('Email ou mot de passe incorrect', 401);
    }
    
    // Créer la session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nom'] = $user['nom'];
    $_SESSION['user_role'] = $user['role'];
    
    sendSuccess([
        'user' => [
            'id' => $user['id'],
            'nom' => $user['nom'],
            'email' => $user['email'],
            'role' => $user['role'],
            'photo' => $user['photo'],
            'telephone' => $user['telephone'],
            'quartier' => $user['quartier'],
            'avenue' => $user['avenue'],
            'adresse' => $user['adresse']
        ]
    ]);
}

// ===== DÉCONNEXION =====
function handleLogout() {
    session_destroy();
    sendSuccess(null, 'Déconnecté');
}

// ===== INFORMATIONS UTILISATEUR =====
function handleMe() {
    requireLogin();
    $user = getCurrentUser();
    if (!$user) {
        session_destroy();
        sendError('Utilisateur non trouvé', 404);
    }
    
    sendSuccess([
        'id' => $user['id'],
        'nom' => $user['nom'],
        'email' => $user['email'],
        'role' => $user['role'],
        'photo' => $user['photo'],
        'telephone' => $user['telephone'],
        'adresse' => $user['adresse'],
        'quartier' => $user['quartier'],
        'avenue' => $user['avenue'],
        'province' => $user['province'],
        'territoire' => $user['territoire'],
        'sexe' => $user['sexe'],
        'date_inscription' => $user['date_inscription']
    ]);
}

// ===== MISE À JOUR DU PROFIL =====
function handleUpdateProfile($data) {
    requireLogin();
    $userId = getCurrentUserId();
    $pdo = getDB();
    
    $updates = [];
    $params = [];
    
    $fields = ['nom', 'telephone', 'adresse', 'quartier', 'avenue', 'province', 'territoire', 'sexe'];
    foreach ($fields as $field) {
        if (isset($data[$field]) && !empty($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = trim($data[$field]);
        }
    }
    
    if (isset($data['photo']) && !empty($data['photo'])) {
        $updates[] = "photo = ?";
        $params[] = $data['photo'];
    }
    
    if (empty($updates)) {
        sendSuccess(null, 'Aucune modification');
    }
    
    $params[] = $userId;
    $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
    $stmt->execute($params);
    
    sendSuccess();
}

// ===== CHANGEMENT DE MOT DE PASSE =====
function handleChangePassword($data) {
    requireLogin();
    $userId = getCurrentUserId();
    $oldPassword = $data['old_password'] ?? '';
    $newPassword = $data['new_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword) || strlen($newPassword) < 6) {
        sendError('Mot de passe invalide');
    }
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($oldPassword, $user['password_hash'])) {
        sendError('Ancien mot de passe incorrect', 401);
    }
    
    $newHash = hashPassword($newPassword);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newHash, $userId]);
    
    sendSuccess();
}
?>