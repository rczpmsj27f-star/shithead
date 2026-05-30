<?php

function cardValue($rank) {
    $map = [
        "2" => 2, "3" => 3, "4" => 4, "5" => 5, "6" => 6,
        "7" => 7, "8" => 8, "9" => 9, "10" => 10,
        "J" => 11, "Q" => 12, "K" => 13, "A" => 14
    ];
    return $map[$rank] ?? 0; // Joker = 0
}

function canPlayCard($card, $topCard) {
    $rank = $card["rank"];

    // 10 can always be played
    if ($rank === "10") return true;

    // 2 can always be played
    if ($rank === "2") return true;

    // Joker — always playable
    if ($rank === "Joker") return true;

    // If no top card, any card can start
    if (!$topCard) return true;

    $topRank = $topCard["rank"];

    // 2 on top — any card can follow
    if ($topRank === "2") return true;

    // If top is 7 → must be lower than 7 (or a special card, handled above)
    if ($topRank === "7") {
        return cardValue($rank) < 7;
    }

    // Normal rule: must be >= top card
    return cardValue($rank) >= cardValue($topRank);
}

/**
 * Given a list of active (unfinished) players ordered by seat_position,
 * find the next player's user_id after the current player's seat.
 * This correctly handles gaps in seat numbers after players finish.
 */
function nextActivePlayer($pdo, $game_id, $currentUserId, $direction) {
    $stmt = $pdo->prepare("
        SELECT user_id, seat_position
        FROM game_players
        WHERE game_id = ? AND place_finished IS NULL
        ORDER BY seat_position ASC
    ");
    $stmt->execute([$game_id]);
    $activePlayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($activePlayers) <= 1) {
        // Only one player left — return them
        return $activePlayers[0]["user_id"] ?? null;
    }

    // Find index of current player in the active list
    $currentIndex = null;
    foreach ($activePlayers as $i => $p) {
        if ($p["user_id"] == $currentUserId) {
            $currentIndex = $i;
            break;
        }
    }

    if ($currentIndex === null) {
        // Current player just finished — find next by seat position
        // Use the first active player as fallback
        return $activePlayers[0]["user_id"];
    }

    $count = count($activePlayers);
    $nextIndex = (($currentIndex + $direction) % $count + $count) % $count;
    return $activePlayers[$nextIndex]["user_id"];
}

function checkMagicFour($pdo, $game_id) {
    $stmt = $pdo->prepare("
        SELECT rank FROM game_cards
        WHERE game_id = ? AND location_type = 'discard'
        ORDER BY id DESC LIMIT 4
    ");
    $stmt->execute([$game_id]);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($cards) < 4) return false;

    $r = array_column($cards, "rank");
    return count(array_unique($r)) === 1;
}

/**
 * Draw up to $count cards from the stack into the player's hand.
 */
function drawCards($pdo, $game_id, $player_id, $count) {
    if ($count <= 0) return;
    $stmt = $pdo->prepare("
        SELECT id FROM game_cards
        WHERE game_id = ? AND location_type = 'stack'
        ORDER BY position ASC
        LIMIT ?
    ");
    $stmt->execute([$game_id, $count]);
    $cards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cards as $c) {
        $pdo->prepare("
            UPDATE game_cards
            SET location_type = 'hand', owner_player_id = ?, position = NULL
            WHERE id = ?
        ")->execute([$player_id, $c["id"]]);
    }
}

/**
 * Move the entire discard pile into a player's hand (pick up pile).
 */
function pickUpDiscardPile($pdo, $game_id, $player_id) {
    $pdo->prepare("
        UPDATE game_cards
        SET location_type = 'hand', owner_player_id = ?
        WHERE game_id = ? AND location_type = 'discard'
    ")->execute([$player_id, $game_id]);
}

/**
 * Determine which location a player should be playing from:
 * hand → face_up → face_down, in that order.
 * Returns the required location_type string, or null if player has no cards.
 */
function requiredPlayLocation($pdo, $player_id) {
    $locations = ["hand", "face_up", "face_down"];
    foreach ($locations as $loc) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM game_cards
            WHERE owner_player_id = ? AND location_type = ?
        ");
        $stmt->execute([$player_id, $loc]);
        if ($stmt->fetchColumn() > 0) return $loc;
    }
    return null; // No cards — player is done
}

/**
 * Check if a player has finished (no cards left) and assign their place.
 * Returns true if the game is now over.
 */
function checkPlayerFinished($pdo, $game_id, $player_id) {
    $loc = requiredPlayLocation($pdo, $player_id);
    if ($loc !== null) return false; // Still has cards

    // Count already-finished players to determine place
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM game_players
        WHERE game_id = ? AND place_finished IS NOT NULL
    ");
    $stmt->execute([$game_id]);
    $place = $stmt->fetchColumn() + 1;

    $pdo->prepare("
        UPDATE game_players SET place_finished = ? WHERE id = ?
    ")->execute([$place, $player_id]);

    // Check remaining active players
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM game_players
        WHERE game_id = ? AND place_finished IS NULL
    ");
    $stmt->execute([$game_id]);
    $remaining = $stmt->fetchColumn();

    if ($remaining == 1) {
        // Last player is the Shithead
        $stmt = $pdo->prepare("
            SELECT id FROM game_players
            WHERE game_id = ? AND place_finished IS NULL
        ");
        $stmt->execute([$game_id]);
        $lastPlayer = $stmt->fetch(PDO::FETCH_ASSOC);

        $lastPlace = $place + 1;
        $pdo->prepare("
            UPDATE game_players SET place_finished = ?, is_shithead = 1 WHERE id = ?
        ")->execute([$lastPlace, $lastPlayer["id"]]);

        // Write results to leaderboard
        $pdo->prepare("
            INSERT INTO results (user_id, place, is_shithead)
            SELECT user_id, place_finished, is_shithead
            FROM game_players WHERE game_id = ?
        ")->execute([$game_id]);

        // Mark game finished
        $pdo->prepare("UPDATE games SET status = 'finished' WHERE id = ?")
            ->execute([$game_id]);

        return true; // Game over
    }

    return false;
}
