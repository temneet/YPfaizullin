<?php
require_once 'config/database.php';
redirectIfNotLoggedIn();

$user = getUserById($_SESSION['user_id']);
$database = new Database();
$db = $database->getConnection();

// Получение проектов пользователя
$stmt = $db->prepare("
    SELECT * FROM projects 
    WHERE created_by = ? AND deleted_at IS NULL 
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обновление профиля
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        $stmt = $db->prepare("UPDATE users SET full_name = ?, bio = ? WHERE id = ?");
        if ($stmt->execute([$full_name, $bio, $_SESSION['user_id']])) {
            $success = 'Профиль успешно обновлен';
            $user = getUserById($_SESSION['user_id']);
        } else {
            $error = 'Ошибка при обновлении профиля';
        }
    } elseif (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (password_verify($current, $user['password_hash'])) {
            if ($new === $confirm && strlen($new) >= 6) {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                if ($stmt->execute([$new_hash, $_SESSION['user_id']])) {
                    $success = 'Пароль успешно изменен';
                } else {
                    $error = 'Ошибка при смене пароля';
                }
            } else {
                $error = 'Новый пароль должен содержать минимум 6 символов и совпадать с подтверждением';
            }
        } else {
            $error = 'Текущий пароль неверен';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет - StreamHub</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <h1>🎬 StreamHub</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Главная</a>
                <a href="profile.php" class="active">Профиль</a>
                <a href="project.php">Создать проект</a>
                <a href="kanban.php">Доска задач</a>
                <a href="logout.php">Выйти</a>
            </div>
        </nav>
    </header>

    <main class="profile-container">
        <div class="profile-sidebar">
            <div class="profile-avatar">
                <?php if($user['avatar']): ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <?= strtoupper(substr($user['username'], 0, 2)) ?>
                    </div>
                <?php endif; ?>
            </div>
            <h2><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h2>
            <p>@<?= htmlspecialchars($user['username']) ?></p>
            <p class="user-role"><?= $user['role'] == 'admin' ? 'Администратор' : 'Пользователь' ?></p>
            <div class="profile-stats">
                <div class="stat">
                    <span class="stat-number"><?= count($user_projects) ?></span>
                    <span class="stat-label">Проектов</span>
                </div>
            </div>
        </div>

        <div class="profile-content">
            <?php if($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="profile-section">
                <h3>Редактировать профиль</h3>
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="full_name">Полное имя</label>
                        <input type="text" id="full_name" name="full_name" 
                               value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="bio">О себе</label>
                        <textarea id="bio" name="bio" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn-primary">Сохранить изменения</button>
                </form>
            </div>

            <div class="profile-section">
                <h3>Сменить пароль</h3>
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="current_password">Текущий пароль</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Новый пароль</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Подтверждение пароля</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn-primary">Сменить пароль</button>
                </form>
            </div>

            <div class="profile-section">
                <h3>Мои проекты</h3>
                <div class="projects-list">
                    <?php foreach($user_projects as $project): ?>
                        <div class="project-item">
                            <div class="project-info">
                                <h4><?= htmlspecialchars($project['title']) ?></h4>
                                <p><?= htmlspecialchars(substr($project['description'], 0, 100)) ?></p>
                                <span class="project-status status-<?= $project['status'] ?>">
                                    <?= $project['status'] ?>
                                </span>
                            </div>
                            <div class="project-actions">
                                <a href="project.php?id=<?= $project['id'] ?>" class="btn-small">Просмотр</a>
                                <a href="kanban.php?project_id=<?= $project['id'] ?>" class="btn-small">Задачи</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="js/profile.js"></script>
</body>
</html>