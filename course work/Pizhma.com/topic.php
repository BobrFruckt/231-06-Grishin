<?php
session_start();
require 'config.php';

$topic_id = intval($_GET['id'] ?? 0);
if ($topic_id <= 0) {
    header('Location: index.php');
    exit;
}

// Получаем данные темы
try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.username as author_name, c.name as category_name 
        FROM topics t
        JOIN users u ON t.author_id = u.id
        JOIN categories c ON t.category_id = c.id
        WHERE t.id = ?
    ");
    $stmt->execute([$topic_id]);
    $topic = $stmt->fetch();
    
    if (!$topic) {
        header('Location: index.php');
        exit;
    }
    
    // Увеличиваем счетчик просмотров
    $pdo->prepare("UPDATE topics SET views = views + 1 WHERE id = ?")->execute([$topic_id]);
    
} catch (PDOException $e) {
    die("Ошибка загрузки темы: " . $e->getMessage());
}

// Получаем комментарии
$comments = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username as author_name 
        FROM comments c
        JOIN users u ON c.author_id = u.id
        WHERE c.topic_id = ?
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$topic_id]);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка загрузки комментариев: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($topic['title']) ?> | Pizhma.com</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .topic-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .topic-title {
            margin-top: 0;
            color: #2c3e50;
        }
        .topic-meta {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .topic-content {
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .comments {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .comment {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .comment:last-child {
            border-bottom: none;
        }
        .comment-author {
            font-weight: bold;
            color: #2c3e50;
        }
        .comment-date {
            color: #7f8c8d;
            font-size: 12px;
        }
        .comment-content {
            margin-top: 10px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topic-header">
            <h1 class="topic-title"><?= htmlspecialchars($topic['title']) ?></h1>
            <div class="topic-meta">
                Автор: <?= htmlspecialchars($topic['author_name']) ?> | 
                Категория: <?= htmlspecialchars($topic['category_name']) ?> | 
                Дата: <?= date('d.m.Y H:i', strtotime($topic['created_at'])) ?> | 
                Просмотров: <?= $topic['views'] ?>
            </div>
            <div class="topic-content">
                <?= nl2br(htmlspecialchars($topic['content'])) ?>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="create_comment.php?topic_id=<?= $topic_id ?>" class="btn">Добавить комментарий</a>
            <?php else: ?>
                <p>Для добавления комментария <a href="login.php">войдите</a> в систему.</p>
            <?php endif; ?>
        </div>
        
        <div class="comments">
            <h2>Комментарии (<?= count($comments) ?>)</h2>
            
            <?php if (empty($comments)): ?>
                <p>Комментариев пока нет. Будьте первым!</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-author"><?= htmlspecialchars($comment['author_name']) ?></div>
                        <div class="comment-date"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></div>
                        <div class="comment-content"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <a href="index.php" class="btn" style="background-color: #95a5a6;">На главную</a>
    </div>
</body>
</html>