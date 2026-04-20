<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$specialty_id = isset($_GET['specialty_id']) ? 
    mysqli_real_escape_string($conn, $_GET['specialty_id']) : '';

$where = $specialty_id ? 
    "WHERE d.specialty_id = '$specialty_id'" : '';

$sql = "SELECT d.id, d.full_name, d.doctor_code,
               d.qualification, d.experience_years,
               d.consultation_fee, d.image,
               s.name AS specialty,
               COALESCE(AVG(r.rating), 0) AS avg_rating,
               COUNT(r.id) AS total_ratings
        FROM doctors d
        JOIN specialties s ON d.specialty_id = s.id
        LEFT JOIN ratings r ON d.id = r.doctor_id
        $where
        GROUP BY d.id
        ORDER BY avg_rating DESC";

$result = mysqli_query($conn, $sql);

$doctors = [];
while ($row = mysqli_fetch_assoc($result)) {
    $doctors[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data'   => $doctors
]);

mysqli_close($conn);
?>