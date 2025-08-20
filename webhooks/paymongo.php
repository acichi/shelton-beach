<?php
// PayMongo webhook handler with optional signature verification
// Set this URL in your PayMongo Dashboard → Webhooks.

require __DIR__ . '/../properties/connection.php';
require __DIR__ . '/../config/paymongo.php';
require_once __DIR__ . '/../properties/mailer.php';
require_once __DIR__ . '/../properties/email_template.php';
require_once __DIR__ . '/../config/env.php';

header('Content-Type: application/json');

$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') { http_response_code(400); echo json_encode(['error'=>'Empty body']); exit; }

// Verify signature if PAYMONGO_WEBHOOK_SECRET is configured
if (defined('PAYMONGO_WEBHOOK_SECRET') && PAYMONGO_WEBHOOK_SECRET !== '') {
    $headerSig = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
    $expected  = base64_encode(hash_hmac('sha256', $raw, PAYMONGO_WEBHOOK_SECRET, true));
    if (!hash_equals($expected, $headerSig)) { http_response_code(400); echo json_encode(['error'=>'Invalid signature']); exit; }
}

$payload = json_decode($raw, true);
if (!is_array($payload) || !isset($payload['data'])) { http_response_code(400); echo json_encode(['error'=>'Invalid payload']); exit; }

// Try to read checkout session style payload
$data = $payload['data'];
$attributes = $data['attributes'] ?? [];
$eventType = $payload['type'] ?? ($attributes['event'] ?? '');
$sessionId = $data['id'] ?? ($attributes['id'] ?? '');
$status = strtolower($attributes['status'] ?? '');
$amount = null; // amount paid now
// Prefer amount from line_items or amount_total if present
if (isset($attributes['line_items'][0]['amount'])) { $amount = (float)$attributes['line_items'][0]['amount'] / 100.0; }
if (isset($attributes['amount_total'])) { $amount = (float)$attributes['amount_total'] / 100.0; }

// Attempt to extract metadata we might have sent (reservee, facility)
$reservee = $attributes['customer_email'] ?? '';
$facility = $attributes['description'] ?? '';
$paymentPlan = '';
$downPercent = null;
$totalAmountMeta = null;
$payNowMeta = null;
$originalTx = '';
if (isset($attributes['metadata'])) {
	$reservee = $attributes['metadata']['reservee'] ?? $reservee;
	$facility = $attributes['metadata']['facility_name'] ?? $facility;
	$paymentPlan = $attributes['metadata']['payment_plan'] ?? '';
	if (isset($attributes['metadata']['down_percent'])) { $downPercent = (int)$attributes['metadata']['down_percent']; }
	if (isset($attributes['metadata']['total_amount'])) { $totalAmountMeta = (float)$attributes['metadata']['total_amount']; }
	if (isset($attributes['metadata']['pay_now'])) { $payNowMeta = (float)$attributes['metadata']['pay_now']; }
	if (isset($attributes['metadata']['original_tx'])) { $originalTx = (string)$attributes['metadata']['original_tx']; }
}

