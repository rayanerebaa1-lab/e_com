<?php
session_start();
include 'connexion.php';

if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

$produits = [];
$ids = array_keys($_SESSION['panier']);
if (!empty($ids)) {
    $in = implode(',', $ids);
    $result = mysqli_query($conn, "SELECT * FROM produits WHERE id IN ($in)");
    while ($row = mysqli_fetch_assoc($result)) {
        $produits[$row['id']] = $row;
    }
}

$total = 0;
foreach ($_SESSION['panier'] as $id => $qte) {
    if (isset($produits[$id])) {
        $total += $produits[$id]['prix'] * $qte;
    }
}
$nb_articles = array_sum($_SESSION['panier']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Panier - Ma Boutique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">MA BOUTIQUE</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto search-form" action="recherche.php" method="get">
                    <input class="form-control me-2" type="search" name="q" placeholder="Rechercher...">
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
                        <a class="nav-link active" href="panier.php">
                            Panier
                            <?php if ($nb_articles > 0): ?>
                                <span class="cart-badge"><?= $nb_articles ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <!-- reste navbar -->
                </ul>
            </div>
        </div>
    </nav>

    <main class="container my-5">
        <h2>Votre panier</h2>
        <?php if (empty($_SESSION['panier'])): ?>
            <p>Votre panier est vide.</p>
            <a href="produits.php" class="btn btn-primary">Continuer mes achats</a>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Prix</th>
                        <th>Quantité</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($_SESSION['panier'] as $id => $qte): ?>
                    <?php if (isset($produits[$id])): $p = $produits[$id]; ?>
                    <tr>
                        <td>
                            <img src="<?= $p['image'] ?>" alt="">
                            <?= $p['nom'] ?>
                        </td>
                        <td><?= number_format($p['prix'], 2, ',', ' ') ?> €</td>
                        <td>
                            <div class="quantity-control">
                                <form action="modifier_panier.php" method="POST">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <input type="hidden" name="action" value="moins">
                                    <button type="submit">-</button>
                                </form>
                                <span><?= $qte ?></span>
                                <form action="modifier_panier.php" method="POST">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <input type="hidden" name="action" value="plus">
                                    <button type="submit">+</button>
                                </form>
                            </div>
                        </td>
                        <td><?= number_format($p['prix'] * $qte, 2, ',', ' ') ?> €</td>
                        <td>
                            <form action="modifier_panier.php" method="POST">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="action" value="supprimer">
                                <button class="btn btn-danger btn-sm">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <h3>Total : <?= number_format($total, 2, ',', ' ') ?> €</h3>
            <a href="paiement.php" class="btn btn-success">Passer la commande</a>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2026 Ma Boutique</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>