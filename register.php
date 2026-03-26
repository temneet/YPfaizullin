<?php
require_once 'config/database.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    // Валидация
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } else {
        $database = new Database();
        $db = $database->getConnection();
        
        // Проверка существования пользователя
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ? AND deleted_at IS NULL");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = 'Пользователь с таким именем или email уже существует';
        } else {
            // Создание пользователя
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password_hash, full_name) 
                VALUES (?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$username, $email, $password_hash, $full_name])) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
                // Перенаправление на страницу входа через 2 секунды
                header("refresh:2;url=login.php");
            } else {
                $error = 'Ошибка при регистрации. Пожалуйста, попробуйте позже.';
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
    <title>Регистрация - StreamHub</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <h1>🎬 StreamHub</h1>
                <p>Создайте аккаунт</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="register-form" id="registerForm">
                <div class="form-group">
                    <label for="username">Имя пользователя *</label>
                    <input type="text" id="username" name="username" required 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <div class="error-message" id="usernameError"></div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <div class="error-message" id="emailError"></div>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Полное имя</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль *</label>
                    <input type="password" id="password" name="password" required>
                    <div class="error-message" id="passwordError"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Подтверждение пароля *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <div class="error-message" id="confirmError"></div>
                </div>
                
                <button type="submit" class="register-button">Зарегистрироваться</button>
            </form>
            
            <div class="register-footer">
                <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
            </div>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('registerForm');
        const username = document.getElementById('username');
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const confirm = document.getElementById('confirm_password');
        
        function validateUsername() {
            const error = document.getElementById('usernameError');
            if (username.value.length < 3) {
                error.textContent = 'Имя пользователя должно содержать минимум 3 символа';
                return false;
            }
            error.textContent = '';
            return true;
        }
        
        function validateEmail() {
            const error = document.getElementById('emailError');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email.value)) {
                error.textContent = 'Введите корректный email адрес';
                return false;
            }
            error.textContent = '';
            return true;
        }
        
        function validatePassword() {
            const error = document.getElementById('passwordError');
            if (password.value.length < 6) {
                error.textContent = 'Пароль должен содержать минимум 6 символов';
                return false;
            }
            error.textContent = '';
            return true;
        }
        
        function validateConfirm() {
            const error = document.getElementById('confirmError');
            if (password.value !== confirm.value) {
                error.textContent = 'Пароли не совпадают';
                return false;
            }
            error.textContent = '';
            return true;
        }
        
        username.addEventListener('input', validateUsername);
        email.addEventListener('input', validateEmail);
        password.addEventListener('input', validatePassword);
        confirm.addEventListener('input', validateConfirm);
        
        form.addEventListener('submit', (e) => {
            const isValid = validateUsername() && validateEmail() && 
                           validatePassword() && validateConfirm();
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>