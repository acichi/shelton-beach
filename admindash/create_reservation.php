<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

$reservee = trim($_POST['reservee'] ?? '');
$facility_name = trim($_POST['facility_name'] ?? '');
$status = trim($_POST['status'] ?? 'Pending');
// Normalize to enum values: Pending, Confirmed, Cancelled
switch (strtolower($status)) {
    case 'pending': $status = 'Pending'; break;
    case 'confirmed': $status = 'Confirmed'; break;
    case 'cancelled':
    case 'canceled': $status = 'Cancelled'; break;
    default: $status = 'Pending';
}
$date_booked = date('Y-m-d H:i:s');
$date_start = trim($_POST['date_start'] ?? '');
$date_end = trim($_POST['date_end'] ?? '');
$payment_type = trim($_POST['payment_type'] ?? 'Cash');
$amount = floatval($_POST['amount'] ?? 0);

if ($reservee === '' || $facility_name === '' || $date_start === '' || $date_end === '') {
	echo json_encode(['success' => false, 'message' => 'Missing required fields']);
	exit;
}

$stmt = $conn->prepare("INSERT INTO reservations (reservee, facility_name, status, date_booked, date_start, date_end, payment_type, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
	echo json_encode(['success' => false, 'message' => 'Prepare failed']);
	exit;
}
$stmt->bind_param('sssssssd', $reservee, $facility_name, $status, $date_booked, $date_start, $date_end, $payment_type, $amount);

if ($stmt->execute()) {
	echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
} else {
	echo json_encode(['success' => false, 'message' => 'Insert failed']);
}
?>



