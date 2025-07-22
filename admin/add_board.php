<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$old_input = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);




if (isset($_SESSION['error_message'])):
?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php unset($_SESSION['error_message']);
endif; ?>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php unset($_SESSION['success_message']);
endif; ?>

<script src="../js/bootstrap.bundle.min.js"></script>
<script>
    setTimeout(function() {
        const alert = document.querySelector('.alert');
        if (alert) {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }
    }, 4000);
</script>



<?php
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
        redirect_with_error($error, 'add_board.php', true);
    } elseif (!is_numeric($board_index_id) || $board_index_id < 10000 || $board_index_id > 99999) {

        redirect_with_error("Entrez un entier entre 10000 et 99999 pour l'ID de la carte.", 'add_board.php', true);
    } else {
        // Check if board_index_id already exists
        $check = $pdo->prepare("SELECT COUNT(*) FROM documents_search.boards WHERE board_index_id = :id");
        $check->execute(['id' => $board_index_id]);

        if ($check->fetchColumn() > 0) {

            redirect_with_error("Une carte avec cet ID existe déjà.", 'add_board.php', true);
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
                'repere_dm' => $repere_dm !== '' ? $repere_dm : '-',
                'designation' => $designation !== '' ? $designation : '-',
                'ref_cie' => $ref_cie !== '' ? $ref_cie : '-',
                'ref_pcb' => $ref_pcb !== '' ? $ref_pcb : '-',
                'clicher_pcb' => $clicher_pcb !== '' ? $clicher_pcb : '-',
            ]);
            $_SESSION['success_message'] = "Carte " . $board_index_id . " ajoutée avec succès.";
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

        .nav-link {
            color: #00d6ff !important;
            font-weight: bold;
        }

        .nav-link:hover {
            text-decoration: underline;
        }

        .navbar .nav-link {
            color: #fff !important;
        }

        .nav-link.active {
            color: #90969D !important;
        }
    </style>

</head>

<body class="pb-3">


    <!-- ✅ Bandeau de navigation -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-dark border-bottom border-info shadow-sm mb-4" style="background-color: #000;">
        <div class="container-fluid">

            <a class="navbar-brand" href="#">
                <img src="..\assets\logo.png" alt="Company Logo" height="48">
            </a>

            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link " href="dashboard.php" style="color: #fff;">Documents</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="dashboard.php?view=boards" style="color: #fff;">Code Index</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php?view=posts" style="color: #fff;">Postes</a>
                    </li>
                </ul>
                <a href="logout.php" class="btn" style="background-color: #bdd284;">Se déconnecter</a>
            </div>
        </div>
    </nav>


    <div class="container mt-5 p-3">
        <h2 class="mb-4 mt-3">📤 Ajouter une nouvelle carte</h2>


        <form method="POST">
            <input type="hidden" name="return_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            <div class="mb-3">
                <label for="board_index_id" class="form-label">ID Carte (index)</label>
                <input type="number" name="board_index_id" id="board_index_id" class="form-control" required min="10000"
                    max="99999" value="<?= htmlspecialchars($old_input['board_index_id'] ?? '') ?>"
                    oninput="this.value = this.value.slice(0, 5)">

            </div>

            <div class="mb-3">
                <label for="board_name" class="form-label">Nom de la carte</label>
                <input type="text" name="board_name" id="board_name" class="form-control" required
                    value="<?= htmlspecialchars($old_input['board_name'] ?? '') ?>">

            </div>

            <div class="mb-3">
                <label for="repere_dm" class="form-label">Repère DM (optionnel)</label>
                <input type="text" name="repere_dm" id="repere_dm" class="form-control"
                    value="<?= htmlspecialchars($old_input['repere_dm'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="designation" class="form-label">Désignation (optionnel)</label>
                <input type="text" name="designation" id="designation" class="form-control"
                    value="<?= htmlspecialchars($old_input['designation'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="ref_cie" class="form-label">Réf CIE (optionnel)</label>
                <input type="text" name="ref_cie" id="ref_cie" class="form-control"
                    value="<?= htmlspecialchars($old_input['ref_cie'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="ref_pcb" class="form-label">Réf PCB (optionnel)</label>
                <input type="text" name="ref_pcb" id="ref_pcb" class="form-control"
                    value="<?= htmlspecialchars($old_input['ref_pcb'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label for="clicher_pcb" class="form-label">Clicher PCB (optionnel)</label>
                <input type="text" name="clicher_pcb" id="clicher_pcb" class="form-control"
                    value="<?= htmlspecialchars($old_input['clicher_pcb'] ?? '') ?>">
            </div>

            <button type="submit" class="btn btn-success">Ajouter</button>
            <a href="dashboard.php?view=boards" class="btn btn-secondary ms-2">Annuler</a>
        </form>
    </div>
</body>

</html>