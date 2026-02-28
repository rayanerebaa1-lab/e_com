<?php
session_start();
require_once 'connexion.php';

// Initialisation sécurisée du panier
if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Récupération de la catégorie sélectionnée (si présente)
$categorie_id = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;

// Récupération du terme de recherche (si présent)
$recherche = isset($_GET['q']) ? trim($_GET['q']) : '';

// Récupération du tri (par défaut : date décroissante)
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'date_desc';
$order_by = 'p.id DESC'; // Par défaut
switch ($tri) {
    case 'prix_asc':
        $order_by = 'p.prix ASC';
        break;
    case 'prix_desc':
        $order_by = 'p.prix DESC';
        break;
    case 'nom_asc':
        $order_by = 'p.nom ASC';
        break;
    case 'nom_desc':
        $order_by = 'p.nom DESC';
        break;
    case 'date_desc':
    default:
        $order_by = 'p.id DESC';
        break;
}

// Récupération de toutes les catégories pour le menu de filtrage
$categories = [];
$query_categories = "SELECT id, nom FROM categories ORDER BY nom";
if ($stmt = mysqli_prepare($conn, $query_categories)) {
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    mysqli_stmt_close($stmt);
}

// Construction de la requête produits avec filtres
$produits = [];
$params = [];
$types = "";
$sql = "SELECT p.*, c.nom as categorie_nom 
        FROM produits p 
        LEFT JOIN categories c ON p.categorie_id = c.id 
        WHERE 1=1";

// Filtre par catégorie
if ($categorie_id > 0) {
    $sql .= " AND p.categorie_id = ?";
    $params[] = $categorie_id;
    $types .= "i";
}

