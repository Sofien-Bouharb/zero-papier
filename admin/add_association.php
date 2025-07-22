<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';
$_SESSION['LAST_ACTIVITY'] = time();

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_GET['id'])) {
    redirect_with_error("ID du document manquant.");
}

$document_id = intval($_GET['id']);

// Get document info
$docStmt = $pdo->prepare("SELECT * FROM documents_search.documents WHERE document_id = ?");
$docStmt->execute([$document_id]);
$document = $docStmt->fetch();

if (!$document) {
    redirect_with_error("Document introuvable.");
}

// Get all workers and ilots (we no longer fetch all boards here)
$workers = $pdo->query("SELECT step_number, hostname, ilot_id FROM documents_search.workers ORDER BY hostname")->fetchAll();
$board_names = $pdo->query("SELECT DISTINCT board_name FROM documents_search.boards ORDER BY board_name")->fetchAll();
$ilots = $pdo->query("SELECT ilot_id, ilot_name FROM documents_search.ilot ORDER BY ilot_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Ajouter des associations</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body {
            background-color: #eaeaea;
        }

        h2,
        h4,
        label {
            color: #000;
        }

        #board_checkboxes {
            max-height: 200px;
            overflow-y: auto;
            background-color: #d1d2d5;
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

        li {
            color: #000;
        }
    </style>
</head>

<body>
    <nav class="navbar fixed-top navbar-expand-lg navbar-dark border-bottom border-info shadow-sm mb-4" style="background-color: #000;">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><img src="../assets/logo.png" alt="Company Logo" height="48"></a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Documents</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php?view=boards">Code Index</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php?view=posts">Postes</a></li>
                </ul>
                <a href="logout.php" class="btn" style="background-color: #bdd284;">Se déconnecter</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5 p-3">
        <h2 class="mt-3">Ajouter des associations pour :</h2>
        <h4 class="text-success"><?= htmlspecialchars($document['document_name']) ?></h4>
        <p class="text-muted">File path : <?= htmlspecialchars($document['file_path']) ?></p>

        <form method="POST" action="save_association.php" class="mt-4">
            <input type="hidden" name="document_id" id="document_id" value="<?= $document_id ?>">

            <div class="mb-3">
                <label for="ilot_select" class="form-label">Choisir un îlot :</label>
                <select id="ilot_select" class="form-select" onchange="filterPostsByIlot()">
                    <?php foreach ($ilots as $index => $ilot): ?>
                        <option value="<?= $ilot['ilot_id'] ?>" <?= $index === 0 ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ilot['ilot_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="selected_post" class="form-label">Choisir un poste :</label>
                <select id="selected_post" class="form-select">
                    <option value="" disabled selected>-- Sélectionnez un poste --</option>
                    <?php foreach ($workers as $w): ?>
                        <option value="<?= $w['step_number'] ?>" data-ilot-id="<?= $w['ilot_id'] ?>">
                            <?= htmlspecialchars($w['hostname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="board_name_select">Choisir un nom de carte :</label>
                <select id="board_name_select" class="form-select">
                    <option value="" selected disabled>-- Sélectionner un nom --</option>
                    <?php foreach ($board_names as $bn): ?>
                        <option value="<?= htmlspecialchars($bn['board_name']) ?>"><?= htmlspecialchars($bn['board_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group mb-2">
                <input type="text" id="board_id_search" class="form-control" placeholder="🔍 Rechercher par ID de carte...">
                <button type="button" class="btn btn-secondary m-1" id="select_all_boards">Tout sélectionner</button>
                <button type="button" class="btn btn-secondary m-1" id="deselect_all_boards">Tout désélectionner</button>
            </div>

            <div id="board_checkboxes" class="form-control text-light">
                <p class="text-muted">Sélectionnez un nom de carte et un poste.</p>
            </div>

            <button type="button" class="btn my-2" onclick="addMapping()" style="background-color:#2d91ae; color:#000;">➕ Ajouter l'association</button>
            <input type="hidden" name="mappings" id="mappingsInput">

            <ul id="mappingList" class="mt-3"></ul>

            <button type="submit" class="btn btn-success my-3">💾 Enregistrer les associations</button>
            <a href="dashboard.php" class="btn ms-2 my-3" style="background-color:#747e87; color:#000;">Retour</a>
        </form>
    </div>

    <script src="js/add_association_dynamic.js"></script>
</body>

</html>