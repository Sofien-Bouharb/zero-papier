<?php
require_once '../includes/auth_check.php';
require_once '../includes/db.php';

$sql = "SELECT document_id, document_name FROM documents_search.documents ORDER BY document_name";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($documents);
