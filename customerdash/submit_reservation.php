<?php
header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

$respond = function($ok, $msg, $extra = []){
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $extra));
    exit;
};

register_shutdown_function(function() use ($respond){
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        $respond(false, 'Server error');
    }
});

set_error_handler(function($severity, $message, $file, $line) use ($respond){
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
require_once __DIR__ . '/../properties/availability.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $respond(false, 'Invalid request');
}

// Accept x-www-form-urlencoded or JSON bodies
if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?: [];
    foreach (['reservee','facility_name','date_start','date_end','payment_type','amount','date_booked','agree_booking_terms','csrf_token','payment_plan','down_percent'] as $k) {
        if (isset($body[$k]) && !isset($_POST[$k])) { $_POST[$k] = $body[$k]; }
    }
}

// CSRF validation
$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    $respond(false, 'Invalid CSRF token');
}

$sessionFullname = trim($_SESSION['user']['fullname'] ?? '');
$reservee = trim($_POST['reservee'] ?? $sessionFullname);
$facility = trim($_POST['facility_name'] ?? '');
$dateStart = trim($_POST['date_start'] ?? '');
$dateEnd = trim($_POST['date_end'] ?? '');
$paymentType = trim($_POST['payment_type'] ?? 'Cash');
$paymentPlan = trim($_POST['payment_plan'] ?? 'full');
$downPercent = isset($_POST['down_percent']) ? (int)$_POST['down_percent'] : null;
$bookingType = trim($_POST['booking_type'] ?? 'day'); // Default to day-use
$amount = (float)($_POST['amount'] ?? 0);
$dateBooked = trim($_POST['date_booked'] ?? date('Y-m-d H:i:s'));
$agreed = filter_var($_POST['agree_booking_terms'] ?? false, FILTER_VALIDATE_BOOLEAN);

// Enforce reservee from session
if ($sessionFullname !== '' && strcasecmp($reservee, $sessionFullname) !== 0) {
    $reservee = $sessionFullname;
}

if (!$agreed) { $respond(false, 'You must accept the booking terms'); }
if ($facility === '') { $respond(false, 'Missing or invalid booking details'); }

// Default dates when missing: start = now rounded to next hour, end = start + 1 day
if ($dateStart === '' || $dateEnd === '') {
    $now = time();
    $rounded = strtotime(date('Y-m-d H:00:00', $now)) + 3600;
    if ($dateStart === '') { $dateStart = date('Y-m-d H:i:s', $rounded); }
    if ($dateEnd === '') { $dateEnd = date('Y-m-d H:i:s', $rounded + 86400); }
}

// Basic sanity and normalization
$tsStart = strtotime($dateStart);
$tsEnd = strtotime($dateEnd);
if (!$tsStart) { $tsStart = time(); }
if (!$tsEnd || $tsEnd <= $tsStart) { $tsEnd = $tsStart + 86400; }
$dateStart = date('Y-m-d H:i:s', $tsStart);
$dateEnd = date('Y-m-d H:i:s', $tsEnd);

// Normalize to DATE for receipt table columns
$startDate = date('Y-m-d', $tsStart);
$endDate = date('Y-m-d', $tsEnd);
$bookedDate = date('Y-m-d', strtotime($dateBooked));

// Verify facility exists and get price + facility_id (base table)
$price = 0.0; $facilityId = 0; $customerId = intval($_SESSION['user']['id'] ?? 0);
if ($facStmt = $conn->prepare("SELECT facility_id, facility_price FROM facility_tbl WHERE facility_name = ? LIMIT 1")) {
    $facStmt->bind_param('s', $facility);
    $facStmt->execute();
    $res = $facStmt->get_result();
    if ($res && ($row = $res->fetch_assoc())) { $facilityId = intval($row['facility_id']); $price = (float)$row['facility_price']; }
    $facStmt->close();
}
if ($facilityId <= 0) { $respond(false, 'Selected facility not found'); }

// Check for booking conflicts: only block admin-confirmed reservations
$conflict = 0;
$confStmt = $conn->prepare("SELECT COUNT(*) FROM reservation_tbl WHERE reservation_facility = ? AND reservation_status = 'Confirmed' AND NOT (reservation_date_end <= ? OR reservation_date_start >= ?)");
if ($confStmt) {
    $startStr = date('Y-m-d H:i:s', $tsStart);
    $endStr = date('Y-m-d H:i:s', $tsEnd);
    $confStmt->bind_param('sss', $facility, $startStr, $endStr);
    $confStmt->execute();
    $confStmt->bind_result($conflict);
    $confStmt->fetch();
    $confStmt->close();
}
if ($conflict > 0) {
    $respond(false, 'Selected time range is not available for this facility');
}

// Recalculate amount on server for safety (this is the TOTAL amount for the stay)
// New flexible pricing system: Day-use vs Overnight
$totalAmount = 0.0;
$hours = 0;
$days = 0;

