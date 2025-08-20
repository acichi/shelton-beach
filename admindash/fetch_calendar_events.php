<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$events = [];
$sql = "SELECT id, reservee, facility_name, date_start, date_end, status, amount
        FROM reservations
        ORDER BY date_start DESC, id DESC";

if ($res = $conn->query($sql)) {
	while ($r = $res->fetch_assoc()) {
		$events[] = [
			'id' => (int)$r['id'],
			'title' => ($r['facility_name'] ?: 'Reservation') . ' - ' . ($r['reservee'] ?: ''),
			'start' => $r['date_start'],
			'end' => $r['date_end'],
			'extendedProps' => [
				'amount' => $r['amount'],
				'status' => $r['status'],
				'facility' => $r['facility_name'],
				'reservee' => $r['reservee']
			]
		];
	}
}

echo json_encode($events);
?>


