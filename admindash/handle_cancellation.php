<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
require_once __DIR__ . '/../properties/mailer.php';
require_once __DIR__ . '/../properties/email_template.php';

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed']); exit; }

$id = intval($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');
if ($id <= 0 || ($action !== 'approve' && $action !== 'deny')) {
  echo json_encode(['success'=>false,'message'=>'Invalid request']);
  exit;
}

// Ensure table exists (idempotent)
@$conn->query("CREATE TABLE IF NOT EXISTS cancellation_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id VARCHAR(100),
  reservee VARCHAR(255),
  status VARCHAR(50) DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Audit table for approvals/denials
@$conn->query("CREATE TABLE IF NOT EXISTS cancellation_audit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  action VARCHAR(50) NOT NULL,
  admin_name VARCHAR(255) DEFAULT NULL,
  admin_id INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Fetch request and associated receipt
$req = null;
if ($s = $conn->prepare("SELECT id, transaction_id, reservee, status, created_at FROM cancellation_requests WHERE id = ? LIMIT 1")) {
  $s->bind_param('i', $id);
  $s->execute();
  $res = $s->get_result();
  $req = $res ? $res->fetch_assoc() : null;
  $s->close();
}
if (!$req) { echo json_encode(['success'=>false,'message'=>'Request not found']); exit; }
if (strtolower($req['status']) !== 'pending') { echo json_encode(['success'=>false,'message'=>'Already handled']); exit; }

$tx = (string)$req['transaction_id'];

// Deny -> mark denied and record audit
if ($action === 'deny') {
  $ok = $conn->query("UPDATE cancellation_requests SET status='denied' WHERE id = ".intval($id));
  if ($ok) {
    $adminName = $_SESSION['user']['fullname'] ?? 'Admin';
    $adminId = intval($_SESSION['user']['id'] ?? 0);
    $an = $conn->real_escape_string($adminName);
    @$conn->query("INSERT INTO cancellation_audit (request_id, action, admin_name, admin_id) VALUES (".intval($id).", 'denied', '$an', ".$adminId.")");
  }
  echo json_encode(['success'=>(bool)$ok]);
  exit;
}

// Approve -> mark request approved, set reservation(s) to Cancelled, keep payments as-is (no refund)
$conn->begin_transaction();
try {
  // 1) Mark request
  $ok1 = $conn->query("UPDATE cancellation_requests SET status='approved' WHERE id = ".intval($id));
  if (!$ok1) { throw new Exception('fail1'); }

  // 2) Find latest matching reservation by reservee/tx info
  $reservee = '';
  $facility = '';
  $dateStart = null; $dateEnd = null;
  if ($g = $conn->prepare("SELECT reservee, facility_name, date_checkin, date_checkout FROM receipt WHERE transaction_id = ? LIMIT 1")) {
    $g->bind_param('s', $tx);
    if ($g->execute()) {
      $gr = $g->get_result();
      if ($gr && ($row = $gr->fetch_assoc())) {
        $reservee = (string)$row['reservee'];
        $facility = (string)$row['facility_name'];
        $dateStart = $row['date_checkin'];
        $dateEnd = $row['date_checkout'];
      }
    }
    $g->close();
  }

  $rid = 0;
  if ($reservee !== '' && $facility !== '') {
    if ($f = $conn->prepare("SELECT id FROM reservations WHERE reservee = ? AND facility_name = ? ORDER BY date_booked DESC, id DESC LIMIT 1")) {
      $f->bind_param('ss', $reservee, $facility);
      if ($f->execute()) {
        $res2 = $f->get_result();
        if ($res2 && ($row2 = $res2->fetch_assoc())) { $rid = intval($row2['id']); }
      }
      $f->close();
    }
  }

  if ($rid > 0) {
    $ok2 = $conn->query("UPDATE reservations SET status='Cancelled' WHERE id = ".$rid);
    if (!$ok2) { throw new Exception('fail2'); }
  } else if ($reservee !== '' && $facility !== '') {
    // Create a minimal cancelled reservation for tracking
    if ($ins = $conn->prepare("INSERT INTO reservations (reservee, facility_name, status, date_booked, date_start, date_end, payment_type, amount) VALUES (?, ?, 'Cancelled', NOW(), ?, ?, '', 0)")) {
      $ds = $dateStart ?: null; $de = $dateEnd ?: null;
      $ins->bind_param('ssss', $reservee, $facility, $ds, $de);
      @$ins->execute();
      $ins->close();
    }
  }

  $conn->commit();

  // Audit record
  $adminName = $_SESSION['user']['fullname'] ?? 'Admin';
  $adminId = intval($_SESSION['user']['id'] ?? 0);
  $an = $conn->real_escape_string($adminName);
  @$conn->query("INSERT INTO cancellation_audit (request_id, action, admin_name, admin_id) VALUES (".intval($id).", 'approved', '$an', ".$adminId.")");

  // Optional email to customer
  if ($reservee !== '') {
    $emailTo = '';
    if ($u2 = $conn->prepare('SELECT email FROM users WHERE fullname = ? LIMIT 1')) {
      $u2->bind_param('s', $reservee);
      if ($u2->execute()) {
        $res3 = $u2->get_result();
        if ($res3 && ($row3 = $res3->fetch_assoc())) { $emailTo = (string)$row3['email']; }
      }
      $u2->close();
    }
    if ($emailTo !== '') {
      $subject = 'Your cancellation has been approved';
      $html = '<p>Hi ' . htmlspecialchars($reservee) . ',</p>'
        . '<p>Your booking cancellation has been approved. Per our Terms and Conditions, no refund will be issued.</p>'
        . '<p>If you have questions, please reply to this email.</p>'
        . '<p>Thank you,<br/>Shelton Resort</p>';
      $html = renderEmailTemplate('Cancellation approved', $html, [ 'preheader' => 'Cancellation approved (no refund)' ]);
      $alt = 'Your cancellation has been approved. No refund will be issued.';
      @sendEmail($emailTo, $subject, $html, $alt);
    }
  }

  echo json_encode(['success'=>true]);
  exit;
} catch (Throwable $e) {
  $conn->rollback();
  echo json_encode(['success'=>false,'message'=>'Server error']);
  exit;
}

?>


