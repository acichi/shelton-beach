/**
 * Fixed Login Modal - Shelton Beach Haven
 * Simple, working modal functionality
 */

// Global variables
let loginModal = null;
// Resolve app base prefix (e.g., /sbh when app is under http://host/sbh)
function getAppBase(){
	try{
		const parts = window.location.pathname.split('/').filter(Boolean);
		return parts.length ? '/' + parts[0] : '';
	}catch(e){ return ''; }
}
const APP_BASE = getAppBase();

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeLoginModal();
    checkRememberMe(); // Check for existing remember me tokens
});

function initializeLoginModal() {
    // Get modal elements
    loginModal = document.getElementById('loginModalOverlay');
    const loginButton = document.getElementById('loginButton');
    
    if (loginButton) {
        loginButton.addEventListener('click', openLoginModal);
    }
    
    // Close modal when clicking outside
    if (loginModal) {
        loginModal.addEventListener('click', function(e) {
            if (e.target === loginModal) {
                closeLoginModal();
            }
        });
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && loginModal && loginModal.style.display === 'flex') {
            closeLoginModal();
        }
    });
}

// Function to check remember me and redirect immediately if logged in
async function checkRememberMe() {
    try {
        const token = localStorage.getItem('remember_token');
        const email = localStorage.getItem('remember_email');
        if (!token || !email) {
            // Nothing to auto-login with
            return;
        }
        const response = await fetch(APP_BASE + '/Login/check_remember_me.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ token, email })
        });
        const data = await response.json();
        if (data && data.success && data.redirect) {
            window.location.href = data.redirect; // Logged in: go straight to dashboard
            return;
        }
        // If server indicates banned, show a clear toast and clear remember token
        if (data && data.message && /banned/i.test(String(data.message))) {
            try { localStorage.removeItem('remember_token'); localStorage.removeItem('remember_email'); } catch(e){}
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Account banned',
                    text: 'Your account has been banned. Please contact support.',
                    showConfirmButton: true
                });
            }
        }
        // Not logged in; ensure any login/register triggers are visible
        try {
            document.querySelectorAll('[data-hide-when-logged-in]')
              .forEach(el => el.style.removeProperty('display'));
        } catch(e) {}
    } catch (error) {
        console.error('Remember me check error:', error);
    }
}

// Remove auto-login notification UI (not needed with immediate redirect)

function openLoginModal() {
    if (!loginModal) {
        loginModal = document.getElementById('loginModalOverlay');
    }
    
    loginModal.style.display = 'flex';
    loginModal.style.opacity = '1';
    loginModal.style.visibility = 'visible';
    
    // Focus on email field
    setTimeout(() => {
        const emailField = document.getElementById('loginEmail');
        if (emailField) emailField.focus();
    }, 100);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function closeLoginModal() {
    if (!loginModal) return;
    
    loginModal.style.display = 'none';
    loginModal.style.opacity = '0';
    loginModal.style.visibility = 'hidden';
    
    // Reset form
    const form = document.getElementById('loginForm');
    if (form) form.reset();
    
    // Re-enable body scroll
    document.body.style.overflow = '';
    
    // Hide loading state
    hideLoginLoading();
}

function togglePassword() {
    const passwordInput = document.getElementById('loginPassword');
    const toggleIcon = document.querySelector('.toggle-password i');
    
    if (!passwordInput || !toggleIcon) return;
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'fa fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'fa fa-eye';
    }
}

function showLoginLoading() {
    const submitBtn = document.getElementById('loginSubmitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const loadingText = submitBtn.querySelector('.login-loading');
    
    if (submitBtn) submitBtn.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (loadingText) loadingText.style.display = 'inline';
}

function hideLoginLoading() {
    const submitBtn = document.getElementById('loginSubmitBtn');
    const btnText = submitBtn.querySelector('.btn-text');
    const loadingText = submitBtn.querySelector('.login-loading');
    
    if (submitBtn) submitBtn.disabled = false;
    if (btnText) btnText.style.display = 'inline';
    if (loadingText) loadingText.style.display = 'none';
}

async function handleLogin(event) {
    event.preventDefault();
    
    const form = document.getElementById('loginForm');
    const formData = new FormData(form);
    
    // Basic validation
    const email = formData.get('email'); // this may contain username or email
    const password = formData.get('password');
    const rememberMe = formData.get('remember') === 'on'; // Get remember me value
    
    if (!email || !password) {
        showLoginAlert('Please fill in all fields', 'warning');
        return;
    }
    
    // Allow either username or email, so skip strict email format validation
    
    showLoginLoading();
    
    try {
        const response = await fetch(APP_BASE + '/Login/modal_login.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showLoginAlert(data.message, 'success');
            
            // Handle remember me functionality
            if (rememberMe && data.remember_token) {
                // Store remember me token in localStorage for client-side reference
                localStorage.setItem('remember_token', data.remember_token);
                // Always store the canonical email returned by server
                if (data.user_email) {
                    localStorage.setItem('remember_email', data.user_email);
                } else {
                    localStorage.setItem('remember_email', email);
                }
            } else {
                // Clear any existing remember me data
                localStorage.removeItem('remember_token');
                localStorage.removeItem('remember_email');
            }
            
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1500);
        } else {
            showLoginAlert(data.message, 'error');
            hideLoginLoading();
        }
    } catch (error) {
        console.error('Login error:', error);
        showLoginAlert('An error occurred. Please try again.', 'error');
        hideLoginLoading();
    }
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showLoginAlert(message, type) {
    // Always use SweetAlert2 for consistent, beautiful notifications
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: type,
            title: message,
            timer: type === 'success' ? 2000 : 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            background: '#fff',
            color: '#333',
            timerProgressBar: true
        });
    } else {
        // Fallback to console log instead of alert to avoid localhost popup
        console.log(`${type.toUpperCase()}: ${message}`);
        
        // Create a simple custom notification if SweetAlert2 fails
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#ffc107'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 9999;
            font-family: Arial, sans-serif;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
        `;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.remove();
            }
        }, 3000);
    }
}

// Function to clear remember me tokens
function clearRememberMeTokens() {
    localStorage.removeItem('remember_token');
    localStorage.removeItem('remember_email');
}

// Make function globally available
window.clearRememberMeTokens = clearRememberMeTokens;

// Export functions for global access
window.openLoginModal = openLoginModal;
window.closeLoginModal = closeLoginModal;
window.togglePassword = togglePassword;
window.handleLogin = handleLogin;
