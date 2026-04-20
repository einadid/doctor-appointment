
<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}
include '../php/config/database.php';

$user_id = $_SESSION['user_id'];

// Admin info
$admin = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT a.*, u.email, u.username 
     FROM admins a JOIN users u ON a.user_id = u.id 
     WHERE a.user_id = $user_id"));

if (!$admin) {
    $admin = ['full_name' => $_SESSION['username'], 'email' => '', 'username' => $_SESSION['username']];
}

// Stats
$total_patients = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM patients"))['t'];
$total_doctors = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM doctors"))['t'];
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM appointments"))['t'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM users"))['t'];
$total_posts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as t FROM posts"))['t'];

// Appointment stats
$appt_stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(status='pending') as pending,
            SUM(status='approved') as approved,
            SUM(status='completed') as completed,
            SUM(status='cancelled') as cancelled
     FROM appointments"));

// Today
$today = date('Y-m-d');
$today_appts = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as c FROM appointments WHERE appointment_date = '$today'"))['c'];

// Revenue (total fees from completed)
$revenue = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT SUM(d.consultation_fee) as total
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.id
     WHERE a.status = 'completed'"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - DocBook</title>
    <link rel="stylesheet" href="../assets/css/fix.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; }

        .admin-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        /* ========== SIDEBAR ========== */
        .sidebar {
            background: linear-gradient(180deg, #0f0f1a, #1a1a35);
            color: white;
            padding: 25px 18px;
            position: fixed;
            top: 0; left: 0;
            width: 260px;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 20px;
        }

        .sidebar-brand .brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .sidebar-brand h2 {
            font-size: 18px; font-weight: 700;
        }

        .sidebar-brand h2 span { color: #8b5cf6; }
        .sidebar-brand small {
            display: block;
            font-size: 10px;
            color: rgba(255,255,255,0.4);
            font-weight: 400;
        }

        .sidebar-section {
            font-size: 10px;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,0.2);
            margin: 20px 0 8px 12px;
            text-transform: uppercase;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            color: rgba(255,255,255,0.55);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 2px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(139,92,246,0.12);
            color: #a78bfa;
        }

        .sidebar-menu a i {
            font-size: 16px; width: 20px; text-align: center;
        }

        .sidebar-menu a .menu-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: 700;
        }

        .sidebar-menu a.logout-btn {
            background: rgba(239,68,68,0.1);
            color: #ef4444;
            margin-top: 15px;
        }

        .sidebar-menu a.logout-btn:hover {
            background: #ef4444; color: white;
        }

        .sidebar-admin-info {
            padding: 15px;
            background: rgba(255,255,255,0.03);
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar-admin-info p {
            font-size: 11px; color: rgba(255,255,255,0.4);
        }

        .sidebar-admin-info h4 {
            font-size: 13px; color: white; font-weight: 600;
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
            background: white;
            padding: 16px 22px;
            border-radius: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        }

        .topbar h2 { font-size: 18px; font-weight: 700; color: #0f0f1a; }
        .topbar p { font-size: 12px; color: #6c757d; }

        .topbar-right {
            display: flex; align-items: center; gap: 10px;
        }

        .admin-tag {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 14px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0;
            width: 60px; height: 60px;
            border-radius: 0 14px 0 50%;
            opacity: 0.08;
        }

        .stat-card:nth-child(1)::after { background: #6366f1; }
        .stat-card:nth-child(2)::after { background: #1a73e8; }
        .stat-card:nth-child(3)::after { background: #198754; }
        .stat-card:nth-child(4)::after { background: #ffc107; }
        .stat-card:nth-child(5)::after { background: #e535ab; }
        .stat-card:nth-child(6)::after { background: #ef4444; }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        .stat-card .s-icon {
            width: 42px; height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .stat-card h3 {
            font-size: 24px; font-weight: 700; color: #0f0f1a; line-height: 1;
        }

        .stat-card p {
            font-size: 11px; color: #6c757d; margin-top: 3px;
        }

        /* Tab System */
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Content Card */
        .content-card {
            background: white;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.04);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }

        .card-header h3 {
            font-size: 15px; font-weight: 600; color: #0f0f1a;
            display: flex; align-items: center; gap: 8px;
        }

        .card-header-actions {
            display: flex; gap: 8px; align-items: center;
        }

        /* Table */
        .data-table {
            width: 100%; border-collapse: collapse; font-size: 13px;
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

        .data-table tr:hover td { background: #fafbfc; }

        .badge {
            padding: 4px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }

        .badge.pending { background: #fff3cd; color: #856404; }
        .badge.approved { background: #d4edda; color: #155724; }
        .badge.completed { background: #cce5ff; color: #004085; }
        .badge.cancelled { background: #f8d7da; color: #721c24; }
        .badge.active { background: #d4edda; color: #155724; }
        .badge.inactive { background: #f8d7da; color: #721c24; }

        .act-btn {
            padding: 5px 11px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            margin: 1px;
        }

        .act-danger { background: #fee2e2; color: #991b1b; }
        .act-danger:hover { background: #ef4444; color: white; }

        .act-toggle { background: #e0e7ff; color: #3730a3; }
        .act-toggle:hover { background: #6366f1; color: white; }

        .act-view { background: #dbeafe; color: #1e40af; }
        .act-view:hover { background: #1a73e8; color: white; }

        /* Search */
        .search-box {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafb;
            border: 1.5px solid #e0e5ec;
            border-radius: 8px;
            padding: 6px 12px;
        }

        .search-box i { color: #9ca3af; font-size: 14px; }

        .search-box input {
            border: none; background: transparent;
            outline: none; font-size: 12px;
            font-family: 'Poppins', sans-serif;
            width: 180px;
        }

        /* Print */
        .btn-print {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #198754;
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
        }

        .btn-print:hover { background: #0d6e3f; }

        /* Form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-grid .full-width { grid-column: 1 / -1; }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
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
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        .btn-submit {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
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

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99,102,241,0.3);
        }

        /* Report Cards */
        .report-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .report-box {
            background: #f8fafb;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e8ecf0;
        }

        .report-box h4 {
            font-size: 14px; font-weight: 600; margin-bottom: 12px;
            display: flex; align-items: center; gap: 8px;
        }

        .report-table {
            width: 100%; font-size: 13px;
        }

        .report-table td {
            padding: 8px 0;
            border-bottom: 1px solid #e8ecf0;
        }

        .report-table td:last-child {
            text-align: right; font-weight: 600;
        }

        /* Quick Actions */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 25px;
        }

        .quick-card {
            background: white;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #e8ecf0;
        }

        .quick-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border-color: #6366f1;
        }

        .quick-card i {
            font-size: 28px; margin-bottom: 8px;
        }

        .quick-card h4 { font-size: 13px; font-weight: 600; }
        .quick-card p { font-size: 11px; color: #6c757d; }

        /* Pagination */
        .pagination {
            display: flex; gap: 6px; justify-content: center; margin-top: 15px;
        }

        .pagination a {
            padding: 6px 12px;
            border-radius: 8px;
            background: #f0f4f8;
            color: #6366f1;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .pagination a.active, .pagination a:hover {
            background: #6366f1; color: white;
        }

        /* Empty */
        .empty-state {
            text-align: center; padding: 40px; color: #6c757d;
        }

        .empty-state i { font-size: 48px; color: #ddd; margin-bottom: 12px; }
        .empty-state h4 { font-size: 16px; font-weight: 600; color: #333; }

        /* Print Styles */
        @media print {
            .sidebar, .topbar, .card-header-actions,
            .act-btn, .btn-print, .pagination,
            .sidebar-menu, .quick-grid { display: none !important; }
            .admin-layout { grid-template-columns: 1fr !important; }
            .main { margin-left: 0 !important; padding: 10px !important; }
            .tab-content { display: block !important; }
            .content-card { box-shadow: none !important; border: 1px solid #ddd; }
        }
        .admin-layout { display: flex !important; }
.sidebar { width: 230px !important; }
.main { margin-left: 230px !important; width: calc(100% - 230px) !important; }

@media (max-width: 768px) {
    .admin-layout { display: block !important; }
    .sidebar { display: none !important; }
    .main { margin-left: 0 !important; width: 100% !important; }
}
    </style>
</head>
<body>

<div class="admin-layout">

    <!-- ========== SIDEBAR ========== -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="bi bi-shield-check"></i></div>
            <div>
                <h2>Doc<span>Book</span></h2>
                <small>Admin Control Panel</small>
            </div>
        </div>

        <div class="sidebar-section">Dashboard</div>
        <nav class="sidebar-menu">
            <a onclick="switchTab('overview')" class="active" id="nav-overview">
                <i class="bi bi-grid-1x2"></i> Overview
            </a>

            <div class="sidebar-section">Management</div>
            <a onclick="switchTab('appointments')" id="nav-appointments">
                <i class="bi bi-calendar-check"></i> Appointments
                <?php if(($appt_stats['pending'] ?? 0) > 0): ?>
                    <span class="menu-badge"><?= $appt_stats['pending'] ?></span>
                <?php endif; ?>
            </a>
            <a onclick="switchTab('doctors')" id="nav-doctors">
                <i class="bi bi-person-badge"></i> Doctors
            </a>
            <a onclick="switchTab('patients')" id="nav-patients">
                <i class="bi bi-people"></i> Patients
            </a>
            <a onclick="switchTab('add-doctor')" id="nav-add-doctor">
                <i class="bi bi-person-plus"></i> Add Doctor
            </a>

            <div class="sidebar-section">Analytics</div>
            <a onclick="switchTab('reports')" id="nav-reports">
                <i class="bi bi-bar-chart-line"></i> Reports
            </a>

            <a href="../php/auth/logout.php" class="logout-btn">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>

        <div class="sidebar-admin-info">
            <p>Logged in as</p>
            <h4><i class="bi bi-person-circle"></i> <?= htmlspecialchars($admin['full_name']) ?></h4>
        </div>
    </aside>

    <!-- ========== MAIN ========== -->
    <div class="main">

        <!-- Topbar -->
        <div class="topbar dash-topbar">
            <div>
                <h2>🛡️ Admin Dashboard</h2>
                <p><?= date('l, d F Y') ?> | Today's Appointments: <?= $today_appts ?></p>
            </div>
            <div class="topbar-right">
                <span class="admin-tag">
                    <i class="bi bi-shield-check"></i> Administrator
                </span>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="s-icon" style="background:#ede9fe; color:#6366f1;">
                    <i class="bi bi-people"></i>
                </div>
                <h3><?= $total_users ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <div class="s-icon" style="background:#dbeafe; color:#1a73e8;">
                    <i class="bi bi-person-badge"></i>
                </div>
                <h3><?= $total_doctors ?></h3>
                <p>Doctors</p>
            </div>
            <div class="stat-card">
                <div class="s-icon" style="background:#d1fae5; color:#198754;">
                    <i class="bi bi-person-heart"></i>
                </div>
                <h3><?= $total_patients ?></h3>
                <p>Patients</p>
            </div>
            <div class="stat-card">
                <div class="s-icon" style="background:#fef3c7; color:#f59e0b;">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <h3><?= $total_appointments ?></h3>
                <p>Appointments</p>
            </div>
            <div class="stat-card">
                <div class="s-icon" style="background:#fce7f3; color:#e535ab;">
                    <i class="bi bi-newspaper"></i>
                </div>
                <h3><?= $total_posts ?></h3>
                <p>Posts</p>
            </div>
            <div class="stat-card">
                <div class="s-icon" style="background:#fee2e2; color:#ef4444;">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <h3>৳<?= number_format($revenue) ?></h3>
                <p>Revenue</p>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB: OVERVIEW -->
        <!-- ======================== -->
        <div class="tab-content active" id="content-overview">
            <div class="quick-grid">
                <div class="quick-card" onclick="switchTab('appointments')">
                    <i class="bi bi-calendar-check" style="color:#6366f1;"></i>
                    <h4>Appointments</h4>
                    <p>Manage all bookings</p>
                </div>
                <div class="quick-card" onclick="switchTab('doctors')">
                    <i class="bi bi-person-badge" style="color:#1a73e8;"></i>
                    <h4>Doctors</h4>
                    <p>View & manage doctors</p>
                </div>
                <div class="quick-card" onclick="switchTab('patients')">
                    <i class="bi bi-people" style="color:#198754;"></i>
                    <h4>Patients</h4>
                    <p>View all patients</p>
                </div>
                <div class="quick-card" onclick="switchTab('reports')">
                    <i class="bi bi-bar-chart-line" style="color:#e535ab;"></i>
                    <h4>Reports</h4>
                    <p>View system reports</p>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-clock-history" style="color:#6366f1;"></i> Recent Appointments</h3>
                </div>
                <div id="overview-recent"></div>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB: APPOINTMENTS -->
        <!-- ======================== -->
        <div class="tab-content" id="content-appointments">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-calendar-check" style="color:#6366f1;"></i> All Appointments</h3>
                    <div class="card-header-actions">
                        <select id="apptFilter" onchange="loadAdminAppointments()" style="padding:6px 12px; border-radius:8px; border:1.5px solid #ddd; font-size:12px; font-family:Poppins;">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button class="btn-print" onclick="printSection('appt-print')">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div id="appt-print">
                    <div id="admin-appts-table"></div>
                </div>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB: DOCTORS -->
        <!-- ======================== -->
        <div class="tab-content" id="content-doctors">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-person-badge" style="color:#1a73e8;"></i> All Doctors</h3>
                    <div class="card-header-actions">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" id="docSearch" placeholder="Search doctor..." onkeyup="searchTable('doc-table', this.value)">
                        </div>
                        <button class="btn-print" onclick="printSection('doc-print')">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div id="doc-print">
                    <div id="admin-docs-table"></div>
                </div>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB: PATIENTS -->
        <!-- ======================== -->
        <div class="tab-content" id="content-patients">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-people" style="color:#198754;"></i> All Patients</h3>
                    <div class="card-header-actions">
                        <div class="search-box">
                            <i class="bi bi-search"></i>
                            <input type="text" placeholder="Search patient..." onkeyup="searchTable('pat-table', this.value)">
                        </div>
                        <button class="btn-print" onclick="printSection('pat-print')">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
                <div id="pat-print">
                    <div id="admin-pats-table"></div>
                </div>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB: ADD DOCTOR -->
        <!-- ======================== -->
        <div class="tab-content" id="content-add-doctor">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-person-plus" style="color:#6366f1;"></i> Add New Doctor</h3>
                </div>

                <form action="../php/admin/add-doctor.php" method="POST" id="addDoctorForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" placeholder="Doctor's full name" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" id="adminUsername" placeholder="Username" required>
                            <small id="admin-user-check" style="font-size:11px;"></small>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" placeholder="01XXXXXXXXX" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Password" required>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Specialty</label>
                            <select name="specialty_id" id="adminSpecialty" required></select>
                        </div>
                        <div class="form-group">
                            <label>Qualification</label>
                            <input type="text" name="qualification" placeholder="e.g. MBBS, FCPS">
                        </div>
                        <div class="form-group">
                            <label>Experience (Years)</label>
                            <input type="number" name="experience_years" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label>Consultation Fee (৳)</label>
                            <input type="number" name="consultation_fee" value="500" min="0">
                        </div>
                        <div class="form-group full-width">
                            <label>Bio</label>
                            <textarea name="bio" rows="3" placeholder="Short bio..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">
                        <i class="bi bi-plus-circle"></i> Add Doctor
                    </button>
                </form>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB: REPORTS -->
        <!-- ======================== -->
        <div class="tab-content" id="content-reports">
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-bar-chart-line" style="color:#e535ab;"></i> System Reports</h3>
                    <button class="btn-print" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Full Report
                    </button>
                </div>

                <div class="report-grid">
                    <div class="report-box">
                        <h4><i class="bi bi-calendar-check" style="color:#6366f1;"></i> Appointment Summary</h4>
                        <table class="report-table">
                            <tr><td>Total Appointments</td><td><?= $total_appointments ?></td></tr>
                            <tr><td>Pending</td><td style="color:#f59e0b;"><?= $appt_stats['pending'] ?? 0 ?></td></tr>
                            <tr><td>Approved</td><td style="color:#1a73e8;"><?= $appt_stats['approved'] ?? 0 ?></td></tr>
                            <tr><td>Completed</td><td style="color:#198754;"><?= $appt_stats['completed'] ?? 0 ?></td></tr>
                            <tr><td>Cancelled</td><td style="color:#ef4444;"><?= $appt_stats['cancelled'] ?? 0 ?></td></tr>
                        </table>
                    </div>

                    <div class="report-box">
                        <h4><i class="bi bi-people" style="color:#198754;"></i> User Summary</h4>
                        <table class="report-table">
                            <tr><td>Total Users</td><td><?= $total_users ?></td></tr>
                            <tr><td>Doctors</td><td><?= $total_doctors ?></td></tr>
                            <tr><td>Patients</td><td><?= $total_patients ?></td></tr>
                            <tr><td>Total Posts</td><td><?= $total_posts ?></td></tr>
                            <tr><td>Total Revenue</td><td style="color:#198754;">৳<?= number_format($revenue) ?></td></tr>
                        </table>
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
    if(nav) nav.classList.add('active');

    gsap.from(`#content-${tab}`, { duration: 0.4, opacity: 0, y: 15, ease: "power2.out" });

    if(tab === 'appointments') loadAdminAppointments();
    if(tab === 'doctors') loadAdminDoctors();
    if(tab === 'patients') loadAdminPatients();
    if(tab === 'add-doctor') loadAdminSpecialties();
}

// ================================
// LOAD APPOINTMENTS
// ================================
function loadAdminAppointments() {
    let status = document.getElementById('apptFilter').value;
    fetch(`../php/admin/get-appointments.php?status=${status}`)
        .then(res => res.json())
        .then(data => {
            let el = document.getElementById('admin-appts-table');
            if(data.length === 0) {
                el.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><h4>No Appointments</h4></div>';
                return;
            }
            let html = '<table class="data-table"><thead><tr><th>#</th><th>Appt No</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
            data.forEach((a, i) => {
                html += `<tr id="admin-row-${a.id}">
                    <td>${i+1}</td>
                    <td style="font-size:11px; color:#6c757d;">${a.appointment_no}</td>
                    <td>${a.patient_name}</td>
                    <td>Dr. ${a.doctor_name}</td>
                    <td>${a.appointment_date}</td>
                    <td>${a.appointment_time}</td>
                    <td><span class="badge ${a.status}">${a.status.charAt(0).toUpperCase()+a.status.slice(1)}</span></td>
                    <td><button class="act-btn act-danger" onclick="deleteAppt(${a.id})"><i class="bi bi-trash"></i></button></td>
                </tr>`;
            });
            html += '</tbody></table>';
            el.innerHTML = html;
        });
}

// ================================
// LOAD DOCTORS
// ================================
function loadAdminDoctors() {
    fetch('../php/admin/get-doctors-list.php')
        .then(res => res.json())
        .then(data => {
            let el = document.getElementById('admin-docs-table');
            if(data.length === 0) {
                el.innerHTML = '<div class="empty-state"><i class="bi bi-person-x"></i><h4>No Doctors</h4></div>';
                return;
            }
            let html = '<table class="data-table" id="doc-table"><thead><tr><th>#</th><th>Name</th><th>Specialty</th><th>Fee</th><th>Appointments</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
            data.forEach((d, i) => {
                html += `<tr id="doc-row-${d.user_id}">
                    <td>${i+1}</td>
                    <td><strong>Dr. ${d.full_name}</strong><br><small style="color:#6c757d;">${d.doctor_code}</small></td>
                    <td>${d.specialty_name}</td>
                    <td>৳${d.consultation_fee}</td>
                    <td>${d.total_appts}</td>
                    <td>${d.avg_rating > 0 ? '⭐ '+d.avg_rating : '-'}</td>
                    <td><span class="badge ${d.status}">${d.status.charAt(0).toUpperCase()+d.status.slice(1)}</span></td>
                    <td>
                        <button class="act-btn act-toggle" onclick="toggleUser(${d.user_id},'${d.status}')"><i class="bi bi-toggle-on"></i></button>
                        <button class="act-btn act-danger" onclick="deleteUser(${d.user_id},'doc-row-${d.user_id}')"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            el.innerHTML = html;
        });
}

// ================================
// LOAD PATIENTS
// ================================
function loadAdminPatients() {
    fetch('../php/admin/get-patients-list.php')
        .then(res => res.json())
        .then(data => {
            let el = document.getElementById('admin-pats-table');
            if(data.length === 0) {
                el.innerHTML = '<div class="empty-state"><i class="bi bi-people"></i><h4>No Patients</h4></div>';
                return;
            }
            let html = '<table class="data-table" id="pat-table"><thead><tr><th>#</th><th>Name</th><th>Code</th><th>Gender</th><th>Phone</th><th>Appointments</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
            data.forEach((p, i) => {
                html += `<tr id="pat-row-${p.user_id}">
                    <td>${i+1}</td>
                    <td><strong>${p.full_name}</strong></td>
                    <td style="font-size:11px; color:#6c757d;">${p.patient_code}</td>
                    <td>${p.gender ? p.gender.charAt(0).toUpperCase()+p.gender.slice(1) : '-'}</td>
                    <td>${p.phone}</td>
                    <td>${p.total_appts}</td>
                    <td><span class="badge ${p.status}">${p.status.charAt(0).toUpperCase()+p.status.slice(1)}</span></td>
                    <td><button class="act-btn act-danger" onclick="deleteUser(${p.user_id},'pat-row-${p.user_id}')"><i class="bi bi-trash"></i></button></td>
                </tr>`;
            });
            html += '</tbody></table>';
            el.innerHTML = html;
        });
}

// ================================
// LOAD SPECIALTIES
// ================================
function loadAdminSpecialties() {
    let select = document.getElementById('adminSpecialty');
    if(select.options.length > 1) return;
    fetch('../php/api/get-specialties.php')
        .then(res => res.json())
        .then(data => {
            select.innerHTML = '';
            data.forEach(s => {
                select.innerHTML += `<option value="${s.id}">${s.name}</option>`;
            });
        });
}

// ================================
// LOAD RECENT OVERVIEW
// ================================
function loadOverviewRecent() {
    fetch('../php/admin/get-appointments.php?status=all&limit=5')
        .then(res => res.json())
        .then(data => {
            let el = document.getElementById('overview-recent');
            if(data.length === 0) {
                el.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><h4>No Appointments Yet</h4></div>';
                return;
            }
            let html = '<table class="data-table"><thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Status</th></tr></thead><tbody>';
            data.slice(0,5).forEach(a => {
                html += `<tr><td>${a.patient_name}</td><td>Dr. ${a.doctor_name}</td><td>${a.appointment_date}</td><td><span class="badge ${a.status}">${a.status.charAt(0).toUpperCase()+a.status.slice(1)}</span></td></tr>`;
            });
            html += '</tbody></table>';
            el.innerHTML = html;
        });
}
loadOverviewRecent();

// ================================
// ACTIONS
// ================================
function deleteAppt(id) {
    if(!confirm('Delete this appointment?')) return;
    fetch('../php/admin/delete-appointment.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({id: id})
    }).then(res => res.json()).then(data => {
        if(data.success) document.getElementById(`admin-row-${id}`).remove();
    });
}

function deleteUser(userId, rowId) {
    if(!confirm('Delete this user permanently?')) return;
    fetch('../php/admin/delete-user.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({user_id: userId})
    }).then(res => res.json()).then(data => {
        if(data.success) document.getElementById(rowId).remove();
    });
}

function toggleUser(userId, currentStatus) {
    let newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    fetch('../php/admin/toggle-status.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({user_id: userId, status: newStatus})
    }).then(res => res.json()).then(data => {
        if(data.success) loadAdminDoctors();
    });
}

// ================================
// SEARCH TABLE
// ================================
function searchTable(tableId, query) {
    let table = document.getElementById(tableId);
    if(!table) return;
    let rows = table.querySelectorAll('tbody tr');
    query = query.toLowerCase();
    rows.forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
    });
}

// ================================
// PRINT SECTION
// ================================
function printSection(sectionId) {
    let content = document.getElementById(sectionId).innerHTML;
    let w = window.open('', '_blank');
    w.document.write(`
        <html><head><title>DocBook Report</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            h2 { color: #6366f1; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background: #f0f4f8; padding: 10px; text-align: left; font-size: 12px; }
            td { padding: 10px; border-bottom: 1px solid #eee; font-size: 13px; }
            .badge { padding: 3px 8px; border-radius: 10px; font-size: 11px; }
            .act-btn { display: none; }
        </style></head>
        <body>
            <h2>DocBook - Report</h2>
            <p>Generated: ${new Date().toLocaleString()}</p><hr>
            ${content}
        </body></html>`);
    w.document.close();
    w.print();
}

// ================================
// USERNAME CHECK
// ================================
document.getElementById('adminUsername')?.addEventListener('input', function() {
    let val = this.value.trim();
    let msg = document.getElementById('admin-user-check');
    if(val.length < 3) { msg.textContent = ''; return; }
    fetch(`../php/auth/check-username.php?username=${val}`)
        .then(res => res.json())
        .then(data => {
            if(data.exists) {
                msg.textContent = '✖ Taken'; msg.style.color = '#ef4444';
            } else {
                msg.textContent = '✓ Available'; msg.style.color = '#198754';
            }
        });
});

// Load specialties on page load
loadAdminSpecialties();

// ================================
// GSAP
// ================================
gsap.from('.stat-card', { duration: 0.5, y: 25, opacity: 0, stagger: 0.08, ease: "power3.out" });
gsap.from('.quick-card', { duration: 0.5, y: 20, opacity: 0, stagger: 0.1, delay: 0.3, ease: "power3.out" });
</script>

</body>
</html>