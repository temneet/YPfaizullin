<?php
require_once '../config/database.php';
session_start();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$project_id = $_POST['project_id'] ?? null;

if (!$project_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing project ID']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Проверяем, не лайкнул ли уже пользователь
$stmt = $db->prepare("SELECT id FROM likes WHERE user_id = ? AND project_id = ?");
$stmt->execute([$_SESSION['user_id'], $project_id]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'already_liked' => true]);
    exit();
}

// Добавляем лайк
$stmt = $db->prepare("INSERT INTO likes (user_id, project_id) VALUES (?, ?)");
if ($stmt->execute([$_SESSION['user_id'], $project_id])) {
    // Получаем общее количество лайков
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM likes WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'likes_count' => $count['count']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>