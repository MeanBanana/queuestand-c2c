<?php
require_once 'includes/auth.php';
requireLogin();
$job_id = (int)($_GET['job_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Cancelled</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <h1>Payment Cancelled</h1>
    <p>Your payment for job #<?= $job_id ?> was cancelled. No charge was made.</p>
    <a href="checkout.php?job_id=<?= $job_id ?>">Try Again</a> |
    <a href="dashboard.php">Back to Dashboard</a>
</body>
</html>
