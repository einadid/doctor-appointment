<?php
header('Content-Type: application/json');
include '../config/database.php';

$result = mysqli_query($conn,
    "SELECT d.id, d.full_name, d.consultation_fee,
            d.experience_years, s.name as specialty
     FROM doctors d
     JOIN specialties s ON d.specialty_id = s.id
     ORDER BY d.full_name");

$doctors = [];
while($row = mysqli_fetch_assoc($result)) {
    $doctors[] = $row;
}
echo json_encode($doctors);
?>