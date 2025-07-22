<?php

require_once 'includes/db.php';
require_once 'includes/helpers.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo "Veuillez entrer un nom d'utilisateur et un mot de passe.";
    redirect_with_error("Veuillez entrer un nom d'utilisateur et un mot de passe.", 'index.php', false);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM documents_search.admins WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if ($admin && $admin['password'] === $password) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['LAST_ACTIVITY'] = time(); // For timeout tracking
        $_SESSION['CREATED'] = time();

        header("Location: admin/dashboard.php");
        exit();
    } else {
        redirect_with_error("Nom d'utilisateur ou mot de passe invalide.", 'index.php', false);
    }
} catch (PDOException $e) {
    echo "Erreur base de données : " . $e->getMessage();
}
