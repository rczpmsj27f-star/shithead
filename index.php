<?php
session_start();

// If user is already logged in, redirect to lobby
if (isset($_SESSION['user_id'])) {
    header('Location: lobby.php');
    exit();
}

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'login') {
        header('Location: login.php');
        exit();
    } elseif ($action === 'register') {
        header('Location: register.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shithead Game - Welcome</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            text-align: center;
            max-width: 600px;
            width: 100%;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-icon {
            font-size: 4em;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 3em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 1.2em;
            font-weight: 300;
        }

        .button-group {
            display: flex;
            gap: 15px;
            flex-direction: column;
            margin-bottom: 40px;
        }

        button {
            padding: 16px 32px;
            font-size: 1.15em;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .btn-register {
            background: #f0f0f0;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-register:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-register:active {
            transform: translateY(-1px);
        }

        .features {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
            padding-top: 40px;
            border-top: 2px solid #f0f0f0;
        }

        .feature {
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .feature:hover {
            background: #f0f0f0;
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .feature-title {
            color: #333;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .feature-desc {
            color: #777;
            font-size: 0.9em;
        }

        .info-box {
            background: #f0f7ff;
            padding: 25px;
            border-radius: 8px;
            margin-top: 30px;
            border-left: 4px solid #667eea;
        }

        .info-box h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.1em;
        }

        .info-box p {
            color: #666;
            line-height: 1.6;
            font-size: 0.95em;
        }

        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }

            h1 {
                font-size: 2.2em;
            }

            .features {
                grid-template-columns: 1fr;
            }

            button {
                padding: 14px 28px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card-icon">🂡 🃏 🂮</div>
        <h1>Shithead</h1>
        <p class="subtitle">The Ultimate Card Game Experience</p>
        
        <form method="POST">
            <div class="button-group">
                <button type="submit" name="action" value="login" class="btn-login">Sign In</button>
                <button type="submit" name="action" value="register" class="btn-register">Create Account</button>
            </div>
        </form>

        <div class="features">
            <div class="feature">
                <div class="feature-icon">🎮</div>
                <div class="feature-title">Multiplayer</div>
                <div class="feature-desc">Play with friends and compete globally</div>
            </div>
            <div class="feature">
                <div class="feature-icon">🏆</div>
                <div class="feature-title">Leaderboard</div>
                <div class="feature-desc">Track your rank and progress</div>
            </div>
            <div class="feature">
                <div class="feature-icon">⚡</div>
                <div class="feature-title">Fast Games</div>
                <div class="feature-desc">Quick matches anytime, anywhere</div>
            </div>
        </div>

        <div class="info-box">
            <h3>Welcome to Shithead!</h3>
            <p>Shithead is a fast-paced, strategic card game that combines skill with a touch of luck. Play against other players, climb the global leaderboard, and master the art of outsmarting your opponents. Whether you're a casual player or a competitive strategist, there's a game waiting for you.</p>
        </div>
    </div>
</body>
</html>
