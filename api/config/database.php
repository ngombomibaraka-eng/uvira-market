<?php
/**
 * Configuration de la base de données MySQL
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'uviramarket');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion PDO
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch(PDOException $e) {
    die("❌ Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonction pour obtenir la connexion
function getDB() {
    global $pdo;
    return $pdo;
}

// Fonction pour exécuter une requête préparée
function executeQuery($sql, $params = []) {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

// Fonction pour obtenir une ligne
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

// Fonction pour obtenir toutes les lignes
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}

// Fonction pour obtenir le dernier ID inséré
function lastInsertId() {
    $pdo = getDB();
    return $pdo->lastInsertId();
}
?>