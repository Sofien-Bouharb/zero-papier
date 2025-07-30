<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

// Get parameters
$step_number  = intval($_GET['step_number'] ?? 0);
$document_id  = intval($_GET['document_id'] ?? 0);
$board_name   = trim($_GET['board_name'] ?? '');
$search_query = trim($_GET['search_query'] ?? '');

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

// Build the query
$query = "
    SELECT board_index_id, board_name
    FROM documents_search.boards
    WHERE board_name = ?
";
$params = [$board_name];

if ($search_query !== '') {
    $query .= " AND board_index_id::text LIKE ?";
    $params[] = "%$search_query%";
}

if (count($excluded)) {
    $placeholders = implode(',', array_fill(0, count($excluded), '?'));
    $query .= " AND board_index_id NOT IN ($placeholders)";
    $params = array_merge($params, $excluded);
}

$query .= " ORDER BY board_index_id";

// Prepare and execute
$boardsStmt = $pdo->prepare($query);
$boardsStmt->execute($params);
$boards = $boardsStmt->fetchAll();

// Check if any boards were found
if (empty($boards)) {
    echo '<p class="text-muted">Aucune carte disponible à associer pour ce document.</p>';
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