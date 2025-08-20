<?php
require '../properties/connection.php';
require_once __DIR__ . '/../properties/mailer.php';
require_once __DIR__ . '/../properties/email_template.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$id = intval($_POST['id'] ?? 0);
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
// Normalize to enum values
switch (strtolower($status)) {
  case 'pending': $status = 'Pending'; break;
  case 'confirmed': $status = 'Confirmed'; break;
  case 'cancelled':
  case 'canceled': $status = 'Cancelled'; break;
  default: $status = 'Pending';
}
$note = isset($_POST['note']) ? trim($_POST['note']) : '';
if ($id <= 0 || $status === '') { echo json_encode(['success'=>false,'message'=>'Missing id or status']); exit; }

$statusEsc = $conn->real_escape_string($status);
$ok = $conn->query("UPDATE reservations SET status='$statusEsc' WHERE id = $id");

if ($ok && $note !== '') {
  $noteEsc = $conn->real_escape_string($note);
  @$conn->query("INSERT INTO audit_reservation (reservation_id, action, note, created_at) VALUES ($id, '$statusEsc', '$noteEsc', NOW())");
}

if ($ok && strtolower($statusEsc) === 'confirmed') {
  // Try to enrich notification with user email from receipt if available
  $emailTo = null; $reserveeName = null; $facilityName = null; $dates = '';
  if ($r = $conn->prepare('SELECT r.reservee, r.facility_name, u.email FROM reservations z LEFT JOIN users u ON u.fullname = z.reservee LEFT JOIN receipt r ON r.facility_name = z.facility_name AND r.reservee = z.reservee WHERE z.id = ? LIMIT 1')) {
    $r->bind_param('i', $id);
    if ($r->execute()) {
      $tmp = $r->get_result();
      if ($tmp && ($row = $tmp->fetch_assoc())) {
        $reserveeName = $row['reservee'] ?: null;
        $facilityName = $row['facility_name'] ?: null;
        $emailTo = $row['email'] ?: null;
      }
    }
    $r->close();
  }
  if ($emailTo) {
    $subject = 'Your booking status update: ' . ucfirst($statusEsc);
    $html = '<p>Hello ' . htmlspecialchars($reserveeName ?: 'Guest') . ',</p>' .
            '<p>Your booking' . ($facilityName ? ' for <strong>' . htmlspecialchars($facilityName) . '</strong>' : '') . ' has been <strong>' . htmlspecialchars($statusEsc) . '</strong>.</p>' .
            ($note !== '' ? '<p>Note: ' . nl2br(htmlspecialchars($note)) . '</p>' : '') .
            '<p>Thank you,<br/>Shelton Resort</p>';
    $html = renderEmailTemplate('Booking status updated', $html, ['preheader' => 'Your booking status has changed']);
    $alt  = 'Your booking status: ' . $statusEsc . ($note !== '' ? "\nNote: $note" : '');
    @sendEmail($emailTo, $subject, $html, $alt);
  }
}

echo json_encode(['success'=>(bool)$ok]);
?>


