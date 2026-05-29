<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $email      = trim($_POST['email']      ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $role       = 'user';
    $password   = $_POST['password']        ?? '';
    $confirm    = $_POST['confirm']         ?? '';
    $user_id    = trim($_POST['user_id']    ?? ''); // SA ID number (13 digits)

    if ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (!preg_match('/^\d{13}$/', $user_id)) {
        $error = 'ID number must be 13 digits.';
    } else {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR user_id = ?");
        $check->execute([$email, $user_id]);
        if ($check->fetch()) {
            $error = 'An account with this email or ID already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (user_id, email, password, first_name, last_name, phone, role) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$user_id, $email, password_hash($password, PASSWORD_DEFAULT), $first_name, $last_name, $phone, $role]);
            header('Location: login.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up - QueueStand</title>
    <link rel="stylesheet" href="css/styles.css" />
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>

    <main class="auth-main">
      <h2>Create an Account</h2>
      <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
      <form method="POST">
        <div>
          <label>SA ID Number</label>
          <input type="text" name="user_id" placeholder="13-digit ID number" maxlength="13" required />
        </div>
        <div>
          <label>First Name</label>
          <input type="text" name="first_name" placeholder="First name" required />
        </div>
        <div>
          <label>Last Name</label>
          <input type="text" name="last_name" placeholder="Last name" required />
        </div>
        <div>
          <label>Email</label>
          <input type="email" name="email" placeholder="you@example.com" required />
        </div>
        <div>
          <label>Phone</label>
          <input type="text" name="phone" placeholder="0812345678" maxlength="10" />
        </div>
        <div>
          <label>Password</label>
          <input type="password" name="password" placeholder="Create a password" required />
        </div>
        <div>
          <label>Confirm Password</label>
          <input type="password" name="confirm" placeholder="Repeat your password" required />
        </div>
        <div><button type="submit">Sign Up</button></div>
      </form>
      <p class="auth-link">Already have an account? <a href="login.php">Login</a></p>
    </main>

    <script src="js/footer.js"></script>
  </body>
</html>
