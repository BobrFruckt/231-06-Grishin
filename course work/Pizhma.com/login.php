<?php
require 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $password = $_POST['password'];
    
    try {
        // Пытаемся найти пользователя по логину или email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = "Неверные данные для входа";
        }
    } catch (PDOException $e) {
        $error = "Ошибка входа: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход - Pizhma.com</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .auth-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 300px;
            text-align: center;
        }
        .auth-container h2 {
            margin-top: 0;
            color: #333;
        }
        .input-group {
            position: relative;
            margin: 10px 0;
        }
        .auth-container input {
            width: 100%;
            padding: 10px;
            padding-right: 40px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
        }
        .toggle-password:hover {
            color: #333;
        }
        .auth-container button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        .auth-container button[type="submit"]:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .success {
            color: green;
            margin-bottom: 10px;
        }
        .switch-auth {
            margin-top: 15px;
            font-size: 14px;
        }
        .login-hint {
            font-size: 12px;
            color: #666;
            margin: 5px 0 10px 0;
            text-align: left;
            padding-left: 5px;
        }
    </style>
    <script>
        function validateLatin(event) {
            const key = event.key;
            // Разрешаем: латинские буквы, цифры, @._- для логина/email
            if (!/[a-zA-Z0-9@._-]/.test(key) && 
                key !== 'Backspace' && 
                key !== 'Delete' && 
                key !== 'Tab' &&
                key !== 'ArrowLeft' &&
                key !== 'ArrowRight' &&
                key !== 'Home' &&
                key !== 'End') {
                event.preventDefault();
            }
        }

        function validateLatinPassword(event) {
            const key = event.key;
            // Разрешаем: латинские буквы, цифры, спецсимволы для пароля
            if (!/[a-zA-Z0-9!@#$%^&*()_+]/.test(key) && 
                key !== 'Backspace' && 
                key !== 'Delete' && 
                key !== 'Tab' &&
                key !== 'ArrowLeft' &&
                key !== 'ArrowRight' &&
                key !== 'Home' &&
                key !== 'End') {
                event.preventDefault();
            }
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.toggle-password');
            
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                toggleButton.innerHTML = "👁️";
            } else {
                passwordInput.type = "password";
                toggleButton.innerHTML = "👁";
            }
        }
    </script>
</head>
<body>
    <div class="auth-container">
        <h2>Pizhma.com</h2>
        <p>Войти</p>
        
        <?php if (isset($_GET['registered'])): ?>
            <div class="success">Успешная регистрация! Теперь вы можете войти</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <input type="text" name="login" placeholder="Логин или Email" required
                       onkeypress="validateLatin(event)"
                       pattern="[a-zA-Z0-9@._-]+"
                       title="Только латинские буквы, цифры и символы @._-">
            </div>
            <div class="login-hint">Только латинские буквы, цифры и символы @._-</div>
            
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Пароль" required
                       onkeypress="validateLatinPassword(event)"
                       pattern="[a-zA-Z0-9!@#$%^&*()_+]+"
                       title="Латинские буквы, цифры и !@#$%^&*()_+">
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility()">👁</button>
            </div>
            <div class="login-hint">Только латинские буквы, цифры и !@#$%^&*()_+</div>
            
            <button type="submit">Войти</button>
        </form>
        
        <div class="switch-auth">
            Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
        </div>
    </div>
</body>
</html>