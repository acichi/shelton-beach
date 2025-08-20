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

function respond_json($ok, $msg, $extra = []) {}

try {
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    $respond(false, 'Invalid request');
  }

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?: [];

$reservee = trim($body['reservee'] ?? 'Walk-in Guest');
$facility = trim($body['facility_name'] ?? '');
$dateStart = trim($body['date_start'] ?? '');
$dateEnd = trim($body['date_end'] ?? '');
$paymentType = trim($body['payment_type'] ?? 'Cash');

if ($facility === '') {
  $respond(false, 'Missing or invalid booking details');
}

// Default dates when not provided: start = now rounded to next hour, end = start + 1 day
if ($dateStart === '' || $dateEnd === '') {
  $now = time();
  $rounded = strtotime(date('Y-m-d H:00:00', $now)) + 3600; // next hour
  if ($dateStart === '') { $dateStart = date('Y-m-d H:i:s', $rounded); }
  if ($dateEnd === '') { $dateEnd = date('Y-m-d H:i:s', $rounded + 86400); }
}

$tsStart = strtotime($dateStart);
$tsEnd = strtotime($dateEnd);
  if (!$tsStart) { $tsStart = time(); }
  if (!$tsEnd || $tsEnd <= $tsStart) { $tsEnd = $tsStart + 86400; }
  // Normalize string representations after defaults/fixes
  $dateStart = date('Y-m-d H:i:s', $tsStart);
  $dateEnd = date('Y-m-d H:i:s', $tsEnd);

// Verify facility and get price
$price = 0.0;
$facStmt = $conn->prepare("SELECT price FROM facility WHERE name = ? LIMIT 1");
  if (!$facStmt) { $respond(false, 'Server error'); }
$facStmt->bind_param('s', $facility);
$facStmt->execute();
$facStmt->bind_result($price);
  if (!$facStmt->fetch()) { $facStmt->close(); $respond(false, 'Facility not found'); }
$facStmt->close();

// Conflict check (align with availability: block only fully-paid receipts)
$conflict = 0;
$confStmt = $conn->prepare("SELECT COUNT(*) FROM receipt WHERE facility_name = ? AND balance = 0 AND NOT (date_checkout <= ? OR date_checkin >= ?)");
if ($confStmt) {
  $startStr = date('Y-m-d H:i:s', $tsStart);
  $endStr = date('Y-m-d H:i:s', $tsEnd);
  $confStmt->bind_param('sss', $facility, $startStr, $endStr);
  $confStmt->execute();
  $confStmt->bind_result($conflict);
  $confStmt->fetch();
  $confStmt->close();
}
  if ($conflict > 0) { $respond(false, 'Selected range not available'); }

// Amount (always use calculated amount; ignore client-provided amount)
$days = (int)ceil(($tsEnd - $tsStart) / 86400);
if ($days < 1) { $days = 1; }
$amount = (float)$days * (float)$price;

// Insert receipt (with optional source columns)
$transactionId = 'TX' . date('YmdHis') . substr(bin2hex(random_bytes(3)), 0, 6);
$amountPaid = 0.0;
$balance = $amount;
$dateBooked = date('Y-m-d');
$startDate = date('Y-m-d', $tsStart);
$endDate = date('Y-m-d', $tsEnd);

// Detect optional columns
$hasSource = false; $hasCreatedByAdmin = false;
try {
  if ($conn) {
    $dbRes = $conn->query("SELECT DATABASE() AS db");
    $dbRow = $dbRes ? $dbRes->fetch_assoc() : null; $dbName = $dbRow ? $dbRow['db'] : '';
    if ($dbName) {
      $escDb = $conn->real_escape_string($dbName);
      $q = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$escDb}' AND TABLE_NAME='receipt' AND COLUMN_NAME IN ('source','created_by_admin')";
      if ($rs = $conn->query($q)) {
        while ($col = $rs->fetch_assoc()) {
          if ($col['COLUMN_NAME'] === 'source') $hasSource = true;
          if ($col['COLUMN_NAME'] === 'created_by_admin') $hasCreatedByAdmin = true;
        }
      }
    }
  }
} catch (Throwable $e) {}

if ($hasSource || $hasCreatedByAdmin) {
  $cols = "transaction_id, reservee, facility_name, amount_paid, balance, date_checkin, date_checkout, timestamp, date_booked, payment_type";
  $place = "?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?";
  $types = 'sssdsssss';
  $params = [$transactionId, $reservee, $facility, $amountPaid, $balance, $startDate, $endDate, $dateBooked, $paymentType];
  if ($hasSource) { $cols .= ", source"; $place .= ", ?"; $types .= 's'; $params[] = 'walkin'; }
  if ($hasCreatedByAdmin) { $cols .= ", created_by_admin"; $place .= ", ?"; $types .= 'i'; $params[] = 1; }
  $sql = "INSERT INTO receipt ($cols) VALUES ($place)";
  $stmt = $conn->prepare($sql);
  if (!$stmt) { $respond(false, 'DB prepare failed'); }
  $stmt->bind_param($types, ...$params);
  $ok = $stmt->execute();
  $stmt->close();
  $conn->close();
} else {
  $stmt = $conn->prepare("INSERT INTO receipt (transaction_id, reservee, facility_name, amount_paid, balance, date_checkin, date_checkout, timestamp, date_booked, payment_type) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
  if (!$stmt) { $respond(false, 'DB prepare failed'); }
  $stmt->bind_param('sssdsssss', $transactionId, $reservee, $facility, $amountPaid, $balance, $startDate, $endDate, $dateBooked, $paymentType);
  $ok = $stmt->execute();
  $stmt->close();
  $conn->close();
}

  $respond($ok, $ok ? 'Walk-in reservation saved' : 'DB insert failed', $ok ? ['transaction_id' => $transactionId] : []);
} catch (Throwable $e) {
  while (ob_get_level() > 0) { ob_end_clean(); }
  $respond(false, 'Server error');
}


