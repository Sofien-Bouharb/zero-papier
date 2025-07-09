<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$_SESSION['LAST_ACTIVITY'] = time();

$step_number = intval($_GET['step_number'] ?? 0);
$document_id = intval($_GET['document_id'] ?? 0);

if ($step_number <= 0 || $document_id <= 0) {
    echo '<p class="text-danger">Paramètres invalides.</p>';
    exit();
}

// Get all board IDs already associated with this doc+post
$stmt = $pdo->prepare("
  SELECT board_index_id 
  FROM documents_search.board_post_documents 
  WHERE step_number = :step AND document_id = :doc
");
$stmt->execute(['step' => $step_number, 'doc' => $document_id]);
$excludedBoards = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all boards not in that list
if (count($excludedBoards)) {
    $placeholders = implode(',', array_fill(0, count($excludedBoards), '?'));
    $boardsStmt = $pdo->prepare("
    SELECT board_index_id, board_name 
    FROM documents_search.boards 
    WHERE board_index_id NOT IN ($placeholders)
    ORDER BY board_name, board_index_id
  ");
    $boardsStmt->execute($excludedBoards);
} else {
    $boardsStmt = $pdo->query("
    SELECT board_index_id, board_name 
    FROM documents_search.boards 
    ORDER BY board_name, board_index_id
  ");
}

$boards = $boardsStmt->fetchAll();

if (empty($boards)) {
    echo '<p class="text-warning">Aucune carte disponible à associer.</p>';
    exit();
}

foreach ($boards as $b): ?>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" value="<?= $b['board_index_id'] ?>" id="board<?= $b['board_index_id'] ?>">
        <label class="form-check-label" for="board<?= $b['board_index_id'] ?>">
            <?= htmlspecialchars($b['board_name']) ?> (ID: <?= $b['board_index_id'] ?>)
        </label>
    </div>
<?php endforeach; ?>