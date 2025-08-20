<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');
// Prevent any caching so hide/unhide updates reflect immediately
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$rows = [];
$only_visible = isset($_GET['visible']) ? (int)$_GET['visible'] : 0; // 1 = only visible
$only_hidden = isset($_GET['hidden']) ? (int)$_GET['hidden'] : 0;   // 1 = only hidden
$where = [];
if ($only_visible === 1) { $where[] = 'COALESCE(is_hidden,0) = 0'; }
if ($only_hidden === 1) { $where[] = 'COALESCE(is_hidden,0) = 1'; }
$sql = 'SELECT id, fullname, facility_name, feedback, rate, timestamp, COALESCE(is_hidden,0) AS is_hidden FROM feedback';
if ($where) { $sql .= ' WHERE ' . implode(' AND ', $where); }
$sql .= ' ORDER BY timestamp DESC, id DESC';

if ($res = $conn->query($sql)) {
	while ($r = $res->fetch_assoc()) { $rows[] = $r; }
}

echo json_encode($rows);
?>


