<?php
$host = 'localhost';
$db = 'queue_stander';
$user = 'root';
$pass = '';

$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);