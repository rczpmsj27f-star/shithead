<?php
session_start();
require "db.php";
require "helpers.php";

if (!isset($_SESSION["user_id"])) {
    die("Not logged in");
}

$game_id = (int)$_POST["game_id"];
$user_id = $_SESSION["user_id"];

$pdo->beginTransaction();

try {
    // Load game
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ? FOR UPDATE");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game || $game["status"] !== "in_progress") {
        $pdo->rollBack();
        die("Game not found or not in progress");
    }

    if ($game["current_player"] != $user_id) {
        $pdo->rollBack();
        die("Not your turn");
    }

    // Load player row
    $stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id = ? AND user_id = ?");
    $stmt->execute([$game_id, $user_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    $player_id = $player["id"];

    // Verify the player actually cannot play any valid card from their required location
    $requiredLoc = requiredPlayLocation($pdo, $player_id);

    if ($requiredLoc === null) {
        $pdo->rollBack();
        die("You have no cards");
    }

    // Only allow pickup if playing from hand or face_up (face_down is blind — handled in play_card.php)
    if ($requiredLoc === "face_down") {
        $pdo->rollBack();
        die("Face-down cards must be played blind — you cannot choose to pick up");
    }

    // Check that no card in their required location can be played
    $stmt = $pdo->prepare("
        SELECT * FROM game_cards
        WHERE owner_player_id = ? AND location_type = ?
    ");
    $stmt->execute([$player_id, $requiredLoc]);
    $playerCards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Load top of discard
    $stmt = $pdo->prepare("
        SELECT * FROM game_cards
        WHERE game_id = ? AND location_type = 'discard'
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$game_id]);
    $top = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$top) {
        $pdo->rollBack();
        die("The discard pile is empty — play any card to start");
    }

    // Check if any card can be played — if so, player must play, not pick up
    foreach ($playerCards as $c) {
        if (canPlayCard($c, $top)) {
            $pdo->rollBack();
            die("You have a playable card — you must play it, not pick up");
        }
    }

    // Move entire discard pile to player's hand
    pickUpDiscardPile($pdo, $game_id, $player_id);

    // Also draw from stack if hand < 3 (after picking up, they have cards, but top up anyway)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM game_cards
        WHERE owner_player_id = ? AND location_type = 'hand'
    ");
    $stmt->execute([$player_id]);
    $handCount = $stmt->fetchColumn();

    if ($handCount < 3) {
        drawCards($pdo, $game_id, $player_id, 3 - $handCount);
    }

    // Advance to next player
    $nextUserId = nextActivePlayer($pdo, $game_id, $user_id, $game["direction"]);
    $pdo->prepare("UPDATE games SET current_player = ? WHERE id = ?")
        ->execute([$nextUserId, $game_id]);

    $pdo->commit();
    echo "OK";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}
