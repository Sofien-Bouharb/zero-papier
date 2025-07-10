<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
$_SESSION['LAST_ACTIVITY'] = time(); // Met à jour l'heure de la dernière activité
// Quelle vue est demandée ?
$view = $_GET['view'] ?? 'documents';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">


  <style>
    body {
      padding-top: 70px;
      background-image: url('../assets/bg2.avif');
      background-size: cover;
      background-repeat: no-repeat;
      background-position: center;
      background-attachment: fixed;
      color: white;
    }

    .nav-link {
      color: #00d6ff !important;
      font-weight: bold;
    }

    .nav-link:hover {
      text-decoration: underline;
    }

    .nav-link.active {
      color: rgb(228, 54, 54) !important;
    }

    .table th,
    .table td {
      vertical-align: middle;
    }
  </style>
</head>

<body>

  <!-- ✅ Bandeau de navigation -->
  <nav class="navbar fixed-top navbar-expand-lg navbar-dark bg-dark border-bottom border-info shadow-sm mb-4">
    <div class="container-fluid">
      <span class="navbar-brand text-info">Tableau de bord</span>
      <div class="collapse navbar-collapse">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link <?= $view === 'documents' ? ' active' : '' ?>" href="?view=documents">Documents</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $view === 'boards' ? ' active' : '' ?>" href="?view=boards">Code Index</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $view === 'posts' ? ' active' : '' ?>" href="?view=posts">Postes</a>
          </li>
        </ul>

        <a href="logout.php" class="btn btn-outline-danger">Se déconnecter</a>
      </div>
    </div>
  </nav>

  <div class="container">

    <?php if ($view === 'boards'): ?>
      <h3 class="mb-3">Liste des cartes (boards)</h3>

      <div class="my-3 d-flex justify-content-center">
        <input type="text" id="searchBoard" class="form-control w-50" placeholder="🔍 Rechercher un code index, un nom, un repère, etc.">
      </div>

      <a href="add_board.php" class="btn btn-success m-3 ">📤 Ajouter un code index</a>

      <table class="table table-dark table-bordered table-hover">
        <thead>
          <tr>
            <th style="text-align:center;">Code</th>
            <th style="text-align:center;">Nom</th>
            <th style="text-align:center;">Repère DM</th>
            <th style="text-align:center;">Désignation</th>
            <th style="text-align:center;">Réf CIE</th>
            <th style="text-align:center;">Réf PCB</th>
            <th style="text-align:center;">Clicher PCB</th>
            <th style="text-align:center;">Actions</th>

          </tr>
        </thead>
        <tbody id="boardsTableBody">
          <?php
          $boards = $pdo->query("SELECT * FROM documents_search.boards ORDER BY board_name")->fetchAll();
          foreach ($boards as $b):
          ?>
            <tr>
              <td><?= htmlspecialchars($b['board_index_id']) ?></td>
              <td><?= htmlspecialchars($b['board_name']) ?></td>
              <td><?= htmlspecialchars($b['repere_dm'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['designation'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['ref_cie'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['ref_pcb'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['clicher_pcb'] ?? '-') ?></td>
              <td style="text-align:center;">
                <a href="edit_board.php?board_index_id=<?= $b['board_index_id'] ?>" class="text-warning me-3" title="Modifier">✏️</a>
                <a href="delete_board.php?id=<?= $b['board_index_id'] ?>" class="text-danger" title="Supprimer" onclick="return confirm('Supprimer cette carte ?');">🗑️</a>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php elseif ($view === 'posts'): ?>
      <h3 class="mb-3">Liste des postes (workers)</h3>
      <div class="mb-3 d-flex justify-content-center">
        <input type="text" id="searchPost" class="form-control w-50" placeholder="🔍 Rechercher un poste, un ilot, une IP...">
      </div>

      <a href="add_post.php" class="btn btn-success m-3">📤 Ajouter un poste</a>

      <table class="table table-dark table-bordered table-hover">
        <thead>
          <tr>
            <th style="text-align:center;">Nom d'hôte (hostname)</th>
            <th style="text-align:center;">Adresse IP</th>
            <th style="text-align:center;">Îlot</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody id="postsTableBody">
          <?php
          $workers = $pdo->query("
        SELECT w.step_number, w.hostname, w.ip_address, i.ilot_name
        FROM documents_search.workers w
        LEFT JOIN documents_search.ilot i ON w.ilot_id = i.ilot_id
        ORDER BY w.hostname
      ")->fetchAll();

          foreach ($workers as $w):
          ?>
            <tr>
              <td><?= htmlspecialchars($w['hostname']) ?></td>
              <td><?= htmlspecialchars($w['ip_address']) ?></td>
              <td><?= htmlspecialchars($w['ilot_name'] ?? 'Non défini') ?></td>
              <td style="text-align:center;">
                <a href="edit_post.php?step_number=<?= $w['step_number'] ?>" class="text-warning me-3" title="Modifier">✏️</a>
                <a href="delete_post.php?id=<?= $w['step_number'] ?>" class="text-danger" title="Supprimer" onclick="return confirm('Supprimer ce poste ?');">🗑️</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>


    <?php else: // default view = documents 
    ?>
      <h3 class="my-4">Liste des associations documents-postes-codes</h3>
      <div class="mb-3 d-flex justify-content-center">
        <input type="text" id="searchDocument" class="form-control w-50" placeholder="🔍 Rechercher un document, un poste, une carte..." autofocus>
      </div>





      <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="upload.php" class="btn btn-success">📤 Ajouter un document</a>
        <a href="#" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#deleteDocumentModal" title="Modifier ou supprimer un document">🛠️ Modifier/Supprimer un document</a>
      </div>



      <table id="documentsTable" class="table table-dark table-bordered table-hover align-middle" style="border-width: 2px; border-color:rgb(188, 208, 212);">
        <thead>
          <tr>
            <th style="text-align:center;">Nom du document</th>
            <th style="text-align:center;">Fichier</th>
            <th style="text-align:center;">Poste</th>
            <th style="text-align:center;">Carte</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody id="documentsTableBody">
          <?php
          // Get all associations (document ↔ board ↔ post)
          $stmt = $pdo->query("
        SELECT d.document_id, d.document_name, d.file_path,
               b.board_name, b.board_index_id,
               w.hostname,w.step_number
        FROM documents_search.board_post_documents bp
        JOIN documents_search.documents d ON bp.document_id = d.document_id
        JOIN documents_search.boards b ON bp.board_index_id = b.board_index_id
        JOIN documents_search.workers w ON bp.step_number = w.step_number
        ORDER BY d.document_name ASC,w.hostname, b.board_name
      ");
          $rows = $stmt->fetchAll();

          foreach ($rows as $row): ?>
            <tr data-doc-id="<?= $row['document_id'] ?>">
              <td><?= htmlspecialchars($row['document_name']) ?></td>
              <td>
                <a href="../uploads/<?= urlencode($row['file_path']) ?>" target="_blank" class="text-info">
                  <?= htmlspecialchars($row['file_path']) ?>
                </a>
              </td>
              <td><span><strong><?= htmlspecialchars($row['hostname']) ?></strong></span></td>
              <td><strong><?= htmlspecialchars($row['board_name']) ?> (ID: <?= $row['board_index_id'] ?>)</strong></td>

              <td style="text-align:center;">
                <a href="delete_association.php?doc_id=<?= $row['document_id'] ?>&board_id=<?= $row['board_index_id'] ?>&step_number=<?= urlencode($row['step_number']) ?>" class="text-danger" title="Supprimer cette association doc-post-board" onclick="return confirm('Supprimer cette association ?');">🗑️</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>




    <?php endif; ?>

  </div>


  <!-- Delete Document Modal -->
  <div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content bg-dark text-white">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteDocumentModalLabel">Modifier/Supprimer un document</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
        </div>
        <div class="modal-body">
          <div id="delete-feedback"></div>
          <input type="text" id="modalSearch" class="form-control mb-3" placeholder="🔍 Rechercher un document..." autofocus>

          <ul id="document-list" class="list-group" style="max-height: 350px; overflow-y: auto;">
            <!-- AJAX will insert document rows here -->
          </ul>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const modalElement = document.getElementById('deleteDocumentModal');

      if (modalElement) {
        modalElement.addEventListener('shown.bs.modal', function() {

          loadDocuments();
          modalElement.addEventListener('hidden.bs.modal', function() {
            const searchInput = document.getElementById('modalSearch');
            const list = document.getElementById('document-list');

            if (searchInput) {
              searchInput.value = '';
            }

            if (allDocuments.length > 0 && list) {
              // Re-render full list
              list.innerHTML = '';
              allDocuments.forEach(doc => {
                const item = document.createElement('li');
                item.className = 'list-group-item bg-secondary text-white mb-2';

                item.innerHTML = `
        <div class="d-flex justify-content-between align-items-start flex-wrap">
          <div>
            <strong>${doc.document_name}</strong><br>
            <small>
              📄 <a href="../uploads/${doc.file_path}" target="_blank" class="text-info text-decoration-underline">${doc.file_path}</a>
            </small>
          </div>
          <div class="mt-2 mt-sm-0">
            <a href="edit_document.php?id=${doc.document_id}" class="btn btn-sm btn-warning me-2" title="Modifier ce document">Modifier document</a>
            <a href="add_association.php?id=${doc.document_id}" class="btn btn-sm btn-info me-2" title="Ajouter des associations">Ajouter associations</a>
            <button class="btn btn-sm btn-danger" onclick="deleteDocument(${doc.document_id}, this)" title="Supprimer ce document complétement">Supprimer</button>
          </div>
        </div>
      `;

                list.appendChild(item);
              });
            }
          });

        });
      }

      let allDocuments = []; // Global variable to store all docs

      function loadDocuments() {
        fetch('get_documents.php')
          .then(response => response.json())
          .then(data => {
            allDocuments = data;

            const list = document.getElementById('document-list');
            const searchInput = document.getElementById('modalSearch');
            list.innerHTML = '';

            // Create list items function
            const renderList = (docs) => {
              list.innerHTML = ''; // Clear previous content

              if (docs.length === 0) {
                const empty = document.createElement('li');
                empty.className = 'list-group-item  text-muted';
                empty.textContent = 'Aucun document trouvé.';
                list.appendChild(empty);
                return;
              }

              docs.forEach(doc => {
                const item = document.createElement('li');
                item.className = 'list-group-item bg-secondary text-white mb-2';

                item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start flex-wrap">
              <div>
                <strong>${doc.document_name}</strong><br>
                <small>
                  📄 <a href="../uploads/${doc.file_path}" target="_blank" class="text-info text-decoration-underline">${doc.file_path}</a>
                </small>
              </div>
              <div class="mt-2 mt-sm-0">
                <a href="edit_document.php?id=${doc.document_id}" class="btn btn-sm btn-warning me-2" title="Modifier ce document">Modifier document</a>
                <a href="add_association.php?id=${doc.document_id}" class="btn btn-sm btn-info me-2" title="Ajouter des associations">Ajouter associations</a>
                <button class="btn btn-sm btn-danger" onclick="deleteDocument(${doc.document_id}, this)" title="Supprimer ce document complétement">Supprimer</button>
              </div>
            </div>
          `;

                list.appendChild(item);
              });
            };

            renderList(allDocuments); // Initial display

            // Attach search input listener only once
            if (searchInput && !searchInput.dataset.listenerAttached) {
              searchInput.addEventListener('input', () => {
                const query = searchInput.value.trim().toLowerCase();

                const filtered = allDocuments.filter(doc =>
                  doc.document_name.toLowerCase().includes(query) ||
                  doc.file_path.toLowerCase().includes(query)
                );

                renderList(filtered);
              });

              // Prevent duplicate event listeners
              searchInput.dataset.listenerAttached = 'true';
            }
          })
          .catch(error => {
            console.error('Erreur lors du chargement des documents:', error);
          });
      }

      window.deleteDocument = function(id, btn) {
        if (!confirm('Confirmer la suppression du document ?')) return;

        fetch('delete_document.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'document_id=' + encodeURIComponent(id)
          })
          .then(response => response.text())
          .then(result => {
            if (result === 'success') {
              // Remove from modal list
              btn.closest('li').remove();


              // Remove all corresponding rows from the main table
              const rows = document.querySelectorAll(`#documentsTable tr[data-doc-id="${id}"]`);
              rows.forEach(row => row.remove());

              const feedback = document.getElementById('delete-feedback');
              feedback.innerHTML = '<div class="alert alert-success">Document supprimé avec succès.</div>';

              // Auto-hide after 3 seconds
              setTimeout(() => {
                feedback.innerHTML = '';
              }, 3000);

            } else {
              feedback.innerHTML = '<div class="alert alert-danger">Erreur lors de la suppression.</div>';
              setTimeout(() => {
                feedback.innerHTML = '';
              }, 3000);
            }
          });
      };


    });





    document.addEventListener('DOMContentLoaded', function() {
      const input = document.getElementById('searchDocument');
      const tbody = document.getElementById('documentsTableBody');

      input.addEventListener('keyup', function() {
        const query = input.value;

        fetch('search_documents.php?q=' + encodeURIComponent(query))
          .then(response => response.text())
          .then(html => {
            tbody.innerHTML = html;
          })
          .catch(error => {
            console.error('Erreur AJAX lors de la recherche des documents :', error);
          });
      });
    });

    document.addEventListener('DOMContentLoaded', function() {
      const boardInput = document.getElementById('searchBoard');
      const boardTbody = document.getElementById('boardsTableBody');

      if (boardInput && boardTbody) {
        boardInput.addEventListener('keyup', function() {
          const query = boardInput.value;

          fetch('search_boards.php?q=' + encodeURIComponent(query))
            .then(response => response.text())
            .then(html => {
              boardTbody.innerHTML = html;
            })
            .catch(error => {
              console.error('Erreur AJAX lors de la recherche des cartes :', error);
            });
        });
      }
    });

    document.addEventListener('DOMContentLoaded', function() {
      const postInput = document.getElementById('searchPost');
      const postTbody = document.getElementById('postsTableBody');

      if (postInput && postTbody) {
        postInput.addEventListener('keyup', function() {
          const query = postInput.value;

          fetch('search_posts.php?q=' + encodeURIComponent(query))
            .then(response => response.text())
            .then(html => {
              postTbody.innerHTML = html;
            })
            .catch(error => {
              console.error('Erreur AJAX lors de la recherche des postes :', error);
            });
        });
      }
    });
  </script>




</body>

</html>