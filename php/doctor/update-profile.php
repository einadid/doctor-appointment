<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    echo "<script>alert('Unauthorized!'); window.history.back();</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$doc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM doctors WHERE user_id = $user_id"));

if (!$doc) {
    echo "<script>alert('Doctor profile not found!'); window.history.back();</script>";
    exit();
}

$doctor_id = $doc['id'];
$full_name = mysqli_real_escape_string($conn, $_POST['full_name'] ?? '');
$qualification = mysqli_real_escape_string($conn, $_POST['qualification'] ?? '');
$experience = (int)($_POST['experience_years'] ?? 0);
$fee = (float)($_POST['consultation_fee'] ?? 0);
$bio = mysqli_real_escape_string($conn, trim($_POST['bio'] ?? ''));

// Image upload
$image_sql = '';
if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($ext, $allowed)) {
        $image_name = 'doc_' . $doctor_id . '_' . time() . '.' . $ext;
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/doctor-appointment/uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
            $image_sql = ", image = '$image_name'";
        }
    }
}

$sql = "UPDATE doctors SET
        full_name = '$full_name',
        qualification = '$qualification',
        experience_years = $experience,
        consultation_fee = $fee,
        bio = '$bio'
        $image_sql
        WHERE id = $doctor_id";

if (mysqli_query($conn, $sql)) {
    echo "<script>alert('Profile updated!'); window.location.href='../../pages/doctor-dashboard.php';</script>";
} else {
    echo "<script>alert('Update failed: " . mysqli_error($conn) . "'); window.history.back();</script>";
}
?>