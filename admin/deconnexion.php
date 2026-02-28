<?php
session_start();
session_destroy();
header('Location: ../index.php'); // Retour à l'accueil du site
exit;