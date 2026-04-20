<?php
session_start();
include '../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $gender = $_POST['gender'];
    $specialty_id = (int)$_POST['specialty_id'];
    $qualification = $_POST['qualification'] ?? '';
    $experience = (int)$_POST['experience_years'];
    $fee = (float)$_POST['consultation_fee'];
    $bio = $_POST['bio'] ?? '';

    // Insert user
    $sql = "INSERT INTO users (role, username, email, phone, password)
            VALUES ('doctor', ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssss",
        $username, $email, $phone, $password);

    if(mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);
        $doc_code = 'DOC-' . strtoupper(substr(uniqid(), -6));

        // Insert doctor
        $sql2 = "INSERT INTO doctors
                 (user_id, specialty_id, doctor_code, full_name,
                  gender, qualification, experience_years,
                  consultation_fee, bio)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt2 = mysqli_prepare($conn, $sql2);
        mysqli_stmt_bind_param($stmt2, "iissssiis",
            $user_id, $specialty_id, $doc_code, $full_name,
            $gender, $qualification, $experience, $fee, $bio);
        mysqli_stmt_execute($stmt2);

        echo "<script>
            alert('Doctor added successfully!');
            window.history.back();
        </script>";
    } else {
        echo "<script>
            alert('Error: " . mysqli_error($conn) . "');
            window.history.back();
        </script>";
    }
}
?>