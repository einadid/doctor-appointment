<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

$result = mysqli_query($conn,
    "SELECT p.*, u.username 
     FROM posts p 
     JOIN users u ON p.user_id = u.id 
     ORDER BY p.created_at DESC 
     LIMIT 20");

$posts = [];
while($row = mysqli_fetch_assoc($result)) {
    $row['created_at'] = date('d M Y, h:i A', strtotime($row['created_at']));
    $posts[] = $row;
}
echo json_encode($posts);
?>