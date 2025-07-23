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

//  Ensure the request is POST (security check)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Redirect to the documents dashboard if accessed directly
    header("Location: dashboard.php?view=documents");
    exit();
}

//  Retrieve and sanitize incoming POST data
$document_id = intval($_POST['document_id'] ?? 0); // ID of the document
$mappings = json_decode($_POST['mappings'] ?? '[]', true); // JSON list of post-board associations

//  Validate input data
if ($document_id <= 0 || empty($mappings)) {
    // Redirect back to the association form with an error message
    redirect_with_error("Paramètres invalides ou aucune association à enregistrer.", 'add_association.php?id=' . $document_id);
}

//  Prepare reusable SQL statements
// Check if a given association already exists
$check_stmt = $pdo->prepare("
  SELECT COUNT(*) FROM documents_search.board_post_documents 
  WHERE board_index_id = :board AND step_number = :step AND document_id = :doc
");

// Insert a new association if it doesn’t already exist
$insert_stmt = $pdo->prepare("
  INSERT INTO documents_search.board_post_documents (board_index_id, step_number, document_id) 
  VALUES (:board, :step, :doc)
");

$inserted = 0; // Counter for number of inserted associations

//  Process all mappings (each is a step_number + array of board_ids)
foreach ($mappings as $map) {
    // Skip malformed entries
    if (!$map || !isset($map['step_number'], $map['board_ids'])) continue;

    $step = intval($map['step_number']);
    $board_ids = $map['board_ids'];

    foreach ($board_ids as $board_id) {
        $board = intval($board_id);

        // Check if the association already exists
        $check_stmt->execute([
            'board' => $board,
            'step' => $step,
            'doc'  => $document_id
        ]);

        // If it doesn't exist, insert the new association
        if ($check_stmt->fetchColumn() == 0) {
            $insert_stmt->execute([
                'board' => $board,
                'step'  => $step,
                'doc'   => $document_id
            ]);
            $inserted++; // Increment counter
        }
    }
}

//  Set a success message in the session
$_SESSION['success_message'] = $inserted . " association(s) ajoutée(s) avec succès.";

//  Redirect back to the documents dashboard
header("Location: dashboard.php?view=documents");
exit();
