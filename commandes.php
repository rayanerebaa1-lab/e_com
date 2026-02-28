<?php
session_start();
require_once 'connexion.php';

// Vérifier que le client est connecté
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit;
}

$client_id = $_SESSION['client_id'];

// Récupérer toutes les commandes du client
$commandes = [];
$sql = "SELECT c.*, 
               (SELECT COUNT(*) FROM lignes_commandes WHERE commande_id = c.id) as nb_articles
        FROM commandes c 
        WHERE c.client_id = ? 
        ORDER BY c.date_commande DESC";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $client_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $commandes[] = $row;
    }
    mysqli_stmt_close($stmt);
}

$nb_articles = isset($_SESSION['panier']) ? array_sum($_SESSION['panier']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
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
        .badge-payée { background: #28a745; color: #fff; }
        .product-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar (identique à celle de votre site) -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">MA BOUTIQUE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto search-form" action="recherche.php" method="get">
                    <input class="form-control me-2" type="search" name="q" placeholder="Rechercher un produit...">
                    <button class="btn" type="submit">Rechercher</button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
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
                    <?php if (isset($_SESSION['client_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <?= htmlspecialchars($_SESSION['client_nom']) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profil.php">Mon profil</a></li>
                                <li><a class="dropdown-item active" href="commandes.php">Mes commandes</a></li>
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

    <div class="container my-5">
        <h2 class="mb-4">Mes commandes</h2>

        <?php if (empty($commandes)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h3>Vous n'avez pas encore passé de commande.</h3>
                <a href="produits.php" class="btn btn-primary mt-3">Découvrir nos produits</a>
            </div>
        <?php else: ?>
            <?php foreach ($commandes as $cmd): 
                // Récupérer les détails de la commande
                $details = mysqli_query($conn, "SELECT lc.*, p.nom, p.image 
                                                FROM lignes_commandes lc 
                                                LEFT JOIN produits p ON lc.produit_id = p.id 
                                                WHERE lc.commande_id = " . $cmd['id']);
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h5>Commande #<?= $cmd['id'] ?></h5>
                            <p class="text-muted">Passée le <?= date('d/m/Y à H:i', strtotime($cmd['date_commande'])) ?></p>
                        </div>
                        <div>
                            <span class="badge-statut badge-<?= $cmd['statut'] ?>">
                                <?= ucfirst($cmd['statut']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produit</th>
                                        <th>Quantité</th>
                                        <th>Prix unitaire</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $sous_total = 0;
                                    while ($detail = mysqli_fetch_assoc($details)): 
                                        $total_ligne = $detail['prix_unitaire'] * $detail['quantite'];
                                        $sous_total += $total_ligne;
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($detail['image'])): ?>
                                                <img src="<?= htmlspecialchars($detail['image']) ?>" alt="" class="product-thumb">
                                            <?php endif; ?>
                                            <?= htmlspecialchars($detail['nom'] ?? 'Produit supprimé') ?>
                                        </td>
                                        <td><?= $detail['quantite'] ?></td>
                                        <td><?= number_format($detail['prix_unitaire'], 2, ',', ' ') ?> €</td>
                                        <td><?= number_format($total_ligne, 2, ',', ' ') ?> €</td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-end">Total :</th>
                                        <th><?= number_format($cmd['total'], 2, ',', ' ') ?> €</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="col-md-4">
                            <!-- Vous pouvez ajouter des informations supplémentaires ici -->
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p>&copy; <?= date('Y') ?> Ma Boutique. Tous droits réservés.</p>
            <p><a href="mentions-legales.php">Mentions légales</a> | <a href="cgv.php">CGV</a> | <a href="contact.php">Contact</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>