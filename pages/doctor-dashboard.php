<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.html");
    exit();
}
include '../php/config/database.php';

$user_id = $_SESSION['user_id'];

// Doctor info
$doc_result = mysqli_query(
    $conn,
    "SELECT d.*, u.email, u.phone, s.name as specialty_name
     FROM doctors d
     JOIN users u ON d.user_id = u.id
     JOIN specialties s ON d.specialty_id = s.id
     WHERE d.user_id = $user_id"
);
$doctor = mysqli_fetch_assoc($doc_result);

if (!$doctor) {
    $doctor = [
        'id' => 0,
        'full_name' => $_SESSION['username'],
        'specialty_name' => 'Not Set',
        'doctor_code' => 'N/A',
        'email' => '',
        'phone' => '',
        'image' => null,
        'qualification' => '',
        'experience_years' => 0,
        'consultation_fee' => 0,
        'bio' => '',
        'gender' => ''
    ];
}

$doctor_id = $doctor['id'];

// Stats
$stats = ['total' => 0, 'pending' => 0, 'completed' => 0, 'approved' => 0, 'cancelled' => 0];
$avg_rating = 0;
$total_ratings = 0;

if ($doctor_id > 0) {
    $st = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) as total,
                SUM(status='pending') as pending,
                SUM(status='completed') as completed,
                SUM(status='approved') as approved,
                SUM(status='cancelled') as cancelled
         FROM appointments WHERE doctor_id = $doctor_id"
    ));
    if ($st) $stats = $st;

    $rt = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT AVG(rating) as avg_r, COUNT(*) as total_r
         FROM ratings WHERE doctor_id = $doctor_id"
    ));
    $avg_rating = round($rt['avg_r'] ?? 0, 1);
    $total_ratings = $rt['total_r'] ?? 0;
}

