<?php
// Ensure session is started with safer cookie flags
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.cookie_httponly', '1');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        @ini_set('session.cookie_secure', '1');
        @ini_set('session.cookie_samesite', 'Strict');
    } else {
        @ini_set('session.cookie_samesite', 'Lax');
    }
    session_start();
}

// Determine role from either new or legacy session formats
$role = null;
if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user']['role'])) {
	$role = strtolower((string)$_SESSION['user']['role']);
} elseif (isset($_SESSION['user_role'])) {
	$role = strtolower((string)$_SESSION['user_role']);
}

if ($role !== 'admin') {
	// If request expects JSON (AJAX/API), return 403 JSON
	$accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
	$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
	$isFetch = isset($_SERVER['HTTP_SEC_FETCH_MODE']); // modern fetch requests send this
	if ($isAjax || $isFetch || stripos($accept, 'application/json') !== false) {
		http_response_code(403);
		echo json_encode(['success' => false, 'error' => 'Forbidden: admin access required']);
		exit;
	}
	// Otherwise redirect to homepage
	header('Location: ../index.php');
	exit;
}

// CSRF token bootstrap for admin as well (lazy-init per session)
if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Throwable $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}
?>


