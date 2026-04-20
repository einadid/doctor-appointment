<?php
header('Content-Type: application/json');
require_once '../config/session_check.php';
require_once '../config/database.php';

requireRole('patient');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error']);
    exit;
}

$patient_id = $_SESSION['patient_id'];
$doctor_id  = mysqli_real_escape_string($conn, $_POST['doctor_id'] ?? '');
$rating     = (int)($_POST['rating'] ?? 0);
$review     = mysqli_real_escape_string($conn, $_POST['review'] ?? '');

if ($rating < 1 || $rating > 5) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Rating must be 1 to 5!'
    ]);
    exit;
}

// Insert or Update
$sql = "INSERT INTO ratings 
        (doctor_id, patient_id, rating, review)
        VALUES 
        ('$doctor_id', '$patient_id', '$rating', '$review')
        ON DUPLICATE KEY UPDATE 
        rating = '$rating', review = '$review'";

if (mysqli_query($conn, $sql)) {
    // Average rating
    $avg = mysqli_fetch_row(mysqli_query($conn,
        "SELECT AVG(rating) FROM ratings 
         WHERE doctor_id = '$doctor_id'"
    ))[0];

    echo json_encode([
        'status'     => 'success',
        'message'    => 'Rating submitted!',
        'avg_rating' => round($avg, 1)
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed!'
    ]);
}

mysqli_close($conn);
?>