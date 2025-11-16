-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : sam. 15 nov. 2025 à 10:43
-- Version du serveur : 10.4.28-MariaDB
-- Version de PHP : 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `chaussure_shop`
--

-- --------------------------------------------------------

--
-- Structure de la table `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `taille` varchar(10) NOT NULL,
  `couleur` varchar(50) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `taille`, `couleur`, `quantite`, `date_ajout`) VALUES
(1, 1, 2, '42', 'Noir', 3, '2025-09-23 08:07:49'),
(2, 3, 2, '42', 'Noir', 1, '2025-09-23 10:20:34'),
(4, 1, 10, '42', 'Noir', 2, '2025-11-04 16:34:48'),
(6, 5, 1, '42', 'Noir', 1, '2025-11-15 07:39:04'),
(7, 5, 2, '42', 'Noir', 1, '2025-11-15 07:39:07');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`, `image`, `actif`) VALUES
(1, 'Sport', 'Chaussures de sport et running', NULL, 1),
(2, 'Casual', 'Chaussures décontractées pour tous les jours', NULL, 1),
(3, 'Formal', 'Chaussures élégantes pour occasions spéciales', NULL, 1),
(4, 'Boots', 'Bottes et bottines', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `numero_commande` varchar(50) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `statut` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `mode_paiement` enum('lumicash','ecocash','bancobu','bcb','carte') NOT NULL,
  `adresse_livraison` text NOT NULL,
  `telephone_livraison` varchar(20) NOT NULL,
  `notes` text DEFAULT NULL,
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `numero_commande`, `total`, `statut`, `mode_paiement`, `adresse_livraison`, `telephone_livraison`, `notes`, `date_commande`, `date_modification`) VALUES
(6, 4, 'CMD-2025-41833', 650000.00, 'delivered', 'bancobu', 'campus kiriri', '65257921', '', '2025-10-20 16:27:05', '2025-11-05 13:06:13');

-- --------------------------------------------------------

