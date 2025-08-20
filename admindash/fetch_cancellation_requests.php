<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

header('Content-Type: application/json');

// Ensure table exists (idempotent)
@$conn->query("CREATE TABLE IF NOT EXISTS cancellation_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id VARCHAR(100),
  reservee VARCHAR(255),
  status VARCHAR(50) DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$rows = [];
$sql = "SELECT 
  cr.id AS req_id,
  cr.transaction_id,
  COALESCE(r.reservee, cr.reservee) AS reservee,
  r.facility_name,
  r.amount_paid,
  r.balance,
  r.date_checkin,
  r.date_checkout,
  cr.status,
  cr.created_at
FROM cancellation_requests cr
LEFT JOIN receipt r ON r.transaction_id = cr.transaction_id
WHERE cr.status = 'pending'
ORDER BY cr.created_at DESC, cr.id DESC";

if ($res = $conn->query($sql)) {
  while ($r = $res->fetch_assoc()) {
    $rows[] = $r;
  }
}

echo json_encode(['success' => true, 'rows' => $rows]);

?>


