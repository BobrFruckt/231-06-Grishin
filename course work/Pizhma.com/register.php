<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Проверка на латинские буквы в логине
    if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
        $error = "Логин должен содержать только латинские буквы и цифры";
    }
    // Проверка email на латинские символы
    elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $error = "Email должен содержать только латинские буквы, цифры и стандартные символы (@._-)";
    }
    // Проверка совпадения паролей
    elseif ($password !== $confirm_password) {
        $error = "Пароли не совпадают";
    } 
    // Проверка на латинские буквы в пароле
    elseif (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+]+$/', $password)) {
        $error = "Пароль должен содержать только латинские буквы, цифры и специальные символы";
    }
    // Проверка длины пароля
    elseif (strlen($password) < 6) {
        $error = "Пароль должен содержать не менее 6 символов";
    }
    // Проверка согласия с политикой конфиденциальности
    elseif (!isset($_POST['privacy_policy'])) {
        $error = "Необходимо согласиться с политикой конфиденциальности";
    } else {
        // Проверка уникальности логина
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Этот логин уже занят, выберите другой";
        }
        // Проверка уникальности email
        else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Этот email уже зарегистрирован";
            } else {
                // Хеширование пароля
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $password_hash, $email]);
                    header('Location: login.php?registered=1');
                    exit;
                } catch (PDOException $e) {
                    $error = "Ошибка регистрации: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация - Pizhma.com</title>
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
        .switch-auth {
            margin-top: 15px;
            font-size: 14px;
        }
        .privacy-checkbox {
            display: flex;
            align-items: center;
            margin: 10px 0;
            font-size: 14px;
        }
        .privacy-checkbox input {
            width: auto;
            margin-right: 8px;
        }
        .privacy-link {
            color: #4CAF50;
            text-decoration: none;
        }
        .privacy-link:hover {
            text-decoration: underline;
        }
        .password-hint, .username-hint, .email-hint {
            font-size: 12px;
            color: #666;
            margin: 5px 0 10px 0;
            text-align: left;
            padding-left: 5px;
        }
        .availability-check {
            font-size: 12px;
            margin-top: -5px;
            margin-bottom: 10px;
            text-align: left;
            padding-left: 5px;
        }
        .available {
            color: green;
        }
        .taken {
            color: red;
        }
    </style>
    <script>
        function validateLatin(event) {
            const key = event.key;
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
        
        function validateLatinUsername(event) {
            const key = event.key;
            if (!/[a-zA-Z0-9]/.test(key) && 
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

        function validateLatinEmail(event) {
            const key = event.key;
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

        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                button.innerHTML = "👁️";
            } else {
                input.type = "password";
                button.innerHTML = "👁";
            }
        }

        // Проверка доступности логина в реальном времени
        function checkUsernameAvailability() {
            const username = document.querySelector('input[name="username"]').value;
            if (username.length < 3) return;
            
            fetch('check_availability.php?type=username&value=' + encodeURIComponent(username))
                .then(response => response.json())
                .then(data => {
                    const availabilityElement = document.getElementById('username-availability');
                    availabilityElement.style.display = 'block';
                    availabilityElement.className = 'availability-check ' + (data.available ? 'available' : 'taken');
                    availabilityElement.textContent = data.available 
                        ? '✓ Логин доступен' 
                        : '✗ Этот логин уже занят';
                });
        }

        // Проверка доступности email в реальном времени
        function checkEmailAvailability() {
            const email = document.querySelector('input[name="email"]').value;
            if (email.length < 5 || email.indexOf('@') === -1) return;
            
            fetch('check_availability.php?type=email&value=' + encodeURIComponent(email))
                .then(response => response.json())
                .then(data => {
                    const availabilityElement = document.getElementById('email-availability');
                    availabilityElement.style.display = 'block';
                    availabilityElement.className = 'availability-check ' + (data.available ? 'available' : 'taken');
                    availabilityElement.textContent = data.available 
                        ? '✓ Email доступен' 
                        : '✗ Этот email уже зарегистрирован';
                });
        }
    </script>
</head>
<body>
    <div class="auth-container">
        <h2>Pizhma.com</h2>
        <p>Регистрация</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Логин" required 
                   onkeypress="validateLatinUsername(event)"
                   oninput="checkUsernameAvailability()"
                   pattern="[a-zA-Z0-9]+"
                   title="Только латинские буквы и цифры">
            <div class="username-hint">Только латинские буквы и цифры</div>
            <div id="username-availability" class="availability-check" style="display: none;"></div>
            
            <input type="email" name="email" placeholder="Email" required
                   onkeypress="validateLatinEmail(event)"
                   oninput="checkEmailAvailability()"
                   pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                   title="Только латинские буквы, цифры и символы @._-">
            <div class="email-hint">Только латинские буквы, цифры и символы @._-</div>
            <div id="email-availability" class="availability-check" style="display: none;"></div>
            
            <div class="input-group">
                <input type="password" id="password" name="password" placeholder="Пароль" required
                       onkeypress="validateLatin(event)"
                       pattern="[a-zA-Z0-9!@#$%^&*()_+]+"
                       title="Латинские буквы, цифры и !@#$%^&*()_+">
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('password', this)">👁</button>
            </div>
            <div class="password-hint">Не менее 6 символов: латинские буквы, цифры и !@#$%^&*()_+</div>
            
            <div class="input-group">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Подтвердите пароль" required
                       onkeypress="validateLatin(event)">
                <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)">👁</button>
            </div>
            
            <div class="privacy-checkbox">
                <input type="checkbox" id="privacy_policy" name="privacy_policy" required>
                <label for="privacy_policy">Я согласен с <a href="privacy.php" class="privacy-link" target="_blank">политикой конфиденциальности</a></label>
            </div>
            
            <button type="submit">Зарегистрироваться</button>
        </form>
        
        <div class="switch-auth">
            Есть аккаунт? <a href="login.php">Войти</a>
        </div>
    </div>
</body>
</html>