<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$toast = '';

// Handle apply action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn() && $_SESSION['role'] === 'queue_stander') {
  $job_id = (int) ($_POST['job_id'] ?? 0);
  $stander_id = currentUser()['id'];

  // Check not already applied
  $check = $pdo->prepare("SELECT 1 FROM job_applications WHERE job_id=? AND stander_id=?");
  $check->execute([$job_id, $stander_id]);

  if (!$check->fetch()) {
    $pdo->prepare("INSERT INTO job_applications (job_id, stander_id) VALUES (?,?)")
      ->execute([$job_id, $stander_id]);

    // Notify the poster
    $poster = $pdo->prepare("SELECT poster_id, title FROM jobs WHERE job_id=?");
    $poster->execute([$job_id]);
    $jobRow = $poster->fetch();

    $name = currentUser()['first_name'] . ' ' . currentUser()['last_name'];
    $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
      ->execute([$jobRow['poster_id'], "{$name} has applied to stand for your job: \"{$jobRow['title']}\""]);

    header('Location: browse-jobs.php?toast=applied');
    exit;
  }
  header('Location: browse-jobs.php?toast=already_applied');
  exit;
}

$toast = $_GET['toast'] ?? '';

// Get applied job IDs for current stander to disable buttons
$appliedJobIds = [];
if (isLoggedIn() && $_SESSION['role'] === 'queue_stander') {
  $rows = $pdo->prepare("SELECT job_id FROM job_applications WHERE stander_id=?");
  $rows->execute([currentUser()['id']]);
  $appliedJobIds = array_column($rows->fetchAll(), 'job_id');
}

$jobs = $pdo->query("
    SELECT j.*, u.first_name, u.last_name
    FROM jobs j
    JOIN users u ON j.poster_id = u.user_id
    WHERE j.status = 'open'
    ORDER BY j.required_datetime ASC
")->fetchAll();

$toastMessages = [
  'applied' => ['msg' => 'Application sent! The poster will review it.', 'type' => 'toast-success'],
  'already_applied' => ['msg' => 'You already applied for this job.', 'type' => 'toast-warning'],
];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Available Queue Jobs | QueueStand</title>
  <link rel="stylesheet" href="css/styles.css" />
</head>

<body>
  <?php include 'includes/navbar.php'; ?>

  <?php if ($toast && isset($toastMessages[$toast])): ?>
    <div id="toast" class="toast <?= $toastMessages[$toast]['type'] ?>">
      <?= $toastMessages[$toast]['msg'] ?>
    </div>
  <?php endif; ?>

  <main>
      <div class="browse-header">
        <h1>Available Queue Jobs</h1>

        <!-- Search Bar -->
        <div class="search-container">
          <div class="search-input-wrapper">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="jobSearch" placeholder="Search jobs or locations..." autocomplete="off" />
            <button id="clearSearch" class="clear-btn" title="Clear search">
              <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
        </div>
      </div>

      <?php if (empty($jobs)): ?>
        <p class="browse-empty">No open jobs at the moment. Check back soon!</p>
      <?php endif; ?>

      <div id="jobsContainer">
        <?php foreach ($jobs as $job): ?>
          <div class="card" data-title="<?= strtolower(htmlspecialchars($job['title'])) ?>"
            data-location="<?= strtolower(htmlspecialchars($job['location'])) ?>">
            <h3><?= htmlspecialchars($job['title']) ?></h3>
            <p><?= htmlspecialchars($job['location']) ?></p>
            <p><?= date('d M Y, H:i', strtotime($job['required_datetime'])) ?></p>
            <p>R <?= number_format($job['pay_amount'], 2) ?></p>
            <p>Posted by: <?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?></p>
            <?php if ($job['description']): ?>
              <p><?= htmlspecialchars($job['description']) ?></p>
            <?php endif; ?>

            <?php if (isLoggedIn() && $_SESSION['role'] === 'queue_stander'): ?>
              <?php $alreadyApplied = in_array($job['job_id'], $appliedJobIds); ?>
              <form method="POST">
                <input type="hidden" name="job_id" value="<?= $job['job_id'] ?>" />
                <button type="submit" <?= $alreadyApplied ? 'disabled class="btn-applied"' : '' ?>>
                  <?= $alreadyApplied ? 'Applied' : 'Apply to Stand' ?>
                </button>
              </form>
            <?php elseif (!isLoggedIn()): ?>
              <a href="login.php">Login to Apply</a>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
  </main>

  <script src="js/footer.js"></script>
  <script>
    const toast = document.getElementById('toast');
    if (toast) setTimeout(() => toast.classList.add('toast-hide'), 3500);
  </script>

  <!-- Search Bar funtionality -->
  <script>
    const searchInput = document.getElementById('jobSearch');
    const clearBtn = document.getElementById('clearSearch');
    const jobsContainer = document.getElementById('jobsContainer');
    const cards = jobsContainer.querySelectorAll('.card');
    let noResultMsg = null;

    function filterJobs() {
      const query = searchInput.value.toLowerCase().trim();
      let visibleCount = 0;

      cards.forEach(card => {
        const title = card.dataset.title || '';
        const location = card.dataset.location || '';

        if (title.includes(query) || location.includes(query)) {
          card.style.display = 'block';
          visibleCount++;
        } else {
          card.style.display = 'none';
        }
      });

      // No Result message
      if (!noResultMsg) {
        noResultMsg = document.createElement('p');
        noResultMsg.className = 'no-results';
        noResultMsg.textContent = 'No matching jobs found.';
        jobsContainer.parentNode.insertBefore(noResultMsg, jobsContainer.nextSibling);
      }

      noResultMsg.style.display = (visibleCount === 0 && query !== '') ? 'block' : 'none';

    }

    // Event Listener
    searchInput.addEventListener('input', filterJobs);

    clearBtn.addEventListener('click', () => {
      searchInput.value = '';
      filterJobs();
      searchInput.focus();
    });

    // Search/hide clear button
    searchInput.addEventListener('input', () => {
      clearBtn.style.display = searchInput.value.length > 0 ? 'flex' : 'none';
    });

    // Keyboard Shortcut (/)
    document.addEventListener('keydown', (e) => {
      if (e.key === '/' && document.activeElement.tagName !== "INPUT" && document.activeElement.tagName !== "TEXTAREA") {
        e.preventDefault();
        searchInput.focus();
      }
    })

  </script>

</body>

</html>