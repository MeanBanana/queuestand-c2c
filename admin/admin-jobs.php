<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
guardRoute('admin');

$toast = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jid = (int)($_POST['job_id'] ?? 0);

    if (isset($_POST['set_status']) && $jid) {
        $allowed = ['open','assigned','in_progress','completed','cancelled'];
        $status = $_POST['status'] ?? '';
        if (in_array($status, $allowed)) {
            $pdo->prepare("UPDATE jobs SET status=? WHERE job_id=?")->execute([$status, $jid]);
            $toast = 'status_updated';
        }
    }

    if (isset($_POST['delete_job']) && $jid) {
        $pdo->prepare("DELETE FROM jobs WHERE job_id=?")->execute([$jid]);
        $toast = 'deleted';
    }

    header('Location: admin-jobs.php?toast=' . $toast);
    exit;
}

$toast  = $_GET['toast'] ?? '';
$search = trim($_GET['q'] ?? '');
$filter = $_GET['status'] ?? '';

$statuses = ['open','assigned','in_progress','completed','cancelled'];

$query = "SELECT j.*, u.first_name, u.last_name,
            s.first_name AS s_first, s.last_name AS s_last
          FROM jobs j
          JOIN users u ON j.poster_id = u.user_id
          LEFT JOIN users s ON j.assigned_stander_id = s.user_id
          WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (j.title LIKE ? OR j.location LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
    $like = "%$search%";
    $params = array_merge($params, [$like, $like, $like, $like]);
}
if ($filter !== '' && in_array($filter, $statuses)) {
    $query .= " AND j.status = ?";
    $params[] = $filter;
}

$query .= " ORDER BY j.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$statusBadge = [
    'open'        => 'badge-open',
    'assigned'    => 'badge-assigned',
    'in_progress' => 'badge-progress',
    'completed'   => 'badge-completed',
    'cancelled'   => 'badge-cancelled',
];

$toastMessages = [
    'status_updated' => ['msg' => 'Job status updated.', 'type' => 'toast-success'],
    'deleted'        => ['msg' => 'Job deleted.', 'type' => 'toast-warning'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Jobs | QueueStand Admin</title>
  <link rel="stylesheet" href="../css/styles.css" />
  <link rel="stylesheet" href="../css/dashboard.css" />
  <link rel="stylesheet" href="../css/components.css" />
  <link rel="stylesheet" href="../css/admin.css" />
</head>
<body>
  <?php include 'admin-navbar.php'; ?>

  <?php if ($toast && isset($toastMessages[$toast])): ?>
    <div id="toast" class="toast <?= $toastMessages[$toast]['type'] ?>"><?= $toastMessages[$toast]['msg'] ?></div>
  <?php endif; ?>

  <main>
    <div class="dash-header">
      <h1>Manage Jobs</h1>
    </div>

    <form method="GET" class="filter-bar">
      <input type="text" name="q" placeholder="Search by title, location or poster…" value="<?= htmlspecialchars($search) ?>" />
      <select name="status">
        <option value="">All Statuses</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= $s ?>" <?= $filter===$s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit">Filter</button>
      <?php if ($search || $filter): ?><a href="admin-jobs.php" class="btn-cancel btn-clear-filter">Clear</a><?php endif; ?>
    </form>

    <p class="job-count"><?= count($jobs) ?> job<?= count($jobs) !== 1 ? 's' : '' ?> found</p>

    <div class="table-wrapper">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Title</th>
          <th>Posted By</th>
          <th>Stander</th>
          <th>Location</th>
          <th>Pay</th>
          <th>Status</th>
          <th>Date</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($jobs as $j): ?>
        <tr>
          <td><?= $j['job_id'] ?></td>
          <td><?= htmlspecialchars($j['title']) ?></td>
          <td><?= htmlspecialchars($j['first_name'] . ' ' . $j['last_name']) ?></td>
          <td><?= $j['s_first'] ? htmlspecialchars($j['s_first'] . ' ' . $j['s_last']) : '<span class="text-muted">—</span>' ?></td>
          <td><?= htmlspecialchars($j['location']) ?></td>
          <td>R <?= number_format($j['pay_amount'], 2) ?></td>
          <td><span class="badge <?= $statusBadge[$j['status']] ?>"><?= ucfirst(str_replace('_',' ',$j['status'])) ?></span></td>
          <td><?= date('d M Y', strtotime($j['created_at'])) ?></td>
          <td>
            <div class="action-group">
              <form method="POST" class="action-form">
                <input type="hidden" name="job_id" value="<?= $j['job_id'] ?>">
                <select name="status" class="status-select">
                  <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $j['status']===$s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" name="set_status" class="btn-accept btn-sm">Update</button>
              </form>
              <form method="POST" class="action-form-inline" onsubmit="return confirm('Delete this job and all related data?')">
                <input type="hidden" name="job_id" value="<?= $j['job_id'] ?>">
                <button type="submit" name="delete_job" class="btn-cancel btn-sm">Delete</button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </main>

  <script src="../js/footer.js"></script>
  <script>
    const toast = document.getElementById('toast');
    if (toast) setTimeout(() => toast.classList.add('toast-hide'), 3500);
  </script>
</body>
</html>