// Today's appointments
$today = date('Y-m-d');
$today_count = 0;
if ($doctor_id > 0) {
    $tc = mysqli_fetch_assoc(mysqli_query(
        $conn,
        "SELECT COUNT(*) as c FROM appointments
         WHERE doctor_id = $doctor_id AND appointment_date = '$today'
         AND status IN ('pending','approved')"
    ));
    $today_count = $tc['c'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - DocBook</title>
    <link rel="stylesheet" href="../assets/css/fix.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            background: linear-gradient(180deg, #0a1628, #0d2847);
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
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

        .sidebar-profile .specialty-tag {
            display: inline-block;
            background: rgba(26, 115, 232, 0.2);
            color: #60a5fa;
            padding: 3px 12px;
            border-radius: 15px;
            font-size: 11px;
            margin-top: 5px;
        }

        .sidebar-profile .rating-line {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            margin-top: 8px;
        }

        .sidebar-profile .rating-line i {
            font-size: 12px;
            color: #fbbf24;
        }

        .sidebar-profile .rating-line span {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
        }

        .sidebar-section {
            font-size: 10px;
            letter-spacing: 1px;
            color: rgba(255, 255, 255, 0.25);
            margin: 18px 0 8px 12px;
            text-transform: uppercase;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            color: rgba(255, 255, 255, 0.65);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 3px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(52, 211, 153, 0.12);
            color: #34d399;
        }

        .sidebar-menu a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .sidebar-menu a.logout-btn {
            background: rgba(239, 68, 68, 0.12);
            color: #ef4444;
            margin-top: 15px;
        }

        .sidebar-menu a.logout-btn:hover {
            background: #ef4444;
            color: white;
        }

        /* ========== MAIN ========== */
        .main {
            margin-left: 260px;
            padding: 25px 30px;
        }

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

        .today-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .today-badge .count {
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 10px;
            border-radius: 15px;
            font-size: 14px;
        }

        /* Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: white;
            border-radius: 14px;
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            transition: all 0.3s;
        }

        .stat-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .stat-info h3 {
            font-size: 22px;
            font-weight: 700;
            color: #0a1628;
            line-height: 1;
        }

        .stat-info p {
            font-size: 11px;
            color: #6c757d;
            margin-top: 2px;
        }

        /* Tab System */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Content Card */
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
            font-size: 15px;
            font-weight: 600;
            color: #0a1628;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Table */
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
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f4f8;
            vertical-align: middle;
        }

        .data-table tr:hover td {
            background: #fafbfc;
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

        /* Action Buttons */
        .act-btn {
            padding: 5px 12px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            margin: 1px;
        }

        .act-approve {
            background: #d4edda;
            color: #155724;
        }

        .act-approve:hover {
            background: #198754;
            color: white;
        }

        .act-complete {
            background: #cce5ff;
            color: #004085;
        }

        .act-complete:hover {
            background: #1a73e8;
            color: white;
        }

        .act-cancel {
            background: #f8d7da;
            color: #721c24;
        }

        .act-cancel:hover {
            background: #dc3545;
            color: white;
        }

        /* Schedule Grid */
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }

        .schedule-card {
            background: #f8fafb;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            border: 1px solid #e8ecf0;
            transition: all 0.3s;
        }

        .schedule-card:hover {
            border-color: #1a73e8;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }

        .schedule-card h4 {
            font-size: 13px;
            font-weight: 600;
            color: #1a73e8;
        }

        .schedule-card p {
            font-size: 12px;
            color: #6c757d;
            margin-top: 3px;
        }

        .schedule-card small {
            font-size: 11px;
            color: #198754;
        }

        /* Add Schedule Form */
        .schedule-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr auto;
            gap: 10px;
            align-items: end;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e8ecf0;
        }

        .schedule-form select,
        .schedule-form input {
            padding: 8px 12px;
            border: 1.5px solid #e0e5ec;
            border-radius: 8px;
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
            outline: none;
        }

        .schedule-form select:focus,
        .schedule-form input:focus {
            border-color: #1a73e8;
        }

        /* Profile Form */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .profile-grid .full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e0e5ec;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            outline: none;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
        }

        .btn-save {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            color: white;
            border: none;
            padding: 11px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            margin-top: 10px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 115, 232, 0.3);
        }

        .btn-add-schedule {
            background: #198754;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            white-space: nowrap;
        }

        /* Profile Info Cards */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .info-box {
            background: #f8fafb;
            border-radius: 10px;
            padding: 14px;
            border: 1px solid #e8ecf0;
        }

        .info-box p {
            font-size: 11px;
            color: #6c757d;
        }

        .info-box h4 {
            font-size: 14px;
            font-weight: 600;
            color: #0a1628;
            margin-top: 2px;
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
        }

        .empty-state p {
            font-size: 13px;
        }

        /* Setup Warning */
        .setup-alert {
            background: linear-gradient(135deg, #fff3cd, #ffeeba);
            border: 1px solid #ffc107;
            border-radius: 12px;
            padding: 18px 22px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .setup-alert i {
            font-size: 22px;
            color: #856404;
        }

        .setup-alert div h4 {
            font-size: 14px;
            color: #856404;
            font-weight: 600;
        }

        .setup-alert div p {
            font-size: 12px;
            color: #856404;
        }

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
    </style>
</head>

<body>

    <div class="dashboard">

        <!-- ========== SIDEBAR ========== -->
        <aside class="sidebar">
            <div class="sidebar-profile">
                <img src="<?= $doctor['image']
                                ? '../uploads/' . $doctor['image']
                                : 'https://ui-avatars.com/api/?name=' . urlencode($doctor['full_name']) . '&background=1a73e8&color=fff' ?>" alt="Doctor">
                <h4>Dr. <?= htmlspecialchars($doctor['full_name']) ?></h4>
                <div class="specialty-tag"><?= htmlspecialchars($doctor['specialty_name']) ?></div>
                <div class="rating-line">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star-fill" style="color:<?= $i <= round($avg_rating) ? '#fbbf24' : '#444' ?>"></i>
                    <?php endfor; ?>
                    <span>(<?= $total_ratings ?>)</span>
                </div>
            </div>

            <div class="sidebar-section">Main</div>
            <nav class="sidebar-menu">
                <a onclick="switchTab('overview')" class="active" id="nav-overview">
                    <i class="bi bi-grid"></i> Overview
                </a>

                <div class="sidebar-section">Management</div>
                <a onclick="switchTab('appointments')" id="nav-appointments">
                    <i class="bi bi-calendar-check"></i> Appointments
                </a>
                <a onclick="switchTab('schedule')" id="nav-schedule">
                    <i class="bi bi-clock"></i> My Schedule
                </a>
                <a onclick="switchTab('patients')" id="nav-patients">
                    <i class="bi bi-people"></i> My Patients
                </a>

                <div class="sidebar-section">Account</div>
                <a onclick="switchTab('profile')" id="nav-profile">
                    <i class="bi bi-person"></i> Edit Profile
                </a>
                <a href="../php/auth/logout.php" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- ========== MAIN ========== -->
        <div class="main">

            <!-- Topbar -->
            <div class="topbar dash-topbar">
                <div>
                    <h2>🩺 Doctor Dashboard</h2>
                    <p><?= date('l, d F Y') ?></p>
                </div>
                <div class="today-badge">
                    <i class="bi bi-calendar2-check"></i>
                    Today's Appointments:
                    <span class="count"><?= $today_count ?></span>
                </div>
            </div>

            <?php if ($doctor_id == 0): ?>
                <div class="setup-alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div>
                        <h4>Complete Your Profile</h4>
                        <p>Go to "Edit Profile" tab to set up your doctor profile</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-icon" style="background:#e8f0fe; color:#1a73e8;">
                        <i class="bi bi-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['total'] ?></h3>
                        <p>Total</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="background:#fff3cd; color:#ffc107;">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['pending'] ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="background:#d4edda; color:#198754;">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['approved'] ?></h3>
                        <p>Approved</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="background:#cce5ff; color:#004085;">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $stats['completed'] ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-icon" style="background:#ffeaa7; color:#fdcb6e;">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?= $avg_rating ?></h3>
                        <p>Rating</p>
                    </div>
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: OVERVIEW -->
            <!-- ======================== -->
            <div class="tab-content active" id="content-overview">
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="bi bi-clock-history" style="color:#1a73e8;"></i> Recent Appointments</h3>
                        <a onclick="switchTab('appointments')" style="color:#1a73e8; font-size:13px; cursor:pointer;">View All →</a>
                    </div>
                    <div id="recent-appts-overview">
                        <!-- Load via AJAX -->
                    </div>
                </div>

                <!-- Quick Actions -->
                <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px;">
                    <div class="content-card" style="cursor:pointer; text-align:center;" onclick="switchTab('appointments')">
                        <i class="bi bi-calendar-check" style="font-size:28px; color:#1a73e8;"></i>
                        <h4 style="font-size:13px; margin-top:8px;">All Appointments</h4>
                        <p style="font-size:11px; color:#6c757d;">Manage requests</p>
                    </div>
                    <div class="content-card" style="cursor:pointer; text-align:center;" onclick="switchTab('schedule')">
                        <i class="bi bi-clock" style="font-size:28px; color:#198754;"></i>
                        <h4 style="font-size:13px; margin-top:8px;">My Schedule</h4>
                        <p style="font-size:11px; color:#6c757d;">Set availability</p>
                    </div>
                    <div class="content-card" style="cursor:pointer; text-align:center;" onclick="switchTab('patients')">
                        <i class="bi bi-people" style="font-size:28px; color:#e535ab;"></i>
                        <h4 style="font-size:13px; margin-top:8px;">My Patients</h4>
                        <p style="font-size:11px; color:#6c757d;">Patient history</p>
                    </div>
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: APPOINTMENTS -->
            <!-- ======================== -->
            <div class="tab-content" id="content-appointments">
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="bi bi-calendar-check" style="color:#1a73e8;"></i> All Appointments</h3>
                        <div style="display:flex; gap:8px;">
                            <select id="filterStatus" onchange="loadAppointments()" style="padding:6px 12px; border-radius:8px; border:1.5px solid #ddd; font-size:12px; font-family:Poppins;">
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div id="appointments-table">
                        <!-- Load via AJAX -->
                    </div>
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: SCHEDULE -->
            <!-- ======================== -->
            <div class="tab-content" id="content-schedule">
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="bi bi-clock" style="color:#198754;"></i> Weekly Schedule</h3>
                    </div>
                    <div class="schedule-grid" id="schedule-grid">
                        <!-- Load via AJAX -->
                    </div>

                    <!-- Add Schedule -->
                    <div class="schedule-form" id="addScheduleForm">
                        <div>
                            <label style="font-size:11px; font-weight:600; display:block; margin-bottom:4px;">Day</label>
                            <select id="scheduleDay">
                                <option>Saturday</option>
                                <option>Sunday</option>
                                <option>Monday</option>
                                <option>Tuesday</option>
                                <option>Wednesday</option>
                                <option>Thursday</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; display:block; margin-bottom:4px;">Start Time</label>
                            <input type="time" id="scheduleStart" value="09:00">
                        </div>
                        <div>
                            <label style="font-size:11px; font-weight:600; display:block; margin-bottom:4px;">End Time</label>
                            <input type="time" id="scheduleEnd" value="17:00">
                        </div>
                        <button class="btn-add-schedule" onclick="addSchedule()">
                            <i class="bi bi-plus"></i> Add
                        </button>
                    </div>
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: MY PATIENTS -->
            <!-- ======================== -->
            <div class="tab-content" id="content-patients">
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="bi bi-people" style="color:#e535ab;"></i> My Patients</h3>
                    </div>
                    <div id="patients-list">
                        <!-- Load via AJAX -->
                    </div>
                </div>
            </div>

            <!-- ======================== -->
            <!-- TAB: PROFILE -->
            <!-- ======================== -->
            <div class="tab-content" id="content-profile">
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="bi bi-person" style="color:#1a73e8;"></i> Edit Profile</h3>
                    </div>

                    <!-- Current Info -->
                    <div class="info-grid">
                        <div class="info-box">
                            <p>Doctor Code</p>
                            <h4><?= $doctor['doctor_code'] ?></h4>
                        </div>
                        <div class="info-box">
                            <p>Email</p>
                            <h4><?= htmlspecialchars($doctor['email'] ?? 'N/A') ?></h4>
                        </div>
                        <div class="info-box">
                            <p>Phone</p>
                            <h4><?= htmlspecialchars($doctor['phone'] ?? 'N/A') ?></h4>
                        </div>
                    </div>

                    <form action="../php/doctor/update-profile.php"
                        method="POST"
                        enctype="multipart/form-data">

                        <div class="profile-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name"
                                    value="<?= htmlspecialchars($doctor['full_name']) ?>">
                            </div>
                            <div class="form-group">
                                <label>Qualification</label>
                                <input type="text" name="qualification"
                                    value="<?= htmlspecialchars($doctor['qualification'] ?? '') ?>"
                                    placeholder="e.g. MBBS, FCPS">
                            </div>
                            <div class="form-group">
                                <label>Experience (Years)</label>
                                <input type="number" name="experience_years"
                                    value="<?= $doctor['experience_years'] ?? 0 ?>" min="0">
                            </div>
                            <div class="form-group">
                                <label>Consultation Fee (৳)</label>
                                <input type="number" name="consultation_fee"
                                    value="<?= $doctor['consultation_fee'] ?? 0 ?>" min="0">
                            </div>
                            <div class="form-group full-width">
                                <label>Bio</label>
                                <textarea name="bio" rows="3" placeholder="Write about yourself..."><?= htmlspecialchars(trim($doctor['bio'] ?? '')) ?></textarea>
                            </div>
                            <div class="form-group full-width">
                                <label>Profile Image</label>
                                <input type="file" name="image" accept="image/*">
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </form>
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

            if (tab === 'appointments') loadAppointments();
            if (tab === 'schedule') loadSchedule();
            if (tab === 'patients') loadPatients();
        }

        // ================================
        // LOAD APPOINTMENTS
        // ================================
        function loadAppointments() {
            let status = document.getElementById('filterStatus').value;
            fetch(`../php/doctor/get-appointments.php?status=${status}`)
                .then(res => res.json())
                .then(data => {
                    let el = document.getElementById('appointments-table');
                    if (data.length === 0) {
                        el.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><h4>No Appointments</h4></div>';
                        return;
                    }
                    let html = '<table class="data-table"><thead><tr><th>#</th><th>Patient</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
                    data.forEach((a, i) => {
                        html += `<tr id="row-${a.id}">
                    <td>${i+1}</td>
                    <td><strong>${a.patient_name}</strong><br><small style="color:#6c757d;">${a.gender || ''}</small></td>
                    <td>${a.appointment_date}</td>
                    <td>${a.appointment_time}</td>
                    <td style="max-width:150px;">${a.reason || '-'}</td>
                    <td><span class="badge ${a.status}" id="badge-${a.id}">${a.status.charAt(0).toUpperCase()+a.status.slice(1)}</span></td>
                    <td>
                        ${a.status === 'pending' ? `<button class="act-btn act-approve" onclick="updateStatus(${a.id},'approved')">Approve</button>` : ''}
                        ${a.status === 'approved' ? `<button class="act-btn act-complete" onclick="updateStatus(${a.id},'completed')">Complete</button>` : ''}
                        ${['pending','approved'].includes(a.status) ? `<button class="act-btn act-cancel" onclick="updateStatus(${a.id},'cancelled')">Cancel</button>` : ''}
                        ${a.status === 'completed' ? '<span style="color:#198754;font-size:11px;">✓ Done</span>' : ''}
                    </td>
                </tr>`;
                    });
                    html += '</tbody></table>';
                    el.innerHTML = html;
                })
                .catch(() => {
                    document.getElementById('appointments-table').innerHTML = '<p style="text-align:center;color:#6c757d;padding:20px;">Could not load</p>';
                });
        }

        // ================================
        // UPDATE STATUS
        // ================================
        function updateStatus(id, status) {
            if (!confirm(`Mark as ${status}?`)) return;
            fetch('../php/appointments/update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    appt_id: id,
                    status: status
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    loadAppointments();
                    gsap.from(`#appointments-table`, {
                        duration: 0.3,
                        opacity: 0
                    });
                }
            });
        }

        // ================================
        // LOAD SCHEDULE
        // ================================
        function loadSchedule() {
            fetch('../php/doctor/get-schedule.php')
                .then(res => res.json())
                .then(data => {
                    let grid = document.getElementById('schedule-grid');
                    if (data.length === 0) {
                        grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><i class="bi bi-clock"></i><h4>No Schedule Set</h4><p>Add your availability below</p></div>';
                        return;
                    }
                    grid.innerHTML = '';
                    data.forEach(s => {
                        grid.innerHTML += `
                    <div class="schedule-card">
                        <h4>${s.day_of_week}</h4>
                        <p>${formatTime(s.start_time)} - ${formatTime(s.end_time)}</p>
                        <small>Max: ${s.max_patients} patients</small>
                    </div>`;
                    });
                });
        }

        function formatTime(t) {
            let d = new Date('2000-01-01T' + t);
            return d.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        }

        // ================================
        // ADD SCHEDULE
        // ================================
        function addSchedule() {
            let day = document.getElementById('scheduleDay').value;
            let start = document.getElementById('scheduleStart').value;
            let end = document.getElementById('scheduleEnd').value;

            fetch('../php/doctor/add-schedule.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    day_of_week: day,
                    start_time: start,
                    end_time: end,
                    max_patients: 10
                })
            }).then(res => res.json()).then(data => {
                if (data.success) {
                    loadSchedule();
                } else {
                    alert('Failed to add schedule');
                }
            });
        }

        // ================================
        // LOAD PATIENTS
        // ================================
        function loadPatients() {
            fetch('../php/doctor/get-patients.php')
                .then(res => res.json())
                .then(data => {
                    let el = document.getElementById('patients-list');
                    if (data.length === 0) {
                        el.innerHTML = '<div class="empty-state"><i class="bi bi-people"></i><h4>No Patients Yet</h4></div>';
                        return;
                    }
                    let html = '<table class="data-table"><thead><tr><th>#</th><th>Name</th><th>Gender</th><th>Phone</th><th>Total Visits</th><th>Last Visit</th></tr></thead><tbody>';
                    data.forEach((p, i) => {
                        html += `<tr>
                    <td>${i+1}</td>
                    <td><strong>${p.full_name}</strong></td>
                    <td>${p.gender || '-'}</td>
                    <td>${p.phone || '-'}</td>
                    <td>${p.total_visits}</td>
                    <td>${p.last_visit || '-'}</td>
                </tr>`;
                    });
                    html += '</tbody></table>';
                    el.innerHTML = html;
                });
        }

        // ================================
        // LOAD RECENT ON OVERVIEW
        // ================================
        function loadRecentOverview() {
            fetch('../php/doctor/get-appointments.php?status=all&limit=5')
                .then(res => res.json())
                .then(data => {
                    let el = document.getElementById('recent-appts-overview');
                    if (data.length === 0) {
                        el.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><h4>No Appointments Yet</h4></div>';
                        return;
                    }
                    let html = '<table class="data-table"><thead><tr><th>Patient</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>';
                    data.slice(0, 5).forEach(a => {
                        html += `<tr><td>${a.patient_name}</td><td>${a.appointment_date}</td><td>${a.appointment_time}</td><td><span class="badge ${a.status}">${a.status.charAt(0).toUpperCase()+a.status.slice(1)}</span></td></tr>`;
                    });
                    html += '</tbody></table>';
                    el.innerHTML = html;
                });
        }
        loadRecentOverview();

        // ================================
        // GSAP
        // ================================
        gsap.from('.stat-box', {
            duration: 0.5,
            y: 25,
            opacity: 0,
            stagger: 0.08,
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