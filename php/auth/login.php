<?php
session_start();
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identity = $_POST['identity'];
    $password = $_POST['password'];

    $sql = "SELECT id, username, role, password FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $identity, $identity);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($user = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // ✅ এখানে .php দেওয়া আছে
            if ($user['role'] == 'patient') {
                header("Location: ../../pages/patient-dashboard.php");
            } elseif ($user['role'] == 'doctor') {
                header("Location: ../../pages/doctor-dashboard.php");
            } else {
                header("Location: ../../pages/admin-dashboard.php");
            }
            exit();

        } else {
            echo "<script>alert('Invalid Password!'); window.history.back();</script>";
        }
    } else {
        echo "<script>alert('User not found!'); window.history.back();</script>";
    }
}
?>