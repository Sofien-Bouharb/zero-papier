<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

// Return JSON
header('Content-Type: application/json');

//Gert parameters
$q = $_GET['q'] ?? '';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$terms = preg_split('/\s+/', trim($q));
$conditions = [];
$params = [];

foreach ($terms as $i => $word) {
    $param = ":term$i";
    $conditions[] = "(w.hostname ILIKE $param OR w.ip_address ILIKE $param OR i.ilot_name ILIKE $param)";
    $params[$param] = '%' . $word . '%';
}

$whereClause = '';
if (!empty($conditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $conditions);
}

// Count total rows for pagination
$countSql = "
    SELECT COUNT(*) FROM documents_search.workers w
    LEFT JOIN documents_search.ilot i ON w.ilot_id = i.ilot_id
    $whereClause
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// Fetch paginated results
$dataSql = "
    SELECT w.step_number, w.hostname, w.ip_address, i.ilot_name
    FROM documents_search.workers w
    LEFT JOIN documents_search.ilot i ON w.ilot_id = i.ilot_id
    $whereClause
    ORDER BY  i.ilot_name, w.hostname
    LIMIT :limit OFFSET :offset
";
$dataStmt = $pdo->prepare($dataSql);
foreach ($params as $param => $value) {
    $dataStmt->bindValue($param, $value);
}
$dataStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$dataStmt->execute();
$posts = $dataStmt->fetchAll();

// Build table rows HTML
ob_start();
foreach ($posts as $w): ?>
    <tr>
        <td><?= htmlspecialchars($w['ilot_name'] ?? 'Non défini') ?></td>
        <td><?= htmlspecialchars($w['hostname']) ?></td>
        <td><?= htmlspecialchars($w['ip_address']) ?></td>
        <td style="text-align:center;">
            <a href="edit_post.php?step_number=<?= $w['step_number'] ?>" class="text-warning me-3" title="Modifier">✏️</a>
            <a href="delete_post.php?id=<?= $w['step_number'] ?>" class="text-danger" title="Supprimer" onclick="return confirm('Supprimer ce poste ?');">🗑️</a>
        </td>
    </tr>
<?php endforeach;

if (count($posts) === 0): ?>
    <tr>
        <td colspan="4" class="text-center">Aucun poste trouvé.</td>
    </tr>
<?php endif;

$html = ob_get_clean();

// Build smart pagination HTML
ob_start();
if ($totalPages > 1):
    $range = 2;
?>
    <nav>
        <ul class="pagination justify-content-end mb-0 pagination-sm">
            <?php if ($page > 1): ?>
                <li class="page-item"><a class="page-link page-link-nav" data-page="<?= $page - 1 ?>" href="#">«</a></li>
            <?php endif; ?>

            <?php if ($page > $range + 1): ?>
                <li class="page-item"><a class="page-link page-link-nav" data-page="1" href="#">1</a></li>
                <li class="page-item disabled"><span class="page-link">...</span></li>
            <?php endif; ?>

            <?php for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link page-link-nav" data-page="<?= $i ?>" href="#"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages - $range): ?>
                <li class="page-item disabled"><span class="page-link">...</span></li>
                <li class="page-item"><a class="page-link page-link-nav" data-page="<?= $totalPages ?>" href="#"><?= $totalPages ?></a></li>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <li class="page-item"><a class="page-link page-link-nav" data-page="<?= $page + 1 ?>" href="#">»</a></li>
            <?php endif; ?>
        </ul>
    </nav>
<?php
endif;
$pagination = ob_get_clean();


echo json_encode([
    'html' => $html,
    'pagination' => $pagination
]);
