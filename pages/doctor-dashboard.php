<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    header("Location: login.html");
    exit();
}
include '../php/config/database.php';

$user_id = $_SESSION['user_id'];

// Doctor info
$sql = "SELECT d.*, u.email, u.phone, s.name as specialty_name 
        FROM doctors d 
        JOIN users u ON d.user_id = u.id 
        JOIN specialties s ON d.specialty_id = s.id
        WHERE d.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$doctor = mysqli_fetch_assoc($result);

// ✅ Doctor data না থাকলে default value দাও
if (!$doctor) {
    $doctor = [
        'id' => 0,
        'full_name' => $_SESSION['username'],
        'specialty_name' => 'Not Set',
        'email' => '',
        'phone' => '',
        'image' => null,
        'qualification' => '',
        'experience_years' => 0,
        'consultation_fee' => 0,
        'bio' => ''
    ];
}

$doctor_id = $doctor['id'];

// ✅ doctor_id check করে তারপর query করো
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

if ($doctor_id > 0) {
    $total_result = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as t FROM appointments 
         WHERE doctor_id = $doctor_id"));
    $total = $total_result['t'] ?? 0;

    $stats_result = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT 
            COUNT(*) as total,
            SUM(status='pending') as pending,
            SUM(status='completed') as completed,
            SUM(status='approved') as approved
         FROM appointments WHERE doctor_id = $doctor_id"));
    $stats = $stats_result ?? ['total'=>0,'pending'=>0,'completed'=>0,'approved'=>0];

    $rating_data = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings
         FROM ratings WHERE doctor_id = $doctor_id"));
    $rating_data = $rating_data ?? ['avg_rating'=>0,'total_ratings'=>0];

    $total_pages = ceil($total / $limit);

    $appts = mysqli_query($conn,
        "SELECT a.*, p.full_name as patient_name, 
                p.gender, p.date_of_birth
         FROM appointments a
         JOIN patients p ON a.patient_id = p.id
         WHERE a.doctor_id = $doctor_id
         ORDER BY a.appointment_date DESC
         LIMIT $limit OFFSET $offset");

} else {
    // ✅ Doctor profile setup না থাকলে empty data
    $total = 0;
    $total_pages = 0;
    $stats = ['total'=>0,'pending'=>0,'completed'=>0,'approved'=>0];
    $rating_data = ['avg_rating'=>0,'total_ratings'=>0];
    $appts = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard - DocBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .dashboard-wrapper {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: #0d47a1;
            color: white;
            padding: 30px 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar .doctor-profile {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 20px;
        }

        .sidebar .doctor-profile img {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .sidebar .doctor-profile h4 {
            font-size: 15px;
            font-weight: 600;
        }

        .sidebar .doctor-profile p {
            font-size: 12px;
            opacity: 0.8;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 14px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            margin-bottom: 4px;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255,255,255,0.2);
        }

        .sidebar-menu a.logout {
            background: rgba(220,53,69,0.3);
            margin-top: 20px;
        }

        .main-content {
            padding: 30px;
            background: #f0f4f8;
        }

        .page-header h2 {
            font-size: 22px;
            color: #1a73e8;
            font-weight: 700;
        }

        .page-header p {
            font-size: 13px;
            color: #6c757d;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .stat-card .icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-card .info h3 {
            font-size: 22px;
            font-weight: 700;
        }

        .stat-card .info p {
            font-size: 12px;
            color: #6c757d;
        }

        .table-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .appt-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .appt-table th {
            background: #f0f4f8;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            color: #6c757d;
        }

        .appt-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f4f8;
            vertical-align: middle;
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

        .action-btn {
            padding: 5px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
            margin: 2px;
        }

        .btn-approve { background: #d4edda; color: #155724; }
        .btn-approve:hover { background: #198754; color: white; }
        .btn-complete { background: #cce5ff; color: #004085; }
        .btn-complete:hover { background: #1a73e8; color: white; }
        .btn-cancel { background: #f8d7da; color: #721c24; }
        .btn-cancel:hover { background: #dc3545; color: white; }

        .pagination {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 20px;
        }

        .pagination a {
            padding: 7px 13px;
            border-radius: 8px;
            background: white;
            color: #1a73e8;
            font-size: 13px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }

        .pagination a.active,
        .pagination a:hover {
            background: #1a73e8;
            color: white;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }

        /* ✅ Doctor profile not set warning */
        .setup-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }

        .setup-warning h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 8px;
        }

        .setup-warning p {
            color: #856404;
            font-size: 13px;
        }
    </style>
</head>
<body>

<div class="dashboard-wrapper">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="doctor-profile">
            <img src="<?= $doctor['image']
                ? '../uploads/'.$doctor['image']
                : 'https://ui-avatars.com/api/?name='.urlencode($doctor['full_name']).'&background=0d47a1&color=fff' ?>"
                alt="Doctor">
            <h4>Dr. <?= htmlspecialchars($doctor['full_name']) ?></h4>
            <p><?= htmlspecialchars($doctor['specialty_name']) ?></p>
        </div>

        <nav class="sidebar-menu">
            <a href="#" class="active">
                <i class="bi bi-grid"></i> Dashboard
            </a>
            <a href="#appointments">
                <i class="bi bi-calendar-check"></i> Appointments
            </a>
            <a href="#profile">
                <i class="bi bi-person"></i> Profile
            </a>
            <a href="../php/auth/logout.php" class="logout">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <div class="page-header" style="margin-bottom:20px;">
            <h2>Dr. <?= htmlspecialchars($doctor['full_name']) ?>'s Dashboard</h2>
            <p><?= date('l, d F Y') ?></p>
        </div>

        <?php if($doctor_id == 0): ?>
        <!-- ✅ Profile Setup Warning -->
        <div class="setup-warning">
            <h3>⚠️ Doctor Profile Not Complete!</h3>
            <p>
                Your doctor profile is not set up yet.<br>
                Please fill in the profile form below and save.
            </p>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon" style="background:#e8f0fe;">
                    <i class="bi bi-calendar-check" style="color:#1a73e8;"></i>
                </div>
                <div class="info">
                    <h3><?= $stats['total'] ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#fff3cd;">
                    <i class="bi bi-clock" style="color:#ffc107;"></i>
                </div>
                <div class="info">
                    <h3><?= $stats['pending'] ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#d4edda;">
                    <i class="bi bi-check-circle" style="color:#198754;"></i>
                </div>
                <div class="info">
                    <h3><?= $stats['completed'] ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="icon" style="background:#ffeaa7;">
                    <i class="bi bi-star-fill" style="color:#ffc107;"></i>
                </div>
                <div class="info">
                    <h3><?= number_format($rating_data['avg_rating'] ?? 0, 1) ?></h3>
                    <p>Avg Rating</p>
                </div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="table-card" id="appointments">
            <div class="card-title">
                <i class="bi bi-calendar-check" style="color:#1a73e8;"></i>
                Appointment Requests
            </div>

            <?php if($doctor_id == 0): ?>
                <p style="color:#6c757d; text-align:center; padding:20px;">
                    Complete your profile first to see appointments.
                </p>
            <?php else: ?>
            <table class="appt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sl = $offset + 1;
                if($appts && mysqli_num_rows($appts) > 0):
                    while($appt = mysqli_fetch_assoc($appts)):
                ?>
                    <tr id="appt-row-<?= $appt['id'] ?>">
                        <td><?= $sl++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($appt['patient_name']) ?></strong>
                            <br>
                            <small style="color:#6c757d;"><?= $appt['gender'] ?></small>
                        </td>
                        <td><?= date('d M Y', strtotime($appt['appointment_date'])) ?></td>
                        <td><?= date('h:i A', strtotime($appt['appointment_time'])) ?></td>
                        <td><?= htmlspecialchars(substr($appt['reason'] ?? 'N/A', 0, 30)) ?></td>
                        <td>
                            <span class="badge <?= $appt['status'] ?>"
                                  id="status-<?= $appt['id'] ?>">
                                <?= ucfirst($appt['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if($appt['status'] == 'pending'): ?>
                            <button class="action-btn btn-approve"
                                onclick="updateStatus(<?= $appt['id'] ?>, 'approved')">
                                Approve
                            </button>
                            <?php endif; ?>

                            <?php if($appt['status'] == 'approved'): ?>
                            <button class="action-btn btn-complete"
                                onclick="updateStatus(<?= $appt['id'] ?>, 'completed')">
                                Complete
                            </button>
                            <?php endif; ?>

                            <?php if(in_array($appt['status'], ['pending','approved'])): ?>
                            <button class="action-btn btn-cancel"
                                onclick="updateStatus(<?= $appt['id'] ?>, 'cancelled')">
                                Cancel
                            </button>
                            <?php endif; ?>

                            <?php if($appt['status'] == 'completed'): ?>
                            <span style="color:#198754; font-size:12px;">✓ Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:#6c757d; padding:20px;">
                            No appointments yet.
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <div class="pagination">
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                <a href="?page=<?= $i ?>"
                   class="<?= $page == $i ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>

        <!-- Profile Edit -->
        <div class="profile-card" id="profile">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:20px;">
                <i class="bi bi-person"></i> Update Profile
            </h3>
            <form action="../php/doctor/update-profile.php"
                  method="POST"
                  enctype="multipart/form-data">

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name"
                               value="<?= htmlspecialchars($doctor['full_name']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification"
                               value="<?= htmlspecialchars($doctor['qualification'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Experience (Years)</label>
                        <input type="number" name="experience_years"
                               value="<?= $doctor['experience_years'] ?? 0 ?>">
                    </div>
                    <div class="form-group">
                        <label>Consultation Fee (৳)</label>
                        <input type="number" name="consultation_fee"
                               value="<?= $doctor['consultation_fee'] ?? 0 ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-top:10px;">
                    <label>Bio</label>
                    <textarea name="bio" rows="3"
                        style="width:100%; padding:10px; border-radius:8px;
                               border:1.5px solid #ddd; font-family:inherit;">
<?= htmlspecialchars($doctor['bio'] ?? '') ?>
                    </textarea>
                </div>

                <div class="form-group" style="margin-top:10px;">
                    <label>Profile Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>

                <button type="submit"
                    style="background:#1a73e8; color:white; border:none;
                           padding:10px 25px; border-radius:8px;
                           cursor:pointer; margin-top:10px; font-size:14px;">
                    Save Profile
                </button>
            </form>
        </div>

    </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
gsap.from(".stat-card", {
    duration: 0.6,
    y: 30,
    opacity: 0,
    stagger: 0.12,
    ease: "power3.out"
});

gsap.from(".table-card", {
    duration: 0.6,
    y: 20,
    opacity: 0,
    stagger: 0.15,
    delay: 0.3,
    ease: "power3.out"
});

function updateStatus(apptId, status) {
    if(!confirm(`Are you sure to mark as ${status}?`)) return;

    fetch('../php/appointments/update-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ appt_id: apptId, status: status })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            let badge = document.getElementById(`status-${apptId}`);
            badge.className = `badge ${status}`;
            badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            setTimeout(() => location.reload(), 800);
        } else {
            alert('Update failed!');
        }
    });
}
</script>

</body>
</html>