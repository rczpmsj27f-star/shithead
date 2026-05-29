<?php
session_start();
require "db.php";
require "helpers.php";

$game_id = (int)$_POST["game_id"];

// Load players
$stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id = ? ORDER BY seat_position");
$stmt->execute([$game_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

$playerCount = count($players);

// Build deck
$ranks = ["2","3","4","5","6","7","8","9","10","J","Q","K","A"];
$suits = ["C","D","H","S"];
$deck = [];

foreach ($suits as $s) {
    foreach ($ranks as $r) {
        $deck[] = ["rank"=>$r, "suit"=>$s];
    }
}

$deck[] = ["rank"=>"Joker","suit"=>null];
$deck[] = ["rank"=>"Joker","suit"=>null];

shuffle($deck);

// Deal cards
foreach ($players as $p) {
    $pid = $p["id"];

    // 3 face-down
    for ($i=0; $i<3; $i++) {
        $c = array_pop($deck);
        insertCard($pdo, $game_id, $pid, "face_down", $c);
    }

    // 3 face-up
    for ($i=0; $i<3; $i++) {
        $c = array_pop($deck);
        insertCard($pdo, $game_id, $pid, "face_up", $c);
    }

    // 3 hand
    for ($i=0; $i<3; $i++) {
        $c = array_pop($deck);
        insertCard($pdo, $game_id, $pid, "hand", $c);
    }
}

// Remaining cards → stack
$pos = 0;
foreach ($deck as $c) {
    insertCard($pdo, $game_id, null, "stack", $c, $pos++);
}

// Set game active
$stmt = $pdo->prepare("UPDATE games SET status='in_progress', current_player=? WHERE id=?");
$stmt->execute([$players[0]["user_id"], $game_id]);

header("Location: game.php?game_id=$game_id");
exit;

function insertCard($pdo, $game_id, $owner, $loc, $card, $pos=null) {
    $stmt = $pdo->prepare("
        INSERT INTO game_cards (game_id, rank, suit, owner_player_id, location_type, position)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$game_id, $card["rank"], $card["suit"], $owner, $loc, $pos]);
}
