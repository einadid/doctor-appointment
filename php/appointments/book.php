<?php
session_start();
include '../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];

    // Patient id নেওয়া
    $p = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM patients WHERE user_id = $user_id"));
    $patient_id = $p['id'];

    $doctor_id = (int)$_POST['doctor_id'];
    $date = $_POST['appointment_date'];
    $time = date('H:i:s', strtotime($_POST['appointment_time']));
    $reason = $_POST['reason'] ?? '';

    // Unique appointment number
    $appt_no = 'APT-' . strtoupper(uniqid());

    $sql = "INSERT INTO appointments 
            (appointment_no, patient_id, doctor_id, 
             appointment_date, appointment_time, reason) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "siisss",
        $appt_no, $patient_id, $doctor_id, $date, $time, $reason);

    if(mysqli_stmt_execute($stmt)) {
        echo "<script>
            alert('Appointment booked! Your ID: $appt_no');
            window.location.href='../../pages/patient-dashboard.php';
        </script>";
    } else {
        echo "<script>
            alert('Booking failed!');
            window.history.back();
        </script>";
    }
}
?>