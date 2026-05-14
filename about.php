<?php require_once 'includes/auth.php'; ?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>About Us - QueueStand</title>
  <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
  <?php include 'includes/navbar.php'; ?>

  <main>
    <div class="about-hero">
      <h1>About QueueStand</h1>
      <p>South Africa's trusted marketplace connecting busy people with reliable standers. We turn hours lost in queues
        into time saved — and income earned.</p>
    </div>

    <div class="stats-bar">
      <div class="stat"><strong>10,000+</strong><span>Hours Saved</span></div>
      <div class="stat"><strong>2,500+</strong><span>Verified Standers</span></div>
      <div class="stat"><strong>9 Provinces</strong><span>Across South Africa</span></div>
    </div>

    <div class="about-grid">
      <div class="card">
        <h2>Our Mission</h2>
        <p>No one should lose a full day just to renew an ID or collect a grant. We formalise informal queue-standing
          into a safe, transparent marketplace — helping professionals reclaim their time while giving gig workers
          dignified, consistent income.</p>
      </div>
      <div class="card">
        <h2>The Problem We Solve</h2>
        <p>14-hour waits at Home Affairs. Overnight lines at SASSA. Hours lost at clinics and banks. These queues steal
          productivity and cost real money. Informal arrangements lead to disputes and safety risks. We fix that with
          technology.</p>
      </div>
      <div class="card">
        <h2>What Makes Us Different</h2>
        <ul>
          <li><strong>Trust First:</strong> Every stander is ID-verified.</li>
          <li><strong>Secure Payments:</strong> Funds held in escrow until the job is done.</li>
          <li><strong>Local &amp; Inclusive:</strong> Built for South Africa, supporting local payment methods.</li>
          <li><strong>Win-Win:</strong> You save hours. Standers earn steady income.</li>
        </ul>
      </div>
      <div class="card">
        <h2>Who We Serve</h2>
        <p>Busy professionals, small business owners, and parents who can't afford to lose a day — and hardworking
          youth, side-hustlers, and community members looking for flexible, dignified work opportunities.</p>
      </div>
    </div>

    <div class="about-cta">
      <h2>Ready to Get Started?</h2>
      <p>Join thousands of South Africans saving time and earning income — one queue at a time.</p>
      <a href="post-job.php">Post a Queue Job</a>
      <a href="browse-jobs.php">Become a Stander</a>
    </div>
  </main>

  <script src="js/footer.js"></script>
</body>

</html>