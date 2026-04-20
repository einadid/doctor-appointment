<?php
header('Content-Type: application/json');
include '../config/database.php';

$result = mysqli_query($conn,
    "SELECT p.*, u.phone, u.status, u.id as user_id,
            COUNT(a.id) as total_appts
     FROM patients p
     JOIN users u ON p.user_id = u.id
     LEFT JOIN appointments a ON a.patient_id = p.id
     GROUP BY p.id
     ORDER BY p.created_at DESC");

$data = [];
while($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}
echo json_encode($data);
?>