<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

header('Content-Type: application/json');

$rows = [];
// Treat tiny residual balances as zero to avoid floating point residue keeping receipts pending
$sql = "SELECT id, transaction_id, reservee, facility_name, amount_paid, balance, date_checkin, date_checkout, date_booked, payment_type FROM receipt WHERE balance > 0.01 ORDER BY date_booked DESC, id DESC";
if ($res = $conn->query($sql)) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
}
echo json_encode(['success' => true, 'rows' => $rows]);
?>


