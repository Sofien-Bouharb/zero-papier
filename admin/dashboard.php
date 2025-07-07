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
      <a href="add_board.php" class="btn btn-success m-3 ">📤 Ajouter un code index</a>

      <table class="table table-dark table-bordered table-hover">
        <thead>
          <tr>
            <th style="text-align:center;">Nom</th>
            <th style="text-align:center;">Repère DM</th>
            <th style="text-align:center;">Désignation</th>
            <th style="text-align:center;">Réf CIE</th>
            <th style="text-align:center;">Réf PCB</th>
            <th style="text-align:center;">Clicher PCB</th>
            <th style="text-align:center;">Actions</th>

          </tr>
        </thead>
        <tbody>
          <?php
          $boards = $pdo->query("SELECT * FROM documents_search.boards ORDER BY board_name")->fetchAll();
          foreach ($boards as $b):
          ?>
            <tr>
              <td><?= htmlspecialchars($b['board_name']) ?></td>
              <td><?= htmlspecialchars($b['repere_dm'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['designation'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['ref_cie_actia'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['ref_pcb'] ?? '-') ?></td>
              <td><?= htmlspecialchars($b['clicher_pcb'] ?? '-') ?></td>
              <td style="text-align:center;">
                <a href="edit_board.php?id=<?= $b['board_index_id'] ?>" class="text-warning me-3" title="Modifier">✏️</a>
                <a href="delete_board.php?id=<?= $b['board_index_id'] ?>" class="text-danger" title="Supprimer" onclick="return confirm('Supprimer cette carte ?');">🗑️</a>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

    <?php elseif ($view === 'posts'): ?>
      <h3 class="mb-3">Liste des postes (workers)</h3>
      <a href="add_post.php" class="btn btn-success m-3">📤 Ajouter un poste</a>

      <table class="table table-dark table-bordered table-hover">
        <thead>
          <tr>
            <th style="text-align:center;">Nom d'hôte (hostname)</th>
            <th style="text-align:center;">Adresse IP</th>
            <th style="text-align:center;">Îlot</th> <!-- 🆕 -->
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
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
              <td><?= htmlspecialchars($w['ilot_name'] ?? 'Non défini') ?></td> <!-- 🆕 -->
              <td style="text-align:center;">
                <a href="edit_document.php?id=<?= $w['step_number'] ?>" class="text-warning me-3" title="Modifier">✏️</a>
                <a href="delete_document.php?id=<?= $w['step_number'] ?>" class="text-danger" title="Supprimer" onclick="return confirm('Supprimer ce poste ?');">🗑️</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>


    <?php else: // default view = documents 
    ?>
      <h3 class="mb-3">Liste des documents</h3>
      <a href="upload.php" class="btn btn-success m-3">📤 Ajouter un document</a>

      <table class="table table-dark table-bordered table-hover align-middle" style="border-width: 2px; border-color:rgb(188, 208, 212);">
        <thead>
          <tr>
            <th style="text-align:center;">Nom du document</th>
            <th style="text-align:center;">Fichier</th>
            <th style="text-align:center;">Poste</th>
            <th style="text-align:center;">Carte</th>
            <th style="text-align:center;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Get all associations (document ↔ board ↔ post)
          $stmt = $pdo->query("
        SELECT d.document_id, d.document_name, d.file_path,
               b.board_name, b.board_index_id,
               w.hostname
        FROM documents_search.board_post_documents bp
        JOIN documents_search.documents d ON bp.document_id = d.document_id
        JOIN documents_search.boards b ON bp.board_index_id = b.board_index_id
        JOIN documents_search.workers w ON bp.step_number = w.step_number
        ORDER BY d.document_name ASC,w.hostname, b.board_name
      ");
          $rows = $stmt->fetchAll();

          foreach ($rows as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['document_name']) ?></td>
              <td>
                <a href="/uploads/<?= urlencode($row['file_path']) ?>" target="_blank" class="text-info">
                  <?= htmlspecialchars($row['file_path']) ?>
                </a>
              </td>
              <td><span><strong><?= htmlspecialchars($row['hostname']) ?></strong></span></td>
              <td><strong><?= htmlspecialchars($row['board_name']) ?> (ID: <?= $row['board_index_id'] ?>)</strong></td>

              <td style="text-align:center;">
                <a href="edit_association.php?doc_id=<?= $row['document_id'] ?>&board_id=<?= $row['board_index_id'] ?>&hostname=<?= urlencode($row['hostname']) ?>" class="text-warning me-3" title="Modifier">✏️</a>

                <a href="delete_association.php?doc_id=<?= $row['document_id'] ?>&board_id=<?= $row['board_index_id'] ?>&hostname=<?= urlencode($row['hostname']) ?>" class="text-danger" title="Supprimer" onclick="return confirm('Supprimer cette association ?');">🗑️</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  </div>
</body>

</html>