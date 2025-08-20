<!-- Paginated Register Modal - Multi-step registration -->
<div class="login-modal-overlay" id="registerModalOverlay">
    <div class="login-modal">
        <button type="button" class="login-close" aria-label="Close modal" onclick="closeRegisterModal()">
            <i class="fa fa-times"></i>
        </button>
        
        <div class="login-modal-header">
            <h3>Create Your Account</h3>
            <p class="text-muted mb-2">Step 1 of 3: Complete your registration</p>
            <div class="step-indicator">
                <span class="step active" data-step="1">1</span>
                <span class="step-line"></span>
                <span class="step" data-step="2">2</span>
                <span class="step-line"></span>
                <span class="step" data-step="3">3</span>
            </div>
        </div>
        
        <div class="login-modal-body">
            <form id="registerForm" method="POST" action="#" onsubmit="handlePaginatedRegister(event)" autocomplete="on" novalidate>
                
                <!-- Step 1: Personal Information -->
                <div class="register-step" id="step1">
                    <h4>Personal Information</h4>
                    
                    <div class="name-fields">
                        <div class="login-form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" id="firstName" name="first_name" class="login-form-control" required autocomplete="given-name">
                        </div>
                        
                        <div class="login-form-group">
                            <label for="middleName">Middle Name</label>
                            <input type="text" id="middleName" name="middle_name" class="login-form-control" autocomplete="additional-name">
                        </div>
                        
                        <div class="login-form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" id="lastName" name="last_name" class="login-form-control" required autocomplete="family-name">
                        </div>
                    </div>
                    
                    <div class="login-form-group">
                        <label for="registerEmail">Email Address</label>
                        <input type="email" id="registerEmail" name="email" class="login-form-control" required autocomplete="email">
                    </div>

                    <div class="login-form-group">
                        <label for="registerGender">Gender</label>
                        <select id="registerGender" name="gender" class="login-form-control" required>
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="login-form-group">
                        <label for="registerAddress">Address</label>
                        <input type="text" id="registerAddress" name="address" class="login-form-control" placeholder="House/Street, City, Province" autocomplete="street-address">
                    </div>
                    
                    <div class="step-navigation">
                        <button type="button" class="login-btn login-btn-secondary" onclick="switchToLogin()">← Back to Login</button>
                        <button type="button" class="login-btn login-btn-primary" onclick="nextStep(2)">Next Step</button>
                    </div>
                </div>
                
                <!-- Step 2: Account Details -->
                <div class="register-step" id="step2" style="display: none;">
                    <h4>Account Details</h4>
                    
                    <div class="login-form-group">
                        <label for="registerUsername">Username</label>
                        <input type="text" id="registerUsername" name="user" class="login-form-control" required autocomplete="username">
                    </div>
                    
                    <div class="login-form-group">
                        <label for="registerMobile">Phone Number</label>
                        <input type="tel" id="registerMobile" name="mobile" class="login-form-control" required autocomplete="tel">
                    </div>
                    
                    <div class="login-form-group">
                        <label>Verify via</label>
                        <div class="login-form-radio-group">
                            <label style="margin-right:12px;">
                                <input type="radio" name="otp_delivery" value="phone" checked> SMS (phone)
                            </label>
                            <label>
                                <input type="radio" name="otp_delivery" value="email"> Email
                            </label>
                        </div>
                    </div>
                    
                    <div class="login-form-group">
                        <label for="registerPassword">Password</label>
                        <div class="password-toggle">
                            <input type="password" id="registerPassword" name="password" class="login-form-control" required autocomplete="new-password">
                            <span class="toggle-password" onclick="toggleRegisterPassword()" title="Toggle password visibility">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="login-form-group">
                        <label for="registerConfirmPassword">Confirm Password</label>
                        <div class="password-toggle">
                            <input type="password" id="registerConfirmPassword" name="confirm_password" class="login-form-control" required autocomplete="new-password">
                            <span class="toggle-password" onclick="toggleRegisterConfirmPassword()" title="Toggle password visibility">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="step-navigation">
                        <button type="button" class="login-btn login-btn-secondary" onclick="switchToLogin()">← Back to Login</button>
                        <button type="button" class="login-btn login-btn-secondary" onclick="prevStep(1)">Previous</button>
                        <button type="button" class="login-btn login-btn-primary" onclick="nextStep(3)">Next Step</button>
                    </div>
                </div>
                
                <!-- Step 3: OTP Verification & Terms -->
                <div class="register-step" id="step3" style="display: none;">
                    <h4>Phone Verification</h4>
                    <p class="text-muted">Enter the 6-digit code sent to your phone</p>
                    
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
                    
                    <div class="login-form-check">
                        <input type="checkbox" id="agreeTerms" name="agree_terms" required>
                        <label for="agreeTerms">I agree to the <a href="#" onclick="showTerms()">Terms and Conditions</a></label>
                    </div>
                    
                    <div class="step-navigation">
                        <button type="button" class="login-btn login-btn-secondary" onclick="switchToLogin()">← Back to Login</button>
                        <button type="button" class="login-btn login-btn-secondary" onclick="prevStep(2)">Previous</button>
                        <button type="submit" class="login-btn login-btn-primary" id="registerSubmitBtn">
                            <span class="btn-text">Create Account</span>
                            <span class="register-loading" style="display: none;">
                                <i class="fa fa-spinner fa-spin"></i> Creating account...
                            </span>
                        </button>
                    </div>
                    
                    <div class="login-modal-footer">
                        <p>Didn't receive the code? 
                            <button type="button" class="btn-link" onclick="resendOTP()" id="resendBtn" disabled>
                                Resend <span id="countdown">(30s)</span>
                            </button>
                        </p>
                    </div>
                </div>
            </form>
            
            <div class="login-modal-footer">
                <p>Already have an account? <a href="#" onclick="switchToLogin()">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<style>
