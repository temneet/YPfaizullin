// js/main.js
// Основной JavaScript файл для глобальной функциональности сайта

document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех компонентов
    initMobileMenu();
    initNotifications();
    initLazyLoading();
    initScrollToTop();
    initFormSubmissions();
});

// Мобильное меню
function initMobileMenu() {
    const navLinks = document.querySelector('.nav-links');
    if (!navLinks) return;
    
    // Создаем кнопку мобильного меню если ее нет
    if (!document.querySelector('.mobile-menu-btn')) {
        const header = document.querySelector('.header .nav-container');
        if (header && window.innerWidth <= 768) {
            const menuBtn = document.createElement('button');
            menuBtn.className = 'mobile-menu-btn';
            menuBtn.innerHTML = '☰';
            menuBtn.style.cssText = `
                display: block;
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: white;
            `;
            
            menuBtn.addEventListener('click', function() {
                navLinks.classList.toggle('active');
            });
            
            header.insertBefore(menuBtn, header.firstChild);
        }
    }
    
    // Обработка изменения размера окна
    window.addEventListener('resize', function() {
        const menuBtn = document.querySelector('.mobile-menu-btn');
        if (window.innerWidth > 768) {
            if (menuBtn) menuBtn.style.display = 'none';
            navLinks.classList.remove('active');
        } else {
            if (menuBtn) menuBtn.style.display = 'block';
        }
    });
}

// Уведомления
function initNotifications() {
    // Создаем контейнер для уведомлений
    let notificationContainer = document.querySelector('.notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'notification-container';
        notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
        `;
        document.body.appendChild(notificationContainer);
    }
}

// Глобальная функция для показа уведомлений
window.showNotification = function(message, type = 'info', duration = 3000) {
    const container = document.querySelector('.notification-container');
    const notification = document.createElement('div');
    
    const icons = {
        success: '✅',
        error: '❌',
        warning: '⚠️',
        info: 'ℹ️'
    };
    
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 0.75rem;
        animation: slideIn 0.3s ease;
        border-left: 4px solid ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : type === 'warning' ? '#ffc107' : '#17a2b8'};
    `;
    
    notification.innerHTML = `
        <span style="font-size: 1.25rem;">${icons[type] || icons.info}</span>
        <span style="flex: 1; color: #333;">${message}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: #999;">&times;</button>
    `;
    
    container.appendChild(notification);
    
    // Автоматическое удаление
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, duration);
};

// Добавляем CSS анимации для уведомлений
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Ленивая загрузка изображений
function initLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.dataset.src;
                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                    }
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback для старых браузеров
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
        });
    }
}

// Кнопка прокрутки вверх
function initScrollToTop() {
    let scrollBtn = document.querySelector('.scroll-to-top');
    
    if (!scrollBtn) {
        scrollBtn = document.createElement('button');
        scrollBtn.className = 'scroll-to-top';
        scrollBtn.innerHTML = '↑';
        scrollBtn.style.cssText = `
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            display: none;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s, opacity 0.3s;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        `;
        
        scrollBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        
        scrollBtn.addEventListener('mouseenter', () => {
            scrollBtn.style.transform = 'translateY(-5px)';
        });
        
        scrollBtn.addEventListener('mouseleave', () => {
            scrollBtn.style.transform = 'translateY(0)';
        });
        
        document.body.appendChild(scrollBtn);
    }
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            scrollBtn.style.display = 'flex';
        } else {
            scrollBtn.style.display = 'none';
        }
    });
}

// Обработка отправки форм с AJAX
function initFormSubmissions() {
    const forms = document.querySelectorAll('form[data-ajax]');
    
    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const url = this.action || window.location.href;
            const method = this.method || 'POST';
            
            // Показываем индикатор загрузки
            const submitBtn = this.querySelector('[type="submit"]');
            const originalText = submitBtn?.innerHTML || '';
            if (submitBtn) {
                submitBtn.innerHTML = '⏳ Загрузка...';
                submitBtn.disabled = true;
            }
            
            try {
                const response = await fetch(url, {
                    method: method,
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message || 'Операция выполнена успешно!', 'success');
                    
                    // Если есть callback, выполняем его
                    if (this.dataset.onSuccess) {
                        eval(this.dataset.onSuccess)(data);
                    }
                    
                    // Если указан редирект
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    }
                } else {
                    showNotification(data.message || 'Произошла ошибка', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('Ошибка соединения с сервером', 'error');
            } finally {
                if (submitBtn) {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }
            }
        });
    });
}

