<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: connexion_admin.php');
    exit();
}
include '../connexion.php';

$sql = "SELECT p.*, c.nom as categorie_nom FROM produits p LEFT JOIN categories c ON p.categorie_id = c.id ORDER BY p.id DESC";
$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des produits</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #f1f5f9; }
        .navbar { background: #1e293b; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark">
        <div class="container">
            <span class="navbar-brand fw-bold">Admin - Produits</span>
            <a href="accueil_admin.php" class="btn btn-outline-light rounded-pill"><i class="bi bi-arrow-left"></i> Retour</a>
        </div>
    </nav>
    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-3">
            <h2>Liste des produits</h2>
            <a href="ajouter_produit.php" class="btn btn-success rounded-pill"><i class="bi bi-plus-circle"></i> Nouveau produit</a>
        </div>
        <div class="table-responsive bg-white rounded-4 shadow p-3">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>Catégorie</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><img src="<?= htmlspecialchars($row['image']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px;"></td>
                        <td><?= htmlspecialchars($row['nom']) ?></td>
                        <td><?= number_format($row['prix'], 2, ',', ' ') ?> €</td>
                        <td><span class="badge bg-<?= $row['stock'] > 0 ? 'success' : 'danger' ?>"><?= $row['stock'] ?></span></td>
                        <td><?= htmlspecialchars($row['categorie_nom'] ?? 'Aucune') ?></td>
                        <td>
                            <a href="modifier_produit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning rounded-pill"><i class="bi bi-pencil"></i> Modifier</a>
                            <a href="supprimer_produit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger rounded-pill" onclick="return confirm('Supprimer ?')"><i class="bi bi-trash"></i> Supprimer</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>