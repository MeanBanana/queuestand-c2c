<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
guardRoute('user');

header('Content-Type: application/json');

$stander_id = (int)($_GET['stander_id'] ?? 0);
if (!$stander_id) { echo '[]'; exit; }

$stmt = $pdo->prepare("
    SELECT r.rating, r.comment, r.created_at,
           u.first_name, u.last_name,
           CONCAT(u.first_name, ' ', u.last_name) AS rater_name
    FROM reviews r
    JOIN users u ON r.rater_id = u.user_id
    WHERE r.rated_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$stander_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
