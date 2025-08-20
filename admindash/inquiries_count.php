<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
header('Content-Type: application/json');

@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS inquiries (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	email VARCHAR(255) NOT NULL,
	message TEXT NOT NULL,
	status ENUM('new','read') NOT NULL DEFAULT 'new',
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$count = 0;
if ($res = $conn->query("SELECT COUNT(*) AS c FROM inquiries WHERE status = 'new'")) {
	if ($row = $res->fetch_assoc()) { $count = (int)$row['c']; }
}
echo json_encode(['count' => $count]);
?>


