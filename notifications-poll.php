<?php
require_once 'includes/auth.php';
require_once 'includes/db.php';
requireLogin();

header('Content-Type: application/json');

$stmt = $pdo->prepare("
    SELECT notification_id, message, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute([currentUser()['id']]);
$notifications = $stmt->fetchAll();

$unread = count(array_filter($notifications, fn($n) => !$n['is_read']));

echo json_encode(['unread' => $unread, 'notifications' => $notifications]);
