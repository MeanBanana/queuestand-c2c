<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
guardRoute('user');

$job_id = (int) ($_GET['job_id'] ?? 0);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Payment Cancelled - QueueStand</title>
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/components.css" />
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <main class="auth-main">
    <h2>Payment Cancelled</h2>
    <p class="msg-error">Your payment for job #<?= $job_id ?> was cancelled. No charge was made.</p>
    <div class="page-actions">
      <a href="checkout.php?job_id=<?= $job_id ?>" class="btn-primary">Try Again</a>
      <a href="dashboard.php" class="btn-primary">Back to Dashboard</a>
    </div>
  </main>

  <script src="js/footer.js"></script>
</body>
</html>
