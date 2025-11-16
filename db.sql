-- Création de la base de données
CREATE DATABASE IF NOT EXISTS chaussure_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chaussure_shop;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    actif BOOLEAN DEFAULT TRUE
);

-- Table des catégories
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    actif BOOLEAN DEFAULT TRUE
);

-- Table des produits
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    prix_original DECIMAL(10,2) NULL,
    category_id INT,
    genre ENUM('homme', 'femme', 'enfant') NOT NULL,
    tailles JSON, -- Stockage des tailles disponibles
    couleurs JSON, -- Stockage des couleurs disponibles
    stock_total INT DEFAULT 0,
    image_principale VARCHAR(255),
    images_secondaires JSON, -- Stockage des images supplémentaires
    note_moyenne DECIMAL(3,2) DEFAULT 0,
    nombre_avis INT DEFAULT 0,
    en_promotion BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    actif BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Table de stock détaillé par taille/couleur
CREATE TABLE stock_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    taille VARCHAR(10) NOT NULL,
    couleur VARCHAR(50) NOT NULL,
    quantite INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_size_color (product_id, taille, couleur)
);

-- Table des commandes
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    numero_commande VARCHAR(50) UNIQUE NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    statut ENUM('en_attente', 'confirmee', 'en_preparation', 'expediee', 'livree', 'annulee') DEFAULT 'en_attente',
    mode_paiement ENUM('lumicash', 'ecocash', 'bancobu', 'bcb', 'carte') NOT NULL,
    adresse_livraison TEXT NOT NULL,
    telephone_livraison VARCHAR(20) NOT NULL,
    notes TEXT,
    date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des détails de commande
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    nom_produit VARCHAR(255) NOT NULL, -- Sauvegarde du nom au moment de la commande
    prix_unitaire DECIMAL(10,2) NOT NULL, -- Prix au moment de la commande
    taille VARCHAR(10) NOT NULL,
    couleur VARCHAR(50) NOT NULL,
    quantite INT NOT NULL,
    sous_total DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Table du panier
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    taille VARCHAR(10) NOT NULL,
    couleur VARCHAR(50) NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_size_color (user_id, product_id, taille, couleur)
);

-- Table des favoris/wishlist
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- Table des avis produits
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    note INT CHECK (note >= 1 AND note <= 5),
    commentaire TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approuve BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_review (user_id, product_id)
);

-- Table des sessions utilisateur
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    data TEXT,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insertion des catégories par défaut
INSERT INTO categories (nom, description) VALUES
('Sport', 'Chaussures de sport et running'),
('Casual', 'Chaussures décontractées pour tous les jours'),
('Formal', 'Chaussures élégantes pour occasions spéciales'),
('Boots', 'Bottes et bottines');

