<?php
require_once 'config/database.php';
redirectIfNotLoggedIn();

$database = new Database();
$db = $database->getConnection();

$project_id = $_GET['project_id'] ?? null;

if (!$project_id) {
    // Получаем проекты пользователя
    $stmt = $db->prepare("
        SELECT * FROM projects 
        WHERE created_by = ? AND deleted_at IS NULL 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($projects)) {
        header("Location: project.php");
        exit();
    }
    
    $project_id = $projects[0]['id'];
    header("Location: kanban.php?project_id=$project_id");
    exit();
}

// Получение информации о проекте
$stmt = $db->prepare("SELECT * FROM projects WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    header("Location: index.php");
    exit();
}

// Получение задач
$stmt = $db->prepare("
    SELECT t.*, u.username as assignee_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.project_id = ? AND t.deleted_at IS NULL
    ORDER BY t.position ASC
");
$stmt->execute([$project_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Группировка задач по статусам
$tasks_by_status = [
    'todo' => [],
    'in_progress' => [],
    'review' => [],
    'done' => []
];

foreach ($tasks as $task) {
    $tasks_by_status[$task['status']][] = $task;
}

// Обработка создания задачи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $assigned_to = $_POST['assigned_to'] ?? null;
    $due_date = $_POST['due_date'] ?? null;
    
    if (!empty($title)) {
        $stmt = $db->prepare("
            INSERT INTO tasks (project_id, title, description, priority, assigned_to, due_date, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$project_id, $title, $description, $priority, $assigned_to, $due_date, $_SESSION['user_id']]);
        header("Location: kanban.php?project_id=$project_id");
        exit();
    }
}

// Обработка обновления статуса задачи
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_task_status'])) {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $task_id]);
    exit();
}

// Получение пользователей для назначения
$stmt = $db->prepare("SELECT id, username FROM users WHERE deleted_at IS NULL");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доска задач - <?= htmlspecialchars($project['title']) ?> - StreamHub</title>
    <link rel="stylesheet" href="css/kanban.css">
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
                <a href="project.php?id=<?= $project_id ?>">Проект</a>
                <a href="kanban.php?project_id=<?= $project_id ?>" class="active">Доска задач</a>
                <a href="logout.php">Выйти</a>
            </div>
        </nav>
    </header>

    <main class="kanban-container">
        <div class="kanban-header">
            <h1><?= htmlspecialchars($project['title']) ?> - Доска задач</h1>
            <button class="btn-primary" onclick="openTaskModal()">+ Создать задачу</button>
        </div>

        <div class="kanban-board">
            <div class="kanban-column" data-status="todo">
                <div class="column-header">
                    <h3>📝 To Do</h3>
                    <span class="task-count"><?= count($tasks_by_status['todo']) ?></span>
                </div>
                <div class="tasks-container" id="todo-tasks">
                    <?php foreach($tasks_by_status['todo'] as $task): ?>
                        <div class="task-card" data-task-id="<?= $task['id'] ?>">
                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                            <div class="task-description"><?= htmlspecialchars(substr($task['description'], 0, 100)) ?></div>
                            <div class="task-meta">
                                <span class="priority priority-<?= $task['priority'] ?>">
                                    <?= $task['priority'] == 'low' ? 'Низкий' : ($task['priority'] == 'medium' ? 'Средний' : ($task['priority'] == 'high' ? 'Высокий' : 'Срочный')) ?>
                                </span>
                                <?php if($task['assignee_name']): ?>
                                    <span class="assignee">👤 <?= htmlspecialchars($task['assignee_name']) ?></span>
                                <?php endif; ?>
                                <?php if($task['due_date']): ?>
                                    <span class="due-date">📅 <?= date('d.m.Y', strtotime($task['due_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <button onclick="editTask(<?= $task['id'] ?>)" class="btn-icon">✏️</button>
                                <button onclick="deleteTask(<?= $task['id'] ?>)" class="btn-icon">🗑️</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="kanban-column" data-status="in_progress">
                <div class="column-header">
                    <h3>⚙️ В работе</h3>
                    <span class="task-count"><?= count($tasks_by_status['in_progress']) ?></span>
                </div>
                <div class="tasks-container" id="in_progress-tasks">
                    <?php foreach($tasks_by_status['in_progress'] as $task): ?>
                        <div class="task-card" data-task-id="<?= $task['id'] ?>">
                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                            <div class="task-description"><?= htmlspecialchars(substr($task['description'], 0, 100)) ?></div>
                            <div class="task-meta">
                                <span class="priority priority-<?= $task['priority'] ?>">
                                    <?= $task['priority'] == 'low' ? 'Низкий' : ($task['priority'] == 'medium' ? 'Средний' : ($task['priority'] == 'high' ? 'Высокий' : 'Срочный')) ?>
                                </span>
                                <?php if($task['assignee_name']): ?>
                                    <span class="assignee">👤 <?= htmlspecialchars($task['assignee_name']) ?></span>
                                <?php endif; ?>
                                <?php if($task['due_date']): ?>
                                    <span class="due-date">📅 <?= date('d.m.Y', strtotime($task['due_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <button onclick="editTask(<?= $task['id'] ?>)" class="btn-icon">✏️</button>
                                <button onclick="deleteTask(<?= $task['id'] ?>)" class="btn-icon">🗑️</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="kanban-column" data-status="review">
                <div class="column-header">
                    <h3>🔍 На проверке</h3>
                    <span class="task-count"><?= count($tasks_by_status['review']) ?></span>
                </div>
                <div class="tasks-container" id="review-tasks">
                    <?php foreach($tasks_by_status['review'] as $task): ?>
                        <div class="task-card" data-task-id="<?= $task['id'] ?>">
                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                            <div class="task-description"><?= htmlspecialchars(substr($task['description'], 0, 100)) ?></div>
                            <div class="task-meta">
                                <span class="priority priority-<?= $task['priority'] ?>">
                                    <?= $task['priority'] == 'low' ? 'Низкий' : ($task['priority'] == 'medium' ? 'Средний' : ($task['priority'] == 'high' ? 'Высокий' : 'Срочный')) ?>
                                </span>
                                <?php if($task['assignee_name']): ?>
                                    <span class="assignee">👤 <?= htmlspecialchars($task['assignee_name']) ?></span>
                                <?php endif; ?>
                                <?php if($task['due_date']): ?>
                                    <span class="due-date">📅 <?= date('d.m.Y', strtotime($task['due_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <button onclick="editTask(<?= $task['id'] ?>)" class="btn-icon">✏️</button>
                                <button onclick="deleteTask(<?= $task['id'] ?>)" class="btn-icon">🗑️</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="kanban-column" data-status="done">
                <div class="column-header">
                    <h3>✅ Готово</h3>
                    <span class="task-count"><?= count($tasks_by_status['done']) ?></span>
                </div>
                <div class="tasks-container" id="done-tasks">
                    <?php foreach($tasks_by_status['done'] as $task): ?>
                        <div class="task-card" data-task-id="<?= $task['id'] ?>">
                            <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                            <div class="task-description"><?= htmlspecialchars(substr($task['description'], 0, 100)) ?></div>
                            <div class="task-meta">
                                <span class="priority priority-<?= $task['priority'] ?>">
                                    <?= $task['priority'] == 'low' ? 'Низкий' : ($task['priority'] == 'medium' ? 'Средний' : ($task['priority'] == 'high' ? 'Высокий' : 'Срочный')) ?>
                                </span>
                                <?php if($task['assignee_name']): ?>
                                    <span class="assignee">👤 <?= htmlspecialchars($task['assignee_name']) ?></span>
                                <?php endif; ?>
                                <?php if($task['due_date']): ?>
                                    <span class="due-date">📅 <?= date('d.m.Y', strtotime($task['due_date'])) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="task-actions">
                                <button onclick="editTask(<?= $task['id'] ?>)" class="btn-icon">✏️</button>
                                <button onclick="deleteTask(<?= $task['id'] ?>)" class="btn-icon">🗑️</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Модальное окно для создания/редактирования задачи -->
    <div id="taskModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Создать задачу</h2>
                <span class="close" onclick="closeTaskModal()">&times;</span>
            </div>
            <form id="taskForm" method="POST">
                <input type="hidden" id="task_id" name="task_id">
                <div class="form-group">
                    <label for="task_title">Название задачи *</label>
                    <input type="text" id="task_title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="task_description">Описание</label>
                    <textarea id="task_description" name="description" rows="4"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="task_priority">Приоритет</label>
                        <select id="task_priority" name="priority">
                            <option value="low">Низкий</option>
                            <option value="medium" selected>Средний</option>
                            <option value="high">Высокий</option>
                            <option value="urgent">Срочный</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="task_assigned_to">Назначить</label>
                        <select id="task_assigned_to" name="assigned_to">
                            <option value="">Не назначено</option>
                            <?php foreach($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="task_due_date">Срок выполнения</label>
                    <input type="date" id="task_due_date" name="due_date">
                </div>
                <button type="submit" name="create_task" class="btn-primary">Создать задачу</button>
            </form>
        </div>
    </div>

    <script src="js/kanban.js"></script>
    <script>
        const projectId = <?= $project_id ?>;
        const users = <?= json_encode($users) ?>;
        
        function openTaskModal() {
            document.getElementById('modalTitle').textContent = 'Создать задачу';
            document.getElementById('taskForm').reset();
            document.getElementById('task_id').value = '';
            document.getElementById('taskModal').style.display = 'block';
        }
        
        function closeTaskModal() {
            document.getElementById('taskModal').style.display = 'none';
        }
        
        function editTask(taskId) {
            // Здесь можно загрузить данные задачи для редактирования
            alert('Функция редактирования в разработке');
        }
        
        function deleteTask(taskId) {
            if (confirm('Вы уверены, что хотите удалить эту задачу?')) {
                fetch(`api/delete_task.php?id=${taskId}`, { method: 'POST' })
                    .then(() => location.reload());
            }
        }
        
        // Drag and drop functionality
        const containers = document.querySelectorAll('.tasks-container');
        containers.forEach(container => {
            container.addEventListener('dragover', (e) => {
                e.preventDefault();
            });
            
            container.addEventListener('drop', (e) => {
                e.preventDefault();
                const taskId = e.dataTransfer.getData('text/plain');
                const newStatus = container.parentElement.dataset.status;
                
                fetch('api/update_task_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `task_id=${taskId}&new_status=${newStatus}`
                }).then(() => location.reload());
            });
        });
        
        document.querySelectorAll('.task-card').forEach(card => {
            card.setAttribute('draggable', 'true');
            card.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', card.dataset.taskId);
            });
        });
        
        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('taskModal');
            if (event.target == modal) {
                closeTaskModal();
            }
        }
    </script>
</body>
</html>