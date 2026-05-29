<?php
session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user["password_hash"])) {
        die("Invalid login");
    }

    $_SESSION["user_id"] = $user["id"];
    $_SESSION["username"] = $username;

    header("Location: create_game.php");
    exit;
}
?>

<form method="post">
    <label>Player Name</label>
    <input type="text" name="username" required>

    <label>Password / PIN</label>
    <input type="password" name="password" required>

    <button type="submit">Login</button>
</form>
