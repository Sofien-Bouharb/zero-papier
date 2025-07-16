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

    // Get all workers, boards, and ilots
    $workers = $pdo->query("SELECT step_number, hostname, ilot_id FROM documents_search.workers ORDER BY hostname")->fetchAll();
    $boards = $pdo->query("SELECT board_index_id, board_name FROM documents_search.boards ORDER BY board_name, board_index_id")->fetchAll();
    $ilots = $pdo->query("SELECT ilot_id, ilot_name FROM documents_search.ilot ORDER BY ilot_name")->fetchAll();

    // Get all existing associations for this document
    $existingStmt = $pdo->prepare("
    SELECT step_number, board_index_id
    FROM documents_search.board_post_documents
    WHERE document_id = ?
    ");
    $existingStmt->execute([$document_id]);
    $existing = $existingStmt->fetchAll();

    $existing_map = [];
    foreach ($existing as $assoc) {
        $step = $assoc['step_number'];
        $board = $assoc['board_index_id'];
        $existing_map[$step][] = $board;
    }
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
            <h2>Ajouter des associations pour :</h2>
            <h4 class="mt-3 text-success"><?= htmlspecialchars($document['document_name']) ?></h4>
            <p class="text-muted">File path : <?= htmlspecialchars($document['file_path']) ?></p>

            <form method="POST" action="save_association.php" class="mt-4">
                <input type="hidden" name="document_id" value="<?= $document_id ?>">

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

                <!-- Boards Checklist -->
                <div class="mb-3">
                    <label class="form-label">Sélectionner les cartes à associer :</label>
                    <div id="board_checkboxes" class="form-control text-light " style="max-height: 200px; overflow-y: auto; background-color: #d1d2d5;">
                        <p class="text-muted">Sélectionnez d’abord un poste.</p>
                    </div>
                </div>


                <button type="button" class="btn" onclick="addMapping()" style="background-color:#2d91ae; color:#000;">➕ Ajouter l'association</button>
                <input type="hidden" name="mappings" id="mappingsInput">

                <ul id="mappingList" class="mt-3"></ul>

                <button type="submit" class="btn btn-success my-3">💾 Enregistrer les associations</button>
                <a href="dashboard.php" class="btn  ms-2 my-3" style="background-color:#747e87; color:#000;">Retour</a>
            </form>
        </div>

        <script>
            const postSelect = document.getElementById('selected_post');
            const checkboxesDiv = document.getElementById('board_checkboxes');
            const mappings = [];
            const existingMap = <?= json_encode($existing_map) ?>;

            function filterPostsByIlot() {
                const ilotSelect = document.getElementById('ilot_select');
                const selectedIlot = ilotSelect.value;
                const postSelect = document.getElementById('selected_post');

                let firstMatchFound = false;

                Array.from(postSelect.options).forEach(option => {
                    if (!option.value) {
                        option.style.display = 'block'; // keep the placeholder
                        return;
                    }

                    const ilotId = option.getAttribute('data-ilot-id');
                    if (ilotId === selectedIlot) {
                        option.style.display = 'block';

                        if (!firstMatchFound) {
                            option.selected = true;
                            firstMatchFound = true;
                        }
                    } else {
                        option.style.display = 'none';
                        option.selected = false;
                    }
                });

                // Trigger board checklist load for the first matching post
                if (firstMatchFound) {
                    postSelect.dispatchEvent(new Event('change'));
                }
            }

            function clearBoards() {
                const allCheckboxes = document.querySelectorAll('#board_checkboxes .board-option');
                allCheckboxes.forEach(el => el.style.display = 'block');
            }

            postSelect.addEventListener('change', () => {
                clearBoards();

                const step_number = postSelect.value;
                const boardsToExclude = existingMap[step_number] || [];

                document.querySelectorAll('#board_checkboxes .board-option').forEach(el => {
                    const board_id = el.getAttribute('data-board-id');
                    if (boardsToExclude.includes(board_id)) {
                        el.style.display = 'none';
                        el.querySelector('input').checked = false;
                    }
                });
            });

            function addMapping() {
                const step_number = postSelect.value;
                const post_label = postSelect.options[postSelect.selectedIndex]?.text || "";
                const selected = document.querySelectorAll('#board_checkboxes input[type="checkbox"]:checked');

                if (!step_number || selected.length === 0) {
                    alert("Veuillez sélectionner un poste et au moins une carte.");
                    return;
                }

                const board_ids = Array.from(selected).map(cb => cb.value);
                const board_labels = Array.from(selected).map(cb => cb.nextElementSibling.innerText);

                const index = mappings.length;
                mappings.push({
                    step_number,
                    board_ids
                });
                document.getElementById('mappingsInput').value = JSON.stringify(mappings);

                const li = document.createElement('li');
                li.setAttribute('data-index', index);
                li.innerHTML = `<strong>${post_label}</strong> ↔ ${board_labels.join(', ')} 
            <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeMapping(${index})">❌</button>`;
                document.getElementById('mappingList').appendChild(li);

                selected.forEach(cb => cb.checked = false);
            }

            function removeMapping(index) {
                mappings[index] = null;
                document.querySelector(`li[data-index='${index}']`).remove();
                document.getElementById('mappingsInput').value = JSON.stringify(mappings.filter(m => m));
            }


            postSelect.addEventListener('change', () => {
                const step_number = postSelect.value;
                const doc_id = <?= $document_id ?>;

                document.getElementById('board_checkboxes').innerHTML = '<p>Chargement...</p>';

                fetch(`get_available_boards.php?step_number=${step_number}&document_id=${doc_id}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('board_checkboxes').innerHTML = html;
                    })
                    .catch(err => {
                        document.getElementById('board_checkboxes').innerHTML = '<p class="text-danger">Erreur de chargement.</p>';
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



            document.addEventListener('DOMContentLoaded', filterPostsByIlot);
        </script>
    </body>

    </html>