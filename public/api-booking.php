<?php
session_start();
header('Content-Type: application/json');

// Set test session if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'System Admin';
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'unidia_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// GET SLOTS
if ($action == 'get-slots') {
    $doctorId = (int)$_GET['doctor_id'];
    $date = $_GET['date'];
    $dayOfWeek = date('l', strtotime($date));
    
    $result = $conn->query("SELECT id, DATE_FORMAT(start_time, '%h:%i %p') as start_time, DATE_FORMAT(end_time, '%h:%i %p') as end_time 
                           FROM doctor_schedule_sessions 
                           WHERE doctor_id = $doctorId AND day_of_week = '$dayOfWeek' AND is_available = 1");
    
    $slots = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $slots[] = $row;
        }
    } else {
        // Default slots
        $slots = [
            ['id' => 1, 'start_time' => '09:00 AM', 'end_time' => '10:00 AM'],
            ['id' => 2, 'start_time' => '10:00 AM', 'end_time' => '11:00 AM'],
            ['id' => 3, 'start_time' => '11:00 AM', 'end_time' => '12:00 PM'],
            ['id' => 4, 'start_time' => '02:00 PM', 'end_time' => '03:00 PM'],
            ['id' => 5, 'start_time' => '03:00 PM', 'end_time' => '04:00 PM'],
            ['id' => 6, 'start_time' => '04:00 PM', 'end_time' => '05:00 PM']
        ];
    }
    
    echo json_encode($slots);
    exit;
}

// BOOK APPOINTMENT
if ($action == 'book') {
    $patientId = (int)$_POST['patient_id'];
    $doctorId = (int)$_POST['doctor_id'];
    $appointmentDate = $conn->real_escape_string($_POST['appointment_date']);
    $appointmentType = $conn->real_escape_string($_POST['appointment_type'] ?? 'regular');
    $paymentStatus = $conn->real_escape_string($_POST['payment_status'] ?? 'pending');
    $symptoms = $conn->real_escape_string($_POST['symptoms'] ?? '');
    $notes = $conn->real_escape_string($_POST['notes'] ?? '');
    $scheduleId = (int)($_POST['schedule_id'] ?? 0);
    
    // Validate
    if ($patientId == 0 || $doctorId == 0 || empty($appointmentDate)) {
        echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
        exit;
    }
    
    // Get schedule times
    $startTime = '09:00:00';
    $endTime = '17:00:00';
    
    if ($scheduleId > 0) {
        $scheduleResult = $conn->query("SELECT start_time, end_time FROM doctor_schedule_sessions WHERE id = $scheduleId");
        if ($scheduleResult && $scheduleResult->num_rows > 0) {
            $schedule = $scheduleResult->fetch_assoc();
            $startTime = $schedule['start_time'];
            $endTime = $schedule['end_time'];
        }
    }
    
    // Get consultation fee
    $doctorResult = $conn->query("SELECT consultation_fee FROM doctors WHERE id = $doctorId");
    $consultationFee = 500;
    if ($doctorResult && $doctorResult->num_rows > 0) {
        $consultationFee = $doctorResult->fetch_assoc()['consultation_fee'];
    }
    
    // Generate appointment number
    $appointmentNumber = 'APT' . date('Ymd') . rand(1000, 9999);
    
    $query = "INSERT INTO appointments (appointment_number, patient_id, doctor_id, appointment_date, 
              start_time, end_time, appointment_type, symptoms, status, payment_status, total_amount, notes, created_by) 
              VALUES (
                '$appointmentNumber', 
                $patientId, 
                $doctorId, 
                '$appointmentDate', 
                '$startTime', 
                '$endTime', 
                '$appointmentType', 
                '$symptoms', 
                'scheduled', 
                '$paymentStatus', 
                $consultationFee, 
                '$notes', 
                {$_SESSION['user_id']}
              )";
    
    if ($conn->query($query)) {
        // Create bill
        $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
        $conn->query("INSERT INTO bills (bill_number, patient_id, bill_type, bill_date, total_amount, payment_status, created_by) 
                      VALUES ('$billNumber', $patientId, 'consultation', CURDATE(), $consultationFee, '$paymentStatus', {$_SESSION['user_id']})");
        
        echo json_encode(['success' => true, 'appointment_number' => $appointmentNumber]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

// TODAY'S APPOINTMENTS
if ($action == 'today') {
    $today = date('Y-m-d');
    $result = $conn->query("SELECT a.*, 
                           CONCAT(p.first_name, ' ', p.last_name) as patient_name, 
                           p.patient_code,
                           CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                           DATE_FORMAT(a.start_time, '%h:%i %p') as start_time
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE a.appointment_date = '$today'
                           ORDER BY a.start_time ASC");
    
    $appointments = [];
    while($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    echo json_encode($appointments);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>