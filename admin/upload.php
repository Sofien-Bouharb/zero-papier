<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';
$_SESSION['LAST_ACTIVITY'] = time();
?>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();

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
// Récupération des postes et cartes et îlots
$workers = $pdo->query("
SELECT step_number, hostname, ilot_id
FROM documents_search.workers
ORDER BY hostname
")->fetchAll();

$boards = $pdo->query("SELECT board_index_id, board_name FROM documents_search.boards ORDER BY board_name, board_index_id")->fetchAll();
$ilots = $pdo->query("SELECT ilot_id, ilot_name FROM documents_search.ilot ORDER BY ilot_name")->fetchAll();

// Fonction pour générer un nom de fichier propre
function slugify($text)
{
  $text = strtolower(trim($text));
  $text = preg_replace('/[^a-z0-9]+/', '_', $text);
  return trim($text, '_');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $document_name = trim($_POST['document_name'] ?? '');
  $file = $_FILES['pdf_file'] ?? null;
  $mappings = json_decode($_POST['mappings'] ?? '[]', true);

  if (!$document_name || !$file || $file['error'] !== 0 || empty($mappings)) {
    redirect_with_error("Tous les champs sont obligatoires, et vous devez sélectionner au moins une association poste-carte.");
  }

  // Get original filename (e.g., xyz.pdf), sanitize it
  $original_name = basename($file['name']);
  $filename = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $original_name);

  // Check if file_path already exists in DB
  $check = $pdo->prepare("SELECT COUNT(*) FROM documents_search.documents WHERE file_path = :path");
  $check->execute(['path' => $filename]);

  if ($check->fetchColumn() > 0) {
    redirect_with_error("Un fichier avec ce file path existe déjà.");
  }

  $upload_dir = '../uploads/';
  $target_path = $upload_dir . $filename;

  if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $stmt = $pdo->prepare("INSERT INTO documents_search.documents (document_name, file_path) VALUES (:name, :path) RETURNING document_id");
    $stmt->execute(['name' => $document_name, 'path' => $filename]);
    $document_id = $stmt->fetchColumn();

    $link_stmt = $pdo->prepare("INSERT INTO documents_search.board_post_documents (board_index_id, step_number, document_id) VALUES (:board_id, :step, :doc_id)");
    $uniquePairs = [];

    foreach ($mappings as $map) {
      $step = $map['step_number'];
      foreach ($map['board_ids'] as $board) {
        $key = $step . '-' . $board;
        $uniquePairs[$key] = [
          'step' => $step,
          'board_id' => $board
        ];
      }
    }

    // Now insert only unique associations
    foreach ($uniquePairs as $pair) {
      $link_stmt->execute([
        'board_id' => $pair['board_id'],
        'step' => $pair['step'],
        'doc_id' => $document_id
      ]);
    }

    header("Location: dashboard.php?view=documents");
    exit();
  } else {
    echo "Erreur lors du téléversement du fichier.";
  }
}
?>

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

    h2 {
      color: #000;
      margin: 0;
      padding: 0;
    }

    label {
      color: #000;
      font-weight: bold;
    }

    #board_checkboxes {
      max-height: 200px;
      overflow-y: auto;
      background-color: #d1d2d5;
    }
  </style>
</head>

