<?php
header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/../properties/connection.php';
require_once __DIR__ . '/../properties/mailer.php';
require_once __DIR__ . '/../properties/email_template.php';
require_once __DIR__ . '/../config/env.php';

$respond = function(bool $ok, string $message, array $extra = []) {
	echo json_encode(array_merge(['success' => $ok, 'message' => $message], $extra));
	exit;
};

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
	$respond(false, 'Method not allowed');
}

// Support JSON or form-encoded
$raw = file_get_contents('php://input');
$body = [];
if ($raw !== false && $raw !== '' && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
	$body = json_decode($raw, true) ?: [];
}

$name = trim($body['name'] ?? $_POST['name'] ?? '');
$email = trim($body['email'] ?? $_POST['email'] ?? '');
$message = trim($body['message'] ?? $_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
	$respond(false, 'Please fill in all fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	$respond(false, 'Please enter a valid email address.');
}

// Ensure table exists
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS inquiries (
	 id INT AUTO_INCREMENT PRIMARY KEY,
	 name VARCHAR(255) NOT NULL,
	 email VARCHAR(255) NOT NULL,
	 message TEXT NOT NULL,
	 status ENUM('new','read') NOT NULL DEFAULT 'new',
	 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Insert inquiry
$stmt = $conn->prepare('INSERT INTO inquiries (name, email, message) VALUES (?, ?, ?)');
if (!$stmt) { $respond(false, 'Server error.'); }
$stmt->bind_param('sss', $name, $email, $message);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) { $respond(false, 'Failed to submit inquiry.'); }

// Email notification to admin
$toAdmin = (string)env('INQUIRY_TO', (string)env('SMTP_FROM', ''));
if ($toAdmin !== '') {
	$subjectA = 'New inquiry from ' . $name;
	$htmlA = '<p>You received a new inquiry via the website:</p>' .
	         '<p><strong>Name:</strong> ' . htmlspecialchars($name) . '<br/>' .
	         '<strong>Email:</strong> ' . htmlspecialchars($email) . '</p>' .
	         '<p style="white-space:pre-wrap;">' . nl2br(htmlspecialchars($message)) . '</p>' .
	         '<p>— Shelton Resort Website</p>';
	$htmlA = renderEmailTemplate($subjectA, $htmlA, ['preheader' => 'New inquiry submitted via the website']);
	$altA  = "New inquiry from $name\nEmail: $email\n\n$message";
	@sendEmail($toAdmin, $subjectA, $htmlA, $altA);
}

// Auto-reply to sender (optional but nice)
$subjectU = 'We received your inquiry';
$htmlU = '<p>Hi ' . htmlspecialchars($name) . ',</p>' .
	     '<p>Thanks for reaching out to Shelton Resort. We have received your inquiry and will get back to you within 24 hours.</p>' .
	     '<p>Your message:</p>' .
	     '<blockquote style="border-left:3px solid #7ab4a1;padding-left:10px;">' . nl2br(htmlspecialchars($message)) . '</blockquote>' .
	     '<p>Warm regards,<br/>Shelton Resort</p>';
$htmlU = renderEmailTemplate($subjectU, $htmlU, ['preheader' => 'Thanks for contacting us']);
$altU  = 'Thanks for reaching out to Shelton Resort. We have received your inquiry and will reply within 24 hours.';
@sendEmail($email, $subjectU, $htmlU, $altU);

$respond(true, 'Thank you! Your inquiry has been sent successfully. We\'ll get back to you within 24 hours.');

?>