/* Paginated Register Modal Styles */
.register-step {animation: fadeIn .25s ease-in-out}
@keyframes fadeIn {from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)}}
.step-indicator{display:flex;align-items:center;justify-content:center;margin:14px 0}
.step{width:30px;height:30px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-weight:700;color:#6b7280;transition:all .2s ease}
.step.active{background:#7ab4a1;color:#fff}
.step-line{width:46px;height:2px;background:#e5e7eb;margin:0 8px}
.name-fields{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}
.step-navigation{display:flex;justify-content:space-between;align-items:center;margin-top:16px;gap:10px}
.step-navigation .login-btn{flex:1;min-width:0}
.login-form-control{width:100%;padding:12px 14px;border:1px solid #d1d5db;border-radius:10px;background:#fff;font-size:.95rem}
.login-form-control:focus{outline:none;border-color:#7ab4a1;box-shadow:0 0 0 3px rgba(122,180,161,.2)}
.password-toggle{position:relative}
.password-toggle .toggle-password{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#6b7280}
.login-btn{display:inline-flex;align-items:center;justify-content:center;width:100%;padding:12px 16px;border-radius:10px;border:0;background:#7ab4a1;color:#fff;cursor:pointer;font-weight:600}
.login-btn.login-btn-secondary{background:#e5e7eb;color:#111827}
.btn-link{background:none;border:0;color:#0d6efd;cursor:pointer}
.login-form-radio-group{display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.otp-input-container{display:flex;gap:10px;justify-content:center;margin:15px 0}
.otp-input{width:45px;height:45px;text-align:center;font-size:18px;font-weight:700;border:2px solid #e5e7eb;border-radius:8px;background:#fff;transition:all .2s ease}
.otp-input:focus{border-color:#7ab4a1;box-shadow:0 0 0 3px rgba(122,180,161,.15);outline:none}
.otp-input:valid{border-color:#28a745;background:#f8fff9}
.login-btn:disabled{opacity:.65;cursor:not-allowed}
@media (max-width: 768px){
	.name-fields{grid-template-columns:1fr}
	.step-navigation{flex-direction:column;gap:10px}
	.otp-input-container{gap:8px}
	.otp-input{width:40px;height:40px;font-size:16px}
}
</style>

<script>
// Paginated Register Modal JavaScript (scoped to avoid global collisions)
(function() {
    function getAppBase(){
        try{
            const parts = window.location.pathname.split('/').filter(Boolean);
            return parts.length ? '/' + parts[0] : '';
        }catch(e){ return ''; }
    }
    const APP_BASE = getAppBase();
    let currentStep = 1;
    const totalSteps = 3;

    function showStep(step) {
        document.querySelectorAll('.register-step').forEach(s => s.style.display = 'none');
        const stepEl = document.getElementById(`step${step}`);
        if (stepEl) stepEl.style.display = 'block';
        document.querySelectorAll('.step').forEach((s, index) => {
            s.classList.toggle('active', index + 1 === step);
        });
        currentStep = step;
    }

    function validateCurrentStep(step) {
        let isValid = true;
        switch(step) {
            case 1: {
                const firstName = document.getElementById('firstName').value.trim();
                const lastName = document.getElementById('lastName').value.trim();
                const email = document.getElementById('registerEmail').value.trim();
                if (!firstName || !lastName || !email) {
                    alert('Please fill in all required fields');
                    isValid = false;
                }
                break;
            }
            case 2: {
                const username = document.getElementById('registerUsername').value.trim();
                const mobile = document.getElementById('registerMobile').value.trim();
                const password = document.getElementById('registerPassword').value;
                const confirmPassword = document.getElementById('registerConfirmPassword').value;
                if (!username || !mobile || !password || !confirmPassword) {
                    alert('Please fill in all required fields');
                    isValid = false;
                } else if (password !== confirmPassword) {
                    alert('Passwords do not match');
                    isValid = false;
                } else if (password.length < 6) {
                    alert('Password must be at least 6 characters');
                    isValid = false;
                }
                break;
            }
            case 3: {
                const otpValue = document.getElementById('otpValue').value;
                const agreeTerms = document.getElementById('agreeTerms').checked;
                if (!otpValue || otpValue.length !== 6) {
                    alert('Please enter the 6-digit verification code');
                    isValid = false;
                } else if (!agreeTerms) {
                    alert('Please agree to the terms and conditions');
                    isValid = false;
                }
                break;
            }
        }
        return isValid;
    }

    // Modified nextStep function to send OTP when moving to step 3
    function nextStep(step) {
        if (step === 3 && currentStep === 2) {
            // Send OTP when moving to step 3
            sendOTPAndProceed();
        } else if (validateCurrentStep(currentStep)) {
            showStep(step);
        } else {
            alert('Please fill in all required fields before proceeding.');
        }
    }

    // Function to send OTP and proceed to step 3
    function sendOTPAndProceed() {
        const firstName = document.getElementById('firstName').value.trim();
        const middleName = document.getElementById('middleName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const fullName = `${firstName} ${middleName ? middleName + ' ' : ''}${lastName}`.trim();
        
        const formData = new FormData();
        formData.append('name', fullName);
        formData.append('first_name', firstName);
        formData.append('middle_name', middleName);
        formData.append('last_name', lastName);
        formData.append('email', document.getElementById('registerEmail').value.trim());
        formData.append('gender', document.getElementById('registerGender').value);
        formData.append('address', document.getElementById('registerAddress').value.trim());
        formData.append('user', document.getElementById('registerUsername').value.trim());
        formData.append('password', document.getElementById('registerPassword').value);
        formData.append('mobile', document.getElementById('registerMobile').value.trim());
        formData.append('confirm_password', document.getElementById('registerConfirmPassword').value);
        const otpDeliveryEl = document.querySelector('input[name="otp_delivery"]:checked');
        if (otpDeliveryEl) { formData.append('otp_delivery', otpDeliveryEl.value); }
        
        // Show loading state
        const nextBtn = document.querySelector('button[onclick="nextStep(3)"]');
        const originalText = nextBtn.innerHTML;
        nextBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending OTP...';
        nextBtn.disabled = true;
        
        fetch(APP_BASE + '/Login/register_logic_fixed.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // If server fell back to another channel, update the selection to match
                if (data.delivery === 'email') {
                    const emailRadio = document.querySelector('input[name="otp_delivery"][value="email"]');
                    if (emailRadio) emailRadio.checked = true;
                } else if (data.delivery === 'phone') {
                    const phoneRadio = document.querySelector('input[name="otp_delivery"][value="phone"]');
                    if (phoneRadio) phoneRadio.checked = true;
                }
                showStep(3);
                updateVerificationCopy();
                setupOTPInputs();
                startCountdown();
            } else {
                alert(data.message || 'Failed to send OTP');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending OTP');
        })
        .finally(() => {
            nextBtn.innerHTML = originalText;
            nextBtn.disabled = false;
        });
    }

    // Setup OTP input handling
    function setupOTPInputs() {
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
    }

    // Start countdown for resend button
    function startCountdown() {
        let countdown = 30;
        const resendBtn = document.getElementById('resendBtn');
        const countdownSpan = document.getElementById('countdown');
        
        resendBtn.disabled = true;
        
        const timer = setInterval(() => {
            countdown--;
            countdownSpan.textContent = `(${countdown}s)`;
            if (countdown <= 0) {
                clearInterval(timer);
                resendBtn.disabled = false;
                countdownSpan.textContent = '';
            }
        }, 1000);
    }

    // Resend OTP function
    function resendOTP() {
        const firstName = document.getElementById('firstName').value.trim();
        const middleName = document.getElementById('middleName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const fullName = `${firstName} ${middleName ? middleName + ' ' : ''}${lastName}`.trim();
        
        const formData = new FormData();
        formData.append('name', fullName);
        formData.append('email', document.getElementById('registerEmail').value.trim());
        formData.append('user', document.getElementById('registerUsername').value.trim());
        formData.append('password', document.getElementById('registerPassword').value);
        formData.append('mobile', document.getElementById('registerMobile').value.trim());
        formData.append('confirm_password', document.getElementById('registerConfirmPassword').value);
        const otpDeliveryEl2 = document.querySelector('input[name="otp_delivery"]:checked');
        if (otpDeliveryEl2) { formData.append('otp_delivery', otpDeliveryEl2.value); }
        
        fetch('Login/resend_otp.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('New OTP sent successfully');
                startCountdown();
            } else {
                alert(data.message || 'Failed to resend OTP');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resending OTP');
        });
    }

    function updateVerificationCopy() {
        const channel = (document.querySelector('input[name="otp_delivery"]:checked') || {}).value || 'phone';
        const step3 = document.getElementById('step3');
        if (!step3) return;
        const h4 = step3.querySelector('h4');
        const p = step3.querySelector('p.text-muted');
        if (h4) { h4.textContent = channel === 'email' ? 'Email Verification' : 'Phone Verification'; }
        if (p) { p.textContent = channel === 'email' ? 'Enter the 6-digit code sent to your email' : 'Enter the 6-digit code sent to your phone'; }
    }

    function handlePaginatedRegister(event) {
        event.preventDefault();
        console.log('Registration form submitted');
        
        if (!validateCurrentStep(3)) return;
        
        const firstName = document.getElementById('firstName').value.trim();
        const middleName = document.getElementById('middleName').value.trim();
        const lastName = document.getElementById('lastName').value.trim();
        const fullName = `${firstName} ${middleName ? middleName + ' ' : ''}${lastName}`.trim();
        const otpValue = document.getElementById('otpValue').value;
        
        console.log('Full name:', fullName);
        console.log('OTP:', otpValue);
        
        const submitBtn = document.getElementById('registerSubmitBtn');
        const btnText = submitBtn.querySelector('.btn-text');
        const loadingText = submitBtn.querySelector('.register-loading');
        
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        loadingText.style.display = 'inline-block';
        
        console.log('Verifying OTP and creating account...');
        
        // Verify OTP and create account
        fetch(APP_BASE + '/Login/verify_otp_fixed.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `otp=${otpValue}`
        })
        .then(response => {
            console.log('Response status:', response.status);
            return response.text();
        })
        .then(text => {
            console.log('Raw response:', text);
            
            let data;
            try {
                // Strip BOM and trim stray whitespace before parsing
                const clean = text.replace(/^\uFEFF/, '').trim();
                data = JSON.parse(clean);
            } catch (e) {
                console.error('Failed to parse JSON response:', e);
                // Surface the first 200 chars of the server response to help debugging
                const snippet = (text || '').slice(0, 200);
                throw new Error('Invalid response format from server. Server said: ' + snippet);
            }
            
            console.log('Parsed response:', data);
            
            if (data.success) {
                console.log('Account created successfully, redirecting to login');
                
                // Show success message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Account Created Successfully!',
                        text: 'Your account has been created. You can now login.',
                        confirmButtonText: 'Login Now'
                    }).then(() => {
                        // Close register modal and open login modal
                        closeRegisterModal();
                        setTimeout(() => {
                            const loginModal = document.getElementById('loginModalOverlay');
                            if (loginModal) {
                                loginModal.style.display = 'flex';
                                document.body.style.overflow = 'hidden';
                            }
                        }, 300);
                    });
                } else {
                    alert('Account created successfully! You can now login.');
                    closeRegisterModal();
                    setTimeout(() => {
                        const loginModal = document.getElementById('loginModalOverlay');
                        if (loginModal) {
                            loginModal.style.display = 'flex';
                            document.body.style.overflow = 'hidden';
                        }
                    }, 300);
                }
            } else {
                console.error('Account creation failed:', data.message);
                alert(data.message || 'Account creation failed');
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('An error occurred during account creation: ' + error.message);
        })
        .finally(() => {
            console.log('Resetting button state');
            submitBtn.disabled = false;
            btnText.style.display = 'inline-block';
            loadingText.style.display = 'none';
        });
    }

    // Expose only necessary functions globally for onclick handlers
    window.showStep = showStep;
    window.nextStep = nextStep; // Use the modified nextStep
    window.prevStep = function(step) { showStep(step); };
    window.handlePaginatedRegister = handlePaginatedRegister;
    
    // Terms and Conditions Modal
    window.showTerms = function() {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Terms & Conditions',
                width: '60%',
                html: `
                    <div style="text-align: left; font-family: Arial, sans-serif; font-size: 14px; max-height: 400px; overflow-y: auto;">
                        <h6>Shelton Beach Resort – Bacolod, Negros Occidental</h6>
                        <p>By creating an account, you agree to the following:</p>
                        <hr>
                        <strong>1. Account Usage</strong>
                        <ul>
                            <li>Provide accurate personal information when registering.</li>
                            <li>Do not share your login credentials with others.</li>
                            <li>We may suspend accounts that violate our rules.</li>
                        </ul>
                        <strong>2. Guest Conduct</strong>
                        <ul>
                            <li>Respect resort staff, guests, and property at all times.</li>
                            <li>Guests are responsible for any damages caused.</li>
                            <li>We reserve the right to deny service for misconduct.</li>
                        </ul>
                        <strong>3. Privacy</strong>
                        <ul>
                            <li>Your data is used for booking and communication only.</li>
                            <li>We do not share your personal info with third parties.</li>
                        </ul>
                        <strong>4. Availability & Liability</strong>
                        <ul>
                            <li>Access to facilities is subject to availability.</li>
                            <li>We are not liable for system issues or disruptions.</li>
                        </ul>
                    </div>
                `,
                background: '#fffaf5',
                color: '#3b3a36',
                confirmButtonText: 'I Understand',
                backdrop: 'rgba(122, 180, 161, 0.3)',
                customClass: {
                    popup: 'rounded-4 shadow',
                    title: 'fw-bold',
                    confirmButton: 'btn btn-success px-4 mt-3',
                },
            });
        } else {
            alert('Terms & Conditions:\n\nBy creating an account, you agree to provide accurate information, respect resort policies, and understand our privacy practices.');
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        showStep(1);
    });
})();
</script>
