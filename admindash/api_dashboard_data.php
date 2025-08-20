<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

function safe_number($value) {
	if ($value === null) return 0;
	if (is_numeric($value)) return 0 + $value;
	return 0;
}

try {
	// Stats (receipt-based)
	$stats = [
		'total_reservations' => 0,
		'total_facilities' => 0,
		'total_customers' => 0,
		'total_revenue' => 0,
		'total_feedback' => 0,
	];

	// total_reservations (confirmed receipts) — treat tiny residues as zero
	if ($res = $conn->query("SELECT COUNT(*) AS c FROM receipt WHERE balance <= 0.01")) {
		$row = $res->fetch_assoc();
		$stats['total_reservations'] = (int)($row['c'] ?? 0);
	}

	// total_facilities
	if ($res = $conn->query("SELECT COUNT(*) AS c FROM facility")) {
		$row = $res->fetch_assoc();
		$stats['total_facilities'] = (int)($row['c'] ?? 0);
	}

	// total_customers (users with role = 'customer')
	if ($res = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'customer'")) {
		$row = $res->fetch_assoc();
		$stats['total_customers'] = (int)($row['c'] ?? 0);
	}

	// total_revenue (sum of amount_paid where balance <= 0.01)
	if ($res = $conn->query("SELECT SUM(amount_paid) AS s FROM receipt WHERE balance <= 0.01")) {
		$row = $res->fetch_assoc();
		$stats['total_revenue'] = safe_number($row['s'] ?? 0);
	}

	// total_feedback (visible only)
	if ($res = $conn->query("SELECT COUNT(*) AS c FROM feedback WHERE COALESCE(is_hidden, 0) = 0")) {
		$row = $res->fetch_assoc();
		$stats['total_feedback'] = (int)($row['c'] ?? 0);
	}

	// Recent reservations (from receipt)
	$recent_reservations = [];
	if ($res = $conn->query("SELECT transaction_id AS id, reservee, facility_name, date_booked, date_checkin AS date_start, date_checkout AS date_end, payment_type, amount_paid AS amount FROM receipt WHERE balance <= 0.01 ORDER BY date_booked DESC, transaction_id DESC LIMIT 5")) {
		while ($row = $res->fetch_assoc()) {
			// normalize id to TX id for display
			$recent_reservations[] = $row;
		}
	}

	// Recent feedback (unchanged)
	$recent_feedback = [];
	if ($res = $conn->query("SELECT id, fullname, facility_name, feedback, rate, timestamp FROM feedback WHERE COALESCE(is_hidden, 0) = 0 ORDER BY timestamp DESC, id DESC LIMIT 5")) {
		while ($row = $res->fetch_assoc()) {
			$recent_feedback[] = $row;
		}
	}

	// Facilities
	$facilities = [];
	if ($res = $conn->query("SELECT id, name, details, price, status, image, date_added FROM facility ORDER BY date_added DESC, id DESC")) {
		while ($row = $res->fetch_assoc()) {
			$facilities[] = $row;
		}
	}

	// Customers (users)
	$customers = [];
	if ($res = $conn->query("SELECT id, fullname, email, number, gender, address, date_added FROM users WHERE role = 'customer' ORDER BY COALESCE(date_added, '1970-01-01') DESC, id DESC")) {
		while ($row = $res->fetch_assoc()) {
			$customers[] = $row;
		}
	}

	// Gallery
	$gallery = [];
	if ($res = $conn->query("SELECT id, description, location, date_added FROM gallery ORDER BY date_added DESC, id DESC LIMIT 12")) {
		while ($row = $res->fetch_assoc()) {
			$gallery[] = $row;
		}
	}

	echo json_encode([
		'stats' => $stats,
		'recent_reservations' => $recent_reservations,
		'recent_feedback' => $recent_feedback,
		'facilities' => $facilities,
		'customers' => $customers,
		'gallery' => $gallery,
	]);
} catch (Throwable $e) {
	http_response_code(500);
	echo json_encode(['error' => $e->getMessage()]);
}

// No need to close $conn explicitly; PHP will handle on script end
?>



