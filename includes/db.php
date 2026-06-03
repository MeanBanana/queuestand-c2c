<?php
$host = 'sql208.infinityfree.com'; // your InfinityFree DB host
$db   = 'if0_42081203_queue_stand'; // your InfinityFree DB name
$user = 'if0_42081203';              // your InfinityFree DB username
$pass = 'Ehvgta5k';          // your InfinityFree DB password

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
]);