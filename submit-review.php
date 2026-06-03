<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
guardRoute('user');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}
verifyCsrfToken();

$job_id = (int) ($_POST['job_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$poster_id = currentUser()['id'];

if ($rating < 1 || $rating > 5) {
    header('Location: dashboard.php?toast=review_invalid');
    exit;
}

// Verify: job must be completed, belong to this poster, and have an assigned stander
$completedJobQuery = $pdo->prepare("SELECT job_id, assigned_stander_id FROM jobs WHERE job_id=? AND poster_id=? AND status='completed'");
$completedJobQuery->execute([$job_id, $poster_id]);
$completedJob = $completedJobQuery->fetch();

if (!$completedJob || !$completedJob['assigned_stander_id']) {
    header('Location: dashboard.php?toast=review_invalid');
    exit;
}

// Prevent duplicate reviews per job
$exists = $pdo->prepare("SELECT 1 FROM reviews WHERE job_id=? AND rater_id=?");
$exists->execute([$job_id, $poster_id]);
if ($exists->fetch()) {
    header('Location: dashboard.php?toast=review_exists');
    exit;
}

$pdo->prepare("INSERT INTO reviews (job_id, rater_id, rated_id, rating, comment) VALUES (?,?,?,?,?)")
    ->execute([$job_id, $poster_id, $completedJob['assigned_stander_id'], $rating, $comment ?: null]);

// Notify stander
$pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)")
    ->execute([$completedJob['assigned_stander_id'], "You received a {$rating}-star review from a job poster."]);

header('Location: dashboard.php?toast=review_submitted');
exit;
