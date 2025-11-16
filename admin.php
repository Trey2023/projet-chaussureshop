<?php
require_once 'config.php';
requireAdmin();

$pdo = getDB();

// Statistiques générales
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE actif = 1");
$stats['products'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
$stats['orders'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'client'");
$stats['users'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE statut != 'cancelled'");
$stats['revenue'] = $stmt->fetchColumn();

// Gestion des actions
$message = '';
$messageType = 'success';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_product':
            $nom = trim($_POST['nom']);
            $description = trim($_POST['description']);
            $prix = (float)$_POST['prix'];
            $prix_original = !empty($_POST['prix_original']) ? (float)$_POST['prix_original'] : null;
            $category_id = (int)$_POST['category_id'];
            $genre = $_POST['genre'];
            $tailles = json_encode(explode(',', $_POST['tailles']));
            $couleurs = json_encode(explode(',', $_POST['couleurs']));
            $stock_total = (int)$_POST['stock_total'];
     
            $en_promotion = isset($_POST['en_promotion']) ? 1 : 0;

             // Vérifier si un fichier a été uploadé
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/images/uploads/'; // chemin serveur
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true); // crée le dossier si inexistant
                    }

                    // Sécuriser le nom du fichier
                    $fileName = time() . '_' . basename($_FILES['image']['name']);
                    $targetFile = $uploadDir . $fileName;

                    // Déplacer le fichier temporaire
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                        $imagePath = 'images/uploads/' . $fileName; // chemin relatif pour DB
                    } else {
                       $message ="Erreur lors du téléchargement de l'image.";
                        $messageType = 'danger';
                    }
                } else {
                    $message="Veuillez sélectionner une image valide.";
                     $messageType = 'danger';
                }
            
            $stmt = $pdo->prepare("INSERT INTO products (nom, description, prix, prix_original, category_id, genre, tailles, couleurs, stock_total, image_principale, en_promotion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$nom, $description, $prix, $prix_original, $category_id, $genre, $tailles, $couleurs, $stock_total, $imagePath, $en_promotion])) {
                 header('location: ./admin.php');
                $message = "Produit ajouté avec succès";
                 $messageType = 'success';
               
            } else {
                $message = "Erreur lors de l'ajout du produit";
                $messageType = 'danger';
            }
            break;
            
        case 'update_order_status':
            $orderId = (int)$_POST['order_id'];
            $newStatus = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE orders SET statut = ? WHERE id = ?");
            if ($stmt->execute([$newStatus, $orderId])) {
                $_SESSION['message'] = "La commande #$orderId a été mise à jour vers : $newStatus";
                $_SESSION['messageType'] = "success";
            } else {
                $_SESSION['message'] = "Erreur lors de la mise à jour de la commande #$orderId.";
                $_SESSION['messageType'] = "danger";
            }

            header('Location: ./admin.php#orders');
            exit;

            
        case 'delete_product':
            $productId = (int)$_POST['product_id'];
            
            $stmt = $pdo->prepare("UPDATE products SET actif = 0 WHERE id = ?");
            if ($stmt->execute([$productId])) {
                 header('location: ./admin.php#products');
                $message = "Produit désactivé avec succès";
                 $messageType = 'success';
                
            } else {
                $message = "Erreur lors de la désactivation";
                $messageType = 'danger';
            }
            break;
    }
}

// Récupérer les données
$products = $pdo->query("SELECT p.*, c.nom as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.actif = 1 ORDER BY p.date_creation DESC LIMIT 10")->fetchAll();

$orders = $pdo->query("SELECT o.*, u.nom, u.prenom FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.date_commande DESC LIMIT 20")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE actif = 1")->fetchAll();

