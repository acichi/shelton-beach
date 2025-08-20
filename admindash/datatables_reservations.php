<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

$draw = intval($_GET['draw'] ?? 0);
$start = intval($_GET['start'] ?? 0);
$length = intval($_GET['length'] ?? 10);
$searchValue = trim($_GET['search']['value'] ?? '');

// Filters removed from UI; keep parameters optional but unused
$status = '';
$facility = '';
$dateStart = '';
$dateEnd = '';

// Ordering
$orderColIndex = intval($_GET['order'][0]['column'] ?? 0);
$orderDir = (strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc') ? 'ASC' : 'DESC';
// Map DataTables column indices to database columns (no checkbox column now)
$columnMap = [
  'id',            // 0: ID
  'reservee',      // 1: Reservee
  'facility_name', // 2: Facility
  'date_start',    // 3: Check-in
  'date_end',      // 4: Check-out
  'status',        // 5: Status
  'amount',        // 6: Amount
  null             // 7: Change Status (select)
];
$orderCol = $columnMap[$orderColIndex] ?? 'date_start';
if ($orderCol === null) { $orderCol = 'date_start'; }

// Base queries
$where = [];
if ($searchValue !== '') {
  $sv = '%' . $conn->real_escape_string($searchValue) . '%';
  $where[] = "(reservee LIKE '$sv' OR facility_name LIKE '$sv' OR status LIKE '$sv' OR payment_type LIKE '$sv')";
}
// Exclude 'pending' from All Reservations view; those are handled under Pending Cash Receipts
$where[] = "status IN ('confirmed','cancelled')";

$whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

// Total counts (restricted to confirmed + cancelled)
$totalRes = $conn->query("SELECT COUNT(*) AS c FROM reservations WHERE status IN ('confirmed','cancelled')");
$recordsTotal = ($totalRes && ($row = $totalRes->fetch_assoc())) ? intval($row['c']) : 0;

$filteredRes = $conn->query("SELECT COUNT(*) AS c FROM reservations $whereSql");
$recordsFiltered = ($filteredRes && ($row = $filteredRes->fetch_assoc())) ? intval($row['c']) : 0;

// Page data (no pending rows)
$sql = "SELECT id, reservee, facility_name, status, date_booked, date_start, date_end, payment_type, amount
        FROM reservations $whereSql
        ORDER BY $orderCol $orderDir
        LIMIT $start, $length";

$data = [];
if ($res = $conn->query($sql)) {
  while ($r = $res->fetch_assoc()) {
    $data[] = [
      $r['id'],
      $r['reservee'],
      $r['facility_name'],
      $r['date_start'],
      $r['date_end'],
      $r['status'],
      $r['amount']
    ];
  }
}

echo json_encode([
  'draw' => $draw,
  'recordsTotal' => $recordsTotal,
  'recordsFiltered' => $recordsFiltered,
  'data' => $data
]);
?>


