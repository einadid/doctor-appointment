<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
include 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first!'); window.history.back();</script>";
    exit();
}

if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo "<script>alert('Only images allowed!'); window.history.back();</script>";
        exit();
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        echo "<script>alert('Max file size is 5MB!'); window.history.back();</script>";
        exit();
    }

    $new_name = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/doctor-appointment/uploads/';

    // uploads folder না থাকলে তৈরি করো
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
        $user_id = $_SESSION['user_id'];
        $role = $_SESSION['role'];

        // uploads table এ save
        $sql = "INSERT INTO uploads (user_id, file_name, file_path, file_type)
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        $file_type = $file['type'];
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $new_name, $new_name, $file_type);
        mysqli_stmt_execute($stmt);

        // Profile image update
        if ($role === 'patient') {
            mysqli_query($conn,
                "UPDATE patients SET image = '$new_name' WHERE user_id = $user_id");
        } elseif ($role === 'doctor') {
            mysqli_query($conn,
                "UPDATE doctors SET image = '$new_name' WHERE user_id = $user_id");
        }

        echo "<script>alert('Upload Successful!'); window.history.back();</script>";
    } else {
        echo "<script>alert('Upload Failed!'); window.history.back();</script>";
    }
} else {
    echo "<script>alert('Please select a file!'); window.history.back();</script>";
}
?>