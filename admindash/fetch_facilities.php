<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$rows = [];
if ($res = $conn->query("SELECT id, name, details, price, status, image, date_added FROM facility ORDER BY date_added DESC, id DESC")) {
	while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

echo json_encode($rows);
?>


