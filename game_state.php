<?php
session_start();
require "db.php";
require "helpers.php";

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$game_id = (int)$_GET["game_id"];
$user_id = $_SESSION["user_id"];

// Load game
$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    http_response_code(404);
    echo json_encode(["error" => "Game not found"]);
    exit;
}

// Load this player's row
$stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id = ? AND user_id = ?");
$stmt->execute([$game_id, $user_id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    http_response_code(403);
    echo json_encode(["error" => "You are not in this game"]);
    exit;
}

$player_id = $player["id"];

// Load MY hand (visible only to me)
$stmt = $pdo->prepare("SELECT * FROM game_cards WHERE owner_player_id = ? AND location_type = 'hand' ORDER BY rank");
$stmt->execute([$player_id]);
$hand = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load MY face-up cards
$stmt = $pdo->prepare("SELECT * FROM game_cards WHERE owner_player_id = ? AND location_type = 'face_up'");
$stmt->execute([$player_id]);
$myFaceUp = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load MY face-down count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE owner_player_id = ? AND location_type = 'face_down'");
$stmt->execute([$player_id]);
$myFaceDown = (int)$stmt->fetchColumn();

// What location must I play from?
$requiredLoc = requiredPlayLocation($pdo, $player_id);

// Load top of discard pile
$stmt = $pdo->prepare("
    SELECT * FROM game_cards
    WHERE game_id = ? AND location_type = 'discard'
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$game_id]);
$top = $stmt->fetch(PDO::FETCH_ASSOC);

// Stack count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE game_id = ? AND location_type = 'stack'");
$stmt->execute([$game_id]);
$stackCount = (int)$stmt->fetchColumn();

// Check if I can pick up (has a playable card from required loc, or pile is empty)
$canPickup = false;
if ($requiredLoc === "hand" || $requiredLoc === "face_up") {
    if ($top) {
        $stmt = $pdo->prepare("
            SELECT * FROM game_cards WHERE owner_player_id = ? AND location_type = ?
        ");
        $stmt->execute([$player_id, $requiredLoc]);
        $myCards = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $hasPlayable = false;
        foreach ($myCards as $c) {
            if (canPlayCard($c, $top)) { $hasPlayable = true; break; }
        }
        $canPickup = !$hasPlayable;
    }
}

// Load ALL players for display (with their card counts and face-up cards)
$stmt = $pdo->prepare("
    SELECT gp.*, u.username
    FROM game_players gp
    JOIN users u ON gp.user_id = u.id
    WHERE gp.game_id = ?
    ORDER BY gp.seat_position
");
$stmt->execute([$game_id]);
$allPlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$playersData = [];
foreach ($allPlayers as $p) {
    $pid = $p["id"];

    // Hand count (hidden — only show count)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE owner_player_id = ? AND location_type = 'hand'");
    $stmt->execute([$pid]);
    $handCount = (int)$stmt->fetchColumn();

    // Face-up cards (visible to all)
    $stmt = $pdo->prepare("SELECT * FROM game_cards WHERE owner_player_id = ? AND location_type = 'face_up'");
    $stmt->execute([$pid]);
    $faceUpCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Face-down count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM game_cards WHERE owner_player_id = ? AND location_type = 'face_down'");
    $stmt->execute([$pid]);
    $faceDownCount = (int)$stmt->fetchColumn();

    $playersData[] = [
        "id"             => $p["id"],
        "user_id"        => $p["user_id"],
        "username"       => $p["username"],
        "seat_position"  => $p["seat_position"],
        "place_finished" => $p["place_finished"],
        "is_shithead"    => $p["is_shithead"],
        "hand_count"     => $handCount,
        "face_up"        => $faceUpCards,
        "face_down_count"=> $faceDownCount,
        "is_current"     => ($p["user_id"] == $game["current_player"]),
    ];
}

echo json_encode([
    "game"         => $game,
    "hand"         => $hand,
    "my_face_up"   => $myFaceUp,
    "my_face_down" => $myFaceDown,
    "required_loc" => $requiredLoc, // "hand", "face_up", "face_down", or null
    "can_pickup"   => $canPickup,
    "discard_top"  => $top,
    "stack"        => $stackCount,
    "players"      => $playersData,
]);
