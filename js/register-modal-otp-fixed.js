// Enhanced Register Modal with OTP functionality - FIXED VERSION (idempotent)
// Avoid duplicate global declarations across multiple loads
(function() {
    function getAppBase(){
        try{
            const parts = window.location.pathname.split('/').filter(Boolean);
            return parts.length ? '/' + parts[0] : '';
        }catch(e){ return ''; }
    }
    const APP_BASE = getAppBase();
    // State stored on window to prevent duplicate 'let' errors
    if (!window.registerModal) {
        window.registerModal = document.getElementById('registerModalOverlay');
    }
    if (!window.registerFlowState) {
        window.registerFlowState = 'registration'; // 'registration' or 'otp'
    }

    // Initialize register modal (handles case where script runs after DOMContentLoaded)
    if (!window.registerModal) {
        document.addEventListener('DOMContentLoaded', function() {
            window.registerModal = document.getElementById('registerModalOverlay');
        });
    }

    if (!window.openRegisterModal) {
        window.openRegisterModal = function openRegisterModal() {
            if (!window.registerModal) {
                window.registerModal = document.getElementById('registerModalOverlay');
            }
            if (window.registerModal) {
                window.registerModal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                
                // Close login modal if open
                const loginModal = document.getElementById('loginModalOverlay');
                if (loginModal) {
                    loginModal.style.display = 'none';
                }
                
                // Ensure step 1 is shown for paginated modal
                if (typeof showStep === 'function') {
                    try { showStep(1); } catch (e) {}
                }
            } else {
                console.error('Register modal element with id "registerModalOverlay" not found.');
                // Fallback: reload to ensure modal markup is present
                window.location.reload();
            }
        };
    }

    // Close register modal
    if (!window.closeRegisterModal) {
        window.closeRegisterModal = function closeRegisterModal() {
            if (window.registerModal) {
                window.registerModal.style.display = 'none';
                document.body.style.overflow = 'auto';
                
                // Reset form and step
                const form = document.getElementById('registerForm');
                if (form) {
                    form.reset();
                }
                window.registerFlowState = 'registration';
            }
        };
    }

    // Toggle password visibility for register password
    if (!window.toggleRegisterPassword) {
        window.toggleRegisterPassword = function toggleRegisterPassword() {
            const passwordInput = document.getElementById('registerPassword');
            const icon = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.remove('fas');
                icon.classList.add('fa-eye-slash');
                icon.classList.add('fa');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.remove('fas');
                icon.classList.add('fa-eye');
                icon.classList.add('fa');
            }
        };
    }

    // Toggle password visibility for confirm password
    if (!window.toggleRegisterConfirmPassword) {
        window.toggleRegisterConfirmPassword = function toggleRegisterConfirmPassword() {
            const passwordInput = document.getElementById('registerConfirmPassword');
            const icon = passwordInput.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.remove('fas');
                icon.classList.add('fa-eye-slash');
                icon.classList.add('fa');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.remove('fas');
                icon.classList.add('fa-eye');
                icon.classList.add('fa');
            }
        };
    }

    // Switch to login modal
    if (!window.switchToLogin) {
        window.switchToLogin = function switchToLogin() {
            window.closeRegisterModal();
            setTimeout(() => {
                const loginModal = document.getElementById('loginModalOverlay');
                if (loginModal) {
                    loginModal.style.display = 'flex';
                    document.body.style.overflow = 'hidden';
                }
            }, 300);
        };
    }

    // Handle registration form submission
    if (!window.handleRegister) {
        window.handleRegister = function handleRegister(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const submitBtn = document.getElementById('registerSubmitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const loadingText = submitBtn.querySelector('.register-loading');
            
            // Disable submit button
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            loadingText.style.display = 'inline-block';
            
            // Validate password match
            const password = document.getElementById('registerPassword').value;
            const confirmPassword = document.getElementById('registerConfirmPassword').value;
            
            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'Password Mismatch',
                    text: 'Passwords do not match. Please check and try again.'
                });
                
                // Re-enable submit button
                submitBtn.disabled = false;
                btnText.style.display = 'inline-block';
                loadingText.style.display = 'none';
                return;
            }
            
            // Validate password strength
            if (password.length < 6) {
                Swal.fire({
                    icon: 'error',
                    title: 'Weak Password',
                    text: 'Password must be at least 6 characters long.'
                });
                
                // Re-enable submit button
                submitBtn.disabled = false;
                btnText.style.display = 'inline-block';
                loadingText.style.display = 'none';
                return;
            }
            
            // Send registration request
            fetch(APP_BASE + '/Login/register_logic_fixed.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Optional: Adjust UI text based on delivery
                    const msg = data.delivery === 'email' ? 'We sent a code to your email' : 'We sent a code to your phone';
                    // Show OTP step
                    showOTPStep();
                    try {
                        const header = document.querySelector('.login-modal-body .login-modal-header p.text-muted');
                        if (header) header.textContent = msg;
                    } catch(e) {}
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Registration Failed',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred during registration. Please try again.'
                });
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                btnText.style.display = 'inline-block';
                loadingText.style.display = 'none';
            });
        };
    }

    // Show OTP step
    if (!window.showOTPStep) {
        window.showOTPStep = function showOTPStep() {
            window.registerFlowState = 'otp';
            const modalBody = document.querySelector('.login-modal-body');
            
            modalBody.innerHTML = `
                <div class="login-modal-header">
                    <h3>Verify Your Account</h3>
                    <p class="text-muted">Enter the 6-digit code we sent</p>
                </div>
                
                <form id="otpForm" onsubmit="handleOTPVerification(event)">
                    <div class="login-form-group">
                        <label>Verification Code</label>
                        <div class="otp-input-container">
                            <input type="text" maxlength="1" class="otp-input" required>
                            <input type="text" maxlength="1" class="otp-input" required>
                            <input type="text" maxlength="1" class="otp-input" required>
                            <input type="text" maxlength="1" class="otp-input" required>
                            <input type="text" maxlength="1" class="otp-input" required>
                            <input type="text" maxlength="1" class="otp-input" required>
                        </div>
                        <input type="hidden" name="otp" id="otpValue">
                    </div>
                    
                    <button type="submit" class="login-btn login-btn-primary" id="otpSubmitBtn">
                        <span class="btn-text">Verify</span>
                        <span class="otp-loading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Verifying...
                        </span>
                    </button>
                    
                    <div class="login-modal-footer">
                        <p>Didn't receive the code? 
                            <button type="button" class="btn-link" onclick="resendOTP()" id="resendBtn" disabled>
                                Resend <span id="countdown">(30s)</span>
                            </button>
                        </p>
                    </div>
                </form>
            `;
            
            // Setup OTP input handling
            setupOTPInputs();
            startCountdown();
        };
    }

    // Setup OTP input handling
    if (!window.setupOTPInputs) {
        window.setupOTPInputs = function setupOTPInputs() {
            const inputs = document.querySelectorAll('.otp-input');
            const otpField = document.getElementById('otpValue');
            
            inputs.forEach((input, i) => {
                input.addEventListener('input', (e) => {
                    if (e.target.value && i < inputs.length - 1) {
                        inputs[i + 1].focus();
                    }
                    updateOTPValue();
                });
                
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !e.target.value && i > 0) {
                        inputs[i - 1].focus();
                    }
                });
            });
            
            function updateOTPValue() {
                let otpValue = '';
                inputs.forEach(input => otpValue += input.value);
                otpField.value = otpValue;
            }
        };
    }

    // Handle OTP verification
    if (!window.handleOTPVerification) {
        window.handleOTPVerification = function handleOTPVerification(event) {
            event.preventDefault();
            
            const otpValue = document.getElementById('otpValue').value;
            const submitBtn = document.getElementById('otpSubmitBtn');
            const btnText = submitBtn.querySelector('.btn-text');
            const loadingText = submitBtn.querySelector('.otp-loading');
            
            if (otpValue.length !== 6) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid OTP',
                    text: 'Please enter a 6-digit code'
                });
                return;
            }
            
            // Disable submit button
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            loadingText.style.display = 'inline-block';
            
            // Send OTP verification request
            fetch(APP_BASE + '/Login/verify_otp_fixed.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `otp=${otpValue}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const redirectTarget = data.redirect || 'customerdash/cusdash.php';
                    Swal.fire({
                        icon: 'success',
                        title: 'Registration Successful!',
                        text: 'Your account has been created successfully.'
                    }).then(() => {
                        window.location.href = redirectTarget;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid OTP',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred during verification. Please try again.'
                });
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                btnText.style.display = 'inline-block';
                loadingText.style.display = 'none';
            });
        };
    }

    // Start countdown for resend button
    if (!window.startCountdown) {
        window.startCountdown = function startCountdown() {
            let countdown = 30;
            const resendBtn = document.getElementById('resendBtn');
            const countdownSpan = document.getElementById('countdown');
            
            const timer = setInterval(() => {
                countdown--;
                countdownSpan.textContent = `(${countdown}s)`;
                if (countdown <= 0) {
                    clearInterval(timer);
                    resendBtn.disabled = false;
                    countdownSpan.textContent = '';
                }
            }, 1000);
        };
    }

    // Resend OTP
    if (!window.resendOTP) {
        window.resendOTP = function resendOTP() {
            fetch(APP_BASE + '/Login/resend_otp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'resend=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'OTP Resent',
                        text: 'A new OTP has been sent'
                    });
                    startCountdown();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed',
                        text: 'Failed to resend OTP'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while resending OTP'
                });
            });
        };
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        if (window.registerModal && event.target === window.registerModal) {
            window.closeRegisterModal();
        }
    });

    // Close modal on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && window.registerModal && window.registerModal.style.display === 'flex') {
            window.closeRegisterModal();
        }
    });
})();
