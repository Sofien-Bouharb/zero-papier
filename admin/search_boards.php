<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$_SESSION['LAST_ACTIVITY'] = time();


$q = $_GET['q'] ?? '';
$terms = preg_split('/\s+/', trim($q));
$conditions = [];
$params = [];

foreach ($terms as $i => $word) {
    $param = ":term$i";
    $conditions[] = "(board_name ILIKE $param OR designation ILIKE $param OR repere_dm ILIKE $param OR ref_cie_actia ILIKE $param OR ref_pcb ILIKE $param OR clicher_pcb ILIKE $param OR CAST(board_index_id AS TEXT) ILIKE $param)";
    $params[$param] = '%' . $word . '%';
}

$sql = "SELECT * FROM documents_search.boards";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY board_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$boards = $stmt->fetchAll();

foreach ($boards as $b): ?>
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
<?php endforeach;

if (count($boards) === 0): ?>
    <tr>
        <td colspan="8" class="text-center ">Aucun résultat trouvé.</td>
    </tr>
<?php endif; ?>
<script>