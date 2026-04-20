<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

$status = $_GET['status'] ?? 'all';
$limit = (int)($_GET['limit'] ?? 50);

$where = '';
if($status !== 'all') {
    $status = mysqli_real_escape_string($conn, $status);
    $where = "WHERE a.status = '$status'";
}

$result = mysqli_query($conn,
    "SELECT a.*, p.full_name as patient_name, d.full_name as doctor_name, s.name as specialty
     FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     JOIN doctors d ON a.doctor_id = d.id
     JOIN specialties s ON d.specialty_id = s.id
     $where
     ORDER BY a.created_at DESC LIMIT $limit");

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $row['appointment_date'] = date('d M Y', strtotime($row['appointment_date']));
    $row['appointment_time'] = date('h:i A', strtotime($row['appointment_time']));
    $data[] = $row;
}
echo json_encode($data);
?>