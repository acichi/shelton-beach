<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$input = json_decode(file_get_contents('php://input'), true);
	
	// CSRF for JSON
	$csrf = $_POST['csrf_token'] ?? ($input['csrf_token'] ?? '');
	if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) { echo json_encode(["success" => false, "message" => "Invalid CSRF token"]); exit; }
	
	if (!isset($input['id'])) {
		echo json_encode(["success" => false, "message" => "Facility ID is required"]);
		exit;
	}
	
	$id = intval($input['id']);

	// Get facility image before deletion
	$checkStmt = $conn->prepare("SELECT image FROM facility WHERE id = ?");
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

	// Delete facility from database
	$stmt = $conn->prepare("DELETE FROM facility WHERE id = ?");
	if (!$stmt) {
		echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
		exit;
	}

	$stmt->bind_param("i", $id);

	if ($stmt->execute()) {
		// Delete associated image file if it exists
		if ($facility['image']) {
			$img = $facility['image'];
			$fs = strpos($img, 'admindash/images/') === 0
				? (__DIR__ . '/' . substr($img, strlen('admindash/')))
				: (strpos($img, 'admindash2/images/') === 0 ? (__DIR__ . '/' . substr($img, strlen('admindash2/'))) : $img);
			if (file_exists($fs)) { @unlink($fs); }
		}
		
		echo json_encode(["success" => true, "message" => "Facility deleted successfully"]);
	} else {
		echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
	}

	$stmt->close();
	$conn->close();
} else {
	echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>


