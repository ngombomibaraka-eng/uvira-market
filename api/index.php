<?php
/**
 * API Routeur Principal
 * Point d'entrée unique pour toutes les requêtes API
 */

// Headers CORS
header('Access-Control-Allow-Origin: http://localhost:5000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Réponse préflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Chargement des configurations
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Démarrer la session
startSession();

// Récupérer l'URI et la méthode
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Supprimer le préfixe /api/
$path = str_replace('/api/', '', parse_url($requestUri, PHP_URL_PATH));
$path = rtrim($path, '/');

// Décoder le corps de la requête
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    $input = [];
}

// ===== ROUTING =====

switch ($path) {
    // ===== AUTHENTIFICATION =====
    case 'auth/register':
        require_once __DIR__ . '/routes/auth.php';
        handleRegister($input);
        break;
        
    case 'auth/login':
        require_once __DIR__ . '/routes/auth.php';
        handleLogin($input);
        break;
        
    case 'auth/logout':
        require_once __DIR__ . '/routes/auth.php';
        handleLogout();
        break;
        
    case 'auth/me':
        require_once __DIR__ . '/routes/auth.php';
        handleMe();
        break;
        
    case 'auth/update':
        require_once __DIR__ . '/routes/auth.php';
        handleUpdateProfile($input);
        break;
        
    case 'auth/change-password':
        require_once __DIR__ . '/routes/auth.php';
        handleChangePassword($input);
        break;
        
    // ===== CATÉGORIES =====
    case 'categories':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/public.php';
            handleGetCategories();
        }
        break;
        
    // ===== PRODUITS =====
    case 'produits':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/produits.php';
            handleGetProduits();
        } elseif ($requestMethod === 'POST') {
            require_once __DIR__ . '/routes/produits.php';
            handleCreateProduit($input);
        }
        break;
        
    // ===== PRODUITS (avec ID) =====
    case (preg_match('/^produits\/(\d+)$/', $path, $matches) ? true : false):
        $produitId = $matches[1];
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/produits.php';
            handleGetProduit($produitId);
        } elseif ($requestMethod === 'PUT') {
            require_once __DIR__ . '/routes/produits.php';
            handleUpdateProduit($produitId, $input);
        } elseif ($requestMethod === 'DELETE') {
            require_once __DIR__ . '/routes/produits.php';
            handleDeleteProduit($produitId);
        }
        break;
        
    // ===== PANIER =====
    case 'panier':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/panier.php';
            handleGetPanier();
        } elseif ($requestMethod === 'POST') {
            require_once __DIR__ . '/routes/panier.php';
            handleAddPanier($input);
        }
        break;
        
    case 'panier/clear':
        if ($requestMethod === 'DELETE') {
            require_once __DIR__ . '/routes/panier.php';
            handleClearPanier();
        }
        break;
        
    case (preg_match('/^panier\/(\d+)$/', $path, $matches) ? true : false):
        $produitId = $matches[1];
        if ($requestMethod === 'PUT') {
            require_once __DIR__ . '/routes/panier.php';
            handleUpdatePanier($produitId, $input);
        } elseif ($requestMethod === 'DELETE') {
            require_once __DIR__ . '/routes/panier.php';
            handleRemovePanier($produitId);
        }
        break;
        
    // ===== COMMANDES =====
    case 'commandes':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/commandes.php';
            handleGetCommandes();
        } elseif ($requestMethod === 'POST') {
            require_once __DIR__ . '/routes/commandes.php';
            handleCreateCommande($input);
        }
        break;
        
    case (preg_match('/^commandes\/([A-Z0-9-]+)$/', $path, $matches) ? true : false):
        $commandeId = $matches[1];
        if ($requestMethod === 'PUT') {
            require_once __DIR__ . '/routes/commandes.php';
            handleUpdateCommande($commandeId, $input);
        } elseif ($requestMethod === 'DELETE') {
            require_once __DIR__ . '/routes/commandes.php';
            handleDeleteCommande($commandeId);
        }
        break;
        
    // ===== MESSAGES =====
    case 'messages':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/messages.php';
            handleGetMessages();
        } elseif ($requestMethod === 'POST') {
            require_once __DIR__ . '/routes/messages.php';
            handleSendMessage($input);
        }
        break;
        
    case 'messages/read':
        if ($requestMethod === 'PUT') {
            require_once __DIR__ . '/routes/messages.php';
            handleMarkRead($input);
        }
        break;
        
    case (preg_match('/^messages\/(\d+)$/', $path, $matches) ? true : false):
        $msgId = $matches[1];
        if ($requestMethod === 'DELETE') {
            require_once __DIR__ . '/routes/messages.php';
            handleDeleteMessage($msgId);
        }
        break;
        
    // ===== FAVORIS =====
    case (preg_match('/^favoris\/(\d+)$/', $path, $matches) ? true : false):
        $produitId = $matches[1];
        if ($requestMethod === 'POST') {
            require_once __DIR__ . '/routes/favoris.php';
            handleToggleFavori($produitId);
        }
        break;
        
    // ===== NOTIFICATIONS =====
    case 'notifications':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/notifications.php';
            handleGetNotifications();
        }
        break;
        
    case 'notifications/read':
        if ($requestMethod === 'PUT') {
            require_once __DIR__ . '/routes/notifications.php';
            handleMarkNotificationsRead();
        }
        break;
        
    // ===== PUBLICITÉS =====
    case 'publicites':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/public.php';
            handleGetPublicites();
        } elseif ($requestMethod === 'POST') {
            require_once __DIR__ . '/routes/admin.php';
            handleCreatePublicite($input);
        }
        break;
        
    case (preg_match('/^publicites\/(\d+)$/', $path, $matches) ? true : false):
        $pubId = $matches[1];
        if ($requestMethod === 'PUT') {
            require_once __DIR__ . '/routes/admin.php';
            handleUpdatePublicite($pubId, $input);
        } elseif ($requestMethod === 'DELETE') {
            require_once __DIR__ . '/routes/admin.php';
            handleDeletePublicite($pubId);
        }
        break;
        
    // ===== LIVREURS =====
    case 'livreurs':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/public.php';
            handleGetLivreurs();
        } elseif ($requestMethod === 'POST') {
            require_once __DIR__ . '/routes/admin.php';
            handleCreateLivreur($input);
        }
        break;
        
    // ===== ADMIN - UTILISATEURS =====
    case 'admin/users':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/admin.php';
            handleAdminGetUsers();
        }
        break;
        
    case (preg_match('/^admin\/users\/(\d+)$/', $path, $matches) ? true : false):
        $userId = $matches[1];
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/admin.php';
            handleAdminGetUser($userId);
        } elseif ($requestMethod === 'PUT') {
            require_once __DIR__ . '/routes/admin.php';
            handleAdminUpdateUser($userId, $input);
        } elseif ($requestMethod === 'DELETE') {
            require_once __DIR__ . '/routes/admin.php';
            handleAdminDeleteUser($userId);
        }
        break;
        
    // ===== STATISTIQUES =====
    case 'stats':
        if ($requestMethod === 'GET') {
            require_once __DIR__ . '/routes/admin.php';
            handleGetStats();
        }
        break;
        
    // ===== ROUTE PAR DÉFAUT =====
    default:
        sendError('Endpoint non trouvé: ' . $path, 404);
        break;
}
?>