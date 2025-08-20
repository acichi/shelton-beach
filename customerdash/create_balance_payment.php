<?php
session_start();
header('Content-Type: application/json');

require __DIR__ . '/../properties/connection.php';
require __DIR__ . '/../config/paymongo.php';

// Input: transaction_id (original), method one of gcash|paymaya|card
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$tx = trim($data['transaction_id'] ?? '');
$method = strtolower(trim($data['method'] ?? ''));

if ($tx === '' || !in_array($method, ['gcash','paymaya','card'], true)) {
    echo json_encode(['success'=>false,'error'=>'Invalid request']);
    exit;
}

// Load receipt row to compute outstanding
$stmt = $conn->prepare("SELECT receipt_reservee AS reservee, receipt_facility AS facility_name, receipt_amount_paid AS amount_paid, receipt_balance AS balance, receipt_date_checkin AS date_checkin, receipt_date_checkout AS date_checkout FROM receipt_tbl WHERE receipt_trans_code = ? AND receipt_reservee = ? LIMIT 1");
$reservee = (string)($_SESSION['user']['fullname'] ?? '');
$stmt->bind_param('ss', $tx, $reservee);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
if (!$row) { echo json_encode(['success'=>false,'error'=>'Receipt not found']); exit; }

$balance = (float)($row['balance'] ?? 0);
if ($balance <= 0.01) { echo json_encode(['success'=>false,'error'=>'No outstanding balance']); exit; }

$amountCentavos = (int) round($balance * 100);

$headers = [
    'Content-Type: application/json',
    'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':')
];

$dateCheckinDate  = $row['date_checkin'] ? date('Y-m-d', strtotime($row['date_checkin'])) : date('Y-m-d');
$dateCheckoutDate = $row['date_checkout'] ? date('Y-m-d', strtotime($row['date_checkout'])) : date('Y-m-d');

$body = [
    'data' => [
        'attributes' => [
            'line_items' => [[
                'currency' => 'PHP',
                'amount' => $amountCentavos,
                'description' => 'Balance payment for ' . ($row['facility_name'] ?? 'Reservation'),
                'name' => 'Balance for ' . $reservee,
                'quantity' => 1
            ]],
            'payment_method_types' => [$method],
            'metadata' => [
                'reservee' => $reservee,
                'facility_name' => (string)$row['facility_name'],
                'payment_plan' => 'balance',
                'down_percent' => null,
                'total_amount' => null,
                'pay_now' => $balance,
                'original_tx' => $tx,
                'date_checkin' => $dateCheckinDate,
                'date_checkout' => $dateCheckoutDate
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
if ($err) { echo json_encode(['success'=>false,'error'=>'Curl error: ' . $err]); exit; }

$res = json_decode($response, true);
if (!isset($res['data']['attributes']['checkout_url'])) { echo json_encode(['success'=>false,'error'=>'Invalid PayMongo response']); exit; }

echo json_encode(['success'=>true,'checkout_url'=>$res['data']['attributes']['checkout_url']]);
?>


