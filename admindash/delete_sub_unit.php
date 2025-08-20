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

    // Validation
    if ($sub_unit_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid sub-unit ID."]);
        exit;
    }

    // Check if sub-unit exists
    $checkStmt = $conn->prepare("SELECT sub_unit_name FROM sub_unit_tbl WHERE sub_unit_id = ?");
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
    
    $checkStmt->close();

    // Check if sub-unit has active reservations
    $reservationCheckStmt = $conn->prepare("SELECT COUNT(*) as count FROM reservation_tbl WHERE reservation_facility = ? AND reservation_status IN ('Confirmed', 'Pending')");
    if (!$reservationCheckStmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }
    
    // Get sub-unit name for reservation check
    $nameStmt = $conn->prepare("SELECT sub_unit_name FROM sub_unit_tbl WHERE sub_unit_id = ?");
    $nameStmt->bind_param("i", $sub_unit_id);
    $nameStmt->execute();
    $nameResult = $nameStmt->get_result();
    $subUnitName = $nameResult->fetch_assoc()['sub_unit_name'];
    $nameStmt->close();
    
    $reservationCheckStmt->bind_param("s", $subUnitName);
    $reservationCheckStmt->execute();
    $reservationResult = $reservationCheckStmt->get_result();
    $reservationCount = $reservationResult->fetch_assoc()['count'];
    $reservationCheckStmt->close();
    
    if ($reservationCount > 0) {
        echo json_encode(["success" => false, "message" => "Cannot delete sub-unit with active reservations. Please cancel or complete all reservations first."]);
        exit;
    }

    // Delete sub-unit
    $stmt = $conn->prepare("DELETE FROM sub_unit_tbl WHERE sub_unit_id = ?");

    if (!$stmt) {
        echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
        exit;
    }

    $stmt->bind_param("i", $sub_unit_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Sub-unit deleted successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Database error: " . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
