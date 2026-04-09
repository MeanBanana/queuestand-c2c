<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

// Handle apply action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $job_id = (int)($_POST['job_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE jobs SET status='assigned', assigned_stander_id=? WHERE job_id=? AND status='open'");
    $stmt->execute([currentUser()['id'], $job_id]);
    header('Location: browse-jobs.php');
    exit;
}

$jobs = $pdo->query("SELECT j.*, u.first_name, u.last_name FROM jobs j JOIN users u ON j.poster_id = u.user_id WHERE j.status='open' ORDER BY j.required_datetime ASC")->fetchAll();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Available Queue Jobs | QueueStand</title>
    <link rel="stylesheet" href="css/styles.css" />
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>

    <main>
      <h1>Available Queue Jobs</h1>

      <?php if (empty($jobs)): ?>
        <p>No open jobs at the moment. Check back soon!</p>
      <?php endif; ?>

      <?php foreach ($jobs as $job): ?>
        <div class="card">
          <h3><?= htmlspecialchars($job['title']) ?></h3>
          <p>Date: <?= htmlspecialchars($job['required_datetime']) ?></p>
          <p>Location: <?= htmlspecialchars($job['location']) ?></p>
          <p>Offered: R <?= number_format($job['pay_amount'], 2) ?></p>
          <?php if ($job['description']): ?>
            <p><?= htmlspecialchars($job['description']) ?></p>
          <?php endif; ?>
          <?php if (isLoggedIn() && $_SESSION['role'] === 'queue_stander'): ?>
            <form method="POST">
              <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>" />
              <button type="submit">Apply to Stand</button>
            </form>
          <?php elseif (!isLoggedIn()): ?>
            <a href="login.php">Login to Apply</a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </main>

    <script src="js/footer.js"></script>
  </body>
</html>
