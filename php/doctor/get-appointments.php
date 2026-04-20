<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Content-Type: application/json');
include '../config/database.php';

$user_id = $_SESSION['user_id'] ?? 0;
$doc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM doctors WHERE user_id = $user_id"));
$doctor_id = $doc['id'] ?? 0;

$status = $_GET['status'] ?? 'all';
$limit = (int)($_GET['limit'] ?? 50);

$where = "WHERE a.doctor_id = $doctor_id";
if($status !== 'all') {
    $status = mysqli_real_escape_string($conn, $status);
    $where .= " AND a.status = '$status'";
}

$result = mysqli_query($conn,
    "SELECT a.*, p.full_name as patient_name, p.gender
     FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     $where
     ORDER BY a.appointment_date DESC
     LIMIT $limit");

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $row['appointment_date'] = date('d M Y', strtotime($row['appointment_date']));
    $row['appointment_time'] = date('h:i A', strtotime($row['appointment_time']));
    $data[] = $row;
}
echo json_encode($data);
?>