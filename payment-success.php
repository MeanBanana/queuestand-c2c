<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$job_id = (int) ($_GET['job_id'] ?? 0);
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
