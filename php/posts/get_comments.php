<?php
header('Content-Type: application/json');
require_once '../config/session_check.php';
require_once '../config/database.php';

requireLogin();

$post_id = isset($_GET['post_id']) ?
    mysqli_real_escape_string($conn, $_GET['post_id']) : '';

if (empty($post_id)) {
    echo json_encode(['status' => 'error']);
    exit;
}

$sql = "SELECT c.*, u.username 
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = '$post_id'
        ORDER BY c.created_at ASC";

$result   = mysqli_query($conn, $sql);
$comments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $comments[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data'   => $comments
]);

mysqli_close($conn);
?>