<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login | Shelton Beach Haven</title>

  <!-- Fonts & Core Styles -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../css/theme-overrides.css" rel="stylesheet" />
  <link rel="icon" href="pics/logo2.png" type="image/png">

  <style>
  body {
    font-family: 'Roboto', sans-serif;
    background: url('../pics/bg.png') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
  }

  .form-wrapper {
    background-color: rgba(247, 135, 71, 0.35);
    backdrop-filter: blur(6px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 2rem;
    padding: 2rem;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  }

  .form-wrapper h2 {
    font-family: 'Playfair Display', serif;
    color: #fff;
    text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
    font-size: 1.8rem;
  }

  .form-wrapper img {
    width: 150px;
    height: 150px;
    object-fit: cover;
  }

  .form-wrapper .form-label,
  .form-wrapper p,
  .form-wrapper a,
  .form-wrapper label {
    color: #fff;
    font-size: 0.9rem;
  }

  .form-wrapper .form-control {
    background-color: rgba(255, 255, 255, 0.85);
    border: none;
    padding: 0.5rem 0.75rem;
    font-size: 0.9rem;
  }

  .btn {
    padding: 0.5rem;
    font-size: 0.9rem;
  }

  .btn-warning {
    background-color: #e08f5f;
    border: none;
  }

  .btn-warning:hover {
    background-color: #d27c49;
  }

  .btn-success {
    background-color: #7ab4a1;
    border: none;
  }

  .btn-success:hover {
    background-color: #65a291;
  }

  .back-home-btn {
    position: fixed;
    top: 20px;
    left: 20px;
    padding: 10px 20px;
    background-color: rgba(247, 135, 71, 0.35);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 50px;
    font-weight: 600;
    color: #fff;
    text-decoration: none;
    font-family: 'Roboto', sans-serif;
    backdrop-filter: blur(6px);
    transition: background 0.3s ease, box-shadow 0.3s ease;
    z-index: 999;
  }

  .back-home-btn:hover {
    background-color: rgba(255, 255, 255, 0.3);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    color: #fff;
  }

  .swal2-popup {
    font-family: 'Playfair Display', serif !important;
    border: 2px solid #7ab4a1;
    background: #fffdfc;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
  }

  .swal2-title {
    font-size: 24px !important;
  }

  .swal2-confirm {
    background-color: #7ab4a1 !important;
    border: none !important;
    font-family: 'Playfair Display', serif;
  }

  @media (max-width: 576px) {
    .form-wrapper {
      padding: 1.5rem;
      max-width: 90vw;
    }

    .form-wrapper h2 {
      font-size: 1.5rem;
    }

    .form-wrapper img {
      width: 120px;
      height: 120px;
    }

    .btn {
      font-size: 0.85rem;
    }
  }
</style>
</head>
<body>
  <a href="../index.php" class="back-home-btn">← Back to Home</a>

  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="form-wrapper">
      <h2 class="text-center mb-4">Welcome!</h2>

      <div class="text-center mb-4">
        <img src="../pics/profile.png" alt="Profile" class="rounded-circle" width="300" height="300">
      </div>

      <!-- LOGIN FORM -->
      <div id="login-form-wrapper">
        <form method="POST" action="login_logic.php">
          <div class="mb-3">
            <label class="form-label">Email or Username</label>
            <input type="text" name="email" class="form-control" placeholder="Enter email or username" required>
          </div>
          <div class="mb-3 position-relative">
            <label class="form-label">Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
            <span id="toggle-password" class="position-absolute top-50 end-0 translate-middle-y pe-3" style="cursor: pointer;">👁️</span>
          </div>
          <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="agreeLogin" disabled>
            <label class="form-check-label" for="agreeLogin">
              I agree to the <a href="#" id="viewTerms">Terms and Conditions</a>
            </label>
          </div>
          <button type="submit" class="btn btn-warning w-100" id="loginBtn">Login</button>
          <div class="text-center mt-3">
            <p>Don't have an account? <a href="#" id="show-register">Register here</a></p>
          </div>
        </form>
      </div>

      <!-- REGISTER FORM -->
      <div id="register-form-wrapper" class="d-none">
        <form method="POST" action="register_logic.php">
          <div class="row">
            <div class="col-md-6 mb-2">
              <label class="form-label">Full Name</label>
              <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Username</label>
              <input type="text" name="user" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Mobile</label>
              <input type="text" name="mobile" class="form-control" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Address</label>
              <input type="text" name="address" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Gender</label>
              <select name="gender" class="form-control" required>
                <option value="">Select</option>
                <option>Male</option>
                <option>Female</option>
                <option>Other</option>
              </select>
            </div>
          </div>
          <button type="submit" class="btn btn-success w-100">Create Account</button>
          <div class="text-center mt-3">
            <p>Already have an account? <a href="#" id="show-login">Login here</a></p>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../js/notify.js?v=<?php echo filemtime(__DIR__ . '/../js/notify.js'); ?>"></script>

  <script>
    $("#toggle-password").click(function () {
      const input = $("#password");
      input.attr("type", input.attr("type") === "password" ? "text" : "password");
    });

    $("#show-register").click(function () {
      $("#login-form-wrapper").addClass("d-none");
      $("#register-form-wrapper").removeClass("d-none");
    });

    $("#show-login").click(function () {
      $("#register-form-wrapper").addClass("d-none");
      $("#login-form-wrapper").removeClass("d-none");
    });

    // SweetAlert Terms Modal Trigger
    document.getElementById('viewTerms').addEventListener('click', function (e) {
      e.preventDefault();

      Swal.fire({
        title: 'Terms & Conditions',
        width: '40%',
        html: `
          <div style="text-align: left; font-family: 'Playfair Display', serif; font-size: 16px;">
           <div style="text-align: left; font-family: 'Playfair Display', serif; font-size: 16px;">
  <h6>Shelton Beach Resort – Bacolod, Negros Occidental</h6>
  <p>By signing in or creating an account, you agree to the following:</p>
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
        confirmButtonText: 'I Agree',
        backdrop: 'rgba(122, 180, 161, 0.3)',
        customClass: {
          popup: 'rounded-4 shadow',
          title: 'fw-bold',
          confirmButton: 'btn btn-success px-4 mt-3',
        },
      }).then((result) => {
        if (result.isConfirmed) {
          const checkbox = document.getElementById('agreeLogin');
          checkbox.checked = true;
          checkbox.disabled = false;
        }
      });
    });

    // Block form submission if T&C not checked
    $("form").on("submit", function (e) {
      if (!$("#agreeLogin").is(":checked")) {
        e.preventDefault();
        Swal.fire({
          icon: 'warning',
          title: 'Agreement Required',
          text: 'You must agree to the Terms and Conditions before continuing.',
          confirmButtonColor: '#e08f5f',
          customClass: {
            popup: 'rounded-4 shadow',
            title: 'fw-bold',
            confirmButton: 'btn btn-warning px-4 mt-3',
          }
        });
      }
    });
  </script>
</body>
</html>
