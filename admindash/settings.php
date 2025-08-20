<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
require_once __DIR__ . '/../properties/activity_log.php';

$userId = isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : 0;
if ($userId <= 0) { header('Location: ../login.php'); exit; }

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$message = '';
$messageType = 'success';

// Ensure admin address column exists (parity with customer address)
$__col = $conn->query("SHOW COLUMNS FROM admin_tbl LIKE 'admin_address'");
if ($__col && $__col->num_rows === 0) {
  @$conn->query("ALTER TABLE admin_tbl ADD COLUMN admin_address varchar(255) DEFAULT NULL");
}
if ($__col) { $__col->close(); }

// Try to ensure the `users` view exposes address for both customers and admins
@($conn->query(
  "CREATE OR REPLACE VIEW `users` AS\n" .
  "  SELECT\n" .
  "    c.`customer_id` AS `id`,\n" .
  "    CONCAT(COALESCE(c.`customer_fname`, ''), ' ', COALESCE(c.`customer_lname`, '')) AS `fullname`,\n" .
  "    c.`customer_email` AS `email`,\n" .
  "    c.`customer_number` AS `number`,\n" .
  "    c.`customer_user` AS `username`,\n" .
  "    c.`customer_pass` AS `password`,\n" .
  "    c.`customer_gender` AS `gender`,\n" .
  "    'customer' AS `role`,\n" .
  "    c.`customer_address` AS `address`,\n" .
  "    NULL AS `date_added`,\n" .
  "    NULL AS `date_updated`\n" .
  "  FROM `customer_tbl` c\n" .
  "  UNION ALL\n" .
  "  SELECT\n" .
  "    a.`admin_id` AS `id`,\n" .
  "    CONCAT(COALESCE(a.`admin_fname`, ''), ' ', COALESCE(a.`admin_lname`, '')) AS `fullname`,\n" .
  "    a.`admin_email` AS `email`,\n" .
  "    a.`admin_number` AS `number`,\n" .
  "    a.`admin_user` AS `username`,\n" .
  "    a.`admin_pass` AS `password`,\n" .
  "    a.`admin_gender` AS `gender`,\n" .
  "    'admin' AS `role`,\n" .
  "    a.`admin_address` AS `address`,\n" .
  "    NULL AS `date_added`,\n" .
  "    NULL AS `date_updated`\n" .
  "  FROM `admin_tbl` a"
));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = isset($_POST['action']) ? (string)$_POST['action'] : '';

  if ($action === 'update_profile') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $number   = trim($_POST['number'] ?? '');
    $gender   = trim($_POST['gender'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $otpCode  = trim($_POST['otp'] ?? '');
    $current  = (string)($_POST['current_password'] ?? '');

    if ($fullname === '' || $email === '' || $username === '') {
      $message = 'Full name, email, and username are required.';
      $messageType = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $message = 'Please enter a valid email address.';
      $messageType = 'danger';
    } else {
      if ($current === '') {
        $message = 'Please enter your current password to save changes.';
        $messageType = 'danger';
      } elseif (!($_SESSION['admin_update_otp'] ?? false) || !hash_equals((string)($_SESSION['admin_update_otp'] ?? ''), $otpCode) || time() > (int)($_SESSION['admin_update_otp_expires'] ?? 0)) {
        $message = 'Please enter a valid OTP code (or request a new one).';
        $messageType = 'danger';
      } else {
        $verify = $conn->prepare('SELECT admin_pass AS password FROM admin_tbl WHERE admin_id = ? LIMIT 1');
        $verify->bind_param('i', $userId);
        if ($verify->execute() && ($vr = $verify->get_result()) && ($vw = $vr->fetch_assoc())) {
          if (!password_verify($current, (string)$vw['password'])) {
            $message = 'Current password is incorrect.';
            $messageType = 'danger';
          } else {
            $dupStmt = $conn->prepare('SELECT admin_id AS id FROM admin_tbl WHERE (admin_email = ? OR admin_user = ?) AND admin_id <> ? LIMIT 1');
            $dupStmt->bind_param('ssi', $email, $username, $userId);
            if ($dupStmt->execute() && ($dupRes = $dupStmt->get_result()) && $dupRes->num_rows > 0) {
              $message = 'Email or username is already taken by another account.';
              $messageType = 'danger';
            } else {
              // Split fullname
              $parts = preg_split('/\s+/', trim($fullname), 2);
              $fname = $parts[0] ?? $fullname;
              $lname = $parts[1] ?? '';
              $upd = $conn->prepare('UPDATE admin_tbl SET admin_fname = ?, admin_lname = ?, admin_email = ?, admin_number = ?, admin_user = ?, admin_gender = ?, admin_address = ? WHERE admin_id = ?');
              $upd->bind_param('sssssssi', $fname, $lname, $email, $number, $username, $gender, $address, $userId);
              if ($upd->execute()) {
                $_SESSION['user']['fullname'] = $fullname;
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['username'] = $username;
                $_SESSION['user']['address'] = $address;
                $message = 'Profile updated successfully.';
                $messageType = 'success';
                logActivity($conn, $userId, 'admin', 'profile_update', 'Updated admin account details');
                unset($_SESSION['admin_update_otp'], $_SESSION['admin_update_otp_expires']);
              } else {
                $message = 'Failed to update profile.';
                $messageType = 'danger';
              }
              $upd->close();
            }
            $dupStmt->close();
          }
        } else {
          $message = 'Unable to verify current password.';
          $messageType = 'danger';
        }
        $verify->close();
      }
    }
  }
  // Removed send_test_email action

  if ($action === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new === '' || $confirm === '' || $current === '') {
      $message = 'Please fill in all password fields.';
      $messageType = 'danger';
    } elseif ($new !== $confirm) {
      $message = 'New password and confirmation do not match.';
      $messageType = 'danger';
    } elseif (strlen($new) < 8) {
      $message = 'New password must be at least 8 characters.';
      $messageType = 'danger';
    } else {
      $stmt = $conn->prepare('SELECT admin_pass AS password FROM admin_tbl WHERE admin_id = ? LIMIT 1');
      $stmt->bind_param('i', $userId);
      if ($stmt->execute() && ($res = $stmt->get_result()) && ($row = $res->fetch_assoc())) {
        if (!password_verify($current, $row['password'])) {
          $message = 'Current password is incorrect.';
          $messageType = 'danger';
        } else {
          $hash = password_hash($new, PASSWORD_BCRYPT);
          $upd = $conn->prepare('UPDATE admin_tbl SET admin_pass = ? WHERE admin_id = ?');
          $upd->bind_param('si', $hash, $userId);
          if ($upd->execute()) {
            $message = 'Password changed successfully.';
            $messageType = 'success';
            logActivity($conn, $userId, 'admin', 'password_change', 'Changed admin password');
          } else {
            $message = 'Failed to change password.';
            $messageType = 'danger';
          }
          $upd->close();
        }
      } else {
        $message = 'Unable to verify current password.';
        $messageType = 'danger';
      }
      $stmt->close();
    }
  }
}

