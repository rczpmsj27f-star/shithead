<?php
session_start();
require "db.php";

if (!isset($_GET["game_id"])) die("Missing game");

$game_id = (int)$_GET["game_id"];

$stmt = $pdo->prepare("SELECT * FROM games WHERE id = ?");
$stmt->execute([$game_id]);
$game = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT u.username 
    FROM game_players gp 
    JOIN users u ON gp.user_id = u.id 
    WHERE gp.game_id = ?
    ORDER BY gp.seat_position
");
$stmt->execute([$game_id]);
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Game Lobby</h2>
<p>Game Code: <strong><?= $game["game_code"] ?></strong></p>

<h3>Players Joined:</h3>
<ul>
<?php foreach ($players as $p): ?>
    <li><?= htmlspecialchars($p["username"]) ?></li>
<?php endforeach; ?>
</ul>

<?php if ($_SESSION["user_id"] == $game["created_by"]): ?>
    <form method="post" action="start_game.php">
        <input type="hidden" name="game_id" value="<?= $game_id ?>">
        <button type="submit">Start Game</button>
    </form>
<?php endif; ?>
