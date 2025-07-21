<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
$_SESSION['LAST_ACTIVITY'] = time();

header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$terms = preg_split('/\s+/', trim($q));
$conditions = [];
$params = [];

foreach ($terms as $i => $word) {
    $param = ":term$i";
    $conditions[] = "(board_name ILIKE $param OR designation ILIKE $param OR repere_dm ILIKE $param OR ref_cie_actia ILIKE $param OR ref_pcb ILIKE $param OR clicher_pcb ILIKE $param OR CAST(board_index_id AS TEXT) ILIKE $param)";
    $params[$param] = '%' . $word . '%';
}

// Count
$countSql = "SELECT COUNT(*) FROM documents_search.boards";
if (!empty($conditions)) {
    $countSql .= " WHERE " . implode(" AND ", $conditions);
}
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Data
$dataSql = "SELECT * FROM documents_search.boards";
if (!empty($conditions)) {
    $dataSql .= " WHERE " . implode(" AND ", $conditions);
}
$dataSql .= " ORDER BY board_index_id LIMIT :limit OFFSET :offset";
$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $key => $val) {
    $dataStmt->bindValue($key, $val);
}
$dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$boards = $dataStmt->fetchAll();

// Generate HTML rows
ob_start();
if ($boards):
    foreach ($boards as $b): ?>
        <tr>
            <td><?= htmlspecialchars($b['board_index_id']) ?></td>
            <td><?= htmlspecialchars($b['board_name']) ?></td>
            <td><?= htmlspecialchars($b['repere_dm'] ?? '-') ?></td>
            <td><?= htmlspecialchars($b['designation'] ?? '-') ?></td>
            <td><?= htmlspecialchars($b['ref_cie_actia'] ?? '-') ?></td>
            <td><?= htmlspecialchars($b['ref_pcb'] ?? '-') ?></td>
            <td><?= htmlspecialchars($b['clicher_pcb'] ?? '-') ?></td>
            <td style="text-align:center;">
                <a href="edit_board.php?board_index_id=<?= $b['board_index_id'] ?>" class="text-warning me-3" title="Modifier">✏️</a>
                <a href="delete_board.php?id=<?= $b['board_index_id'] ?>" class="text-danger" title="Supprimer" onclick="return confirm('Supprimer cette carte ?');">🗑️</a>
            </td>
        </tr>
    <?php endforeach;
else: ?>
    <tr>
        <td colspan="8" class="text-center">Aucun résultat trouvé.</td>
    </tr>
<?php endif;
$html = ob_get_clean();

// Generate pagination HTML
ob_start();
$range = 2;
if ($totalPages > 1): ?>
    <?php if ($page > 1): ?>
        <li class="page-item"><a href="#" class="page-link page-link-nav" data-page="<?= $page - 1 ?>">«</a></li>
    <?php endif; ?>
    <?php if ($page > $range + 1): ?>
        <li class="page-item"><a href="#" class="page-link page-link-nav" data-page="1">1</a></li>
        <li class="page-item disabled"><span class="page-link">…</span></li>
    <?php endif; ?>
    <?php for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
            <a href="#" class="page-link page-link-nav" data-page="<?= $i ?>"><?= $i ?></a>
        </li>
    <?php endfor; ?>
    <?php if ($page < $totalPages - $range): ?>
        <li class="page-item disabled"><span class="page-link">…</span></li>
        <li class="page-item"><a href="#" class="page-link page-link-nav" data-page="<?= $totalPages ?>"><?= $totalPages ?></a></li>
    <?php endif; ?>
    <?php if ($page < $totalPages): ?>
        <li class="page-item"><a href="#" class="page-link page-link-nav" data-page="<?= $page + 1 ?>">»</a></li>
    <?php endif; ?>
<?php
endif;
$pagination = ob_get_clean();

// Return JSON
echo json_encode([
    'html' => $html,
    'pagination' => $pagination
]);
