<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'user') {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

$job_id = (int) ($_GET['job_id'] ?? 0);
if (!$job_id) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT j.*, u.first_name, u.last_name, u.email
    FROM jobs j
    JOIN users u ON j.poster_id = u.user_id
    WHERE j.job_id = ? AND j.poster_id = ? AND j.status = 'assigned'
");
$stmt->execute([$job_id, $_SESSION['user_id']]);
$job = $stmt->fetch();

if (!$job) {
    header('Location: dashboard.php');
    exit;
}

$merchant_id  = getenv('PAYFAST_MERCHANT_ID')  ?: '10048867';
$merchant_key = getenv('PAYFAST_MERCHANT_KEY') ?: 'd8ppo20k59w3c';
$passphrase   = getenv('PAYFAST_PASSPHRASE')   ?: '';

$data = [
    'merchant_id'   => $merchant_id,
    'merchant_key'  => $merchant_key,
    'return_url'    => BASE_URL . "/payment-success.php?job_id=$job_id",
    'cancel_url'    => BASE_URL . "/payment-cancel.php?job_id=$job_id",
    'notify_url'    => IS_LOCAL ? '' : BASE_URL . '/payment-notify.php',
    'name_first'    => trim($job['first_name']),
    'name_last'     => trim($job['last_name']),
    'email_address' => trim($job['email']),
    'm_payment_id'  => (string) $job_id,
    'amount'        => number_format((float) $job['pay_amount'], 2, '.', ''),
    'item_name'     => 'QueueStand Job ' . $job_id,
];

function generatePayFastSignature(array $data, string $passphrase = ''): string
{
    $parts = [];
    foreach ($data as $key => $val) {
        if ($val !== '') {
            $parts[] = $key . '=' . urlencode($val);
        }
    }
    if ($passphrase !== '') {
        $parts[] = 'passphrase=' . urlencode($passphrase);
    }
    return md5(implode('&', $parts));
}

$data['signature'] = generatePayFastSignature($data, $passphrase);

$txStmt = $pdo->prepare("SELECT 1 FROM transactions WHERE job_id = ? AND status = 'paid'");
$txStmt->execute([$job_id]);
$alreadyPaid = (bool) $txStmt->fetch();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Checkout - QueueStand</title>
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="css/components.css" />
  <link rel="stylesheet" href="css/dashboard.css" />
</head>
<body>
  <?php include 'includes/navbar.php'; ?>

  <main class="auth-main">
    <h2>Checkout</h2>
    <div class="card">
      <h3><?= htmlspecialchars($job['title']) ?></h3>
      <p><?= htmlspecialchars($job['location']) ?></p>
      <p class="dash-pay">R <?= number_format($job['pay_amount'], 2) ?></p>

      <?php if ($alreadyPaid): ?>
        <p class="msg-success">✅ Payment complete. This job is now in progress.</p>
        <a href="dashboard.php" class="btn-primary">Back to Dashboard</a>
      <?php else: ?>
        <form action="https://sandbox.payfast.co.za/eng/process" method="post">
          <?php foreach ($data as $key => $value): ?>
            <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>">
          <?php endforeach; ?>
          <button type="submit" class="btn-primary">💳 Pay Now</button>
        </form>
      <?php endif; ?>
    </div>
  </main>

  <script src="js/footer.js"></script>
</body>
</html>
