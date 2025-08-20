<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }

$description = trim($_POST['description'] ?? '');
if ($description === '' || empty($_FILES['image']['name'])) { echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }

$dir = '../uploads/gallery/';
if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
$base = basename($_FILES['image']['name']);
$name = time() . '_' . bin2hex(random_bytes(6)) . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $base);
$target = $dir . $name;

// Size limit and MIME validation
if (($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) { echo json_encode(['success'=>false,'message'=>'Image too large (max 5MB)']); exit; }
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['image']['tmp_name']);
if (strpos((string)$mime, 'image/') !== 0) { echo json_encode(['success'=>false,'message'=>'Invalid image content type']); exit; }

if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
	echo json_encode(['success'=>false,'message'=>'Upload failed']);
	exit;
}

$rel = 'uploads/gallery/' . $name;

$stmt = $conn->prepare("INSERT INTO gallery (description, location, date_added) VALUES (?, ?, NOW())");
if (!$stmt) { echo json_encode(['success'=>false,'message'=>'Prepare failed']); exit; }
$stmt->bind_param('ss', $description, $rel);
if ($stmt->execute()) {
	echo json_encode(['success'=>true,'id'=>$stmt->insert_id,'location'=>$rel]);
} else {
	echo json_encode(['success'=>false,'message'=>'Insert failed']);
}
?>



