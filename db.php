<?php

$host = "YOUR_HOSTNAME_HERE";
$dbname = "u983097270_shithead";
$user = "u983097270_shithead";
$pass = "Farrell0405!!";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (Exception $e) {
    die("DB ERROR: " . $e->getMessage());
}
