<?php
require_once '../config/database.php';
session_start();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$task_id = $_POST['id'] ?? null;

if (!$task_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing task ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Проверка прав доступа
$stmt = $db->prepare("
    SELECT t.*, p.created_by 
    FROM tasks t
    JOIN projects p ON t.project_id = p.id
    WHERE t.id = ?
");
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$task || ($task['created_by'] != $_SESSION['user_id'] && $_SESSION['role'] != 'admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Мягкое удаление
$stmt = $db->prepare("UPDATE tasks SET deleted_at = NOW() WHERE id = ?");
if ($stmt->execute([$task_id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Database error']);
}
?>