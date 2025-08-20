<!-- Fixed Register Modal - Using same CSS as login modal -->
<div class="login-modal-overlay" id="registerModalOverlay">
    <div class="login-modal">
        <button type="button" class="login-close" aria-label="Close modal" onclick="closeRegisterModal()">
            <i class="fa fa-times"></i>
        </button>
        
        <div class="login-modal-header">
            <h3>Create Your Account</h3>
        </div>
        
        <div class="login-modal-body">
            <form id="registerForm" method="POST" action="register_logic.php" onsubmit="handleRegister(event)">
                <div class="login-form-group">
                    <label for="registerName">Full Name</label>
                    <input type="text" id="registerName" name="name" class="login-form-control" required>
                </div>
                
                <div class="login-form-group">
                    <label for="registerEmail">Email Address</label>
                    <input type="email" id="registerEmail" name="email" class="login-form-control" required>
                </div>
                
                <div class="login-form-group">
                    <label for="registerUsername">Username</label>
                    <input type="text" id="registerUsername" name="user" class="login-form-control" required>
                </div>
                
                <div class="login-form-group">
                    <label for="registerMobile">Phone Number</label>
                    <input type="tel" id="registerMobile" name="mobile" class="login-form-control" required>
                </div>
                
                <div class="login-form-group">
                    <label for="registerPassword">Password</label>
                    <div class="password-toggle">
                        <input type="password" id="registerPassword" name="password" class="login-form-control" required>
                        <span class="toggle-password" onclick="toggleRegisterPassword()" title="Toggle password visibility">
                            <i class="fa fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="login-form-group">
                    <label for="registerConfirmPassword">Confirm Password</label>
                    <div class="password-toggle">
                        <input type="password" id="registerConfirmPassword" name="confirm_password" class="login-form-control" required>
                        <span class="toggle-password" onclick="toggleRegisterConfirmPassword()" title="Toggle password visibility">
                            <i class="fa fa-eye"></i>
                        </span>
                    </div>
                </div>
                
                <div class="login-form-check">
                    <input type="checkbox" id="agreeTerms" name="agree_terms" required>
                    <label for="agreeTerms">I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a></label>
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
                
                <button type="submit" class="login-btn login-btn-primary" id="registerSubmitBtn">
                    <span class="btn-text">Create Account</span>
                    <span class="register-loading" style="display: none;">
                        <i class="fa fa-spinner fa-spin"></i> Creating account...
                    </span>
                </button>
            </form>
            
            <div class="login-modal-footer">
                <p>Already have an account? <a href="#" onclick="switchToLogin()">Login here</a></p>
            </div>
        </div>
    </div>
</div>
