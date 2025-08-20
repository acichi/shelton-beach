<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	// CSRF validation for form or JSON
	$csrf = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) { echo json_encode(["success" => false, "message" => "Invalid CSRF token."]); exit; }
	$id = intval($_POST['id']);
	$name = trim($_POST['name']);
	$details = trim($_POST['details']);
	$status = $_POST['status'];
	$price = floatval($_POST['price']);
	$pin_x = floatval($_POST['pin_x']);
	$pin_y = floatval($_POST['pin_y']);

	// Check if facility exists
	$checkStmt = $conn->prepare("SELECT id, image FROM facility WHERE id = ?");
	if (!$checkStmt) {
		echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
		exit;
	}
	
	$checkStmt->bind_param("i", $id);
	$checkStmt->execute();
	$result = $checkStmt->get_result();
	
	if ($result->num_rows === 0) {
		echo json_encode(["success" => false, "message" => "Facility not found"]);
		exit;
	}
	
	$facility = $result->fetch_assoc();
	$checkStmt->close();

	$imagePath = $facility['image']; // Keep existing image by default

	// Handle image upload if provided
	if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
		if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
			// Delete old image if it exists and is different
			if ($facility['image']) {
				$oldPath = $facility['image'];
				$oldFsPath = strpos($oldPath, 'admindash/images/') === 0
					? (__DIR__ . '/' . substr($oldPath, strlen('admindash/')))
					: (strpos($oldPath, 'admindash2/images/') === 0 ? (__DIR__ . '/' . substr($oldPath, strlen('admindash2/'))) : $oldPath);
				if (file_exists($oldFsPath)) { @unlink($oldFsPath); }
			}
			$imagePath = 'admindash/images/' . $uniqueName;
		} else {
			echo json_encode(["success" => false, "message" => "Failed to move uploaded image."]);
			exit;
		}
	}

	// Update facility
	$stmt = $conn->prepare("UPDATE facility SET name = ?, pin_x = ?, pin_y = ?, details = ?, status = ?, price = ?, image = ?, date_updated = NOW() WHERE id = ?");

	if (!$stmt) {
		echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
		exit;
	}

	$stmt->bind_param("sddssdsi", $name, $pin_x, $pin_y, $details, $status, $price, $imagePath, $id);

	if ($stmt->execute()) {
		echo json_encode(["success" => true, "message" => "Facility updated successfully"]);
	} else {
		echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
	}

	$stmt->close();
	$conn->close();
} else {
	echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>


