<?php
session_start();
require '../properties/connection.php';

function sanitize($data) {
    return htmlspecialchars(trim($data));
}

$identifier = sanitize($_POST['email'] ?? ''); // may be email or username
$password = $_POST['password'] ?? '';

// ✅ SweetAlert2 Themed Function
function showSweetAlert($message, $redirectUrl, $type = 'success') {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login | Shelton Beach Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: url('../pics/bg.png') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Playfair Display', serif;
    }
    .swal2-popup {
      font-family: 'Playfair Display', serif;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
    }
    .swal2-title {
      font-size: 1.5rem;
    }
  </style>
</head>
<body>

<script>
  Swal.fire({
    icon: '$type',
    title: `$message`,
    showConfirmButton: false,
    timer: 2500,
    background: '#ffffffee',
    color: '#333',
    timerProgressBar: true,
    didClose: () => {
      window.location.href = '$redirectUrl';
    }
  });
</script>

</body>
</html>
HTML;
    exit;
}

// ✅ Validation
if (empty($identifier) || empty($password)) {
    showSweetAlert('❌ Please fill in all fields.', './index.php', 'error');
}

// ✅ Determine if identifier is email or username
$isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;

// ✅ Check user by email or username directly from base tables (no views)
// Build a unified users subquery from customer_tbl and admin_tbl
$userUnionSql =
    "SELECT * FROM (\n" .
    "  SELECT\n" .
    "    c.customer_id AS id,\n" .
    "    CONCAT(COALESCE(c.customer_fname,''),' ',COALESCE(c.customer_lname,'')) AS fullname,\n" .
    "    c.customer_email AS email,\n" .
    "    c.customer_number AS `number`,\n" .
    "    c.customer_user AS username,\n" .
    "    c.customer_pass AS `password`,\n" .
    "    c.customer_gender AS gender,\n" .
    "    'customer' AS role,\n" .
    "    c.customer_address AS address\n" .
    "  FROM customer_tbl c\n" .
    "  UNION ALL\n" .
    "  SELECT\n" .
    "    a.admin_id AS id,\n" .
    "    CONCAT(COALESCE(a.admin_fname,''),' ',COALESCE(a.admin_lname,'')) AS fullname,\n" .
    "    a.admin_email AS email,\n" .
    "    a.admin_number AS `number`,\n" .
    "    a.admin_user AS username,\n" .
    "    a.admin_pass AS `password`,\n" .
    "    a.admin_gender AS gender,\n" .
    "    'admin' AS role,\n" .
    "    a.admin_address AS address\n" .
    "  FROM admin_tbl a\n" .
    ") AS users_union WHERE ";

if ($isEmail) {
    $stmt = $conn->prepare($userUnionSql . "email = ?");
} else {
    $stmt = $conn->prepare($userUnionSql . "username = ?");
}

if (!$stmt) {
    // Log server-side detail, show friendly message client-side
    error_log('Login prepare failed: ' . $conn->error);
    showSweetAlert('⚠️ Server error during login. Please try again later or contact the administrator.', 'login.php', 'error');
}
$stmt->bind_param("s", $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        // Ensure banned_users table exists and block banned customers from logging in
        @$conn->query("CREATE TABLE IF NOT EXISTS banned_users (\n  user_id INT PRIMARY KEY,\n  created_at DATETIME DEFAULT CURRENT_TIMESTAMP\n)");

        if ($user['role'] !== 'admin') {
            $banStmt = $conn->prepare("SELECT 1 FROM banned_users WHERE user_id = ? LIMIT 1");
            $banStmt->bind_param("i", $user['id']);
            $banStmt->execute();
            $banStmt->store_result();
            if ($banStmt->num_rows > 0) {
                $banStmt->close();
                showSweetAlert('🚫 Your account has been banned. Please contact support.', 'login.php', 'error');
            }
            $banStmt->close();
        }

        $_SESSION['user'] = [
            'id'       => $user['id'],
            'fullname' => $user['fullname'],
            'email'    => $user['email'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];
        $_SESSION['fullname'] = $user['fullname'];

        $welcomeMessage = "✅ Welcome back, " . htmlspecialchars($user['fullname']) . "!";

        if ($user['role'] === 'admin') {
            showSweetAlert($welcomeMessage, '../admindash/admindash.php', 'success');
        } else {
            showSweetAlert($welcomeMessage, '../customerdash/cusdash.php', 'success');
        }
    } else {
        showSweetAlert('❌ Incorrect password.', 'login.php', 'error');
    }
} else {
    showSweetAlert('❌ No account found with that email/username.', 'login.php', 'error');
}

$stmt->close();
$conn->close();
?>
