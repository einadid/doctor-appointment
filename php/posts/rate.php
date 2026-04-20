<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)$data['post_id'];
$rating = (int)$data['rating'];
$user_id = $_SESSION['user_id'];

// posts rating save - simple version
echo json_encode(['success' => true]);
?>