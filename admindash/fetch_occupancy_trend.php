<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$range = $_GET['range'] ?? 'monthly';
$range = in_array($range, ['daily','weekly','monthly','yearly'], true) ? $range : 'monthly';

// Total facilities (avoid division by zero)
$totalFacilities = 0;
if ($res = $conn->query("SELECT COUNT(*) AS c FROM facility")) {
	$row = $res->fetch_assoc();
	$totalFacilities = intval($row['c'] ?? 0);
}
if ($totalFacilities <= 0) {
	echo json_encode(['labels'=>[], 'counts'=>[], 'percent'=>[]]);
	exit;
}

// Build date window based on range
$endDate = new DateTime('today');
if ($range === 'daily') {
	$startDate = (clone $endDate)->modify('-29 days');
}
elseif ($range === 'weekly') {
	$startDate = (clone $endDate)->modify('-83 days'); // last 12 weeks (12*7-1)
}
elseif ($range === 'yearly') {
	$startDate = (clone $endDate)->modify('-4 years')->modify('first day of january');
	$endDate = (clone $endDate)->modify('last day of december');
}
else { // monthly (default): last 12 months including current month
	$startDate = (clone $endDate)->modify('first day of this month')->modify('-11 months');
	$endDate = (clone $endDate)->modify('last day of this month');
}

// Initialize daily buckets for the entire window
$dailyCounts = [];
for ($d = clone $startDate; $d <= $endDate; $d->modify('+1 day')) {
	$dailyCounts[$d->format('Y-m-d')] = 0;
}

// Fetch confirmed receipts overlapping window and increment daily occupancy per facility-day
$s = $conn->real_escape_string($startDate->format('Y-m-d').' 00:00:00');
$e = $conn->real_escape_string($endDate->format('Y-m-d').' 23:59:59');
$sql = "SELECT date_checkin, date_checkout FROM receipt WHERE balance = 0 AND date_checkout >= '$s' AND date_checkin <= '$e'";
if ($res = $conn->query($sql)) {
	while ($row = $res->fetch_assoc()) {
		$rs = new DateTime($row['date_checkin']);
		$re = new DateTime($row['date_checkout']);
		if ($rs < $startDate) $rs = clone $startDate;
		if ($re > $endDate) $re = clone $endDate;
		for ($d = clone $rs; $d <= $re; $d->modify('+1 day')) {
			$key = $d->format('Y-m-d');
			if (isset($dailyCounts[$key])) { $dailyCounts[$key] += 1; }
		}
	}
}

// Aggregate based on range
$labels = [];
$counts = [];
$percent = [];

if ($range === 'daily') {
	// Use each day, label as Mon D (e.g., Aug 12)
	for ($d = clone $startDate; $d <= $endDate; $d->modify('+1 day')) {
		$key = $d->format('Y-m-d');
		$labels[] = $d->format('M j');
		$c = (float)($dailyCounts[$key] ?? 0);
		$counts[] = $c;
		$percent[] = round(($c / $totalFacilities) * 100, 2);
	}
}
elseif ($range === 'weekly') {
	// 12 buckets of 7 days each ending today; label by ISO week (e.g., W32)
	for ($i = 11; $i >= 0; $i--) {
		$start = (clone $endDate)->modify('-'.($i*7 + 6).' days');
		$end = (clone $endDate)->modify('-'.($i*7).' days');
		$sum = 0.0; $n = 0;
		for ($d = clone $start; $d <= $end; $d->modify('+1 day')) { $sum += (float)($dailyCounts[$d->format('Y-m-d')] ?? 0); $n++; }
		$avg = $n > 0 ? $sum / $n : 0.0;
		$labels[] = 'W'. $end->format('o-W');
		$counts[] = round($avg, 2);
		$percent[] = round(($avg / $totalFacilities) * 100, 2);
	}
}
elseif ($range === 'yearly') {
	$curYear = (int)date('Y');
	for ($y = $curYear - 4; $y <= $curYear; $y++) {
		$ys = new DateTime($y.'-01-01');
		$ye = new DateTime($y.'-12-31');
		$sum = 0.0; $n = 0;
		for ($d = clone $ys; $d <= $ye; $d->modify('+1 day')) { $sum += (float)($dailyCounts[$d->format('Y-m-d')] ?? 0); $n++; }
		$avg = $n > 0 ? $sum / $n : 0.0;
		$labels[] = (string)$y;
		$counts[] = round($avg, 2);
		$percent[] = round(($avg / $totalFacilities) * 100, 2);
	}
}
else { // monthly
	for ($i = 11; $i >= 0; $i--) {
		$ts = (clone $endDate)->modify('-'.$i.' months');
		$ms = (clone $ts)->modify('first day of this month');
		$me = (clone $ts)->modify('last day of this month');
		$sum = 0.0; $n = 0;
		for ($d = clone $ms; $d <= $me; $d->modify('+1 day')) { $sum += (float)($dailyCounts[$d->format('Y-m-d')] ?? 0); $n++; }
		$avg = $n > 0 ? $sum / $n : 0.0;
		$labels[] = $ms->format('M');
		$counts[] = round($avg, 2);
		$percent[] = round(($avg / $totalFacilities) * 100, 2);
	}
}

echo json_encode(['labels'=>$labels, 'counts'=>$counts, 'percent'=>$percent, 'total_facilities'=>$totalFacilities]);
?>


 