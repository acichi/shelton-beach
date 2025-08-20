<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$rows = [];
$sql = "SELECT facility_name AS name, COUNT(*) AS bookings
        FROM receipt
        WHERE COALESCE(facility_name, '') <> '' AND balance = 0
        GROUP BY facility_name
        ORDER BY bookings DESC, facility_name ASC
        LIMIT 10";

if ($res = $conn->query($sql)) {
	while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

echo json_encode($rows);
?>


