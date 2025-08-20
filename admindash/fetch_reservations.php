<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$rows = [];
$where = [];

// Optional filters: status, start (date_booked), end (date_booked)
if (!empty($_GET['status'])) {
	$status = $conn->real_escape_string($_GET['status']);
	$where[] = "LOWER(status) = LOWER('$status')";
}

if (!empty($_GET['start'])) {
	$start = $conn->real_escape_string($_GET['start']); // YYYY-MM-DD
	$where[] = "date_booked >= '$start'";
}
if (!empty($_GET['end'])) {
	$end = $conn->real_escape_string($_GET['end']);
	$where[] = "date_booked <= '$end'";
}
if (!empty($_GET['facility'])) {
	$facility = $conn->real_escape_string($_GET['facility']);
	$where[] = "facility_name LIKE '%$facility%'";
}

$sql = "SELECT id, reservee, facility_name, status, date_booked, date_start, date_end, payment_type, amount
		FROM reservations";
if ($where) {
	$sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY date_booked DESC, id DESC';

if ($res = $conn->query($sql)) {
	while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

echo json_encode($rows);
?>


