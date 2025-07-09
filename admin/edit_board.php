<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_GET['board_index_id'])) {
    die("Aucune carte spécifiée.");
}

$board_index_id = (int) $_GET['board_index_id'];

// Fetch board data
$stmt = $pdo->prepare("SELECT * FROM documents_search.boards WHERE board_index_id = :id");
$stmt->execute(['id' => $board_index_id]);
$board = $stmt->fetch();

if (!$board) {
    die("Carte introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $board_index = (int)($_POST['board_index_id'] ?? 0);
    $board_name = trim($_POST['board_name'] ?? '-');
    $repere_dm = trim($_POST['repere_dm'] ?? '-');
    $designation = trim($_POST['designation'] ?? '-');
    $ref_cie = trim($_POST['ref_cie'] ?? '-');
    $ref_pcb = trim($_POST['ref_pcb'] ?? '-');
    $clicher_pcb = trim($_POST['clicher_pcb'] ?? '-');

    if (!$board_name || !$board_index_id) {
        $error = "Tous les champs obligatoires doivent être remplis.";
    } else {
        $update = $pdo->prepare("
      UPDATE documents_search.boards
      SET board_index_id= :id_b,
          board_name = :name,
          repere_dm = :repere,
          designation = :des,
          ref_cie_actia = :cie,
          ref_pcb = :pcb,
          clicher_pcb = :clicher
      WHERE board_index_id = :id
    ");

        $update->execute([
            'id_b' => $board_index,
            'name' => $board_name,
            'repere' => $repere_dm,
            'des' => $designation,
            'cie' => $ref_cie,
            'pcb' => $ref_pcb,
            'clicher' => $clicher_pcb,
            'id' => $board_index_id
        ]);

        header("Location: dashboard.php?view=boards&success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Modifier une carte</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-dark text-light">
    <div class="container mt-5">
        <h2>Modifier la carte #<?= htmlspecialchars($board_index_id) ?></h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label class="form-label">Code Index</label>
                <input type="number" name="board_index_id" class="form-control" value="<?= htmlspecialchars($board['board_index_id']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Nom de la carte</label>
                <input type="text" name="board_name" class="form-control" value="<?= htmlspecialchars($board['board_name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Repère DM (optionnel)</label>
                <input type="text" name="repere_dm" class="form-control" value="<?= htmlspecialchars($board['repere_dm'] ?? '-') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Désignation (optionnel)</label>
                <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($board['designation'] ?? '-') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Référence CIE (optionnel)</label>
                <input type="text" name="ref_cie" class="form-control" value="<?= htmlspecialchars($board['ref_cie_actia'] ?? '-') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Référence PCB (optionnel)</label>
                <input type="text" name="ref_pcb" class="form-control" value="<?= htmlspecialchars($board['ref_pcb'] ?? '-') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Cliché PCB (optionnel)</label>
                <input type="text" name="clicher_pcb" class="form-control" value="<?= htmlspecialchars($board['clicher_pcb'] ?? '-') ?>">
            </div>

            <button type="submit" class="btn btn-primary m-2">💾 Enregistrer</button>
            <a href="dashboard.php?view=boards" class="btn btn-secondary m-2">Retour</a>
        </form>
    </div>
</body>

</html>