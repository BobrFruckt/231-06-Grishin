<?php
session_start();
require 'config.php';

// Получаем список последних тем форума
$latest_topics = [];
try {
    $stmt = $pdo->query("SELECT * FROM topics ORDER BY created_at DESC LIMIT 5");
    $latest_topics = $stmt->fetchAll();
} catch (PDOException $e) {
    $forum_error = "Ошибка загрузки тем форума: " . $e->getMessage();
}

// Получаем количество пользователей
$user_count = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Игнорируем ошибку подсчета пользователей
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форум Pizhma.com</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        .nav {
            display: flex;
            gap: 15px;
        }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }
        .nav a:hover {
            background-color: #34495e;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 20px;
        }
        .main-content {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .sidebar {
            background-color: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .welcome-banner {
            background-color: #3498db;
            color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .section-title {
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .topic-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .topic-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .topic-item:last-child {
            border-bottom: none;
        }
        .topic-title {
            font-weight: bold;
            color: #2c3e50;
            text-decoration: none;
            display: block;
            margin-bottom: 5px;
        }
        .topic-title:hover {
            color: #3498db;
        }
        .topic-meta {
            font-size: 12px;
            color: #7f8c8d;
        }
        .stats-box {
            margin-bottom: 20px;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 3px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #45a049;
        }
        .btn-primary {
            background-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        .error {
            color: #e74c3c;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">Pizhma.com</div>
        <div class="nav">
            <a href="index.php">Главная</a>
            <a href="forum.php">Форум</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="dashboard.php">Профиль</a>
                <a href="logout.php">Выход</a>
            <?php else: ?>
                <a href="login.php">Вход</a>
                <a href="register.php">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <main class="main-content">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="welcome-banner">
                    <h2>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                    <p>Присоединяйтесь к обсуждениям на нашем форуме</p>
                    <a href="new_topic.php" class="btn btn-primary">Создать новую тему</a>
                </div>
            <?php else: ?>
                <div class="welcome-banner">
                    <h2>Добро пожаловать на форум Pizhma.com!</h2>
                    <p>Присоединяйтесь к нашему сообществу. Зарегистрируйтесь или войдите, чтобы участвовать в обсуждениях.</p>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <a href="register.php" class="btn btn-primary">Регистрация</a>
                        <a href="login.php" class="btn">Вход</a>
                    </div>
                </div>
            <?php endif; ?>

            <h3 class="section-title">Последние темы</h3>
            
            <?php if (isset($forum_error)): ?>
                <div class="error"><?php echo $forum_error; ?></div>
            <?php elseif (empty($latest_topics)): ?>
                <p>Пока нет созданных тем. Будьте первым!</p>
            <?php else: ?>
                <ul class="topic-list">
                    <?php foreach ($latest_topics as $topic): ?>
                        <li class="topic-item">
                            <a href="topic.php?id=<?php echo $topic['id']; ?>" class="topic-title"><?php echo htmlspecialchars($topic['title']); ?></a>
                            <div class="topic-meta">
                                Автор: <?php echo htmlspecialchars($topic['author']); ?> | 
                                Дата: <?php echo date('d.m.Y H:i', strtotime($topic['created_at'])); ?> | 
                                Комментарии: <?php echo $topic['comment_count']; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div style="text-align: right; margin-top: 15px;">
                <a href="forum.php" class="btn">Перейти в форум</a>
                </div>
            <?php endif; ?>
        </main>

        <aside class="sidebar">
            <div class="stats-box">
                <h3 class="section-title">Статистика форума</h3>
                <div class="stat-item">
                    <span>Пользователей:</span>
                    <span><?php echo $user_count; ?></span>
                </div>
                <div class="stat-item">
                    <span>Тем:</span>
                    <span><?php echo count($latest_topics); ?></span>
                </div>
                <div class="stat-item">
                    <span>Комментариев:</span>
                    <span>0</span>
                </div>
            </div>

            <div class="rules-box">
                <h3 class="section-title">Правила форума</h3>
                <ol style="padding-left: 20px; font-size: 14px;">
                    <li>Будьте вежливы с другими участниками</li>
                    <li>Запрещены оскорбления и дискриминация</li>
                    <li>Размещайте темы в соответствующих разделах</li>
                    <li>Не публикуйте спам или рекламу</li>
                    <li>Соблюдайте законы РФ</li>
                </ol>
            </div>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <p>Еще не с нами?</p>
                    <a href="register.php" class="btn btn-primary">Присоединиться</a>
                </div>
            <?php endif; ?>
        </aside>
    </div>
</body>
</html>