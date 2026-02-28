<?php
session_start();
require_once '../connexion.php';

// Vérification que l'utilisateur est admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $nom = trim($_POST['nom']);
        $description = trim($_POST['description']);
        if (!empty($nom)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO categories (nom, description) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ss", $nom, $description);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Catégorie ajoutée avec succès.";
            } else {
                $error = "Erreur lors de l'ajout.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Le nom de la catégorie est obligatoire.";
        }
    } elseif ($_POST['action'] === 'edit' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        $nom = trim($_POST['nom']);
        $description = trim($_POST['description']);
        if (!empty($nom)) {
            $stmt = mysqli_prepare($conn, "UPDATE categories SET nom = ?, description = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "ssi", $nom, $description, $id);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Catégorie modifiée avec succès.";
            } else {
                $error = "Erreur lors de la modification.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Le nom de la catégorie est obligatoire.";
        }
    }
}

// Suppression d'une catégorie
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Vérifier si des produits utilisent cette catégorie
    $check = mysqli_query($conn, "SELECT COUNT(*) as nb FROM produits WHERE categorie_id = $id");
    $nb = mysqli_fetch_assoc($check)['nb'];
    if ($nb > 0) {
        $error = "Impossible de supprimer cette catégorie car elle contient $nb produit(s).";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Catégorie supprimée.";
        } else {
            $error = "Erreur lors de la suppression.";
        }
        mysqli_stmt_close($stmt);
    }
}

// Récupération de toutes les catégories
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
$categories = mysqli_fetch_all($categories, MYSQLI_ASSOC);

// Récupération du nombre d'articles dans le panier (pour l'affichage)
$nb_articles = isset($_SESSION['panier']) ? array_sum($_SESSION['panier']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Catégories</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Styles supplémentaires pour l'admin */
        .admin-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn-action {
            margin: 0 2px;
        }
    </style>
</head>
<body>
    <!-- Navbar identique au site public -->
    <nav class="navbar navbar-expand-lg">
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
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../produits.php">Boutique</a>
                    </li>
                    <li class="nav-item position-relative">
                        <a class="nav-link" href="../panier.php">
                            Panier
                            <?php if ($nb_articles > 0): ?>
                                <span class="cart-badge"><?= $nb_articles ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <!-- Lien Admin visible uniquement pour les admins -->
                    <?php if (isset($_SESSION['admin_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                Admin
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="index.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="categories.php">Catégories</a></li>
                                <li><a class="dropdown-item" href="commandes.php">Commandes</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="deconnexion.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Gestion des catégories</h2>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Bouton d'ajout -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
            + Ajouter une catégorie
        </button>

        <!-- Tableau des catégories -->
        <div class="table-responsive admin-table">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Nombre de produits</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): 
                        $nb_produits = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM produits WHERE categorie_id = {$cat['id']}"))['nb'];
                    ?>
                    <tr>
                        <td>#<?= $cat['id'] ?></td>
                        <td><?= htmlspecialchars($cat['nom']) ?></td>
                        <td><?= htmlspecialchars(substr($cat['description'] ?? '', 0, 50)) ?>...</td>
                        <td><?= $nb_produits ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning btn-action" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars(addslashes($cat['nom'])) ?>', '<?= htmlspecialchars(addslashes($cat['description'] ?? '')) ?>')">Modifier</button>
                            <?php if ($nb_produits == 0): ?>
                                <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-danger btn-action" onclick="return confirm('Supprimer cette catégorie ?')">Supprimer</a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary btn-action" disabled title="Catégorie non vide">Supprimer</button>
                            <?php endif; ?>
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
                        <h5 class="modal-title">Ajouter une catégorie</h5>
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
                        <h5 class="modal-title">Modifier la catégorie</h5>
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
        function editCategory(id, nom, description) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nom').value = nom;
            document.getElementById('edit_description').value = description;
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }
    </script>
</body>
</html>