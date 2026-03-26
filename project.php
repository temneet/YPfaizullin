<?php
require_once 'config/database.php';
redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();
$project_id = $_GET['id'] ?? null;
$is_edit = $project_id !== null;

// Обработка создания/редактирования проекта
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'movie';
    $release_year = $_POST['release_year'] ?? null;
    
    if (empty($title)) {
        $error = 'Название проекта обязательно';
    } else {
        if ($is_edit) {
            // Проверка прав доступа
            $stmt = $db->prepare("SELECT created_by FROM projects WHERE id = ?");
            $stmt->execute([$project_id]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($project['created_by'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin') {
                $error = 'У вас нет прав на редактирование этого проекта';
            } else {
                $stmt = $db->prepare("
                    UPDATE projects 
                    SET title = ?, description = ?, type = ?, release_year = ?
                    WHERE id = ?
                ");
                if ($stmt->execute([$title, $description, $type, $release_year, $project_id])) {
                    $success = 'Проект успешно обновлен';
                } else {
                    $error = 'Ошибка при обновлении проекта';
                }
            }
        } else {
            $stmt = $db->prepare("
                INSERT INTO projects (title, description, type, release_year, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$title, $description, $type, $release_year, $_SESSION['user_id']])) {
                $project_id = $db->lastInsertId();
                $success = 'Проект успешно создан';
                header("Location: project.php?id=$project_id");
                exit();
            } else {
                $error = 'Ошибка при создании проекта';
            }
        }
    }
}

// Получение данных проекта для редактирования
$project = null;
if ($is_edit) {
    $stmt = $db->prepare("
        SELECT p.*, u.username as creator_name
        FROM projects p
        JOIN users u ON p.created_by = u.id
        WHERE p.id = ? AND p.deleted_at IS NULL
    ");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        header("Location: index.php");
        exit();
    }
}

// Получение комментариев
if ($is_edit) {
    $stmt = $db->prepare("
        SELECT c.*, u.username 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.project_id = ? AND c.deleted_at IS NULL
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$project_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Добавление комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment']) && $is_edit) {
    $content = trim($_POST['content'] ?? '');
    if (!empty($content)) {
        $stmt = $db->prepare("INSERT INTO comments (project_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$project_id, $_SESSION['user_id'], $content]);
        header("Location: project.php?id=$project_id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Редактирование проекта' : 'Создание проекта' ?> - StreamHub</title>
    <link rel="stylesheet" href="css/project.css">
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <h1>🎬 StreamHub</h1>
            </div>
            <div class="nav-links">
                <a href="index.php">Главная</a>
                <a href="profile.php">Профиль</a>
                <a href="project.php" class="active">Создать проект</a>
                <a href="kanban.php">Доска задач</a>
                <a href="logout.php">Выйти</a>
            </div>
        </nav>
    </header>

    <main class="container">
        <?php if($is_edit && $project): ?>
            <div class="project-header">
                <h1><?= htmlspecialchars($project['title']) ?></h1>
                <div class="project-meta">
                    <span>Создатель: <?= htmlspecialchars($project['creator_name']) ?></span>
                    <span>Тип: <?= $project['type'] == 'movie' ? 'Фильм' : 'Сериал' ?></span>
                    <?php if($project['release_year']): ?>
                        <span>Год: <?= $project['release_year'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <h1>Создание нового проекта</h1>
        <?php endif; ?>

        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST" class="project-form">
            <div class="form-group">
                <label for="title">Название проекта *</label>
                <input type="text" id="title" name="title" required 
                       value="<?= htmlspecialchars($project['title'] ?? '') ?>">
                <div class="error-message" id="titleError"></div>
            </div>
            
            <div class="form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description" rows="6"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type">Тип</label>
                    <select id="type" name="type">
                        <option value="movie" <?= ($project['type'] ?? '') == 'movie' ? 'selected' : '' ?>>Фильм</option>
                        <option value="series" <?= ($project['type'] ?? '') == 'series' ? 'selected' : '' ?>>Сериал</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="release_year">Год выпуска</label>
                    <input type="number" id="release_year" name="release_year" 
                           value="<?= htmlspecialchars($project['release_year'] ?? '') ?>"
                           min="1900" max="<?= date('Y') ?>">
                </div>
            </div>
            
            <button type="submit" class="btn-primary">
                <?= $is_edit ? 'Обновить проект' : 'Создать проект' ?>
            </button>
        </form>

        <?php if($is_edit && $project): ?>
            <div class="comments-section">
                <h3>Комментарии</h3>
                
                <form method="POST" class="comment-form">
                    <textarea name="content" placeholder="Напишите комментарий..." rows="3" required></textarea>
                    <button type="submit" name="add_comment" class="btn-secondary">Отправить</button>
                </form>
                
                <div class="comments-list">
                    <?php if(isset($comments) && count($comments) > 0): ?>
                        <?php foreach($comments as $comment): ?>
                            <div class="comment">
                                <div class="comment-header">
                                    <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                    <span class="comment-date"><?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?></span>
                                </div>
                                <div class="comment-content">
                                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-comments">Пока нет комментариев. Будьте первым!</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        const titleInput = document.getElementById('title');
        const titleError = document.getElementById('titleError');
        
        titleInput.addEventListener('input', () => {
            if (titleInput.value.length < 3) {
                titleError.textContent = 'Название должно содержать минимум 3 символа';
            } else {
                titleError.textContent = '';
            }
        });
    </script>
</body>
</html>