<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
guardRoute('admin');

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
$totalJobs = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
$openJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status='open'")->fetchColumn();
$completedJobs = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status='completed'")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE status IN ('paid','released')")->fetchColumn();

$recentJobs = $pdo->query("
    SELECT j.job_id, j.title, j.status, j.pay_amount, j.created_at,
           u.first_name, u.last_name
    FROM jobs j
    JOIN users u ON j.poster_id = u.user_id
    ORDER BY j.created_at DESC LIMIT 8
")->fetchAll();

$recentUsers = $pdo->query("
    SELECT user_id, first_name, last_name, email, role, is_verified, created_at
    FROM users ORDER BY created_at DESC LIMIT 8
")->fetchAll();

$statusBadge = [
  'open' => 'badge-open',
  'assigned' => 'badge-assigned',
  'in_progress' => 'badge-progress',
  'completed' => 'badge-completed',
  'cancelled' => 'badge-cancelled',
];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard | QueueStand</title>
  <link rel="stylesheet" href="../css/styles.css" />
  <link rel="stylesheet" href="../css/dashboard.css" />
  <link rel="stylesheet" href="../css/components.css" />
  <link rel="stylesheet" href="../css/admin.css" />
</head>

<body>
  <?php include 'admin-navbar.php'; ?>

  <main>
    <div class="dash-header">
      <div>
        <h1>Admin Dashboard</h1>
        <p class="dash-role">Welcome, <?= htmlspecialchars($_SESSION['first_name']) ?></p>
      </div>
    </div>

    <div class="dash-stats admin-stats-grid">
      <div class="stat-card"><span class="stat-number"><?= $totalUsers ?></span><span class="stat-label">Users</span>
      </div>
      <div class="stat-card"><span class="stat-number"><?= $totalJobs ?></span><span class="stat-label">Total
          Jobs</span></div>
      <div class="stat-card"><span class="stat-number"><?= $openJobs ?></span><span class="stat-label">Open Jobs</span>
      </div>
      <div class="stat-card"><span class="stat-number"><?= $completedJobs ?></span><span
          class="stat-label">Completed</span></div>
      <div class="stat-card"><span class="stat-number"><?= $totalAdmins ?></span><span class="stat-label">Admins</span>
      </div>
      <div class="stat-card"><span class="stat-number">R <?= number_format($totalRevenue, 2) ?></span><span
          class="stat-label">Revenue Paid</span></div>
    </div>

    <div class="admin-section">
      <div class="admin-section-title">
        Recent Jobs
        <a href="admin-jobs.php">View all →</a>
      </div>
      <div class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Posted By</th>
            <th>Pay</th>
            <th>Status</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentJobs as $j): ?>
            <tr>
              <td><?= htmlspecialchars($j['title']) ?></td>
              <td><?= htmlspecialchars($j['first_name'] . ' ' . $j['last_name']) ?></td>
              <td>R <?= number_format($j['pay_amount'], 2) ?></td>
              <td><span
                  class="badge <?= $statusBadge[$j['status']] ?? 'badge-open' ?>"><?= ucfirst(str_replace('_', ' ', $j['status'])) ?></span>
              </td>
              <td><?= date('d M Y', strtotime($j['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

    <div class="admin-section">
      <div class="admin-section-title">
        Recent Users
        <a href="admin-users.php">View all →</a>
      </div>
      <div class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Verified</th>
            <th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentUsers as $u): ?>
            <tr>
              <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><span
                  class="badge <?= $u['role'] === 'admin' ? 'badge-progress' : 'badge-open' ?>"><?= ucfirst($u['role']) ?></span>
              </td>
              <td>
                <?= $u['is_verified'] ? '<span class="badge badge-completed">Yes</span>' : '<span class="badge badge-cancelled">No</span>' ?>
              </td>
              <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </main>

  <script src="../js/footer.js"></script>
</body>

</html>