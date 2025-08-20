<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $facility_id = intval($_GET['facility_id'] ?? 0);
    
    if ($facility_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid facility ID."]);
        exit;
    }

    try {
        $sub_units = [];
        
        $sql = "SELECT 
            su.sub_unit_id,
            su.facility_id,
            su.sub_unit_name,
            su.sub_unit_type,
            su.sub_unit_capacity,
            su.sub_unit_price,
            su.sub_unit_status,
            su.is_available,
            su.sub_unit_details,
            su.sub_unit_added,
            su.sub_unit_updated
        FROM sub_unit_tbl su
        WHERE su.facility_id = ?
        ORDER BY su.sub_unit_name ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
            exit;
        }

        $stmt->bind_param("i", $facility_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $sub_units[] = $row;
        }

        $stmt->close();
        
        echo json_encode([
            "success" => true, 
            "sub_units" => $sub_units,
            "count" => count($sub_units)
        ]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
