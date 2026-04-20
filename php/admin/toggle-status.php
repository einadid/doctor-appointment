<?php
session_start();
include '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$user_id = (int)$data['user_id'];
$status = $data['status'];

$stmt = mysqli_prepare($conn,
    "UPDATE users SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $status, $user_id);

echo json_encode(['success' => mysqli_stmt_execute($stmt)]);
?>