<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'doctor_appointment');

// Connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

// Charset
mysqli_set_charset($conn, 'utf8mb4');

// Base URL
define('BASE_URL', 'http://localhost/doctor-appointment/');
?>