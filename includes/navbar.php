<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<header>
  <div>
    <h1><a href="/ITECA_SumativeAssessment/index.php"><img src="/ITECA_SumativeAssessment/assets/Logo.png" alt="QueueStand"></a></h1>
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" onclick="toggleNav(this)">
      <span class="nav-toggle-icon">&#9776;</span>
      <span class="nav-toggle-label">Menu</span>
    </button>
    <nav id="main-nav">
      <ul>
        <li><a href="/ITECA_SumativeAssessment/about.php">About Us</a></li>
        <li><a href="/ITECA_SumativeAssessment/how-it-works.php">How It Works</a></li>
        <li><a href="/ITECA_SumativeAssessment/browse-jobs.php">Find Standers</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <li><a href="/ITECA_SumativeAssessment/post-job.php">Post A Job</a></li>
          <li><a href="/ITECA_SumativeAssessment/dashboard.php">Dashboard</a></li>
          <li><a href="/ITECA_SumativeAssessment/profile.php"><?= htmlspecialchars($_SESSION['first_name']) ?></a></li>
          <li><a href="/ITECA_SumativeAssessment/logout.php">Logout</a></li>
          <li><a href="/ITECA_SumativeAssessment/checkout.php">Checkout</a></li>
        <?php else: ?>
          <li><a href="/ITECA_SumativeAssessment/post-job.php">Post A Job</a></li>
          <li><a href="/ITECA_SumativeAssessment/login.php">Login</a></li>
          <li><a href="/ITECA_SumativeAssessment/register.php">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
