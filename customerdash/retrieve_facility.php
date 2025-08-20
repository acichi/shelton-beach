<?php
require '../properties/connection.php';
header('Content-Type: application/json');

// Fetch facilities with their sub-units for customer booking
$facilities = [];

// First, get all facilities
$facilitySql = "SELECT 
    facility_id AS id, 
    facility_name AS name, 
    facility_type AS type,
    facility_capacity AS capacity,
    facility_details AS details, 
    facility_status AS status, 
    facility_price AS price, 
    facility_image AS image
FROM facility_tbl 
WHERE facility_status = 'Available'
ORDER BY facility_type, facility_name";

$facilityResult = $conn->query($facilitySql);

if ($facilityResult) {
    while ($facility = $facilityResult->fetch_assoc()) {
        // Get sub-units for this facility
        $subUnitSql = "SELECT 
            sub_unit_id AS id,
            sub_unit_name AS name,
            sub_unit_type AS type,
            sub_unit_capacity AS capacity,
            sub_unit_price AS price,
            sub_unit_status AS status,
            sub_unit_details AS details,
            is_available
        FROM sub_unit_tbl 
        WHERE facility_id = ? AND is_available = TRUE AND sub_unit_status = 'Available'
        ORDER BY sub_unit_name";
        
        $subUnitStmt = $conn->prepare($subUnitSql);
        $subUnitStmt->bind_param("i", $facility['id']);
        $subUnitStmt->execute();
        $subUnitResult = $subUnitStmt->get_result();
        
        $subUnits = [];
        while ($subUnit = $subUnitResult->fetch_assoc()) {
            // Add pricing information based on sub-unit type
            $subUnit['pricing_info'] = getPricingInfo($subUnit['type'], $subUnit['price']);
            $subUnits[] = $subUnit;
        }
        $subUnitStmt->close();
        
        // If facility has sub-units, add them to the list
        if (!empty($subUnits)) {
            foreach ($subUnits as $subUnit) {
                $subUnit['facility_name'] = $facility['name'];
                $subUnit['facility_type'] = $facility['type'];
                $subUnit['facility_image'] = $facility['image'];
                $facilities[] = $subUnit;
            }
        } else {
            // If no sub-units, add the facility itself (for backward compatibility)
            $facility['pricing_info'] = getPricingInfo($facility['type'], $facility['price']);
            $facility['facility_name'] = $facility['name'];
            $facility['facility_type'] = $facility['type'];
            $facilities[] = $facility;
        }
    }
}

echo json_encode($facilities);

function getPricingInfo($type, $basePrice) {
    if ($type === 'room') {
        return [
            'day_use' => [
                '4_hours' => round($basePrice * 0.5, 2),
                '8_hours' => round($basePrice * 0.8, 2),
                '12_hours' => round($basePrice * 0.9, 2),
                '24_hours' => $basePrice
            ],
            'overnight' => [
                'per_day' => $basePrice
            ],
            'can_overnight' => true
        ];
    } else {
        // Tables, cottages, and areas - day-use only
        return [
            'day_use' => [
                '4_hours' => round($basePrice * 0.5, 2),
                '8_hours' => round($basePrice * 0.8, 2),
                '12_hours' => round($basePrice * 0.9, 2),
                '24_hours' => $basePrice
            ],
            'can_overnight' => false
        ];
    }
}
?>


