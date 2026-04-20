<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.html");
    exit();
}
include '../php/config/database.php';

// Specialties load করো
$specialties = mysqli_query($conn, "SELECT * FROM specialties");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment - DocBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .booking-wrapper {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .booking-card {
            background: white;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .booking-card h2 {
            font-size: 24px;
            color: #1a73e8;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step-indicator {
            display: flex;
            gap: 0;
            margin-bottom: 30px;
        }

        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            background: #f0f4f8;
            font-size: 13px;
            font-weight: 500;
            color: #6c757d;
            position: relative;
        }

        .step.active {
            background: #1a73e8;
            color: white;
        }

        .step.done {
            background: #198754;
            color: white;
        }

        .step:first-child {
            border-radius: 8px 0 0 8px;
        }

        .step:last-child {
            border-radius: 0 8px 8px 0;
        }

        .doctor-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .doctor-select-card {
            border: 2px solid #eee;
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .doctor-select-card:hover {
            border-color: #1a73e8;
            background: #e8f0fe;
        }

        .doctor-select-card.selected {
            border-color: #1a73e8;
            background: #e8f0fe;
        }

        .doctor-select-card img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .doctor-select-card .doc-info h4 {
            font-size: 14px;
            font-weight: 600;
        }

        .doctor-select-card .doc-info p {
            font-size: 12px;
            color: #6c757d;
        }

        .time-slots {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .time-slot {
            padding: 8px 16px;
            border: 1.5px solid #ddd;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .time-slot:hover,
        .time-slot.selected {
            background: #1a73e8;
            color: white;
            border-color: #1a73e8;
        }

        .time-slot.booked {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
            cursor: not-allowed;
        }

        .map-container {
            border-radius: 12px;
            overflow: hidden;
            margin-top: 20px;
            height: 300px;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .summary-box {
            background: #f0f4f8;
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .summary-box .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
            font-size: 14px;
        }

        .summary-box .summary-row:last-child {
            border-bottom: none;
            font-weight: 600;
            color: #1a73e8;
        }

        .btn-next {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .btn-next:hover {
            background: #0d47a1;
            transform: translateY(-2px);
        }

        .btn-back {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 20px;
            margin-right: 10px;
            transition: all 0.3s;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
        }
    </style>
</head>
<body>

<div class="booking-wrapper">
    <div class="booking-card">
        <h2>
            <i class="bi bi-calendar-plus"></i>
            Book an Appointment
        </h2>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" id="step1-indicator">1. Specialty</div>
            <div class="step" id="step2-indicator">2. Doctor</div>
            <div class="step" id="step3-indicator">3. Date & Time</div>
            <div class="step" id="step4-indicator">4. Confirm</div>
        </div>

        <form id="bookingForm" 
              action="../php/appointments/book.php" 
              method="POST">

            <!-- STEP 1: Specialty Selection -->
            <div class="form-section active" id="section1">
                <div class="form-group">
                    <label>Select Specialty</label>
                    <select name="specialty_id" id="specialtySelect" required>
                        <option value="">-- Choose Specialty --</option>
                        <?php while($s = mysqli_fetch_assoc($specialties)): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['name']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-top:15px;">
                    <label>Reason for Visit</label>
                    <textarea name="reason" rows="4" 
                              placeholder="Describe your symptoms..." 
                              style="width:100%; padding:10px; border-radius:8px; 
                                     border:1.5px solid #ddd; font-family:inherit;"></textarea>
                </div>

                <button type="button" class="btn-next" onclick="goToStep(2)">
                    Next <i class="bi bi-arrow-right"></i>
                </button>
            </div>

            <!-- STEP 2: Doctor Selection -->
            <div class="form-section" id="section2">
                <label style="font-weight:500; font-size:14px;">
                    Select a Doctor
                </label>
                <div class="doctor-grid" id="doctorGrid">
                    <!-- Doctors will load via AJAX -->
                    <p style="color:#6c757d;">
                        Please select a specialty first.
                    </p>
                </div>
                <input type="hidden" name="doctor_id" id="selectedDoctorId">

                <div>
                    <button type="button" class="btn-back" onclick="goToStep(1)">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-next" onclick="goToStep(3)">
                        Next <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 3: Date & Time -->
            <div class="form-section" id="section3">
                <div class="form-group">
                    <label>Select Date</label>
                    <input type="text" name="appointment_date" 
                           id="appointmentDate" 
                           placeholder="Pick a date" required>
                </div>

                <div style="margin-top:15px;">
                    <label style="font-weight:500; font-size:14px;">
                        Available Time Slots
                    </label>
                    <div class="time-slots" id="timeSlots">
                        <!-- Time slots load via AJAX -->
                    </div>
                    <input type="hidden" name="appointment_time" id="selectedTime">
                </div>

                <!-- Google Maps (Feature #10) -->
                <div style="margin-top:20px;">
                    <label style="font-weight:500; font-size:14px;">
                        <i class="bi bi-geo-alt"></i> Doctor's Location
                    </label>
                    <div class="map-container">
                        <iframe
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3651.9018474949!2d90.3584!3d23.7461!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjPCsDQ0JzQ2LjAiTiA5MMKwMjEnMzAuMiJF!5e0!3m2!1sen!2sbd!4v1234567890"
                            allowfullscreen=""
                            loading="lazy">
                        </iframe>
                    </div>
                </div>

                <div>
                    <button type="button" class="btn-back" onclick="goToStep(2)">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-next" onclick="goToStep(4)">
                        Next <i class="bi bi-arrow-right"></i>
                    </button>
                </div>
            </div>

            <!-- STEP 4: Confirm -->
            <div class="form-section" id="section4">
                <h3 style="font-size:16px; margin-bottom:15px;">
                    Appointment Summary
                </h3>

                <div class="summary-box">
                    <div class="summary-row">
                        <span>Doctor</span>
                        <span id="summary-doctor">-</span>
                    </div>
                    <div class="summary-row">
                        <span>Specialty</span>
                        <span id="summary-specialty">-</span>
                    </div>
                    <div class="summary-row">
                        <span>Date</span>
                        <span id="summary-date">-</span>
                    </div>
                    <div class="summary-row">
                        <span>Time</span>
                        <span id="summary-time">-</span>
                    </div>
                    <div class="summary-row">
                        <span>Consultation Fee</span>
                        <span id="summary-fee">-</span>
                    </div>
                </div>

                <div>
                    <button type="button" class="btn-back" onclick="goToStep(3)">
                        <i class="bi bi-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-next">
                        <i class="bi bi-check-circle"></i> Confirm Booking
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

<script>
// Selected data store
let selectedDoctor = null;

// ================================
// Step Navigation
// ================================
function goToStep(step) {
    // Validation
    if(step === 2) {
        let specialty = document.getElementById('specialtySelect').value;
        if(!specialty) {
            alert('Please select a specialty!');
            return;
        }
        loadDoctors(specialty);
    }

    if(step === 3) {
        if(!document.getElementById('selectedDoctorId').value) {
            alert('Please select a doctor!');
            return;
        }
        initDatepicker();
    }

    if(step === 4) {
        let date = document.getElementById('appointmentDate').value;
        let time = document.getElementById('selectedTime').value;
        if(!date) { alert('Please select a date!'); return; }
        if(!time) { alert('Please select a time slot!'); return; }
        updateSummary();
    }

    // Hide all sections
    document.querySelectorAll('.form-section').forEach(s => {
        s.classList.remove('active');
    });

    // Show target section
    document.getElementById(`section${step}`).classList.add('active');

    // Update step indicators
    document.querySelectorAll('.step').forEach((s, index) => {
        s.classList.remove('active', 'done');
        if(index + 1 < step) s.classList.add('done');
        if(index + 1 === step) s.classList.add('active');
    });

    // GSAP animation
    gsap.from(`#section${step}`, {
        duration: 0.4,
        x: 30,
        opacity: 0,
        ease: "power2.out"
    });
}

// ================================
// Load Doctors by Specialty (AJAX)
// ================================
function loadDoctors(specialtyId) {
    fetch(`../php/api/get-doctors.php?specialty_id=${specialtyId}`)
        .then(res => res.json())
        .then(doctors => {
            let grid = document.getElementById('doctorGrid');
            grid.innerHTML = '';

            if(doctors.length === 0) {
                grid.innerHTML = '<p style="color:#6c757d;">No doctors available.</p>';
                return;
            }

            doctors.forEach(doc => {
                grid.innerHTML += `
                    <div class="doctor-select-card" 
                         onclick="selectDoctor(${doc.id}, '${doc.full_name}', 
                                  '${doc.specialty}', ${doc.consultation_fee})">
                        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(doc.full_name)}&background=1a73e8&color=fff">
                        <div class="doc-info">
                            <h4>${doc.full_name}</h4>
                            <p>${doc.specialty}</p>
                            <p style="color:#198754; font-weight:600;">
                                ৳${doc.consultation_fee}
                            </p>
                        </div>
                    </div>`;
            });
        });
}

// ================================
// Select Doctor
// ================================
function selectDoctor(id, name, specialty, fee) {
    selectedDoctor = { id, name, specialty, fee };
    document.getElementById('selectedDoctorId').value = id;

    // Visual select
    document.querySelectorAll('.doctor-select-card').forEach(c => {
        c.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}

// ================================
// Datepicker Initialize (Feature #14)
// ================================
function initDatepicker() {
    flatpickr("#appointmentDate", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: [
            function(date) {
                return (date.getDay() === 5); // Friday বন্ধ
            }
        ],
        onChange: function(selectedDates, dateStr) {
            loadTimeSlots(dateStr);
        }
    });
}

// ================================
// Load Time Slots via AJAX
// ================================
function loadTimeSlots(date) {
    let doctorId = document.getElementById('selectedDoctorId').value;
    fetch(`../php/api/get-timeslots.php?doctor_id=${doctorId}&date=${date}`)
        .then(res => res.json())
        .then(slots => {
            let container = document.getElementById('timeSlots');
            container.innerHTML = '';
            slots.forEach(slot => {
                let cls = slot.booked ? 'booked' : '';
                container.innerHTML += `
                    <div class="time-slot ${cls}" 
                         onclick="${!slot.booked ? `selectTime('${slot.time}')` : ''}">
                        ${slot.time}
                    </div>`;
            });
        });
}

// ================================
// Select Time Slot
// ================================
function selectTime(time) {
    document.getElementById('selectedTime').value = time;
    document.querySelectorAll('.time-slot:not(.booked)').forEach(s => {
        s.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}

// ================================
// Update Summary
// ================================
function updateSummary() {
    document.getElementById('summary-doctor').textContent = 
        selectedDoctor?.name || '-';
    document.getElementById('summary-specialty').textContent = 
        selectedDoctor?.specialty || '-';
    document.getElementById('summary-date').textContent = 
        document.getElementById('appointmentDate').value;
    document.getElementById('summary-time').textContent = 
        document.getElementById('selectedTime').value;
    document.getElementById('summary-fee').textContent = 
        '৳' + (selectedDoctor?.fee || 0);
}
</script>

</body>
</html>