<?php
if (session_status() === PHP_SESSION_NONE) session_start();
define('BASE_URL', 'https://queue-stand.infinityfree.me');
if (!defined('BASE_URL')) define('BASE_URL', '');
?>
<header>
  <div>
    <h1><a href="<?= BASE_URL ?>/index.php"><img src="<?= BASE_URL ?>/assets/Logo.png" alt="QueueStand"></a></h1>
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" onclick="toggleNav(this)">
      <span class="nav-toggle-icon">&#9776;</span>
      <span class="nav-toggle-label">Menu</span>
    </button>
    <nav id="main-nav">
      <ul>
        <li><a href="<?= BASE_URL ?>/about.php">About Us</a></li>
        <li><a href="<?= BASE_URL ?>/how-it-works.php">How It Works</a></li>
        <li><a href="<?= BASE_URL ?>/browse-jobs.php">Find Standers</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <li><a href="<?= BASE_URL ?>/post-job.php">Post A Job</a></li>
          <li><a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
          <li>
            <a href="<?= BASE_URL ?>/dashboard.php" class="nav-notif-link" id="nav-notif-btn" title="Notifications">
              🔔<span class="notif-badge" id="nav-notif-badge" style="display:none"></span>
            </a>
          </li>
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
