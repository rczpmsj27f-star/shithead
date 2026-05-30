<?php
session_start();
require "db.php";
require "helpers.php";

if (!isset($_SESSION["user_id"])) {
    die("Not logged in");
}

$game_id  = (int)$_POST["game_id"];
$card_id  = (int)$_POST["card_id"];
$user_id  = $_SESSION["user_id"];

$pdo->beginTransaction();

try {
    // Load game
    $stmt = $pdo->prepare("SELECT * FROM games WHERE id = ? FOR UPDATE");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        $pdo->rollBack();
        die("Game not found");
    }

    if ($game["current_player"] != $user_id) {
        $pdo->rollBack();
        die("Not your turn");
    }

    if ($game["status"] !== "in_progress") {
        $pdo->rollBack();
        die("Game is not in progress");
    }

    // Load player row
    $stmt = $pdo->prepare("SELECT * FROM game_players WHERE game_id = ? AND user_id = ?");
    $stmt->execute([$game_id, $user_id]);
    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        $pdo->rollBack();
        die("Player not found");
    }

    $player_id = $player["id"];

    // Determine which location this player must play from
    $requiredLoc = requiredPlayLocation($pdo, $player_id);

    if ($requiredLoc === null) {
        $pdo->rollBack();
        die("You have no cards to play");
    }

    // Load the card being played — must belong to this player AND be in the required location
    $stmt = $pdo->prepare("
        SELECT * FROM game_cards
        WHERE id = ? AND owner_player_id = ? AND location_type = ?
    ");
    $stmt->execute([$card_id, $player_id, $requiredLoc]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$card) {
        $pdo->rollBack();
        die("Invalid card — you must play from your " . str_replace("_", "-", $requiredLoc) . " cards");
    }

    // Load top of discard pile
    $stmt = $pdo->prepare("
        SELECT * FROM game_cards
        WHERE game_id = ? AND location_type = 'discard'
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([$game_id]);
    $top = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- FACE-DOWN BLIND PLAY ---
    // Face-down cards are played without seeing them — card is already loaded above.
    // After playing, check if it beats the pile. If not, player picks up.
    $playedBlind = ($requiredLoc === "face_down");

    // Move card to discard
    $pdo->prepare("
        UPDATE game_cards
        SET location_type = 'discard', owner_player_id = NULL
        WHERE id = ?
    ")->execute([$card_id]);

    // For face-down blind plays: check validity AFTER placing on pile
    if ($playedBlind) {
        if (!canPlayCard($card, $top)) {
            // Card can't beat the pile — player picks up everything
            pickUpDiscardPile($pdo, $game_id, $player_id);
            $pdo->commit();
            echo "PICKUP"; // Tell the client the player had to pick up
            exit;
        }
        // If it CAN beat the pile, fall through to normal special card handling
    } else {
        // For hand/face-up plays: validate BEFORE (already loaded with location check above)
        if (!canPlayCard($card, $top)) {
            $pdo->rollBack();
            die("Cannot play this card — it does not beat the current pile");
        }
    }

    $rank = $card["rank"];
    $sameTurn = false; // Whether current player gets another go

    // --- SPECIAL CARD RULES ---

    // 10 → burn discard pile, same player plays again
    if ($rank === "10") {
        $pdo->prepare("
            UPDATE game_cards SET location_type = 'removed'
            WHERE game_id = ? AND location_type = 'discard'
        ")->execute([$game_id]);
        $sameTurn = true;
    }

    // Joker → reverse direction
    if ($rank === "Joker") {
        $newDir = $game["direction"] * -1;
        $pdo->prepare("UPDATE games SET direction = ? WHERE id = ?")
            ->execute([$newDir, $game_id]);
        $game["direction"] = $newDir;
    }

    // Magic 4 → burn discard pile, same player plays again
    if (!$sameTurn && checkMagicFour($pdo, $game_id)) {
        $pdo->prepare("
            UPDATE game_cards SET location_type = 'removed'
            WHERE game_id = ? AND location_type = 'discard'
        ")->execute([$game_id]);
        $sameTurn = true;
    }

    // --- DRAW BACK UP TO 3 HAND CARDS (while stack has cards) ---
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM game_cards
        WHERE owner_player_id = ? AND location_type = 'hand'
    ");
    $stmt->execute([$player_id]);
    $handCount = $stmt->fetchColumn();

    if ($handCount < 3) {
        drawCards($pdo, $game_id, $player_id, 3 - $handCount);
    }

    // --- CHECK IF CURRENT PLAYER IS FINISHED ---
    $gameOver = checkPlayerFinished($pdo, $game_id, $player_id);

    if ($gameOver) {
        $pdo->commit();
        echo "GAME_FINISHED";
        exit;
    }

    // --- ADVANCE TURN ---
    if (!$sameTurn) {
        $nextUserId = nextActivePlayer($pdo, $game_id, $user_id, $game["direction"]);
        $pdo->prepare("UPDATE games SET current_player = ? WHERE id = ?")
            ->execute([$nextUserId, $game_id]);
    }
    // If $sameTurn, current_player stays the same

    $pdo->commit();
    echo "OK";

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}
