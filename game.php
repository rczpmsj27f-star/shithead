<?php
session_start();
require "db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET["game_id"])) die("Missing game");

$game_id = (int)$_GET["game_id"];
$user_id = $_SESSION["user_id"];

// Verify player is in this game
$stmt = $pdo->prepare("SELECT gp.*, u.username FROM game_players gp JOIN users u ON gp.user_id = u.id WHERE gp.game_id = ? AND gp.user_id = ?");
$stmt->execute([$game_id, $user_id]);
$me = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$me) die("You are not in this game");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shithead — Game</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: Arial, sans-serif;
    background: #1a5c1a;
    min-height: 100vh;
    padding: 16px;
    color: white;
}

.game-board {
    background: linear-gradient(135deg, #2d8c2d 0%, #1a5c1a 100%);
    border: 6px solid #0d3d0d;
    border-radius: 32px;
    padding: 24px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 10px;
}

.header-badge {
    background: rgba(0,0,0,0.35);
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: bold;
    font-size: 14px;
}

.turn-indicator {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    background: rgba(0,0,0,0.3);
}

.turn-indicator.my-turn  { color: #ffff00; background: rgba(0,0,0,0.5); }
.turn-indicator.not-turn { color: #ffaaaa; }

/* Other players area */
.opponents-area {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 24px;
    justify-content: center;
}

.opponent-box {
    background: rgba(255,255,255,0.12);
    border: 2px solid rgba(255,255,255,0.25);
    border-radius: 10px;
    padding: 12px;
    min-width: 160px;
    text-align: center;
}

.opponent-box.current-player {
    border-color: #ffff00;
    background: rgba(255,255,0,0.1);
}

.opponent-box.finished {
    opacity: 0.5;
}

.opponent-name {
    font-weight: bold;
    margin-bottom: 8px;
    font-size: 13px;
}

.opponent-place {
    font-size: 11px;
    color: #ffd700;
    margin-bottom: 6px;
}

.card-row {
    display: flex;
    gap: 4px;
    justify-content: center;
    margin-bottom: 4px;
    flex-wrap: wrap;
}

/* Card styles */
.card {
    width: 44px;
    height: 62px;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: bold;
    text-align: center;
    line-height: 1.2;
    border: 2px solid rgba(0,0,0,0.3);
    user-select: none;
}

.card.face-down  { background: #c0392b; color: white; cursor: default; }
.card.face-up    { background: #8e44ad; color: white; }
.card.hand-card  { background: #2980b9; color: white; }
.card.playable   { cursor: pointer; box-shadow: 0 0 8px 3px rgba(255,255,100,0.8); transform: translateY(-4px); }
.card.not-play   { cursor: default; opacity: 0.6; }
.card.discard-card { background: #e67e22; color: white; width: 60px; height: 84px; font-size: 13px; }
.card.empty-pile { background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.4); border: 2px dashed rgba(255,255,255,0.3); width: 60px; height: 84px; font-size: 11px; }

/* Middle section */
.middle-section {
    display: flex;
    gap: 24px;
    justify-content: center;
    align-items: flex-start;
    margin-bottom: 24px;
    flex-wrap: wrap;
}

.pile-area {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: center;
}

.pile-label {
    font-size: 12px;
    font-weight: bold;
    color: #ccc;
    text-align: center;
    margin-bottom: 4px;
}

.pile-count {
    font-size: 26px;
    font-weight: bold;
    color: #fff;
    text-align: center;
}

.action-btn {
    padding: 10px 22px;
    font-size: 14px;
    font-weight: bold;
    border: none;
    border-radius: 7px;
    cursor: pointer;
    transition: all 0.2s;
    margin: 4px;
}

.btn-pickup {
    background: #e74c3c;
    color: white;
}
.btn-pickup:hover { background: #c0392b; transform: scale(1.04); }

.btn-pickup:disabled {
    background: #666;
    cursor: not-allowed;
    transform: none;
}

/* My cards section */
.my-section {
    background: rgba(0,0,0,0.25);
    border-radius: 10px;
    padding: 16px;
}

.my-section h3 {
    margin-bottom: 12px;
    font-size: 14px;
    color: #ddd;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.my-label {
    font-size: 12px;
    color: #aaa;
    margin: 8px 0 4px;
}

#my-hand, #my-face-up, #my-face-down {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
    min-height: 70px;
}

#my-hand .card, #my-face-up .card, #my-face-down .card {
    width: 54px;
    height: 76px;
    font-size: 12px;
}

/* Game over overlay */
.game-over {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.75);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
}

.game-over-box {
    background: white;
    color: #333;
    border-radius: 16px;
    padding: 40px;
    text-align: center;
    max-width: 480px;
    width: 90%;
}

.game-over-box h2 { font-size: 28px; margin-bottom: 20px; }
.results-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
.results-table th, .results-table td { padding: 8px 12px; border-bottom: 1px solid #eee; text-align: left; }
.results-table th { background: #f5f5f5; }
.shithead-row { color: #e74c3c; font-weight: bold; }

/* Message toast */
#toast {
    position: fixed;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 15px;
    display: none;
    z-index: 50;
}

@media (max-width: 600px) {
    .game-board { padding: 12px; }
    .card { width: 36px; height: 52px; font-size: 9px; }
    #my-hand .card, #my-face-up .card { width: 44px; height: 62px; }
}
</style>
</head>
<body>

<div class="game-board">

    <div class="header">
        <span class="header-badge">GAME: <?= htmlspecialchars($me['game_id'] ?? $game_id) ?></span>
        <span class="header-badge" id="header-turn">Loading...</span>
        <a href="leaderboard.php" style="color:#adf;font-size:13px;">📊 Leaderboard</a>
    </div>

    <div class="turn-indicator" id="turn-indicator">Loading...</div>

    <!-- Other players -->
    <div class="opponents-area" id="opponents-area"></div>

    <!-- Middle: discard + stack -->
    <div class="middle-section">
        <div class="pile-area">
            <div class="pile-label">DISCARD PILE</div>
            <div id="discard-top" class="card empty-pile">EMPTY</div>
        </div>

        <div class="pile-area">
            <div class="pile-label">STACK</div>
            <div class="pile-count" id="stack-count">—</div>
            <div style="font-size:11px;color:#aaa;">cards left</div>
        </div>

        <div class="pile-area">
            <div class="pile-label">ACTION</div>
            <button class="action-btn btn-pickup" id="btn-pickup" onclick="pickupPile()" disabled>
                Pick Up Pile
            </button>
        </div>
    </div>

    <!-- My cards -->
    <div class="my-section">
        <h3>Your Cards — <span id="my-name"><?= htmlspecialchars($me['username']) ?></span></h3>

        <div class="my-label">Hand</div>
        <div id="my-hand"></div>

        <div class="my-label">Face-Up</div>
        <div id="my-face-up"></div>

        <div class="my-label">Face-Down (played blind)</div>
        <div id="my-face-down"></div>
    </div>

</div>

<!-- Game over overlay (hidden until game ends) -->
<div class="game-over" id="game-over-overlay" style="display:none;">
    <div class="game-over-box">
        <h2>🎴 Game Over!</h2>
        <table class="results-table" id="results-table">
            <thead><tr><th>Place</th><th>Player</th></tr></thead>
            <tbody></tbody>
        </table>
        <a href="create_game.php" style="display:inline-block;padding:12px 24px;background:#667eea;color:white;border-radius:8px;text-decoration:none;font-weight:bold;">Play Again</a>
    </div>
</div>

<div id="toast"></div>

<script>
const gameId        = <?= $game_id ?>;
const myUserId      = <?= $user_id ?>;
let   myTurn        = false;
let   requiredLoc   = null;
let   pollTimer     = null;

function showToast(msg, duration = 3000) {
    const t = document.getElementById("toast");
    t.textContent = msg;
    t.style.display = "block";
    setTimeout(() => t.style.display = "none", duration);
}

function suitSymbol(suit) {
    const map = { C:"♣", D:"♦", H:"♥", S:"♠" };
    return map[suit] || "";
}

function cardLabel(c) {
    if (c.rank === "Joker") return "🃏";
    return c.rank + (c.suit ? "\n" + suitSymbol(c.suit) : "");
}

function makeCard(c, cssClass, clickFn) {
    const el = document.createElement("div");
    el.className = "card " + cssClass;
    el.style.whiteSpace = "pre";
    el.textContent = cardLabel(c);
    if (clickFn) el.onclick = clickFn;
    return el;
}

function loadState() {
    fetch("game_state.php?game_id=" + gameId)
        .then(r => r.json())
        .then(updateUI)
        .catch(() => {});
}

function updateUI(data) {
    if (data.error) return;

    const game     = data.game;
    myTurn         = (game.current_player == myUserId);
    requiredLoc    = data.required_loc;

    // Turn indicator
    const turnDiv = document.getElementById("turn-indicator");
    const headerTurn = document.getElementById("header-turn");
    if (game.status === "finished") {
        turnDiv.textContent = "🏁 Game Over!";
        turnDiv.className = "turn-indicator";
        showGameOver(data.players);
        clearInterval(pollTimer);
        return;
    }

    if (myTurn) {
        let locLabel = requiredLoc ? requiredLoc.replace("_","-") : "?";
        turnDiv.textContent = "🎮 YOUR TURN — play from: " + locLabel.toUpperCase();
        turnDiv.className = "turn-indicator my-turn";
    } else {
        const cur = data.players.find(p => p.user_id == game.current_player);
        turnDiv.textContent = "⏳ Waiting for " + (cur ? cur.username : "...");
        turnDiv.className = "turn-indicator not-turn";
    }
    headerTurn.textContent = myTurn ? "YOUR TURN" : "Waiting...";

    // Discard top
    const discardEl = document.getElementById("discard-top");
    if (data.discard_top) {
        discardEl.className = "card discard-card";
        discardEl.style.whiteSpace = "pre";
        discardEl.textContent = cardLabel(data.discard_top);
    } else {
        discardEl.className = "card empty-pile";
        discardEl.textContent = "EMPTY";
    }

    // Stack
    document.getElementById("stack-count").textContent = data.stack;

    // Pick up button
    const pickupBtn = document.getElementById("btn-pickup");
    pickupBtn.disabled = !(myTurn && data.can_pickup);

    // Opponents
    const oppArea = document.getElementById("opponents-area");
    oppArea.innerHTML = "";
    data.players.forEach(p => {
        if (p.user_id == myUserId) return; // skip self

        const box = document.createElement("div");
        let cls = "opponent-box";
        if (p.is_current) cls += " current-player";
        if (p.place_finished !== null) cls += " finished";
        box.className = cls;

        let placeLabel = "";
        if (p.place_finished !== null) {
            placeLabel = p.is_shithead ? "💩 SHITHEAD" : "🏆 " + ordinal(p.place_finished) + " place";
        }

        let html = `<div class="opponent-name">${escHtml(p.username)}</div>`;
        if (placeLabel) html += `<div class="opponent-place">${placeLabel}</div>`;

        // Face-down (show count as red card backs)
        html += `<div class="card-row">`;
        for (let i = 0; i < p.face_down_count; i++) {
            html += `<div class="card face-down" style="width:36px;height:50px;font-size:9px;">▓</div>`;
        }
        html += `</div>`;

        // Face-up (visible to all)
        html += `<div class="card-row">`;
        p.face_up.forEach(c => {
            html += `<div class="card face-up" style="width:36px;height:50px;font-size:9px;white-space:pre;">${escHtml(cardLabel(c))}</div>`;
        });
        html += `</div>`;

        // Hand (show count only)
        html += `<div style="font-size:11px;color:#ddd;margin-top:4px;">✋ ${p.hand_count} in hand</div>`;

        box.innerHTML = html;
        oppArea.appendChild(box);
    });

    // My hand
    const handDiv = document.getElementById("my-hand");
    handDiv.innerHTML = "";
    data.hand.forEach(c => {
        const isPlayable = myTurn && requiredLoc === "hand" && isCardPlayable(c, data.discard_top);
        const el = makeCard(c, "hand-card " + (isPlayable ? "playable" : "not-play"), isPlayable ? () => playCard(c.id) : null);
        handDiv.appendChild(el);
    });

    // My face-up
    const fuDiv = document.getElementById("my-face-up");
    fuDiv.innerHTML = "";
    data.my_face_up.forEach(c => {
        const isPlayable = myTurn && requiredLoc === "face_up" && isCardPlayable(c, data.discard_top);
        const el = makeCard(c, "face-up " + (isPlayable ? "playable" : "not-play"), isPlayable ? () => playCard(c.id) : null);
        fuDiv.appendChild(el);
    });

    // My face-down (show as backs but clickable when it's my turn and I'm on face-down)
    const fdDiv = document.getElementById("my-face-down");
    fdDiv.innerHTML = "";
    for (let i = 0; i < data.my_face_down; i++) {
        // We need actual card IDs for face-down cards — fetch them
        fdDiv.dataset.pending = data.my_face_down;
    }
    // Face-down cards need IDs — request them separately
    if (myTurn && requiredLoc === "face_down") {
        loadFaceDownCards(fdDiv);
    } else {
        for (let i = 0; i < data.my_face_down; i++) {
            const el = document.createElement("div");
            el.className = "card face-down";
            el.textContent = "▓";
            fdDiv.appendChild(el);
        }
    }
}

function loadFaceDownCards(container) {
    fetch("get_facedown.php?game_id=" + gameId)
        .then(r => r.json())
        .then(cards => {
            container.innerHTML = "";
            cards.forEach(c => {
                const el = document.createElement("div");
                el.className = "card face-down playable";
                el.textContent = "?";
                el.title = "Click to play blind";
                el.onclick = () => playCard(c.id);
                container.appendChild(el);
            });
        });
}

function isCardPlayable(card, topCard) {
    const rank = card.rank;
    if (rank === "10" || rank === "2" || rank === "Joker") return true;
    if (!topCard) return true;
    const topRank = topCard.rank;
    if (topRank === "2") return true;
    const vals = {"2":2,"3":3,"4":4,"5":5,"6":6,"7":7,"8":8,"9":9,"10":10,"J":11,"Q":12,"K":13,"A":14,"Joker":0};
    if (topRank === "7") return (vals[rank] || 0) < 7;
    return (vals[rank] || 0) >= (vals[topRank] || 0);
}

function playCard(cardId) {
    if (!myTurn) return;
    const form = new FormData();
    form.append("game_id", gameId);
    form.append("card_id", cardId);

    fetch("play_card.php", { method: "POST", body: form })
        .then(r => r.text())
        .then(t => {
            if (t === "OK" || t === "GAME_FINISHED") {
                loadState();
            } else if (t === "PICKUP") {
                showToast("That card couldn't beat the pile — you picked it up!");
                loadState();
            } else {
                showToast("❌ " + t);
            }
        });
}

function pickupPile() {
    if (!myTurn) return;
    const form = new FormData();
    form.append("game_id", gameId);

    fetch("pickup_pile.php", { method: "POST", body: form })
        .then(r => r.text())
        .then(t => {
            if (t === "OK") {
                showToast("You picked up the pile.");
                loadState();
            } else {
                showToast("❌ " + t);
            }
        });
}

function showGameOver(players) {
    const overlay = document.getElementById("game-over-overlay");
    const tbody = document.querySelector("#results-table tbody");
    tbody.innerHTML = "";
    const sorted = [...players].sort((a,b) => (a.place_finished||99) - (b.place_finished||99));
    sorted.forEach(p => {
        const tr = document.createElement("tr");
        if (p.is_shithead) tr.className = "shithead-row";
        tr.innerHTML = `<td>${p.is_shithead ? "💩 Shithead" : ordinal(p.place_finished)}</td><td>${escHtml(p.username)}</td>`;
        tbody.appendChild(tr);
    });
    overlay.style.display = "flex";
}

function ordinal(n) {
    if (!n) return "?";
    const s = ["th","st","nd","rd"];
    const v = n % 100;
    return n + (s[(v-20)%10] || s[v] || s[0]);
}

function escHtml(s) {
    return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;");
}

// Poll every 1.5 seconds
loadState();
pollTimer = setInterval(loadState, 1500);
</script>

</body>
</html>
