<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

$tx = isset($_GET['id']) ? trim($_GET['id']) : '';
if ($tx === '' && isset($_SESSION['last_tx'])) { $tx = trim($_SESSION['last_tx']); }
$fullname = $_SESSION['user']['fullname'] ?? '';
if ($tx === '' || $fullname === '') { http_response_code(400); echo 'Invalid request'; exit; }

$stmt = $conn->prepare("SELECT 
  receipt_trans_code AS transaction_id,
  receipt_reservee AS reservee,
  receipt_facility AS facility_name,
  receipt_amount_paid AS amount_paid,
  receipt_balance AS balance,
  receipt_date_booked AS date_booked,
  receipt_date_checkin AS date_checkin,
  receipt_date_checkout AS date_checkout,
  receipt_payment_type AS payment_type
FROM receipt_tbl WHERE receipt_trans_code = ? AND receipt_reservee = ? LIMIT 1");
$stmt->bind_param('ss', $tx, $fullname);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) { http_response_code(404); echo 'Not found'; exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Receipt #<?php echo htmlspecialchars($tx); ?></title>
	<link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="../css/theme-overrides.css" rel="stylesheet">
	<style>
		@media print { .no-print { display: none; } }
		.body { background: var(--sbh-light-gray); }
		.card{ max-width: 720px; margin: 24px auto; border: 0; border-radius: 16px; box-shadow: 0 20px 60px var(--sbh-shadow-hover); }
		.badge-balance{ background: var(--sbh-accent-orange); color: #fff; }
		.badge-paid{ background: var(--sbh-primary); color: #fff; }
		.btn-primary{ background: var(--sbh-primary); border-color: var(--sbh-primary); }
		.btn-primary:hover{ filter: brightness(.95); }
	</style>
</head>
<body class="body">
	<div class="card shadow">
		<div class="card-body">
			<div class="d-flex justify-content-between align-items-center mb-3">
				<h5 class="mb-0">Shelton Beach Resort</h5>
				<small class="text-muted">Receipt</small>
			</div>
			<?php $__bal = (float)($row['balance'] ?? 0); ?>
			<?php if ($__bal > 0.01): ?>
				<div class="alert alert-warning d-flex justify-content-between align-items-center py-2">
					<span><strong>Balance due</strong></span>
					<span class="badge badge-balance">₱<?php echo number_format($__bal, 2); ?></span>
				</div>
			<?php else: ?>
				<div class="alert alert-success d-flex justify-content-between align-items-center py-2">
					<span><strong>Fully paid</strong></span>
					<span class="badge badge-paid">₱0.00</span>
				</div>
			<?php endif; ?>
			<hr>
			<div class="row g-2">
				<div class="col-md-6">
					<div><strong>Transaction ID:</strong> <?php echo htmlspecialchars($row['transaction_id']); ?></div>
					<div><strong>Reservee:</strong> <?php echo htmlspecialchars($row['reservee']); ?></div>
					<div><strong>Facility:</strong> <?php echo htmlspecialchars($row['facility_name']); ?></div>
				</div>
				<div class="col-md-6">
					<div><strong>Date Booked:</strong> <?php echo date('M d, Y', strtotime($row['date_booked'])); ?></div>
					<div><strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($row['date_checkin'])); ?></div>
					<div><strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($row['date_checkout'])); ?></div>
				</div>
			</div>
			<hr>
			<div class="row g-2">
				<div class="col-md-4"><strong>Payment Type:</strong> <?php echo htmlspecialchars($row['payment_type']); ?></div>
				<div class="col-md-4"><strong>Amount Paid:</strong> ₱<?php echo number_format((float)$row['amount_paid'], 2); ?></div>
				<div class="col-md-4"><strong>Remaining Balance:</strong> ₱<?php echo number_format((float)($row['balance'] ?? 0), 2); ?></div>
			</div>
			<hr>
			<div class="text-muted small">Thank you for choosing Shelton Beach Resort. Enjoy your stay!</div>
		</div>
		<div class="card-footer d-flex justify-content-end gap-2 no-print">
			<a class="btn btn-secondary" href="javascript:window.print()">Print</a>
			<a class="btn btn-primary" href="cusdash.php#transactions">Back</a>
		</div>
	</div>
	<script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>


