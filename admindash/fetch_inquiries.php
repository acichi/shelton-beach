<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
header('Content-Type: application/json');

$status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : '';
$allowed = ['new','read',''];
if (!in_array($status, $allowed, true)) { $status = ''; }

// Ensure table exists (no-op if already there)
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS inquiries (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	email VARCHAR(255) NOT NULL,
	message TEXT NOT NULL,
	status ENUM('new','read') NOT NULL DEFAULT 'new',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$where = '';
if ($status !== '') { $where = "WHERE status = '" . $conn->real_escape_string($status) . "'"; }

$res = $conn->query("SELECT id, name, email, message, status, created_at FROM inquiries $where ORDER BY created_at DESC, id DESC LIMIT 200");
$rows = [];
if ($res) {
	while ($row = $res->fetch_assoc()) {
		$rows[] = $row;
	}
}

echo json_encode(['success' => true, 'inquiries' => $rows]);
?>


