<?php
session_start();
include '../config/database.php';

$user_id = $_SESSION['user_id'];

// Patient id নেওয়া
$p = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT id FROM patients WHERE user_id = $user_id"));
$patient_id = $p['id'] ?? 0;

$total = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id"))['total'];
$completed = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id AND status='completed'"))['total'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM appointments WHERE patient_id = $patient_id AND status='pending'"))['total'];

echo json_encode([
    'total' => $total,
    'completed' => $completed,
    'pending' => $pending
]);
?>