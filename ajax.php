<?php
session_start();
header('Content-Type: application/json');

// Initialiser panier et wishlist en session si non définis
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if (!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_to_cart':
        $id = (int)$_POST['product_id'];
        // Exemple : on ajoute un produit sans tailles/couleurs pour simplifier
        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = ['quantite' => 1];
        } else {
            $_SESSION['cart'][$id]['quantite']++;
        }
        echo json_encode(['success' => true]);
        break;

    case 'remove_from_cart':
        $id = (int)$_POST['product_id'];
        unset($_SESSION['cart'][$id]);
        echo json_encode(['success' => true]);
        break;

    case 'update_cart':
        $id = (int)$_POST['product_id'];
        $qte = max(1, (int)$_POST['quantite']);
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantite'] = $qte;
        }
        echo json_encode(['success' => true]);
        break;

    case 'get_cart':
        $cart = [];
        $count = 0;
        foreach ($_SESSION['cart'] as $id => $item) {
            $cart[] = [
                'product_id' => $id,
                'nom' => "Produit $id",
                'prix' => 50, // à récupérer depuis la base
                'quantite' => $item['quantite'],
                'image_principale' => "images/produits/$id.jpg",
                'taille' => "N/A",
                'couleur' => "N/A"
            ];
            $count += $item['quantite'];
        }
        echo json_encode(['items' => $cart, 'count' => $count]);
        break;

    case 'toggle_wishlist':
        $id = (int)$_POST['product_id'];
        if (isset($_SESSION['wishlist'][$id])) {
            unset($_SESSION['wishlist'][$id]);
            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            $_SESSION['wishlist'][$id] = true;
            echo json_encode(['success' => true, 'action' => 'added']);
        }
        break;

    case 'get_wishlist':
        $wishlist = [];
        foreach ($_SESSION['wishlist'] as $id => $_) {
            $wishlist[] = [
                'product_id' => $id,
                'nom' => "Produit $id",
                'prix' => 50,
                'image_principale' => "images/produits/$id.jpg",
                'note_moyenne' => 4.5
            ];
        }
        echo json_encode(['items' => $wishlist, 'count' => count($wishlist)]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
}
