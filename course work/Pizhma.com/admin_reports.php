<?php
session_start();
require 'config.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Параметры фильтрации
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// Формируем SQL запрос с фильтрами
$where = [];
$params = [];

if (!empty($type_filter)) {
    $where[] = "r.type = ?";
    $params[] = $type_filter;
}

if (!empty($status_filter)) {
    if ($status_filter === 'resolved') {
        $where[] = "r.resolved = 1";
    } else {
        $where[] = "r.resolved = 0";
    }
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Получаем общее количество жалоб
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM reports r $where_clause");
$count_stmt->execute($params);
$total_reports = $count_stmt->fetchColumn();
$total_pages = ceil($total_reports / $per_page);

// Получаем список жалоб
$offset = ($page - 1) * $per_page;
$reports_stmt = $pdo->prepare("
    SELECT r.*, u1.username as reporter, u2.username as resolver 
    FROM reports r
    LEFT JOIN users u1 ON r.reporter_id = u1.id
    LEFT JOIN users u2 ON r.resolver_id = u2.id
    $where_clause 
    ORDER BY r.created_at DESC 
    LIMIT ? OFFSET ?
");
$reports_stmt->execute(array_merge($params, [$per_page, $offset]));
$reports = $reports_stmt->fetchAll();

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $action = $_POST['action'] ?? '';
    $report_id = intval($_POST['report_id'] ?? 0);

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
        }

        header("Location: admin_reports.php?" . http_build_query($_GET));
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
    <title>Управление жалобами | Админ-панель</title>
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
        
        .status-resolved {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .status-unresolved {
            color: var(--danger-color);
            font-weight: 600;
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
        <a href="admin.php" class="logo">Pizhma.com | Управление жалобами</a>
        <div class="admin-nav-links">
            <a href="index.php">На сайт</a>
            <a href="admin.php">Главная</a>
            <a href="admin_users.php">Пользователи</a>
            <a href="admin_reports.php" class="active">Жалобы</a>
            <a href="admin_content.php">Контент</a>
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
            <h2 class="section-title">Фильтры жалоб</h2>
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label for="type">Тип жалобы</label>
                    <select id="type" name="type">
                        <option value="">Все типы</option>
                        <option value="topic" <?= $type_filter === 'topic' ? 'selected' : '' ?>>Тема</option>
                        <option value="comment" <?= $type_filter === 'comment' ? 'selected' : '' ?>>Комментарий</option>
                        <option value="user" <?= $type_filter === 'user' ? 'selected' : '' ?>>Пользователь</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status">Статус</label>
                    <select id="status" name="status">
                        <option value="">Все статусы</option>
                        <option value="unresolved" <?= $status_filter === 'unresolved' ? 'selected' : '' ?>>Нерешенные</option>
                        <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Решенные</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-search">Применить</button>
                <a href="admin_reports.php" class="btn btn-search">Сбросить</a>
            </form>
        </div>
        
        <div class="admin-section">
            <h2 class="section-title">Список жалоб (<?= $total_reports ?>)</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Тип</th>
                        <th>От кого</th>
                        <th>Дата</th>
                        <th>Статус</th>
                        <th>Решено</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><?= $report['id'] ?></td>
                            <td class="type-<?= $report['type'] ?>">
                                <?= $report['type'] === 'topic' ? 'Тема' : ($report['type'] === 'comment' ? 'Комментарий' : 'Пользователь') ?>
                            </td>
                            <td><?= htmlspecialchars($report['reporter']) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($report['created_at'])) ?></td>
                            <td class="status-<?= $report['resolved'] ? 'resolved' : 'unresolved' ?>">
                                <?= $report['resolved'] ? 'Решена' : 'Не решена' ?>
                            </td>
                            <td>
                                <?php if ($report['resolved']): ?>
                                    <?= htmlspecialchars($report['resolver'] ?? 'Система') ?><br>
                                    <?= date('d.m.Y H:i', strtotime($report['resolved_at'])) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="action-buttons">
                                <a href="view_report.php?id=<?= $report['id'] ?>" class="btn btn-primary btn-sm">Просмотр</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                    
                                    <?php if ($report['resolved']): ?>
                                        <button type="submit" name="action" value="unresolve" class="btn btn-warning btn-sm">Отметить нерешенной</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="resolve" class="btn btn-success btn-sm">Решено</button>
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