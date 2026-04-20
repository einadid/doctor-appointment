<?php
header('Content-Type: application/json');
require_once '../config/session_check.php';

if (!isLoggedIn()) {
    echo json_encode([
        'logged_in' => false
    ]);
    exit;
}

echo json_encode([
    'logged_in' => true,
    'data'      => getSession()
]);
?>