<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connexion.php';
require_once 'vendor/autoload.php';

// Clé secrète Stripe
\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

$payment_intent_id = $_GET['payment_intent'] ?? '';
if (empty($payment_intent_id)) {
    die("❌ Aucun identifiant de paiement fourni.");
}

try {
    $intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);
    if ($intent->status !== 'succeeded') {
        die("❌ Le paiement n'a pas abouti. Statut : " . $intent->status);
    }

    // Enregistrement de la commande
    $client_id = $_SESSION['client_id'] ?? null;
    $total = $intent->amount / 100;

    mysqli_begin_transaction($conn);
    try {
        // Insertion de la commande
        $stmt = mysqli_prepare($conn, "INSERT INTO commandes (client_id, total, statut, date_commande) VALUES (?, ?, 'payée', NOW())");
        mysqli_stmt_bind_param($stmt, "id", $client_id, $total);
        mysqli_stmt_execute($stmt);
        $commande_id = mysqli_stmt_insert_id($stmt);
        mysqli_stmt_close($stmt);

        // Insertion des lignes de commande
        if (!empty($_SESSION['panier'])) {
            foreach ($_SESSION['panier'] as $produit_id => $quantite) {
                $produit_id = intval($produit_id);
                $quantite = intval($quantite);
                $res = mysqli_query($conn, "SELECT prix FROM produits WHERE id = $produit_id");
                $produit = mysqli_fetch_assoc($res);
                $prix = $produit['prix'];

                $stmt = mysqli_prepare($conn, "INSERT INTO lignes_commandes (commande_id, produit_id, quantite, prix_unitaire) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iiid", $commande_id, $produit_id, $quantite, $prix);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        mysqli_commit($conn);
        $_SESSION['panier'] = []; // Vider le panier
        $success = true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $success = false;
        $error_message = "Erreur lors de l'enregistrement de la commande : " . $e->getMessage();
    }
} catch (Exception $e) {
    $success = false;
    $error_message = "Erreur Stripe : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .confirmation-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .checkmark {
            color: #28a745;
            font-size: 80px;
            margin-bottom: 20px;
        }
        .btn-home {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-container">
            <?php if ($success): ?>
                <div class="checkmark">✅</div>
                <h1 class="mb-4">Commande validée !</h1>
                <p class="lead">Merci pour votre achat. Votre paiement a été accepté avec succès.</p>
                <p><strong>Numéro de commande : #<?= $commande_id ?></strong></p>
                <p>Un email de confirmation vous sera envoyé sous peu.</p>
                <a href="index.php" class="btn btn-primary btn-lg btn-home">Retour à l'accueil</a>
                <?php if (isset($_SESSION['client_id'])): ?>
                    <a href="commandes.php" class="btn btn-outline-secondary btn-lg btn-home ms-2">Voir mes commandes</a>
                <?php endif; ?>
            <?php else: ?>
                <div class="checkmark" style="color: #dc3545;">❌</div>
                <h1 class="mb-4">Erreur de paiement</h1>
                <p class="lead"><?= htmlspecialchars($error_message ?? 'Une erreur est survenue.') ?></p>
                <a href="panier.php" class="btn btn-warning btn-lg btn-home">Retour au panier</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>