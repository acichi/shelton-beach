<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
$input = $_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []);
$id = intval($input['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }

// get existing to remove file
$loc = null;
if ($res = $conn->query("SELECT location FROM gallery WHERE id = $id")) {
	if ($row = $res->fetch_assoc()) { $loc = $row['location']; }
}

if ($conn->query("DELETE FROM gallery WHERE id = $id")) {
	if ($loc && file_exists('../'.$loc)) { @unlink('../'.$loc); }
	echo json_encode(['success'=>true]);
} else {
	echo json_encode(['success'=>false,'message'=>'Delete failed']);
}
?>



