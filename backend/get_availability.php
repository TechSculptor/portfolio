<?php
/**
 * AJAX Endpoint for Doctor Availability
 * Returns JSON with available/booked slots for a doctor on a specific date
 */

header('Content-Type: application/json');
require_once "config/db.php";

// Get parameters
$doctorId = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$response = [
    'success' => false,
    'doctor' => null,
    'date' => $date,
    'slots' => []
];

if ($doctorId > 0) {
    // Get doctor info
    $stmt = $conn->prepare("SELECT * FROM DOCTOR WHERE doctor_id = :doctor_id");
    $stmt->execute(['doctor_id' => $doctorId]);
    $doctor = $stmt->fetch();

    if ($doctor) {
        $response['doctor'] = [
            'id' => $doctor['doctor_id'],
            'name' => 'Dr. ' . $doctor['first_name'] . ' ' . $doctor['last_name'],
            'specialty' => $doctor['specialty']
        ];

        // Get booked slots for this doctor on this date
        $stmt = $conn->prepare("
            SELECT appointment_time 
            FROM APPOINTMENT 
            WHERE doctor_id = :doctor_id 
            AND appointment_date = :date
            AND status != 'cancelled'
        ");
        $stmt->execute(['doctor_id' => $doctorId, 'date' => $date]);

        $bookedSlots = [];
        while ($row = $stmt->fetch()) {
            $bookedSlots[] = substr($row['appointment_time'], 0, 5); // HH:MM
        }

        // Generate all possible slots
        $allSlots = [];

        // Morning: 9:00 - 11:30
        for ($hour = 9; $hour < 12; $hour++) {
            $allSlots[] = sprintf("%02d:00", $hour);
            $allSlots[] = sprintf("%02d:30", $hour);
        }

        // Afternoon: 13:00 - 16:00
        for ($hour = 13; $hour <= 15; $hour++) {
            $allSlots[] = sprintf("%02d:00", $hour);
            if ($hour < 16) {
                $allSlots[] = sprintf("%02d:30", $hour);
            }
        }
        $allSlots[] = "16:00";

        // Create slot objects
        foreach ($allSlots as $slot) {
            $response['slots'][] = [
                'time' => $slot,
                'available' => !in_array($slot, $bookedSlots)
            ];
        }

        $response['success'] = true;
    }
}

echo json_encode($response);
