<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$sql = "SELECT id, name FROM specialties ORDER BY name ASC";
$result = mysqli_query($conn, $sql);

$specialties = [];
while ($row = mysqli_fetch_assoc($result)) {
    $specialties[] = $row;
}

echo json_encode([
    'status' => 'success',
    'data' => $specialties
]);

mysqli_close($conn);
?>