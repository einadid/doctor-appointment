<?php
header('Content-Type: application/json');
include '../config/database.php';

$result = mysqli_query($conn, "SELECT * FROM specialties ORDER BY name");
$specialties = [];
while($row = mysqli_fetch_assoc($result)) {
    $specialties[] = $row;
}
echo json_encode($specialties);
?>