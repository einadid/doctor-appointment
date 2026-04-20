<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$type = $data['type']; // 'email' or 'phone'
$value = $data['value'];
$user_id = $_SESSION['user_id'] ?? 0;

// 6 digit OTP generate করো
$otp = rand(100000, 999999);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// DB তে save করো
$sql = "INSERT INTO verifications 
        (user_id, verify_type, otp_code, expires_at)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        otp_code = VALUES(otp_code),
        expires_at = VALUES(expires_at),
        is_used = 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "isss",
    $user_id, $type, $otp, $expires);
mysqli_stmt_execute($stmt);

// Email পাঠানো (PHPMailer ছাড়া simple version)
if($type == 'email') {
    $to = $value;
    $subject = "DocBook - Email Verification OTP";
    $message = "
    <html>
    <body style='font-family:Arial,sans-serif;'>
        <div style='max-width:400px;margin:0 auto;
                    background:#f0f4f8;padding:30px;
                    border-radius:10px;'>
            <h2 style='color:#1a73e8;'>DocBook Verification</h2>
            <p>Your OTP code is:</p>
            <h1 style='font-size:40px;color:#1a73e8;
                       letter-spacing:8px;'>$otp</h1>
            <p style='color:#6c757d;font-size:13px;'>
                This OTP expires in 10 minutes.
            </p>
        </div>
    </body>
    </html>";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: noreply@docbook.com\r\n";

    // XAMPP localhost এ mail() কাজ করে না
    // তাই আমরা OTP টা response এ দিচ্ছি (development mode)
    echo json_encode([
        'success' => true,
        'otp' => $otp, // Production এ এটা remove করবে
        'message' => "OTP sent to $value"
    ]);
} else {
    // Phone verification - same way
    echo json_encode([
        'success' => true,
        'otp' => $otp,
        'message' => "OTP sent to $value"
    ]);
}
?>