<?php
session_start();
require "db.php";

if (!isset($_GET["game_id"])) die("Missing game");
$game_id = (int)$_GET["game_id"];
$user_id = $_SESSION["user_id"];
?>

<!DOCTYPE html>
<html>
<head>
<title>Shithead Game</title>
<style>
body { font-family: Arial; background: #f4f4f4; padding: 20px; }
.card {
    display: inline-block;
    padding: 10px 14px;
    margin: 5px;
    background: white;
    border: 1px solid #333;
    border-radius: 6px;
    cursor: pointer;
}
.card:hover { background: #e0ffe0; }
#hand, #faceup { margin-bottom: 20px; }
#discard, #stack { margin-top: 20px; }
</style>

<script>
let gameId = <?= $game_id ?>;

function loadState() {
    fetch("game_state.php?game_id=" + gameId)
        .then(r => r.json())
        .then(updateUI);
}

function updateUI(data) {
    document.getElementById("turn").innerHTML =
        (data.game.current_player == <?= $user_id ?>)
        ? "<b>Your turn</b>"
        : "Waiting for other players";

    // Hand
    let handDiv = document.getElementById("hand");
    handDiv.innerHTML = "";
    data.hand.forEach(c => {
        let el = document.createElement("div");
        el.className = "card";
        el.innerHTML = c.rank + (c.suit ? " " + c.suit : "");
        el.onclick = () => playCard(c.id);
        handDiv.appendChild(el);
    });

    // Face-up
    let fuDiv = document.getElementById("faceup");
    fuDiv.innerHTML = "";
    data.face_up.forEach(c => {
        let el = document.createElement("div");
        el.className = "card";
        el.innerHTML = c.rank + (c.suit ? " " + c.suit : "");
        el.onclick = () => playCard(c.id);
        fuDiv.appendChild(el);
    });

    // Face-down count
    document.getElementById("facedown").innerHTML =
        "Face-down cards: " + data.face_down;

    // Discard top
    let d = document.getElementById("discard");
    if (data.discard_top) {
        d.innerHTML = "Top of discard: <b>" +
            data.discard_top.rank +
            (data.discard_top.suit ? " " + data.discard_top.suit : "") +
            "</b>";
    } else {
        d.innerHTML = "Discard pile is empty";
    }

    // Stack
    document.getElementById("stack").innerHTML =
        "Cards in stack: " + data.stack;
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

<h2>Shithead – Game <?= $game_id ?></h2>

<div id="turn">Loading...</div>

<h3>Your Hand</h3>
<div id="hand"></div>

<h3>Your Face-Up Cards</h3>
<div id="faceup"></div>

<div id="facedown"></div>

<hr>

<div id="discard"></div>
<div id="stack"></div>

</body>
</html>
