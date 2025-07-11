<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';
$_SESSION['LAST_ACTIVITY'] = time(); // Met à jour l'heure de la dernière activité
$q = $_GET['q'] ?? '';
$terms = preg_split('/\s+/', trim($q));
$conditions = [];
$params = [];

foreach ($terms as $i => $word) {
    $param = ":term$i";
    $conditions[] = "(w.hostname ILIKE $param OR w.ip_address ILIKE $param OR i.ilot_name ILIKE $param)";
    $params[$param] = '%' . $word . '%';
}

$sql = "
    SELECT w.step_number, w.hostname, w.ip_address, i.ilot_name
    FROM documents_search.workers w
    LEFT JOIN documents_search.ilot i ON w.ilot_id = i.ilot_id
";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY w.hostname";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

foreach ($posts as $w): ?>
    <tr>
        <td><?= htmlspecialchars($w['hostname']) ?></td>
        <td><?= htmlspecialchars($w['ip_address']) ?></td>
        <td><?= htmlspecialchars($w['ilot_name'] ?? 'Non défini') ?></td>
        <td style="text-align:center;">
            <a href="edit_post.php?step_number=<?= $w['step_number'] ?>" class="text-warning me-3" title="Modifier">✏️</a>
            <a href="delete_post.php?id=<?= $w['step_number'] ?>" class="text-danger" title="Supprimer" onclick="return confirm('Supprimer ce poste ?');">🗑️</a>
        </td>
    </tr>
<?php endforeach;

if (count($posts) === 0): ?>
    <tr>
        <td colspan="4" class="text-center ">Aucun poste trouvé.</td>
    </tr>
<?php endif; ?>