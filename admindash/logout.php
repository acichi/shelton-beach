<?php
session_start();

// Remove remember token from database if present
if (isset($_COOKIE['remember_token']) && $_COOKIE['remember_token'] !== '') {
	$token = $_COOKIE['remember_token'];
	require __DIR__ . '/../properties/connection.php';
	if ($conn) {
		if ($stmt = $conn->prepare('DELETE FROM remember_tokens WHERE token = ?')) {
			$stmt->bind_param('s', $token);
			@$stmt->execute();
			$stmt->close();
		}
		$conn->close();
	}
	// Expire cookie in browser
	setcookie('remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
}

// Clear session data and cookie
$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], true);
}
session_destroy();

// Redirect to home
header('Location: ../index.php');
exit;


 

