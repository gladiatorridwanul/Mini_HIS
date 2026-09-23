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

if ($request == 'reception/daily-list') { $appointment = new AppointmentController(); $appointment->dailyList(); exit; }

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['REQUEST_URI'], '/patient/quick-store') !== false) {
    require_once 'app/controllers/PatientController.php';
    $controller = new PatientController();
    $controller->quickStore();
    exit;
}

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


// public/index.php - Add this at the top after session_start()

// ===== API ROUTE - Direct handling =====
if (strpos($_SERVER['REQUEST_URI'], '/prescriptions/api-medicines') !== false) {
    require_once '../app/controllers/PrescriptionController.php';
    $controller = new PrescriptionController();
    $controller->apiMedicines();
    exit;
}

// public/index.php - Add this at the top

// ===== API ROUTES =====
if (strpos($_SERVER['REQUEST_URI'], '/prescriptions/get-previous-prescriptions') !== false) {
    require_once '../app/controllers/PrescriptionController.php';
    $controller = new PrescriptionController();
    $controller->getPreviousPrescriptions();
    exit;
}

if (strpos($_SERVER['REQUEST_URI'], '/prescriptions/api-medicines') !== false) {
    require_once '../app/controllers/PrescriptionController.php';
    $controller = new PrescriptionController();
    $controller->apiMedicines();
    exit;
}

if (strpos($_SERVER['REQUEST_URI'], '/prescriptions/load-tab-data') !== false) {
    require_once '../app/controllers/PrescriptionController.php';
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