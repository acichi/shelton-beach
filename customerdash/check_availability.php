<?php
header('Content-Type: application/json');
ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();

$respond = function($ok, $extra = []){
    echo json_encode(array_merge(['success'=>$ok], $extra));
    exit;
};

register_shutdown_function(function() use ($respond){
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (ob_get_level() > 0) { ob_end_clean(); }
        $respond(false, ['message' => 'Server error. Please try again.']);
    }
});

set_error_handler(function($severity, $message, $file, $line) use ($respond){
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    require __DIR__ . '/_auth.php';
    require __DIR__ . '/../properties/connection.php';

    $facility = trim($_GET['facility_name'] ?? $_POST['facility_name'] ?? '');
    $dateStart = trim($_GET['date_start'] ?? $_POST['date_start'] ?? '');
    $dateEnd = trim($_GET['date_end'] ?? $_POST['date_end'] ?? '');
    $bookingType = trim($_GET['booking_type'] ?? $_POST['booking_type'] ?? 'day'); // Default to day-use

    if ($facility === '' || $dateStart === '' || $dateEnd === '') {
        $respond(false, ['message' => 'Missing parameters']);
    }

    // Basic sanity
    $tsStart = strtotime($dateStart);
    $tsEnd = strtotime($dateEnd);
    if (!$tsStart || !$tsEnd || $tsEnd <= $tsStart) {
        $respond(false, ['message' => 'Invalid date range']);
    }

    // Verify facility exists (base table)
    $price = 0.0; $facilityStatus = ''; $facilityType = '';
    if ($stmt = $conn->prepare('SELECT facility_price, facility_status, facility_type FROM facility_tbl WHERE facility_name = ? LIMIT 1')) {
        $stmt->bind_param('s', $facility);
        $stmt->execute();
        $stmt->bind_result($price, $facilityStatus, $facilityType);
        if (!$stmt->fetch()) {
            $stmt->close();
            $respond(false, ['message' => 'Facility not found']);
        }
        $stmt->close();
    } else {
        $respond(false, ['message' => 'Server error']);
    }

    // Check for conflicts using Confirmed reservations only
    $conflict = 0;
    if ($check = $conn->prepare('SELECT COUNT(*) FROM reservation_tbl WHERE reservation_facility = ? AND reservation_status = \'Confirmed\' AND NOT (reservation_date_end <= ? OR reservation_date_start >= ?)')) {
        $startStr = date('Y-m-d H:i:s', $tsStart);
        $endStr = date('Y-m-d H:i:s', $tsEnd);
        $check->bind_param('sss', $facility, $startStr, $endStr);
        $check->execute();
        $check->bind_result($conflict);
        $check->fetch();
        $check->close();
    }

    // Compute server-side amount based on booking type
    $amount = 0.0;
    $hours = 0;
    $days = 0;
    
    if ($bookingType === 'day') {
        // Day-use pricing: Calculate by hours
        $hours = (int)ceil(($tsEnd - $tsStart) / 3600); // Convert seconds to hours
        if ($hours < 1) { $hours = 1; }
        
        // Flexible hourly pricing tiers
        if ($hours <= 4) {
            $amount = $price * 0.5; // 4 hours = 50% of daily rate
        } elseif ($hours <= 8) {
            $amount = $price * 0.8; // 8 hours = 80% of daily rate
        } elseif ($hours <= 12) {
            $amount = $price * 0.9; // 12 hours = 90% of daily rate
        } else {
            $amount = $price; // More than 12 hours = full daily rate
        }
        
        $days = 0; // Not applicable for day-use
    } else {
        // Overnight pricing: Calculate by days (existing logic)
        $startDateOnly = date('Y-m-d', $tsStart);
        $endDateOnly = date('Y-m-d', $tsEnd);
        
        if (class_exists('DateTime')) {
            $startDateTime = new DateTime($startDateOnly);
            $endDateTime = new DateTime($endDateOnly);
            $days = $endDateTime->diff($startDateTime)->days;
        } else {
            // Fallback for older PHP versions
            $startTs = strtotime($startDateOnly);
            $endTs = strtotime($endDateOnly);
            $days = round(($endTs - $startTs) / (24 * 60 * 60));
        }
        
        // If same day, count as 1 day
        if ($days === 0) { $days = 1; }
        
        $amount = (float)$days * (float)$price;
        $hours = 0; // Not applicable for overnight
    }

    $respond(true, [
        'available' => ($conflict === 0),
        'booking_type' => $bookingType,
        'facility_type' => $facilityType,
        'hours' => $hours,
        'days' => $days,
        'price' => (float)$price,
        'amount' => (float)$amount,
        'facility_status' => $facilityStatus
    ]);
} catch (Throwable $e) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    $respond(false, ['message' => 'Server error.']);
}

?>


