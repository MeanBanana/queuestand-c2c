<?php
require_once 'includes/db.php';

// Whitelist PayFast IPs (sandbox + production)
$validIps = ['197.97.145.144','41.74.179.194','196.33.227.144','196.33.227.145'];
if (!in_array($_SERVER['REMOTE_ADDR'], $validIps, true)) {
    http_response_code(403); exit;
}

$passphrase = '';

// 1. Validate signature
$post = $_POST;
$received_sig = $post['signature'] ?? '';
unset($post['signature']);
$parts = [];
foreach ($post as $key => $val) {
    $parts[] = $key . '=' . urlencode(stripslashes($val));
}
if ($passphrase !== '') $parts[] = 'passphrase=' . urlencode($passphrase);
if (md5(implode('&', $parts)) !== $received_sig) { http_response_code(400); exit; }

// 2. Verify with PayFast server
$ch = curl_init('https://sandbox.payfast.co.za/eng/query/validate');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($_POST),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$result = curl_exec($ch);
curl_close($ch);
if (trim($result) !== 'VALID') { http_response_code(400); exit; }

// 3. Update DB only if payment is complete
if (($_POST['payment_status'] ?? '') !== 'COMPLETE') exit;

$job_id     = (int)($_POST['m_payment_id'] ?? 0);
$amount     = (float)($_POST['amount_gross'] ?? 0);
$gateway_tx = $_POST['pf_payment_id'] ?? null;

try {
    $pdo->prepare("INSERT INTO transactions (job_id, amount, status, payment_gateway, gateway_tx_id) VALUES (?, ?, 'paid', 'PayFast', ?)")
        ->execute([$job_id, $amount, $gateway_tx]);

    $pdo->prepare("UPDATE jobs SET status = 'in_progress' WHERE job_id = ?")
        ->execute([$job_id]);

    $stander = $pdo->prepare("SELECT assigned_stander_id, title FROM jobs WHERE job_id = ?");
    $stander->execute([$job_id]);
    $job = $stander->fetch();
    if ($job && $job['assigned_stander_id']) {
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")
            ->execute([$job['assigned_stander_id'], "Payment received for job '{$job['title']}'. The job is now in progress."]);
    }
} catch (Exception $e) {
    http_response_code(500); exit;
}

http_response_code(200);
