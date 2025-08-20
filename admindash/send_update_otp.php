<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
require_once __DIR__ . '/../properties/mailer.php';
require_once __DIR__ . '/../properties/email_template.php';
require_once __DIR__ . '/../Login/send_otp.php';
require_once __DIR__ . '/../properties/activity_log.php';

header('Content-Type: application/json');

function out($ok, $msg){ echo json_encode(['success'=>$ok,'message'=>$msg]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Invalid request');

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) out(false, 'Invalid CSRF token');

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) out(false, 'Not authenticated');

// Fetch from admin table to avoid collisions and ensure correct contact info
$q = $conn->prepare("SELECT CONCAT(COALESCE(admin_fname,''),' ',COALESCE(admin_lname,'')) AS fullname, admin_email AS email, admin_number AS number, admin_user AS username FROM admin_tbl WHERE admin_id = ? LIMIT 1");
$q->bind_param('i', $userId);
$q->execute();
$u = $q->get_result()->fetch_assoc();
$q->close();
if (!$u) out(false, 'User not found');

$channel = strtolower(trim($_POST['channel'] ?? 'email'));
if (!in_array($channel, ['email','phone'], true)) { $channel = 'email'; }

$code = (string)rand(100000, 999999);
$_SESSION['admin_update_otp'] = $code;
$_SESSION['admin_update_otp_expires'] = time() + 300;

@($conn->query("CREATE TABLE IF NOT EXISTS `otp` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `otp` varchar(6) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `status` enum('pending','verified','expired') DEFAULT 'pending',
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"));

// Ensure notifications table exists (idempotent)
@($conn->query("CREATE TABLE IF NOT EXISTS `notifications_tbl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `scope` enum('customer','admin','system') NOT NULL DEFAULT 'admin',
  `type` varchar(50) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text DEFAULT NULL,
  `channel` enum('email','sms','system') NOT NULL DEFAULT 'system',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_scope_read` (`user_id`,`scope`,`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"));

$ins = $conn->prepare('INSERT INTO otp (otp, user_email, status, date_created, date_updated) VALUES (?, ?, "pending", NOW(), NOW())');
if ($ins) { $ins->bind_param('ss', $code, $u['email']); $ins->execute(); $ins->close(); }

if ($channel === 'phone' && !empty($u['number'])) {
    $msg = "Your Shelton admin verification code is: $code. This will expire in 5 minutes.";
    $res = sendSMS([$u['number']], $msg);
    if (($res['status_code'] ?? 0) >= 200 && ($res['status_code'] ?? 0) < 300) {
        $note = $conn->prepare("INSERT INTO notifications_tbl (user_id, scope, type, title, message, channel) VALUES (?, 'admin', 'otp', 'OTP sent', ?, 'sms')");
        if ($note) { $msgText = 'We sent an OTP to your phone ' . (string)$u['number']; $note->bind_param('is', $userId, $msgText); $note->execute(); $note->close(); }
        logActivity($conn, $userId, 'admin', 'otp_send', 'OTP sent via SMS');
        out(true, 'OTP sent via SMS');
    } else {
        $channel = 'email';
    }
}

$subject = 'Your admin verification code';
$body    = '<p>Hello ' . htmlspecialchars($u['fullname'] ?? $u['username'] ?? 'Admin') . ',</p>' .
           '<p>Your verification code is:</p>' .
           '<p style="font-size:20px;font-weight:bold;letter-spacing:2px;">' . $code . '</p>' .
           '<p>This code expires in 5 minutes.</p>' .
           '<p>— Shelton</p>';
$html = renderEmailTemplate('Verification code', $body, ['preheader' => 'Verification code for admin update']);
$alt  = "Your verification code is: $code";
$mail = sendEmail($u['email'], $subject, $html, $alt);

if ($mail['success'] ?? false) {
    $note2 = $conn->prepare("INSERT INTO notifications_tbl (user_id, scope, type, title, message, channel) VALUES (?, 'admin', 'otp', 'OTP sent', ?, 'email')");
    if ($note2) { $msgText2 = 'We sent an OTP to your email ' . (string)$u['email']; $note2->bind_param('is', $userId, $msgText2); $note2->execute(); $note2->close(); }
    logActivity($conn, $userId, 'admin', 'otp_send', 'OTP sent via Email');
    out(true, 'OTP sent via Email');
}
out(false, 'Failed to send OTP' . (isset($mail['error']) && $mail['error'] ? ': ' . $mail['error'] : ''));

?>

