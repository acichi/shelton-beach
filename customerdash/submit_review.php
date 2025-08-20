<?php
header('Content-Type: application/json');
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

function resp($ok, $msg = ''){ echo json_encode(['success'=>$ok, 'message'=>$msg]); exit; }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') { resp(false, 'Invalid request'); }

// CSRF check

// Some clients may post JSON; support both
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
	$raw = file_get_contents('php://input');
	$body = json_decode($raw, true) ?: [];
	foreach (['fullname','facility_name','feedback','rate','csrf_token'] as $k) {
		if (isset($body[$k]) && !isset($_POST[$k])) { $_POST[$k] = $body[$k]; }
	}
}

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
	resp(false, 'Invalid CSRF token');
}

$fullname = trim($_POST['fullname'] ?? '');
$facility = trim($_POST['facility_name'] ?? '');
$feedback = trim($_POST['feedback'] ?? '');
$rate = (int)($_POST['rate'] ?? 0);

// Enforce fullname from session to prevent impersonation
$sessionFullname = trim($_SESSION['user']['fullname'] ?? '');
if ($sessionFullname !== '' && strcasecmp($fullname, $sessionFullname) !== 0) {
	$fullname = $sessionFullname;
}

if ($fullname === '' || $facility === '' || $feedback === '' || $rate < 1 || $rate > 5) {
	resp(false, 'Invalid input');
}

// Ensure the user has used this facility (has a past receipt)
$used = 0;
if ($chk = $conn->prepare('SELECT COUNT(*) FROM receipt_tbl WHERE receipt_reservee = ? AND receipt_facility = ? AND receipt_date_checkout <= CURRENT_DATE()')) {
	$chk->bind_param('ss', $fullname, $facility);
	$chk->execute();
	$chk->bind_result($used);
	$chk->fetch();
	$chk->close();
}
if ((int)$used === 0) {
	resp(false, 'You can only review facilities you have used.');
}

// Enforce one review per facility per user
$exists = 0;
if ($ex = $conn->prepare('SELECT COUNT(*) FROM feedback_tbl WHERE feedback_name = ? AND feedback_facility = ?')) {
	$ex->bind_param('ss', $fullname, $facility);
	$ex->execute();
	$ex->bind_result($exists);
	$ex->fetch();
	$ex->close();
}
if ((int)$exists > 0) {
	resp(false, 'You have already reviewed this facility.');
}

$stmt = $conn->prepare("INSERT INTO feedback_tbl (feedback_name, feedback_facility, feedback_message, feedback_rate, feedback_timestamp, feedback_status) VALUES (?, ?, ?, ?, NOW(), 'show')");
if (!$stmt) { resp(false, 'Server error'); }
$stmt->bind_param('sssi', $fullname, $facility, $feedback, $rate);
$ok = $stmt->execute();
$stmt->close();
$conn->close();

resp($ok, $ok ? 'Saved' : 'Failed');


