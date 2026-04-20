<?php
header('Content-Type: application/json');
require_once '../config/session_check.php';
require_once '../config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error']);
    exit;
}

$user_id  = $_SESSION['user_id'];
$post_id  = mysqli_real_escape_string($conn, $_POST['post_id'] ?? '');
$reaction = mysqli_real_escape_string($conn, $_POST['reaction'] ?? '');

if (empty($post_id) || !in_array($reaction, ['like', 'dislike'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

// Already reacted?
$existing = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM post_reactions 
     WHERE post_id = '$post_id' 
     AND user_id = '$user_id'"
));

if ($existing) {
    if ($existing['reaction'] === $reaction) {
        // Same reaction - remove it (toggle off)
        mysqli_query($conn,
            "DELETE FROM post_reactions 
             WHERE post_id = '$post_id' 
             AND user_id = '$user_id'"
        );
    } else {
        // Different reaction - update
        mysqli_query($conn,
            "UPDATE post_reactions 
             SET reaction = '$reaction' 
             WHERE post_id = '$post_id' 
             AND user_id = '$user_id'"
        );
    }
} else {
    // New reaction
    mysqli_query($conn,
        "INSERT INTO post_reactions 
         (post_id, user_id, reaction) 
         VALUES 
         ('$post_id', '$user_id', '$reaction')"
    );
}

// Updated counts
$likes = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM post_reactions 
     WHERE post_id = '$post_id' 
     AND reaction = 'like'"
))[0];

$dislikes = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM post_reactions 
     WHERE post_id = '$post_id' 
     AND reaction = 'dislike'"
))[0];

echo json_encode([
    'status'   => 'success',
    'likes'    => (int)$likes,
    'dislikes' => (int)$dislikes
]);

mysqli_close($conn);
?>