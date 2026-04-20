<?php
header('Content-Type: application/json');
include '../config/database.php';

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$date = $_GET['date'] ?? date('Y-m-d');

// সময় slots তৈরি করো
$all_slots = [
    '09:00 AM', '09:30 AM', '10:00 AM', '10:30 AM',
    '11:00 AM', '11:30 AM', '12:00 PM', '02:00 PM',
    '02:30 PM', '03:00 PM', '03:30 PM', '04:00 PM'
];

// Booked slots দেখো
$booked_sql = "SELECT appointment_time FROM appointments 
               WHERE doctor_id = ? AND appointment_date = ? 
               AND status != 'cancelled'";
$stmt = mysqli_prepare($conn, $booked_sql);
mysqli_stmt_bind_param($stmt, "is", $doctor_id, $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$booked = [];
while($row = mysqli_fetch_assoc($result)) {
    $booked[] = date('h:i A', strtotime($row['appointment_time']));
}

$slots = [];
foreach($all_slots as $slot) {
    $slots[] = [
        'time' => $slot,
        'booked' => in_array($slot, $booked)
    ];
}

echo json_encode($slots);
?>