<?php
// Lightweight activity logging helper

if (!function_exists('log_activity')) {
	/**
	 * Insert an activity log entry.
	 *
	 * Expected keys in $opts:
	 * - user_id (int)
	 * - actor (string) 'admin'|'customer'|'system'
	 * - ref_reservation_id (int|null)
	 * - ref_transaction_id (string|null)
	 * - reservee (string|null)
	 * - facility_name (string|null)
	 * - amount (float|null)
	 * - metadata (array|string|null)
	 */
	function log_activity(mysqli $conn, string $eventType, string $message, array $opts = []): void {
		// Ensure table exists (idempotent)
		@($conn->query("CREATE TABLE IF NOT EXISTS `activity_log` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`event_type` varchar(50) NOT NULL,
			`user_id` int(11) DEFAULT NULL,
			`actor` enum('admin','customer','system') NOT NULL DEFAULT 'system',
			`message` varchar(255) NOT NULL,
			`ref_reservation_id` int(11) DEFAULT NULL,
			`ref_transaction_id` varchar(100) DEFAULT NULL,
			`reservee` varchar(100) DEFAULT NULL,
			`facility_name` varchar(100) DEFAULT NULL,
			`amount` decimal(12,2) DEFAULT NULL,
			`metadata` text DEFAULT NULL,
			`ip` varchar(45) DEFAULT NULL,
			`user_agent` varchar(255) DEFAULT NULL,
			`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY `idx_event_type` (`event_type`),
			KEY `idx_user_id` (`user_id`),
			KEY `idx_created_at` (`created_at`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"));

		$userId = isset($opts['user_id']) ? (int)$opts['user_id'] : null;
		$actor = isset($opts['actor']) ? (string)$opts['actor'] : 'system';
		$refReservationId = isset($opts['ref_reservation_id']) ? (int)$opts['ref_reservation_id'] : null;
		$refTransactionId = $opts['ref_transaction_id'] ?? null;
		$reservee = $opts['reservee'] ?? null;
		$facilityName = $opts['facility_name'] ?? null;
		$amount = isset($opts['amount']) ? (float)$opts['amount'] : null;
		$metadata = $opts['metadata'] ?? null;
		if (is_array($metadata)) { $metadata = json_encode($metadata); }
		$ip = $_SERVER['REMOTE_ADDR'] ?? null;
		$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

		$stmt = $conn->prepare("INSERT INTO activity_log (
			event_type, user_id, actor, message, ref_reservation_id, ref_transaction_id, reservee, facility_name, amount, metadata, ip, user_agent
		) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		if ($stmt) {
			// Bind as strings for nullable fields to avoid PHP/MySQLi null-double issues
			$stmt->bind_param(
				"sississssssss",
				$eventType,
				$userId,
				$actor,
				$message,
				$refReservationId,
				$refTransactionId,
				$reservee,
				$facilityName,
				$amount,
				$metadata,
				$ip,
				$userAgent
			);
			@$stmt->execute();
			$stmt->close();
		}
	}
}

// Backward-compatible wrapper used by parts of the app
if (!function_exists('logActivity')) {
	/**
	 * @param mysqli $conn
	 * @param int $userId
	 * @param string $actor 'admin'|'customer'|'system'
	 * @param string $eventType
	 * @param string $message
	 * @param array $extras Optional extra metadata
	 */
	function logActivity(mysqli $conn, int $userId, string $actor, string $eventType, string $message, array $extras = []): void {
		$payload = array_merge([
			'user_id' => $userId,
			'actor' => $actor,
			'metadata' => $extras,
		], $extras);
		log_activity($conn, $eventType, $message, $payload);
	}
}

?>

