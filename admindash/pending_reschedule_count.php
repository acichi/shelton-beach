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

$count = 0;
$sql = "SELECT COUNT(*) as count FROM reschedule_requests WHERE status = 'pending'";
if ($res = $conn->query($sql)) {
    $row = $res->fetch_assoc();
    $count = (int)($row['count'] ?? 0);
}

echo json_encode(['count' => $count]);
?>