<body>
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
    <h2 class="mt-3">Ajouter un document</h2>
    <form method="POST" enctype="multipart/form-data" class="mt-4">
      <div class="mb-3">
        <label for="document_name" class="form-label">Nom du document</label>
        <input type="text" name="document_name" id="document_name" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="pdf_file" class="form-label">Fichier PDF</label>
        <input type="file" name="pdf_file" id="pdf_file" class="form-control" accept="application/pdf" required>
      </div>

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
        <label class="form-label">Sélectionner les cartes à associer :</label>
        <div class="input-group mb-2">
          <input type="text" id="board_search" class="form-control" placeholder="Rechercher des cartes..." oninput="filterBoards()">
          <button type="button" class="btn btn-secondary m-1" onclick="selectAllFilteredBoards()">Sélectionner tout</button>
          <button type="button" class="btn btn-secondary m-1" onclick="deselectAllVisibleBoards()">Désélectionner tout</button>
        </div>
        <div id="board_checkboxes" class="form-control text-light" style="max-height: 200px; overflow-y: auto; background-color: #d1d2d5;">
          <?php foreach ($boards as $b): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="<?= $b['board_index_id'] ?>" id="board<?= $b['board_index_id'] ?>">
              <label class="form-check-label" for="board<?= $b['board_index_id'] ?>">
                <?= htmlspecialchars($b['board_name']) ?> (ID: <?= $b['board_index_id'] ?>)
              </label>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <button type="button" class="btn" onclick="addMapping()" style="background-color:#2d91ae; color:#000;">➕ Ajouter l'association</button>
      <input type="hidden" name="mappings" id="mappingsInput">

      <ul id="mappingList" class="mt-3"></ul>

      <button type="submit" class="btn btn-success my-3 ">📤 Ajouter</button>
      <a href="dashboard.php" class="btn btn ms-2 my-3" style="background-color:#747e87; color:#000;">Annuler</a>
    </form>
  </div>

  <script>
    function filterPostsByIlot() {
      const ilotSelect = document.getElementById('ilot_select');
      const selectedIlot = ilotSelect.value;
      const postSelect = document.getElementById('selected_post');

      let found = false;

      Array.from(postSelect.options).forEach(option => {
        if (!option.value) {
          option.style.display = 'block'; // Keep the placeholder
          return;
        }

        const ilotId = option.getAttribute('data-ilot-id');
        if (ilotId === selectedIlot) {
          option.style.display = 'block';
          if (!found) {
            option.selected = true;
            found = true;
          }
        } else {
          option.style.display = 'none';
          option.selected = false;
        }
      });

      // If no match was found, reset selection to placeholder
      if (!found) {
        postSelect.selectedIndex = 0;
      }
    }

    // Run it once on page load
    document.addEventListener('DOMContentLoaded', filterPostsByIlot);

    // dynamic document-post-board managing
    let mappings = [];

    function addMapping() {
      const postSelect = document.getElementById('selected_post');
      const postId = postSelect.value;
      const postLabel = postSelect.options[postSelect.selectedIndex].text;
      const checkboxes = document.querySelectorAll('#board_checkboxes input[type="checkbox"]:checked');

      const boardIds = Array.from(checkboxes).map(cb => cb.value);
      const boardLabels = Array.from(checkboxes).map(cb => cb.nextElementSibling.innerText);

      if (!postId || boardIds.length === 0) {
        alert("Veuillez sélectionner un poste et au moins une carte.");
        return;
      }

      const index = mappings.length;
      mappings.push({
        step_number: postId,
        board_ids: boardIds
      });
      document.getElementById('mappingsInput').value = JSON.stringify(mappings);

      const li = document.createElement('li');
      li.setAttribute('data-index', index);
      li.innerHTML = `<strong>${postLabel}</strong> ↔ ${boardLabels.join(', ')} <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeMapping(${index})">❌</button>`;
      document.getElementById('mappingList').appendChild(li);

      checkboxes.forEach(cb => cb.checked = false);
      postSelect.selectedIndex = 0;
    }

    function removeMapping(index) {
      mappings[index] = null; // Mark as deleted
      document.querySelector(`li[data-index='${index}']`).remove();
      document.getElementById('mappingsInput').value = JSON.stringify(mappings.filter(m => m));
    }

    // Function to filter boards based on search input
    function filterBoards() {
      const searchInput = document.getElementById('board_search').value.toLowerCase();
      const boardCheckboxes = document.querySelectorAll('#board_checkboxes .form-check');

      boardCheckboxes.forEach(checkbox => {
        const label = checkbox.querySelector('label').innerText.toLowerCase();
        if (label.includes(searchInput)) {
          checkbox.style.display = 'block';
        } else {
          checkbox.style.display = 'none';
        }
      });
    }

    // Function to select all filtered boards
    function selectAllFilteredBoards() {
      const visibleCheckboxes = document.querySelectorAll('#board_checkboxes .form-check[style="display: block;"] input[type="checkbox"]');
      visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
      });
    }

    // Function to deselect all visible boards
    function deselectAllVisibleBoards() {
      const visibleCheckboxes = document.querySelectorAll('#board_checkboxes .form-check[style="display: block;"] input[type="checkbox"]');
      visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
      });
    }
  </script>
</body>

</html>