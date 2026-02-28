<?php
session_start();
if (!isset($_SESSION['client_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes commandes - Ma Boutique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">MA BOUTIQUE</a>
        </div>
    </nav>
    <main class="container my-5">
        <h2>Mes commandes</h2>
        <p>Vous n'avez pas encore passé de commande.</p>
    </main>
    <footer>
        <div class="container">
            <p>&copy; 2026 Ma Boutique</p>
        </div>
    </footer>
</body>
</html>