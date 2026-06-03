<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
guardRoute('user');

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([currentUser()['id']]);
$user = $stmt->fetch();

// Fetch this user's reviews as a stander
$revStmt = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at,
           CONCAT(u.first_name, ' ', u.last_name) AS rater_name
    FROM reviews r
    JOIN users u ON r.rater_id = u.user_id
    WHERE r.rated_id = ?
    ORDER BY r.created_at DESC
");
$revStmt->execute([$user['user_id']]);
$myReviews = $revStmt->fetchAll();
$avgRating = count($myReviews)
    ? round(array_sum(array_column($myReviews, 'rating')) / count($myReviews), 1)
    : null;

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $phone      = trim($_POST['phone']      ?? '');
    $city       = trim($_POST['city']       ?? '');

    if ($first_name === '' || $last_name === '') {
        $error = 'First and last name are required.';
    } elseif ($phone !== '' && !preg_match('/^0\d{9}$/', $phone)) {
        $error = 'Phone must be a valid 10-digit SA number.';
    } else {
        $upd = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, city=? WHERE user_id=?");
        $upd->execute([$first_name, $last_name, $phone, $city, $user['user_id']]);

        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name']  = $last_name;
        $success = 'Profile updated.';
        $user = array_merge($user, compact('first_name', 'last_name', 'phone', 'city'));
    }
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
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>" />
        <?php if ($error): ?><p class="msg-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
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

      <?php if (!empty($myReviews)): ?>
        <div class="profile-reviews">
          <h3>My Reviews as a Stander
            <span class="avg-rating-badge">
              <?= str_repeat('★', (int)round($avgRating)) ?><?= str_repeat('☆', 5 - (int)round($avgRating)) ?>
              <?= $avgRating ?>/5 (<?= count($myReviews) ?> review<?= count($myReviews) !== 1 ? 's' : '' ?>)
            </span>
          </h3>
          <?php foreach ($myReviews as $rev): ?>
            <div class="review-item">
              <div class="review-stars">
                <?= str_repeat('★', $rev['rating']) ?><?= str_repeat('☆', 5 - $rev['rating']) ?>
              </div>
              <p class="review-comment"><?= $rev['comment'] ? htmlspecialchars($rev['comment']) : '<em>No comment</em>' ?></p>
              <p class="review-meta">— <?= htmlspecialchars($rev['rater_name']) ?> · <?= date('d M Y', strtotime($rev['created_at'])) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($user['role'] === 'user'): ?>
        <p style="margin-top:1.5rem;color:#888">No reviews yet as a stander.</p>
      <?php endif; ?>
    </main>

    <script src="js/footer.js"></script>
  </body>
</html>
