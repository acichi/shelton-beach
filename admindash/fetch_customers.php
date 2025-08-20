<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

// Create soft-ban table if not exists
@$conn->query("CREATE TABLE IF NOT EXISTS banned_users (
  user_id INT PRIMARY KEY,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$rows = [];
$onlyBanned = isset($_GET['only_banned']) ? (int)$_GET['only_banned'] : 0;
$sql = "SELECT u.id, u.fullname, u.email, u.number, u.gender, u.address, u.date_added,
        CASE WHEN b.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_banned
        FROM users u
        LEFT JOIN banned_users b ON b.user_id = u.id
        WHERE u.role = 'customer'" . ($onlyBanned === 1 ? " AND b.user_id IS NOT NULL" : "") . "
        ORDER BY COALESCE(u.date_added, '1970-01-01') DESC, u.id DESC";
if ($res = $conn->query($sql)) {
	while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

echo json_encode($rows);
?>


