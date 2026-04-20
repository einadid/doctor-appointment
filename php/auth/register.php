<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request'
    ]);
    exit;
}

// Get form data
$role       = mysqli_real_escape_string($conn, $_POST['role'] ?? '');
$full_name  = mysqli_real_escape_string($conn, $_POST['full_name'] ?? '');
$username   = mysqli_real_escape_string($conn, $_POST['username'] ?? '');
$email      = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
$phone      = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
$gender     = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
$dob        = mysqli_real_escape_string($conn, $_POST['dob'] ?? '');
$password   = $_POST['password'] ?? '';

// Basic validation
if (empty($role) || empty($full_name) || empty($username) || 
    empty($email) || empty($phone) || empty($password)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'All fields are required!'
    ]);
    exit;
}

// Check duplicate username
$check = mysqli_query($conn, 
    "SELECT id FROM users WHERE username='$username' OR 
     email='$email' OR phone='$phone' LIMIT 1");

if (mysqli_num_rows($check) > 0) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Username, Email or Phone already exists!'
    ]);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Generate codes
$user_code = strtoupper($role[0]) . rand(10000, 99999);

// Insert into users table
$sql = "INSERT INTO users 
        (role, username, email, phone, password) 
        VALUES 
        ('$role', '$username', '$email', '$phone', '$hashed_password')";

if (!mysqli_query($conn, $sql)) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Registration failed! Try again.'
    ]);
    exit;
}

$user_id = mysqli_insert_id($conn);

// Handle profile image upload
$image_name = 'default.png';
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (in_array(strtolower($ext), $allowed)) {
        $image_name = 'user_' . $user_id . '.' . $ext;
        $upload_dir = '../../assets/images/uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        move_uploaded_file(
            $_FILES['image']['tmp_name'], 
            $upload_dir . $image_name
        );
    }
}

// Insert into role specific table
if ($role === 'patient') {

    $patient_code = 'P' . rand(10000, 99999);
    $sql2 = "INSERT INTO patients 
             (user_id, patient_code, full_name, gender, date_of_birth, image) 
             VALUES 
             ('$user_id', '$patient_code', '$full_name', 
              '$gender', '$dob', '$image_name')";
    mysqli_query($conn, $sql2);

} elseif ($role === 'doctor') {

    $specialty_id     = mysqli_real_escape_string($conn, $_POST['specialty_id'] ?? '1');
    $qualification    = mysqli_real_escape_string($conn, $_POST['qualification'] ?? '');
    $experience_years = (int)($_POST['experience_years'] ?? 0);
    $consultation_fee = (float)($_POST['consultation_fee'] ?? 0);
    $chamber_address  = mysqli_real_escape_string($conn, $_POST['chamber_address'] ?? '');
    $doctor_code      = 'D' . rand(10000, 99999);

    $sql2 = "INSERT INTO doctors 
             (user_id, specialty_id, doctor_code, full_name, gender, 
              qualification, experience_years, consultation_fee, 
              chamber_address, image) 
             VALUES 
             ('$user_id', '$specialty_id', '$doctor_code', '$full_name', 
              '$gender', '$qualification', '$experience_years', 
              '$consultation_fee', '$chamber_address', '$image_name')";
    mysqli_query($conn, $sql2);
}

echo json_encode([
    'status' => 'success',
    'message' => 'Registration successful!'
]);

mysqli_close($conn);
?>