<?php
session_start();
include '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)$data['id'];

$stmt = mysqli_prepare($conn, "DELETE FROM appointments WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

echo json_encode(['success' => mysqli_stmt_execute($stmt)]);
?>