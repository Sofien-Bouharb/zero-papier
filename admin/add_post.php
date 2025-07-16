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
$ilot_stmt = $pdo->query("SELECT ilot_id, ilot_name FROM documents_search.ilot ORDER BY ilot_name");
$ilots = $ilot_stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostname = trim($_POST['hostname'] ?? '');
    $ip = trim($_POST['ip_address'] ?? '');
    $ilot_id = $_POST['ilot_id'] ?? null;

    if (!$hostname || !$ip) {
        $error = "Tous les champs sont obligatoires.";
        redirect_with_error($error);
    } elseif (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $error = "Adresse IP invalide.";
        redirect_with_error($error);
    } else {
        // Vérifier unicité du hostname et de l'IP
        $check = $pdo->prepare("
SELECT COUNT(*) FROM documents_search.workers
WHERE hostname = :hostname OR ip_address = :ip
");
        $check->execute(['hostname' => $hostname, 'ip' => $ip]);

        if ($check->fetchColumn() > 0) {
            $error = "Ce nom de poste ou cette adresse IP existe déjà.";
            redirect_with_error($error);
        } else {
            $stmt = $pdo->prepare("
INSERT INTO documents_search.workers (hostname, ip_address, ilot_id)
VALUES (:hostname, :ip, :ilot_id)
");
            $stmt->execute([
                'hostname' => $hostname,
                'ip' => $ip,
                'ilot_id' => $ilot_id
            ]);
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
    <title>Ajouter un poste</title>
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

<body class="p-4">

    <div class="container">
        <h2 class="mb-4">📤 Ajouter un nouveau poste</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="hostname" class="form-label">Nom du poste (hostname)</label>
                <input type="text" name="hostname" id="hostname" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="ip_address" class="form-label">Adresse IP</label>
                <input type="text" name="ip_address" id="ip_address" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="ilot_id" class="form-label">Ilot</label>
                <select name="ilot_id" id="ilot_id" class="form-select" required>
                    <option value="" disabled selected>-- Choisir un îlot --</option>
                    <?php foreach ($ilots as $ilot): ?>
                        <option value="<?= $ilot['ilot_id'] ?>"><?= htmlspecialchars($ilot['ilot_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-success">Ajouter</button>
            <a href="dashboard.php?view=posts" class="btn ms-2" style="background-color: #747e87; color:#000;">Annuler</a>
        </form>
    </div>

</body>

</html>