--
-- Structure de la table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `nom_produit` varchar(255) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `taille` varchar(10) NOT NULL,
  `couleur` varchar(50) NOT NULL,
  `quantite` int(11) NOT NULL,
  `sous_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `nom_produit`, `prix_unitaire`, `taille`, `couleur`, `quantite`, `sous_total`) VALUES
(6, 6, 8, 'Ballerines Chanel', 650000.00, '42', 'Noir', 1, 650000.00);

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `prix` decimal(10,2) NOT NULL,
  `prix_original` decimal(10,2) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `genre` enum('homme','femme','enfant') NOT NULL,
  `tailles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tailles`)),
  `couleurs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`couleurs`)),
  `stock_total` int(11) DEFAULT 0,
  `image_principale` varchar(255) DEFAULT NULL,
  `images_secondaires` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images_secondaires`)),
  `note_moyenne` decimal(3,2) DEFAULT 0.00,
  `nombre_avis` int(11) DEFAULT 0,
  `en_promotion` tinyint(1) DEFAULT 0,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `nom`, `description`, `prix`, `prix_original`, `category_id`, `genre`, `tailles`, `couleurs`, `stock_total`, `image_principale`, `images_secondaires`, `note_moyenne`, `nombre_avis`, `en_promotion`, `date_creation`, `date_modification`, `actif`) VALUES
(1, 'Nike Air Max 90', 'Chaussures de sport confortables avec technologie Air Max', 129000.00, 149000.00, 1, 'homme', '[\"39\", \"40\", \"41\", \"42\", \"43\", \"44\", \"45\"]', '[\"Noir\", \"Blanc\", \"Rouge\"]', 50, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=300&h=250&fit=crop', NULL, 4.50, 23, 1, '2025-09-23 07:59:56', '2025-09-23 07:59:56', 1),
(2, 'Adidas Ultraboost', 'Chaussures de running haute performance', 180000.00, NULL, 1, 'femme', '[\"36\", \"37\", \"38\", \"39\", \"40\", \"41\", \"42\"]', '[\"Rose\", \"Noir\", \"Blanc\"]', 30, 'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=300&h=250&fit=crop', NULL, 4.80, 45, 0, '2025-09-23 07:59:56', '2025-09-23 07:59:56', 1),
(3, 'Converse Chuck Taylor', 'Baskets iconiques en toile', 65000.00, NULL, 2, 'homme', '[\"39\", \"40\", \"41\", \"42\", \"43\", \"44\"]', '[\"Noir\", \"Blanc\", \"Rouge\", \"Bleu\"]', 75, 'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=300&h=250&fit=crop', NULL, 4.20, 67, 0, '2025-09-23 07:59:56', '2025-09-23 07:59:56', 1),
(4, 'Timberland Boots', 'Bottes robustes en cuir imperméable', 220000.00, 250000.00, 4, 'homme', '[\"40\", \"41\", \"42\", \"43\", \"44\", \"45\", \"46\"]', '[\"Marron\", \"Noir\"]', 25, 'https://images.unsplash.com/photo-1544966503-7cc5ac882d5f?w=300&h=250&fit=crop', NULL, 4.60, 34, 1, '2025-09-23 07:59:56', '2025-09-25 05:07:26', 0),
(5, 'Vans Old Skool', 'Chaussures de skate classiques', 75000.00, NULL, 2, 'femme', '[\"36\", \"37\", \"38\", \"39\", \"40\", \"41\"]', '[\"Noir\", \"Blanc\", \"Rouge\"]', 40, 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?w=300&h=250&fit=crop', NULL, 4.30, 28, 0, '2025-09-23 07:59:56', '2025-09-23 07:59:56', 1),
(6, 'Louboutin Heels', 'Escarpins de luxe à semelle rouge', 890000.00, NULL, 3, 'femme', '[\"36\", \"37\", \"38\", \"39\", \"40\", \"41\"]', '[\"Noir\", \"Rouge\", \"Nude\"]', 15, 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=300&h=250&fit=crop', NULL, 4.90, 12, 0, '2025-09-23 07:59:56', '2025-09-23 07:59:56', 1),
(7, 'Dr. Martens 1460', 'Bottes en cuir avec semelle coussinée d\'air', 169000.00, 189000.00, 4, 'homme', '[\"39\", \"40\", \"41\", \"42\", \"43\", \"44\", \"45\"]', '[\"Noir\", \"Marron\"]', 35, 'https://images.unsplash.com/photo-1608667508764-33cf0726f7c6?w=300&h=250&fit=crop', NULL, 4.40, 56, 1, '2025-09-23 07:59:56', '2025-09-25 05:06:39', 0),
(8, 'Ballerines Chanel', 'Ballerines élégantes en cuir matelassé', 650000.00, NULL, 3, 'femme', '[\"36\", \"37\", \"38\", \"39\", \"40\", \"41\"]', '[\"Noir\", \"Beige\", \"Rouge\"]', 20, 'https://images.unsplash.com/photo-1535043934128-cf0b28d52f95?w=300&h=250&fit=crop', NULL, 4.70, 18, 0, '2025-09-23 07:59:56', '2025-09-23 07:59:56', 1),
(9, 'Baskets Enfant', 'Chaussures colorées pour enfants', 45000.00, NULL, 1, 'enfant', '[\"28\", \"29\", \"30\", \"31\", \"32\", \"33\", \"34\", \"35\"]', '[\"Rose\", \"Bleu\", \"Vert\", \"Rouge\"]', 60, 'https://images.unsplash.com/photo-1514989940723-e8e51635b782?w=300&h=250&fit=crop', NULL, 4.10, 22, 0, '2025-09-23 07:59:56', '2025-09-23 07:59:56', 1),
(10, 'Ibirato', 'Ibirato ntanguje', 50000.00, 70000.00, 4, 'homme', '[\"40\",\"41\"]', '[\"Marron\"]', 5, 'https://www.commeuncamion.com/content/uploads/2014/02/chaussures-classe-brogues-septieme-largeur.jpg', NULL, 0.00, 0, 1, '2025-09-23 08:15:40', '2025-09-23 08:15:40', 1);

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` int(11) DEFAULT NULL CHECK (`note` >= 1 and `note` <= 5),
  `commentaire` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `approuve` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déclencheurs `reviews`
--
DELIMITER $$
CREATE TRIGGER `update_product_rating_after_delete` AFTER DELETE ON `reviews` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_product_rating_after_insert` AFTER INSERT ON `reviews` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_product_rating_after_update` AFTER UPDATE ON `reviews` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `stock_details`
--

