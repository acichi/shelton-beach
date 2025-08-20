<?php
session_start();

require_once __DIR__ . '/../properties/connection.php';
require_once __DIR__ . '/../config/env.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function sanitize($value) {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function normalize_gender($input) {
    $g = strtolower(trim((string)$input));
    if ($g === 'male' || $g === 'm') return 'Male';
    if ($g === 'female' || $g === 'f') return 'Female';
    return 'Other';
}

function table_exists_base(mysqli $conn, string $table): bool {
    $res = $conn->query("SHOW FULL TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    if (!$res) return false;
    $row = $res->fetch_row();
    if (!$row) return false;
    // Index 1 should be the table type (BASE TABLE or VIEW)
    return isset($row[1]) && strtoupper((string)$row[1]) === 'BASE TABLE';
}

function view_exists(mysqli $conn, string $view): bool {
    $res = $conn->query("SHOW FULL TABLES LIKE '" . $conn->real_escape_string($view) . "'");
    if (!$res) return false;
    $row = $res->fetch_row();
    if (!$row) return false;
    return isset($row[1]) && strtoupper((string)$row[1]) === 'VIEW';
}

// Gate with optional secret and one-time availability
$adminSecret = (string)env('ADMIN_REG_SECRET', '');

// Determine if an admin already exists via admin_tbl (base table)
$hasAdmin = false;
if ($stmt = $conn->prepare("SELECT 1 FROM admin_tbl LIMIT 1")) {
    $stmt->execute();
    $stmt->store_result();
    $hasAdmin = $stmt->num_rows > 0;
    $stmt->close();
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $providedSecret = sanitize($_POST['secret'] ?? '');
    if ($hasAdmin && ($adminSecret !== '' ? !hash_equals((string)$adminSecret, (string)$providedSecret) : true)) {
        http_response_code(403);
        echo 'Admin already exists. This temporary registrar is disabled.';
        exit;
    }

    if ($adminSecret !== '' && !hash_equals((string)$adminSecret, (string)$providedSecret)) {
        http_response_code(403);
        echo 'Invalid secret.';
        exit;
    }

    $firstName = sanitize($_POST['first_name'] ?? '');
    $lastName  = sanitize($_POST['last_name'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $username  = sanitize($_POST['username'] ?? '');
    $number    = sanitize($_POST['number'] ?? '');
    $gender    = normalize_gender($_POST['gender'] ?? 'Other');
    $password  = (string)($_POST['password'] ?? '');
    $confirm   = (string)($_POST['confirm_password'] ?? '');

    if ($firstName === '' || $lastName === '' || $email === '' || $username === '' || $password === '' || $confirm === '') {
        http_response_code(400);
        echo 'Please fill in all required fields.';
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo 'Invalid email address.';
        exit;
    }
    if ($password !== $confirm) {
        http_response_code(400);
        echo 'Passwords do not match.';
        exit;
    }
    if (strlen($password) < 6) {
        http_response_code(400);
        echo 'Password must be at least 6 characters.';
        exit;
    }

    // Duplicate check across consolidated users (admin_tbl + customer_tbl)
    if ($chk = $conn->prepare(
        "SELECT 1 FROM (\n" .
        "  SELECT admin_email AS email, admin_user AS username, admin_number AS `number` FROM admin_tbl\n" .
        "  UNION ALL\n" .
        "  SELECT customer_email AS email, customer_user AS username, customer_number AS `number` FROM customer_tbl\n" .
        ") u WHERE u.email = ? OR u.username = ? OR u.`number` = ? LIMIT 1"
    )) {
        $chk->bind_param('sss', $email, $username, $number);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            http_response_code(409);
            echo 'An account with the same email, username, or number already exists.';
            exit;
        }
        $chk->close();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $inserted = false;
    $fullName = trim($firstName . ' ' . $lastName);

    // Preferred path: insert into admin_tbl (base table)
    if (table_exists_base($conn, 'admin_tbl')) {
        if ($ins = $conn->prepare("INSERT INTO admin_tbl (admin_fname, admin_lname, admin_gender, admin_email, admin_number, admin_user, admin_pass) VALUES (?,?,?,?,?,?,?)")) {
            $ins->bind_param('sssssss', $firstName, $lastName, $gender, $email, $number, $username, $passwordHash);
            $inserted = $ins->execute();
            $ins->close();
        }
    } else {
        http_response_code(500);
        echo 'No suitable destination table found (admin_tbl).';
        exit;
    }

    if (!$inserted) {
        http_response_code(500);
        echo 'Failed to create admin. Please check server logs.';
        exit;
    }

    // Bootstrap session from admin_tbl for created admin
    if ($sel = $conn->prepare("SELECT admin_id AS id, CONCAT(COALESCE(admin_fname,''),' ',COALESCE(admin_lname,'')) AS fullname, admin_email AS email, admin_user AS username FROM admin_tbl WHERE admin_email = ? OR admin_user = ? ORDER BY admin_id DESC LIMIT 1")) {
        $sel->bind_param('ss', $email, $username);
        $sel->execute();
        $res = $sel->get_result();
        $user = $res ? $res->fetch_assoc() : null;
        $sel->close();
        if ($user) {
            $_SESSION['user'] = [
                'id'       => (int)$user['id'],
                'fullname' => (string)$user['fullname'],
                'email'    => (string)$user['email'],
                'username' => (string)$user['username'],
                'role'     => 'admin',
            ];
        }
    }

    // Redirect to admin dashboard
    header('Location: ../admindash/admindash.php');
    exit;
}

// GET: Render minimal form
$showSecretField = env('ADMIN_REG_SECRET', '') !== '';
$disabledNote = '';
if ($hasAdmin && !$showSecretField) {
    $disabledNote = '<div style="color:#b00020; margin-bottom:12px;">An admin already exists. This page is currently disabled.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temporary Admin Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { background: #fafafa; }
        .card { max-width: 560px; margin: 48px auto; }
    </style>
</head>
<body>
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <strong>Temporary Admin Registration</strong>
        </div>
        <div class="card-body">
            <p class="text-muted small">For temporary use only. Remove this file after creating your admin account.</p>
            <?php echo $disabledNote; ?>
            <form method="post" action="">
                <?php if ($showSecretField) { ?>
                <div class="mb-3">
                    <label class="form-label">Secret Code</label>
                    <input type="password" name="secret" class="form-control" required>
                </div>
                <?php } ?>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control" required <?php echo ($hasAdmin && !$showSecretField) ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control" required <?php echo ($hasAdmin && !$showSecretField) ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required <?php echo ($hasAdmin && !$showSecretField) ? 'disabled' : ''; ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required <?php echo ($hasAdmin && !$showSecretField) ? 'disabled' : ''; ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="number" class="form-control" placeholder="Optional" <?php echo ($hasAdmin && !$showSecretField) ? 'disabled' : ''; ?>>
                </div>
                <div class="mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select" <?php echo ($hasAdmin && !$showSecretField) ? 'disabled' : ''; ?>>
                        <option>Male</option>
                        <option>Female</option>
                        <option selected>Other</option>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required <?php echo ($hasAdmin && !$showSecretField) ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-control" required <?php echo ($hasAdmin && !$showSecretField) ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success" <?php echo ($hasAdmin && !$showSecretField) ? 'disabled' : ''; ?>>Create Admin</button>
                </div>
            </form>
        </div>
        <div class="card-footer text-center small text-muted">
            Path: Login/admin_temp_register.php
        </div>
    </div>
</body>
</html>


