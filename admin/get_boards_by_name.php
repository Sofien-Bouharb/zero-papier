<?php
require_once '../includes/db.php';

header('Content-Type: application/json');

$board_name = $_GET['board_name'] ?? '';
$board_index_id = $_GET['board_index_id'] ?? '';

if (empty($board_name)) {
    echo json_encode(['error' => 'Nom de carte manquant.']);
    exit();
}

try {
    $sql = "SELECT board_index_id, board_name
            FROM documents_search.boards
            WHERE board_name = :board_name";

    $params = [':board_name' => $board_name];

    if (!empty($board_index_id)) {
        $sql .= " AND board_index_id::TEXT LIKE :board_index_id";
        $params[':board_index_id'] = "%$board_index_id%";
    }

    $sql .= " ORDER BY board_index_id LIMIT 1000"; // Limit to 1000 to avoid overload

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($boards);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur lors de la récupération des cartes.']);
}
