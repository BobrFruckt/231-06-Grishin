<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'config.php';

// Получаем данные пользователя
$user = [];
$topics = [];
$comments = [];

try {
    // Информация о пользователе
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Последние темы пользователя
    $stmt = $pdo->prepare("SELECT * FROM topics WHERE author_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $topics = $stmt->fetchAll();

    // Последние комментарии пользователя
    $stmt = $pdo->prepare("SELECT c.*, t.title as topic_title FROM comments c 
                          JOIN topics t ON c.topic_id = t.id 
                          WHERE c.author_id = ? ORDER BY c.created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION['user_id']]);
    $comments = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Ошибка загрузки данных: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - <?php echo htmlspecialchars($user['username']); ?> | Pizhma.com</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --background-color: #f5f7fa;
            --card-color: #ffffff;
            --text-color: #333333;
            --light-text: #7f8c8d;
            --border-color: #e0e0e0;
        }
        
        /* Навигационная панель */
        .navbar {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 20px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }
        
        .nav-links a:hover {
            background-color: var(--secondary-color);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }
        
        /* Боковая панель профиля */
        .profile-sidebar {
            background: var(--card-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            position: sticky;
            top: 80px;
            height: fit-content;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .avatar-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 15px;
            border-radius: 50%;
            overflow: hidden;
            border: 5px solid #f1f1f1;
        }
        
        .avatar {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .username {
            font-size: 24px;
            margin: 0;
            color: var(--primary-color);
        }
        
        .user-title {
            font-size: 14px;
            color: var(--light-text);
            margin: 5px 0 15px;
        }
        
        .profile-stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 12px;
            color: var(--light-text);
        }
        
        .profile-bio {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            font-size: 14px;
        }
        
        /* Основное содержимое профиля */
        .profile-content {
            background: var(--card-color);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
        }
        
        .section-title {
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .activity-meta {
            font-size: 12px;
            color: var(--light-text);
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: var(--secondary-color);
        }
        
        .edit-profile {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 20px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .tab.active {
            border-bottom: 2px solid var(--primary-color);
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .home-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
            transition: background 0.3s;
        }
        
        .home-btn:hover {
            background: #7f8c8d;
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
            
            .profile-sidebar {
                position: static;
                margin-bottom: 20px;
                top: 0;
            }
            
            .navbar {
                flex-direction: column;
                padding: 10px;
            }
            
            .nav-links {
                margin-top: 10px;
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Навигационная панель -->
    <nav class="navbar">
        <a href="index.php" class="logo">Pizhma.com</a>
        <div class="nav-links">
            <a href="index.php">Главная</a>
            <a href="forum.php">Форум</a>
            <?php if ($user['role'] === 'admin'): ?>
                <a href="admin.php">Админ-панель</a>
            <?php endif; ?>
            <a href="logout.php">Выход</a>
        </div>
    </nav>

    <div class="container">
        <!-- Боковая панель профиля -->
        <aside class="profile-sidebar">
            <div class="profile-header">
                <div class="avatar-container">
                    <?php 
                    $avatar_path = !empty($user['avatar']) && file_exists($user['avatar']) 
                        ? $user['avatar'] 
                        : 'images/default-avatar.png';
                    ?>
                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" 
                         alt="Аватар" class="avatar" 
                         onerror="this.src='images/default-avatar.png'">
                </div>
                <h1 class="username"><?php echo htmlspecialchars($user['username']); ?></h1>
                <div class="user-title"><?php echo htmlspecialchars($user['role'] === 'admin' ? 'Администратор' : 'Участник'); ?></div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($topics); ?></div>
                    <div class="stat-label">Темы</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($comments); ?></div>
                    <div class="stat-label">Комментарии</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">0</div>
                    <div class="stat-label">Репутация</div>
                </div>
            </div>
            
            <div class="profile-bio">
                <h3>О себе</h3>
                <?php if (!empty($user['about'])): ?>
                    <p><?php echo htmlspecialchars($user['about']); ?></p>
                <?php else: ?>
                    <p>Пользователь пока ничего не рассказал о себе.</p>
                <?php endif; ?>
                
                <?php if (!empty($user['website'])): ?>
                    <p><strong>Сайт:</strong> <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank"><?php echo htmlspecialchars($user['website']); ?></a></p>
                <?php endif; ?>
                
                <?php if (!empty($user['location'])): ?>
                    <p><strong>Местоположение:</strong> <?php echo htmlspecialchars($user['location']); ?></p>
                <?php endif; ?>
            </div>
            
            <a href="edit_profile.php" class="btn edit-profile">Редактировать профиль</a>
            <a href="index.php" class="home-btn">На главную</a>
        </aside>
        
        <!-- Основное содержимое профиля -->
        <main class="profile-content">
            <div class="tabs">
                <div class="tab active" data-tab="activity">Активность</div>
                <div class="tab" data-tab="topics">Мои темы</div>
                <div class="tab" data-tab="comments">Мои комментарии</div>
            </div>
            
            <div class="tab-content active" id="activity">
                <h2 class="section-title">Последняя активность</h2>
                <ul class="activity-list">
                    <?php foreach ($topics as $topic): ?>
                        <li class="activity-item">
                            <div class="activity-title">Создал тему: <a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a></div>
                            <div class="activity-meta"><?php echo date('d.m.Y H:i', strtotime($topic['created_at'])); ?></div>
                        </li>
                    <?php endforeach; ?>
                    
                    <?php foreach ($comments as $comment): ?>
                        <li class="activity-item">
                            <div class="activity-title">Оставил комментарий в теме: <a href="topic.php?id=<?php echo $comment['topic_id']; ?>#comment-<?php echo $comment['id']; ?>"><?php echo htmlspecialchars($comment['topic_title']); ?></a></div>
                            <div class="activity-meta"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="tab-content" id="topics">
                <h2 class="section-title">Мои темы</h2>
                <ul class="activity-list">
                    <?php if (empty($topics)): ?>
                        <li>Вы еще не создавали тем.</li>
                    <?php else: ?>
                        <?php foreach ($topics as $topic): ?>
                            <li class="activity-item">
                                <div class="activity-title"><a href="topic.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a></div>
                                <div class="activity-meta">
                                    Создано: <?php echo date('d.m.Y H:i', strtotime($topic['created_at'])); ?> | 
                                    Комментарии: <?php echo $topic['comment_count'] ?? 0; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <a href="user_topics.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn">Все мои темы</a>
            </div>
            
            <div class="tab-content" id="comments">
                <h2 class="section-title">Мои комментарии</h2>
                <ul class="activity-list">
                    <?php if (empty($comments)): ?>
                        <li>Вы еще не оставляли комментариев.</li>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <li class="activity-item">
                                <div class="activity-title">
                                    В теме: <a href="topic.php?id=<?php echo $comment['topic_id']; ?>#comment-<?php echo $comment['id']; ?>"><?php echo htmlspecialchars($comment['topic_title']); ?></a>
                                </div>
                                <div class="activity-meta">
                                    <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?> | 
                                    <?php echo strlen($comment['content']) > 100 ? substr($comment['content'], 0, 100) . '...' : $comment['content']; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
                <a href="user_comments.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn">Все мои комментарии</a>
            </div>
        </main>
    </div>

    <script>
        // Табы в профиле
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                // Удаляем активный класс у всех табов и контента
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                // Добавляем активный класс текущему табу и соответствующему контенту
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });
    </script>
</body>
</html>