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
//Check if step_number is provided
if (!isset($_GET['step_number'])) {
    redirect_with_error("Aucun poste spécifié.");
}
//Get step_number from query parameters
$step_number = (int) $_GET['step_number'];

// Fetch ilots for dropdown
$ilots = $pdo->query("SELECT ilot_id, ilot_name FROM documents_search.ilot ORDER BY ilot_name")->fetchAll();

// Fetch current post data
$stmt = $pdo->prepare("SELECT * FROM documents_search.workers WHERE step_number = :step");
$stmt->execute(['step' => $step_number]);
$post = $stmt->fetch();
//Post not found
if (!$post) {

    redirect_with_error("Poste introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostname = trim($_POST['hostname'] ?? '');
    $ip_address = trim($_POST['ip_address'] ?? '');
    $ilot_id = (int) ($_POST['ilot_id'] ?? 0);
    // Validate inputs
    if (!$hostname || !$ip_address || !$ilot_id) {
        $error = "Tous les champs sont obligatoires.";
        redirect_with_error($error, 'edit_post.php?step_number=' . $step_number);
    } elseif (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $error = "L'adresse IP n'est pas valide.";
        redirect_with_error($error, 'edit_post.php?step_number=' . $step_number);
    } else {

        // Check for uniqueness of hostname and ip_address (excluding current post)
        $conflict = $pdo->prepare("
      SELECT COUNT(*) FROM documents_search.workers
      WHERE (hostname = :host OR ip_address = :ip)
        AND step_number != :step
    ");
        $conflict->execute([
            'host' => $hostname,
            'ip' => $ip_address,
            'step' => $step_number
        ]);

        if ($conflict->fetchColumn() > 0) {
            $error = "Le nom d'hôte ou l'adresse IP est déjà utilisé(e) par un autre poste.";
            redirect_with_error($error, 'edit_post.php?step_number=' . $step_number);
        } else {
            // Update the post
            $update = $pdo->prepare("
        UPDATE documents_search.workers
        SET hostname = :host, ip_address = :ip, ilot_id = :ilot
        WHERE step_number = :step
      ");
            $update->execute([
                'host' => $hostname,
                'ip' => $ip_address,
                'ilot' => $ilot_id,
                'step' => $step_number
            ]);
            $_SESSION['success_message'] = "Poste modifié avec succès.";
            header("Location: dashboard.php?view=posts");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Modifier un poste</title>
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

<body>
    <!-- Navigation bar -->
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
    <!-- Form -->
    <div class="container mt-5 p-3">
        <h2 class="mt-3">Modifier le poste #<?= htmlspecialchars($step_number) ?></h2>


        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label class="form-label">Nom d'hôte</label>
                <input type="text" name="hostname" class="form-control" value="<?= htmlspecialchars($post['hostname']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Adresse IP</label>
                <input type="text" name="ip_address" class="form-control" value="<?= htmlspecialchars($post['ip_address']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Îlot</label>
                <select name="ilot_id" class="form-select" required>
                    <?php foreach ($ilots as $ilot): ?>
                        <option value="<?= $ilot['ilot_id'] ?>" <?= $ilot['ilot_id'] == $post['ilot_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ilot['ilot_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-success">💾 Enregistrer</button>
            <a href="dashboard.php?view=posts" class="btn ms-2" style="background-color:#747e87; color:#000;">Retour</a>
        </form>
    </div>
</body>

</html>