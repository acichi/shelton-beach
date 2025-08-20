<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF validation
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) { 
        echo json_encode(["success" => false, "message" => "Invalid CSRF token."]); 
        exit; 
    }

    $facility_id = intval($_POST['facility_id'] ?? 0);
    $sub_unit_name = trim($_POST['sub_unit_name'] ?? '');
    $sub_unit_type = trim($_POST['sub_unit_type'] ?? 'table');
    $sub_unit_capacity = intval($_POST['sub_unit_capacity'] ?? 4);
    $sub_unit_price = floatval($_POST['sub_unit_price'] ?? 0.00);
    $sub_unit_details = trim($_POST['sub_unit_details'] ?? '');

    // Validation
    if ($facility_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid facility ID."]);
        exit;
    }

    if (empty($sub_unit_name)) {
        echo json_encode(["success" => false, "message" => "Sub-unit name is required."]);
        exit;
    }

    if ($sub_unit_capacity < 1 || $sub_unit_capacity > 50) {
        echo json_encode(["success" => false, "message" => "Capacity must be between 1 and 50 people."]);
        exit;
    }

    if ($sub_unit_price < 0) {
        echo json_encode(["success" => false, "message" => "Price cannot be negative."]);
        exit;
    }

    // Check if facility exists
    $checkStmt = $conn->prepare("SELECT facility_id FROM facility_tbl WHERE facility_id = ?");
    if (!$checkStmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }
    
    $checkStmt->bind_param("i", $facility_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Facility not found."]);
        exit;
    }
    $checkStmt->close();

    // Check if sub-unit name already exists in this facility
    $nameCheckStmt = $conn->prepare("SELECT sub_unit_id FROM sub_unit_tbl WHERE facility_id = ? AND sub_unit_name = ?");
    if (!$nameCheckStmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }
    
    $nameCheckStmt->bind_param("is", $facility_id, $sub_unit_name);
    $nameCheckStmt->execute();
    $nameResult = $nameCheckStmt->get_result();
    
    if ($nameResult->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "A sub-unit with this name already exists in this facility."]);
        exit;
    }
    $nameCheckStmt->close();

    // Insert sub-unit
    $stmt = $conn->prepare("INSERT INTO sub_unit_tbl (facility_id, sub_unit_name, sub_unit_type, sub_unit_capacity, sub_unit_price, sub_unit_details) VALUES (?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("issids", $facility_id, $sub_unit_name, $sub_unit_type, $sub_unit_capacity, $sub_unit_price, $sub_unit_details);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Sub-unit created successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
