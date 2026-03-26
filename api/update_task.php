<?php
require_once '../config/database.php';
session_start();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$task_id = $_POST['task_id'] ?? null;
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$priority = $_POST['priority'] ?? 'medium';
$assigned_to = $_POST['assigned_to'] ?? null;
$due_date = $_POST['due_date'] ?? null;

if (!$task_id || empty($title)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
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

$stmt = $db->prepare("
    UPDATE tasks 
    SET title = ?, description = ?, priority = ?, assigned_to = ?, due_date = ?
    WHERE id = ?
");
if ($stmt->execute([$title, $description, $priority, $assigned_to, $due_date, $task_id])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Database error']);
}
?>