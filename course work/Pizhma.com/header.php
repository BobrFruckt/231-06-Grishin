<?php
session_start();
?>

<style>
    .navbar {
        background-color: #2c3e50;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: white;
    }

    .navbar a {
        color: white;
        text-decoration: none;
        margin: 0 10px;
        font-weight: 500;
    }

    .navbar a:hover {
        text-decoration: underline;
    }

    .nav-left,
    .nav-right {
        display: flex;
        align-items: center;
    }

    .navbar .logo {
        font-weight: bold;
        font-size: 20px;
        margin-right: 20px;
    }
</style>

<div class="navbar">
    <div class="nav-left">
        <a href="index.php" class="logo">Pizhma.com</a>
        <a href="forum.php">Форум</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="new_topic.php">Новая тема</a>
        <?php endif; ?>
    </div>
    <div class="nav-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="edit_profile.php"><?= htmlspecialchars($_SESSION['username'] ?? 'Профиль') ?></a>
            <a href="logout.php">Выход</a>
        <?php else: ?>
            <a href="login.php">Вход</a>
            <a href="register.php">Регистрация</a>
        <?php endif; ?>
    </div>
</div>
