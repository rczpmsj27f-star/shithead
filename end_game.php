<?php
session_start();
require "db.php";

$game_id = (int)$_POST["game_id"];
$user_id = $_SESSION["user_id"];

// Load game
$stmt = $pdo->prepare("SELECT * FROM games WHERE id=?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if ($game["created_by"] != $user_id) {
    die("Only the host can end the game");
}

// Mark abandoned
$stmt = $pdo->prepare("UPDATE games SET status='abandoned' WHERE id=?");
$stmt->execute([$game_id]);

header("Location: create_game.php");
exit;
