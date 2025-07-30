<?php
require_once '../includes/auth_check.php';  // Ensure user is authenticated
require_once '../includes/db.php';          // Include DB connection

// Pagination and search setup
$limit = 5;  // Number of documents per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$query = isset($_GET['q']) ? trim($_GET['q']) : ''; // User search input

// If there is a search query
if ($query !== '') {
    // Prepare SELECT with ILIKE for case-insensitive search in document name or file path
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

    // Count total matching documents for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM documents_search.documents WHERE document_name ILIKE :query OR file_path ILIKE :query");
    $countStmt->execute([':query' => "%$query%"]);
    $totalRows = $countStmt->fetchColumn();
} else {
    // No search query, load default paginated results
    $sql = "SELECT document_id, document_name, file_path
            FROM documents_search.documents
            ORDER BY document_name
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count all documents for pagination
    $countStmt = $pdo->query("SELECT COUNT(*) FROM documents_search.documents");
    $totalRows = $countStmt->fetchColumn();
}

// Calculate total number of pages
$totalPages = ceil($totalRows / $limit);

// Buffer list HTML output
ob_start();

// Display message if no documents found
if (count($documents) === 0): ?>
    <li class="list-group-item text-muted">Aucun document trouvé.</li>
    <?php else:
    foreach ($documents as $doc): ?>
        <li class="list-group-item text-black mb-2" style="background-color: #d1d2d5">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <!-- Document name and file link -->
                    <strong><?= htmlspecialchars($doc['document_name']) ?></strong><br>
                    <small>
                        <img src="../../assets/emojis/1f4c4.png" alt="voir" class="emoji"> <a href="../uploads/<?= urlencode($doc['file_path']) ?>" target="_blank" class="text-decoration-underline">
                            <?= htmlspecialchars($doc['file_path']) ?>
                        </a>
                    </small>
                </div>
                <div class="mt-2 mt-sm-0">
                    <!-- Action buttons -->
                    <a href="edit_document.php?id=<?= $doc['document_id'] ?>" class="btn btn-sm me-2" style="background-color: #747e87; color:#000;">Modifier document</a>
                    <a href="add_association.php?id=<?= $doc['document_id'] ?>" class="btn btn-sm me-2" style="background-color: #747e87; color:#000;">Ajouter associations</a>
                    <button class="btn btn-sm btn-danger" onclick="deleteDocument(<?= $doc['document_id'] ?>, this)">Supprimer</button>
                </div>
            </div>
        </li>
<?php endforeach;
endif;

$itemsHtml = ob_get_clean(); // Store list HTML

// Buffer pagination HTML
ob_start(); ?>
<nav>
    <ul class="pagination pagination-sm justify-content-center mt-2">
        <!-- Previous page link -->
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a href="#" class="page-link modal-page-link" data-page="<?= $page - 1 ?>" data-query="<?= htmlspecialchars($query) ?>">«</a>
            </li>
        <?php endif; ?>

        <!-- Page number links -->
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                <a href="#" class="page-link modal-page-link" data-page="<?= $i ?>" data-query="<?= htmlspecialchars($query) ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <!-- Next page link -->
        <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a href="#" class="page-link modal-page-link" data-page="<?= $page + 1 ?>" data-query="<?= htmlspecialchars($query) ?>">»</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php
$paginationHtml = ob_get_clean(); // Store pagination HTML

// Output HTML and pagination as JSON
echo json_encode([
    'html' => $itemsHtml,
    'pagination' => $paginationHtml
]);
