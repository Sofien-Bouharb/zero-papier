<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
$_SESSION['LAST_ACTIVITY'] = time(); // Met à jour l'heure de la dernière activité

$q = $_GET['q'] ?? '';
$terms = preg_split('/\s+/', trim($q));  // split by space
$conditions = [];
$params = [];

foreach ($terms as $index => $word) {
    $param = ":term$index";
    $conditions[] = "(d.document_name ILIKE $param OR d.file_path ILIKE $param OR w.hostname ILIKE $param OR b.board_name ILIKE $param OR CAST(b.board_index_id AS TEXT) ILIKE $param)";
    $params[$param] = '%' . $word . '%';
}

$sql = "
    SELECT d.document_id, d.document_name, d.file_path,
           b.board_name, b.board_index_id,
           w.hostname, w.step_number
    FROM documents_search.board_post_documents bp
    JOIN documents_search.documents d ON bp.document_id = d.document_id
    JOIN documents_search.boards b ON bp.board_index_id = b.board_index_id
    JOIN documents_search.workers w ON bp.step_number = w.step_number
";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY d.document_name ASC, w.hostname, b.board_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

foreach ($rows as $row): ?>
    <tr data-doc-id="<?= $row['document_id'] ?>">
        <td><?= htmlspecialchars($row['document_name']) ?></td>
        <td>
            <a href="../uploads/<?= urlencode($row['file_path']) ?>" target="_blank" class="text-info">
                <?= htmlspecialchars($row['file_path']) ?>
            </a>
        </td>
        <td><strong><?= htmlspecialchars($row['hostname']) ?></strong></td>
        <td><strong><?= htmlspecialchars($row['board_name']) ?> (ID: <?= $row['board_index_id'] ?>)</strong></td>
        <td style="text-align:center;">
            <a href="delete_association.php?doc_id=<?= $row['document_id'] ?>&board_id=<?= $row['board_index_id'] ?>&step_number=<?= urlencode($row['step_number']) ?>" class="text-danger" title="Supprimer cette association doc-post-board" onclick="return confirm('Supprimer cette association ?');">🗑️</a>
        </td>
    </tr>
<?php endforeach;

if (count($rows) === 0): ?>
    <tr>
        <td colspan="5" class="text-center text-muted">Aucun résultat trouvé.</td>
    </tr>
<?php endif; ?>