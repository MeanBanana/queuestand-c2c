<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$user = currentUser();
$isPoster = $user['role'] === 'job_poster';

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $jid = (int) ($_POST['job_id'] ?? 0);

  // POSTER: Accept applicant
  if (isset($_POST['accept_applicant']) && $isPoster) {
    $sid = $_POST['stander_id'];
    $pdo->prepare("UPDATE jobs SET status='assigned', assigned_stander_id=? WHERE job_id=? AND poster_id=?")
      ->execute([$sid, $jid, $user['id']]);
    // Mark this application accepted, decline all others
    $pdo->prepare("UPDATE job_applications SET status='accepted' WHERE job_id=? AND stander_id=?")
      ->execute([$jid, $sid]);
    $pdo->prepare("UPDATE job_applications SET status='declined' WHERE job_id=? AND stander_id!=?")
      ->execute([$jid, $sid]);
    // Notify accepted stander
    $jobTitle = $pdo->prepare("SELECT title FROM jobs WHERE job_id=?");
    $jobTitle->execute([$jid]);
    $title = $jobTitle->fetchColumn();
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
      ->execute([$sid, "Your application for \"{$title}\" was accepted! Get ready to stand."]);
    // Notify declined standers
    $declined = $pdo->prepare("SELECT stander_id FROM job_applications WHERE job_id=? AND stander_id!=?");
    $declined->execute([$jid, $sid]);
    foreach ($declined->fetchAll() as $row) {
      $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
        ->execute([$row['stander_id'], "Your application for \"{$title}\" was not selected this time."]);
    }
    header('Location: dashboard.php?toast=accepted');
    exit;
  }

  // POSTER: Decline a single applicant
  if (isset($_POST['decline_applicant']) && $isPoster) {
    $sid = $_POST['stander_id'];
    $pdo->prepare("UPDATE job_applications SET status='declined' WHERE job_id=? AND stander_id=?")
      ->execute([$jid, $sid]);
    $jobTitle = $pdo->prepare("SELECT title FROM jobs WHERE job_id=?");
    $jobTitle->execute([$jid]);
    $title = $jobTitle->fetchColumn();
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
      ->execute([$sid, "Your application for \"{$title}\" was not selected this time."]);
    header('Location: dashboard.php?toast=declined');
    exit;
  }

  // POSTER: Advance job status
  if (isset($_POST['advance_status']) && $isPoster) {
    $transitions = ['assigned' => 'in_progress', 'in_progress' => 'completed'];
    $current = $_POST['current_status'] ?? '';
    if (isset($transitions[$current])) {
      $next = $transitions[$current];
      $pdo->prepare("UPDATE jobs SET status=? WHERE job_id=? AND poster_id=?")
        ->execute([$next, $jid, $user['id']]);
      // Notify stander of status change
      $row = $pdo->prepare("SELECT assigned_stander_id, title FROM jobs WHERE job_id=?");
      $row->execute([$jid]);
      $jobRow = $row->fetch();
      $msgs = [
        'in_progress' => "The poster has marked your job \"{$jobRow['title']}\" as In Progress.",
        'completed' => "Your job \"{$jobRow['title']}\" has been marked as Completed. Payment will follow.",
      ];
      if ($jobRow['assigned_stander_id']) {
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
          ->execute([$jobRow['assigned_stander_id'], $msgs[$next]]);
      }
    }
    header('Location: dashboard.php?toast=status_updated');
    exit;
  }

  // POSTER: Cancel job
  if (isset($_POST['cancel_job']) && $isPoster) {
    $pdo->prepare("UPDATE jobs SET status='cancelled' WHERE job_id=? AND poster_id=? AND status IN ('open','assigned')")
      ->execute([$jid, $user['id']]);
    // Notify assigned stander if any
    $row = $pdo->prepare("SELECT assigned_stander_id, title FROM jobs WHERE job_id=?");
    $row->execute([$jid]);
    $jobRow = $row->fetch();
    if ($jobRow['assigned_stander_id']) {
      $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
        ->execute([$jobRow['assigned_stander_id'], "The job \"{$jobRow['title']}\" has been cancelled by the poster."]);
    }
    header('Location: dashboard.php?toast=job_cancelled');
    exit;
  }
}

