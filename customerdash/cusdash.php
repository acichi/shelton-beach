<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

$userId = (int)($_SESSION['user']['id'] ?? 0);
$fullname = $_SESSION['user']['fullname'] ?? 'Guest';

// Fetch profile details
$stmt = $conn->prepare("SELECT 
    CONCAT(COALESCE(customer_fname,''),' ',COALESCE(customer_lname,'')) AS fullname,
    customer_gender AS gender,
    customer_email AS email,
    customer_number AS `number`,
    NULL AS date_added
  FROM customer_tbl WHERE customer_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$email = $number = '';
$memberSince = '';
$salutation = 'Welcome';
if ($row = $result->fetch_assoc()) {
	$fullname = $row['fullname'] ?: $fullname;
	$gender = strtolower($row['gender'] ?? '');
	$email = $row['email'] ?? '';
	$number = $row['number'] ?? '';
	$memberSince = $row['date_added'] ? date('Y', strtotime($row['date_added'])) : '';
	$firstName = explode(' ', $fullname)[0];
	$salutation = $gender === 'male' ? "Welcome Mr. $firstName" : ($gender === 'female' ? "Welcome Ms. $firstName" : "Welcome $firstName");
}
$stmt->close();

// Data: reservations (latest per facility), alias columns to match template keys
$reservations = [];
$resSql = "SELECT 
    r.receipt_trans_code AS transaction_id,
    COALESCE(r.receipt_reservee,'') AS reservee,
    COALESCE(r.receipt_facility,'') AS facility_name,
    COALESCE(r.receipt_date_booked,'') AS date_booked,
    COALESCE(r.receipt_date_checkin,'') AS date_checkin,
    COALESCE(r.receipt_date_checkout,'') AS date_checkout,
    COALESCE(r.receipt_payment_type,'Cash') AS payment_type,
    COALESCE(r.receipt_amount_paid,0) AS amount_paid,
    COALESCE(r.receipt_balance,0) AS balance
  FROM receipt_tbl r
  INNER JOIN (
    SELECT receipt_facility AS facility_name, MAX(receipt_date_booked) AS latest_date
    FROM receipt_tbl WHERE receipt_reservee = ? GROUP BY receipt_facility
  ) latest ON r.receipt_facility = latest.facility_name AND r.receipt_date_booked = latest.latest_date
  WHERE r.receipt_reservee = ?
  ORDER BY r.receipt_date_booked DESC";
$resStmt = $conn->prepare($resSql);
$resStmt->bind_param("ss", $fullname, $fullname);
$resStmt->execute();
$resResult = $resStmt->get_result();
while ($r = $resResult->fetch_assoc()) { $reservations[] = $r; }

// Data: transactions
$transactions = [];
$transSql = "SELECT receipt_trans_code AS transaction_id, receipt_reservee AS reservee, receipt_facility AS facility_name, receipt_date_booked AS date_booked, receipt_amount_paid AS amount_paid, receipt_balance AS balance, receipt_payment_type AS payment_type FROM receipt_tbl WHERE receipt_reservee = ? ORDER BY receipt_date_booked DESC";
$transStmt = $conn->prepare($transSql);
$transStmt->bind_param("s", $fullname);
$transStmt->execute();
$transResult = $transStmt->get_result();
while ($t = $transResult->fetch_assoc()) { $transactions[] = $t; }

// KPIs
$upcomingCount = 0; $reviewCount = 0; $totalSpent = 0; $totalOutstanding = 0;
$upcomingStmt = $conn->prepare("SELECT COUNT(*) c FROM receipt_tbl WHERE receipt_reservee = ? AND receipt_date_checkin > CURRENT_DATE()");
$upcomingStmt->bind_param("s", $fullname); $upcomingStmt->execute(); $upcomingCount = (int)($upcomingStmt->get_result()->fetch_assoc()['c'] ?? 0);

$reviewsStmt = $conn->prepare("SELECT COUNT(*) c FROM feedback_tbl WHERE feedback_name = ?");
$reviewsStmt->bind_param("s", $fullname); $reviewsStmt->execute(); $reviewCount = (int)($reviewsStmt->get_result()->fetch_assoc()['c'] ?? 0);

$spentStmt = $conn->prepare("SELECT SUM(receipt_amount_paid) s FROM receipt_tbl WHERE receipt_reservee = ?");
$spentStmt->bind_param("s", $fullname); $spentStmt->execute(); $totalSpent = (float)($spentStmt->get_result()->fetch_assoc()['s'] ?? 0);
$outStmt = $conn->prepare("SELECT SUM(receipt_balance) s FROM receipt_tbl WHERE receipt_reservee = ? AND receipt_balance > 0.01");
$outStmt->bind_param("s", $fullname); $outStmt->execute(); $totalOutstanding = (float)($outStmt->get_result()->fetch_assoc()['s'] ?? 0);

// Feedbacks and recent activity
$feedbacks = [];
$feedbackStmt = $conn->prepare("SELECT feedback_id AS id, feedback_name AS fullname, feedback_facility AS facility_name, feedback_message AS feedback, feedback_rate AS rate, feedback_timestamp AS timestamp, feedback_status FROM feedback_tbl WHERE feedback_name = ? ORDER BY feedback_timestamp DESC");
$feedbackStmt->bind_param("s", $fullname);
$feedbackStmt->execute();
$fr = $feedbackStmt->get_result();
while ($f = $fr->fetch_assoc()) { $feedbacks[] = $f; }
$feedbackStmt->close();

$recentActivity = [];
$activityStmt = $conn->prepare("SELECT 'reservation' as type, receipt_facility as title, receipt_date_booked as date_created, 'Booked facility' as description FROM receipt_tbl WHERE receipt_reservee = ? UNION ALL SELECT 'feedback' as type, feedback_facility as title, feedback_timestamp as date_created, 'Left feedback' as description FROM feedback_tbl WHERE feedback_name = ? ORDER BY date_created DESC LIMIT 5");
$activityStmt->bind_param("ss", $fullname, $fullname);
$activityStmt->execute();
$ar = $activityStmt->get_result();
while ($a = $ar->fetch_assoc()) { $recentActivity[] = $a; }
$activityStmt->close();

$upcomingList = [];
$ulStmt = $conn->prepare("SELECT receipt_trans_code AS transaction_id, receipt_facility AS facility_name, receipt_date_checkin AS date_checkin, receipt_date_checkout AS date_checkout FROM receipt_tbl WHERE receipt_reservee = ? AND receipt_date_checkin >= CURRENT_DATE() ORDER BY receipt_date_checkin ASC LIMIT 5");
$ulStmt->bind_param("s", $fullname);
$ulStmt->execute();
$ulRes = $ulStmt->get_result();
while ($u = $ulRes->fetch_assoc()) { $upcomingList[] = $u; }
$ulStmt->close();

$usedFacilities = [];
$usedStmt = $conn->prepare("SELECT DISTINCT r.receipt_facility AS facility_name FROM receipt_tbl r WHERE r.receipt_reservee = ? AND r.receipt_date_checkout <= CURRENT_DATE() AND NOT EXISTS (SELECT 1 FROM feedback_tbl f WHERE f.feedback_name = r.receipt_reservee AND f.feedback_facility = r.receipt_facility)");
$usedStmt->bind_param('s', $fullname);
$usedStmt->execute();
$usedRes = $usedStmt->get_result();
while ($u = $usedRes->fetch_assoc()) { $usedFacilities[] = $u['facility_name']; }
$usedStmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta content="width=device-width, initial-scale=1.0" name="viewport">
	<title>My Dashboard - Shelton Beach Resort</title>
	<link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
	<link href="../template/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
	<link href="../template/assets/vendor/simple-datatables/style.css" rel="stylesheet">
	<link href="../template/assets/css/style.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
	<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
	<link href="../css/theme-overrides.css?v=<?php echo filemtime(__DIR__ . '/../css/theme-overrides.css'); ?>" rel="stylesheet">
	<style>
		/* Ensure SweetAlert modals and Bootstrap modals inherit theme spacing/colors */
		.swal2-popup .swal2-actions .swal2-styled { min-width: 120px; }
		.swal2-popup .swal2-actions { gap: .5rem; }
		.modal .btn-primary { background: var(--sbh-primary); border-color: var(--sbh-primary); }
		.modal .btn-primary:hover { filter: brightness(.95); }
	</style>
	<style>
		#main .table td,#main .table th{padding:.6rem .6rem}
		#main .card .card-body{padding:1rem}
		#main .info-card .card-icon{width:44px;height:44px}
		#main .info-card h6{font-size:1.125rem;margin:0}
		/* Fit tables without horizontal scroll: fixed layout + wrapping */
		#reservationTable, #transactionTable { table-layout: fixed; width: 100%; }
		#reservationTable th, #reservationTable td, #transactionTable th, #transactionTable td { white-space: normal; word-break: break-word; }
		/* Keep buttons on one line to look tidy */
		#reservationTable td .btn, #transactionTable td .btn { white-space: nowrap; }
		/* Transaction id cells can be long; limit visual width */
		#reservationTable td:first-child, #transactionTable td:first-child { max-width: 140px; }
	</style>
</head>
<body>
	<header id="header" class="header fixed-top d-flex align-items-center">
		<div class="d-flex align-items-center justify-content-between">
			<a href="#" class="logo d-flex align-items-center">
				<img src="../pics/logo2.png" alt="">
				<span class="d-none d-lg-block">Shelton Customer</span>
			</a>
			<i class="bi bi-list toggle-sidebar-btn"></i>
		</div>
		<nav class="header-nav ms-auto">
			<ul class="d-flex align-items-center">
				<li class="nav-item pe-3">
					<div class="nav-link nav-profile d-flex align-items-center pe-0">
						<?php 
							$__cusUser = htmlspecialchars($_SESSION['user']['username'] ?? '');
							$__sessGender = strtolower($_SESSION['user']['gender'] ?? '');
							$__avatarSrc = '../pics/profile.png';
							if ($__sessGender === 'female') { $__avatarSrc = '../pics/avatar-female.png'; }
							else if ($__sessGender === 'male') { $__avatarSrc = '../pics/avatar-male.png'; }
						?>
						<img src="<?php echo $__avatarSrc; ?>" alt="Profile" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" onerror="this.onerror=null;this.src='../template/assets/img/profile-img.jpg';">
						<span class="d-none d-md-block ps-2"><?php echo ($__cusUser !== '' ? $__cusUser : 'Customer'); ?></span>
					</div>
				</li>
			</ul>
		</nav>
	</header>
	<aside id="sidebar" class="sidebar">
		<ul class="sidebar-nav" id="sidebar-nav">
			<li class="nav-item"><a class="nav-link collapsed" href="../index.php"><i class="bi bi-house"></i><span>Home</span></a></li>
			<li class="nav-item"><a class="nav-link" id="menu-dashboard" href="#main" onclick="showPage('dashboard')"><i class="bi bi-grid"></i><span>Dashboard</span></a></li>
			<li class="nav-item"><a class="nav-link collapsed" id="menu-facilities" href="#page-facilities" onclick="showPage('facilities')"><i class="bi bi-geo-alt"></i><span>Available Facilities</span></a></li>
			<li class="nav-item"><a class="nav-link collapsed" id="menu-reservations" href="#page-reservations" onclick="showPage('reservations')"><i class="bi bi-calendar-check"></i><span>My Reservations</span></a></li>
			<li class="nav-item"><a class="nav-link collapsed" id="menu-transactions" href="#page-transactions" onclick="showPage('transactions')"><i class="bi bi-receipt"></i><span>Transactions</span></a></li>
			<li class="nav-item"><a class="nav-link collapsed" id="menu-feedback" href="#page-feedback" onclick="showPage('feedback')"><i class="bi bi-star"></i><span>My Feedback</span></a></li>
			<li class="nav-item"><a class="nav-link collapsed" id="menu-book" href="book_now.php"><i class="bi bi-geo"></i><span>Book Now</span></a></li>
			<li class="nav-item"><a class="nav-link collapsed" id="menu-profile" href="profile.php"><i class="bi bi-person"></i><span>Profile</span></a></li>
			<li class="nav-item"><a class="nav-link collapsed text-danger" href="logout.php"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
		</ul>
	</aside>
	<main id="main" class="main">
		<div class="pagetitle">
			<h1>Dashboard</h1>
			<nav><ol class="breadcrumb"><li class="breadcrumb-item"><a href="#" onclick="showPage('dashboard');return false;">Home</a></li><li class="breadcrumb-item active">Dashboard</li></ol></nav>
		</div>
		<section class="section dashboard">
			<div class="content-page active" id="page-dashboard">
				<div class="row g-3 flex-nowrap">
					<div class="col-6 col-md-3">
						<div class="card info-card sales-card"><div class="card-body"><h5 class="card-title">Upcoming <span>| Reservations</span></h5><div class="d-flex align-items-center"><div class="card-icon rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-calendar-event"></i></div><div class="ps-3"><h6><?php echo (int)$upcomingCount; ?></h6></div></div></div></div>
					</div>
					<div class="col-6 col-md-3">
						<div class="card info-card customers-card"><div class="card-body"><h5 class="card-title">Reviews <span>| Submitted</span></h5><div class="d-flex align-items-center"><div class="card-icon rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-star"></i></div><div class="ps-3"><h6><?php echo (int)$reviewCount; ?></h6></div></div></div></div>
					</div>
					<div class="col-12 col-md-3">
						<div class="card info-card revenue-card"><div class="card-body"><h5 class="card-title">Total Spent <span>| All-time</span></h5><div class="d-flex align-items-center"><div class="card-icon rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-cash-stack"></i></div><div class="ps-3"><h6>₱<?php echo number_format($totalSpent, 2); ?></h6></div></div></div></div>
					</div>
					<div class="col-12 col-md-3">
						<div class="card info-card revenue-card"><div class="card-body"><h5 class="card-title">Outstanding <span>| Balance</span></h5><div class="d-flex align-items-center"><div class="card-icon rounded-circle d-flex align-items-center justify-content-center"><i class="bi bi-exclamation-circle"></i></div><div class="ps-3"><h6>₱<?php echo number_format($totalOutstanding, 2); ?></h6></div></div></div></div>
					</div>
				</div>
				<div class="row g-3 mt-1">
					<div class="col-lg-6">
						<div class="card"><div class="card-body">
							<h5 class="card-title">Recent Activity</h5>
							<?php if (!empty($recentActivity)): foreach($recentActivity as $a): ?>
								<div class="d-flex align-items-center mb-2 p-2 rounded border">
									<div class="me-2"><span class="badge bg-primary"><i class="bi <?php echo $a['type']==='reservation'?'bi-calendar-check':'bi-chat-left-dots'; ?>"></i></span></div>
									<div>
										<div class="fw-semibold"><?php echo htmlspecialchars($a['description']); ?></div>
										<small class="text-muted"><?php echo htmlspecialchars($a['title']); ?> - <?php echo date('M d, Y', strtotime($a['date_created'])); ?></small>
									</div>
								</div>
							<?php endforeach; else: ?>
								<div class="alert alert-info mb-0">No recent activity found.</div>
							<?php endif; ?>
						</div></div>
					</div>
					<div class="col-lg-6">
						<div class="card"><div class="card-body">
							<h5 class="card-title">Upcoming Reservations</h5>
							<?php if (!empty($upcomingList)): ?>
								<ul class="list-group list-group-flush">
									<?php foreach($upcomingList as $u): ?>
									<li class="list-group-item d-flex justify-content-between align-items-center">
										<div>
											<div class="fw-semibold"><?php echo htmlspecialchars($u['facility_name']); ?></div>
											<small class="text-muted">Check-in: <?php echo htmlspecialchars(date('M d, Y', strtotime($u['date_checkin']))); ?> · Check-out: <?php echo htmlspecialchars(date('M d, Y', strtotime($u['date_checkout']))); ?></small>
										</div>
										<a class="btn btn-sm btn-outline-secondary" href="receipt.php?id=<?php echo urlencode($u['transaction_id']); ?>"><i class="bi bi-receipt"></i></a>
									</li>
									<?php endforeach; ?>
								</ul>
							<?php else: ?>
								<div class="alert alert-info mb-0">No upcoming reservations.</div>
							<?php endif; ?>
						</div></div>
					</div>
				</div>
			</div>

			<!-- New Facilities Section -->
			<div class="content-page" id="page-facilities">
				<div class="card">
					<div class="card-body">
						<h5 class="card-title">Available Facilities</h5>
						<div class="row" id="facilitiesContainer">
							<div class="col-12 text-center">
								<div class="spinner-border text-primary" role="status">
									<span class="visually-hidden">Loading facilities...</span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="content-page" id="page-reservations">
				<div class="card"><div class="card-body">
					<h5 class="card-title">My Reservations</h5>
					<div class="table-responsive">
						<table class="table table-borderless align-middle" id="reservationTable">
							<thead><tr><th>Transaction ID</th><th>Date Booked</th><th>Facility</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Amount Paid</th><th>Balance</th><th>Payment Type</th><th>Actions</th></tr></thead>
							<tbody>
								<?php foreach($reservations as $row): $status='Confirmed'; $badge='bg-success'; if (strtotime($row['date_checkin'])>time()){ $status='Upcoming'; $badge='bg-warning text-dark'; } elseif (strtotime($row['date_checkout'])<time()){ $status='Completed'; $badge='bg-secondary'; } ?>
								<tr>
									<td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
									<td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['date_booked']))); ?></td>
									<td><?php echo htmlspecialchars($row['facility_name']); ?></td>
									<td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['date_checkin']))); ?></td>
									<td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['date_checkout']))); ?></td>
									<td><span class="badge <?php echo $badge; ?>"><?php echo $status; ?></span></td>
									<td>₱<?php echo htmlspecialchars(number_format($row['amount_paid'], 2)); ?></td>
									<td>
										<?php $bal = (float)($row['balance'] ?? 0); $isDue = $bal > 0.01; ?>
										<span class="badge <?php echo $isDue ? 'bg-warning text-dark' : 'bg-success'; ?>">₱<?php echo number_format($bal, 2); ?></span>
									</td>
									<td><?php echo htmlspecialchars($row['payment_type']); ?></td>
									<td>
										<button class="btn btn-sm btn-outline-primary" onclick="viewReservation('<?php echo $row['transaction_id']; ?>')"><i class="bi bi-eye"></i></button>
										<?php if (strtotime($row['date_checkin']) > time()): ?>
											<button class="btn btn-sm btn-outline-danger" onclick="cancelReservation('<?php echo $row['transaction_id']; ?>')"><i class="bi bi-x"></i></button>
											<button class="btn btn-sm btn-outline-warning" onclick="rescheduleReservation('<?php echo $row['transaction_id']; ?>', '<?php echo htmlspecialchars($row['facility_name']); ?>', '<?php echo $row['date_checkin']; ?>', '<?php echo $row['date_checkout']; ?>')" title="Request reschedule"><i class="bi bi-calendar-event"></i></button>
										<?php endif; ?>
										<?php if ($isDue): ?>
											<button class="btn btn-sm btn-success" onclick="payBalanceOffline('<?php echo $row['transaction_id']; ?>')" title="Mark balance paid (admin will confirm)" data-bs-toggle="tooltip"><i class="bi bi-cash-coin"></i></button>
										<?php endif; ?>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div></div>
			</div>

			<div class="content-page" id="page-transactions">
				<div class="card"><div class="card-body">
					<h5 class="card-title">Transaction History</h5>
					<div class="table-responsive">
						<table class="table table-borderless align-middle" id="transactionTable">
							<thead><tr><th>Transaction ID</th><th>Facility</th><th>Date Booked</th><th>Amount Paid</th><th>Balance</th><th>Payment Type</th><th>Actions</th></tr></thead>
							<tbody>
								<?php foreach($transactions as $row): ?>
								<tr>
									<td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
									<td><?php echo htmlspecialchars($row['facility_name']); ?></td>
									<td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['date_booked']))); ?></td>
									<td>₱<?php echo htmlspecialchars(number_format($row['amount_paid'], 2)); ?></td>
									<td>
										<?php $bal = (float)($row['balance'] ?? 0); $isDue = $bal > 0.01; ?>
										<span class="badge <?php echo $isDue ? 'bg-warning text-dark' : 'bg-success'; ?>">₱<?php echo number_format($bal, 2); ?></span>
									</td>
									<td><?php echo htmlspecialchars($row['payment_type']); ?></td>
									<td><a class="btn btn-sm btn-outline-secondary" href="receipt.php?id=<?php echo urlencode($row['transaction_id']); ?>"><i class="bi bi-download"></i> Receipt</a>
										<?php if ($isDue): ?>
											<button class="btn btn-sm btn-success" onclick="payBalanceOffline('<?php echo $row['transaction_id']; ?>')" title="Mark balance paid (admin will confirm)" data-bs-toggle="tooltip"><i class="bi bi-cash-coin"></i></button>
										<?php endif; ?>
									</td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div></div>
			</div>

			<div class="content-page" id="page-feedback">
				<div class="card"><div class="card-body">
					<h5 class="card-title">My Feedback</h5>
					<div class="row g-3">
						<div class="col-lg-6">
							<div class="card h-100"><div class="card-body">
								<h6 class="card-title">Submit a Review</h6>
								<form id="reviewForm" action="submit_review.php" method="POST">
									<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
									<input type="hidden" name="fullname" value="<?php echo htmlspecialchars($fullname); ?>">
									<div class="mb-2"><label class="form-label">Facility (used)</label>
										<select name="facility_name" class="form-select" required>
											<option value="">Select Facility</option>
											<?php foreach($usedFacilities as $fname): ?>
												<option value="<?php echo htmlspecialchars($fname); ?>"><?php echo htmlspecialchars($fname); ?></option>
											<?php endforeach; ?>
										</select>
										<small class="text-muted">You can submit one review per facility after you have completed your stay.</small>
									</div>
									<div class="mb-2"><label class="form-label">Your Review</label><textarea name="feedback" class="form-control" rows="3" required></textarea></div>
									<div class="mb-3"><label class="form-label">Rating</label>
										<div class="d-flex flex-wrap align-items-center gap-2">
											<?php for($i=5;$i>=1;$i--): ?>
												<input type="radio" class="btn-check" name="rate" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" autocomplete="off" required>
												<label class="btn btn-outline-primary btn-sm" for="star<?php echo $i; ?>"><i class="bi bi-star-fill me-1"></i><?php echo $i; ?> <?php echo $i>1 ? 'Stars' : 'Star'; ?></label>
											<?php endfor; ?>
										</div>
										<div class="form-text">Select a rating: 1 Star (poor) to 5 Stars (excellent).</div>
									</div>
									<button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-send"></i> Submit</button>
								</form>
							</div></div>
						</div>
						<div class="col-lg-6">
							<div class="card h-100"><div class="card-body">
								<h6 class="card-title">Your Feedback Summary</h6>
								<?php if (!empty($feedbacks)): $totalRating = array_sum(array_column($feedbacks, 'rate')); $avgRating = $totalRating / max(count($feedbacks),1); ?>
									<p class="mb-1"><strong>Average Rating:</strong>
										<?php for($i=1;$i<=5;$i++): ?><i class="bi <?php echo $i<=round($avgRating)?'bi-star-fill text-warning':'bi-star text-muted'; ?>"></i><?php endfor; ?> (<?php echo number_format($avgRating,1); ?>)
									</p>
									<div class="row">
										<?php foreach(array_slice($feedbacks,0,3) as $fb): ?>
											<div class="col-md-4 mb-2"><div class="border rounded p-2 h-100">
												<div class="mb-1"><?php for($i=1;$i<=5;$i++): ?><i class="bi <?php echo $i<=$fb['rate']?'bi-star-fill text-warning':'bi-star text-muted'; ?>"></i><?php endfor; ?></div>
												<div class="small">"<?php echo htmlspecialchars($fb['feedback']); ?>"</div>
												<small class="text-muted"><?php echo htmlspecialchars($fb['facility_name']); ?><br><?php echo htmlspecialchars(date('M d, Y', strtotime($fb['timestamp']))); ?></small>
											</div></div>
										<?php endforeach; ?>
									</div>
								<?php else: ?>
									<div class="alert alert-info mb-0">No feedback yet. Share your first experience!</div>
								<?php endif; ?>
							</div></div>
						</div>
					</div>
				</div></div>
			</div>
		</section>
	</main>
	<footer id="footer" class="footer"><div class="copyright">&copy; <strong><span>Shelton Beach Resort</span></strong> All Rights Reserved</div></footer>
	<a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
	<script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
	<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
	<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
	<script src="../template/assets/js/main.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="../js/notify.js?v=<?php echo filemtime(__DIR__ . '/../js/notify.js'); ?>"></script>
	<script>
		function showPage(pageId){
			document.querySelectorAll('.content-page').forEach(p=>p.classList.remove('active'));
			const el=document.getElementById('page-'+pageId); if(el) el.classList.add('active');
			document.querySelectorAll('#sidebar .nav-link').forEach(a=>{a.classList.add('collapsed');a.classList.remove('active');});
			const active=document.getElementById('menu-'+pageId); if(active){active.classList.remove('collapsed');active.classList.add('active');}
			const h1=document.querySelector('.pagetitle h1'); if(h1) h1.textContent = pageId.charAt(0).toUpperCase()+pageId.slice(1);
		}
		$(function(){
			// On load: respect hash (#reservations, #transactions, #feedback)
			const initialHash = (window.location.hash||'').replace('#','');
			if (initialHash && document.getElementById('page-'+initialHash)) {
				showPage(initialHash);
			} else {
				showPage('dashboard');
			}
			// React to subsequent hash changes
			window.addEventListener('hashchange', function(){
				const h = (window.location.hash||'').replace('#','');
				if (h && document.getElementById('page-'+h)) { showPage(h); }
			});
			const common={responsive:false,dom:'<"d-flex justify-content-between align-items-center mb-2"Bfl>rt<"d-flex justify-content-between align-items-center"ip>',buttons:[{extend:'copy',className:'btn btn-sm'},{extend:'excel',className:'btn btn-sm'},{extend:'csv',className:'btn btn-sm'}],pageLength:5,lengthMenu:[[5,10,25,50,-1],[5,10,25,50,'All']],autoWidth:false};
			$('#reservationTable').DataTable(Object.assign({},common,{order:[[1,'desc']]}));
			$('#transactionTable').DataTable(Object.assign({},common,{order:[[2,'desc']]}));
			// Removed welcome toast to avoid redundancy on dashboard
			// Review AJAX submit
			$('#reviewForm').on('submit',function(e){
				e.preventDefault();
				Swal.fire({title:'Submitting...',allowOutsideClick:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()});
				$.ajax({type:'POST',url:this.action,data:$(this).serialize(),dataType:'json'})
					.done(function(r){
						if(r&&r.success){
							Swal.fire({icon:'success',title:'Review submitted'}).then(()=>location.reload());
						}else{
							Swal.fire({icon:'error',title:r.message||'Failed to submit'});
						}
					})
					.fail(function(){
						Swal.fire({icon:'error',title:'Network error'});
					});
			});
		});
		function viewReservation(id){
			Swal.fire({title:'Loading details...',allowOutsideClick:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()});
			fetch('get_reservation.php?id='+encodeURIComponent(id),{credentials:'same-origin'})
				.then(r=>r.text())
				.then(t=>{ let d=null; try{ d=JSON.parse(t);}catch(_){ } if(!d||!d.success){ throw new Error((d&&d.message)||'Reservation not found'); }
					const fmt=(s)=>{ try{ return s? new Date(s).toLocaleString():'';}catch(_){ return s||''; } };
					const html=`
						<div class="text-start">
							<div><strong>Transaction ID:</strong> ${d.transaction_id}</div>
							<div><strong>Facility:</strong> ${d.facility_name}</div>
							<hr/>
							<div class="row g-2">
								<div class="col-md-6"><strong>Date Booked:</strong> ${fmt(d.date_booked)}</div>
								<div class="col-md-6"><strong>Check-in:</strong> ${fmt(d.date_checkin)}</div>
								<div class="col-md-6"><strong>Check-out:</strong> ${fmt(d.date_checkout)}</div>
								<div class="col-md-6"><strong>Payment Type:</strong> ${d.payment_type||''}</div>
								<div class="col-md-6"><strong>Amount Paid:</strong> ₱${Number(d.amount_paid||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>
								${(d.balance!==undefined)?`<div class="col-md-6"><strong>Balance:</strong> ₱${Number(d.balance||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>`:''}
							</div>
							<hr/>
							<div class="d-flex justify-content-end gap-2">
								<a class="btn btn-outline-secondary btn-sm" href="receipt.php?id=${encodeURIComponent(d.transaction_id)}" target="_blank"><i class="bi bi-receipt"></i> Receipt</a>
							</div>
						</div>`;
					Swal.fire({title:'Reservation Details',html:html,icon:'info',confirmButtonText:'Close'});
				})
				.catch(err=>{
					Swal.fire({icon:'error',title:(err&&err.message)?err.message:'Failed to load details'});
				});
		}
		function cancelReservation(id){Swal.fire({title:'Cancel reservation?',text:`Are you sure you want to cancel ${id}?`,icon:'warning',showCancelButton:true,confirmButtonText:'Yes, cancel'}).then(res=>{if(!res.isConfirmed)return;Swal.fire({title:'Submitting request...',allowOutsideClick:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()});
			$.post('request_cancellation.php',{transaction_id:id, csrf_token:'<?php echo addslashes($_SESSION['csrf_token'] ?? ''); ?>'}).done(function(r){if(r&&r.success){Swal.fire({icon:'success',title:'Cancellation requested'});}else{Swal.fire({icon:'error',title:r.message||'Failed to request'});} }).fail(function(){Swal.fire({icon:'error',title:'Network error'});});
		});}
		function rescheduleReservation(id, facility, checkin, checkout){
			const currentCheckin = new Date(checkin).toLocaleDateString();
			const currentCheckout = new Date(checkout).toLocaleDateString();
			const today = new Date().toISOString().split('T')[0];
			
			Swal.fire({
				title: 'Request Reschedule',
				html: `
					<div class="text-start">
						<p><strong>Current dates:</strong></p>
						<p>Check-in: ${currentCheckin}<br>Check-out: ${currentCheckout}</p>
						<hr>
						<div class="mb-3">
							<label class="form-label">New Check-in Date</label>
							<input type="date" id="new_checkin" class="form-control" min="${today}" onchange="validateRescheduleDates()">
							<div id="checkin_error" class="text-danger small mt-1" style="display:none;"></div>
						</div>
						<div class="mb-3">
							<label class="form-label">New Check-out Date</label>
							<input type="date" id="new_checkout" class="form-control" onchange="validateRescheduleDates()">
							<div id="checkout_error" class="text-danger small mt-1" style="display:none;"></div>
						</div>
						<div class="mb-3">
							<label class="form-label">Reason for reschedule</label>
							<textarea id="reschedule_reason" class="form-control" rows="3" placeholder="Please provide a reason for the reschedule request..." oninput="validateRescheduleReason()"></textarea>
							<div id="reason_error" class="text-danger small mt-1" style="display:none;"></div>
						</div>
						<div id="date_summary" class="alert alert-info small" style="display:none;">
							<strong>Date Summary:</strong><br>
							<span id="summary_text"></span>
						</div>
					</div>
				`,
				showCancelButton: true,
				confirmButtonText: 'Submit Request',
				confirmButtonColor: '#3085d6',
				cancelButtonColor: '#d33',
				allowOutsideClick: false,
				preConfirm: () => {
					return validateRescheduleForm();
				}
			}).then((result) => {
				if (result.isConfirmed) {
					Swal.fire({title:'Submitting request...',allowOutsideClick:false,showConfirmButton:false,didOpen:()=>Swal.showLoading()});
					$.post('request_reschedule.php',{
						transaction_id: id,
						new_checkin: result.value.newCheckin,
						new_checkout: result.value.newCheckout,
						reason: result.value.reason,
						csrf_token: '<?php echo addslashes($_SESSION['csrf_token'] ?? ''); ?>'
					}).done(function(r){
						if(r&&r.success){
							Swal.fire({icon:'success',title:'Reschedule requested',text:'Your request has been submitted and is pending admin approval.'});
						}else{
							Swal.fire({icon:'error',title:r.message||'Failed to request'});
						}
					}).fail(function(){
						Swal.fire({icon:'error',title:'Network error'});
					});
				}
			});
			
			// Add validation functions to window scope
			window.validateRescheduleDates = function() {
				const newCheckin = document.getElementById('new_checkin').value;
				const newCheckout = document.getElementById('new_checkout').value;
				const checkinError = document.getElementById('checkin_error');
				const checkoutError = document.getElementById('checkout_error');
				const dateSummary = document.getElementById('date_summary');
				const summaryText = document.getElementById('summary_text');
				
				// Reset errors
				checkinError.style.display = 'none';
				checkoutError.style.display = 'none';
				dateSummary.style.display = 'none';
				
				let hasError = false;
				
				// Validate check-in date
				if (newCheckin) {
					const checkinDate = new Date(newCheckin);
					const today = new Date();
					today.setHours(0, 0, 0, 0);
					
					if (checkinDate < today) {
						checkinError.textContent = 'Check-in date cannot be in the past';
						checkinError.style.display = 'block';
						hasError = true;
					}
				}
				
				// Validate check-out date
				if (newCheckout) {
					if (newCheckin && new Date(newCheckout) <= new Date(newCheckin)) {
						checkoutError.textContent = 'Check-out date must be after check-in date';
						checkoutError.style.display = 'block';
						hasError = true;
					}
				}
				
				// Show date summary if both dates are valid
				if (newCheckin && newCheckout && !hasError) {
					const checkinDate = new Date(newCheckin);
					const checkoutDate = new Date(newCheckout);
					const nights = Math.ceil((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
					
					summaryText.innerHTML = `
						Check-in: ${checkinDate.toLocaleDateString()}<br>
						Check-out: ${checkoutDate.toLocaleDateString()}<br>
						Duration: ${nights} night${nights > 1 ? 's' : ''}
					`;
					dateSummary.style.display = 'block';
				}
				
				return !hasError;
			};
			
			window.validateRescheduleReason = function() {
				const reason = document.getElementById('reschedule_reason').value;
				const reasonError = document.getElementById('reason_error');
				
				reasonError.style.display = 'none';
				
				if (reason.trim().length < 10) {
					reasonError.textContent = 'Please provide a detailed reason (at least 10 characters)';
					reasonError.style.display = 'block';
					return false;
				}
				
				return true;
			};
			
			window.validateRescheduleForm = function() {
				const newCheckin = document.getElementById('new_checkin').value;
				const newCheckout = document.getElementById('new_checkout').value;
				const reason = document.getElementById('reschedule_reason').value;
				
				// Validate all fields
				if (!newCheckin || !newCheckout) {
					Swal.showValidationMessage('Please select both check-in and check-out dates');
					return false;
				}
				
				if (!validateRescheduleDates()) {
					Swal.showValidationMessage('Please fix the date validation errors');
					return false;
				}
				
				if (!validateRescheduleReason()) {
					Swal.showValidationMessage('Please provide a detailed reason for reschedule');
					return false;
				}
				
				return { newCheckin, newCheckout, reason };
			};
		}
		function downloadReceipt(id){window.open('receipt.php?id='+encodeURIComponent(id), '_blank');}
		window.payBalanceOffline = async function(tx){
			await Swal.fire({ icon:'info', title:'Balance payment', text:'Please pay your remaining balance directly to the admin. They will confirm it in the system.', confirmButtonText:'OK' });
		}

		// Load facilities when facilities page is shown
		window.loadFacilities = function() {
			const container = document.getElementById('facilitiesContainer');
			if (!container) return;

			container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading facilities...</span></div></div>';

			fetch('retrieve_facility.php')
				.then(response => response.json())
				.then(facilities => {
					if (facilities.length === 0) {
						container.innerHTML = '<div class="col-12"><div class="alert alert-info">No facilities available at the moment.</div></div>';
						return;
					}

					container.innerHTML = facilities.map(facility => {
						const typeIcon = facility.type === 'room' ? '🏠' : 
									   facility.type === 'cottage' ? '🌳' : '🪑';
						const typeLabel = facility.type === 'room' ? 'Room' : 
										facility.type === 'cottage' ? 'Cottage' : 'Table';
						const typeClass = facility.type === 'room' ? 'primary' : 
										facility.type === 'cottage' ? 'success' : 'info';

						let pricingHtml = '';
						if (facility.pricing_info.can_overnight) {
							pricingHtml = `
								<div class="mb-2">
									<strong>Day-use Pricing:</strong>
									<ul class="list-unstyled small">
										<li>4 hours: ₱${facility.pricing_info.day_use['4_hours']}</li>
										<li>8 hours: ₱${facility.pricing_info.day_use['8_hours']}</li>
										<li>12 hours: ₱${facility.pricing_info.day_use['12_hours']}</li>
										<li>24+ hours: ₱${facility.pricing_info.day_use['24_hours']}</li>
									</ul>
								</div>
								<div class="mb-2">
									<strong>Overnight:</strong> ₱${facility.pricing_info.overnight.per_day} per day
								</div>
							`;
						} else {
							pricingHtml = `
								<div class="mb-2">
									<strong>Day-use Only:</strong>
									<ul class="list-unstyled small">
										<li>4 hours: ₱${facility.pricing_info.day_use['4_hours']}</li>
										<li>8 hours: ₱${facility.pricing_info.day_use['8_hours']}</li>
										<li>12 hours: ₱${facility.pricing_info.day_use['12_hours']}</li>
										<li>24+ hours: ₱${facility.pricing_info.day_use['24_hours']}</li>
									</ul>
								</div>
								<div class="text-muted small">No overnight bookings available</div>
							`;
						}

						return `
							<div class="col-lg-4 col-md-6 mb-3">
								<div class="card h-100">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-start mb-2">
											<h6 class="card-title mb-0">${facility.name}</h6>
											<span class="badge bg-${typeClass}">${typeIcon} ${typeLabel}</span>
										</div>
										<p class="card-text small text-muted">${facility.details || 'No description available'}</p>
										<p class="card-text small text-muted mb-2">
											<strong>Capacity:</strong> Up to ${facility.facility_capacity || 4} people
										</p>
										${pricingHtml}
										<div class="mt-auto">
											<a href="book_now.php" class="btn btn-primary btn-sm w-100">
												<i class="bi bi-calendar-plus"></i> Book Now
											</a>
										</div>
									</div>
								</div>
							</div>
						`;
					}).join('');
				})
				.catch(error => {
					console.error('Error loading facilities:', error);
					container.innerHTML = '<div class="col-12"><div class="alert alert-danger">Error loading facilities. Please try again.</div></div>';
				});
		};

		// Load facilities when facilities page is shown
		document.addEventListener('DOMContentLoaded', function() {
			// Add event listener for facilities page
			const facilitiesMenu = document.getElementById('menu-facilities');
			if (facilitiesMenu) {
				facilitiesMenu.addEventListener('click', function() {
					setTimeout(loadFacilities, 100); // Small delay to ensure page is shown
				});
			}
		});
	</script>
</body>
</html>


