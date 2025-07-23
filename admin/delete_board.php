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
    redirect_with_error("ID de carte invalide.");
}

$board_index_id = (int)$_GET['id'];

// Just delete the board — cascade handles related links
$pdo->prepare("DELETE FROM documents_search.boards WHERE board_index_id = :id")
    ->execute(['id' => $board_index_id]);
$_SESSION['success_message'] = "Code index supprimée avec succès.";
header("Location: dashboard.php?view=boards");
exit();
