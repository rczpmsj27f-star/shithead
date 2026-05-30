<?php
session_start();
require "db.php";

$stmt = $pdo->query("
    SELECT
        u.username AS player_name,
        SUM(CASE WHEN r.place = 1             THEN 1 ELSE 0 END) AS wins,
        SUM(CASE WHEN r.place = 2 AND r.is_shithead = 0 THEN 1 ELSE 0 END) AS second,
        SUM(CASE WHEN r.place = 3 AND r.is_shithead = 0 THEN 1 ELSE 0 END) AS third,
        SUM(CASE WHEN r.place = 4 AND r.is_shithead = 0 THEN 1 ELSE 0 END) AS fourth,
        SUM(CASE WHEN r.is_shithead = 1       THEN 1 ELSE 0 END) AS shithead_count,
        COUNT(r.id) AS games_played
    FROM users u
    LEFT JOIN results r ON r.user_id = u.id
    GROUP BY u.id, u.username
    ORDER BY wins DESC, second DESC, third DESC, fourth DESC, shithead_count ASC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shithead — Leaderboard</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 30px 20px;
}

.container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}

.header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    text-align: center;
}

.header h1 { font-size: 2em; margin-bottom: 6px; }
.header p  { opacity: 0.85; font-size: 14px; }

.table-wrap { overflow-x: auto; padding: 20px; }

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

thead tr {
    background: #f8f8f8;
}

th {
    padding: 12px 14px;
    text-align: center;
    font-weight: 700;
    color: #555;
    border-bottom: 2px solid #eee;
    white-space: nowrap;
}

th.name-col { text-align: left; }

td {
    padding: 11px 14px;
    text-align: center;
    border-bottom: 1px solid #f0f0f0;
    color: #333;
}

td.name-col { text-align: left; font-weight: 600; }

tr:hover td { background: #fafafa; }

.rank-cell {
    font-weight: bold;
    color: #aaa;
    font-size: 13px;
}

.rank-1 { color: #f1c40f; }
.rank-2 { color: #95a5a6; }
.rank-3 { color: #cd7f32; }

.shithead-count {
    color: #e74c3c;
    font-weight: bold;
}

.zero { color: #ccc; }

.nav {
    padding: 16px 20px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.nav a {
    padding: 9px 18px;
    background: #667eea;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
}

.nav a:hover { background: #5567d5; }

.no-games { padding: 40px; text-align: center; color: #999; font-size: 15px; }
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🏆 Leaderboard</h1>
        <p>All-time standings across every game</p>
    </div>

    <div class="table-wrap">
        <?php if (empty($rows)): ?>
        <div class="no-games">No games played yet. Be the first!</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th class="name-col">Player</th>
                    <th>🏆 Win</th>
                    <th>2nd</th>
                    <th>3rd</th>
                    <th>4th</th>
                    <th>💩 Shithead</th>
                    <th>Games</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $rank = 1;
            foreach ($rows as $r):
                $rankClass = $rank <= 3 ? "rank-$rank" : "";
            ?>
            <tr>
                <td class="rank-cell <?= $rankClass ?>"><?= $rank++ ?></td>
                <td class="name-col"><?= htmlspecialchars($r["player_name"]) ?></td>
                <td><?= $r["wins"]   > 0 ? $r["wins"]   : '<span class="zero">—</span>' ?></td>
                <td><?= $r["second"] > 0 ? $r["second"] : '<span class="zero">—</span>' ?></td>
                <td><?= $r["third"]  > 0 ? $r["third"]  : '<span class="zero">—</span>' ?></td>
                <td><?= $r["fourth"] > 0 ? $r["fourth"] : '<span class="zero">—</span>' ?></td>
                <td class="shithead-count"><?= $r["shithead_count"] > 0 ? $r["shithead_count"] : '<span class="zero">—</span>' ?></td>
                <td><?= $r["games_played"] ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <div class="nav">
        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="create_game.php">🎮 New Game</a>
            <a href="lobby.php" style="background:#764ba2;">🚪 Join Game</a>
        <?php else: ?>
            <a href="login.php">Sign In</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
