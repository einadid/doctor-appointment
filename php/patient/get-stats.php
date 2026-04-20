<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

$user_id = $_SESSION['user_id'] ?? 0;
$p = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM patients WHERE user_id = $user_id"));
$patient_id = $p['id'] ?? 0;

if ($patient_id == 0) {
    echo json_encode(['total' => 0, 'completed' => 0, 'pending' => 0]);
    exit();
}

$result = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total,
            SUM(status='completed') as completed,
            SUM(status='pending') as pending
     FROM appointments WHERE patient_id = $patient_id"));

echo json_encode([
    'total' => $result['total'] ?? 0,
    'completed' => $result['completed'] ?? 0,
    'pending' => $result['pending'] ?? 0
]);
?>