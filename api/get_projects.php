<?php
require_once '../config/database.php';
session_start();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT p.*, u.username as creator_name,
    (SELECT COUNT(*) FROM likes WHERE project_id = p.id) as likes_count
    FROM projects p
    JOIN users u ON p.created_by = u.id
    WHERE p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'projects' => $projects,
    'page' => $page,
    'has_more' => count($projects) === $limit
]);
?>