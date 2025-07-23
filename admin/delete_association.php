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


if (isset($_GET['doc_id'], $_GET['board_id'], $_GET['step_number'])) {
    $doc_id = intval($_GET['doc_id']);
    $board_id = intval($_GET['board_id']);
    $step_number = intval($_GET['step_number']);

    // Delete the specific association
    $stmt = $pdo->prepare("DELETE FROM documents_search.board_post_documents 
                           WHERE document_id = ? AND board_index_id = ? AND step_number = ?");
    $stmt->execute([$doc_id, $board_id, $step_number]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "Association supprimée avec succès.";
        header("Location: dashboard.php?view=documents");
        exit;
    } else {
        redirect_with_error("Erreur : association non trouvée ou déjà supprimée.");
    }
} else {
    redirect_with_error("Paramètres manquants.");
}
