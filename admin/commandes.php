<?php
session_start();
require_once '../connexion.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Filtres
$statut_filter = isset($_GET['statut']) ? $_GET['statut'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Construction de la requête
$sql = "SELECT c.*, cl.nom as client_nom, cl.email 
        FROM commandes c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($statut_filter)) {
    $sql .= " AND c.statut = ?";
    $params[] = $statut_filter;
    $types .= "s";
}
if (!empty($date_filter)) {
    $sql .= " AND DATE(c.date_commande) = ?";
    $params[] = $date_filter;
    $types .= "s";
}
$sql .= " ORDER BY c.date_commande DESC";

// Exécution
$commandes = [];
if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $commandes[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $commandes[] = $row;
    }
}

// Statistiques des statuts
$stats = [];
$statuts = ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'];
foreach ($statuts as $s) {
    $res = mysqli_query($conn, "SELECT COUNT(*) as total FROM commandes WHERE statut = '$s'");
    $stats[$s] = mysqli_fetch_assoc($res)['total'];
}

$nb_articles = isset($_SESSION['panier']) ? array_sum($_SESSION['panier']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Commandes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .badge-statut {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: normal;
        }
        .badge-en_attente { background: #ffc107; color: #000; }
        .badge-confirmee { background: #17a2b8; color: #fff; }
        .badge-expediee { background: #28a745; color: #fff; }
        .badge-livree { background: #28a745; color: #fff; }
        .badge-annulee { background: #dc3545; color: #fff; }
        .filter-link {
            display: inline-block;
            padding: 5px 15px;
            margin-right: 5px;
            border-radius: 20px;
            background: #f1f1f1;
            color: #333;
            text-decoration: none;
        }
        .filter-link.active {
            background: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <!-- Même navbar que précédemment -->
        <div class="container">
            <a class="navbar-brand" href="../index.php">MA BOUTIQUE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto search-form" action="../recherche.php" method="get">
                    <input class="form-control me-2" type="search" name="q" placeholder="Rechercher un produit...">
                    <button class="btn" type="submit">Rechercher</button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="../produits.php">Boutique</a></li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="../panier.php">Panier
                            <?php if ($nb_articles > 0): ?><span class="cart-badge"><?= $nb_articles ?></span><?php endif; ?>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['admin_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="index.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="categories.php">Catégories</a></li>
                                <li><a class="dropdown-item" href="commandes.php">Commandes</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="deconnexion.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Gestion des commandes</h2>

        <!-- Filtres rapides par statut -->
        <div class="mb-3">
            <a href="commandes.php" class="filter-link <?= empty($statut_filter) ? 'active' : '' ?>">Toutes</a>
            <?php foreach ($statuts as $s): ?>
                <a href="?statut=<?= $s ?>" class="filter-link <?= $statut_filter === $s ? 'active' : '' ?>">
                    <?= ucfirst($s) ?> (<?= $stats[$s] ?>)
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Filtre par date -->
        <form method="get" class="row g-3 mb-4">
            <div class="col-auto">
                <label for="date" class="col-form-label">Date :</label>
            </div>
            <div class="col-auto">
                <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </div>
            <?php if (!empty($statut_filter)): ?>
                <input type="hidden" name="statut" value="<?= htmlspecialchars($statut_filter) ?>">
            <?php endif; ?>
        </form>

        <!-- Liste des commandes -->
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $cmd): ?>
                    <tr>
                        <td>#<?= $cmd['id'] ?></td>
                        <td><?= htmlspecialchars($cmd['client_nom'] ?? 'Compte supprimé') ?><br><small><?= htmlspecialchars($cmd['email'] ?? '') ?></small></td>
                        <td><?= date('d/m/Y H:i', strtotime($cmd['date_commande'])) ?></td>
                        <td><?= number_format($cmd['total'], 2, ',', ' ') ?> €</td>
                        <td>
                            <span class="badge-statut badge-<?= $cmd['statut'] ?>">
                                <?= ucfirst($cmd['statut']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="commande.php?id=<?= $cmd['id'] ?>" class="btn btn-sm btn-primary">Détails</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer class="mt-5">
        <div class="container">
            <p>&copy; 2026 Ma Boutique. Tous droits réservés.</p>
            <p><a href="../mentions-legales.php">Mentions légales</a> | <a href="../cgv.php">CGV</a> | <a href="../contact.php">Contact</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>