<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$range = $_GET['range'] ?? 'monthly';
$range = in_array($range, ['daily','weekly','monthly','yearly'], true) ? $range : 'monthly';

$labels = [];
$registered = [];
$walkin = [];
$totalReg = 0.0;
$totalWalk = 0.0;

function pushDual(&$labels, &$registered, &$walkin, $label, $regVal, $walkVal){
  $labels[] = $label;
  $registered[] = (float)$regVal;
  $walkin[] = (float)$walkVal;
}

// Detect if receipt.source column exists for accurate split
$hasSource = false;
$hasCreatedByAdmin = false;
try {
  $db = $conn->query("SELECT DATABASE() AS db");
  $dbRow = $db ? $db->fetch_assoc() : null;
  $dbName = $dbRow ? $dbRow['db'] : '';
  if ($dbName) {
    $escDb = $conn->real_escape_string($dbName);
    $q1 = sprintf("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='%s' AND TABLE_NAME='receipt' AND COLUMN_NAME='source'", $escDb);
    if ($res = $conn->query($q1)) { $hasSource = $res->num_rows > 0; }
    $q2 = sprintf("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='%s' AND TABLE_NAME='receipt' AND COLUMN_NAME='created_by_admin'", $escDb);
    if ($res2 = $conn->query($q2)) { $hasCreatedByAdmin = $res2->num_rows > 0; }
  }
} catch (Throwable $e) { /* ignore */ }

