<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'user') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$job_id = (int) ($_GET['job_id'] ?? 0);
if ($job_id) {
    $jobStmt = $pdo->prepare("SELECT pay_amount, assigned_stander_id, poster_id, title FROM jobs WHERE job_id = ?");
    $jobStmt->execute([$job_id]);
    $job = $jobStmt->fetch();

    if ($job) {
        $pdo->prepare("UPDATE jobs SET status = 'in_progress' WHERE job_id = ? AND status = 'assigned'")
            ->execute([$job_id]);

        $exists = $pdo->prepare("SELECT 1 FROM transactions WHERE job_id = ? AND status = 'paid'");
        $exists->execute([$job_id]);
        if (!$exists->fetch()) {
            $pdo->prepare("INSERT INTO transactions (job_id, amount, status, payment_gateway) VALUES (?, ?, 'paid', 'PayFast')")
                ->execute([$job_id, $job['pay_amount']]);

            if ($job['assigned_stander_id']) {
                $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                    ->execute([$job['assigned_stander_id'], "Payment received for job '{$job['title']}'. The job is now in progress."]);
            }
            $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
                ->execute([$job['poster_id'], "Payment confirmed for job '{$job['title']}'. Job is now in progress."]);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment Successful - QueueStand</title>
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/components.css" />
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <main class="auth-main">
    <h2>Payment Successful</h2>
    <p class="msg-success">Your payment for job #<?= $job_id ?> has been received. The job is now in progress.</p>
    <a href="dashboard.php" class="btn-primary">Back to Dashboard</a>
  </main>

  <script src="js/footer.js"></script>
</body>
</html>
