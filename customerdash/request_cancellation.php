<?php
header('Content-Type: application/json');
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') { echo json_encode(['success'=>false,'message'=>'Invalid request']); exit; }

// Support JSON or form
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?: [];
    foreach (['transaction_id','csrf_token'] as $k) { if (isset($body[$k]) && !isset($_POST[$k])) { $_POST[$k] = $body[$k]; } }
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']);
    exit;
}

$tx = trim($_POST['transaction_id'] ?? '');
$fullname = $_SESSION['user']['fullname'] ?? '';
if ($tx === '' || $fullname === '') { echo json_encode(['success'=>false,'message'=>'Invalid request']); exit; }

// Optional: ensure the transaction belongs to the user and is upcoming
$stmt = $conn->prepare("SELECT receipt_id AS id FROM receipt_tbl WHERE receipt_trans_code = ? AND receipt_reservee = ? AND receipt_date_checkin > NOW() LIMIT 1");
$stmt->bind_param('ss', $tx, $fullname);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) { echo json_encode(['success'=>false,'message'=>'Not allowed']); exit; }
$stmt->close();

// Record a cancellation request note in a simple table or fallback to updating a flag
@$conn->query("CREATE TABLE IF NOT EXISTS cancellation_requests (id INT AUTO_INCREMENT PRIMARY KEY, transaction_id VARCHAR(100), reservee VARCHAR(255), status VARCHAR(50) DEFAULT 'pending', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
// Optional: prevent duplicate requests
$dup = $conn->prepare("SELECT id FROM cancellation_requests WHERE transaction_id=? AND reservee=? AND status='pending' LIMIT 1");
$dup->bind_param('ss', $tx, $fullname);
$dup->execute();
$dup->store_result();
if ($dup->num_rows > 0) { echo json_encode(['success'=>true,'message'=>'Request already pending']); exit; }
$dup->close();

$ins = $conn->prepare("INSERT INTO cancellation_requests (transaction_id, reservee) VALUES (?, ?)");
$ins->bind_param('ss', $tx, $fullname);
$ok = $ins->execute();
$ins->close();
$conn->close();

echo json_encode(['success'=>$ok,'message'=>$ok?'Request submitted':'Failed']);


