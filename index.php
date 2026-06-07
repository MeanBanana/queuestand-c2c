<!-- Updated 2026 Test -->
<?php
require_once 'includes/auth.php';
guardRoute('open');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>QueueStand - Pay Someone to Stand in Queue | South Africa</title>
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/components.css" />
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <main>
    <section class="hero">
      <h1>Don't Waste Time In Queues</h1>
      <p>Hire trusted people to stand in line for you — Home Affairs, Banks, Clinics and more</p>
      <p>
        <a href="post-job.php">Post a Queue Job</a>
        <a href="browse-jobs.php">Become a Stander</a>
      </p>
    </section>

    <section>
      <h2>Why QueueStand</h2>
      <div class="grid">
        <div class="card"><h3>Save Hours</h3><p>Let someone stand for you while you work or relax</p></div>
        <div class="card"><h3>Secure &amp; Verified</h3><p>ID-verified standers and secure payments</p></div>
        <div class="card"><h3>Support Locals</h3><p>Create income opportunities in communities</p></div>
      </div>
    </section>
  </main>

  <script src="js/footer.js"></script>
</body>
</html>
