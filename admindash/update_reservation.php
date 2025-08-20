<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	echo json_encode(['success' => false, 'message' => 'Method not allowed']);
	exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid id']); exit; }

$reservee = isset($_POST['reservee']) ? trim($_POST['reservee']) : null;
$facility_name = isset($_POST['facility_name']) ? trim($_POST['facility_name']) : null;
$status = isset($_POST['status']) ? trim($_POST['status']) : null;
if ($status !== null) {
    switch (strtolower($status)) {
        case 'pending': $status = 'Pending'; break;
        case 'confirmed': $status = 'Confirmed'; break;
        case 'cancelled':
        case 'canceled': $status = 'Cancelled'; break;
        default: $status = 'Pending';
    }
}
$date_start = isset($_POST['date_start']) ? trim($_POST['date_start']) : null;
$date_end = isset($_POST['date_end']) ? trim($_POST['date_end']) : null;
$payment_type = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : null;
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : null;

$fields = [];
if ($reservee !== null) $fields['reservee'] = $reservee;
if ($facility_name !== null) $fields['facility_name'] = $facility_name;
if ($status !== null) $fields['status'] = $status;
if ($date_start !== null) $fields['date_start'] = $date_start;
if ($date_end !== null) $fields['date_end'] = $date_end;
if ($payment_type !== null) $fields['payment_type'] = $payment_type;
if ($amount !== null) $fields['amount'] = $amount;

if (!$fields) { echo json_encode(['success' => false, 'message' => 'Nothing to update']); exit; }

// Build dynamic prepared statement
$columns = array_keys($fields);
$placeholders = [];
$types = '';
$values = [];
foreach ($columns as $col) {
	$placeholders[] = "$col = ?";
	$val = $fields[$col];
	if (is_float($val) || is_int($val)) { $types .= 'd'; } else { $types .= 's'; }
	$values[] = $val;
}

$sql = 'UPDATE reservations SET ' . implode(', ', $placeholders) . ' WHERE id = ?';
$types .= 'i';
$values[] = $id;

$stmt = $conn->prepare($sql);
if ($stmt) {
	$stmt->bind_param($types, ...$values);
	$ok = $stmt->execute();
	$stmt->close();
	echo json_encode(['success' => (bool)$ok]);
} else {
	echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>


