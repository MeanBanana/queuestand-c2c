<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

// Redirect already-logged-in admins
if (isAdmin()) {
    header('Location: admin-dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $ip  = $_SERVER['REMOTE_ADDR'];
    $key = 'admin_attempts_' . md5($ip);
    if (!isset($_SESSION[$key])) $_SESSION[$key] = ['count' => 0, 'since' => time()];
    if (time() - $_SESSION[$key]['since'] > 900) $_SESSION[$key] = ['count' => 0, 'since' => time()];
    if ($_SESSION[$key]['count'] >= 5) {
        $error = 'Too many attempts. Please try again later.';
    } else {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION[$key]['count'] = 0;
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];
        $_SESSION['role']       = $user['role'];
        header('Location: admin-dashboard.php');
        exit;
    }

    $_SESSION[$key]['count']++;
    $error = 'Invalid admin credentials.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login - QueueStand</title>
  <link rel="stylesheet" href="../css/styles.css" />
  <link rel="stylesheet" href="../css/components.css" />
</head>
<body>
  <?php include 'admin-navbar.php'; ?>

  <main class="auth-main">
    <h2>Admin Login</h2>
    <?php if ($error): ?>
      <p class="msg-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>" />
      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required />
      </div>
      <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required />
      </div>
      <div><button type="submit">Login</button></div>
    </form>
    <p class="auth-link"><a href="<?= BASE_URL ?>/login.php">← User Login</a></p>
  </main>

  <script src="../js/footer.js"></script>
</body>
</html>
