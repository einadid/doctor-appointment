<?php
header('Content-Type: application/json');
include '../config/database.php';

$result = mysqli_query($conn,
    "SELECT d.*, u.status, u.id as user_id, s.name as specialty_name,
            COUNT(a.id) as total_appts,
            ROUND(AVG(r.rating), 1) as avg_rating
     FROM doctors d
     JOIN users u ON d.user_id = u.id
     JOIN specialties s ON d.specialty_id = s.id
     LEFT JOIN appointments a ON a.doctor_id = d.id
     LEFT JOIN ratings r ON r.doctor_id = d.id
     GROUP BY d.id
     ORDER BY d.created_at DESC");

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
echo json_encode($data);
?>