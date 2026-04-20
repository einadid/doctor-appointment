<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.html");
    exit();
}
include '../php/config/database.php';

$user_id = $_SESSION['user_id'];

// Patient info
$p_result = mysqli_query(
    $conn,
    "SELECT p.*, u.email, u.phone 
     FROM patients p 
     JOIN users u ON p.user_id = u.id 
     WHERE p.user_id = $user_id"
);
$patient = mysqli_fetch_assoc($p_result);

if (!$patient) {
    $patient = [
        'id' => 0,
        'full_name' => $_SESSION['username'],
        'patient_code' => 'N/A',
        'image' => null,
        'gender' => '',
        'date_of_birth' => '',
        'visits' => 0,
        'email' => '',
        'phone' => ''
    ];
}

$patient_id = $patient['id'];

// Stats
$stats_total = 0;
$stats_completed = 0;
$stats_pending = 0;
if ($patient_id > 0) {
    $st = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) as t, 
                SUM(status='completed') as c,
                SUM(status='pending') as p
         FROM appointments WHERE patient_id = $patient_id"
    ));
    $stats_total = $st['t'] ?? 0;
    $stats_completed = $st['c'] ?? 0;
    $stats_pending = $st['p'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../assets/css/fix.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - DocBook</title>
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f4f8;
        }

        .dashboard {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            background: linear-gradient(180deg, #0a1628, #1a3a5c);
            color: white;
            padding: 25px 18px;
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-profile {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-profile img {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            border: 3px solid #34d399;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .sidebar-profile h4 {
            font-size: 14px;
            font-weight: 600;
        }

        .sidebar-profile p {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
        }

        .sidebar-section {
            font-size: 10px;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.3);
            margin: 18px 0 8px 12px;
            text-transform: uppercase;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 3px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(52, 211, 153, 0.15);
            color: #34d399;
        }

        .sidebar-menu a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .sidebar-menu a.logout-btn {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            margin-top: 15px;
        }

        .sidebar-menu a.logout-btn:hover {
            background: #ef4444;
            color: white;
        }

        /* ========== MAIN CONTENT ========== */
        .main {
            margin-left: 260px;
            padding: 25px 30px;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .topbar h2 {
            font-size: 20px;
            font-weight: 700;
            color: #0a1628;
        }

        .topbar p {
            font-size: 12px;
            color: #6c757d;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-book-now {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: linear-gradient(135deg, #34d399, #059669);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-book-now:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 211, 153, 0.4);
        }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: white;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            transition: all 0.3s;
        }

        .stat-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .stat-info h3 {
            font-size: 24px;
            font-weight: 700;
            color: #0a1628;
            line-height: 1;
        }

        .stat-info p {
            font-size: 12px;
            color: #6c757d;
            margin-top: 3px;
        }

        /* ========== TAB SYSTEM ========== */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ========== CARDS ========== */
        .content-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .card-header h3 {
            font-size: 16px;
            font-weight: 600;
            color: #0a1628;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ========== TABLE ========== */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-table th {
            background: #f8fafb;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: #6c757d;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f4f8;
        }

        .data-table tr:hover td {
            background: #f8fafb;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.approved {
            background: #d4edda;
            color: #155724;
        }

        .badge.completed {
            background: #cce5ff;
            color: #004085;
        }

        .badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* ========== DOCTOR CARDS ========== */
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .doc-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e8ecf0;
            transition: all 0.3s;
        }

        .doc-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-color: #1a73e8;
        }

        .doc-card img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid #1a73e8;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .doc-card h4 {
            font-size: 14px;
            font-weight: 600;
            color: #0a1628;
        }

        .doc-card .specialty {
            font-size: 12px;
            color: #1a73e8;
            font-weight: 500;
        }

        .doc-card .fee {
            font-size: 13px;
            color: #198754;
            font-weight: 600;
            margin-top: 5px;
        }

        .doc-card .btn-book-small {
            display: inline-block;
            margin-top: 10px;
            padding: 7px 20px;
            background: #1a73e8;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }

        .doc-card .btn-book-small:hover {
            background: #0d47a1;
        }

        /* ========== POST CARD ========== */
        .post-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 16px;
            border: 1px solid #e8ecf0;
            transition: all 0.3s;
        }

        .post-card:hover {
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        .post-top {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .post-top img {
            width: 38px;
            height: 38px;
            border-radius: 50%;
        }

        .post-top .meta h4 {
            font-size: 13px;
            font-weight: 600;
        }

        .post-top .meta p {
            font-size: 11px;
            color: #6c757d;
        }

        .post-body {
            font-size: 13px;
            color: #333;
            line-height: 1.6;
            margin-bottom: 12px;
        }

        .post-body strong {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
            color: #0a1628;
        }

        .post-actions-bar {
            display: flex;
            gap: 12px;
            padding-top: 10px;
            border-top: 1px solid #f0f4f8;
        }

        .post-action-btn {
            background: none;
            border: none;
            font-size: 13px;
            color: #6c757d;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 8px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .post-action-btn:hover {
            background: #f0f4f8;
            color: #1a73e8;
        }

        /* Rating Stars */
        .stars {
            display: flex;
            gap: 3px;
            margin-top: 8px;
        }

        .stars i {
            font-size: 18px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .stars i.active,
        .stars i:hover {
            color: #fbbf24;
        }

        /* Comments */
        .comments-area {
            margin-top: 10px;
            display: none;
        }

        .comments-area.show {
            display: block;
        }

        .comment-item {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
        }

        .comment-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e8f0fe;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: #1a73e8;
            font-weight: 700;
            flex-shrink: 0;
        }

        .comment-body {
            background: #f8fafb;
            border-radius: 10px;
            padding: 8px 12px;
            flex: 1;
        }

        .comment-body strong {
            font-size: 11px;
            color: #1a73e8;
        }

        .comment-body p {
            font-size: 12px;
            margin-top: 2px;
            color: #333;
        }

        .comment-input-row {
            display: flex;
            gap: 8px;
            margin-top: 10px;
        }

        .comment-input-row input {
            flex: 1;
            padding: 8px 14px;
            border: 1.5px solid #e0e5ec;
            border-radius: 20px;
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
            outline: none;
        }

        .comment-input-row input:focus {
            border-color: #1a73e8;
        }

        .comment-input-row button {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        /* Upload Area */
        .upload-zone {
            border: 2px dashed #1a73e8;
            border-radius: 14px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }

        .upload-zone:hover {
            background: #e8f0fe;
        }

        .upload-zone i {
            font-size: 36px;
            color: #1a73e8;
        }

        .upload-zone p {
            font-size: 13px;
            color: #6c757d;
            margin-top: 8px;
        }

        /* Create Post Area */
        .create-post-box textarea {
            width: 100%;
            padding: 12px;
            border: 1.5px solid #e0e5ec;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            resize: vertical;
            outline: none;
            min-height: 80px;
        }

        .create-post-box textarea:focus {
            border-color: #1a73e8;
        }

        /* Pagination */
        .pagination {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            padding: 7px 13px;
            border-radius: 8px;
            background: white;
            color: #1a73e8;
            font-size: 13px;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
        }

        .pagination a.active,
        .pagination a:hover {
            background: #1a73e8;
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 12px;
        }

        .empty-state h4 {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .empty-state p {
            font-size: 13px;
        }

        .empty-state a {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 22px;
            background: #1a73e8;
            color: white;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
        }

        /* Override to use fix.css */
        .dashboard {
            display: flex !important;
        }

        .sidebar {
            width: 230px !important;
        }

        .main {
            margin-left: 230px !important;
            width: calc(100% - 230px) !important;
        }

        @media (max-width: 768px) {
            .dashboard {
                display: block !important;
            }

            .sidebar {
                display: none !important;
            }

            .main {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }

        .simple-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
            background: white;
            border-bottom: 2px solid #1a73e8;
            padding: 10px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .simple-nav .logo {
            font-size: 18px;
            font-weight: bold;
            color: #1a73e8;
        }

        .simple-nav .logo span {
            color: #198754;
        }

        .simple-nav .nav-links {
            display: flex;
            gap: 18px;
            align-items: center;
        }

        .simple-nav .nav-links a {
            color: #333;
            font-size: 13px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .simple-nav .nav-links a:hover {
            color: #1a73e8;
        }

        /* Sidebar top adjust */
        .sidebar {
            top: 50px !important;
            height: calc(100vh - 50px) !important;
        }

        /* Main content top adjust */
        .main {
            margin-top: 50px !important;
        }
    </style>
</head>

<body>

    <div class="dashboard">

        <!-- ========== SIDEBAR ========== -->
        <aside class="sidebar">
            <div class="sidebar-profile">
                <img src="<?= $patient['image']
                                ? '../uploads/' . $patient['image']
                                : 'https://ui-avatars.com/api/?name=' . urlencode($patient['full_name']) . '&background=1a73e8&color=fff' ?>" alt="Profile">
                <h4><?= htmlspecialchars($patient['full_name']) ?></h4>
                <p><?= $patient['patient_code'] ?></p>
            </div>

            <div class="sidebar-section">Main</div>
            <!-- TOP NAVBAR -->
            <nav class="simple-nav">
                <div class="logo">
                    <i class="bi bi-heart-pulse"></i> Doc<span>Book</span>
                </div>
                <div class="nav-links">
                    <a href="../index.html"><i class="bi bi-house"></i> Home</a>
                    <a href="patient-dashboard.php"><i class="bi bi-grid"></i> Dashboard</a>
                    <a href="book-appointment.php"><i class="bi bi-calendar-plus"></i> Book</a>
                    <a href="symptom-checker.php"><i class="bi bi-cpu"></i> AI Checker</a>
                    <a href="../php/auth/logout.php" style="color:#dc3545;"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </div>
            </nav>
        </aside>

        <!-- ========== MAIN CONTENT ========== -->
        <div class="main">

            <!-- Top Bar -->
            <div class="topbar dash-topbar">
                <div>
                    <h2>👋 Welcome, <?= htmlspecialchars($patient['full_name']) ?></h2>
                    <p><?= date('l, d F Y') ?></p>
                </div>
                <div class="topbar-right">
                    <a href="book-appointment.php" class="btn-book-now">
                        <i class="bi bi-calendar-plus"></i>
                        Book Appointment
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-icon" style="background:#e8f0fe; color:#1a73e8;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats_total ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="background:#d4edda; color:#198754;">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats_completed ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="background:#fff3cd; color:#ffc107;">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats_pending ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="background:#e0f2f1; color:#009688;">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $patient['visits'] ?? 0 ?></h3>
                        <p>Total Visits</p>
                    </div>
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: OVERVIEW -->
            <!-- ======================== -->
            <div class="tab-content active" id="content-overview">
                <!-- Recent Appointments -->
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="bi bi-clock-history" style="color:#1a73e8;"></i> Recent Appointments</h3>
                        <a onclick="switchTab('appointments')" style="color:#1a73e8; font-size:13px; cursor:pointer;">View All →</a>
                    </div>
                    <div id="recent-appointments">
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h4>No Appointments Yet</h4>
                            <p>Book your first appointment with a doctor</p>
                            <a href="book-appointment.php">Book Now</a>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;">
                    <div class="content-card" style="cursor:pointer; text-align:center;" onclick="switchTab('doctors')">
                        <i class="bi bi-person-badge" style="font-size:30px; color:#1a73e8;"></i>
                        <h4 style="font-size:14px; margin-top:10px;">Find Doctors</h4>
                        <p style="font-size:12px; color:#6c757d;">Browse all specialists</p>
                    </div>
                    <div class="content-card" style="cursor:pointer; text-align:center;" onclick="switchTab('posts')">
                        <i class="bi bi-newspaper" style="font-size:30px; color:#198754;"></i>
                        <h4 style="font-size:14px; margin-top:10px;">Health Posts</h4>
                        <p style="font-size:12px; color:#6c757d;">Read health tips</p>
                    </div>
                    <a href="symptom-checker.php" class="content-card" style="cursor:pointer; text-align:center; text-decoration:none; color:inherit;">
                        <i class="bi bi-cpu" style="font-size:30px; color:#e535ab;"></i>
                        <h4 style="font-size:14px; margin-top:10px;">AI Symptom Checker</h4>
                        <p style="font-size:12px; color:#6c757d;">Check your symptoms</p>
                    </a>
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: MY APPOINTMENTS -->
            <!-- ======================== -->
            <div class="tab-content" id="content-appointments">
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="bi bi-calendar-check" style="color:#1a73e8;"></i> My Appointments</h3>
                        <a href="book-appointment.php" class="btn-book-now" style="padding:8px 18px; font-size:12px;">
                            <i class="bi bi-plus"></i> New Appointment
                        </a>
                    </div>
                    <div id="appointments-list">
                        <!-- Load via AJAX -->
                    </div>
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: FIND DOCTORS -->
            <!-- ======================== -->
            <div class="tab-content" id="content-doctors">
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="bi bi-person-badge" style="color:#1a73e8;"></i> Available Doctors</h3>
                        <select id="filterSpecialty" onchange="loadDoctors()" style="padding:6px 12px; border-radius:8px; border:1.5px solid #ddd; font-size:12px; font-family:Poppins;">
                            <option value="0">All Specialties</option>
                        </select>
                    </div>
                    <div class="doctors-grid" id="doctors-grid">
                        <!-- Load via AJAX -->
                    </div>
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: HEALTH POSTS -->
            <!-- ======================== -->
            <div class="tab-content" id="content-posts">

                <!-- Create Post -->
                <div class="content-card create-post-box">
                    <div class="card-header">
                        <h3><i class="bi bi-pencil-square" style="color:#198754;"></i> Create Post</h3>
                    </div>
                    <input type="text" id="postTitle" placeholder="Post title..."
                        style="width:100%; padding:10px; border:1.5px solid #e0e5ec; border-radius:10px; font-family:Poppins; font-size:13px; margin-bottom:10px; outline:none;">
                    <textarea id="postContent" placeholder="Share health tips..."></textarea>
                    <div style="display:flex; gap:10px; margin-top:10px; flex-wrap:wrap;">
                        <select id="mediaType" onchange="toggleMedia()" style="padding:8px 12px; border:1.5px solid #e0e5ec; border-radius:8px; font-size:12px; font-family:Poppins;">
                            <option value="none">No Media</option>
                            <option value="youtube">YouTube Video</option>
                        </select>
                        <input type="text" id="mediaUrl" placeholder="YouTube Video ID" style="flex:1; display:none; padding:8px; border:1.5px solid #e0e5ec; border-radius:8px; font-size:12px;">
                        <button onclick="createPost()" style="background:#198754; color:white; border:none; padding:8px 20px; border-radius:8px; font-size:13px; cursor:pointer; font-family:Poppins;">
                            <i class="bi bi-send"></i> Post
                        </button>
                    </div>
                </div>

                <!-- Posts List -->
                <div id="posts-list">
                    <!-- Load via AJAX -->
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: PROFILE -->
            <!-- ======================== -->
            <div class="tab-content" id="content-profile">
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="bi bi-person" style="color:#1a73e8;"></i> My Profile</h3>
                    </div>

                    <!-- Upload Image -->
                    <form action="../php/upload.php" method="POST" enctype="multipart/form-data">
                        <div class="upload-zone" onclick="document.getElementById('fileInput').click()">
                            <i class="bi bi-cloud-upload"></i>
                            <p>Click to upload profile image</p>
                            <small id="file-name" style="color:#1a73e8;"></small>
                        </div>
                        <input type="file" id="fileInput" name="image" accept="image/*" style="display:none;" onchange="document.getElementById('file-name').textContent = this.files[0]?.name || ''">
                        <button type="submit" style="background:#1a73e8; color:white; border:none; padding:8px 20px; border-radius:8px; cursor:pointer; font-size:13px; font-family:Poppins;">Upload</button>
                    </form>

                    <!-- Profile Info -->
                    <div style="margin-top:25px; display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                        <div style="background:#f8fafb; padding:15px; border-radius:10px;">
                            <p style="font-size:11px; color:#6c757d;">Full Name</p>
                            <h4 style="font-size:14px;"><?= htmlspecialchars($patient['full_name']) ?></h4>
                        </div>
                        <div style="background:#f8fafb; padding:15px; border-radius:10px;">
                            <p style="font-size:11px; color:#6c757d;">Patient Code</p>
                            <h4 style="font-size:14px;"><?= $patient['patient_code'] ?></h4>
                        </div>
                        <div style="background:#f8fafb; padding:15px; border-radius:10px;">
                            <p style="font-size:11px; color:#6c757d;">Email</p>
                            <h4 style="font-size:14px;"><?= htmlspecialchars($patient['email'] ?? 'N/A') ?></h4>
                        </div>
                        <div style="background:#f8fafb; padding:15px; border-radius:10px;">
                            <p style="font-size:11px; color:#6c757d;">Phone</p>
                            <h4 style="font-size:14px;"><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></h4>
                        </div>
                        <div style="background:#f8fafb; padding:15px; border-radius:10px;">
                            <p style="font-size:11px; color:#6c757d;">Gender</p>
                            <h4 style="font-size:14px;"><?= ucfirst($patient['gender'] ?? 'N/A') ?></h4>
                        </div>
                        <div style="background:#f8fafb; padding:15px; border-radius:10px;">
                            <p style="font-size:11px; color:#6c757d;">Date of Birth</p>
                            <h4 style="font-size:14px;"><?= $patient['date_of_birth'] ?? 'N/A' ?></h4>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script>
        // ================================
        // TAB SWITCHING
        // ================================
        function switchTab(tab) {
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));

            document.getElementById(`content-${tab}`).classList.add('active');
            let nav = document.getElementById(`nav-${tab}`);
            if (nav) nav.classList.add('active');

            gsap.from(`#content-${tab}`, {
                duration: 0.4,
                opacity: 0,
                y: 15,
                ease: "power2.out"
            });

            // Load data
            if (tab === 'appointments') loadAppointments();
            if (tab === 'doctors') {
                loadSpecialtyFilter();
                loadDoctors();
            }
            if (tab === 'posts') loadPosts();
        }

        // ================================
        // LOAD APPOINTMENTS
        // ================================
        function loadAppointments() {
            fetch('../php/patient/get-appointments.php')
                .then(res => res.json())
                .then(data => {
                    let el = document.getElementById('appointments-list');
                    if (data.length === 0) {
                        el.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <h4>No Appointments</h4>
                        <p>You haven't booked any appointment yet</p>
                        <a href="book-appointment.php">Book Now</a>
                    </div>`;
                        return;
                    }
                    let html = '<table class="data-table"><thead><tr><th>#</th><th>Doctor</th><th>Specialty</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>';
                    data.forEach((a, i) => {
                        html += `<tr>
                    <td>${i+1}</td>
                    <td><strong>Dr. ${a.doctor_name}</strong></td>
                    <td>${a.specialty}</td>
                    <td>${a.appointment_date}</td>
                    <td>${a.appointment_time}</td>
                    <td><span class="badge ${a.status}">${a.status.charAt(0).toUpperCase()+a.status.slice(1)}</span></td>
                </tr>`;
                    });
                    html += '</tbody></table>';
                    el.innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('appointments-list').innerHTML = '<p style="text-align:center; color:#6c757d; padding:20px;">Could not load appointments</p>';
                });
        }

        // ================================
        // LOAD DOCTORS
        // ================================
        function loadSpecialtyFilter() {
            fetch('../php/api/get-specialties.php')
                .then(res => res.json())
                .then(data => {
                    let select = document.getElementById('filterSpecialty');
                    data.forEach(s => {
                        if (!select.querySelector(`option[value="${s.id}"]`)) {
                            select.innerHTML += `<option value="${s.id}">${s.name}</option>`;
                        }
                    });
                });
        }

        function loadDoctors() {
            let specialtyId = document.getElementById('filterSpecialty').value;
            let url = specialtyId > 0 ?
                `../php/api/get-doctors.php?specialty_id=${specialtyId}` :
                '../php/api/get-all-doctors.php';

            fetch(url)
                .then(res => res.json())
                .then(doctors => {
                    let grid = document.getElementById('doctors-grid');
                    if (doctors.length === 0) {
                        grid.innerHTML = '<div class="empty-state"><i class="bi bi-person-x"></i><h4>No Doctors Found</h4></div>';
                        return;
                    }
                    grid.innerHTML = '';
                    doctors.forEach(d => {
                        grid.innerHTML += `
                    <div class="doc-card">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(d.full_name)}&background=1a73e8&color=fff">
                        <h4>Dr. ${d.full_name}</h4>
                        <div class="specialty">${d.specialty || 'General'}</div>
                        <div class="fee">৳${d.consultation_fee || 0}</div>
                        <a href="book-appointment.php" class="btn-book-small">Book Now</a>
                    </div>`;
                    });
                });
        }

        // ================================
        // LOAD POSTS
        // ================================
        function loadPosts() {
            fetch('../php/posts/get-posts.php')
                .then(res => res.json())
                .then(posts => {
                    let el = document.getElementById('posts-list');
                    if (posts.length === 0) {
                        el.innerHTML = '<div class="empty-state"><i class="bi bi-newspaper"></i><h4>No Posts Yet</h4><p>Be the first to share health tips!</p></div>';
                        return;
                    }
                    el.innerHTML = '';
                    posts.forEach(p => {
                        el.innerHTML += `
                    <div class="post-card" id="post-${p.id}">
                        <div class="post-top">
                            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(p.username)}&background=1a73e8&color=fff">
                            <div class="meta">
                                <h4>${p.username}</h4>
                                <p>${p.created_at}</p>
                            </div>
                        </div>
                        <div class="post-body">
                            <strong>${p.title}</strong>
                            <p>${p.content}</p>
                            ${p.media_type === 'youtube' && p.media_url ? 
                                `<iframe width="100%" height="250" src="https://www.youtube.com/embed/${p.media_url}" frameborder="0" allowfullscreen style="border-radius:10px; margin-top:10px;"></iframe>` : ''}
                        </div>
                        <div class="post-actions-bar">
                            <button class="post-action-btn" onclick="reactPost(${p.id},'like')">
                                <i class="bi bi-hand-thumbs-up"></i> <span id="like-${p.id}">0</span>
                            </button>
                            <button class="post-action-btn" onclick="reactPost(${p.id},'dislike')">
                                <i class="bi bi-hand-thumbs-down"></i> <span id="dislike-${p.id}">0</span>
                            </button>
                            <button class="post-action-btn" onclick="toggleComments(${p.id})">
                                <i class="bi bi-chat"></i> Comment
                            </button>
                        </div>
                        <div class="stars" id="stars-${p.id}">
                            ${[1,2,3,4,5].map(i => `<i class="bi bi-star-fill" onclick="ratePost(${p.id},${i})" data-val="${i}"></i>`).join('')}
                        </div>
                        <div class="comments-area" id="comments-${p.id}">
                            <div id="comment-list-${p.id}"></div>
                            <div class="comment-input-row">
                                <input type="text" id="comment-input-${p.id}" placeholder="Write a comment...">
                                <button onclick="submitComment(${p.id})">Send</button>
                            </div>
                        </div>
                    </div>`;
                        loadReactions(p.id);
                    });
                });
        }

        // ================================
        // REACTIONS (Like/Dislike)
        // ================================
        function reactPost(postId, type) {
            fetch('../php/posts/react.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    post_id: postId,
                    reaction: type
                })
            }).then(res => res.json()).then(() => loadReactions(postId));
        }

        function loadReactions(postId) {
            fetch(`../php/posts/get-reactions.php?post_id=${postId}`)
                .then(res => res.json())
                .then(data => {
                    let likeEl = document.getElementById(`like-${postId}`);
                    let dislikeEl = document.getElementById(`dislike-${postId}`);
                    if (likeEl) likeEl.textContent = data.likes || 0;
                    if (dislikeEl) dislikeEl.textContent = data.dislikes || 0;
                });
        }

        // ================================
        // COMMENTS
        // ================================
        function toggleComments(postId) {
            let el = document.getElementById(`comments-${postId}`);
            el.classList.toggle('show');
            if (el.classList.contains('show')) loadComments(postId);
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
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    post_id: postId,
                    comment: text
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    input.value = '';
                    loadComments(postId);
                }
            });
        }

        // ================================
        // RATING
        // ================================
        function ratePost(postId, rating) {
            let stars = document.querySelectorAll(`#stars-${postId} i`);
            stars.forEach((s, i) => {
                s.classList.toggle('active', i < rating);
            });

            fetch('../php/posts/rate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    post_id: postId,
                    rating: rating
                })
            });
        }

        // ================================
        // CREATE POST
        // ================================
        function toggleMedia() {
            let type = document.getElementById('mediaType').value;
            document.getElementById('mediaUrl').style.display = type === 'none' ? 'none' : 'block';
        }

        function createPost() {
            let title = document.getElementById('postTitle').value.trim();
            let content = document.getElementById('postContent').value.trim();
            let mediaType = document.getElementById('mediaType').value;
            let mediaUrl = document.getElementById('mediaUrl').value.trim();

            if (!title || !content) {
                alert('Please fill title and content!');
                return;
            }

            fetch('../php/posts/create-post.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    title,
                    content,
                    media_type: mediaType,
                    media_url: mediaUrl || null
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    document.getElementById('postTitle').value = '';
                    document.getElementById('postContent').value = '';
                    loadPosts();
                } else {
                    alert('Error: ' + data.message);
                }
            });
        }

        // ================================
        // LOAD RECENT ON OVERVIEW
        // ================================
        function loadRecentAppointments() {
            fetch('../php/patient/get-appointments.php')
                .then(res => res.json())
                .then(data => {
                    let el = document.getElementById('recent-appointments');
                    if (data.length === 0) return;
                    let html = '<table class="data-table"><thead><tr><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>';
                    data.slice(0, 3).forEach(a => {
                        html += `<tr><td>Dr. ${a.doctor_name}</td><td>${a.appointment_date}</td><td>${a.appointment_time}</td><td><span class="badge ${a.status}">${a.status.charAt(0).toUpperCase()+a.status.slice(1)}</span></td></tr>`;
                    });
                    html += '</tbody></table>';
                    el.innerHTML = html;
                });
        }
        loadRecentAppointments();

        // ================================
        // GSAP
        // ================================
        gsap.from('.stat-box', {
            duration: 0.5,
            y: 25,
            opacity: 0,
            stagger: 0.1,
            ease: "power3.out"
        });
        gsap.from('.content-card', {
            duration: 0.5,
            y: 15,
            opacity: 0,
            stagger: 0.1,
            delay: 0.3,
            ease: "power3.out"
        });
    </script>

</body>

</html>