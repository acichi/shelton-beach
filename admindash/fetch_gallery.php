<?php
require '../properties/connection.php';
require __DIR__ . '/_auth.php';
header('Content-Type: application/json');

// Resolve gallery table (prefer `gallery`, fallback to legacy `gallery_tbl`)
$useLegacy = false;
try {
    $dbRes = $conn->query("SELECT DATABASE() AS db");
    $dbRow = $dbRes ? $dbRes->fetch_assoc() : null;
    $dbName = $dbRow ? $dbRow['db'] : '';
    if ($dbName) {
        $escDb = $conn->real_escape_string($dbName);
        $q = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='${escDb}' AND TABLE_NAME IN ('gallery','gallery_tbl')";
        if ($t = $conn->query($q)) {
            $found = [];
            while ($tr = $t->fetch_assoc()) { $found[] = $tr['TABLE_NAME']; }
            if (!in_array('gallery', $found, true) && in_array('gallery_tbl', $found, true)) { $useLegacy = true; }
        }
    }
} catch (Throwable $e) {}
$sql = $useLegacy
    ? "SELECT gallery_id AS id, gallery_description AS description, gallery_image AS location, gallery_date_added AS date_added FROM gallery_tbl ORDER BY gallery_date_added DESC, gallery_id DESC"
    : "SELECT id, description, location, date_added FROM gallery ORDER BY date_added DESC, id DESC";

$rows = [];
if ($res = $conn->query("SELECT id, description, location, date_added FROM gallery ORDER BY date_added DESC, id DESC")) {
	while ($r = $res->fetch_assoc()) {
		$loc = trim($r['location'] ?? '');
		if ($loc !== '') {
			if (preg_match('~^(https?:)?//~', $loc) || strpos($loc, '/') === 0) {
				// absolute URL or root-relative; keep as-is
			} else if (strpos($loc, '/') === false) {
				// bare filename: try uploads/gallery first, then admindash/images
				if (file_exists('../uploads/gallery/' . $loc)) {
					$loc = 'uploads/gallery/' . $loc;
				} else if (file_exists(__DIR__ . '/images/' . $loc)) {
					$loc = 'admindash/images/' . $loc;
				} else {
					$loc = 'uploads/gallery/' . $loc; // default
				}
			}
		}
		$r['location'] = $loc;
		$rows[] = $r;
	}
}

echo json_encode($rows);
?>



