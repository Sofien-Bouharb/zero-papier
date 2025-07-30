<?php
// Check if the admin is logged in
require_once '../includes/auth_check.php';

// Connect to the database
require_once '../includes/db.php';

// Include helper functions
require_once '../includes/helpers.php';

// Ensure a session is started
if (session_status() === PHP_SESSION_NONE) session_start();

// Update the last activity timestamp (for session timeout management)
$_SESSION['LAST_ACTIVITY'] = time();

//Session messages handling
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
//Check for board index ID in the URL
if (!isset($_GET['board_index_id'])) {
    redirect_with_error("Aucune carte spécifiée.");
}
// Get the board index ID from the URL
$board_index_id = (int) $_GET['board_index_id'];

// Fetch board data
$stmt = $pdo->prepare("SELECT * FROM documents_search.boards WHERE board_index_id = :id");
$stmt->execute(['id' => $board_index_id]);
$board = $stmt->fetch();

if (!$board) {
    redirect_with_error("Carte introuvable.");
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Get form inputs and attribute '-' if value not set
    $board_name = trim($_POST['board_name'] ?? '-');
    $repere_dm = trim($_POST['repere_dm'] ?? '-');
    $designation = trim($_POST['designation'] ?? '-');
    $ref_cie = trim($_POST['ref_cie'] ?? '-');
    $ref_pcb = trim($_POST['ref_pcb'] ?? '-');
    $clicher_pcb = trim($_POST['clicher_pcb'] ?? '-');

    // Validate required fields
    if (!$board_name) {
        $error = "Le nom de la carte est un champ obligatoire.";
        redirect_with_error($error, 'edit_board.php?board_index_id=' . $board_index_id);
    } else {
        // Update the board
        $update = $pdo->prepare("
UPDATE documents_search.boards
SET 
board_name = :name,
repere_dm = :repere,
designation = :des,
ref_cie_actia = :cie,
ref_pcb = :pcb,
clicher_pcb = :clicher
WHERE board_index_id = :id
");

        $update->execute([

            'name' => $board_name,
            'repere' => $repere_dm,
            'des' => $designation,
            'cie' => $ref_cie,
            'pcb' => $ref_pcb,
            'clicher' => $clicher_pcb,
            'id' => $board_index_id
        ]);
        $_SESSION['success_message'] = "Carte modifié avec succès.";
        header("Location: dashboard.php?view=boards");
        exit();
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

        .emoji {
            width: 1em;
            height: 1em;
            vertical-align: middle;
        }
    </style>
</head>

<body>
    <!-- Navigation bar -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-dark border-bottom border-info shadow-sm mb-4" style="background-color: #000;">
        <div class="container-fluid">

            <a class="navbar-brand" href="#" style="cursor:default">
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
    <!-- Form -->
    <div class="container mt-5 p-3">
        <h2 class="mt-3">Modifier la carte d'ID: <?= htmlspecialchars($board_index_id) ?></h2>
        <form method="POST" class="mt-4">
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

            <button type="submit" class="btn btn-success m-2"><img src="../../assets/emojis/1f4be.png" alt="enregistrer" class="emoji"> Enregistrer</button>
            <a href="dashboard.php?view=boards" class="btn m-2" style="background-color:#747e87; color:#000;">Retour</a>
        </form>
    </div>
</body>

</html>