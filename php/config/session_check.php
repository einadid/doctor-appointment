<?php
session_start();

// Login আছে কিনা check
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true;
}

// Role check
function isRole($role) {
    return isset($_SESSION['role']) && 
           $_SESSION['role'] === $role;
}

// Login না থাকলে redirect
function requireLogin() {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'message' => 'Please login first!'
        ]);
        exit;
    }
}

// Role না মিললে redirect
function requireRole($role) {
    requireLogin();
    if (!isRole($role)) {
        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'error',
            'message' => 'Access denied!'
        ]);
        exit;
    }
}

// Session info নাও
function getSession() {
    return [
        'user_id'   => $_SESSION['user_id']   ?? null,
        'username'  => $_SESSION['username']  ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
        'email'     => $_SESSION['email']     ?? null,
        'role'      => $_SESSION['role']      ?? null,
        'image'     => $_SESSION['image']     ?? 'default.png',
    ];
}
?>