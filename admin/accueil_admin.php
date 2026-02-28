<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header('Location: connexion_admin.php');
    exit();
}
include '../connexion.php';

// Statistiques
$nb_produits = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM produits"))['total'];
$nb_utilisateurs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM utilisateurs"))['total'];
$nb_commandes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM commandes"))['total'] ?? 0;
$total_ventes = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as total FROM commandes"))['total'] ?? 0;

// Données pour le graphique (ex: ventes par mois)
$mois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'];
$ventes = [1200, 1900, 3000, 5000, 2300, 3400]; // données fictives
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - Ma Boutique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f4f7fc; }
        .navbar { background: linear-gradient(135deg, #1e293b, #0f172a); }
        .card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 15px 30px -10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card:hover { transform: translateY(-5px); }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark px-4">
        <span class="navbar-brand fw-bold fs-3"> Admin - Tableau de bord</span>
        <a href="deconnexion.php" class="btn btn-outline-light rounded-pill"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Bienvenue, <?= $_SESSION['admin'] ?> !</h2>
        
        <!-- Cartes statistiques -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card p-4 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-gradient"><i class="bi bi-box-seam"></i></div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= $nb_produits ?></h3>
                            <p class="text-secondary mb-0">Produits</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-4 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-success bg-gradient"><i class="bi bi-people"></i></div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= $nb_utilisateurs ?></h3>
                            <p class="text-secondary mb-0">Utilisateurs</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-4 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-gradient"><i class="bi bi-cart-check"></i></div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= $nb_commandes ?></h3>
                            <p class="text-secondary mb-0">Commandes</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-4 bg-white">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-danger bg-gradient"><i class="bi bi-currency-euro"></i></div>
                        <div class="ms-3">
                            <h3 class="mb-0"><?= number_format($total_ventes, 2, ',', ' ') ?> €</h3>
                            <p class="text-secondary mb-0">CA total</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphique et actions -->
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card p-4">
                    <h5>Ventes mensuelles (en €)</h5>
                    <canvas id="ventesChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <h5>Actions rapides</h5>
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="liste_produits.php" class="text-decoration-none">Gérer les produits</a>
        <i class="bi bi-arrow-right-short"></i>
    </li>
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="#" class="text-decoration-none">Voir les commandes</a>
        <i class="bi bi-arrow-right-short"></i>
    </li>
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="#" class="text-decoration-none">Gérer les utilisateurs</a>
        <i class="bi bi-arrow-right-short"></i>
    </li>
    <!-- Nouveau lien pour les catégories -->
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="gestion_categories.php" class="text-decoration-none">Gérer les catégories</a>
        <i class="bi bi-arrow-right-short"></i>
    </li>
           </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('ventesChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($mois) ?>,
                datasets: [{
                    label: 'Ventes (€)',
                    data: <?= json_encode($ventes) ?>,
                    backgroundColor: 'rgba(78, 84, 200, 0.1)',
                    borderColor: '#4e54c8',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>