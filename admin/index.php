<?php
session_start();
include '../connexion.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Récupération des statistiques (exemple)
$nb_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM categories"))['total'];
$nb_produits = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM produits"))['total'];
$nb_commandes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM commandes"))['total'];

$nb_articles = isset($_SESSION['panier']) ? array_sum($_SESSION['panier']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Tableau de bord</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <!-- Header identique -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="../index.php">MA BOUTIQUE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto search-form" action="../recherche.php" method="get">
                    <input class="form-control me-2" type="search" name="q" placeholder="Rechercher...">
                    <button class="btn" type="submit">Rechercher</button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="../produits.php">Boutique</a></li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="../panier.php">Panier
                            <?php if ($nb_articles > 0): ?>
                                <span class="cart-badge"><?= $nb_articles ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Admin (<?= htmlspecialchars($_SESSION['admin_nom']) ?>)
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="categories.php">Catégories</a></li>
                            <li><a class="dropdown-item" href="produits.php">Produits</a></li>
                            <li><a class="dropdown-item" href="commandes.php">Commandes</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="deconnexion.php">Déconnexion</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="container mt-4">
        <h2>Tableau de bord</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-header">Catégories</div>
                    <div class="card-body">
                        <h5 class="card-title"><?= $nb_categories ?></h5>
                        <a href="categories.php" class="btn btn-light">Gérer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-header">Produits</div>
                    <div class="card-body">
                        <h5 class="card-title"><?= $nb_produits ?></h5>
                        <a href="produits.php" class="btn btn-light">Gérer</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3">
                    <div class="card-header">Commandes</div>
                    <div class="card-body">
                        <h5 class="card-title"><?= $nb_commandes ?></h5>
                        <a href="commandes.php" class="btn btn-light">Voir</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5">
        <div class="container">
            <p>&copy; 2026 Ma Boutique. Tous droits réservés.</p>
            <p><a href="../mentions-legales.php">Mentions légales</a> | <a href="../cgv.php">CGV</a> | <a href="../contact.php">Contact</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>