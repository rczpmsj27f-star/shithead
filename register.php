<?php
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    if ($username === "" || $password === "") {
        die("All fields required");
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        die("Username already exists");
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
    $stmt->execute([$username, $hash]);

    header("Location: login.php");
    exit;
}
?>

<form method="post">
    <label>Player Name</label>
    <input type="text" name="username" required>

    <label>Password / PIN</label>
    <input type="password" name="password" required>

    <button type="submit">Register</button>
</form>
