<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<header>
  <div>
    <h1><a href="<?= BASE_URL ?>/index.php"><img src="<?= BASE_URL ?>/assets/Logo.png" alt="QueueStand"></a></h1>
    <div class="nav-right">
      <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" onclick="toggleNav(this)">
        <span class="nav-toggle-icon">&#9776;</span>
        <span class="nav-toggle-label">Menu</span>
      </button>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="<?= BASE_URL ?>/dashboard.php" class="nav-notif-btn" id="nav-notif-btn" title="Notifications">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <span class="notif-badge" id="nav-notif-badge" style="display:none"></span>
        </a>
      <?php endif; ?>
    </div>
    <nav id="main-nav">
      <ul>
        <li><a href="<?= BASE_URL ?>/about.php">About Us</a></li>
        <li><a href="<?= BASE_URL ?>/how-it-works.php">How It Works</a></li>
        <li><a href="<?= BASE_URL ?>/browse-jobs.php">Find Standers</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <li><a href="<?= BASE_URL ?>/post-job.php">Post A Job</a></li>
          <li><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
          <li><a href="<?= BASE_URL ?>/profile.php"><?= htmlspecialchars($_SESSION['first_name']) ?></a></li>
          <li><a href="<?= BASE_URL ?>/logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="<?= BASE_URL ?>/post-job.php">Post A Job</a></li>
          <li><a href="<?= BASE_URL ?>/login.php">Login</a></li>
          <li><a href="<?= BASE_URL ?>/register.php">Sign Up</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>
<?php if (isset($_SESSION['user_id'])): ?>
<script>
(function () {
  let lastUnread = 0;
  const badge = document.getElementById('nav-notif-badge');
  if (!badge) return;
  function poll() {
    fetch('<?= BASE_URL ?>/notifications-poll.php')
      .then(r => r.json())
      .then(data => {
        if (data.unread > 0) {
          badge.textContent = data.unread;
          badge.style.display = '';
        } else {
          badge.style.display = 'none';
        }
        lastUnread = data.unread;
      }).catch(() => {});
  }
  poll();
  setInterval(poll, 10000);
})();
</script>
<?php endif; ?>
