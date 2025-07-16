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










if (!isset($_GET['board_index_id'])) {
    redirect_with_error("Aucune carte spécifiée.");
}

$board_index_id = (int) $_GET['board_index_id'];

// Fetch board data
$stmt = $pdo->prepare("SELECT * FROM documents_search.boards WHERE board_index_id = :id");
$stmt->execute(['id' => $board_index_id]);
$board = $stmt->fetch();

if (!$board) {
    redirect_with_error("Carte introuvable.");
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
        redirect_with_error($error);
    } else {



        $check = $pdo->prepare("
  SELECT COUNT(*) FROM documents_search.boards 
  WHERE board_index_id = :id");
        $check->execute(['id' =>  $_POST['board_index_id']]);

        if ($check->fetchColumn() > 0) {
            redirect_with_error("Ce code index existe déjà.");
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
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Modifier une carte</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body {
            background-color: #eaeaea;
            color: #ffffff;
        }

        .container {
            max-width: 600px;
            margin-top: 50px;
        }

        h2 {
            color: #000;
            margin: 0;
            padding: 0;
        }

        label {
            color: #000;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2>Modifier la carte d'ID: <?= htmlspecialchars($board_index_id) ?></h2>

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

            <button type="submit" class="btn btn-success m-2">💾 Enregistrer</button>
            <a href="dashboard.php?view=boards" class="btn m-2" style="background-color:#747e87; color:#000;">Retour</a>
        </form>
    </div>
</body>

</html>