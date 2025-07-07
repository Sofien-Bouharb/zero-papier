<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
$_SESSION['LAST_ACTIVITY'] = time();
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
    die("Tous les champs sont obligatoires, et vous devez sélectionner au moins une association poste-carte.");
  }

  $check = $pdo->prepare("SELECT COUNT(*) FROM documents_search.documents WHERE document_name = :name");
  $check->execute(['name' => $document_name]);
  if ($check->fetchColumn() > 0) {
    die("Un document avec ce nom existe déjà. Veuillez choisir un nom unique.");
  }

  $filename = slugify($document_name) . '.pdf';
  $upload_dir = '../uploads/';
  $target_path = $upload_dir . $filename;

  if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $stmt = $pdo->prepare("INSERT INTO documents_search.documents (document_name, file_path) VALUES (:name, :path) RETURNING document_id");
    $stmt->execute(['name' => $document_name, 'path' => $filename]);
    $document_id = $stmt->fetchColumn();

    $link_stmt = $pdo->prepare("INSERT INTO documents_search.board_post_documents (board_index_id, step_number, document_id) VALUES (:board_id, :step, :doc_id)");

    foreach ($mappings as $map) {
      $step = $map['step_number'];
      foreach ($map['board_ids'] as $board) {
        $link_stmt->execute([
          'board_id' => $board,
          'step' => $step,
          'doc_id' => $document_id
        ]);
      }
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
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-dark text-light">
  <div class="container mt-5">
    <h2>Ajouter un document PDF</h2>
    <form method="POST" enctype="multipart/form-data" class="mt-4">
      <div class="mb-3">
        <label for="document_name" class="form-label">Nom du document (unique)</label>
        <input type="text" name="document_name" id="document_name" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="pdf_file" class="form-label">Fichier PDF</label>
        <input type="file" name="pdf_file" id="pdf_file" class="form-control" accept="application/pdf" required>
      </div>

      <div class="mb-3">
        <label for="ilot_select" class="form-label">Choisir un îlot :</label>
        <select id="ilot_select" class="form-select" required onchange="filterPostsByIlot()">
          <?php foreach ($ilots as $ilot): ?>
            <option value="<?= $ilot['ilot_id'] ?>" <?= $ilot['ilot_name'] === 'ilot1' ? 'selected' : '' ?>>
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
        <div id="board_checkboxes" class="form-control bg-dark text-light" style="max-height: 200px; overflow-y: auto;">
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

      <button type="button" class="btn btn-info" onclick="addMapping()">➕ Ajouter l'association</button>
      <input type="hidden" name="mappings" id="mappingsInput">

      <ul id="mappingList" class="mt-3"></ul>

      <button type="submit" class="btn btn-primary my-3 ">📤 Téléverser</button>
      <a href="dashboard.php" class="btn btn-secondary ms-2 my-3">Retour</a>
    </form>

  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      filterPostsByIlot(); // Auto-run on page load
    });

    function filterPostsByIlot() {
      const ilotSelect = document.getElementById('ilot_select');
      const selectedIlot = ilotSelect.value;
      const postSelect = document.getElementById('selected_post');

      Array.from(postSelect.options).forEach(option => {
        if (!option.value) {
          option.style.display = 'block';
          return;
        }

        const optionIlot = option.getAttribute('data-ilot-id');

        if (optionIlot === selectedIlot) {
          option.style.display = 'block';
        } else {
          option.style.display = 'none';
        }
      });

      postSelect.selectedIndex = 0; // Reset post selection
    }




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
  </script>

</body>

</html>