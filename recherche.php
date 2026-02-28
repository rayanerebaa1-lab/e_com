<?php
session_start();
include 'connexion.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$produits = [];
if (!empty($q)) {
    $search = "%$q%";
    $stmt = mysqli_prepare($conn, "SELECT * FROM produits WHERE nom LIKE ? OR description LIKE ?");
    mysqli_stmt_bind_param($stmt, "ss", $search, $search);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $produits = mysqli_fetch_all($result, MYSQLI_ASSOC);
}

$nb_articles = array_sum($_SESSION['panier'] ?? []);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recherche - Ma Boutique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <!-- même navbar que index.php -->
    </nav>
    <main class="container my-5">
        <h2>Résultats pour "<?= htmlspecialchars($q) ?>"</h2>
        <?php if (empty($produits)): ?>
            <p>Aucun produit trouvé.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($produits as $p): ?>
                <a href="produit.php?id=<?= $p['id'] ?>" class="product-card">
                    <img src="<?= $p['image'] ?>" alt="<?= $p['nom'] ?>">
                    <div class="product-info">
                        <h3><?= $p['nom'] ?></h3>
                        <p class="price"><?= number_format($p['prix'], 2, ',', ' ') ?> €</p>
                        <form action="ajouter_panier.php" method="POST" onclick="event.stopPropagation();">
                            <input type="hidden" name="produit_id" value="<?= $p['id'] ?>">
                            <button class="btn">Ajouter</button>
                        </form>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <footer>...</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>