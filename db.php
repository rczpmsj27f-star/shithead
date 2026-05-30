<?php

$host = "YOUR_HOSTNAME_HERE";
$dbname = "YOUR_DATABASE_NAME";
$user = "YOUR_DATABASE_USER";
$pass = "YOUR_DATABASE_PASSWORD";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (Exception $e) {
    die("DB ERROR: " . $e->getMessage());
}
