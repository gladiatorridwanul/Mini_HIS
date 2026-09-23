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
$pass = '<Admin123!@#>';
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
// HELPER FUNCTION: Check if user has permission for a route
// ================================================================
function hasRoutePermission($permissionSlug, $userRole, $userPermissions) {
    // Super Admin and Admin have all permissions
    if ($userRole == 'super_admin' || $userRole == 'admin') {
        return true;
    }
    
    // If no permission required, allow access
    if (empty($permissionSlug)) {
        return true;
    }
    
    // Check if user has the specific permission
    return in_array($permissionSlug, $userPermissions);
}

// ================================================================
// API ROUTES - All API endpoints (sorted alphabetically for clarity)
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
        $serviceName = isset($_POST['service_name']) ? $conn->real_escape_string($_POST['service_name']) : 'Consultation';
        $serviceType = isset($_POST['service_type']) ? $conn->real_escape_string($_POST['service_type']) : 'consultation';
        $userId = $_SESSION['user_id'] ?? 1;
        
        if($patientId == 0 || $doctorId == 0 || empty($appointmentDate) || empty($shift)) {
            echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
            exit;
        }
        
        $doctorResult = $conn->query("SELECT d.consultation_fee, CONCAT(u.title, ' ', u.first_name, ' ', u.last_name) as doctor_name 
                                      FROM doctors d 
                                      JOIN users u ON d.user_id = u.id 
                                      WHERE d.id = $doctorId");
        if(!$doctorResult || $doctorResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Doctor not found']);
            exit;
        }
        $doctorData = $doctorResult->fetch_assoc();
        $consultationFee = (float)$doctorData['consultation_fee'];
        $doctorName = $doctorData['doctor_name'] ?? 'Doctor';
        
        // Get service details
        $serviceFee = 0;
        if($serviceId > 0) {
            $serviceResult = $conn->query("SELECT service_name, service_price, service_type FROM doctor_services WHERE id = $serviceId");
            if($serviceResult && $serviceResult->num_rows > 0) {
                $serviceRow = $serviceResult->fetch_assoc();
                $serviceFee = (float)$serviceRow['service_price'];
                $serviceName = $serviceRow['service_name'];
                $serviceType = $serviceRow['service_type'] ?? 'consultation';
            }
        }
        
        $additionalFee = 0;
        $additionalName = '';
        if($additionalServiceId > 0) {
            $additionalResult = $conn->query("SELECT service_name, service_price FROM additional_services WHERE id = $additionalServiceId");
            if($additionalResult && $additionalResult->num_rows > 0) {
                $additionalRow = $additionalResult->fetch_assoc();
                $additionalFee = (float)$additionalRow['service_price'];
                $additionalName = $additionalRow['service_name'];
            }
        }
        
        // Calculate totals
        $subtotal = $serviceFee + $additionalFee;
        $discountAmount = ($subtotal * $discountPercent) / 100;
        $totalAmount = $subtotal - $discountAmount;
        
        // Generate serial number
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
        
        $conn->begin_transaction();
        
        try {
            // ============================================================
            // 1. INSERT APPOINTMENT
            // ============================================================
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
                        " . ($serviceId ? $serviceId : "NULL") . ", $userId
                      )";
            
            if (!$conn->query($query)) {
                throw new Exception("Appointment insert failed: " . $conn->error);
            }
            $appointmentId = $conn->insert_id;
            
            // ============================================================
            // 2. ADD TO QUEUE
            // ============================================================
            $queueExists = $conn->query("SHOW TABLES LIKE 'queue'");
            if($queueExists->num_rows > 0) {
                $conn->query("INSERT INTO queue (appointment_id, serial_number, shift, status) 
                             VALUES ($appointmentId, '$serialNumber', '$shift', 'waiting')");
            }
            
            // ============================================================
            // 3. CREATE BILL
            // ============================================================
            $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
            $billQuery = "INSERT INTO bills (
                            bill_number, patient_id, bill_type, bill_date, 
                            subtotal, discount_amount, discount_percentage, total_amount, 
                            paid_amount, balance_amount, payment_status, 
                            payment_method, created_by, reference_type, reference_id
                          ) VALUES (
                            '$billNumber', $patientId, 'consultation', CURDATE(), 
                            $subtotal, $discountAmount, $discountPercent, $totalAmount, 
                            0, $totalAmount, 'pending', 
                            '$paymentMethod', $userId, 'appointment', $appointmentId
                          )";
            
            if (!$conn->query($billQuery)) {
                throw new Exception("Bill insert failed: " . $conn->error);
            }
            $billId = $conn->insert_id;
            
            // ============================================================
            // 4. INSERT BILL ITEMS - MAIN SERVICE
            // ============================================================
            $itemDescription = $serviceName . ' (Dr. ' . $doctorName . ')';
            $billItemQuery = "INSERT INTO bill_items (
                                bill_id, item_type, description, quantity, 
                                unit_price, discount_percentage, discount_amount,
                                tax_percentage, tax_amount, total_amount
                              ) VALUES (
                                $billId, '$serviceType', '$itemDescription', 1, 
                                $serviceFee, $discountPercent, $discountAmount, 
                                0, 0, $serviceFee
                              )";
            
            if (!$conn->query($billItemQuery)) {
                throw new Exception("Bill item insert failed: " . $conn->error);
            }
            
            // ============================================================
            // 5. INSERT BILL ITEMS - ADDITIONAL SERVICE (if any)
            // ============================================================
            if ($additionalFee > 0 && !empty($additionalName)) {
                $additionalDesc = $additionalName . ' (Dr. ' . $doctorName . ')';
                $additionalItemQuery = "INSERT INTO bill_items (
                                            bill_id, item_type, description, quantity, 
                                            unit_price, discount_percentage, discount_amount,
                                            tax_percentage, tax_amount, total_amount
                                          ) VALUES (
                                            $billId, 'service', '$additionalDesc', 1, 
                                            $additionalFee, 0, 0, 0, 0, $additionalFee
                                          )";
                $conn->query($additionalItemQuery);
            }
            
            // ============================================================
            // 6. CREATE DOCTOR COMMISSION
            // ============================================================
            $commissionPercentage = 20;
            $commissionAmount = ($consultationFee * $commissionPercentage) / 100;
            $conn->query("INSERT INTO doctor_commissions (
                            doctor_id, reference_type, reference_id, amount, 
                            commission_percentage, commission_amount, status
                          ) VALUES (
                            $doctorId, 'consultation', $appointmentId, $consultationFee, 
                            $commissionPercentage, $commissionAmount, 'pending'
                          )");
            
            $conn->commit();
            
            // ============================================================
            // 7. RETURN SUCCESS
            // ============================================================
            echo json_encode([
                'success' => true, 
                'message' => 'Appointment booked successfully! Serial: ' . $serialNumber,
                'appointment_number' => $appointmentNumber,
                'appointment_id' => $appointmentId,
                'serial_number' => $serialNumber,
                'bill_id' => $billId,
                'bill_number' => $billNumber,
                'service_name' => $serviceName,
                'service_price' => $serviceFee,
                'total_amount' => number_format($totalAmount, 2),
                'item_count' => 1 + ($additionalFee > 0 ? 1 : 0)
            ]);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
    
    // FIXED: Join with bills table to get accurate payment information
    $query = "SELECT a.*, 
                     p.first_name, p.last_name, p.phone, p.patient_code, p.id as patient_id,
                     u.first_name as doctor_fname, u.last_name as doctor_lname, d.specialization,
                     b.id as bill_id,
                     b.total_amount as bill_total,
                     b.paid_amount as bill_paid,
                     b.discount_amount as bill_discount,
                     b.balance_amount as bill_balance,
                     b.payment_status as bill_payment_status,
                     COALESCE(a.payment_received, 0) as payment_received,
                     COALESCE(a.total_amount, 0) as total_amount,
                     COALESCE(a.payment_status, 'pending') as payment_status
              FROM appointments a
              JOIN patients p ON a.patient_id = p.id
              JOIN doctors d ON a.doctor_id = d.id
              JOIN users u ON d.user_id = u.id
              LEFT JOIN bills b ON b.reference_type = 'appointment' AND b.reference_id = a.id
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
            
            // Calculate amounts from bill if exists, otherwise from appointment
            if ($row['bill_id']) {
                $row['total_amount'] = (float)$row['bill_total'];
                $row['paid_amount'] = (float)$row['bill_paid'];
                $row['discount_amount'] = (float)$row['bill_discount'];
                $row['balance_amount'] = (float)$row['bill_balance'];
                $row['payment_status'] = $row['bill_payment_status'];
            } else {
                $row['paid_amount'] = (float)($row['payment_received'] ?? 0);
                $row['discount_amount'] = 0;
                $row['balance_amount'] = (float)$row['total_amount'] - $row['paid_amount'];
                if ($row['balance_amount'] < 0) $row['balance_amount'] = 0;
                
                if ($row['balance_amount'] == 0) {
                    $row['payment_status'] = 'paid';
                } elseif ($row['paid_amount'] > 0 && $row['balance_amount'] > 0) {
                    $row['payment_status'] = 'partial';
                } else {
                    $row['payment_status'] = 'pending';
                }
            }
            
            $row['payment_received'] = (float)($row['paid_amount'] ?? 0);
            $appointments[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $appointments]);
    exit;
}

