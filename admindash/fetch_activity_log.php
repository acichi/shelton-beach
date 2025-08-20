<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

header('Content-Type: application/json');

$eventTypeParam = trim($_GET['event_type'] ?? '');
$actor = trim($_GET['actor'] ?? '');
$q = trim($_GET['q'] ?? '');
$start = trim($_GET['start_date'] ?? '');
$end = trim($_GET['end_date'] ?? '');
$limit = (int)($_GET['limit'] ?? 500);
if ($limit <= 0 || $limit > 1000) { $limit = 500; }

$conds = [];
$params = [];
$types = '';

// Discover existing columns for backward compatibility
$colSet = [];
if ($rr = @$conn->query("SHOW COLUMNS FROM activity_log")) {
    while ($c = $rr->fetch_assoc()) {
        $name = isset($c['Field']) ? (string)$c['Field'] : '';
        if ($name !== '') { $colSet[$name] = true; }
    }
    $rr->close();
}

// Support CSV list for event types
$eventTypes = [];
if ($eventTypeParam !== '' && isset($colSet['event_type'])) {
    foreach (explode(',', $eventTypeParam) as $et) {
        $et = trim($et);
        if ($et !== '') { $eventTypes[$et] = true; }
    }
    if (!empty($eventTypes)) {
        $placeholders = implode(',', array_fill(0, count($eventTypes), '?'));
        $conds[] = "event_type IN ($placeholders)";
        $types .= str_repeat('s', count($eventTypes));
        foreach (array_keys($eventTypes) as $et) { $params[] = $et; }
    }
}
if ($actor !== '' && isset($colSet['actor'])) { $conds[] = 'actor = ?'; $types .= 's'; $params[] = $actor; }
if ($start !== '' && isset($colSet['created_at'])) { $conds[] = 'created_at >= ?'; $types .= 's'; $params[] = $start . ' 00:00:00'; }
if ($end !== '' && isset($colSet['created_at'])) { $conds[] = 'created_at <= ?'; $types .= 's'; $params[] = $end . ' 23:59:59'; }
if ($q !== '') {
    $likeConds = [];
    $likeParams = [];
    if (isset($colSet['message'])) { $likeConds[] = 'message LIKE ?'; $likeParams[] = '%' . $q . '%'; }
    if (isset($colSet['reservee'])) { $likeConds[] = 'reservee LIKE ?'; $likeParams[] = '%' . $q . '%'; }
    if (isset($colSet['facility_name'])) { $likeConds[] = 'facility_name LIKE ?'; $likeParams[] = '%' . $q . '%'; }
    if (isset($colSet['ref_transaction_id'])) { $likeConds[] = 'ref_transaction_id LIKE ?'; $likeParams[] = '%' . $q . '%'; }
    if (!empty($likeConds)) {
        $conds[] = '(' . implode(' OR ', $likeConds) . ')';
        $types .= str_repeat('s', count($likeParams));
        foreach ($likeParams as $p) { $params[] = $p; }
    }
}

$where = count($conds) ? ('WHERE ' . implode(' AND ', $conds)) : '';

// Build select list based on existing columns; alias missing ones for a stable API
$selectList = [];
$wantCols = ['id','event_type','actor','user_id','message','ref_reservation_id','ref_transaction_id','reservee','facility_name','amount','metadata','ip','user_agent','created_at'];
foreach ($wantCols as $wc) {
    if (isset($colSet[$wc])) { $selectList[] = $wc; }
    else { $selectList[] = 'NULL AS ' . $wc; }
}
$selectCols = implode(', ', $selectList);
$sql = "SELECT $selectCols FROM activity_log $where ORDER BY id DESC LIMIT ?";
$types .= 'i';
$params[] = $limit;

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success'=>false, 'error'=>'Query prepare failed']);
    exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = [
        'id' => (int)$row['id'],
        'event_type' => (string)$row['event_type'],
        'actor' => (string)$row['actor'],
        'user_id' => isset($row['user_id']) ? (int)$row['user_id'] : null,
        'message' => (string)$row['message'],
        'ref_reservation_id' => isset($row['ref_reservation_id']) ? (int)$row['ref_reservation_id'] : null,
        'ref_transaction_id' => $row['ref_transaction_id'],
        'reservee' => $row['reservee'],
        'facility_name' => $row['facility_name'],
        'amount' => isset($row['amount']) ? (float)$row['amount'] : null,
        'metadata' => $row['metadata'],
        'ip' => $row['ip'],
        'user_agent' => $row['user_agent'],
        'created_at' => (string)$row['created_at'],
    ];
}
$stmt->close();

echo json_encode(['success'=>true, 'rows'=>$rows]);
exit;
?>


