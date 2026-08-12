-- =====================================================
-- BASE DE DONNÉES : UviraMarket
-- =====================================================

CREATE DATABASE IF NOT EXISTS uviramarket;
USE uviramarket;

-- =====================================================
-- Table: users
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('acheteur', 'vendeur', 'admin') DEFAULT 'acheteur',
    photo LONGTEXT,
    telephone VARCHAR(20),
    adresse TEXT,
    quartier VARCHAR(100),
    avenue VARCHAR(100),
    province VARCHAR(100),
    territoire VARCHAR(100),
    sexe ENUM('M', 'F'),
    actif TINYINT(1) DEFAULT 1,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- Table: vendeurs
-- =====================================================
CREATE TABLE IF NOT EXISTS vendeurs (
    id INT PRIMARY KEY,
    boutique VARCHAR(100),
    devise ENUM('FC', 'USD') DEFAULT 'FC',
    note DECIMAL(3,2) DEFAULT 0,
    ventes INT DEFAULT 0,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- Table: livreurs
-- =====================================================
CREATE TABLE IF NOT EXISTS livreurs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    photo LONGTEXT,
    disponible TINYINT(1) DEFAULT 1
);

-- =====================================================
-- Table: categories
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(50),
    color VARCHAR(20)
);

-- =====================================================
-- Table: produits
-- =====================================================
CREATE TABLE IF NOT EXISTS produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    categorie_id INT,
    prix DECIMAL(10,2) NOT NULL,
    prix_promo DECIMAL(10,2) DEFAULT NULL,
    unite VARCHAR(20),
    description TEXT,
    devise ENUM('FC', 'USD') DEFAULT 'FC',
    images LONGTEXT,
    vendeur_id INT,
    stock INT DEFAULT 0,
    note DECIMAL(3,2) DEFAULT 0,
    nb_avis INT DEFAULT 0,
    date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
    en_promotion TINYINT(1) DEFAULT 0,
    ventes INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (categorie_id) REFERENCES categories(id),
    FOREIGN KEY (vendeur_id) REFERENCES users(id)
);

-- =====================================================
-- Table: commandes
-- =====================================================
CREATE TABLE IF NOT EXISTS commandes (
    id VARCHAR(20) PRIMARY KEY,
    acheteur_id INT,
    vendeur_id INT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en_attente', 'confirmee', 'en_preparation', 'en_livraison', 'livree', 'annulee') DEFAULT 'en_attente',
    livraison_quartier VARCHAR(100),
    livraison_avenue VARCHAR(100),
    livraison_adresse TEXT,
    livraison_note TEXT,
    livreur_id INT,
    total_fc DECIMAL(10,2) DEFAULT 0,
    total_usd DECIMAL(10,2) DEFAULT 0,
    FOREIGN KEY (acheteur_id) REFERENCES users(id),
    FOREIGN KEY (vendeur_id) REFERENCES users(id),
    FOREIGN KEY (livreur_id) REFERENCES livreurs(id)
);

-- =====================================================
-- Table: commande_articles
-- =====================================================
CREATE TABLE IF NOT EXISTS commande_articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    commande_id VARCHAR(20),
    produit_id INT,
    nom VARCHAR(100),
    qty INT,
    prix DECIMAL(10,2),
    devise ENUM('FC', 'USD'),
    unite VARCHAR(20),
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id)
);

-- =====================================================
-- Table: messages
-- =====================================================
CREATE TABLE IF NOT EXISTS messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conv_id VARCHAR(50) NOT NULL,
    type ENUM('prive', 'commande') DEFAULT 'prive',
    order_id VARCHAR(20),
    from_id INT,
    from_nom VARCHAR(100),
    from_photo LONGTEXT,
    participants LONGTEXT,
    texte TEXT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    lu LONGTEXT,
    system TINYINT(1) DEFAULT 0,
    media_type ENUM('image', 'video', 'audio') DEFAULT NULL,
    media_data LONGTEXT,
    FOREIGN KEY (from_id) REFERENCES users(id)
);

-- =====================================================
-- Table: notifications
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    texte TEXT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    lu TINYINT(1) DEFAULT 0,
    icone VARCHAR(50) DEFAULT 'fa-bell',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- Table: publicites
-- =====================================================
CREATE TABLE IF NOT EXISTS publicites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(100) NOT NULL,
    texte TEXT,
    image LONGTEXT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) DEFAULT 1
);

