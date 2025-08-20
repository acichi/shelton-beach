<?php

// Centralized helpers to keep facility availability in sync with bookings

if (!function_exists('setFacilityUnavailable')) {
	function setFacilityUnavailable(mysqli $conn, string $facilityName): void {
		if ($facilityName === '') { return; }
		if ($stmt = $conn->prepare("UPDATE facility_tbl SET facility_status = 'Unavailable' WHERE facility_name = ?")) {
			$stmt->bind_param('s', $facilityName);
			@$stmt->execute();
			$stmt->close();
		}
	}
}

if (!function_exists('setFacilityAvailable')) {
	function setFacilityAvailable(mysqli $conn, string $facilityName): void {
		if ($facilityName === '') { return; }
		if ($stmt = $conn->prepare("UPDATE facility_tbl SET facility_status = 'Available' WHERE facility_name = ?")) {
			$stmt->bind_param('s', $facilityName);
			@$stmt->execute();
			$stmt->close();
		}
	}
}

if (!function_exists('recomputeFacilityAvailability')) {
	function recomputeFacilityAvailability(mysqli $conn, string $facilityName): void {
		if ($facilityName === '') { return; }
		$hasFutureConfirmed = 0;
		if ($q = $conn->prepare("SELECT COUNT(*) FROM reservation_tbl WHERE reservation_facility = ? AND reservation_status = 'Confirmed' AND reservation_date_end > NOW()")) {
			$q->bind_param('s', $facilityName);
			if ($q->execute()) {
				$q->bind_result($hasFutureConfirmed);
				$q->fetch();
			}
			$q->close();
		}
		if ((int)$hasFutureConfirmed > 0) {
			setFacilityUnavailable($conn, $facilityName);
		} else {
			setFacilityAvailable($conn, $facilityName);
		}
	}
}

?>

