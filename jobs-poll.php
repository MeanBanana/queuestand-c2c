<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

header('Content-Type: application/json');

$user     = currentUser();
$isPoster = $user['role'] === 'job_poster';

if ($isPoster) {
    // Return pending applicant counts per job + job statuses
    $stmt = $pdo->prepare("
        SELECT j.job_id, j.status,
               COUNT(ja.application_id) AS pending_count
        FROM jobs j
        LEFT JOIN job_applications ja ON ja.job_id = j.job_id AND ja.status = 'pending'
        WHERE j.poster_id = ?
        GROUP BY j.job_id
    ");
} else {
    // Return application statuses for this stander
    $stmt = $pdo->prepare("
        SELECT ja.job_id, ja.status AS app_status, j.status AS job_status
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.job_id
        WHERE ja.stander_id = ?
    ");
}

$stmt->execute([$user['id']]);
echo json_encode($stmt->fetchAll());
