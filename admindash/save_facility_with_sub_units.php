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

    // Get main facility data
    $name = trim($_POST['name']);
    $details = trim($_POST['details']);
    $status = $_POST['status'];
    $price = floatval($_POST['price']);
    $pin_x = floatval($_POST['pin_x']);
    $pin_y = floatval($_POST['pin_y']);
    $facility_type = trim($_POST['facility_type'] ?? 'other');

    // Check if sub-units should be created
    $subUnitsData = $_POST['sub_units'] ?? '';
    $subUnits = [];
    if ($subUnitsData) {
        $subUnits = json_decode($subUnitsData, true);
        if (!is_array($subUnits)) {
            $subUnits = [];
        }
    }

    // Validation
    if (empty($name)) {
        echo json_encode(["success" => false, "message" => "Facility name is required."]);
        exit;
    }

    if ($price < 0) {
        echo json_encode(["success" => false, "message" => "Price cannot be negative."]);
        exit;
    }

    // Check if facility name already exists
    $nameCheckStmt = $conn->prepare("SELECT facility_id FROM facility_tbl WHERE facility_name = ?");
    if (!$nameCheckStmt) {
        echo json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }
    
    $nameCheckStmt->bind_param("s", $name);
    $nameCheckStmt->execute();
    $nameResult = $nameCheckStmt->get_result();
    
    if ($nameResult->num_rows > 0) {
        echo json_encode(["success" => false, "message" => "A facility with this name already exists."]);
        exit;
    }
    $nameCheckStmt->close();

    // Handle image upload
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

    if (($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
        echo json_encode(["success" => false, "message" => "Image too large (max 5MB)."]);
        exit;
    }

    if (!is_dir($targetDir)) { 
        @mkdir($targetDir, 0777, true); 
    }
    
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        echo json_encode(["success" => false, "message" => "Failed to move uploaded image."]);
        exit;
    }

    // Store web-relative path
    $relativePath = 'admindash/images/' . $uniqueName;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert main facility
        $stmt = $conn->prepare("INSERT INTO facility_tbl (facility_name, facility_pin_x, facility_pin_y, facility_details, facility_status, facility_price, facility_image, facility_type, facility_added) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sddssds", $name, $pin_x, $pin_y, $details, $status, $price, $relativePath, $facility_type);

        if (!$stmt->execute()) {
            throw new Exception("Database error: " . $stmt->error);
        }

        $facilityId = $conn->insert_id;
        $stmt->close();

        // Create sub-units if requested
        $subUnitsCreated = 0;
        if (!empty($subUnits)) {
            // First, ensure sub_unit_tbl exists
            $createTableSQL = "
                CREATE TABLE IF NOT EXISTS `sub_unit_tbl` (
                    `sub_unit_id` int(11) NOT NULL AUTO_INCREMENT,
                    `facility_id` int(11) NOT NULL,
                    `sub_unit_name` varchar(100) NOT NULL,
                    `sub_unit_type` enum('table','room','cottage','area','cabana','pavilion') NOT NULL DEFAULT 'table',
                    `sub_unit_capacity` int(11) NOT NULL DEFAULT 4,
                    `sub_unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
                    `sub_unit_status` enum('Available','Unavailable','Maintenance','Reserved') NOT NULL DEFAULT 'Available',
                    `is_available` tinyint(1) NOT NULL DEFAULT 1,
                    `sub_unit_details` text DEFAULT NULL,
                    `sub_unit_added` timestamp NOT NULL DEFAULT current_timestamp(),
                    `sub_unit_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`sub_unit_id`),
                    KEY `fk_sub_unit_facility` (`facility_id`),
                    CONSTRAINT `fk_sub_unit_facility` FOREIGN KEY (`facility_id`) REFERENCES `facility_tbl` (`facility_id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ";
            
            if (!$conn->query($createTableSQL)) {
                throw new Exception("Failed to create sub_unit_tbl: " . $conn->error);
            }

            // Insert sub-units
            $subUnitStmt = $conn->prepare("INSERT INTO sub_unit_tbl (facility_id, sub_unit_name, sub_unit_type, sub_unit_capacity, sub_unit_price, sub_unit_details) VALUES (?, ?, ?, ?, ?, ?)");

            if (!$subUnitStmt) {
                throw new Exception("Sub-unit prepare failed: " . $conn->error);
            }

            foreach ($subUnits as $subUnit) {
                $subUnitStmt->bind_param("issids", 
                    $facilityId, 
                    $subUnit['name'], 
                    $subUnit['type'], 
                    $subUnit['capacity'], 
                    $subUnit['price'], 
                    $subUnit['details']
                );

                if (!$subUnitStmt->execute()) {
                    throw new Exception("Failed to create sub-unit: " . $subUnitStmt->error);
                }
                $subUnitsCreated++;
            }
            $subUnitStmt->close();
        }

        // Commit transaction
        $conn->commit();

        $message = "Facility created successfully";
        if ($subUnitsCreated > 0) {
            $message .= " with {$subUnitsCreated} sub-units";
        }

        echo json_encode([
            "success" => true, 
            "message" => $message,
            "facility_id" => $facilityId,
            "sub_units_created" => $subUnitsCreated
        ]);

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }

    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
?>
