<?php
/**
 * finish_turn.php
 *
 * Player elimination is now handled directly inside play_card.php via
 * checkPlayerFinished() in helpers.php. This file is kept for
 * backwards compatibility but is no longer needed as a standalone endpoint.
 *
 * If called directly, redirect to the game.
 */
session_start();

$game_id = (int)($_POST["game_id"] ?? $_GET["game_id"] ?? 0);

if ($game_id) {
    header("Location: game.php?game_id=$game_id");
} else {
    header("Location: create_game.php");
}
exit;
