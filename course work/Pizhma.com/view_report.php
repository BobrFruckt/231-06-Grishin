<?php
session_start();
require 'config.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Получаем ID жалобы
$report_id = intval($_GET['id'] ?? 0);
if ($report_id <= 0) {
    header('Location: admin_reports.php');
    exit;
}

// Получаем данные жалобы
try {
    $stmt = $pdo->prepare("
        SELECT r.*, u1.username as reporter, u2.username as resolver, t.title as topic_title, c.content as comment_content
        FROM reports r
        LEFT JOIN users u1 ON r.reporter_id = u1.id
        LEFT JOIN users u2 ON r.resolver_id = u2.id
        LEFT JOIN topics t ON r.content_id = t.id AND r.type = 'topic'
        LEFT JOIN comments c ON r.content_id = c.id AND r.type = 'comment'
        WHERE r.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        header('Location: admin_reports.php');
        exit;
    }

} catch (PDOException $e) {
    die("Ошибка загрузки данных жалобы: " . $e->getMessage());
}

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'resolve':
                $stmt = $pdo->prepare("UPDATE reports SET resolved = 1, resolved_at = NOW(), resolver_id = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $report_id]);
                $_SESSION['admin_message'] = "Жалоба помечена как решенная";
                break;

            case 'unresolve':
                $stmt = $pdo->prepare("UPDATE reports SET resolved = 0, resolved_at = NULL, resolver_id = NULL WHERE id = ?");
                $stmt->execute([$report_id]);
                $_SESSION['admin_message'] = "Жалоба помечена как нерешенная";
                break;

            case 'delete_content':
                if ($report['type'] === 'topic') {
                    $stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
                } else {
                    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                }
                $stmt->execute([$report['content_id']]);
                
                $stmt = $pdo->prepare("UPDATE reports SET resolved = 1, resolved_at = NOW(), resolver_id = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $report_id]);
                
                $_SESSION['admin_message'] = "Контент удален и жалоба помечена как решенная";
                break;
        }

        header("Location: admin_reports.php");
        exit;

    } catch (PDOException $e) {
        die("Ошибка выполнения действия: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр жалобы #<?= $report_id ?> | Админ-панель</title>
    <style>
        /* Стили из admin.php и admin_users.php */
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --danger-color: #e74c3c;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --background-color: #f5f7fa;
            --card-color: #ffffff;
            --text-color: #333333;
            --light-text: #7f8c8d;
            --border-color: #e0e0e0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .admin-navbar {
            background-color: #2c3e50;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .admin-section {
            background: var(--card-color);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 20px;
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .report-meta {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .report-meta dt {
            font-weight: 600;
            text-align: right;
        }
        
        .report-meta dd {
            margin: 0;
        }
        
        .report-content {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .report-reason {
            padding: 20px;
            background: #fff8f8;
            border-left: 4px solid var(--danger-color);
            margin-bottom: 20px;
        }
        
        .report-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn-resolve {
            background-color: var(--success-color);
        }
        
        .btn-unresolve {
            background-color: var(--warning-color);
        }
        
        .btn-delete {
            background-color: var(--danger-color);
        }
        
        .btn-back {
            background-color: var(--light-text);
        }
        
        .status-resolved {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .status-unresolved {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .type-topic {
            color: var(--primary-color);
        }
        
        .type-comment {
            color: var(--warning-color);
        }
        
        .type-user {
            color: var(--danger-color);
        }
    </style>
</head>
<body>
    <nav class="admin-navbar">
        <a href="admin.php" class="logo">Pizhma.com | Просмотр жалобы #<?= $report_id ?></a>
        <div class="admin-nav-links">
            <a href="index.php">На сайт</a>
            <a href="admin.php">Главная</a>
            <a href="admin_users.php">Пользователи</a>
            <a href="admin_reports.php">Жалобы</a>
            <a href="admin_content.php">Контент</a>
            <a href="logout.php">Выход</a>
        </div>
    </nav>

    <div class="container">
        <div class="admin-section">
            <h2 class="section-title">Детали жалобы</h2>
            
            <dl class="report-meta">
                <dt>Тип:</dt>
                <dd class="type-<?= $report['type'] ?>">
                    <?= $report['type'] === 'topic' ? 'Тема' : ($report['type'] === 'comment' ? 'Комментарий' : 'Пользователь') ?>
                </dd>
                
                <dt>От кого:</dt>
                <dd><?= htmlspecialchars($report['reporter']) ?></dd>
                
                <dt>Дата:</dt>
                <dd><?= date('d.m.Y H:i', strtotime($report['created_at'])) ?></dd>
                
                <dt>Статус:</dt>
                <dd class="status-<?= $report['resolved'] ? 'resolved' : 'unresolved' ?>">
                    <?= $report['resolved'] ? 'Решена' : 'Не решена' ?>
                    <?php if ($report['resolved']): ?>
                        (<?= htmlspecialchars($report['resolver'] ?? 'Система') ?>, <?= date('d.m.Y H:i', strtotime($report['resolved_at'])) ?>)
                    <?php endif; ?>
                </dd>
            </dl>
            
            <h3>Причина жалобы:</h3>
            <div class="report-reason">
                <?= nl2br(htmlspecialchars($report['reason'])) ?>
            </div>
            
            <?php if ($report['type'] === 'topic'): ?>
                <h3>Содержание темы:</h3>
                <div class="report-content">
                    <h4><?= htmlspecialchars($report['topic_title']) ?></h4>
                    <?= nl2br(htmlspecialchars($report['content'])) ?>
                </div>
                <a href="topic.php?id=<?= $report['content_id'] ?>" class="btn btn-primary" target="_blank">Перейти к теме</a>
            <?php elseif ($report['type'] === 'comment'): ?>
                <h3>Содержание комментария:</h3>
                <div class="report-content">
                    <?= nl2br(htmlspecialchars($report['comment_content'])) ?>
                </div>
                <a href="topic.php?id=<?= $report['topic_id'] ?>#comment-<?= $report['content_id'] ?>" class="btn btn-primary" target="_blank">Перейти к комментарию</a>
            <?php endif; ?>
            
            <div class="report-actions">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <?php if ($report['resolved']): ?>
                        <button type="submit" name="action" value="unresolve" class="btn btn-unresolve">Отметить нерешенной</button>
                    <?php else: ?>
                        <button type="submit" name="action" value="resolve" class="btn btn-resolve">Пометить как решенную</button>
                    <?php endif; ?>
                    
                    <?php if ($report['type'] !== 'user'): ?>
                        <button type="submit" name="action" value="delete_content" class="btn btn-delete" 
                                onclick="return confirm('Вы уверены? Контент будет удален безвозвратно.')">
                            Удалить <?= $report['type'] === 'topic' ? 'тему' : 'комментарий' ?>
                        </button>
                    <?php endif; ?>
                </form>
                
                <a href="admin_reports.php" class="btn btn-back">Назад к списку</a>
            </div>
        </div>
    </div>
</body>
</html>