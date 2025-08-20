<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
require_once __DIR__ . '/../properties/mailer.php';
require_once __DIR__ . '/../properties/email_template.php';
require_once __DIR__ . '/../properties/availability.php';

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { 
    http_response_code(405); 
    echo json_encode(['success'=>false,'message'=>'Method not allowed']); 
    exit; 
}

$id = intval($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');
if ($id <= 0 || ($action !== 'approve' && $action !== 'deny')) {
    echo json_encode(['success'=>false,'message'=>'Invalid request']);
    exit;
}

// Ensure table exists (idempotent)
@$conn->query("CREATE TABLE IF NOT EXISTS reschedule_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100),
    reservee VARCHAR(255),
    facility_name VARCHAR(255),
    current_checkin DATE,
    current_checkout DATE,
    new_checkin DATE,
    new_checkout DATE,
    reason TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Audit table for approvals/denials
@$conn->query("CREATE TABLE IF NOT EXISTS reschedule_audit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    admin_name VARCHAR(255) DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Fetch request
$req = null;
if ($s = $conn->prepare("SELECT id, transaction_id, reservee, facility_name, current_checkin, current_checkout, new_checkin, new_checkout, reason, status FROM reschedule_requests WHERE id = ? LIMIT 1")) {
    $s->bind_param('i', $id);
    $s->execute();
    $res = $s->get_result();
    $req = $res ? $res->fetch_assoc() : null;
    $s->close();
}
if (!$req) { 
    echo json_encode(['success'=>false,'message'=>'Request not found']); 
    exit; 
}
if (strtolower($req['status']) !== 'pending') { 
    echo json_encode(['success'=>false,'message'=>'Already handled']); 
    exit; 
}

$tx = (string)$req['transaction_id'];
$reservee = (string)$req['reservee'];
$facility = (string)$req['facility_name'];

// Deny -> mark denied and record audit
if ($action === 'deny') {
    $ok = $conn->query("UPDATE reschedule_requests SET status='denied' WHERE id = ".intval($id));
    if ($ok) {
        $adminName = $_SESSION['user']['fullname'] ?? 'Admin';
        $adminId = intval($_SESSION['user']['id'] ?? 0);
        $an = $conn->real_escape_string($adminName);
        @$conn->query("INSERT INTO reschedule_audit (request_id, action, admin_name, admin_id) VALUES (".intval($id).", 'denied', '$an', ".$adminId.")");
    }
    echo json_encode(['success'=>(bool)$ok]);
    exit;
}

// Approve -> update dates in receipt and reservation tables
$conn->begin_transaction();
try {
    // 1) Mark request approved
    $ok1 = $conn->query("UPDATE reschedule_requests SET status='approved' WHERE id = ".intval($id));
    if (!$ok1) { throw new Exception('fail1'); }

    // 2) Update receipt dates
    $newCheckin = $conn->real_escape_string($req['new_checkin']);
    $newCheckout = $conn->real_escape_string($req['new_checkout']);
    $ok2 = $conn->query("UPDATE receipt_tbl SET receipt_date_checkin='$newCheckin', receipt_date_checkout='$newCheckout' WHERE receipt_trans_code='$tx'");
    if (!$ok2) { throw new Exception('fail2'); }

    // 3) Update reservation dates if exists
    $rid = 0;
    if ($f = $conn->prepare("SELECT reservation_id AS id FROM reservation_tbl WHERE reservation_reservee = ? AND reservation_facility = ? ORDER BY reservation_date_booked DESC, reservation_id DESC LIMIT 1")) {
        $f->bind_param('ss', $reservee, $facility);
        if ($f->execute()) {
            $res2 = $f->get_result();
            if ($res2 && ($row2 = $res2->fetch_assoc())) { 
                $rid = intval($row2['id']); 
            }
        }
        $f->close();
    }
    
    if ($rid > 0) {
        $newStart = date('Y-m-d H:i:s', strtotime($req['new_checkin']));
        $newEnd = date('Y-m-d H:i:s', strtotime($req['new_checkout']));
        $ok3 = $conn->query("UPDATE reservation_tbl SET reservation_date_start='$newStart', reservation_date_end='$newEnd' WHERE reservation_id = ".$rid);
        if (!$ok3) { throw new Exception('fail3'); }
    }

    $conn->commit();

    // Recompute availability for the facility
    if ($facility !== '') { 
        recomputeFacilityAvailability($conn, $facility); 
    }

    // Audit record
    $adminName = $_SESSION['user']['fullname'] ?? 'Admin';
    $adminId = intval($_SESSION['user']['id'] ?? 0);
    $an = $conn->real_escape_string($adminName);
    @$conn->query("INSERT INTO reschedule_audit (request_id, action, admin_name, admin_id) VALUES (".intval($id).", 'approved', '$an', ".$adminId.")");

    // Email to customer
    if ($reservee !== '') {
        $emailTo = '';
        if ($u2 = $conn->prepare('SELECT customer_email AS email FROM customer_tbl WHERE CONCAT(COALESCE(customer_fname, \'\'), \' \' , COALESCE(customer_lname, \'\')) = ? LIMIT 1')) {
            $u2->bind_param('s', $reservee);
            if ($u2->execute()) {
                $res3 = $u2->get_result();
                if ($res3 && ($row3 = $res3->fetch_assoc())) { 
                    $emailTo = (string)$row3['email']; 
                }
            }
            $u2->close();
        }
        if ($emailTo !== '') {
            $subject = 'Your reschedule request has been approved';
            $currentDates = date('M d, Y', strtotime($req['current_checkin'])) . ' - ' . date('M d, Y', strtotime($req['current_checkout']));
            $newDates = date('M d, Y', strtotime($req['new_checkin'])) . ' - ' . date('M d, Y', strtotime($req['new_checkout']));
            $html = '<p>Hi ' . htmlspecialchars($reservee) . ',</p>'
                . '<p>Your reschedule request has been approved!</p>'
                . '<p><strong>Facility:</strong> ' . htmlspecialchars($facility) . '</p>'
                . '<p><strong>Previous dates:</strong> ' . htmlspecialchars($currentDates) . '</p>'
                . '<p><strong>New dates:</strong> ' . htmlspecialchars($newDates) . '</p>'
                . '<p>Your reservation has been updated with the new dates.</p>'
                . '<p>Thank you,<br/>Shelton Resort</p>';
            $html = renderEmailTemplate('Reschedule approved', $html, ['preheader' => 'Your reschedule request has been approved']);
            $alt = 'Your reschedule request has been approved. New dates: ' . $newDates;
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
