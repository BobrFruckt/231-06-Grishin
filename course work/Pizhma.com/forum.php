<?php
require 'config.php';
require 'header.php';

$search = $_GET['search'] ?? '';
$order = $_GET['order'] ?? 'newest'; // newest | oldest | most_commented

// SQL сортировка
switch ($order) {
    case 'oldest':
        $orderBy = "t.created_at ASC";
        break;
    case 'most_commented':
        $orderBy = "comment_count DESC";
        break;
    default:
        $orderBy = "t.created_at DESC";
}

// Запрос тем
$sql = "
    SELECT t.id, t.title, t.created_at, u.username,
           (SELECT COUNT(*) FROM comments c WHERE c.topic_id = t.id) as comment_count
    FROM topics t
    JOIN users u ON t.user_id = u.id
";

$params = [];
if (!empty($search)) {
    $sql .= " WHERE t.title LIKE :search";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY $orderBy"; // без LIMIT

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val, PDO::PARAM_STR);
}
$stmt->execute();
$topics = $stmt->fetchAll();

// Подсчёт общего числа
$totalTopics = count($topics);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форум | Pizhma.com</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f8;
        }

        .forum-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .forum-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }

        .forum-title {
            font-size: 28px;
            margin: 0;
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .filter-bar input[type="text"],
        .filter-bar select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }

        .btn:hover {
            background-color: #2980b9;
        }

        .topic {
            border-bottom: 1px solid #e0e0e0;
            padding: 15px 0;
        }

        .topic:last-child {
            border-bottom: none;
        }

        .topic a {
            font-size: 20px;
            color: #3498db;
            text-decoration: none;
        }

        .topic a:hover {
            text-decoration: underline;
        }

        .meta {
            font-size: 14px;
            color: #888;
        }

        .counter {
            font-size: 14px;
            color: #888;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="forum-container">
        <div class="forum-header">
            <h1 class="forum-title">Форум</h1>

            <form class="filter-bar" method="GET">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Поиск тем...">
                <select name="order">
                    <option value="newest" <?= $order === 'newest' ? 'selected' : '' ?>>Сначала новые</option>
                    <option value="oldest" <?= $order === 'oldest' ? 'selected' : '' ?>>Сначала старые</option>
                    <option value="most_commented" <?= $order === 'most_commented' ? 'selected' : '' ?>>По комментариям</option>
                </select>
                <button class="btn" type="submit">Применить</button>
            </form>
        </div>

        <div class="counter">Всего тем: <?= $totalTopics ?></div>

        <?php if (count($topics) === 0): ?>
            <p>Тем не найдено.</p>
        <?php else: ?>
            <?php foreach ($topics as $topic): ?>
                <div class="topic">
                    <a href="topic.php?id=<?= $topic['id'] ?>"><?= htmlspecialchars($topic['title']) ?></a>
                    <div class="meta">
                        Автор: <?= htmlspecialchars($topic['username']) ?> |
                        <?= date('d.m.Y H:i', strtotime($topic['created_at'])) ?> |
                        Комментариев: <?= $topic['comment_count'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
