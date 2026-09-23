<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/unidia/public');

// Database connection
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'unidia_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Get request path
$request = $_SERVER['REQUEST_URI'];
$basePath = '/unidia/public';
if (strpos($request, $basePath) === 0) {
    $request = substr($request, strlen($basePath));
}
if (($pos = strpos($request, '?')) !== false) {
    $request = substr($request, 0, $pos);
}
$request = trim($request, '/');
if (empty($request)) {
    $request = 'login';
}

// Autoload controllers
spl_autoload_register(function($className) {
    $file = BASE_PATH . '/app/controllers/' . $className . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Make db connection available to controllers
$GLOBALS['db_conn'] = $conn;

// ==================== API ROUTES ====================

// API - BOOK APPOINTMENT
if ($request == 'api/book-appointment') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
        $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
        $appointmentDate = isset($_POST['appointment_date']) ? $conn->real_escape_string($_POST['appointment_date']) : '';
        $shift = isset($_POST['shift']) ? $conn->real_escape_string($_POST['shift']) : '';
        $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
        $appointmentType = isset($_POST['appointment_type']) ? $conn->real_escape_string($_POST['appointment_type']) : 'regular';
        $symptoms = isset($_POST['symptoms']) ? $conn->real_escape_string($_POST['symptoms']) : '';
        $specialNote = isset($_POST['special_note']) ? $conn->real_escape_string($_POST['special_note']) : '';
        $paymentMethod = isset($_POST['payment_method']) ? $conn->real_escape_string($_POST['payment_method']) : 'cash';
        $discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0;
        
        if($patientId == 0 || $doctorId == 0 || empty($appointmentDate) || empty($shift)) {
            echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
            exit;
        }
        
        $doctorResult = $conn->query("SELECT consultation_fee FROM doctors WHERE id = $doctorId");
        if(!$doctorResult || $doctorResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
            exit;
        }
        $consultationFee = (float)$doctorResult->fetch_assoc()['consultation_fee'];
        
        $serviceFee = 0;
        if($serviceId > 0) {
            $serviceResult = $conn->query("SELECT default_price FROM services WHERE id = $serviceId");
            if($serviceResult && $serviceResult->num_rows > 0) {
                $serviceFee = (float)$serviceResult->fetch_assoc()['default_price'];
            }
        }
        
        $serialQuery = $conn->query("SELECT COUNT(*) as count FROM appointments 
                                     WHERE doctor_id = $doctorId 
                                     AND appointment_date = '$appointmentDate' 
                                     AND session_type = '$shift'
                                     AND status != 'canceled'");
        $serialCount = (int)$serialQuery->fetch_assoc()['count'];
        $serialNumber = ($shift == 'morning' ? 'M' : 'E') . str_pad(($serialCount + 1), 3, '0', STR_PAD_LEFT);
        
        $subtotal = $consultationFee + $serviceFee;
        $totalAmount = $subtotal - $discount;
        $appointmentNumber = 'APT' . date('Ymd') . rand(1000, 9999);
        
        $startTime = $shift == 'morning' ? '09:00:00' : '14:00:00';
        $endTime = $shift == 'morning' ? '13:00:00' : '18:00:00';
        
        $query = "INSERT INTO appointments (
                    appointment_number, patient_id, doctor_id, appointment_date, 
                    start_time, end_time, session_type, appointment_type, 
                    symptoms, special_note, status, payment_status, 
                    payment_method, total_amount, discount, service_id, created_by
                  ) VALUES (
                    '$appointmentNumber', $patientId, $doctorId, '$appointmentDate', 
                    '$startTime', '$endTime', '$shift', '$appointmentType', 
                    '" . addslashes($symptoms) . "', '" . addslashes($specialNote) . "',
                    'scheduled', 'pending', '$paymentMethod', $totalAmount, $discount, 
                    " . ($serviceId ? $serviceId : "NULL") . ", {$_SESSION['user_id']}
                  )";
        
        if ($conn->query($query)) {
            $appointmentId = $conn->insert_id;
            
            $queueExists = $conn->query("SHOW TABLES LIKE 'queue'");
            if($queueExists->num_rows > 0) {
                $conn->query("INSERT INTO queue (appointment_id, serial_number, shift, status) 
                             VALUES ($appointmentId, '$serialNumber', '$shift', 'waiting')");
            }
            
            $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
            $conn->query("INSERT INTO bills (
                            bill_number, patient_id, bill_type, bill_date, 
                            subtotal, discount_amount, total_amount, payment_status, 
                            payment_method, created_by, reference_type, reference_id
                          ) VALUES (
                            '$billNumber', $patientId, 'consultation', CURDATE(), 
                            $subtotal, $discount, $totalAmount, 'pending', 
                            '$paymentMethod', {$_SESSION['user_id']}, 'appointment', $appointmentId
                          )");
            
            $commissionPercentage = 20;
            $commissionAmount = ($consultationFee * $commissionPercentage) / 100;
            $conn->query("INSERT INTO doctor_commissions (
                            doctor_id, reference_type, reference_id, amount, 
                            commission_percentage, commission_amount, status
                          ) VALUES (
                            $doctorId, 'consultation', $appointmentId, $consultationFee, 
                            $commissionPercentage, $commissionAmount, 'pending'
                          )");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Appointment booked successfully! Serial: ' . $serialNumber,
                'appointment_number' => $appointmentNumber,
                'appointment_id' => $appointmentId,
                'serial_number' => $serialNumber
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

// API - GET AVAILABLE SLOTS
if ($request == 'api/get-available-slots') {
    header('Content-Type: application/json');
    $doctorId = (int)$_GET['doctor_id'];
    $date = $conn->real_escape_string($_GET['date']);
    $shift = $conn->real_escape_string($_GET['shift']);
    $dayOfWeek = date('l', strtotime($date));
    
    $schedule = $conn->query("SELECT start_time, end_time, max_patients 
                             FROM doctor_schedule_sessions 
                             WHERE doctor_id = $doctorId 
                             AND day_of_week = '$dayOfWeek' 
                             AND session_type = '$shift' 
                             AND is_available = 1");
    
    if($schedule->num_rows == 0) {
        $maxPatients = 20;
        $startTime = $shift == 'morning' ? '09:00:00' : '14:00:00';
        $endTime = $shift == 'morning' ? '13:00:00' : '18:00:00';
    } else {
        $row = $schedule->fetch_assoc();
        $startTime = $row['start_time'];
        $endTime = $row['end_time'];
        $maxPatients = $row['max_patients'];
    }
    
    $booked = $conn->query("SELECT COUNT(*) as booked_count FROM appointments 
                           WHERE doctor_id = $doctorId 
                           AND appointment_date = '$date' 
                           AND session_type = '$shift'
                           AND status != 'canceled'");
    $bookedCount = (int)$booked->fetch_assoc()['booked_count'];
    $availableSlots = $maxPatients - $bookedCount;
    $nextSerial = $bookedCount + 1;
    
    echo json_encode([
        'success' => true,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'max_patients' => $maxPatients,
        'booked_count' => $bookedCount,
        'available_slots' => $availableSlots,
        'next_serial' => $nextSerial,
        'has_availability' => $availableSlots > 0
    ]);
    exit;
}

// API - SEARCH PATIENTS
if ($request == 'api/search-patients') {
    header('Content-Type: application/json');
    $search = $conn->real_escape_string($_GET['search'] ?? '');
    
    $query = "SELECT id, patient_code, first_name, last_name, phone, email 
              FROM patients WHERE status = 'active' 
              AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' 
                   OR phone LIKE '%$search%' OR patient_code LIKE '%$search%') LIMIT 20";
    
    $result = $conn->query($query);
    $patients = [];
    while($row = $result->fetch_assoc()) { $patients[] = $row; }
    echo json_encode($patients);
    exit;
}

// API - SEARCH DOCTORS
if ($request == 'api/search-doctors') {
    header('Content-Type: application/json');
    $search = $conn->real_escape_string($_GET['search'] ?? '');
    
    $query = "SELECT d.id, d.specialization, d.consultation_fee, u.first_name, u.last_name
              FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.status = 'active'
              AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR d.specialization LIKE '%$search%') LIMIT 20";
    
    $result = $conn->query($query);
    $doctors = [];
    while($row = $result->fetch_assoc()) { $doctors[] = $row; }
    echo json_encode($doctors);
    exit;
}

// API - PRINT SERIAL SLIP (Simplified - No Amounts)
if ($request == 'api/print-serial') {
    $appointmentId = (int)$_GET['appointment_id'];
    
    $result = $conn->query("SELECT a.*, p.first_name, p.last_name, p.phone, p.patient_code,
                                   u.first_name as doctor_fname, u.last_name as doctor_lname, d.specialization
                            FROM appointments a
                            JOIN patients p ON a.patient_id = p.id
                            JOIN doctors d ON a.doctor_id = d.id
                            JOIN users u ON d.user_id = u.id
                            WHERE a.id = $appointmentId");
    
    $data = $result->fetch_assoc();
    if(!$data) { echo "Appointment not found"; exit; }
    
    // Get serial number
    $serialQuery = $conn->query("SELECT COUNT(*) as count FROM appointments 
                                 WHERE doctor_id = {$data['doctor_id']} 
                                 AND appointment_date = '{$data['appointment_date']}' 
                                 AND session_type = '{$data['session_type']}'");
    $serialCount = $serialQuery->fetch_assoc()['count'];
    $serialNumber = ($data['session_type'] == 'morning' ? 'M' : 'E') . str_pad($serialCount, 3, '0', STR_PAD_LEFT);
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <title>Appointment Serial Slip</title>
        <style>
            @page { size: 80mm auto; margin: 0; }
            body { 
                font-family: "Courier New", Courier, monospace; 
                width: 80mm; 
                margin: 0 auto; 
                padding: 10px; 
                text-align: center;
                font-size: 12px;
            }
            .header { 
                border-bottom: 1px dashed #000; 
                padding-bottom: 8px; 
                margin-bottom: 15px; 
            }
            .hospital-name { 
                font-size: 18px; 
                font-weight: bold; 
                letter-spacing: 2px;
            }
            .title { 
                font-size: 14px; 
                font-weight: bold; 
                margin-top: 5px;
            }
            .serial-box { 
                border: 2px solid #000; 
                padding: 15px; 
                margin: 15px 0; 
                text-align: center;
            }
            .serial-number { 
                font-size: 32px; 
                font-weight: bold; 
                letter-spacing: 3px;
            }
            .info-row { 
                text-align: left; 
                margin: 8px 0; 
                display: flex;
                justify-content: space-between;
            }
            .info-label { 
                font-weight: bold; 
            }
            .footer { 
                margin-top: 20px; 
                border-top: 1px dashed #000; 
                padding-top: 8px; 
                font-size: 10px; 
                text-align: center;
            }
            @media print { 
                .no-print { display: none; } 
                body { margin: 0; padding: 10px; }
            }
            button { 
                margin: 10px; 
                padding: 8px 16px; 
                cursor: pointer; 
                background: #10b981;
                color: white;
                border: none;
                border-radius: 5px;
            }
            button:hover { background: #059669; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="hospital-name">UNIDIA HOSPITAL</div>
            <div class="title">APPOINTMENT SERIAL SLIP</div>
        </div>
        
        <div class="serial-box">
            <div style="font-size: 12px; margin-bottom: 5px;">SERIAL NUMBER</div>
            <div class="serial-number">' . $serialNumber . '</div>
        </div>
        
        <div class="info-row">
            <span class="info-label">Patient Name:</span>
            <span>' . htmlspecialchars($data['first_name'] . ' ' . $data['last_name']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Patient ID:</span>
            <span>' . $data['patient_code'] . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Phone:</span>
            <span>' . $data['phone'] . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Doctor:</span>
            <span>Dr. ' . htmlspecialchars($data['doctor_fname'] . ' ' . $data['doctor_lname']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Specialization:</span>
            <span>' . htmlspecialchars($data['specialization']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date:</span>
            <span>' . date('d/m/Y', strtotime($data['appointment_date'])) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Shift:</span>
            <span>' . ucfirst($data['session_type']) . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Time:</span>
            <span>' . date('h:i A', strtotime($data['start_time'])) . ' - ' . date('h:i A', strtotime($data['end_time'])) . '</span>
        </div>
        
        <div class="footer">
            <div>Please keep this slip for reference</div>
            <div>Generated: ' . date('d/m/Y h:i A') . '</div>
            <div style="margin-top: 8px;">Thank you for choosing UNIDIA Hospital</div>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 15px;">
            <button onclick="window.print()">🖨️ Print</button>
            <button onclick="window.close()">✖ Close</button>
        </div>
        
        <script>
            window.onload = function() { 
                setTimeout(function() { 
                    window.print(); 
                }, 500); 
            }
        </script>
    </body>
    </html>';
    
    echo $html;
    exit;
}

// ==================== API - GET FILTERED APPOINTMENTS ====================
if ($request == 'api/get-filtered-appointments') {
    header('Content-Type: application/json');
    
    $doctorId = isset($_GET['doctor_id']) && $_GET['doctor_id'] != '' ? (int)$_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
    $shift = isset($_GET['shift']) && $_GET['shift'] != '' ? $conn->real_escape_string($_GET['shift']) : '';
    $status = isset($_GET['status']) && $_GET['status'] != '' ? $conn->real_escape_string($_GET['status']) : '';
    
    $query = "SELECT a.*, p.first_name, p.last_name, p.phone, p.patient_code, p.id as patient_id,
                     u.first_name as doctor_fname, u.last_name as doctor_lname, d.specialization,
                     COALESCE(a.payment_received, 0) as payment_received,
                     a.total_amount, COALESCE(a.payment_status, 'pending') as payment_status
              FROM appointments a
              JOIN patients p ON a.patient_id = p.id
              JOIN doctors d ON a.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              WHERE a.appointment_date = '$date'";
    
    if($doctorId > 0) $query .= " AND a.doctor_id = $doctorId";
    if($shift != '') $query .= " AND a.session_type = '$shift'";
    if($status != '') $query .= " AND a.status = '$status'";
    
    $query .= " ORDER BY a.session_type, a.created_at ASC";
    
    $result = $conn->query($query);
    $appointments = [];
    
    if($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Calculate serial number
            $serialQuery = $conn->query("SELECT COUNT(*) as cnt FROM appointments 
                                         WHERE doctor_id = {$row['doctor_id']} 
                                         AND appointment_date = '{$row['appointment_date']}' 
                                         AND session_type = '{$row['session_type']}'
                                         AND id <= {$row['id']}");
            $cnt = $serialQuery->fetch_assoc()['cnt'];
            $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . str_pad($cnt, 3, '0', STR_PAD_LEFT);
            
            // Ensure payment values are set correctly
            $row['payment_received'] = (float)($row['payment_received'] ?? 0);
            $row['payment_status'] = $row['payment_status'] ?? 'pending';
            
            $appointments[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $appointments]);
    exit;
}

// API - UPDATE APPOINTMENT STATUS
if ($request == 'api/update-appointment-status') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    if ($conn->query("UPDATE appointments SET status = '$status' WHERE id = $appointmentId")) {
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

// API - GET DOCTORS WITH SCHEDULES
if ($request == 'api/get-doctors-with-schedules') {
    header('Content-Type: application/json');
    
    $doctors = $conn->query("SELECT d.id, d.specialization, d.consultation_fee, u.first_name, u.last_name 
                             FROM doctors d 
                             JOIN users u ON d.user_id = u.id 
                             WHERE d.status = 'active'");
    
    $doctorList = [];
    while($doctor = $doctors->fetch_assoc()) {
        $schedules = $conn->query("SELECT * FROM doctor_schedule_sessions WHERE doctor_id = {$doctor['id']}");
        $sessions = [];
        while($schedule = $schedules->fetch_assoc()) {
            $sessions[] = $schedule;
        }
        $doctor['sessions'] = $sessions;
        $doctorList[] = $doctor;
    }
    
    echo json_encode($doctorList);
    exit;
}

// API - GET DOCTOR SCHEDULE
if ($request == 'api/get-doctor-schedule') {
    header('Content-Type: application/json');
    $doctorId = (int)$_GET['doctor_id'];
    
    $schedules = $conn->query("SELECT * FROM doctor_schedule_sessions WHERE doctor_id = $doctorId");
    $scheduleData = [];
    while($row = $schedules->fetch_assoc()) {
        $scheduleData[$row['day_of_week']][$row['session_type']] = [
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time'],
            'slot_duration' => $row['slot_duration'],
            'max_patients' => $row['max_patients']
        ];
    }
    
    echo json_encode($scheduleData);
    exit;
}

// API - SAVE DOCTOR SCHEDULE
if ($request == 'api/save-doctor-schedule') {
    header('Content-Type: application/json');
    
    $doctorId = (int)$_POST['doctor_id'];
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    $conn->query("DELETE FROM doctor_schedule_sessions WHERE doctor_id = $doctorId");
    
    $inserted = 0;
    
    foreach($days as $day) {
        // Morning session
        $morningActive = isset($_POST["{$day}_morning_active"]) && $_POST["{$day}_morning_active"] == '1';
        if($morningActive) {
            $startTime = $conn->real_escape_string($_POST["{$day}_morning_start"]);
            $endTime = $conn->real_escape_string($_POST["{$day}_morning_end"]);
            $slotDuration = (int)$_POST["{$day}_morning_slot"];
            $maxPatients = (int)$_POST["{$day}_morning_patients"];
            
            $conn->query("INSERT INTO doctor_schedule_sessions 
                         (doctor_id, day_of_week, session_type, start_time, end_time, slot_duration, max_patients, is_available) 
                         VALUES ($doctorId, '$day', 'morning', '$startTime', '$endTime', $slotDuration, $maxPatients, 1)");
            $inserted++;
        }
        
        // Evening session
        $eveningActive = isset($_POST["{$day}_evening_active"]) && $_POST["{$day}_evening_active"] == '1';
        if($eveningActive) {
            $startTime = $conn->real_escape_string($_POST["{$day}_evening_start"]);
            $endTime = $conn->real_escape_string($_POST["{$day}_evening_end"]);
            $slotDuration = (int)$_POST["{$day}_evening_slot"];
            $maxPatients = (int)$_POST["{$day}_evening_patients"];
            
            $conn->query("INSERT INTO doctor_schedule_sessions 
                         (doctor_id, day_of_week, session_type, start_time, end_time, slot_duration, max_patients, is_available) 
                         VALUES ($doctorId, '$day', 'evening', '$startTime', '$endTime', $slotDuration, $maxPatients, 1)");
            $inserted++;
        }
    }
    
    echo json_encode(['success' => true, 'message' => "$inserted schedules saved"]);
    exit;
}

// API - ADD DOCTOR SCHEDULE
if ($request == 'api/add-doctor-schedule') {
    header('Content-Type: application/json');
    
    $doctorId = (int)$_POST['doctor_id'];
    $dayOfWeek = $conn->real_escape_string($_POST['day_of_week']);
    $sessionType = $conn->real_escape_string($_POST['session_type']);
    $startTime = $conn->real_escape_string($_POST['start_time']);
    $endTime = $conn->real_escape_string($_POST['end_time']);
    $slotDuration = (int)$_POST['slot_duration'];
    $maxPatients = (int)$_POST['max_patients'];
    
    $check = $conn->query("SELECT id FROM doctor_schedule_sessions 
                          WHERE doctor_id = $doctorId 
                          AND day_of_week = '$dayOfWeek' 
                          AND session_type = '$sessionType'");
    
    if($check->num_rows > 0) {
        $conn->query("UPDATE doctor_schedule_sessions 
                     SET start_time = '$startTime', end_time = '$endTime', 
                         slot_duration = $slotDuration, max_patients = $maxPatients, is_available = 1
                     WHERE doctor_id = $doctorId AND day_of_week = '$dayOfWeek' AND session_type = '$sessionType'");
        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
    } else {
        $conn->query("INSERT INTO doctor_schedule_sessions 
                     (doctor_id, day_of_week, session_type, start_time, end_time, slot_duration, max_patients, is_available) 
                     VALUES ($doctorId, '$dayOfWeek', '$sessionType', '$startTime', '$endTime', $slotDuration, $maxPatients, 1)");
        echo json_encode(['success' => true, 'message' => 'Schedule added successfully']);
    }
    exit;
}

// API - GET DAILY PATIENTS
if ($request == 'api/daily-patients') {
    header('Content-Type: application/json');
    
    $date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
    $shift = isset($_GET['shift']) && $_GET['shift'] != '' ? $conn->real_escape_string($_GET['shift']) : '';
    
    $query = "SELECT a.*, p.first_name, p.last_name, p.patient_code, p.phone,
                     u.first_name as doctor_fname, u.last_name as doctor_lname, d.specialization
              FROM appointments a
              JOIN patients p ON a.patient_id = p.id
              JOIN doctors d ON a.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              WHERE a.appointment_date = '$date'";
    
    if($shift != '') {
        $query .= " AND a.session_type = '$shift'";
    }
    
    $query .= " ORDER BY a.session_type, a.start_time ASC";
    
    $result = $conn->query($query);
    
    if(!$result) {
        echo json_encode(['error' => $conn->error, 'success' => false]);
        exit;
    }
    
    $appointments = [];
    while($row = $result->fetch_assoc()) {
        $serialQuery = $conn->query("SELECT COUNT(*) as cnt FROM appointments 
                                     WHERE doctor_id = {$row['doctor_id']} 
                                     AND appointment_date = '{$row['appointment_date']}' 
                                     AND session_type = '{$row['session_type']}'
                                     AND id <= {$row['id']}");
        $cnt = $serialQuery->fetch_assoc()['cnt'];
        $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . str_pad($cnt, 3, '0', STR_PAD_LEFT);
        $appointments[] = $row;
    }
    
    echo json_encode($appointments);
    exit;
}

// API - GET DOCTOR DAILY PATIENTS
if ($request == 'api/doctor-daily-patients') {
    header('Content-Type: application/json');
    
    $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
    
    if($doctorId == 0) {
        echo json_encode([]);
        exit;
    }
    
    $query = "SELECT a.*, p.first_name, p.last_name, p.patient_code, p.phone, p.address,
                     d.specialization
              FROM appointments a
              JOIN patients p ON a.patient_id = p.id
              JOIN doctors d ON a.doctor_id = d.id
              WHERE a.doctor_id = $doctorId AND a.appointment_date = '$date'
              ORDER BY a.session_type, a.start_time ASC";
    
    $result = $conn->query($query);
    
    if(!$result) {
        echo json_encode(['error' => $conn->error, 'success' => false]);
        exit;
    }
    
    $patients = [];
    while($row = $result->fetch_assoc()) {
        $serialQuery = $conn->query("SELECT COUNT(*) as cnt FROM appointments 
                                     WHERE doctor_id = $doctorId 
                                     AND appointment_date = '$date' 
                                     AND session_type = '{$row['session_type']}'
                                     AND id <= {$row['id']}");
        $cnt = $serialQuery->fetch_assoc()['cnt'];
        $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . str_pad($cnt, 3, '0', STR_PAD_LEFT);
        $patients[] = $row;
    }
    
    echo json_encode($patients);
    exit;
}

// API - PATIENT APPOINTMENTS
if ($request == 'api/patient-appointments') {
    header('Content-Type: application/json');
    $patientId = (int)$_GET['patient_id'];
    $date = $conn->real_escape_string($_GET['date'] ?? date('Y-m-d'));
    
    $query = "SELECT a.*, u.first_name as doctor_fname, u.last_name as doctor_lname, d.specialization
             FROM appointments a
             JOIN doctors d ON a.doctor_id = d.id
             JOIN users u ON d.user_id = u.id
             WHERE a.patient_id = $patientId AND a.appointment_date = '$date'
             AND a.status NOT IN ('canceled', 'completed')
             ORDER BY a.start_time ASC";
    
    $result = $conn->query($query);
    $appointments = [];
    while($row = $result->fetch_assoc()) {
        $row['doctor_name'] = $row['doctor_fname'] . ' ' . $row['doctor_lname'];
        $appointments[] = $row;
    }
    
    echo json_encode($appointments);
    exit;
}

// ==================== API - QUEUE STATUS ====================
if ($request == 'api/queue-status') {
    $today = date('Y-m-d');
    
    // Only show waiting and in_progress - exclude completed
    $queue = $conn->query("SELECT q.*, a.appointment_number, a.session_type as shift,
                                  p.first_name, p.last_name, p.patient_code,
                                  u.first_name as doctor_fname, u.last_name as doctor_lname
                          FROM queue q
                          JOIN appointments a ON q.appointment_id = a.id
                          JOIN patients p ON a.patient_id = p.id
                          JOIN doctors d ON a.doctor_id = d.id
                          JOIN users u ON d.user_id = u.id
                          WHERE DATE(q.created_at) = '$today' 
                          AND q.status IN ('waiting', 'in_progress')
                          ORDER BY FIELD(q.status, 'in_progress', 'waiting'), q.serial_number ASC");
    
    ob_start();
    ?>
    <div class="list-group">
        <?php if($queue->num_rows > 0): ?>
            <?php while($row = $queue->fetch_assoc()): ?>
            <div class="list-group-item queue-item" data-queue-id="<?php echo $row['id']; ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-<?php echo $row['status'] == 'in_progress' ? 'success' : 'warning'; ?> fs-5 p-2">
                            #<?php echo $row['serial_number']; ?>
                        </span>
                    </div>
                    <div class="flex-grow-1 mx-3">
                        <strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
                        <br><small class="text-muted">Dr. <?php echo htmlspecialchars($row['doctor_fname'] . ' ' . $row['doctor_lname']); ?></small>
                        <br><small class="text-muted"><?php echo ucfirst($row['shift']); ?> Shift</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-<?php echo $row['status'] == 'in_progress' ? 'success' : 'warning'; ?> mb-2">
                            <?php echo $row['status'] == 'in_progress' ? 'IN PROGRESS' : 'WAITING'; ?>
                        </span>
                        <?php if($row['status'] == 'waiting'): ?>
                        <div class="mt-1">
                            <button class="btn btn-sm btn-success call-patient" data-id="<?php echo $row['id']; ?>" data-serial="<?php echo $row['serial_number']; ?>">
                                <i class="fas fa-bullhorn me-1"></i>Call
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <p>Queue is empty</p>
                <small>No patients waiting or in progress</small>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();
    echo $html;
    exit;
}

// ==================== AUTH ROUTES ====================
if ($request == 'login') {
    $auth = new AuthController();
    $auth->showLogin();
    exit;
}
if ($request == 'do-login') {
    $auth = new AuthController();
    $auth->doLogin();
    exit;
}
if ($request == 'logout') {
    $auth = new AuthController();
    $auth->logout();
    exit;
}

// ==================== CHECK AUTH ====================
$publicRoutes = ['login', 'do-login', 'patient/portal-login', 'patient/portal/do-login'];
if (!isset($_SESSION['user_id']) && !in_array($request, $publicRoutes)) {
    header('Location: ' . BASE_URL . '/login');
    exit;
}

// ==================== DASHBOARD ====================
if ($request == 'admin/dashboard') {
    $dashboard = new DashboardController();
    $dashboard->index();
    exit;
}

// ==================== USER MANAGEMENT ====================
if ($request == 'admin/users') { $user = new UserController(); $user->index(); exit; }
if ($request == 'admin/users/create') { $user = new UserController(); $user->create(); exit; }
if ($request == 'admin/users/store') { $user = new UserController(); $user->store(); exit; }
if (preg_match('/admin\/users\/edit\/(\d+)/', $request, $matches)) { $user = new UserController(); $user->edit($matches[1]); exit; }
if (preg_match('/admin\/users\/update\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { $user = new UserController(); $user->update($matches[1]); exit; }
if (preg_match('/admin\/users\/delete\/(\d+)/', $request, $matches)) { $user = new UserController(); $user->delete($matches[1]); exit; }
if ($request == 'admin/users/roles') { $user = new UserController(); $user->roles(); exit; }
if ($request == 'admin/users/attendance') { $user = new UserController(); $user->attendance(); exit; }
if ($request == 'admin/users/payroll') { $user = new UserController(); $user->payroll(); exit; }

// ==================== PATIENT MANAGEMENT ====================
if ($request == 'patient/list') { $patient = new PatientController(); $patient->index(); exit; }
if ($request == 'patient/register') { $patient = new PatientController(); $patient->register(); exit; }
if ($request == 'patient/store') { $patient = new PatientController(); $patient->store(); exit; }
if ($request == 'patient/id-card') { $patient = new PatientController(); $patient->idCard(); exit; }
if ($request == 'patient/view') { $patient = new PatientController(); $patient->view(); exit; }
if ($request == 'patient/edit') { $patient = new PatientController(); $patient->edit(); exit; }
if ($request == 'patient/update') { $patient = new PatientController(); $patient->update(); exit; }
if ($request == 'patient/delete') { $patient = new PatientController(); $patient->delete(); exit; }
if ($request == 'patient/book-appointment') { $patient = new PatientController(); $patient->bookAppointment(); exit; }

// ==================== DOCTOR MANAGEMENT ====================
if ($request == 'doctor/list') { $doctor = new DoctorController(); $doctor->index(); exit; }
if ($request == 'doctor/create') { $doctor = new DoctorController(); $doctor->create(); exit; }
if ($request == 'doctor/store') { $doctor = new DoctorController(); $doctor->store(); exit; }
if (preg_match('/doctor\/edit\/(\d+)/', $request, $matches)) { $doctor = new DoctorController(); $doctor->edit($matches[1]); exit; }
if (preg_match('/doctor\/update\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { $doctor = new DoctorController(); $doctor->update($matches[1]); exit; }
if (preg_match('/doctor\/delete\/(\d+)/', $request, $matches)) { $doctor = new DoctorController(); $doctor->delete($matches[1]); exit; }
if (preg_match('/doctor\/view\/(\d+)/', $request, $matches)) { $doctor = new DoctorController(); $doctor->show($matches[1]); exit; }
if ($request == 'doctor/schedule-list') { $doctor = new DoctorController(); $doctor->scheduleList(); exit; }
if (preg_match('/doctor\/schedule\/(\d+)/', $request, $matches)) { $doctor = new DoctorController(); $doctor->schedule($matches[1]); exit; }
if (preg_match('/doctor\/save-schedule\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { $doctor = new DoctorController(); $doctor->saveSchedule($matches[1]); exit; }
if ($request == 'doctor/commissions') { $doctor = new DoctorController(); $doctor->commissions(); exit; }
if (preg_match('/doctor\/approve-commission\/(\d+)/', $request, $matches)) { $doctor = new DoctorController(); $doctor->approveCommission($matches[1]); exit; }
if (preg_match('/doctor\/pay-commission\/(\d+)/', $request, $matches)) { $doctor = new DoctorController(); $doctor->payCommission($matches[1]); exit; }

// ==================== APPOINTMENT ROUTES ====================
if ($request == 'appointments/book') { $appointment = new AppointmentController(); $appointment->book(); exit; }
if ($request == 'reception/appointments') { $appointment = new AppointmentController(); $appointment->index(); exit; }
if ($request == 'reception/today') { $appointment = new AppointmentController(); $appointment->today(); exit; }


// ==================== RECEPTION MANAGEMENT ====================
if ($request == 'reception/dashboard') { $reception = new ReceptionController(); $reception->dashboard(); exit; }
if ($request == 'reception/check-in') { $reception = new ReceptionController(); $reception->checkIn(); exit; }
if ($request == 'reception/do-checkin') { $reception = new ReceptionController(); $reception->doCheckIn(); exit; }
if ($request == 'reception/queue') { $reception = new ReceptionController(); $reception->queue(); exit; }
if ($request == 'reception/call-patient') { $reception = new ReceptionController(); $reception->callPatient(); exit; }
if ($request == 'reception/complete-consultation') { $reception = new ReceptionController(); $reception->completeConsultation(); exit; }
if ($request == 'reception/search') { $reception = new ReceptionController(); $reception->searchPatient(); exit; }

// ==================== LABORATORY ROUTES ====================
if ($request == 'lab/dashboard') { $lab = new LaboratoryController(); $lab->dashboard(); exit; }
if ($request == 'lab/orders') { $lab = new LaboratoryController(); $lab->orders(); exit; }
if ($request == 'lab/create-order') { $lab = new LaboratoryController(); $lab->createOrder(); exit; }
if ($request == 'lab/store-order') { $lab = new LaboratoryController(); $lab->storeOrder(); exit; }
if ($request == 'lab/sample-collection') { $lab = new LaboratoryController(); $lab->sampleCollection(); exit; }
if ($request == 'lab/collect-sample') { $lab = new LaboratoryController(); $lab->collectSample(); exit; }
if ($request == 'lab/enter-results') { $lab = new LaboratoryController(); $lab->enterResults(); exit; }
if ($request == 'lab/save-result') { $lab = new LaboratoryController(); $lab->saveResult(); exit; }
if ($request == 'lab/reports') { $lab = new LaboratoryController(); $lab->reports(); exit; }
if (preg_match('/lab\/report\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->viewReport($matches[1]); exit; }
if ($request == 'lab/deliver-report') { $lab = new LaboratoryController(); $lab->deliverReport(); exit; }

// ==================== PHARMACY ROUTES ====================
if ($request == 'pharmacy/dashboard') { $pharmacy = new PharmacyController(); $pharmacy->dashboard(); exit; }
if ($request == 'pharmacy/medicines') { $pharmacy = new PharmacyController(); $pharmacy->medicines(); exit; }
if ($request == 'pharmacy/pos') { $pharmacy = new PharmacyController(); $pharmacy->pos(); exit; }
if ($request == 'pharmacy/prescriptions') { $pharmacy = new PharmacyController(); $pharmacy->prescriptions(); exit; }
if ($request == 'pharmacy/stock') { $pharmacy = new PharmacyController(); $pharmacy->stock(); exit; }
if ($request == 'pharmacy/sales') { $pharmacy = new PharmacyController(); $pharmacy->sales(); exit; }
if (preg_match('/pharmacy\/dispense\/(\d+)/', $request, $matches)) { $pharmacy = new PharmacyController(); $pharmacy->dispense($matches[1]); exit; }
if (preg_match('/pharmacy\/sale\/(\d+)/', $request, $matches)) { $pharmacy = new PharmacyController(); $pharmacy->saleDetails($matches[1]); exit; }

// ==================== PHARMACY API ROUTES ====================
if ($request == 'pharmacy/add-to-cart') { $pharmacy = new PharmacyController(); $pharmacy->addToCart(); exit; }
if ($request == 'pharmacy/get-cart') { $pharmacy = new PharmacyController(); $pharmacy->getCart(); exit; }
if ($request == 'pharmacy/clear-cart') { $pharmacy = new PharmacyController(); $pharmacy->clearCart(); exit; }
if ($request == 'pharmacy/remove-from-cart') { $pharmacy = new PharmacyController(); $pharmacy->removeFromCart(); exit; }
if ($request == 'pharmacy/update-cart') { $pharmacy = new PharmacyController(); $pharmacy->updateCart(); exit; }
if ($request == 'pharmacy/process-sale') { $pharmacy = new PharmacyController(); $pharmacy->processSale(); exit; }
if ($request == 'pharmacy/add-stock') { $pharmacy = new PharmacyController(); $pharmacy->addStock(); exit; }
if ($request == 'pharmacy/add-medicine') { $pharmacy = new PharmacyController(); $pharmacy->addMedicine(); exit; }

// ==================== PLACEHOLDER ROUTES (Under Development) ====================
$placeholderRoutes = ['bills', 'inventory', 'admin/audit-logs', 'admin/settings'];
if (in_array($request, $placeholderRoutes)) {
    $controller = new Controller();
    $title = ucwords(str_replace('/', ' ', str_replace('admin/', '', $request)));
    $content = "<div class='alert alert-info text-center py-5'><i class='fas fa-info-circle fa-3x mb-3 d-block'></i><h4>$title</h4><p>This module is under development. Coming soon!</p><a href='" . BASE_URL . "/admin/dashboard' class='btn btn-primary'>Back to Dashboard</a></div>";
    $controller->renderLayout($title, $content);
    exit;
}

// TEST ROUTE - Remove after testing
if ($request == 'test-pharmacy') {
    echo "<h1>Pharmacy Controller Test</h1>";
    try {
        require_once BASE_PATH . '/app/controllers/PharmacyController.php';
        $pharmacy = new PharmacyController();
        echo "<p style='color:green'>✓ PharmacyController loaded successfully</p>";
        $pharmacy->dashboard();
    } catch (Exception $e) {
        echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    }
    exit;
}

// ==================== INVENTORY ROUTES ====================
if ($request == 'inventory/dashboard') { $inventory = new InventoryController(); $inventory->dashboard(); exit; }
if ($request == 'inventory/items') { $inventory = new InventoryController(); $inventory->items(); exit; }
if ($request == 'inventory/add-item') { $inventory = new InventoryController(); $inventory->addItem(); exit; }
if ($request == 'inventory/stock') { $inventory = new InventoryController(); $inventory->stock(); exit; }
if ($request == 'inventory/add-stock') { $inventory = new InventoryController(); $inventory->addStock(); exit; }
if ($request == 'inventory/expiry-alerts') { $inventory = new InventoryController(); $inventory->expiryAlerts(); exit; }
if ($request == 'inventory/mark-alert-read') { $inventory = new InventoryController(); $inventory->markAlertRead(); exit; }
if ($request == 'inventory/reorder-alerts') { $inventory = new InventoryController(); $inventory->reorderAlerts(); exit; }
if ($request == 'inventory/process-reorder') { $inventory = new InventoryController(); $inventory->processReorder(); exit; }
if ($request == 'inventory/scan-barcode') { $inventory = new InventoryController(); $inventory->scanBarcode(); exit; }
if ($request == 'inventory/generate-barcode') { $inventory = new InventoryController(); $inventory->generateBarcode(); exit; }
if ($request == 'inventory/stores') { $inventory = new InventoryController(); $inventory->stores(); exit; }
if ($request == 'inventory/add-store') { $inventory = new InventoryController(); $inventory->addStore(); exit; }
if ($request == 'inventory/stock-transfers') { $inventory = new InventoryController(); $inventory->stockTransfers(); exit; }
if ($request == 'inventory/create-transfer') { $inventory = new InventoryController(); $inventory->createTransfer(); exit; }
if ($request == 'inventory/approve-transfer') { $inventory = new InventoryController(); $inventory->approveTransfer(); exit; }
if ($request == 'inventory/receive-transfer') { $inventory = new InventoryController(); $inventory->receiveTransfer(); exit; }
if ($request == 'inventory/purchase-orders') { $inventory = new InventoryController(); $inventory->purchaseOrders(); exit; }
if ($request == 'inventory/create-po') { $inventory = new InventoryController(); $inventory->createPurchaseOrder(); exit; }
if ($request == 'inventory/receive-po') { $inventory = new InventoryController(); $inventory->receivePurchaseOrder(); exit; }
if ($request == 'inventory/reports') { $inventory = new InventoryController(); $inventory->reports(); exit; }
if ($request == 'inventory/suppliers') { $inventory = new InventoryController(); $inventory->suppliers(); exit; }


// ==================== INVENTORY ROUTES ====================
if ($request == 'inventory/dashboard') { $inventory = new InventoryController(); $inventory->dashboard(); exit; }
if ($request == 'inventory/items') { $inventory = new InventoryController(); $inventory->items(); exit; }
if ($request == 'inventory/items/add') { $inventory = new InventoryController(); $inventory->addItemForm(); exit; }
if ($request == 'inventory/add-item') { $inventory = new InventoryController(); $inventory->addItem(); exit; }
if ($request == 'inventory/view-item') { 
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $inventory = new InventoryController(); 
    $inventory->viewItem($id); 
    exit; 
}
if ($request == 'inventory/stock') { $inventory = new InventoryController(); $inventory->stock(); exit; }
if ($request == 'inventory/add-stock') { $inventory = new InventoryController(); $inventory->addStock(); exit; }
if ($request == 'inventory/delete-stock') { $inventory = new InventoryController(); $inventory->deleteStock(); exit; }
if ($request == 'inventory/expiry-alerts') { $inventory = new InventoryController(); $inventory->expiryAlerts(); exit; }
if ($request == 'inventory/mark-alert-read') { $inventory = new InventoryController(); $inventory->markAlertRead(); exit; }
if ($request == 'inventory/reorder-alerts') { $inventory = new InventoryController(); $inventory->reorderAlerts(); exit; }
if ($request == 'inventory/process-reorder') { $inventory = new InventoryController(); $inventory->processReorder(); exit; }
if ($request == 'inventory/scan-barcode') { $inventory = new InventoryController(); $inventory->scanBarcode(); exit; }
if ($request == 'inventory/generate-barcode') { $inventory = new InventoryController(); $inventory->generateBarcode(); exit; }


// ==================== LABORATORY ROUTES ====================
if ($request == 'lab/dashboard') { $lab = new LaboratoryController(); $lab->dashboard(); exit; }
if ($request == 'lab/orders') { $lab = new LaboratoryController(); $lab->orders(); exit; }
if ($request == 'lab/create-order') { $lab = new LaboratoryController(); $lab->createOrder(); exit; }
if ($request == 'lab/store-order') { $lab = new LaboratoryController(); $lab->storeOrder(); exit; }
if (preg_match('/lab\/orders\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->viewOrder($matches[1]); exit; }
if ($request == 'lab/collect-sample') { $lab = new LaboratoryController(); $lab->collectSample(); exit; }
if ($request == 'lab/enter-results') { $lab = new LaboratoryController(); $lab->enterResults(); exit; }
if (preg_match('/lab\/enter-result\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->enterResultForm($matches[1]); exit; }
if ($request == 'lab/save-result') { $lab = new LaboratoryController(); $lab->saveResult(); exit; }
if ($request == 'lab/reports') { $lab = new LaboratoryController(); $lab->reports(); exit; }
if (preg_match('/lab\/report\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->viewReport($matches[1]); exit; }
if ($request == 'lab/deliver-report') { $lab = new LaboratoryController(); $lab->deliverReport(); exit; }

// ==================== LABORATORY API ROUTES ====================
if ($request == 'api/lab-orders') { $lab = new LaboratoryController(); $lab->apiLabOrders(); exit; }
if ($request == 'api/stat-orders') { $lab = new LaboratoryController(); $lab->apiStatOrders(); exit; }
if ($request == 'api/validate-barcode') { $lab = new LaboratoryController(); $lab->apiValidateBarcode(); exit; }
if ($request == 'api/collect-by-barcode') { $lab = new LaboratoryController(); $lab->apiCollectByBarcode(); exit; }
if ($request == 'api/doctors') { $lab = new LaboratoryController(); $lab->apiDoctors(); exit; }


// ==================== SAMPLE COLLECTION ROUTES ====================
if ($request == 'lab/sample-collection') { $lab = new LaboratoryController(); $lab->sampleCollection(); exit; }
if ($request == 'lab/update-sample-status') { $lab = new LaboratoryController(); $lab->updateSampleStatus(); exit; }
if ($request == 'lab/generate-single-barcode') { $lab = new LaboratoryController(); $lab->generateSingleBarcode(); exit; }
if ($request == 'lab/print-barcode') { $lab = new LaboratoryController(); $lab->printBarcode(); exit; }
if ($request == 'lab/bulk-collect-samples') { $lab = new LaboratoryController(); $lab->bulkCollectSamples(); exit; }
if ($request == 'lab/get-sample-details') { $lab = new LaboratoryController(); $lab->getSampleDetails(); exit; }

// ==================== LABORATORY REPORTS ROUTES ====================
if ($request == 'lab/reports') { $lab = new LaboratoryController(); $lab->reports(); exit; }
if ($request == 'api/lab-reports-list') { $lab = new LaboratoryController(); $lab->apiLabReportsList(); exit; }
if ($request == 'lab/view-report-ajax') { $lab = new LaboratoryController(); $lab->viewReportAjax(); exit; }
if ($request == 'lab/print-report') { $lab = new LaboratoryController(); $lab->printReport(); exit; }
if ($request == 'lab/export-reports') { $lab = new LaboratoryController(); $lab->exportReports(); exit; }

// ==================== API - GET ITEMS BY TYPE ====================
if ($request == 'api/get-items-by-type') {
    header('Content-Type: application/json');
    $type = $conn->real_escape_string($_GET['type']);
    
    if($type == 'medicine') {
        $result = $conn->query("SELECT id, medicine_name as name, selling_price as price FROM medicines WHERE status = 'active'");
    } elseif($type == 'lab_test') {
        $result = $conn->query("SELECT id, test_name as name, price FROM lab_tests WHERE status = 'active'");
    } else {
        $result = [];
    }
    
    $items = [];
    if($result) {
        while($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    echo json_encode($items);
    exit;
}

// ==================== API - GET DOCTOR SLOTS ====================
if ($request == 'api/get-doctor-slots') {
    header('Content-Type: application/json');
    $doctorId = (int)$_GET['doctor_id'];
    $date = $conn->real_escape_string($_GET['date']);
    $dayOfWeek = date('l', strtotime($date));
    
    $slots = $conn->query("SELECT id, start_time, end_time, max_patients 
                           FROM doctor_schedule_sessions 
                           WHERE doctor_id = $doctorId AND day_of_week = '$dayOfWeek' AND is_available = 1");
    
    $slotList = [];
    while($row = $slots->fetch_assoc()) {
        $slotList[] = $row;
    }
    echo json_encode($slotList);
    exit;
}

// ==================== API - TODAY'S APPOINTMENTS ====================
if ($request == 'api/today-appointments') {
    header('Content-Type: application/json');
    $today = date('Y-m-d');
    
    $query = $conn->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.patient_code,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE a.appointment_date = '$today'
                           ORDER BY a.start_time ASC");
    
    $appointments = [];
    while($row = $query->fetch_assoc()) {
        $appointments[] = $row;
    }
    echo json_encode($appointments);
    exit;
}

// ==================== API - GET DOCTOR AVAILABILITY FOR BOOKING ====================
if ($request == 'api/get-doctor-availability') {
    header('Content-Type: application/json');
    $doctorId = (int)$_GET['doctor_id'];
    $date = $conn->real_escape_string($_GET['date']);
    $shift = $conn->real_escape_string($_GET['shift']);
    $dayOfWeek = date('l', strtotime($date));
    
    // Get doctor's schedule for this day
    $schedule = $conn->query("SELECT start_time, end_time, slot_duration, max_patients 
                             FROM doctor_schedule_sessions 
                             WHERE doctor_id = $doctorId 
                             AND day_of_week = '$dayOfWeek' 
                             AND session_type = '$shift' 
                             AND is_available = 1");
    
    if($schedule->num_rows == 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Doctor not available on this day/shift',
            'has_availability' => false
        ]);
        exit;
    }
    
    $row = $schedule->fetch_assoc();
    $startTime = $row['start_time'];
    $endTime = $row['end_time'];
    $slotDuration = $row['slot_duration'];
    $maxPatients = $row['max_patients'];
    
    // Calculate session duration in minutes
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    $sessionMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
    $totalSlots = floor($sessionMinutes / $slotDuration);
    
    // Count booked appointments for this session
    $booked = $conn->query("SELECT COUNT(*) as booked_count FROM appointments 
                           WHERE doctor_id = $doctorId 
                           AND appointment_date = '$date' 
                           AND session_type = '$shift'
                           AND status != 'canceled'");
    $bookedCount = (int)$booked->fetch_assoc()['booked_count'];
    $availableSlots = $maxPatients - $bookedCount;
    $nextSerial = $bookedCount + 1;
    
    // Calculate available time slots
    $timeSlots = [];
    $currentTime = clone $start;
    $slotNumber = 1;
    
    while ($currentTime < $end && $slotNumber <= $maxPatients) {
        $timeSlots[] = [
            'slot_number' => $slotNumber,
            'start_time' => $currentTime->format('H:i:s'),
            'end_time' => $currentTime->modify("+{$slotDuration} minutes")->format('H:i:s'),
            'is_available' => $slotNumber > $bookedCount
        ];
        $slotNumber++;
    }
    
    echo json_encode([
        'success' => true,
        'doctor_id' => $doctorId,
        'date' => $date,
        'shift' => $shift,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'slot_duration' => $slotDuration,
        'max_patients' => $maxPatients,
        'total_slots' => $totalSlots,
        'booked_count' => $bookedCount,
        'available_slots' => $availableSlots,
        'next_serial' => $nextSerial,
        'has_availability' => $availableSlots > 0,
        'time_slots' => $timeSlots
    ]);
    exit;
}

// API - BOOK APPOINTMENT
if ($request == 'api/book-appointment') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
        $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
        $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
        $additionalServiceId = isset($_POST['additional_service_id']) ? (int)$_POST['additional_service_id'] : 0;
        $appointmentDate = isset($_POST['appointment_date']) ? $conn->real_escape_string($_POST['appointment_date']) : '';
        $shift = isset($_POST['shift']) ? $conn->real_escape_string($_POST['shift']) : '';
        $appointmentType = isset($_POST['appointment_type']) ? $conn->real_escape_string($_POST['appointment_type']) : 'regular';
        $symptoms = isset($_POST['symptoms']) ? $conn->real_escape_string($_POST['symptoms']) : '';
        $specialNote = isset($_POST['special_note']) ? $conn->real_escape_string($_POST['special_note']) : '';
        $paymentMethod = isset($_POST['payment_method']) ? $conn->real_escape_string($_POST['payment_method']) : 'cash';
        $discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
        $totalAmount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
        $discountAmount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0;
        $subtotal = isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0;
        
        if($patientId == 0 || $doctorId == 0 || empty($appointmentDate) || empty($shift)) {
            echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
            exit;
        }
        
        // Get doctor's consultation fee
        $doctorResult = $conn->query("SELECT consultation_fee FROM doctors WHERE id = $doctorId");
        if(!$doctorResult || $doctorResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
            exit;
        }
        $consultationFee = (float)$doctorResult->fetch_assoc()['consultation_fee'];
        
        // Get service fee if selected
        $serviceFee = 0;
        if($serviceId > 0) {
            $serviceResult = $conn->query("SELECT service_price FROM doctor_services WHERE id = $serviceId");
            if($serviceResult && $serviceResult->num_rows > 0) {
                $serviceFee = (float)$serviceResult->fetch_assoc()['service_price'];
            }
        }
        
        // Get additional service fee if selected (from additional_services table)
        $additionalFee = 0;
        if($additionalServiceId > 0) {
            $additionalResult = $conn->query("SELECT service_price FROM additional_services WHERE id = $additionalServiceId");
            if($additionalResult && $additionalResult->num_rows > 0) {
                $additionalFee = (float)$additionalResult->fetch_assoc()['service_price'];
            }
        }
        
        // Calculate serial number
        $serialQuery = $conn->query("SELECT COUNT(*) as count FROM appointments 
                                     WHERE doctor_id = $doctorId 
                                     AND appointment_date = '$appointmentDate' 
                                     AND session_type = '$shift'
                                     AND status != 'canceled'");
        $serialCount = (int)$serialQuery->fetch_assoc()['count'];
        $serialNumber = ($shift == 'morning' ? 'M' : 'E') . str_pad(($serialCount + 1), 3, '0', STR_PAD_LEFT);
        
        $appointmentNumber = 'APT' . date('Ymd') . rand(1000, 9999);
        
        $startTime = $shift == 'morning' ? '09:00:00' : '14:00:00';
        $endTime = $shift == 'morning' ? '13:00:00' : '18:00:00';
        
        // Insert appointment
        $query = "INSERT INTO appointments (
                    appointment_number, patient_id, doctor_id, appointment_date, 
                    start_time, end_time, session_type, appointment_type, 
                    symptoms, special_note, status, payment_status, 
                    payment_method, total_amount, discount, service_id, created_by
                  ) VALUES (
                    '$appointmentNumber', $patientId, $doctorId, '$appointmentDate', 
                    '$startTime', '$endTime', '$shift', '$appointmentType', 
                    '" . addslashes($symptoms) . "', '" . addslashes($specialNote) . "',
                    'scheduled', 'pending', '$paymentMethod', $totalAmount, $discountAmount, 
                    " . ($serviceId ? $serviceId : "NULL") . ", {$_SESSION['user_id']}
                  )";
        
        if ($conn->query($query)) {
            $appointmentId = $conn->insert_id;
            
            // Add to queue
            $queueExists = $conn->query("SHOW TABLES LIKE 'queue'");
            if($queueExists->num_rows > 0) {
                $conn->query("INSERT INTO queue (appointment_id, serial_number, shift, status) 
                             VALUES ($appointmentId, '$serialNumber', '$shift', 'waiting')");
            }
            
            // Create bill
            $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
            $conn->query("INSERT INTO bills (
                            bill_number, patient_id, bill_type, bill_date, 
                            subtotal, discount_amount, total_amount, payment_status, 
                            payment_method, created_by, reference_type, reference_id
                          ) VALUES (
                            '$billNumber', $patientId, 'consultation', CURDATE(), 
                            $subtotal, $discountAmount, $totalAmount, 'pending', 
                            '$paymentMethod', {$_SESSION['user_id']}, 'appointment', $appointmentId
                          )");
            
            // Create commission entry
            $commissionPercentage = 20;
            $commissionAmount = ($consultationFee * $commissionPercentage) / 100;
            $conn->query("INSERT INTO doctor_commissions (
                            doctor_id, reference_type, reference_id, amount, 
                            commission_percentage, commission_amount, status
                          ) VALUES (
                            $doctorId, 'consultation', $appointmentId, $consultationFee, 
                            $commissionPercentage, $commissionAmount, 'pending'
                          )");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Appointment booked successfully!',
                'appointment_number' => $appointmentNumber,
                'appointment_id' => $appointmentId,
                'serial_number' => $serialNumber,
                'total_amount' => number_format($totalAmount, 2)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

// API - GET DOCTOR SERVICES
if ($request == 'api/get-doctor-services') {
    header('Content-Type: application/json');
    $doctorId = (int)$_GET['doctor_id'];
    
    $services = $conn->query("SELECT * FROM doctor_services WHERE doctor_id = $doctorId AND status = 'active'");
    $serviceList = [];
    while($row = $services->fetch_assoc()) {
        $serviceList[] = $row;
    }
    
    echo json_encode(['services' => $serviceList]);
    exit;
}

// ==================== API - UPCOMING APPOINTMENTS ====================
if ($request == 'api/upcoming-appointments') {
    header('Content-Type: application/json');
    $today = date('Y-m-d');
    
    $query = $conn->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.patient_code,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE a.appointment_date > '$today' AND a.status NOT IN ('canceled', 'completed')
                           ORDER BY a.appointment_date ASC, a.start_time ASC
                           LIMIT 20");
    
    $appointments = [];
    while($row = $query->fetch_assoc()) {
        $appointments[] = $row;
    }
    echo json_encode($appointments);
    exit;
}

// ==================== API - CANCEL APPOINTMENT ====================
if ($request == 'api/cancel-appointment') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_POST['appointment_id'];
    
    if($conn->query("UPDATE appointments SET status = 'canceled', cancellation_reason = 'Cancelled by staff' WHERE id = $appointmentId")) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

// ==================== API LOCATION ROUTES ====================
if ($request == 'api/get-districts') {
    header('Content-Type: application/json');
    $division_id = isset($_GET['division_id']) ? (int)$_GET['division_id'] : 0;
    
    if ($division_id > 0) {
        $result = $conn->query("SELECT id, name FROM districts WHERE division_id = $division_id AND status = 1 ORDER BY name");
        $districts = [];
        while($row = $result->fetch_assoc()) {
            $districts[] = $row;
        }
        echo json_encode($districts);
    } else {
        echo json_encode([]);
    }
    exit;
}

if ($request == 'api/get-thanas') {
    header('Content-Type: application/json');
    $district_id = isset($_GET['district_id']) ? (int)$_GET['district_id'] : 0;
    
    if ($district_id > 0) {
        $result = $conn->query("SELECT id, name FROM thanas WHERE district_id = $district_id AND status = 1 ORDER BY name");
        $thanas = [];
        while($row = $result->fetch_assoc()) {
            $thanas[] = $row;
        }
        echo json_encode($thanas);
    } else {
        echo json_encode([]);
    }
    exit;
}

// ==================== API - GET DOCTOR SLOTS ====================
if ($request == 'api/get-doctor-slots') {
    header('Content-Type: application/json');
    $doctorId = (int)$_GET['doctor_id'];
    $date = $conn->real_escape_string($_GET['date']);
    $dayOfWeek = date('l', strtotime($date));
    
    $slots = $conn->query("SELECT id, start_time, end_time, max_patients 
                           FROM doctor_schedule_sessions 
                           WHERE doctor_id = $doctorId AND day_of_week = '$dayOfWeek' AND is_available = 1");
    
    $slotList = [];
    while($row = $slots->fetch_assoc()) {
        $slotList[] = $row;
    }
    echo json_encode($slotList);
    exit;
}

// ==================== API - TODAY'S APPOINTMENTS ====================
if ($request == 'api/today-appointments') {
    header('Content-Type: application/json');
    $today = date('Y-m-d');
    
    $query = $conn->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.patient_code,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE a.appointment_date = '$today'
                           ORDER BY a.start_time ASC");
    
    $appointments = [];
    while($row = $query->fetch_assoc()) {
        $appointments[] = $row;
    }
    echo json_encode($appointments);
    exit;
}

// ==================== API - UPCOMING APPOINTMENTS ====================
if ($request == 'api/upcoming-appointments') {
    header('Content-Type: application/json');
    $today = date('Y-m-d');
    
    $query = $conn->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.patient_code,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE a.appointment_date > '$today' AND a.status NOT IN ('canceled', 'completed')
                           ORDER BY a.appointment_date ASC, a.start_time ASC
                           LIMIT 20");
    
    $appointments = [];
    while($row = $query->fetch_assoc()) {
        $appointments[] = $row;
    }
    echo json_encode($appointments);
    exit;
}

// ==================== API - CANCEL APPOINTMENT ====================
if ($request == 'api/cancel-appointment') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_POST['appointment_id'];
    
    if($conn->query("UPDATE appointments SET status = 'canceled', cancellation_reason = 'Cancelled by staff' WHERE id = $appointmentId")) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

// ==================== API - GET ITEM DETAILS ====================
if ($request == 'api/get-item-details') {
    header('Content-Type: application/json');
    $type = $conn->real_escape_string($_GET['type']);
    $id = (int)$_GET['id'];
    
    if($type == 'medicine') {
        $result = $conn->query("SELECT id, medicine_name as name, selling_price as price, 'medicine' as item_type FROM medicines WHERE id = $id");
    } elseif($type == 'lab_test') {
        $result = $conn->query("SELECT id, test_name as name, price, 'lab_test' as item_type FROM lab_tests WHERE id = $id");
    } else {
        echo json_encode(['success' => false]);
        exit;
    }
    
    if($result && $result->num_rows > 0) {
        echo json_encode(['success' => true, 'item' => $result->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// ==================== BILLING ROUTES ====================
// All Bills - List all bills
if ($request == 'bills/bills') { 
    $billing = new BillingController(); 
    $billing->bills(); 
    exit; 
}

// Create Bill - Form to create new bill
if ($request == 'bills/create') { 
    $billing = new BillingController(); 
    $billing->createBill(); 
    exit; 
}

// Store Bill - Save new bill
if ($request == 'bills/store') { 
    $billing = new BillingController(); 
    $billing->storeBill(); 
    exit; 
}

// View Invoice - View bill details
if (preg_match('/bills\/view\/(\d+)/', $request, $matches)) { 
    $billing = new BillingController(); 
    $billing->viewInvoice($matches[1]); 
    exit; 
}

// Process Payment - Process payment for a bill
if ($request == 'bills/process-payment') { 
    $billing = new BillingController(); 
    $billing->processPayment(); 
    exit; 
}

// Payments - List all payments
if ($request == 'bills/payments') { 
    $billing = new BillingController(); 
    $billing->payments(); 
    exit; 
}

// Export Report - Export bills/payments to CSV
if ($request == 'bills/export') { 
    $billing = new BillingController(); 
    $billing->exportReport(); 
    exit; 
}

// Financial Reports - Revenue and payment analytics
if ($request == 'account/reports') { 
    $billing = new BillingController(); 
    $billing->financialReports(); 
    exit; 
}

// ==================== AUDIT TRAIL ROUTES ====================
if ($request == 'admin/audit-logs') { $audit = new AuditController(); $audit->index(); exit; }
if ($request == 'admin/audit/login-history') { $audit = new AuditController(); $audit->loginHistory(); exit; }
if ($request == 'admin/audit/clear-logs') { $audit = new AuditController(); $audit->clearLogs(); exit; }
if ($request == 'admin/audit/export') { $audit = new AuditController(); $audit->exportLogs(); exit; }

// ==================== SETTINGS ROUTES ====================
if ($request == 'admin/settings') { $settings = new SettingsController(); $settings->index(); exit; }
if ($request == 'admin/settings/update') { $settings = new SettingsController(); $settings->update(); exit; }
if ($request == 'admin/settings/backup') { $settings = new SettingsController(); $settings->backup(); exit; }
if ($request == 'admin/settings/backup-history') { $settings = new SettingsController(); $settings->backupHistory(); exit; }
if ($request == 'admin/settings/delete-backup') { $settings = new SettingsController(); $settings->deleteBackup(); exit; }
if ($request == 'admin/settings/restore-backup') { $settings = new SettingsController(); $settings->restoreBackup(); exit; }


// API - GET DOCTOR SERVICES (Make sure this is at the top of API routes)
if ($request == 'api/get-doctor-services') {
    header('Content-Type: application/json');
    $doctorId = (int)$_GET['doctor_id'];
    
    $services = $conn->query("SELECT id, service_name, service_price, commission_percentage FROM doctor_services WHERE doctor_id = $doctorId AND status = 'active'");
    $serviceList = [];
    while($row = $services->fetch_assoc()) {
        $serviceList[] = $row;
    }
    
    echo json_encode(['services' => $serviceList]);
    exit;
}

// ==================== API - GET APPOINTMENT DETAILS FOR PAYMENT ====================
if ($request == 'api/get-appointment-details') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_GET['appointment_id'];
    
    $query = $conn->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                                  p.id as patient_id,
                                  COALESCE(a.payment_received, 0) as payment_received,
                                  COALESCE(a.payment_status, 'pending') as payment_status
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE a.id = $appointmentId");
    
    $appointment = $query->fetch_assoc();
    
    if($appointment) {
        // Get payment history
        $payments = $conn->query("SELECT * FROM payment_transactions WHERE appointment_id = $appointmentId ORDER BY payment_date DESC");
        $paymentList = [];
        while($row = $payments->fetch_assoc()) {
            $paymentList[] = $row;
        }
        $appointment['payments'] = $paymentList;
        
        echo json_encode(['success' => true, 'data' => $appointment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
    exit;
}

// ==================== API - PROCESS PAYMENT ====================
if ($request == 'api/process-payment') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    $amount = (float)$_POST['amount'];
    $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
    $transactionId = isset($_POST['transaction_id']) ? $conn->real_escape_string($_POST['transaction_id']) : '';
    $note = isset($_POST['note']) ? $conn->real_escape_string($_POST['note']) : '';
    $userId = $_SESSION['user_id'];
    
    // Get appointment details
    $appointment = $conn->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
    if(!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    $currentPaid = (float)($appointment['payment_received'] ?? 0);
    $totalAmount = (float)$appointment['total_amount'];
    $newPaid = $currentPaid + $amount;
    
    if($newPaid > $totalAmount) {
        echo json_encode(['success' => false, 'message' => 'Payment amount exceeds total amount']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert payment record into payment_transactions
        $paymentSql = "INSERT INTO payment_transactions 
                      (appointment_id, patient_id, amount, payment_method, transaction_id, payment_date, received_by, notes, status)
                      VALUES ($appointmentId, {$appointment['patient_id']}, $amount, '$paymentMethod', '$transactionId', NOW(), $userId, '$note', 'completed')";
        
        if(!$conn->query($paymentSql)) {
            throw new Exception("Failed to insert payment: " . $conn->error);
        }
        
        // Update appointment payment_received and payment_status
        $paymentStatus = ($newPaid >= $totalAmount) ? 'paid' : 'partial';
        $updateSql = "UPDATE appointments SET 
                      payment_received = $newPaid,
                      payment_status = '$paymentStatus',
                      last_payment_date = NOW()
                      WHERE id = $appointmentId";
        
        if(!$conn->query($updateSql)) {
            throw new Exception("Failed to update appointment: " . $conn->error);
        }
        
        // Update bill if exists (sync with bills table)
        $bill = $conn->query("SELECT id FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
        if($bill->num_rows > 0) {
            $billData = $bill->fetch_assoc();
            $billId = $billData['id'];
            $updateBill = "UPDATE bills SET 
                          paid_amount = $newPaid,
                          balance_amount = " . ($totalAmount - $newPaid) . ",
                          payment_status = '$paymentStatus'
                          WHERE id = $billId";
            
            if(!$conn->query($updateBill)) {
                throw new Exception("Failed to update bill: " . $conn->error);
            }
            
            // Also insert into payments table
            $paymentNumber = 'PAY' . date('Ymd') . rand(1000, 9999);
            $insertPayment = "INSERT INTO payments (payment_number, bill_id, patient_id, amount, payment_method, 
                              transaction_id, notes, payment_date, received_by, created_at) 
                              VALUES ('$paymentNumber', $billId, {$appointment['patient_id']}, $amount, '$paymentMethod', 
                              '$transactionId', '$note', CURDATE(), $userId, NOW())";
            $conn->query($insertPayment);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'new_paid' => $newPaid,
            'due' => ($totalAmount - $newPaid)
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API - GET FILTERED APPOINTMENTS (Updated with payment fields)
if ($request == 'api/get-filtered-appointments') {
    header('Content-Type: application/json');
    
    $doctorId = isset($_GET['doctor_id']) && $_GET['doctor_id'] != '' ? (int)$_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
    $shift = isset($_GET['shift']) && $_GET['shift'] != '' ? $conn->real_escape_string($_GET['shift']) : '';
    $status = isset($_GET['status']) && $_GET['status'] != '' ? $conn->real_escape_string($_GET['status']) : '';
    
    $query = "SELECT a.*, p.first_name, p.last_name, p.phone, p.patient_code,
                     u.first_name as doctor_fname, u.last_name as doctor_lname, d.specialization,
                     COALESCE(a.payment_received, 0) as payment_received,
                     a.total_amount
              FROM appointments a
              JOIN patients p ON a.patient_id = p.id
              JOIN doctors d ON a.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              WHERE a.appointment_date = '$date'";
    
    if($doctorId > 0) $query .= " AND a.doctor_id = $doctorId";
    if($shift != '') $query .= " AND a.session_type = '$shift'";
    if($status != '') $query .= " AND a.status = '$status'";
    
    $query .= " ORDER BY a.session_type, a.created_at ASC";
    
    $result = $conn->query($query);
    $appointments = [];
    
    while($row = $result->fetch_assoc()) {
        $serialQuery = $conn->query("SELECT COUNT(*) as cnt FROM appointments 
                                     WHERE doctor_id = {$row['doctor_id']} 
                                     AND appointment_date = '{$row['appointment_date']}' 
                                     AND session_type = '{$row['session_type']}'
                                     AND id <= {$row['id']}");
        $cnt = $serialQuery->fetch_assoc()['cnt'];
        $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . str_pad($cnt, 3, '0', STR_PAD_LEFT);
        $row['payment_received'] = $row['payment_received'] ?? 0;
        $appointments[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $appointments]);
    exit;
}

// ==================== API - GET APPOINTMENT DETAILS FOR PAYMENT ====================
if ($request == 'api/get-appointment-details') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_GET['appointment_id'];
    
    $query = $conn->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                                  p.id as patient_id
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE a.id = $appointmentId");
    
    $appointment = $query->fetch_assoc();
    
    if($appointment) {
        // Get payment history
        $payments = $conn->query("SELECT * FROM payment_transactions WHERE appointment_id = $appointmentId ORDER BY payment_date DESC");
        $paymentList = [];
        while($row = $payments->fetch_assoc()) {
            $paymentList[] = $row;
        }
        $appointment['payments'] = $paymentList;
        
        echo json_encode(['success' => true, 'data' => $appointment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
    exit;
}

// ==================== API - PROCESS PAYMENT ====================
if ($request == 'api/process-payment') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    $amount = (float)$_POST['amount'];
    $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
    $transactionId = isset($_POST['transaction_id']) ? $conn->real_escape_string($_POST['transaction_id']) : '';
    $note = isset($_POST['note']) ? $conn->real_escape_string($_POST['note']) : '';
    $userId = $_SESSION['user_id'];
    
    // Get appointment details
    $appointment = $conn->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
    if(!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    $currentPaid = (float)($appointment['payment_received'] ?? 0);
    $totalAmount = (float)$appointment['total_amount'];
    $newPaid = $currentPaid + $amount;
    
    if($newPaid > $totalAmount) {
        echo json_encode(['success' => false, 'message' => 'Payment amount exceeds total amount']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert payment record
        $paymentSql = "INSERT INTO payment_transactions 
                      (appointment_id, patient_id, amount, payment_method, transaction_id, payment_date, received_by, notes, status)
                      VALUES ($appointmentId, {$appointment['patient_id']}, $amount, '$paymentMethod', '$transactionId', NOW(), $userId, '$note', 'completed')";
        
        if(!$conn->query($paymentSql)) {
            throw new Exception("Failed to insert payment");
        }
        
        // Update appointment payment received
        $paymentStatus = ($newPaid >= $totalAmount) ? 'paid' : 'partial';
        $updateSql = "UPDATE appointments SET 
                      payment_received = $newPaid,
                      payment_status = '$paymentStatus',
                      last_payment_date = NOW()
                      WHERE id = $appointmentId";
        
        if(!$conn->query($updateSql)) {
            throw new Exception("Failed to update appointment");
        }
        
        // Update bill if exists
        $bill = $conn->query("SELECT id FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
        if($bill->num_rows > 0) {
            $billData = $bill->fetch_assoc();
            $billId = $billData['id'];
            $conn->query("UPDATE bills SET 
                          paid_amount = $newPaid,
                          balance_amount = " . ($totalAmount - $newPaid) . ",
                          payment_status = '$paymentStatus'
                          WHERE id = $billId");
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'new_paid' => $newPaid,
            'due' => ($totalAmount - $newPaid)
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


// ==================== API - GET FILTERED APPOINTMENTS (Updated) ====================
if ($request == 'api/get-filtered-appointments') {
    header('Content-Type: application/json');
    
    $doctorId = isset($_GET['doctor_id']) && $_GET['doctor_id'] != '' ? (int)$_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
    $shift = isset($_GET['shift']) && $_GET['shift'] != '' ? $conn->real_escape_string($_GET['shift']) : '';
    $status = isset($_GET['status']) && $_GET['status'] != '' ? $conn->real_escape_string($_GET['status']) : '';
    
    $query = "SELECT a.*, p.first_name, p.last_name, p.phone, p.patient_code,
                     u.first_name as doctor_fname, u.last_name as doctor_lname, d.specialization,
                     COALESCE(a.payment_received, 0) as payment_received,
                     a.total_amount, a.payment_status
              FROM appointments a
              JOIN patients p ON a.patient_id = p.id
              JOIN doctors d ON a.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              WHERE a.appointment_date = '$date'";
    
    if($doctorId > 0) $query .= " AND a.doctor_id = $doctorId";
    if($shift != '') $query .= " AND a.session_type = '$shift'";
    if($status != '') $query .= " AND a.status = '$status'";
    
    $query .= " ORDER BY a.session_type, a.created_at ASC";
    
    $result = $conn->query($query);
    $appointments = [];
    
    while($row = $result->fetch_assoc()) {
        $serialQuery = $conn->query("SELECT COUNT(*) as cnt FROM appointments 
                                     WHERE doctor_id = {$row['doctor_id']} 
                                     AND appointment_date = '{$row['appointment_date']}' 
                                     AND session_type = '{$row['session_type']}'
                                     AND id <= {$row['id']}");
        $cnt = $serialQuery->fetch_assoc()['cnt'];
        $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . str_pad($cnt, 3, '0', STR_PAD_LEFT);
        $row['payment_received'] = $row['payment_received'] ?? 0;
        $appointments[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $appointments]);
    exit;
}

// ==================== API - GET APPOINTMENT DETAILS FOR PAYMENT ====================
if ($request == 'api/get-appointment-details') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_GET['appointment_id'];
    
    $query = $conn->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                                  p.id as patient_id
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE a.id = $appointmentId");
    
    $appointment = $query->fetch_assoc();
    
    if($appointment) {
        // Get payment history
        $payments = $conn->query("SELECT * FROM payment_transactions WHERE appointment_id = $appointmentId ORDER BY payment_date DESC");
        $paymentList = [];
        while($row = $payments->fetch_assoc()) {
            $paymentList[] = $row;
        }
        $appointment['payments'] = $paymentList;
        
        echo json_encode(['success' => true, 'data' => $appointment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
    exit;
}

// ==================== API - PROCESS PAYMENT ====================
if ($request == 'api/process-payment') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    $amount = (float)$_POST['amount'];
    $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
    $transactionId = isset($_POST['transaction_id']) ? $conn->real_escape_string($_POST['transaction_id']) : '';
    $note = isset($_POST['note']) ? $conn->real_escape_string($_POST['note']) : '';
    $userId = $_SESSION['user_id'];
    
    // Get appointment details
    $appointment = $conn->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
    if(!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    $currentPaid = (float)($appointment['payment_received'] ?? 0);
    $totalAmount = (float)$appointment['total_amount'];
    $newPaid = $currentPaid + $amount;
    
    if($newPaid > $totalAmount) {
        echo json_encode(['success' => false, 'message' => 'Payment amount exceeds total amount']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert payment record
        $paymentSql = "INSERT INTO payment_transactions 
                      (appointment_id, patient_id, amount, payment_method, transaction_id, payment_date, received_by, notes, status)
                      VALUES ($appointmentId, {$appointment['patient_id']}, $amount, '$paymentMethod', '$transactionId', NOW(), $userId, '$note', 'completed')";
        
        if(!$conn->query($paymentSql)) {
            throw new Exception("Failed to insert payment");
        }
        
        // Update appointment payment received
        $paymentStatus = ($newPaid >= $totalAmount) ? 'paid' : 'partial';
        $updateSql = "UPDATE appointments SET 
                      payment_received = $newPaid,
                      payment_status = '$paymentStatus',
                      last_payment_date = NOW()
                      WHERE id = $appointmentId";
        
        if(!$conn->query($updateSql)) {
            throw new Exception("Failed to update appointment");
        }
        
        // Update bill if exists
        $bill = $conn->query("SELECT id FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
        if($bill->num_rows > 0) {
            $billData = $bill->fetch_assoc();
            $billId = $billData['id'];
            $conn->query("UPDATE bills SET 
                          paid_amount = $newPaid,
                          balance_amount = " . ($totalAmount - $newPaid) . ",
                          payment_status = '$paymentStatus'
                          WHERE id = $billId");
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'new_paid' => $newPaid,
            'due' => ($totalAmount - $newPaid)
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


// ==================== API - CANCEL APPOINTMENT WITH REFUND ====================
if ($request == 'api/cancel-appointment') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    $cancelReason = isset($_POST['cancel_reason']) ? $conn->real_escape_string($_POST['cancel_reason']) : '';
    $userId = $_SESSION['user_id'];
    
    // Get appointment details with payments
    $appointment = $conn->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
    if(!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    // Check if already canceled
    if($appointment['status'] == 'canceled') {
        echo json_encode(['success' => false, 'message' => 'Appointment is already canceled']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        // Get all payments for this appointment
        $payments = $conn->query("SELECT * FROM payment_transactions WHERE appointment_id = $appointmentId AND status = 'completed'");
        
        // If payments exist, refund them
        if($payments->num_rows > 0) {
            while($payment = $payments->fetch_assoc()) {
                $conn->query("UPDATE payment_transactions SET 
                              status = 'refunded', 
                              refunded_at = NOW(), 
                              refunded_by = $userId,
                              cancellation_note = '$cancelReason'
                              WHERE id = {$payment['id']}");
            }
        }
        
        // Update appointment status
        $conn->query("UPDATE appointments SET 
                      status = 'canceled', 
                      cancellation_reason = '$cancelReason',
                      payment_status = 'refunded',
                      payment_received = 0
                      WHERE id = $appointmentId");
        
        // Update bill if exists
        $bill = $conn->query("SELECT id FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
        if($bill->num_rows > 0) {
            $billData = $bill->fetch_assoc();
            $conn->query("UPDATE bills SET 
                          payment_status = 'refunded',
                          paid_amount = 0,
                          balance_amount = total_amount
                          WHERE id = {$billData['id']}");
        }
        
        // Update queue if exists
        $conn->query("UPDATE queue SET status = 'canceled' WHERE appointment_id = $appointmentId");
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Appointment canceled successfully' . ($payments->num_rows > 0 ? ' and payment refunded' : '')]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to cancel appointment: ' . $e->getMessage()]);
    }
    exit;
}

// ==================== API - GET PAYMENT SLIP ====================
if ($request == 'api/get-payment-slip') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_GET['appointment_id'];
    
    // Get latest payment
    $payment = $conn->query("SELECT pt.*, 
                                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                                    a.service_id,
                                    a.total_amount,
                                    a.payment_received
                             FROM payment_transactions pt
                             JOIN appointments a ON pt.appointment_id = a.id
                             JOIN patients p ON pt.patient_id = p.id
                             JOIN doctors d ON a.doctor_id = d.id
                             JOIN users u ON d.user_id = u.id
                             WHERE pt.appointment_id = $appointmentId AND pt.status = 'completed'
                             ORDER BY pt.payment_date DESC LIMIT 1");
    
    if($payment->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'No payment found']);
        exit;
    }
    
    $data = $payment->fetch_assoc();
    
    // Get service name
    $serviceName = 'Consultation';
    if($data['service_id']) {
        $service = $conn->query("SELECT service_name FROM doctor_services WHERE id = {$data['service_id']}");
        if($service->num_rows > 0) {
            $serviceName = $service->fetch_assoc()['service_name'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'receipt_no' => 'RCP-' . str_pad($data['id'], 6, '0', STR_PAD_LEFT),
            'payment_date' => date('d/m/Y h:i A', strtotime($data['payment_date'])),
            'patient_name' => $data['patient_name'],
            'doctor_name' => $data['doctor_name'],
            'service_name' => $serviceName,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'],
            'total_paid' => $data['payment_received'],
            'due_amount' => $data['total_amount'] - $data['payment_received']
        ]
    ]);
    exit;
}

// ==================== API - GET PATIENT DETAILS ====================
if ($request == 'api/get-patient-details') {
    header('Content-Type: application/json');
    $patientId = (int)$_GET['patient_id'];
    
    $result = $conn->query("SELECT * FROM patients WHERE id = $patientId");
    $patient = $result->fetch_assoc();
    
    if($patient) {
        echo json_encode(['success' => true, 'data' => $patient]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Patient not found']);
    }
    exit;
}

// ==================== API - CHECK APPOINTMENT PAYMENTS ====================
if ($request == 'api/check-appointment-payments') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_GET['appointment_id'];
    
    $result = $conn->query("SELECT COUNT(*) as cnt FROM payment_transactions WHERE appointment_id = $appointmentId AND status = 'completed'");
    $row = $result->fetch_assoc();
    
    echo json_encode(['has_payment' => $row['cnt'] > 0]);
    exit;
}

// ==================== API - GET BOOKING RECEIPT ====================
if ($request == 'api/get-booking-receipt') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_GET['appointment_id'];
    
    $result = $conn->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                                  p.patient_code
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.id
                           JOIN doctors d ON a.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           WHERE a.id = $appointmentId");
    $data = $result->fetch_assoc();
    
    if($data) {
        $serialQuery = $conn->query("SELECT COUNT(*) as cnt FROM appointments WHERE doctor_id = {$data['doctor_id']} AND appointment_date = '{$data['appointment_date']}' AND session_type = '{$data['session_type']}' AND id <= {$data['id']}");
        $cnt = $serialQuery->fetch_assoc()['cnt'];
        $serialNumber = ($data['session_type'] == 'morning' ? 'M' : 'E') . str_pad($cnt, 3, '0', STR_PAD_LEFT);
        
        $serviceName = 'Consultation';
        if($data['service_id']) {
            $service = $conn->query("SELECT service_name FROM doctor_services WHERE id = {$data['service_id']}");
            if($service->num_rows > 0) $serviceName = $service->fetch_assoc()['service_name'];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'receipt_no' => 'BKG-' . str_pad($data['id'], 6, '0', STR_PAD_LEFT),
                'appointment_date' => date('d/m/Y', strtotime($data['appointment_date'])),
                'patient_name' => $data['patient_name'],
                'doctor_name' => $data['doctor_name'],
                'service_name' => $serviceName,
                'serial_number' => $serialNumber,
                'amount' => $data['total_amount'],
                'appointment_status' => $data['status']
            ]
        ]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// ==================== API - GET PAYMENT SLIP ====================
if ($request == 'api/get-payment-slip') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_GET['appointment_id'];
    
    $payment = $conn->query("SELECT pt.*, 
                                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                             FROM payment_transactions pt
                             JOIN appointments a ON pt.appointment_id = a.id
                             JOIN patients p ON pt.patient_id = p.id
                             JOIN doctors d ON a.doctor_id = d.id
                             JOIN users u ON d.user_id = u.id
                             WHERE pt.appointment_id = $appointmentId AND pt.status = 'completed'
                             ORDER BY pt.payment_date DESC LIMIT 1");
    
    if($payment->num_rows == 0) {
        echo json_encode(['success' => false]);
        exit;
    }
    
    $data = $payment->fetch_assoc();
    echo json_encode([
        'success' => true,
        'data' => [
            'receipt_no' => 'PAY-' . str_pad($data['id'], 6, '0', STR_PAD_LEFT),
            'payment_date' => date('d/m/Y h:i A', strtotime($data['payment_date'])),
            'patient_name' => $data['patient_name'],
            'doctor_name' => $data['doctor_name'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id']
        ]
    ]);
    exit;
}

// ==================== API - CANCEL APPOINTMENT WITH REFUND ====================
if ($request == 'api/cancel-appointment') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    $cancelReason = isset($_POST['cancel_reason']) ? $conn->real_escape_string($_POST['cancel_reason']) : '';
    $userId = $_SESSION['user_id'];
    
    $appointment = $conn->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
    if(!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    if($appointment['status'] == 'canceled') {
        echo json_encode(['success' => false, 'message' => 'Already canceled']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        $payments = $conn->query("SELECT * FROM payment_transactions WHERE appointment_id = $appointmentId AND status = 'completed'");
        
        if($payments->num_rows > 0) {
            while($payment = $payments->fetch_assoc()) {
                $conn->query("UPDATE payment_transactions SET 
                              status = 'refunded', refunded_at = NOW(), refunded_by = $userId, 
                              cancellation_note = '$cancelReason', refund_reason = 'Appointment Cancelled'
                              WHERE id = {$payment['id']}");
            }
            $conn->query("UPDATE appointments SET status = 'canceled', cancellation_reason = '$cancelReason', 
                          payment_status = 'refunded', payment_received = 0, refund_amount = payment_received,
                          refund_status = 'completed' WHERE id = $appointmentId");
        } else {
            $conn->query("UPDATE appointments SET status = 'canceled', cancellation_reason = '$cancelReason' WHERE id = $appointmentId");
        }
        
        $bill = $conn->query("SELECT id FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
        if($bill->num_rows > 0) {
            $billData = $bill->fetch_assoc();
            $conn->query("UPDATE bills SET payment_status = 'refunded', paid_amount = 0, 
                          balance_amount = total_amount WHERE id = {$billData['id']}");
        }
        
        $conn->query("UPDATE queue SET status = 'canceled' WHERE appointment_id = $appointmentId");
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => $payments->num_rows > 0 ? 'Appointment canceled & payment refunded' : 'Appointment canceled']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed: ' . $e->getMessage()]);
    }
    exit;
}

// Pharmacy POS API Routes
if ($request == 'pharmacy/add-to-cart') { $pharmacy = new PharmacyController(); $pharmacy->addToCart(); exit; }
if ($request == 'pharmacy/get-cart') { $pharmacy = new PharmacyController(); $pharmacy->getCart(); exit; }
if ($request == 'pharmacy/clear-cart') { $pharmacy = new PharmacyController(); $pharmacy->clearCart(); exit; }
if ($request == 'pharmacy/remove-from-cart') { $pharmacy = new PharmacyController(); $pharmacy->removeFromCart(); exit; }
if ($request == 'pharmacy/update-cart') { $pharmacy = new PharmacyController(); $pharmacy->updateCart(); exit; }
if ($request == 'pharmacy/process-sale') { $pharmacy = new PharmacyController(); $pharmacy->processSale(); exit; }



// ==================== BILLING ROUTES ====================
if ($request == 'bills') { $billing = new BillingController(); $billing->bills(); exit; }
if ($request == 'bills/create') { $billing = new BillingController(); $billing->createBill(); exit; }
if ($request == 'bills/store') { $billing = new BillingController(); $billing->storeBill(); exit; }
if (preg_match('/bills\/view\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->viewInvoice($matches[1]); exit; }
if (preg_match('/bills\/print\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->printInvoice($matches[1]); exit; }
if ($request == 'bills/process-payment') { $billing = new BillingController(); $billing->processPayment(); exit; }
if ($request == 'bills/payments') { $billing = new BillingController(); $billing->payments(); exit; }
if ($request == 'bills/export') { $billing = new BillingController(); $billing->exportBills(); exit; }
if ($request == 'account/reports') { $billing = new BillingController(); $billing->financialReports(); exit; }
if (preg_match('/bills\/email\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->emailInvoice($matches[1]); exit; }


// ==================== API FOR BILLING ====================
if ($request == 'api/get-services') {
    header('Content-Type: application/json');
    $result = $conn->query("SELECT id, service_name, service_price FROM pharmacy_services WHERE status = 'active' ORDER BY service_name");
    $services = [];
    while($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    echo json_encode(['services' => $services]);
    exit;
}

if ($request == 'api/get-medicines') {
    header('Content-Type: application/json');
    $result = $conn->query("SELECT id, medicine_name, selling_price FROM medicines WHERE status = 'active' ORDER BY medicine_name");
    $medicines = [];
    while($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
    echo json_encode(['medicines' => $medicines]);
    exit;
}

if ($request == 'api/get-lab-tests') {
    header('Content-Type: application/json');
    $result = $conn->query("SELECT id, test_name, price FROM lab_tests WHERE status = 'active' ORDER BY test_name");
    $tests = [];
    while($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
    echo json_encode(['tests' => $tests]);
    exit;
}

// ==================== API FOR BILLING ====================
if ($request == 'api/get-services') {
    header('Content-Type: application/json');
    $result = $conn->query("SELECT id, service_name, service_price FROM pharmacy_services WHERE status = 'active' ORDER BY service_name");
    $services = [];
    while($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    echo json_encode(['services' => $services]);
    exit;
}

if ($request == 'api/get-medicines') {
    header('Content-Type: application/json');
    $result = $conn->query("SELECT id, medicine_name, selling_price FROM medicines WHERE status = 'active' ORDER BY medicine_name");
    $medicines = [];
    while($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
    echo json_encode(['medicines' => $medicines]);
    exit;
}

if ($request == 'api/get-lab-tests') {
    header('Content-Type: application/json');
    $result = $conn->query("SELECT id, test_name, price FROM lab_tests WHERE status = 'active' ORDER BY test_name");
    $tests = [];
    while($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
    echo json_encode(['tests' => $tests]);
    exit;
}

// ==================== API - GET PAYMENT HISTORY ====================
if ($request == 'api/get-payment-history') {
    header('Content-Type: application/json');
    $billId = (int)$_GET['bill_id'];
    
    $payments = $conn->query("SELECT p.*, u.first_name as received_by_name 
                              FROM payments p
                              LEFT JOIN users u ON p.received_by = u.id
                              WHERE p.bill_id = $billId
                              ORDER BY p.payment_date DESC");
    
    $paymentList = [];
    while($row = $payments->fetch_assoc()) {
        $paymentList[] = $row;
    }
    
    echo json_encode(['success' => true, 'payments' => $paymentList]);
    exit;
}



// Add these routes for payment functionality
if ($parts[0] === 'bills' && isset($parts[1]) && $parts[1] === 'get-payment-history') {
    $controller_name = 'BillingController';
    $method_name = 'getPaymentHistory';
    $params = [];
}
elseif ($parts[0] === 'bills' && isset($parts[1]) && $parts[1] === 'process-payment') {
    $controller_name = 'BillingController';
    $method_name = 'processPayment';
    $params = [];
}


// ==================== BILLING ROUTES ====================
// All Bills - List all bills
if ($request == 'bills') { $billing = new BillingController(); $billing->bills(); exit; }
if ($request == 'bills/bills') { $billing = new BillingController(); $billing->bills(); exit; }

// Create Bill - Form to create new bill
if ($request == 'bills/create') { $billing = new BillingController(); $billing->createBill(); exit; }

// Store Bill - Save new bill
if ($request == 'bills/store') { $billing = new BillingController(); $billing->storeBill(); exit; }

// View Invoice - View bill details
if (preg_match('/bills\/view\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->viewInvoice($matches[1]); exit; }

// Print Invoice - Print bill
if (preg_match('/bills\/print\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->printInvoice($matches[1]); exit; }

// Process Payment - Process payment for a bill
if ($request == 'bills/process-payment') { $billing = new BillingController(); $billing->processPayment(); exit; }

// Get Payment History - IMPORTANT: Add this route
if ($request == 'bills/get-payment-history') { $billing = new BillingController(); $billing->getPaymentHistory(); exit; }

// Export Bills - Export to CSV
if ($request == 'bills/export') { $billing = new BillingController(); $billing->exportBills(); exit; }

// Payments list
if ($request == 'bills/payments') { $billing = new BillingController(); $billing->payments(); exit; }

// Financial Reports
if ($request == 'account/reports') { $billing = new BillingController(); $billing->financialReports(); exit; }

// Email Invoice
if (preg_match('/bills\/email\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->emailInvoice($matches[1]); exit; }

// ==================== API - GET PAYMENT HISTORY ====================
if ($request == 'api/get-payment-history') {
    header('Content-Type: application/json');
    $billId = (int)$_GET['bill_id'];
    
    $payments = $conn->query("SELECT p.*, u.first_name as received_by_name 
                              FROM payments p
                              LEFT JOIN users u ON p.received_by = u.id
                              WHERE p.bill_id = $billId
                              ORDER BY p.payment_date DESC");
    
    $paymentList = [];
    while($row = $payments->fetch_assoc()) {
        $paymentList[] = $row;
    }
    
    echo json_encode(['success' => true, 'payments' => $paymentList]);
    exit;
}

// ==================== API - GET DOCTORS ====================
if ($request == 'api/get-doctors') {
    header('Content-Type: application/json');
    
    $query = "SELECT d.id, CONCAT(u.first_name, ' ', u.last_name) as name 
              FROM doctors d
              JOIN users u ON d.user_id = u.id
              WHERE d.status = 'active'
              ORDER BY u.first_name ASC";
    $result = $conn->query($query);
    $doctors = [];
    while($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }
    echo json_encode(['success' => true, 'doctors' => $doctors]);
    exit;
}

// ==================== API - GET DOCTOR SERVICES ====================
if ($request == 'api/get-doctor-services') {
    header('Content-Type: application/json');
    $doctorId = (int)$_GET['doctor_id'];
    
    $query = "SELECT ds.*, d.id as doctor_id, CONCAT(u.first_name, ' ', u.last_name) as doctor_name
              FROM doctor_services ds
              JOIN doctors d ON ds.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              WHERE ds.status = 'active' AND ds.doctor_id = $doctorId
              ORDER BY ds.service_name ASC";
    $result = $conn->query($query);
    $services = [];
    while($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
    echo json_encode(['success' => true, 'services' => $services]);
    exit;
}


// ==================== API - PROCESS PAYMENT ====================
if ($request == 'api/process-payment') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    $amount = (float)$_POST['amount'];
    $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
    $transactionId = isset($_POST['transaction_id']) ? $conn->real_escape_string($_POST['transaction_id']) : '';
    $note = isset($_POST['note']) ? $conn->real_escape_string($_POST['note']) : '';
    $userId = $_SESSION['user_id'];
    
    // Get appointment details
    $appointment = $conn->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
    if(!$appointment) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    $currentPaid = (float)($appointment['payment_received'] ?? 0);
    $totalAmount = (float)$appointment['total_amount'];
    $newPaid = $currentPaid + $amount;
    
    if($newPaid > $totalAmount) {
        echo json_encode(['success' => false, 'message' => 'Payment amount exceeds total amount']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert payment record
        $paymentSql = "INSERT INTO payment_transactions 
                      (appointment_id, patient_id, amount, payment_method, transaction_id, payment_date, received_by, notes, status)
                      VALUES ($appointmentId, {$appointment['patient_id']}, $amount, '$paymentMethod', '$transactionId', NOW(), $userId, '$note', 'completed')";
        
        if(!$conn->query($paymentSql)) {
            throw new Exception("Failed to insert payment");
        }
        
        // Update appointment payment received
        $paymentStatus = ($newPaid >= $totalAmount) ? 'paid' : 'partial';
        $updateSql = "UPDATE appointments SET 
                      payment_received = $newPaid,
                      payment_status = '$paymentStatus',
                      last_payment_date = NOW()
                      WHERE id = $appointmentId";
        
        if(!$conn->query($updateSql)) {
            throw new Exception("Failed to update appointment");
        }
        
        // Update bill if exists
        $bill = $conn->query("SELECT id FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
        if($bill->num_rows > 0) {
            $billData = $bill->fetch_assoc();
            $billId = $billData['id'];
            $conn->query("UPDATE bills SET 
                          paid_amount = $newPaid,
                          balance_amount = " . ($totalAmount - $newPaid) . ",
                          payment_status = '$paymentStatus'
                          WHERE id = $billId");
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'new_paid' => $newPaid,
            'due' => ($totalAmount - $newPaid)
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


// ==================== API - QUEUE STATISTICS ====================
if ($request == 'api/queue-stats') {
    header('Content-Type: application/json');
    $today = date('Y-m-d');
    
    // Get waiting count
    $waitingQuery = "SELECT COUNT(*) as cnt FROM queue WHERE status = 'waiting'";
    $waitingResult = $conn->query($waitingQuery);
    $waiting = $waitingResult->fetch_assoc()['cnt'];
    
    // Get in progress count
    $progressQuery = "SELECT COUNT(*) as cnt FROM queue WHERE status = 'in_progress'";
    $progressResult = $conn->query($progressQuery);
    $inProgress = $progressResult->fetch_assoc()['cnt'];
    
    // Get completed appointments today
    $completedQuery = "SELECT COUNT(*) as cnt FROM appointments WHERE appointment_date = '$today' AND status = 'completed'";
    $completedResult = $conn->query($completedQuery);
    $completed = $completedResult->fetch_assoc()['cnt'];
    
    echo json_encode([
        'success' => true,
        'waiting' => (int)$waiting,
        'in_progress' => (int)$inProgress,
        'completed' => (int)$completed
    ]);
    exit;
}

// ==================== API - PATIENT APPOINTMENTS (Updated) ====================
if ($request == 'api/patient-appointments') {
    header('Content-Type: application/json');
    $patientId = (int)$_GET['patient_id'];
    $date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
    
    $query = "SELECT a.*, u.first_name as doctor_fname, u.last_name as doctor_lname, d.specialization
              FROM appointments a
              JOIN doctors d ON a.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              WHERE a.patient_id = $patientId 
              AND a.appointment_date = '$date'
              AND a.status NOT IN ('canceled', 'completed')
              ORDER BY a.start_time ASC";
    
    $result = $conn->query($query);
    $appointments = [];
    while($row = $result->fetch_assoc()) {
        $row['doctor_name'] = $row['doctor_fname'] . ' ' . $row['doctor_lname'];
        $appointments[] = $row;
    }
    
    echo json_encode($appointments);
    exit;
}

// ==================== API - QUEUE STATUS (Updated) ====================
if ($request == 'api/queue-status') {
    $today = date('Y-m-d');
    
    $queue = $conn->query("SELECT q.*, a.appointment_number, a.session_type as shift,
                                  p.first_name, p.last_name, p.patient_code,
                                  u.first_name as doctor_fname, u.last_name as doctor_lname
                          FROM queue q
                          JOIN appointments a ON q.appointment_id = a.id
                          JOIN patients p ON a.patient_id = p.id
                          JOIN doctors d ON a.doctor_id = d.id
                          JOIN users u ON d.user_id = u.id
                          WHERE DATE(q.created_at) = '$today' AND q.status != 'completed'
                          ORDER BY FIELD(q.status, 'in_progress', 'waiting'), q.serial_number ASC");
    
    ob_start();
    ?>
    <div class="list-group">
        <?php if($queue->num_rows > 0): ?>
            <?php while($row = $queue->fetch_assoc()): ?>
            <div class="list-group-item queue-item" data-queue-id="<?php echo $row['id']; ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-<?php echo $row['status'] == 'in_progress' ? 'success' : 'warning'; ?> fs-5 p-2">
                            #<?php echo $row['serial_number']; ?>
                        </span>
                    </div>
                    <div class="flex-grow-1 mx-3">
                        <strong><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></strong>
                        <br><small class="text-muted">Dr. <?php echo $row['doctor_fname'] . ' ' . $row['doctor_lname']; ?></small>
                        <br><small class="text-muted"><?php echo ucfirst($row['shift']); ?> Shift</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-<?php echo $row['status'] == 'in_progress' ? 'success' : 'warning'; ?> mb-2">
                            <?php echo $row['status'] == 'in_progress' ? 'IN PROGRESS' : 'WAITING'; ?>
                        </span>
                        <?php if($row['status'] == 'waiting'): ?>
                        <div class="mt-1">
                            <button class="btn btn-sm btn-success call-patient" data-id="<?php echo $row['id']; ?>" data-serial="<?php echo $row['serial_number']; ?>">
                                <i class="fas fa-bullhorn me-1"></i>Call
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-4 text-muted">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <p>Queue is empty</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $html = ob_get_clean();
    echo $html;
    exit;
}

// ==================== RECEPTION - COMPLETE CONSULTATION ====================
if ($request == 'reception/complete-consultation') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    $userId = $_SESSION['user_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update appointment status to completed
        $updateAppointment = "UPDATE appointments SET status = 'completed', updated_at = NOW() WHERE id = $appointmentId";
        if (!$conn->query($updateAppointment)) {
            throw new Exception("Failed to update appointment: " . $conn->error);
        }
        
        // Update queue status to completed
        $updateQueue = "UPDATE queue SET status = 'completed', end_time = NOW() WHERE appointment_id = $appointmentId";
        if (!$conn->query($updateQueue)) {
            throw new Exception("Failed to update queue: " . $conn->error);
        }
        
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Consultation completed successfully']);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==================== RECEPTION - DO CHECK-IN ====================
if ($request == 'reception/do-checkin') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    
    // Get appointment details
    $apptQuery = "SELECT a.*, d.id as doctor_id, d.consultation_fee 
                  FROM appointments a
                  JOIN doctors d ON a.doctor_id = d.id
                  WHERE a.id = $appointmentId";
    $apptResult = $conn->query($apptQuery);
    
    if($apptResult->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    
    $appointment = $apptResult->fetch_assoc();
    
    // Check if already checked in
    if($appointment['status'] == 'checked_in' || $appointment['status'] == 'in_progress') {
        echo json_encode(['success' => false, 'message' => 'Patient already checked in']);
        exit;
    }
    
    // Calculate serial number for this session
    $serialQuery = $conn->query("SELECT COUNT(*) as cnt FROM appointments 
                                WHERE doctor_id = {$appointment['doctor_id']} 
                                AND appointment_date = '{$appointment['appointment_date']}' 
                                AND session_type = '{$appointment['session_type']}'
                                AND status NOT IN ('canceled')
                                AND id <= $appointmentId");
    $serialCount = $serialQuery->fetch_assoc()['cnt'];
    $serialNumber = ($appointment['session_type'] == 'morning' ? 'M' : 'E') . str_pad($serialCount, 3, '0', STR_PAD_LEFT);
    
    // Update appointment status
    $updateAppt = "UPDATE appointments SET status = 'checked_in' WHERE id = $appointmentId";
    $conn->query($updateAppt);
    
    // Check if already in queue
    $checkQueue = $conn->query("SELECT id FROM queue WHERE appointment_id = $appointmentId");
    if($checkQueue->num_rows == 0) {
        // Add to queue
        $insertQueue = "INSERT INTO queue (appointment_id, serial_number, shift, status, check_in_time, created_at) 
                        VALUES ($appointmentId, '$serialNumber', '{$appointment['session_type']}', 'waiting', NOW(), NOW())";
        $conn->query($insertQueue);
        $queueId = $conn->insert_id;
    } else {
        $queueRow = $checkQueue->fetch_assoc();
        $queueId = $queueRow['id'];
        $conn->query("UPDATE queue SET status = 'waiting', check_in_time = NOW() WHERE id = $queueId");
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Patient checked in successfully',
        'queue_number' => $serialNumber,
        'serial_number' => $serialNumber,
        'shift' => $appointment['session_type']
    ]);
    exit;
}

// Add this route for filtered queue
if ($request == 'reception/get-filtered-queue') {
    $reception = new ReceptionController();
    $reception->getFilteredQueue();
    exit;
}

// ==================== RECEPTION ROUTES ====================
if ($request == 'reception/dashboard') { $reception = new ReceptionController(); $reception->dashboard(); exit; }
if ($request == 'reception/check-in') { $reception = new ReceptionController(); $reception->checkIn(); exit; }
if ($request == 'reception/do-checkin') { $reception = new ReceptionController(); $reception->doCheckIn(); exit; }
if ($request == 'reception/queue') { $reception = new ReceptionController(); $reception->queue(); exit; }
if ($request == 'reception/call-patient') { $reception = new ReceptionController(); $reception->callPatient(); exit; }
if ($request == 'reception/complete-consultation') { $reception = new ReceptionController(); $reception->completeConsultation(); exit; }
if ($request == 'reception/search') { $reception = new ReceptionController(); $reception->searchPatient(); exit; }
if ($request == 'reception/get-patient-appointments') { $reception = new ReceptionController(); $reception->getPatientAppointments(); exit; }
if ($request == 'reception/get-queue-status') { $reception = new ReceptionController(); $reception->getQueueStatus(); exit; }
if ($request == 'reception/get-filtered-queue') { $reception = new ReceptionController(); $reception->getFilteredQueue(); exit; }
if ($request == 'reception/get-queue-stats') { $reception = new ReceptionController(); $reception->getQueueStats(); exit; }

// ==================== 404 ====================
http_response_code(404);
$controller = new Controller();
$content = "<div class='text-center' style='padding:50px;'><h1 class='text-danger'>404</h1><h3>Page Not Found</h3><p>The page you're looking for doesn't exist.</p><a href='" . BASE_URL . "/admin/dashboard' class='btn btn-primary'>Back to Dashboard</a></div>";
$controller->renderLayout('404 - Page Not Found', $content);
exit;
?>