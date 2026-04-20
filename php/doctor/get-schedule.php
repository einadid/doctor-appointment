<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

$user_id = $_SESSION['user_id'] ?? 0;
$doc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM doctors WHERE user_id = $user_id"));
$doctor_id = $doc['id'] ?? 0;

if ($doctor_id == 0) {
    echo json_encode([]);
    exit();
}

$result = mysqli_query($conn,
    "SELECT * FROM doctor_schedules
     WHERE doctor_id = $doctor_id AND status = 'active'
     ORDER BY FIELD(day_of_week,
        'Saturday','Sunday','Monday',
        'Tuesday','Wednesday','Thursday','Friday')");

$schedules = [];
while ($row = mysqli_fetch_assoc($result)) {
    $schedules[] = $row;
}
echo json_encode($schedules);
?>