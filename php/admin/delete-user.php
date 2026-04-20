<?php
session_start();
include '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = (int)$data['user_id'];

$stmt = mysqli_prepare($conn,
    "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);

echo json_encode(['success' => mysqli_stmt_execute($stmt)]);
?>