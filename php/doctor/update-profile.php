<?php
session_start();
include '../config/database.php';

$user_id = $_SESSION['user_id'];
$doc = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM doctors WHERE user_id = $user_id"));
$doctor_id = $doc['id'];

$full_name = $_POST['full_name'];
$qualification = $_POST['qualification'];
$experience = (int)$_POST['experience_years'];
$fee = (float)$_POST['consultation_fee'];
$bio = $_POST['bio'];

// Image upload
$image_name = null;
if(isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_name = 'doc_' . $doctor_id . '_' . time() . '.' . $ext;
    move_uploaded_file(
        $_FILES['image']['tmp_name'],
        '../uploads/' . $image_name
    );
}

if($image_name) {
    $sql = "UPDATE doctors SET full_name=?, qualification=?,
            experience_years=?, consultation_fee=?, bio=?, image=?
            WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssiidsi",
        $full_name, $qualification, $experience, $fee, $bio,
        $image_name, $doctor_id);
} else {
    $sql = "UPDATE doctors SET full_name=?, qualification=?,
            experience_years=?, consultation_fee=?, bio=?
            WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssiidi",
        $full_name, $qualification, $experience, $fee, $bio,
        $doctor_id);
}

if(mysqli_stmt_execute($stmt)) {
    echo "<script>
        alert('Profile updated!');
        window.history.back();
    </script>";
} else {
    echo "<script>
        alert('Update failed!');
        window.history.back();
    </script>";
}
?>