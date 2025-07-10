<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_GET['step_number'])) {
    die("Aucun poste spécifié.");
}

$step_number = (int) $_GET['step_number'];

// Fetch ilots for dropdown
$ilots = $pdo->query("SELECT ilot_id, ilot_name FROM documents_search.ilot ORDER BY ilot_name")->fetchAll();

// Fetch current post data
$stmt = $pdo->prepare("SELECT * FROM documents_search.workers WHERE step_number = :step");
$stmt->execute(['step' => $step_number]);
$post = $stmt->fetch();

if (!$post) {
    die("Poste introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hostname = trim($_POST['hostname'] ?? '');
    $ip_address = trim($_POST['ip_address'] ?? '');
    $ilot_id = (int) ($_POST['ilot_id'] ?? 0);

    if (!$hostname || !$ip_address || !$ilot_id) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        $error = "L'adresse IP n'est pas valide.";
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
</head>

<body class="bg-dark text-light">
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

            <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
            <a href="dashboard.php?view=posts" class="btn btn-secondary ms-2">Retour</a>
        </form>
    </div>
</body>

</html>