-- =====================================================
-- Table: favoris
-- =====================================================
CREATE TABLE IF NOT EXISTS favoris (
    user_id INT,
    produit_id INT,
    date DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, produit_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- =====================================================
-- Table: panier
-- =====================================================
CREATE TABLE IF NOT EXISTS panier (
    user_id INT,
    produit_id INT,
    qty INT DEFAULT 1,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, produit_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- =====================================================
-- DONNÉES INITIALES
-- =====================================================

-- Admin par défaut (mot de passe: Admin@2026)
-- Pour générer le hash: password_hash('Admin@2026', PASSWORD_DEFAULT)
INSERT INTO users (id, nom, email, password_hash, role, actif) VALUES 
(1, 'Administrateur', 'admin@uviramarket.com', '$2y$10$YourHashedPasswordHere', 'admin', 1);

-- Catégories
INSERT IGNORE INTO categories (id, nom, icon, color) VALUES
(1, 'Agriculture', 'fa-seedling', '#2D6A4F'),
(2, 'Élevage', 'fa-cow', '#8B6914'),
(3, 'Pêche', 'fa-fish', '#1E6091'),
(4, 'Alimentaire', 'fa-jar', '#C45D2C'),
(5, 'Mode', 'fa-shirt', '#9B2335'),
(6, 'Chaussures', 'fa-shoe-prints', '#5C4D7D'),
(7, 'Accessoires', 'fa-gem', '#D4A017'),
(8, 'Électronique', 'fa-mobile-screen', '#34495E'),
(9, 'Maison', 'fa-couch', '#7D6B5D'),
(10, 'Artisanat', 'fa-hands', '#A0522D'),
(11, 'Services', 'fa-wrench', '#2C3E50');

-- Vendeurs par défaut (mot de passe: password123)
INSERT IGNORE INTO users (id, nom, email, password_hash, sexe, telephone, province, territoire, quartier, avenue, photo, role, actif) VALUES
(100, 'Marie Mwamba', 'vendeur100@uviramarket.com', '$2y$10$YourHashedPasswordHere', 'F', '+243991234567', 'Sud-Kivu', 'Uvira', 'Market', 'Kasuku', 'https://picsum.photos/seed/v1f/100/100', 'vendeur', 1),
(101, 'Jean-Pierre Kahindo', 'vendeur101@uviramarket.com', '$2y$10$YourHashedPasswordHere', 'M', '+243992345678', 'Sud-Kivu', 'Uvira', 'Majengo', 'Kasuku', 'https://picsum.photos/seed/v2f/100/100', 'vendeur', 1),
(102, 'Fatuma Habari', 'vendeur102@uviramarket.com', '$2y$10$YourHashedPasswordHere', 'F', '+243993456789', 'Sud-Kivu', 'Uvira', 'Kasuku', 'Centre-ville', 'https://picsum.photos/seed/v3f/100/100', 'vendeur', 1),
(103, 'Pascal Lushima', 'vendeur103@uviramarket.com', '$2y$10$YourHashedPasswordHere', 'M', '+243994567890', 'Sud-Kivu', 'Uvira', 'Kabindula', 'Mulongwe', 'https://picsum.photos/seed/v4f/100/100', 'vendeur', 1),
(104, 'Grace Mugoli', 'vendeur104@uviramarket.com', '$2y$10$YourHashedPasswordHere', 'F', '+243995678901', 'Sud-Kivu', 'Uvira', 'Centre-ville', 'Kasuku', 'https://picsum.photos/seed/v5f/100/100', 'vendeur', 1),
(105, 'David Bizimana', 'vendeur105@uviramarket.com', '$2y$10$YourHashedPasswordHere', 'M', '+243996789012', 'Sud-Kivu', 'Uvira', 'Ndeke', 'Kimanga', 'https://picsum.photos/seed/v6f/100/100', 'vendeur', 1),
(106, 'Amina Nsimba', 'vendeur106@uviramarket.com', '$2y$10$YourHashedPasswordHere', 'F', '+243997890123', 'Sud-Kivu', 'Uvira', 'Kimanga', 'Puisse', 'https://picsum.photos/seed/v7f/100/100', 'vendeur', 1),
(107, 'Cédric Mubalama', 'vendeur107@uviramarket.com', '$2y$10$YourHashedPasswordHere', 'M', '+243998901234', 'Sud-Kivu', 'Uvira', 'Puisse', 'Kabindula', 'https://picsum.photos/seed/v8f/100/100', 'vendeur', 1);

-- Profils vendeurs
INSERT IGNORE INTO vendeurs (id, boutique, devise, note, ventes, user_id) VALUES
(100, 'Marie Productions', 'FC', 4.7, 234, 100),
(101, 'JP Agro', 'FC', 4.5, 189, 101),
(102, 'Fatuma Pêche', 'FC', 4.8, 312, 102),
(103, 'Lushima Élevage', 'FC', 4.3, 156, 103),
(104, 'Grace Mode', 'FC', 4.9, 445, 104),
(105, 'David Tech', 'USD', 4.2, 98, 105),
(106, 'Amina Artisanat', 'FC', 4.6, 267, 106),
(107, 'Mubalama Alimentaire', 'FC', 4.4, 178, 107);

-- Livreurs
INSERT IGNORE INTO livreurs (id, nom, telephone, photo, disponible) VALUES
(201, 'David Lwanga', '+243993333333', 'https://picsum.photos/seed/liv1/100/100', 1),
(202, 'Patrick Mupenda', '+243994444444', 'https://picsum.photos/seed/liv2/100/100', 1);

-- Publicités
INSERT IGNORE INTO publicites (titre, texte, image, active) VALUES
('Offre spéciale : -20% sur toute la mode', 'Profitez de réductions incroyables chez Grace Mode.', 'https://picsum.photos/seed/ad1/800/300', 1),
('Livraison gratuite dès 5 000 FC', 'UviraMarket offre la livraison gratuite pour toute commande supérieure à 5 000 FC.', 'https://picsum.photos/seed/ad2/800/300', 1);