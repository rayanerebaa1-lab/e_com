<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $action = $_POST['action'] ?? '';

    if ($action == 'plus') {
        $_SESSION['panier'][$id]++;
    } elseif ($action == 'moins') {
        $_SESSION['panier'][$id]--;
        if ($_SESSION['panier'][$id] <= 0) {
            unset($_SESSION['panier'][$id]);
        }
    } elseif ($action == 'supprimer') {
        unset($_SESSION['panier'][$id]);
    }
}
header('Location: panier.php');
exit();