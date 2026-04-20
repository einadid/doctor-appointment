<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$username = isset($_GET['username']) ? 
            mysqli_real_escape_string($conn, trim($_GET['username'])) : '';

if (empty($username)) {
    echo json_encode(['available' => false]);
    exit;
}

$sql = "SELECT id FROM users WHERE username = '$username' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    echo json_encode(['available' => false]);
} else {
    echo json_encode(['available' => true]);
}

mysqli_close($conn);
?>