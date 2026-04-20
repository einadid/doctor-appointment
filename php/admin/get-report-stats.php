<?php
include '../config/database.php';

$result = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT
        SUM(status='pending') as pending,
        SUM(status='approved') as approved,
        SUM(status='completed') as completed,
        SUM(status='cancelled') as cancelled
     FROM appointments"));

echo json_encode($result);
?>