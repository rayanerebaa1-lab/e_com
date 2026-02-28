<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'connexion.php';
require_once 'vendor/autoload.php';

// Configuration Stripe
\Stripe\Stripe::setApiKey(getenv('STRIPE_SECRET_KEY'));

// Vérifications de base
if (empty($_SESSION['panier'])) {
    die("❌ Votre panier est vide. <a href='panier.php'>Retour au panier</a>");
}

// Calcul du total
$total = 0;
foreach ($_SESSION['panier'] as $produit_id => $quantite) {
    $result = mysqli_query($conn, "SELECT prix FROM produits WHERE id = " . intval($produit_id));
    if ($row = mysqli_fetch_assoc($result)) {
        $total += $row['prix'] * $quantite;
    } else {
        die("❌ Produit introuvable (ID: $produit_id)");
    }
}
if ($total <= 0) {
    die("❌ Le total est invalide.");
}

// Création du PaymentIntent
try {
    $intent = \Stripe\PaymentIntent::create([
        'amount' => round($total * 100),
        'currency' => 'eur',
        'metadata' => ['panier' => json_encode($_SESSION['panier'])]
    ]);
} catch (Exception $e) {
    die("❌ Erreur Stripe : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement sécurisé</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .payment-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #f9f9f9;
        }
        .stripe-element {
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 10px;
            margin-bottom: 15px;
            background: white;
        }
        .stripe-element--focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25);
        }
        .stripe-element--invalid {
            border-color: #dc3545;
        }
        #card-errors {
            color: #dc3545;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-container">
            <h2 class="text-center mb-4">Paiement sécurisé</h2>
            <p class="text-center">Total à payer : <strong><?= number_format($total, 2, ',', ' ') ?> €</strong></p>

            <form id="payment-form">
                <!-- Champ numéro de carte -->
                <div class="mb-3">
                    <label for="card-number" class="form-label">Numéro de carte</label>
                    <div id="card-number" class="stripe-element"></div>
                </div>

                <!-- Champ date d'expiration -->
                <div class="mb-3">
                    <label for="card-expiry" class="form-label">Date d'expiration</label>
                    <div id="card-expiry" class="stripe-element"></div>
                </div>

                <!-- Champ CVC -->
                <div class="mb-3">
                    <label for="card-cvc" class="form-label">CVC</label>
                    <div id="card-cvc" class="stripe-element"></div>
                </div>

                <button id="submit" class="btn btn-primary w-100">Payer <?= number_format($total, 2, ',', ' ') ?> €</button>
                <div id="card-errors" role="alert"></div>
            </form>

            <div class="text-center mt-3">
                <a href="panier.php">← Retour au panier</a>
            </div>
        </div>
    </div>

    <script>
        const stripe =08= getenv('STRIPE_SECRET_KEY');');
        const elements = stripe.elements();

        // Créer des éléments séparés
        const cardNumber = elements.create('cardNumber', { style: { base: { fontSize: '16px' } } });
        const cardExpiry = elements.create('cardExpiry', { style: { base: { fontSize: '16px' } } });
        const cardCvc = elements.create('cardCvc', { style: { base: { fontSize: '16px' } } });

        // Monter les éléments
        cardNumber.mount('#card-number');
        cardExpiry.mount('#card-expiry');
        cardCvc.mount('#card-cvc');

        // Gestion des erreurs en temps réel
        const errorElement = document.getElementById('card-errors');
        [cardNumber, cardExpiry, cardCvc].forEach(element => {
            element.on('change', ({error}) => {
                if (error) {
                    errorElement.textContent = error.message;
                } else {
                    errorElement.textContent = '';
                }
            });
        });

        // Soumission du formulaire
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            document.getElementById('submit').disabled = true;

            const { error, paymentIntent } = await stripe.confirmPayment({
                elements,
                confirmParams: {
                    return_url: window.location.origin + '/ecommerce/site/e_com/confirmation.php',
                },
                redirect: 'if_required',
            });

            if (error) {
                errorElement.textContent = error.message;
                document.getElementById('submit').disabled = false;
            } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                window.location.href = 'confirmation.php?payment_intent=' + paymentIntent.id;
            }
        });
    </script>
</body>
</html>