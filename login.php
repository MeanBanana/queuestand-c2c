<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
guardRoute('public');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    // Simple brute-force throttle: max 10 attempts per IP per 15 minutes
    $ip  = $_SERVER['REMOTE_ADDR'];
    $key = 'login_attempts_' . md5($ip);
    if (!isset($_SESSION[$key])) $_SESSION[$key] = ['count' => 0, 'since' => time()];
    if (time() - $_SESSION[$key]['since'] > 900) $_SESSION[$key] = ['count' => 0, 'since' => time()];
    if ($_SESSION[$key]['count'] >= 10) {
        $error = 'Too many login attempts. Please try again later.';
    } else {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION[$key]['count'] = 0;
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];
        $_SESSION['role']       = $user['role'];
        header('Location: ' . ($user['role'] === 'admin'
            ? BASE_URL . '/admin/admin-dashboard.php'
            : BASE_URL . '/dashboard.php'));
        exit;
    }

    $_SESSION[$key]['count']++;
    $error = 'Invalid email or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - QueueStand</title>
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/components.css" />
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <main class="auth-main">
    <h2>Welcome Back</h2>
    <?php if ($error): ?>
      <p class="msg-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>" />
      <div>
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" required />
      </div>
      <div>
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required />
      </div>
      <div><button type="submit">Login</button></div>
    </form>
    <p class="auth-link">Don't have an account? <a href="register.php">Sign Up</a></p>
  </main>

  <script src="js/footer.js"></script>
</body>
</html>
