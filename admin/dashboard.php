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
  setTimeout(function() {
    const alert = document.querySelector('.alert');
    if (alert) {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      bsAlert.close();
    }
  }, 4000);
</script>

<?php
// Get the view from the query parameter (set default to 'documents')
$view = $_GET['view'] ?? 'documents';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <style>
    body {
      padding-top: 70px;
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

    .table td {
      vertical-align: middle;
      background-color: #edf4f5;
    }

    .table th {

      vertical-align: middle;
      background-color: #afb3b9;
    }

    .sticky-pagination {
      border-bottom: 1px solid #444;
      background-color: #212529;
      /* Bootstrap dark */
    }

    h3 {
      color: #141414;
    }

    .pagination .page-link {
      color: #333;
      background-color: #fff;
      border: 1px solid #ccc;
    }

    .pagination .page-link:hover {
      background-color: #dcdcdc;
      color: #000;
    }

    .pagination .active .page-link {
      background-color: #bdd284;
      border-color: #bdd284;
      color: #000;
    }

    .search {
      background-color: #fff;
      border: 1px solid #ccc;
      color: #333;
      padding: 8px 12px;
      border-radius: 4px;
      width: 100%;
      max-width: 300px;
      transition: border-color 0.3s ease;
    }

    .search::placeholder {
      color: #999;
    }

    .search:focus {
      outline: none;
      border-color: #bdd284;
      box-shadow: 0 0 5px rgba(189, 210, 132, 0.5);

    }


    button.close,
    .btn-close {
      color: #000 !important;
      /* or any color like red, #333, etc. */
      opacity: 1 !important;
      /* make it fully visible */
    }
  </style>
</head>

<body data-view="<?= $view ?>">
  <!-- Navigation bar -->
  <nav class="navbar fixed-top navbar-expand-lg navbar-dark border-bottom border-info shadow-sm mb-4" style="background-color: #000;">
    <div class="container-fluid">

      <a class="navbar-brand" href="#">
        <img src="..\assets\logo.png" alt="Company Logo" height="48">
      </a>

      <div class="collapse navbar-collapse">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link <?= $view === 'documents' ? ' active' : '' ?>" href="?view=documents" style="color: #fff;">Documents</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $view === 'boards' ? ' active' : '' ?>" href="?view=boards" style="color: #fff;">Code Index</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $view === 'posts' ? ' active' : '' ?>" href="?view=posts" style="color: #fff;">Postes</a>
          </li>
        </ul>
        <a href="logout.php" class="btn" style="background-color: #bdd284;">Se déconnecter</a>
      </div>
    </div>
  </nav>

  <div class="container">
    <!-- The boards dashboard -->
    <?php if ($view === 'boards'): ?>
      <h3 class="m-3">Liste des cartes (boards)</h3>
      <!-- Search bar -->
      <div class="my-3 d-flex justify-content-center">
        <input type="text" id="searchBoard" class="form-control w-50 search" placeholder="🔍 Rechercher un code index, un nom, un repère, etc." autofocus>
      </div>
      <div class="row justify-content-between" id="paginationRow">
        <div class="col-auto">
          <a href="add_board.php" class="btn btn-success mb-2 ">📤 Ajouter un code index</a>
        </div>
        <!-- Pagination Navbar-->
        <?php
        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $countstmt = $pdo->query("SELECT COUNT(*) FROM documents_search.boards");
        $totalRows = $countstmt->fetchColumn();
        $totalPages = ceil($totalRows / $limit);
        ?>
        <?php if ($totalPages > 1): ?>
          <div class="col-auto" id="paginationContainer">
            <nav id="mainPagination">
              <ul class="pagination pagination-sm">
                <?php
                $range = 2;
                if ($page > 1):
                  echo '<li class="page-item"><a href="?view=boards&page=' . ($page - 1) . '" class="page-link page-link-nav" data-page="' . ($page - 1) . '">«</a></li>';
                endif;
                if ($page > $range + 1):
                  echo '<li class="page-item"><a href="?view=boards&page=1" class="page-link page-link-nav" data-page="1">1</a></li>';
                  echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                endif;
                for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++):
                  $active = ($i == $page) ? 'active' : '';
                  echo '<li class="page-item ' . $active . '"><a href="?view=boards&page=' . $i . '" class="page-link page-link-nav" data-page="' . $i . '">' . $i . '</a></li>';
                endfor;
                if ($page < $totalPages - $range):
                  echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                  echo '<li class="page-item"><a href="?view=boards&page=' . $totalPages . '" class="page-link page-link-nav" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
                endif;
                if ($page < $totalPages):
                  echo '<li class="page-item"><a href="?view=boards&page=' . ($page + 1) . '" class="page-link page-link-nav" data-page="' . ($page + 1) . '">»</a></li>';
                endif;
                ?>
              </ul>
            </nav>
          </div>
        <?php endif; ?>
      </div>
      <table class="table  table-bordered table-hover" id="fixedHeaderTable">
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
          // Fetch boards with pagination
          $stmt = $pdo->prepare("SELECT * FROM documents_search.boards ORDER BY board_index_id LIMIT :limit OFFSET :offset");
          $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
          $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
          $stmt->execute();
          $boards = $stmt->fetchAll();
          foreach ($boards as $b):
          ?>
            <tr>
              <td><?= htmlspecialchars($b['board_index_id']) ?></td>
              <td><?= htmlspecialchars($b['board_name']) ?></td>
              <td><?= htmlspecialchars($b['repere_dm'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['designation'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['ref_cie_actia'] ?? '-') ?></td>
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
      <!--  The posts dashboard -->
    <?php elseif ($view === 'posts'): ?>
      <h3 class="m-3">Liste des postes (workers)</h3>
      <!-- Search bar -->
      <div class="mb-3 d-flex justify-content-center">
        <input type="text" id="searchPost" class="form-control w-50 search" placeholder="🔍 Rechercher un poste, un ilot, une IP..." autofocus>
      </div>

      <div class="row justify-content-between" id="paginationRow">
        <div class="col-auto">
          <a href="add_post.php" class="btn btn-success mb-2">📤 Ajouter un poste</a>
        </div>
        <!-- Pagination Navbar-->
        <?php
        $limit = 10;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
        $countstmt = $pdo->query("SELECT COUNT(*) FROM documents_search.workers");
        $totalRows = $countstmt->fetchColumn();
        $totalPages = ceil($totalRows / $limit);
        ?>
        <?php if ($totalPages > 1): ?>
          <div class="col-auto" id="paginationContainer">
            <nav id="mainPagination">
              <ul class="pagination pagination-sm mb-0">
                <?php
                $range = 2;

                if ($page > 1):
                  echo '<li class="page-item"><a href="?view=posts&page=' . ($page - 1) . '" class="page-link page-link-nav" data-page="' . ($page - 1) . '">«</a></li>';
                endif;

                if ($page > $range + 1):
                  echo '<li class="page-item"><a href="?view=posts&page=1" class="page-link page-link-nav" data-page="1">1</a></li>';
                  echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                endif;

                for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++):
                  $active = ($i === $page) ? 'active' : '';
                  echo '<li class="page-item ' . $active . '"><a href="?view=posts&page=' . $i . '" class="page-link page-link-nav" data-page="' . $i . '">' . $i . '</a></li>';
                endfor;

                if ($page < $totalPages - $range):
                  echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                  echo '<li class="page-item"><a href="?view=posts&page=' . $totalPages . '" class="page-link page-link-nav" data-page="' . $totalPages . '">' . $totalPages . '</a></li>';
                endif;

                if ($page < $totalPages):
                  echo '<li class="page-item"><a href="?view=posts&page=' . ($page + 1) . '" class="page-link page-link-nav" data-page="' . ($page + 1) . '">»</a></li>';
                endif;
                ?>
              </ul>
            </nav>
          </div>
        <?php endif; ?>


      </div>
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th style="text-align:center;">Ilot</th>
            <th style="text-align:center;">Nom d'hôte (hostname)</th>
            <th style="text-align:center;">Adresse IP</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody id="postsTableBody">
          <?php
          // Fetch posts with pagination
          $stmt = $pdo->prepare("SELECT w.step_number, w.hostname, w.ip_address, i.ilot_name
                                          FROM documents_search.workers w
                                          LEFT JOIN documents_search.ilot i ON w.ilot_id = i.ilot_id
                                          ORDER BY  i.ilot_name, w.hostname
                                          LIMIT :limit OFFSET :offset");
          $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
          $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
          $stmt->execute();
          $workers = $stmt->fetchAll();
          foreach ($workers as $w):
          ?>
            <tr>
              <td><?= htmlspecialchars($w['ilot_name'] ?? 'Non défini') ?></td>
              <td><?= htmlspecialchars($w['hostname']) ?></td>
              <td><?= htmlspecialchars($w['ip_address']) ?></td>
              <td style="text-align:center;">
                <a href="edit_post.php?step_number=<?= $w['step_number'] ?>" class="text-warning me-3" title="Modifier">✏️</a>
                <a href="delete_post.php?id=<?= $w['step_number'] ?>" class="text-danger" title="Supprimer" onclick="return confirm('Supprimer ce poste ?');">🗑️</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <!-- Default view: documents -->
    <?php else:
    ?>
      <h3 class="m-3">Liste des associations documents-postes-codes</h3>
      <!-- Search bar -->
      <div class="mb-4 d-flex justify-content-center">
        <input type="text" id="searchDocument" class="form-control w-50 search" placeholder="🔍 Rechercher un document, un poste, une carte..." autofocus>
      </div>
      <!-- Pagination Navbar-->
      <?php
      $limit = 10;
      $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
      $offset = ($page - 1) * $limit;
      $countstmt = $pdo->query("SELECT COUNT(*) FROM documents_search.board_post_documents");
      $totalRows = $countstmt->fetchColumn();
      $totalPages = ceil($totalRows / $limit);
      ?>
      <div class="row align-items-center mb-2 text-center" id="paginationRow">
        <div class="col-md-4 text-start">
          <a href="upload.php" class="btn btn-success">📤 Ajouter un document</a>
        </div>
        <div class="col-md-4 text-center">
          <a href="#" class="btn" style="background-color: #747e87; color:#000;" data-bs-toggle="modal" data-bs-target="#deleteDocumentModal" title="Modifier ou supprimer un document">🛠️ Modifier/Supprimer un document</a>
        </div>
        <?php $range = 2; ?>
        <div class="col-md-4 text-end" id="paginationContainer">
          <nav id="mainPagination">
            <ul class="pagination justify-content-end mb-0 align-middle pagination-sm">
              <?php if ($page > 1): ?>
                <li class="page-item">
                  <a href="?page=<?= $page - 1 ?>" class="page-link page-link-nav" data-page="<?= $page - 1 ?>">«</a>
                </li>
              <?php endif; ?>
              <?php if ($page > $range + 1): ?>
                <li class="page-item"><a href="?page=1" class="page-link page-link-nav" data-page="1">1</a></li>
                <li class="page-item disabled"><span class="page-link">…</span></li>
              <?php endif; ?>
              <?php for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++): ?>
                <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                  <a href="?page=<?= $i ?>" class="page-link page-link-nav" data-page="<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <?php if ($page < $totalPages - $range): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
                <li class="page-item"><a href="?page=<?= $totalPages ?>" class="page-link page-link-nav" data-page="<?= $totalPages ?>"><?= $totalPages ?></a></li>
              <?php endif; ?>
              <?php if ($page < $totalPages): ?>
                <li class="page-item">
                  <a href="?page=<?= $page + 1 ?>" class="page-link page-link-nav" data-page="<?= $page + 1 ?>">»</a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>
      </div>
      <table id="documentsTable" class="table  table-bordered table-hover align-middle" style="border-width: 2px; border-color:rgb(188, 208, 212);">
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
          // Fetch documents with pagination
          $stmt = $pdo->prepare("
                        SELECT d.document_id, d.document_name, d.file_path,
                               b.board_name, b.board_index_id,
                               w.hostname, w.step_number
                        FROM documents_search.board_post_documents bp
                        JOIN documents_search.documents d ON bp.document_id = d.document_id
                        JOIN documents_search.boards b ON bp.board_index_id = b.board_index_id
                        JOIN documents_search.workers w ON bp.step_number = w.step_number
                        ORDER BY d.document_name ASC, w.hostname, b.board_name
                        LIMIT :limit OFFSET :offset
                    ");
          $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
          $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
          $stmt->execute();
          $rows = $stmt->fetchAll();
          foreach ($rows as $row):
          ?>
            <tr data-doc-id="<?= $row['document_id'] ?>">
              <td><?= htmlspecialchars($row['document_name']) ?></td>
              <td>
                <a href="../uploads/<?= urlencode($row['file_path']) ?>" target="_blank">
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

  <!-- Edit & Delete Document Modal (used to edit or delete a document not an association )-->
  <div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content text-white" style="background-color: #eaeaea;">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteDocumentModalLabel" style="color: #000; font-weight:bold;">Modifier/Supprimer un document</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
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

  <script src="../js/bootstrap.bundle.min.js"></script>
  <script>
    // Wait until the entire DOM is fully loaded before running the script
    document.addEventListener('DOMContentLoaded', function() {
      // Get the current view (documents, boards, or posts) from a data attribute on the <body>
      const view = document.body.dataset.view;

      /** ---------- MODAL LOGIC ---------- **/

      const modalElement = document.getElementById('deleteDocumentModal'); // Delete modal element
      const modalSearch = document.getElementById('modalSearch'); // Search input inside modal
      const documentList = document.getElementById('document-list'); // List where modal results are displayed

      // If modal exists on the page
      if (modalElement) {
        // When modal is shown, load documents into it
        modalElement.addEventListener('shown.bs.modal', function() {
          loadDocumentsModal();

          // When modal is hidden, reset the search input and clear the results
          modalElement.addEventListener('hidden.bs.modal', function() {
            if (modalSearch) modalSearch.value = '';
            documentList.innerHTML = '';
          });
        });
      }

      // Loads documents inside the delete modal with optional search query and page number
      function loadDocumentsModal(query = '', page = 1) {
        fetch(`get_documents.php?q=${encodeURIComponent(query)}&page=${page}`)
          .then(response => response.json())
          .then(data => {
            documentList.innerHTML = data.html + data.pagination;
            attachModalPaginationHandlers(); // Reattach pagination links inside the modal
          })
          .catch(error => {
            console.error('Erreur AJAX lors du chargement des documents :', error);
          });
      }

      // Attaches event listeners to modal pagination links
      function attachModalPaginationHandlers() {
        const links = document.querySelectorAll('.modal-page-link');
        links.forEach(link => {
          link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = parseInt(this.dataset.page);
            const query = modalSearch.value.trim();
            loadDocumentsModal(query, page); // Load content for selected page
          });
        });
      }

      // Attach search listener only once to avoid duplicates
      if (modalSearch && !modalSearch.dataset.listenerAttached) {
        modalSearch.addEventListener('input', () => {
          const query = modalSearch.value.trim();
          loadDocumentsModal(query, 1); // Search when input changes
        });
        modalSearch.dataset.listenerAttached = 'true'; // Mark listener as attached
      }

      // Function called when user confirms deletion of a document
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
            const feedback = document.getElementById('delete-feedback');
            if (result === 'success') {
              // Remove item from modal list and main table
              btn.closest('li').remove();
              const rows = document.querySelectorAll(`#documentsTable tr[data-doc-id="${id}"]`);
              rows.forEach(row => row.remove());

              feedback.innerHTML = '<div class="alert alert-success">Document supprimé avec succès.</div>';
            } else {
              feedback.innerHTML = '<div class="alert alert-danger">Erreur lors de la suppression.</div>';
            }
            // Remove feedback message after 3 seconds
            setTimeout(() => {
              feedback.innerHTML = '';
            }, 3000);
          });
      };

      /** ---------- VIEW-SPECIFIC LOGIC: DOCUMENTS DASHBOARD ---------- **/

      if (view === 'documents') {
        const input = document.getElementById('searchDocument'); // Search input
        const tbody = document.getElementById('documentsTableBody'); // Table body for documents
        const paginationContainer = document.querySelector('#mainPagination ul'); // Pagination area

        // If search input has value on load (e.g. from refresh), show results
        if (input && input.value.trim().length > 0) {
          loadSearchResults(input.value.trim(), 1);
        }

        // Attach event on typing in search input
        if (input) {
          input.addEventListener('keyup', function() {
            const query = input.value.trim();
            if (query.length > 0) {
              loadSearchResults(query, 1); // Search
            } else {
              window.location.href = window.location.pathname; // Reload the page to reset view
            }
          });
        }

        // AJAX search for documents
        function loadSearchResults(query = '', page = 1) {
          fetch(`search_documents.php?q=${encodeURIComponent(query)}&page=${page}`)
            .then(response => response.json())
            .then(data => {
              tbody.innerHTML = data.html;
              paginationContainer.innerHTML = data.pagination;
              attachPaginationHandlers(); // Rebind pagination links
            })
            .catch(error => {
              console.error('Erreur AJAX lors de la recherche des documents :', error);
            });
        }

        // Binds pagination events for documents view
        function attachPaginationHandlers() {
          const paginationLinks = document.querySelectorAll('.page-link-nav, .search-page-link');
          paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
              e.preventDefault();
              const page = parseInt(this.dataset.page);
              const query = input ? input.value.trim() : '';
              if (query.length > 0) {
                loadSearchResults(query, page);
              } else {
                const url = new URL(window.location.href);
                url.searchParams.set('page', page);
                url.searchParams.set('view', 'documents');
                window.location.href = url.toString(); // Full reload for normal pagination
              }
            });
          });
        }

        attachPaginationHandlers(); // Initial pagination
      }

      /** ---------- VIEW-SPECIFIC LOGIC: BOARDS DASHBOARD ---------- **/
      else if (view === 'boards') {
        const boardInput = document.getElementById('searchBoard');
        const boardTbody = document.getElementById('boardsTableBody');
        const paginationContainer = document.querySelector('#mainPagination ul');

        if (boardInput) {
          boardInput.addEventListener('input', () => {
            const query = boardInput.value.trim();
            if (query.length > 0) {
              loadSearchBoards(query, 1);
            } else {
              window.location.href = '?view=boards';
            }
          });
        }

        function loadSearchBoards(query = '', page = 1) {
          fetch(`search_boards.php?q=${encodeURIComponent(query)}&page=${page}`)
            .then(res => res.json())
            .then(data => {
              boardTbody.innerHTML = data.html;
              paginationContainer.innerHTML = data.pagination;
              attachBoardPaginationHandlers();
            })
            .catch(error => console.error('Erreur AJAX lors de la recherche des cartes :', error));
        }

        function attachBoardPaginationHandlers() {
          document.querySelectorAll('.page-link-nav').forEach(link => {
            link.addEventListener('click', function(e) {
              e.preventDefault();
              const page = parseInt(this.dataset.page);
              const query = boardInput ? boardInput.value.trim() : '';
              if (query.length > 0) {
                loadSearchBoards(query, page);
              } else {
                const url = new URL(window.location.href);
                url.searchParams.set('view', 'boards');
                url.searchParams.set('page', page);
                window.location.href = url.toString();
              }
            });
          });
        }

        attachBoardPaginationHandlers(); // Initial page load
      }

      /** ---------- VIEW-SPECIFIC LOGIC: POSTS DASHBOARD ---------- **/
      else if (view === 'posts') {
        const postInput = document.getElementById('searchPost');
        const postTbody = document.getElementById('postsTableBody');
        const paginationContainer = document.querySelector('#mainPagination ul');

        if (postInput) {
          postInput.addEventListener('input', () => {
            const query = postInput.value.trim();
            if (query.length > 0) {
              loadSearchPosts(query, 1);
            } else {
              window.location.href = '?view=posts';
            }
          });
        }

        function loadSearchPosts(query = '', page = 1) {
          fetch(`search_posts.php?q=${encodeURIComponent(query)}&page=${page}`)
            .then(res => res.json())
            .then(data => {
              postTbody.innerHTML = data.html;
              paginationContainer.innerHTML = data.pagination;
              attachPostPaginationHandlers();
            })
            .catch(error => console.error('Erreur AJAX lors de la recherche des postes :', error));
        }

        function attachPostPaginationHandlers() {
          document.querySelectorAll('.page-link-nav').forEach(link => {
            link.addEventListener('click', function(e) {
              e.preventDefault();
              const page = parseInt(this.dataset.page);
              const query = postInput ? postInput.value.trim() : '';
              if (query.length > 0) {
                loadSearchPosts(query, page);
              } else {
                const url = new URL(window.location.href);
                url.searchParams.set('view', 'posts');
                url.searchParams.set('page', page);
                window.location.href = url.toString();
              }
            });
          });
        }

        attachPostPaginationHandlers(); // Initial page load
      }
    });
  </script>

</body>

</html>