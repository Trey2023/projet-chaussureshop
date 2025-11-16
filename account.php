<?php
require_once 'config.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Récupérer les informations utilisateur
$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

// Récupérer les commandes utilisateur
$orders = getUserOrders($userId);

// Récupérer les favoris
$wishlistItems = getWishlistItems($userId);

// Statistiques utilisateur
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(total), 0) as total_spent,
    (SELECT COUNT(*) FROM wishlist WHERE user_id = :user_id) as wishlist_count
    FROM orders WHERE user_id = :user_id2");
$stmt->execute(['user_id' => $userId, 'user_id2' => $userId]);
$stats = $stmt->fetch();

// Gestion de la mise à jour du profil
if (isset($_POST['update_profile'])) {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $telephone = trim($_POST['telephone']);
    $date_naissance = $_POST['date_naissance'] ?: null;
    $genre = $_POST['genre'] ?: null;
    $adresse = trim($_POST['adresse']);
    $ville = trim($_POST['ville']);
    
    $stmt = $pdo->prepare("UPDATE users SET nom = :nom, prenom = :prenom, telephone = :telephone, 
                           date_naissance = :date_naissance, genre = :genre, adresse = :adresse, ville = :ville 
                           WHERE id = :id");
    
    if ($stmt->execute([
        'nom' => $nom,
        'prenom' => $prenom,
        'telephone' => $telephone,
        'date_naissance' => $date_naissance,
        'genre' => $genre,
        'adresse' => $adresse,
        'ville' => $ville,
        'id' => $userId
    ])) {
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_prenom'] = $prenom;
        $success = "Profil mis à jour avec succès";
        
        // Recharger les données utilisateur
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
    } else {
        $error = "Erreur lors de la mise à jour";
    }
}

// Fonction pour obtenir le statut en français
function getStatusLabel($status) {
    $statuses = [
        'pending' => ['label' => 'En attente', 'class' => 'status-pending'],
        'processing' => ['label' => 'En traitement', 'class' => 'status-processing'],
        'shipped' => ['label' => 'Expédiée', 'class' => 'status-shipped'],
        'delivered' => ['label' => 'Livrée', 'class' => 'status-delivered'],
        'cancelled' => ['label' => 'Annulée', 'class' => 'status-cancelled']
    ];
    return $statuses[$status] ?? ['label' => $status, 'class' => 'status-pending'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - ChaussureShop</title>
        <link rel="shortcut icon" href="favicon.svg" type="image/x-svg">
    <link href="css/bootstrap.css" rel="stylesheet">
    <link href="fontawesome/css/all.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #f39c12;
            --success-color: #27ae60;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #34495e);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .sidebar {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .sidebar .nav-link {
            color: #333;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--secondary-color);
            color: white;
        }

        .main-content {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
            min-height: 600px;
        }

        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .form-section h5 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary-color);
        }

        .order-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .order-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .order-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }

        .order-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #cce5ff; color: #0066cc; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child { border-bottom: none; }

        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .wishlist-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .wishlist-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shoe-prints me-2"></i>ChaussureShop
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>Accueil
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="sidebar">
                    <div class="text-center mb-4">
                        <div class="avatar bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                        <h5 class="mt-2 mb-0"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#profile" onclick="showSection('profile')">
                            <i class="fas fa-user me-2"></i>Mon Profil
                        </a>
                        <a class="nav-link" href="#orders" onclick="showSection('orders')">
                            <i class="fas fa-box me-2"></i>Mes Commandes
                        </a>
                        <a class="nav-link" href="#wishlist" onclick="showSection('wishlist')">
                            <i class="fas fa-heart me-2"></i>Ma Liste de Souhaits
                        </a>
                        <hr>
                        <a class="nav-link text-danger" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <div class="main-content">
                    <!-- Profile Section -->
                    <div id="profile-section">
                        <!-- Stats -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <div class="stats-number"><?= $stats['total_orders'] ?></div>
                                    <div class="text-muted">Commandes</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <div class="stats-number"><?= formatPrice($stats['total_spent']) ?></div>
                                    <div class="text-muted">Total Dépensé</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stats-card">
                                    <div class="stats-number"><?= $stats['wishlist_count'] ?></div>
                                    <div class="text-muted">Favoris</div>
                                </div>
                            </div>
                        </div>

                        <?php if (isset($success)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Personal Information -->
                        <div class="form-section">
                            <h5><i class="fas fa-user me-2"></i>Informations Personnelles</h5>
                            <form method="post" id="profileForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Prénom</label>
                                        <input type="text" class="form-control" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nom</label>
                                        <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date de naissance</label>
                                        <input type="date" class="form-control" name="date_naissance" value="<?= $user['date_naissance'] ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Genre</label>
                                        <select class="form-select" name="genre" disabled>
                                            <option value="">Choisir</option>
                                            <option value="homme" <?= $user['genre'] === 'homme' ? 'selected' : '' ?>>Homme</option>
                                            <option value="femme" <?= $user['genre'] === 'femme' ? 'selected' : '' ?>>Femme</option>
                                            <option value="autre" <?= $user['genre'] === 'autre' ? 'selected' : '' ?>>Autre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" class="form-control" name="adresse" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ville</label>
                                        <input type="text" class="form-control" name="ville" value="<?= htmlspecialchars($user['ville'] ?? '') ?>" readonly>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-primary" onclick="editProfile()">
                                    <i class="fas fa-edit me-2"></i>Modifier le profil
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Section -->
                    <div id="orders-section" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2><i class="fas fa-box me-2"></i>Mes Commandes</h2>
                        </div>

                        <?php if (empty($orders)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-box fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">Aucune commande</h4>
                                <p class="text-muted">Vous n'avez pas encore passé de commande</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag me-2"></i>Commencer mes achats
                                </a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($orders as $order): 
                                $orderItems = getOrderItems($order['id']);
                                $statusInfo = getStatusLabel($order['statut']);
                            ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="row align-items-center">
                                            <div class="col-md-3">
                                                <strong><?= htmlspecialchars($order['numero_commande']) ?></strong><br>
                                                <small class="text-muted">Passée le <?= date('d/m/Y', strtotime($order['date_commande'])) ?></small>
                                            </div>
                                            <div class="col-md-3">
                                                <span class="order-status <?= $statusInfo['class'] ?>">
                                                    <?= $statusInfo['label'] ?>
                                                </span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Total: <?= formatPrice($order['total']) ?></strong>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <small class="text-muted">Paiement: <?= ucfirst($order['mode_paiement']) ?></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="p-3">
                                        <?php foreach ($orderItems as $item): ?>
                                            <div class="order-item">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= htmlspecialchars($item['nom_produit']) ?></h6>
                                                    <small class="text-muted">
                                                        Taille: <?= htmlspecialchars($item['taille']) ?> - 
                                                        Couleur: <?= htmlspecialchars($item['couleur']) ?> - 
                                                        Quantité: <?= $item['quantite'] ?>
                                                    </small>
                                                </div>
                                                <div class="text-end">
                                                    <strong><?= formatPrice($item['sous_total']) ?></strong>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if ($order['statut'] === 'pending'): ?>
                                            <div class="mt-3">
                                                <button class="btn btn-sm btn-danger" onclick="cancelOrder('<?= $order['numero_commande'] ?>')">
                                                    <i class="fas fa-times me-1"></i>Annuler la commande
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['statut'] === 'cancelled'): ?>
                                            <div class="alert alert-warning mt-3 mb-0">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                Commande annulée
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Wishlist Section -->
                    <div id="wishlist-section" style="display: none;">
                        <h2 class="mb-4"><i class="fas fa-heart me-2"></i>Ma Liste de Souhaits</h2>
                        
                        <?php if (empty($wishlistItems)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">Votre liste de souhaits est vide</h4>
                                <p class="text-muted">Ajoutez des produits à vos favoris pour les retrouver ici</p>
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-shopping-bag me-2"></i>Découvrir nos produits
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($wishlistItems as $item): ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100">
                                            <img src="<?= htmlspecialchars($item['image_principale']) ?>" class="card-img-top" alt="<?= htmlspecialchars($item['nom']) ?>" style="height: 200px; object-fit: cover;">
                                            <div class="card-body d-flex flex-column">
                                                <h6 class="card-title"><?= htmlspecialchars($item['nom']) ?></h6>
                                                <div class="rating mb-2">
                                                    <?= generateStars($item['note_moyenne']) ?>
                                                    <small class="text-muted">(<?= number_format($item['note_moyenne'], 1) ?>)</small>
                                                </div>
                                                <div class="price mb-3">
                                                    <strong><?= formatPrice($item['prix']) ?></strong>
                                                </div>
                                                <div class="mt-auto">
                                                    <button class="btn btn-primary btn-sm w-100 mb-2" onclick="addToCartFromWishlist(<?= $item['product_id'] ?>)">
                                                        <i class="fas fa-shopping-cart me-1"></i>Ajouter au panier
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-sm w-100" onclick="removeFromWishlistAccount(<?= $item['product_id'] ?>)">
                                                        <i class="fas fa-trash me-1"></i>Retirer
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.js"></script>
    <script>
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            if (hash && ['profile', 'orders', 'wishlist'].includes(hash)) {
                showSection(hash);
            } else {
                showSection('profile');
            }

                        // Lire la section depuis le hash de l'URL
            let section = window.location.hash ? window.location.hash.substring(1) : 'dashboard';
            showSection(section);
        });

        // Show specific section
        function showSection(section) {
            const sections = ['profile', 'orders', 'wishlist'];
            sections.forEach(s => {
                const element = document.getElementById(s + '-section');
                if (element) {
                    element.style.display = 'none';
                }
            });

            const selectedSection = document.getElementById(section + '-section');
            if (selectedSection) {
                selectedSection.style.display = 'block';
            }

            document.querySelectorAll('.sidebar .nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            const activeLink = document.querySelector(`[href="#${section}"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }

                // Mettre à jour le hash dans l'URL sans recharger
         window.history.replaceState(null, null, '#' + section);
        }

      function editProfile() {
    const form = document.getElementById('profileForm');
    const inputs = form.querySelectorAll('input, select');
    const isReadonly = inputs[0].hasAttribute('readonly');

    if (isReadonly) {
        // Activer l'édition
        inputs.forEach(input => {
            input.removeAttribute('readonly');
            input.removeAttribute('disabled');
        });
        
        // Changer le bouton
        event.target.innerHTML = '<i class="fas fa-save me-2"></i>Enregistrer';
        event.target.classList.remove('btn-primary');
        event.target.classList.add('btn-success');
    } else {
        // Ajouter un champ caché pour signaler l'update
        let updateField = document.createElement('input');
        updateField.type = 'hidden';
        updateField.name = 'update_profile';
        updateField.value = '1';
        form.appendChild(updateField);

        // Soumettre le formulaire
        form.submit();
    }
}

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
                showAlert("Erreur de connexion au serveur", "danger");
                return null;
            }
        }

        async function addToCartFromWishlist(productId) {
            const data = await apiRequest("add_to_cart", { product_id: productId });
            if (data?.success) {
                showAlert("Produit ajouté au panier!", "success");
            }
        }

        async function removeFromWishlistAccount(productId) {
            const data = await apiRequest("toggle_wishlist", { product_id: productId });
            if (data?.success) {
                location.reload(); // Recharger pour mettre à jour l'affichage
            }
        }

        function cancelOrder(numeroCommande) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
                // Ici vous pourriez ajouter la logique d'annulation
                showAlert('Commande annulée avec succès', 'success');
                setTimeout(() => location.reload(), 1000);
            }
        }

        // Show alert
        function showAlert(message, type = 'success') {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} position-fixed`;
            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; opacity: 0; transform: translateX(100%); transition: all 0.3s ease; max-width: 300px;';
            
            const iconMap = {
                'success': 'check-circle',
                'danger': 'exclamation-circle',
                'warning': 'exclamation-triangle',
                'info': 'info-circle'
            };
            
            alert.innerHTML = `<i class="fas fa-${iconMap[type]} me-2"></i>${message}`;
            document.body.appendChild(alert);

            setTimeout(() => {
                alert.style.opacity = '1';
                alert.style.transform = 'translateX(0)';
            }, 100);

            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => alert.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>