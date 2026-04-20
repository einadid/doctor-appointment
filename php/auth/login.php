<?php
header('Content-Type: application/json');
session_start();

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Invalid request'
    ]);
    exit;
}

// Input নাও
$username = mysqli_real_escape_string(
    $conn, trim($_POST['username'] ?? '')
);
$password = $_POST['password'] ?? '';

// Validation
if (empty($username) || empty($password)) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Username and password are required!'
    ]);
    exit;
}

// User খোঁজো
$sql = "SELECT * FROM users 
        WHERE username = '$username' 
        OR email = '$username' 
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'User not found!'
    ]);
    exit;
}

$user = mysqli_fetch_assoc($result);

// Password check
if (!password_verify($password, $user['password'])) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Wrong password!'
    ]);
    exit;
}

// Account active check
if ($user['status'] !== 'active') {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Your account is inactive!'
    ]);
    exit;
}

// ================================
// SESSION SET করো
// ================================
$_SESSION['user_id']    = $user['id'];
$_SESSION['username']   = $user['username'];
$_SESSION['email']      = $user['email'];
$_SESSION['role']       = $user['role'];
$_SESSION['logged_in']  = true;

// Role অনুযায়ী extra info নাও
if ($user['role'] === 'patient') {
    $pid = $user['id'];
    $p = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM patients WHERE user_id = '$pid' LIMIT 1"
    ));
    if ($p) {
        $_SESSION['full_name']    = $p['full_name'];
        $_SESSION['patient_id']   = $p['id'];
        $_SESSION['patient_code'] = $p['patient_code'];
        $_SESSION['image']        = $p['image'];
    }

} elseif ($user['role'] === 'doctor') {
    $did = $user['id'];
    $d = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM doctors WHERE user_id = '$did' LIMIT 1"
    ));
    if ($d) {
        $_SESSION['full_name']  = $d['full_name'];
        $_SESSION['doctor_id']  = $d['id'];
        $_SESSION['doctor_code']= $d['doctor_code'];
        $_SESSION['image']      = $d['image'];
    }

} elseif ($user['role'] === 'admin') {
    $aid = $user['id'];
    $a = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM admins WHERE user_id = '$aid' LIMIT 1"
    ));
    if ($a) {
        $_SESSION['full_name'] = $a['full_name'];
        $_SESSION['admin_id']  = $a['id'];
    }
}

echo json_encode([
    'status'  => 'success',
    'message' => 'Login successful!',
    'role'    => $user['role']
]);

mysqli_close($conn);
?>