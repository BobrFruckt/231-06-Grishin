<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require 'config.php';

// Генерируем CSRF токен если его нет
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Получаем текущие данные пользователя
$user = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    die("Ошибка загрузки данных пользователя: " . $e->getMessage());
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Ошибка безопасности. Пожалуйста, попробуйте еще раз.";
    } else {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $about = trim($_POST['about']);
        $website = trim($_POST['website']);
        $location = trim($_POST['location']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Валидация
        if (empty($username)) {
            $errors[] = "Логин не может быть пустым";
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            $errors[] = "Логин должен содержать только латинские буквы и цифры";
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Некорректный email";
        }

        // Проверка пароля, если пользователь хочет его изменить
        if (!empty($new_password)) {
            if (empty($current_password)) {
                $errors[] = "Для смены пароля введите текущий пароль";
            } elseif (!password_verify($current_password, $user['password'])) {
                $errors[] = "Неверный текущий пароль";
            } elseif ($new_password !== $confirm_password) {
                $errors[] = "Новые пароли не совпадают";
            } elseif (strlen($new_password) < 6) {
                $errors[] = "Пароль должен содержать минимум 6 символов";
            } elseif (!preg_match('/^[a-zA-Z0-9!@#$%^&*()_+]+$/', $new_password)) {
                $errors[] = "Пароль содержит недопустимые символы";
            }
        }

        // Проверка уникальности логина и email
        if ($username !== $user['username']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Этот логин уже занят";
            }
        }

        if ($email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Этот email уже зарегистрирован";
            }
        }

        // Обработка загрузки аватара
        $avatar_path = $user['avatar'];
        if (!empty($_FILES['avatar']['name'])) {
            $upload_dir = 'uploads/avatars/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_types)) {
                // Проверяем размер файла (максимум 2MB)
                if ($_FILES['avatar']['size'] > 2097152) {
                    $errors[] = "Размер файла не должен превышать 2MB";
                } else {
                    $file_name = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $file_path)) {
                        $avatar_path = $file_path;
                        // Удаляем старый аватар, если он не дефолтный
                        if (!empty($user['avatar']) && strpos($user['avatar'], 'default-avatar.png') === false) {
                            @unlink($user['avatar']);
                        }
                    } else {
                        $errors[] = "Ошибка загрузки аватара";
                    }
                }
            } else {
                $errors[] = "Недопустимый тип файла. Разрешены только JPG, PNG и GIF";
            }
        }

        // Если ошибок нет - обновляем данные
        if (empty($errors)) {
            try {
                $update_data = [
                    'username' => $username,
                    'email' => $email,
                    'about' => $about,
                    'website' => $website,
                    'location' => $location,
                    'avatar' => $avatar_path,
                    'id' => $_SESSION['user_id']
                ];

                // Если меняем пароль
                if (!empty($new_password)) {
                    $update_data['password'] = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET username = :username, email = :email, about = :about, 
                            website = :website, location = :location, avatar = :avatar, password = :password 
                            WHERE id = :id";
                } else {
                    $sql = "UPDATE users SET username = :username, email = :email, about = :about, 
                            website = :website, location = :location, avatar = :avatar WHERE id = :id";
                }

                $stmt = $pdo->prepare($sql);
                $stmt->execute($update_data);

                // Обновляем данные в сессии
                $_SESSION['username'] = $username;
                if (!empty($update_data['avatar'])) {
                    $_SESSION['avatar'] = $update_data['avatar'];
                }
                
                // Генерируем новый CSRF токен
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
                $success = true;
            } catch (PDOException $e) {
                $errors[] = "Ошибка обновления данных: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование профиля - <?php echo htmlspecialchars($user['username']); ?> | Pizhma.com</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --error-color: #e74c3c;
            --success-color: #27ae60;
            --text-color: #333;
            --light-text: #7f8c8d;
            --border-color: #ddd;
            --background-color: #f5f7fa;
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
        
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            margin-top: 0;
            color: var(--primary-color);
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        textarea:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .btn:hover {
            background-color: var(--secondary-color);
        }
        
        .btn-cancel {
            background-color: var(--light-text);
            margin-left: 15px;
        }
        
        .btn-cancel:hover {
            background-color: #6c7a7d;
        }
        
        .error {
            color: var(--error-color);
            margin: 8px 0;
            font-size: 14px;
        }
        
        .success {
            color: var(--success-color);
            margin: 15px 0;
            padding: 10px;
            background-color: rgba(39, 174, 96, 0.1);
            border-radius: 4px;
            text-align: center;
        }
        
        .password-section {
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }
        
        .password-section h2 {
            margin-top: 0;
            font-size: 20px;
            color: var(--text-color);
        }
        
        .avatar-upload {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 4px solid #f1f1f1;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .avatar-upload-controls {
            flex: 1;
            min-width: 250px;
        }
        
        .avatar-upload-info {
            font-size: 14px;
            color: var(--light-text);
            margin-top: 10px;
        }
        
        .form-actions {
            display: flex;
            justify-content: center;
            margin-top: 30px;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 20px;
            }
            
            .avatar-upload {
                flex-direction: column;
                text-align: center;
            }
            
            .avatar-preview {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-cancel {
                margin-left: 0;
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Редактирование профиля</h1>
        
        <?php if ($success): ?>
            <div class="success">Профиль успешно обновлен!</div>
        <?php endif; ?>
        
        <?php foreach ($errors as $error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endforeach; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="avatar-upload">
                <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : 'images/default-avatar.png'; ?>" 
                     alt="Аватар" class="avatar-preview" id="avatar-preview">
                <div class="avatar-upload-controls">
                    <label for="avatar">Аватар профиля</label>
                    <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/gif">
                    <p class="avatar-upload-info">Рекомендуемый размер: 200x200 пикселей. Форматы: JPG, PNG, GIF (макс. 2MB)</p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="username">Логин</label>
                <input type="text" id="username" name="username" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" required
                       pattern="[a-zA-Z0-9]+" title="Только латинские буквы и цифры">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="about">О себе</label>
                <textarea id="about" name="about" maxlength="500"><?php echo htmlspecialchars($user['about']); ?></textarea>
                <small>Максимум 500 символов</small>
            </div>
            
            <div class="form-group">
                <label for="website">Веб-сайт</label>
                <input type="text" id="website" name="website" 
                       value="<?php echo htmlspecialchars($user['website']); ?>"
                       placeholder="https://example.com">
            </div>
            
            <div class="form-group">
                <label for="location">Местоположение</label>
                <input type="text" id="location" name="location" 
                       value="<?php echo htmlspecialchars($user['location']); ?>"
                       placeholder="Город, страна">
            </div>
            
            <div class="password-section">
                <h2>Смена пароля</h2>
                <p>Заполните эти поля только если хотите изменить пароль.</p>
                
                <div class="form-group">
                    <label for="current_password">Текущий пароль</label>
                    <input type="password" id="current_password" name="current_password"
                           placeholder="Введите текущий пароль">
                </div>
                
                <div class="form-group">
                    <label for="new_password">Новый пароль</label>
                    <input type="password" id="new_password" name="new_password"
                           placeholder="Не менее 6 символов"
                           pattern="[a-zA-Z0-9!@#$%^&*()_+]+" 
                           title="Латинские буквы, цифры и специальные символы">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Подтвердите новый пароль</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Повторите новый пароль">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn">Сохранить изменения</button>
                <a href="dashboard.php" class="btn btn-cancel">Отмена</a>
            </div>
        </form>
    </div>

    <script>
        // Превью аватара
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Подсчет символов в поле "О себе"
        const aboutTextarea = document.getElementById('about');
        const aboutCounter = document.createElement('div');
        aboutCounter.style.fontSize = '12px';
        aboutCounter.style.color = '#7f8c8d';
        aboutCounter.style.textAlign = 'right';
        aboutTextarea.parentNode.insertBefore(aboutCounter, aboutTextarea.nextSibling);

        function updateCounter() {
            const currentLength = aboutTextarea.value.length;
            aboutCounter.textContent = `${currentLength}/500 символов`;
        }

        aboutTextarea.addEventListener('input', updateCounter);
        updateCounter();
    </script>
</body>
</html>