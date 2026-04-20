<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.html");
    exit();
}

include '../php/config/database.php';

// Patient info নিয়ে আসা
$user_id = $_SESSION['user_id'];
$sql = "SELECT p.*, u.email, u.phone FROM patients p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$patient = mysqli_fetch_assoc($result);

// Posts নিয়ে আসা (Pagination এর জন্য)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

$total_posts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM posts"))['total'];
$total_pages = ceil($total_posts / $limit);

$posts_sql = "SELECT p.*, u.username FROM posts p 
              JOIN users u ON p.user_id = u.id 
              ORDER BY p.created_at DESC 
              LIMIT $limit OFFSET $offset";
$posts_result = mysqli_query($conn, $posts_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Dashboard - DocBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" 
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" 
    href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .dashboard-wrapper {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: #0d47a1;
            color: white;
            padding: 30px 20px;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .sidebar .user-info {
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar .user-info img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .sidebar .user-info h4 {
            font-size: 15px;
            font-weight: 600;
        }

        .sidebar .user-info p {
            font-size: 12px;
            opacity: 0.8;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            margin-bottom: 5px;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
        }

        .sidebar-menu a.logout {
            background: rgba(220, 53, 69, 0.3);
            margin-top: 20px;
        }

        /* Main Content */
        .main-content {
            padding: 30px;
            background: #f0f4f8;
        }

        .dashboard-header {
            margin-bottom: 25px;
        }

        .dashboard-header h2 {
            font-size: 22px;
            color: #1a73e8;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-card .info h3 {
            font-size: 24px;
            font-weight: 700;
        }

        .stat-card .info p {
            font-size: 13px;
            color: #6c757d;
        }

        /* Post Card */
        .post-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .post-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .post-header img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e8f0fe;
            object-fit: cover;
        }

        .post-header .post-meta h4 {
            font-size: 14px;
            font-weight: 600;
        }

        .post-header .post-meta p {
            font-size: 12px;
            color: #6c757d;
        }

        .post-content {
            font-size: 14px;
            color: #212529;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        /* Like/Dislike (Feature #3) */
        .post-actions {
            display: flex;
            gap: 15px;
            padding: 12px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }

        .post-actions button {
            background: none;
            border: none;
            font-size: 14px;
            cursor: pointer;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .post-actions button:hover {
            background: #f0f4f8;
        }

        .post-actions button.liked {
            color: #1a73e8;
        }

        .post-actions button.disliked {
            color: #dc3545;
        }

        /* Rating (Feature #7) */
        .rating-stars {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }

        .rating-stars i {
            font-size: 20px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .rating-stars i.active,
        .rating-stars i:hover {
            color: #ffc107;
        }

        /* Comments (Feature #2) */
        .comments-section {
            margin-top: 10px;
        }

        .comment-item {
            display: flex;
            gap: 10px;
            margin-bottom: 12px;
        }

        .comment-item .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e8f0fe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #1a73e8;
            font-weight: 600;
            flex-shrink: 0;
        }

        .comment-item .comment-body {
            background: #f0f4f8;
            border-radius: 10px;
            padding: 8px 12px;
            flex: 1;
        }

        .comment-item .comment-body strong {
            font-size: 12px;
            color: #1a73e8;
        }

        .comment-item .comment-body p {
            font-size: 13px;
            margin-top: 2px;
        }

        .comment-input-area {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .comment-input-area input {
            flex: 1;
            padding: 8px 14px;
            border-radius: 20px;
            border: 1px solid #ddd;
            font-size: 13px;
        }

        .comment-input-area button {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
        }

        /* Pagination (Feature #8) */
        .pagination {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 25px;
        }

        .pagination a {
            padding: 8px 14px;
            border-radius: 8px;
            background: white;
            color: #1a73e8;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s;
        }

        .pagination a.active,
        .pagination a:hover {
            background: #1a73e8;
            color: white;
        }

        /* Upload Section (Feature #13) */
        .upload-area {
            border: 2px dashed #1a73e8;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-area:hover {
            background: #e8f0fe;
        }

        .upload-area i {
            font-size: 40px;
            color: #1a73e8;
        }

        .upload-area p {
            font-size: 14px;
            color: #6c757d;
            margin-top: 10px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #212529;
            display: flex;
            align-items: center;
            gap: 8px;
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="user-info">
            <img src="<?= $patient['image'] ? '../uploads/'.$patient['image'] : 'https://ui-avatars.com/api/?name='.urlencode($patient['full_name']).'&background=1a73e8&color=fff' ?>" alt="Profile">
            <h4><?= htmlspecialchars($patient['full_name']) ?></h4>
            <p><?= htmlspecialchars($patient['patient_code']) ?></p>
        </div>

        <nav class="sidebar-menu">
            <a href="#" class="active"><i class="bi bi-grid"></i> Dashboard</a>
            <a href="#appointments"><i class="bi bi-calendar-check"></i> Appointments</a>
            <a href="#doctors"><i class="bi bi-person-badge"></i> Doctors</a>
            <a href="#posts"><i class="bi bi-newspaper"></i> Posts</a>
            <a href="#profile"><i class="bi bi-person"></i> Profile</a>
            <a href="../php/auth/logout.php" class="logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- Header -->
        <div class="dashboard-header">
            <h2>👋 Welcome, <?= htmlspecialchars($patient['full_name']) ?>!</h2>
            <p style="color:#6c757d; font-size:14px;">
                <?= date('l, d F Y') ?>
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon" style="background:#e8f0fe;">
                    <i class="bi bi-calendar-check" style="color:#1a73e8;"></i>
                </div>
                <div class="info">
                    <h3 id="total-appointments">0</h3>
                    <p>Total Appointments</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#d4edda;">
                    <i class="bi bi-check-circle" style="color:#198754;"></i>
                </div>
                <div class="info">
                    <h3 id="completed-appointments">0</h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#fff3cd;">
                    <i class="bi bi-clock" style="color:#ffc107;"></i>
                </div>
                <div class="info">
                    <h3 id="pending-appointments">0</h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>

        <!-- Image Upload Section (Feature #13) -->
        <div id="profile" style="margin-bottom:30px;">
            <p class="section-title">
                <i class="bi bi-cloud-upload"></i> Upload Profile Image
            </p>
            <form action="../php/upload.php" method="POST" 
                  enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                    <i class="bi bi-cloud-upload"></i>
                    <p>Click to upload image</p>
                    <small id="file-name" style="color:#1a73e8;"></small>
                </div>
                <input type="file" id="fileInput" name="image" 
                       accept="image/*" style="display:none;" 
                       onchange="showFileName(this)">
                <button type="submit" class="btn-submit" style="max-width:200px;">
                    Upload
                </button>
            </form>
        </div>

        <!-- Posts Section -->
        <div id="posts">
            <p class="section-title">
                <i class="bi bi-newspaper"></i> Health Posts
            </p>

            <?php while($post = mysqli_fetch_assoc($posts_result)): ?>
            <div class="post-card" id="post-<?= $post['id'] ?>">
                
                <!-- Post Header -->
                <div class="post-header">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($post['username']) ?>&background=1a73e8&color=fff" alt="">
                    <div class="post-meta">
                        <h4><?= htmlspecialchars($post['username']) ?></h4>
                        <p><?= date('d M Y, h:i A', strtotime($post['created_at'])) ?></p>
                    </div>
                </div>

                <!-- Post Content -->
                <div class="post-content">
                    <strong><?= htmlspecialchars($post['title']) ?></strong>
                    <p><?= nl2br(htmlspecialchars($post['content'])) ?></p>

                    <!-- YouTube Video (Feature #9) -->
                    <?php if($post['media_type'] == 'youtube' && $post['media_url']): ?>
                    <div style="margin-top:10px;">
                        <iframe width="100%" height="300" 
                                src="https://www.youtube.com/embed/<?= htmlspecialchars($post['media_url']) ?>" 
                                frameborder="0" allowfullscreen></iframe>
                    </div>
                    <?php endif; ?>

                    <!-- Image Media -->
                    <?php if($post['media_type'] == 'image' && $post['media_url']): ?>
                    <img src="../uploads/<?= htmlspecialchars($post['media_url']) ?>" 
                         style="width:100%; border-radius:8px; margin-top:10px;">
                    <?php endif; ?>
                </div>

                <!-- Like/Dislike (Feature #3) -->
                <div class="post-actions">
                    <button onclick="reactPost(<?= $post['id'] ?>, 'like')" 
                            id="like-btn-<?= $post['id'] ?>">
                        <i class="bi bi-hand-thumbs-up"></i>
                        <span id="like-count-<?= $post['id'] ?>">0</span>
                    </button>
                    <button onclick="reactPost(<?= $post['id'] ?>, 'dislike')" 
                            id="dislike-btn-<?= $post['id'] ?>">
                        <i class="bi bi-hand-thumbs-down"></i>
                        <span id="dislike-count-<?= $post['id'] ?>">0</span>
                    </button>
                    <button onclick="toggleComments(<?= $post['id'] ?>)">
                        <i class="bi bi-chat"></i> Comment
                    </button>
                </div>

                <!-- Rating (Feature #7) -->
                <div style="margin-bottom:10px;">
                    <small style="color:#6c757d;">Rate this post:</small>
                    <div class="rating-stars" id="rating-<?= $post['id'] ?>">
                        <?php for($i=1; $i<=5; $i++): ?>
                        <i class="bi bi-star-fill" 
                           onclick="ratePost(<?= $post['id'] ?>, <?= $i ?>)" 
                           data-value="<?= $i ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Comments Section (Feature #2) -->
                <div class="comments-section" id="comments-<?= $post['id'] ?>" style="display:none;">
                    <div id="comment-list-<?= $post['id'] ?>">
                        <!-- Comments will load here via AJAX -->
                    </div>
                    <div class="comment-input-area">
                        <input type="text" 
                               id="comment-input-<?= $post['id'] ?>" 
                               placeholder="Write a comment...">
                        <button onclick="submitComment(<?= $post['id'] ?>)">
                            Send
                        </button>
                    </div>
                </div>

            </div>
            <?php endwhile; ?>

            <!-- Pagination (Feature #8) -->
            <div class="pagination">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                <a href="?page=<?= $i ?>" 
                   class="<?= $page == $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>

        </div>

    </main>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

<script>
// ================================
// GSAP Animations
// ================================
gsap.from(".stat-card", {
    duration: 0.6,
    y: 30,
    opacity: 0,
    stagger: 0.15,
    ease: "power3.out"
});

gsap.from(".post-card", {
    duration: 0.5,
    y: 20,
    opacity: 0,
    stagger: 0.1,
    delay: 0.3,
    ease: "power3.out"
});

// ================================
// Load Appointment Stats
// ================================
fetch('../php/patient/get-stats.php')
    .then(res => res.json())
    .then(data => {
        document.getElementById('total-appointments').textContent = data.total || 0;
        document.getElementById('completed-appointments').textContent = data.completed || 0;
        document.getElementById('pending-appointments').textContent = data.pending || 0;
    });

// ================================
// Load Reaction Counts on Page Load
// ================================
document.querySelectorAll('.post-card').forEach(card => {
    let postId = card.id.replace('post-', '');
    loadReactions(postId);
    loadComments(postId);
});

// ================================
// Feature #3: Like/Dislike
// ================================
function reactPost(postId, type) {
    fetch('../php/posts/react.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId, reaction: type })
    })
    .then(res => res.json())
    .then(data => {
        loadReactions(postId);
    });
}

function loadReactions(postId) {
    fetch(`../php/posts/get-reactions.php?post_id=${postId}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById(`like-count-${postId}`).textContent = data.likes || 0;
            document.getElementById(`dislike-count-${postId}`).textContent = data.dislikes || 0;
        });
}

// ================================
// Feature #2: Real-time Comments
// ================================
function toggleComments(postId) {
    let section = document.getElementById(`comments-${postId}`);
    if (section.style.display === 'none') {
        section.style.display = 'block';
        loadComments(postId);
    } else {
        section.style.display = 'none';
    }
}

function loadComments(postId) {
    fetch(`../php/posts/get-comments.php?post_id=${postId}`)
        .then(res => res.json())
        .then(data => {
            let list = document.getElementById(`comment-list-${postId}`);
            list.innerHTML = '';
            data.forEach(c => {
                list.innerHTML += `
                    <div class="comment-item">
                        <div class="comment-avatar">${c.username.charAt(0).toUpperCase()}</div>
                        <div class="comment-body">
                            <strong>${c.username}</strong>
                            <p>${c.comment_text}</p>
                        </div>
                    </div>`;
            });
        });
}

function submitComment(postId) {
    let input = document.getElementById(`comment-input-${postId}`);
    let text = input.value.trim();
    if (!text) return;

    fetch('../php/posts/add-comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId, comment: text })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadComments(postId);
        }
    });
}

// ================================
// Feature #7: Rating
// ================================
function ratePost(postId, rating) {
    let stars = document.querySelectorAll(`#rating-${postId} i`);
    stars.forEach((star, index) => {
        star.style.color = index < rating ? '#ffc107' : '#ddd';
    });

    fetch('../php/posts/rate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId, rating: rating })
    })
    .then(res => res.json())
    .then(data => {
        console.log('Rating saved:', data);
    });
}

// ================================
// File Upload Preview
// ================================
function showFileName(input) {
    let name = input.files[0]?.name;
    document.getElementById('file-name').textContent = name || '';
}
</script>

</body>
</html>