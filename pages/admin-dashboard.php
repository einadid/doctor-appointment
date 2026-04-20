<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html");
    exit();
}
include '../php/config/database.php';

$user_id = $_SESSION['user_id'];

// Admin info
$admin = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT a.*, u.email, u.username 
     FROM admins a 
     JOIN users u ON a.user_id = u.id 
     WHERE a.user_id = $user_id"));

// যদি admin table এ data না থাকে
if (!$admin) {
    $admin = [
        'full_name' => $_SESSION['username'],
        'email' => '',
        'username' => $_SESSION['username']
    ];
}

// Overall Stats
$total_patients = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM patients"))['t'];
$total_doctors = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM doctors"))['t'];
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM appointments"))['t'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as t FROM users"))['t'];

// Pagination for appointments
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 8;
$offset = ($page - 1) * $limit;
$total_pages = ceil($total_appointments / $limit);

$appointments = mysqli_query($conn,
    "SELECT a.*, 
            p.full_name as patient_name,
            d.full_name as doctor_name,
            s.name as specialty
     FROM appointments a
     JOIN patients p ON a.patient_id = p.id
     JOIN doctors d ON a.doctor_id = d.id
     JOIN specialties s ON d.specialty_id = s.id
     ORDER BY a.created_at DESC
     LIMIT $limit OFFSET $offset");

// All Doctors list
$doctors_list = mysqli_query($conn,
    "SELECT d.*, u.email, u.phone, u.status,
            s.name as specialty_name,
            COUNT(a.id) as total_appointments,
            AVG(r.rating) as avg_rating
     FROM doctors d
     JOIN users u ON d.user_id = u.id
     JOIN specialties s ON d.specialty_id = s.id
     LEFT JOIN appointments a ON a.doctor_id = d.id
     LEFT JOIN ratings r ON r.doctor_id = d.id
     GROUP BY d.id
     ORDER BY d.created_at DESC");

// All Patients list
$patients_list = mysqli_query($conn,
    "SELECT p.*, u.email, u.phone, u.status,
            COUNT(a.id) as total_appointments
     FROM patients p
     JOIN users u ON p.user_id = u.id
     LEFT JOIN appointments a ON a.patient_id = p.id
     GROUP BY p.id
     ORDER BY p.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - DocBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; }

        .admin-wrapper {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: #1a1a2e;
            color: white;
            padding: 25px 18px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-logo {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-logo h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1a73e8;
        }

        .sidebar-logo p {
            font-size: 11px;
            opacity: 0.6;
            margin-top: 3px;
        }

        .sidebar-section {
            font-size: 10px;
            letter-spacing: 1px;
            opacity: 0.5;
            margin: 15px 0 8px 10px;
            text-transform: uppercase;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 8px;
            color: rgba(255,255,255,0.8);
            font-size: 13px;
            margin-bottom: 3px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #1a73e8;
            color: white;
        }

        .sidebar-menu a.logout {
            background: rgba(220,53,69,0.2);
            color: #ff6b6b;
            margin-top: 15px;
        }

        .sidebar-menu a.logout:hover {
            background: #dc3545;
            color: white;
        }

        /* Main */
        .main-content {
            padding: 25px 30px;
            overflow-y: auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .topbar h2 {
            font-size: 18px;
            font-weight: 600;
            color: #212529;
        }

        .topbar p {
            font-size: 12px;
            color: #6c757d;
        }

        .admin-badge {
            background: #1a73e8;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        /* Tab System */
        .tab-nav {
            display: flex;
            gap: 5px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .tab-btn {
            padding: 9px 18px;
            border: none;
            border-radius: 8px;
            background: transparent;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            color: #6c757d;
            transition: all 0.3s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tab-btn:hover {
            background: #f0f4f8;
            color: #1a73e8;
        }

        .tab-btn.active {
            background: #1a73e8;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .stat-card .icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-card .info h3 {
            font-size: 26px;
            font-weight: 700;
            line-height: 1;
        }

        .stat-card .info p {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }

        /* Table */
        .data-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }

        .data-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .data-card-header h3 {
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 11px 13px;
            text-align: left;
            font-weight: 600;
            color: #6c757d;
            font-size: 12px;
        }

        .data-table td {
            padding: 12px 13px;
            border-bottom: 1px solid #f0f4f8;
            vertical-align: middle;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover td {
            background: #f8f9fa;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge.pending { background: #fff3cd; color: #856404; }
        .badge.approved { background: #d4edda; color: #155724; }
        .badge.completed { background: #cce5ff; color: #004085; }
        .badge.cancelled { background: #f8d7da; color: #721c24; }
        .badge.active { background: #d4edda; color: #155724; }
        .badge.inactive { background: #f8d7da; color: #721c24; }

        .action-btn {
            padding: 5px 11px;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 2px;
        }

        .btn-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-danger:hover {
            background: #dc3545;
            color: white;
        }

        .btn-success {
            background: #d4edda;
            color: #155724;
        }

        .btn-success:hover {
            background: #198754;
            color: white;
        }

        .btn-primary {
            background: #e8f0fe;
            color: #1a73e8;
        }

        .btn-primary:hover {
            background: #1a73e8;
            color: white;
        }

        /* Print Button */
        .btn-print {
            background: #198754;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .btn-print:hover {
            background: #0d6e3f;
        }

        /* Pagination */
        .pagination {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            padding: 7px 12px;
            border-radius: 8px;
            background: #f0f4f8;
            color: #1a73e8;
            font-size: 13px;
            transition: all 0.3s;
        }

        .pagination a.active,
        .pagination a:hover {
            background: #1a73e8;
            color: white;
        }

        /* Add Doctor Form */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* Print Styles (Feature #20) */
        @media print {
            .sidebar, .topbar, .tab-nav,
            .action-btn, .btn-print,
            .pagination { display: none !important; }

            .admin-wrapper {
                grid-template-columns: 1fr !important;
            }

            .main-content {
                padding: 0 !important;
            }

            .tab-content {
                display: block !important;
            }

            .data-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6;
            }
        }

        /* Search Box */
        .search-box {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f0f4f8;
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 13px;
        }

        .search-box input {
            border: none;
            background: transparent;
            outline: none;
            font-size: 13px;
            width: 200px;
        }
    </style>
</head>
<body>

<div class="admin-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <h2><i class="bi bi-hospital"></i> DocBook</h2>
            <p>Admin Control Panel</p>
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
            <a onclick="switchTab('doctors')" id="nav-doctors">
                <i class="bi bi-person-badge"></i> Doctors
            </a>
            <a onclick="switchTab('patients')" id="nav-patients">
                <i class="bi bi-people"></i> Patients
            </a>
            <a onclick="switchTab('add-doctor')" id="nav-add-doctor">
                <i class="bi bi-person-plus"></i> Add Doctor
            </a>

            <div class="sidebar-section">Reports</div>
            <a onclick="switchTab('reports')" id="nav-reports">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </a>

            <a href="../php/auth/logout.php" class="logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <div>
                <h2>Admin Dashboard</h2>
                <p><?= date('l, d F Y') ?> &nbsp;|&nbsp;
                   Welcome, <?= htmlspecialchars($admin['full_name']) ?>
                </p>
            </div>
            <span class="admin-badge">
                <i class="bi bi-shield-check"></i> Admin
            </span>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-nav">
            <button class="tab-btn active" onclick="switchTab('overview')" id="tab-overview">
                <i class="bi bi-grid"></i> Overview
            </button>
            <button class="tab-btn" onclick="switchTab('appointments')" id="tab-appointments">
                <i class="bi bi-calendar-check"></i> Appointments
            </button>
            <button class="tab-btn" onclick="switchTab('doctors')" id="tab-doctors">
                <i class="bi bi-person-badge"></i> Doctors
            </button>
            <button class="tab-btn" onclick="switchTab('patients')" id="tab-patients">
                <i class="bi bi-people"></i> Patients
            </button>
            <button class="tab-btn" onclick="switchTab('add-doctor')" id="tab-add-doctor">
                <i class="bi bi-person-plus"></i> Add Doctor
            </button>
            <button class="tab-btn" onclick="switchTab('reports')" id="tab-reports">
                <i class="bi bi-file-earmark-bar-graph"></i> Reports
            </button>
        </div>

        <!-- ======================== -->
        <!-- TAB 1: OVERVIEW -->
        <!-- ======================== -->
        <div class="tab-content active" id="content-overview">

            <div class="stats-grid">
                <div class="stat-card" id="sc1">
                    <div class="icon" style="background:#e8f0fe;">
                        <i class="bi bi-people" style="color:#1a73e8;"></i>
                    </div>
                    <div class="info">
                        <h3><?= $total_patients ?></h3>
                        <p>Total Patients</p>
                    </div>
                </div>
                <div class="stat-card" id="sc2">
                    <div class="icon" style="background:#d4edda;">
                        <i class="bi bi-person-badge" style="color:#198754;"></i>
                    </div>
                    <div class="info">
                        <h3><?= $total_doctors ?></h3>
                        <p>Total Doctors</p>
                    </div>
                </div>
                <div class="stat-card" id="sc3">
                    <div class="icon" style="background:#fff3cd;">
                        <i class="bi bi-calendar-check" style="color:#ffc107;"></i>
                    </div>
                    <div class="info">
                        <h3><?= $total_appointments ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
                <div class="stat-card" id="sc4">
                    <div class="icon" style="background:#f8d7da;">
                        <i class="bi bi-person-circle" style="color:#dc3545;"></i>
                    </div>
                    <div class="info">
                        <h3><?= $total_users ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
            </div>

            <!-- Recent Appointments -->
            <div class="data-card">
                <div class="data-card-header">
                    <h3>
                        <i class="bi bi-clock-history" style="color:#1a73e8;"></i>
                        Recent Appointments
                    </h3>
                </div>
                <table class="data-table" id="recent-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Specialty</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="recent-tbody">
                        <!-- Load via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB 2: APPOINTMENTS -->
        <!-- ======================== -->
        <div class="tab-content" id="content-appointments">
            <div class="data-card">
                <div class="data-card-header">
                    <h3>
                        <i class="bi bi-calendar-check" style="color:#1a73e8;"></i>
                        All Appointments
                    </h3>
                    <button class="btn-print" onclick="printSection('appointments-print')">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>

                <div id="appointments-print">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Appt No</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Specialty</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sl = $offset + 1;
                        mysqli_data_seek($appointments, 0);
                        while($appt = mysqli_fetch_assoc($appointments)):
                        ?>
                            <tr id="admin-appt-<?= $appt['id'] ?>">
                                <td><?= $sl++ ?></td>
                                <td style="font-size:11px; color:#6c757d;">
                                    <?= $appt['appointment_no'] ?>
                                </td>
                                <td><?= htmlspecialchars($appt['patient_name']) ?></td>
                                <td>Dr. <?= htmlspecialchars($appt['doctor_name']) ?></td>
                                <td><?= htmlspecialchars($appt['specialty']) ?></td>
                                <td><?= date('d M Y', strtotime($appt['appointment_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($appt['appointment_time'])) ?></td>
                                <td>
                                    <span class="badge <?= $appt['status'] ?>">
                                        <?= ucfirst($appt['status']) ?>
                                    </span>
                                </td>
                                <td class="no-print">
                                    <button class="action-btn btn-danger"
                                        onclick="deleteAppointment(<?= $appt['id'] ?>)">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>"
                       class="<?= $page == $i ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB 3: DOCTORS -->
        <!-- ======================== -->
        <div class="tab-content" id="content-doctors">
            <div class="data-card">
                <div class="data-card-header">
                    <h3>
                        <i class="bi bi-person-badge" style="color:#198754;"></i>
                        All Doctors
                    </h3>
                    <div style="display:flex; gap:10px;">
                        <div class="search-box">
                            <i class="bi bi-search" style="color:#6c757d;"></i>
                            <input type="text"
                                   id="doctorSearch"
                                   placeholder="Search doctor..."
                                   onkeyup="searchTable('doctorTable', this.value)">
                        </div>
                        <button class="btn-print" onclick="printSection('doctors-print')">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>

                <div id="doctors-print">
                    <table class="data-table" id="doctorTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Specialty</th>
                                <th>Qualification</th>
                                <th>Fee</th>
                                <th>Appointments</th>
                                <th>Avg Rating</th>
                                <th>Status</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sl = 1;
                        while($doc = mysqli_fetch_assoc($doctors_list)):
                        ?>
                            <tr id="doc-row-<?= $doc['id'] ?>">
                                <td><?= $sl++ ?></td>
                                <td>
                                    <strong>Dr. <?= htmlspecialchars($doc['full_name']) ?></strong>
                                    <br>
                                    <small style="color:#6c757d;">
                                        <?= htmlspecialchars($doc['doctor_code']) ?>
                                    </small>
                                </td>
                                <td><?= htmlspecialchars($doc['specialty_name']) ?></td>
                                <td><?= htmlspecialchars($doc['qualification'] ?? '-') ?></td>
                                <td>৳<?= number_format($doc['consultation_fee'], 0) ?></td>
                                <td><?= $doc['total_appointments'] ?></td>
                                <td>
                                    <?php
                                    $avg = round($doc['avg_rating'] ?? 0, 1);
                                    echo $avg > 0 ? "⭐ $avg" : '-';
                                    ?>
                                </td>
                                <td>
                                    <span class="badge <?= $doc['status'] ?>">
                                        <?= ucfirst($doc['status']) ?>
                                    </span>
                                </td>
                                <td class="no-print">
                                    <button class="action-btn btn-danger"
                                        onclick="deleteUser(<?= $doc['user_id'] ?>, 'doc-row-<?= $doc['id'] ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <button class="action-btn btn-primary"
                                        onclick="toggleStatus(<?= $doc['user_id'] ?>, '<?= $doc['status'] ?>')">
                                        <i class="bi bi-toggle-on"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB 4: PATIENTS -->
        <!-- ======================== -->
        <div class="tab-content" id="content-patients">
            <div class="data-card">
                <div class="data-card-header">
                    <h3>
                        <i class="bi bi-people" style="color:#1a73e8;"></i>
                        All Patients
                    </h3>
                    <div style="display:flex; gap:10px;">
                        <div class="search-box">
                            <i class="bi bi-search" style="color:#6c757d;"></i>
                            <input type="text"
                                   placeholder="Search patient..."
                                   onkeyup="searchTable('patientTable', this.value)">
                        </div>
                        <button class="btn-print" onclick="printSection('patients-print')">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>

                <div id="patients-print">
                    <table class="data-table" id="patientTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Patient Code</th>
                                <th>Gender</th>
                                <th>Phone</th>
                                <th>Appointments</th>
                                <th>Status</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sl = 1;
                        while($pat = mysqli_fetch_assoc($patients_list)):
                        ?>
                            <tr id="pat-row-<?= $pat['id'] ?>">
                                <td><?= $sl++ ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($pat['full_name']) ?></strong>
                                </td>
                                <td style="font-size:12px; color:#6c757d;">
                                    <?= $pat['patient_code'] ?>
                                </td>
                                <td><?= ucfirst($pat['gender']) ?></td>
                                <td><?= $pat['phone'] ?></td>
                                <td><?= $pat['total_appointments'] ?></td>
                                <td>
                                    <span class="badge <?= $pat['status'] ?>">
                                        <?= ucfirst($pat['status']) ?>
                                    </span>
                                </td>
                                <td class="no-print">
                                    <button class="action-btn btn-danger"
                                        onclick="deleteUser(<?= $pat['user_id'] ?>, 'pat-row-<?= $pat['id'] ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB 5: ADD DOCTOR -->
        <!-- ======================== -->
        <div class="tab-content" id="content-add-doctor">
            <div class="data-card">
                <div class="data-card-header">
                    <h3>
                        <i class="bi bi-person-plus" style="color:#1a73e8;"></i>
                        Add New Doctor
                    </h3>
                </div>

                <form action="../php/admin/add-doctor.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name"
                                   placeholder="Doctor full name" required>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username"
                                   placeholder="Username" required>
                            <small id="admin-user-status"
                                   style="font-size:12px;"></small>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email"
                                   placeholder="Email" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone"
                                   placeholder="Phone number" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password"
                                   placeholder="Password" required>
                        </div>
                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Specialty</label>
                            <select name="specialty_id" required>
                                <?php
                                $specs = mysqli_query($conn, "SELECT * FROM specialties");
                                while($s = mysqli_fetch_assoc($specs)):
                                ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Qualification</label>
                            <input type="text" name="qualification"
                                   placeholder="e.g. MBBS, MD">
                        </div>
                        <div class="form-group">
                            <label>Experience (Years)</label>
                            <input type="number" name="experience_years"
                                   value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label>Consultation Fee (৳)</label>
                            <input type="number" name="consultation_fee"
                                   value="500" min="0">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top:10px;">
                        <label>Bio</label>
                        <textarea name="bio" rows="3"
                            style="width:100%; padding:10px; border-radius:8px;
                                   border:1.5px solid #ddd; font-family:inherit;"
                            placeholder="Short bio about the doctor..."></textarea>
                    </div>

                    <button type="submit"
                        style="background:#1a73e8; color:white; border:none;
                               padding:11px 28px; border-radius:8px;
                               cursor:pointer; margin-top:15px; font-size:14px;
                               font-weight:500;">
                        <i class="bi bi-plus-circle"></i> Add Doctor
                    </button>
                </form>
            </div>
        </div>

        <!-- ======================== -->
        <!-- TAB 6: REPORTS -->
        <!-- ======================== -->
        <div class="tab-content" id="content-reports">
            <div class="data-card">
                <div class="data-card-header">
                    <h3>
                        <i class="bi bi-file-earmark-bar-graph"
                           style="color:#1a73e8;"></i>
                        System Reports
                    </h3>
                    <button class="btn-print" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Full Report
                    </button>
                </div>

                <!-- Summary Report -->
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
                    <div style="background:#f8f9fa; border-radius:10px; padding:20px;">
                        <h4 style="font-size:14px; margin-bottom:15px; color:#1a73e8;">
                            📊 Appointment Summary
                        </h4>
                        <table class="data-table">
                            <tr>
                                <td>Total Appointments</td>
                                <td><strong><?= $total_appointments ?></strong></td>
                            </tr>
                            <tr>
                                <td>Pending</td>
                                <td id="r-pending">Loading...</td>
                            </tr>
                            <tr>
                                <td>Approved</td>
                                <td id="r-approved">Loading...</td>
                            </tr>
                            <tr>
                                <td>Completed</td>
                                <td id="r-completed">Loading...</td>
                            </tr>
                            <tr>
                                <td>Cancelled</td>
                                <td id="r-cancelled">Loading...</td>
                            </tr>
                        </table>
                    </div>

                    <div style="background:#f8f9fa; border-radius:10px; padding:20px;">
                        <h4 style="font-size:14px; margin-bottom:15px; color:#198754;">
                            👥 User Summary
                        </h4>
                        <table class="data-table">
                            <tr>
                                <td>Total Users</td>
                                <td><strong><?= $total_users ?></strong></td>
                            </tr>
                            <tr>
                                <td>Total Doctors</td>
                                <td><strong><?= $total_doctors ?></strong></td>
                            </tr>
                            <tr>
                                <td>Total Patients</td>
                                <td><strong><?= $total_patients ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
// ================================
// GSAP Animations
// ================================
gsap.from(".stat-card", {
    duration: 0.5,
    y: 25,
    opacity: 0,
    stagger: 0.1,
    ease: "power3.out"
});

gsap.from(".data-card", {
    duration: 0.5,
    y: 15,
    opacity: 0,
    delay: 0.3,
    ease: "power3.out"
});

// ================================
// Tab System
// ================================
function switchTab(tabName) {
    // Hide all content
    document.querySelectorAll('.tab-content').forEach(c => {
        c.classList.remove('active');
    });

    // Remove active from all buttons
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.classList.remove('active');
    });

    document.querySelectorAll('.sidebar-menu a').forEach(a => {
        a.classList.remove('active');
    });

    // Show target
    document.getElementById(`content-${tabName}`).classList.add('active');
    document.getElementById(`tab-${tabName}`).classList.add('active');
    document.getElementById(`nav-${tabName}`).classList.add('active');

    // GSAP animation
    gsap.from(`#content-${tabName}`, {
        duration: 0.4,
        opacity: 0,
        y: 15,
        ease: "power2.out"
    });
}

// ================================
// Load Recent Appointments (AJAX)
// ================================
function loadRecentAppointments() {
    fetch('../php/admin/get-recent.php')
        .then(res => res.json())
        .then(data => {
            let tbody = document.getElementById('recent-tbody');
            tbody.innerHTML = '';
            data.forEach(a => {
                tbody.innerHTML += `
                    <tr>
                        <td>${a.patient_name}</td>
                        <td>Dr. ${a.doctor_name}</td>
                        <td>${a.specialty}</td>
                        <td>${a.appointment_date}</td>
                        <td>${a.appointment_time}</td>
                        <td>
                            <span class="badge ${a.status}">
                                ${a.status.charAt(0).toUpperCase() + a.status.slice(1)}
                            </span>
                        </td>
                    </tr>`;
            });
        });
}

// Load Report Stats
function loadReportStats() {
    fetch('../php/admin/get-report-stats.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('r-pending').innerHTML =
                `<strong>${data.pending}</strong>`;
            document.getElementById('r-approved').innerHTML =
                `<strong>${data.approved}</strong>`;
            document.getElementById('r-completed').innerHTML =
                `<strong>${data.completed}</strong>`;
            document.getElementById('r-cancelled').innerHTML =
                `<strong>${data.cancelled}</strong>`;
        });
}

loadRecentAppointments();
loadReportStats();

// ================================
// Search Table
// ================================
function searchTable(tableId, query) {
    let rows = document.querySelectorAll(`#${tableId} tbody tr`);
    query = query.toLowerCase();
    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

// ================================
// Delete Appointment
// ================================
function deleteAppointment(id) {
    if(!confirm('Delete this appointment?')) return;
    fetch('../php/admin/delete-appointment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById(`admin-appt-${id}`).remove();
        }
    });
}

// ================================
// Delete User
// ================================
function deleteUser(userId, rowId) {
    if(!confirm('Delete this user? This cannot be undone!')) return;
    fetch('../php/admin/delete-user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById(rowId).remove();
        }
    });
}

// ================================
// Toggle User Status
// ================================
function toggleStatus(userId, currentStatus) {
    let newStatus = currentStatus === 'active' ? 'inactive' : 'active';
    fetch('../php/admin/toggle-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId, status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) location.reload();
    });
}

// ================================
// Print Section (Feature #20)
// ================================
function printSection(sectionId) {
    let content = document.getElementById(sectionId).innerHTML;
    let printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>DocBook Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h2 { color: #1a73e8; }
                table { width: 100%; border-collapse: collapse; }
                th { background: #f0f4f8; padding: 10px; text-align: left; }
                td { padding: 10px; border-bottom: 1px solid #eee; }
                .badge { padding: 3px 8px; border-radius: 10px; font-size: 11px; }
                .no-print { display: none; }
            </style>
        </head>
        <body>
            <h2>DocBook - System Report</h2>
            <p>Generated: ${new Date().toLocaleString()}</p>
            <hr>
            ${content}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// ================================
// Username Check in Add Doctor
// ================================
document.addEventListener('DOMContentLoaded', function() {
    let usernameField = document.querySelector('[name="username"]');
    if(usernameField) {
        usernameField.addEventListener('keyup', function() {
            let val = this.value;
            if(val.length > 2) {
                fetch(`../php/auth/check-username.php?username=${val}`)
                    .then(res => res.json())
                    .then(data => {
                        let msg = document.getElementById('admin-user-status');
                        if(data.exists) {
                            msg.textContent = '❌ Username taken!';
                            msg.style.color = 'red';
                        } else {
                            msg.textContent = '✅ Available!';
                            msg.style.color = 'green';
                        }
                    });
            }
        });
    }
});
</script>

</body>
</html>