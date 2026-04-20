<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

$user_id = $_SESSION['user_id'] ?? 0;
$doc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM doctors WHERE user_id = $user_id"));
$doctor_id = $doc['id'] ?? 0;

$result = mysqli_query($conn,
    "SELECT p.full_name, p.gender, u.phone,
            COUNT(a.id) as total_visits,
            MAX(a.appointment_date) as last_visit
     FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     JOIN users u ON p.user_id = u.id
     WHERE a.doctor_id = $doctor_id
     GROUP BY p.id
     ORDER BY last_visit DESC");

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    if($row['last_visit']) {
        $row['last_visit'] = date('d M Y', strtotime($row['last_visit']));
    }
    $data[] = $row;
}
echo json_encode($data);
?>