<?php
session_start();
require_once 'connexion.php';

// Initialisation sécurisée du panier
if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Calcul du nombre total d'articles dans le panier
$nb_articles = array_sum($_SESSION['panier']);

// Récupération des catégories
$categories = [];
$query_categories = "SELECT id, nom FROM categories ORDER BY id LIMIT 4";
if ($stmt = mysqli_prepare($conn, $query_categories)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Récupération des 8 derniers produits
$produits = [];
$query_produits = "SELECT id, nom, prix, image FROM produits ORDER BY id DESC LIMIT 8";
if ($stmt = mysqli_prepare($conn, $query_produits)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $produits[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Fonction de nettoyage des sorties
function clean_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Fonction pour choisir l'image selon la catégorie
function getCategoryImage($nom_categorie) {
    $nom = strtolower($nom_categorie);
    if (strpos($nom, 'vêtement') !== false || strpos($nom, 'vetement') !== false) {
        return 'https://static.cnews.fr/sites/default/files/styles/image_750_422/public/amanda-vick-ohwf6yuzoqk-unsplash_5f6c8a36eba2e.jpg?itok=0S6doBOI';
    } elseif (strpos($nom, 'accessoire') !== false) {
        return 'https://textileaddict.me/wp-content/uploads/2024/03/Collection-mode-accessoire-textileaddict.webp';
    } elseif (strpos($nom, 'chaussure') !== false) {
        return 'https://media.istockphoto.com/id/1279108197/fr/photo/vari%C3%A9t%C3%A9-de-chaussures-confortables-de-mode-de-femmes-de-toutes-saisons-sur-un-fond-l%C3%A9ger-vue.jpg?s=612x612&w=0&k=20&c=UZqf007bciqUY_KI1kC1v3yGwrM_ZvWZvla0moau7vQ=';
    } else {
        // Image par défaut si aucune correspondance
        return 'https://via.placeholder.com/300x200?text=' . urlencode($nom_categorie);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Boutique - Accueil</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://via.placeholder.com/1920x600?text=Boutique') center/cover no-repeat;
            padding: 100px 0;
            margin-bottom: 40px;
        }
        .category-card {
            transition: transform 0.3s;
            height: 100%;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .product-card {
            transition: box-shadow 0.3s;
            height: 100%;
        }
        .product-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
        .cart-badge {
            position: absolute;
            top: 0;
            left: 100%;
            transform: translate(-50%, -50%);
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            line-height: 1;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">MA BOUTIQUE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Formulaire de recherche -->
                <form class="d-flex mx-auto" action="recherche.php" method="get" role="search">
                    <input class="form-control me-2" type="search" name="q" placeholder="Rechercher un produit..." aria-label="Rechercher">
                    <button class="btn btn-outline-primary" type="submit">Rechercher</button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="produits.php">Boutique</a>
                    </li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="panier.php">
                            Panier
                            <?php if ($nb_articles > 0): ?>
                                <span class="cart-badge"><?= $nb_articles ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['client_id'], $_SESSION['client_nom'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?= clean_output($_SESSION['client_nom']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="profil.php">Mon profil</a></li>
                                <li><a class="dropdown-item" href="commandes.php">Mes commandes</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="deconnexion.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="inscription.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Section Héro -->
    <section class="hero text-white text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Nouvelle collection 2026</h1>
            <p class="lead">Des pièces uniques pour un style affirmé.</p>
            <a href="produits.php" class="btn btn-primary btn-lg">Découvrir</a>
        </div>
    </section>

    <main class="container my-5">
        <!-- Affichage des catégories -->
        <?php if (!empty($categories)): ?>
            <h2 class="text-center mb-4">Nos catégories</h2>
            <div class="row g-4 mb-5 justify-content-center">
                <?php foreach ($categories as $cat): ?>
                    <div class="col-md-3">
                        <a href="produits.php?categorie=<?= (int)$cat['id'] ?>" class="text-decoration-none">
                            <div class="card h-100 shadow-sm category-card">
                                <!-- Image spécifique selon la catégorie -->
                                <img src="<?= getCategoryImage($cat['nom']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= clean_output($cat['nom']) ?>"
                                     onerror="this.src='https://via.placeholder.com/300x200?text=<?= urlencode($cat['nom']) ?>'">
                                <div class="card-body text-center">
                                    <h3 class="card-title h5"><?= clean_output($cat['nom']) ?></h3>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Affichage des nouveautés -->
        <h2 class="text-center mb-4">Nouveautés</h2>
        <?php if (!empty($produits)): ?>
            <div class="row g-4">
                <?php foreach ($produits as $p): ?>
                    <div class="col-md-3">
                        <div class="card h-100 shadow-sm product-card">
                            <a href="produit.php?id=<?= (int)$p['id'] ?>" class="text-decoration-none text-dark">
                                <img src="<?= !empty($p['image']) ? clean_output($p['image']) : 'https://via.placeholder.com/300x200?text=Produit' ?>" 
                                     class="card-img-top" 
                                     alt="<?= clean_output($p['nom']) ?>"
                                     onerror="this.src='https://via.placeholder.com/300x200?text=Image+non+disponible'">
                                <div class="card-body">
                                    <h3 class="card-title h6"><?= clean_output($p['nom']) ?></h3>
                                    <p class="card-text fw-bold text-primary"><?= number_format($p['prix'], 2, ',', ' ') ?> €</p>
                                </div>
                            </a>
                            <div class="card-footer bg-transparent border-0 pb-3">
                                <form action="ajouter_panier.php" method="POST">
                                    <input type="hidden" name="produit_id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="btn btn-primary w-100">Ajouter au panier</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center">Aucun produit disponible pour le moment.</p>
        <?php endif; ?>
    </main>

    <!-- Pied de page -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="mb-1">&copy; <?= date('Y') ?> Ma Boutique. Tous droits réservés.</p>
            <p class="mb-0">
                <a href="mentions-legales.php" class="text-decoration-none me-2">Mentions légales</a> |
                <a href="cgv.php" class="text-decoration-none mx-2">CGV</a> |
                <a href="contact.php" class="text-decoration-none ms-2">Contact</a>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>