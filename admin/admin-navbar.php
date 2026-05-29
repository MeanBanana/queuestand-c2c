<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<header class="admin-nav">
  <div>
    <h1><a href="/ITECA_SumativeAssessment/admin/admin-dashboard.php" class="admin-brand">⚙ QueueStand Admin</a></h1>
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" onclick="toggleNav(this)">
      <span class="nav-toggle-icon">&#9776;</span>
      <span class="nav-toggle-label">Menu</span>
    </button>
    <nav id="main-nav">
      <ul>
        <li><a href="/ITECA_SumativeAssessment/admin/admin-dashboard.php">Dashboard</a></li>
        <li><a href="/ITECA_SumativeAssessment/admin/admin-users.php">Users</a></li>
        <li><a href="/ITECA_SumativeAssessment/admin/admin-jobs.php">Jobs</a></li>
        <li><a href="/ITECA_SumativeAssessment/index.php">← Site</a></li>
        <li><a href="/ITECA_SumativeAssessment/logout.php">Logout</a></li>
      </ul>
    </nav>
  </div>
</header>
