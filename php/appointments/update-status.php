<?php
session_start();
include '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$appt_id = (int)$data['appt_id'];
$status = $data['status'];

$allowed = ['approved', 'completed', 'cancelled'];
if(!in_array($status, $allowed)) {
    echo json_encode(['success' => false]);
    exit();
}

$sql = "UPDATE appointments SET status = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "si", $status, $appt_id);

if(mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>