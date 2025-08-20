<?php
session_start();
require '../properties/connection.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$token = isset($input['token']) ? $input['token'] : '';
$email = isset($input['email']) ? htmlspecialchars(trim($input['email'])) : '';

$response = [
	'success' => false,
	'message' => '',
	'redirect' => ''
];

// Validate input
if (empty($token) || empty($email)) {
	$response['message'] = 'Invalid token or email';
	echo json_encode($response);
	exit;
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
	$response['success'] = true;
	$response['message'] = 'Already logged in';
	
	// Set redirect based on role
	if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin') {
		$response['redirect'] = 'admindash/admindash.php';
	} else {
		$response['redirect'] = 'customerdash/cusdash.php';
	}
	
	echo json_encode($response);
	exit;
}

// Verify token and get user data from base tables (customers only)
$stmt = $conn->prepare("\n    SELECT\n      c.customer_id AS id,\n      CONCAT(COALESCE(c.customer_fname,''),' ',COALESCE(c.customer_lname,'')) AS fullname,\n      c.customer_email AS email,\n      c.customer_user AS username,\n      'customer' AS role,\n      rt.expires_at\n    FROM customer_tbl c\n    INNER JOIN remember_tokens_tbl rt ON c.customer_id = rt.customer_id\n    WHERE rt.token = ? AND c.customer_email = ? AND rt.expires_at > NOW()\n");
$stmt->bind_param("ss", $token, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
	$response['message'] = 'Invalid or expired token';
	echo json_encode($response);
	exit;
}

$user = $result->fetch_assoc();

// Check if token has expired
if (strtotime($user['expires_at']) < time()) {
	// Clean up expired token
	$cleanupStmt = $conn->prepare("DELETE FROM remember_tokens_tbl WHERE token = ?");
	$cleanupStmt->bind_param("s", $token);
	$cleanupStmt->execute();
	$cleanupStmt->close();
	
	$response['message'] = 'Token has expired';
	echo json_encode($response);
	exit;
}

// Before logging in, block banned customers (admins are exempt)
@($conn->query("CREATE TABLE IF NOT EXISTS banned_users (\n  user_id INT PRIMARY KEY,\n  created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n)"));
if ($user['role'] !== 'admin') {
	$banStmt = $conn->prepare("SELECT 1 FROM banned_users WHERE user_id = ? LIMIT 1");
	$banStmt->bind_param("i", $user['id']);
	$banStmt->execute();
	$banStmt->store_result();
	if ($banStmt->num_rows > 0) {
		$banStmt->close();
		$response['message'] = 'Account banned. Please contact support.';
		echo json_encode($response);
		exit;
	}
	$banStmt->close();
}

// Token is valid, log user in
$_SESSION['user'] = [
	'id' => $user['id'],
	'fullname' => $user['fullname'],
	'email' => $user['email'],
	'username' => $user['username'] ?? '',
	'role' => $user['role'],
];
$_SESSION['fullname'] = $user['fullname'];
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['fullname'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'];

// Set redirect based on role
if ($user['role'] === 'admin') {
	$response['redirect'] = 'admindash/admindash.php';
} else {
	$response['redirect'] = 'customerdash/cusdash.php';
}

$response['success'] = true;
$response['message'] = 'Welcome back, ' . htmlspecialchars($user['fullname']) . '!';

$stmt->close();
$conn->close();

// Send JSON response
echo json_encode($response);
exit;
?>
