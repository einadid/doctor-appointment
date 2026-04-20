<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$title = trim($data['title'] ?? '');
$content = trim($data['content'] ?? '');
$media_type = $data['media_type'] ?? 'none';
$media_url = $data['media_url'] ?? null;
$user_id = $_SESSION['user_id'];

if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Title and content required']);
    exit();
}

$sql = "INSERT INTO posts (user_id, title, content, media_type, media_url)
        VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "issss", $user_id, $title, $content, $media_type, $media_url);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Post created!']);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
?>