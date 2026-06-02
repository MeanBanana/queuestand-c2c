<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
guardRoute('admin');

$me = $_SESSION['user_id'];
$toast = '';

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $uid = $_POST['user_id'] ?? '';

    if (isset($_POST['toggle_role']) && $uid !== $me) {
        $current = $pdo->prepare("SELECT role FROM users WHERE user_id=?");
        $current->execute([$uid]);
        $role = $current->fetchColumn();
        $newRole = $role === 'admin' ? 'user' : 'admin';
        $pdo->prepare("UPDATE users SET role=? WHERE user_id=?")->execute([$newRole, $uid]);
        $toast = $newRole === 'admin' ? 'promoted' : 'demoted';
    }

    if (isset($_POST['toggle_verify'])) {
        $pdo->prepare("UPDATE users SET is_verified = 1 - is_verified WHERE user_id=?")->execute([$uid]);
        $toast = 'verify_toggled';
    }

    if (isset($_POST['delete_user']) && $uid !== $me) {
        $pdo->prepare("DELETE FROM users WHERE user_id=?")->execute([$uid]);
        $toast = 'deleted';
    }

    header('Location: admin-users.php?toast=' . $toast);
    exit;
}

$toast = $_GET['toast'] ?? '';
$search = trim($_GET['q'] ?? '');

$query = "SELECT u.*,
            (SELECT COUNT(*) FROM jobs WHERE poster_id = u.user_id) AS jobs_posted,
            (SELECT COUNT(*) FROM jobs WHERE assigned_stander_id = u.user_id) AS jobs_stood
          FROM users u
          WHERE 1=1";
$params = [];

if ($search !== '') {
    $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.user_id LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like, $like];
}

$query .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

$toastMessages = [
    'promoted'       => ['msg' => 'User promoted to admin.', 'type' => 'toast-success'],
    'demoted'        => ['msg' => 'Admin demoted to user.', 'type' => 'toast-warning'],
    'verify_toggled' => ['msg' => 'Verification status updated.', 'type' => 'toast-success'],
    'deleted'        => ['msg' => 'User deleted.', 'type' => 'toast-warning'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manage Users | QueueStand Admin</title>
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
      <h1>Manage Users</h1>
    </div>

    <form method="GET" class="search-bar">
      <input type="text" name="q" placeholder="Search by name, email or ID…" value="<?= htmlspecialchars($search) ?>" />
      <button type="submit">Search</button>
      <?php if ($search): ?><a href="admin-users.php" class="btn-cancel btn-clear-filter">Clear</a><?php endif; ?>
    </form>

    <p class="user-count"><?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?> found</p>

    <div class="table-wrapper">
      <table class="admin-table">
        <thead>
          <tr>
            <th></th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Verified</th>
            <th>Joined</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): $isSelf = $u['user_id'] === $me; ?>
          <tr class="user-main-row" data-uid="<?= $u['user_id'] ?>">
            <td>
              <button class="btn-expand-row" onclick="toggleUserRow('<?= $u['user_id'] ?>')">▶</button>
            </td>
            <td>
              <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
              <?= $isSelf ? ' <span class="badge badge-progress">You</span>' : '' ?>
            </td>
            <td class="col-email"><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge <?= $u['role']==='admin' ? 'badge-progress' : 'badge-open' ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><?= $u['is_verified'] ? '<span class="badge badge-completed">Yes</span>' : '<span class="badge badge-cancelled">No</span>' ?></td>
            <td class="col-date"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          </tr>
          <tr class="user-detail-row" id="detail-<?= $u['user_id'] ?>">
            <td colspan="6">
              <div class="user-detail-grid">
                <div class="user-detail-item">
                  <span class="detail-label">ID</span>
                  <span class="detail-value"><?= htmlspecialchars($u['user_id']) ?></span>
                </div>
                <div class="user-detail-item">
                  <span class="detail-label">Phone</span>
                  <span class="detail-value"><?= htmlspecialchars($u['phone'] ?? '—') ?></span>
                </div>
                <div class="user-detail-item">
                  <span class="detail-label">City</span>
                  <span class="detail-value"><?= htmlspecialchars($u['city'] ?? '—') ?></span>
                </div>
                <div class="user-detail-item">
                  <span class="detail-label">Jobs Posted</span>
                  <span class="detail-value"><?= $u['jobs_posted'] ?></span>
                </div>
                <div class="user-detail-item">
                  <span class="detail-label">Jobs Stood</span>
                  <span class="detail-value"><?= $u['jobs_stood'] ?></span>
                </div>
              </div>
              <div class="action-group" style="margin-top:0.75rem">
                <form method="POST" class="action-form-inline">
                  <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                  <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                  <button type="submit" name="toggle_verify" class="<?= $u['is_verified'] ? 'btn-decline' : 'btn-accept' ?> btn-sm">
                    <?= $u['is_verified'] ? 'Unverify' : 'Verify' ?>
                  </button>
                </form>
                <?php if (!$isSelf): ?>
                <form method="POST" class="action-form-inline" onsubmit="return confirm('<?= $u['role']==='admin' ? 'Demote this admin to user?' : 'Promote this user to admin?' ?>')">
                  <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                  <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                  <button type="submit" name="toggle_role" class="<?= $u['role']==='admin' ? 'btn-decline' : 'btn-reviews' ?> btn-sm">
                    <?= $u['role']==='admin' ? 'Demote' : 'Make Admin' ?>
                  </button>
                </form>
                <form method="POST" class="action-form-inline" onsubmit="return confirm('Permanently delete this user and all their data?')">
                  <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                  <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                  <button type="submit" name="delete_user" class="btn-cancel btn-sm">Delete</button>
                </form>
                <?php endif; ?>
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

    function toggleUserRow(uid) {
      const detail = document.getElementById('detail-' + uid);
      const btn = document.querySelector(`[data-uid="${uid}"] .btn-expand-row`);
      const isOpen = detail.classList.toggle('open');
      btn.textContent = isOpen ? '▼' : '▶';
      btn.classList.toggle('expanded', isOpen);
    }
  </script>
</body>
</html>
