<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

// Получение фильмов для отображения
$stmt = $db->prepare("
    SELECT p.*, u.username as creator_name,
    (SELECT COUNT(*) FROM likes WHERE project_id = p.id) as likes_count
    FROM projects p
    JOIN users u ON p.created_by = u.id
    WHERE p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT 12
");
$stmt->execute();
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StreamHub - Стриминговый сервис фильмов и сериалов</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <nav class="nav-container">
            <div class="logo">
                <h1>🎬 StreamHub</h1>
            </div>
            <div class="nav-links">
                <a href="index.php" class="active">Главная</a>
                <?php if(isLoggedIn()): ?>
                    <a href="profile.php">Профиль</a>
                    <a href="project.php">Создать проект</a>
                    <a href="kanban.php">Доска задач</a>
                    <a href="logout.php">Выйти</a>
                <?php else: ?>
                    <a href="login.php">Вход</a>
                    <a href="register.php">Регистрация</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <h1>Добро пожаловать в StreamHub</h1>
                <p>Создавайте, публикуйте и смотрите лучшие фильмы и сериалы</p>
                <?php if(!isLoggedIn()): ?>
                    <a href="register.php" class="cta-button">Начать сейчас</a>
                <?php endif; ?>
            </div>
        </section>

        <section class="featured">
            <div class="container">
                <h2>Популярные проекты</h2>
                <div class="filters">
                    <input type="text" id="searchInput" placeholder="Поиск проектов..." class="search-input">
                    <select id="typeFilter" class="filter-select">
                        <option value="all">Все</option>
                        <option value="movie">Фильмы</option>
                        <option value="series">Сериалы</option>
                    </select>
                </div>
                <div class="projects-grid" id="projectsGrid">
                    <?php foreach($projects as $project): ?>
                        <div class="project-card" data-title="<?= htmlspecialchars($project['title']) ?>" 
                             data-type="<?= $project['type'] ?>">
                            <div class="project-poster">
                                <?php if($project['poster_url']): ?>
                                    <img src="<?= htmlspecialchars($project['poster_url']) ?>" 
                                         alt="<?= htmlspecialchars($project['title']) ?>">
                                <?php else: ?>
                                    <div class="poster-placeholder">🎬</div>
                                <?php endif; ?>
                            </div>
                            <div class="project-info">
                                <h3><?= htmlspecialchars($project['title']) ?></h3>
                                <p class="project-type">
                                    <?= $project['type'] == 'movie' ? 'Фильм' : 'Сериал' ?>
                                </p>
                                <p class="project-description">
                                    <?= htmlspecialchars(substr($project['description'], 0, 100)) ?>...
                                </p>
                                <div class="project-meta">
                                    <span>❤️ <?= $project['likes_count'] ?></span>
                                    <span>👤 <?= htmlspecialchars($project['creator_name']) ?></span>
                                </div>
                                <button onclick="viewProject(<?= $project['id'] ?>)" 
                                        class="view-button">Подробнее</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <p>&copy; 2024 StreamHub. Все права защищены.</p>
    </footer>

    <script src="js/main.js"></script>
    <script>
        function viewProject(id) {
            window.location.href = `project.php?id=${id}`;
        }

        // Фильтрация проектов
        const searchInput = document.getElementById('searchInput');
        const typeFilter = document.getElementById('typeFilter');
        const projectsGrid = document.getElementById('projectsGrid');

        function filterProjects() {
            const searchTerm = searchInput.value.toLowerCase();
            const type = typeFilter.value;
            const cards = document.querySelectorAll('.project-card');

            cards.forEach(card => {
                const title = card.dataset.title.toLowerCase();
                const projectType = card.dataset.type;
                const matchesSearch = title.includes(searchTerm);
                const matchesType = type === 'all' || projectType === type;

                card.style.display = matchesSearch && matchesType ? 'block' : 'none';
            });
        }

        searchInput.addEventListener('input', filterProjects);
        typeFilter.addEventListener('change', filterProjects);
    </script>
</body>
</html>