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

if (!isset($_GET['step_number'])) {
    redirect_with_error("Aucun poste spécifié.");
}

$step_number = (int) $_GET['step_number'];

// Fetch ilots for dropdown
$ilots = $pdo->query("SELECT ilot_id, ilot_name FROM documents_search.ilot ORDER BY ilot_name")->fetchAll();

// Fetch current post data
$stmt = $pdo->prepare("SELECT * FROM documents_search.workers WHERE step_number = :step");
$stmt->execute(['step' => $step_number]);
$post = $stmt->fetch();

if (!$post) {

    redirect_with_error("Poste introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostname = trim($_POST['hostname'] ?? '');
    $ip_address = trim($_POST['ip_address'] ?? '');
    $ilot_id = (int) ($_POST['ilot_id'] ?? 0);

    if (!$hostname || !$ip_address || !$ilot_id) {
        $error = "Tous les champs sont obligatoires.";
        redirect_with_error($error);
    } elseif (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $error = "L'adresse IP n'est pas valide.";
        redirect_with_error($error);
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
            redirect_with_error($error);
        } else {
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

            header("Location: dashboard.php?view=posts&success=1");
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
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2>Modifier le poste #<?= htmlspecialchars($step_number) ?></h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

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