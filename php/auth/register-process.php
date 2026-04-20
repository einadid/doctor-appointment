<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];

    // Check duplicate
    $check = "SELECT id FROM users 
              WHERE username = ? OR email = ? OR phone = ?";
    $stmt_check = mysqli_prepare($conn, $check);
    mysqli_stmt_bind_param($stmt_check, "sss",
        $username, $email, $phone);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if(mysqli_stmt_num_rows($stmt_check) > 0) {
        echo "<script>
            alert('Username, Email or Phone already exists!');
            window.history.back();
        </script>";
        exit();
    }

    // Insert user
    $sql = "INSERT INTO users (role, username, email, phone, password)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssss",
        $role, $username, $email, $phone, $password);

    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);

        if($role == 'patient') {
            $full_name = $_POST['full_name'] ?? $username;
            $p_code = "PAT-" . strtoupper(substr(uniqid(), -6));

            $sql_p = "INSERT INTO patients 
                      (user_id, patient_code, full_name, gender, date_of_birth)
                      VALUES (?, ?, ?, ?, ?)";
            $stmt_p = mysqli_prepare($conn, $sql_p);
            mysqli_stmt_bind_param($stmt_p, "issss",
                $user_id, $p_code, $full_name, $gender, $dob);
            mysqli_stmt_execute($stmt_p);

        } elseif($role == 'doctor') {
            $full_name = $_POST['full_name'] ?? $username;
            $specialty_id = (int)($_POST['specialty_id'] ?? 1);
            $doc_code = "DOC-" . strtoupper(substr(uniqid(), -6));

            $sql_d = "INSERT INTO doctors
                      (user_id, specialty_id, doctor_code,
                       full_name, gender)
                      VALUES (?, ?, ?, ?, ?)";
            $stmt_d = mysqli_prepare($conn, $sql_d);
            mysqli_stmt_bind_param($stmt_d, "iisss",
                $user_id, $specialty_id, $doc_code,
                $full_name, $gender);
            mysqli_stmt_execute($stmt_d);
        }

        // Session start
        session_start();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;

        // Redirect to verify page
        header("Location: ../../pages/verify.php");
        exit();

    } else {
        echo "<script>
            alert('Registration failed: " . mysqli_error($conn) . "');
            window.history.back();
        </script>";
    }
}
?>