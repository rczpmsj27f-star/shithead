<?php
session_start();
require "db.php";

$game_id = (int)$_GET["game_id"];
$user_id = $_SESSION["user_id"];

// Load game
$stmt = $pdo->prepare("SELECT * FROM games WHERE id=?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

// Load player row
$stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id=? AND user_id=?");
$stmt->execute([$game_id, $user_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

// Load hand
$stmt = $pdo->prepare("SELECT * FROM game_cards WHERE owner_player_id=? AND location_type='hand'");
$stmt->execute([$player["id"]]);
$hand = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load face-up
$stmt = $pdo->prepare("SELECT * FROM game_cards WHERE owner_player_id=? AND location_type='face_up'");
$stmt->execute([$player["id"]]);
$faceUp = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load face-down count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE owner_player_id=? AND location_type='face_down'");
$stmt->execute([$player["id"]]);
$faceDown = $stmt->fetchColumn();

// Load discard top
$stmt = $pdo->prepare("
    SELECT * FROM game_cards
    WHERE game_id=? AND location_type='discard'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$game_id]);
$top = $stmt->fetch(PDO::FETCH_ASSOC);

// Stack count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE game_id=? AND location_type='stack'");
$stmt->execute([$game_id]);
$stack = $stmt->fetchColumn();

echo json_encode([
    "game" => $game,
    "hand" => $hand,
    "face_up" => $faceUp,
    "face_down" => $faceDown,
    "discard_top" => $top,
    "stack" => $stack
]);
