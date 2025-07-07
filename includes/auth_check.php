<?php
session_start();

// Durée d'inactivité max (en secondes)
$timeout_duration = 1800; // 30 minutes

// Vérifier si l'utilisateur est inactif trop longtemps
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();     // Supprime les variables de session
    session_destroy();   // Détruit la session
    header("Location: login.php?timeout=1"); // Redirection avec message
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['admin_id'])) {
    header("Location: /index.php");
    exit();
}
