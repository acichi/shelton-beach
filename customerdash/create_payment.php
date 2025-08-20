<?php
session_start();
require __DIR__ . '/../properties/connection.php';
require __DIR__ . '/../config/paymongo.php';

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Extract payload values early so we can use them in metadata and DB
$reservee      = $data['reservee'] ?? '';
$facilityName  = $data['facility_name'] ?? '';
// Support both legacy 'amount' (treated as total) and explicit total/pay_now fields for down payments
$totalAmount   = isset($data['total_amount']) ? (float)$data['total_amount'] : (float)($data['amount'] ?? 0);
$payNow        = isset($data['pay_now']) ? (float)$data['pay_now'] : $totalAmount;
$paymentPlan   = $data['payment_plan'] ?? 'full';
$downPercent   = isset($data['down_percent']) ? (int)$data['down_percent'] : null;
$dateCheckin   = $data['date_start'] ?? null;
$dateCheckout  = $data['date_end'] ?? null;
$dateBooked    = $data['date_booked'] ?? date('Y-m-d H:i:s');

// Normalize computed values and convert to PayMongo expected types
if ($paymentPlan === 'down' && $downPercent !== null && $downPercent > 0 && $downPercent <= 100) {
    // If only total provided, derive payNow from percent
    if (!isset($data['pay_now'])) {
        $payNow = round($totalAmount * ($downPercent / 100), 2);
    }
}
// integer centavos for the amount to actually charge now
$amountCentavos = (int) round(max(0, $payNow) * 100);
$paymentType = strtolower($data['payment_type'] ?? '');

$validTypes = ['gcash', 'paymaya', 'card'];
if ($paymentType === 'visa' || $paymentType === 'mastercard') $paymentType = 'card';
if (!in_array($paymentType, $validTypes, true)) { echo json_encode(['error' => 'Invalid payment type']); exit; }

$headers = [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
];

// Normalize dates for metadata and DB
$dateCheckinDate  = $dateCheckin ? date('Y-m-d', strtotime($dateCheckin)) : date('Y-m-d');
$dateCheckoutDate = $dateCheckout ? date('Y-m-d', strtotime($dateCheckout)) : date('Y-m-d');

$body = [
    'data' => [
        'attributes' => [
            'line_items' => [[
                'currency' => 'PHP',
                'amount' => $amountCentavos,
                'description' => $facilityName !== '' ? $facilityName : 'Reservation',
                'name' => 'Reservation for ' . $reservee,
                'quantity' => 1
            ]],
            'payment_method_types' => [$paymentType],
            'metadata' => [
                'reservee' => $reservee,
                'facility_name' => $facilityName,
                'date_checkin' => $dateCheckinDate,
                'date_checkout' => $dateCheckoutDate,
                'payment_plan' => $paymentPlan,
                'down_percent' => $downPercent,
                'total_amount' => $totalAmount,
                'pay_now' => $payNow
            ],
            'redirect' => [
                'success' => PAYMONGO_CHECKOUT_SUCCESS_URL,
                'failed' => PAYMONGO_CHECKOUT_FAILED_URL
            ]
        ]
    ]
];

$ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_CUSTOMREQUEST => 'POST', CURLOPT_POSTFIELDS => json_encode($body), CURLOPT_HTTPHEADER => $headers]);
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);
if ($err) { echo json_encode(['error' => 'Curl error: ' . $err]); exit; }

$res = json_decode($response, true);
if (!isset($res['data']['id'], $res['data']['attributes']['checkout_url'])) { echo json_encode(['error' => 'Invalid PayMongo response']); exit; }

$transactionId = $res['data']['id'];
$checkoutUrl   = $res['data']['attributes']['checkout_url'];

$timestamp     = date('Y-m-d H:i:s');
// Map to DB enum values defined in shelton_db.sql
switch ($paymentType) {
    case 'gcash':
        $paymentTypeDB = 'Gcash';
        break;
    case 'paymaya':
        $paymentTypeDB = 'Maya';
        break;
    case 'card':
        // Generic mapping; schema allows 'Credit Card' and 'Debit Card'. Use 'Credit Card' as default.
        $paymentTypeDB = 'Credit Card';
        break;
    default:
        $paymentTypeDB = 'Cash';
}

// Persist a provisional receipt row; keep amount_paid 0 and balance as full amount until webhook confirms payment
// Insert receipt; tag source if column exists
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
    $stmt = $conn->prepare("INSERT INTO receipt_tbl (receipt_trans_code, receipt_reservee, receipt_facility, receipt_amount_paid, receipt_balance, receipt_date_checkin, receipt_date_checkout, receipt_timestamp, receipt_date_booked, receipt_payment_type, source) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, 'customer')");
    $stmt->bind_param('sssdsssss', $transactionId, $reservee, $facilityName, $totalAmount, $dateCheckinDate, $dateCheckoutDate, $timestamp, $dateBooked, $paymentTypeDB);
    if (!$stmt->execute()) { echo json_encode(['error' => 'DB insert failed: ' . $stmt->error]); exit; }
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO receipt_tbl (receipt_trans_code, receipt_reservee, receipt_facility, receipt_amount_paid, receipt_balance, receipt_date_checkin, receipt_date_checkout, receipt_timestamp, receipt_date_booked, receipt_payment_type) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssdsssss', $transactionId, $reservee, $facilityName, $totalAmount, $dateCheckinDate, $dateCheckoutDate, $timestamp, $dateBooked, $paymentTypeDB);
    if (!$stmt->execute()) { echo json_encode(['error' => 'DB insert failed: ' . $stmt->error]); exit; }
    $stmt->close();
}

// Do not mark facility unavailable here; wait for webhook 'paid' confirmation

$_SESSION['last_tx'] = $transactionId;

echo json_encode(['checkout_url' => $checkoutUrl]);
?>


