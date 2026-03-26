// js/kanban.js
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация drag and drop
    const tasks = document.querySelectorAll('.task-card');
    const containers = document.querySelectorAll('.tasks-container');
    
    tasks.forEach(task => {
        task.setAttribute('draggable', 'true');
        
        task.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.dataset.taskId);
            this.style.opacity = '0.5';
        });
        
        task.addEventListener('dragend', function(e) {
            this.style.opacity = '1';
        });
    });
    
    containers.forEach(container => {
        container.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        
        container.addEventListener('drop', function(e) {
            e.preventDefault();
            const taskId = e.dataTransfer.getData('text/plain');
            const newStatus = this.parentElement.dataset.status;
            
            // Отправка запроса на обновление статуса
            fetch('api/update_task_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `task_id=${taskId}&new_status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка при обновлении статуса');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ошибка при обновлении статуса');
            });
        });
    });
});

// Функции для работы с модальным окном
function openTaskModal() {
    const modal = document.getElementById('taskModal');
    const modalTitle = document.getElementById('modalTitle');
    const form = document.getElementById('taskForm');
    
    modalTitle.textContent = 'Создать задачу';
    form.reset();
    document.getElementById('task_id').value = '';
    
    modal.style.display = 'block';
}

function closeTaskModal() {
    const modal = document.getElementById('taskModal');
    modal.style.display = 'none';
}

function editTask(taskId) {
    // Загрузка данных задачи для редактирования
    fetch(`api/get_task.php?id=${taskId}`)
        .then(response => response.json())
        .then(task => {
            const modal = document.getElementById('taskModal');
            const modalTitle = document.getElementById('modalTitle');
            const form = document.getElementById('taskForm');
            
            modalTitle.textContent = 'Редактировать задачу';
            document.getElementById('task_id').value = task.id;
            document.getElementById('task_title').value = task.title;
            document.getElementById('task_description').value = task.description;
            document.getElementById('task_priority').value = task.priority;
            document.getElementById('task_assigned_to').value = task.assigned_to || '';
            document.getElementById('task_due_date').value = task.due_date || '';
            
            modal.style.display = 'block';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при загрузке задачи');
        });
}

function deleteTask(taskId) {
    if (confirm('Вы уверены, что хотите удалить эту задачу?')) {
        fetch('api/delete_task.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${taskId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка при удалении задачи');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Ошибка при удалении задачи');
        });
    }
}

// Закрытие модального окна при клике вне его
window.onclick = function(event) {
    const modal = document.getElementById('taskModal');
    if (event.target == modal) {
        closeTaskModal();
    }
}

// Валидация формы создания задачи
document.getElementById('taskForm').addEventListener('submit', function(e) {
    const title = document.getElementById('task_title').value.trim();
    
    if (title.length < 3) {
        e.preventDefault();
        alert('Название задачи должно содержать минимум 3 символа');
        return false;
    }
});