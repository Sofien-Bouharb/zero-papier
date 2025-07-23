<?php
// Check if the admin is logged in
require_once '../includes/auth_check.php';

// Connect to the database
require_once '../includes/db.php';

// Include helper functions
require_once '../includes/helpers.php';

// Ensure a session is started
if (session_status() === PHP_SESSION_NONE) session_start();

// Update the last activity timestamp (for session timeout management)
$_SESSION['LAST_ACTIVITY'] = time();

// Check for ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect_with_error("ID de poste invalide.");
}

// Get the step number from the GET request
$step_number = (int)$_GET['id'];

// Just delete the worker — cascade takes care of the rest!
$pdo->prepare("DELETE FROM documents_search.workers WHERE step_number = :step")
    ->execute(['step' => $step_number]);
$_SESSION['success_message'] = "Poste supprimé avec succès.";
header("Location: dashboard.php?view=posts");
exit();
