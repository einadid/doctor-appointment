<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
include '../config/database.php';

$specialty_id = (int)($_GET['specialty_id'] ?? 0);

$sql = "SELECT d.id, d.full_name, d.consultation_fee, 
               d.experience_years, s.name as specialty
        FROM doctors d
        JOIN specialties s ON d.specialty_id = s.id
        WHERE d.specialty_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $specialty_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$doctors = [];
while($row = mysqli_fetch_assoc($result)) {
    $doctors[] = $row;
}

echo json_encode($doctors);
?>