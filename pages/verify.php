<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Account - DocBook</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .verify-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            padding: 20px;
        }

        .verify-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }

        .verify-card h2 {
            font-size: 22px;
            color: #1a73e8;
            margin-bottom: 8px;
        }

        .verify-card p {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 25px;
        }

        .verify-type-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .verify-tab {
            flex: 1;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: 500;
            background: white;
        }

        .verify-tab.active {
            border-color: #1a73e8;
            background: #e8f0fe;
            color: #1a73e8;
        }

        .otp-input-group {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }

        .otp-input-group input {
            flex: 1;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 8px;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
        }

        .otp-input-group input:focus {
            border-color: #1a73e8;
        }

        .btn-send-otp {
            background: #1a73e8;
            color: white;
            border: none;
            padding: 11px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .btn-send-otp:hover {
            background: #0d47a1;
        }

        .btn-send-otp:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .btn-verify {
            width: 100%;
            background: #198754;
            color: white;
            border: none;
            padding: 13px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }

        .btn-verify:hover {
            background: #0d6e3f;
        }

        .status-msg {
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 12px;
            display: none;
        }

        .status-msg.success {
            background: #d4edda;
            color: #155724;
        }

        .status-msg.error {
            background: #f8d7da;
            color: #721c24;
        }

        .countdown {
            font-size: 12px;
            color: #6c757d;
            margin-top: 8px;
        }

        .otp-display {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 13px;
            color: #856404;
            margin-top: 10px;
            display: none;
        }
    </style>
</head>
<body>

<div class="verify-wrapper">
    <div class="verify-card">
        <h2>
            <i class="bi bi-shield-check"></i>
            Verify Your Account
        </h2>
        <p>Verify your email and phone number to complete registration.</p>

        <!-- Type Selection -->
        <div class="verify-type-tabs">
            <div class="verify-tab active"
                 onclick="selectType('email')"
                 id="tab-email">
                <i class="bi bi-envelope"></i> Email
            </div>
            <div class="verify-tab"
                 onclick="selectType('phone')"
                 id="tab-phone">
                <i class="bi bi-phone"></i> Phone
            </div>
        </div>

        <!-- Input + Send OTP -->
        <div style="display:flex; gap:10px; align-items:flex-end;">
            <div style="flex:1;">
                <label style="font-size:13px; font-weight:500; display:block; margin-bottom:5px;"
                       id="input-label">Email Address</label>
                <input type="text"
                       id="verifyValue"
                       placeholder="Enter your email">
            </div>
            <button class="btn-send-otp"
                    id="sendOtpBtn"
                    onclick="sendOTP()">
                Send OTP
            </button>
        </div>

        <!-- Dev Mode OTP Display -->
        <div class="otp-display" id="otpDisplay">
            🔑 Your OTP (Dev Mode): <strong id="otpCode"></strong>
        </div>

        <div class="countdown" id="countdown"></div>

        <!-- OTP Input -->
        <div style="margin-top:20px;">
            <label style="font-size:13px; font-weight:500; display:block; margin-bottom:8px;">
                Enter OTP Code
            </label>
            <input type="text"
                   id="otpInput"
                   placeholder="Enter 6-digit OTP"
                   maxlength="6"
                   style="text-align:center; font-size:24px;
                          font-weight:700; letter-spacing:8px;
                          padding:12px; border:2px solid #ddd;
                          border-radius:10px; width:100%;">
        </div>

        <button class="btn-verify" onclick="verifyOTP()">
            <i class="bi bi-check-circle"></i> Verify Now
        </button>

        <div class="status-msg" id="statusMsg"></div>

        <div style="text-align:center; margin-top:20px;">
            <a href="patient-dashboard.php"
               style="color:#1a73e8; font-size:13px;">
                Skip for now →
            </a>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<script>
let currentType = 'email';
let countdownTimer = null;

// GSAP
gsap.from('.verify-card', {
    duration: 0.6,
    y: 40,
    opacity: 0,
    ease: "power3.out"
});

// ================================
// Select Verify Type
// ================================
function selectType(type) {
    currentType = type;

    document.querySelectorAll('.verify-tab').forEach(t => {
        t.classList.remove('active');
    });
    document.getElementById(`tab-${type}`).classList.add('active');

    let label = document.getElementById('input-label');
    let input = document.getElementById('verifyValue');

    if(type === 'email') {
        label.textContent = 'Email Address';
        input.placeholder = 'Enter your email';
        input.type = 'email';
    } else {
        label.textContent = 'Phone Number';
        input.placeholder = 'Enter your phone number';
        input.type = 'tel';
    }
}

// ================================
// Send OTP
// ================================
function sendOTP() {
    let value = document.getElementById('verifyValue').value.trim();
    if(!value) {
        showMsg('Please enter your ' + currentType, 'error');
        return;
    }

    let btn = document.getElementById('sendOtpBtn');
    btn.disabled = true;
    btn.textContent = 'Sending...';

    fetch('../php/auth/send-otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            type: currentType,
            value: value
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            showMsg('OTP sent successfully!', 'success');

            // Dev mode: Show OTP
            document.getElementById('otpDisplay').style.display = 'block';
            document.getElementById('otpCode').textContent = data.otp;

            // Countdown 60 seconds
            startCountdown(60);
        } else {
            showMsg('Failed to send OTP!', 'error');
            btn.disabled = false;
            btn.textContent = 'Send OTP';
        }
    })
    .catch(() => {
        showMsg('Network error!', 'error');
        btn.disabled = false;
        btn.textContent = 'Send OTP';
    });
}

// ================================
// Verify OTP
// ================================
function verifyOTP() {
    let otp = document.getElementById('otpInput').value.trim();
    if(otp.length !== 6) {
        showMsg('Please enter 6-digit OTP', 'error');
        return;
    }

    fetch('../php/auth/verify-otp.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            type: currentType,
            otp: otp
        })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            showMsg('✅ ' + data.message, 'success');
            document.getElementById('otpDisplay').style.display = 'none';

            // GSAP success animation
            gsap.to('.verify-card', {
                duration: 0.3,
                scale: 1.02,
                yoyo: true,
                repeat: 1
            });

            setTimeout(() => {
                if(currentType === 'email') {
                    selectType('phone');
                } else {
                    window.location.href = 'patient-dashboard.php';
                }
            }, 1500);
        } else {
            showMsg('❌ ' + data.message, 'error');

            // Shake animation
            gsap.to('#otpInput', {
                duration: 0.1,
                x: 10,
                yoyo: true,
                repeat: 5
            });
        }
    });
}

// ================================
// Show Message
// ================================
function showMsg(msg, type) {
    let el = document.getElementById('statusMsg');
    el.textContent = msg;
    el.className = `status-msg ${type}`;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 3000);
}

// ================================
// Countdown Timer
// ================================
function startCountdown(seconds) {
    let el = document.getElementById('countdown');
    clearInterval(countdownTimer);

    countdownTimer = setInterval(() => {
        el.textContent = `Resend OTP in ${seconds}s`;
        seconds--;

        if(seconds < 0) {
            clearInterval(countdownTimer);
            el.textContent = '';
            let btn = document.getElementById('sendOtpBtn');
            btn.disabled = false;
            btn.textContent = 'Resend OTP';
        }
    }, 1000);
}

// Auto-format OTP input
document.getElementById('otpInput').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
    if(this.value.length === 6) {
        verifyOTP();
    }
});
</script>

</body>
</html>