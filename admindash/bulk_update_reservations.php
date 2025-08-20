<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$payload = json_decode(file_get_contents('php://input'), true) ?: [];
$ids = isset($payload['ids']) && is_array($payload['ids']) ? array_map('intval', $payload['ids']) : [];
$status = isset($payload['status']) ? trim($payload['status']) : '';
$reason = isset($payload['reason']) ? trim($payload['reason']) : '';

if (!$ids || !$status) { echo json_encode(['success'=>false,'message'=>'Missing ids or status']); exit; }

$in = implode(',', array_filter($ids, fn($v)=>$v>0));
if ($in === '') { echo json_encode(['success'=>false,'message'=>'No valid ids']); exit; }

$statusEsc = $conn->real_escape_string($status);
$sql = "UPDATE reservations SET status='$statusEsc' WHERE id IN ($in)";
$ok = $conn->query($sql);

// Optional: write to a simple audit trail if table exists
if ($ok && $reason !== '') {
    $reasonEsc = $conn->real_escape_string($reason);
    // audit_reservation(id, action, note, created_at)
    @$conn->query("INSERT INTO audit_reservation (reservation_id, action, note, created_at) SELECT id, '$statusEsc', '$reasonEsc', NOW() FROM reservations WHERE id IN ($in)");
}

echo json_encode(['success'=> (bool)$ok]);
?>


