<?php
session_start();

// সব session destroy করো
$_SESSION = [];
session_destroy();

// Login page এ redirect
header('Location: ../../pages/login.html');
exit;
?>