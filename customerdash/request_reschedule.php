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
    foreach (['transaction_id','new_checkin','new_checkout','reason','csrf_token'] as $k) { 
        if (isset($body[$k]) && !isset($_POST[$k])) { $_POST[$k] = $body[$k]; } 
    }
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']);
    exit;
}

$tx = trim($_POST['transaction_id'] ?? '');
$newCheckin = trim($_POST['new_checkin'] ?? '');
$newCheckout = trim($_POST['new_checkout'] ?? '');
$reason = trim($_POST['reason'] ?? '');
$fullname = $_SESSION['user']['fullname'] ?? '';

if ($tx === '' || $newCheckin === '' || $newCheckout === '' || $reason === '' || $fullname === '') { 
    echo json_encode(['success'=>false,'message'=>'Missing required fields']); 
    exit; 
}

// Validate dates
$checkinDate = strtotime($newCheckin);
$checkoutDate = strtotime($newCheckout);
$today = strtotime(date('Y-m-d'));

if (!$checkinDate || !$checkoutDate) {
    echo json_encode(['success'=>false,'message'=>'Invalid date format']);
    exit;
}

if ($checkinDate < $today) {
    echo json_encode(['success'=>false,'message'=>'Check-in date cannot be in the past']);
    exit;
}

if ($checkinDate >= $checkoutDate) {
    echo json_encode(['success'=>false,'message'=>'Check-out date must be after check-in date']);
    exit;
}

// Verify the transaction belongs to the user and is upcoming
$stmt = $conn->prepare("SELECT receipt_id AS id, receipt_facility AS facility_name FROM receipt_tbl WHERE receipt_trans_code = ? AND receipt_reservee = ? AND receipt_date_checkin > NOW() LIMIT 1");
$stmt->bind_param('ss', $tx, $fullname);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) { 
    echo json_encode(['success'=>false,'message'=>'Not allowed or reservation not found']); 
    exit; 
}
$stmt->bind_result($receiptId, $facilityName);
$stmt->fetch();
$stmt->close();

// Check if new dates conflict with existing confirmed reservations
$conflict = 0;
$confStmt = $conn->prepare("SELECT COUNT(*) FROM reservation_tbl WHERE reservation_facility = ? AND reservation_status = 'Confirmed' AND NOT (reservation_date_end <= ? OR reservation_date_start >= ?)");
if ($confStmt) {
    $startStr = date('Y-m-d H:i:s', $checkinDate);
    $endStr = date('Y-m-d H:i:s', $checkoutDate);
    $confStmt->bind_param('sss', $facilityName, $startStr, $endStr);
    $confStmt->execute();
    $confStmt->bind_result($conflict);
    $confStmt->fetch();
    $confStmt->close();
}
if ($conflict > 0) {
    echo json_encode(['success'=>false,'message'=>'Selected dates conflict with existing confirmed reservations']);
    exit;
}

// Create reschedule_requests table if it doesn't exist
@$conn->query("CREATE TABLE IF NOT EXISTS reschedule_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100),
    reservee VARCHAR(255),
    facility_name VARCHAR(255),
    current_checkin DATE,
    current_checkout DATE,
    new_checkin DATE,
    new_checkout DATE,
    reason TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Check for duplicate pending requests
$dup = $conn->prepare("SELECT id FROM reschedule_requests WHERE transaction_id=? AND reservee=? AND status='pending' LIMIT 1");
$dup->bind_param('ss', $tx, $fullname);
$dup->execute();
$dup->store_result();
if ($dup->num_rows > 0) { 
    echo json_encode(['success'=>true,'message'=>'Reschedule request already pending']); 
    exit; 
}
$dup->close();

// Get current dates from receipt
$currentCheckin = '';
$currentCheckout = '';
$dateStmt = $conn->prepare("SELECT receipt_date_checkin, receipt_date_checkout FROM receipt_tbl WHERE receipt_trans_code = ? LIMIT 1");
$dateStmt->bind_param('s', $tx);
$dateStmt->execute();
$dateStmt->bind_result($currentCheckin, $currentCheckout);
$dateStmt->fetch();
$dateStmt->close();

// Insert reschedule request
$ins = $conn->prepare("INSERT INTO reschedule_requests (transaction_id, reservee, facility_name, current_checkin, current_checkout, new_checkin, new_checkout, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$ins->bind_param('ssssssss', $tx, $fullname, $facilityName, $currentCheckin, $currentCheckout, $newCheckin, $newCheckout, $reason);
$ok = $ins->execute();
$ins->close();
$conn->close();

echo json_encode(['success'=>$ok,'message'=>$ok?'Reschedule request submitted':'Failed to submit request']);
?>
