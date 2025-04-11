<?php
session_start();
require 'config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$topic_id = intval($_GET['topic_id'] ?? 0);
if ($topic_id <= 0) {
    header('Location: index.php');
    exit;
}

// Проверяем существование темы
try {
    $stmt = $pdo->prepare("SELECT id FROM topics WHERE id = ?");
    $stmt->execute([$topic_id]);
    if (!$stmt->fetch()) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    die("Ошибка проверки темы: " . $e->getMessage());
}

$errors = [];

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);

    // Валидация
    if (empty($content)) {
        $errors[] = "Комментарий не может быть пустым";
    } elseif (strlen($content) < 5) {
        $errors[] = "Комментарий слишком короткий (минимум 5 символов)";
    }

    // Если нет ошибок - сохраняем комментарий
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO comments (content, topic_id, author_id) VALUES (?, ?, ?)");
            $stmt->execute([$content, $topic_id, $_SESSION['user_id']]);
            
            // Перенаправляем обратно к теме
            header("Location: topic.php?id=$topic_id");
            exit;
        } catch (PDOException $e) {
            $errors[] = "Ошибка сохранения комментария: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить комментарий | Pizhma.com</title>
    <style>
        /* Аналогичные стили как в create_topic.php */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f7fa;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            color: #2c3e50;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            min-height: 150px;
            resize: vertical;
        }
        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Добавить комментарий</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="content">Текст комментария</label>
                <textarea id="content" name="content" required><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn">Отправить</button>
            <a href="topic.php?id=<?= $topic_id ?>" class="btn" style="background-color: #95a5a6;">Отмена</a>
        </form>
    </div>
</body>
</html>