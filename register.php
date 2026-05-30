<?php
session_start();
require "db.php";

if (isset($_SESSION["user_id"])) {
    header("Location: create_game.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Both fields are required.";
    } elseif (strlen($username) < 2 || strlen($username) > 20) {
        $error = "Username must be 2–20 characters.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = "That username is already taken.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)")
                ->execute([$username, $hash]);
            header("Location: login.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shithead — Create Account</title>
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

.card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    padding: 44px 36px;
    width: 100%;
    max-width: 400px;
    animation: slideUp 0.4s ease;
}

@keyframes slideUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}

.icon { font-size: 2.8em; text-align: center; margin-bottom: 12px; }

h1 {
    text-align: center;
    font-size: 1.7em;
    color: #333;
    margin-bottom: 6px;
}

.subtitle {
    text-align: center;
    color: #888;
    font-size: 14px;
    margin-bottom: 28px;
}

label {
    display: block;
    font-weight: 600;
    color: #555;
    font-size: 13px;
    margin-bottom: 6px;
}

input[type=text], input[type=password] {
    width: 100%;
    padding: 12px 14px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    margin-bottom: 18px;
    transition: border-color 0.2s;
    outline: none;
}

input:focus { border-color: #667eea; }

.error {
    background: #fdecea;
    color: #c0392b;
    padding: 10px 14px;
    border-radius: 7px;
    font-size: 13px;
    margin-bottom: 16px;
}

button {
    width: 100%;
    padding: 14px;
    font-size: 16px;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 16px;
}

button:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.4); }

.login-link {
    text-align: center;
    color: #888;
    font-size: 13px;
}

.login-link a { color: #667eea; font-weight: 600; text-decoration: none; }
.login-link a:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="card">
    <div class="icon">🃏</div>
    <h1>Create Account</h1>
    <p class="subtitle">Join the Shithead community</p>

    <?php if ($error): ?>
    <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="username">Player Name</label>
        <input type="text" id="username" name="username" maxlength="20"
               value="<?= htmlspecialchars($_POST["username"] ?? "") ?>" required autofocus>

        <label for="password">Password / PIN</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Create Account</button>
    </form>

    <div class="login-link">
        Already have an account? <a href="login.php">Sign in</a>
    </div>
</div>

</body>
</html>
