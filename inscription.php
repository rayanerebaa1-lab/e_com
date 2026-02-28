<?php
session_start();
require_once 'connexion.php';

if (isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($nom) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } else {
        // Vérifier si l'utilisateur existe déjà
        $check = mysqli_prepare($conn, "SELECT id FROM utilisateurs WHERE nom_utilisateur = ? OR email = ?");
        mysqli_stmt_bind_param($check, "ss", $nom, $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Ce nom ou email est déjà utilisé.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $insert = mysqli_prepare($conn, "INSERT INTO utilisateurs (nom_utilisateur, email, mot_de_passe) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($insert, "sss", $nom, $email, $hashed);
            if (mysqli_stmt_execute($insert)) {
                $success = "Inscription réussie ! Vous pouvez <a href='login.php'>vous connecter</a>.";
            } else {
                $error = "Erreur lors de l'inscription.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Ma Boutique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">MA BOUTIQUE</a>
        </div>
    </nav>
    <div class="form-container">
        <h2>Inscription</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="nom" class="form-label">Nom d'utilisateur</label>
                <input type="text" class="form-control" id="nom" name="nom" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmer</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
        </form>
        <p class="mt-3 text-center">Déjà un compte ? <a href="login.php">Connectez-vous</a></p>
    </div>
    <footer>
        <div class="container">
            <p>&copy; 2026 Ma Boutique</p>
        </div>
    </footer>
</body>
</html>