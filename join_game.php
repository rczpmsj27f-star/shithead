<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $code = trim($_POST["game_code"]);

    $stmt = $pdo->prepare("SELECT * FROM games WHERE game_code = ? AND status = 'lobby'");
    $stmt->execute([$code]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        die("Game not found or already started");
    }

    $game_id = $game["id"];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM game_players WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $count = $stmt->fetchColumn();

    if ($count >= $game["max_players"]) {
        die("Game is full");
    }

    $stmt = $pdo->prepare("INSERT INTO game_players (game_id, user_id, seat_position) VALUES (?, ?, ?)");
    $stmt->execute([$game_id, $_SESSION["user_id"], $count]);

    header("Location: lobby.php?game_id=$game_id");
    exit;
}
?>

<form method="post">
    <label>Enter Game Code</label>
    <input type="text" name="game_code" maxlength="4" required>

    <button type="submit">Join Game</button>
</form>
