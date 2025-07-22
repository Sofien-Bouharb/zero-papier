<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de carte invalide.");
}

$board_index_id = (int)$_GET['id'];

// Just delete the board — cascade handles related links
$pdo->prepare("DELETE FROM documents_search.boards WHERE board_index_id = :id")
    ->execute(['id' => $board_index_id]);
$_SESSION['success_message'] = "Code index supprimée avec succès.";
header("Location: dashboard.php?view=boards");
exit();
