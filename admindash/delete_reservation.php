<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }

// CSRF validation
$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }

$input = $_POST;
if (empty($input)) {
	$input = json_decode(file_get_contents('php://input'), true) ?: [];
}
$id = intval($input['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }

// Use prepared statement to avoid injection
if ($stmt = $conn->prepare('DELETE FROM reservations WHERE id = ?')) {
	$stmt->bind_param('i', $id);
	$ok = $stmt->execute();
	$stmt->close();
	echo json_encode(['success' => (bool)$ok]);
} else {
	echo json_encode(['success'=>false,'message'=>'Delete failed']);
}
?>


