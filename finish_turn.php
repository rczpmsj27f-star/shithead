<?php
session_start();
require "db.php";

$game_id = (int)$_POST["game_id"];
$user_id = $_SESSION["user_id"];

// Load player row
$stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id=? AND user_id=?");
$stmt->execute([$game_id, $user_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

$player_id = $player["id"];

// Count cards
function countCards($pdo, $player_id, $type) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE owner_player_id=? AND location_type=?");
    $stmt->execute([$player_id, $type]);
    return $stmt->fetchColumn();
}

$hand = countCards($pdo, $player_id, "hand");
$faceUp = countCards($pdo, $player_id, "face_up");
$faceDown = countCards($pdo, $player_id, "face_down");

// If player still has cards → nothing to do
if ($hand + $faceUp + $faceDown > 0) {
    echo "OK";
    exit;
}

// Player is OUT
// Determine next finishing place
$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_players WHERE game_id=? AND place_finished IS NOT NULL");
$stmt->execute([$game_id]);
$finishedCount = $stmt->fetchColumn();

$place = $finishedCount + 1;

// Assign place
$stmt = $pdo->prepare("UPDATE game_players SET place_finished=? WHERE id=?");
$stmt->execute([$place, $player_id]);

// Check how many players remain
$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_players WHERE game_id=? AND place_finished IS NULL");
$stmt->execute([$game_id]);
$remaining = $stmt->fetchColumn();

// If only one player remains → they are Shithead
if ($remaining == 1) {

    // Find last player
    $stmt = $pdo->prepare("
        SELECT id, user_id FROM game_players
        WHERE game_id=? AND place_finished IS NULL
    ");
    $stmt->execute([$game_id]);
    $last = $stmt->fetch(PDO::FETCH_ASSOC);

    $lastPlace = $place + 1;

    // Mark last player
    $pdo->prepare("UPDATE game_players SET place_finished=?, is_shithead=1 WHERE id=?")
        ->execute([$lastPlace, $last["id"]]);

    // Write results to leaderboard
    $pdo->prepare("
        INSERT INTO results (user_id, place, is_shithead)
        SELECT user_id, place_finished, is_shithead
        FROM game_players WHERE game_id=?
    ")->execute([$game_id]);

    // Mark game finished
    $pdo->prepare("UPDATE games SET status='finished' WHERE id=?")->execute([$game_id]);

    echo "GAME_FINISHED";
    exit;
}

echo "OK";
exit;
