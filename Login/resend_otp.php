<?php
session_start();
require '../properties/connection.php';
require_once __DIR__ . '/../properties/mailer.php';
require_once __DIR__ . '/../properties/email_template.php';
require_once __DIR__ . '/send_otp.php';

header('Content-Type: application/json');

// Check if we have the necessary data
if (!isset($_SESSION['registration'])) {
    echo json_encode(['success' => false, 'message' => 'No registration data found']);
    exit;
}

// Generate new OTP
$otp = rand(100000, 999999);

// Update session with new OTP
$_SESSION['otp'] = $otp;

// Get phone number from session
$phone = $_SESSION['registration']['mobile'] ?? '';
$userEmail = $_SESSION['registration']['email'] ?? '';
$userName  = $_SESSION['registration']['name'] ?? '';
$otpDelivery = strtolower($_SESSION['registration']['otp_delivery'] ?? 'phone');
if (!in_array($otpDelivery, ['phone','email'], true)) { $otpDelivery = 'phone'; }

// Persist OTP to database (status: pending) in base table
$otpInsert = $conn->prepare("INSERT INTO otp_tbl (otp_code, status, date_created, date_updated, customer_email) VALUES (?, 'pending', NOW(), NOW(), ?)");
if ($otpInsert) {
    $otpString = (string)$otp;
    $userEmail = $_SESSION['registration']['email'];
    $otpInsert->bind_param("ss", $otpString, $userEmail);
    $otpInsert->execute();
    $otpInsert->close();
}
$conn->close();

// Send new OTP via chosen method
if ($otpDelivery === 'phone') {
    if ($phone === '') { echo json_encode(['success'=>false,'message'=>'Missing phone']); exit; }
    $message = "Your new Shelton Resort OTP is: $otp. Please don't share this with anyone.";
    $sms_result = sendSMS([$phone], $message);
    if (($sms_result['status_code'] ?? 0) == 200 || ($sms_result['status_code'] ?? 0) == 201) {
        echo json_encode(['success' => true, 'message' => 'New OTP sent via SMS', 'delivery' => 'phone']);
    } else {
        // Fallback to email
        if ($userEmail === '') { echo json_encode(['success'=>false,'message'=>'Failed SMS and missing email']); exit; }
        $emailSubject = 'Your new Shelton Resort verification code';
        $emailHtml = '<p>Hello ' . htmlspecialchars($userName) . ',</p>' .
                     '<p>Your new verification code is:</p>' .
                     '<p style="font-size:20px;font-weight:bold;letter-spacing:2px;">' . $otp . '</p>' .
                     '<p>If you did not request this, you can ignore this email.</p>' .
                     '<p>— Shelton Resort</p>';
        $emailHtml = renderEmailTemplate('Verification code', $emailHtml, ['preheader' => 'Your new Shelton Resort verification code']);
        $emailAlt  = "Your new Shelton Resort verification code is: $otp";
        $mail_result = sendEmail($userEmail, $emailSubject, $emailHtml, $emailAlt);
        if ($mail_result['success'] ?? false) {
            echo json_encode(['success' => true, 'message' => 'New OTP sent via Email', 'delivery' => 'email']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send OTP via SMS and Email']);
        }
    }
} else {
    if ($userEmail === '') { echo json_encode(['success'=>false,'message'=>'Missing email']); exit; }
    $emailSubject = 'Your new Shelton Resort verification code';
    $emailHtml = '<p>Hello ' . htmlspecialchars($userName) . ',</p>' .
                 '<p>Your new verification code is:</p>' .
                 '<p style="font-size:20px;font-weight:bold;letter-spacing:2px;">' . $otp . '</p>' .
                 '<p>If you did not request this, you can ignore this email.</p>' .
                 '<p>— Shelton Resort</p>';
    $emailHtml = renderEmailTemplate('Verification code', $emailHtml, ['preheader' => 'Your new Shelton Resort verification code']);
    $emailAlt  = "Your new Shelton Resort verification code is: $otp";
    $mail_result = sendEmail($userEmail, $emailSubject, $emailHtml, $emailAlt);
    if ($mail_result['success'] ?? false) {
        echo json_encode(['success' => true, 'message' => 'New OTP sent via Email', 'delivery' => 'email']);
    } else {
        // Fallback to SMS
        if ($phone === '') { echo json_encode(['success'=>false,'message'=>'Failed Email and missing phone']); exit; }
        $message = "Your new Shelton Resort OTP is: $otp. Please don't share this with anyone.";
        $sms_result = sendSMS([$phone], $message);
        if (($sms_result['status_code'] ?? 0) == 200 || ($sms_result['status_code'] ?? 0) == 201) {
            echo json_encode(['success' => true, 'message' => 'New OTP sent via SMS', 'delivery' => 'phone']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send OTP via Email and SMS']);
        }
    }
}
?>
