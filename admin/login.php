<?php
session_start();
include '../connexion.php'; // Votre fichier de connexion à la BDD

// Redirection si déjà connecté
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Vérification dans votre table admins (à créer si besoin)
        $stmt = mysqli_prepare($conn, "SELECT id, nom, password FROM admins WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($result);

        // Si vous stockez les mots de passe en clair (non recommandé) :
        if ($admin && $password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nom'] = $admin['nom'];
            header('Location: index.php');
            exit;
        }
        // Si vous utilisez password_hash(), décommentez la ligne suivante et commentez la précédente
        // if ($admin && password_verify($password, $admin['password'])) { ... }

        $error = 'Identifiants incorrects';
    } else {
        $error = 'Veuillez remplir tous les champs';
    }
}

// Récupération du nombre d'articles dans le panier pour affichage dans le header (optionnel)
$nb_articles = isset($_SESSION['panier']) ? array_sum($_SESSION['panier']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - Ma Boutique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css"> <!-- Votre style CSS -->
</head>
<body>
    <!-- Header identique à votre site -->
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
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenu de la page de connexion -->
    <div class="container mt-5" style="max-width: 400px;">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Espace Administrateur</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Se connecter</button>
                </form>
                <div class="text-center mt-3">
                    <a href="../index.php">← Retour à la boutique</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer identique à votre site -->
    <footer class="mt-5">
        <div class="container">
            <p>&copy; 2026 Ma Boutique. Tous droits réservés.</p>
            <p><a href="../mentions-legales.php">Mentions légales</a> | <a href="../cgv.php">CGV</a> | <a href="../contact.php">Contact</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>