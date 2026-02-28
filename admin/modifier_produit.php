<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: connexion_admin.php');
    exit();
}
include '../connexion.php';

$cat_sql = "SELECT * FROM categories ORDER BY nom";
$cat_result = mysqli_query($conn, $cat_sql);
$categories = mysqli_fetch_all($cat_result, MYSQLI_ASSOC);

if (!isset($_GET['id'])) {
    header('Location: liste_produits.php');
    exit();
}
$id = (int)$_GET['id'];
$sql = "SELECT * FROM produits WHERE id = $id";
$result = mysqli_query($conn, $sql);
$produit = mysqli_fetch_assoc($result);
if (!$produit) {
    header('Location: liste_produits.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $prix = (float)$_POST['prix'];
    $image = mysqli_real_escape_string($conn, $_POST['image']);
    $categorie_id = !empty($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : 'NULL';
    $stock = (int)$_POST['stock'];
    
    $sql = "UPDATE produits SET nom='$nom', description='$description', prix='$prix', image='$image', categorie_id=$categorie_id, stock=$stock WHERE id=$id";
    if (mysqli_query($conn, $sql)) {
        header('Location: liste_produits.php?msg=updated');
        exit();
    } else {
        $error = "Erreur : " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un produit</title>
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
            <span class="navbar-brand fw-bold">Modifier un produit</span>
            <a href="liste_produits.php" class="btn btn-outline-light rounded-pill">Retour</a>
        </div>
    </nav>
    <div class="container mt-4" style="max-width: 600px;">
        <div class="card shadow border-0 rounded-4 p-4">
            <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label for="nom" class="form-label fw-semibold">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($produit['nom']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label fw-semibold">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required><?= htmlspecialchars($produit['description']) ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="prix" class="form-label fw-semibold">Prix (€)</label>
                    <input type="number" step="0.01" class="form-control" id="prix" name="prix" value="<?= $produit['prix'] ?>" required>
                </div>
                <div class="mb-3">
                    <label for="categorie" class="form-label fw-semibold">Catégorie</label>
                    <select class="form-control" id="categorie" name="categorie_id">
                        <option value="">-- Aucune --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($produit['categorie_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="stock" class="form-label fw-semibold">Stock</label>
                    <input type="number" class="form-control" id="stock" name="stock" value="<?= $produit['stock'] ?>" min="0">
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label fw-semibold">URL de l'image</label>
                    <input type="text" class="form-control" id="image" name="image" value="<?= htmlspecialchars($produit['image']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 rounded-pill"><i class="bi bi-save"></i> Enregistrer</button>
            </form>
        </div>
    </div>
</body>
</html>