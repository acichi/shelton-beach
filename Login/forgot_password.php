<?php
session_start();
require '../properties/connection.php';

// Check if form was submitted
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageType = 'warning';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'warning';
    } else {
        // Check if email exists in base tables (customer_tbl or admin_tbl)
        $sql = "SELECT * FROM (
            SELECT c.customer_id AS id,
                   CONCAT(COALESCE(c.customer_fname,''),' ',COALESCE(c.customer_lname,'')) AS fullname,
                   c.customer_email AS email
            FROM customer_tbl c
            UNION ALL
            SELECT a.admin_id AS id,
                   CONCAT(COALESCE(a.admin_fname,''),' ',COALESCE(a.admin_lname,'')) AS fullname,
                   a.admin_email AS email
            FROM admin_tbl a
        ) u WHERE u.email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token in database
            $tokenStmt = $conn->prepare("INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $tokenStmt->bind_param("iss", $user['id'], $token, $expires);
            
            if ($tokenStmt->execute()) {
                // Send reset email (you can customize this)
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=" . $token;
                
                // For now, just show success message with the link
                // In production, you'd send this via email
                $message = 'Password reset instructions have been sent to your email address.';
                $messageType = 'success';
                
                // Store in session for demo purposes (remove in production)
                $_SESSION['demo_reset_link'] = $resetLink;
                $_SESSION['demo_reset_email'] = $email;
            } else {
                $message = 'An error occurred. Please try again.';
                $messageType = 'error';
            }
            $tokenStmt->close();
        } else {
            // Don't reveal if email exists or not for security
            $message = 'If an account with that email exists, password reset instructions have been sent.';
            $messageType = 'info';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Shelton Beach Haven</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('../pics/bg.png') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-wrapper {
            background-color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-wrapper h2 {
            color: #7ab4a1;
            font-weight: 700;
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .form-wrapper .subtitle {
            color: #6c757d;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #7ab4a1;
            box-shadow: 0 0 0 0.2rem rgba(122, 180, 161, 0.25);
        }

        .btn-primary {
            background-color: #7ab4a1;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #65a291;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        .back-link {
            color: #7ab4a1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #65a291;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .demo-info {
            background-color: #e2f3f5;
            border: 1px solid #7ab4a1;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .demo-info h6 {
            color: #7ab4a1;
            margin-bottom: 0.5rem;
        }

        .demo-link {
            word-break: break-all;
            color: #7ab4a1;
            text-decoration: none;
        }

        .demo-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .form-wrapper {
                margin: 1rem;
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="form-wrapper">
        <h2><i class="fas fa-key me-2"></i>Forgot Password</h2>
        <p class="subtitle">Enter your email address and we'll send you instructions to reset your password.</p>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : ($messageType === 'error' ? 'times-circle' : 'info-circle')); ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['demo_reset_link'])): ?>
            <div class="demo-info">
                <h6><i class="fas fa-info-circle me-2"></i>Demo Information</h6>
                <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['demo_reset_email']); ?></p>
                <p class="mb-2"><strong>Reset Link:</strong></p>
                <a href="<?php echo htmlspecialchars($_SESSION['demo_reset_link']); ?>" class="demo-link" target="_blank">
                    <?php echo htmlspecialchars($_SESSION['demo_reset_link']); ?>
                </a>
                <p class="mt-2 mb-0"><small class="text-muted">This is a demo. In production, this link would be sent via email.</small></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Enter your email address" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
            </div>
            
            <div class="d-grid gap-2 mb-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Instructions
                </button>
            </div>
        </form>
        
        <div class="text-center">
            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left me-1"></i>Back to Home
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
