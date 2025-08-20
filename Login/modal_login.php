<?php
session_start();
require '../properties/connection.php';

header('Content-Type: application/json');

// Get and sanitize input (can be email or username)
$identifier = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$rememberMe = isset($_POST['remember']) && $_POST['remember'] === 'on';

$response = [
    'success' => false,
    'message' => '',
    'redirect' => '',
    'remember_token' => ''
];

// Validate input
if (empty($identifier) || empty($password)) {
    $response['message'] = 'Please fill in all fields';
    echo json_encode($response);
    exit;
}

// Determine if identifier is an email or a username
$isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

// Check user in database (by email or username) using base tables (no views)
$userUnionSql =
    "SELECT * FROM (\n" .
    "  SELECT\n" .
    "    c.customer_id AS id,\n" .
    "    CONCAT(COALESCE(c.customer_fname,''),' ',COALESCE(c.customer_lname,'')) AS fullname,\n" .
    "    c.customer_email AS email,\n" .
    "    c.customer_user AS username,\n" .
    "    c.customer_pass AS `password`,\n" .
    "    'customer' AS role\n" .
    "  FROM customer_tbl c\n" .
    "  UNION ALL\n" .
    "  SELECT\n" .
    "    a.admin_id AS id,\n" .
    "    CONCAT(COALESCE(a.admin_fname,''),' ',COALESCE(a.admin_lname,'')) AS fullname,\n" .
    "    a.admin_email AS email,\n" .
    "    a.admin_user AS username,\n" .
    "    a.admin_pass AS `password`,\n" .
    "    'admin' AS role\n" .
    "  FROM admin_tbl a\n" .
    ") AS users_union WHERE ";

if ($isEmail) {
    $stmt = $conn->prepare($userUnionSql . "email = ?");
} else {
    $stmt = $conn->prepare($userUnionSql . "username = ?");
}
$stmt->bind_param("s", $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'No account found with that email/username';
    echo json_encode($response);
    exit;
}

$user = $result->fetch_assoc();

if (password_verify($password, $user['password'])) {
    // Set session data
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

    // Handle Remember Me functionality (customers only; use remember_tokens_tbl)
    if ($rememberMe && $user['role'] === 'customer') {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        $tokenStmt = $conn->prepare("INSERT INTO remember_tokens_tbl (customer_id, token, expires_at) VALUES (?, ?, ?)");
        $tokenStmt->bind_param("iss", $user['id'], $token, $expires);
        if ($tokenStmt->execute()) {
            setcookie('remember_token', $token, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            $response['remember_token'] = $token;
            $response['user_email'] = $user['email'];
        }
        $tokenStmt->close();
    }

    // Set redirect based on role
    if ($user['role'] === 'admin') {
        $response['redirect'] = 'admindash/admindash.php';
    } else {
        $response['redirect'] = 'customerdash/cusdash.php';
    }

    $response['success'] = true;
    $response['message'] = 'Welcome back, ' . htmlspecialchars($user['fullname']) . '!';
    // Ensure user_email is present for remember me even when remember is off
    if (!isset($response['user_email'])) {
        $response['user_email'] = $user['email'];
    }
} else {
    $response['message'] = 'Incorrect password';
}

$stmt->close();
$conn->close();

// Send JSON response
echo json_encode($response);
exit;
?>
