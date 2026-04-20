CREATE DATABASE IF NOT EXISTS doctor_appointment;
USE doctor_appointment;

-- =========================
-- 1. USERS TABLE
-- =========================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('admin', 'doctor', 'patient') NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    phone_verified TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================
-- 2. SPECIALTIES TABLE
-- =========================
CREATE TABLE specialties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL
);

-- =========================
-- 3. PATIENTS TABLE
-- =========================
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    patient_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    date_of_birth DATE NOT NULL,
    visits INT DEFAULT 0,
    address TEXT NULL,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- 4. DOCTORS TABLE
-- =========================
CREATE TABLE doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    specialty_id INT NOT NULL,
    doctor_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    qualification VARCHAR(150) NULL,
    experience_years INT DEFAULT 0,
    chamber_address TEXT NULL,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    bio TEXT NULL,
    image VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE RESTRICT
);

-- =========================
-- 5. ADMINS TABLE
-- =========================
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    admin_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- 6. DOCTOR SCHEDULES TABLE
-- =========================
CREATE TABLE doctor_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    day_of_week ENUM('Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    max_patients INT DEFAULT 10,
    status ENUM('active', 'inactive') DEFAULT 'active',
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- =========================
-- 7. APPOINTMENTS TABLE
-- =========================
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_no VARCHAR(20) NOT NULL UNIQUE,
    patient_id INT NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT NULL,
    status ENUM('pending', 'approved', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
);

-- =========================
-- 8. POSTS TABLE
-- =========================
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT NOT NULL,
    media_type ENUM('none', 'image', 'video', 'audio', 'youtube') DEFAULT 'none',
    media_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- 9. COMMENTS TABLE
-- =========================
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- 10. LIKE/DISLIKE TABLE
-- =========================
CREATE TABLE post_reactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction ENUM('like', 'dislike') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_post_user (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- 11. RATINGS TABLE
-- =========================
CREATE TABLE ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    rating TINYINT NOT NULL,
    review TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_doctor_patient_rating (doctor_id, patient_id),
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- =========================
-- 12. UPLOADS TABLE
-- =========================
CREATE TABLE uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- 13. VERIFICATIONS TABLE
-- =========================
CREATE TABLE verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    verify_type ENUM('email', 'phone') NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    is_used TINYINT(1) DEFAULT 0,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =========================
-- SAMPLE SPECIALTIES
-- =========================
INSERT INTO specialties (name, description) VALUES
('Cardiology', 'Heart specialist'),
('Dermatology', 'Skin specialist'),
('Neurology', 'Brain and nerve specialist'),
('Orthopedics', 'Bone specialist'),
('Medicine', 'General physician');


-- Admin user তৈরি করো
INSERT INTO users (role, username, email, phone, password)
VALUES (
    'admin',
    'admin',
    'admin@docbook.com',
    '01700000000',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- Password হলো: password

-- Admin table এ insert করো
INSERT INTO admins (user_id, admin_code, full_name)
VALUES (
    LAST_INSERT_ID(),
    'ADM-001',
    'System Admin'
);

-- Sample Posts
INSERT INTO posts (user_id, title, content, media_type, media_url) VALUES
(1, 'Stay Hydrated', 'Drinking 8 glasses of water daily keeps you healthy.', 'none', NULL),
(1, 'Benefits of Walking', 'Walking 30 minutes daily can reduce heart disease risk.', 'youtube', 'dQw4w9WgXcQ'),
(1, 'Healthy Diet Tips', 'Eat more fruits and vegetables for a balanced diet.', 'none', NULL);

SELECT d.*, s.name as specialty 
FROM doctors d 
JOIN specialties s ON d.specialty_id = s.id;


-- প্রথমে doctor users তৈরি করো
INSERT INTO users (role, username, email, phone, password) VALUES
('doctor', 'dr_ahmed', 'ahmed@docbook.com', '01711111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('doctor', 'dr_fatima', 'fatima@docbook.com', '01722222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('doctor', 'dr_imran', 'imran@docbook.com', '01733333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('doctor', 'dr_nadia', 'nadia@docbook.com', '01744444444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('doctor', 'dr_karim', 'karim@docbook.com', '01755555555', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Password: password (all doctors)

-- এখন doctors table এ insert করো
-- NOTE: user_id গুলো তোমার database অনুযায়ী different হতে পারে
-- তাই আমরা LAST_INSERT_ID() ব্যবহার করতে পারবো না
-- নিচের query চালাও:

INSERT INTO doctors (user_id, specialty_id, doctor_code, full_name, gender, qualification, experience_years, consultation_fee, bio)
SELECT u.id, 1, CONCAT('DOC-', UPPER(SUBSTRING(MD5(u.id), 1, 6))), 'Ahmed Khan', 'male', 'MBBS, FCPS (Cardiology)', 12, 800, 'Expert cardiologist with 12 years experience'
FROM users u WHERE u.username = 'dr_ahmed';

INSERT INTO doctors (user_id, specialty_id, doctor_code, full_name, gender, qualification, experience_years, consultation_fee, bio)
SELECT u.id, 3, CONCAT('DOC-', UPPER(SUBSTRING(MD5(u.id), 1, 6))), 'Fatima Rahman', 'female', 'MBBS, MD (Neurology)', 8, 1000, 'Specialist in brain and nerve disorders'
FROM users u WHERE u.username = 'dr_fatima';

INSERT INTO doctors (user_id, specialty_id, doctor_code, full_name, gender, qualification, experience_years, consultation_fee, bio)
SELECT u.id, 4, CONCAT('DOC-', UPPER(SUBSTRING(MD5(u.id), 1, 6))), 'Imran Hossain', 'male', 'MBBS, MS (Orthopedics)', 15, 700, 'Bone and joint specialist'
FROM users u WHERE u.username = 'dr_imran';

INSERT INTO doctors (user_id, specialty_id, doctor_code, full_name, gender, qualification, experience_years, consultation_fee, bio)
SELECT u.id, 2, CONCAT('DOC-', UPPER(SUBSTRING(MD5(u.id), 1, 6))), 'Nadia Islam', 'female', 'MBBS, DDV (Dermatology)', 6, 600, 'Skin care and treatment specialist'
FROM users u WHERE u.username = 'dr_nadia';

INSERT INTO doctors (user_id, specialty_id, doctor_code, full_name, gender, qualification, experience_years, consultation_fee, bio)
SELECT u.id, 5, CONCAT('DOC-', UPPER(SUBSTRING(MD5(u.id), 1, 6))), 'Karim Uddin', 'male', 'MBBS, MD (Medicine)', 20, 500, 'General physician with vast experience'
FROM users u WHERE u.username = 'dr_karim';


-- Doctor schedules (doctor_id তোমার database অনুযায়ী adjust করো)
-- আগে doctor ids বের করো:
SELECT id, full_name FROM doctors;

-- ধরো doctor ids হলো 1,2,3,4,5 — তাহলে:
INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, max_patients) VALUES
(1, 'Saturday', '09:00:00', '14:00:00', 15),
(1, 'Monday', '09:00:00', '14:00:00', 15),
(1, 'Wednesday', '09:00:00', '14:00:00', 15),
(2, 'Sunday', '10:00:00', '16:00:00', 12),
(2, 'Tuesday', '10:00:00', '16:00:00', 12),
(2, 'Thursday', '10:00:00', '16:00:00', 12),
(3, 'Saturday', '14:00:00', '20:00:00', 10),
(3, 'Monday', '14:00:00', '20:00:00', 10),
(4, 'Sunday', '09:00:00', '13:00:00', 8),
(4, 'Wednesday', '09:00:00', '13:00:00', 8),
(5, 'Saturday', '08:00:00', '15:00:00', 20),
(5, 'Sunday', '08:00:00', '15:00:00', 20),
(5, 'Tuesday', '08:00:00', '15:00:00', 20);