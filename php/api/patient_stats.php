<?php
header('Content-Type: application/json');
require_once '../config/session_check.php';
require_once '../config/database.php';

requireRole('patient');

$patient_id = $_SESSION['patient_id'];

// Total appointments
$total = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM appointments 
     WHERE patient_id = '$patient_id'"
))[0];

// Pending
$pending = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM appointments 
     WHERE patient_id = '$patient_id' 
     AND status = 'pending'"
))[0];

// Completed
$completed = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM appointments 
     WHERE patient_id = '$patient_id' 
     AND status = 'completed'"
))[0];

// Total doctors
$doctors = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM doctors"
))[0];

// Recent appointments
$recent_result = mysqli_query($conn,
    "SELECT a.*, 
            d.full_name AS doctor_name
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     WHERE a.patient_id = '$patient_id'
     ORDER BY a.created_at DESC
     LIMIT 5"
);

$recent = [];
while ($row = mysqli_fetch_assoc($recent_result)) {
    $recent[] = $row;
}

echo json_encode([
    'status'    => 'success',
    'total'     => $total,
    'pending'   => $pending,
    'completed' => $completed,
    'doctors'   => $doctors,
    'recent'    => $recent
]);

mysqli_close($conn);
?>