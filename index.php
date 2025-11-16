<?php
require_once 'config.php';

// Gestion de l'authentification
if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (login($email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $loginError = "Email ou mot de passe incorrect";
    }
}

// Filtres et recherche
$filters = [
    'search' => $_GET['search'] ?? '',
    'category' => $_GET['category'] ?? '',
    'genre' => $_GET['genre'] ?? '',
    'sort' => $_GET['sort'] ?? ''
];

if (!empty($_GET['price'])) {
    $priceRange = explode('-', $_GET['price']);
    if (count($priceRange) === 2) {
        $filters['price_min'] = (int)$priceRange[0] * 1000; // Convertir en BIF
        $filters['price_max'] = $priceRange[1] === '+' ? 999999999 : (int)$priceRange[1] * 1000;
    }
}

$products = getProducts($filters);
$cartCount = isLoggedIn() ? getCartCount($_SESSION['user_id']) : 0;
$wishlistCount = isLoggedIn() ? getWishlistCount($_SESSION['user_id']) : 0;

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChaussureShop - Votre boutique en ligne</title>
    <link rel="shortcut icon" href="favicon.svg" type="image/x-svg">
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="fontawesome/css/all.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #f39c12;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #34495e);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            position: relative;
            overflow: hidden;
        }

        .card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--secondary-color), #c0392b);
            border: none;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .price {
            color: var(--secondary-color);
            font-weight: bold;
            font-size: 1.2em;
        }

        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9em;
        }

        .badge-sale {
            background: var(--accent-color);
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }

        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .search-bar {
            border-radius: 25px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .search-bar:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
        }

        .footer {
            background: var(--primary-color);
            color: white;
            padding: 40px 0;
        }

        .rating {
            color: var(--accent-color);
        }

        .product-image {
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
        }

        .num-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-form {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            min-width: 300px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
            color: black;
        }

        .account-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            min-width: 200px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }

        .account-dropdown a:hover {
            text-decoration: underline;
        }

                .paiements {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
        }

        .paiements img {
            width: 150px;
            height: 60px;
            object-fit: cover;
            margin: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shoe-prints me-2"></i>ChaussureShop
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#accueil">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#produits">Produits</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin.php">
                            <i class="fas fa-cog me-1"></i>Administration
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="d-flex align-items-center">
                    <?php if (!isLoggedIn()): ?>
                    <!-- Login Button -->
                    <div class="position-relative me-3">
                        <button class="btn btn-light" onclick="toggleLogin()">
                            <i class="fas fa-user"></i>
                            <span class="ms-1">Connexion</span>
                        </button>
                        <div class="login-form" id="loginForm">
                            <div class="p-4">
                                <h6 class="mb-3">Connexion</h6>
                                <?php if (isset($loginError)): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
                                <?php endif; ?>
                                <form method="post" autocomplete="off">
                                    <div class="mb-3">
                                        <input type="email" class="form-control" name="email" placeholder="Email" readonly onfocus="this.removeAttribute('readonly');" required>
                                    </div>
                                    <div class="mb-3">
                                        <input type="password" id="password" class="form-control" name="password" placeholder="Mot de passe" readonly onfocus="this.removeAttribute('readonly');" required>
                                        <input type="checkbox" name="" id="tooglepass"> <label for="tooglepass"> <span id="tooglepass-text"></span></label>
                                    </div>
                                    <button type="submit" name="login" class="btn btn-primary w-100 mb-2">Se connecter</button>
                                    <a href="./register" class="btn btn-outline-secondary w-100">S'inscrire</a>
                                    <script>
                                        const tooglepass= document.getElementById('tooglepass');
                                        const input = document.getElementById('password');
                                        const tooglepassText = document.getElementById('tooglepass-text');
                                         tooglepassText.textContent="Afficher le Mot de passe"


                                        tooglepass.addEventListener('click',
                                            ()=>{
                                                if (tooglepass.checked) {
                                                 if (input.type="password") {
                                                    input.type="text"
                                                }
                                                } else{
                                                     input.type="password";
                                             }
                                              
                                            }
                                        )
                                    </script>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Account Button -->
                    <div class="position-relative me-3">
                        <button class="btn btn-outline-light" onclick="toggleAccount()">
                            <i class="fas fa-user"></i>
                            <span class="ms-1"><?= htmlspecialchars($_SESSION['user_prenom']) ?></span>
                            <i class="fas fa-chevron-down ms-1"></i>
                        </button>
                        <div class="account-dropdown" id="accountDropdown">
                            <div class="p-3">
                                <div class="mb-2">
                                   <a href="account.php" class="nav-link"> <i class="fas fa-user me-2"></i>Mon compte </a>
                                </div>
                                <div class="mb-2">
                                    <a href="account.php#orders" class="nav-link">
                                        <i class="fas fa-box me-2"></i>Mes commandes
                                    </a>
                                </div>
                                <div class="mb-2">
                                    <a href="account.php#wishlist" class="nav-link">
                                    <i class="fas fa-heart me-2"></i>Mes favoris
                                    </a>
                                </div>
                                <hr>
                                <div>
                                    <a href="logout.php" class="btn btn-primary mb-2">
                                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
    
                    <!-- Cart Button -->
                    <button class="btn btn-outline-light me-3 position-relative" onclick="toggleCart()">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="num-badge" id="cart-count"><?= $cartCount ?></span>
                    </button>
                    
                    <!-- Wishlist Button -->
                    <button class="btn btn-outline-light me-3 position-relative" onclick="showWishlist()">
                        <i class="fas fa-heart"></i> 
                        <span class="num-badge" id="wishlist-count"><?= $wishlistCount ?></span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section py-5 bg-light" id="accueil">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-4">Trouvez vos chaussures parfaites</h1>
                    <p class="lead mb-4">Découvrez notre collection exclusive de chaussures pour tous les styles et toutes les occasions.</p>
                    <a href="#produits" class="btn btn-primary btn-lg">
                        <i class="fas fa-shopping-bag me-2"></i>Découvrir
                    </a>
                </div>
                <div class="col-lg-6">
                    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner rounded shadow">

                            <div class="carousel-item active">
                                <img src="https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=300&h=250&fit=crop" class="d-block w-100" alt="Chaussure 1" style="height: 350px; object-fit: cover;">
                            </div>
                                <?php foreach ($products as $product): ?>
                            <div class="carousel-item">
                                <img src="<?= htmlspecialchars($product['image_principale']) ?>" class="d-block w-100" alt="Image de cette chaussure est n'est pas disponible: <?= htmlspecialchars($product['nom']) ?>" style="height: 350px; object-fit: cover;">
                            </div>
                                <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Précédent</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Suivant</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section class="py-5" id="produits">
        <div class="container">
            <h2 class="text-center mb-5">Nos Chaussures</h2>
            
            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" class="row">
                    <div class="col-md-4 mb-3">
                        <input type="text" class="form-control search-bar" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Rechercher une chaussure...">
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-select" name="category">
                            <option value="">Toutes catégories</option>
                            <option value="Sport" <?= $filters['category'] === 'Sport' ? 'selected' : '' ?>>Sport</option>
                            <option value="Casual" <?= $filters['category'] === 'Casual' ? 'selected' : '' ?>>Casual</option>
                            <option value="Formal" <?= $filters['category'] === 'Formal' ? 'selected' : '' ?>>Formal</option>
                            <option value="Boots" <?= $filters['category'] === 'Boots' ? 'selected' : '' ?>>Boots</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-select" name="genre">
                            <option value="">Tout genre</option>
                            <option value="homme" <?= $filters['genre'] === 'homme' ? 'selected' : '' ?>>Homme</option>
                            <option value="femme" <?= $filters['genre'] === 'femme' ? 'selected' : '' ?>>Femme</option>
                            <option value="enfant" <?= $filters['genre'] === 'enfant' ? 'selected' : '' ?>>Enfant</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-select" name="price">
                            <option value="">Tous prix</option>
                            <option value="0-50" <?= ($_GET['price'] ?? '') === '0-50' ? 'selected' : '' ?>>0 - 50K BIF</option>
                            <option value="50-100" <?= ($_GET['price'] ?? '') === '50-100' ? 'selected' : '' ?>>50K - 100K BIF</option>
                            <option value="100-200" <?= ($_GET['price'] ?? '') === '100-200' ? 'selected' : '' ?>>100K - 200K BIF</option>
                            <option value="200+" <?= ($_GET['price'] ?? '') === '200+' ? 'selected' : '' ?>>200K BIF +</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <select class="form-select" name="sort">
                            <option value="">Trier par</option>
                            <option value="price-asc" <?= $filters['sort'] === 'price-asc' ? 'selected' : '' ?>>Prix croissant</option>
                            <option value="price-desc" <?= $filters['sort'] === 'price-desc' ? 'selected' : '' ?>>Prix décroissant</option>
                            <option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>Nom A-Z</option>
                            <option value="rating" <?= $filters['sort'] === 'rating' ? 'selected' : '' ?>>Note</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">Filtrer</button>
                        <a href="index.php" class="btn btn-outline-secondary">Réinitialiser</a>
                    </div>
                </form>
            </div>

            <!-- Products Grid -->
            <div class="row" id="products-container">
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">Aucun produit trouvé</h4>
                        <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 position-relative">
                                <?php if ($product['en_promotion']): ?>
                                    <span class="badge badge-sale">PROMO</span>
                                <?php endif; ?>
                                <img src="<?= htmlspecialchars($product['image_principale']) ?>" class="card-img-top product-image" alt="<?= htmlspecialchars($product['nom']) ?>">
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($product['nom']) ?></h5>
                                    <div class="rating mb-2">
                                        <?= generateStars($product['note_moyenne']) ?>
                                        <small class="text-muted">(<?= number_format($product['note_moyenne'], 1) ?>)</small>
                                    </div>
                                    <div class="price mb-3">
                                        <?php if ($product['prix_original']): ?>
                                            <span class="original-price"><?= formatPrice($product['prix_original']) ?></span><br>
                                        <?php endif; ?>
                                        <?= formatPrice($product['prix']) ?>
                                    </div>
                                    <div class="mt-auto">
                                        <div class="d-grid gap-2">
                                            <?php if (isLoggedIn()): ?>
                                                <button class="btn btn-primary" onclick="addToCart(<?= $product['id'] ?>)">
                                                    <i class="fas fa-shopping-cart me-2"></i>Ajouter au panier
                                                </button>
                                                <button class="btn btn-outline-secondary wishlist-btn" onclick="toggleWishlist(<?= $product['id'] ?>)" id="wishlist-btn-<?= $product['id'] ?>">
                                                    <i class="fas fa-heart me-2"></i>J'aime
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-primary" onclick="showLoginRequired()">
                                                    <i class="fas fa-shopping-cart me-2"></i>Ajouter au panier
                                                </button>
                                                <button class="btn btn-outline-secondary" onclick="showLoginRequired()">
                                                    <i class="fas fa-heart me-2"></i>J'aime
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-shopping-cart me-2"></i>Mon Panier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="cartItems">
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Chargement...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="me-auto">
                        <strong>Total: <span id="cartTotal">0 BIF</span></strong>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continuer les achats</button>
                    <button type="button" class="btn btn-success" onclick="showCheckout()">Commander</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Finaliser la commande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="checkoutForm">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Adresse de livraison</h6>
                                <div class="mb-3">
                                    <textarea class="form-control" name="adresse_livraison" placeholder="Adresse complète de livraison" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <input type="tel" class="form-control" name="telephone_livraison" placeholder="Numéro de téléphone" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Mode de paiement</h6>
                                <div class="mb-3">
                                    <select class="form-select" name="mode_paiement" required>
                                        <option value="">Choisir un mode de paiement</option>
                                        <option value="lumicash">LumiCash</option>
                                        <option value="ecocash">EcoCash</option>
                                        <option value="bancobu">Bancobu eNoti</option>
                                        <option value="bcb">BCB Mobile</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <textarea class="form-control" name="notes" placeholder="Notes supplémentaires (optionnel)"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <div class="me-auto">
                        <strong>Total: <span id="checkoutTotal">0 BIF</span></strong>
                    </div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-success" onclick="processOrder()">Confirmer la commande</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Wishlist Modal -->
    <div class="modal fade" id="wishlistModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-heart me-2"></i>Ma Liste de Souhaits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="wishlistItems">
                        <div class="text-center py-5">
                            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Chargement...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5><i class="fas fa-shoe-prints me-2"></i>ChaussureShop</h5>
                    <p>Votre boutique en ligne de confiance pour les meilleures chaussures.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <h5>Contact</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i>123 Bujumbura, Burundi</p>
                    <p><i class="fas fa-phone me-2"></i>+257 64 56 78 91</p>
                    <p><i class="fas fa-envelope me-2"></i>contact@chaussureshop.com</p>
                </div>
                <div class="paiements col-md-4 mb-4">
                    <h5>Modes de paiement</h5>
                    <img src="images/logos/lumicash.svg" alt="Lumicash">
                    <img src="images/logos/Bancobu_eNoti.jpg" alt="Bancobu">
                    <img src="images/logos/ecocash-logo.svg" alt="EcoCash">
                    <img src="images/logos/bcb.png" alt="BCB">
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 ChaussureShop. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script src="js/bootstrap.js"></script>
    <script>
        // Variables globales
        const isLoggedIn = <?= json_encode(isLoggedIn()) ?>;

        // Initialisation
        document.addEventListener('DOMContentLoaded', function () {
            if (isLoggedIn) {
                loadWishlistStates();
            }
            document.addEventListener('click', closeDropdowns);
        });

        // Fonction utilitaire fetch
        async function apiRequest(action, params = {}) {
            try {
                const body = new URLSearchParams({ action, ...params }).toString();
                const response = await fetch('config.php', {   
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body
                });

                if (!response.ok) {
                    throw new Error("Erreur réseau");
                }

                const data = await response.json();
                return data;
            } catch (error) {
                console.error('API Error:', error);
                showNotification("Erreur de connexion au serveur", "danger");
                return null;
            }
        }

        // Gestion Login / Compte
        function toggleLogin() {
            const loginForm = document.getElementById('loginForm');
            const accountDropdown = document.getElementById('accountDropdown');
            if (accountDropdown) accountDropdown.style.display = 'none';
            loginForm.style.display = (loginForm.style.display === 'block') ? 'none' : 'block';
        }

        function toggleAccount() {
            const accountDropdown = document.getElementById('accountDropdown');
            const loginForm = document.getElementById('loginForm');
            if (loginForm) loginForm.style.display = 'none';
            accountDropdown.style.display = (accountDropdown.style.display === 'block') ? 'none' : 'block';
        }

        function closeDropdowns(event) {
            const loginForm = document.getElementById('loginForm');
            const accountDropdown = document.getElementById('accountDropdown');
            if (!event.target.closest('.position-relative')) {
                if (loginForm) loginForm.style.display = 'none';
                if (accountDropdown) accountDropdown.style.display = 'none';
            }
        }

        function showLoginRequired() {
            showNotification("Veuillez vous connecter pour effectuer cette action", "warning");
            toggleLogin();
        }

        // Panier
        async function addToCart(productId) {
            if (!isLoggedIn) {
                showLoginRequired();
                return;
            }

            const data = await apiRequest("add_to_cart", { product_id: productId });
            if (data?.success) {
                updateCartCount();
                showNotification("Produit ajouté au panier!", "success");
            } else {
                showNotification("Erreur lors de l'ajout au panier", "danger");
            }
        }

        async function toggleCart() {
            if (!isLoggedIn) {
                showLoginRequired();
                return;
            }
            
            await loadCartItems();
            const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
            cartModal.show();
        }

        async function loadCartItems() {
            const data = await apiRequest("get_cart");
            if (data?.items) displayCartItems(data.items);
        }

        function displayCartItems(items) {
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');

            if (!items || items.length === 0) {
                cartItems.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Votre panier est vide</p>
                    </div>`;
                cartTotal.textContent = "0 BIF";
                return;
            }

            let total = 0;
            cartItems.innerHTML = items.map(item => {
                const subtotal = item.prix * item.quantite;
                total += subtotal;
                return `
                    <div class="d-flex align-items-center mb-3 p-3 border rounded">
                        <img src="${item.image_principale}" alt="${item.nom}" class="me-3" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${item.nom}</h6>
                            <small class="text-muted">${item.taille} - ${item.couleur}</small><br>
                            <small class="text-muted">${formatPrice(item.prix)} × ${item.quantite}</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <button class="btn btn-sm btn-outline-secondary me-2" onclick="updateCartQuantity(${item.product_id}, '${item.taille}', '${item.couleur}', ${item.quantite - 1})">-</button>
                            <span class="mx-2">${item.quantite}</span>
                            <button class="btn btn-sm btn-outline-secondary me-3" onclick="updateCartQuantity(${item.product_id}, '${item.taille}', '${item.couleur}', ${item.quantite + 1})">+</button>
                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.product_id}, '${item.taille}', '${item.couleur}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>`;
            }).join('');
            cartTotal.textContent = formatPrice(total);
            
            // Mettre à jour le total dans le modal de checkout aussi
            const checkoutTotal = document.getElementById('checkoutTotal');
            if (checkoutTotal) checkoutTotal.textContent = formatPrice(total);
        }

        async function updateCartQuantity(productId, taille, couleur, newQuantity) {
            const data = await apiRequest("update_cart", {
                product_id: productId,
                taille,
                couleur,
                quantite: newQuantity
            });
            if (data?.success) {
                loadCartItems();
                updateCartCount();
            }
        }

        async function removeFromCart(productId, taille, couleur) {
            const data = await apiRequest("remove_from_cart", { product_id: productId, taille, couleur });
            if (data?.success) {
                loadCartItems();
                updateCartCount();
                showNotification("Produit retiré du panier", "info");
            }
        }

        async function updateCartCount() {
            const data = await apiRequest("get_cart");
            if (data?.count !== undefined) {
                document.getElementById('cart-count').textContent = data.count;
            }
        }

        function showCheckout() {
            const cartItems = document.querySelectorAll('#cartItems .d-flex');
            if (cartItems.length === 0) {
                showNotification("Votre panier est vide!", "warning");
                return;
            }
            
            bootstrap.Modal.getInstance(document.getElementById('cartModal')).hide();
            const checkoutModal = new bootstrap.Modal(document.getElementById('checkoutModal'));
            checkoutModal.show();
        }

        async function processOrder() {
            const form = document.getElementById('checkoutForm');
            const formData = new FormData(form);
            
            if (!formData.get('adresse_livraison') || !formData.get('telephone_livraison') || !formData.get('mode_paiement')) {
                showNotification("Veuillez remplir tous les champs obligatoires", "warning");
                return;
            }
            
            const data = await apiRequest("create_order", {
                adresse_livraison: formData.get('adresse_livraison'),
                telephone_livraison: formData.get('telephone_livraison'),
                mode_paiement: formData.get('mode_paiement'),
                notes: formData.get('notes')
            });
            
            if (data?.success) {
                bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
                showNotification(`Commande confirmée! Numéro: ${data.numero_commande}`, "success");
                updateCartCount();
                // Réinitialiser le panier affiché
                document.getElementById('cartItems').innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Votre panier est vide</p>
                    </div>`;
                document.getElementById('cartTotal').textContent = '0 BIF';
            } else {
                showNotification(data?.message || "Erreur lors de la création de la commande", "danger");
            }
        }

        // Wishlist
        async function toggleWishlist(productId) {
            if (!isLoggedIn) {
                showLoginRequired();
                return;
            }

            const data = await apiRequest("toggle_wishlist", { product_id: productId });
            if (data?.success) {
                const btn = document.getElementById(`wishlist-btn-${productId}`);
                if (!btn) return;
                if (data.action === "added") {
                    btn.classList.replace("btn-outline-secondary", "btn-danger");
                    btn.innerHTML = '<i class="fas fa-heart me-2"></i>Retirer des favoris';
                    showNotification("Ajouté aux favoris!", "success");
                } else {
                    btn.classList.replace("btn-danger", "btn-outline-secondary");
                    btn.innerHTML = '<i class="fas fa-heart me-2"></i>J\'aime';
                    showNotification("Retiré des favoris", "info");
                }
                updateWishlistCount();
            }
        }

        async function showWishlist() {
            if (!isLoggedIn) {
                showLoginRequired();
                return;
            }
            
            await loadWishlistItems();
            const wishlistModal = new bootstrap.Modal(document.getElementById('wishlistModal'));
            wishlistModal.show();
        }

        async function loadWishlistItems() {
            const data = await apiRequest("get_wishlist");
            if (data?.items) displayWishlistItems(data.items);
        }

        function displayWishlistItems(items) {
            const wishlistItems = document.getElementById('wishlistItems');
            if (!items || items.length === 0) {
                wishlistItems.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Votre liste de souhaits est vide</p>
                    </div>`;
                return;
            }
            wishlistItems.innerHTML = items.map(item => `
                <div class="d-flex align-items-center mb-3 p-3 border rounded">
                    <img src="${item.image_principale}" alt="${item.nom}" class="me-3" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${item.nom}</h6>
                        <div class="rating mb-1">${generateStarsJS(item.note_moyenne)}
                            <small class="text-muted">(${parseFloat(item.note_moyenne).toFixed(1)})</small>
                        </div>
                        <div class="price">${formatPrice(item.prix)}</div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <button class="btn btn-sm btn-primary" onclick="addToCart(${item.product_id})">
                            <i class="fas fa-shopping-cart me-1"></i>Ajouter au panier
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="toggleWishlist(${item.product_id})">
                            <i class="fas fa-trash me-1"></i>Retirer
                        </button>
                    </div>
                </div>`).join('');
        }

        async function updateWishlistCount() {
            const data = await apiRequest("get_wishlist");
            if (data?.count !== undefined) {
                document.getElementById('wishlist-count').textContent = data.count;
            }
        }

        async function loadWishlistStates() {
            const data = await apiRequest("get_wishlist");
            if (data?.items) {
                data.items.forEach(item => {
                    const btn = document.getElementById(`wishlist-btn-${item.product_id}`);
                    if (btn) {
                        btn.classList.replace("btn-outline-secondary", "btn-danger");
                        btn.innerHTML = '<i class="fas fa-heart me-2"></i>Retirer des favoris';
                    }
                });
            }
        }

        // Utilitaires
        function generateStarsJS(rating) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = (rating - fullStars) >= 0.5;
            let stars = '';
            for (let i = 0; i < fullStars; i++) stars += '<i class="fas fa-star"></i>';
            if (hasHalfStar) stars += '<i class="fas fa-star-half-alt"></i>';
            for (let i = 0; i < 5 - Math.ceil(rating); i++) stars += '<i class="far fa-star"></i>';
            return stars;
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price) + ' BIF';
        }

        function showNotification(message, type = "success") {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type} position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; opacity: 0; transform: translateX(100%); transition: all 0.3s ease; max-width: 300px;';
            const iconMap = {
                success: "check-circle",
                info: "info-circle",
                warning: "exclamation-triangle",
                danger: "exclamation-circle"
            };
            notification.innerHTML = `<i class="fas fa-${iconMap[type] || "info-circle"} me-2"></i>${message}`;
            document.body.appendChild(notification);
            setTimeout(() => {
                notification.style.opacity = "1";
                notification.style.transform = "translateX(0)";
            }, 100);
            setTimeout(() => {
                notification.style.opacity = "0";
                notification.style.transform = "translateX(100%)";
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener("click", function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute("href"));
                if (target) {
                    target.scrollIntoView({ behavior: "smooth", block: "start" });
                }
            });
        });
    </script>
</body>
</html>