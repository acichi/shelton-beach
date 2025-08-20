<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }

$csrf = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) { echo json_encode(['success'=>false,'message'=>'Invalid CSRF token']); exit; }

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }

$description = isset($_POST['description']) ? trim($_POST['description']) : null;
$location = null;

if (!empty($_FILES['image']['name'])) {
	$dir = '../uploads/gallery/';
	if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
	$base = basename($_FILES['image']['name']);
	$name = time() . '_' . bin2hex(random_bytes(6)) . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $base);
	$target = $dir . $name;
	// size and MIME checks
	if (($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) { echo json_encode(['success'=>false,'message'=>'Image too large (max 5MB)']); exit; }
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime = $finfo->file($_FILES['image']['tmp_name']);
	if (strpos((string)$mime, 'image/') !== 0) { echo json_encode(['success'=>false,'message'=>'Invalid image content type']); exit; }
	if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
		echo json_encode(['success'=>false,'message'=>'Upload failed']);
		exit;
	}
	$location = 'uploads/gallery/' . $name;
}

$cols = [];
$types = '';
$vals = [];
if ($description !== null) { $cols[] = 'description = ?'; $types .= 's'; $vals[] = $description; }
if ($location !== null) { $cols[] = 'location = ?'; $types .= 's'; $vals[] = $location; }
if (!$cols) { echo json_encode(['success'=>false,'message'=>'Nothing to update']); exit; }

$sql = 'UPDATE gallery SET ' . implode(', ', $cols) . ' WHERE id = ?';
$types .= 'i';
$vals[] = $id;

$stmt = $conn->prepare($sql);
if ($stmt) {
	$stmt->bind_param($types, ...$vals);
	$ok = $stmt->execute();
	$stmt->close();
	echo json_encode(['success'=> (bool)$ok]);
} else {
	echo json_encode(['success'=>false,'message'=>'Update failed']);
}
?>



