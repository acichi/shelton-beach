<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) { header('Location: ../index.php'); exit; }

$rows = [];
$q = $conn->prepare('SELECT id, action, details, ip_address, user_agent, created_at FROM activity_logs_tbl WHERE user_id = ? AND scope = "admin" ORDER BY created_at DESC, id DESC LIMIT 200');
$q->bind_param('i', $userId);
$q->execute();
$res = $q->get_result();
while ($row = $res->fetch_assoc()) { $rows[] = $row; }
$q->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Activity Log - Admin</title>
  <link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../template/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="admindash.php" class="logo d-flex align-items-center"><img src="../pics/logo2.png" alt=""><span class="d-none d-lg-block">Shelton Admin</span></a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>
  </header>
  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item"><a class="nav-link" href="admindash.php"><i class="bi bi-grid"></i><span>Dashboard</span></a></li>
      <li class="nav-item"><a class="nav-link" href="settings.php"><i class="bi bi-geo-alt"></i><span>Settings</span></a></li>
      <li class="nav-item"><a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i><span>Notifications</span></a></li>
      <li class="nav-item"><a class="nav-link" href="activity.php"><i class="bi bi-activity"></i><span>Activity Log</span></a></li>
    </ul>
  </aside>
  <main id="main" class="main">
    <div class="pagetitle"><h1>Activity Log</h1></div>
    <section class="section">
      <div class="card"><div class="card-body">
        <h5 class="card-title">Recent</h5>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>When</th><th>Action</th><th>Details</th><th>IP</th><th>User Agent</th></tr></thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="5" class="text-muted">No activity yet.</td></tr>
              <?php else: foreach ($rows as $n): ?>
                <tr>
                  <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($n['created_at']))); ?></td>
                  <td><?php echo htmlspecialchars($n['action']); ?></td>
                  <td><?php echo htmlspecialchars($n['details'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($n['ip_address'] ?? ''); ?></td>
                  <td class="text-truncate" style="max-width:320px;">&lrm;<?php echo htmlspecialchars($n['user_agent'] ?? ''); ?>&lrm;</td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div></div>
    </section>
  </main>
  <script src="../template/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>


