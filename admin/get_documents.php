<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$limit = 5;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($query !== '') {
    $sql = "SELECT document_id, document_name, file_path
          FROM documents_search.documents
          WHERE document_name ILIKE :query OR file_path ILIKE :query
          ORDER BY document_name
          LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM documents_search.documents WHERE document_name ILIKE :query OR file_path ILIKE :query");
    $countStmt->execute([':query' => "%$query%"]);
    $totalRows = $countStmt->fetchColumn();
} else {
    $sql = "SELECT document_id, document_name, file_path
          FROM documents_search.documents
          ORDER BY document_name
          LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $countStmt = $pdo->query("SELECT COUNT(*) FROM documents_search.documents");
    $totalRows = $countStmt->fetchColumn();
}

$totalPages = ceil($totalRows / $limit);

ob_start();

if (count($documents) === 0): ?>
    <li class="list-group-item text-muted">Aucun document trouvé.</li>
    <?php else:
    foreach ($documents as $doc): ?>
        <li class="list-group-item bg-secondary text-white mb-2">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <strong><?= htmlspecialchars($doc['document_name']) ?></strong><br>
                    <small>
                        📄 <a href="../uploads/<?= urlencode($doc['file_path']) ?>" target="_blank" class="text-info text-decoration-underline">
                            <?= htmlspecialchars($doc['file_path']) ?>
                        </a>
                    </small>
                </div>
                <div class="mt-2 mt-sm-0">
                    <a href="edit_document.php?id=<?= $doc['document_id'] ?>" class="btn btn-sm btn-warning me-2">Modifier document</a>
                    <a href="add_association.php?id=<?= $doc['document_id'] ?>" class="btn btn-sm btn-info me-2">Ajouter associations</a>
                    <button class="btn btn-sm btn-danger" onclick="deleteDocument(<?= $doc['document_id'] ?>, this)">Supprimer</button>
                </div>
            </div>
        </li>
<?php endforeach;
endif;

$itemsHtml = ob_get_clean();

ob_start(); ?>
<nav>
    <ul class="pagination pagination-sm justify-content-center mt-2">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a href="#" class="page-link modal-page-link" data-page="<?= $page - 1 ?>" data-query="<?= htmlspecialchars($query) ?>">«</a>
            </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                <a href="#" class="page-link modal-page-link" data-page="<?= $i ?>" data-query="<?= htmlspecialchars($query) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a href="#" class="page-link modal-page-link" data-page="<?= $page + 1 ?>" data-query="<?= htmlspecialchars($query) ?>">»</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php
$paginationHtml = ob_get_clean();

echo json_encode([
    'html' => $itemsHtml,
    'pagination' => $paginationHtml
]);
