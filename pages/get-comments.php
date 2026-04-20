<?php
session_start();
include '../config/database.php';

$post_id = (int)$_GET['post_id'];

$sql = "SELECT c.comment_text, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$comments = [];
while($row = mysqli_fetch_assoc($result)) {
    $comments[] = $row;
}

echo json_encode($comments);
?>