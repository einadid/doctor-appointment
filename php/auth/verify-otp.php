<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$otp = $data['otp'];
$type = $data['type'];
$user_id = $_SESSION['user_id'] ?? 0;

// OTP check করো
$sql = "SELECT * FROM verifications 
        WHERE user_id = ? 
        AND verify_type = ? 
        AND otp_code = ? 
        AND is_used = 0 
        AND expires_at > NOW()";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iss",
    $user_id, $type, $otp);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) > 0) {
    // OTP mark as used
    $upd = "UPDATE verifications 
            SET is_used = 1 
            WHERE user_id = ? AND verify_type = ?";
    $stmt2 = mysqli_prepare($conn, $upd);
    mysqli_stmt_bind_param($stmt2, "is", $user_id, $type);
    mysqli_stmt_execute($stmt2);

    // User verified update করো
    if($type == 'email') {
        mysqli_query($conn,
            "UPDATE users SET email_verified = 1 
             WHERE id = $user_id");
    } else {
        mysqli_query($conn,
            "UPDATE users SET phone_verified = 1 
             WHERE id = $user_id");
    }

    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' verified successfully!'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or expired OTP!'
    ]);
}
?>