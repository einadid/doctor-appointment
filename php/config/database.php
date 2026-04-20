<?php
// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'doctor_appointment');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conn, 'utf8mb4');

// Base URL helper
define('BASE_URL', 'http://localhost/doctor-appointment/');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/doctor-appointment/uploads/');
?>