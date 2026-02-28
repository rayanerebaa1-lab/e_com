<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: connexion_admin.php');
    exit();
}
include '../connexion.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM produits WHERE id = $id");
}
header('Location: liste_produits.php?msg=deleted');
exit();