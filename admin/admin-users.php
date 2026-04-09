<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
requireRole('admin');

// Toggle verification
if (isset($_GET['verify'])) {
    $pdo->prepare("UPDATE users SET is_verified = 1 - is_verified WHERE user_id = ?")->execute([(int)$_GET['verify']]);
    header('Location: admin-users.php');
    exit;
}

$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Users | QueueStand Admin</title>
    <link rel="stylesheet" href="../css/styles.css" />
  </head>
  <body>
    <header>
      <div>
        <h1>QueueStand Admin</h1>
        <nav><ul>
          <li><a href="admin-dashboard.php">Dashboard</a></li>
          <li><a href="admin-users.php">Users</a></li>
          <li><a href="../logout.php">Logout</a></li>
        </ul></nav>
      </div>
    </header>

    <main>
      <h2>All Users</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Joined</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['user_id']) ?></td>
            <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['role']) ?></td>
            <td><?= $u['is_verified'] ? 'Yes' : 'No' ?></td>
            <td><?= htmlspecialchars($u['created_at']) ?></td>
            <td><a href="?verify=<?= $u['user_id'] ?>"><?= $u['is_verified'] ? 'Unverify' : 'Verify' ?></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </main>
  </body>
</html>
