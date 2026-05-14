<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$toast = '';

// Handle apply action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && $_SESSION['role'] === 'queue_stander') {
  $job_id = (int) ($_POST['job_id'] ?? 0);
  $stander_id = currentUser()['id'];

  // Check not already applied
  $check = $pdo->prepare("SELECT 1 FROM job_applications WHERE job_id=? AND stander_id=?");
  $check->execute([$job_id, $stander_id]);

  if (!$check->fetch()) {
    $pdo->prepare("INSERT INTO job_applications (job_id, stander_id) VALUES (?,?)")
      ->execute([$job_id, $stander_id]);

    // Notify the poster
    $poster = $pdo->prepare("SELECT poster_id, title FROM jobs WHERE job_id=?");
    $poster->execute([$job_id]);
    $jobRow = $poster->fetch();

    $name = currentUser()['first_name'] . ' ' . currentUser()['last_name'];
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
      ->execute([$jobRow['poster_id'], "{$name} has applied to stand for your job: \"{$jobRow['title']}\""]);

    header('Location: browse-jobs.php?toast=applied');
    exit;
  }
  header('Location: browse-jobs.php?toast=already_applied');
  exit;
}

$toast = $_GET['toast'] ?? '';

// Get applied job IDs for current stander to disable buttons
$appliedJobIds = [];
if (isLoggedIn() && $_SESSION['role'] === 'queue_stander') {
  $rows = $pdo->prepare("SELECT job_id FROM job_applications WHERE stander_id=?");
  $rows->execute([currentUser()['id']]);
  $appliedJobIds = array_column($rows->fetchAll(), 'job_id');
}

$jobs = $pdo->query("
    SELECT j.*, u.first_name, u.last_name
    FROM jobs j
    JOIN users u ON j.poster_id = u.user_id
    WHERE j.status = 'open'
    ORDER BY j.required_datetime ASC
")->fetchAll();

$toastMessages = [
  'applied' => ['msg' => 'Application sent! The poster will review it.', 'type' => 'toast-success'],
  'already_applied' => ['msg' => 'You already applied for this job.', 'type' => 'toast-warning'],
];
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

  <?php if ($toast && isset($toastMessages[$toast])): ?>
    <div id="toast" class="toast <?= $toastMessages[$toast]['type'] ?>">
      <?= $toastMessages[$toast]['msg'] ?>
    </div>
  <?php endif; ?>

  <main>
    <h1>Available Queue Jobs</h1>

    <?php if (empty($jobs)): ?>
      <p>No open jobs at the moment. Check back soon!</p>
    <?php endif; ?>

    <?php foreach ($jobs as $job): ?>
      <div class="card">
        <h3><?= htmlspecialchars($job['title']) ?></h3>
        <p><?= htmlspecialchars($job['location']) ?></p>
        <p>🗓 <?= date('d M Y, H:i', strtotime($job['required_datetime'])) ?></p>
        <p>R <?= number_format($job['pay_amount'], 2) ?></p>
        <p>Posted by: <?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?></p>
        <?php if ($job['description']): ?>
          <p><?= htmlspecialchars($job['description']) ?></p>
        <?php endif; ?>

        <?php if (isLoggedIn() && $_SESSION['role'] === 'queue_stander'): ?>
          <?php $alreadyApplied = in_array($job['job_id'], $appliedJobIds); ?>
          <form method="POST">
            <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>" />
            <button type="submit" <?= $alreadyApplied ? 'disabled class="btn-applied"' : '' ?>>
              <?= $alreadyApplied ? 'Applied' : 'Apply to Stand' ?>
            </button>
          </form>
        <?php elseif (!isLoggedIn()): ?>
          <a href="login.php">Login to Apply</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </main>

  <script src="js/footer.js"></script>
  <script>
    const toast = document.getElementById('toast');
    if (toast) setTimeout(() => toast.classList.add('toast-hide'), 3500);
  </script>
</body>

</html>