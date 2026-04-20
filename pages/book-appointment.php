<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.html");
    exit();
}
include '../php/config/database.php';

// Specialties
$specialties = mysqli_query($conn, "SELECT * FROM specialties ORDER BY name");

// All doctors for initial load
$all_doctors = mysqli_query($conn,
    "SELECT d.id, d.full_name, d.consultation_fee, d.experience_years,
            d.qualification, s.name as specialty
     FROM doctors d
     JOIN specialties s ON d.specialty_id = s.id
     ORDER BY d.full_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - DocBook</title>
    <link rel="stylesheet" href="../assets/css/fix.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .booking-page {
            max-width: 850px;
            margin: 0 auto;
            padding: 20px;
        }

        .step-bar {
            display: flex;
            margin-bottom: 25px;
            border: 1px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
        }

        .step-item {
            flex: 1;
            padding: 10px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            background: #f5f5f5;
            color: #999;
            border-right: 1px solid #ddd;
        }

        .step-item:last-child {
            border-right: none;
        }

        .step-item.active {
            background: #1a73e8;
            color: white;
        }

        .step-item.done {
            background: #198754;
            color: white;
        }

        .step-section {
            display: none;
        }

        .step-section.active {
            display: block;
        }

        .doctor-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 15px;
        }

        .doctor-option {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .doctor-option:hover {
            border-color: #1a73e8;
            background: #f0f7ff;
        }

        .doctor-option.selected {
            border-color: #1a73e8;
            background: #e8f0fe;
        }

        .doctor-option img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid #1a73e8;
        }

        .doctor-option h4 {
            font-size: 14px;
            margin: 0;
        }

        .doctor-option p {
            font-size: 12px;
            color: #666;
            margin: 0;
        }

        .doctor-option .doc-fee {
            color: #198754;
            font-weight: bold;
            font-size: 13px;
        }

        .time-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }

        .time-slot {
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
            cursor: pointer;
        }

        .time-slot:hover {
            border-color: #1a73e8;
            background: #f0f7ff;
        }

        .time-slot.selected {
            background: #1a73e8;
            color: white;
            border-color: #1a73e8;
        }

        .time-slot.booked {
            background: #f8d7da;
            color: #721c24;
            cursor: not-allowed;
            border-color: #f5c6cb;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .summary-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .summary-table td:first-child {
            font-weight: bold;
            color: #555;
            width: 40%;
        }

        .btn-row {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .map-box {
            border-radius: 8px;
            overflow: hidden;
            margin-top: 15px;
            border: 1px solid #ddd;
        }

        .map-box iframe {
            width: 100%;
            height: 250px;
            border: none;
        }

        .no-doctors {
            text-align: center;
            padding: 30px;
            color: #999;
            background: #f9f9f9;
            border-radius: 8px;
            margin-top: 15px;
        }

        .no-doctors i {
            font-size: 40px;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>
<body>

<!-- TOP NAVBAR -->
<nav class="simple-nav">
    <div class="logo">
        <i class="bi bi-heart-pulse"></i> Doc<span>Book</span>
    </div>
    <div class="nav-links">
        <a href="../index.html"><i class="bi bi-house"></i> Home</a>
        <a href="patient-dashboard.php"><i class="bi bi-grid"></i> Dashboard</a>
        <a href="symptom-checker.php"><i class="bi bi-cpu"></i> AI Checker</a>
        <a href="graphql-test.html"><i class="bi bi-braces"></i> GraphQL</a>
        <a href="verify.php"><i class="bi bi-shield-check"></i> Verify</a>
        <a href="../php/auth/logout.php" style="color:#dc3545;"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
</nav>


<div class="booking-page">

    <h2 style="margin-bottom:5px;"><i class="bi bi-calendar-plus"></i> Book an Appointment</h2>
    <p style="color:#999; font-size:13px; margin-bottom:20px;">Follow the steps to book your appointment</p>

    <!-- Step Bar -->
    <div class="step-bar">
        <div class="step-item active" id="step-ind-1">1. Specialty</div>
        <div class="step-item" id="step-ind-2">2. Doctor</div>
        <div class="step-item" id="step-ind-3">3. Date & Time</div>
        <div class="step-item" id="step-ind-4">4. Confirm</div>
    </div>

    <form id="bookingForm" action="../php/appointments/book.php" method="POST">

        <!-- ========== STEP 1: Specialty ========== -->
        <div class="step-section active" id="step-1">
            <div class="card">
                <div class="card-title">Select Specialty</div>

                <div class="form-group">
                    <label>Choose a medical specialty:</label>
                    <select name="specialty_id" id="specialtySelect" required>
                        <option value="">-- Select Specialty --</option>
                        <?php while($s = mysqli_fetch_assoc($specialties)): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> — <?= htmlspecialchars($s['description'] ?? '') ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Reason for Visit:</label>
                    <textarea name="reason" rows="3" placeholder="Describe your symptoms or reason..."></textarea>
                </div>

                <div class="btn-row">
                    <button type="button" class="btn btn-blue" onclick="goStep(2)">
                        Next → Select Doctor
                    </button>
                </div>
            </div>
        </div>

        <!-- ========== STEP 2: Doctor ========== -->
        <div class="step-section" id="step-2">
            <div class="card">
                <div class="card-title">Select a Doctor</div>

                <div class="doctor-list" id="doctorList">
                    <div class="no-doctors">
                        <i class="bi bi-person-x"></i>
                        <p>Please select a specialty first</p>
                    </div>
                </div>

                <input type="hidden" name="doctor_id" id="selectedDoctorId">

                <div class="btn-row">
                    <button type="button" class="btn btn-gray" onclick="goStep(1)">← Back</button>
                    <button type="button" class="btn btn-blue" onclick="goStep(3)">Next → Select Date</button>
                </div>
            </div>
        </div>

        <!-- ========== STEP 3: Date & Time ========== -->
        <div class="step-section" id="step-3">
            <div class="card">
                <div class="card-title">Select Date & Time</div>

                <div class="form-group">
                    <label>Appointment Date:</label>
                    <input type="text" name="appointment_date" id="appointmentDate" placeholder="Click to pick date" required readonly>
                </div>

                <div>
                    <label style="font-size:13px; font-weight:bold; color:#555;">Available Time Slots:</label>
                    <div class="time-slots" id="timeSlots">
                        <p style="color:#999; font-size:13px;">Select a date first</p>
                    </div>
                    <input type="hidden" name="appointment_time" id="selectedTime">
                </div>

                <!-- Google Map -->
                <div style="margin-top:20px;">
                    <label style="font-size:13px; font-weight:bold; color:#555;">
                        <i class="bi bi-geo-alt"></i> Doctor's Location:
                    </label>
                    <div class="map-box">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3651.9!2d90.3584!3d23.7461!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjPCsDQ0JzQ2LjAiTiA5MMKwMjEnMzAuMiJF!5e0!3m2!1sen!2sbd!4v1234567890"
                            allowfullscreen="" loading="lazy">
                        </iframe>
                    </div>
                </div>

                <div class="btn-row">
                    <button type="button" class="btn btn-gray" onclick="goStep(2)">← Back</button>
                    <button type="button" class="btn btn-blue" onclick="goStep(4)">Next → Review</button>
                </div>
            </div>
        </div>

        <!-- ========== STEP 4: Confirm ========== -->
        <div class="step-section" id="step-4">
            <div class="card">
                <div class="card-title">Confirm Your Appointment</div>

                <table class="summary-table">
                    <tr><td>Doctor</td><td id="sum-doctor">-</td></tr>
                    <tr><td>Specialty</td><td id="sum-specialty">-</td></tr>
                    <tr><td>Date</td><td id="sum-date">-</td></tr>
                    <tr><td>Time</td><td id="sum-time">-</td></tr>
                    <tr><td>Fee</td><td id="sum-fee">-</td></tr>
                </table>

                <div class="btn-row">
                    <button type="button" class="btn btn-gray" onclick="goStep(3)">← Back</button>
                    <button type="submit" class="btn btn-green" style="font-size:14px; padding:10px 30px;">
                        ✓ Confirm Booking
                    </button>
                </div>
            </div>
        </div>

    </form>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Store selected data
let selectedDoctor = null;
let datepickerReady = false;

// ================================
// STEP NAVIGATION
// ================================
function goStep(step) {
    // Validation
    if (step === 2) {
        let spec = document.getElementById('specialtySelect').value;
        if (!spec) { alert('Please select a specialty!'); return; }
        loadDoctors(spec);
    }

    if (step === 3) {
        if (!document.getElementById('selectedDoctorId').value) {
            alert('Please select a doctor!');
            return;
        }
        if (!datepickerReady) {
            initDatepicker();
            datepickerReady = true;
        }
    }

    if (step === 4) {
        let date = document.getElementById('appointmentDate').value;
        let time = document.getElementById('selectedTime').value;
        if (!date) { alert('Please select a date!'); return; }
        if (!time) { alert('Please select a time slot!'); return; }
        updateSummary();
    }

    // Hide all steps
    document.querySelectorAll('.step-section').forEach(s => s.classList.remove('active'));
    document.getElementById(`step-${step}`).classList.add('active');

    // Update indicators
    for (let i = 1; i <= 4; i++) {
        let ind = document.getElementById(`step-ind-${i}`);
        ind.className = 'step-item';
        if (i < step) ind.classList.add('done');
        if (i === step) ind.classList.add('active');
    }

    // Scroll to top
    window.scrollTo(0, 0);
}

// ================================
// LOAD DOCTORS
// ================================
function loadDoctors(specialtyId) {
    let list = document.getElementById('doctorList');
    list.innerHTML = '<p style="text-align:center; color:#999; padding:20px;">Loading doctors...</p>';

    fetch(`../php/api/get-doctors.php?specialty_id=${specialtyId}`)
        .then(res => res.json())
        .then(doctors => {
            list.innerHTML = '';

            if (doctors.length === 0) {
                list.innerHTML = '<div class="no-doctors"><i class="bi bi-person-x"></i><p>No doctors available for this specialty</p></div>';
                return;
            }

            doctors.forEach(doc => {
                let div = document.createElement('div');
                div.className = 'doctor-option';
                div.onclick = function() { selectDoc(doc, this); };
                div.innerHTML = `
                    <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(doc.full_name)}&background=1a73e8&color=fff">
                    <div>
                        <h4>Dr. ${doc.full_name}</h4>
                        <p>${doc.specialty}</p>
                        <p>${doc.experience_years || 0} yrs experience</p>
                        <span class="doc-fee">৳${doc.consultation_fee || 0}</span>
                    </div>`;
                list.appendChild(div);
            });
        })
        .catch(err => {
            list.innerHTML = '<div class="no-doctors"><i class="bi bi-exclamation-triangle"></i><p>Error loading doctors. Try again.</p></div>';
            console.error(err);
        });
}

// ================================
// SELECT DOCTOR
// ================================
function selectDoc(doc, el) {
    selectedDoctor = doc;
    document.getElementById('selectedDoctorId').value = doc.id;

    document.querySelectorAll('.doctor-option').forEach(d => d.classList.remove('selected'));
    el.classList.add('selected');
}

// ================================
// DATEPICKER
// ================================
function initDatepicker() {
    flatpickr("#appointmentDate", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: [
            function(date) {
                return (date.getDay() === 5); // Friday off
            }
        ],
        onChange: function(selectedDates, dateStr) {
            loadTimeSlots(dateStr);
        }
    });
}

// ================================
// LOAD TIME SLOTS
// ================================
function loadTimeSlots(date) {
    let doctorId = document.getElementById('selectedDoctorId').value;
    let container = document.getElementById('timeSlots');
    container.innerHTML = '<p style="color:#999;">Loading...</p>';

    fetch(`../php/api/get-timeslots.php?doctor_id=${doctorId}&date=${date}`)
        .then(res => res.json())
        .then(slots => {
            container.innerHTML = '';
            if (slots.length === 0) {
                container.innerHTML = '<p style="color:#999;">No slots available</p>';
                return;
            }
            slots.forEach(slot => {
                let div = document.createElement('div');
                div.className = 'time-slot' + (slot.booked ? ' booked' : '');
                div.textContent = slot.time;
                if (!slot.booked) {
                    div.onclick = function() { pickTime(slot.time, this); };
                }
                container.appendChild(div);
            });
        })
        .catch(() => {
            container.innerHTML = '<p style="color:#999;">Error loading slots</p>';
        });
}

// ================================
// SELECT TIME
// ================================
function pickTime(time, el) {
    document.getElementById('selectedTime').value = time;
    document.querySelectorAll('.time-slot:not(.booked)').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
}

// ================================
// UPDATE SUMMARY
// ================================
function updateSummary() {
    let specSelect = document.getElementById('specialtySelect');
    let specText = specSelect.options[specSelect.selectedIndex].text;

    document.getElementById('sum-doctor').textContent = selectedDoctor ? 'Dr. ' + selectedDoctor.full_name : '-';
    document.getElementById('sum-specialty').textContent = specText || '-';
    document.getElementById('sum-date').textContent = document.getElementById('appointmentDate').value;
    document.getElementById('sum-time').textContent = document.getElementById('selectedTime').value;
    document.getElementById('sum-fee').textContent = selectedDoctor ? '৳' + selectedDoctor.consultation_fee : '-';
}
</script>

</body>
</html>