<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $board_index_id = trim($_POST['board_index_id']);
    $board_name = trim($_POST['board_name']);
    $repere_dm = trim($_POST['repere_dm'] ?? null);
    $designation = trim($_POST['designation'] ?? null);
    $ref_cie = trim($_POST['ref_cie'] ?? null);
    $ref_pcb = trim($_POST['ref_pcb'] ?? null);
    $clicher_pcb = trim($_POST['clicher_pcb'] ?? null);

    // Validate required fields
    if (!$board_index_id || !$board_name) {
        $error = "Les champs 'ID carte' et 'Nom de carte' sont obligatoires.";
    } else {
        // Check if board_index_id already exists
        $check = $pdo->prepare("SELECT COUNT(*) FROM documents_search.boards WHERE board_index_id = :id");
        $check->execute(['id' => $board_index_id]);

        if ($check->fetchColumn() > 0) {
            $error = "Une carte avec cet ID existe déjà.";
        } else {
            // Insert into database
            $stmt = $pdo->prepare("
                INSERT INTO documents_search.boards (
                    board_index_id, board_name, repere_dm, designation, ref_cie_actia, ref_pcb, clicher_pcb
                ) VALUES (
                    :id, :name, :repere_dm, :designation, :ref_cie, :ref_pcb, :clicher_pcb
                )
            ");
            $stmt->execute([
                'id' => $board_index_id,
                'name' => $board_name,
                'repere_dm' => $repere_dm ?: null,
                'designation' => $designation ?: null,
                'ref_cie' => $ref_cie ?: null,
                'ref_pcb' => $ref_pcb ?: null,
                'clicher_pcb' => $clicher_pcb ?: null,
            ]);
            header("Location: dashboard.php?view=boards");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Ajouter une carte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-dark text-light p-4">
    <div class="container">
        <h2 class="mb-4">📤 Ajouter une nouvelle carte</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="board_index_id" class="form-label">ID Carte (index)</label>
                <input type="number" name="board_index_id" id="board_index_id" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="board_name" class="form-label">Nom de la carte</label>
                <input type="text" name="board_name" id="board_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="repere_dm" class="form-label">Repère DM (optionnel)</label>
                <input type="text" name="repere_dm" id="repere_dm" class="form-control">
            </div>

            <div class="mb-3">
                <label for="designation" class="form-label">Désignation (optionnel)</label>
                <input type="text" name="designation" id="designation" class="form-control">
            </div>

            <div class="mb-3">
                <label for="ref_cie" class="form-label">Réf CIE (optionnel)</label>
                <input type="text" name="ref_cie" id="ref_cie" class="form-control">
            </div>

            <div class="mb-3">
                <label for="ref_pcb" class="form-label">Réf PCB (optionnel)</label>
                <input type="text" name="ref_pcb" id="ref_pcb" class="form-control">
            </div>

            <div class="mb-3">
                <label for="clicher_pcb" class="form-label">Clicher PCB (optionnel)</label>
                <input type="text" name="clicher_pcb" id="clicher_pcb" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Ajouter</button>
            <a href="dashboard.php?view=boards" class="btn btn-secondary ms-2">Annuler</a>
        </form>
    </div>
</body>

</html>