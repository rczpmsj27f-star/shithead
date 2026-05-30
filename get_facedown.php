<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    echo json_encode([]);
    exit;
}

$game_id = (int)$_GET["game_id"];
$user_id = $_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT id FROM game_players WHERE game_id = ? AND user_id = ?");
$stmt->execute([$game_id, $user_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    echo json_encode([]);
    exit;
}

// Return only the IDs (not rank/suit) so the client can't peek
$stmt = $pdo->prepare("
    SELECT id FROM game_cards
    WHERE owner_player_id = ? AND location_type = 'face_down'
");
$stmt->execute([$player["id"]]);
$cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($cards);
