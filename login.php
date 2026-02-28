<?php
session_start();
require_once 'connexion.php';

if (isset($_SESSION['client_id'])) {
    header('Location: index.php');
    exit();
}
if (isset($_SESSION['admin'])) {
    header('Location: admin/accueil_admin.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifiant = trim($_POST['identifiant']);
    $password = $_POST['password'];
    if (!empty($identifiant) && !empty($password)) {
        // Vérifier admin d'abord
        $admin_sql = "SELECT * FROM admin WHERE username = ?";
        $stmt = mysqli_prepare($conn, $admin_sql);
        mysqli_stmt_bind_param($stmt, "s", $identifiant);
        mysqli_stmt_execute($stmt);
        $admin_res = mysqli_stmt_get_result($stmt);
        $admin = mysqli_fetch_assoc($admin_res);
        if ($admin && $password === $admin['password']) {
            $_SESSION['admin'] = $admin['username'];
            header('Location: admin/accueil_admin.php');
            exit();
        }
        // Sinon client
        $client_sql = "SELECT * FROM utilisateurs WHERE nom_utilisateur = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $client_sql);
        mysqli_stmt_bind_param($stmt, "ss", $identifiant, $identifiant);
        mysqli_stmt_execute($stmt);
        $client_res = mysqli_stmt_get_result($stmt);
        $client = mysqli_fetch_assoc($client_res);
        if ($client && password_verify($password, $client['mot_de_passe'])) {
            $_SESSION['client_id'] = $client['id'];
            $_SESSION['client_nom'] = $client['nom_utilisateur'];
            header('Location: index.php');
            exit();
        }
        $error = "Identifiants incorrects";
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Ma Boutique</title>
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
        <h2>Connexion</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="identifiant" class="form-label">Nom d'utilisateur ou email</label>
                <input type="text" class="form-control" id="identifiant" name="identifiant" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Se connecter</button>
        </form>
        <p class="mt-3 text-center">Pas encore de compte ? <a href="inscription.php">Inscrivez-vous</a></p>
    </div>
    <footer>
        <div class="container">
            <p>&copy; 2026 Ma Boutique</p>
        </div>
    </footer>
</body>
</html>