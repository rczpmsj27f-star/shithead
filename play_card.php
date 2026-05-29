<?php
session_start();
require "db.php";
require "helpers.php";

$game_id = (int)$_POST["game_id"];
$card_id = (int)$_POST["card_id"];
$user_id = $_SESSION["user_id"];

// Load game
$stmt = $pdo->prepare("SELECT * FROM games WHERE id=?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if ($game["current_player"] != $user_id) {
    die("Not your turn");
}

// Load player row
$stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id=? AND user_id=?");
$stmt->execute([$game_id, $user_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

// Load card
$stmt = $pdo->prepare("SELECT * FROM game_cards WHERE id=? AND owner_player_id=?");
$stmt->execute([$card_id, $player["id"]]);
$card = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$card) die("Invalid card");

// Load top of discard
$stmt = $pdo->prepare("
    SELECT * FROM game_cards
    WHERE game_id=? AND location_type='discard'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$game_id]);
$top = $stmt->fetch(PDO::FETCH_ASSOC);

// Validate play
if (!canPlayCard($card, $top)) {
    die("Cannot play this card");
}

// Move card to discard
$stmt = $pdo->prepare("UPDATE game_cards SET location_type='discard', owner_player_id=NULL WHERE id=?");
$stmt->execute([$card_id]);

// SPECIAL RULES
$rank = $card["rank"];

// 10 → burn pile
if ($rank === "10") {
    $pdo->prepare("UPDATE game_cards SET location_type='removed' WHERE game_id=? AND location_type='discard'")
        ->execute([$game_id]);

    // Same player plays again
    echo "OK";
    exit;
}

// Joker → reverse direction
if ($rank === "Joker") {
    $newDir = $game["direction"] * -1;
    $pdo->prepare("UPDATE games SET direction=? WHERE id=?")->execute([$newDir, $game_id]);
}

// Magic 4
if (checkMagicFour($pdo, $game_id)) {
    $pdo->prepare("UPDATE game_cards SET location_type='removed' WHERE game_id=? AND location_type='discard'")
        ->execute([$game_id]);
}

// Draw cards if hand < 3
$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE owner_player_id=? AND location_type='hand'");
$stmt->execute([$player["id"]]);
$handCount = $stmt->fetchColumn();

if ($handCount < 3) {
    drawCards($pdo, $game_id, $player["id"], 3 - $handCount);
}

// Next player
$stmt = $pdo->prepare("SELECT seat_position FROM game_players WHERE id=?");
$stmt->execute([$player["id"]]);
$seat = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_players WHERE game_id=? AND place_finished IS NULL");
$stmt->execute([$game_id]);
$alive = $stmt->fetchColumn();

$nextSeat = nextSeat($seat, $game["direction"], $alive);

// Find next player by seat
$stmt = $pdo->prepare("
    SELECT user_id FROM game_players
    WHERE game_id=? AND seat_position=? AND place_finished IS NULL
");
$stmt->execute([$game_id, $nextSeat]);
$nextUser = $stmt->fetchColumn();

$pdo->prepare("UPDATE games SET current_player=? WHERE id=?")->execute([$nextUser, $game_id]);

echo "OK";
exit;

function drawCards($pdo, $game_id, $player_id, $count) {
    $stmt = $pdo->prepare("
        SELECT id FROM game_cards
        WHERE game_id=? AND location_type='stack'
        ORDER BY position ASC LIMIT $count
    ");
    $stmt->execute([$game_id]);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cards as $c) {
        $pdo->prepare("UPDATE game_cards SET location_type='hand', owner_player_id=?, position=NULL WHERE id=?")
            ->execute([$player_id, $c["id"]]);
    }
}
