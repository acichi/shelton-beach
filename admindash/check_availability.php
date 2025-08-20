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

    if ($facility === '' || $dateStart === '' || $dateEnd === '') {
        $respond(false, ['message' => 'Missing parameters']);
    }

    // Basic sanity
    $tsStart = strtotime($dateStart);
    $tsEnd = strtotime($dateEnd);
    if (!$tsStart || !$tsEnd || $tsEnd <= $tsStart) {
        $respond(false, ['message' => 'Invalid date range']);
    }

    // Verify facility exists and get price
    $price = 0.0; $facilityStatus = '';
    if ($stmt = $conn->prepare('SELECT price, status FROM facility WHERE name = ? LIMIT 1')) {
        $stmt->bind_param('s', $facility);
        $stmt->execute();
        $stmt->bind_result($price, $facilityStatus);
        if (!$stmt->fetch()) {
            $stmt->close();
            $respond(false, ['message' => 'Facility not found']);
        }
        $stmt->close();
    } else {
        $respond(false, ['message' => 'Server error']);
    }

    // Check for conflicts against ANY overlapping receipt (blocks holds and paid)
    $conflict = 0;
    if ($check = $conn->prepare('SELECT COUNT(*) FROM receipt WHERE facility_name = ? AND NOT (date_checkout <= ? OR date_checkin >= ?)')) {
        $startStr = date('Y-m-d H:i:s', $tsStart);
        $endStr = date('Y-m-d H:i:s', $tsEnd);
        $check->bind_param('sss', $facility, $startStr, $endStr);
        $check->execute();
        $check->bind_result($conflict);
        $check->fetch();
        $check->close();
    }

    // Compute server-side amount
    $days = (int)ceil(($tsEnd - $tsStart) / 86400);
    if ($days < 1) { $days = 1; }
    $amount = (float)$days * (float)$price;

    $respond(true, [
        'available' => ($conflict === 0),
        'days' => $days,
        'amount' => $amount,
        'facility_status' => $facilityStatus,
    ]);
} catch (Throwable $e) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    $respond(false, ['message' => 'Server error.']);
}

?>


 