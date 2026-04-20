<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../vendor/autoload.php';
include '../config/database.php';

use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\InputObjectType;

// ================================
// Doctor Type
// ================================
$doctorType = new ObjectType([
    'name' => 'Doctor',
    'fields' => [
        'id' => Type::int(),
        'full_name' => Type::string(),
        'specialty' => Type::string(),
        'consultation_fee' => Type::float(),
        'experience_years' => Type::int(),
        'bio' => Type::string(),
    ]
]);

// ================================
// Appointment Type
// ================================
$appointmentType = new ObjectType([
    'name' => 'Appointment',
    'fields' => [
        'id' => Type::int(),
        'appointment_no' => Type::string(),
        'patient_name' => Type::string(),
        'doctor_name' => Type::string(),
        'appointment_date' => Type::string(),
        'appointment_time' => Type::string(),
        'status' => Type::string(),
    ]
]);

// ================================
// Query Type
// ================================
$queryType = new ObjectType([
    'name' => 'Query',
    'fields' => [

        // Get all doctors
        'doctors' => [
            'type' => Type::listOf($doctorType),
            'args' => [
                'specialty_id' => Type::int(),
            ],
            'resolve' => function($root, $args) use ($conn) {
                if(isset($args['specialty_id'])) {
                    $id = (int)$args['specialty_id'];
                    $sql = "SELECT d.id, d.full_name, d.consultation_fee,
                                   d.experience_years, d.bio,
                                   s.name as specialty
                            FROM doctors d
                            JOIN specialties s ON d.specialty_id = s.id
                            WHERE d.specialty_id = $id";
                } else {
                    $sql = "SELECT d.id, d.full_name, d.consultation_fee,
                                   d.experience_years, d.bio,
                                   s.name as specialty
                            FROM doctors d
                            JOIN specialties s ON d.specialty_id = s.id";
                }
                $result = mysqli_query($conn, $sql);
                $doctors = [];
                while($row = mysqli_fetch_assoc($result)) {
                    $doctors[] = $row;
                }
                return $doctors;
            }
        ],

        // Get single doctor
        'doctor' => [
            'type' => $doctorType,
            'args' => [
                'id' => Type::nonNull(Type::int()),
            ],
            'resolve' => function($root, $args) use ($conn) {
                $id = (int)$args['id'];
                $result = mysqli_query($conn,
                    "SELECT d.*, s.name as specialty
                     FROM doctors d
                     JOIN specialties s ON d.specialty_id = s.id
                     WHERE d.id = $id");
                return mysqli_fetch_assoc($result);
            }
        ],

        // Get appointments
        'appointments' => [
            'type' => Type::listOf($appointmentType),
            'args' => [
                'status' => Type::string(),
            ],
            'resolve' => function($root, $args) use ($conn) {
                $where = '';
                if(isset($args['status'])) {
                    $status = mysqli_real_escape_string($conn, $args['status']);
                    $where = "WHERE a.status = '$status'";
                }
                $sql = "SELECT a.id, a.appointment_no,
                               a.appointment_date, a.appointment_time,
                               a.status,
                               p.full_name as patient_name,
                               d.full_name as doctor_name
                        FROM appointments a
                        JOIN patients p ON a.patient_id = p.id
                        JOIN doctors d ON a.doctor_id = d.id
                        $where
                        ORDER BY a.created_at DESC
                        LIMIT 20";
                $result = mysqli_query($conn, $sql);
                $appts = [];
                while($row = mysqli_fetch_assoc($result)) {
                    $appts[] = $row;
                }
                return $appts;
            }
        ],
    ]
]);

// ================================
// Schema তৈরি করো
// ================================
$schema = new Schema([
    'query' => $queryType
]);

// ================================
// Request Handle করো
// ================================
try {
    $input = json_decode(file_get_contents('php://input'), true);
    $query = $input['query'] ?? '{ doctors { id full_name specialty } }';
    $variables = $input['variables'] ?? null;

    $result = GraphQL::executeQuery($schema, $query, null, null, $variables);
    echo json_encode($result->toArray());

} catch(\Exception $e) {
    echo json_encode([
        'errors' => [['message' => $e->getMessage()]]
    ]);
}
?>