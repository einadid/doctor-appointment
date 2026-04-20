<?php
header('Content-Type: application/json');
require_once '../config/session_check.php';
require_once '../config/database.php';

requireLogin();

$user_id = $_SESSION['user_id'];

// Pagination
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 5;
$offset   = ($page - 1) * $per_page;

// Total posts
$total = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM posts"
))[0];

// Posts with like/dislike count
$sql = "SELECT p.*,
               u.username,
               COALESCE(likes.cnt, 0) AS likes,
               COALESCE(dislikes.cnt, 0) AS dislikes,
               COALESCE(comments.cnt, 0) AS comment_count,
               ur.reaction AS user_reaction
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN (
            SELECT post_id, COUNT(*) AS cnt 
            FROM post_reactions 
            WHERE reaction = 'like' 
            GROUP BY post_id
        ) likes ON p.id = likes.post_id
        LEFT JOIN (
            SELECT post_id, COUNT(*) AS cnt 
            FROM post_reactions 
            WHERE reaction = 'dislike' 
            GROUP BY post_id
        ) dislikes ON p.id = dislikes.post_id
        LEFT JOIN (
            SELECT post_id, COUNT(*) AS cnt 
            FROM comments 
            GROUP BY post_id
        ) comments ON p.id = comments.post_id
        LEFT JOIN post_reactions ur 
            ON p.id = ur.post_id 
            AND ur.user_id = '$user_id'
        ORDER BY p.created_at DESC
        LIMIT $per_page OFFSET $offset";

$result = mysqli_query($conn, $sql);
$posts  = [];
while ($row = mysqli_fetch_assoc($result)) {
    $posts[] = $row;
}

echo json_encode([
    'status'      => 'success',
    'data'        => $posts,
    'total'       => (int)$total,
    'page'        => $page,
    'per_page'    => $per_page,
    'total_pages' => ceil($total / $per_page)
]);

mysqli_close($conn);
?>