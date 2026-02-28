<?php
session_start();
require_once '../connexion.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: commandes.php');
    exit;
}
$commande_id = (int)$_GET['id'];

// Récupération de la commande
$stmt = mysqli_prepare($conn, "SELECT c.*, cl.nom, cl.email, cl.telephone, cl.adresse 
                                FROM commandes c 
                                LEFT JOIN clients cl ON c.client_id = cl.id 
                                WHERE c.id = ?");
mysqli_stmt_bind_param($stmt, "i", $commande_id);
mysqli_stmt_execute($stmt);
$commande = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$commande) {
    header('Location: commandes.php');
    exit;
}

// Récupération des lignes de commande
$lignes = mysqli_query($conn, "SELECT lc.*, p.nom, p.image 
                                FROM lignes_commandes lc 
                                LEFT JOIN produits p ON lc.produit_id = p.id 
                                WHERE lc.commande_id = $commande_id");

// Mise à jour du statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['statut'])) {
    $new_statut = $_POST['statut'];
    $statuts_valides = ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'];
    if (in_array($new_statut, $statuts_valides)) {
        $stmt = mysqli_prepare($conn, "UPDATE commandes SET statut = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "si", $new_statut, $commande_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $commande['statut'] = $new_statut; // mise à jour locale
        $success = "Statut mis à jour.";
    }
}

$nb_articles = isset($_SESSION['panier']) ? array_sum($_SESSION['panier']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande #<?= $commande_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .badge-statut { padding: 5px 10px; border-radius: 20px; }
        .badge-en_attente { background: #ffc107; }
        .badge-confirmee { background: #17a2b8; color: #fff; }
        .badge-expediee { background: #28a745; color: #fff; }
        .badge-livree { background: #28a745; color: #fff; }
        .badge-annulee { background: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <!-- Même navbar -->
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
                    <li class="nav-item position-relative"><a class="nav-link" href="../panier.php">Panier
                        <?php if ($nb_articles > 0): ?><span class="cart-badge"><?= $nb_articles ?></span><?php endif; ?>
                    </a></li>
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
        <h2>Détail de la commande #<?= $commande_id ?></h2>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Informations client</div>
                    <div class="card-body">
                        <p><strong>Nom :</strong> <?= htmlspecialchars($commande['nom'] ?? 'Non renseigné') ?></p>
                        <p><strong>Email :</strong> <?= htmlspecialchars($commande['email'] ?? '') ?></p>
                        <p><strong>Téléphone :</strong> <?= htmlspecialchars($commande['telephone'] ?? '') ?></p>
                        <p><strong>Adresse :</strong> <?= nl2br(htmlspecialchars($commande['adresse'] ?? '')) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-3">
                    <div class="card-header">Récapitulatif</div>
                    <div class="card-body">
                        <p><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($commande['date_commande'])) ?></p>
                        <p><strong>Total :</strong> <?= number_format($commande['total'], 2, ',', ' ') ?> €</p>
                        <p><strong>Statut actuel :</strong> 
                            <span class="badge-statut badge-<?= $commande['statut'] ?>">
                                <?= ucfirst($commande['statut']) ?>
                            </span>
                        </p>
                        <form method="POST" class="mt-2">
                            <label for="statut">Modifier le statut :</label>
                            <select name="statut" id="statut" class="form-select form-select-sm" style="width: auto; display: inline-block;">
                                <option value="en_attente" <?= $commande['statut'] == 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="confirmee" <?= $commande['statut'] == 'confirmee' ? 'selected' : '' ?>>Confirmée</option>
                                <option value="expediee" <?= $commande['statut'] == 'expediee' ? 'selected' : '' ?>>Expédiée</option>
                                <option value="livree" <?= $commande['statut'] == 'livree' ? 'selected' : '' ?>>Livrée</option>
                                <option value="annulee" <?= $commande['statut'] == 'annulee' ? 'selected' : '' ?>>Annulée</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Mettre à jour</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <h4>Produits commandés</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th>Prix unitaire</th>
                    <th>Quantité</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_lignes = 0;
                while ($ligne = mysqli_fetch_assoc($lignes)): 
                    $sous_total = $ligne['prix_unitaire'] * $ligne['quantite'];
                    $total_lignes += $sous_total;
                ?>
                <tr>
                    <td>
                        <?php if ($ligne['image']): ?>
                            <img src="<?= htmlspecialchars($ligne['image']) ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; margin-right: 10px;">
                        <?php endif; ?>
                        <?= htmlspecialchars($ligne['nom'] ?? 'Produit supprimé') ?>
                    </td>
                    <td><?= number_format($ligne['prix_unitaire'], 2, ',', ' ') ?> €</td>
                    <td><?= $ligne['quantite'] ?></td>
                    <td><?= number_format($sous_total, 2, ',', ' ') ?> €</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">Total commande :</th>
                    <th><?= number_format($commande['total'], 2, ',', ' ') ?> €</th>
                </tr>
            </tfoot>
        </table>
        <a href="commandes.php" class="btn btn-secondary">← Retour aux commandes</a>
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