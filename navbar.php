<?php
session_start();
// Check if user is logged in (support both session formats for backward compatibility)
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['user']['id']);
$userName = '';
$userEmail = '';
$userRole = 'customer';

if ($isLoggedIn) {
	// Support both session formats for backward compatibility
	if (isset($_SESSION['user']['fullname'])) {
		$userName = htmlspecialchars($_SESSION['user']['fullname']);
		$userEmail = htmlspecialchars($_SESSION['user']['email']);
		$userRole = htmlspecialchars($_SESSION['user']['role']);
	} elseif (isset($_SESSION['user_name'])) {
		$userName = htmlspecialchars($_SESSION['user_name']);
		$userEmail = htmlspecialchars($_SESSION['user_email']);
		$userRole = htmlspecialchars($_SESSION['user_role']);
	}
}
?>
    <nav class="navbar navbar-expand-lg navbar-enhanced<?php echo $isLoggedIn ? ' logged-in' : ''; ?>" role="navigation" aria-label="Main navigation">
        <div class="container">
            <!-- Brand - Always visible -->
            <a class="navbar-brand-enhanced" href="index.php" aria-label="Shelton Beach Haven - Home">
                <img src="pics/logo2.png" alt="Shelton Beach Haven Logo" width="50" height="50">
                <span class="brand-mobile">SBR</span>
                <span class="brand-desktop">Shelton Beach Resort</span>
            </a>
            
            <!-- Mobile menu button -->
            <button class="navbar-toggler-enhanced" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon-enhanced"></span>
            </button>
            
            <!-- Navigation content -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Main navigation - Center aligned -->
                <ul class="navbar-nav mx-auto navbar-nav-enhanced main-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="#top" aria-label="Home" onclick="closeMobileNavbar()">
                            <i class="fa fa-home" aria-hidden="true"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about-section" aria-label="About Us" onclick="closeMobileNavbar()">
                            <i class="fa fa-info-circle" aria-hidden="true"></i>
                            <span>About</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#offer" aria-label="Our Services" onclick="closeMobileNavbar()">
                            <i class="fa fa-star" aria-hidden="true"></i>
                            <span>Services</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#gall" aria-label="Gallery" onclick="closeMobileNavbar()">
                            <i class="fa fa-picture-o" aria-hidden="true"></i>
                            <span>Gallery</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#feed" aria-label="Feedback" onclick="closeMobileNavbar()">
                            <i class="fa fa-comments" aria-hidden="true"></i>
                            <span>Reviews</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reservation-section" aria-label="Make Reservation" onclick="closeMobileNavbar()" 
                           title="<?php echo $isLoggedIn ? 'Make your reservation' : 'Login required for reservations'; ?>">
                            <i class="fa fa-calendar-check-o" aria-hidden="true"></i>
                            <span>Reserve</span>
                            <?php if (!$isLoggedIn): ?>
                                <span class="login-required-badge" title="Login Required">
                                    <i class="fa fa-lock" style="font-size: 0.7rem; color: var(--orange-bright);"></i>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#us" aria-label="Contact Us" onclick="closeMobileNavbar()">
                            <i class="fa fa-phone" aria-hidden="true"></i>
                            <span>Contact</span>
                        </a>
                    </li>
                </ul>
                
                <!-- User actions - Right aligned -->
                <ul class="navbar-nav navbar-nav-enhanced user-actions">
                    <?php if ($isLoggedIn): ?>
                        <!-- Direct dashboard link (no dropdown) -->
                        <li class="nav-item">
                            <a class="nav-link user-profile-link" href="<?php echo ($userRole === 'admin') ? 'admindash/admindash.php' : 'customerdash/cusdash.php'; ?>" onclick="closeMobileNavbar()" aria-label="Open Dashboard">
                                <i class="fa fa-tachometer" aria-hidden="true"></i>
                                <span class="d-none d-lg-inline ms-1">Dashboard</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <!-- Guest user actions -->
                        <li class="nav-item">
                            <button class="btn btn-auth-combined" aria-label="Sign In or Create Account" id="authButton" type="button" onclick="openSignInModal()">
                                <i class="fa fa-user-circle" aria-hidden="true"></i>
                                <span>Sign In</span>
                            </button>
                        </li>

                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    

    <script>
		// Function to close mobile navbar
		function closeMobileNavbar() {
			const navbarCollapse = document.getElementById('navbarNav');
			if (!navbarCollapse) return;
			try {
				const bsCollapse = new bootstrap.Collapse(navbarCollapse, { toggle: false });
				bsCollapse.hide();
			} catch (e) {
				// fallback: remove 'show' class
				navbarCollapse.classList.remove('show');
			}
		}

		// Function to open the sign in modal
		function openSignInModal() {
			if (typeof openLoginModal === 'function') {
				openLoginModal();
			}
		}

		// Function to switch from login to register modal
		function switchToRegister() {
			if (typeof closeLoginModal === 'function') closeLoginModal();
			setTimeout(() => {
				if (typeof openRegisterModal === 'function') {
					openRegisterModal();
				}
			}, 300);
		}

		// Function to clear remember me tokens on logout
		function clearRememberMeTokens() {
			localStorage.removeItem('remember_token');
			localStorage.removeItem('remember_email');
			document.cookie = 'remember_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
		}

		document.addEventListener('DOMContentLoaded', function() {
			const authButton = document.getElementById('authButton');
			if (authButton) {
				authButton.addEventListener('click', function() {
					openSignInModal();
				});
			}

			// Auto-open auth modal based on URL params
			try {
				const params = new URLSearchParams(window.location.search);
				const auth = params.get('auth');
				if (auth === 'login') {
					if (typeof openLoginModal === 'function') openLoginModal();
				} else if (auth === 'register') {
					if (typeof openRegisterModal === 'function') openRegisterModal();
				}
			} catch(e) {}
		});
    </script>
