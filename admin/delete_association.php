<?php

require_once '../includes/auth_check.php';
require_once '../includes/db.php';

if (isset($_GET['doc_id'], $_GET['board_id'], $_GET['step_number'])) {
    $doc_id = intval($_GET['doc_id']);
    $board_id = intval($_GET['board_id']);
    $step_number = intval($_GET['step_number']);

    // Delete the specific association
    $stmt = $pdo->prepare("DELETE FROM documents_search.board_post_documents 
                           WHERE document_id = ? AND board_index_id = ? AND step_number = ?");
    $stmt->execute([$doc_id, $board_id, $step_number]);

    if ($stmt->rowCount() > 0) {
        header("Location: dashboard.php?view=documents&msg=association_deleted");
        
        exit;
    } else {
        echo "Erreur : association non trouvée ou déjà supprimée.";
    }
} else {
    echo "Paramètres manquants.";
}
