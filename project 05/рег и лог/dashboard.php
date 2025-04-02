<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'config.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Pizhma.com</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .dashboard {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .welcome {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .logout {
            display: inline-block;
            margin-top: 20px;
            color: #333;
            text-decoration: none;
            border: 1px solid #333;
            padding: 5px 10px;
            border-radius: 3px;
        }
        .logout:hover {
            background-color: #333;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="welcome">Привет, <?php echo htmlspecialchars($_SESSION['username']); ?>!</div>
        <p>Вы попали на наш сайт!</p>
        <a href="logout.php" class="logout">Выход</a>
    </div>
</body>
</html>