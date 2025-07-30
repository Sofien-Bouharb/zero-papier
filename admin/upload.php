<?php

require_once '../includes/auth_check.php'; // Ensure the user is authenticated
require_once '../includes/db.php';         // Database connection
require_once '../includes/helpers.php';    // Utility functions

//  Ensure session is started and update timeout timer
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['LAST_ACTIVITY'] = time(); // Used for session timeout

//  Retrieve previous form input if there was an error (for form repopulation)
$old_input = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']); // Clear old input after use

//  Display error message alert (top right corner)
if (isset($_SESSION['error_message'])):
?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
    <?= htmlspecialchars($_SESSION['error_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
  </div>
<?php unset($_SESSION['error_message']);
endif; ?>

<!--  Display success message alert -->
<?php if (isset($_SESSION['success_message'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1050;">
    <?= htmlspecialchars($_SESSION['success_message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
  </div>
<?php unset($_SESSION['success_message']);
endif; ?>

<!--  Auto-close alert after 4 seconds -->
<script src="../js/bootstrap.bundle.min.js"></script>
<script>
  setTimeout(() => {
    const alert = document.querySelector('.alert');
    if (alert) {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }
  }, 4000);
</script>

<?php
//  Fetch select dropdown options from the database
$workers = $pdo->query("SELECT step_number, hostname, ilot_id FROM documents_search.workers ORDER BY hostname")->fetchAll();
$board_names = $pdo->query("SELECT DISTINCT board_name FROM documents_search.boards ORDER BY board_name")->fetchAll();
$ilots = $pdo->query("SELECT ilot_id, ilot_name FROM documents_search.ilot ORDER BY ilot_name")->fetchAll();

//  Helper function: Slugify text for file-safe names
function slugify($text)
{
  $text = strtolower(trim($text));
  $text = preg_replace('/[^a-z0-9]+/', '_', $text);
  return trim($text, '_');
}

//  Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  //  Get form fields
  $document_name = trim($_POST['document_name'] ?? '');
  $file = $_FILES['pdf_file'] ?? null;
  $mappings = json_decode($_POST['mappings'] ?? '[]', true);

  //  Basic validation
  if (!$document_name || !$file || $file['error'] !== 0 || empty($mappings)) {
    redirect_with_error("Tous les champs sont obligatoires, et vous devez sélectionner au moins une association poste-carte.", 'upload.php', true);
  }

  //  Sanitize and normalize file name
  $original_name = basename($file['name']);
  $filename = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $original_name);

  //  Check for duplicate file path
  $check = $pdo->prepare("SELECT COUNT(*) FROM documents_search.documents WHERE file_path = :path");
  $check->execute(['path' => $filename]);
  if ($check->fetchColumn() > 0) {
    redirect_with_error("Un fichier avec ce file path existe déjà.", 'upload.php', true);
  }

  //  Move uploaded file to destination folder
  $upload_dir = '../uploads/';
  $target_path = $upload_dir . $filename;

  if (move_uploaded_file($file['tmp_name'], $target_path)) {
    //  Insert document info into database
    $stmt = $pdo->prepare("INSERT INTO documents_search.documents (document_name, file_path) VALUES (:name, :path) RETURNING document_id");
    $stmt->execute(['name' => $document_name, 'path' => $filename]);
    $document_id = $stmt->fetchColumn();

    //  Prepare for association insertion
    $inserted = 0;
    $link_stmt = $pdo->prepare("INSERT INTO documents_search.board_post_documents (board_index_id, step_number, document_id) VALUES (:board_id, :step, :doc_id)");

    //  Remove duplicate (post, board) combinations
    $uniquePairs = [];
    foreach ($mappings as $map) {
      $step = $map['step_number'];
      foreach ($map['board_ids'] as $board) {
        $key = $step . '-' . $board;
        $uniquePairs[$key] = ['step' => $step, 'board_id' => $board];
      }
    }

    //  Insert associations
    foreach ($uniquePairs as $pair) {
      $link_stmt->execute([
        'board_id' => $pair['board_id'],
        'step' => $pair['step'],
        'doc_id' => $document_id
      ]);
      $inserted++;
    }

    //  Success message + redirect
    $_SESSION['success_message'] = "Document ajouté avec succès et " . " $inserted association(s) créée(s).";
    header("Location: dashboard.php?view=documents");
    exit();
  } else {
    redirect_with_error("Erreur lors du téléversement du fichier.", 'upload.php', true);
  }
}
?>

<!--  HTML Upload Form Page -->
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Téléverser un document</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <style>
    body {
      background-color: #eaeaea;
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

    h2,
    label {
      color: #000;
      font-weight: bold;
    }

    #board_checkboxes {
      max-height: 200px;
      overflow-y: auto;
      background-color: #d1d2d5;
    }

    .emoji {
      width: 1em;
      height: 1em;
      vertical-align: middle;
    }
  </style>
</head>

<body>
  <!--  Top navbar -->
  <nav class="navbar fixed-top navbar-expand-lg navbar-dark border-bottom border-info shadow-sm mb-4" style="background-color: #000;">
    <div class="container-fluid">
      <a class="navbar-brand" href="#" style="cursor:default"><img src="../assets/logo.png" alt="Company Logo" height="48"></a>
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

  <!--  Form content -->
  <div class="container mt-5 p-3">
    <h2 class="mt-3">Ajouter un document</h2>
    <form method="POST" enctype="multipart/form-data" class="mt-4">
      <!-- Hidden input to preserve return page -->
      <input type="hidden" name="return_to" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

      <!--  Document name input -->
      <div class="mb-3">
        <label for="document_name">Nom du document</label>
        <input type="text" name="document_name" id="document_name" class="form-control" value="<?= htmlspecialchars($old_input['document_name'] ?? '') ?>" required>
      </div>

      <!--  PDF file upload -->
      <div class="mb-3">
        <label for="pdf_file">Fichier PDF</label>
        <input type="file" name="pdf_file" id="pdf_file" class="form-control" accept="application/pdf" required>
      </div>

      <!--  Ilot selection -->
      <div class="mb-3">
        <label for="ilot_select">Choisir un îlot :</label>
        <select id="ilot_select" class="form-select" onchange="filterPostsByIlot()">
          <?php foreach ($ilots as $index => $ilot): ?>
            <option value="<?= $ilot['ilot_id'] ?>" <?= $index === 0 ? 'selected' : '' ?>>
              <?= htmlspecialchars($ilot['ilot_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!--  Post selection (filtered by ilot) -->
      <div class="mb-3">
        <label for="selected_post">Choisir un poste :</label>
        <select id="selected_post" class="form-select">
          <option value="" disabled selected>-- Sélectionnez un poste --</option>
          <?php foreach ($workers as $w): ?>
            <option value="<?= $w['step_number'] ?>" data-ilot-id="<?= $w['ilot_id'] ?>">
              <?= htmlspecialchars($w['hostname']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!--  Board name family selection and search -->
      <div class="mb-3">
        <label>Sélectionner une famille de cartes: <span style="color: red;">*</span></label>
        <select id="board_name_select" class="form-select mb-2">
          <option value="" selected disabled>-- Sélectionner un nom de carte --</option>
          <?php foreach ($board_names as $bn): ?>
            <option value="<?= htmlspecialchars($bn['board_name']) ?>"><?= htmlspecialchars($bn['board_name']) ?></option>
          <?php endforeach; ?>
        </select>

        <div class="input-group mb-2">
          <input type="text" id="board_id_search" class="form-control" placeholder="ID carte (optionnel)">
          <button type="button" class="btn btn-outline-secondary btn-sm mx-1" onclick="searchBoards()"><img src="../../assets/emojis/1f50d.png" alt="search" class="emoji"></button>
          <button type="button" class="btn btn-secondary m-1" onclick="selectAllBoards()">Tout sélectionner</button>
          <button type="button" class="btn btn-secondary m-1" onclick="deselectAllBoards()">Tout désélectionner</button>
        </div>

        <div id="board_checkboxes" class="form-control text-light"></div>
      </div>

      <!--  Add mapping button and preview -->
      <button type="button" class="btn" onclick="addMapping()" style="background-color:#2d91ae; color:#000;"> <img src="../../assets/emojis/2795.png" alt="ajout" class="emoji"> Ajouter l'association</button>
      <input type="hidden" name="mappings" id="mappingsInput">
      <ul id="mappingList" class="mt-3"></ul>

      <!--  Submit + Cancel -->
      <button type="submit" class="btn btn-success my-3"><img src="../assets/emojis/1F4E4.png" alt="ajout" class="emoji"> Ajouter</button>
      <a href="dashboard.php" class="btn btn ms-2 my-3" style="background-color:#747e87; color:#000;">Annuler</a>
    </form>
  </div>

  <script src="js/upload_dynamic.js">
    console.log("js loaded");
  </script>
</body>

</html>