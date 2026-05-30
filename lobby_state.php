<?php
session_start();
require "db.php";

$game_id = (int)$_GET["game_id"];

$stmt = $pdo->prepare("SELECT status FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_players WHERE game_id = ?");
$stmt->execute([$game_id]);
$count = (int)$stmt->fetchColumn();

echo json_encode([
    "status"       => $game["status"] ?? "unknown",
    "player_count" => $count,
]);
