<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title']    ?? '');
    $location = trim($_POST['location'] ?? '');
    $datetime = $_POST['required_datetime'] ?? '';
    $pay      = $_POST['pay_amount']    ?? '';
    $desc     = trim($_POST['description'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO jobs (poster_id, title, description, location, required_datetime, pay_amount) VALUES (?,?,?,?,?,?)");
    $stmt->execute([currentUser()['id'], $title, $desc, $location, $datetime, $pay]);
    header('Location: dashboard.php?toast=job_posted');
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Post A Queue Job | QueueStand</title>
    <link rel="stylesheet" href="css/styles.css" />
  </head>
  <body>
    <?php include 'includes/navbar.php'; ?>

    <main>
      <h1>Post a Queue Job</h1>
      <form method="POST">
        <div>
          <label>Queue Location / Service</label><br />
          <input type="text" name="title" placeholder="e.g Home Affairs Johannesburg" required /><br />
        </div>
        <div>
          <label>Date & Time Needed</label><br />
          <input type="datetime-local" name="required_datetime" required /><br />
        </div>
        <div>
          <label>Your Location (Suburb/City)</label><br />
          <input type="text" name="location" placeholder="e.g Sandton" required /><br />
        </div>
        <div>
          <label>Offer Price (ZAR)</label><br />
          <input type="number" name="pay_amount" min="50" placeholder="e.g 150" required /><br />
        </div>
        <div>
          <label>Additional Details</label><br />
          <textarea name="description" rows="6" placeholder="Documents needed"></textarea><br />
        </div>
        <div><button type="submit">Post Job</button></div>
      </form>
    </main>

    <script src="js/footer.js"></script>
  </body>
</html>
