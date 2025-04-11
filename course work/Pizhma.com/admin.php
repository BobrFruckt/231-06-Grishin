<?php
// Настройки сессии
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

require 'config.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Проверка существования роли с дефолтным значением
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Доступ запрещен. Недостаточно прав. Ваша роль: " . ($_SESSION['role'] ?? 'не определена'));
}

// Получаем статистику сайта
$stats = [];
$users = [];
$reports = [];

try {
    // Общая статистика
    $stats['users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['topics'] = $pdo->query("SELECT COUNT(*) FROM topics")->fetchColumn();
    $stats['comments'] = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $stats['reports'] = $pdo->query("SELECT COUNT(*) FROM reports WHERE resolved = 0")->fetchColumn();

    // Последние зарегистрированные пользователи
    $users = $pdo->query("SELECT id, username, email, created_at, role FROM users ORDER BY created_at DESC LIMIT 10")->fetchAll();

    // Необработанные жалобы
    $reports = $pdo->query("SELECT r.*, u.username as reporter FROM reports r 
                           JOIN users u ON r.reporter_id = u.id 
                           WHERE r.resolved = 0 ORDER BY r.created_at DESC")->fetchAll();

} catch (PDOException $e) {
    die("Ошибка загрузки данных: " . $e->getMessage());
}

// Обработка действий администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Ошибка безопасности. Недействительный CSRF токен.");
    }

    $action = $_POST['action'] ?? '';
    $target_id = $_POST['id'] ?? 0;

    try {
        switch ($action) {
            case 'ban_user':
                $stmt = $pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ?");
                $stmt->execute([$target_id]);
                $_SESSION['admin_message'] = "Пользователь успешно заблокирован";
                break;

            case 'unban_user':
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$target_id]);
                $_SESSION['admin_message'] = "Пользователь успешно разблокирован";
                break;

            case 'delete_user':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$target_id]);
                $_SESSION['admin_message'] = "Пользователь успешно удален";
                break;

            case 'promote_to_moderator':
                $stmt = $pdo->prepare("UPDATE users SET role = 'moderator' WHERE id = ?");
                $stmt->execute([$target_id]);
                $_SESSION['admin_message'] = "Пользователь повышен до модератора";
                break;

            case 'demote_to_user':
                $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                $stmt->execute([$target_id]);
                $_SESSION['admin_message'] = "Пользователь понижен до обычного пользователя";
                break;

            case 'resolve_report':
                $stmt = $pdo->prepare("UPDATE reports SET resolved = 1, resolved_at = NOW(), resolver_id = ? WHERE id = ?");
                $stmt->execute([$_SESSION['user_id'], $target_id]);
                $_SESSION['admin_message'] = "Жалоба помечена как решенная";
                break;

            case 'delete_topic':
                $stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
                $stmt->execute([$target_id]);
                $_SESSION['admin_message'] = "Тема успешно удалена";
                break;

            case 'delete_comment':
                $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$target_id]);
                $_SESSION['admin_message'] = "Комментарий успешно удален";
                break;
        }

        header("Location: admin.php");
        exit;

    } catch (PDOException $e) {
        die("Ошибка выполнения действия: " . $e->getMessage());
    }
}

