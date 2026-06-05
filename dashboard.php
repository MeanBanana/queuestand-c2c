<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
guardRoute('user');

$user = currentUser();

// ── POST ACTIONS ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verifyCsrfToken();
  $jid = (int) ($_POST['job_id'] ?? 0);

  // Accept applicant
  if (isset($_POST['accept_applicant'])) {
    $sid = $_POST['stander_id'];
    $pdo->prepare("UPDATE jobs SET status='assigned', assigned_stander_id=? WHERE job_id=? AND poster_id=?")
      ->execute([$sid, $jid, $user['id']]);
    $pdo->prepare("UPDATE job_applications SET status='accepted' WHERE job_id=? AND stander_id=?")
      ->execute([$jid, $sid]);
    $pdo->prepare("UPDATE job_applications SET status='declined' WHERE job_id=? AND stander_id!=?")
      ->execute([$jid, $sid]);
    $jobTitle = $pdo->prepare("SELECT title FROM jobs WHERE job_id=?");
    $jobTitle->execute([$jid]);
    $title = $jobTitle->fetchColumn();
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
      ->execute([$sid, "Your application for \"{$title}\" was accepted! Get ready to stand."]);
    $declined = $pdo->prepare("SELECT stander_id FROM job_applications WHERE job_id=? AND stander_id!=?");
    $declined->execute([$jid, $sid]);
    foreach ($declined->fetchAll() as $row) {
      $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
        ->execute([$row['stander_id'], "Your application for \"{$title}\" was not selected this time."]);
    }
    header('Location: dashboard.php?toast=accepted');
    exit;
  }

  // Decline a single applicant
  if (isset($_POST['decline_applicant'])) {
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

  // Advance job status
  if (isset($_POST['advance_status'])) {
    $transitions = ['assigned' => 'in_progress', 'in_progress' => 'completed'];
    $current = $_POST['current_status'] ?? '';
    if (isset($transitions[$current])) {
      $next = $transitions[$current];
      $pdo->prepare("UPDATE jobs SET status=? WHERE job_id=? AND poster_id=?")
        ->execute([$next, $jid, $user['id']]);
      $row = $pdo->prepare("SELECT assigned_stander_id, title FROM jobs WHERE job_id=?");
      $row->execute([$jid]);
      $jobRow = $row->fetch();
      $msgs = [
        'in_progress' => "The poster has marked your job \"{$jobRow['title']}\" as In Progress.",
        'completed'   => "Your job \"{$jobRow['title']}\" has been marked as Completed.",
      ];
      if ($jobRow['assigned_stander_id']) {
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
          ->execute([$jobRow['assigned_stander_id'], $msgs[$next]]);
      }
    }
    header('Location: dashboard.php?toast=status_updated');
    exit;
  }

  // Cancel job
  if (isset($_POST['cancel_job'])) {
    $pdo->prepare("UPDATE jobs SET status='cancelled' WHERE job_id=? AND poster_id=? AND status IN ('open','assigned')")
      ->execute([$jid, $user['id']]);
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
if (isset($_POST['mark_read'])) {
  verifyCsrfToken();
  $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);
  header('Location: dashboard.php');
  exit;
}

$toast = $_GET['toast'] ?? '';

// FETCH POSTED JOBS
$postedStmt = $pdo->prepare("
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
$postedStmt->execute([$user['id']]);
$postedJobs = $postedStmt->fetchAll();

// FETCH APPLICATIONS (jobs this user applied to stand in)
$appStmt2 = $pdo->prepare("
    SELECT j.*, ja.status AS app_status, ja.applied_at,
           p.first_name AS p_first, p.last_name AS p_last, p.email AS p_email, p.phone AS p_phone
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.job_id
    JOIN users p ON j.poster_id = p.user_id
    WHERE ja.stander_id = ?
    ORDER BY ja.applied_at DESC
");
$appStmt2->execute([$user['id']]);
$appliedJobs = $appStmt2->fetchAll();

// FETCH APPLICANTS PER POSTED JOB
$applicantsByJob = [];
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

// FETCH REVIEWED JOB IDS
$revStmt = $pdo->prepare("SELECT job_id FROM reviews WHERE rater_id=?");
$revStmt->execute([$user['id']]);
$reviewedJobIds = array_column($revStmt->fetchAll(), 'job_id');

// FETCH NOTIFICATIONS
$notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$notifStmt->execute([$user['id']]);
$notifications = $notifStmt->fetchAll();
$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));

// STATS — combine both
$allJobStatuses = array_merge(
  array_column($postedJobs, 'status'),
  array_column($appliedJobs, 'status')
);
$counts = [
  'posted'      => count($postedJobs),
  'applied'     => count($appliedJobs),
  'in_progress' => count(array_filter($allJobStatuses, fn($s) => $s === 'in_progress')),
  'completed'   => count(array_filter($allJobStatuses, fn($s) => $s === 'completed')),
];

$statusLabels = [
  'open'        => ['label' => 'Open',        'class' => 'badge-open'],
  'assigned'    => ['label' => 'Assigned',    'class' => 'badge-assigned'],
  'in_progress' => ['label' => 'In Progress', 'class' => 'badge-progress'],
  'completed'   => ['label' => 'Completed',   'class' => 'badge-completed'],
  'cancelled'   => ['label' => 'Cancelled',   'class' => 'badge-cancelled'],
];

$appStatusLabels = [
  'pending'  => ['label' => 'Pending',  'class' => 'badge-assigned'],
  'accepted' => ['label' => 'Accepted', 'class' => 'badge-completed'],
  'declined' => ['label' => 'Declined', 'class' => 'badge-cancelled'],
];

$toastMessages = [
  'job_posted'       => ['msg' => 'Job posted successfully! Check your dashboard below.', 'type' => 'toast-success'],
  'job_cancelled'    => ['msg' => 'Job cancelled.', 'type' => 'toast-warning'],
  'accepted'         => ['msg' => 'Stander accepted! They\'ve been notified.', 'type' => 'toast-success'],
  'declined'         => ['msg' => 'Applicant declined.', 'type' => 'toast-warning'],
  'status_updated'   => ['msg' => 'Job status updated.', 'type' => 'toast-success'],
  'review_submitted' => ['msg' => 'Review submitted! Thank you.', 'type' => 'toast-success'],
  'review_exists'    => ['msg' => 'You already reviewed this job.', 'type' => 'toast-warning'],
  'review_invalid'   => ['msg' => 'Invalid review submission.', 'type' => 'toast-warning'],
  'applied'          => ['msg' => 'Application sent! Check "Your Applications" below to track it.', 'type' => 'toast-success'],
  'app_accepted'     => ['msg' => 'Your application was accepted! Get ready to stand.', 'type' => 'toast-success'],
  'app_declined'     => ['msg' => 'Your application was not selected this time.', 'type' => 'toast-warning'],
  'welcome'          => ['msg' => 'Welcome to QueueStand! Post a job or browse available queues.', 'type' => 'toast-success'],
];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | QueueStand</title>
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/components.css" />
  <link rel="stylesheet" href="css/dashboard.css" />
</head>

<body>
  <?php include 'includes/navbar.php'; ?>

  <?php if ($toast && isset($toastMessages[$toast])): ?>
    <div id="toast" class="toast <?= $toastMessages[$toast]['type'] ?>">
      <?= $toastMessages[$toast]['msg'] ?>
    </div>
  <?php endif; ?>

  <!-- Leave-a-Review Modal -->
  <div id="review-form-modal" class="modal-overlay" style="display:none">
    <div class="modal-box">
      <button class="modal-close" onclick="closeModal('review-form-modal')">✕</button>
      <h3 id="review-form-title">Leave a Review</h3>
      <form method="POST" action="submit-review.php">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="job_id" id="review-job-id">
        <div class="star-rating" id="star-rating">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <button type="button" class="star" data-value="<?= $i ?>">★</button>
          <?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="review-rating" value="0">
        <p id="star-error" class="msg-error" style="display:none">Please select a rating.</p>
        <textarea name="comment" id="review-comment" placeholder="Share your experience (optional)…" rows="4"></textarea>
        <button type="submit" class="btn-primary" style="width:100%;margin-top:1rem" onclick="return validateStars()">Submit Review</button>
      </form>
    </div>
  </div>

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
            <form method="POST">
              <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
              <input type="hidden" name="mark_read" value="1">
              <button type="submit" class="btn-mark-read">Mark all as read</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <main>
    <div class="dash-header">
      <div>
        <h1>Welcome back, <?= htmlspecialchars($user['first_name']) ?></h1>
        <p class="dash-role">You can post jobs and apply to stand in queues</p>
      </div>
      <div class="dash-header-actions">
        <button class="btn-notif" onclick="openModal('notif-modal')">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
          <?php if ($unreadCount > 0): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?>
        </button>
        <a href="post-job.php" class="btn-primary">+ Post a Job</a>
        <a href="browse-jobs.php" class="btn-primary">Browse Jobs</a>
      </div>
    </div>

    <div class="dash-stats">
      <div class="stat-card"><span class="stat-number"><?= $counts['posted'] ?></span><span class="stat-label">Jobs Posted</span></div>
      <div class="stat-card"><span class="stat-number"><?= $counts['applied'] ?></span><span class="stat-label">Applied To</span></div>
      <div class="stat-card"><span class="stat-number"><?= $counts['in_progress'] ?></span><span class="stat-label">In Progress</span></div>
      <div class="stat-card"><span class="stat-number"><?= $counts['completed'] ?></span><span class="stat-label">Completed</span></div>
    </div>

    <?php
    $nextStatus = ['assigned' => 'in_progress', 'in_progress' => 'completed'];
    $nextLabels = ['assigned' => 'Mark as In Progress', 'in_progress' => 'Mark as Completed'];
    ?>

    <!-- ── SECTION 1: POSTED JOBS ── -->
    <h2 class="dash-section-title">Your Posted Jobs</h2>
    <?php if (empty($postedJobs)): ?>
      <div class="dash-empty">
        <p>You haven't posted any jobs yet.</p>
        <a href="post-job.php" class="btn-primary">Post Your First Job</a>
      </div>
    <?php else: ?>
      <div class="dash-grid">
        <?php foreach ($postedJobs as $job):
          $badge = $statusLabels[$job['status']] ?? ['label' => $job['status'], 'class' => 'badge-open'];
          $hasStander = !empty($job['assigned_stander_id']);
          $applicants = $applicantsByJob[$job['job_id']] ?? [];
          $pendingApplicants = array_filter($applicants, fn($a) => $a['status'] === 'pending');
          $canCancel = in_array($job['status'], ['open', 'assigned']);
        ?>
          <div class="dash-card" id="card-<?= $job['job_id'] ?>">
            <div class="dash-card-top">
              <h3><?= htmlspecialchars($job['title']) ?></h3>
              <span id="job-badge-<?= $job['job_id'] ?>" class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
            </div>
            <p><?= htmlspecialchars($job['location']) ?></p>
            <p><?= date('d M Y, H:i', strtotime($job['required_datetime'])) ?></p>
            <p class="dash-pay">R <?= number_format($job['pay_amount'], 2) ?></p>

            <button class="btn-expand" onclick="toggleExpand('p<?= $job['job_id'] ?>')">
              <span id="expand-label-p<?= $job['job_id'] ?>">&#9660; Details</span>
            </button>
            <div id="expand-p<?= $job['job_id'] ?>" class="card-expand" style="display:none">
              <?php if ($job['description']): ?>
                <p class="expand-desc"><?= htmlspecialchars($job['description']) ?></p>
              <?php endif; ?>

              <?php if ($hasStander): ?>
                <div class="stander-info" id="stander-info-<?= $job['job_id'] ?>">
                  <p class="stander-title">Assigned Stander</p>
                  <p><strong><?= htmlspecialchars($job['s_first'] . ' ' . $job['s_last']) ?></strong></p>
                  <p><?= htmlspecialchars($job['s_email']) ?></p>
                  <?php if ($job['s_phone']): ?><p><?= htmlspecialchars($job['s_phone']) ?></p><?php endif; ?>
                  <?php if ($job['s_city']): ?><p><?= htmlspecialchars($job['s_city']) ?></p><?php endif; ?>
                  <p><?= $job['avg_rating'] ? $job['avg_rating'] . '/5 (' . $job['review_count'] . ' reviews)' : 'No reviews yet' ?></p>
                  <button class="btn-reviews" onclick="loadReviews(<?= $job['assigned_stander_id'] ?>, '<?= htmlspecialchars(addslashes($job['s_first'] . ' ' . $job['s_last'])) ?>')">Read Reviews</button>
                  <?php if ($job['status'] === 'completed' && !in_array($job['job_id'], $reviewedJobIds)): ?>
                    <button class="btn-leave-review" onclick="openReviewForm(<?= $job['job_id'] ?>, '<?= htmlspecialchars(addslashes($job['s_first'] . ' ' . $job['s_last'])) ?>')">Leave a Review</button>
                  <?php elseif ($job['status'] === 'completed' && in_array($job['job_id'], $reviewedJobIds)): ?>
                    <span class="reviewed-badge">✓ Reviewed</span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <?php if ($job['status'] === 'open' && count($pendingApplicants) > 0): ?>
                <div class="applicants-section">
                  <p id="pending-count-<?= $job['job_id'] ?>" class="applicants-title">Applicants (<?= count($pendingApplicants) ?>)</p>
                  <?php foreach ($pendingApplicants as $app): ?>
                    <div class="applicant-card">
                      <div class="applicant-info">
                        <strong><?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?></strong>
                        <span class="applicant-meta"><?= $app['avg_rating'] ? $app['avg_rating'] . '/5 (' . $app['review_count'] . ' reviews)' : 'No reviews' ?></span>
                        <span class="applicant-meta"><?= htmlspecialchars($app['email']) ?></span>
                        <?php if ($app['phone']): ?><span class="applicant-meta"><?= htmlspecialchars($app['phone']) ?></span><?php endif; ?>
                      </div>
                      <div class="applicant-actions">
                        <button class="btn-reviews" style="margin-right:0.4rem" onclick="loadReviews(<?= $app['stander_id'] ?>, '<?= htmlspecialchars(addslashes($app['first_name'] . ' ' . $app['last_name'])) ?>')">Reviews</button>
                        <form method="POST" style="display:inline">
                          <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                          <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                          <input type="hidden" name="stander_id" value="<?= $app['stander_id'] ?>">
                          <button type="submit" name="accept_applicant" class="btn-accept">✓ Accept</button>
                        </form>
                        <form method="POST" style="display:inline">
                          <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                          <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                          <input type="hidden" name="stander_id" value="<?= $app['stander_id'] ?>">
                          <button type="submit" name="decline_applicant" class="btn-decline" onclick="return confirm('Decline this applicant?')">✕ Decline</button>
                        </form>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php elseif ($job['status'] === 'open'): ?>
                <p class="no-stander">No applicants yet.</p>
              <?php endif; ?>

              <?php if ($job['status'] === 'assigned'): ?>
                <a href="checkout.php?job_id=<?= $job['job_id'] ?>" class="btn-primary" style="display:inline-block;margin-top:0.75rem">Pay Now</a>
              <?php endif; ?>

              <?php if (isset($nextStatus[$job['status']])): ?>
                <form method="POST" style="margin-top:0.75rem">
                  <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                  <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                  <input type="hidden" name="current_status" value="<?= $job['status'] ?>">
                  <button type="submit" name="advance_status" class="btn-advance"><?= $nextLabels[$job['status']] ?></button>
                </form>
              <?php endif; ?>

              <?php if ($canCancel): ?>
                <form method="POST" onsubmit="return confirm('Cancel this job?')">
                  <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                  <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>">
                  <button type="submit" name="cancel_job" class="btn-cancel">Cancel Job</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- ── SECTION 2: APPLICATIONS ── -->
    <h2 class="dash-section-title" style="margin-top:2.5rem">Your Applications</h2>
    <?php if (empty($appliedJobs)): ?>
      <div class="dash-empty">
        <p>You haven't applied for any jobs yet.</p>
        <a href="browse-jobs.php" class="btn-primary">Find a Job to Stand In</a>
      </div>
    <?php else: ?>
      <div class="dash-grid">
        <?php foreach ($appliedJobs as $job):
          $badge    = $statusLabels[$job['status']]    ?? ['label' => $job['status'],        'class' => 'badge-open'];
          $appBadge = $appStatusLabels[$job['app_status']] ?? ['label' => $job['app_status'], 'class' => 'badge-assigned'];
        ?>
          <div class="dash-card" id="app-card-<?= $job['job_id'] ?>">
            <div class="dash-card-top">
              <h3><?= htmlspecialchars($job['title']) ?></h3>
              <span id="job-status-badge-<?= $job['job_id'] ?>" class="badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
            </div>
            <div class="app-status-row" style="margin-bottom:0.5rem">
              <span>Your application: </span>
              <span id="app-badge-<?= $job['job_id'] ?>" class="badge <?= $appBadge['class'] ?>" data-prev="<?= $job['app_status'] ?>"><?= $appBadge['label'] ?></span>
            </div>
            <div class="stander-info">
              <p class="stander-title">Job Details</p>
              <p><strong>Location:</strong> <?= htmlspecialchars($job['location']) ?></p>
              <p><strong>Required:</strong> <?= date('d M Y, H:i', strtotime($job['required_datetime'])) ?></p>
              <p><strong>Pay:</strong> R <?= number_format($job['pay_amount'], 2) ?></p>
              <p><strong>Posted by:</strong> <?= htmlspecialchars($job['p_first'] . ' ' . $job['p_last']) ?></p>
              <?php if ($job['p_email']): ?><p><strong>Email:</strong> <?= htmlspecialchars($job['p_email']) ?></p><?php endif; ?>
              <?php if ($job['description']): ?><p><strong>Details:</strong> <?= htmlspecialchars($job['description']) ?></p><?php endif; ?>
              <p><strong>Applied:</strong> <?= date('d M Y, H:i', strtotime($job['applied_at'])) ?></p>
            </div>
            <?php if ($job['app_status'] === 'accepted'): ?>
              <p class="msg-success" style="margin-top:0.5rem">You were accepted! Prepare to stand in the queue.</p>
            <?php elseif ($job['app_status'] === 'declined'): ?>
              <p class="msg-error" style="margin-top:0.5rem">You were not selected for this job.</p>
            <?php else: ?>
              <p style="margin-top:0.5rem;color:#888;font-size:0.85rem">Waiting for the poster to review your application.</p>
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

    function openReviewForm(jobId, standerName) {
      document.getElementById('review-job-id').value = jobId;
      document.getElementById('review-form-title').textContent = 'Review for ' + standerName;
      document.getElementById('review-rating').value = 0;
      document.getElementById('review-comment').value = '';
      document.getElementById('star-error').style.display = 'none';
      document.querySelectorAll('#star-rating .star').forEach(s => s.classList.remove('active'));
      openModal('review-form-modal');
    }

    (function initStars() {
      const stars = document.querySelectorAll('#star-rating .star');
      stars.forEach(star => {
        star.addEventListener('click', () => {
          const val = +star.dataset.value;
          document.getElementById('review-rating').value = val;
          stars.forEach(s => s.classList.toggle('active', +s.dataset.value <= val));
          document.getElementById('star-error').style.display = 'none';
        });
        star.addEventListener('mouseenter', () => {
          const val = +star.dataset.value;
          stars.forEach(s => s.classList.toggle('hover', +s.dataset.value <= val));
        });
        star.addEventListener('mouseleave', () => {
          stars.forEach(s => s.classList.remove('hover'));
        });
      });
    })();

    function validateStars() {
      if (+document.getElementById('review-rating').value < 1) {
        document.getElementById('star-error').style.display = 'block';
        return false;
      }
      return true;
    }

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
    let lastUnread = <?= $unreadCount ?>;

    const statusLabels = {
      open:        { label: 'Open',        cls: 'badge-open' },
      assigned:    { label: 'Assigned',    cls: 'badge-assigned' },
      in_progress: { label: 'In Progress', cls: 'badge-progress' },
      completed:   { label: 'Completed',   cls: 'badge-completed' },
      cancelled:   { label: 'Cancelled',   cls: 'badge-cancelled' },
    };
    const appLabels = {
      pending:  { label: 'Pending',  cls: 'badge-assigned' },
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
          const bell = document.querySelector('.btn-notif');
          let badge = bell.querySelector('.notif-badge');
          if (data.unread > 0) {
            if (!badge) { badge = document.createElement('span'); badge.className = 'notif-badge'; bell.appendChild(badge); }
            badge.textContent = data.unread;
          } else if (badge) { badge.remove(); }
          if (data.unread > lastUnread) {
            const newest = data.notifications.find(n => n.is_read == 0);
            if (newest) showLiveToast(newest.message, 'toast-success');
          }
          lastUnread = data.unread;
          const modal = document.getElementById('notif-modal');
          if (modal.style.display === 'flex') {
            document.getElementById('notif-body').innerHTML = data.notifications.length
              ? data.notifications.map(n => `<div class="notif-item ${n.is_read == 0 ? 'notif-unread' : ''}"><p>${n.message}</p><span class="notif-time">${n.created_at}</span></div>`).join('') +
                (data.unread > 0 ? `<form method="POST"><input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"><input type="hidden" name="mark_read" value="1"><button type="submit" class="btn-mark-read">Mark all as read</button></form>` : '')
              : '<p>No notifications yet.</p>';
          }
        }).catch(() => {});
    }

    function pollJobs() {
      // Poll posted jobs (poster view)
      fetch('jobs-poll.php?mode=poster')
        .then(r => r.json())
        .then(data => {
          data.forEach(row => {
            const countEl = document.getElementById('pending-count-' + row.job_id);
            if (countEl) countEl.textContent = 'Applicants (' + (row.pending_count || 0) + ')';
            const badgeEl = document.getElementById('job-badge-' + row.job_id);
            const s = statusLabels[row.status];
            if (badgeEl && s) { badgeEl.textContent = s.label; badgeEl.className = 'badge ' + s.cls; }
            const standerEl = document.getElementById('stander-info-' + row.job_id);
            if (standerEl && row.stander_id) {
              standerEl.style.display = 'block';
              standerEl.innerHTML =
                '<p class="stander-title">Assigned Stander</p>' +
                '<p><strong>' + row.s_first + ' ' + row.s_last + '</strong></p>' +
                '<p>' + row.s_email + '</p>' +
                (row.s_phone ? '<p>' + row.s_phone + '</p>' : '') +
                (row.s_city  ? '<p>' + row.s_city  + '</p>' : '') +
                '<p>' + (row.avg_rating ? row.avg_rating + '/5 (' + row.review_count + ' reviews)' : 'No reviews yet') + '</p>';
            }
          });
        }).catch(() => {});

      // Poll applications (stander view)
      fetch('jobs-poll.php?mode=stander')
        .then(r => r.json())
        .then(data => {
          data.forEach(row => {
            const appEl = document.getElementById('app-badge-' + row.job_id);
            const a = appLabels[row.app_status];
            if (appEl && a) {
              const prev = appEl.dataset.prev;
              appEl.textContent = a.label;
              appEl.className = 'badge ' + a.cls;
              if (prev === 'pending' && row.app_status === 'accepted')
                showLiveToast('Your application was accepted! Get ready to stand.', 'toast-success');
              if (prev === 'pending' && row.app_status === 'declined')
                showLiveToast('Your application was not selected this time.', 'toast-warning');
              appEl.dataset.prev = row.app_status;
            }
          });
        }).catch(() => {});
    }

    setInterval(pollNotifications, 5000);
    setInterval(pollJobs, 5000);
  </script>
</body>

</html>