<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];
        $_SESSION['role']       = $user['role'];
        header('Location: admin-dashboard.php');
        exit;
    } else {
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
  </head>
  <body>
    <header>
      <div>
        <h1><a href="/ITECA_SumativeAssessment/admin/admin-login.php" style="color:#c3fcf1;text-decoration:none;font-size:1.4rem;">⚙ QueueStand Admin</a></h1>
        <nav><ul><li><a href="/ITECA_SumativeAssessment/login.php">← User Login</a></li></ul></nav>
      </div>
    </header>
    <main class="auth-main">
      <h2>Admin Login</h2>
      <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="POST">
        <div>
          <label>Email</label>
          <input type="email" name="email" required />
        </div>
        <div>
          <label>Password</label>
          <input type="password" name="password" required />
        </div>
        <div><button type="submit">Login</button></div>
      </form>
    </main>
  </body>
</html>
