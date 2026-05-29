<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([currentUser()['id']]);
$user = $stmt->fetch();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $city       = trim($_POST['city']       ?? '');

    $upd = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, city=? WHERE user_id=?");
    $upd->execute([$first_name, $last_name, $phone, $city, $user['user_id']]);

    $_SESSION['first_name'] = $first_name;
    $_SESSION['last_name']  = $last_name;
    $success = 'Profile updated.';
    $user = array_merge($user, compact('first_name', 'last_name', 'phone', 'city'));
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile | QueueStand</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="css/components.css" />
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>

    <main class="auth-main">
      <h2>My Profile</h2>
      <?php if ($success): ?><p class="msg-success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

      <form method="POST">
        <div>
          <label>First Name</label>
          <input type="text" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required />
        </div>
        <div>
          <label>Last Name</label>
          <input type="text" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required />
        </div>
        <div>
          <label>Email</label>
          <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled />
        </div>
        <div>
          <label>Phone</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" maxlength="10" />
        </div>
        <div>
          <label>City</label>
          <input type="text" name="city" value="<?= htmlspecialchars($user['city'] ?? 'Johannesburg') ?>" />
        </div>
        <div>
          <label>Role</label>
          <input type="text" value="<?= htmlspecialchars($user['role']) ?>" disabled />
        </div>
        <div><button type="submit">Save Changes</button></div>
      </form>
      <p><a href="logout.php">Logout</a></p>
    </main>

    <script src="js/footer.js"></script>
  </body>
</html>
