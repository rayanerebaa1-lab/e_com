<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produit_id'])) {
    $produit_id = (int)$_POST['produit_id'];
    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }
    // Gestion simple sans taille pour l'instant
    if (isset($_SESSION['panier'][$produit_id])) {
        $_SESSION['panier'][$produit_id]++;
    } else {
        $_SESSION['panier'][$produit_id] = 1;
    }
    header('Location: panier.php');
    exit();
}
header('Location: index.php');
exit();