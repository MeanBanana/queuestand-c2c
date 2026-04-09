<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireRole('admin');

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalJobs  = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$openJobs   = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status='open'")->fetchColumn();
$recentJobs = $pdo->query("SELECT j.*, u.first_name, u.last_name FROM jobs j JOIN users u ON j.poster_id=u.user_id ORDER BY j.created_at DESC LIMIT 10")->fetchAll();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard | QueueStand</title>
    <link rel="stylesheet" href="../css/styles.css" />
  </head>
  <body>
    <header>
      <div>
        <h1>QueueStand Admin</h1>
        <nav><ul>
          <li><a href="admin-dashboard.php">Dashboard</a></li>
          <li><a href="admin-users.php">Users</a></li>
          <li><a href="../logout.php">Logout</a></li>
        </ul></nav>
      </div>
    </header>

    <main>
      <h2>Dashboard</h2>
      <div class="grid">
        <div class="card"><h3><?= $totalUsers ?></h3><p>Total Users</p></div>
        <div class="card"><h3><?= $totalJobs ?></h3><p>Total Jobs</p></div>
        <div class="card"><h3><?= $openJobs ?></h3><p>Open Jobs</p></div>
      </div>

      <h2>Recent Jobs</h2>
      <?php foreach ($recentJobs as $job): ?>
        <div class="card">
          <h3><?= htmlspecialchars($job['title']) ?></h3>
          <p>Posted by: <?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?></p>
          <p>Location: <?= htmlspecialchars($job['location']) ?></p>
          <p>Pay: R <?= number_format($job['pay_amount'], 2) ?> | Status: <strong><?= $job['status'] ?></strong></p>
        </div>
      <?php endforeach; ?>
    </main>
  </body>
</html>
