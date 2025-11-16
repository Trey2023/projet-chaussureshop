<?php
session_start();

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'chaussure_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion PDO
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }
    return $pdo;
}

/* ------------------- AUTHENTIFICATION ------------------- */
function login($email, $password) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email AND actif = 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_nom'] = $user['nom'];
        $_SESSION['user_prenom'] = $user['prenom'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    return false;
}

function register($nom, $prenom, $email, $password, $telephone = null) {
    $pdo = getDB();
    
    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        return false; // Email déjà utilisé
    }
    
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password_hash, telephone) VALUES (:nom, :prenom, :email, :password_hash, :telephone)");
    return $stmt->execute([
        'nom' => $nom,
        'prenom' => $prenom,
        'email' => $email,
        'password_hash' => $passwordHash,
        'telephone' => $telephone
    ]);
}

function logout() {
    session_destroy();
    header('Location: index.php');
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

/* ------------------- PRODUITS ------------------- */
function getProducts($filters = []) {
    $pdo = getDB();
    $sql = "SELECT p.*, c.nom as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.actif = 1";
    $params = [];

    if (!empty($filters['search'])) {
        $sql .= " AND p.nom LIKE :search";
        $params['search'] = "%" . $filters['search'] . "%";
    }

    if (!empty($filters['category'])) {
        $sql .= " AND c.nom = :category";
        $params['category'] = $filters['category'];
    }

    if (!empty($filters['genre'])) {
        $sql .= " AND p.genre = :genre";
        $params['genre'] = $filters['genre'];
    }

    if (!empty($filters['price_min']) && !empty($filters['price_max'])) {
        $sql .= " AND p.prix BETWEEN :price_min AND :price_max";
        $params['price_min'] = $filters['price_min'];
        $params['price_max'] = $filters['price_max'];
    }

    if (!empty($filters['sort'])) {
        switch ($filters['sort']) {
            case 'price-asc': $sql .= " ORDER BY p.prix ASC"; break;
            case 'price-desc': $sql .= " ORDER BY p.prix DESC"; break;
            case 'name': $sql .= " ORDER BY p.nom ASC"; break;
            case 'rating': $sql .= " ORDER BY p.note_moyenne DESC"; break;
            default: $sql .= " ORDER BY p.date_creation DESC";
        }
    } else {
        $sql .= " ORDER BY p.date_creation DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getProductById($id) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT p.*, c.nom as category_name 
                           FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.id = :id AND p.actif = 1");
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

/* ------------------- PANIER ------------------- */
function addToCart($userId, $productId, $taille, $couleur, $quantite = 1) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, taille, couleur, quantite) 
                           VALUES (:user_id, :product_id, :taille, :couleur, :quantite)
                           ON DUPLICATE KEY UPDATE quantite = quantite + :quantite2");
    return $stmt->execute([
        'user_id' => $userId,
        'product_id' => $productId,
        'taille' => $taille,
        'couleur' => $couleur,
        'quantite' => $quantite,
        'quantite2' => $quantite
    ]);
}

function getCartCount($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT SUM(quantite) as total FROM cart WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function clearCart($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = :user_id");
    return $stmt->execute(['user_id' => $userId]);
}

/* ------------------- FAVORIS ------------------- */
function addToWishlist($userId, $productId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (:user_id, :product_id)");
    return $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
}

function removeFromWishlist($userId, $productId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
    return $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
}

function getWishlistItems($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT w.*, p.nom, p.prix, p.image_principale, p.note_moyenne 
                           FROM wishlist w 
                           JOIN products p ON w.product_id = p.id 
                           WHERE w.user_id = :user_id AND p.actif = 1");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function isInWishlist($userId, $productId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
    $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
    return $stmt->fetchColumn() > 0;
}

function getWishlistCount($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchColumn();
}

/* ------------------- COMMANDES ------------------- */
function createOrder($userId, $items, $modePaiement, $adresseLivraison, $telephoneLivraison, $notes = null) {
    $pdo = getDB();
    
    try {
        $pdo->beginTransaction();
        
        // Générer numéro de commande
        $numeroCommande = 'CMD-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        
        // Calculer le total
        $total = 0;
        foreach ($items as $item) {
            $total += $item['prix'] * $item['quantite'];
        }
        
        // Insérer la commande
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, numero_commande, total, mode_paiement, adresse_livraison, telephone_livraison, notes) 
                               VALUES (:user_id, :numero_commande, :total, :mode_paiement, :adresse_livraison, :telephone_livraison, :notes)");
        $stmt->execute([
            'user_id' => $userId,
            'numero_commande' => $numeroCommande,
            'total' => $total,
            'mode_paiement' => $modePaiement,
            'adresse_livraison' => $adresseLivraison,
            'telephone_livraison' => $telephoneLivraison,
            'notes' => $notes
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insérer les items de commande
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, nom_produit, prix_unitaire, taille, couleur, quantite, sous_total) 
                               VALUES (:order_id, :product_id, :nom_produit, :prix_unitaire, :taille, :couleur, :quantite, :sous_total)");
        
        foreach ($items as $item) {
            $sousTotal = $item['prix'] * $item['quantite'];
            $stmt->execute([
                'order_id' => $orderId,
                'product_id' => $item['product_id'],
                'nom_produit' => $item['nom'],
                'prix_unitaire' => $item['prix'],
                'taille' => $item['taille'],
                'couleur' => $item['couleur'],
                'quantite' => $item['quantite'],
                'sous_total' => $sousTotal
            ]);
        }
        
        // Vider le panier
        clearCart($userId);
        
        $pdo->commit();
        return $numeroCommande;
        
    } catch (Exception $e) {
        $pdo->rollback();
        return false;
    }
}

function getUserOrders($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY date_commande DESC");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function getOrderItems($orderId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmt->execute(['order_id' => $orderId]);
    return $stmt->fetchAll();
}

function getOrderByNumber($numeroCommande, $userId = null) {
    $pdo = getDB();
    $sql = "SELECT * FROM orders WHERE numero_commande = :numero_commande";
    $params = ['numero_commande' => $numeroCommande];
    
    if ($userId !== null) {
        $sql .= " AND user_id = :user_id";
        $params['user_id'] = $userId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

/* ------------------- UTILITAIRES ------------------- */
function generateStars($rating) {
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;
    $stars = '';
    for ($i = 0; $i < $fullStars; $i++) $stars .= '<i class="fas fa-star"></i>';
    if ($hasHalfStar) $stars .= '<i class="fas fa-star-half-alt"></i>';
    $emptyStars = 5 - ceil($rating);
    for ($i = 0; $i < $emptyStars; $i++) $stars .= '<i class="far fa-star"></i>';
    return $stars;
}

function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' BIF';
}

/* ------------------- AJAX HANDLERS ------------------- */
if (isset($_POST['action']) && isLoggedIn()) {
    header('Content-Type: application/json');
    $userId = $_SESSION['user_id'];

    switch ($_POST['action']) {
        case 'add_to_cart':
            $result = addToCart($userId, $_POST['product_id'], $_POST['taille'] ?? '42', $_POST['couleur'] ?? 'Noir', $_POST['quantite'] ?? 1);
            echo json_encode(['success' => $result]);
            exit;

        case 'update_cart':
            $result = updateCartQuantity($userId, $_POST['product_id'], $_POST['taille'], $_POST['couleur'], $_POST['quantite']);
            echo json_encode(['success' => $result]);
            exit;

        case 'remove_from_cart':
            $result = removeFromCart($userId, $_POST['product_id'], $_POST['taille'], $_POST['couleur']);
            echo json_encode(['success' => $result]);
            exit;

        case 'get_cart':
            $items = getCartItems($userId);
            $count = getCartCount($userId);
            echo json_encode(['items' => $items, 'count' => $count]);
            exit;

        case 'toggle_wishlist':
            $productId = $_POST['product_id'];
            if (isInWishlist($userId, $productId)) {
                $result = removeFromWishlist($userId, $productId);
                $action = 'removed';
            } else {
                $result = addToWishlist($userId, $productId);
                $action = 'added';
            }
            echo json_encode(['success' => $result, 'action' => $action]);
            exit;

        case 'get_wishlist':
            $items = getWishlistItems($userId);
            $count = getWishlistCount($userId);
            echo json_encode(['items' => $items, 'count' => $count]);
            exit;

        case 'create_order':
            $items = getCartItems($userId);
            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Panier vide']);
                exit;
            }
            
            $numeroCommande = createOrder(
                $userId, 
                $items,
                $_POST['mode_paiement'] ?? 'lumicash',
                $_POST['adresse_livraison'] ?? '',
                $_POST['telephone_livraison'] ?? '',
                $_POST['notes'] ?? ''
            );
            
            if ($numeroCommande) {
                echo json_encode(['success' => true, 'numero_commande' => $numeroCommande]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erreur lors de la création de la commande']);
            }
            exit;
    }
}
function getCartItems($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT c.*, p.nom, p.prix, p.image_principale 
                           FROM cart c 
                           JOIN products p ON c.product_id = p.id 
                           WHERE c.user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetchAll();
}

function updateCartQuantity($userId, $productId, $taille, $couleur, $quantite) {
    $pdo = getDB();
    if ($quantite <= 0) {
        return removeFromCart($userId, $productId, $taille, $couleur);
    }
    $stmt = $pdo->prepare("UPDATE cart SET quantite = :quantite 
                           WHERE user_id = :user_id AND product_id = :product_id 
                           AND taille = :taille AND couleur = :couleur");
    return $stmt->execute([
        'quantite' => $quantite,
        'user_id' => $userId,
        'product_id' => $productId,
        'taille' => $taille,
        'couleur' => $couleur
    ]);
}

function removeFromCart($userId, $productId, $taille, $couleur) {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM cart 
                           WHERE user_id = :user_id AND product_id = :product_id 
                           AND taille = :taille AND couleur = :couleur");
    return $stmt->execute([
        'user_id' => $userId,
        'product_id' => $productId,
        'taille' => $taille,
        'couleur' => $couleur
    ]);
}

