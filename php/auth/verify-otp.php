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

$otp = trim($data['otp'] ?? '');
$type = $data['type'] ?? '';
$user_id = (int) $_SESSION['user_id'];

if (!in_array($type, ['email', 'phone']) || empty($otp)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit();
}

$sql = "SELECT id FROM verifications
        WHERE user_id = ?
        AND verify_type = ?
        AND otp_code = ?
        AND is_used = 0
        AND expires_at > NOW()
        ORDER BY id DESC
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iss", $user_id, $type, $otp);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

if ($row) {
    $upd1 = "UPDATE verifications SET is_used = 1 WHERE id = ?";
    $stmt1 = mysqli_prepare($conn, $upd1);
    mysqli_stmt_bind_param($stmt1, "i", $row['id']);
    mysqli_stmt_execute($stmt1);

    if ($type === 'email') {
        $upd2 = "UPDATE users SET email_verified = 1 WHERE id = ?";
    } else {
        $upd2 = "UPDATE users SET phone_verified = 1 WHERE id = ?";
    }

    $stmt2 = mysqli_prepare($conn, $upd2);
    mysqli_stmt_bind_param($stmt2, "i", $user_id);
    mysqli_stmt_execute($stmt2);

    echo json_encode([
        'success' => true,
        'message' => ucfirst($type) . ' verified successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or expired OTP'
    ]);
}
?>