<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
include '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login first'
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$type = $data['type'] ?? '';
$value = trim($data['value'] ?? '');
$user_id = (int) $_SESSION['user_id'];

if (!in_array($type, ['email', 'phone']) || empty($value)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit();
}

$otp = rand(100000, 999999);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// আগে existing verification আছে কিনা check
$check_sql = "SELECT id FROM verifications WHERE user_id = ? AND verify_type = ? ORDER BY id DESC LIMIT 1";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "is", $user_id, $type);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$existing = mysqli_fetch_assoc($check_result);

if ($existing) {
    $update_sql = "UPDATE verifications 
                   SET otp_code = ?, is_used = 0, expires_at = ?
                   WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ssi", $otp, $expires, $existing['id']);
    mysqli_stmt_execute($update_stmt);
} else {
    $insert_sql = "INSERT INTO verifications (user_id, verify_type, otp_code, is_used, expires_at)
                   VALUES (?, ?, ?, 0, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "isss", $user_id, $type, $otp, $expires);
    mysqli_stmt_execute($insert_stmt);
}

// Dev mode এ OTP response এ দেখাচ্ছি
echo json_encode([
    'success' => true,
    'otp' => $otp,
    'message' => 'OTP generated successfully'
]);
?>