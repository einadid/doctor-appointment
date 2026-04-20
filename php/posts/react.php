<?php
session_start();
include '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$post_id = (int)$data['post_id'];
$reaction = $data['reaction'];
$user_id = $_SESSION['user_id'];

// Already reacted কিনা চেক করো
$check = "SELECT id, reaction FROM post_reactions 
          WHERE post_id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $check);
mysqli_stmt_bind_param($stmt, "ii", $post_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$existing = mysqli_fetch_assoc($result);

if($existing) {
    if($existing['reaction'] == $reaction) {
        // Same reaction = remove it (toggle)
        $del = "DELETE FROM post_reactions WHERE post_id = ? AND user_id = ?";
        $stmt2 = mysqli_prepare($conn, $del);
        mysqli_stmt_bind_param($stmt2, "ii", $post_id, $user_id);
        mysqli_stmt_execute($stmt2);
    } else {
        // Different reaction = update it
        $upd = "UPDATE post_reactions SET reaction = ? 
                WHERE post_id = ? AND user_id = ?";
        $stmt2 = mysqli_prepare($conn, $upd);
        mysqli_stmt_bind_param($stmt2, "sii", $reaction, $post_id, $user_id);
        mysqli_stmt_execute($stmt2);
    }
} else {
    // New reaction
    $ins = "INSERT INTO post_reactions (post_id, user_id, reaction) VALUES (?, ?, ?)";
    $stmt2 = mysqli_prepare($conn, $ins);
    mysqli_stmt_bind_param($stmt2, "iis", $post_id, $user_id, $reaction);
    mysqli_stmt_execute($stmt2);
}

echo json_encode(['success' => true]);
?>