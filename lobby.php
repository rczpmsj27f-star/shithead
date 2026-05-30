<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["game_id"])) die("Missing game ID");

$game_id = (int)$_GET["game_id"];
$user_id = $_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game) die("Game not found");

// If game has started, redirect to game page
if ($game["status"] === "in_progress") {
    header("Location: game.php?game_id=$game_id");
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.username, gp.user_id
    FROM game_players gp
    JOIN users u ON gp.user_id = u.id
    WHERE gp.game_id = ?
    ORDER BY gp.seat_position
");
$stmt->execute([$game_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

$isHost = ($user_id == $game["created_by"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shithead — Game Lobby</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    padding: 40px;
    max-width: 480px;
    width: 100%;
    text-align: center;
}

h2 {
    font-size: 1.8em;
    color: #333;
    margin-bottom: 8px;
}

.subtitle { color: #777; margin-bottom: 28px; font-size: 14px; }

.game-code-box {
    background: #f0f7ff;
    border: 2px dashed #667eea;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 28px;
}

.game-code-box p { color: #666; font-size: 13px; margin-bottom: 8px; }

.game-code {
    font-size: 3em;
    font-weight: 900;
    letter-spacing: 8px;
    color: #667eea;
}

.players-list {
    background: #f9f9f9;
    border-radius: 10px;
    padding: 16px;
    margin-bottom: 24px;
    text-align: left;
}

.players-list h3 {
    color: #555;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
}

.player-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    font-size: 15px;
    color: #333;
}

.player-item:last-child { border-bottom: none; }

.player-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    flex-shrink: 0;
}

.host-badge {
    font-size: 11px;
    background: #667eea;
    color: white;
    border-radius: 4px;
    padding: 2px 6px;
    margin-left: auto;
}

.waiting {
    color: #999;
    font-size: 13px;
    font-style: italic;
    padding: 8px 0;
}

.btn {
    width: 100%;
    padding: 14px;
    font-size: 16px;
    font-weight: 700;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 10px;
    text-decoration: none;
    display: inline-block;
}

.btn-start {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-start:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.4); }
.btn-start:disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

.btn-end {
    background: #e74c3c;
    color: white;
    font-size: 13px;
    padding: 10px;
}

.btn-end:hover { background: #c0392b; }

.waiting-msg {
    color: #888;
    font-size: 14px;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 8px;
}

.capacity {
    font-size: 13px;
    color: #999;
    margin-bottom: 16px;
}
</style>
<script>
// Auto-check if game has started or new players joined every 2 seconds
setInterval(() => {
    fetch("lobby_state.php?game_id=<?= $game_id ?>")
        .then(r => r.json())
        .then(d => {
            if (d.status === "in_progress") {
                window.location = "game.php?game_id=<?= $game_id ?>";
            } else if (d.player_count !== <?= count($players) ?>) {
                window.location.reload();
            }
        })
        .catch(() => {});
}, 2000);
</script>
</head>
<body>

<div class="container">
    <h2>🎴 Game Lobby</h2>
    <p class="subtitle">Share the code with your friends!</p>

    <div class="game-code-box">
        <p>Game Code</p>
        <div class="game-code"><?= htmlspecialchars($game["game_code"]) ?></div>
    </div>

    <p class="capacity">
        <?= count($players) ?> / <?= $game["max_players"] ?> players joined
    </p>

    <div class="players-list">
        <h3>Players</h3>
        <?php foreach ($players as $p): ?>
        <div class="player-item">
            <div class="player-avatar"><?= strtoupper(substr($p["username"], 0, 1)) ?></div>
            <span><?= htmlspecialchars($p["username"]) ?></span>
            <?php if ($p["user_id"] == $game["created_by"]): ?>
                <span class="host-badge">HOST</span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php for ($i = count($players); $i < $game["max_players"]; $i++): ?>
        <div class="waiting">Waiting for player <?= $i + 1 ?>...</div>
        <?php endfor; ?>
    </div>

    <?php if ($isHost): ?>
        <form method="post" action="start_game.php">
            <input type="hidden" name="game_id" value="<?= $game_id ?>">
            <button type="submit" class="btn btn-start" <?= count($players) < 2 ? "disabled" : "" ?>>
                🚀 Start Game
            </button>
        </form>
        <form method="post" action="end_game.php">
            <input type="hidden" name="game_id" value="<?= $game_id ?>">
            <button type="submit" class="btn btn-end">✖ Abandon Game</button>
        </form>
    <?php else: ?>
        <div class="waiting-msg">⏳ Waiting for the host to start the game...</div>
    <?php endif; ?>
</div>

</body>
</html>
