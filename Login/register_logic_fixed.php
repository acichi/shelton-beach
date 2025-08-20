<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log the start of the process
error_log("Registration process started");

require '../properties/connection.php';
require '../properties/sweetalert.php';
require_once __DIR__ . '/../properties/mailer.php';
require_once __DIR__ . '/../properties/email_template.php';
require_once __DIR__ . '/send_otp.php';

header('Content-Type: application/json');

function sanitize($data) {
    return htmlspecialchars(trim($data));
}

/**
 * Normalize Philippine phone numbers to E.164-like format used by the app
 * Examples:
 *  - 09171234567   -> +639171234567
 *  - 9171234567    -> +639171234567
 *  - 639171234567  -> +639171234567
 *  - +63 917-123-4567 -> +639171234567
 */
function normalize_phone($input) {
    $raw = trim((string)$input);
    // Keep plus for the first char if present, strip other non-digits
    $raw = preg_replace('/\s+/', '', $raw);
    $hasPlus = substr($raw, 0, 1) === '+';
    $digits = preg_replace('/\D+/', '', $raw);
    if ($hasPlus && str_starts_with($digits, '63')) {
        return '+'.$digits;
    }
    if (str_starts_with($digits, '0')) {
        // Local 0-prefixed mobile
        return '+63' . substr($digits, 1);
    }
    if (str_starts_with($digits, '63')) {
        return '+'.$digits;
    }
    if (str_starts_with($digits, '9')) {
        // 9xxxxxxxxx without leading 0 -> assume PH mobile
        return '+63'.$digits;
    }
    // Fallback: if we have 10-15 digits, just prefix plus
    return ($digits !== '') ? '+'.$digits : '';
}

// Log POST data
error_log("POST data: " . print_r($_POST, true));

// Validate required fields
$name     = sanitize($_POST['name'] ?? '');
$email    = sanitize($_POST['email'] ?? '');
$username = sanitize($_POST['user'] ?? '');
$password = $_POST['password'] ?? '';
$mobile   = sanitize($_POST['mobile'] ?? '');
// Optional profile fields
$address  = sanitize($_POST['address'] ?? '');
$genderIn = sanitize($_POST['gender'] ?? '');
// Normalize gender early for consistency in session
$g = strtolower(trim($genderIn));
if ($g === 'male' || $g === 'm') { $genderIn = 'Male'; }
elseif ($g === 'female' || $g === 'f') { $genderIn = 'Female'; }
elseif ($g === 'other') { $genderIn = 'Other'; }
else { $genderIn = 'Not specified'; }

$otp_delivery = strtolower(sanitize($_POST['otp_delivery'] ?? 'phone'));
if (!in_array($otp_delivery, ['phone','email'], true)) { $otp_delivery = 'phone'; }

// Log sanitized data
error_log("Sanitized data - Name: $name, Email: $email, Username: $username, Mobile: $mobile");

// Validate password match
$confirm_password = $_POST['confirm_password'] ?? '';
if ($password !== $confirm_password) {
    error_log("Password mismatch error");
    echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
    exit;
}

// Validate password strength
if (strlen($password) < 6) {
    error_log("Password too short error");
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

// Normalize and format mobile number early for duplicate checks
$normalized_mobile = normalize_phone($mobile);

// Check if username/email/phone already exists against base tables
$check = $conn->prepare("SELECT 1 FROM (\n  SELECT customer_email AS email, customer_user AS username, customer_number AS `number` FROM customer_tbl\n  UNION ALL\n  SELECT admin_email AS email, admin_user AS username, admin_number AS `number` FROM admin_tbl\n) u WHERE u.email = ? OR u.username = ? OR u.`number` = ? LIMIT 1");
if (!$check) {
    error_log('Prepare failed for duplicate check: ' . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
    exit;
}
$check->bind_param("sss", $email, $username, $normalized_mobile);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    error_log("Duplicate user error");
    $check->close();
    echo json_encode(['success' => false, 'message' => 'Username, email, or phone number already exists']);
    exit;
}

// Use normalized mobile number consistently
$formatted_number = $normalized_mobile;
error_log("Formatted mobile: $formatted_number");

// Generate OTP
$otp = rand(100000, 999999);
error_log("Generated OTP: $otp");

// Store registration data in session (include provided address/gender)
$_SESSION['registration'] = [
    'name'     => $name,
    'email'    => $email,
    'user'     => $username,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'mobile'   => $formatted_number,
    'address'  => ($address !== '' ? $address : 'Not provided'),
    'gender'   => $genderIn,
    'otp_delivery' => $otp_delivery
];

$_SESSION['otp'] = $otp;
error_log("Session data stored");

// Check if OTP table exists and create if needed (base table name otp_tbl)
$tableCheck = $conn->query("SHOW TABLES LIKE 'otp_tbl'");
if ($tableCheck->num_rows == 0) {
    error_log("OTP base table does not exist, creating it");
    $createTable = "CREATE TABLE IF NOT EXISTS `otp_tbl` (
        `otp_id` int(11) NOT NULL AUTO_INCREMENT,
        `otp_code` varchar(6) NOT NULL,
        `customer_email` varchar(255) NOT NULL,
        `status` enum('pending','verified','expired') DEFAULT 'pending',
        `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `date_updated` datetime DEFAULT NULL,
        PRIMARY KEY (`otp_id`),
        KEY `idx_customer_email` (`customer_email`),
        KEY `idx_status_email` (`status`,`customer_email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($createTable)) {
        error_log("OTP table created successfully");
    } else {
        error_log("Failed to create OTP table: " . $conn->error);
    }
}

