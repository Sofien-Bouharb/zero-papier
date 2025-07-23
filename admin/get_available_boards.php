<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

//Get parameters
$step_number  = intval($_GET['step_number'] ?? 0);
$document_id  = intval($_GET['document_id'] ?? 0);
$board_name   = trim($_GET['board_name'] ?? '');
// Validate parameters
if ($step_number <= 0 || $document_id <= 0 || !$board_name) {
    echo '<p class="text-danger">Paramètres invalides ou nom de carte manquant.</p>';
    exit();
}

// Get board IDs already associated with this doc+post
$stmt = $pdo->prepare("
  SELECT board_index_id 
  FROM documents_search.board_post_documents 
  WHERE step_number = :step AND document_id = :doc
");
$stmt->execute(['step' => $step_number, 'doc' => $document_id]);
$excluded = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch boards of the selected name NOT already linked to this doc + post
if (count($excluded)) {
    $placeholders = implode(',', array_fill(0, count($excluded), '?'));
    $params = array_merge([$board_name], $excluded);
    $query = "
      SELECT board_index_id, board_name
      FROM documents_search.boards
      WHERE board_name = ? AND board_index_id NOT IN ($placeholders)
      ORDER BY board_index_id
    ";
    $boardsStmt = $pdo->prepare($query);
    $boardsStmt->execute($params);
} else {
    // If no exclusions, just fetch by name
    $boardsStmt = $pdo->prepare("
      SELECT board_index_id, board_name
      FROM documents_search.boards
      WHERE board_name = ?
      ORDER BY board_index_id
    ");
    $boardsStmt->execute([$board_name]);
}

$boards = $boardsStmt->fetchAll();
// Check if any boards were found
if (empty($boards)) {
    echo '<p class="text-danger">Aucune carte disponible à associer pour ce nom.</p>';
    exit();
}
// Output the available boards
foreach ($boards as $b): ?>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" value="<?= $b['board_index_id'] ?>" id="board<?= $b['board_index_id'] ?>">
        <label class="form-check-label" for="board<?= $b['board_index_id'] ?>">
            <?= htmlspecialchars($b['board_name']) ?> (ID: <?= $b['board_index_id'] ?>)
        </label>
    </div>
<?php endforeach; ?>