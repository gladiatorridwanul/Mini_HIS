<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Define constants first
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

// ==================== DEFINE $request FIRST ====================
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

// ==================== DEFINE $path FOR ROUTING ====================
$path = '/' . $request;
if ($path == '/') {
    $path = '/login';
}

// Debug logging (AFTER $request is defined)
error_log("Request: " . $request);
error_log("Path: " . $path);

// ==================== AUTOLOADER - WITH BOTH CONTROLLER LOCATIONS ====================
spl_autoload_register(function($className) {
    $basePath = dirname(__DIR__);
    
    // Track loaded classes to prevent duplicate loading
    static $loadedClasses = [];
    
    // If class already loaded, skip
    if (isset($loadedClasses[$className])) {
        return;
    }
    
    // Special handling for Controller class - load from core first
    if ($className === 'Controller') {
        // Try core/Controller.php first
        $coreFile = $basePath . '/core/Controller.php';
        if (file_exists($coreFile)) {
            if (!class_exists('Controller', false)) {
                require_once $coreFile;
                $loadedClasses[$className] = true;
                return;
            } else {
                $loadedClasses[$className] = true;
                return;
            }
        }
        
        // If not in core, try app/controllers/Controller.php
        $appFile = $basePath . '/app/controllers/Controller.php';
        if (file_exists($appFile)) {
            if (!class_exists('Controller', false)) {
                require_once $appFile;
                $loadedClasses[$className] = true;
                return;
            } else {
                $loadedClasses[$className] = true;
                return;
            }
        }
        return;
    }
    
    // For other classes - check directories in order
    $directories = [
        $basePath . '/core/',
        $basePath . '/app/controllers/',
        $basePath . '/app/models/',
        $basePath . '/app/helpers/',
        $basePath . '/app/middleware/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $className . '.php';
        if (file_exists($file)) {
            // Check if class already exists before including
            if (!class_exists($className, false)) {
                require_once $file;
                $loadedClasses[$className] = true;
                return;
            } else {
                // Class already exists, mark as loaded and skip
                $loadedClasses[$className] = true;
                return;
            }
        }
    }
});

// ================================================================
// DATABASE CLASS - Using existing $conn connection
// ================================================================
if (!class_exists('Database')) {
    class Database {
        private static $instance = null;
        private $connection = null;
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function setConnection($conn) {
            $this->connection = $conn;
        }
        
        public function getConnection() {
            return $this->connection;
        }
        
        public function query($sql, $params = []) {
            global $conn;
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return new DatabaseResult([]);
            }
            
            if (!empty($params)) {
                $types = '';
                $bindParams = [];
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param) || is_double($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 's';
                    }
                    $bindParams[] = $param;
                }
                $stmt->bind_param($types, ...$bindParams);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            return new DatabaseResult($data);
        }
        
        public function execute($sql, $params = []) {
            global $conn;
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            
            if (!empty($params)) {
                $types = '';
                $bindParams = [];
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_float($param) || is_double($param)) {
                        $types .= 'd';
                    } elseif (is_string($param)) {
                        $types .= 's';
                    } else {
                        $types .= 's';
                    }
                    $bindParams[] = $param;
                }
                $stmt->bind_param($types, ...$bindParams);
            }
            
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        
        public function lastInsertId() {
            global $conn;
            return $conn->insert_id;
        }
        
        public function affectedRows() {
            global $conn;
            return $conn->affected_rows;
        }
        
        public function beginTransaction() {
            global $conn;
            $conn->begin_transaction();
        }
        
        public function commit() {
            global $conn;
            $conn->commit();
        }
        
        public function rollback() {
            global $conn;
            $conn->rollback();
        }
        
        public function escapeString($string) {
            global $conn;
            return $conn->real_escape_string($string);
        }
    }
}

// DatabaseResult class for fetch methods
if (!class_exists('DatabaseResult')) {
    class DatabaseResult {
        private $data;
        private $index = 0;
        
        public function __construct($data) {
            $this->data = $data;
        }
        
        public function fetch() {
            if (isset($this->data[$this->index])) {
                return $this->data[$this->index++];
            }
            return null;
        }
        
        public function fetchAll() {
            return $this->data;
        }
        
        public function rowCount() {
            return count($this->data);
        }
    }
}

// ================================================================
// SET DATABASE CONNECTION
// ================================================================
if (class_exists('Database')) {
    $db = Database::getInstance();
    $db->setConnection($conn);
}

// Make db connection available to controllers
$GLOBALS['db_conn'] = $conn;

// ================================================================
// CONTROLLER CLASS - Load from core if not already loaded
// ================================================================
if (!class_exists('Controller')) {
    // Try core/Controller.php first
    if (file_exists(BASE_PATH . '/core/Controller.php')) {
        require_once BASE_PATH . '/core/Controller.php';
    } 
    // If not in core, try app/controllers/Controller.php
    elseif (file_exists(BASE_PATH . '/app/controllers/Controller.php')) {
        require_once BASE_PATH . '/app/controllers/Controller.php';
    }
}

// ================================================================
// API ROUTES
// ================================================================

// API - BOOK APPOINTMENT
if ($request == 'api/book-appointment') {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $patientId = (int)$_POST['patient_id'];
        $doctorId = (int)$_POST['doctor_id'];
        $serviceId = (int)$_POST['service_id'];
        $additionalServiceId = (int)$_POST['additional_service_id'];
        $appointmentDate = $conn->real_escape_string($_POST['appointment_date'] ?? '');
        $shift = $conn->real_escape_string($_POST['shift'] ?? '');
        $appointmentType = $conn->real_escape_string($_POST['appointment_type'] ?? 'regular');
        $symptoms = $conn->real_escape_string($_POST['symptoms'] ?? '');
        $specialNote = $conn->real_escape_string($_POST['special_note'] ?? '');
        $paymentMethod = $conn->real_escape_string($_POST['payment_method'] ?? 'cash');
        $discountPercent = (float)($_POST['discount_percent'] ?? 0);
        $totalAmount = (float)($_POST['total_amount'] ?? 0);
        $discountAmount = (float)($_POST['discount_amount'] ?? 0);
        $subtotal = (float)($_POST['subtotal'] ?? 0);
        
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
            $serviceResult = $conn->query("SELECT service_price FROM doctor_services WHERE id = $serviceId");
            if($serviceResult && $serviceResult->num_rows > 0) {
                $serviceFee = (float)$serviceResult->fetch_assoc()['service_price'];
            }
        }
        
        $additionalFee = 0;
        if($additionalServiceId > 0) {
            $additionalResult = $conn->query("SELECT service_price FROM additional_services WHERE id = $additionalServiceId");
            if($additionalResult && $additionalResult->num_rows > 0) {
                $additionalFee = (float)$additionalResult->fetch_assoc()['service_price'];
            }
        }
        
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
                            $subtotal, $discountAmount, $totalAmount, 'pending', 
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

