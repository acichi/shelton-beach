<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

header('Content-Type: application/json');

$count = 0;
// Use a small epsilon to ignore floating residue
if ($res = $conn->query("SELECT COUNT(*) AS c FROM receipt WHERE balance > 0.01")) {
    $row = $res->fetch_assoc();
    $count = (int)($row['c'] ?? 0);
}
echo json_encode(['success' => true, 'count' => $count]);
?>


