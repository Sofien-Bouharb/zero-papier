<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['document_id'])) {
    $docId = intval($_POST['document_id']);

    // Step 1: Get the file path
    $sql = "SELECT file_path FROM documents_search.documents WHERE document_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$docId]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($doc) {
        $file = '../uploads/' . $doc['file_path']; // adjust path if needed

        // Step 2: Delete associations
        $sql1 = "DELETE FROM documents_search.board_post_documents WHERE document_id = ?";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute([$docId]);

        // Step 3: Delete document entry
        $sql2 = "DELETE FROM documents_search.documents WHERE document_id = ?";
        $stmt2 = $pdo->prepare($sql2);
        $success = $stmt2->execute([$docId]);

        // Step 4: Delete the actual file
        if ($success && file_exists($file)) {
            unlink($file);
        }

        echo $success ? "success" : "error";
    } else {
        echo "error";
    }
}
