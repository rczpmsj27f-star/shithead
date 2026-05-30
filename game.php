<?php
session_start();
require "db.php";

if (!isset($_GET["game_id"])) die("Missing game");
$game_id = (int)$_GET["game_id"];
$user_id = $_SESSION["user_id"];

// Load all players for this game
$stmt = $pdo->prepare("SELECT gp.*, u.username FROM game_players gp JOIN users u ON gp.user_id = u.id WHERE gp.game_id = ? ORDER BY gp.seat_position");
$stmt->execute([$game_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shithead Game</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: #1a5c1a;
    padding: 20px;
    min-height: 100vh;
}

.game-board {
    background: linear-gradient(135deg, #2d8c2d 0%, #1a5c1a 100%);
    border: 8px solid #0d3d0d;
    border-radius: 40px;
    padding: 40px;
    max-width: 1400px;
    margin: 0 auto;
    color: white;
}

.header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    font-size: 18px;
    font-weight: bold;
    color: #ffff00;
}

.header span {
    background: rgba(0,0,0,0.3);
    padding: 10px 20px;
    border-radius: 5px;
}

.players-area {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 40px;
}

.player-box {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.player-name {
    font-weight: bold;
    color: #333;
    margin-bottom: 15px;
    font-size: 14px;
}

.player-cards {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.card-row {
    display: flex;
    gap: 6px;
    justify-content: center;
}

.card {
    width: 50px;
    height: 70px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    color: white;
    cursor: pointer;
    transition: transform 0.2s;
}

.card:hover {
    transform: scale(1.1);
}

.face-down {
    background: #e74c3c;
    border: 2px solid #c0392b;
}

.face-up {
    background: #8e44ad;
    border: 2px solid #6c3483;
}

.hand-card {
    background: #3498db;
    border: 2px solid #2980b9;
}

.player-info {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    justify-content: center;
}

.info-box {
    background: #ecf0f1;
    color: #333;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
}

.middle-section {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 40px;
    align-items: start;
    margin-bottom: 40px;
}

.player-info-box {
    background: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    color: #333;
}

.player-info-box h3 {
    margin-bottom: 10px;
    font-size: 16px;
}

.pile-box {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.pile {
    background: white;
    border: 3px solid #333;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    min-width: 120px;
}

.pile h3 {
    margin-bottom: 10px;
    color: #333;
    font-size: 14px;
}

.pile-count {
    font-size: 24px;
    font-weight: bold;
    color: #2c3e50;
}

.hand-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-top: 40px;
}

.hand-section h3 {
    color: #333;
    margin-bottom: 15px;
    text-align: center;
}

#hand {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

#hand .card {
    width: 60px;
    height: 85px;
    font-size: 13px;
}

.turn-indicator {
    text-align: center;
    font-size: 20px;
    font-weight: bold;
    color: #ffff00;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}

@media (max-width: 1200px) {
    .players-area {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .middle-section {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .game-board {
        padding: 20px;
    }
    
    .players-area {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let gameId = <?= $game_id ?>;
let currentUserId = <?= $user_id ?>;

function loadState() {
    fetch("game_state.php?game_id=" + gameId)
        .then(r => r.json())
        .then(updateUI)
        .catch(err => console.error("Error loading game state:", err));
}

function updateUI(data) {
    // Update turn indicator
    const turnDiv = document.getElementById("turn");
    if (data.game.current_player == currentUserId) {
        turnDiv.innerHTML = "🎮 YOUR TURN 🎮";
        turnDiv.style.color = "#ffff00";
    } else {
        turnDiv.innerHTML = "⏳ WAITING FOR OTHER PLAYERS";
        turnDiv.style.color = "#ff6b6b";
    }

    // Update current player's hand
    let handDiv = document.getElementById("hand");
    handDiv.innerHTML = "";
    data.hand.forEach(c => {
        let el = document.createElement("div");
        el.className = "card hand-card";
        el.innerHTML = c.rank + (c.suit ? "<br>" + c.suit : "");
        el.onclick = () => playCard(c.id);
        el.title = "Click to play";
        handDiv.appendChild(el);
    });

    // Update discard pile
    if (data.discard_top) {
        document.getElementById("discard-top").innerHTML = 
            data.discard_top.rank + (data.discard_top.suit ? "<br>" + data.discard_top.suit : "");
    } else {
        document.getElementById("discard-top").innerHTML = "EMPTY";
    }

    // Update stack count
    document.getElementById("stack-count").innerHTML = data.stack;
}

function playCard(cardId) {
    let form = new FormData();
    form.append("game_id", gameId);
    form.append("card_id", cardId);

    fetch("play_card.php", { method: "POST", body: form })
        .then(r => r.text())
        .then(t => {
            if (t !== "OK") alert(t);
            loadState();
        });
}

setInterval(loadState, 1000);
window.onload = loadState;
</script>
</head>

<body>

<div class="game-board">
    
    <div class="turn-indicator" id="turn">Loading...</div>
    
    <div class="header">
        <span>GAME ID: <?= $game_id ?></span>
        <span>PLAYER TURN: <span id="current-player">Loading...</span></span>
    </div>

    <div class="players-area">
        <?php foreach ($players as $player): ?>
        <div class="player-box" id="player-<?= $player['id'] ?>">
            <div class="player-name"><?= htmlspecialchars($player['username']) ?></div>
            <div class="player-cards"></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="middle-section">
        <div class="player-info-box">
            <h3>CURRENT PLAYER</h3>
            <p id="current-player-name">Loading...</p>
        </div>

        <div class="pile-box">
            <div class="pile">
                <h3>DISCARD</h3>
                <div class="card" id="discard-top" style="background: #f39c12; border-color: #d68910;">
                    EMPTY
                </div>
            </div>

            <div class="pile">
                <h3>STACK</h3>
                <div class="pile-count" id="stack-count">0</div>
            </div>
        </div>

        <div class="player-info-box">
            <h3>YOUR HAND</h3>
            <p id="hand-count">0 cards</p>
        </div>
    </div>

    <div class="hand-section">
        <h3>YOUR HAND CARDS</h3>
        <div id="hand"></div>
    </div>

</div>

</body>
</html>
