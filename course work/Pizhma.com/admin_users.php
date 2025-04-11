<?php
session_start();
require 'config.php';

// Проверка прав администратора
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Параметры фильтрации
$role_filter = $_GET['role'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;

// Формируем SQL запрос с фильтрами
$where = [];
$params = [];

if (!empty($role_filter)) {
    $where[] = "role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $where[] = "status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $where[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";

// Получаем общее количество пользователей
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM users $where_clause");
$count_stmt->execute($params);
$total_users = $count_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Получаем список пользователей
$offset = ($page - 1) * $per_page;
$users_stmt = $pdo->prepare("SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?");
$users_stmt->execute(array_merge($params, [$per_page, $offset]));
$users = $users_stmt->fetchAll();

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    try {
        switch ($action) {
            case 'ban':
                $stmt = $pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['admin_message'] = "Пользователь заблокирован";
                break;

            case 'unban':
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['admin_message'] = "Пользователь разблокирован";
                break;

            case 'promote':
                $stmt = $pdo->prepare("UPDATE users SET role = 'moderator' WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['admin_message'] = "Пользователь повышен до модератора";
                break;

            case 'demote':
                $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['admin_message'] = "Пользователь понижен до обычного";
                break;
        }

        header("Location: admin_users.php?" . http_build_query($_GET));
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
    <title>Управление пользователями | Админ-панель</title>
    <style>
        /* Стили из admin.php */
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
        
        .status-banned {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .status-active {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .role-admin {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .role-moderator {
            color: var(--warning-color);
            font-weight: 600;
        }
        
        .role-user {
            color: var(--primary-color);
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
    </style>
</head>
<body>
    <nav class="admin-navbar">
        <a href="admin.php" class="logo">Pizhma.com | Управление пользователями</a>
        <div class="admin-nav-links">
            <a href="index.php">На сайт</a>
            <a href="admin.php">Главная</a>
            <a href="admin_users.php" class="active">Пользователи</a>
            <a href="admin_reports.php">Жалобы</a>
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
            <h2 class="section-title">Фильтры пользователей</h2>
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label for="role">Роль</label>
                    <select id="role" name="role">
                        <option value="">Все роли</option>
                        <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>Пользователь</option>
                        <option value="moderator" <?= $role_filter === 'moderator' ? 'selected' : '' ?>>Модератор</option>
                        <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Администратор</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status">Статус</label>
                    <select id="status" name="status">
                        <option value="">Все статусы</option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Активный</option>
                        <option value="banned" <?= $status_filter === 'banned' ? 'selected' : '' ?>>Заблокирован</option>
                    </select>
                </div>
                
                <div class="filter-group" style="flex-grow: 1;">
                    <label for="search">Поиск</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search_query) ?>" placeholder="Логин или email">
                </div>
                
                <button type="submit" class="btn btn-search">Применить</button>
                <a href="admin_users.php" class="btn btn-search">Сбросить</a>
            </form>
        </div>
        
        <div class="admin-section">
            <h2 class="section-title">Список пользователей (<?= $total_users ?>)</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Email</th>
                        <th>Роль</th>
                        <th>Статус</th>
                        <th>Дата регистрации</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td class="role-<?= $user['role'] ?>"><?= $user['role'] === 'admin' ? 'Админ' : ($user['role'] === 'moderator' ? 'Модератор' : 'Пользователь') ?></td>
                            <td class="status-<?= $user['status'] ?>"><?= $user['status'] === 'banned' ? 'Заблокирован' : 'Активен' ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                            <td class="action-buttons">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    
                                    <?php if ($user['status'] === 'active'): ?>
                                        <button type="submit" name="action" value="ban" class="btn btn-danger btn-sm">Забанить</button>
                                    <?php else: ?>
                                        <button type="submit" name="action" value="unban" class="btn btn-success btn-sm">Разбанить</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['role'] === 'user'): ?>
                                        <button type="submit" name="action" value="promote" class="btn btn-warning btn-sm">Сделать модератором</button>
                                    <?php elseif ($user['role'] === 'moderator'): ?>
                                        <button type="submit" name="action" value="demote" class="btn btn-primary btn-sm">Понизить</button>
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