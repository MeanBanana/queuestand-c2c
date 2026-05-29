<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isLoggedIn()) {
    header('Location: ' . ($_SESSION['role'] === 'admin'
        ? '/ITECA_SumativeAssessment/admin/admin-dashboard.php'
        : '/ITECA_SumativeAssessment/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];
        $_SESSION['role']       = $user['role'];

        header('Location: ' . ($user['role'] === 'admin'
            ? '/ITECA_SumativeAssessment/admin/admin-dashboard.php'
            : 'dashboard.php'));
        exit;
    }

    $error = 'Invalid email or password.';
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
