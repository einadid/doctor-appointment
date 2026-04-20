<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

$user_id = $_SESSION['user_id'] ?? 0;
$doc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM doctors WHERE user_id = $user_id"));
$doctor_id = $doc['id'] ?? 0;

if($doctor_id == 0) {
    echo json_encode(['success' => false]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$day = $data['day_of_week'];
$start = $data['start_time'];
$end = $data['end_time'];
$max = (int)($data['max_patients'] ?? 10);

$sql = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, max_patients)
        VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "isssi", $doctor_id, $day, $start, $end, $max);

echo json_encode(['success' => mysqli_stmt_execute($stmt)]);
?>