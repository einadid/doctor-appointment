<?php
include '../config/database.php';

$username = $_GET['username'] ?? '';
$response = ['exists' => false];

if (!empty($username)) {
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $response['exists'] = true;
    }
}

echo json_encode($response);
?>