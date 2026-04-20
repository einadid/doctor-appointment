<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$doctor_id = isset($_GET['doctor_id']) ?
    mysqli_real_escape_string($conn, $_GET['doctor_id']) : '';

if (empty($doctor_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Doctor ID required']);
    exit;
}

$sql = "SELECT * FROM doctor_schedules 
        WHERE doctor_id = '$doctor_id' 
        AND status = 'active'
        ORDER BY FIELD(day_of_week,
        'Saturday','Sunday','Monday',
        'Tuesday','Wednesday','Thursday','Friday')";

$result = mysqli_query($conn, $sql);

$schedules = [];
while ($row = mysqli_fetch_assoc($result)) {
    $schedules[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data'   => $schedules
]);

mysqli_close($conn);
?>