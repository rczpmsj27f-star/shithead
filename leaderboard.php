<?php
require "db.php";

$stmt = $pdo->query("
    SELECT
        u.username AS player_name,
        SUM(CASE WHEN r.place = 1 THEN 1 ELSE 0 END) AS win,
        SUM(CASE WHEN r.place = 2 THEN 1 ELSE 0 END) AS second,
        SUM(CASE WHEN r.place = 3 THEN 1 ELSE 0 END) AS third,
        SUM(CASE WHEN r.place = 4 THEN 1 ELSE 0 END) AS fourth,
        SUM(CASE WHEN r.is_shithead = 1 THEN 1 ELSE 0 END) AS shithead
    FROM users u
    LEFT JOIN results r ON r.user_id = u.id
    GROUP BY u.id
    ORDER BY win DESC, second DESC, third DESC, fourth DESC, shithead DESC
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Leaderboard</h2>

<table border="1" cellpadding="5">
    <tr>
        <th>Player</th>
        <th>Win</th>
        <th>2nd</th>
        <th>3rd</th>
        <th>4th</th>
        <th>Shithead</th>
    </tr>

    <?php foreach ($rows as $r): ?>
    <tr>
        <td><?= htmlspecialchars($r["player_name"]) ?></td>
        <td><?= $r["win"] ?></td>
        <td><?= $r["second"] ?></td>
        <td><?= $r["third"] ?></td>
        <td><?= $r["fourth"] ?></td>
        <td><?= $r["shithead"] ?></td>
    </tr>
    <?php endforeach; ?>
</table>
