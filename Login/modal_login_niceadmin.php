<?php
// Simple login modal markup used by js/login-modal-fixed.js
?>
<style>
	/* Responsive, accessible modal styles shared by Login & Register */
	.login-modal-overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.45);z-index:1080;transition:opacity .2s ease;padding:16px}
	.login-modal{position:relative;background:#fff;border-radius:16px;padding:24px;max-width:460px;width:100%;box-shadow:0 12px 36px rgba(0,0,0,.22);max-height:calc(100vh - 40px);overflow:auto}
	.login-close{position:absolute;top:10px;right:10px;background:transparent;border:0;font-size:18px;color:#555;cursor:pointer;line-height:1}
	.login-modal-header{margin-bottom:12px}
	.login-modal-header h3{margin:0 0 6px 0;font-size:1.25rem;line-height:1.2}
	.login-modal-header p{margin:0;color:#6b7280;font-size:.9rem}
	.login-modal-body{margin-top:6px}
	.login-form-group{margin-bottom:12px}
	.login-form-control{width:100%;padding:12px 14px;border:1px solid #d1d5db;border-radius:10px;background:#fff;font-size:0.95rem}
	.login-form-control:focus{outline:none;border-color:#7ab4a1;box-shadow:0 0 0 3px rgba(122,180,161,.2)}
	.password-toggle{position:relative}
	.password-toggle .toggle-password{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#6b7280}
	.login-form-check{display:flex;align-items:center;gap:8px;margin:8px 0 12px}
	.login-btn{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:12px 16px;border-radius:10px;border:0;background:#0d6efd;color:#fff;cursor:pointer;font-weight:600}
	.login-btn-primary{background:#7ab4a1}
	.login-btn-secondary{background:#e5e7eb;color:#111827}
	.login-btn:hover{filter:brightness(.98)}
	.login-btn:disabled{opacity:.7;cursor:not-allowed}
	.login-loading,.register-loading,.otp-loading{margin-left:8px}
	.login-modal-footer{margin-top:12px;text-align:center;font-size:.95rem}
	.btn-link{background:none;border:0;color:#0d6efd;cursor:pointer}
	.login-form-radio-group{display:flex;gap:12px;align-items:center;flex-wrap:wrap}

	@media (max-width: 576px){
		.login-modal{border-radius:14px;padding:20px}
		.login-modal-header h3{font-size:1.15rem}
		.login-form-control{padding:11px 13px}
	}
</style>
<div class="login-modal-overlay" id="loginModalOverlay" style="display:none;opacity:0;visibility:hidden;">
	<div class="login-modal">
		<button type="button" class="login-close" aria-label="Close modal" onclick="closeLoginModal()">
			<i class="fa fa-times"></i>
		</button>

		<div class="login-modal-header">
			<h3>Sign In</h3>
			<p class="text-muted mb-0">Welcome back</p>
		</div>

		<div class="login-modal-body">
			<form id="loginForm" onsubmit="handleLogin(event)">
				<div class="login-form-group">
					<label for="loginEmail">Email Address</label>
					<input type="email" id="loginEmail" name="email" class="login-form-control" required autocomplete="email">
				</div>
				<div class="login-form-group">
					<label for="loginPassword">Password</label>
					<div class="password-toggle">
						<input type="password" id="loginPassword" name="password" class="login-form-control" required autocomplete="current-password">
						<span class="toggle-password" onclick="togglePassword()" title="Toggle password visibility">
							<i class="fa fa-eye"></i>
						</span>
					</div>
				</div>
				<div class="login-form-check">
					<input type="checkbox" id="loginRemember" name="remember">
					<label for="loginRemember">Keep me signed in</label>
				</div>
				<button type="submit" class="login-btn login-btn-primary" id="loginSubmitBtn">
					<span class="btn-text">Sign In</span>
					<span class="login-loading" style="display:none;"><i class="fa fa-spinner fa-spin"></i> Signing in...</span>
				</button>
			</form>

			<div class="login-modal-footer">
				<p>Don't have an account? <a href="#" onclick="switchToRegister()">Create one</a></p>
			</div>
		</div>
	</div>
</div>