// Persist OTP to database (status: pending) using base table
$otpInsert = $conn->prepare("INSERT INTO otp_tbl (otp_code, status, date_created, date_updated, customer_email) VALUES (?, 'pending', NOW(), NOW(), ?)");
if ($otpInsert) {
    $otpString = (string)$otp;
    $otpInsert->bind_param("ss", $otpString, $email);
    if ($otpInsert->execute()) {
        error_log("OTP inserted into database successfully");
    } else {
        error_log("Failed to insert OTP: " . $otpInsert->error);
    }
    $otpInsert->close();
} else {
    error_log("Failed to prepare OTP insert statement: " . $conn->error);
}

// Send OTP via selected delivery method
if ($otp_delivery === 'phone') {
    error_log("Attempting to send OTP via SMS");
    $message = "Your Shelton Resort OTP is: $otp. Please don't share this with anyone.";
    $sms_result = sendSMS([$formatted_number], $message);
    error_log("SMS result: " . print_r($sms_result, true));
    if (($sms_result['status_code'] ?? 0) == 200 || ($sms_result['status_code'] ?? 0) == 201) {
        echo json_encode(['success' => true, 'message' => 'OTP sent via SMS', 'delivery' => 'phone']);
    } else {
        error_log("SMS failed with status: " . ($sms_result['status_code'] ?? ''));
        // Try email as fallback
        $emailSubject = 'Your Shelton Resort verification code';
        $emailHtml = '<p>Hello ' . htmlspecialchars($name) . ',</p>' .
                     '<p>Your verification code is:</p>' .
                     '<p style="font-size:20px;font-weight:bold;letter-spacing:2px;">' . $otp . '</p>' .
                     '<p>If you did not request this, you can ignore this email.</p>' .
                     '<p>— Shelton Resort</p>';
        $emailHtml = renderEmailTemplate('Verification code', $emailHtml, ['preheader' => 'Your Shelton Resort verification code']);
        $emailAlt  = "Your Shelton Resort verification code is: $otp";
        $fallback_mail = sendEmail($email, $emailSubject, $emailHtml, $emailAlt);
        error_log('Fallback email OTP send result: ' . json_encode($fallback_mail));
        if ($fallback_mail['success'] ?? false) {
            echo json_encode(['success' => true, 'message' => 'OTP sent via Email', 'delivery' => 'email']);
        } else {
            echo json_encode(['success' => true, 'message' => 'OTP generated, but delivery failed. Please try resend.', 'delivery' => 'unknown']);
        }
    }
} else {
    error_log("Attempting to send OTP via Email");
    $emailSubject = 'Your Shelton Resort verification code';
    $emailHtml = '<p>Hello ' . htmlspecialchars($name) . ',</p>' .
                 '<p>Your verification code is:</p>' .
                 '<p style="font-size:20px;font-weight:bold;letter-spacing:2px;">' . $otp . '</p>' .
                 '<p>If you did not request this, you can ignore this email.</p>' .
                 '<p>— Shelton Resort</p>';
    $emailHtml = renderEmailTemplate('Verification code', $emailHtml, ['preheader' => 'Your Shelton Resort verification code']);
    $emailAlt  = "Your Shelton Resort verification code is: $otp";
    $mail_result = sendEmail($email, $emailSubject, $emailHtml, $emailAlt);
    error_log('Email OTP send result: ' . json_encode($mail_result));
    if ($mail_result['success'] ?? false) {
        echo json_encode(['success' => true, 'message' => 'OTP sent via Email', 'delivery' => 'email']);
    } else {
        // Try SMS as fallback
        $message = "Your Shelton Resort OTP is: $otp. Please don't share this with anyone.";
        $sms_result = sendSMS([$formatted_number], $message);
        error_log('Fallback SMS OTP result: ' . json_encode($sms_result));
        if (($sms_result['status_code'] ?? 0) == 200 || ($sms_result['status_code'] ?? 0) == 201) {
            echo json_encode(['success' => true, 'message' => 'OTP sent via SMS', 'delivery' => 'phone']);
        } else {
            echo json_encode(['success' => true, 'message' => 'OTP generated, but delivery failed. Please try resend.', 'delivery' => 'unknown']);
        }
    }
}

$check->close();
$conn->close();
error_log("Registration process completed");
?>
