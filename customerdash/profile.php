<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';
require_once __DIR__ . '/../properties/activity_log.php';

$userId = (int)($_SESSION['user']['id'] ?? 0);
$msg = '';
// Ensure customer address column exists (safety on first run)
$colCheck = $conn->query("SHOW COLUMNS FROM customer_tbl LIKE 'customer_address'");
if ($colCheck && $colCheck->num_rows === 0) {
    @$conn->query("ALTER TABLE customer_tbl ADD COLUMN customer_address varchar(255) DEFAULT NULL");
}
if ($colCheck) { $colCheck->close(); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// CSRF validation
	$csrf = $_POST['csrf_token'] ?? '';
	if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
		$msg = 'Invalid request. Please refresh and try again.';
	} else {
		$fullname = trim($_POST['fullname'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$number = trim($_POST['number'] ?? '');
		$address = trim($_POST['address'] ?? '');
		$gender = trim($_POST['gender'] ?? '');
		$currentPassword = (string)($_POST['current_password'] ?? '');
		$otp = trim($_POST['otp'] ?? '');
		if ($fullname !== '') {
			// Require current password and OTP to authorize changes
			if ($currentPassword === '') {
				$msg = 'Please enter your current password to save changes.';
			} else if (!($_SESSION['profile_update_otp_verified'] ?? false)) {
				$msg = 'Please verify the OTP code before saving changes.';
			} else {
				$pwdStmt = $conn->prepare("SELECT customer_pass AS password, customer_email AS email FROM customer_tbl WHERE customer_id = ?");
				$pwdStmt->bind_param('i', $userId);
				$pwdStmt->execute();
				$pwdRes = $pwdStmt->get_result();
				$pwdRow = $pwdRes->fetch_assoc();
				$pwdStmt->close();
				if (!$pwdRow || !password_verify($currentPassword, (string)$pwdRow['password'])) {
					$msg = 'Current password is incorrect.';
				} else {
					// Validate OTP inline
					if ($otp === '' || !isset($_SESSION['profile_update_otp'], $_SESSION['profile_update_otp_expires']) || time() > (int)$_SESSION['profile_update_otp_expires'] || !hash_equals((string)$_SESSION['profile_update_otp'], $otp)) {
						$msg = 'Please enter a valid OTP code (or request a new one).';
					} else {
						// Validate email if provided; fallback to existing
						if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
							$email = (string)($pwdRow['email'] ?? $email);
						}
						// Check duplicate email/phone across base tables
						$dup = $conn->prepare("SELECT 1 FROM (\n  SELECT customer_id AS id, customer_email AS email, customer_number AS `number` FROM customer_tbl\n  UNION ALL\n  SELECT admin_id AS id, admin_email AS email, admin_number AS `number` FROM admin_tbl\n) u WHERE (u.email = ? OR u.`number` = ?) AND u.id <> ? LIMIT 1");
						$dup->bind_param('ssi', $email, $number, $userId);
						$dup->execute();
						$dupRes = $dup->get_result();
						if ($dupRes && $dupRes->num_rows > 0) {
							$msg = 'Email or phone is already used by another account.';
							$dup->close();
						} else {
							$dup->close();
			// Split full name for normalized table
			$parts = preg_split('/\s+/', trim($fullname), 2);
			$fname = $parts[0] ?? $fullname;
			$lname = $parts[1] ?? '';
			$stmt = $conn->prepare("UPDATE customer_tbl SET customer_fname=?, customer_lname=?, customer_email=?, customer_number=?, customer_gender=?, customer_address=? WHERE customer_id=?");
			$stmt->bind_param('ssssssi', $fname, $lname, $email, $number, $gender, $address, $userId);
			if ($stmt->execute()) {
				$_SESSION['user']['fullname'] = $fullname;
				$_SESSION['user']['email'] = $email;
				$_SESSION['user']['gender'] = $gender;
				$_SESSION['user']['address'] = $address;
				$msg = 'Profile updated';
				logActivity($conn, $userId, 'customer', 'profile_update', 'Updated account details');
				unset($_SESSION['profile_update_otp'], $_SESSION['profile_update_otp_expires'], $_SESSION['profile_update_otp_verified']);
			}
			$stmt->close();
						}
					}
				}
			}
		}
	}
}

