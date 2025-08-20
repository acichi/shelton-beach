<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

header('Content-Type: application/json');

$tx = isset($_GET['id']) ? trim($_GET['id']) : '';
$fullname = trim($_SESSION['user']['fullname'] ?? '');

if ($tx === '' || $fullname === '') {
	echo json_encode([ 'success' => false, 'message' => 'Invalid request' ]);
	exit;
}

try {
	// Use case- and whitespace-insensitive match to avoid small mismatches (base table)
	$stmt = $conn->prepare("SELECT receipt_trans_code AS transaction_id, COALESCE(receipt_reservee,'') AS reservee, COALESCE(receipt_facility,'') AS facility_name, COALESCE(receipt_date_booked,'') AS date_booked, COALESCE(receipt_date_checkin,'') AS date_checkin, COALESCE(receipt_date_checkout,'') AS date_checkout, COALESCE(receipt_payment_type,'Cash') AS payment_type, COALESCE(receipt_amount_paid,0) AS amount_paid, COALESCE(receipt_balance,0) AS balance FROM receipt_tbl WHERE receipt_trans_code = ? AND LOWER(TRIM(receipt_reservee)) = LOWER(TRIM(?)) LIMIT 1");
	if (!$stmt) { echo json_encode([ 'success' => false, 'message' => 'Server error' ]); exit; }
	$stmt->bind_param('ss', $tx, $fullname);
	$stmt->execute();
	$res = $stmt->get_result();
	$row = $res->fetch_assoc();
	$stmt->close();

	// Fallback: if not found, try by id only then verify owner in PHP
	if (!$row) {
		if ($stmt2 = $conn->prepare("SELECT receipt_trans_code AS transaction_id, COALESCE(receipt_reservee,'') AS reservee, COALESCE(receipt_facility,'') AS facility_name, COALESCE(receipt_date_booked,'') AS date_booked, COALESCE(receipt_date_checkin,'') AS date_checkin, COALESCE(receipt_date_checkout,'') AS date_checkout, COALESCE(receipt_payment_type,'Cash') AS payment_type, COALESCE(receipt_amount_paid,0) AS amount_paid, COALESCE(receipt_balance,0) AS balance FROM receipt_tbl WHERE receipt_trans_code = ? LIMIT 1")) {
			$stmt2->bind_param('s', $tx);
			$stmt2->execute();
			$r2 = $stmt2->get_result();
			$tmp = $r2->fetch_assoc();
			$stmt2->close();
			if ($tmp) {
				$owner = trim((string)$tmp['reservee']);
				if (strcasecmp($owner, $fullname) === 0) { $row = $tmp; }
			}
		}
	}

	$conn->close();

	if (!$row) {
		echo json_encode([ 'success' => false, 'message' => 'Reservation not found' ]);
		exit;
	}

	// Normalize output
	$out = [
		'success' => true,
		'transaction_id' => (string)$row['transaction_id'],
		'reservee' => (string)$row['reservee'],
		'facility_name' => (string)$row['facility_name'],
		'date_booked' => (string)$row['date_booked'],
		'date_checkin' => (string)$row['date_checkin'],
		'date_checkout' => (string)$row['date_checkout'],
		'payment_type' => (string)$row['payment_type'],
		'amount_paid' => (float)$row['amount_paid'],
		'balance' => isset($row['balance']) ? (float)$row['balance'] : 0.0
	];

	echo json_encode($out);
} catch (Throwable $e) {
	echo json_encode([ 'success' => false, 'message' => 'Server error' ]);
}
 