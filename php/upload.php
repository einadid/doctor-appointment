<?php
session_start();
include 'config/database.php';

if(isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_name = uniqid() . '.' . $ext;
    $upload_path = '../uploads/' . $new_name;

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if(!in_array(strtolower($ext), $allowed)) {
        echo "<script>alert('Only images allowed!'); window.history.back();</script>";
        exit();
    }

    if(move_uploaded_file($file['tmp_name'], $upload_path)) {
        $user_id = $_SESSION['user_id'];
        
        // DB তে save করো
        $sql = "INSERT INTO uploads (user_id, file_name, file_path, file_type) 
                VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        $file_type = $file['type'];
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $new_name, $new_name, $file_type);
        mysqli_stmt_execute($stmt);

        echo "<script>alert('Upload Successful!'); window.history.back();</script>";
    } else {
        echo "<script>alert('Upload Failed!'); window.history.back();</script>";
    }
}
?>