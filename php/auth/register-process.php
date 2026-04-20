<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // User Table এ ডাটা ইনসার্ট
    $sql = "INSERT INTO users (role, username, email, phone, password) VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss", $role, $username, $email, $phone, $password);
    
    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);
        
        // যদি Patient হয়, তবে Patients Table এও ডাটা যাবে
        if($role == 'patient') {
            $full_name = $username; // সাময়িকভাবে ইউজারনেমকেই নাম ধরছি
            $dob = $_POST['dob'];
            $gender = $_POST['gender'];
            $p_code = "PAT-" . time();
            
            $sql_p = "INSERT INTO patients (user_id, patient_code, full_name, gender, date_of_birth) VALUES (?, ?, ?, ?, ?)";
            $stmt_p = mysqli_prepare($conn, $sql_p);
            mysqli_stmt_bind_param($stmt_p, "issss", $user_id, $p_code, $full_name, $gender, $dob);
            mysqli_stmt_execute($stmt_p);
        }
        
        echo "<script>alert('Registration Successful!'); window.location.href='../../pages/login.html';</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>