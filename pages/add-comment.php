<?php
session_start();
include '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)$data['post_id'];
$comment = $data['comment'];
$user_id = $_SESSION['user_id'];

$sql = "INSERT INTO comments (post_id, user_id, comment_text) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iis", $post_id, $user_id, $comment);

if(mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>