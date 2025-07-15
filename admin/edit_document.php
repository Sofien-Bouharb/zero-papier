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
// Check if ID is passed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_document'])) {
    $document_id = intval($_POST['document_id']);
    $document_name = trim($_POST['document_name']);

    // Fetch current file path
    $stmt = $pdo->prepare("SELECT file_path FROM documents_search.documents WHERE document_id = ?");
    $stmt->execute([$document_id]);
    $existing = $stmt->fetch();

    if (!$existing) {

        redirect_with_error("Document introuvable.");
    }

    $file_path = $existing['file_path'];

    // Handle file upload (optional)
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/';
        $newFileName = basename($_FILES['document_file']['name']);
        $targetPath = $uploadDir . $newFileName;

        // Check for duplicate file path (excluding current document)
        $check = $pdo->prepare("
SELECT COUNT(*) FROM documents_search.documents
WHERE file_path = ? AND document_id != ?
");
        $check->execute([$newFileName, $document_id]);

        if ($check->fetchColumn() > 0) {
            redirect_with_error("Un autre document utilise déjà ce file path. Veuillez renommer le fichier.");
            $_SESSION['error_message'] = "Un autre document utilise déjà ce nom de fichier. Veuillez renommer le fichier.";
            $_SESSION['old_input'] = $_POST;
            header("Location: edit_document.php?id=" . $document_id);
            exit();
        }

        if (move_uploaded_file($_FILES['document_file']['tmp_name'], $targetPath)) {
            // Delete old file if it's different
            if ($existing['file_path'] !== $newFileName) {
                $oldFile = '../uploads/' . $existing['file_path'];
                if (file_exists($oldFile)) {
                    unlink($oldFile);
                }
            }

            $file_path = $newFileName;
        } else {
            redirect_with_error("Erreur lors du téléchargement du fichier.");
            $_SESSION['error_message'] = "Erreur lors du téléchargement du fichier.";
            $_SESSION['old_input'] = $_POST;
            header("Location: edit_document.php?id=" . $document_id);
            exit();
        }
    }

    // Update the document
    $stmt = $pdo->prepare("UPDATE documents_search.documents SET document_name = ?, file_path = ? WHERE document_id = ?");
    $stmt->execute([$document_name, $file_path, $document_id]);

    $success = "Document modifié avec succès.";
    header("Location: dashboard.php?view=documents&success=1");
    exit();
}

// Fetch document data (GET or POST fallback)
$document_id = isset($_GET['id']) ? intval($_GET['id']) : ($_POST['document_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM documents_search.documents WHERE document_id = ?");
$stmt->execute([$document_id]);
$doc = $stmt->fetch();

if (!$doc) {
    redirect_with_error("Document introuvable.");
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Modifier le document</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
</head>

<body class="bg-dark text-light">

    <div class="container mt-5">
        <h2 class="mb-4">📝 Modifier le document</h2>

        <form method="POST" enctype="multipart/form-data" class="mt-4">
            <input type="hidden" name="document_id" value="<?= htmlspecialchars($doc['document_id']) ?>">

            <div class="mb-3">
                <label for="document_name" class="form-label">Nom du document</label>
                <input type="text" name="document_name" id="document_name" class="form-control" value="<?= htmlspecialchars($doc['document_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="document_file" class="form-label">Remplacer le fichier (PDF)</label>
                <input type="file" name="document_file" class="form-control" accept="application/pdf" id="document_file">

                <!-- Show the current file name -->
                <p class="text-danger mt-2">
                    Fichier actuel : <strong><?= htmlspecialchars($doc['file_path']) ?></strong>
                </p>

                <!-- Optional: View/download the current file -->
                <a href="../uploads/<?= urlencode($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">📄 Voir le document</a>
            </div>

            <button type="submit" name="update_document" class="btn btn-primary">✅ Enregistrer</button>
            <a href="dashboard.php" class="btn btn-secondary">↩️ Retour</a>
        </form>
    </div>

</body>

</html>