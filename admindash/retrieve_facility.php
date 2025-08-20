<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$facilities = [];
if ($result = $conn->query("SELECT id, name, pin_x, pin_y, details, status, price, image, date_added, date_updated FROM facility")) {
	while ($row = $result->fetch_assoc()) {
		$facilities[] = $row;
	}
}
echo json_encode($facilities);
?>


