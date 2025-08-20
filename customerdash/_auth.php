<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Require logged-in user (any non-admin role allowed)
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
	$redirect = isset($_SERVER['REQUEST_URI']) ? urlencode($_SERVER['REQUEST_URI']) : urlencode('../customerdash/cusdash.php');
	header('Location: ../index.php?auth=login&redirect=' . $redirect);
	exit;
}

// If an admin opens this page, send them to admin dashboard to avoid confusion
$role = strtolower((string)($_SESSION['user']['role'] ?? ''));
if ($role === 'admin') {
	header('Location: ../admindash/admindash.php');
	exit;
}

// Block banned customers from accessing customer pages
require_once '../properties/connection.php';
@$_banTbl = $conn->query("CREATE TABLE IF NOT EXISTS banned_users (\n  user_id INT PRIMARY KEY,\n  created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n)");
$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId > 0) {
	$banStmt = $conn->prepare("SELECT 1 FROM banned_users WHERE user_id = ? LIMIT 1");
	$banStmt->bind_param("i", $userId);
	$banStmt->execute();
	$banStmt->store_result();
	if ($banStmt->num_rows > 0) {
		$banStmt->close();
		// Destroy session and redirect to homepage with message
		$_SESSION = [];
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
		session_destroy();
		header('Location: ../index.php?banned=1');
		exit;
	}
	$banStmt->close();
}

// CSRF token bootstrap (lazy-init per session)
if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
	try {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	} catch (Throwable $e) {
		$_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
	}
}
?>