$stmt = $conn->prepare("SELECT CONCAT(COALESCE(customer_fname,''),' ',COALESCE(customer_lname,'')) AS fullname, customer_email AS email, customer_number AS number, customer_address AS address, NULL AS date_added, customer_gender AS gender FROM customer_tbl WHERE customer_id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();
$memberSince = isset($data['date_added']) ? date('M Y', strtotime($data['date_added'])) : '';
$genderLower = strtolower($data['gender'] ?? '');
$avatarSrc = '../pics/profile.png';
if ($genderLower === 'female') { $avatarSrc = '../pics/avatar-female.png'; }
else if ($genderLower === 'male') { $avatarSrc = '../pics/avatar-male.png'; }
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>My Profile</title>
	<link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
	<link href="../template/assets/css/style.css" rel="stylesheet">
	<link href="../css/theme-overrides.css" rel="stylesheet">
</head>
<body>
	<header id="header" class="header fixed-top d-flex align-items-center">
		<div class="d-flex align-items-center justify-content-between">
			<a href="cusdash.php" class="logo d-flex align-items-center"><img src="../pics/logo2.png" alt=""><span class="d-none d-lg-block">Shelton Customer</span></a>
			<i class="bi bi-list toggle-sidebar-btn" title="Toggle sidebar"></i>
		</div>
		<nav class="header-nav ms-auto">
			<ul class="d-flex align-items-center">
				<li class="nav-item pe-3">
					<div class="nav-link nav-profile d-flex align-items-center pe-0">
						<?php 
							$__cusUser = htmlspecialchars($_SESSION['user']['username'] ?? '');
							$__sessGender = strtolower($_SESSION['user']['gender'] ?? '');
							$__avatarSrc = '../pics/profile.png';
							if ($__sessGender === 'female') { $__avatarSrc = '../pics/avatar-female.png'; }
							else if ($__sessGender === 'male') { $__avatarSrc = '../pics/avatar-male.png'; }
						?>
						<img src="<?php echo $__avatarSrc; ?>" alt="Profile" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;" onerror="this.onerror=null;this.src='../template/assets/img/profile-img.jpg';">
						<span class="d-none d-md-block ps-2"><?php echo ($__cusUser !== '' ? $__cusUser : 'Customer'); ?></span>
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
        <a class="nav-link collapsed" href="cusdash.php">
          <i class="bi bi-grid"></i><span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="profile.php">
          <i class="bi bi-person"></i><span>Profile</span>
        </a>
      </li>
    </ul>
  </aside>
	<main id="main" class="main">
		<div class="pagetitle"><h1>My Account</h1></div>
		<section class="section">
			<div class="row g-4">
				<div class="col-lg-4">
					<div class="card">
						<div class="card-body text-center">
							<div class="mb-3"><img src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="Avatar" class="rounded-circle" style="width:96px;height:96px;object-fit:cover" onerror="this.onerror=null;this.src='../template/assets/img/profile-img.jpg';"></div>
							<h5 class="card-title mb-1"><?php echo htmlspecialchars($data['fullname'] ?? ''); ?></h5>
							<div class="text-muted small mb-3"><?php echo htmlspecialchars($data['email'] ?? ''); ?></div>
							<div class="d-flex justify-content-center gap-2 small">
								<span class="badge bg-success"><i class="bi bi-patch-check"></i> Member</span>
								<?php if ($memberSince): ?><span class="badge bg-secondary">Since <?php echo htmlspecialchars($memberSince); ?></span><?php endif; ?>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-8">
					<div class="card mb-4"><div class="card-body">
						<h5 class="card-title">Account Details</h5>
						<?php if ($msg): ?><div class="alert alert-success py-2 mb-2"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
						<form method="post" autocomplete="on">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
							<div class="row g-3">
								<div class="col-md-6"><label class="form-label">Full Name</label><input name="fullname" class="form-control" value="<?php echo htmlspecialchars($data['fullname'] ?? ''); ?>" required></div>
								<div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" required></div>
								<div class="col-md-6"><label class="form-label">Phone</label><input name="number" class="form-control" value="<?php echo htmlspecialchars($data['number'] ?? ''); ?>" placeholder="e.g. +639xxxxxxxxx"></div>
								<div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="<?php echo htmlspecialchars($data['address'] ?? ''); ?>" placeholder="House/Street, City, Province"></div>
								<div class="col-md-6">
									<label class="form-label">Gender</label>
									<select name="gender" class="form-select">
										<option value="" <?php echo (($data['gender'] ?? '')==='')?'selected':''; ?>>Not specified</option>
										<option value="Male" <?php echo (($data['gender'] ?? '')==='Male')?'selected':''; ?>>Male</option>
										<option value="Female" <?php echo (($data['gender'] ?? '')==='Female')?'selected':''; ?>>Female</option>
									</select>
								</div>
								<div class="col-md-6">
									<label class="form-label">OTP Code</label>
									<div class="d-flex flex-wrap align-items-stretch gap-2">
										<input type="text" name="otp" class="form-control" placeholder="6-digit code" maxlength="6" style="max-width:160px;height:38px;">
										<button type="button" id="btnSendEmailOtp" class="btn btn-outline-secondary btn-sm text-nowrap" style="min-width:130px;height:38px;" onclick="sendUpdateOTP('email','btnSendEmailOtp')">Send Email</button>
										<button type="button" id="btnSendPhoneOtp" class="btn btn-outline-secondary btn-sm text-nowrap" style="min-width:130px;height:38px;" onclick="sendUpdateOTP('phone','btnSendPhoneOtp')">Send Phone</button>
									</div>
									<div class="form-text"><span id="otpStatusText">Code expires in 5 minutes. You may resend after 30s.</span></div>
								</div>
								<div class="col-md-6"><label class="form-label">Current Password (required to save)</label><input type="password" name="current_password" class="form-control" required></div>
							</div>
							<div class="mt-3">
								<button class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
								<a href="cusdash.php" class="btn btn-outline-secondary">Back</a>
							</div>
						</form>
					</div></div>
					<div class="card"><div class="card-body">
						<h5 class="card-title">Change Password</h5>
						<form method="post" action="profile_change_password.php" autocomplete="off">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
							<div class="row g-3">
								<div class="col-md-4"><label class="form-label">Current Password</label><input type="password" name="current" class="form-control" required></div>
								<div class="col-md-4"><label class="form-label">New Password</label><input type="password" name="new" class="form-control" minlength="8" required></div>
								<div class="col-md-4"><label class="form-label">Confirm New Password</label><input type="password" name="confirm" class="form-control" minlength="8" required></div>
							</div>
							<div class="mt-3"><button class="btn btn-outline-primary"><i class="bi bi-shield-lock"></i> Update Password</button></div>
						</form>
					</div></div>
				</div>
			</div>
		</section>
	</main>
	<script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script src="../template/assets/js/main.js"></script>
</body>

<script>
async function sendUpdateOTP(channel, btnId){
  try{
    const formData = new FormData();
    formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>');
    formData.append('channel', channel);
    const btn = btnId ? document.getElementById(btnId) : null;
    const original = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.classList.add('disabled'); btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Sending...'; }
    const res = await fetch('send_update_otp.php', { method:'POST', body: formData });
    const data = await res.json();
    if (data.success){
      if (window.Swal) Swal.fire({icon:'success', title:data.message, timer:2000, showConfirmButton:false, toast:true, position:'top-end'});
      else alert(data.message);
      // simple 30s cooldown
      let remaining = 30;
      const status = document.getElementById('otpStatusText');
      const timer = setInterval(() => {
        remaining--;
        if (status) status.textContent = `Code expires in 5 minutes. You may resend after ${remaining}s.`;
        if (remaining <= 0) {
          clearInterval(timer);
          if (status) status.textContent = 'Code expires in 5 minutes. You may resend now.';
          if (btn) { btn.disabled = false; btn.classList.remove('disabled'); btn.innerHTML = (channel==='email'?'Send Email':'Send Phone'); }
        }
      }, 1000);
    } else {
      if (window.Swal) Swal.fire({icon:'error', title:data.message, timer:2200, showConfirmButton:false, toast:true, position:'top-end'});
      else alert(data.message);
      if (btn) { btn.disabled = false; btn.classList.remove('disabled'); btn.innerHTML = (channel==='email'?'Send Email':'Send Phone'); }
    }
  }catch(err){
    if (window.Swal) Swal.fire({icon:'error', title:'Failed to send code', timer:2200, showConfirmButton:false, toast:true, position:'top-end'});
    else alert('Failed to send code');
    const btn = btnId ? document.getElementById(btnId) : null;
    if (btn) { btn.disabled = false; btn.classList.remove('disabled'); btn.innerHTML = (channel==='email'?'Send Email':'Send Phone'); }
  }
}
</script>
</html>


