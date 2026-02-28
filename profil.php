<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['client_id'];
$message = '';
$error = '';

$sql = "SELECT nom_utilisateur, email FROM utilisateurs WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if (empty($nom) || empty($email)) {
        $error = "Le nom et l'email sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } else {
        // Vérifier unicité
        $check = mysqli_prepare($conn, "SELECT id FROM utilisateurs WHERE (nom_utilisateur = ? OR email = ?) AND id != ?");
        mysqli_stmt_bind_param($check, "ssi", $nom, $email, $user_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Ce nom ou email est déjà utilisé.";
        } else {
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    $error = "Le mot de passe doit contenir au moins 6 caractères.";
                } elseif ($password !== $confirm) {
                    $error = "Les mots de passe ne correspondent pas.";
                } else {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $update = mysqli_prepare($conn, "UPDATE utilisateurs SET nom_utilisateur = ?, email = ?, mot_de_passe = ? WHERE id = ?");
                    mysqli_stmt_bind_param($update, "sssi", $nom, $email, $hashed, $user_id);
                }
            } else {
                $update = mysqli_prepare($conn, "UPDATE utilisateurs SET nom_utilisateur = ?, email = ? WHERE id = ?");
                mysqli_stmt_bind_param($update, "ssi", $nom, $email, $user_id);
            }
            if (empty($error)) {
                if (mysqli_stmt_execute($update)) {
                    $message = "Informations mises à jour.";
                    $_SESSION['client_nom'] = $nom;
                } else {
                    $error = "Erreur lors de la mise à jour.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil - Ma Boutique</title>
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
        <h2>Mon profil</h2>
        <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="nom" class="form-label">Nom d'utilisateur</label>
                <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($user['nom_utilisateur']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Nouveau mot de passe (optionnel)</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmer</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
        </form>
    </div>
    <footer>
        <div class="container">
            <p>&copy; 2026 Ma Boutique</p>
        </div>
    </footer>
</body>
</html>