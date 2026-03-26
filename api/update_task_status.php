<?php
require_once '../config/database.php';
session_start();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$task_id = $_POST['task_id'] ?? null;
$new_status = $_POST['new_status'] ?? null;

if (!$task_id || !$new_status) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
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

$stmt = $db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
if ($stmt->execute([$new_status, $task_id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Database error']);
}
?>