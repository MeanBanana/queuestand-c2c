<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$user = currentUser();
$isPoser = $user['role'] === 'job_poster';

if ($isPoser) {
    $jobs = $pdo->prepare("SELECT * FROM jobs WHERE poster_id = ? ORDER BY created_at DESC");
} else {
    $jobs = $pdo->prepare("SELECT * FROM jobs WHERE assigned_stander_id = ? ORDER BY created_at DESC");
}
$jobs->execute([$user['id']]);
$jobs = $jobs->fetchAll();

$counts = ['total' => count($jobs), 'open' => 0, 'in_progress' => 0, 'completed' => 0];
foreach ($jobs as $j) {
    if (isset($counts[$j['status']])) $counts[$j['status']]++;
}

$statusLabels = [
    'open'        => ['label' => 'Open',        'class' => 'badge-open'],
    'assigned'    => ['label' => 'Assigned',    'class' => 'badge-assigned'],
    'in_progress' => ['label' => 'In Progress', 'class' => 'badge-progress'],
    'completed'   => ['label' => 'Completed',   'class' => 'badge-completed'],
    'cancelled'   => ['label' => 'Cancelled',   'class' => 'badge-cancelled'],
];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard | QueueStand</title>
    <link rel="stylesheet" href="css/styles.css" />
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>

    <main>
      <div class="dash-header">
        <div>
          <h1>Welcome back, <?= htmlspecialchars($user['first_name']) ?> 👋</h1>
          <p class="dash-role"><?= $isPoser ? 'Job Poster' : 'Queue Stander' ?></p>
        </div>
        <?php if ($isPoser): ?>
          <a href="post-job.php" class="btn-primary">+ Post a New Job</a>
        <?php else: ?>
          <a href="browse-jobs.php" class="btn-primary">Browse Jobs</a>
        <?php endif; ?>
      </div>

      <div class="dash-stats">
        <div class="stat-card">
          <span class="stat-number"><?= $counts['total'] ?></span>
          <span class="stat-label">Total Jobs</span>
        </div>
        <div class="stat-card">
          <span class="stat-number"><?= $counts['open'] ?></span>
          <span class="stat-label">Open</span>
        </div>
        <div class="stat-card">
          <span class="stat-number"><?= $counts['in_progress'] ?></span>
          <span class="stat-label">In Progress</span>
        </div>
        <div class="stat-card">
          <span class="stat-number"><?= $counts['completed'] ?></span>
          <span class="stat-label">Completed</span>
        </div>
      </div>

      <h2 class="dash-section-title"><?= $isPoser ? 'Your Posted Jobs' : 'Your Assigned Jobs' ?></h2>

      <?php if (empty($jobs)): ?>
        <div class="dash-empty">
          <p><?= $isPoser ? 'You haven\'t posted any jobs yet.' : 'You have no assigned jobs yet.' ?></p>
          <a href="<?= $isPoser ? 'post-job.php' : 'browse-jobs.php' ?>" class="btn-primary">
            <?= $isPoser ? 'Post Your First Job' : 'Find a Job' ?>
          </a>
        </div>
      <?php else: ?>
        <div class="dash-grid">
          <?php foreach ($jobs as $job):
            $badge = $statusLabels[$job['status']] ?? ['label' => $job['status'], 'class' => 'badge-open'];
          ?>
            <div class="dash-card">
              <div class="dash-card-top">
                <h3><?= htmlspecialchars($job['title']) ?></h3>
                <span class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
              </div>
              <p>📍 <?= htmlspecialchars($job['location']) ?></p>
              <p>🗓 <?= date('d M Y, H:i', strtotime($job['required_datetime'])) ?></p>
              <p class="dash-pay">R <?= number_format($job['pay_amount'], 2) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>

    <script src="js/footer.js"></script>
  </body>
</html>
