<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }

@$conn->query("CREATE TABLE IF NOT EXISTS banned_users (
  user_id INT PRIMARY KEY,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$ok = $conn->query("REPLACE INTO banned_users (user_id, created_at) VALUES ($id, NOW())");
echo json_encode(['success'=>(bool)$ok]);
?>


