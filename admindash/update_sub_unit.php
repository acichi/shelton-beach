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

    $sub_unit_id = intval($_POST['sub_unit_id'] ?? 0);
    $sub_unit_name = trim($_POST['sub_unit_name'] ?? '');
    $sub_unit_type = trim($_POST['sub_unit_type'] ?? 'table');
    $sub_unit_capacity = intval($_POST['sub_unit_capacity'] ?? 4);
    $sub_unit_price = floatval($_POST['sub_unit_price'] ?? 0.00);
    $sub_unit_status = trim($_POST['sub_unit_status'] ?? 'Available');
    $is_available = intval($_POST['is_available'] ?? 1);
    $sub_unit_details = trim($_POST['sub_unit_details'] ?? '');

    // Validation
    if ($sub_unit_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid sub-unit ID."]);
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

    // Check if sub-unit exists
    $checkStmt = $conn->prepare("SELECT sub_unit_id, facility_id FROM sub_unit_tbl WHERE sub_unit_id = ?");
    if (!$checkStmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }
    
    $checkStmt->bind_param("i", $sub_unit_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(["success" => false, "message" => "Sub-unit not found."]);
        exit;
    }
    
    $subUnit = $result->fetch_assoc();
    $facility_id = $subUnit['facility_id'];
    $checkStmt->close();

    // Check if sub-unit name already exists in this facility (excluding current sub-unit)
    $nameCheckStmt = $conn->prepare("SELECT sub_unit_id FROM sub_unit_tbl WHERE facility_id = ? AND sub_unit_name = ? AND sub_unit_id != ?");
    if (!$nameCheckStmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }
    
    $nameCheckStmt->bind_param("isi", $facility_id, $sub_unit_name, $sub_unit_id);
    $nameCheckStmt->execute();
    $nameResult = $nameCheckStmt->get_result();
    
    if ($nameResult->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "A sub-unit with this name already exists in this facility."]);
        exit;
    }
    $nameCheckStmt->close();

    // Update sub-unit
    $stmt = $conn->prepare("UPDATE sub_unit_tbl SET 
        sub_unit_name = ?, 
        sub_unit_type = ?, 
        sub_unit_capacity = ?, 
        sub_unit_price = ?, 
        sub_unit_status = ?, 
        is_available = ?, 
        sub_unit_details = ?,
        sub_unit_updated = NOW()
        WHERE sub_unit_id = ?");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("ssidssi", $sub_unit_name, $sub_unit_type, $sub_unit_capacity, $sub_unit_price, $sub_unit_status, $is_available, $sub_unit_details, $sub_unit_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Sub-unit updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
