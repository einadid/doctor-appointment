<?php
header('Content-Type: application/json');
require_once '../config/session_check.php';
require_once '../config/database.php';

requireRole('patient');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid request'
    ]);
    exit;
}

$patient_id       = $_SESSION['patient_id'];
$doctor_id        = mysqli_real_escape_string($conn, $_POST['doctor_id'] ?? '');
$appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date'] ?? '');
$appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time'] ?? '');
$reason           = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');

// Validation
if (empty($doctor_id) || empty($appointment_date) || empty($appointment_time)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'All fields are required!'
    ]);
    exit;
}

// Duplicate check
$dup = mysqli_query($conn,
    "SELECT id FROM appointments 
     WHERE patient_id = '$patient_id'
     AND doctor_id = '$doctor_id'
     AND appointment_date = '$appointment_date'
     AND status != 'cancelled'
     LIMIT 1"
);

if (mysqli_num_rows($dup) > 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'You already have an appointment on this date!'
    ]);
    exit;
}

// Appointment number generate
$appt_no = 'APT' . strtoupper(uniqid());

// Insert
$sql = "INSERT INTO appointments 
        (appointment_no, patient_id, doctor_id, 
         appointment_date, appointment_time, reason)
        VALUES
        ('$appt_no', '$patient_id', '$doctor_id',
         '$appointment_date', '$appointment_time', '$reason')";

if (mysqli_query($conn, $sql)) {
    // Visit count update
    mysqli_query($conn,
        "UPDATE patients SET visits = visits + 1 
         WHERE id = '$patient_id'"
    );

    echo json_encode([
        'status'       => 'success',
        'message'      => 'Appointment booked successfully!',
        'appointment_no' => $appt_no
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Booking failed! Try again.'
    ]);
}

mysqli_close($conn);
?>