// API - GET FILTERED APPOINTMENTS
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
            $serialQuery = $conn->query("SELECT COUNT(*) as cnt FROM appointments 
                                         WHERE doctor_id = {$row['doctor_id']} 
                                         AND appointment_date = '{$row['appointment_date']}' 
                                         AND session_type = '{$row['session_type']}'
                                         AND id <= {$row['id']}");
            $cnt = $serialQuery->fetch_assoc()['cnt'];
            $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . str_pad($cnt, 3, '0', STR_PAD_LEFT);
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

// API - GET APPOINTMENT DETAILS
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

// API - CANCEL APPOINTMENT
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

// API - PROCESS PAYMENT
if ($request == 'api/process-payment') {
    header('Content-Type: application/json');
    
    $appointmentId = (int)$_POST['appointment_id'];
    $amount = (float)$_POST['amount'];
    $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
    $transactionId = isset($_POST['transaction_id']) ? $conn->real_escape_string($_POST['transaction_id']) : '';
    $note = isset($_POST['note']) ? $conn->real_escape_string($_POST['note']) : '';
    $userId = $_SESSION['user_id'];
    
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
    
    $conn->begin_transaction();
    
    try {
        $paymentSql = "INSERT INTO payment_transactions 
                      (appointment_id, patient_id, amount, payment_method, transaction_id, payment_date, received_by, notes, status)
                      VALUES ($appointmentId, {$appointment['patient_id']}, $amount, '$paymentMethod', '$transactionId', NOW(), $userId, '$note', 'completed')";
        if(!$conn->query($paymentSql)) {
            throw new Exception("Failed to insert payment");
        }
        
        $paymentStatus = ($newPaid >= $totalAmount) ? 'paid' : 'partial';
        $updateSql = "UPDATE appointments SET 
                      payment_received = $newPaid,
                      payment_status = '$paymentStatus',
                      last_payment_date = NOW()
                      WHERE id = $appointmentId";
        if(!$conn->query($updateSql)) {
            throw new Exception("Failed to update appointment");
        }
        
        $bill = $conn->query("SELECT id FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
        if($bill->num_rows > 0) {
            $billData = $bill->fetch_assoc();
            $billId = $billData['id'];
            $conn->query("UPDATE bills SET 
                          paid_amount = $newPaid,
                          balance_amount = " . ($totalAmount - $newPaid) . ",
                          payment_status = '$paymentStatus'
                          WHERE id = $billId");
            
            $paymentNumber = 'PAY' . date('Ymd') . rand(1000, 9999);
            $conn->query("INSERT INTO payments (payment_number, bill_id, patient_id, amount, payment_method, 
                          transaction_id, notes, payment_date, received_by, created_at) 
                          VALUES ('$paymentNumber', $billId, {$appointment['patient_id']}, $amount, '$paymentMethod', 
                          '$transactionId', '$note', CURDATE(), $userId, NOW())");
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

// API - GET PAYMENT HISTORY
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

// API - GET PATIENT DETAILS
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

// API - GET DOCTORS
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

// API - GET DOCTOR AVAILABILITY
// API - GET DOCTOR AVAILABILITY (Schedule)
if ($request == 'api/get-doctor-availability') {
    $appointment = new AppointmentController();
    $appointment->getDoctorAvailability();
    exit;
}

if ($request == 'api/get-doctor-availability') {
    header('Content-Type: application/json');
    $doctorId = (int)$_GET['doctor_id'];
    $date = $conn->real_escape_string($_GET['date']);
    $shift = $conn->real_escape_string($_GET['shift']);
    $dayOfWeek = date('l', strtotime($date));
    
    $schedule = $conn->query("SELECT start_time, end_time, slot_duration, max_patients 
                             FROM doctor_schedule_sessions 
                             WHERE doctor_id = $doctorId 
                             AND day_of_week = '$dayOfWeek' 
                             AND session_type = '$shift' 
                             AND is_available = 1");
    
    if($schedule->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Doctor not available on this day/shift', 'has_availability' => false]);
        exit;
    }
    
    $row = $schedule->fetch_assoc();
    $startTime = $row['start_time'];
    $endTime = $row['end_time'];
    $slotDuration = $row['slot_duration'];
    $maxPatients = $row['max_patients'];
    
    $start = new DateTime($startTime);
    $end = new DateTime($endTime);
    $sessionMinutes = ($end->getTimestamp() - $start->getTimestamp()) / 60;
    $totalSlots = floor($sessionMinutes / $slotDuration);
    
    $booked = $conn->query("SELECT COUNT(*) as booked_count FROM appointments 
                           WHERE doctor_id = $doctorId 
                           AND appointment_date = '$date' 
                           AND session_type = '$shift'
                           AND status != 'canceled'");
    $bookedCount = (int)$booked->fetch_assoc()['booked_count'];
    $availableSlots = $maxPatients - $bookedCount;
    $nextSerial = $bookedCount + 1;
    
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

// API - GET DISTRICTS
if ($request == 'api/get-districts') {
    header('Content-Type: application/json');
    $division_id = isset($_GET['division_id']) ? (int)$_GET['division_id'] : 0;
    if ($division_id > 0) {
        $result = $conn->query("SELECT id, name FROM districts WHERE division_id = $division_id AND status = 1 ORDER BY name");
        $districts = [];
        while($row = $result->fetch_assoc()) { $districts[] = $row; }
        echo json_encode($districts);
    } else { echo json_encode([]); }
    exit;
}

// API - GET THANAS
if ($request == 'api/get-thanas') {
    header('Content-Type: application/json');
    $district_id = isset($_GET['district_id']) ? (int)$_GET['district_id'] : 0;
    if ($district_id > 0) {
        $result = $conn->query("SELECT id, name FROM thanas WHERE district_id = $district_id AND status = 1 ORDER BY name");
        $thanas = [];
        while($row = $result->fetch_assoc()) { $thanas[] = $row; }
        echo json_encode($thanas);
    } else { echo json_encode([]); }
    exit;
}

// API - GET BILL PATIENT (for payment modal)
if ($request == 'api/get-bill-patient') {
    header('Content-Type: application/json');
    $billId = isset($_GET['bill_id']) ? (int)$_GET['bill_id'] : 0;
    if($billId) {
        $result = $conn->query("SELECT patient_id FROM bills WHERE id = $billId");
        if($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            echo json_encode(['success' => true, 'patient_id' => $row['patient_id']]);
        } else {
            echo json_encode(['success' => false]);
        }
    } else {
        echo json_encode(['success' => false]);
    }
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
if ($request == 'admin/dashboard') { $dashboard = new DashboardController(); $dashboard->index(); exit; }

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

// ==================== PAYROLL API ROUTES ====================

// Generate all payroll
if ($request == 'api/payroll/generate-all') {
    header('Content-Type: application/json');
    $user = new UserController();
    $user->apiGenerateAllPayroll();
    exit;
}

// Get payroll details for editing
if ($request == 'api/payroll/get-details') {
    header('Content-Type: application/json');
    $user = new UserController();
    $user->apiGetPayrollDetails();
    exit;
}

// Update salary
if ($request == 'api/payroll/update-salary') {
    header('Content-Type: application/json');
    $user = new UserController();
    $user->apiUpdateSalary();
    exit;
}

// Mark as paid
if ($request == 'api/payroll/mark-paid') {
    header('Content-Type: application/json');
    $user = new UserController();
    $user->apiMarkPaid();
    exit;
}

// Print payslip
if ($request == 'api/payroll/print-slip') {
    $user = new UserController();
    $user->apiPrintPayslip();
    exit;
}

// Export payroll
if ($request == 'api/payroll/export') {
    $user = new UserController();
    $user->apiExportPayroll();
    exit;
}

// ==================== PATIENT MANAGEMENT ====================
if ($request == 'patient/list') { $patient = new PatientController(); $patient->index(); exit; }
if ($request == 'patient/register') { $patient = new PatientController(); $patient->register(); exit; }
if ($request == 'patient/store') { $patient = new PatientController(); $patient->store(); exit; }
if ($request == 'patient/id-card') { $patient = new PatientController(); $patient->idCard(); exit; }
if ($request == 'patient/view') { $patient = new PatientController(); $patient->show(); exit; }
if ($request == 'patient/show') { $patient = new PatientController(); $patient->show(); exit; }
if ($request == 'patient/edit') { $patient = new PatientController(); $patient->edit(); exit; }
if ($request == 'patient/update') { $patient = new PatientController(); $patient->update(); exit; }
if ($request == 'patient/delete') { $patient = new PatientController(); $patient->delete(); exit; }
if ($request == 'patient/book-appointment') { $patient = new PatientController(); $patient->bookAppointment(); exit; }

// ==================== FIXED: QUICK PATIENT REGISTRATION ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $request == 'patient/quick-store') {
    require_once BASE_PATH . '/app/controllers/PatientController.php';
    $controller = new PatientController();
    $controller->quickStore();
    exit;
}

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
if ($request == 'reception/get-patient-appointments') { $reception = new ReceptionController(); $reception->getPatientAppointments(); exit; }
if ($request == 'reception/get-queue-status') { $reception = new ReceptionController(); $reception->getQueueStatus(); exit; }
if ($request == 'reception/get-filtered-queue') { $reception = new ReceptionController(); $reception->getFilteredQueue(); exit; }
if ($request == 'reception/get-queue-stats') { $reception = new ReceptionController(); $reception->getQueueStats(); exit; }

// Laboratory Routes
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
if ($request == 'lab/sample-collection') { $lab = new LaboratoryController(); $lab->sampleCollection(); exit; }
if ($request == 'lab/update-sample-status') { $lab = new LaboratoryController(); $lab->updateSampleStatus(); exit; }
if ($request == 'lab/generate-single-barcode') { $lab = new LaboratoryController(); $lab->generateSingleBarcode(); exit; }
if ($request == 'lab/print-barcode') { $lab = new LaboratoryController(); $lab->printBarcode(); exit; }
if ($request == 'lab/bulk-collect-samples') { $lab = new LaboratoryController(); $lab->bulkCollectSamples(); exit; }
if ($request == 'lab/get-sample-details') { $lab = new LaboratoryController(); $lab->getSampleDetails(); exit; }
if ($request == 'lab/view-report-ajax') { $lab = new LaboratoryController(); $lab->viewReportAjax(); exit; }
if ($request == 'lab/print-report') { $lab = new LaboratoryController(); $lab->printReport(); exit; }
if ($request == 'lab/export-reports') { $lab = new LaboratoryController(); $lab->exportReports(); exit; }
if (preg_match('/lab\/invoice\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->generateInvoice($matches[1]); exit; }

// Lab API Routes
if ($request == 'api/lab-orders') { $lab = new LaboratoryController(); $lab->apiLabOrders(); exit; }
if ($request == 'api/stat-orders') { $lab = new LaboratoryController(); $lab->apiStatOrders(); exit; }
if ($request == 'api/validate-barcode') { $lab = new LaboratoryController(); $lab->apiValidateBarcode(); exit; }
if ($request == 'api/collect-by-barcode') { $lab = new LaboratoryController(); $lab->apiCollectByBarcode(); exit; }
if ($request == 'api/doctors') { $lab = new LaboratoryController(); $lab->apiDoctors(); exit; }
if ($request == 'api/lab-reports-list') { $lab = new LaboratoryController(); $lab->apiLabReportsList(); exit; }

// ==================== LAB ORDERS API ROUTES ====================
if ($request == 'api/lab-orders-list') { 
    $lab = new LaboratoryController(); 
    $lab->apiLabOrdersList(); 
    exit; 
}

if (preg_match('/api\/lab-order-details\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiLabOrderDetails($matches[1]); 
    exit; 
}

if ($request == 'lab/export-orders') { 
    $lab = new LaboratoryController(); 
    $lab->exportOrders(); 
    exit; 
}

// In index.php - Laboratory Routes section
if (preg_match('/lab\/enter-results-form\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->enterResultForm($matches[1]); 
    exit; 
}

// Invoice view route
if (preg_match('/bills\/view\/(\d+)/', $request, $matches)) { 
    $billing = new BillingController(); 
    $billing->viewInvoice($matches[1]); 
    exit; 
}

// Pharmacy routes
if (preg_match('/pharmacy\/invoice\/(\d+)/', $request, $matches)) { 
    $pharmacy = new PharmacyController(); 
    $pharmacy->invoice($matches[1]); 
    exit; 
}

if (preg_match('/pharmacy\/sale-details\/(\d+)/', $request, $matches)) { 
    $pharmacy = new PharmacyController(); 
    $pharmacy->saleDetails($matches[1]); 
    exit; 
}

if ($request == 'pharmacy/sales-data') { 
    $pharmacy = new PharmacyController(); 
    $pharmacy->salesData(); 
    exit; 
}

if ($request == 'api/sales-list') { 
    $pharmacy = new PharmacyController(); 
    $pharmacy->salesList(); 
    exit; 
}

if ($request == 'api/bill-details') { 
    $billId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $billing = new BillingController(); 
    $billing->getBillDetailsAjax($billId); 
    exit; 
}

if ($request == 'pharmacy/move-to-expiry') { 
    $pharmacy = new PharmacyController(); 
    $pharmacy->moveToExpiry(); 
    exit; 
}

if ($request == 'pharmacy/bulk-move-expiry') { 
    $pharmacy = new PharmacyController(); 
    $pharmacy->bulkMoveExpiry(); 
    exit; 
}

if ($request == 'pharmacy/dashboard-stats') { 
    $pharmacy = new PharmacyController(); 
    $pharmacy->dashboardStats(); 
    exit; 
}

if ($request == 'inventory/recent-activity') { 
    $inventory = new InventoryController(); 
    $inventory->recentActivity(); 
    exit; 
}

if ($request == 'inventory/add-stock') { 
    $inventory = new InventoryController(); 
    $inventory->addStock(); 
    exit; 
}

// ==================== VIEW PURCHASE ORDER DETAILS ====================
if (preg_match('/inventory\/view-po\/(\d+)/', $request, $matches)) {
    $inventory = new InventoryController();
    $inventory->viewPurchaseOrder($matches[1]);
    exit;
}

// ==================== POST ROUTES FOR INVENTORY ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($request == 'inventory/create-po') {
        $inventory = new InventoryController();
        $inventory->createPurchaseOrder();
        exit;
    }
    if ($request == 'inventory/update-po') {
        $inventory = new InventoryController();
        $inventory->updatePurchaseOrder();
        exit;
    }
    if ($request == 'inventory/approve-po') {
        $inventory = new InventoryController();
        $inventory->approvePurchaseOrder();
        exit;
    }
    if ($request == 'inventory/receive-po') {
        $inventory = new InventoryController();
        $inventory->receivePurchaseOrder();
        exit;
    }
    if ($request == 'inventory/cancel-po') {
        $inventory = new InventoryController();
        $inventory->cancelPurchaseOrder();
        exit;
    }
    if ($request == 'inventory/ignore-reorder-alert') {
        $inventory = new InventoryController();
        $inventory->ignoreReorderAlert();
        exit;
    }
}

// Commission API Routes
if ($request == 'doctor/commission/approve') {
    $doctor = new DoctorController();
    $doctor->apiApproveCommission();
    exit;
}
if ($request == 'doctor/commission/pay') {
    $doctor = new DoctorController();
    $doctor->apiPayCommission();
    exit;
}
if (preg_match('/doctor\/commission\/details\/(\d+)/', $request, $matches)) {
    $doctor = new DoctorController();
    $doctor->apiCommissionDetails($matches[1]);
    exit;
}
if ($request == 'doctor/commission/export') {
    $doctor = new DoctorController();
    $doctor->apiExportCommissions();
    exit;
}
if ($request == 'doctor/commission/report') {
    $doctor = new DoctorController();
    $doctor->apiGenerateCommissionReport();
    exit;
}

if (preg_match('/doctor\/commission\/print\/(\d+)/', $request, $matches)) {
    $doctor = new DoctorController();
    $doctor->apiPrintCommission($matches[1]);
    exit;
}

// =================================================================
// ==================== PRESCRIPTION ROUTES ========================
// =================================================================

// INDEX - List all prescriptions
if ($request == 'prescriptions' || $request == 'prescriptions/index' || $request == 'prescription/list') { 
    $prescription = new PrescriptionController(); 
    $prescription->index(); 
    exit; 
}

// LIST - Alternative list view
if ($request == 'prescription/list') { 
    $prescription = new PrescriptionController(); 
    $prescription->index(); 
    exit; 
}

// CREATE - Show prescription creation form
if ($request == 'prescription/create' || $request == 'prescriptions/create') { 
    $prescription = new PrescriptionController(); 
    $prescription->create(); 
    exit; 
}

// SAVE - Full prescription save
if ($request == 'prescription/save' || $request == 'prescriptions/save') { 
    $prescription = new PrescriptionController(); 
    $prescription->save(); 
    exit; 
}

// SAVE TAB - Save individual tab data via AJAX
if ($request == 'prescription/save-tab' || $request == 'prescriptions/save-tab') { 
    $prescription = new PrescriptionController(); 
    $prescription->saveTab(); 
    exit; 
}

// PREVIOUS PRESCRIPTIONS - Get patient's previous prescriptions
if ($request == 'prescription/previous-list' || $request == 'prescriptions/previous-list') { 
    $prescription = new PrescriptionController(); 
    $prescription->getPreviousPrescriptions(); 
    exit; 
}

// LOAD TAB DATA - Load specific tab data from previous prescription
if ($request == 'prescription/load-tab' || $request == 'prescriptions/load-tab') { 
    $prescription = new PrescriptionController(); 
    $prescription->loadTabData(); 
    exit; 
}

// SEARCH DRUGS - AJAX search for drugs
if ($request == 'prescription/search-drugs' || $request == 'prescriptions/search-drugs') { 
    $prescription = new PrescriptionController(); 
    $prescription->searchDrugs(); 
    exit; 
}

// API MEDICINE SEARCH - For autocomplete
if ($request == 'api/medicines') { 
    $prescription = new PrescriptionController(); 
    $prescription->apiMedicines(); 
    exit; 
}

// SHOW - View prescription details
if (preg_match('/^prescription\/show\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/show\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->show($matches[1]); 
    exit; 
}

// VIEW - Alternative view prescription
if (preg_match('/^prescription\/view\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/view\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->show($matches[1]); 
    exit; 
}

// EDIT - Edit prescription
if (preg_match('/^prescription\/edit\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/edit\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->edit($matches[1]); 
    exit; 
}

// UPDATE - Update prescription
if ((preg_match('/^prescription\/update\/(\d+)$/', $request, $matches) || 
     preg_match('/^prescriptions\/update\/(\d+)$/', $request, $matches)) && 
     $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $prescription = new PrescriptionController(); 
    $prescription->update($matches[1]); 
    exit; 
}

// PRINT - Print prescription
if (preg_match('/^prescription\/print\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/print\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->printView($matches[1]); 
    exit; 
}

// PRINT PAD - Print on prescription pad
if (preg_match('/^prescription\/print-pad\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/print-pad\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->printPad($matches[1]); 
    exit; 
}

// EXPORT PDF - Export prescription as PDF
if (preg_match('/^prescription\/export-pdf\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/export-pdf\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->exportPdf($matches[1]); 
    exit; 
}

// DUPLICATE - Duplicate prescription
if (preg_match('/^prescription\/duplicate\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/duplicate\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->duplicate($matches[1]); 
    exit; 
}

// CANCEL - Cancel prescription
if ((preg_match('/^prescription\/cancel\/(\d+)$/', $request, $matches) || 
     preg_match('/^prescriptions\/cancel\/(\d+)$/', $request, $matches)) && 
     $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $prescription = new PrescriptionController(); 
    $prescription->cancel($matches[1]); 
    exit; 
}

// PATIENT PRESCRIPTIONS - View all prescriptions for a patient
if (preg_match('/^prescription\/patient\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/patient\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->patientPrescriptions($matches[1]); 
    exit; 
}

// DELETE - Delete prescription
if (preg_match('/^prescription\/delete\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/delete\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->delete($matches[1]); 
    exit; 
}

// =================================================================
// ==================== END PRESCRIPTION ROUTES ====================
// =================================================================

// ==================== APPOINTMENT API ROUTES ====================
if ($request == 'api/get-doctor-services') { 
    $appointment = new AppointmentController(); 
    $appointment->getDoctorServices(); 
    exit; 
}

if ($request == 'api/get-available-slots') { 
    $appointment = new AppointmentController(); 
    $appointment->getAvailableSlots(); 
    exit; 
}

if ($request == 'api/book-appointment') { 
    $appointment = new AppointmentController(); 
    $appointment->apiBookAppointment(); 
    exit; 
}

// ==================== ATTENDANCE API ROUTES ====================
if ($request == 'api/attendance/save') {
    $user = new UserController();
    $user->apiSaveAttendance();
    exit;
}

if ($request == 'api/attendance/save-all') {
    $user = new UserController();
    $user->apiSaveAllAttendance();
    exit;
}

if ($request == 'api/attendance/export-monthly') {
    $user = new UserController();
    $user->apiExportMonthlyAttendance();
    exit;
}

if ($request == 'admin/users/attendance') { $user = new UserController(); $user->attendance(); exit; }

// Add these routes to public/index.php

// Dashboard Route
if ($request == 'admin/dashboard' || $request == 'dashboard') { 
    $dashboard = new DashboardController(); 
    $dashboard->index(); 
    exit; 
}

// ================================================================
// RECEPTION & DAILY LIST ROUTES WITH ROLE ACCESS
// ================================================================

// Daily List - Role wise access
if ($request == 'reception/daily-list' || $request == 'daily-list') {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    
    // Role-based access control
    $userRole = $_SESSION['role_slug'] ?? 'guest';
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'doctor', 'nurse'];
    
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to access the daily patient list.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    
    $appointment = new AppointmentController();
    $appointment->dailyList();
    exit;
}

// Appointments - Role wise access
if ($request == 'reception/appointments') {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    
    // Role-based access control
    $userRole = $_SESSION['role_slug'] ?? 'guest';
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'doctor', 'nurse'];
    
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to access appointments.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    
    $appointment = new AppointmentController();
    $appointment->index();
    exit;
}

// Check-in - Role wise access
if ($request == 'reception/check-in') {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    
    // Role-based access control
    $userRole = $_SESSION['role_slug'] ?? 'guest';
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'nurse'];
    
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to access check-in.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    
    $appointment = new AppointmentController();
    $appointment->checkIn();
    exit;
}

// Queue - Role wise access
if ($request == 'reception/queue') {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    
    // Role-based access control
    $userRole = $_SESSION['role_slug'] ?? 'guest';
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'doctor', 'nurse'];
    
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to access queue.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    
    $appointment = new AppointmentController();
    $appointment->queue();
    exit;
}

// ================================================================
// RECEPTION ROUTES
// ================================================================

// Daily List - MUST COME BEFORE appointments route
if ($request == 'reception/daily-list') {
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    
    // Role-based access control
    $userRole = $_SESSION['role_slug'] ?? 'guest';
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'doctor', 'nurse'];
    
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to access the daily patient list.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    
    $appointment = new AppointmentController();
    $appointment->dailyList();
    exit;
}

// Appointments
if ($request == 'reception/appointments') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $appointment = new AppointmentController();
    $appointment->index();
    exit;
}

// Check-in
if ($request == 'reception/check-in') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $appointment = new AppointmentController();
    $appointment->checkIn();
    exit;
}

// Queue
if ($request == 'reception/queue') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $appointment = new AppointmentController();
    $appointment->queue();
    exit;
}

// Do Check-in (POST)
if ($request == 'reception/do-checkin') {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $appointment = new AppointmentController();
    $appointment->doCheckIn();
    exit;
}


// Attendance API Routes
if ($request == 'api/attendance/save') {
    $user = new UserController();
    $user->apiSaveAttendance();
    exit;
}

if ($request == 'api/attendance/save-all') {
    $user = new UserController();
    $user->apiSaveAllAttendance();
    exit;
}

if ($request == 'api/attendance/export') {
    $user = new UserController();
    $user->apiExport();
    exit;
}

if ($request == 'api/attendance/manual-entry') {
    $user = new UserController();
    $user->apiManualEntry();
    exit;
}

if ($request == 'api/attendance/history') {
    $user = new UserController();
    $user->apiHistory();
    exit;
}

// ==================== ROLES & PERMISSIONS ====================
if ($request == 'admin/users/roles') { 
    $user = new UserController(); 
    $user->roles(); 
    exit; 
}

// ==================== UPDATE ROLE PERMISSIONS ====================
if (preg_match('/admin\/users\/roles\/update\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $user = new UserController(); 
    $user->updateRolePermissions($matches[1]); 
    exit; 
}

// ==================== PHARMACY ROUTES ====================
if ($request == 'pharmacy/dashboard') { $pharmacy = new PharmacyController(); $pharmacy->dashboard(); exit; }
if ($request == 'pharmacy/medicines') { $pharmacy = new PharmacyController(); $pharmacy->medicines(); exit; }
if ($request == 'pharmacy/pos') { $pharmacy = new PharmacyController(); $pharmacy->pos(); exit; }
if ($request == 'pharmacy/prescriptions') { $pharmacy = new PharmacyController(); $pharmacy->prescriptions(); exit; }
if ($request == 'pharmacy/stock') { $pharmacy = new PharmacyController(); $pharmacy->stock(); exit; }
if ($request == 'pharmacy/sales') { $pharmacy = new PharmacyController(); $pharmacy->sales(); exit; }
if (preg_match('/pharmacy\/dispense\/(\d+)/', $request, $matches)) { $pharmacy = new PharmacyController(); $pharmacy->dispense($matches[1]); exit; }

// ==================== PHARMACY API ROUTES ====================
if ($request == 'pharmacy/add-to-cart') { $pharmacy = new PharmacyController(); $pharmacy->addToCart(); exit; }
if ($request == 'pharmacy/get-cart') { $pharmacy = new PharmacyController(); $pharmacy->getCart(); exit; }
if ($request == 'pharmacy/clear-cart') { $pharmacy = new PharmacyController(); $pharmacy->clearCart(); exit; }
if ($request == 'pharmacy/remove-from-cart') { $pharmacy = new PharmacyController(); $pharmacy->removeFromCart(); exit; }
if ($request == 'pharmacy/update-cart') { $pharmacy = new PharmacyController(); $pharmacy->updateCart(); exit; }
if ($request == 'pharmacy/process-sale') { $pharmacy = new PharmacyController(); $pharmacy->processSale(); exit; }
if ($request == 'pharmacy/add-stock') { $pharmacy = new PharmacyController(); $pharmacy->addStock(); exit; }
if ($request == 'pharmacy/add-medicine') { $pharmacy = new PharmacyController(); $pharmacy->addMedicine(); exit; }
if ($request == 'pharmacy/get-medicine') { 
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $pharmacy = new PharmacyController(); 
    $pharmacy->getMedicine($id); 
    exit; 
}
if ($request == 'pharmacy/stock-history') { 
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $pharmacy = new PharmacyController(); 
    $pharmacy->stockHistory($id); 
    exit; 
}
if ($request == 'pharmacy/delete-stock') { $pharmacy = new PharmacyController(); $pharmacy->deleteStock(); exit; }
if ($request == 'pharmacy/add-category') { $pharmacy = new PharmacyController(); $pharmacy->addCategory(); exit; }
if ($request == 'pharmacy/update-category') { $pharmacy = new PharmacyController(); $pharmacy->updateCategory(); exit; }
if ($request == 'pharmacy/delete-category') { $pharmacy = new PharmacyController(); $pharmacy->deleteCategory(); exit; }
if ($request == 'pharmacy/update-medicine') { $pharmacy = new PharmacyController(); $pharmacy->updateMedicine(); exit; }



// ================================================================
// RECEPTION ROUTES - ORDER MATTERS! PUT SPECIFIC ROUTES FIRST
// ================================================================

// ===== RECEPTION API ROUTES (Must be before page routes) =====
if ($request == 'api/daily-patients') { 
    $appointment = new AppointmentController(); 
    $appointment->apiDailyPatients(); 
    exit; 
}

if ($request == 'api/print-serial') { 
    $appointment = new AppointmentController(); 
    $appointment->apiPrintSerial(); 
    exit; 
}

if ($request == 'api/get-appointment-details') { 
    $appointment = new AppointmentController(); 
    $appointment->getAppointmentDetails(); 
    exit; 
}

if ($request == 'api/update-appointment-status') { 
    $appointment = new AppointmentController(); 
    $appointment->updateAppointmentStatus(); 
    exit; 
}

if ($request == 'api/cancel-appointment') { 
    $appointment = new AppointmentController(); 
    $appointment->cancelAppointment(); 
    exit; 
}

if ($request == 'api/reschedule-appointment') { 
    $appointment = new AppointmentController(); 
    $appointment->rescheduleAppointment(); 
    exit; 
}

if ($request == 'api/queue-list') { 
    $appointment = new AppointmentController(); 
    $appointment->apiQueueList(); 
    exit; 
}

if ($request == 'api/call-patient') { 
    $appointment = new AppointmentController(); 
    $appointment->apiCallPatient(); 
    exit; 
}

// ===== RECEPTION POST ROUTES =====
if ($request == 'reception/do-checkin') { 
    $appointment = new AppointmentController(); 
    $appointment->doCheckIn(); 
    exit; 
}

// ===== RECEPTION PAGE ROUTES (Specific routes first) =====
// DAILY LIST - MUST COME BEFORE APPOINTMENTS
if ($request == 'reception/daily-list') { 
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    
    // Role-based access control
    $userRole = $_SESSION['role_slug'] ?? 'guest';
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'doctor', 'nurse'];
    
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to access the daily patient list.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    
    $appointment = new AppointmentController(); 
    $appointment->dailyList(); 
    exit; 
}

// APPOINTMENTS
if ($request == 'reception/appointments') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->index(); 
    exit; 
}

// CHECK-IN
if ($request == 'reception/check-in') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->checkIn(); 
    exit; 
}

// QUEUE
if ($request == 'reception/queue') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->queue(); 
    exit; 
}

// DOCTOR LIST
if ($request == 'reception/doctor-list') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->doctorList(); 
    exit; 
}

// SEARCH PATIENT
if ($request == 'reception/search') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->searchPatient(); 
    exit; 
}

// Debug - Remove after testing
if ($request == 'reception/daily-list') {
    error_log("=== DAILY LIST ROUTE HIT ===");
    error_log("Request: " . $request);
    error_log("User ID: " . ($_SESSION['user_id'] ?? 'not set'));
    error_log("Role: " . ($_SESSION['role_slug'] ?? 'not set'));
}

// ==================== RECEPTION ROUTES ====================
if ($request == 'reception/daily-list') { 
    $appointment = new AppointmentController(); 
    $appointment->dailyList(); 
    exit; 
}

if ($request == 'reception/check-in') { 
    $appointment = new AppointmentController(); 
    $appointment->checkIn(); 
    exit; 
}

if ($request == 'reception/queue') { 
    $appointment = new AppointmentController(); 
    $appointment->queue(); 
    exit; 
}

// ==================== API: GET DAILY PATIENTS ====================
if ($request == 'api/daily-patients') { 
    $appointment = new AppointmentController(); 
    $appointment->apiDailyPatients(); 
    exit; 
}

// ==================== API: PRINT SERIAL SLIP ====================
if ($request == 'api/print-serial') { 
    $appointment = new AppointmentController(); 
    $appointment->apiPrintSerial(); 
    exit; 
}

// ==================== API: GET APPOINTMENT DETAILS ====================
if ($request == 'api/get-appointment-details') { 
    $appointment = new AppointmentController(); 
    $appointment->getAppointmentDetails(); 
    exit; 
}

// ==================== API: UPDATE APPOINTMENT STATUS ====================
if ($request == 'api/update-appointment-status') { 
    $appointment = new AppointmentController(); 
    $appointment->updateAppointmentStatus(); 
    exit; 
}

// ==================== API: CANCEL APPOINTMENT ====================
if ($request == 'api/cancel-appointment') { 
    $appointment = new AppointmentController(); 
    $appointment->cancelAppointment(); 
    exit; 
}

// ==================== API: RESCHEDULE APPOINTMENT ====================
if ($request == 'api/reschedule-appointment') { 
    $appointment = new AppointmentController(); 
    $appointment->rescheduleAppointment(); 
    exit; 
}

// ==================== API: DAILY PATIENTS ====================
if ($request == 'api/daily-patients') { 
    $appointment = new AppointmentController(); 
    $appointment->apiDailyPatients(); 
    exit; 
}

// ==================== API: PRINT SERIAL ====================
if ($request == 'api/print-serial') { 
    $appointment = new AppointmentController(); 
    $appointment->apiPrintSerial(); 
    exit; 
}

// ==================== API: QUEUE LIST ====================
if ($request == 'api/queue-list') { 
    $appointment = new AppointmentController(); 
    $appointment->apiQueueList(); 
    exit; 
}

// ==================== API: CALL PATIENT ====================
if ($request == 'api/call-patient') { 
    $appointment = new AppointmentController(); 
    $appointment->apiCallPatient(); 
    exit; 
}

// ==================== RECEPTION SEARCH ====================
if ($request == 'reception/search') { 
    $appointment = new AppointmentController(); 
    $appointment->searchPatient(); 
    exit; 
}

// ==================== API: QUEUE LIST ====================
if ($request == 'api/queue-list') { 
    $appointment = new AppointmentController(); 
    $appointment->apiQueueList(); 
    exit; 
}

// ================================================================
// LAB TEST MANAGEMENT ROUTES
// ================================================================

// Manage Tests - Main listing page
if ($request == 'lab/manage-tests') { 
    $lab = new LaboratoryController(); 
    $lab->manageTests(); 
    exit; 
}

// Add Test - Show add test page
if ($request == 'lab/add-test') { 
    $lab = new LaboratoryController(); 
    $lab->addTest(); 
    exit; 
}

// Edit Test - Show edit test page
if (preg_match('/lab\/edit-test\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->editTest($matches[1]); 
    exit; 
}

// Save Test - API endpoint for add/edit
if ($request == 'lab/api/save-test') { 
    $lab = new LaboratoryController(); 
    $lab->apiSaveTest(); 
    exit; 
}


// ================================================================
// LAB ACCESSORY ROUTES
// ================================================================
if ($request == 'lab/accessories') { $lab = new LaboratoryController(); $lab->manageAccessories(); exit; }

// API Routes for Accessories - ORDER MATTERS!
if (preg_match('/lab\/api\/accessory\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'GET') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetAccessory($matches[1]); 
    exit; 
}

if (preg_match('/lab\/api\/accessory\/delete\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->deleteAccessory($matches[1]); 
    exit; 
}

if (preg_match('/lab\/api\/accessory\/edit\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->editAccessory($matches[1]); 
    exit; 
}

if ($request == 'lab/api/accessory' && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->addAccessory(); 
    exit; 
}

if ($request == 'lab/api/accessories') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetAccessories(); 
    exit; 
}

if ($request == 'lab/api/accessory/update-stock') { 
    $lab = new LaboratoryController(); 
    $lab->updateAccessoryStock(); 
    exit; 
}

// ================================================================
// LAB ACCESSORY ROUTES
// ================================================================

// Page route
if ($request == 'lab/accessories') { 
    $lab = new LaboratoryController(); 
    $lab->manageAccessories(); 
    exit; 
}

// API Routes - ORDER MATTERS! (Specific before generic)
// Get single accessory
if (preg_match('/lab\/api\/accessory\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'GET') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetAccessory($matches[1]); 
    exit; 
}

// Delete accessory
if (preg_match('/lab\/api\/accessory\/delete\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->deleteAccessory($matches[1]); 
    exit; 
}

// Edit accessory
if (preg_match('/lab\/api\/accessory\/edit\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->editAccessory($matches[1]); 
    exit; 
}

// Add accessory (POST)
if ($request == 'lab/api/accessory' && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->addAccessory(); 
    exit; 
}

// Get all accessories (GET)
if ($request == 'lab/api/accessories') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetAccessories(); 
    exit; 
}

// Update stock
if ($request == 'lab/api/accessory/update-stock') { 
    $lab = new LaboratoryController(); 
    $lab->updateAccessoryStock(); 
    exit; 
}

// ================================================================
// LAB INSTRUMENT ROUTES
// ================================================================

// Page route
if ($request == 'lab/instruments') { 
    $lab = new LaboratoryController(); 
    $lab->manageInstruments(); 
    exit; 
}

// API Routes
if (preg_match('/lab\/api\/instrument\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'GET') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetInstrument($matches[1]); 
    exit; 
}

if (preg_match('/lab\/api\/instrument\/delete\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->deleteInstrument($matches[1]); 
    exit; 
}

if (preg_match('/lab\/api\/instrument\/edit\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->editInstrument($matches[1]); 
    exit; 
}

if ($request == 'lab/api/instrument' && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->addInstrument(); 
    exit; 
}

if ($request == 'lab/api/instruments') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetInstruments(); 
    exit; 
}

// ================================================================
// LAB CATEGORY ROUTES
// ================================================================

// Page route
if ($request == 'lab/categories') { 
    $lab = new LaboratoryController(); 
    $lab->manageCategories(); 
    exit; 
}

// API Routes
if (preg_match('/lab\/api\/category\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'GET') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetCategory($matches[1]); 
    exit; 
}

if (preg_match('/lab\/api\/category\/delete\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->deleteCategory($matches[1]); 
    exit; 
}

if (preg_match('/lab\/api\/category\/edit\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->editCategory($matches[1]); 
    exit; 
}

if ($request == 'lab/api/category' && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->addCategory(); 
    exit; 
}

if ($request == 'lab/api/categories') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetAllCategories(); 
    exit; 
}


// ================================================================
// LAB TEST API ROUTES
// ================================================================

// API - Get all lab tests (with search/filter) - NO ID
if ($request == 'api/lab-tests' && $_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['id']) && !isset($_GET['q'])) { 
    $lab = new LaboratoryController(); 
    $lab->apiLabTestsList(); 
    exit; 
}

// API - Search lab tests (with q parameter)
if ($request == 'api/lab-tests' && isset($_GET['q'])) { 
    $lab = new LaboratoryController(); 
    $lab->apiLabTests(); 
    exit; 
}

// API - Save lab test (POST - Add new)
if ($request == 'api/lab-tests' && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->apiSaveLabTest(); 
    exit; 
}

// API - Get single lab test by ID
if (preg_match('/api\/lab-tests\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'GET') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetLabTest($matches[1]); 
    exit; 
}

// API - Update lab test (POST with PUT method)
if (preg_match('/api\/lab-tests\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    if (isset($_POST['_method']) && $_POST['_method'] == 'PUT') {
        $lab = new LaboratoryController(); 
        $lab->apiSaveLabTest(); 
        exit;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['_method']) && $input['_method'] == 'PUT') {
        $lab = new LaboratoryController(); 
        $lab->apiSaveLabTest(); 
        exit;
    }
}

// API - Toggle lab test status
if (preg_match('/api\/lab-tests\/toggle\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiToggleLabTest($matches[1]); 
    exit; 
}

// API - Delete lab test
if (preg_match('/api\/lab-tests\/delete\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiDeleteLabTest($matches[1]); 
    exit; 
}

// ================================================================
// LAB CATEGORY ROUTES
// ================================================================
if ($request == 'lab/categories') { $lab = new LaboratoryController(); $lab->manageCategories(); exit; }
if (preg_match('/lab\/api\/category\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetCategory($matches[1]); exit; }
if (preg_match('/lab\/api\/category\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteCategory($matches[1]); exit; }

// ================================================================
// LAB INSTRUMENT ROUTES
// ================================================================
if ($request == 'lab/instruments') { $lab = new LaboratoryController(); $lab->manageInstruments(); exit; }
if (preg_match('/lab\/api\/instrument\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetInstrument($matches[1]); exit; }
if (preg_match('/lab\/api\/instrument\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteInstrument($matches[1]); exit; }
if ($request == 'lab/api/instruments') { $lab = new LaboratoryController(); $lab->apiGetInstruments(); exit; }

// ================================================================
// LAB TEST-INSTRUMENT ROUTES
// ================================================================
if (preg_match('/lab\/api\/test-instruments\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->manageTestInstruments($matches[1]); 
    exit; 
}
if (preg_match('/lab\/api\/test-instruments\/get\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiGetTestInstruments($matches[1]); 
    exit; 
}

// ================================================================
// LAB ACCESSORY ROUTES
// ================================================================
if ($request == 'lab/accessories') { $lab = new LaboratoryController(); $lab->manageAccessories(); exit; }
if (preg_match('/lab\/api\/accessory\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetAccessory($matches[1]); exit; }
if (preg_match('/lab\/api\/accessory\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteAccessory($matches[1]); exit; }
if ($request == 'lab/api/accessories') { $lab = new LaboratoryController(); $lab->apiGetAccessories(); exit; }
if ($request == 'lab/api/accessory/update-stock') { $lab = new LaboratoryController(); $lab->updateAccessoryStock(); exit; }

// ================================================================
// LAB TEST-ACCESSORY ROUTES
// ================================================================
if (preg_match('/lab\/api\/test-accessories\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->manageTestAccessories($matches[1]); 
    exit; 
}
if (preg_match('/lab\/api\/test-accessories\/get\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiGetTestAccessories($matches[1]); 
    exit; 
}


// ===== LAB TEST MANAGEMENT ROUTES - ADD THESE =====
// Manage Tests - Main page
if ($request == 'lab/manage-tests') { 
    $lab = new LaboratoryController(); 
    $lab->manageTests(); 
    exit; 
}

// ===== LAB TEST API ROUTES - ADD THESE =====
// API - Get all lab tests (with search/filter)
if ($request == 'api/lab-tests') { 
    $lab = new LaboratoryController(); 
    $lab->apiLabTests(); 
    exit; 
}

// API - Get single lab test by ID
if (preg_match('/api\/lab-tests\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiGetLabTest($matches[1]); 
    exit; 
}

// API - Save lab test (Add/Update)
if ($request == 'api/lab-tests' && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->apiSaveLabTest(); 
    exit; 
}

// API - Toggle lab test status
if (preg_match('/api\/lab-tests\/toggle\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiToggleLabTest($matches[1]); 
    exit; 
}

// API - Delete lab test
if (preg_match('/api\/lab-tests\/delete\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiDeleteLabTest($matches[1]); 
    exit; 
}


// ================================================================
// LAB TEST API ROUTES - MUST BE IN THIS ORDER
// ================================================================

// IMPORTANT: This route MUST come BEFORE the generic {id} route
// API - Get all lab tests (with search/filter) - NO ID
if ($request == 'api/lab-tests' && $_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['id']) && !isset($_GET['q'])) { 
    $lab = new LaboratoryController(); 
    $lab->apiLabTestsList(); 
    exit; 
}

// API - Search lab tests (with q parameter)
if ($request == 'api/lab-tests' && isset($_GET['q'])) { 
    $lab = new LaboratoryController(); 
    $lab->apiLabTests(); 
    exit; 
}

// API - Save lab test (POST - Add new)
if ($request == 'api/lab-tests' && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->apiSaveLabTest(); 
    exit; 
}

// API - Get single lab test by ID (must be after POST and search routes)
if (preg_match('/api\/lab-tests\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'GET') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetLabTest($matches[1]); 
    exit; 
}

// API - Update lab test (POST with PUT method)
if (preg_match('/api\/lab-tests\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    // Check if _method=PUT is in the POST data
    if (isset($_POST['_method']) && $_POST['_method'] == 'PUT') {
        $lab = new LaboratoryController(); 
        $lab->apiSaveLabTest(); 
        exit;
    }
    // Also check in raw JSON
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['_method']) && $input['_method'] == 'PUT') {
        $lab = new LaboratoryController(); 
        $lab->apiSaveLabTest(); 
        exit;
    }
}

// API - Toggle lab test status
if (preg_match('/api\/lab-tests\/toggle\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiToggleLabTest($matches[1]); 
    exit; 
}

// API - Delete lab test
if (preg_match('/api\/lab-tests\/delete\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiDeleteLabTest($matches[1]); 
    exit; 
}


// ================================================================
// LAB CATEGORY ROUTES
// ================================================================
if ($request == 'lab/categories') { $lab = new LaboratoryController(); $lab->manageCategories(); exit; }
if ($request == 'lab/category/add') { $lab = new LaboratoryController(); $lab->addCategory(); exit; }
if (preg_match('/lab\/category\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editCategory($matches[1]); exit; }
if (preg_match('/lab\/category\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteCategory($matches[1]); exit; }
if (preg_match('/lab\/api\/category\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetCategory($matches[1]); exit; }
if ($request == 'lab/api/category') { $lab = new LaboratoryController(); $lab->addCategory(); exit; }
if (preg_match('/lab\/api\/category\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editCategory($matches[1]); exit; }
if (preg_match('/lab\/api\/category\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteCategory($matches[1]); exit; }

// ================================================================
// LAB INSTRUMENT ROUTES
// ================================================================
if ($request == 'lab/instruments') { $lab = new LaboratoryController(); $lab->manageInstruments(); exit; }
if ($request == 'lab/instrument/add') { $lab = new LaboratoryController(); $lab->addInstrument(); exit; }
if (preg_match('/lab\/instrument\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editInstrument($matches[1]); exit; }
if (preg_match('/lab\/instrument\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteInstrument($matches[1]); exit; }
if (preg_match('/lab\/api\/instrument\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetInstrument($matches[1]); exit; }
if ($request == 'lab/api/instrument') { $lab = new LaboratoryController(); $lab->addInstrument(); exit; }
if (preg_match('/lab\/api\/instrument\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editInstrument($matches[1]); exit; }
if (preg_match('/lab\/api\/instrument\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteInstrument($matches[1]); exit; }
if ($request == 'lab/api/instruments') { $lab = new LaboratoryController(); $lab->apiGetInstruments(); exit; }

// ================================================================
// LAB TEST-INSTRUMENT ROUTES
// ================================================================
if (preg_match('/lab\/test-instruments\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->manageTestInstruments($matches[1]); exit; }
if (preg_match('/lab\/api\/test-instruments\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->manageTestInstruments($matches[1]); exit; }
if (preg_match('/lab\/api\/test-instruments\/get\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetTestInstruments($matches[1]); exit; }

// ================================================================
// LAB ACCESSORY ROUTES
// ================================================================
if ($request == 'lab/accessories') { $lab = new LaboratoryController(); $lab->manageAccessories(); exit; }
if ($request == 'lab/accessory/add') { $lab = new LaboratoryController(); $lab->addAccessory(); exit; }
if (preg_match('/lab\/accessory\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editAccessory($matches[1]); exit; }
if (preg_match('/lab\/accessory\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteAccessory($matches[1]); exit; }
if (preg_match('/lab\/api\/accessory\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetAccessory($matches[1]); exit; }
if ($request == 'lab/api/accessory') { $lab = new LaboratoryController(); $lab->addAccessory(); exit; }
if (preg_match('/lab\/api\/accessory\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editAccessory($matches[1]); exit; }
if (preg_match('/lab\/api\/accessory\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteAccessory($matches[1]); exit; }
if ($request == 'lab/api/accessories') { $lab = new LaboratoryController(); $lab->apiGetAccessories(); exit; }
if ($request == 'lab/api/accessory/update-stock') { $lab = new LaboratoryController(); $lab->updateAccessoryStock(); exit; }

// ================================================================
// LAB TEST-ACCESSORY ROUTES
// ================================================================
if (preg_match('/lab\/test-accessories\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->manageTestAccessories($matches[1]); exit; }
if (preg_match('/lab\/api\/test-accessories\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->manageTestAccessories($matches[1]); exit; }
if (preg_match('/lab\/api\/test-accessories\/get\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetTestAccessories($matches[1]); exit; }

// ==================== LAB API ROUTES ====================
if ($request == 'api/lab-orders') { $lab = new LaboratoryController(); $lab->apiLabOrders(); exit; }
if ($request == 'api/stat-orders') { $lab = new LaboratoryController(); $lab->apiStatOrders(); exit; }
if ($request == 'api/validate-barcode') { $lab = new LaboratoryController(); $lab->apiValidateBarcode(); exit; }
if ($request == 'api/collect-by-barcode') { $lab = new LaboratoryController(); $lab->apiCollectByBarcode(); exit; }
if ($request == 'api/doctors') { $lab = new LaboratoryController(); $lab->apiDoctors(); exit; }
if ($request == 'api/lab-reports-list') { $lab = new LaboratoryController(); $lab->apiLabReportsList(); exit; }
if ($request == 'api/lab-orders-list') { $lab = new LaboratoryController(); $lab->apiLabOrdersList(); exit; }

// ================================================================
// ADD THIS LINE - API LAB TESTS SEARCH
// ================================================================
if ($request == 'api/lab-tests' || $request == 'laboratory/api-lab-tests') { 
    $lab = new LaboratoryController(); 
    $lab->apiLabTests(); 
    exit; 
}

if (preg_match('/api\/lab-order-details\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiLabOrderDetails($matches[1]); 
    exit; 
}

// ==================== LAB API ROUTES ====================
if ($request == 'api/lab-tests') { $lab = new LaboratoryController(); $lab->apiLabTests(); exit; }

// Also handle the alternative URL for compatibility
if ($request == 'laboratory/api-lab-tests') { $lab = new LaboratoryController(); $lab->apiLabTests(); exit; }

// And also handle from prescriptions namespace
if ($request == 'prescriptions/api-lab-tests') { $lab = new LaboratoryController(); $lab->apiLabTests(); exit; }

// ==================== INVENTORY ROUTES ====================
if ($request == 'inventory/dashboard') { $inventory = new InventoryController(); $inventory->dashboard(); exit; }
if ($request == 'inventory/items') { $inventory = new InventoryController(); $inventory->items(); exit; }
if ($request == 'inventory/items/add') { $inventory = new InventoryController(); $inventory->addItemForm(); exit; }
if ($request == 'inventory/add-item') { $inventory = new InventoryController(); $inventory->addItem(); exit; }
if ($request == 'inventory/stock') { $inventory = new InventoryController(); $inventory->stock(); exit; }
if ($request == 'inventory/add-stock') { $inventory = new InventoryController(); $inventory->addStock(); exit; }
if ($request == 'inventory/delete-stock') { $inventory = new InventoryController(); $inventory->deleteStock(); exit; }
if ($request == 'inventory/expiry-alerts') { $inventory = new InventoryController(); $inventory->expiryAlerts(); exit; }
if ($request == 'inventory/reorder-alerts') { $inventory = new InventoryController(); $inventory->reorderAlerts(); exit; }
if ($request == 'inventory/suppliers') { $inventory = new InventoryController(); $inventory->suppliers(); exit; }
if ($request == 'inventory/stores') { $inventory = new InventoryController(); $inventory->stores(); exit; }
if ($request == 'inventory/stock-transfers') { $inventory = new InventoryController(); $inventory->stockTransfers(); exit; }
if ($request == 'inventory/purchase-orders') { $inventory = new InventoryController(); $inventory->purchaseOrders(); exit; }
if ($request == 'inventory/reports') { $inventory = new InventoryController(); $inventory->reports(); exit; }
if ($request == 'inventory/view-item') { $id = isset($_GET['id']) ? (int)$_GET['id'] : 0; $inventory = new InventoryController(); $inventory->viewItem($id); exit; }

// ==================== BILLING ROUTES ====================
if ($request == 'bills' || $request == 'bills/bills') { $billing = new BillingController(); $billing->bills(); exit; }
if ($request == 'bills/create') { $billing = new BillingController(); $billing->createBill(); exit; }
if ($request == 'bills/store') { $billing = new BillingController(); $billing->storeBill(); exit; }
if (preg_match('/bills\/view\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->viewInvoice($matches[1]); exit; }
if (preg_match('/bills\/print\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->printInvoice($matches[1]); exit; }
if ($request == 'bills/process-payment') { $billing = new BillingController(); $billing->processPayment(); exit; }
if ($request == 'bills/get-payment-history') { $billing = new BillingController(); $billing->getPaymentHistory(); exit; }
if ($request == 'bills/export') { $billing = new BillingController(); $billing->exportBills(); exit; }
if ($request == 'bills/payments') { $billing = new BillingController(); $billing->payments(); exit; }
if ($request == 'account/reports') { $billing = new BillingController(); $billing->financialReports(); exit; }

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



// ================================================================
// LAB-TEST PRESCRIPTIONS ROUTES
// ================================================================

// List all prescriptions with lab tests
if ($request == 'lab/prescriptions') { 
    $lab = new LaboratoryController(); 
    $lab->prescriptions(); 
    exit; 
}

// View prescription lab tests and create order
if (preg_match('/lab\/view-prescription-tests\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->viewPrescriptionTests($matches[1]); 
    exit; 
}

// Create order from prescription (POST)
if ($request == 'lab/create-order-from-prescription') { 
    $lab = new LaboratoryController(); 
    $lab->createOrderFromPrescription(); 
    exit; 
}

// API - Get prescription lab tests
if ($request == 'api/get-prescription-lab-tests') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetPrescriptionLabTests(); 
    exit; 
}

// ==================== LAB ORDER VIEW ROUTES ====================

// View single order - This should be before the generic orders route
if (preg_match('/lab\/view-order\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->viewOrder($matches[1]); 
    exit; 
}

// View order (alternative URL)
if (preg_match('/lab\/orders\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->viewOrder($matches[1]); 
    exit; 
}

// Orders list
if ($request == 'lab/orders') { 
    $lab = new LaboratoryController(); 
    $lab->orders(); 
    exit; 
}

// Create order
if ($request == 'lab/create-order') { 
    $lab = new LaboratoryController(); 
    $lab->createOrder(); 
    exit; 
}

// Store order
if ($request == 'lab/store-order') { 
    $lab = new LaboratoryController(); 
    $lab->storeOrder(); 
    exit; 
}

// ==================== BILLING & PAYMENT API ROUTES ====================

// Get bill details
if ($request == 'api/get-bill-details') {
    header('Content-Type: application/json');
    $billId = isset($_GET['bill_id']) ? (int)$_GET['bill_id'] : 0;
    
    if ($billId == 0) {
        echo json_encode(['success' => false, 'message' => 'Bill ID required']);
        exit;
    }
    
    $query = "SELECT b.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                     p.patient_code, p.id as patient_id,
                     b.reference_type, b.reference_id
              FROM bills b
              JOIN patients p ON b.patient_id = p.id
              WHERE b.id = $billId";
    
    $result = $conn->query($query);
    $bill = $result->fetch_assoc();
    
    if ($bill) {
        // Get payment history
        $payments = $conn->query("SELECT * FROM payments WHERE bill_id = $billId ORDER BY payment_date DESC");
        $paymentList = [];
        while ($row = $payments->fetch_assoc()) {
            $paymentList[] = $row;
        }
        $bill['payments'] = $paymentList;
        
        echo json_encode(['success' => true, 'data' => $bill]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bill not found']);
    }
    exit;
}

// Process bill payment
if ($request == 'api/process-bill-payment') {
    header('Content-Type: application/json');
    
    try {
        $billId = (int)$_POST['bill_id'];
        $amount = (float)$_POST['amount'];
        $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
        $transactionId = isset($_POST['transaction_id']) ? $conn->real_escape_string($_POST['transaction_id']) : '';
        $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
        $userId = $_SESSION['user_id'];
        
        // Get bill details
        $bill = $conn->query("SELECT * FROM bills WHERE id = $billId")->fetch_assoc();
        if (!$bill) {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
            exit;
        }
        
        $currentPaid = (float)$bill['paid_amount'];
        $totalAmount = (float)$bill['total_amount'];
        $newPaid = $currentPaid + $amount;
        
        if ($newPaid > $totalAmount) {
            echo json_encode(['success' => false, 'message' => 'Payment amount exceeds total amount']);
            exit;
        }
        
        $conn->begin_transaction();
        
        // Insert payment record
        $paymentNumber = 'PAY' . date('Ymd') . rand(1000, 9999);
        $paymentSql = "INSERT INTO payments (
                            payment_number, bill_id, patient_id, amount, payment_method,
                            transaction_id, notes, payment_date, received_by, created_at
                        ) VALUES (
                            '$paymentNumber', $billId, {$bill['patient_id']}, $amount, '$paymentMethod',
                            '$transactionId', '$notes', CURDATE(), $userId, NOW()
                        )";
        if (!$conn->query($paymentSql)) {
            throw new Exception("Failed to insert payment: " . $conn->error);
        }
        
        // Update bill
        $paymentStatus = ($newPaid >= $totalAmount) ? 'paid' : 'partial';
        $updateSql = "UPDATE bills SET 
                      paid_amount = $newPaid,
                      balance_amount = " . ($totalAmount - $newPaid) . ",
                      payment_status = '$paymentStatus',
                      updated_at = NOW()
                      WHERE id = $billId";
        if (!$conn->query($updateSql)) {
            throw new Exception("Failed to update bill: " . $conn->error);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'new_paid' => $newPaid,
            'due' => ($totalAmount - $newPaid),
            'payment_status' => $paymentStatus
        ]);
        
    } catch (Exception $e) {
        if ($conn->connect_errno == 0) {
            $conn->rollback();
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ================================================================
// LAB TEST MANAGEMENT - ADD & EDIT PAGES (SEPARATE PAGES)
// ================================================================

// Add Test - Show add test page
if ($request == 'lab/add-test') { 
    $lab = new LaboratoryController(); 
    $lab->addTest(); 
    exit; 
}

// Edit Test - Show edit test page
if (preg_match('/lab\/edit-test\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->editTest($matches[1]); 
    exit; 
}

// Save Test - API endpoint for add/edit
if ($request == 'lab/api/save-test') { 
    $lab = new LaboratoryController(); 
    $lab->apiSaveTest(); 
    exit; 
}


// public/index.php - Add this at the top after session_start()

// ===== API ROUTE - Direct handling =====
if (strpos($_SERVER['REQUEST_URI'], '/prescriptions/api-medicines') !== false) {
    require_once BASE_PATH . '/app/controllers/PrescriptionController.php';
    $controller = new PrescriptionController();
    $controller->apiMedicines();
    exit;
}

// API - GET BOOKING RECEIPT
if ($request == 'api/get-booking-receipt') {
    header('Content-Type: application/json');
    $appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
    
    if ($appointmentId == 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
        exit;
    }
    
    $query = "SELECT a.*, 
                     CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                     p.patient_code,
                     p.phone,
                     CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                     u.first_name as doctor_fname,
                     u.last_name as doctor_lname,
                     d.specialization,
                     ds.service_name,
                     ds.service_price,
                     (SELECT COUNT(*) FROM appointments 
                      WHERE doctor_id = a.doctor_id 
                      AND appointment_date = a.appointment_date 
                      AND session_type = a.session_type
                      AND id <= a.id
                      AND status != 'canceled') as serial_number
              FROM appointments a
              JOIN patients p ON a.patient_id = p.id
              JOIN doctors d ON a.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              LEFT JOIN doctor_services ds ON a.service_id = ds.id
              WHERE a.id = $appointmentId";
    
    $result = $conn->query($query);
    $appointment = $result->fetch_assoc();
    
    if ($appointment) {
        $serial = ($appointment['session_type'] == 'morning' ? 'M' : 'E') . 
                  str_pad($appointment['serial_number'] ?? 0, 3, '0', STR_PAD_LEFT);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'receipt_no' => $appointment['appointment_number'],
                'appointment_date' => date('d M Y', strtotime($appointment['appointment_date'])),
                'patient_name' => $appointment['patient_name'],
                'doctor_name' => $appointment['doctor_name'],
                'service_name' => $appointment['service_name'] ?? 'Consultation',
                'serial_number' => $serial,
                'amount' => $appointment['total_amount'] ?? 0,
                'appointment_status' => $appointment['status'],
                'patient_code' => $appointment['patient_code'],
                'phone' => $appointment['phone'],
                'specialization' => $appointment['specialization'],
                'start_time' => date('h:i A', strtotime($appointment['start_time'])),
                'end_time' => date('h:i A', strtotime($appointment['end_time'])),
                'session_type' => $appointment['session_type'],
                'payment_status' => $appointment['payment_status']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
    exit;
}

// API - CHECK APPOINTMENT PAYMENTS
if ($request == 'api/check-appointment-payments') {
    header('Content-Type: application/json');
    $appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
    
    if ($appointmentId == 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
        exit;
    }
    
    $payments = $conn->query("SELECT COUNT(*) as cnt FROM payment_transactions 
                              WHERE appointment_id = $appointmentId AND status = 'completed'");
    $hasPayment = $payments->fetch_assoc()['cnt'] > 0;
    
    echo json_encode(['success' => true, 'has_payment' => $hasPayment]);
    exit;
}
// API - PRINT SERIAL SLIP (already exists but make sure it's there)
if ($request == 'api/print-serial') {
    $appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
    
    // This should redirect to the AppointmentController or handle it directly
    // Make sure the route exists or add direct handling
    header('Location: ' . BASE_URL . '/reception/print-serial?appointment_id=' . $appointmentId);
    exit;
}

// public/index.php - Add this at the top

// ===== API ROUTES =====
if (strpos($_SERVER['REQUEST_URI'], '/prescriptions/get-previous-prescriptions') !== false) {
    require_once BASE_PATH . '/app/controllers/PrescriptionController.php';
    $controller = new PrescriptionController();
    $controller->getPreviousPrescriptions();
    exit;
}

if (strpos($_SERVER['REQUEST_URI'], '/prescriptions/api-medicines') !== false) {
    require_once BASE_PATH . '/app/controllers/PrescriptionController.php';
    $controller = new PrescriptionController();
    $controller->apiMedicines();
    exit;
}

if (strpos($_SERVER['REQUEST_URI'], '/prescriptions/load-tab-data') !== false) {
    require_once BASE_PATH . '/app/controllers/PrescriptionController.php';
    $controller = new PrescriptionController();
    $controller->loadTabData();
    exit;
}

// ==================== PHARMACY ROUTES ====================
if ($request == 'pharmacy/dashboard') { $pharmacy = new PharmacyController(); $pharmacy->dashboard(); exit; }
if ($request == 'pharmacy/medicines') { $pharmacy = new PharmacyController(); $pharmacy->medicines(); exit; }
if ($request == 'pharmacy/pos') { $pharmacy = new PharmacyController(); $pharmacy->pos(); exit; }
if ($request == 'pharmacy/prescriptions') { $pharmacy = new PharmacyController(); $pharmacy->prescriptions(); exit; }
if ($request == 'pharmacy/stock') { $pharmacy = new PharmacyController(); $pharmacy->stock(); exit; }
if ($request == 'pharmacy/sales') { $pharmacy = new PharmacyController(); $pharmacy->sales(); exit; }
if (preg_match('/pharmacy\/dispense\/(\d+)/', $request, $matches)) { $pharmacy = new PharmacyController(); $pharmacy->dispense($matches[1]); exit; }

// ===== ADD THIS LINE FOR DISPENSE PROCESS =====
if ($request == 'pharmacy/dispense-process') { $pharmacy = new PharmacyController(); $pharmacy->dispenseProcess(); exit; }


// ==================== BILLING & APPOINTMENT INTERCONNECTION API ====================

// API - GET BILL DETAILS (for payment modal)
if ($request == 'api/get-bill-details') {
    header('Content-Type: application/json');
    $billId = isset($_GET['bill_id']) ? (int)$_GET['bill_id'] : 0;
    
    if ($billId == 0) {
        echo json_encode(['success' => false, 'message' => 'Bill ID required']);
        exit;
    }
    
    $query = "SELECT b.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                     p.patient_code, p.id as patient_id,
                     b.reference_type, b.reference_id
              FROM bills b
              JOIN patients p ON b.patient_id = p.id
              WHERE b.id = $billId";
    
    $result = $conn->query($query);
    $bill = $result->fetch_assoc();
    
    if ($bill) {
        // Get payment history
        $payments = $conn->query("SELECT * FROM payments WHERE bill_id = $billId ORDER BY payment_date DESC");
        $paymentList = [];
        while ($row = $payments->fetch_assoc()) {
            $paymentList[] = $row;
        }
        $bill['payments'] = $paymentList;
        
        echo json_encode(['success' => true, 'data' => $bill]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Bill not found']);
    }
    exit;
}

// API - PROCESS PAYMENT FOR BILL (updates both bills and appointments)
if ($request == 'api/process-bill-payment') {
    header('Content-Type: application/json');
    
    $billId = (int)$_POST['bill_id'];
    $amount = (float)$_POST['amount'];
    $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
    $transactionId = isset($_POST['transaction_id']) ? $conn->real_escape_string($_POST['transaction_id']) : '';
    $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
    $userId = $_SESSION['user_id'];
    
    // Get bill details
    $bill = $conn->query("SELECT * FROM bills WHERE id = $billId")->fetch_assoc();
    if (!$bill) {
        echo json_encode(['success' => false, 'message' => 'Bill not found']);
        exit;
    }
    
    $currentPaid = (float)$bill['paid_amount'];
    $totalAmount = (float)$bill['total_amount'];
    $newPaid = $currentPaid + $amount;
    
    if ($newPaid > $totalAmount) {
        echo json_encode(['success' => false, 'message' => 'Payment amount exceeds total amount']);
        exit;
    }
    
    $conn->begin_transaction();
    
    try {
        // Insert payment record
        $paymentNumber = 'PAY' . date('Ymd') . rand(1000, 9999);
        $paymentSql = "INSERT INTO payments (
                            payment_number, bill_id, patient_id, amount, payment_method,
                            transaction_id, notes, payment_date, received_by, created_at
                        ) VALUES (
                            '$paymentNumber', $billId, {$bill['patient_id']}, $amount, '$paymentMethod',
                            '$transactionId', '$notes', CURDATE(), $userId, NOW()
                        )";
        if (!$conn->query($paymentSql)) {
            throw new Exception("Failed to insert payment: " . $conn->error);
        }
        
        // Update bill
        $paymentStatus = ($newPaid >= $totalAmount) ? 'paid' : 'partial';
        $updateSql = "UPDATE bills SET 
                      paid_amount = $newPaid,
                      balance_amount = " . ($totalAmount - $newPaid) . ",
                      payment_status = '$paymentStatus',
                      updated_at = NOW()
                      WHERE id = $billId";
        if (!$conn->query($updateSql)) {
            throw new Exception("Failed to update bill: " . $conn->error);
        }
        
        // ================================================================
        // UPDATE APPOINTMENT IF BILL IS LINKED TO AN APPOINTMENT
        // ================================================================
        if ($bill['reference_type'] == 'appointment' && $bill['reference_id'] > 0) {
            $appointmentId = (int)$bill['reference_id'];
            
            // Update appointment payment
            $appointment = $conn->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
            if ($appointment) {
                $appCurrentPaid = (float)($appointment['payment_received'] ?? 0);
                $appNewPaid = $appCurrentPaid + $amount;
                $appTotal = (float)$appointment['total_amount'];
                $appPaymentStatus = ($appNewPaid >= $appTotal) ? 'paid' : 'partial';
                
                $appUpdateSql = "UPDATE appointments SET 
                                 payment_received = $appNewPaid,
                                 payment_status = '$appPaymentStatus',
                                 last_payment_date = NOW(),
                                 updated_at = NOW()
                                 WHERE id = $appointmentId";
                if (!$conn->query($appUpdateSql)) {
                    throw new Exception("Failed to update appointment: " . $conn->error);
                }
                
                // Also update payment_transactions table
                $transSql = "INSERT INTO payment_transactions (
                                appointment_id, patient_id, bill_id, amount, payment_method,
                                transaction_id, payment_date, received_by, notes, status, created_at
                             ) VALUES (
                                $appointmentId, {$bill['patient_id']}, $billId, $amount, '$paymentMethod',
                                '$transactionId', NOW(), $userId, '$notes', 'completed', NOW()
                             )";
                if (!$conn->query($transSql)) {
                    throw new Exception("Failed to insert payment transaction: " . $conn->error);
                }
            }
        }
        
        // ================================================================
        // UPDATE APPOINTMENT IF BILL IS LINKED TO A PHARMACY SALE
        // (Check if there's an appointment linked to the pharmacy sale)
        // ================================================================
        if ($bill['reference_type'] == 'pharmacy_sale' && $bill['reference_id'] > 0) {
            // Check if there's an appointment linked to this pharmacy sale
            $appCheck = $conn->query("SELECT a.id FROM appointments a 
                                      JOIN pharmacy_sales ps ON ps.patient_id = a.patient_id
                                      WHERE ps.id = {$bill['reference_id']} 
                                      AND a.appointment_date = CURDATE()
                                      LIMIT 1");
            if ($appCheck && $appCheck->num_rows > 0) {
                $appData = $appCheck->fetch_assoc();
                $appointmentId = (int)$appData['id'];
                
                // Update appointment payment status
                $appointment = $conn->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
                if ($appointment) {
                    // Only update if appointment has total amount
                    $appTotal = (float)$appointment['total_amount'];
                    if ($appTotal > 0) {
                        $appCurrentPaid = (float)($appointment['payment_received'] ?? 0);
                        $appNewPaid = $appCurrentPaid + $amount;
                        $appPaymentStatus = ($appNewPaid >= $appTotal) ? 'paid' : 'partial';
                        
                        $conn->query("UPDATE appointments SET 
                                      payment_received = $appNewPaid,
                                      payment_status = '$appPaymentStatus',
                                      last_payment_date = NOW(),
                                      updated_at = NOW()
                                      WHERE id = $appointmentId");
                    }
                }
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment processed successfully',
            'new_paid' => $newPaid,
            'due' => ($totalAmount - $newPaid),
            'payment_status' => $paymentStatus
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API - GET APPOINTMENT PAYMENT STATUS (for bills page to update)
if ($request == 'api/get-appointment-payment-status') {
    header('Content-Type: application/json');
    $appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
    
    if ($appointmentId == 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
        exit;
    }
    
    $result = $conn->query("SELECT id, payment_status, payment_received, total_amount 
                           FROM appointments WHERE id = $appointmentId");
    $appointment = $result->fetch_assoc();
    
    if ($appointment) {
        echo json_encode([
            'success' => true,
            'payment_status' => $appointment['payment_status'],
            'payment_received' => $appointment['payment_received'],
            'total_amount' => $appointment['total_amount'],
            'due' => (float)$appointment['total_amount'] - (float)$appointment['payment_received']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
    }
    exit;
}


// ==================== PATIENT MANAGEMENT ====================
// ... existing routes ...

// BARCODE ROUTES
if ($request == 'patient/barcode-view') { $patient = new PatientController(); $patient->barcodeView(); exit; }
if ($request == 'patient/barcode-print') { $patient = new PatientController(); $patient->barcodePrint(); exit; }
if ($request == 'patient/barcode-download') { $patient = new PatientController(); $patient->barcodeDownload(); exit; }

// ==================== PATIENT MANAGEMENT ====================
if ($request == 'patient/list') { $patient = new PatientController(); $patient->index(); exit; }
if ($request == 'patient/register') { $patient = new PatientController(); $patient->register(); exit; }
if ($request == 'patient/store') { $patient = new PatientController(); $patient->store(); exit; }
if ($request == 'patient/id-card') { $patient = new PatientController(); $patient->idCard(); exit; }
if ($request == 'patient/view') { $patient = new PatientController(); $patient->show(); exit; }
if ($request == 'patient/show') { $patient = new PatientController(); $patient->show(); exit; }
if ($request == 'patient/edit') { $patient = new PatientController(); $patient->edit(); exit; }
if ($request == 'patient/update') { $patient = new PatientController(); $patient->update(); exit; }
if ($request == 'patient/delete') { $patient = new PatientController(); $patient->delete(); exit; }
if ($request == 'patient/book-appointment') { $patient = new PatientController(); $patient->bookAppointment(); exit; }

// ===== ADD THESE ID CARD ROUTES =====
if ($request == 'patient/id-card-front') { $patient = new PatientController(); $patient->idCardFront(); exit; }
if ($request == 'patient/id-card-back') { $patient = new PatientController(); $patient->idCardBack(); exit; }
if ($request == 'patient/print-id-front') { $patient = new PatientController(); $patient->printIdCardFront(); exit; }
if ($request == 'patient/print-id-back') { $patient = new PatientController(); $patient->printIdCardBack(); exit; }

// ===== BARCODE ROUTES =====
if ($request == 'patient/barcode-view') { $patient = new PatientController(); $patient->barcodeView(); exit; }
if ($request == 'patient/barcode-print') { $patient = new PatientController(); $patient->barcodePrint(); exit; }
if ($request == 'patient/barcode-download') { $patient = new PatientController(); $patient->barcodeDownload(); exit; }

// ===== BARCODE ROUTES =====
if ($request == 'barcode/print') { $patient = new PatientController(); $patient->barcodePrint(); exit; }
if ($request == 'barcode/view') { $patient = new PatientController(); $patient->barcodeView(); exit; }
if ($request == 'barcode/download') { $patient = new PatientController(); $patient->barcodeDownload(); exit; }

// ===== BARCODE ROUTES =====
if ($request == 'barcode/print') { $patient = new PatientController(); $patient->barcodePrint(); exit; }
if ($request == 'barcode/view') { $patient = new PatientController(); $patient->barcodeView(); exit; }
if ($request == 'barcode/download') { $patient = new PatientController(); $patient->barcodeDownload(); exit; }

// ===== BARCODE ROUTES =====
if ($request == 'barcode/print') { $patient = new PatientController(); $patient->barcodePrint(); exit; }
if ($request == 'barcode/view') { $patient = new PatientController(); $patient->barcodeView(); exit; }
if ($request == 'barcode/download') { $patient = new PatientController(); $patient->barcodeDownload(); exit; }
if ($request == 'barcode/download-svg') { $patient = new PatientController(); $patient->barcodeDownloadSvg(); exit; }


// ==================== LABORATORY ROUTES ====================
if ($request == 'lab/dashboard') { $lab = new LaboratoryController(); $lab->dashboard(); exit; }
if ($request == 'lab/orders') { $lab = new LaboratoryController(); $lab->orders(); exit; }
if ($request == 'lab/create-order') { $lab = new LaboratoryController(); $lab->createOrder(); exit; }
if ($request == 'lab/store-order') { $lab = new LaboratoryController(); $lab->storeOrder(); exit; }
if (preg_match('/lab\/orders\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->viewOrder($matches[1]); exit; }
if ($request == 'lab/collect-sample') { $lab = new LaboratoryController(); $lab->collectSample(); exit; }
if ($request == 'lab/enter-results') { $lab = new LaboratoryController(); $lab->enterResults(); exit; }
if (preg_match('/lab\/enter-result-form\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->enterResultForm($matches[1]); exit; }
if ($request == 'lab/save-result') { $lab = new LaboratoryController(); $lab->saveResult(); exit; }
if ($request == 'lab/reports') { $lab = new LaboratoryController(); $lab->reports(); exit; }
if (preg_match('/lab\/report\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->viewReport($matches[1]); exit; }
if ($request == 'lab/deliver-report') { $lab = new LaboratoryController(); $lab->deliverReport(); exit; }
if ($request == 'lab/sample-collection') { $lab = new LaboratoryController(); $lab->sampleCollection(); exit; }
if ($request == 'lab/update-sample-status') { $lab = new LaboratoryController(); $lab->updateSampleStatus(); exit; }
if ($request == 'lab/generate-single-barcode') { $lab = new LaboratoryController(); $lab->generateSingleBarcode(); exit; }
if ($request == 'lab/print-barcode') { $lab = new LaboratoryController(); $lab->printBarcode(); exit; }
if ($request == 'lab/bulk-collect-samples') { $lab = new LaboratoryController(); $lab->bulkCollectSamples(); exit; }
if ($request == 'lab/get-sample-details') { $lab = new LaboratoryController(); $lab->getSampleDetails(); exit; }
if ($request == 'lab/view-report-ajax') { $lab = new LaboratoryController(); $lab->viewReportAjax(); exit; }
if ($request == 'lab/print-report') { $lab = new LaboratoryController(); $lab->printReport(); exit; }
if ($request == 'lab/export-reports') { $lab = new LaboratoryController(); $lab->exportReports(); exit; }
if (preg_match('/lab\/invoice\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->generateInvoice($matches[1]); exit; }

// ==================== LAB API ROUTES ====================
if ($request == 'api/lab-orders') { $lab = new LaboratoryController(); $lab->apiLabOrders(); exit; }
if ($request == 'api/stat-orders') { $lab = new LaboratoryController(); $lab->apiStatOrders(); exit; }
if ($request == 'api/validate-barcode') { $lab = new LaboratoryController(); $lab->apiValidateBarcode(); exit; }
if ($request == 'api/collect-by-barcode') { $lab = new LaboratoryController(); $lab->apiCollectByBarcode(); exit; }
if ($request == 'api/doctors') { $lab = new LaboratoryController(); $lab->apiDoctors(); exit; }
if ($request == 'api/lab-reports-list') { $lab = new LaboratoryController(); $lab->apiLabReportsList(); exit; }
if ($request == 'api/lab-orders-list') { $lab = new LaboratoryController(); $lab->apiLabOrdersList(); exit; }
if (preg_match('/api\/lab-order-details\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiLabOrderDetails($matches[1]); exit; }
if ($request == 'lab/export-orders') { $lab = new LaboratoryController(); $lab->exportOrders(); exit; }

// ==================== LAB PATIENT TESTS ROUTE ====================
if ($request == 'lab/patient-tests') { $lab = new LaboratoryController(); $lab->patientLabTests(); exit; }

// ==================== LAB PATIENT TESTS EXPORT ROUTE ====================
if ($request == 'lab/export-patient-tests') { $lab = new LaboratoryController(); $lab->exportPatientTests(); exit; }

// ==================== RECEPTION ROUTES ====================
if ($request == 'reception/select-patient') { $reception = new ReceptionController(); $reception->selectPatient(); exit; }

// ==================== VACCINE ROUTES ====================
// Index - List all vaccines
if ($request == 'vaccines' || $request == 'vaccines/index') {
    $vaccine = new VaccineController();
    $vaccine->index();
    exit;
}

// Create - Show add vaccine form
if ($request == 'vaccines/create') {
    $vaccine = new VaccineController();
    $vaccine->create();
    exit;
}

// Save - Store new vaccine
if ($request == 'vaccines/save') {
    $vaccine = new VaccineController();
    $vaccine->save();
    exit;
}

// Edit - Show edit vaccine form
if (preg_match('/vaccines\/edit\/(\d+)/', $request, $matches)) {
    $vaccine = new VaccineController();
    $vaccine->edit($matches[1]);
    exit;
}

// Update - Update vaccine
if (preg_match('/vaccines\/update\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $vaccine = new VaccineController();
    $vaccine->update($matches[1]);
    exit;
}

// Delete - Delete vaccine
if (preg_match('/vaccines\/delete\/(\d+)/', $request, $matches)) {
    $vaccine = new VaccineController();
    $vaccine->delete($matches[1]);
    exit;
}

// Print Card - Print vaccine card
if (preg_match('/vaccines\/print-card\/(\d+)/', $request, $matches)) {
    $vaccine = new VaccineController();
    $vaccine->printCard($matches[1]);
    exit;
}

// Search - Search vaccines by patient
if ($request == 'vaccines/search') {
    $vaccine = new VaccineController();
    $vaccine->search();
    exit;
}

// API - Get vaccines for a patient
if ($request == 'api/get-patient-vaccines') {
    $vaccine = new VaccineController();
    $vaccine->apiGetVaccines();
    exit;
}

// ================================================================
// API - GET PATIENT PRESCRIPTIONS
// ================================================================
if ($request == 'api/get-patient-prescriptions') {
    header('Content-Type: application/json');
    $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
    
    if (!$patientId) {
        echo json_encode(['success' => false, 'message' => 'Patient ID required']);
        exit;
    }
    
    $query = "SELECT p.*, 
                     CONCAT(u.first_name, ' ', u.last_name) as doctor_name
              FROM prescriptions p
              LEFT JOIN doctors d ON p.doctor_id = d.id
              LEFT JOIN users u ON d.user_id = u.id
              WHERE p.patient_id = $patientId
              ORDER BY p.prescription_date DESC
              LIMIT 20";
    
    $result = $conn->query($query);
    $prescriptions = [];
    while ($row = $result->fetch_assoc()) {
        $prescriptions[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $prescriptions]);
    exit;
}

// ================================================================
// API - GET PATIENT APPOINTMENTS
// ================================================================
if ($request == 'api/get-patient-appointments') {
    header('Content-Type: application/json');
    $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
    
    if (!$patientId) {
        echo json_encode(['success' => false, 'message' => 'Patient ID required']);
        exit;
    }
    
    $query = "SELECT a.*, 
                     CONCAT(u.first_name, ' ', u.last_name) as doctor_name
              FROM appointments a
              LEFT JOIN doctors d ON a.doctor_id = d.id
              LEFT JOIN users u ON d.user_id = u.id
              WHERE a.patient_id = $patientId
              ORDER BY a.appointment_date DESC
              LIMIT 20";
    
    $result = $conn->query($query);
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $appointments]);
    exit;
}

// ================================================================
// API - GET PATIENT VACCINES (Already exists, ensure it's there)
// ================================================================
if ($request == 'api/get-patient-vaccines') {
    header('Content-Type: application/json');
    $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
    
    if (!$patientId) {
        echo json_encode(['success' => false, 'message' => 'Patient ID required']);
        exit;
    }
    
    // Check if VaccineController exists
    if (file_exists(BASE_PATH . '/app/controllers/VaccineController.php')) {
        require_once BASE_PATH . '/app/controllers/VaccineController.php';
        $vaccine = new VaccineController();
        $vaccine->apiGetVaccines();
        exit;
    }
    
    // Fallback - direct query
    $query = "SELECT id, patient_id, prescription_id, vaccine_name, dose, 
                     date_given, next_due, batch_number, site, 
                     administered_by, notes, vaccine_image
              FROM patient_vaccinations 
              WHERE patient_id = $patientId AND is_active = 1
              ORDER BY date_given DESC";
    
    $result = $conn->query($query);
    $vaccines = [];
    while ($row = $result->fetch_assoc()) {
        $vaccines[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $vaccines]);
    exit;
}

// ==================== MANUAL LAB ROUTES ====================
// Index - List all manual results
if ($request == 'lab/manual' || $request == 'lab/manual/index') {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->index();
    exit;
}

// Create - Show add form
if ($request == 'lab/manual/create') {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->create();
    exit;
}

// Store - Save manual result
if ($request == 'lab/manual/store') {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->store();
    exit;
}

// Edit - Show edit form
if (preg_match('/lab\/manual\/edit\/(\d+)/', $request, $matches)) {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->edit($matches[1]);
    exit;
}

// Update - Update manual result
if (preg_match('/lab\/manual\/update\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->update($matches[1]);
    exit;
}

// Delete - Delete manual result
if (preg_match('/lab\/manual\/delete\/(\d+)/', $request, $matches)) {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->delete($matches[1]);
    exit;
}

// Print Report - Print individual report
if (preg_match('/lab\/manual\/print-report\/(\d+)/', $request, $matches)) {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->printReport($matches[1]);
    exit;
}

// ==================== 404 ====================
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: #f5f7fa; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            font-family: 'Inter', sans-serif;
            margin: 0;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            max-width: 500px;
        }
        .error-container h1 { font-size: 72px; color: #ef4444; font-weight: 700; margin: 0; }
        .error-container h3 { color: #1e293b; margin: 20px 0 10px; font-size: 24px; }
        .error-container p { color: #64748b; margin-bottom: 20px; }
        .btn-primary { background: #10b981; border: none; padding: 10px 30px; border-radius: 10px; color: white; text-decoration: none; display: inline-block; }
        .btn-primary:hover { background: #059669; color: white; text-decoration: none; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <h3>Page Not Found</h3>
        <p>The page you're looking for doesn't exist.</p>
        <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-primary">Back to Dashboard</a>
    </div>
</body>
</html>
<?php
exit;
?>