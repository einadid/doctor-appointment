<?php
header('Content-Type: application/json');
require_once '../config/session_check.php';
require_once '../config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error']);
    exit;
}

$user_id      = $_SESSION['user_id'];
$post_id      = mysqli_real_escape_string($conn, $_POST['post_id'] ?? '');
$comment_text = mysqli_real_escape_string($conn, $_POST['comment_text'] ?? '');

if (empty($post_id) || empty($comment_text)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Comment cannot be empty!'
    ]);
    exit;
}

$sql = "INSERT INTO comments 
        (post_id, user_id, comment_text) 
        VALUES 
        ('$post_id', '$user_id', '$comment_text')";

if (mysqli_query($conn, $sql)) {
    $comment_id = mysqli_insert_id($conn);
    
    // New comment data return করো
    $new = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT c.*, u.username 
         FROM comments c
         JOIN users u ON c.user_id = u.id
         WHERE c.id = '$comment_id'"
    ));

    echo json_encode([
        'status' => 'success',
        'data'   => $new
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to add comment!'
    ]);
}

mysqli_close($conn);
?>