<?php
session_start();
require '../properties/connection.php';

$message = '';
$messageType = '';
$validToken = false;
$token = '';
$userId = '';

// Check if token is provided
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    
    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_reset_tokens WHERE token = ? AND expires_at > NOW() AND used = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $tokenData = $result->fetch_assoc();
        $userId = $tokenData['user_id'];
        $validToken = true;
    } else {
        $message = 'This password reset link is invalid or has expired. Please request a new one.';
        $messageType = 'error';
    }
    $stmt->close();
} else {
    $message = 'No reset token provided.';
    $messageType = 'error';
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirmPassword)) {
        $message = 'Please fill in all fields.';
        $messageType = 'warning';
    } elseif ($password !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'warning';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'warning';
    } else {
        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update user's password in base tables (try customer_tbl first, then admin_tbl)
        // Note: both tables store hashed passwords in their respective _pass columns
        $updateStmt = $conn->prepare("UPDATE customer_tbl SET customer_pass = ? WHERE customer_id = ?");
        $updateStmt->bind_param("si", $hashedPassword, $userId);
        $ok = $updateStmt->execute();
        $updateStmt->close();
        if (!$ok) {
            $updateStmt = $conn->prepare("UPDATE admin_tbl SET admin_pass = ? WHERE admin_id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $userId);
            $ok = $updateStmt->execute();
            $updateStmt->close();
        }
        if ($ok) {
            // Mark token as used
            $markUsedStmt = $conn->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
            $markUsedStmt->bind_param("s", $token);
            $markUsedStmt->execute();
            $markUsedStmt->close();
            
            $message = 'Your password has been successfully reset! You can now sign in with your new password.';
            $messageType = 'success';
            $validToken = false; // Hide the form after successful reset
        } else {
            $message = 'An error occurred while resetting your password. Please try again.';
            $messageType = 'error';
        }
        $updateStmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Shelton Beach Haven</title>
    
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

        .password-toggle {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
        }

        .toggle-password:hover {
            color: #7ab4a1;
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.875rem;
        }

        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }

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
        <h2><i class="fas fa-lock me-2"></i>Reset Password</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'times-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <p class="subtitle">Enter your new password below.</p>
            
            <form method="POST" action="" id="resetForm">
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div class="password-toggle">
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter new password" required minlength="6">
                        <span class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div class="password-toggle">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm new password" required minlength="6">
                        <span class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mb-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Reset Password
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($messageType === 'success'): ?>
            <div class="d-grid gap-2 mb-3">
                <a href="../index.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Go to Sign In
                </a>
            </div>
        <?php endif; ?>
        
        <div class="text-center">
            <a href="forgot_password.php" class="back-link">
                <i class="fas fa-arrow-left me-1"></i>Back to Forgot Password
            </a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }
            
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            if (strength <= 2) {
                feedback = 'Weak password';
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength <= 4) {
                feedback = 'Medium strength password';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                feedback = 'Strong password';
                strengthDiv.className = 'password-strength strength-strong';
            }
            
            strengthDiv.textContent = feedback;
        });

        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
