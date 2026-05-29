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

    // Joker — treat as always playable
    if ($rank === "Joker") return true;

    // If no top card, any card can start
    if (!$topCard) return true;

    $topRank = $topCard["rank"];

    // If top is 7 → must be lower than 7
    if ($topRank === "7") {
        return cardValue($rank) < 7;
    }

    // Normal rule: must be >= top card
    return cardValue($rank) >= cardValue($topRank);
}

function nextSeat($currentSeat, $direction, $playerCount) {
    $next = ($currentSeat + $direction) % $playerCount;
    if ($next < 0) $next += $playerCount;
    return $next;
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