// MARK NOTIFICATIONS READ 
if (isset($_GET['mark_read'])) {
  $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);
  header('Location: dashboard.php');
  exit;
}

$toast = $_GET['toast'] ?? '';

// FETCH JOBS
if ($isPoster) {
  $stmt = $pdo->prepare("
        SELECT j.*,
               s.first_name AS s_first, s.last_name AS s_last,
               s.phone AS s_phone, s.email AS s_email, s.city AS s_city,
               ROUND(AVG(r.rating),1) AS avg_rating, COUNT(DISTINCT r.review_id) AS review_count
        FROM jobs j
        LEFT JOIN users s ON j.assigned_stander_id = s.user_id
        LEFT JOIN reviews r ON r.rated_id = s.user_id
        WHERE j.poster_id = ?
        GROUP BY j.job_id
        ORDER BY j.created_at DESC
    ");
} else {
  $stmt = $pdo->prepare("
        SELECT j.*, ja.status AS app_status,
               p.first_name AS p_first, p.last_name AS p_last
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.job_id
        JOIN users p ON j.poster_id = p.user_id
        WHERE ja.stander_id = ?
        ORDER BY ja.applied_at DESC
    ");
}
$stmt->execute([$user['id']]);
$jobs = $stmt->fetchAll();

// Fetch applicants per job for poster
$applicantsByJob = [];
if ($isPoster) {
  $appStmt = $pdo->prepare("
        SELECT ja.*, ja.job_id,
               u.first_name, u.last_name, u.email, u.phone, u.city,
               ROUND(AVG(r.rating),1) AS avg_rating, COUNT(r.review_id) AS review_count
        FROM job_applications ja
        JOIN users u ON ja.stander_id = u.user_id
        LEFT JOIN reviews r ON r.rated_id = u.user_id
        WHERE ja.job_id IN (SELECT job_id FROM jobs WHERE poster_id=?)
        GROUP BY ja.application_id
        ORDER BY ja.applied_at ASC
    ");
  $appStmt->execute([$user['id']]);
  foreach ($appStmt->fetchAll() as $app) {
    $applicantsByJob[$app['job_id']][] = $app;
  }
}

// Fetch unread notifications
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$notifStmt->execute([$user['id']]);
$notifications = $notifStmt->fetchAll();
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

$counts = ['total' => count($jobs), 'open' => 0, 'in_progress' => 0, 'completed' => 0];
foreach ($jobs as $j) {
  if (isset($counts[$j['status']]))
    $counts[$j['status']]++;
}

$statusLabels = [
  'open' => ['label' => 'Open', 'class' => 'badge-open'],
  'assigned' => ['label' => 'Assigned', 'class' => 'badge-assigned'],
  'in_progress' => ['label' => 'In Progress', 'class' => 'badge-progress'],
  'completed' => ['label' => 'Completed', 'class' => 'badge-completed'],
  'cancelled' => ['label' => 'Cancelled', 'class' => 'badge-cancelled'],
];

$appStatusLabels = [
  'pending' => ['label' => 'Pending', 'class' => 'badge-assigned'],
  'accepted' => ['label' => 'Accepted', 'class' => 'badge-completed'],
  'declined' => ['label' => 'Declined', 'class' => 'badge-cancelled'],
];

$toastMessages = [
  'job_posted' => ['msg' => 'Job posted successfully!', 'type' => 'toast-success'],
  'job_cancelled' => ['msg' => 'Job cancelled.', 'type' => 'toast-warning'],
  'accepted' => ['msg' => 'Stander accepted! They\'ve been notified.', 'type' => 'toast-success'],
  'declined' => ['msg' => 'Applicant declined.', 'type' => 'toast-warning'],
  'status_updated' => ['msg' => 'Job status updated.', 'type' => 'toast-success'],
];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | QueueStand</title>
  <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
  <?php include 'includes/navbar.php'; ?>

  <?php if ($toast && isset($toastMessages[$toast])): ?>
    <div id="toast" class="toast <?= $toastMessages[$toast]['type'] ?>">
      <?= $toastMessages[$toast]['msg'] ?>
    </div>
  <?php endif; ?>

  <!-- Reviews Modal -->
  <div id="reviews-modal" class="modal-overlay" style="display:none">
    <div class="modal-box">
      <button class="modal-close" onclick="closeModal('reviews-modal')">✕</button>
      <h3 id="modal-title">Reviews</h3>
      <div id="modal-body">
        <p>Loading…</p>
      </div>
    </div>
  </div>

  <!-- Notifications Modal -->
  <div id="notif-modal" class="modal-overlay" style="display:none">
    <div class="modal-box">
      <button class="modal-close" onclick="closeModal('notif-modal')">✕</button>
      <h3>Notifications</h3>
      <div id="notif-body">
        <?php if (empty($notifications)): ?>
          <p>No notifications yet.</p>
        <?php else: ?>
          <?php foreach ($notifications as $n): ?>
            <div class="notif-item <?= $n['is_read'] ? '' : 'notif-unread' ?>">
              <p><?= htmlspecialchars($n['message']) ?></p>
              <span class="notif-time"><?= date('d M Y, H:i', strtotime($n['created_at'])) ?></span>
            </div>
          <?php endforeach; ?>
          <?php if ($unreadCount > 0): ?>
            <a href="dashboard.php?mark_read=1" class="btn-mark-read">Mark all as read</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <main>
    <div class="dash-header">
      <div>
        <h1>Welcome back, <?= htmlspecialchars($user['first_name']) ?> </h1>
        <p class="dash-role"><?= $isPoster ? 'Job Poster' : 'Queue Stander' ?></p>
      </div>
      <div class="dash-header-actions">
        <button class="btn-notif" onclick="openModal('notif-modal')">
          <?php if ($unreadCount > 0): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?>
        </button>
        <?php if ($isPoster): ?>
          <a href="post-job.php" class="btn-primary">+ Post a New Job</a>
        <?php else: ?>
          <a href="browse-jobs.php" class="btn-primary">Browse Jobs</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="dash-stats">
      <div class="stat-card"><span class="stat-number"><?= $counts['total'] ?></span><span
          class="stat-label">Total</span></div>
      <div class="stat-card"><span class="stat-number"><?= $counts['open'] ?></span><span class="stat-label">Open</span>
      </div>
      <div class="stat-card"><span class="stat-number"><?= $counts['in_progress'] ?></span><span class="stat-label">In
          Progress</span></div>
      <div class="stat-card"><span class="stat-number"><?= $counts['completed'] ?></span><span
          class="stat-label">Completed</span></div>
    </div>

    <h2 class="dash-section-title"><?= $isPoster ? 'Your Posted Jobs' : 'Your Applications' ?></h2>

    <?php if (empty($jobs)): ?>
      <div class="dash-empty">
        <p><?= $isPoster ? "You haven't posted any jobs yet." : 'You have no applications yet.' ?></p>
        <a href="<?= $isPoster ? 'post-job.php' : 'browse-jobs.php' ?>" class="btn-primary">
          <?= $isPoster ? 'Post Your First Job' : 'Find a Job' ?>
        </a>
      </div>
    <?php else: ?>
      <div class="dash-grid">
        <?php foreach ($jobs as $job):
          $badge = $statusLabels[$job['status']] ?? ['label' => $job['status'], 'class' => 'badge-open'];
          $canCancel = $isPoster && in_array($job['status'], ['open', 'assigned']);
          $hasStander = $isPoster && !empty($job['assigned_stander_id']);
          $applicants = $applicantsByJob[$job['job_id']] ?? [];
          $pendingApplicants = array_filter($applicants, fn($a) => $a['status'] === 'pending');
          ?>
          <div class="dash-card" id="card-<?= $job['job_id'] ?>">
            <div class="dash-card-top">
              <h3><?= htmlspecialchars($job['title']) ?></h3>
              <span id="job-badge-<?= $job['job_id'] ?>" class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
            </div>
            <p><?= htmlspecialchars($job['location']) ?></p>
            <p><?= date('d M Y, H:i', strtotime($job['required_datetime'])) ?></p>
            <p class="dash-pay">R <?= number_format($job['pay_amount'], 2) ?></p>

            <?php if (!$isPoster):
              // STANDER VIEW — show their application status
              $appBadge = $appStatusLabels[$job['app_status']] ?? $appStatusLabels['pending'];
              ?>
              <div class="app-status-row">
                <span>Application: </span>
                <span id="app-badge-<?= $job['job_id'] ?>" class="badge <?= $appBadge['class'] ?>"
                  data-prev="<?= $job['app_status'] ?>"><?= $appBadge['label'] ?></span>
              </div>
              <?php if ($job['app_status'] === 'accepted'): ?>
                <div class="stander-info" style="margin-top:0.75rem">
                  <p class="stander-title">Job Details</p>
                  <p>Posted by: <?= htmlspecialchars($job['p_first'] . ' ' . $job['p_last']) ?></p>
                  <p>Status: <strong><?= $badge['label'] ?></strong></p>
                </div>
              <?php endif; ?>

            <?php else:
              // POSTER VIEW
              ?>
              <button class="btn-expand" onclick="toggleExpand(<?= $job['job_id'] ?>)">
                <span id="expand-label-<?= $job['job_id'] ?>">▼ Details</span>
              </button>

              <div id="expand-<?= $job['job_id'] ?>" class="card-expand" style="display:none">
                <?php if ($job['description']): ?>
                  <p class="expand-desc"><?= htmlspecialchars($job['description']) ?></p>
                <?php endif; ?>

                <!-- ASSIGNED STANDER INFO -->
                <?php if ($hasStander): ?>
                  <div class="stander-info">
                    <p class="stander-title">Assigned Stander</p>
                    <p><strong><?= htmlspecialchars($job['s_first'] . ' ' . $job['s_last']) ?></strong></p>
                    <p><?= htmlspecialchars($job['s_email']) ?></p>
                    <?php if ($job['s_phone']): ?>
                      <p><?= htmlspecialchars($job['s_phone']) ?></p><?php endif; ?>
                    <?php if ($job['s_city']): ?>
                      <p><?= htmlspecialchars($job['s_city']) ?></p><?php endif; ?>
                    <p>
                      <?= $job['avg_rating'] ? $job['avg_rating'] . '/5 (' . $job['review_count'] . ' reviews)' : 'No reviews yet' ?>
                    </p>
                    <button class="btn-reviews"
                      onclick="loadReviews(<?= $job['assigned_stander_id'] ?>, '<?= htmlspecialchars(addslashes($job['s_first'] . ' ' . $job['s_last'])) ?>')">
                      Read Reviews
                    </button>
                  </div>
                <?php endif; ?>

                <!-- PENDING APPLICANTS (only when job is open) -->
                <?php if ($job['status'] === 'open' && count($pendingApplicants) > 0): ?>
                  <div class="applicants-section">
                    <p id="pending-count-<?= $job['job_id'] ?>" class="applicants-title">👥 Applicants
                      (<?= count($pendingApplicants) ?>)</p>
                    <?php foreach ($pendingApplicants as $app): ?>
                      <div class="applicant-card">
                        <div class="applicant-info">
                          <strong><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></strong>
                          <span class="applicant-meta">
                            <?= $app['avg_rating'] ? '' . $app['avg_rating'] . '/5 (' . $app['review_count'] . ' reviews)' : 'No reviews' ?>
                          </span>
                          <span class="applicant-meta"><?= htmlspecialchars($app['email']) ?></span>
                          <?php if ($app['phone']): ?>
                            <span class="applicant-meta"><?= htmlspecialchars($app['phone']) ?></span>
                          <?php endif; ?>
                        </div>
                        <div class="applicant-actions">
                          <button class="btn-reviews" style="margin-right:0.4rem"
                            onclick="loadReviews(<?= $app['stander_id'] ?>, '<?= htmlspecialchars(addslashes($app['first_name'] . ' ' . $app['last_name'])) ?>')">
                            Reviews
                          </button>
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                            <input type="hidden" name="stander_id" value="<?= $app['stander_id'] ?>">
                            <button type="submit" name="accept_applicant" class="btn-accept">✓ Accept</button>
                          </form>
                          <form method="POST" style="display:inline">
                            <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                            <input type="hidden" name="stander_id" value="<?= $app['stander_id'] ?>">
                            <button type="submit" name="decline_applicant" class="btn-decline"
                              onclick="return confirm('Decline this applicant?')">✕ Decline</button>
                          </form>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php elseif ($job['status'] === 'open' && count($applicants) === 0): ?>
                  <p class="no-stander">No applicants yet.</p>
                <?php endif; ?>

                <!-- STATUS PROGRESSION -->
                <?php
                $nextStatus = ['assigned' => 'in_progress', 'in_progress' => 'completed'];
                $nextLabels = ['assigned' => 'Mark as In Progress', 'in_progress' => 'Mark as Completed'];
                ?>
                <?php if (isset($nextStatus[$job['status']])): ?>
                  <form method="POST" style="margin-top:0.75rem">
                    <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                    <input type="hidden" name="current_status" value="<?= $job['status'] ?>">
                    <button type="submit" name="advance_status" class="btn-advance">
                      <?= $nextLabels[$job['status']] ?>
                    </button>
                  </form>
                <?php endif; ?>

                <!-- CANCEL -->
                <?php if ($canCancel): ?>
                  <form method="POST" onsubmit="return confirm('Cancel this job? The stander will be notified.')">
                    <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                    <button type="submit" name="cancel_job" class="btn-cancel">Cancel Job</button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>

  <script src="js/footer.js"></script>
  <script>
    const toast = document.getElementById('toast');
    if (toast) setTimeout(() => toast.classList.add('toast-hide'), 3500);

    function toggleExpand(id) {
      const el = document.getElementById('expand-' + id);
      const lbl = document.getElementById('expand-label-' + id);
      const open = el.style.display === 'block';
      el.style.display = open ? 'none' : 'block';
      lbl.textContent = open ? '▼ Details' : '▲ Hide';
    }

    function openModal(id) { document.getElementById(id).style.display = 'flex'; }
    function closeModal(id) { document.getElementById(id).style.display = 'none'; }

    document.querySelectorAll('.modal-overlay').forEach(el => {
      el.addEventListener('click', e => { if (e.target === el) el.style.display = 'none'; });
    });

    function loadReviews(standerId, name) {
      document.getElementById('modal-title').textContent = name + ' — Reviews';
      document.getElementById('modal-body').innerHTML = '<p>Loading…</p>';
      openModal('reviews-modal');
      fetch('get-reviews.php?stander_id=' + standerId)
        .then(r => r.json())
        .then(data => {
          if (!data.length) {
            document.getElementById('modal-body').innerHTML = '<p>No reviews yet.</p>';
            return;
          }
          document.getElementById('modal-body').innerHTML = data.map(r => `
              <div class="review-item">
                <div class="review-stars">${'★'.repeat(r.rating)}${'☆'.repeat(5 - r.rating)}</div>
                <p class="review-comment">${r.comment || '<em>No comment</em>'}</p>
                <p class="review-meta">— ${r.rater_name} · ${r.created_at}</p>
              </div>
            `).join('');
        });
    }

    // Real-time polling
    const isPoster = <?= $isPoster ? 'true' : 'false' ?>;
    let lastUnread = <?= $unreadCount ?>;

    const statusLabels = {
      open: { label: 'Open', cls: 'badge-open' },
      assigned: { label: 'Assigned', cls: 'badge-assigned' },
      in_progress: { label: 'In Progress', cls: 'badge-progress' },
      completed: { label: 'Completed', cls: 'badge-completed' },
      cancelled: { label: 'Cancelled', cls: 'badge-cancelled' },
    };
    const appLabels = {
      pending: { label: 'Pending', cls: 'badge-assigned' },
      accepted: { label: 'Accepted', cls: 'badge-completed' },
      declined: { label: 'Declined', cls: 'badge-cancelled' },
    };

    function showLiveToast(msg, type) {
      const existing = document.getElementById('live-toast');
      if (existing) existing.remove();
      const t = document.createElement('div');
      t.id = 'live-toast';
      t.className = 'toast ' + type;
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(() => t.classList.add('toast-hide'), 4000);
    }

    function pollNotifications() {
      fetch('notifications-poll.php')
        .then(r => r.json())
        .then(data => {
          // Update bell badge
          const bell = document.querySelector('.btn-notif');
          let badge = bell.querySelector('.notif-badge');
          if (data.unread > 0) {
            if (!badge) {
              badge = document.createElement('span');
              badge.className = 'notif-badge';
              bell.appendChild(badge);
            }
            badge.textContent = data.unread;
          } else if (badge) {
            badge.remove();
          }

          // Show toast for new notifications
          if (data.unread > lastUnread) {
            const newest = data.notifications.find(n => n.is_read == 0);
            if (newest) showLiveToast(newest.message, 'toast-success');
          }
          lastUnread = data.unread;

          // Refresh notification modal body if open
          const modal = document.getElementById('notif-modal');
          if (modal.style.display === 'flex') {
            document.getElementById('notif-body').innerHTML = data.notifications.length
              ? data.notifications.map(n => `
                    <div class="notif-item ${n.is_read == 0 ? 'notif-unread' : ''}">
                      <p>${n.message}</p>
                      <span class="notif-time">${n.created_at}</span>
                    </div>`).join('') +
              (data.unread > 0 ? '<a href="dashboard.php?mark_read=1" class="btn-mark-read">Mark all as read</a>' : '')
              : '<p>No notifications yet.</p>';
          }
        }).catch(() => { });
    }

    function pollJobs() {
      fetch('jobs-poll.php')
        .then(r => r.json())
        .then(data => {
          data.forEach(row => {
            if (isPoster) {
              // Update pending applicant count badge on card
              const countEl = document.getElementById('pending-count-' + row.job_id);
              if (countEl) countEl.textContent = row.pending_count > 0
                ? '👥 Applicants (' + row.pending_count + ')'
                : '👥 Applicants (0)';
              // Update job status badge
              const badgeEl = document.getElementById('job-badge-' + row.job_id);
              const s = statusLabels[row.status];
              if (badgeEl && s) {
                badgeEl.textContent = s.label;
                badgeEl.className = 'badge ' + s.cls;
              }
            } else {
              // Update application status badge for stander
              const appEl = document.getElementById('app-badge-' + row.job_id);
              const a = appLabels[row.app_status];
              if (appEl && a) {
                appEl.textContent = a.label;
                appEl.className = 'badge ' + a.cls;
              }
              // If newly accepted, show toast
              const prev = appEl ? appEl.dataset.prev : null;
              if (appEl && prev === 'pending' && row.app_status === 'accepted') {
                showLiveToast('Your application was accepted!', 'toast-success');
              }
              if (appEl && prev === 'pending' && row.app_status === 'declined') {
                showLiveToast('Your application was not selected.', 'toast-warning');
              }
              if (appEl) appEl.dataset.prev = row.app_status;
            }
          });
        }).catch(() => { });
    }

    // Poll every 5 seconds
    setInterval(pollNotifications, 5000);
    setInterval(pollJobs, 5000);
  </script>
</body>

</html>