// Функция для фильтрации проектов на главной странице
window.filterProjects = function(searchTerm, type) {
    const cards = document.querySelectorAll('.project-card');
    let visibleCount = 0;
    
    cards.forEach(card => {
        const title = card.dataset.title?.toLowerCase() || '';
        const projectType = card.dataset.type || '';
        
        const matchesSearch = title.includes(searchTerm.toLowerCase());
        const matchesType = type === 'all' || projectType === type;
        
        if (matchesSearch && matchesType) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Показываем сообщение если нет результатов
    const container = document.querySelector('.projects-grid');
    let noResultsMsg = document.querySelector('.no-results-message');
    
    if (visibleCount === 0) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'no-results-message';
            noResultsMsg.style.cssText = `
                grid-column: 1 / -1;
                text-align: center;
                padding: 3rem;
                color: #666;
                font-size: 1.1rem;
            `;
            noResultsMsg.innerHTML = '😕 Ничего не найдено. Попробуйте изменить параметры поиска.';
            container.appendChild(noResultsMsg);
        }
        noResultsMsg.style.display = 'block';
    } else if (noResultsMsg) {
        noResultsMsg.style.display = 'none';
    }
};

// Функция для загрузки проектов через AJAX (бесконечная прокрутка)
let isLoading = false;
let currentPage = 1;
let hasMore = true;

window.initInfiniteScroll = function() {
    if (!document.querySelector('.projects-grid')) return;
    
    window.addEventListener('scroll', function() {
        const scrollPosition = window.scrollY + window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        
        if (scrollPosition >= documentHeight - 500 && !isLoading && hasMore) {
            loadMoreProjects();
        }
    });
};

async function loadMoreProjects() {
    isLoading = true;
    currentPage++;
    
    // Показываем индикатор загрузки
    let loadingIndicator = document.querySelector('.loading-indicator');
    if (!loadingIndicator) {
        loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'loading-indicator';
        loadingIndicator.style.cssText = `
            text-align: center;
            padding: 2rem;
            color: #667eea;
            font-size: 1.1rem;
        `;
        loadingIndicator.innerHTML = '⏳ Загрузка...';
        document.querySelector('.projects-grid').after(loadingIndicator);
    }
    loadingIndicator.style.display = 'block';
    
    try {
        const response = await fetch(`api/get_projects.php?page=${currentPage}`);
        const data = await response.json();
        
        if (data.projects && data.projects.length > 0) {
            appendProjects(data.projects);
        } else {
            hasMore = false;
        }
    } catch (error) {
        console.error('Error loading projects:', error);
    } finally {
        isLoading = false;
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    }
}

function appendProjects(projects) {
    const container = document.querySelector('.projects-grid');
    
    projects.forEach(project => {
        const card = createProjectCard(project);
        container.appendChild(card);
    });
}

function createProjectCard(project) {
    const card = document.createElement('div');
    card.className = 'project-card';
    card.dataset.title = project.title;
    card.dataset.type = project.type;
    
    card.innerHTML = `
        <div class="project-poster">
            ${project.poster_url ? 
                `<img src="${escapeHtml(project.poster_url)}" alt="${escapeHtml(project.title)}">` : 
                '<div class="poster-placeholder">🎬</div>'
            }
        </div>
        <div class="project-info">
            <h3>${escapeHtml(project.title)}</h3>
            <p class="project-type">${project.type === 'movie' ? 'Фильм' : 'Сериал'}</p>
            <p class="project-description">${escapeHtml(project.description?.substring(0, 100) || '')}...</p>
            <div class="project-meta">
                <span>❤️ ${project.likes_count || 0}</span>
                <span>👤 ${escapeHtml(project.creator_name)}</span>
            </div>
            <button onclick="viewProject(${project.id})" class="view-button">Подробнее</button>
        </div>
    `;
    
    return card;
}

// Функция для просмотра проекта
window.viewProject = function(projectId) {
    window.location.href = `project.php?id=${projectId}`;
};

