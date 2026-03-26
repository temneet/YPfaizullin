<?php
require_once '../config/database.php';
session_start();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing task ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT t.*, u.username as assignee_name 
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.id
    WHERE t.id = ? AND t.deleted_at IS NULL
");
$stmt->execute([$task_id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

if ($task) {
    echo json_encode($task);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Task not found']);
}
?>