// We only process successful/paid sessions
if ($status === 'paid' && $sessionId !== '') {
	// Update receipt row if exists
	if ($conn) {
		// Update amount and set payment_type if provided
		$pt = ucfirst((string)($attributes['payment_method_used'] ?? $attributes['payment_method'] ?? ''));
		$now = date('Y-m-d H:i:s');
		$effectiveTx = $sessionId;
		if ($amount !== null) {
			// Add partial payment and reduce balance atomically; round to 2 decimals to avoid float residue
			if ($stmt = $conn->prepare("UPDATE receipt_tbl SET receipt_amount_paid = ROUND(COALESCE(receipt_amount_paid,0) + ?, 2), receipt_balance = GREATEST(0, ROUND(COALESCE(receipt_balance,0) - ?, 2)), receipt_payment_type = CASE WHEN ? <> '' THEN ? ELSE receipt_payment_type END, receipt_timestamp = ? WHERE receipt_trans_code = ?")) {
				$stmt->bind_param('ddssss', $amount, $amount, $pt, $pt, $now, $sessionId);
				@$stmt->execute();
				$stmt->close();
				if ($conn->affected_rows === 0 && $originalTx !== '') {
					if ($stmt2 = $conn->prepare("UPDATE receipt_tbl SET receipt_amount_paid = ROUND(COALESCE(receipt_amount_paid,0) + ?, 2), receipt_balance = GREATEST(0, ROUND(COALESCE(receipt_balance,0) - ?, 2)), receipt_payment_type = CASE WHEN ? <> '' THEN ? ELSE receipt_payment_type END, receipt_timestamp = ? WHERE receipt_trans_code = ?")) {
						$stmt2->bind_param('ddssss', $amount, $amount, $pt, $pt, $now, $originalTx);
						@$stmt2->execute();
						$stmt2->close();
						if ($conn->affected_rows > 0) { $effectiveTx = $originalTx; }
					}
				}
			}
		} else {
			if ($stmt = $conn->prepare("UPDATE receipt_tbl SET receipt_payment_type = CASE WHEN ? <> '' THEN ? ELSE receipt_payment_type END, receipt_timestamp = ? WHERE receipt_trans_code = ?")) {
				$stmt->bind_param('ssss', $pt, $pt, $now, $sessionId);
				@$stmt->execute();
				$stmt->close();
				if ($conn->affected_rows === 0 && $originalTx !== '') {
					if ($stmt2 = $conn->prepare("UPDATE receipt_tbl SET receipt_payment_type = CASE WHEN ? <> '' THEN ? ELSE receipt_payment_type END, receipt_timestamp = ? WHERE receipt_trans_code = ?")) {
						$stmt2->bind_param('ssss', $pt, $pt, $now, $originalTx);
						@$stmt2->execute();
						$stmt2->close();
						if ($conn->affected_rows > 0) { $effectiveTx = $originalTx; }
					}
				}
			}
		}
		// Do not change facility availability here; admin confirmation controls it
		// Email payment confirmation to customer if we can derive an email
		$customerEmail = '';
		if (isset($attributes['customer_email'])) { $customerEmail = (string)$attributes['customer_email']; }
		if ($customerEmail === '' && $reservee !== '') {
			if ($u = $conn->prepare("SELECT customer_email AS email FROM customer_tbl WHERE CONCAT(COALESCE(customer_fname,''),' ',COALESCE(customer_lname,'')) = ? LIMIT 1")) {
				$u->bind_param('s', $reservee);
				if ($u->execute()) {
					$res = $u->get_result();
					if ($res && ($row = $res->fetch_assoc())) { $customerEmail = (string)$row['email']; }
				}
				$u->close();
			}
		}
		if ($customerEmail !== '') {
			$subject = 'Payment received';
			$amtStr = $amount !== null ? number_format((float)$amount, 2) : 'your amount';
			// Derive totals/balance if we can find a receipt row for this transaction
			$totalPaid = null; $balance = null; $facilityName = $facility; $reserveeName = $reservee;
			if ($s = $conn->prepare('SELECT receipt_reservee AS reservee, receipt_facility AS facility_name, receipt_amount_paid AS amount_paid, receipt_balance AS balance FROM receipt_tbl WHERE receipt_trans_code = ? LIMIT 1')) {
				$s->bind_param('s', $effectiveTx);
				if ($s->execute()) {
					$res = $s->get_result();
					if ($res && ($row = $res->fetch_assoc())) {
						$reserveeName = $row['reservee'] ?: $reserveeName;
						$facilityName = $row['facility_name'] ?: $facilityName;
						$totalPaid = (float)$row['amount_paid'];
						$balance = (float)$row['balance'];
					}
				}
				$s->close();
			}
			$details = '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">'
				. '<tr><td style="padding:6px 0;color:#6b7280;">Transaction ID</td><td style="padding:6px 0;text-align:right;"><strong>' . htmlspecialchars($effectiveTx) . '</strong></td></tr>'
				. ($facilityName !== '' ? '<tr><td style="padding:6px 0;color:#6b7280;">Facility</td><td style="padding:6px 0;text-align:right;"><strong>' . htmlspecialchars($facilityName) . '</strong></td></tr>' : '')
				. ($amount !== null ? '<tr><td style="padding:6px 0;color:#6b7280;">Payment amount</td><td style="padding:6px 0;text-align:right;"><strong>PHP ' . number_format((float)$amount, 2) . '</strong></td></tr>' : '')
				. ($totalPaid !== null ? '<tr><td style="padding:6px 0;color:#6b7280;">Total paid</td><td style="padding:6px 0;text-align:right;"><strong>PHP ' . number_format((float)$totalPaid, 2) . '</strong></td></tr>' : '')
				. ($balance !== null ? '<tr><td style="padding:6px 0;color:#6b7280;">Remaining balance</td><td style="padding:6px 0;text-align:right;"><strong>PHP ' . number_format((float)$balance, 2) . '</strong></td></tr>' : '')
				. '<tr><td style="padding:6px 0;color:#6b7280;">Date</td><td style="padding:6px 0;text-align:right;"><strong>' . $now . '</strong></td></tr>'
				. '</table>';
			// Build receipt URL for CTA
			$baseUrl = rtrim((string)env('APP_BASE_URL', ''), '/');
			if ($baseUrl === '') {
				$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
				$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
				$baseUrl = $scheme . '://' . $host . '/sbh';
			}
			$receiptUrl = $baseUrl . '/customerdash/receipt.php?id=' . urlencode($effectiveTx);
			$html = '<p>Hi ' . htmlspecialchars($reserveeName ?: 'Guest') . ',</p>'
					. '<p>We have received your payment' . ($facilityName !== '' ? ' for <strong>' . htmlspecialchars($facilityName) . '</strong>' : '') . '.</p>'
					. $details
					. '<p>Thank you for booking with Shelton Resort.</p>';
			$html = renderEmailTemplate('Payment received', $html, [
				'preheader' => 'Your payment has been received',
				'cta' => ['text' => 'View receipt', 'url' => $receiptUrl]
			]);
			$altParts = [
				'Txn: ' . $effectiveTx,
				$amount !== null ? ('Paid now: PHP ' . number_format((float)$amount, 2)) : null,
				$totalPaid !== null ? ('Total paid: PHP ' . number_format((float)$totalPaid, 2)) : null,
				$balance !== null ? ('Balance: PHP ' . number_format((float)$balance, 2)) : null,
				$facilityName !== '' ? ('Facility: ' . $facilityName) : null,
				'Date: ' . $now,
			];
			$alt = 'Payment received. ' . implode(' | ', array_filter($altParts));
			@sendEmail($customerEmail, $subject, $html, $alt);
		}

		// Do not auto-confirm reservations on paid; admin will confirm upon arrival

		$conn->close();
	}
	http_response_code(200);
	echo json_encode(['ok'=>true]);
	exit;
}

http_response_code(200);
echo json_encode(['ignored'=>true]);