// Функция для лайка проекта
window.likeProject = async function(projectId) {
    try {
        const response = await fetch('api/like_project.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `project_id=${projectId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            const likeBtn = document.querySelector(`.like-button[data-project-id="${projectId}"]`);
            const likeCount = document.querySelector(`.like-count[data-project-id="${projectId}"]`);
            
            if (likeBtn) {
                likeBtn.classList.add('liked');
                likeBtn.innerHTML = '❤️';
            }
            if (likeCount) {
                likeCount.textContent = data.likes_count;
            }
            
            showNotification('Проект добавлен в избранное!', 'success');
        } else if (data.already_liked) {
            showNotification('Вы уже лайкнули этот проект', 'warning');
        } else {
            showNotification(data.message || 'Ошибка', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Ошибка при лайке', 'error');
    }
};

// Функция для копирования ссылки
window.copyToClipboard = function(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Ссылка скопирована в буфер обмена!', 'success');
    }).catch(() => {
        showNotification('Не удалось скопировать ссылку', 'error');
    });
};

// Функция для форматирования даты
window.formatDate = function(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 0) {
        return 'сегодня';
    } else if (diffDays === 1) {
        return 'вчера';
    } else if (diffDays < 7) {
        return `${diffDays} дней назад`;
    } else {
        return date.toLocaleDateString('ru-RU');
    }
};

// Функция для экранирования HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Функция для валидации email
window.validateEmail = function(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
};

// Функция для валидации пароля
window.validatePassword = function(password) {
    return password.length >= 6;
};

// Функция для валидации имени пользователя
window.validateUsername = function(username) {
    return username.length >= 3 && /^[a-zA-Z0-9_]+$/.test(username);
};

// Обработка клавиши Escape для закрытия модальных окон
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }
});

// Предотвращение двойной отправки форм
document.querySelectorAll('form').forEach(form => {
    let submitted = false;
    form.addEventListener('submit', function(e) {
        if (submitted) {
            e.preventDefault();
            return;
        }
        submitted = true;
        setTimeout(() => {
            submitted = false;
        }, 3000);
    });
});

// Автоматическое сохранение черновиков форм
function initFormAutoSave(formId, storageKey) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    // Загрузка сохраненных данных
    const savedData = localStorage.getItem(storageKey);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const field = form.elements[key];
            if (field) {
                field.value = data[key];
            }
        });
        showNotification('Загружен черновик', 'info');
    }
    
    // Автосохранение при изменении
    let saveTimeout;
    form.addEventListener('input', function() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            localStorage.setItem(storageKey, JSON.stringify(data));
        }, 1000);
    });
    
    // Очистка черновика после отправки
    form.addEventListener('submit', function() {
        localStorage.removeItem(storageKey);
    });
}

// Инициализация автосохранения для форм
if (document.getElementById('projectForm')) {
    initFormAutoSave('projectForm', 'project_draft');
}

if (document.getElementById('taskForm')) {
    initFormAutoSave('taskForm', 'task_draft');
}

// Функция для отображения прогресса загрузки
window.showLoadingProgress = function(container, message = 'Загрузка...') {
    const loader = document.createElement('div');
    loader.className = 'loading-progress';
    loader.style.cssText = `
        text-align: center;
        padding: 2rem;
        color: #667eea;
        font-size: 1rem;
    `;
    loader.innerHTML = `
        <div style="display: inline-block; width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite;"></div>
        <p style="margin-top: 1rem;">${message}</p>
    `;
    
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
    
    const target = document.querySelector(container);
    if (target) {
        target.innerHTML = '';
        target.appendChild(loader);
    }
    
    return loader;
};

// Функция для скрытия прогресса загрузки
window.hideLoadingProgress = function(loader) {
    if (loader && loader.parentElement) {
        loader.remove();
    }
};

// Экспорт функций для глобального использования
window.main = {
    showNotification,
    filterProjects,
    viewProject,
    likeProject,
    copyToClipboard,
    formatDate,
    validateEmail,
    validatePassword,
    validateUsername,
    initInfiniteScroll,
    showLoadingProgress,
    hideLoadingProgress
};

console.log('Main.js loaded successfully');