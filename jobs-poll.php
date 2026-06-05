<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
guardRoute('user');

header('Content-Type: application/json');

$user = currentUser();
$mode = $_GET['mode'] ?? 'poster';

if ($mode === 'stander') {
    $stmt = $pdo->prepare("
        SELECT ja.job_id, ja.status AS app_status, j.status AS job_status
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.job_id
        WHERE ja.stander_id = ?
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT j.job_id, j.status,
               COUNT(ja.application_id) AS pending_count,
               s.user_id AS stander_id,
               s.first_name AS s_first, s.last_name AS s_last,
               s.email AS s_email, s.phone AS s_phone, s.city AS s_city,
               ROUND(AVG(r.rating),1) AS avg_rating, COUNT(DISTINCT r.review_id) AS review_count
        FROM jobs j
        LEFT JOIN job_applications ja ON ja.job_id = j.job_id AND ja.status = 'pending'
        LEFT JOIN users s ON j.assigned_stander_id = s.user_id
        LEFT JOIN reviews r ON r.rated_id = s.user_id
        WHERE j.poster_id = ?
        GROUP BY j.job_id
    ");
}

$stmt->execute([$user['id']]);
echo json_encode($stmt->fetchAll());
