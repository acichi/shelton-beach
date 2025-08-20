<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$id = intval($_POST['id'] ?? 0);
$note = isset($_POST['note']) ? trim($_POST['note']) : '';
if ($id <= 0 || $note === '') { echo json_encode(['success'=>false,'message'=>'Missing id or note']); exit; }

$noteEsc = $conn->real_escape_string($note);
$ok = @$conn->query("INSERT INTO audit_reservation (reservation_id, action, note, created_at) VALUES ($id, 'note', '$noteEsc', NOW())");

echo json_encode(['success'=>(bool)$ok]);
?>


