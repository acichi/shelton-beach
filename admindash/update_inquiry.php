<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$id = intval($_POST['id'] ?? 0);
$action = strtolower(trim($_POST['action'] ?? ''));

if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }
if (!in_array($action, ['read','unread','delete'], true)) { echo json_encode(['success'=>false,'message'=>'Invalid action']); exit; }

// Ensure table exists
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS inquiries (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	email VARCHAR(255) NOT NULL,
	message TEXT NOT NULL,
	status ENUM('new','read') NOT NULL DEFAULT 'new',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

if ($action === 'delete') {
	$ok = $conn->query("DELETE FROM inquiries WHERE id = $id");
} else {
	$newStatus = $action === 'read' ? 'read' : 'new';
	$ok = $conn->query("UPDATE inquiries SET status='$newStatus' WHERE id = $id");
}

echo json_encode(['success'=>(bool)$ok]);
?>


