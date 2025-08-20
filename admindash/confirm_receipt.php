<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
require_once __DIR__ . '/../properties/mailer.php';
require_once __DIR__ . '/../properties/email_template.php';
require_once __DIR__ . '/../properties/activity_log.php';
require_once __DIR__ . '/../config/env.php';

header('Content-Type: application/json');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { echo json_encode(['success'=>false,'message'=>'Invalid request']); exit; }

$tx = trim($_POST['transaction_id'] ?? '');
$amount = isset($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : null;
if ($tx === '') { echo json_encode(['success'=>false,'message'=>'Missing transaction id']); exit; }

// Fetch current balance
$cur = 0.0; $bal = 0.0;
if ($s = $conn->prepare('SELECT amount_paid, balance FROM receipt WHERE transaction_id = ? LIMIT 1')) {
    $s->bind_param('s', $tx);
    $s->execute();
    $s->bind_result($cur, $bal);
    if(!$s->fetch()){ $s->close(); echo json_encode(['success'=>false,'message'=>'Receipt not found']); exit; }
    $s->close();
}

if ($amount === null) { $amount = $bal; }
if ($amount < 0) { $amount = 0; }
if ($amount > $bal) { $amount = $bal; }

$newPaid = round($cur + $amount, 2);
$newBal = max(0.0, round($bal - $amount, 2));

if ($u = $conn->prepare('UPDATE receipt SET amount_paid = ?, balance = ?, timestamp = NOW() WHERE transaction_id = ?')) {
    $u->bind_param('dds', $newPaid, $newBal, $tx);
    $ok = $u->execute();
    $u->close();
    if ($ok) {
        // Log payment update
        @log_activity($conn, 'PAYMENT_UPDATED', 'Payment updated for transaction ' . $tx, [
            'user_id' => (int)($_SESSION['user']['id'] ?? 0),
            'actor' => 'admin',
            'ref_transaction_id' => $tx,
            'amount' => (float)$amount,
        ]);
        // Try to email the customer about payment update
        $reservee = ''; $facility = '';
        if ($r = $conn->prepare('SELECT reservee, facility_name FROM receipt WHERE transaction_id = ? LIMIT 1')) {
            $r->bind_param('s', $tx);
            if ($r->execute()) {
                $res = $r->get_result();
                if ($res && ($row = $res->fetch_assoc())) { $reservee = (string)$row['reservee']; $facility = (string)$row['facility_name']; }
            }
            $r->close();
        }
        // If fully paid, upsert a reservation row to Confirmed
        if ($newBal <= 0.00001) {
            try {
                // Try to match an existing reservation for this reservee/facility today
                $rid = 0;
                if ($f = $conn->prepare("SELECT id FROM reservations WHERE reservee = ? AND facility_name = ? ORDER BY date_booked DESC, id DESC LIMIT 1")) {
                    $f->bind_param('ss', $reservee, $facility);
                    if ($f->execute()) { $gr = $f->get_result(); if ($gr && ($row = $gr->fetch_assoc())) $rid = intval($row['id']); }
                    $f->close();
                }
                if ($rid > 0) {
                    // Update existing row to Confirmed
                    @$conn->query("UPDATE reservations SET status='Confirmed' WHERE id = $rid");
                    @log_activity($conn, 'BOOKING_CONFIRMED', 'Reservation marked Confirmed', [
                        'user_id' => (int)($_SESSION['user']['id'] ?? 0),
                        'actor' => 'admin',
                        'ref_reservation_id' => $rid,
                        'ref_transaction_id' => $tx,
                        'reservee' => $reservee,
                        'facility_name' => $facility,
                        'amount' => (float)$newPaid,
                    ]);
                } else {
                    // Insert a minimal Confirmed row pulling dates from receipt if available
                    $ds = ''; $de = ''; $amt = $newPaid; $pt = '';
                    if ($g = $conn->prepare("SELECT date_checkin, date_checkout, payment_type FROM receipt WHERE transaction_id = ? LIMIT 1")) {
                        $g->bind_param('s', $tx);
                        if ($g->execute()) {
                            $rs = $g->get_result();
                            if ($rs && ($row = $rs->fetch_assoc())) { $ds = (string)$row['date_checkin']; $de = (string)$row['date_checkout']; $pt = (string)$row['payment_type']; }
                        }
                        $g->close();
                    }
                    if ($ins = $conn->prepare("INSERT INTO reservations (reservee, facility_name, status, date_booked, date_start, date_end, payment_type, amount) VALUES (?, ?, 'Confirmed', NOW(), ?, ?, ?, ?)")) {
                        $ins->bind_param('sssssd', $reservee, $facility, $ds, $de, $pt, $amt);
                        @$ins->execute();
                        $newRid = (int)$conn->insert_id;
                        $ins->close();
                        if ($newRid > 0) {
                            @log_activity($conn, 'BOOKING_CONFIRMED', 'Reservation created and Confirmed', [
                                'user_id' => (int)($_SESSION['user']['id'] ?? 0),
                                'actor' => 'admin',
                                'ref_reservation_id' => $newRid,
                                'ref_transaction_id' => $tx,
                                'reservee' => $reservee,
                                'facility_name' => $facility,
                                'amount' => (float)$newPaid,
                            ]);
                        }
                    }
                }
            } catch (Throwable $e) { /* no-op */ }
        }
        $emailTo = '';
        if ($reservee !== '') {
            if ($u2 = $conn->prepare('SELECT email FROM users WHERE fullname = ? LIMIT 1')) {
                $u2->bind_param('s', $reservee);
                if ($u2->execute()) {
                    $res2 = $u2->get_result();
                    if ($res2 && ($row2 = $res2->fetch_assoc())) { $emailTo = (string)$row2['email']; }
                }
                $u2->close();
            }
        }
        if ($emailTo !== '') {
            $subject = 'Payment update for your booking';
            $paymentTime = date('Y-m-d H:i:s');
            // Build receipt URL for CTA
            $baseUrl = rtrim((string)env('APP_BASE_URL', ''), '/');
            if ($baseUrl === '') {
                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $baseUrl = $scheme . '://' . $host . '/sbh';
            }
            $receiptUrl = $baseUrl . '/customerdash/receipt.php?id=' . urlencode($tx);
            $details = '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">'
                . '<tr><td style="padding:6px 0;color:#6b7280;">Transaction ID</td><td style="padding:6px 0;text-align:right;"><strong>' . htmlspecialchars($tx) . '</strong></td></tr>'
                . ($facility !== '' ? '<tr><td style="padding:6px 0;color:#6b7280;">Facility</td><td style="padding:6px 0;text-align:right;"><strong>' . htmlspecialchars($facility) . '</strong></td></tr>' : '')
                . '<tr><td style="padding:6px 0;color:#6b7280;">Payment amount</td><td style="padding:6px 0;text-align:right;"><strong>PHP ' . number_format((float)$amount, 2) . '</strong></td></tr>'
                . '<tr><td style="padding:6px 0;color:#6b7280;">Total paid</td><td style="padding:6px 0;text-align:right;"><strong>PHP ' . number_format((float)$newPaid, 2) . '</strong></td></tr>'
                . '<tr><td style="padding:6px 0;color:#6b7280;">Remaining balance</td><td style="padding:6px 0;text-align:right;"><strong>PHP ' . number_format((float)$newBal, 2) . '</strong></td></tr>'
                . '<tr><td style="padding:6px 0;color:#6b7280;">Date</td><td style="padding:6px 0;text-align:right;"><strong>' . $paymentTime . '</strong></td></tr>'
                . '</table>';
            $html = '<p>Hi ' . htmlspecialchars($reservee ?: 'Guest') . ',</p>'
                . '<p>Your payment has been recorded' . ($facility !== '' ? ' for <strong>' . htmlspecialchars($facility) . '</strong>' : '') . '.</p>'
                . $details
                . '<p>Thank you,<br/>Shelton Resort</p>';
            $html = renderEmailTemplate('Payment update', $html, [
                'preheader' => 'Your booking payment has been updated',
                'cta' => ['text' => 'View receipt', 'url' => $receiptUrl]
            ]);
            $alt  = 'Payment update. Txn: ' . $tx . '. Paid now: PHP ' . number_format((float)$amount, 2) . '. Total paid: PHP ' . number_format((float)$newPaid, 2) . '. Balance: PHP ' . number_format((float)$newBal, 2) . ($facility !== '' ? "\nFacility: $facility" : '') . "\nDate: $paymentTime";
            @sendEmail($emailTo, $subject, $html, $alt);
            @log_activity($conn, 'EMAIL_SENT', 'Payment update email sent', [
                'user_id' => (int)($_SESSION['user']['id'] ?? 0),
                'actor' => 'admin',
                'ref_transaction_id' => $tx,
                'reservee' => $reservee,
                'facility_name' => $facility,
                'amount' => (float)$amount,
                'metadata' => ['to' => $emailTo, 'subject' => $subject],
            ]);
        }
    }
    echo json_encode(['success'=> (bool)$ok, 'new_balance' => $newBal, 'new_amount_paid' => $newPaid]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Update failed']);
?>