$recentUsers = $pdo->query("SELECT nom, prenom, email, date_creation FROM users WHERE role = 'client' ORDER BY date_creation DESC LIMIT 10")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - ChaussureShop</title>
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
            min-height: calc(100vh - 76px);
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            padding: 20px 0;
        }

        .sidebar .nav-link {
            color: #333;
            padding: 12px 20px;
            border: none;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: var(--secondary-color);
            color: white;
            border-left: 4px solid var(--accent-color);
        }

        .main-content {
            padding: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .table-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-card .card-header {
            background: var(--primary-color);
            color: white;
            padding: 15px 20px;
            border: none;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-shipped { background: #cce5ff; color: #0066cc; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 25px;
        }

        .btn-admin {
            background: linear-gradient(45deg, var(--secondary-color), #c0392b);
            border: none;
            color: white;
        }

        .btn-admin:hover {
            background: linear-gradient(45deg, #c0392b, var(--secondary-color));
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shoe-prints me-2"></i>ChaussureShop Admin
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    <i class="fas fa-user me-1"></i>
                    <?= htmlspecialchars($_SESSION['user_prenom'] . ' ' . $_SESSION['user_nom']) ?>
                </span>
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>Site principal
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0">
                <div class="sidebar">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#dashboard" onclick="showSection('dashboard')">
                            <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                        </a>
                        <a class="nav-link" href="#products" onclick="showSection('products')">
                            <i class="fas fa-box me-2"></i>Produits
                        </a>
                        <a class="nav-link" href="#orders" onclick="showSection('orders')">
                            <i class="fas fa-shopping-cart me-2"></i>Commandes
                        </a>
                        <a class="nav-link" href="#users" onclick="showSection('users')">
                            <i class="fas fa-users me-2"></i>Utilisateurs
                        </a>
                        <a class="nav-link" href="#add-product" onclick="showSection('add-product')">
                            <i class="fas fa-plus me-2"></i>Ajouter Produit
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <?php if (!empty($message)): ?>
                        <script>alert("<?= htmlspecialchars($_SESSION['message']) ?>")</script>
                        </div>

                    <?php endif; ?>


                    <!-- Dashboard Section -->
                    <div id="dashboard-section">
                        <h2 class="mb-4">Tableau de bord</h2>
                        
                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number"><?= $stats['products'] ?></div>
                                            <div class="text-muted">Produits actifs</div>
                                        </div>
                                        <div class="text-primary">
                                            <i class="fas fa-box fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number"><?= $stats['orders'] ?></div>
                                            <div class="text-muted">Commandes totales</div>
                                        </div>
                                        <div class="text-warning">
                                            <i class="fas fa-shopping-cart fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number"><?= $stats['users'] ?></div>
                                            <div class="text-muted">Clients</div>
                                        </div>
                                        <div class="text-info">
                                            <i class="fas fa-users fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="stat-number"><?= formatPrice($stats['revenue']) ?></div>
                                            <div class="text-muted">Chiffre d'affaires</div>
                                        </div>
                                        <div class="text-success">
                                            <i class="fas fa-dollar-sign fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Orders -->
                        <div class="table-card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Commandes récentes</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Numéro</th>
                                            <th>Client</th>
                                            <th>Total</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($orders, 0, 5) as $order): 
                                            $statusLabels = [
                                                'pending' => ['En attente', 'status-pending'],
                                                'processing' => ['En traitement', 'status-processing'],
                                                'shipped' => ['Expédiée', 'status-shipped'],
                                                'delivered' => ['Livrée', 'status-delivered'],
                                                'cancelled' => ['Annulée', 'status-cancelled']
                                            ];
                                            $statusInfo = $statusLabels[$order['statut']] ?? ['Inconnu', 'status-pending'];
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($order['numero_commande']) ?></td>
                                                <td><?= htmlspecialchars($order['prenom'] . ' ' . $order['nom']) ?></td>
                                                <td><?= formatPrice($order['total']) ?></td>
                                                <td>
                                                    <span class="status-badge <?= $statusInfo[1] ?>">
                                                        <?= $statusInfo[0] ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($order['date_commande'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div id="products-section" style="display: none;">
                        <h2 class="mb-4">Gestion des Produits</h2>
                        
                        <div class="table-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Liste des produits</h5>
                                <button class="btn btn-light btn-sm" onclick="showSection('add-product')">
                                    <i class="fas fa-plus me-1"></i>Nouveau produit
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Image</th>
                                            <th>Nom</th>
                                            <th>Catégorie</th>
                                            <th>Prix</th>
                                            <th>Stock</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td>
                                                    <img src="<?= htmlspecialchars($product['image_principale']) ?>" 
                                                         alt="<?= htmlspecialchars($product['nom']) ?>" 
                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($product['nom']) ?></strong>
                                                    <?php if ($product['en_promotion']): ?>
                                                        <span class="badge bg-warning ms-1">PROMO</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($product['category_name'] ?? 'Non définie') ?></td>
                                                <td>
                                                    <?php if ($product['prix_original']): ?>
                                                        <small class="text-muted text-decoration-line-through">
                                                            <?= formatPrice($product['prix_original']) ?>
                                                        </small><br>
                                                    <?php endif; ?>
                                                    <strong><?= formatPrice($product['prix']) ?></strong>
                                                </td>
                                                <td><?= $product['stock_total'] ?></td>
                                                <td>
                                                    <form method="post" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_product">
                                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                                onclick="return confirm('Êtes-vous sûr de vouloir désactiver ce produit ?')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
<script>
function confirmerAvantSoumission(select) {
    const form = select.form;
    const status = select.value;
    const id = form.querySelector('input[name="order_id"]').value;
    
    // Statuts nécessitant une confirmation
    const sensibles = ['shipped', 'delivered', 'cancelled'];
    let message = '';

    if (sensibles.includes(status)) {
        switch(status) {
            case 'shipped':
                message = `Confirmez-vous l'expédition de la commande #${id} ?`;
                break;
            case 'delivered':
                message = `Confirmez-vous la livraison de la commande #${id} ?`;
                break;
            case 'cancelled':
                message = `Êtes-vous sûr de vouloir annuler la commande #${id} ?`;
                break;
        }

        if (!confirm(message)) {
            // Si l’utilisateur annule → on remet l’ancien statut et on arrête tout
            select.value = select.getAttribute('data-current');
            return;
        }
    }

    form.submit(); // Soumission confirmée
}
</script>

                    <!-- Orders Section -->
                    <div id="orders-section" style="display: none;">
                        <h2 class="mb-4">Gestion des Commandes</h2>
                        
                        <div class="table-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Toutes les commandes</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Numéro</th>
                                            <th>Client</th>
                                            <th>Total</th>
                                            <th>Statut</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): 
                                            $statusLabels = [
                                                'pending' => ['En attente', 'status-pending'],
                                                'processing' => ['En traitement', 'status-processing'],
                                                'shipped' => ['Expédiée', 'status-shipped'],
                                                'delivered' => ['Livrée', 'status-delivered'],
                                                'cancelled' => ['Annulée', 'status-cancelled']
                                            ];
                                            $statusInfo = $statusLabels[$order['statut']] ?? ['Inconnu', 'status-pending'];
                                        ?>
                                            <tr>
                                                <td><?= htmlspecialchars($order['numero_commande']) ?></td>
                                                <td><?= htmlspecialchars($order['prenom'] . ' ' . $order['nom']) ?></td>
                                                <td><?= formatPrice($order['total']) ?></td>
                                                <td>
                                                    <span class="status-badge <?= $statusInfo[1] ?>">
                                                        <?= $statusInfo[0] ?>
                                                    </span>
                                                </td>
                                                <td><?= date('d/m/Y H:i', strtotime($order['date_commande'])) ?></td>
                                                <td>
                                                    <form method="post" style="display: inline;" onsubmit="return confirmerStatut(this);">
                                                        <input type="hidden" name="action" value="update_order_status">
                                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">

                                                        
                                                        <select name="status"
                                                                class="form-select form-select-sm"
                                                                style="width: auto; display: inline-block;"
                                                                data-current="<?= htmlspecialchars($order['statut']) ?>"
                                                                onchange="confirmerAvantSoumission(this)">
                                                            <option value="pending" <?= $order['statut'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                                            <option value="processing" <?= $order['statut'] === 'processing' ? 'selected' : '' ?>>En traitement</option>
                                                            <option value="shipped" <?= $order['statut'] === 'shipped' ? 'selected' : '' ?>>Expédiée</option>
                                                            <option value="delivered" <?= $order['statut'] === 'delivered' ? 'selected' : '' ?>>Livrée</option>
                                                            <option value="cancelled" <?= $order['statut'] === 'cancelled' ? 'selected' : '' ?>>Annulée</option>
                                                        </select>

                                                    </form>

                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Users Section -->
                    <div id="users-section" style="display: none;">
                        <h2 class="mb-4">Gestion des Utilisateurs</h2>
                        
                        <div class="table-card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Clients récents</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Date d'inscription</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUsers as $user): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= date('d/m/Y', strtotime($user['date_creation'])) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Add Product Section -->
                    <div id="add-product-section" style="display: none;">
                        <h2 class="mb-4">Ajouter un Produit</h2>
                        
                        <div class="form-card">
                            <form method="post" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="add_product">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nom du produit *</label>
                                        <input type="text" class="form-control" name="nom" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Catégorie *</label>
                                        <select class="form-select" name="category_id" required>
                                            <option value="">Choisir une catégorie</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['nom']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Prix (BIF) *</label>
                                        <input type="number" class="form-control" name="prix" step="0.01" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Prix original (BIF)</label>
                                        <input type="number" class="form-control" name="prix_original" step="0.01">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">Stock total *</label>
                                        <input type="number" class="form-control" name="stock_total" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Genre *</label>
                                        <select class="form-select" name="genre" required>
                                            <option value="">Choisir un genre</option>
                                            <option value="homme">Homme</option>
                                            <option value="femme">Femme</option>
                                            <option value="enfant">Enfant</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" name="en_promotion" id="en_promotion">
                                            <label class="form-check-label" for="en_promotion">
                                                Produit en promotion
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tailles disponibles (séparées par des virgules) *</label>
                                        <input type="text" class="form-control" name="tailles" placeholder="36,37,38,39,40,41,42" required>
                                        <small class="form-text text-muted">Exemple: 36,37,38,39,40,41,42</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Couleurs disponibles (séparées par des virgules) *</label>
                                        <input type="text" class="form-control" name="couleurs" placeholder="Noir,Blanc,Rouge" required>
                                        <small class="form-text text-muted">Exemple: Noir,Blanc,Rouge</small>
                                    </div>
                                </div>

                            <div class="mb-3">
                            <label for="productImage" class="form-label">Image du produit</label>
                            <input type="file" name="image" id="productImage" class="form-control" accept="image/*" required>
                            </div>


                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-admin">
                                        <i class="fas fa-save me-2"></i>Ajouter le produit
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="showSection('products')">
                                        <i class="fas fa-arrow-left me-2"></i>Retour à la liste
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/bootstrap.js"></script>
    <script>

        // Show specific section
        function showSection(section) {
            const sections = ['dashboard', 'products', 'orders', 'users', 'add-product'];
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

            // Update active nav link
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

        document.addEventListener('DOMContentLoaded', function() {
            // Lire la section depuis le hash de l'URL
            let section = window.location.hash ? window.location.hash.substring(1) : 'dashboard';
            showSection(section);

            // Auto-dismiss alerts après 5s
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => new bootstrap.Alert(alert).close(), 5000);
            });



        });

    </script>
</body>
</html>