$info = [
  'fullname' => '', 'email' => '', 'username' => '', 'number' => '', 'gender' => '', 'address' => ''
];
// Read directly from admin_tbl to avoid collisions with customer IDs in the `users` view
$q = $conn->prepare('SELECT CONCAT(COALESCE(admin_fname, ""), " ", COALESCE(admin_lname, "")) AS fullname, admin_email AS email, admin_user AS username, admin_number AS number, admin_gender AS gender, admin_address AS address FROM admin_tbl WHERE admin_id = ? LIMIT 1');
$q->bind_param('i', $userId);
if ($q->execute() && ($r = $q->get_result()) && ($u = $r->fetch_assoc())) {
  $info = $u;
}
$q->close();
// Avatar selection similar to customer profile
$memberSince = '';
$genderLower = strtolower($info['gender'] ?? '');
$avatarSrc = '../pics/profile.png';
if ($genderLower === 'female') { $avatarSrc = '../pics/avatar-female.png'; }
else if ($genderLower === 'male') { $avatarSrc = '../pics/avatar-male.png'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Settings - Shelton Admin</title>
  <link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../template/assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="../template/assets/css/style.css" rel="stylesheet">
  <style>
    /* Compact OTP row controls */
    .otp-controls{display:flex;flex-wrap:wrap;align-items:stretch;gap:8px}
    .otp-controls input[type="text"]{max-width:160px;height:38px}
    .otp-controls .btn{min-width:130px;height:38px;white-space:nowrap}
  </style>
</head>
<body>
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="admindash.php" class="logo d-flex align-items-center">
        <img src="../pics/logo2.png" alt="">
        <span class="d-none d-lg-block">Shelton Admin</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>
    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
        <li class="nav-item pe-3">
          <div class="nav-link nav-profile d-flex align-items-center pe-0">
            <img src="../pics/profile.png" alt="Profile" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" onerror="this.onerror=null;this.src='../template/assets/img/profile-img.jpg';">
            <?php 
              $__admFull = h($_SESSION['user']['fullname'] ?? 'Admin');
              $__admUser = h($_SESSION['user']['username'] ?? '');
            ?>
            <span class="d-none d-md-block ps-2"><?php echo $__admFull . ($__admUser !== '' ? " (@$__admUser)" : ''); ?></span>
          </div>
        </li>
      </ul>
    </nav>
  </header>

  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item">
        <a class="nav-link collapsed" href="../index.php">
          <i class="bi bi-house"></i><span>Home</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" href="admindash.php">
          <i class="bi bi-grid"></i><span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="settings.php">
          <i class="bi bi-geo-alt"></i><span>Settings</span>
        </a>
      </li>
    </ul>
  </aside>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Settings</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="admindash.php">Home</a></li>
          <li class="breadcrumb-item active">Settings</li>
        </ol>
      </nav>
    </div>

    <section class="section">
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="card">
            <div class="card-body text-center">
              <div class="mb-3"><img src="<?php echo h($avatarSrc); ?>" alt="Avatar" class="rounded-circle" style="width:96px;height:96px;object-fit:cover" onerror="this.onerror=null;this.src='../template/assets/img/profile-img.jpg';"></div>
              <h5 class="card-title mb-1"><?php echo h($info['fullname'] ?? ''); ?></h5>
              <div class="text-muted small mb-3"><?php echo h($info['email'] ?? ''); ?></div>
              <div class="d-flex justify-content-center gap-2 small">
                <span class="badge bg-success"><i class="bi bi-patch-check"></i> Admin</span>
                <?php if ($memberSince): ?><span class="badge bg-secondary">Since <?php echo h($memberSince); ?></span><?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card mb-4"><div class="card-body">
            <h5 class="card-title">Account Details</h5>
            <form method="post" class="row g-3" autocomplete="on">
              <input type="hidden" name="action" value="update_profile">
              <!-- Keep username hidden to satisfy backend validation -->
              <input type="hidden" name="username" value="<?php echo h($info['username']); ?>">
              <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="fullname" class="form-control" value="<?php echo h($info['fullname']); ?>" required></div>
              <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo h($info['email']); ?>" required></div>
              <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="number" class="form-control" value="<?php echo h($info['number']); ?>" placeholder="e.g. +639xxxxxxxxx"></div>
              <div class="col-md-6"><label class="form-label">Address</label><input type="text" name="address" class="form-control" value="<?php echo h($info['address']); ?>" placeholder="House/Street, City, Province"></div>
              <div class="col-md-6">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                  <option value="" <?php echo $info['gender']===''?'selected':''; ?>>Not specified</option>
                  <option value="Male" <?php echo $info['gender']==='Male'?'selected':''; ?>>Male</option>
                  <option value="Female" <?php echo $info['gender']==='Female'?'selected':''; ?>>Female</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">OTP Code</label>
                <div class="otp-controls">
                  <input type="text" name="otp" class="form-control" placeholder="6-digit code" maxlength="6">
                  <button type="button" id="admBtnSendEmailOtp" class="btn btn-outline-secondary btn-sm text-nowrap" onclick="sendAdminUpdateOTP('email','admBtnSendEmailOtp')">Send Email</button>
                  <button type="button" id="admBtnSendPhoneOtp" class="btn btn-outline-secondary btn-sm text-nowrap" onclick="sendAdminUpdateOTP('phone','admBtnSendPhoneOtp')">Send Phone</button>
                </div>
                <div class="form-text"><span id="admOtpStatusText">Code expires in 5 minutes. You may resend after 30s.</span></div>
              </div>
              <div class="col-md-6"><label class="form-label">Current Password (required to save)</label><input type="password" name="current_password" class="form-control" required></div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                <a href="admindash.php" class="btn btn-outline-secondary">Back</a>
              </div>
            </form>
          </div></div>
          <div class="card"><div class="card-body">
            <h5 class="card-title">Change Password</h5>
            <form method="post" class="row g-3" autocomplete="off">
              <input type="hidden" name="action" value="change_password">
              <div class="col-md-4"><label class="form-label">Current Password</label><input type="password" name="current_password" class="form-control" required></div>
              <div class="col-md-4"><label class="form-label">New Password</label><input type="password" name="new_password" class="form-control" minlength="8" required></div>
              <div class="col-md-4"><label class="form-label">Confirm New Password</label><input type="password" name="confirm_password" class="form-control" minlength="8" required></div>
              <div class="col-12"><button type="submit" class="btn btn-warning"><i class="bi bi-key"></i> Update Password</button></div>
            </form>
          </div></div>
        </div>
      </div>

      <!-- Removed Send Test Email section per request -->
    </section>
  </main>

  <footer id="footer" class="footer">
    <div class="copyright">&copy; <strong><span>Shelton Beach Resort</span></strong> All Rights Reserved</div>
  </footer>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="../template/assets/js/main.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      // Sidebar toggle fallback if template main.js is unavailable
      const toggle = document.querySelector('.toggle-sidebar-btn');
      if (toggle) {
        toggle.addEventListener('click', function(e){
          e.preventDefault();
          document.body.classList.toggle('toggle-sidebar');
        });
      }
      <?php if ($message !== ''): ?>
      Swal.fire({ icon: '<?php echo $messageType === 'success' ? 'success' : 'error'; ?>', title: '<?php echo h($message); ?>', timer: 2200, showConfirmButton: false, toast: true, position: 'top-end' });
      <?php endif; ?>
      window.sendAdminUpdateOTP = async function(channel, btnId){
        try{
          const fd = new FormData();
          fd.append('csrf_token', '<?php echo h($_SESSION['csrf_token'] ?? ''); ?>');
          fd.append('channel', channel);
          const btn = btnId ? document.getElementById(btnId) : null;
          if (btn) { btn.disabled = true; btn.classList.add('disabled'); btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sending...'; }
          const res = await fetch('send_update_otp.php', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.success) {
            Swal.fire({icon:'success', title:data.message, timer:2000, showConfirmButton:false, toast:true, position:'top-end'});
            let remaining = 30;
            const status = document.getElementById('admOtpStatusText');
            const timer = setInterval(()=>{
              remaining--; if (status) status.textContent = `Code expires in 5 minutes. You may resend after ${remaining}s.`;
              if (remaining <= 0) { clearInterval(timer); if (status) status.textContent = 'Code expires in 5 minutes. You may resend now.'; if (btn) { btn.disabled = false; btn.classList.remove('disabled'); btn.innerHTML = (channel==='email'?'Send Email':'Send Phone'); } }
            },1000);
          } else {
            Swal.fire({icon:'error', title:data.message, timer:2200, showConfirmButton:false, toast:true, position:'top-end'});
            if (btn) { btn.disabled = false; btn.classList.remove('disabled'); btn.innerHTML = (channel==='email'?'Send Email':'Send Phone'); }
          }
        }catch(e){
          Swal.fire({icon:'error', title:'Failed to send code', timer:2200, showConfirmButton:false, toast:true, position:'top-end'});
          const btn = btnId ? document.getElementById(btnId) : null;
          if (btn) { btn.disabled = false; btn.classList.remove('disabled'); btn.innerHTML = (channel==='email'?'Send Email':'Send Phone'); }
        }
      }
    });
  </script>
</body>
</html>


