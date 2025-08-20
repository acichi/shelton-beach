<?php
require __DIR__ . '/_auth.php';
require __DIR__ . '/../properties/connection.php';

$userId = (int)($_SESSION['user']['id'] ?? 0);
if ($userId <= 0) { header('Location: ../index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['action'] ?? '';
  if ($act === 'mark_read') {
    $nid = (int)($_POST['id'] ?? 0);
    if ($nid > 0) {
      $u = $conn->prepare('UPDATE notifications_tbl SET is_read = 1 WHERE id = ? AND user_id = ? AND scope = "admin"');
      $u->bind_param('ii', $nid, $userId);
      $u->execute();
      $u->close();
    }
    header('Location: notifications.php'); exit;
  }
}

// Fetch notifications
$rows = [];
$q = $conn->prepare('SELECT id, type, title, message, channel, is_read, created_at FROM notifications_tbl WHERE user_id = ? AND scope = "admin" ORDER BY created_at DESC, id DESC LIMIT 200');
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
  <title>Notifications - Admin</title>
  <link href="../template/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="../template/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../template/assets/css/style.css" rel="stylesheet">
</head>
<body>
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="admindash.php" class="logo d-flex align-items-center">
        <img src="../pics/logo2.png" alt=""><span class="d-none d-lg-block">Shelton Admin</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>
  </header>
  <aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
      <li class="nav-item"><a class="nav-link" href="admindash.php"><i class="bi bi-grid"></i><span>Dashboard</span></a></li>
      <li class="nav-item"><a class="nav-link" href="settings.php"><i class="bi bi-geo-alt"></i><span>Settings</span></a></li>
      <li class="nav-item"><a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i><span>Notifications</span></a></li>
    </ul>
  </aside>
  <main id="main" class="main">
    <div class="pagetitle"><h1>Notifications</h1></div>
    <section class="section">
      <div class="card"><div class="card-body">
        <h5 class="card-title">Recent</h5>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>When</th><th>Type</th><th>Title</th><th>Message</th><th>Channel</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-muted">No notifications yet.</td></tr>
              <?php else: foreach ($rows as $n): ?>
                <tr class="<?php echo $n['is_read'] ? '' : 'table-warning'; ?>">
                  <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($n['created_at']))); ?></td>
                  <td><?php echo htmlspecialchars($n['type']); ?></td>
                  <td><?php echo htmlspecialchars($n['title']); ?></td>
                  <td><?php echo htmlspecialchars($n['message'] ?? ''); ?></td>
                  <td><?php echo htmlspecialchars($n['channel']); ?></td>
                  <td><?php echo $n['is_read'] ? 'Read' : 'Unread'; ?></td>
                  <td>
                    <?php if (!$n['is_read']): ?>
                      <form method="post" class="d-inline">
                        <input type="hidden" name="action" value="mark_read">
                        <input type="hidden" name="id" value="<?php echo (int)$n['id']; ?>">
                        <button class="btn btn-sm btn-outline-secondary">Mark as read</button>
                      </form>
                    <?php endif; ?>
                  </td>
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


