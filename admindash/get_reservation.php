<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }

$resData = null;
if ($res = $conn->query("SELECT id, reservee, facility_name, status, date_booked, date_start, date_end, payment_type, amount FROM reservations WHERE id = $id LIMIT 1")) {
    $resData = $res->fetch_assoc();
}
if (!$resData) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

// Pull notes if audit table exists
$notes = [];
$hasAudit = $conn->query("SHOW TABLES LIKE 'audit_reservation'");
if ($hasAudit && $hasAudit->num_rows > 0) {
    if ($r = $conn->query("SELECT id, action, note, created_at FROM audit_reservation WHERE reservation_id = $id ORDER BY created_at DESC, id DESC")) {
        while ($row = $r->fetch_assoc()) { $notes[] = $row; }
    }
}

echo json_encode(['success'=>true,'reservation'=>$resData,'notes'=>$notes]);
?>


