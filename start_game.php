<?php
session_start();
require "db.php";
require "helpers.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$game_id = (int)$_POST["game_id"];
$user_id = $_SESSION["user_id"];

// Load game
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    die("Game not found");
}

// Only the host can start the game
if ($game["created_by"] != $user_id) {
    die("Only the host can start the game");
}

if ($game["status"] !== "lobby") {
    die("Game has already started");
}

// Load players
$stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id = ? ORDER BY seat_position");
$stmt->execute([$game_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($players) < 2) {
    die("Need at least 2 players to start");
}

// Build deck: standard 52 + 2 jokers
$ranks = ["2","3","4","5","6","7","8","9","10","J","Q","K","A"];
$suits = ["C","D","H","S"];
$deck  = [];

foreach ($suits as $s) {
    foreach ($ranks as $r) {
        $deck[] = ["rank" => $r, "suit" => $s];
    }
}

$deck[] = ["rank" => "Joker", "suit" => null];
$deck[] = ["rank" => "Joker", "suit" => null];

shuffle($deck);

// Deal 9 cards per player: 3 face-down, 3 face-up, 3 hand
foreach ($players as $p) {
    $pid = $p["id"];

    for ($i = 0; $i < 3; $i++) {
        $c = array_pop($deck);
        insertCard($pdo, $game_id, $pid, "face_down", $c);
    }
    for ($i = 0; $i < 3; $i++) {
        $c = array_pop($deck);
        insertCard($pdo, $game_id, $pid, "face_up", $c);
    }
    for ($i = 0; $i < 3; $i++) {
        $c = array_pop($deck);
        insertCard($pdo, $game_id, $pid, "hand", $c);
    }
}

// Remaining cards → stack (position determines draw order)
$pos = 0;
foreach ($deck as $c) {
    insertCard($pdo, $game_id, null, "stack", $c, $pos++);
}

// Set game active; first player in seat order goes first
$stmt = $pdo->prepare("UPDATE games SET status = 'in_progress', current_player = ? WHERE id = ?");
$stmt->execute([$players[0]["user_id"], $game_id]);

header("Location: game.php?game_id=$game_id");
exit;

function insertCard($pdo, $game_id, $owner, $loc, $card, $pos = null) {
    $stmt = $pdo->prepare("
        INSERT INTO game_cards (game_id, rank, suit, owner_player_id, location_type, position)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$game_id, $card["rank"], $card["suit"], $owner, $loc, $pos]);
}
