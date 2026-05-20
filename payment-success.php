<?php
require_once 'includes/auth.php';
requireLogin();
$job_id = (int)($_GET['job_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <h1>Payment Successful</h1>
    <p>Your payment for job #<?= $job_id ?> has been received. The job is now in progress.</p>
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
