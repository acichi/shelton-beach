<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$rows = [];
// Count actual reservation statuses from the reservations table
$sql = "
SELECT LOWER(COALESCE(status, 'unknown')) AS status, COUNT(*) AS count
FROM reservations
GROUP BY LOWER(COALESCE(status, 'unknown'))
ORDER BY count DESC";

if ($res = $conn->query($sql)) {
	while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

echo json_encode($rows);
?>


