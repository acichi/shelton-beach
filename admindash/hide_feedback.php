<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	echo json_encode(['success' => false, 'message' => 'Invalid method']);
	exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$action = $_POST['action'] ?? null;

if ($id <= 0 || !in_array($action, ['hide', 'unhide'], true)) {
	echo json_encode(['success' => false, 'message' => 'Invalid request']);
	exit;
}

$is_hidden = ($action === 'hide') ? 1 : 0;

$stmt = $conn->prepare("UPDATE feedback_tbl SET feedback_status = CASE WHEN ? = 1 THEN 'hide' ELSE 'show' END WHERE feedback_id = ?");
if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Prepare failed']); exit; }
$stmt->bind_param('ii', $is_hidden, $id);

if ($stmt->execute()) {
	$changed = $stmt->affected_rows;
	echo json_encode(['success' => true, 'affected' => $changed, 'id' => $id, 'is_hidden' => $is_hidden]);
} else {
	echo json_encode(['success' => false, 'message' => 'Failed to update feedback', 'error' => $stmt->error]);
}
?>



