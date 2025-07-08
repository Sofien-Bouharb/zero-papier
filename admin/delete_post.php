<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de poste invalide.");
}

$step_number = (int)$_GET['id'];

// Just delete the worker — cascade takes care of the rest!
$pdo->prepare("DELETE FROM documents_search.workers WHERE step_number = :step")
    ->execute(['step' => $step_number]);

header("Location: dashboard.php?view=posts");
exit();
