<?php
function showSweetAlert($message, $redirectUrl, $type = 'success') {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notification | Shelton Beach Haven</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: url('../pics/bg.png') no-repeat center center fixed;
      background-size: cover;
      font-family: 'Playfair Display', serif;
    }
    .swal2-popup {
      font-family: 'Playfair Display', serif;
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
    }
    .swal2-title {
      font-size: 1.5rem;
    }
  </style>
</head>
<body>

<script>
  Swal.fire({
    icon: '$type',
    title: `$message`,
    showConfirmButton: false,
    timer: 2500,
    background: '#ffffffee',
    color: '#333',
    timerProgressBar: true,
    didClose: () => {
      window.location.href = '$redirectUrl';
    }
  });
</script>

</body>
</html>
HTML;
    exit;
}
?>
