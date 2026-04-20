<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

$user_id = $_SESSION['user_id'] ?? 0;
$p = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM patients WHERE user_id = $user_id"));
$patient_id = $p['id'] ?? 0;

$result = mysqli_query($conn,
    "SELECT a.*, d.full_name as doctor_name, s.name as specialty
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     JOIN specialties s ON d.specialty_id = s.id
     WHERE a.patient_id = $patient_id
     ORDER BY a.created_at DESC LIMIT 20");

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $row['appointment_date'] = date('d M Y', strtotime($row['appointment_date']));
    $row['appointment_time'] = date('h:i A', strtotime($row['appointment_time']));
    $data[] = $row;
}
echo json_encode($data);
?>