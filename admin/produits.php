<?php
session_start();
require_once '../connexion.php';

// Vérification admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Récupération de toutes les catégories pour le formulaire
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY nom");
$categories = mysqli_fetch_all($categories, MYSQLI_ASSOC);

// Traitement du formulaire d'ajout / modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Ajout d'un produit
        if ($_POST['action'] === 'add') {
            $nom = trim($_POST['nom']);
            $description = trim($_POST['description']);
            $prix = (float)$_POST['prix'];
            $categorie_id = (int)$_POST['categorie_id'];
            $image = trim($_POST['image']);

            if (!empty($nom) && $prix > 0) {
                $stmt = mysqli_prepare($conn, "INSERT INTO produits (nom, description, prix, categorie_id, image) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssdis", $nom, $description, $prix, $categorie_id, $image);
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Produit ajouté avec succès.";
                } else {
                    $error = "Erreur lors de l'ajout : " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = "Le nom et le prix sont obligatoires.";
            }
        }

        // Modification d'un produit
        elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $nom = trim($_POST['nom']);
            $description = trim($_POST['description']);
            $prix = (float)$_POST['prix'];
            $categorie_id = (int)$_POST['categorie_id'];
            $image = trim($_POST['image']);

            if (!empty($nom) && $prix > 0) {
                $stmt = mysqli_prepare($conn, "UPDATE produits SET nom = ?, description = ?, prix = ?, categorie_id = ?, image = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, "ssdisi", $nom, $description, $prix, $categorie_id, $image, $id);
                if (mysqli_stmt_execute($stmt)) {
                    $message = "Produit modifié avec succès.";
                } else {
                    $error = "Erreur lors de la modification : " . mysqli_error($conn);
                }
                mysqli_stmt_close($stmt);
            } else {
                $error = "Le nom et le prix sont obligatoires.";
            }
        }
    }
}

// Suppression d'un produit
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = mysqli_prepare($conn, "DELETE FROM produits WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        $message = "Produit supprimé.";
    } else {
        $error = "Erreur lors de la suppression.";
    }
    mysqli_stmt_close($stmt);
}

// Récupération de tous les produits avec leur catégorie
$produits = mysqli_query($conn, "SELECT p.*, c.nom as categorie_nom 
                                   FROM produits p 
                                   LEFT JOIN categories c ON p.categorie_id = c.id 
                                   ORDER BY p.id DESC");
$produits = mysqli_fetch_all($produits, MYSQLI_ASSOC);

$nb_articles = isset($_SESSION['panier']) ? array_sum($_SESSION['panier']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Produits</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-table th, .admin-table td {
            vertical-align: middle;
        }
        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
                    <?php if (isset($_SESSION['admin_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="index.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="produits.php">Produits</a></li>
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
        <h2 class="mb-4">Gestion des produits</h2>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Bouton d'ajout -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
            + Ajouter un produit
        </button>

        <!-- Tableau des produits -->
        <div class="table-responsive admin-table">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $p): ?>
                    <tr>
                        <td>#<?= $p['id'] ?></td>
                        <td>
                            <?php if (!empty($p['image'])): ?>
                                <img src="<?= htmlspecialchars($p['image']) ?>" alt="" class="product-img">
                            <?php else: ?>
                                <span class="text-muted">aucune</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['nom']) ?></td>
                        <td><?= htmlspecialchars($p['categorie_nom'] ?? 'Non classé') ?></td>
                        <td><?= number_format($p['prix'], 2, ',', ' ') ?> €</td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editProduct(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nom'])) ?>', '<?= htmlspecialchars(addslashes($p['description'] ?? '')) ?>', <?= $p['prix'] ?>, <?= (int)$p['categorie_id'] ?>, '<?= htmlspecialchars(addslashes($p['image'] ?? '')) ?>')">Modifier</button>
                            <a href="?delete=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Supprimer ce produit ?')">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal d'ajout -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Ajouter un produit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="prix" class="form-label">Prix (€) *</label>
                            <input type="number" step="0.01" class="form-control" id="prix" name="prix" required>
                        </div>
                        <div class="mb-3">
                            <label for="categorie_id" class="form-label">Catégorie</label>
                            <select class="form-control" id="categorie_id" name="categorie_id">
                                <option value="">-- Aucune --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">URL de l'image</label>
                            <input type="text" class="form-control" id="image" name="image" placeholder="https://...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal d'édition -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifier le produit</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_prix" class="form-label">Prix (€) *</label>
                            <input type="number" step="0.01" class="form-control" id="edit_prix" name="prix" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_categorie_id" class="form-label">Catégorie</label>
                            <select class="form-control" id="edit_categorie_id" name="categorie_id">
                                <option value="">-- Aucune --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_image" class="form-label">URL de l'image</label>
                            <input type="text" class="form-control" id="edit_image" name="image" placeholder="https://...">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Modifier</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <footer class="mt-5">
        <div class="container">
            <p>&copy; 2026 Ma Boutique. Tous droits réservés.</p>
            <p><a href="../mentions-legales.php">Mentions légales</a> | <a href="../cgv.php">CGV</a> | <a href="../contact.php">Contact</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(id, nom, description, prix, categorie_id, image) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nom').value = nom;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_prix').value = prix;
            document.getElementById('edit_categorie_id').value = categorie_id;
            document.getElementById('edit_image').value = image;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>