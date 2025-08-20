<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

header('Content-Type: application/json');

// Ensure table exists (idempotent)
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

$rows = [];
$sql = "SELECT 
    rr.id AS req_id,
    rr.transaction_id,
    rr.reservee,
    rr.facility_name,
    rr.current_checkin,
    rr.current_checkout,
    rr.new_checkin,
    rr.new_checkout,
    rr.reason,
    rr.status,
    rr.created_at,
    COALESCE(r.receipt_amount_paid, 0) AS amount_paid,
    COALESCE(r.receipt_balance, 0) AS balance
FROM reschedule_requests rr
LEFT JOIN receipt_tbl r ON r.receipt_trans_code = rr.transaction_id
WHERE rr.status = 'pending'
ORDER BY rr.created_at DESC, rr.id DESC";

if ($res = $conn->query($sql)) {
    while ($r = $res->fetch_assoc()) {
        $rows[] = $r;
    }
}

echo json_encode(['success' => true, 'rows' => $rows]);
?>
