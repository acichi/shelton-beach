<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

function respond($ok, $msg){
	if (!headers_sent()) header('Content-Type: application/json');
	echo json_encode(['success'=>$ok,'message'=>$msg]); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { respond(false, 'Invalid request'); }

// CSRF validation
$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) { respond(false, 'Invalid CSRF token'); }

$userId = (int)($_SESSION['user']['id'] ?? 0);
$current = $_POST['current'] ?? '';
$new = $_POST['new'] ?? '';
$confirm = $_POST['confirm'] ?? '';

if ($new === '' || $new !== $confirm) { respond(false, 'Passwords do not match'); }
if (strlen($new) < 8) { respond(false, 'Password must be at least 8 characters'); }

$stmt = $conn->prepare("SELECT customer_pass AS password FROM customer_tbl WHERE customer_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
if (!$row || !password_verify($current, $row['password'])) { respond(false, 'Current password is incorrect'); }

$hash = password_hash($new, PASSWORD_DEFAULT);
$u = $conn->prepare("UPDATE customer_tbl SET customer_pass=? WHERE customer_id=?");
$u->bind_param('si', $hash, $userId);
$ok = $u->execute();
$u->close();
$conn->close();

respond($ok, $ok ? 'Password updated' : 'Failed to update');