// Генерация CSRF токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | Pizhma.com</title>
    <style>
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
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }
        
        /* Навигационная панель */
        .admin-navbar {
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
        
        .admin-navbar .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }
        
        .admin-nav-links {
            display: flex;
            gap: 20px;
        }
        
        .admin-nav-links a {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 3px;
            transition: background-color 0.3s;
        }
        
        .admin-nav-links a:hover {
            background-color: var(--secondary-color);
        }
        
        .admin-nav-links .active {
            background-color: var(--primary-color);
        }
        
        .container {
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-color);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .users-stat { color: var(--primary-color); }
        .topics-stat { color: var(--success-color); }
        .comments-stat { color: var(--warning-color); }
        .reports-stat { color: var(--danger-color); }
        
        /* Таблицы */
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
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Кнопки действий */
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        /* Сообщения */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        /* Вкладки */
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
        
        /* Адаптивность */
        @media (max-width: 768px) {
            .admin-navbar {
                flex-direction: column;
                padding: 10px;
            }
            
            .admin-nav-links {
                margin-top: 10px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <!-- Навигационная панель админки -->
    <nav class="admin-navbar">
        <a href="admin.php" class="logo">Pizhma.com | Админ-панель</a>
        <div class="admin-nav-links">
            <a href="index.php">На сайт</a>
            <a href="admin.php" class="active">Главная</a>
            <a href="admin_users.php">Пользователи</a>
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
        
        <h1>Административная панель</h1>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <div class="stat-card">
                <div>Пользователи</div>
                <div class="stat-value users-stat"><?php echo $stats['users']; ?></div>
                <a href="admin_users.php" class="btn btn-primary btn-sm">Управление</a>
            </div>
            
            <div class="stat-card">
                <div>Темы</div>
                <div class="stat-value topics-stat"><?php echo $stats['topics']; ?></div>
                <a href="admin_content.php?type=topics" class="btn btn-primary btn-sm">Управление</a>
            </div>
            
            <div class="stat-card">
                <div>Комментарии</div>
                <div class="stat-value comments-stat"><?php echo $stats['comments']; ?></div>
                <a href="admin_content.php?type=comments" class="btn btn-primary btn-sm">Управление</a>
            </div>
            
            <div class="stat-card">
                <div>Новые жалобы</div>
                <div class="stat-value reports-stat"><?php echo $stats['reports']; ?></div>
                <a href="admin_reports.php" class="btn btn-primary btn-sm">Просмотр</a>
            </div>
        </div>
        
        <!-- Последние пользователи -->
        <div class="admin-section">
            <h2 class="section-title">Последние зарегистрированные пользователи</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Логин</th>
                        <th>Email</th>
                        <th>Дата регистрации</th>
                        <th>Роль</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="btn btn-danger btn-sm">Админ</span>
                                <?php elseif ($user['role'] === 'moderator'): ?>
                                    <span class="btn btn-warning btn-sm">Модератор</span>
                                <?php else: ?>
                                    <span class="btn btn-primary btn-sm">Пользователь</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    
                                    <?php if ($user['role'] === 'user'): ?>
                                        <button type="submit" name="action" value="promote_to_moderator" class="btn btn-warning btn-sm">Сделать модератором</button>
                                    <?php elseif ($user['role'] === 'moderator'): ?>
                                        <button type="submit" name="action" value="demote_to_user" class="btn btn-primary btn-sm">Понизить</button>
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="action" value="ban_user" class="btn btn-danger btn-sm">Забанить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="text-align: right; margin-top: 15px;">
                <a href="admin_users.php" class="btn btn-primary">Все пользователи</a>
            </div>
        </div>
        
        <!-- Необработанные жалобы -->
        <div class="admin-section">
            <h2 class="section-title">Необработанные жалобы</h2>
            <?php if (empty($reports)): ?>
                <p>Нет необработанных жалоб.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Тип</th>
                            <th>От кого</th>
                            <th>Дата</th>
                            <th>Причина</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo $report['id']; ?></td>
                                <td><?php echo htmlspecialchars($report['type']); ?></td>
                                <td><?php echo htmlspecialchars($report['reporter']); ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($report['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars(substr($report['reason'], 0, 50)); ?>...</td>
                                <td>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                        <button type="submit" name="action" value="resolve_report" class="btn btn-success btn-sm">Решено</button>
                                        <a href="view_report.php?id=<?php echo $report['id']; ?>" class="btn btn-primary btn-sm">Подробнее</a>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: right; margin-top: 15px;">
                    <a href="admin_reports.php" class="btn btn-primary">Все жалобы</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Инициализация вкладок (если будут добавлены)
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                tab.classList.add('active');
                const tabId = tab.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Подтверждение опасных действий
        document.querySelectorAll('form').forEach(form => {
            const action = form.querySelector('button[name="action"]');
            if (action && ['ban_user', 'delete_user', 'delete_topic', 'delete_comment'].includes(action.value)) {
                form.addEventListener('submit', (e) => {
                    if (!confirm('Вы уверены? Это действие нельзя отменить.')) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>