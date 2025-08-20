<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	// CSRF validation
	$csrf = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) { echo json_encode(["success" => false, "message" => "Invalid CSRF token."]); exit; }

	$name = trim($_POST['name']);
	$details = trim($_POST['details']);
	$status = $_POST['status'];
	$price = floatval($_POST['price']);
	$pin_x = floatval($_POST['pin_x']);
	$pin_y = floatval($_POST['pin_y']);

	if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
		echo json_encode(["success" => false, "message" => "Image upload failed."]);
		exit;
	}

	$targetDir = __DIR__ . "/images/";
	$filename = basename($_FILES["image"]["name"]);
	$uniqueName = bin2hex(random_bytes(8)) . "_" . preg_replace('/[^A-Za-z0-9_.-]/', '_', $filename);
	$targetFile = $targetDir . $uniqueName;
	$imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

	$allowed = ['jpg', 'jpeg', 'png', 'gif'];
	if (!in_array($imageFileType, $allowed)) {
		echo json_encode(["success" => false, "message" => "Invalid file type."]);
		exit;
	}

	// Size limit (e.g., 5 MB)
	if (($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
		echo json_encode(["success" => false, "message" => "Image too large (max 5MB)."]);
		exit;
	}

	// MIME validation
	$finfo = new finfo(FILEINFO_MIME_TYPE);
	$mime = $finfo->file($_FILES["image"]["tmp_name"]);
	if (strpos($mime, 'image/') !== 0) {
		echo json_encode(["success" => false, "message" => "Invalid image content type."]);
		exit;
	}

	if (!is_dir($targetDir)) { @mkdir($targetDir, 0777, true); }
	if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
		echo json_encode(["success" => false, "message" => "Failed to move uploaded image."]);
		exit;
	}

	$stmt = $conn->prepare("INSERT INTO facility (name, pin_x, pin_y, details, status, price, image, date_added) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

	if (!$stmt) {
		echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
		exit;
	}

	// Store web-relative path for later rendering
	$relativePath = 'admindash/images/' . $uniqueName;
	$stmt->bind_param("sddssds", $name, $pin_x, $pin_y, $details, $status, $price, $relativePath);

	if ($stmt->execute()) {
		echo json_encode(["success" => true]);
	} else {
		echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
	}

	$stmt->close();
	$conn->close();
} else {
	echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>


