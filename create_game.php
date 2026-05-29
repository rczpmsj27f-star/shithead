<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

function generateGameCode() {
    $chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
    $code = "";
    for ($i = 0; $i < 4; $i++) {
        $code .= $chars[random_int(0, strlen($chars)-1)];
    }
    return $code;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $max_players = (int)$_POST["max_players"];
    $code = generateGameCode();

    $stmt = $pdo->prepare("INSERT INTO games (game_code, created_by, max_players) VALUES (?, ?, ?)");
    $stmt->execute([$code, $_SESSION["user_id"], $max_players]);

    $game_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO game_players (game_id, user_id, seat_position) VALUES (?, ?, 0)");
    $stmt->execute([$game_id, $_SESSION["user_id"]]);

    header("Location: lobby.php?game_id=$game_id");
    exit;
}
?>

<h2>Create Game</h2>

<form method="post">
    <label>Number of Players</label>
    <select name="max_players">
        <option>2</option>
        <option>3</option>
        <option>4</option>
        <option>5</option>
    </select>

    <button type="submit">Create</button>
</form>
