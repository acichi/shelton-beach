<?php
// Ensure clean JSON output (no HTML, no notices)
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
@ini_set('display_errors', '0');
// Start our own buffer early to fully control output
if (!ob_get_level()) { ob_start(); }
session_start();
require '../properties/connection.php';

header('Content-Type: application/json; charset=utf-8');

function json_out($arr) {
	// Drop any existing buffered output
	while (ob_get_level()) { @ob_end_clean(); }
	header_remove('X-Powered-By');
	echo json_encode($arr);
	exit;
}

// Robust handlers to ensure JSON on unexpected errors
set_error_handler(function($severity, $message, $file, $line){
    if (!(error_reporting() & $severity)) return false;
    error_log("verify_otp_fixed.php PHP error: $message in $file:$line");
    while (ob_get_level()) { @ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Server error']);
    exit;
});
set_exception_handler(function($ex){
    error_log('verify_otp_fixed.php exception: ' . $ex->getMessage());
    while (ob_get_level()) { @ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Server exception']);
    exit;
});
register_shutdown_function(function(){
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])){
        error_log('verify_otp_fixed.php fatal: ' . $e['message']);
        while (ob_get_level()) { @ob_end_clean(); }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'message'=>'Server fatal error']);
    }
});

// Check if OTP and registration data exist
if (!isset($_SESSION['otp'], $_SESSION['registration'])) {
    json_out(['success' => false, 'message' => 'Session expired']);
}

// Check OTP
$input_otp = $_POST['otp'] ?? '';
if ($input_otp != $_SESSION['otp']) {
    json_out(['success' => false, 'message' => 'Invalid OTP']);
}

// Extract registration data
$data     = $_SESSION['registration'];
$name     = (string)($data['name'] ?? '');
$email    = (string)($data['email'] ?? '');
$username = (string)($data['user'] ?? '');
$password = (string)($data['password'] ?? '');
$number   = (string)($data['mobile'] ?? '');
$address  = (string)($data['address'] ?? 'Not provided');
$gender   = (string)($data['gender'] ?? '');
$g = strtolower(trim($gender));
if ($g === 'male' || $g === 'm') { $gender = 'Male'; }
elseif ($g === 'female' || $g === 'f') { $gender = 'Female'; }
else { $gender = 'Other'; }
$role     = 'customer';
$now      = date('Y-m-d H:i:s');

// Ensure customer address column exists (first run safety)
$colCheck = $conn->query("SHOW COLUMNS FROM customer_tbl LIKE 'customer_address'");
if ($colCheck && $colCheck->num_rows === 0) {
    @$conn->query("ALTER TABLE customer_tbl ADD COLUMN customer_address varchar(255) DEFAULT NULL");
}
if ($colCheck) { $colCheck->close(); }

// Check if username/email/phone already exists against base tables (admins + customers)
$check = $conn->prepare(
    "SELECT 1 FROM (\n" .
    "  SELECT customer_email AS email, customer_user AS username, customer_number AS `number` FROM customer_tbl\n" .
    "  UNION ALL\n" .
    "  SELECT admin_email AS email, admin_user AS username, admin_number AS `number` FROM admin_tbl\n" .
    ") u WHERE u.email = ? OR u.username = ? OR u.`number` = ? LIMIT 1"
);
if (!$check) { json_out(['success' => false, 'message' => 'Server error (CHK). Please try again.']); }
$check->bind_param("sss", $email, $username, $number);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->close();
    json_out(['success' => false, 'message' => 'Username, email, or phone number already exists']);
}

// Insert new customer directly into base table to avoid non-insertable view issues
// Store full name in customer_fname; leave last name null if not split
$stmt = $conn->prepare("INSERT INTO customer_tbl (customer_fname, customer_email, customer_number, customer_user, customer_pass, customer_gender, customer_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) { json_out(['success' => false, 'message' => 'Server error (INS). Please try again.']); }
$stmt->bind_param("sssssss", $name, $email, $number, $username, $password, $gender, $address);

if ($stmt->execute()) {
    // Mark OTP as verified in base table
    $otpUpdate = $conn->prepare("UPDATE otp_tbl SET status = 'verified', date_updated = NOW() WHERE otp_code = ? AND customer_email = ? ORDER BY otp_id DESC LIMIT 1");
    if ($otpUpdate) {
        $otpString = (string)$_SESSION['otp'];
        $otpUpdate->bind_param("ss", $otpString, $email);
        $otpUpdate->execute();
        $otpUpdate->close();
    }

    $newUserId = $stmt->insert_id;

    // Set session for auto-login (support both formats used across the app)
    $_SESSION['user'] = [
        'id'       => $newUserId,
        'fullname' => $name,
        'email'    => $email,
        'username' => $username,
        'role'     => $role,
        'gender'   => $gender,
        'address'  => $address,
    ];
    $_SESSION['fullname']  = $name;
    $_SESSION['user_id']   = $newUserId;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email']= $email;
    $_SESSION['user_role'] = $role;

    // Clear temporary registration data
    unset($_SESSION['otp'], $_SESSION['registration']);

    // Provide redirect target
    $redirect = ($role === 'admin') ? '../admindash/admindash.php' : '../customerdash/cusdash.php';
    json_out(['success' => true, 'message' => 'Registration successful', 'redirect' => $redirect]);
} else {
    error_log('verify_otp_fixed.php insert error: ' . $stmt->error);
    // Gracefully handle duplicate key errors from DB unique constraints
    if (strpos(strtolower($stmt->error), 'duplicate') !== false || strpos($stmt->error, '1062') !== false) {
        json_out(['success' => false, 'message' => 'Username, email, or phone number already exists']);
    }
    json_out(['success' => false, 'message' => 'Registration failed']);
}

$stmt->close();
$conn->close();
