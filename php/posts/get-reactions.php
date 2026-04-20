<?php
include '../config/database.php';

$post_id = (int)$_GET['post_id'];

$sql = "SELECT 
    SUM(reaction = 'like') as likes,
    SUM(reaction = 'dislike') as dislikes
    FROM post_reactions WHERE post_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $post_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

echo json_encode($data);
?>