// FIX BILL BALANCES
if ($request == 'bills/fix-balances') {
    $billing = new BillingController();
    $billing->fixBillBalances();
    exit;
}

// API - ADD DEPARTMENT (AJAX)
if ($request == 'api/add-department') {
    // Check if it's from doctor create or user create
    if (file_exists(BASE_PATH . '/app/controllers/DoctorController.php')) {
        require_once BASE_PATH . '/app/controllers/DoctorController.php';
        $doctor = new DoctorController();
        $doctor->apiAddDepartment();
        exit;
    }
    // Fallback to UserController if DoctorController not available
    if (file_exists(BASE_PATH . '/app/controllers/UserController.php')) {
        require_once BASE_PATH . '/app/controllers/UserController.php';
        $user = new UserController();
        $user->apiAddDepartment();
        exit;
    }
    // If both not available, show error
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Controller not found']);
    exit;
}

// API - SEARCH REFERRED BY
if ($request == 'api/search-referred-by') {
    header('Content-Type: application/json');
    $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
    $type = isset($_GET['type']) ? $conn->real_escape_string($_GET['type']) : '';
    
    $where = "WHERE status = 'active'";
    if(!empty($search)) {
        $where .= " AND name LIKE '%$search%'";
    }
    if(!empty($type)) {
        $where .= " AND type = '$type'";
    }
    
    $query = "SELECT id, name, type, phone, email, address, specialization 
              FROM referred_by 
              $where 
              ORDER BY name ASC 
              LIMIT 20";
    
    $result = $conn->query($query);
    $data = [];
    if($result) {
        while($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

// API - ADD REFERRED BY
if ($request == 'api/add-referred-by') {
    header('Content-Type: application/json');
    
    try {
        $name = isset($_POST['name']) ? $conn->real_escape_string(trim($_POST['name'])) : '';
        $type = isset($_POST['type']) ? $conn->real_escape_string($_POST['type']) : 'other';
        $phone = isset($_POST['phone']) ? $conn->real_escape_string(trim($_POST['phone'])) : '';
        $specialization = isset($_POST['specialization']) ? $conn->real_escape_string(trim($_POST['specialization'])) : '';
        
        if(empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Name is required']);
            exit;
        }
        
        // Check if already exists
        $checkQuery = "SELECT id FROM referred_by WHERE name = '$name'";
        $checkResult = $conn->query($checkQuery);
        if($checkResult && $checkResult->num_rows > 0) {
            $row = $checkResult->fetch_assoc();
            echo json_encode(['success' => true, 'id' => $row['id'], 'message' => 'Referrer already exists']);
            exit;
        }
        
        $insertQuery = "INSERT INTO referred_by (name, type, phone, specialization, status, created_at) 
                        VALUES ('$name', '$type', '$phone', '$specialization', 'active', NOW())";
        
        if($conn->query($insertQuery)) {
            $id = $conn->insert_id;
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Referrer added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API - UPDATE APPOINTMENT STATUS
if ($request == 'api/update-appointment-status') {
    header('Content-Type: application/json');
    $appointmentId = (int)$_POST['appointment_id'];
    $status = $conn->real_escape_string($_POST['status']);
    
    // Get the appointment to check its current status
    $appCheck = $conn->query("SELECT patient_id, total_amount, payment_status, payment_received FROM appointments WHERE id = $appointmentId");
    if (!$appCheck || $appCheck->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        exit;
    }
    $appData = $appCheck->fetch_assoc();
    
    // Update appointment status
    if ($conn->query("UPDATE appointments SET status = '$status', updated_at = NOW() WHERE id = $appointmentId")) {
        // Also update bill status if appointment is completed or canceled
        if ($status == 'completed' || $status == 'canceled') {
            // Find the associated bill
            $billCheck = $conn->query("SELECT id, total_amount FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
            if ($billCheck && $billCheck->num_rows > 0) {
                $billData = $billCheck->fetch_assoc();
                $billId = $billData['id'];
                $totalAmount = (float)$billData['total_amount'];
                
                if ($status == 'completed') {
                    // If completed, set payment status to paid and update amounts
                    $conn->query("UPDATE bills SET 
                                  payment_status = 'paid', 
                                  paid_amount = total_amount, 
                                  balance_amount = 0, 
                                  updated_at = NOW() 
                                  WHERE id = $billId");
                    // Also update appointment payment status
                    $conn->query("UPDATE appointments SET 
                                  payment_status = 'paid', 
                                  payment_received = total_amount, 
                                  last_payment_date = NOW() 
                                  WHERE id = $appointmentId");
                } else if ($status == 'canceled') {
                    // If canceled, set payment status to canceled
                    $conn->query("UPDATE bills SET 
                                  payment_status = 'canceled', 
                                  updated_at = NOW() 
                                  WHERE id = $billId");
                    $conn->query("UPDATE appointments SET 
                                  payment_status = 'canceled' 
                                  WHERE id = $appointmentId");
                }
            }
        }
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    exit;
}

// API - UPDATE BILL PAYMENT STATUS
if ($request == 'api/update-bill-payment-status') {
    header('Content-Type: application/json');
    
    $billId = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
    $paymentStatus = isset($_POST['payment_status']) ? $conn->real_escape_string($_POST['payment_status']) : '';
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    
    if ($billId == 0 || empty($paymentStatus)) {
        echo json_encode(['success' => false, 'message' => 'Bill ID and payment status required']);
        exit;
    }
    
    // Validate payment status
    $validStatuses = ['pending', 'partial', 'paid', 'refunded', 'canceled'];
    if (!in_array($paymentStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment status']);
        exit;
    }
    
    // Get bill details
    $billCheck = $conn->query("SELECT patient_id, total_amount, reference_type, reference_id FROM bills WHERE id = $billId");
    if (!$billCheck || $billCheck->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Bill not found']);
        exit;
    }
    $billData = $billCheck->fetch_assoc();
    $totalAmount = (float)$billData['total_amount'];
    $referenceType = $billData['reference_type'];
    $referenceId = (int)$billData['reference_id'];
    
    $conn->begin_transaction();
    
    try {
        // Update bill payment status
        if ($paymentStatus == 'paid') {
            $paidAmount = $totalAmount;
            $balanceAmount = 0;
        } elseif ($paymentStatus == 'partial') {
            $paidAmount = $amount > 0 ? $amount : $totalAmount * 0.5;
            $balanceAmount = $totalAmount - $paidAmount;
        } elseif ($paymentStatus == 'pending') {
            $paidAmount = 0;
            $balanceAmount = $totalAmount;
        } else {
            $paidAmount = 0;
            $balanceAmount = $totalAmount;
        }
        
        $updateBill = "UPDATE bills SET 
                        payment_status = '$paymentStatus',
                        paid_amount = $paidAmount,
                        balance_amount = $balanceAmount,
                        updated_at = NOW()
                        WHERE id = $billId";
        
        if (!$conn->query($updateBill)) {
            throw new Exception("Failed to update bill: " . $conn->error);
        }
        
        // If bill is linked to an appointment, update appointment payment status
        if ($referenceType == 'appointment' && $referenceId > 0) {
            $updateAppointment = "UPDATE appointments SET 
                                    payment_status = '$paymentStatus',
                                    payment_received = $paidAmount,
                                    updated_at = NOW()
                                    WHERE id = $referenceId";
            $conn->query($updateAppointment);
            
            // If paid, also update appointment status to completed if not already
            if ($paymentStatus == 'paid') {
                $conn->query("UPDATE appointments SET status = 'completed' WHERE id = $referenceId");
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Payment status updated successfully',
            'payment_status' => $paymentStatus,
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// API - GET DOCTOR DAILY PATIENTS
if ($request == 'api/doctor-daily-patients') {
    header('Content-Type: application/json');
    
    $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $conn->real_escape_string($_GET['date']) : date('Y-m-d');
    
    if ($doctorId == 0) {
        echo json_encode(['error' => 'Doctor ID required']);
        exit;
    }
    
    $query = "SELECT a.*, 
                     p.first_name, 
                     p.last_name, 
                     p.patient_code,
                     p.phone,
                     CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                     u.first_name as doctor_fname,
                     u.last_name as doctor_lname,
                     d.specialization,
                     ds.service_name,
                     ds.service_price,
                     COALESCE(a.total_amount, 0) as total_amount,
                     COALESCE(a.payment_received, 0) as payment_received,
                     COALESCE(a.payment_status, 'pending') as payment_status,
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
              WHERE a.appointment_date = '$date'
              AND a.doctor_id = $doctorId
              AND a.status NOT IN ('canceled')
              ORDER BY a.session_type, a.start_time ASC";
    
    $result = $conn->query($query);
    $appointments = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            if (empty($row['service_name'])) {
                $row['service_name'] = 'Consultation';
            }
            $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . 
                                    str_pad($row['serial_number'] ?? 0, 3, '0', STR_PAD_LEFT);
            $appointments[] = $row;
        }
    }
    
    echo json_encode($appointments);
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

// ==================== API: GET PATIENTS ====================
if ($request == 'api/get-patients') { 
    $appointment = new AppointmentController(); 
    $appointment->getPatients(); 
    exit; 
}

// ==================== API: SEARCH PATIENTS ====================
if ($request == 'api/search-patients') { 
    $appointment = new AppointmentController(); 
    $appointment->searchPatient(); 
    exit; 
}

// ==================== PATIENT QUICK STORE ====================
if ($request == 'patient/quick-store' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
    $appointment = new AppointmentController(); 
    $appointment->quickStore(); 
    exit; 
}

// API - GET DOCTOR AVAILABILITY (Schedule)
if ($request == 'api/get-doctor-availability') {
    header('Content-Type: application/json');
    $appointment = new AppointmentController();
    $appointment->getDoctorAvailability();
    exit;
}

// API - GET DIVISIONS
if ($request == 'api/get-divisions') {
    $appointment = new AppointmentController();
    $appointment->getDivisions();
    exit;
}

// API - DAILY PATIENTS
if ($request == 'api/daily-patients') { 
    $appointment = new AppointmentController(); 
    $appointment->apiDailyPatients(); 
    exit; 
}

// API - PRINT SERIAL SLIP
if ($request == 'api/print-serial') { 
    $appointment = new AppointmentController(); 
    $appointment->apiPrintSerial(); 
    exit; 
}

// API - QUEUE LIST
if ($request == 'api/queue-list') { 
    $appointment = new AppointmentController(); 
    $appointment->apiQueueList(); 
    exit; 
}

// API - CALL PATIENT
if ($request == 'api/call-patient') { 
    $appointment = new AppointmentController(); 
    $appointment->apiCallPatient(); 
    exit; 
}

// API - RESCHEDULE APPOINTMENT
if ($request == 'api/reschedule-appointment') { 
    $appointment = new AppointmentController(); 
    $appointment->rescheduleAppointment(); 
    exit; 
}

// API - LAB TESTS (Search)
if ($request == 'api/lab-tests' || $request == 'laboratory/api-lab-tests' || $request == 'prescriptions/api-lab-tests') { 
    $lab = new LaboratoryController(); 
    $lab->apiLabTests(); 
    exit; 
}

// API - LAB TESTS LIST (with search/filter)
if ($request == 'api/lab-tests' && $_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['id']) && !isset($_GET['q'])) { 
    $lab = new LaboratoryController(); 
    $lab->apiLabTestsList(); 
    exit; 
}

// API - GET SINGLE LAB TEST
if (preg_match('/api\/lab-tests\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'GET') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetLabTest($matches[1]); 
    exit; 
}

// API - SAVE LAB TEST (POST - Add new)
if ($request == 'api/lab-tests' && $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $lab = new LaboratoryController(); 
    $lab->apiSaveLabTest(); 
    exit; 
}

// API - UPDATE LAB TEST (POST with PUT method)
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

// API - TOGGLE LAB TEST STATUS
if (preg_match('/api\/lab-tests\/toggle\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiToggleLabTest($matches[1]); 
    exit; 
}

// API - DELETE LAB TEST
if (preg_match('/api\/lab-tests\/delete\/(\d+)/', $request, $matches)) { 
    $lab = new LaboratoryController(); 
    $lab->apiDeleteLabTest($matches[1]); 
    exit; 
}

// API - LAB ORDERS
if ($request == 'api/lab-orders') { $lab = new LaboratoryController(); $lab->apiLabOrders(); exit; }
if ($request == 'api/stat-orders') { $lab = new LaboratoryController(); $lab->apiStatOrders(); exit; }
if ($request == 'api/validate-barcode') { $lab = new LaboratoryController(); $lab->apiValidateBarcode(); exit; }
if ($request == 'api/collect-by-barcode') { $lab = new LaboratoryController(); $lab->apiCollectByBarcode(); exit; }
if ($request == 'api/doctors') { $lab = new LaboratoryController(); $lab->apiDoctors(); exit; }
if ($request == 'api/lab-reports-list') { $lab = new LaboratoryController(); $lab->apiLabReportsList(); exit; }
if ($request == 'api/lab-orders-list') { $lab = new LaboratoryController(); $lab->apiLabOrdersList(); exit; }
if (preg_match('/api\/lab-order-details\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiLabOrderDetails($matches[1]); exit; }

// API - GET PRESCRIPTION LAB TESTS
if ($request == 'api/get-prescription-lab-tests') { 
    $lab = new LaboratoryController(); 
    $lab->apiGetPrescriptionLabTests(); 
    exit; 
}

// API - GET BILL DETAILS (for payment modal)
if ($request == 'api/get-bill-details') {
    header('Content-Type: application/json');
    $billId = isset($_GET['bill_id']) ? (int)$_GET['bill_id'] : 0;
    
    if ($billId == 0) {
        echo json_encode(['success' => false, 'message' => 'Bill ID required']);
        exit;
    }
    
    // Get bill with patient info
    $query = "SELECT b.*, 
                     CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                     p.patient_code, 
                     p.id as patient_id,
                     p.phone,
                     b.reference_type, 
                     b.reference_id
              FROM bills b
              JOIN patients p ON b.patient_id = p.id
              WHERE b.id = $billId";
    
    $result = $conn->query($query);
    $bill = $result->fetch_assoc();
    
    if ($bill) {
        // ================================================================
        // GET BILL ITEMS
        // ================================================================
        $itemsQuery = "SELECT bi.*, 
                              bi.id as item_id,
                              bi.item_type,
                              bi.description,
                              bi.quantity,
                              bi.unit_price,
                              bi.total_amount,
                              bi.discount_amount,
                              bi.tax_amount
                       FROM bill_items bi
                       WHERE bi.bill_id = $billId
                       ORDER BY bi.id ASC";
        
        $itemsResult = $conn->query($itemsQuery);
        $items = [];
        $itemCount = 0;
        while ($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
            $itemCount++;
        }
        $bill['items'] = $items;
        $bill['item_count'] = $itemCount;
        
        // Get service name from first item or appointment
        if ($itemCount > 0) {
            $bill['service_name'] = $items[0]['description'] ?? 'Consultation';
        } elseif ($bill['reference_type'] == 'appointment' && $bill['reference_id'] > 0) {
            $appQuery = $conn->query("SELECT ds.service_name, CONCAT(u.first_name, ' ', u.last_name) as doctor_name 
                                      FROM appointments a 
                                      LEFT JOIN doctor_services ds ON a.service_id = ds.id 
                                      LEFT JOIN doctors d ON a.doctor_id = d.id
                                      LEFT JOIN users u ON d.user_id = u.id
                                      WHERE a.id = {$bill['reference_id']}");
            if ($appQuery && $appQuery->num_rows > 0) {
                $appData = $appQuery->fetch_assoc();
                $bill['service_name'] = $appData['service_name'] ?? 'Consultation';
                $bill['doctor_name'] = $appData['doctor_name'] ?? '';
            }
        }
        
        // Get payments
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

// API - PROCESS BILL PAYMENT
if ($request == 'api/process-bill-payment') {
    header('Content-Type: application/json');
    
    try {
        $billId = (int)$_POST['bill_id'];
        $patientId = (int)$_POST['patient_id'];
        $receivedAmount = (float)$_POST['amount'];
        $paymentMethod = $conn->real_escape_string($_POST['payment_method']);
        $transactionId = isset($_POST['transaction_id']) ? $conn->real_escape_string($_POST['transaction_id']) : '';
        $notes = isset($_POST['notes']) ? $conn->real_escape_string($_POST['notes']) : '';
        $userId = $_SESSION['user_id'];
        
        $discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
        $discountAmount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0;
        $fullDiscount = isset($_POST['full_discount']) ? (int)$_POST['full_discount'] : 0;
        
        // Fetch current bill
        $billQuery = "SELECT * FROM bills WHERE id = $billId";
        $billResult = $conn->query($billQuery);
        if (!$billResult || $billResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
            exit;
        }
        $bill = $billResult->fetch_assoc();
        
        $currentTotal = round((float)$bill['total_amount'], 2);
        $currentPaid = round((float)$bill['paid_amount'], 2);
        $currentDiscount = round((float)$bill['discount_amount'], 2);
        
        // CORRECT: Current balance = total - paid - discount
        $currentBalance = round($currentTotal - $currentPaid - $currentDiscount, 2);
        if ($currentBalance < 0.01) $currentBalance = 0;
        
        $appliedDiscount = 0;
        
        if ($fullDiscount == 1) {
            $appliedDiscount = $currentBalance;
            $receivedAmount = 0;
        } elseif ($discountAmount > 0) {
            $appliedDiscount = min($discountAmount, $currentBalance);
        } elseif ($discountPercent > 0) {
            $appliedDiscount = min(round($currentBalance * ($discountPercent / 100), 2), $currentBalance);
        }
        
        $appliedDiscount = round($appliedDiscount, 2);
        
        $newPaid = round($currentPaid + $receivedAmount, 2);
        $newDiscount = round($currentDiscount + $appliedDiscount, 2);
        
        // CORRECT: New balance = total - new_paid - new_discount
        $newBalance = round($currentTotal - $newPaid - $newDiscount, 2);
        if ($newBalance < 0.01) $newBalance = 0;
        
        $paymentStatus = ($newBalance == 0) ? 'paid' : 'partial';
        $paymentNumber = 'PAY' . date('Ymd') . rand(1000, 9999);
        
        $conn->begin_transaction();
        
        // Insert payment record
        if ($receivedAmount > 0 || $appliedDiscount > 0) {
            $insertPayment = "INSERT INTO payments (payment_number, bill_id, patient_id, amount, payment_method, 
                              transaction_id, notes, payment_date, received_by, created_at) 
                              VALUES ('$paymentNumber', $billId, $patientId, $receivedAmount, '$paymentMethod', 
                              '$transactionId', '$notes', CURDATE(), $userId, NOW())";
            if (!$conn->query($insertPayment)) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to insert payment: ' . $conn->error]);
                exit;
            }
        }
        
        // Update bill
        $updateBill = "UPDATE bills SET 
                      paid_amount = $newPaid,
                      discount_amount = $newDiscount,
                      balance_amount = $newBalance,
                      payment_status = '$paymentStatus',
                      updated_at = NOW()
                      WHERE id = $billId";
        if (!$conn->query($updateBill)) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to update bill: ' . $conn->error]);
            exit;
        }
        
        // Update reference appointment if linked
        if ($bill['reference_type'] == 'appointment' && $bill['reference_id'] > 0) {
            $appointmentId = (int)$bill['reference_id'];
            
            $appointmentQuery = "SELECT * FROM appointments WHERE id = $appointmentId";
            $appointmentResult = $conn->query($appointmentQuery);
            if ($appointmentResult && $appointmentResult->num_rows > 0) {
                $appointment = $appointmentResult->fetch_assoc();
                
                $appCurrentPaid = (float)($appointment['payment_received'] ?? 0);
                $appNewPaid = $appCurrentPaid + $receivedAmount;
                $appTotal = (float)$appointment['total_amount'];
                
                $appPaymentStatus = 'pending';
                if ($appNewPaid >= $appTotal) {
                    $appPaymentStatus = 'paid';
                } elseif ($appNewPaid > 0 && $appNewPaid < $appTotal) {
                    $appPaymentStatus = 'partial';
                }
                
                $updateAppointment = "UPDATE appointments SET 
                                      payment_status = '$appPaymentStatus',
                                      payment_received = $appNewPaid,
                                      last_payment_date = NOW(),
                                      updated_at = NOW()
                                      WHERE id = $appointmentId";
                $conn->query($updateAppointment);
                
                if ($newBalance == 0 && $appointment['status'] != 'completed' && $appointment['status'] != 'canceled') {
                    $conn->query("UPDATE appointments SET status = 'completed' WHERE id = $appointmentId");
                    $conn->query("UPDATE queue SET status = 'completed' WHERE appointment_id = $appointmentId");
                }
            }
        }
        
        // FIXED: Update pharmacy sale using 'status' column (not 'payment_status')
        if ($bill['reference_type'] == 'pharmacy_sale' && $bill['reference_id'] > 0) {
            $saleId = (int)$bill['reference_id'];
            $saleStatus = 'completed'; // pharmacy_sales uses 'status' column with values: 'completed', 'refunded', 'canceled'
            $conn->query("UPDATE pharmacy_sales SET 
                          status = '$saleStatus',
                          paid_amount = $newPaid
                          WHERE id = $saleId");
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Payment: ৳ ' . number_format($receivedAmount, 2) . ' | Discount: ৳ ' . number_format($appliedDiscount, 2),
            'bill_id' => $billId,
            'new_balance' => $newBalance,
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

// API - SYNC APPOINTMENT PAYMENT STATUS
if ($request == 'api/sync-appointment-payment') {
    $appointment = new AppointmentController();
    $appointment->syncAppointmentPayment();
    exit;
}

// API - GET APPOINTMENT PAYMENT STATUS
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

// ===== API ROUTE - Direct handling =====
if (strpos($_SERVER['REQUEST_URI'], '/prescriptions/api-medicines') !== false) {
    require_once BASE_PATH . '/app/controllers/PrescriptionController.php';
    $controller = new PrescriptionController();
    $controller->apiMedicines();
    exit;
}

// API - MEDICINES (autocomplete)
if ($request == 'api/medicines') { 
    $prescription = new PrescriptionController(); 
    $prescription->apiMedicines(); 
    exit; 
}

// API - GET PATIENT PRESCRIPTIONS
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

if ($request == 'pharmacy/update-item-discount') { 
    $pharmacy = new PharmacyController(); 
    $pharmacy->updateItemDiscount(); 
    exit; 
}

// API - GET PATIENT APPOINTMENTS
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

// API - GET PATIENT VACCINES
if ($request == 'api/get-patient-vaccines') {
    header('Content-Type: application/json');
    $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
    
    if (!$patientId) {
        echo json_encode(['success' => false, 'message' => 'Patient ID required']);
        exit;
    }
    
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

// API - ATTENDANCE
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

// API - PAYROLL
if ($request == 'api/payroll/generate-all') {
    header('Content-Type: application/json');
    $user = new UserController();
    $user->apiGenerateAllPayroll();
    exit;
}

if ($request == 'api/payroll/get-details') {
    header('Content-Type: application/json');
    $user = new UserController();
    $user->apiGetPayrollDetails();
    exit;
}

if ($request == 'api/payroll/update-salary') {
    header('Content-Type: application/json');
    $user = new UserController();
    $user->apiUpdateSalary();
    exit;
}

if ($request == 'api/payroll/mark-paid') {
    header('Content-Type: application/json');
    $user = new UserController();
    $user->apiMarkPaid();
    exit;
}

if ($request == 'api/payroll/print-slip') {
    $user = new UserController();
    $user->apiPrintPayslip();
    exit;
}

if ($request == 'api/payroll/export') {
    $user = new UserController();
    $user->apiExportPayroll();
    exit;
}

// API - SALES LIST
if ($request == 'api/sales-list') { 
    $pharmacy = new PharmacyController(); 
    $pharmacy->salesList(); 
    exit; 
}

// API - BILL DETAILS (AJAX)
if ($request == 'api/bill-details') { 
    $billId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $billing = new BillingController(); 
    $billing->getBillDetailsAjax($billId); 
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

// ==================== GET USER PERMISSIONS FOR ROLE-WISE ACCESS ====================
$userRole = $_SESSION['role_slug'] ?? 'guest';
$userPermissions = [];

// Get user permissions from session or database
if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
    $userPermissions = $_SESSION['permissions'];
} elseif (isset($_SESSION['role_id'])) {
    try {
        $permResult = $conn->query("SELECT permission_slug FROM role_permissions WHERE role_id = {$_SESSION['role_id']}");
        if ($permResult) {
            while ($row = $permResult->fetch_assoc()) {
                $userPermissions[] = $row['permission_slug'];
            }
            $_SESSION['permissions'] = $userPermissions;
        }
    } catch (Exception $e) {
        // Silently fail
    }
}

// ================================================================
// DASHBOARD - All Roles Redirect to admin/dashboard
// ================================================================

// Main dashboard route
if ($request == 'admin/dashboard' || $request == 'dashboard') { 
    $dashboard = new DashboardController(); 
    $dashboard->index(); 
    exit; 
}

// Doctor dashboard redirect
if ($request == 'doctor/dashboard') { 
    header('Location: ' . BASE_URL . '/admin/dashboard');
    exit; 
}

// Reception dashboard redirect
if ($request == 'reception/dashboard') { 
    header('Location: ' . BASE_URL . '/admin/dashboard');
    exit; 
}

// Pharmacy dashboard redirect
if ($request == 'pharmacy/dashboard') { 
    header('Location: ' . BASE_URL . '/admin/dashboard');
    exit; 
}

// Lab dashboard redirect
if ($request == 'lab/dashboard') { 
    header('Location: ' . BASE_URL . '/admin/dashboard');
    exit; 
}

// Nurse dashboard redirect (if exists)
if ($request == 'nurse/dashboard') { 
    header('Location: ' . BASE_URL . '/admin/dashboard');
    exit; 
}

// Accountant dashboard redirect (if exists)
if ($request == 'accountant/dashboard') { 
    header('Location: ' . BASE_URL . '/admin/dashboard');
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
if (preg_match('/admin\/users\/roles\/update\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') { $user = new UserController(); $user->updateRolePermissions($matches[1]); exit; }
if ($request == 'admin/users/attendance') { $user = new UserController(); $user->attendance(); exit; }
if ($request == 'admin/users/payroll') { $user = new UserController(); $user->payroll(); exit; }

// ==================== PATIENT MANAGEMENT ====================
if ($request == 'patient/list') { $patient = new PatientController(); $patient->index(); exit; }
if ($request == 'patient/register') { $patient = new PatientController(); $patient->register(); exit; }
if ($request == 'patient/store') { $patient = new PatientController(); $patient->store(); exit; }
if ($request == 'patient/quick-store' && $_SERVER['REQUEST_METHOD'] === 'POST') { $patient = new PatientController(); $patient->quickStore(); exit; }
if ($request == 'patient/id-card') { $patient = new PatientController(); $patient->idCard(); exit; }
if ($request == 'patient/view' || $request == 'patient/show') { $patient = new PatientController(); $patient->show(); exit; }
if ($request == 'patient/edit') { $patient = new PatientController(); $patient->edit(); exit; }
if ($request == 'patient/update') { $patient = new PatientController(); $patient->update(); exit; }
if ($request == 'patient/delete') { $patient = new PatientController(); $patient->delete(); exit; }
if ($request == 'patient/book-appointment') { $patient = new PatientController(); $patient->bookAppointment(); exit; }
if ($request == 'patient/id-card-front') { $patient = new PatientController(); $patient->idCardFront(); exit; }
if ($request == 'patient/id-card-back') { $patient = new PatientController(); $patient->idCardBack(); exit; }
if ($request == 'patient/print-id-front') { $patient = new PatientController(); $patient->printIdCardFront(); exit; }
if ($request == 'patient/print-id-back') { $patient = new PatientController(); $patient->printIdCardBack(); exit; }
if ($request == 'patient/barcode-view') { $patient = new PatientController(); $patient->barcodeView(); exit; }
if ($request == 'patient/barcode-print') { $patient = new PatientController(); $patient->barcodePrint(); exit; }
if ($request == 'patient/barcode-download') { $patient = new PatientController(); $patient->barcodeDownload(); exit; }

// BARCODE ROUTES (alternative paths)
if ($request == 'barcode/print') { $patient = new PatientController(); $patient->barcodePrint(); exit; }
if ($request == 'barcode/view') { $patient = new PatientController(); $patient->barcodeView(); exit; }
if ($request == 'barcode/download') { $patient = new PatientController(); $patient->barcodeDownload(); exit; }
if ($request == 'barcode/download-svg') { $patient = new PatientController(); $patient->barcodeDownloadSvg(); exit; }

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

// ==================== DOCTOR COMMISSION API ROUTES ====================
if ($request == 'doctor/commission/approve') { $doctor = new DoctorController(); $doctor->apiApproveCommission(); exit; }
if ($request == 'doctor/commission/pay') { $doctor = new DoctorController(); $doctor->apiPayCommission(); exit; }
if (preg_match('/doctor\/commission\/details\/(\d+)/', $request, $matches)) { $doctor = new DoctorController(); $doctor->apiCommissionDetails($matches[1]); exit; }
if ($request == 'doctor/commission/export') { $doctor = new DoctorController(); $doctor->apiExportCommissions(); exit; }
if ($request == 'doctor/commission/report') { $doctor = new DoctorController(); $doctor->apiGenerateCommissionReport(); exit; }
if (preg_match('/doctor\/commission\/print\/(\d+)/', $request, $matches)) { $doctor = new DoctorController(); $doctor->apiPrintCommission($matches[1]); exit; }

// ==================== RECEPTION ROUTES ====================
// All reception routes with role-wise access control

// Reception Dashboard - Redirect to appointments
if ($request == 'reception/dashboard') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $userRole = $_SESSION['role_slug'] ?? 'guest';
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'nurse', 'doctor'];
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to access reception dashboard.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    header('Location: ' . BASE_URL . '/reception/appointments');
    exit; 
}

// Daily Patient List
if ($request == 'reception/daily-list') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
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

// View Appointment (must be BEFORE generic appointment routes)
if (preg_match('/^appointments\/view\/(\d+)$/', $request, $matches)) { 
    $appointment = new AppointmentController(); 
    $appointment->viewAppointment($matches[1]); 
    exit; 
}

// Also add alias for appointments/show
if (preg_match('/^appointments\/show\/(\d+)$/', $request, $matches)) { 
    $appointment = new AppointmentController(); 
    $appointment->view($matches[1]); 
    exit; 
}

// API routes for appointment actions (add these as well)
if ($request == 'api/appointments/update-status') { 
    $appointment = new AppointmentController(); 
    $appointment->updateStatus(); 
    exit; 
}

if ($request == 'api/appointments/cancel') { 
    $appointment = new AppointmentController(); 
    $appointment->cancel(); 
    exit; 
}

if ($request == 'api/appointments/reschedule') { 
    $appointment = new AppointmentController(); 
    $appointment->reschedule(); 
    exit; 
}

// ================================================================
// APPOINTMENT ROUTES - Add these in the RECEPTION ROUTES section
// ================================================================

// View Appointment (must be BEFORE generic appointment routes)
if (preg_match('/^appointments\/view\/(\d+)$/', $request, $matches)) { 
    $appointment = new AppointmentController(); 
    $appointment->view($matches[1]); 
    exit; 
}

// View Appointment - Alias
if (preg_match('/^appointments\/show\/(\d+)$/', $request, $matches)) { 
    $appointment = new AppointmentController(); 
    $appointment->view($matches[1]); 
    exit; 
}

// API: Update Appointment Status
if ($request == 'api/appointments/update-status') { 
    header('Content-Type: application/json');
    $appointment = new AppointmentController(); 
    $appointment->updateStatus(); 
    exit; 
}

// API: Cancel Appointment
if ($request == 'api/appointments/cancel') { 
    header('Content-Type: application/json');
    $appointment = new AppointmentController(); 
    $appointment->cancel(); 
    exit; 
}

// API: Reschedule Appointment
if ($request == 'api/appointments/reschedule') { 
    header('Content-Type: application/json');
    $appointment = new AppointmentController(); 
    $appointment->reschedule(); 
    exit; 
}

// API: Get Appointment Details
if (preg_match('/^api\/appointments\/(\d+)$/', $request, $matches)) { 
    header('Content-Type: application/json');
    $appointment = new AppointmentController(); 
    $appointment->getDetails($matches[1]); 
    exit; 
}

// Check-in
if ($request == 'reception/check-in') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
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

// Queue Management
if ($request == 'reception/queue') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
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

// Call Patient
if ($request == 'reception/call-patient') { 
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->callPatient(); 
    exit; 
}

// API - COMPLETE CONSULTATION
if ($request == 'reception/complete-consultation') {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
    
    if (!$appointmentId) {
        echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        // Update appointment status
        $updateAppointment = "UPDATE appointments SET 
                              status = 'completed', 
                              updated_at = NOW() 
                              WHERE id = $appointmentId";
        if (!$conn->query($updateAppointment)) {
            throw new Exception('Failed to update appointment: ' . $conn->error);
        }
        
        // Update queue status
        $conn->query("UPDATE queue SET status = 'completed' WHERE appointment_id = $appointmentId");
        
        // Find the associated bill
        $billCheck = $conn->query("SELECT id, total_amount, paid_amount, payment_status 
                                   FROM bills 
                                   WHERE reference_type = 'appointment' 
                                   AND reference_id = $appointmentId");
        
        if ($billCheck && $billCheck->num_rows > 0) {
            $billData = $billCheck->fetch_assoc();
            $billId = $billData['id'];
            $totalAmount = (float)$billData['total_amount'];
            $paidAmount = (float)$billData['paid_amount'];
            $balanceAmount = $totalAmount - $paidAmount;
            
            // If there's a balance, we set payment status to partial
            // If paid amount equals total, set to paid
            // If no payment, keep as pending
            if ($balanceAmount <= 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0 && $balanceAmount > 0) {
                $paymentStatus = 'partial';
            } else {
                $paymentStatus = 'pending';
            }
            
            // Update bill payment status
            $updateBill = "UPDATE bills SET 
                           payment_status = '$paymentStatus',
                           balance_amount = $balanceAmount,
                           updated_at = NOW() 
                           WHERE id = $billId";
            if (!$conn->query($updateBill)) {
                throw new Exception('Failed to update bill: ' . $conn->error);
            }
            
            // Update appointment payment status to match
            $updateAppointmentPayment = "UPDATE appointments SET 
                                         payment_status = '$paymentStatus',
                                         updated_at = NOW() 
                                         WHERE id = $appointmentId";
            $conn->query($updateAppointmentPayment);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Consultation completed successfully',
            'appointment_id' => $appointmentId
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Search Patient
if ($request == 'reception/search') { 
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->searchPatient(); 
    exit; 
}

// Get Patient Appointments
if ($request == 'reception/get-patient-appointments') { 
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->getPatientAppointments(); 
    exit; 
}

// Get Queue Status
if ($request == 'reception/get-queue-status') { 
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->getQueueStatus(); 
    exit; 
}

// Get Filtered Queue
if ($request == 'reception/get-filtered-queue') { 
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->getFilteredQueue(); 
    exit; 
}

// Get Queue Stats
if ($request == 'reception/get-queue-stats') { 
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->getQueueStats(); 
    exit; 
}

// Select Patient
if ($request == 'reception/select-patient') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'nurse'];
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->selectPatient(); 
    exit; 
}

// Doctor List
if ($request == 'reception/doctor-list') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'doctor', 'nurse'];
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to access doctor list.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->doctorList(); 
    exit; 
}

// Legacy appointment routes
if ($request == 'appointments/book') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'doctor', 'nurse'];
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to book appointments.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->book(); 
    exit; 
}

if ($request == 'reception/today') { 
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/login');
        exit;
    }
    $allowedRoles = ['super_admin', 'admin', 'receptionist', 'doctor', 'nurse'];
    if (!in_array($userRole, $allowedRoles)) {
        $_SESSION['error'] = "You don't have permission to view today's appointments.";
        header('Location: ' . BASE_URL . '/admin/dashboard');
        exit;
    }
    $appointment = new AppointmentController(); 
    $appointment->today(); 
    exit; 
}

// ==================== PHARMACY ROUTES ====================
if ($request == 'pharmacy/dashboard') { $pharmacy = new PharmacyController(); $pharmacy->dashboard(); exit; }
if ($request == 'pharmacy/dashboard-stats') { $pharmacy = new PharmacyController(); $pharmacy->dashboardStats(); exit; }
if ($request == 'pharmacy/medicines') { $pharmacy = new PharmacyController(); $pharmacy->medicines(); exit; }
if ($request == 'pharmacy/pos') { $pharmacy = new PharmacyController(); $pharmacy->pos(); exit; }
if ($request == 'pharmacy/prescriptions') { $pharmacy = new PharmacyController(); $pharmacy->prescriptions(); exit; }
if ($request == 'pharmacy/stock') { $pharmacy = new PharmacyController(); $pharmacy->stock(); exit; }
if ($request == 'pharmacy/sales') { $pharmacy = new PharmacyController(); $pharmacy->sales(); exit; }
if ($request == 'pharmacy/sales-data') { $pharmacy = new PharmacyController(); $pharmacy->salesData(); exit; }
if (preg_match('/pharmacy\/dispense\/(\d+)/', $request, $matches)) { $pharmacy = new PharmacyController(); $pharmacy->dispense($matches[1]); exit; }
if ($request == 'pharmacy/dispense-process') { $pharmacy = new PharmacyController(); $pharmacy->dispenseProcess(); exit; }
if (preg_match('/pharmacy\/invoice\/(\d+)/', $request, $matches)) { $pharmacy = new PharmacyController(); $pharmacy->invoice($matches[1]); exit; }
if (preg_match('/pharmacy\/sale-details\/(\d+)/', $request, $matches)) { $pharmacy = new PharmacyController(); $pharmacy->saleDetails($matches[1]); exit; }
if ($request == 'pharmacy/move-to-expiry') { $pharmacy = new PharmacyController(); $pharmacy->moveToExpiry(); exit; }
if ($request == 'pharmacy/bulk-move-expiry') { $pharmacy = new PharmacyController(); $pharmacy->bulkMoveExpiry(); exit; }

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

// ==================== LABORATORY ROUTES ====================
if ($request == 'lab/dashboard') { $lab = new LaboratoryController(); $lab->dashboard(); exit; }
if ($request == 'lab/orders') { $lab = new LaboratoryController(); $lab->orders(); exit; }
if ($request == 'lab/create-order') { $lab = new LaboratoryController(); $lab->createOrder(); exit; }
if ($request == 'lab/store-order') { $lab = new LaboratoryController(); $lab->storeOrder(); exit; }
if (preg_match('/lab\/orders\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->viewOrder($matches[1]); exit; }
if (preg_match('/lab\/view-order\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->viewOrder($matches[1]); exit; }
if ($request == 'lab/collect-sample') { $lab = new LaboratoryController(); $lab->collectSample(); exit; }
if ($request == 'lab/enter-results') { $lab = new LaboratoryController(); $lab->enterResults(); exit; }
if (preg_match('/lab\/enter-result-form\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->enterResultForm($matches[1]); exit; }
if (preg_match('/lab\/enter-results-form\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->enterResultForm($matches[1]); exit; }
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
if ($request == 'lab/export-orders') { $lab = new LaboratoryController(); $lab->exportOrders(); exit; }
if (preg_match('/lab\/invoice\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->generateInvoice($matches[1]); exit; }
if ($request == 'lab/patient-tests') { $lab = new LaboratoryController(); $lab->patientLabTests(); exit; }
if ($request == 'lab/export-patient-tests') { $lab = new LaboratoryController(); $lab->exportPatientTests(); exit; }
if ($request == 'lab/prescriptions') { $lab = new LaboratoryController(); $lab->prescriptions(); exit; }
if (preg_match('/lab\/view-prescription-tests\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->viewPrescriptionTests($matches[1]); exit; }
if ($request == 'lab/create-order-from-prescription') { $lab = new LaboratoryController(); $lab->createOrderFromPrescription(); exit; }

// ==================== LAB MANAGEMENT ROUTES ====================
if ($request == 'lab/manage-tests') { $lab = new LaboratoryController(); $lab->manageTests(); exit; }
if ($request == 'lab/add-test') { $lab = new LaboratoryController(); $lab->addTest(); exit; }
if (preg_match('/lab\/edit-test\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editTest($matches[1]); exit; }
if ($request == 'lab/api/save-test') { $lab = new LaboratoryController(); $lab->apiSaveTest(); exit; }

// ==================== LAB CATEGORY ROUTES ====================
if ($request == 'lab/categories') { $lab = new LaboratoryController(); $lab->manageCategories(); exit; }
if ($request == 'lab/category/add') { $lab = new LaboratoryController(); $lab->addCategory(); exit; }
if (preg_match('/lab\/category\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editCategory($matches[1]); exit; }
if (preg_match('/lab\/category\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteCategory($matches[1]); exit; }
if (preg_match('/lab\/api\/category\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetCategory($matches[1]); exit; }
if ($request == 'lab/api/category') { $lab = new LaboratoryController(); $lab->addCategory(); exit; }
if (preg_match('/lab\/api\/category\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editCategory($matches[1]); exit; }
if (preg_match('/lab\/api\/category\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteCategory($matches[1]); exit; }
if ($request == 'lab/api/categories') { $lab = new LaboratoryController(); $lab->apiGetAllCategories(); exit; }

// ==================== LAB INSTRUMENT ROUTES ====================
if ($request == 'lab/instruments') { $lab = new LaboratoryController(); $lab->manageInstruments(); exit; }
if ($request == 'lab/instrument/add') { $lab = new LaboratoryController(); $lab->addInstrument(); exit; }
if (preg_match('/lab\/instrument\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editInstrument($matches[1]); exit; }
if (preg_match('/lab\/instrument\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteInstrument($matches[1]); exit; }
if (preg_match('/lab\/api\/instrument\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetInstrument($matches[1]); exit; }
if ($request == 'lab/api/instrument') { $lab = new LaboratoryController(); $lab->addInstrument(); exit; }
if (preg_match('/lab\/api\/instrument\/edit\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->editInstrument($matches[1]); exit; }
if (preg_match('/lab\/api\/instrument\/delete\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->deleteInstrument($matches[1]); exit; }
if ($request == 'lab/api/instruments') { $lab = new LaboratoryController(); $lab->apiGetInstruments(); exit; }

// ==================== LAB TEST-INSTRUMENT ROUTES ====================
if (preg_match('/lab\/test-instruments\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->manageTestInstruments($matches[1]); exit; }
if (preg_match('/lab\/api\/test-instruments\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->manageTestInstruments($matches[1]); exit; }
if (preg_match('/lab\/api\/test-instruments\/get\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetTestInstruments($matches[1]); exit; }

// ==================== LAB ACCESSORY ROUTES ====================
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

// ==================== LAB TEST-ACCESSORY ROUTES ====================
if (preg_match('/lab\/test-accessories\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->manageTestAccessories($matches[1]); exit; }
if (preg_match('/lab\/api\/test-accessories\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->manageTestAccessories($matches[1]); exit; }
if (preg_match('/lab\/api\/test-accessories\/get\/(\d+)/', $request, $matches)) { $lab = new LaboratoryController(); $lab->apiGetTestAccessories($matches[1]); exit; }

// ==================== INVENTORY ROUTES ====================
if ($request == 'inventory/dashboard') { $inventory = new InventoryController(); $inventory->dashboard(); exit; }
if ($request == 'inventory/recent-activity') { $inventory = new InventoryController(); $inventory->recentActivity(); exit; }
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
if (preg_match('/inventory\/view-po\/(\d+)/', $request, $matches)) { $inventory = new InventoryController(); $inventory->viewPurchaseOrder($matches[1]); exit; }

// ==================== INVENTORY POST ROUTES ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($request == 'inventory/create-po') { $inventory = new InventoryController(); $inventory->createPurchaseOrder(); exit; }
    if ($request == 'inventory/update-po') { $inventory = new InventoryController(); $inventory->updatePurchaseOrder(); exit; }
    if ($request == 'inventory/approve-po') { $inventory = new InventoryController(); $inventory->approvePurchaseOrder(); exit; }
    if ($request == 'inventory/receive-po') { $inventory = new InventoryController(); $inventory->receivePurchaseOrder(); exit; }
    if ($request == 'inventory/cancel-po') { $inventory = new InventoryController(); $inventory->cancelPurchaseOrder(); exit; }
    if ($request == 'inventory/ignore-reorder-alert') { $inventory = new InventoryController(); $inventory->ignoreReorderAlert(); exit; }
}

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

// ==================== PRESCRIPTION ROUTES ====================
if ($request == 'prescriptions' || $request == 'prescriptions/index' || $request == 'prescription/list') { 
    $prescription = new PrescriptionController(); 
    $prescription->index(); 
    exit; 
}

if ($request == 'prescription/create' || $request == 'prescriptions/create') { 
    $prescription = new PrescriptionController(); 
    $prescription->create(); 
    exit; 
}

if ($request == 'prescription/save' || $request == 'prescriptions/save') { 
    $prescription = new PrescriptionController(); 
    $prescription->save(); 
    exit; 
}

if ($request == 'prescription/save-tab' || $request == 'prescriptions/save-tab') { 
    $prescription = new PrescriptionController(); 
    $prescription->saveTab(); 
    exit; 
}

if ($request == 'prescription/previous-list' || $request == 'prescriptions/previous-list') { 
    $prescription = new PrescriptionController(); 
    $prescription->getPreviousPrescriptions(); 
    exit; 
}

if ($request == 'prescription/load-tab' || $request == 'prescriptions/load-tab') { 
    $prescription = new PrescriptionController(); 
    $prescription->loadTabData(); 
    exit; 
}

if ($request == 'prescription/search-drugs' || $request == 'prescriptions/search-drugs') { 
    $prescription = new PrescriptionController(); 
    $prescription->searchDrugs(); 
    exit; 
}

if (preg_match('/^prescription\/show\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/show\/(\d+)$/', $request, $matches) ||
    preg_match('/^prescription\/view\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/view\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->show($matches[1]); 
    exit; 
}

if (preg_match('/^prescription\/edit\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/edit\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->edit($matches[1]); 
    exit; 
}

if ((preg_match('/^prescription\/update\/(\d+)$/', $request, $matches) || 
     preg_match('/^prescriptions\/update\/(\d+)$/', $request, $matches)) && 
     $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $prescription = new PrescriptionController(); 
    $prescription->update($matches[1]); 
    exit; 
}

if (preg_match('/^prescription\/print\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/print\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->printView($matches[1]); 
    exit; 
}

if (preg_match('/^prescription\/print-pad\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/print-pad\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->printPad($matches[1]); 
    exit; 
}

if (preg_match('/^prescription\/export-pdf\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/export-pdf\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->exportPdf($matches[1]); 
    exit; 
}

if (preg_match('/^prescription\/duplicate\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/duplicate\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->duplicate($matches[1]); 
    exit; 
}

if ((preg_match('/^prescription\/cancel\/(\d+)$/', $request, $matches) || 
     preg_match('/^prescriptions\/cancel\/(\d+)$/', $request, $matches)) && 
     $_SERVER['REQUEST_METHOD'] == 'POST') { 
    $prescription = new PrescriptionController(); 
    $prescription->cancel($matches[1]); 
    exit; 
}

if (preg_match('/^prescription\/patient\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/patient\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->patientPrescriptions($matches[1]); 
    exit; 
}

if (preg_match('/^prescription\/delete\/(\d+)$/', $request, $matches) || 
    preg_match('/^prescriptions\/delete\/(\d+)$/', $request, $matches)) { 
    $prescription = new PrescriptionController(); 
    $prescription->delete($matches[1]); 
    exit; 
}

// ==================== VACCINE ROUTES ====================
if ($request == 'vaccines' || $request == 'vaccines/index') {
    $vaccine = new VaccineController();
    $vaccine->index();
    exit;
}

if ($request == 'vaccines/create') {
    $vaccine = new VaccineController();
    $vaccine->create();
    exit;
}

if ($request == 'vaccines/save') {
    $vaccine = new VaccineController();
    $vaccine->save();
    exit;
}

if (preg_match('/vaccines\/edit\/(\d+)/', $request, $matches)) {
    $vaccine = new VaccineController();
    $vaccine->edit($matches[1]);
    exit;
}

if (preg_match('/vaccines\/update\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $vaccine = new VaccineController();
    $vaccine->update($matches[1]);
    exit;
}

if (preg_match('/vaccines\/delete\/(\d+)/', $request, $matches)) {
    $vaccine = new VaccineController();
    $vaccine->delete($matches[1]);
    exit;
}

if (preg_match('/vaccines\/print-card\/(\d+)/', $request, $matches)) {
    $vaccine = new VaccineController();
    $vaccine->printCard($matches[1]);
    exit;
}

if ($request == 'vaccines/search') {
    $vaccine = new VaccineController();
    $vaccine->search();
    exit;
}

// ==================== MANUAL LAB ROUTES ====================
if ($request == 'lab/manual' || $request == 'lab/manual/index') {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->index();
    exit;
}

if ($request == 'lab/manual/create') {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->create();
    exit;
}

if ($request == 'lab/manual/store') {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->store();
    exit;
}

if (preg_match('/lab\/manual\/edit\/(\d+)/', $request, $matches)) {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->edit($matches[1]);
    exit;
}

if (preg_match('/lab\/manual\/update\/(\d+)/', $request, $matches) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->update($matches[1]);
    exit;
}

if (preg_match('/lab\/manual\/delete\/(\d+)/', $request, $matches)) {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->delete($matches[1]);
    exit;
}

if (preg_match('/lab\/manual\/print-report\/(\d+)/', $request, $matches)) {
    require_once BASE_PATH . '/app/controllers/ManualLabController.php';
    $controller = new ManualLabController();
    $controller->printReport($matches[1]);
    exit;
}

// ==================== AUDIT ROUTES ====================
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

// ==================== BILLING ROUTES ====================
if ($request == 'bills' || $request == 'bills/bills') { $billing = new BillingController(); $billing->bills(); exit; }
if ($request == 'bills/create') { $billing = new BillingController(); $billing->createBill(); exit; }
if ($request == 'bills/store') { $billing = new BillingController(); $billing->storeBill(); exit; }

// Edit Bill Routes
if (preg_match('/bills\/edit\/(\d+)/', $request, $matches)) { 
    $billing = new BillingController(); 
    $billing->editBill($matches[1]); 
    exit; 
}

if ($request == 'bills/update' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
    $billing = new BillingController(); 
    $billing->updateBill(); 
    exit; 
}

if ($request == 'bills/delete' && $_SERVER['REQUEST_METHOD'] === 'POST') { 
    $billing = new BillingController(); 
    $billing->deleteBill(); 
    exit; 
}

// API - SYNC APPOINTMENT PAYMENT
if ($request == 'api/sync-appointment-payment') { 
    $appointment = new AppointmentController(); 
    $appointment->syncAppointmentPayment(); 
    exit; 
}

if ($request == 'bills/update') { 
    $billing = new BillingController(); 
    $billing->updateBill(); 
    exit; 
}
if ($request == 'bills/delete') { 
    $billing = new BillingController(); 
    $billing->deleteBill(); 
    exit; 
}

// View & Print Routes
if (preg_match('/bills\/view\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->viewInvoice($matches[1]); exit; }
if (preg_match('/bills\/print\/(\d+)/', $request, $matches)) { $billing = new BillingController(); $billing->printInvoice($matches[1]); exit; }

// Payment Routes
if ($request == 'bills/process-payment') { $billing = new BillingController(); $billing->processPayment(); exit; }
if ($request == 'bills/get-payment-history') { $billing = new BillingController(); $billing->getPaymentHistory(); exit; }

// Export & Reports
if ($request == 'bills/export') { $billing = new BillingController(); $billing->exportBills(); exit; }
if ($request == 'bills/payments') { $billing = new BillingController(); $billing->payments(); exit; }
if ($request == 'account/reports') { $billing = new BillingController(); $billing->financialReports(); exit; }

// FIX BILL BALANCES
if ($request == 'bills/fix-balances') {
    $billing = new BillingController();
    $billing->fixBillBalances();
    exit;
}

if ($request == 'bills/update') { 
    $billing = new BillingController(); 
    $billing->updateBill(); 
    exit; 
}
if ($request == 'bills/delete') { 
    $billing = new BillingController(); 
    $billing->deleteBill(); 
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