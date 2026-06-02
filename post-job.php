<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
guardRoute('user');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $title    = trim($_POST['title']    ?? '');
    $location = trim($_POST['location'] ?? '');
    $datetime = $_POST['required_datetime'] ?? '';
    $pay      = (float)($_POST['pay_amount'] ?? 0);
    $desc     = trim($_POST['description'] ?? '');

    if ($title === '' || $location === '') {
        $error = 'Title and location are required.';
    } elseif ($pay < 50) {
        $error = 'Minimum offer price is R50.';
    } elseif (strtotime($datetime) === false || strtotime($datetime) <= time()) {
        $error = 'Please select a future date and time.';
    } else {
        $pdo->prepare("INSERT INTO jobs (poster_id, title, description, location, required_datetime, pay_amount) VALUES (?,?,?,?,?,?)")
            ->execute([currentUser()['id'], $title, $desc, $location, $datetime, $pay]);
        header('Location: dashboard.php?toast=job_posted');
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Post A Queue Job | QueueStand</title>
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/components.css" />
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <main>
    <h1>Post a Queue Job</h1>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>" />
      <?php if (!empty($error)): ?>
        <p class="msg-error"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
      <div>
        <label>Queue Location / Service</label>
        <input type="text" name="title" placeholder="e.g. Home Affairs Johannesburg" required />
      </div>
      <div>
        <label>Date &amp; Time Needed</label>
        <input type="datetime-local" name="required_datetime" required />
      </div>
      <div>
        <label>Your Location (Suburb/City)</label>
        <input type="text" name="location" placeholder="e.g. Sandton" required />
      </div>
      <div>
        <label>Offer Price (ZAR)</label>
        <input type="number" name="pay_amount" min="50" placeholder="e.g. 150" required />
      </div>
      <div>
        <label>Additional Details</label>
        <textarea name="description" rows="6" placeholder="Documents needed, special instructions…"></textarea>
      </div>
      <div><button type="submit">Post Job</button></div>
    </form>
  </main>

  <script src="js/footer.js"></script>
</body>
</html>
