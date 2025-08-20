<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

header('Content-Type: application/json');

@$conn->query("CREATE TABLE IF NOT EXISTS cancellation_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id VARCHAR(100),
  reservee VARCHAR(255),
  status VARCHAR(50) DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$count = 0;
if ($res = $conn->query("SELECT COUNT(*) AS c FROM cancellation_requests WHERE status = 'pending'")) {
  $row = $res->fetch_assoc();
  $count = (int)($row['c'] ?? 0);
}
echo json_encode(['success' => true, 'count' => $count]);

?>