// Filtre par recherche
if (!empty($recherche)) {
    $sql .= " AND (p.nom LIKE ? OR p.description LIKE ?)";
    $search_term = "%$recherche%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

// Ajout du tri
$sql .= " ORDER BY $order_by";

// Exécution de la requête
if (!empty($params)) {
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $produits[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
} else {
    // Pas de filtres
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $produits[] = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

// Fonction de nettoyage des sorties
function clean_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Fonction pour garder les paramètres dans les URLs
function build_url($params) {
    $current = $_GET;
    $current = array_merge($current, $params);
    return '?' . http_build_query($current);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma Boutique - Tous nos produits</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-small {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://via.placeholder.com/1920x300?text=Produits') center/cover no-repeat;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        .product-card {
            transition: all 0.3s;
            height: 100%;
            border: 1px solid rgba(0,0,0,0.125);
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
            border-color: transparent;
        }
        .product-img-container {
            height: 200px;
            overflow: hidden;
            background-color: #f8f9fa;
        }
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        .product-card:hover .product-img {
            transform: scale(1.05);
        }
        .category-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgba(0,0,0,0.7);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            z-index: 1;
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
        .filter-sidebar {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .filter-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        .active-filter {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
        }
        .pagination {
            justify-content: center;
            margin-top: 40px;
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
                <form class="d-flex mx-auto" action="produits.php" method="get" role="search">
                    <input class="form-control me-2" type="search" name="q" placeholder="Rechercher un produit..." aria-label="Rechercher" value="<?= clean_output($recherche) ?>">
                    <?php if ($categorie_id > 0): ?>
                        <input type="hidden" name="categorie" value="<?= $categorie_id ?>">
                    <?php endif; ?>
                    <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="produits.php">Boutique</a>
                    </li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="panier.php">
                            Panier <i class="fas fa-shopping-cart"></i>
                            <?php if (!empty($_SESSION['panier'])): 
                                $nb_articles = array_sum($_SESSION['panier']); ?>
                                <span class="cart-badge"><?= $nb_articles ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['client_id'], $_SESSION['client_nom'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?= clean_output($_SESSION['client_nom']) ?>
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
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="inscription.php"><i class="fas fa-user-plus"></i> Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Section Héro (petite) -->
    <section class="hero-small text-white text-center">
        <div class="container">
            <h1 class="display-5 fw-bold">Tous nos produits</h1>
            <?php if ($categorie_id > 0): 
                // Récupérer le nom de la catégorie sélectionnée
                $cat_nom = "";
                foreach ($categories as $cat) {
                    if ($cat['id'] == $categorie_id) {
                        $cat_nom = $cat['nom'];
                        break;
                    }
                }
            ?>
                <p class="lead">Catégorie : <?= clean_output($cat_nom) ?></p>
            <?php elseif (!empty($recherche)): ?>
                <p class="lead">Résultats pour : "<?= clean_output($recherche) ?>"</p>
            <?php else: ?>
                <p class="lead">Découvrez notre sélection de produits</p>
            <?php endif; ?>
        </div>
    </section>

    <main class="container my-5">
        <div class="row">
            <!-- Sidebar des filtres -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar">
                    <h3 class="filter-title">Filtrer par</h3>
                    
                    <!-- Filtre par catégorie -->
                    <div class="mb-4">
                        <h4 class="h6 fw-bold mb-3">Catégories</h4>
                        <div class="list-group">
                            <a href="produits.php<?= !empty($recherche) ? '?q='.urlencode($recherche) : '' ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $categorie_id == 0 ? 'active-filter active' : '' ?>">
                                Toutes les catégories
                                <span class="badge bg-secondary rounded-pill"><?= count($categories) ?></span>
                            </a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="produits.php?categorie=<?= $cat['id'] ?><?= !empty($recherche) ? '&q='.urlencode($recherche) : '' ?>" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $categorie_id == $cat['id'] ? 'active-filter active' : '' ?>">
                                    <?= clean_output($cat['nom']) ?>
                                    <!-- Compter le nombre de produits dans cette catégorie -->
                                    <?php
                                    $count_sql = "SELECT COUNT(*) as total FROM produits WHERE categorie_id = ?";
                                    $count = 0;
                                    if ($count_stmt = mysqli_prepare($conn, $count_sql)) {
                                        mysqli_stmt_bind_param($count_stmt, "i", $cat['id']);
                                        mysqli_stmt_execute($count_stmt);
                                        $count_result = mysqli_stmt_get_result($count_stmt);
                                        $count_row = mysqli_fetch_assoc($count_result);
                                        $count = $count_row['total'];
                                        mysqli_stmt_close($count_stmt);
                                    }
                                    ?>
                                    <span class="badge bg-secondary rounded-pill"><?= $count ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Filtre par prix (optionnel) -->
                    <div class="mb-4">
                        <h4 class="h6 fw-bold mb-3">Prix</h4>
                        <form action="produits.php" method="get">
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control" name="prix_min" placeholder="Min" min="0" step="0.01" value="<?= isset($_GET['prix_min']) ? (float)$_GET['prix_min'] : '' ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control" name="prix_max" placeholder="Max" min="0" step="0.01" value="<?= isset($_GET['prix_max']) ? (float)$_GET['prix_max'] : '' ?>">
                                </div>
                            </div>
                            <?php if ($categorie_id > 0): ?>
                                <input type="hidden" name="categorie" value="<?= $categorie_id ?>">
                            <?php endif; ?>
                            <?php if (!empty($recherche)): ?>
                                <input type="hidden" name="q" value="<?= clean_output($recherche) ?>">
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary w-100 mt-3">Appliquer</button>
                        </form>
                    </div>

                    <!-- Réinitialiser les filtres -->
                    <a href="produits.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-undo"></i> Réinitialiser les filtres
                    </a>
                </div>
            </div>

            <!-- Liste des produits -->
            <div class="col-lg-9">
                <!-- Barre de tri et résultats -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <p class="mb-0"><?= count($produits) ?> produit(s) trouvé(s)</p>
                    <div class="d-flex align-items-center">
                        <label for="tri" class="me-2">Trier par :</label>
                        <select id="tri" class="form-select" style="width: auto;" onchange="window.location.href=this.value">
                            <option value="<?= build_url(['tri' => 'date_desc'])?>" <?= $tri == 'date_desc' ? 'selected' : '' ?>>Nouveautés</option>
                            <option value="<?= build_url(['tri' => 'prix_asc'])?>" <?= $tri == 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
                            <option value="<?= build_url(['tri' => 'prix_desc'])?>" <?= $tri == 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                            <option value="<?= build_url(['tri' => 'nom_asc'])?>" <?= $tri == 'nom_asc' ? 'selected' : '' ?>>Nom A-Z</option>
                            <option value="<?= build_url(['tri' => 'nom_desc'])?>" <?= $tri == 'nom_desc' ? 'selected' : '' ?>>Nom Z-A</option>
                        </select>
                    </div>
                </div>

                <!-- Affichage des produits -->
                <?php if (!empty($produits)): ?>
                    <div class="row g-4">
                        <?php foreach ($produits as $p): ?>
                            <div class="col-md-6 col-xl-4">
                                <div class="card product-card">
                                    <div class="position-relative">
                                        <?php if (isset($p['categorie_nom']) && !empty($p['categorie_nom'])): ?>
                                            <span class="category-badge"><?= clean_output($p['categorie_nom']) ?></span>
                                        <?php endif; ?>
                                        <a href="produit.php?id=<?= (int)$p['id'] ?>">
                                            <div class="product-img-container">
                                                <img src="<?= !empty($p['image']) ? clean_output($p['image']) : 'https://via.placeholder.com/300x200?text=Produit' ?>" class="product-img" alt="<?= clean_output($p['nom']) ?>">
                                            </div>
                                        </a>
                                    </div>
                                    <div class="card-body">
                                        <h3 class="card-title h6"><?= clean_output($p['nom']) ?></h3>
                                        <?php if (isset($p['description'])): ?>
                                            <p class="card-text small text-muted"><?= clean_output(substr($p['description'], 0, 80)) ?>...</p>
                                        <?php endif; ?>
                                        <p class="card-text fw-bold text-primary h5"><?= number_format($p['prix'], 2, ',', ' ') ?> €</p>
                                    </div>
                                    <div class="card-footer bg-transparent border-0 pb-3">
                                        <form action="ajouter_panier.php" method="POST">
                                            <input type="hidden" name="produit_id" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-cart-plus"></i> Ajouter au panier
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination (optionnelle) -->
                    <nav aria-label="Navigation des pages" class="pagination">
                        <ul class="pagination">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Précédent</a>
                            </li>
                            <li class="page-item active" aria-current="page">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Suivant</a>
                            </li>
                        </ul>
                    </nav>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h3>Aucun produit trouvé</h3>
                        <p class="text-muted">Essayez de modifier vos filtres ou effectuer une nouvelle recherche.</p>
                        <a href="produits.php" class="btn btn-primary">Voir tous les produits</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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