if ($bookingType === 'day') {
    // Day-use pricing: Calculate by hours
    $hours = (int)ceil(($tsEnd - $tsStart) / 3600); // Convert seconds to hours
    if ($hours < 1) { $hours = 1; }
    
    // Flexible hourly pricing tiers
    if ($hours <= 4) {
        $totalAmount = $price * 0.5; // 4 hours = 50% of daily rate
    } elseif ($hours <= 8) {
        $totalAmount = $price * 0.8; // 8 hours = 80% of daily rate
    } elseif ($hours <= 12) {
        $totalAmount = $price * 0.9; // 12 hours = 90% of daily rate
    } else {
        $totalAmount = $price; // More than 12 hours = full daily rate
    }
    
    $days = 0; // Not applicable for day-use
} else {
    // Overnight pricing: Calculate by days (existing logic)
    $startDateOnly = date('Y-m-d', $tsStart);
    $endDateOnly = date('Y-m-d', $tsEnd);
    
    if (class_exists('DateTime')) {
        $startDateTime = new DateTime($startDateOnly);
        $endDateTime = new DateTime($endDateOnly);
        $days = $endDateTime->diff($startDateTime)->days;
    } else {
        // Fallback for older PHP versions
        $startTs = strtotime($startDateOnly);
        $endTs = strtotime($endDateOnly);
        $days = round(($endTs - $startTs) / (24 * 60 * 60));
    }
    
    // If same day, count as 1 day
    if ($days === 0) { $days = 1; }
    
    $totalAmount = (float)$days * (float)$price;
    $hours = 0; // Not applicable for overnight
}

// Debug: Log the calculation details
error_log("Booking Debug - Type: $bookingType, Start: $dateStart, End: $dateEnd, Hours: $hours, Days: $days, Price: $price, Total: $totalAmount");

// Determine initial amount to record as paid now based on selected plan (no PayMongo required)
$payNow = 0.0;
if (strcasecmp($paymentPlan, 'full') === 0) {
    $payNow = $totalAmount;
} elseif (strcasecmp($paymentPlan, 'down') === 0 && $downPercent !== null && $downPercent > 0 && $downPercent <= 100) {
    $payNow = round($totalAmount * ($downPercent / 100), 2);
}

// Debug: Log payment plan details
error_log("Booking Debug - Plan: $paymentPlan, Down%: $downPercent, PayNow: $payNow");

// Generate transaction id
$transactionId = 'TX' . date('YmdHis') . substr(bin2hex(random_bytes(3)), 0, 6);

// Insert receipt_tbl; tag source if column exists
// amount_paid reflects customer's chosen plan immediately; balance is the remainder
$amountPaid = (float)$payNow;
$balance = max(0.0, round($totalAmount - $amountPaid, 2));

// Debug: Log final amounts
error_log("Booking Debug - AmountPaid: $amountPaid, Balance: $balance, Transaction: $transactionId");

$hasSource = false;
try {
    $dbRow = $conn->query("SELECT DATABASE() db");
    $dbName = ($dbRow && ($r=$dbRow->fetch_assoc())) ? $r['db'] : '';
    if ($dbName) {
        $q = sprintf("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='%s' AND TABLE_NAME='receipt_tbl' AND COLUMN_NAME='source'", $conn->real_escape_string($dbName));
        if ($res = $conn->query($q)) { $hasSource = $res->num_rows > 0; }
    }
} catch (Throwable $e) {}

if ($hasSource) {
    $stmt = $conn->prepare("INSERT INTO receipt_tbl (receipt_trans_code, receipt_reservee, receipt_facility, customer_id, facility_id, receipt_amount_paid, receipt_balance, receipt_date_checkin, receipt_date_checkout, receipt_timestamp, receipt_date_booked, receipt_payment_type, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, 'customer')");
    if (!$stmt) { $respond(false, 'DB prepare failed'); }
    $stmt->bind_param('sssiiddssss', $transactionId, $reservee, $facility, $customerId, $facilityId, $amountPaid, $balance, $startDate, $endDate, $bookedDate, $paymentType);
    $ok = $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO receipt_tbl (receipt_trans_code, receipt_reservee, receipt_facility, customer_id, facility_id, receipt_amount_paid, receipt_balance, receipt_date_checkin, receipt_date_checkout, receipt_timestamp, receipt_date_booked, receipt_payment_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
    if (!$stmt) { $respond(false, 'DB prepare failed'); }
    $stmt->bind_param('sssiiddssss', $transactionId, $reservee, $facility, $customerId, $facilityId, $amountPaid, $balance, $startDate, $endDate, $bookedDate, $paymentType);
    $ok = $stmt->execute();
    $stmt->close();
}

// Also insert a Pending row into reservations so admin table can track it
if ($ok) {
    try {
        $status = 'Pending';
        $dateBookedDt = date('Y-m-d H:i:s', strtotime($dateBooked));
        if ($ins = $conn->prepare("INSERT INTO reservation_tbl (reservation_reservee, reservation_facility, customer_id, facility_id, reservation_status, reservation_date_booked, reservation_date_start, reservation_date_end, reservation_payment_type, reservation_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
            $ins->bind_param('ssiisssssd', $reservee, $facility, $customerId, $facilityId, $status, $dateBookedDt, $dateStart, $dateEnd, $paymentType, $totalAmount);
            @$ins->execute();
            $ins->close();
        }
        // Do not mark unavailable yet; will be set when Confirmed (payment complete)
    } catch (Throwable $e) { /* ignore to avoid breaking booking flow */ }
}

$conn->close();

$respond($ok, $ok ? 'Reservation saved' : 'DB insert failed', $ok ? ['transaction_id' => $transactionId] : []);
