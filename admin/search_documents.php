<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
$_SESSION['LAST_ACTIVITY'] = time(); // Update session timestamp

// Get parameters
$q = $_GET['q'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int) $_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Split search into terms
$terms = preg_split('/\s+/', trim($q));
$conditions = [];
$params = [];

foreach ($terms as $index => $word) {
    $param = ":term$index";
    $conditions[] = "(
        d.document_name ILIKE $param OR 
        d.file_path ILIKE $param OR 
        w.hostname ILIKE $param OR 
        b.board_name ILIKE $param OR 
        CAST(b.board_index_id AS TEXT) ILIKE $param
    )";
    $params[$param] = '%' . $word . '%';
}

$whereClause = implode(' AND ', $conditions);

// ---------- COUNT QUERY ----------
$countSql = "
    SELECT COUNT(*) FROM documents_search.board_post_documents bp
    JOIN documents_search.documents d ON bp.document_id = d.document_id
    JOIN documents_search.boards b ON bp.board_index_id = b.board_index_id
    JOIN documents_search.workers w ON bp.step_number = w.step_number
";

if (!empty($whereClause)) {
    $countSql .= " WHERE $whereClause";
    $countStmt = $pdo->prepare($countSql);
    foreach ($params as $key => $val) {
        $countStmt->bindValue($key, $val);
    }
    $countStmt->execute();
    $totalRows = $countStmt->fetchColumn();
} else {
    $totalRows = 0;
}

$totalPages = ceil($totalRows / $limit);

// ---------- DATA QUERY ----------
$dataSql = "
    SELECT d.document_id, d.document_name, d.file_path,
           b.board_name, b.board_index_id,
           w.hostname, w.step_number
    FROM documents_search.board_post_documents bp
    JOIN documents_search.documents d ON bp.document_id = d.document_id
    JOIN documents_search.boards b ON bp.board_index_id = b.board_index_id
    JOIN documents_search.workers w ON bp.step_number = w.step_number
";

if (!empty($whereClause)) {
    $dataSql .= " WHERE $whereClause";
}

$dataSql .= "
    ORDER BY d.document_name ASC, w.hostname, b.board_name
    LIMIT :limit OFFSET :offset
";

$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $key => $val) {
    $dataStmt->bindValue($key, $val);
}
$dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$rows = $dataStmt->fetchAll();

// ---------- OUTPUT HTML ----------
if (count($rows) > 0):
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
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>

        <nav id="searchPagination">
            <ul class=" mt-3 pagination justify-content-center pagination-sm bg-transparent ">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a href="#" class="page-link search-page-link"
                            data-page="<?= $i ?>"
                            data-query="<?= htmlspecialchars($q) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>

<?php else: ?>
    <tr>
        <td colspan="5" class="text-center">Aucun résultat trouvé.</td>
    </tr>
<?php endif; ?>