-- Insertion des produits d'exemple
INSERT INTO products (nom, description, prix, prix_original, category_id, genre, tailles, couleurs, stock_total, image_principale, note_moyenne, nombre_avis, en_promotion) VALUES
('Nike Air Max 90', 'Chaussures de sport confortables avec technologie Air Max', 129.99, 149.99, 1, 'homme', '["39", "40", "41", "42", "43", "44", "45"]', '["Noir", "Blanc", "Rouge"]', 50, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&h=250&fit=crop', 4.5, 23, TRUE),

('Adidas Ultraboost', 'Chaussures de running haute performance', 180.00, NULL, 1, 'femme', '["36", "37", "38", "39", "40", "41", "42"]', '["Rose", "Noir", "Blanc"]', 30, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=300&h=250&fit=crop', 4.8, 45, FALSE),

('Converse Chuck Taylor', 'Baskets iconiques en toile', 65.00, NULL, 2, 'homme', '["39", "40", "41", "42", "43", "44"]', '["Noir", "Blanc", "Rouge", "Bleu"]', 75, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=300&h=250&fit=crop', 4.2, 67, FALSE),

('Timberland Boots', 'Bottes robustes en cuir imperméable', 220.00, 250.00, 4, 'homme', '["40", "41", "42", "43", "44", "45", "46"]', '["Marron", "Noir"]', 25, 'https://images.unsplash.com/photo-1544966503-7cc5ac882d5f?w=300&h=250&fit=crop', 4.6, 34, TRUE),

('Vans Old Skool', 'Chaussures de skate classiques', 75.00, NULL, 2, 'femme', '["36", "37", "38", "39", "40", "41"]', '["Noir", "Blanc", "Rouge"]', 40, 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=300&h=250&fit=crop', 4.3, 28, FALSE),

('Louboutin Heels', 'Escarpins de luxe à semelle rouge', 890.00, NULL, 3, 'femme', '["36", "37", "38", "39", "40", "41"]', '["Noir", "Rouge", "Nude"]', 15, 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=300&h=250&fit=crop', 4.9, 12, FALSE),

('Dr. Martens 1460', 'Bottes en cuir avec semelle coussinée d\'air', 169.00, 189.00, 4, 'homme', '["39", "40", "41", "42", "43", "44", "45"]', '["Noir", "Marron"]', 35, 'https://images.unsplash.com/photo-1608667508764-33cf0726f7c6?w=300&h=250&fit=crop', 4.4, 56, TRUE),

('Ballerines Chanel', 'Ballerines élégantes en cuir matelassé', 650.00, NULL, 3, 'femme', '["36", "37", "38", "39", "40", "41"]', '["Noir", "Beige", "Rouge"]', 20, 'https://images.unsplash.com/photo-1535043934128-cf0b28d52f95?w=300&h=250&fit=crop', 4.7, 18, FALSE),

('Baskets Enfant', 'Chaussures colorées pour enfants', 45.00, NULL, 1, 'enfant', '["28", "29", "30", "31", "32", "33", "34", "35"]', '["Rose", "Bleu", "Vert", "Rouge"]', 60, 'https://images.unsplash.com/photo-1514989940723-e8e51635b782?w=300&h=250&fit=crop', 4.1, 22, FALSE);

-- Insertion des détails de stock
INSERT INTO stock_details (product_id, taille, couleur, quantite) VALUES
-- Nike Air Max 90
(1, '40', 'Noir', 5), (1, '41', 'Noir', 8), (1, '42', 'Noir', 7),
(1, '40', 'Blanc', 4), (1, '41', 'Blanc', 6), (1, '42', 'Blanc', 5),
(1, '40', 'Rouge', 3), (1, '41', 'Rouge', 4), (1, '42', 'Rouge', 8),

-- Adidas Ultraboost
(2, '37', 'Rose', 5), (2, '38', 'Rose', 6), (2, '39', 'Rose', 4),
(2, '37', 'Noir', 3), (2, '38', 'Noir', 5), (2, '39', 'Noir', 7),

-- Converse Chuck Taylor
(3, '40', 'Noir', 10), (3, '41', 'Noir', 12), (3, '42', 'Noir', 8),
(3, '40', 'Blanc', 9), (3, '41', 'Blanc', 11), (3, '42', 'Blanc', 10),
(3, '40', 'Rouge', 6), (3, '41', 'Rouge', 8), (3, '42', 'Rouge', 5);

-- Création des index pour optimiser les performances
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_genre ON products(genre);
CREATE INDEX idx_products_prix ON products(prix);
CREATE INDEX idx_products_actif ON products(actif);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_statut ON orders(statut);
CREATE INDEX idx_cart_user ON cart(user_id);
CREATE INDEX idx_wishlist_user ON wishlist(user_id);
CREATE INDEX idx_reviews_product ON reviews(product_id);

-- Création des triggers pour mettre à jour les moyennes des notes
DELIMITER //

CREATE TRIGGER update_product_rating_after_insert 
AFTER INSERT ON reviews 
FOR EACH ROW 
BEGIN
    UPDATE products 
    SET note_moyenne = (
        SELECT AVG(note) 
        FROM reviews 
        WHERE product_id = NEW.product_id AND approuve = TRUE
    ),
    nombre_avis = (
        SELECT COUNT(*) 
        FROM reviews 
        WHERE product_id = NEW.product_id AND approuve = TRUE
    )
    WHERE id = NEW.product_id;
END//

CREATE TRIGGER update_product_rating_after_update 
AFTER UPDATE ON reviews 
FOR EACH ROW 
BEGIN
    UPDATE products 
    SET note_moyenne = (
        SELECT AVG(note) 
        FROM reviews 
        WHERE product_id = NEW.product_id AND approuve = TRUE
    ),
    nombre_avis = (
        SELECT COUNT(*) 
        FROM reviews 
        WHERE product_id = NEW.product_id AND approuve = TRUE
    )
    WHERE id = NEW.product_id;
END//

CREATE TRIGGER update_product_rating_after_delete 
AFTER DELETE ON reviews 
FOR EACH ROW 
BEGIN
    UPDATE products 
    SET note_moyenne = COALESCE((
        SELECT AVG(note) 
        FROM reviews 
        WHERE product_id = OLD.product_id AND approuve = TRUE
    ), 0),
    nombre_avis = (
        SELECT COUNT(*) 
        FROM reviews 
        WHERE product_id = OLD.product_id AND approuve = TRUE
    )
    WHERE id = OLD.product_id;
END//

DELIMITER ;

-- Création d'un utilisateur pour l'application
CREATE USER IF NOT EXISTS 'chaussure_user'@'localhost' IDENTIFIED BY 'ChaussureShop2025!';
GRANT SELECT, INSERT, UPDATE, DELETE ON chaussure_shop.* TO 'chaussure_user'@'localhost';
GRANT EXECUTE ON chaussure_shop.* TO 'chaussure_user'@'localhost';
FLUSH PRIVILEGES;