if ($range === 'daily') {
  // Today by hour: split registered vs walk-in using users.fullname match
  $rows = [];
  if ($hasSource) {
    $sql = "SELECT HOUR(r.timestamp) h,
                   SUM(CASE WHEN r.source='customer' THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN r.source='walkin' THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0 AND DATE(r.timestamp) = CURDATE()
            GROUP BY HOUR(r.timestamp)";
  } elseif ($hasCreatedByAdmin) {
    $sql = "SELECT HOUR(r.timestamp) h,
                   SUM(CASE WHEN IFNULL(r.created_by_admin,0)=0 THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN IFNULL(r.created_by_admin,0)=1 THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0 AND DATE(r.timestamp) = CURDATE()
            GROUP BY HOUR(r.timestamp)";
  } else {
    $sql = "SELECT HOUR(r.timestamp) h,
                   SUM(CASE WHEN EXISTS (SELECT 1 FROM users u WHERE u.fullname = r.reservee LIMIT 1) THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM users u WHERE u.fullname = r.reservee LIMIT 1) THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0 AND DATE(r.timestamp) = CURDATE()
            GROUP BY HOUR(r.timestamp)";
  }
  if ($res = $conn->query($sql)) {
    while ($r = $res->fetch_assoc()) { $rows[(int)$r['h']] = ['reg'=>(float)$r['reg_sum'], 'walk'=>(float)$r['walk_sum']]; }
  }
  for ($h=0; $h<24; $h++) {
    $label = date('g A', mktime($h,0));
    $reg = isset($rows[$h]) ? $rows[$h]['reg'] : 0.0;
    $walk = isset($rows[$h]) ? $rows[$h]['walk'] : 0.0;
    $totalReg += $reg; $totalWalk += $walk; pushDual($labels, $registered, $walkin, $label, $reg, $walk);
  }
}
elseif ($range === 'weekly') {
  // Last 7 days split
  $map = [];
  if ($hasSource) {
    $sql = "SELECT DATE(r.timestamp) d,
                   SUM(CASE WHEN r.source='customer' THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN r.source='walkin' THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0 AND DATE(r.timestamp) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(r.timestamp)";
  } elseif ($hasCreatedByAdmin) {
    $sql = "SELECT DATE(r.timestamp) d,
                   SUM(CASE WHEN IFNULL(r.created_by_admin,0)=0 THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN IFNULL(r.created_by_admin,0)=1 THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0 AND DATE(r.timestamp) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(r.timestamp)";
  } else {
    $sql = "SELECT DATE(r.timestamp) d,
                   SUM(CASE WHEN EXISTS (SELECT 1 FROM users u WHERE u.fullname = r.reservee LIMIT 1) THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM users u WHERE u.fullname = r.reservee LIMIT 1) THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0 AND DATE(r.timestamp) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(r.timestamp)";
  }
  if ($res = $conn->query($sql)) {
    while ($r = $res->fetch_assoc()) { $map[$r['d']] = ['reg'=>(float)$r['reg_sum'], 'walk'=>(float)$r['walk_sum']]; }
  }
  for ($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $label = date('M j', strtotime($d));
    $reg = isset($map[$d]) ? $map[$d]['reg'] : 0.0;
    $walk = isset($map[$d]) ? $map[$d]['walk'] : 0.0;
    $totalReg += $reg; $totalWalk += $walk; pushDual($labels, $registered, $walkin, $label, $reg, $walk);
  }
}
elseif ($range === 'yearly') {
  // Last 5 years split
  $yearMap = [];
  if ($hasSource) {
    $sql = "SELECT YEAR(r.timestamp) y,
                   SUM(CASE WHEN r.source='customer' THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN r.source='walkin' THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0
            GROUP BY YEAR(r.timestamp)";
  } elseif ($hasCreatedByAdmin) {
    $sql = "SELECT YEAR(r.timestamp) y,
                   SUM(CASE WHEN IFNULL(r.created_by_admin,0)=0 THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN IFNULL(r.created_by_admin,0)=1 THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0
            GROUP BY YEAR(r.timestamp)";
  } else {
    $sql = "SELECT YEAR(r.timestamp) y,
                   SUM(CASE WHEN EXISTS (SELECT 1 FROM users u WHERE u.fullname = r.reservee LIMIT 1) THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM users u WHERE u.fullname = r.reservee LIMIT 1) THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0
            GROUP BY YEAR(r.timestamp)";
  }
  if ($res = $conn->query($sql)) {
    while ($r = $res->fetch_assoc()) { $yearMap[(int)$r['y']] = ['reg'=>(float)$r['reg_sum'], 'walk'=>(float)$r['walk_sum']]; }
  }
  $cur = (int)date('Y');
  for ($y = $cur-4; $y <= $cur; $y++) {
    $reg = isset($yearMap[$y]) ? $yearMap[$y]['reg'] : 0.0;
    $walk = isset($yearMap[$y]) ? $yearMap[$y]['walk'] : 0.0;
    $totalReg += $reg; $totalWalk += $walk; pushDual($labels, $registered, $walkin, (string)$y, $reg, $walk);
  }
}
else {
  // Monthly: rolling last 12 months (including current month) split
  $monthMap = [];
  if ($hasSource) {
    $sql = "SELECT DATE_FORMAT(r.timestamp, '%Y-%m') ym,
                   SUM(CASE WHEN r.source='customer' THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN r.source='walkin' THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0
              AND r.timestamp >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01')
            GROUP BY ym";
  } elseif ($hasCreatedByAdmin) {
    $sql = "SELECT DATE_FORMAT(r.timestamp, '%Y-%m') ym,
                   SUM(CASE WHEN IFNULL(r.created_by_admin,0)=0 THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN IFNULL(r.created_by_admin,0)=1 THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0
              AND r.timestamp >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01')
            GROUP BY ym";
  } else {
    $sql = "SELECT DATE_FORMAT(r.timestamp, '%Y-%m') ym,
                   SUM(CASE WHEN EXISTS (SELECT 1 FROM users u WHERE u.fullname = r.reservee LIMIT 1) THEN r.amount_paid ELSE 0 END) AS reg_sum,
                   SUM(CASE WHEN NOT EXISTS (SELECT 1 FROM users u WHERE u.fullname = r.reservee LIMIT 1) THEN r.amount_paid ELSE 0 END) AS walk_sum
            FROM receipt r
            WHERE r.balance = 0
              AND r.timestamp >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01')
            GROUP BY ym";
  }
  if ($res = $conn->query($sql)) {
    while ($r = $res->fetch_assoc()) { $monthMap[$r['ym']] = ['reg'=>(float)$r['reg_sum'], 'walk'=>(float)$r['walk_sum']]; }
  }
  for ($i = 11; $i >= 0; $i--) {
    $ts = strtotime("-{$i} month");
    $key = date('Y-m', $ts);
    $label = date('M', $ts);
    $reg = isset($monthMap[$key]) ? $monthMap[$key]['reg'] : 0.0;
    $walk = isset($monthMap[$key]) ? $monthMap[$key]['walk'] : 0.0;
    $totalReg += $reg; $totalWalk += $walk; pushDual($labels, $registered, $walkin, $label, $reg, $walk);
  }
}

echo json_encode([
  'labels' => $labels,
  // keep counts for backward compatibility (sum of two)
  'counts' => array_map(function($i) use ($registered,$walkin){ return (float)(($registered[$i] ?? 0) + ($walkin[$i] ?? 0)); }, array_keys($labels)),
  'registered' => $registered,
  'walkin' => $walkin,
  'total_registered' => number_format($totalReg, 2, '.', ''),
  'total_walkin' => number_format($totalWalk, 2, '.', ''),
  'total'  => number_format($totalReg + $totalWalk, 2, '.', '')
]);
?>


