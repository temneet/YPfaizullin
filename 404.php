<?php
/**
 * Страница 404 - Не найдено
 * 
 * Отображается при обращении к несуществующим страницам
 * Включает SEO-оптимизацию, поиск по сайту и рекомендации
 */

require_once 'config/database.php';

// Установка HTTP статуса 404
http_response_code(404);

// Получение запрошенного URL для логирования
$requested_url = $_SERVER['REQUEST_URI'];

// Логирование 404 ошибки (опционально)
if (isset($_SERVER['HTTP_REFERER'])) {
    $referer = $_SERVER['HTTP_REFERER'];
    error_log("404 Error: $requested_url - Referer: $referer - IP: {$_SERVER['REMOTE_ADDR']}");
}

// Получение популярных проектов для рекомендаций
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT p.id, p.title, p.type, p.poster_url,
    (SELECT COUNT(*) FROM likes WHERE project_id = p.id) as likes_count
    FROM projects p
    WHERE p.deleted_at IS NULL
    ORDER BY likes_count DESC, p.created_at DESC
    LIMIT 4
");
$stmt->execute();
$popular_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение популярных категорий
$stmt = $db->prepare("
    SELECT type, COUNT(*) as count 
    FROM projects 
    WHERE deleted_at IS NULL 
    GROUP BY type
");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// SEO мета-теги для страницы 404
$pageTitle = "Страница не найдена - 404 - StreamHub";
$pageDescription = "Страница, которую вы ищете, не существует или была перемещена. Вернитесь на главную или воспользуйтесь поиском.";
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="robots" content="noindex, follow">
    <meta name="referrer" content="no-referrer-when-downgrade">
    
    <!-- Каноническая ссылка (главная страница) -->
    <link rel="canonical" href="https://<?= $_SERVER['HTTP_HOST'] ?>/">
    
    <!-- Open Graph мета-теги -->
    <meta property="og:title" content="Страница не найдена - StreamHub">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://<?= $_SERVER['HTTP_HOST'] ?><?= $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:site_name" content="StreamHub">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Страница не найдена - StreamHub">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    
    <link rel="stylesheet" href="css/404.css">
    
    <style>
        /* Критический CSS для быстрой загрузки */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .error-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        @media (max-width: 768px) {
            .error-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <!-- Анимация 404 -->
        <div class="error-header">
            <div class="error-code">
                <span class="digit">4</span>
                <span class="digit">0</span>
                <span class="digit">4</span>
            </div>
            <div class="error-icon">
                <div class="broken-screen">🔍</div>
            </div>
            <h1 class="error-title">Страница не найдена</h1>
            <p class="error-message">
                <?php
                // Случайные сообщения для разнообразия
                $messages = [
                    'Кажется, вы забрели в неизведанные уголки StreamHub...',
                    'Страница, которую вы ищете, исчезла в потоке данных',
                    '404: Фильм не найден. Возможно, он еще в производстве?',
                    'Упс! Такой страницы нет в нашей фильмотеке',
                    'Похоже, этот контент переместился в другой проект'
                ];
                echo $messages[array_rand($messages)];
                ?>
            </p>
            
            <div class="search-box">
                <form action="search.php" method="get" class="search-form">
                    <input type="text" 
                           name="q" 
                           placeholder="Поиск фильмов, сериалов, проектов..." 
                           class="search-input"
                           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                    <button type="submit" class="search-button">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        Найти
                    </button>
                </form>
            </div>
            
            <div class="action-buttons">
                <a href="/" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2h-5v-8H9v8H5a2 2 0 0 1-2-2z"></path>
                    </svg>
                    На главную
                </a>
                <a href="catalog.php" class="btn btn-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                        <path d="M8 2v4M16 2v4M2 10h20"></path>
                    </svg>
                    Каталог
                </a>
                <button onclick="history.back()" class="btn btn-outline">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    Вернуться назад
                </button>
            </div>
        </div>
        
        <!-- Популярные категории -->
        <?php if(!empty($categories)): ?>
        <div class="categories-section">
            <h2 class="section-title">📁 Популярные категории</h2>
            <div class="categories-grid">
                <?php foreach($categories as $category): ?>
                    <a href="catalog.php?type=<?= urlencode($category['type']) ?>" class="category-card">
                        <span class="category-icon">
                            <?= $category['type'] == 'movie' ? '🎬' : '📺' ?>
                        </span>
                        <span class="category-name">
                            <?= $category['type'] == 'movie' ? 'Фильмы' : 'Сериалы' ?>
                        </span>
                        <span class="category-count"><?= $category['count'] ?> проектов</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Рекомендуемые проекты -->
        <?php if(!empty($popular_projects)): ?>
        <div class="recommended-section">
            <h2 class="section-title">🔥 Возможно, вас заинтересует</h2>
            <div class="projects-grid">
                <?php foreach($popular_projects as $project): ?>
                    <div class="project-card">
                        <div class="project-poster">
                            <?php if($project['poster_url']): ?>
                                <img src="<?= htmlspecialchars($project['poster_url']) ?>" 
                                     alt="<?= htmlspecialchars($project['title']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="poster-placeholder">
                                    <?= $project['type'] == 'movie' ? '🎬' : '📺' ?>
                                </div>
                            <?php endif; ?>
                            <div class="project-overlay">
                                <a href="project.php?id=<?= $project['id'] ?>" class="view-btn">Смотреть</a>
                            </div>
                        </div>
                        <div class="project-info">
                            <h3><?= htmlspecialchars($project['title']) ?></h3>
                            <div class="project-meta">
                                <span class="type"><?= $project['type'] == 'movie' ? 'Фильм' : 'Сериал' ?></span>
                                <span class="likes">❤️ <?= $project['likes_count'] ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Полезные ссылки -->
        <div class="helpful-links">
            <h3 class="helpful-title">Полезные ссылки</h3>
            <div class="links-grid">
                <a href="about.php" class="link-card">
                    <span>ℹ️</span>
                    <span>О проекте</span>
                </a>
                <a href="help.php" class="link-card">
                    <span>❓</span>
                    <span>Помощь</span>
                </a>
                <a href="contact.php" class="link-card">
                    <span>📧</span>
                    <span>Связаться с нами</span>
                </a>
                <a href="sitemap.xml" class="link-card">
                    <span>🗺️</span>
                    <span>Карта сайта</span>
                </a>
            </div>
        </div>
    </div>
    
    <footer class="error-footer">
        <p>&copy; <?= date('Y') ?> StreamHub. Все права защищены.</p>
    </footer>
    
    <script>
        // Анимация для цифр 404
        document.addEventListener('DOMContentLoaded', function() {
            const digits = document.querySelectorAll('.digit');
            digits.forEach((digit, index) => {
                digit.style.animation = `bounce 0.6s ease ${index * 0.1}s forwards`;
                digit.style.opacity = '0';
                digit.style.transform = 'translateY(50px)';
            });
            
            // Поиск при нажатии Enter
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const query = this.value.trim();
                        if (query) {
                            window.location.href = `search.php?q=${encodeURIComponent(query)}`;
                        }
                    }
                });
            }
        });
        
        // Добавление анимаций
        const style = document.createElement('style');
        style.textContent = `
            @keyframes bounce {
                0% {
                    opacity: 0;
                    transform: translateY(50px);
                }
                60% {
                    opacity: 1;
                    transform: translateY(-10px);
                }
                100% {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .digit {
                display: inline-block;
                animation-fill-mode: forwards;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>