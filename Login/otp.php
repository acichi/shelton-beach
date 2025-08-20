<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Enter OTP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Roboto&display=swap" rel="stylesheet">
  
  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <style>
    body {
      font-family: 'Playfair Display', serif;
      background: url('../pics/bg.png') no-repeat center center fixed;
      background-size: cover;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .otp-card {
      background: rgba(255, 255, 255, 0.85);
      border-radius: 20px;
      padding: 30px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
      max-width: 400px;
      width: 100%;
    }

    .otp-title {
      color: #e08f5f;
      font-weight: bold;
      text-align: center;
      margin-bottom: 25px;
    }

    .otp-input {
      width: 45px;
      height: 50px;
      text-align: center;
      font-size: 24px;
      font-family: 'Playfair Display', serif;
      margin: 0 5px;
      border: 2px solid #7ab4a1;
      border-radius: 10px;
      outline: none;
      transition: 0.3s ease;
    }

    .otp-input:focus {
      border-color: #e19985;
      box-shadow: 0 0 5px rgba(225, 153, 133, 0.6);
    }

    .otp-form {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 20px;
    }

    .btn-theme {
      background-color: #7ab4a1;
      border: none;
      font-weight: bold;
      font-family: 'Playfair Display', serif;
    }

    .btn-theme:hover {
      background-color: #e08f5f;
    }

    @media (max-width: 480px) {
      .otp-input {
        width: 38px;
        height: 45px;
        font-size: 20px;
        margin: 0 3px;
      }
    }
  </style>
</head>
<body>

  <div class="otp-card text-center">
    <h4 class="otp-title">OTP Verification</h4>
    <form method="POST" action="#" id="otpForm" class="d-flex flex-column align-items-center">
      <div class="otp-form">
        <input type="text" maxlength="1" class="otp-input" required>
        <input type="text" maxlength="1" class="otp-input" required>
        <input type="text" maxlength="1" class="otp-input" required>
        <input type="text" maxlength="1" class="otp-input" required>
        <input type="text" maxlength="1" class="otp-input" required>
        <input type="text" maxlength="1" class="otp-input" required>
      </div>
      <input type="hidden" name="otp" id="otp">
      <button type="submit" class="btn btn-theme w-100 py-2">Verify</button>
    </form>

    <!-- Resend OTP -->
    <div class="mt-3">
      <button type="button" class="btn btn-link text-decoration-none" id="resendBtn" onclick="resendOTP()" disabled>
        Resend OTP <span id="countdown">(30s)</span>
      </button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/notify.js?v=<?php echo filemtime(__DIR__ . '/../js/notify.js'); ?>"></script>
  <script>
    const inputs = document.querySelectorAll('.otp-input');
    const otpField = document.getElementById('otp');
    const form = document.getElementById('otpForm');
    let timer = null;

    // Auto-focus next input
    inputs.forEach((input, i) => {
      input.addEventListener('input', () => {
        if (input.value && i < inputs.length - 1) {
          inputs[i + 1].focus();
        }
      });
    });

    // Handle submit via AJAX to verify_otp_fixed.php
    const BASE = '/' + (location.pathname.split('/').filter(Boolean)[0]||'');
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      let otpValue = '';
      inputs.forEach(input => otpValue += input.value);
      if (otpValue.length !== 6) {
        if (window.SBHNotify) SBHNotify.warning('Please enter the 6-digit code'); else if (window.Swal) Swal.fire({icon:'warning',title:'Please enter the 6-digit code',toast:true,position:'top-end',timer:2200,showConfirmButton:false}); else alert('Please enter the 6-digit code');
        return;
      }

      fetch(BASE + '/Login/verify_otp_fixed.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'otp=' + encodeURIComponent(otpValue)
      })
      .then(r => r.json())
      .then(data => {
        if (data && data.success) {
          window.location.href = data.redirect || '../customerdash/cusdash.php';
        } else {
          if (window.SBHNotify) SBHNotify.error((data && data.message) || 'Invalid OTP'); else if (window.Swal) Swal.fire({icon:'error',title:(data && data.message) || 'Invalid OTP'}); else alert((data && data.message) || 'Invalid OTP');
        }
      })
      .catch(() => { if (window.SBHNotify) SBHNotify.error('An error occurred. Please try again.'); else if (window.Swal) Swal.fire({icon:'error',title:'An error occurred. Please try again.'}); else alert('An error occurred. Please try again.'); });
    });

    // Countdown timer
    let countdown = 30;
    const resendBtn = document.getElementById('resendBtn');
    const countdownSpan = document.getElementById('countdown');

    timer = setInterval(() => {
      countdown--;
      countdownSpan.textContent = `(${countdown}s)`;
      if (countdown <= 0) {
        clearInterval(timer);
        resendBtn.disabled = false;
        countdownSpan.textContent = '';
      }
    }, 1000);

    // Resend OTP function
    function resendOTP() {
      if (resendBtn.disabled) return;
      resendBtn.disabled = true;
      fetch(BASE + '/Login/resend_otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'resend=1'
      })
      .then(r => r.json())
      .then(data => {
        if (data && data.success) {
          // restart countdown
          countdown = 30;
          countdownSpan.textContent = `(${countdown}s)`;
          if (timer) clearInterval(timer);
          timer = setInterval(() => {
            countdown--;
            countdownSpan.textContent = `(${countdown}s)`;
            if (countdown <= 0) {
              clearInterval(timer);
              resendBtn.disabled = false;
              countdownSpan.textContent = '';
            }
          }, 1000);
        } else {
          if (window.SBHNotify) SBHNotify.error('Failed to resend OTP'); else if (window.Swal) Swal.fire({icon:'error',title:'Failed to resend OTP',toast:true,position:'top-end'}); else alert('Failed to resend OTP');
          resendBtn.disabled = false;
        }
      })
      .catch(() => {
        if (window.SBHNotify) SBHNotify.error('An error occurred while resending OTP'); else if (window.Swal) Swal.fire({icon:'error',title:'An error occurred while resending OTP',toast:true,position:'top-end'}); else alert('An error occurred while resending OTP');
        resendBtn.disabled = false;
      });
    }
  </script>

</body>
</html>
