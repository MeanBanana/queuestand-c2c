<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
guardRoute('user');

$job_id = (int) ($_GET['job_id'] ?? 0);
if ($job_id) {
    // Ensure job is moved to in_progress (fallback in case IPN hasn't fired yet)
    $pdo->prepare("UPDATE jobs SET status = 'in_progress' WHERE job_id = ? AND status = 'assigned'")
        ->execute([$job_id]);
    // Insert transaction if not already recorded
    $exists = $pdo->prepare("SELECT 1 FROM transactions WHERE job_id = ? AND status = 'paid'");
    $exists->execute([$job_id]);
    if (!$exists->fetch()) {
        $amount = $pdo->prepare("SELECT pay_amount FROM jobs WHERE job_id = ?");
        $amount->execute([$job_id]);
        $pay = $amount->fetchColumn();
        $pdo->prepare("INSERT INTO transactions (job_id, amount, status, payment_gateway) VALUES (?, ?, 'paid', 'PayFast')")
            ->execute([$job_id, $pay]);
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
