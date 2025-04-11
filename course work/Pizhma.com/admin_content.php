<?php
session_start();
require 'config.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Определяем тип контента для управления
$content_type = $_GET['type'] ?? 'topics';
if (!in_array($content_type, ['topics', 'comments'])) {
    $content_type = 'topics';
}

// Параметры фильтрации
$search_query = $_GET['search'] ?? '';
$author_filter = $_GET['author'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// Формируем SQL запрос с фильтрами
$where = [];
$params = [];

if ($content_type === 'topics') {
    $table = 'topics';
    $select = "t.*, u.username as author_name, COUNT(c.id) as comments_count";
    $joins = "LEFT JOIN users u ON t.author_id = u.id LEFT JOIN comments c ON t.id = c.topic_id";
    $group_by = "GROUP BY t.id";
    
    if (!empty($search_query)) {
        $where[] = "(t.title LIKE ? OR t.content LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    
    if (!empty($author_filter)) {
        $where[] = "u.username LIKE ?";
        $params[] = "%$author_filter%";
    }
} else {
    $table = 'comments';
    $select = "c.*, u.username as author_name, t.title as topic_title";
    $joins = "LEFT JOIN users u ON c.author_id = u.id LEFT JOIN topics t ON c.topic_id = t.id";
    $group_by = "";
    
    if (!empty($search_query)) {
        $where[] = "c.content LIKE ?";
        $params[] = "%$search_query%";
    }
    
    if (!empty($author_filter)) {
        $where[] = "u.username LIKE ?";
        $params[] = "%$author_filter%";
    }
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Получаем общее количество записей
$count_sql = "SELECT COUNT(*) FROM $table t $joins $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();
$total_pages = ceil($total_items / $per_page);

// Получаем список записей
$offset = ($page - 1) * $per_page;
$items_sql = "SELECT $select FROM $table t $joins $where_clause $group_by ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
$items_stmt = $pdo->prepare($items_sql);
$items_stmt->execute(array_merge($params, [$per_page, $offset]));
$items = $items_stmt->fetchAll();

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $action = $_POST['action'] ?? '';
    $item_id = intval($_POST['item_id'] ?? 0);

    try {
        switch ($action) {
            case 'delete_topic':
                $stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
                $stmt->execute([$item_id]);
                $_SESSION['admin_message'] = "Тема успешно удалена";
                break;

            case 'delete_comment':
                $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$item_id]);
                $_SESSION['admin_message'] = "Комментарий успешно удален";
                break;
        }

        header("Location: admin_content.php?" . http_build_query($_GET));
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
    <title>Управление контентом | Админ-панель</title>
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
            max-width: 1400px;
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
        
        .content-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .content-tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
        }
        
        .content-tab.active {
            border-bottom: 2px solid var(--primary-color);
            font-weight: bold;
        }
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 14px;
        }
        
        select, input {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .btn-search {
            align-self: flex-end;
            height: 36px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            text-decoration: none;
        }
        
        .pagination a:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .pagination .current {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
        }
        
        .content-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <nav class="admin-navbar">
        <a href="admin.php" class="logo">Pizhma.com | Управление контентом</a>
        <div class="admin-nav-links">
            <a href="index.php">На сайт</a>
            <a href="admin.php">Главная</a>
            <a href="admin_users.php">Пользователи</a>
            <a href="admin_reports.php">Жалобы</a>
            <a href="admin_content.php" class="active">Контент</a>
            <a href="logout.php">Выход</a>
        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['admin_message']; unset($_SESSION['admin_message']); ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-section">
            <div class="content-tabs">
                <a href="?type=topics" class="content-tab <?= $content_type === 'topics' ? 'active' : '' ?>">Темы</a>
                <a href="?type=comments" class="content-tab <?= $content_type === 'comments' ? 'active' : '' ?>">Комментарии</a>
            </div>
            
            <h2 class="section-title">Управление <?= $content_type === 'topics' ? 'темами' : 'комментариями' ?></h2>
            
            <form method="GET" class="filters">
                <input type="hidden" name="type" value="<?= $content_type ?>">
                
                <div class="filter-group" style="flex-grow: 1;">
                    <label for="search">Поиск</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search_query) ?>" 
                           placeholder="<?= $content_type === 'topics' ? 'Название или содержание темы' : 'Текст комментария' ?>">
                </div>
                
                <div class="filter-group">
                    <label for="author">Автор</label>
                    <input type="text" id="author" name="author" value="<?= htmlspecialchars($author_filter) ?>" placeholder="Имя пользователя">
                </div>
                
                <button type="submit" class="btn btn-search">Применить</button>
                <a href="admin_content.php?type=<?= $content_type ?>" class="btn btn-search">Сбросить</a>
            </form>
        </div>
        
        <div class="admin-section">
            <h2 class="section-title">Список <?= $content_type === 'topics' ? 'тем' : 'комментариев' ?> (<?= $total_items ?>)</h2>
            
            <table>
                <thead>
                    <tr>
                        <?php if ($content_type === 'topics'): ?>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Автор</th>
                            <th>Дата</th>
                            <th>Комментарии</th>
                            <th>Действия</th>
                        <?php else: ?>
                            <th>ID</th>
                            <th>Тема</th>
                            <th>Автор</th>
                            <th>Дата</th>
                            <th>Комментарий</th>
                            <th>Действия</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= $item['id'] ?></td>
                            
                            <?php if ($content_type === 'topics'): ?>
                                <td><a href="topic.php?id=<?= $item['id'] ?>" target="_blank"><?= htmlspecialchars($item['title']) ?></a></td>
                                <td><?= htmlspecialchars($item['author_name']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></td>
                                <td><?= $item['comments_count'] ?></td>
                            <?php else: ?>
                                <td><a href="topic.php?id=<?= $item['topic_id'] ?>#comment-<?= $item['id'] ?>" target="_blank"><?= htmlspecialchars($item['topic_title']) ?></a></td>
                                <td><?= htmlspecialchars($item['author_name']) ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></td>
                                <td class="content-preview" title="<?= htmlspecialchars($item['content']) ?>">
                                    <?= htmlspecialchars(substr($item['content'], 0, 100)) ?>
                                    <?= strlen($item['content']) > 100 ? '...' : '' ?>
                                </td>
                            <?php endif; ?>
                            
                            <td class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                    
                                    <?php if ($content_type === 'topics'): ?>
                                        <button type="submit" name="action" value="delete_topic" class="btn btn-danger btn-sm">Удалить</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="delete_comment" class="btn btn-danger btn-sm">Удалить</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">Первая</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Назад</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($page + 2, $total_pages); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Вперед</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">Последняя</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>