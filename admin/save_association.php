<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';
$_SESSION['LAST_ACTIVITY'] = time();
if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['error_message'])):
?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php unset($_SESSION['error_message']);
endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php unset($_SESSION['success_message']);
endif; ?>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-dismiss alerts after 4 seconds
    setTimeout(function() {
        const alert = document.querySelector('.alert');
        if (alert) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close(); // Triggers fade out
        }
    }, 4000); // 4000ms = 4 seconds
</script>
<?php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php?view=documents");
    exit();
}

$document_id = intval($_POST['document_id'] ?? 0);
$mappings = json_decode($_POST['mappings'] ?? '[]', true);

if ($document_id <= 0 || empty($mappings)) {

    redirect_with_error("Paramètres invalides ou aucune association à enregistrer.");
}

$inserted = 0;
$check_stmt = $pdo->prepare("
  SELECT COUNT(*) FROM documents_search.board_post_documents 
  WHERE board_index_id = :board AND step_number = :step AND document_id = :doc
");

$insert_stmt = $pdo->prepare("
  INSERT INTO documents_search.board_post_documents (board_index_id, step_number, document_id) 
  VALUES (:board, :step, :doc)
");

foreach ($mappings as $map) {
    if (!$map || !isset($map['step_number'], $map['board_ids'])) continue;

    $step = intval($map['step_number']);
    $board_ids = $map['board_ids'];

    foreach ($board_ids as $board_id) {
        $board = intval($board_id);

        // Skip if this association already exists
        $check_stmt->execute([
            'board' => $board,
            'step' => $step,
            'doc'  => $document_id
        ]);

        if ($check_stmt->fetchColumn() == 0) {
            $insert_stmt->execute([
                'board' => $board,
                'step'  => $step,
                'doc'   => $document_id
            ]);
            $inserted++;
        }
    }
}

header("Location: dashboard.php?view=documents&success=added_$inserted");
exit();