CREATE TABLE `stock_details` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `taille` varchar(10) NOT NULL,
  `couleur` varchar(50) NOT NULL,
  `quantite` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stock_details`
--

INSERT INTO `stock_details` (`id`, `product_id`, `taille`, `couleur`, `quantite`) VALUES
(1, 1, '40', 'Noir', 5),
(2, 1, '41', 'Noir', 8),
(3, 1, '42', 'Noir', 7),
(4, 1, '40', 'Blanc', 4),
(5, 1, '41', 'Blanc', 6),
(6, 1, '42', 'Blanc', 5),
(7, 1, '40', 'Rouge', 3),
(8, 1, '41', 'Rouge', 4),
(9, 1, '42', 'Rouge', 8),
(10, 2, '37', 'Rose', 5),
(11, 2, '38', 'Rose', 6),
(12, 2, '39', 'Rose', 4),
(13, 2, '37', 'Noir', 3),
(14, 2, '38', 'Noir', 5),
(15, 2, '39', 'Noir', 7),
(16, 3, '40', 'Noir', 10),
(17, 3, '41', 'Noir', 12),
(18, 3, '42', 'Noir', 8),
(19, 3, '40', 'Blanc', 9),
(20, 3, '41', 'Blanc', 11),
(21, 3, '42', 'Blanc', 10);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `code_postal` varchar(10) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `genre` enum('homme','femme','autre') DEFAULT NULL,
  `role` enum('client','admin') DEFAULT 'client',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `password_hash`, `telephone`, `adresse`, `ville`, `code_postal`, `date_naissance`, `genre`, `role`, `date_creation`, `date_modification`, `actif`) VALUES
(1, 'Admin', 'System', 'admin@chaussureshop.com', '$2y$10$ppdPLarZkLoOZX4fC.WA5.RoVK5khEuw3GXjdIY1zXZlTVLKxg7Ri', '', '', '', NULL, NULL, NULL, 'admin', '2025-09-23 07:59:55', '2025-09-23 08:11:22', 1),
(3, 'uwizeyimana', 'Emmanuel', 'emmadiblouwizeyimana@gmail.com', '$2y$10$ItT3.03kfEy6VrFAF4hNue54foDt4WUAnF78Qj5ecm5F1yaCilDmG', '65517561', 'Kiriri', 'Bujumbura', NULL, '2002-02-12', 'homme', 'client', '2025-09-23 08:09:46', '2025-09-23 09:16:32', 1),
(4, 'komezadusabe', 'Nadine', 'nadinekomezadusabe@gmail.com', '$2y$10$v1WwrWR1ZpeShmQMudQFxuEF9qe48DDNKI1KQclV8LMkyDSlVlH4O', '65257921', NULL, NULL, NULL, NULL, NULL, 'client', '2025-10-20 16:20:49', '2025-10-20 16:20:49', 1),
(5, 'mugisha', 'tresor', 'ogtrey76@gmail.com', '$2y$10$XYE9/T9VOY6TWNdFlsrB7u2uuC2nMILHG59FJ9I4MiYQcAXjl/xH.', '61778592', NULL, NULL, NULL, NULL, NULL, 'client', '2025-11-15 07:36:18', '2025-11-15 07:36:18', 1);

-- --------------------------------------------------------

--
-- Structure de la table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `data` text DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `date_ajout`) VALUES
(4, 3, 2, '2025-09-23 10:20:46'),
(5, 1, 10, '2025-10-20 16:00:13'),
(6, 4, 9, '2025-10-20 16:24:42');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_size_color` (`user_id`,`product_id`,`taille`,`couleur`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_cart_user` (`user_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_commande` (`numero_commande`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_statut` (`statut`);

--
-- Index pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_genre` (`genre`),
  ADD KEY `idx_products_prix` (`prix`),
  ADD KEY `idx_products_actif` (`actif`);

--
-- Index pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product_review` (`user_id`,`product_id`),
  ADD KEY `idx_reviews_product` (`product_id`);

--
-- Index pour la table `stock_details`
--
ALTER TABLE `stock_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_size_color` (`product_id`,`taille`,`couleur`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_wishlist_user` (`user_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `stock_details`
--
ALTER TABLE `stock_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `stock_details`
--
ALTER TABLE `stock_details`
  ADD CONSTRAINT `stock_details_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
