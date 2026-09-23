<?php
// app/controllers/AppointmentController.php
// COMPLETE FIXED VERSION - WITH ACTIVE DOCTOR FILTERING

require_once __DIR__ . '/../models/Patient.php';
require_once __DIR__ . '/../models/Doctor.php';
require_once __DIR__ . '/../models/Appointment.php';

class AppointmentController extends Controller {
    
    // ================================================================
    // HELPER: Get Active Doctors with proper user status check
    // ================================================================
    
    /**
     * Get all active doctors (both doctors.status = 'active' AND users.status = 'active')
     * This ensures only fully active doctors are returned
     */
    private function getActiveDoctors() {
        $db = Database::getInstance()->getConnection();
        
        $query = "SELECT d.id, d.specialization, d.consultation_fee, d.status as doctor_status,
                         u.id as user_id, u.first_name, u.last_name, u.title, u.status as user_status,
                         d.department_id, d.qualification, d.experience_years, d.bmdc_number
                  FROM doctors d 
                  JOIN users u ON d.user_id = u.id 
                  WHERE d.status = 'active' 
                    AND u.status = 'active' 
                    AND u.role_id = 3
                  ORDER BY u.first_name ASC";
        
        $result = $db->query($query);
        $doctorList = [];
        while($row = $result->fetch_assoc()) {
            $doctorList[] = $row;
        }
        return $doctorList;
    }
    
    /**
     * Check if a doctor is active (both doctor and user status)
     */
    private function isDoctorActive($doctorId) {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT d.id 
                  FROM doctors d 
                  JOIN users u ON d.user_id = u.id 
                  WHERE d.id = " . (int)$doctorId . " 
                    AND d.status = 'active' 
                    AND u.status = 'active' 
                    AND u.role_id = 3";
        $result = $db->query($query);
        return $result && $result->num_rows > 0;
    }
    
    /**
     * Get active doctor by ID with full details
     */
    private function getActiveDoctorById($doctorId) {
        $db = Database::getInstance()->getConnection();
        $query = "SELECT d.*, u.id as user_id, u.first_name, u.last_name, u.title, u.email, u.phone, u.status as user_status
                  FROM doctors d 
                  JOIN users u ON d.user_id = u.id 
                  WHERE d.id = " . (int)$doctorId . " 
                    AND d.status = 'active' 
                    AND u.status = 'active' 
                    AND u.role_id = 3";
        $result = $db->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    // ================================================================
    // BOOK APPOINTMENT PAGE
    // ================================================================
    public function book() {
        $this->requireLogin();
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        $patient = null;
        if($patientId > 0) {
            $patient = Patient::find($patientId);
        }
        
        // Use the getActiveDoctors() method to get only fully active doctors
        $doctorList = $this->getActiveDoctors();
        
        $db = Database::getInstance()->getConnection();
        $additionalServices = $db->query("SELECT * FROM additional_services WHERE status = 'active' ORDER BY service_name");
        $additionalServiceList = [];
        while($row = $additionalServices->fetch_assoc()) {
            $additionalServiceList[] = $row;
        }
        
        $this->view('appointments/book', [
            'patient' => $patient,
            'doctors' => $doctorList,
            'additionalServices' => $additionalServiceList
        ], 'Book Appointment');
    }
    
    // ================================================================
    // APPOINTMENTS LIST
    // ================================================================
    public function index() {
        $this->requireLogin();
        $db = Database::getInstance()->getConnection();
        
        // Use getActiveDoctors() for consistent filtering
        $doctorList = $this->getActiveDoctors();
        
        $appointments = $db->query("
            SELECT a.*, 
                   p.first_name as patient_first, p.last_name as patient_last, p.patient_code,
                   u.first_name as doctor_first, u.last_name as doctor_last, d.specialization,
                   ds.service_name, ds.service_price, ds.service_type,
                   b.id as bill_id,
                   b.total_amount as bill_total,
                   b.paid_amount as bill_paid,
                   b.discount_amount as bill_discount,
                   b.balance_amount as bill_balance,
                   b.payment_status as bill_payment_status,
                   COALESCE(a.payment_received, 0) as payment_received,
                   COALESCE(a.payment_status, 'pending') as payment_status,
                   COALESCE(a.total_amount, 0) as total_amount
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            LEFT JOIN doctor_services ds ON a.service_id = ds.id
            LEFT JOIN bills b ON b.reference_type = 'appointment' AND b.reference_id = a.id
            WHERE a.appointment_date = CURDATE()
            ORDER BY a.session_type, a.created_at ASC
        ");
        
        $appointmentList = [];
        while($row = $appointments->fetch_assoc()) {
            if(empty($row['service_name'])) {
                $row['service_name'] = 'Consultation';
                $row['service_price'] = 0;
                $row['service_type'] = 'consultation';
            }
            
            if ($row['bill_id']) {
                $row['total_amount'] = (float)$row['bill_total'];
                $row['paid_amount'] = (float)$row['bill_paid'];
                $row['discount_amount'] = (float)$row['bill_discount'];
                $row['balance_amount'] = (float)$row['bill_balance'];
                $row['payment_status'] = $row['bill_payment_status'];
            } else {
                $row['paid_amount'] = (float)($row['payment_received'] ?? 0);
                $row['discount_amount'] = (float)($row['discount'] ?? 0);
                $row['balance_amount'] = (float)$row['total_amount'] - $row['paid_amount'] - $row['discount_amount'];
                if ($row['balance_amount'] < 0) $row['balance_amount'] = 0;
                
                if ($row['balance_amount'] == 0) {
                    $row['payment_status'] = 'paid';
                } elseif ($row['paid_amount'] > 0 && $row['balance_amount'] > 0) {
                    $row['payment_status'] = 'partial';
                } else {
                    $row['payment_status'] = 'pending';
                }
            }
            
            $appointmentList[] = $row;
        }
        
        $this->view('reception/appointments', [
            'doctors' => $doctorList,
            'appointments' => $appointmentList
        ], 'Appointments');
    }
    
    public function today() {
        $this->requireLogin();
        
        $db = Database::getInstance()->getConnection();
        
        $appointments = $db->query("
            SELECT a.*, 
                   CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                   p.patient_code,
                   CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                   d.specialization,
                   ds.service_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN users u ON d.user_id = u.id
            LEFT JOIN doctor_services ds ON a.service_id = ds.id
            WHERE a.appointment_date = CURDATE()
            ORDER BY a.session_type, a.start_time ASC
        ");
        
        $appointmentList = [];
        while ($row = $appointments->fetch_assoc()) {
            $appointmentList[] = $row;
        }
        
        $this->view('appointments/today', [
            'appointments' => $appointmentList,
            'todayDate' => date('l, d M Y')
        ], 'Today\'s Appointments');
    }
    
    public function receptionIndex() {
        return $this->index();
    }
    
    // ================================================================
    // DAILY PATIENT LIST
    // ================================================================
    public function dailyList() {
        $this->requireLogin();
        $this->checkPermission('view_appointments');
        
        $db = Database::getInstance()->getConnection();
        
        $userRole = $_SESSION['role_slug'] ?? 'guest';
        $userId = $_SESSION['user_id'] ?? 0;
        
        $doctorFilter = "";
        if ($userRole == 'doctor') {
            $doctorId = $this->getDoctorId($userId);
            if ($doctorId > 0 && $this->isDoctorActive($doctorId)) {
                $doctorFilter = " AND a.doctor_id = $doctorId";
            }
        }
        
        $query = "SELECT a.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
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
                         b.id as bill_id,
                         b.total_amount as bill_total,
                         b.paid_amount as bill_paid,
                         b.discount_amount as bill_discount,
                         b.balance_amount as bill_balance,
                         b.payment_status as bill_payment_status,
                         COALESCE(a.total_amount, 0) as total_amount,
                         COALESCE(a.payment_received, 0) as payment_received,
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
                  LEFT JOIN bills b ON b.reference_type = 'appointment' AND b.reference_id = a.id
                  WHERE a.appointment_date = CURDATE()
                  AND a.status NOT IN ('canceled')
                  $doctorFilter
                  ORDER BY a.session_type, a.start_time ASC";
        
        $result = $db->query($query);
        $appointments = [];
        while($row = $result->fetch_assoc()) {
            if(empty($row['service_name'])) {
                $row['service_name'] = 'Consultation';
            }
            $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . 
                                    str_pad($row['serial_number'] ?? 0, 3, '0', STR_PAD_LEFT);
            
            if ($row['bill_id']) {
                $row['total_amount'] = (float)$row['bill_total'];
                $row['paid_amount'] = (float)$row['bill_paid'];
                $row['discount_amount'] = (float)$row['bill_discount'];
                $row['balance_amount'] = (float)$row['bill_balance'];
                $row['payment_status'] = $row['bill_payment_status'];
            } else {
                $row['paid_amount'] = (float)($row['payment_received'] ?? 0);
                $row['discount_amount'] = (float)($row['discount'] ?? 0);
                $row['balance_amount'] = (float)$row['total_amount'] - $row['paid_amount'] - $row['discount_amount'];
                if ($row['balance_amount'] < 0) $row['balance_amount'] = 0;
                
                if ($row['balance_amount'] == 0) {
                    $row['payment_status'] = 'paid';
                } elseif ($row['paid_amount'] > 0 && $row['balance_amount'] > 0) {
                    $row['payment_status'] = 'partial';
                } else {
                    $row['payment_status'] = 'pending';
                }
            }
            
            $appointments[] = $row;
        }
        
        $totalPatients = count($appointments);
        $morningCount = 0;
        $eveningCount = 0;
        $completedCount = 0;
        $scheduledCount = 0;
        
        foreach($appointments as $app) {
            if($app['session_type'] == 'morning') $morningCount++;
            else $eveningCount++;
            if($app['status'] == 'completed') $completedCount++;
            elseif($app['status'] == 'scheduled' || $app['status'] == 'confirmed') $scheduledCount++;
        }
        
        $this->view('reception/daily-list', [
            'appointments' => $appointments,
            'totalPatients' => $totalPatients,
            'morningCount' => $morningCount,
            'eveningCount' => $eveningCount,
            'completedCount' => $completedCount,
            'scheduledCount' => $scheduledCount,
            'todayDate' => date('d M Y')
        ], 'Daily Patient List');
    }
    
    public function doctorList() {
        $this->requireLogin();
        // Use getActiveDoctors() for consistent filtering
        $doctorList = $this->getActiveDoctors();
        $this->view('reception/doctor-list', ['doctors' => $doctorList], 'Doctor Wise Patient List');
    }

    // ================================================================
    // API: GET DAILY PATIENTS
    // ================================================================
    public function apiDailyPatients() {
        header('Content-Type: application/json');
        
        try {
            $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
            $shift = isset($_GET['shift']) ? $_GET['shift'] : '';
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
            
            $db = Database::getInstance()->getConnection();
            
            $where = "a.appointment_date = '$date' AND a.status NOT IN ('canceled')";
            if(!empty($shift)) {
                $where .= " AND a.session_type = '$shift'";
            }
            if(!empty($status)) {
                $where .= " AND a.status = '$status'";
            }
            if($doctorId > 0 && $this->isDoctorActive($doctorId)) {
                $where .= " AND a.doctor_id = $doctorId";
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
                      WHERE $where
                      ORDER BY a.session_type, a.start_time ASC";
            
            $result = $db->query($query);
            $appointments = [];
            
            if($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . 
                                            str_pad($row['serial_number'] ?? 0, 3, '0', STR_PAD_LEFT);
                    if(empty($row['service_name'])) {
                        $row['service_name'] = 'Consultation';
                    }
                    $appointments[] = $row;
                }
            }
            
            echo json_encode($appointments);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // API: PRINT SERIAL SLIP
    // ================================================================
    public function apiPrintSerial() {
        $appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
        
        if($appointmentId == 0) {
            echo "Invalid appointment ID";
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        
        $query = "SELECT a.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code,
                         CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                         d.specialization,
                         ds.service_name,
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
        
        $result = $db->query($query);
        $appointment = $result->fetch_assoc();
        
        if(!$appointment) {
            echo "Appointment not found";
            exit;
        }
        
        $serial = ($appointment['session_type'] == 'morning' ? 'M' : 'E') . 
                  str_pad($appointment['serial_number'] ?? 0, 3, '0', STR_PAD_LEFT);
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Serial Slip - <?php echo $serial; ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                .slip { max-width: 300px; margin: 0 auto; border: 2px solid #10b981; padding: 20px; border-radius: 10px; text-align: center; }
                .serial { font-size: 48px; font-weight: bold; color: #10b981; margin: 10px 0; }
                .patient { font-size: 18px; font-weight: bold; }
                .info { font-size: 12px; color: #666; margin: 5px 0; }
                .divider { border-top: 1px dashed #ccc; margin: 15px 0; }
                .footer { font-size: 10px; color: #999; margin-top: 15px; }
                @media print { body { padding: 0; } .no-print { display: none; } }
                button { margin-top: 15px; padding: 8px 20px; background: #10b981; color: white; border: none; border-radius: 5px; cursor: pointer; }
            </style>
        </head>
        <body>
            <div class="slip">
                <div style="font-size: 14px; font-weight: bold;">UNIDIA HOSPITAL</div>
                <div style="font-size: 10px; color: #666;">123, Hospital Road, Dhaka</div>
                <div class="divider"></div>
                <div style="font-size: 12px; color: #666;">SERIAL NUMBER</div>
                <div class="serial"><?php echo $serial; ?></div>
                <div class="divider"></div>
                <div class="patient"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                <div class="info">Patient ID: <?php echo htmlspecialchars($appointment['patient_code']); ?></div>
                <div class="info">Doctor: Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                <div class="info">Service: <?php echo htmlspecialchars($appointment['service_name'] ?? 'Consultation'); ?></div>
                <div class="info">Date: <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></div>
                <div class="info">Time: <?php echo date('h:i A', strtotime($appointment['start_time'])); ?></div>
                <div class="divider"></div>
                <div class="footer">This is a computer generated slip</div>
                <div class="no-print">
                    <button onclick="window.print()">Print</button>
                    <button onclick="window.close()" style="background: #6c757d;">Close</button>
                </div>
            </div>
            <script>
                setTimeout(function() { window.print(); }, 500);
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    // ================================================================
    // API: GET APPOINTMENT DETAILS
    // ================================================================
    public function getAppointmentDetails() {
        header('Content-Type: application/json');
        $appointmentId = (int)$_GET['appointment_id'];
        
        $db = Database::getInstance()->getConnection();
        
        $query = "SELECT a.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code,
                         p.phone,
                         CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                         d.specialization,
                         ds.service_name,
                         ds.service_price
                  FROM appointments a
                  JOIN patients p ON a.patient_id = p.id
                  JOIN doctors d ON a.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  LEFT JOIN doctor_services ds ON a.service_id = ds.id
                  WHERE a.id = $appointmentId";
        
        $result = $db->query($query);
        $appointment = $result->fetch_assoc();
        
        if($appointment) {
            echo json_encode(['success' => true, 'data' => $appointment]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        }
        exit;
    }

    // ================================================================
    // API: UPDATE APPOINTMENT STATUS
    // ================================================================
    public function updateAppointmentStatus() {
        header('Content-Type: application/json');
        
        $appointmentId = (int)$_POST['appointment_id'];
        $status = $_POST['status'];
        
        $validStatuses = ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed', 'canceled', 'no_show'];
        if(!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        
        $appointment = $db->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
        if(!$appointment) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            exit;
        }
        
        $result = $db->query("UPDATE appointments SET status = '$status', updated_at = NOW() WHERE id = $appointmentId");
        
        if($status == 'completed') {
            $db->query("UPDATE queue SET status = 'completed' WHERE appointment_id = $appointmentId");
        }
        if($status == 'in_progress') {
            $db->query("UPDATE queue SET status = 'in_progress' WHERE appointment_id = $appointmentId");
        }
        if($status == 'checked_in') {
            $queueCheck = $db->query("SELECT id FROM queue WHERE appointment_id = $appointmentId");
            if($queueCheck->num_rows == 0) {
                $serialNumber = ($appointment['session_type'] == 'morning' ? 'M' : 'E') . 
                                str_pad($appointment['id'], 3, '0', STR_PAD_LEFT);
                $db->query("INSERT INTO queue (appointment_id, serial_number, shift, status) 
                                 VALUES ($appointmentId, '$serialNumber', '{$appointment['session_type']}', 'waiting')");
            } else {
                $db->query("UPDATE queue SET status = 'waiting' WHERE appointment_id = $appointmentId");
            }
        }
        
        if($result) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $db->error]);
        }
        exit;
    }

    // ================================================================
    // API: CANCEL APPOINTMENT
    // ================================================================
    public function cancelAppointment() {
        header('Content-Type: application/json');
        
        $appointmentId = (int)$_POST['appointment_id'];
        $cancelReason = isset($_POST['cancel_reason']) ? $_POST['cancel_reason'] : 'Cancelled by staff';
        
        $db = Database::getInstance()->getConnection();
        
        $appointment = $db->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
        if(!$appointment) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            exit;
        }
        
        if($appointment['status'] == 'canceled') {
            echo json_encode(['success' => false, 'message' => 'Appointment already canceled']);
            exit;
        }
        
        $db->begin_transaction();
        
        try {
            $db->query("UPDATE appointments SET 
                              status = 'canceled', 
                              cancellation_reason = '$cancelReason',
                              updated_at = NOW() 
                              WHERE id = $appointmentId");
            
            $db->query("UPDATE queue SET status = 'canceled' WHERE appointment_id = $appointmentId");
            
            $bill = $db->query("SELECT id FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
            if($bill->num_rows > 0) {
                $billData = $bill->fetch_assoc();
                $db->query("UPDATE bills SET payment_status = 'canceled' WHERE id = {$billData['id']}");
            }
            
            $db->commit();
            
            echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
            
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // API: RESCHEDULE APPOINTMENT
    // ================================================================
    public function rescheduleAppointment() {
        header('Content-Type: application/json');
        
        $appointmentId = (int)$_POST['appointment_id'];
        $newDate = $_POST['new_date'];
        $newShift = $_POST['new_shift'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        
        $db = Database::getInstance()->getConnection();
        
        $appointment = $db->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
        if(!$appointment) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            exit;
        }
        
        $startTime = $newShift == 'morning' ? '09:00:00' : '14:00:00';
        $endTime = $newShift == 'morning' ? '13:00:00' : '18:00:00';
        
        $serialQuery = $db->query("SELECT COUNT(*) as count FROM appointments 
                                         WHERE doctor_id = {$appointment['doctor_id']} 
                                         AND appointment_date = '$newDate' 
                                         AND session_type = '$newShift'
                                         AND status != 'canceled'");
        $serialCount = (int)$serialQuery->fetch_assoc()['count'];
        $serialNumber = ($newShift == 'morning' ? 'M' : 'E') . str_pad(($serialCount + 1), 3, '0', STR_PAD_LEFT);
        
        $result = $db->query("UPDATE appointments SET 
                                    appointment_date = '$newDate',
                                    session_type = '$newShift',
                                    start_time = '$startTime',
                                    end_time = '$endTime',
                                    updated_at = NOW()
                                    WHERE id = $appointmentId");
        
        if($result) {
            $db->query("UPDATE queue SET 
                              serial_number = '$serialNumber',
                              shift = '$newShift'
                              WHERE appointment_id = $appointmentId");
            
            echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $db->error]);
        }
        exit;
    }

    // ================================================================
    // RECEPTION CHECK-IN
    // ================================================================
    public function checkIn() {
        $this->requireLogin();
        $this->checkPermission('view_appointments');
        
        // Use getActiveDoctors() for consistent filtering
        $doctorList = $this->getActiveDoctors();
        
        $this->view('reception/check-in', [
            'doctors' => $doctorList
        ], 'Check-in');
    }

    // ================================================================
    // RECEPTION QUEUE
    // ================================================================
    public function queue() {
        $this->requireLogin();
        $this->checkPermission('manage_queue');
        
        // Use getActiveDoctors() for consistent filtering
        $doctorList = $this->getActiveDoctors();
        
        $this->view('reception/queue', [
            'doctors' => $doctorList
        ], 'Queue Management');
    }

    // ================================================================
    // API: GET DOCTOR SERVICES
    // ================================================================
    public function getDoctorServices() {
        header('Content-Type: application/json');
        
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
        
        if (!$doctorId) {
            echo json_encode(['success' => false, 'message' => 'Doctor ID required', 'services' => []]);
            exit;
        }
        
        // Verify doctor is active
        if (!$this->isDoctorActive($doctorId)) {
            echo json_encode(['success' => false, 'message' => 'Doctor is not active', 'services' => []]);
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        $query = "SELECT ds.*, d.id as doctor_id, CONCAT(u.title, ' ', u.first_name, ' ', u.last_name) as doctor_name
                  FROM doctor_services ds
                  INNER JOIN doctors d ON ds.doctor_id = d.id
                  INNER JOIN users u ON d.user_id = u.id
                  WHERE ds.status = 'active' AND ds.doctor_id = $doctorId
                  ORDER BY ds.service_name ASC";
        
        $result = $db->query($query);
        $services = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $services[] = [
                    'id' => (int)$row['id'],
                    'service_name' => $row['service_name'],
                    'service_price' => (float)$row['service_price'],
                    'service_type' => $row['service_type'] ?? 'consultation',
                    'doctor_id' => (int)$row['doctor_id'],
                    'doctor_name' => $row['doctor_name']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'services' => $services]);
        exit;
    }

    // ================================================================
    // API: GET DOCTOR AVAILABILITY
    // ================================================================
    public function getDoctorAvailability() {
        header('Content-Type: application/json');
        
        try {
            $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
            
            if ($doctorId <= 0) {
                echo json_encode([
                    'success' => true,
                    'available_days' => [],
                    'sessions_by_day' => [],
                    'has_schedule' => false,
                    'message' => 'Please select a valid doctor'
                ]);
                exit;
            }
            
            // Verify doctor is active
            if (!$this->isDoctorActive($doctorId)) {
                echo json_encode([
                    'success' => true,
                    'available_days' => [],
                    'sessions_by_day' => [],
                    'has_schedule' => false,
                    'message' => 'Doctor is not active'
                ]);
                exit;
            }
            
            $db = Database::getInstance()->getConnection();
            
            // Check if doctor exists
            $doctorQuery = $db->query("SELECT d.id, d.specialization, d.consultation_fee, d.status as doctor_status,
                                              u.status as user_status
                                       FROM doctors d 
                                       JOIN users u ON d.user_id = u.id 
                                       WHERE d.id = " . (int)$doctorId);
            
            if (!$doctorQuery || $doctorQuery->num_rows == 0) {
                echo json_encode([
                    'success' => true,
                    'available_days' => [],
                    'sessions_by_day' => [],
                    'has_schedule' => false,
                    'message' => 'Doctor not found'
                ]);
                exit;
            }
            
            $doctor = $doctorQuery->fetch_assoc();
            
            // Get schedule for this specific doctor
            $query = "SELECT day_of_week, session_type, start_time, end_time, slot_duration, max_patients, is_available
                      FROM doctor_schedule_sessions 
                      WHERE doctor_id = " . (int)$doctorId . " AND is_available = 1
                      ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
                               FIELD(session_type, 'morning', 'evening')";
            
            $result = $db->query($query);
            $sessionsByDay = [];
            $availableDays = [];
            $sessionCount = 0;
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $day = $row['day_of_week'];
                    if (!isset($sessionsByDay[$day])) {
                        $sessionsByDay[$day] = [];
                    }
                    $sessionsByDay[$day][] = [
                        'session_type' => $row['session_type'],
                        'start_time' => $row['start_time'],
                        'end_time' => $row['end_time'],
                        'slot_duration' => (int)($row['slot_duration'] ?? 15),
                        'max_patients' => (int)($row['max_patients'] ?? 20),
                        'is_available' => (int)$row['is_available']
                    ];
                    if (!in_array($day, $availableDays)) {
                        $availableDays[] = $day;
                    }
                    $sessionCount++;
                }
            }
            
            // FALLBACK: Check doctor_schedules table if no data in doctor_schedule_sessions
            if ($sessionCount == 0) {
                $fallbackQuery = "SELECT day_of_week, start_time, end_time, slot_duration, max_patients, is_available
                                  FROM doctor_schedules 
                                  WHERE doctor_id = " . (int)$doctorId . " AND is_available = 1
                                  ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
                
                $fallbackResult = $db->query($fallbackQuery);
                
                if ($fallbackResult && $fallbackResult->num_rows > 0) {
                    while ($row = $fallbackResult->fetch_assoc()) {
                        $day = $row['day_of_week'];
                        $startHour = (int)substr($row['start_time'], 0, 2);
                        $sessionType = ($startHour < 12) ? 'morning' : 'evening';
                        
                        if (!isset($sessionsByDay[$day])) {
                            $sessionsByDay[$day] = [];
                        }
                        $sessionsByDay[$day][] = [
                            'session_type' => $sessionType,
                            'start_time' => $row['start_time'],
                            'end_time' => $row['end_time'],
                            'slot_duration' => (int)($row['slot_duration'] ?? 15),
                            'max_patients' => (int)($row['max_patients'] ?? 20),
                            'is_available' => (int)$row['is_available']
                        ];
                        if (!in_array($day, $availableDays)) {
                            $availableDays[] = $day;
                        }
                        $sessionCount++;
                    }
                }
            }
            
            $response = [
                'success' => true,
                'doctor_id' => (int)$doctorId,
                'available_days' => $availableDays,
                'sessions_by_day' => $sessionsByDay,
                'has_schedule' => ($sessionCount > 0),
                'session_count' => $sessionCount,
                'message' => $sessionCount > 0 ? 'Schedule loaded successfully' : 'No schedule found for this doctor',
                'doctor_name' => $doctor['specialization'] ?? 'Doctor',
                'consultation_fee' => (float)($doctor['consultation_fee'] ?? 0)
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => true,
                'available_days' => [],
                'sessions_by_day' => [],
                'has_schedule' => false,
                'message' => 'No schedule found'
            ]);
        }
        exit;
    }

    // ================================================================
    // API: GET DIVISIONS
    // ================================================================
    public function getDivisions() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::getInstance()->getConnection();
            $query = "SELECT id, name, bn_name FROM divisions WHERE status = 1 ORDER BY name";
            $result = $db->query($query);
            $divisions = [];
            while($row = $result->fetch_assoc()) {
                $divisions[] = $row;
            }
            echo json_encode($divisions);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

    // ================================================================
    // API: GET AVAILABLE SLOTS
    // ================================================================
    public function getAvailableSlots() {
        header('Content-Type: application/json');
        
        try {
            $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
            $date = isset($_GET['date']) ? $_GET['date'] : '';
            $shift = isset($_GET['shift']) ? $_GET['shift'] : '';
            
            if (!$doctorId || !$date || !$shift) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Missing required parameters',
                    'available_slots' => 0,
                    'booked_count' => 0,
                    'next_serial' => 0,
                    'has_availability' => false
                ]);
                exit;
            }
            
            // Verify doctor is active
            if (!$this->isDoctorActive($doctorId)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Doctor is not active',
                    'available_slots' => 0,
                    'booked_count' => 0,
                    'next_serial' => 0,
                    'has_availability' => false
                ]);
                exit;
            }
            
            $db = Database::getInstance()->getConnection();
            $dayOfWeek = date('l', strtotime($date));
            
            $schedule = $db->query("SELECT start_time, end_time, max_patients, slot_duration
                                   FROM doctor_schedule_sessions 
                                   WHERE doctor_id = $doctorId 
                                   AND day_of_week = '$dayOfWeek' 
                                   AND session_type = '$shift' 
                                   AND is_available = 1");
            
            if(!$schedule || $schedule->num_rows == 0) {
                $fallbackSchedule = $db->query("SELECT start_time, end_time, slot_duration, max_patients, is_available
                                               FROM doctor_schedules 
                                               WHERE doctor_id = $doctorId 
                                               AND day_of_week = '$dayOfWeek' 
                                               AND is_available = 1");
                
                if($fallbackSchedule && $fallbackSchedule->num_rows > 0) {
                    $row = $fallbackSchedule->fetch_assoc();
                    $maxPatients = (int)($row['max_patients'] ?? 20);
                    $slotDuration = (int)($row['slot_duration'] ?? 15);
                    $startTime = $row['start_time'];
                    $endTime = $row['end_time'];
                } else {
                    $maxPatients = 20;
                    $slotDuration = 15;
                    $startTime = $shift == 'morning' ? '09:00:00' : '14:00:00';
                    $endTime = $shift == 'morning' ? '13:00:00' : '18:00:00';
                }
            } else {
                $row = $schedule->fetch_assoc();
                $maxPatients = (int)($row['max_patients'] ?? 20);
                $slotDuration = (int)($row['slot_duration'] ?? 15);
                $startTime = $row['start_time'];
                $endTime = $row['end_time'];
            }
            
            $booked = $db->query("SELECT COUNT(*) as booked_count FROM appointments 
                                 WHERE doctor_id = $doctorId 
                                 AND appointment_date = '$date' 
                                 AND session_type = '$shift'
                                 AND status != 'canceled'");
            $bookedCount = (int)$booked->fetch_assoc()['booked_count'];
            $availableSlots = max(0, $maxPatients - $bookedCount);
            $nextSerial = $bookedCount + 1;
            
            echo json_encode([
                'success' => true,
                'max_patients' => $maxPatients,
                'booked_count' => $bookedCount,
                'available_slots' => $availableSlots,
                'next_serial' => $nextSerial,
                'has_availability' => $availableSlots > 0,
                'slot_duration' => $slotDuration,
                'start_time' => $startTime,
                'end_time' => $endTime
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Error checking availability: ' . $e->getMessage(),
                'available_slots' => 0,
                'booked_count' => 0,
                'next_serial' => 0,
                'has_availability' => false
            ]);
        }
        exit;
    }

    // ================================================================
    // API: BOOK APPOINTMENT
    // ================================================================
    public function apiBookAppointment() {
        $this->requireLogin();
        header('Content-Type: application/json');
        
        $inputData = $_POST;
        if (empty($inputData)) {
            $json = file_get_contents('php://input');
            $inputData = json_decode($json, true) ?: [];
        }
        
        $patientId = isset($inputData['patient_id']) ? (int)$inputData['patient_id'] : 0;
        $doctorId = isset($inputData['doctor_id']) ? (int)$inputData['doctor_id'] : 0;
        $serviceId = isset($inputData['service_id']) ? (int)$inputData['service_id'] : 0;
        $additionalServiceId = isset($inputData['additional_service_id']) ? (int)$inputData['additional_service_id'] : 0;
        $appointmentDate = isset($inputData['appointment_date']) ? $inputData['appointment_date'] : '';
        $shift = isset($inputData['shift']) ? $inputData['shift'] : '';
        $appointmentType = isset($inputData['appointment_type']) ? $inputData['appointment_type'] : 'regular';
        $symptoms = isset($inputData['symptoms']) ? $inputData['symptoms'] : '';
        $specialNote = isset($inputData['special_note']) ? $inputData['special_note'] : '';
        $paymentMethod = isset($inputData['payment_method']) ? $inputData['payment_method'] : 'cash';
        $discountPercent = isset($inputData['discount_percent']) ? (float)$inputData['discount_percent'] : 0;
        $totalAmountFromForm = isset($inputData['total_amount']) ? (float)$inputData['total_amount'] : 0;
        $discountAmountFromForm = isset($inputData['discount_amount']) ? (float)$inputData['discount_amount'] : 0;
        $subtotalFromForm = isset($inputData['subtotal']) ? (float)$inputData['subtotal'] : 0;
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
        
        $errors = [];
        if($patientId == 0) $errors[] = "Patient is required";
        if($doctorId == 0) $errors[] = "Doctor is required";
        if(empty($appointmentDate)) $errors[] = "Appointment date is required";
        if(empty($shift)) $errors[] = "Session/Shift is required";
        if($serviceId == 0) $errors[] = "Service is required";
        
        if(!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            exit;
        }
        
        // Verify doctor is active
        if (!$this->isDoctorActive($doctorId)) {
            echo json_encode(['success' => false, 'message' => 'Selected doctor is not active']);
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        
        $doctorResult = $db->query("SELECT d.consultation_fee, d.id as doctor_id, 
                                           CONCAT(u.title, ' ', u.first_name, ' ', u.last_name) as doctor_name
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
        
        $serviceFee = $consultationFee;
        $serviceName = 'Consultation';
        $serviceType = 'consultation';
        $actualServiceId = null;
        
        if($serviceId > 0) {
            $serviceResult = $db->query("SELECT id, service_name, service_price, service_type 
                                        FROM doctor_services 
                                        WHERE id = $serviceId AND status = 'active'");
            if($serviceResult && $serviceResult->num_rows > 0) {
                $serviceRow = $serviceResult->fetch_assoc();
                $serviceFee = (float)$serviceRow['service_price'];
                $serviceName = $serviceRow['service_name'];
                $serviceType = $serviceRow['service_type'] ?? 'consultation';
                $actualServiceId = (int)$serviceRow['id'];
            }
        }
        
        $additionalFee = 0;
        $additionalName = '';
        if($additionalServiceId > 0) {
            $additionalResult = $db->query("SELECT service_name, service_price FROM additional_services WHERE id = $additionalServiceId AND status = 'active'");
            if($additionalResult && $additionalResult->num_rows > 0) {
                $additionalRow = $additionalResult->fetch_assoc();
                $additionalFee = (float)$additionalRow['service_price'];
                $additionalName = $additionalRow['service_name'];
            }
        }
        
        $subtotal = $serviceFee + $additionalFee;
        $discountAmount = ($subtotal * $discountPercent) / 100;
        $totalAmount = $subtotal - $discountAmount;
        
        if($subtotalFromForm > 0) $subtotal = $subtotalFromForm;
        if($discountAmountFromForm > 0) $discountAmount = $discountAmountFromForm;
        if($totalAmountFromForm > 0) $totalAmount = $totalAmountFromForm;
        
        // Generate serial number
        $serialQuery = $db->query("SELECT COUNT(*) as count FROM appointments 
                                   WHERE doctor_id = $doctorId 
                                   AND appointment_date = '$appointmentDate' 
                                   AND session_type = '$shift'
                                   AND status != 'canceled'");
        $serialCount = (int)$serialQuery->fetch_assoc()['count'];
        $serialNumber = ($shift == 'morning' ? 'M' : 'E') . str_pad(($serialCount + 1), 3, '0', STR_PAD_LEFT);
        
        $appointmentNumber = 'APT' . date('Ymd') . rand(1000, 9999);
        $startTime = $shift == 'morning' ? '09:00:00' : '14:00:00';
        $endTime = $shift == 'morning' ? '13:00:00' : '18:00:00';
        
        $db->begin_transaction();
        
        try {
            $serviceIdValue = $actualServiceId ? $actualServiceId : 'NULL';
            
            $query = "INSERT INTO appointments (
                        appointment_number, patient_id, doctor_id, appointment_date, 
                        start_time, end_time, session_type, appointment_type, 
                        symptoms, special_note, status, payment_status, 
                        payment_method, total_amount, discount, service_id, created_by
                      ) VALUES (
                        '$appointmentNumber', $patientId, $doctorId, '$appointmentDate', 
                        '$startTime', '$endTime', '$shift', '$appointmentType', 
                        '" . $db->real_escape_string($symptoms) . "', '" . $db->real_escape_string($specialNote) . "',
                        'scheduled', 'pending', '$paymentMethod', $totalAmount, $discountAmount, 
                        $serviceIdValue, $userId
                      )";
            
            if (!$db->query($query)) {
                throw new Exception("Appointment insert failed: " . $db->error);
            }
            $appointmentId = $db->insert_id;
            
            // Add to queue
            $db->query("INSERT INTO queue (appointment_id, serial_number, shift, status) 
                       VALUES ($appointmentId, '$serialNumber', '$shift', 'waiting')");
            
            // Create bill
            $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
            
            $billQuery = "INSERT INTO bills (
                            bill_number, patient_id, bill_type, bill_date, 
                            subtotal, discount_amount, discount_percentage, tax_amount, total_amount, 
                            paid_amount, balance_amount, payment_status, 
                            payment_method, created_by, reference_type, reference_id
                          ) VALUES (
                            '$billNumber', $patientId, 'consultation', CURDATE(), 
                            $subtotal, $discountAmount, $discountPercent, 0, $totalAmount, 
                            0, $totalAmount, 'pending', 
                            '$paymentMethod', $userId, 'appointment', $appointmentId
                          )";
            
            if (!$db->query($billQuery)) {
                throw new Exception("Bill insert failed: " . $db->error);
            }
            $billId = $db->insert_id;
            
            // Create bill items
            $itemType = $db->real_escape_string($serviceType);
            $description = $db->real_escape_string($serviceName . ' (' . $doctorName . ')');
            
            $billItemQuery = "INSERT INTO bill_items (
                                bill_id, item_type, description, quantity, 
                                unit_price, discount_percentage, discount_amount,
                                tax_percentage, tax_amount, total_amount
                              ) VALUES (
                                $billId, '$itemType', '$description', 1, 
                                $serviceFee, 0, 0, 0, 0, $serviceFee
                              )";
            
            $result = $db->query($billItemQuery);
            
            if ($result === false) {
                throw new Exception("Bill item insert failed: " . $db->error);
            }
            
            if ($additionalFee > 0 && !empty($additionalName)) {
                $additionalDesc = $db->real_escape_string($additionalName . ' (' . $doctorName . ')');
                $additionalQuery = "INSERT INTO bill_items (
                                        bill_id, item_type, description, quantity, 
                                        unit_price, discount_percentage, discount_amount,
                                        tax_percentage, tax_amount, total_amount
                                    ) VALUES (
                                        $billId, 'service', '$additionalDesc', 1, 
                                        $additionalFee, 0, 0, 0, 0, $additionalFee
                                    )";
                $db->query($additionalQuery);
            }
            
            // Create commission
            $commissionPercentage = 20;
            $commissionAmount = ($consultationFee * $commissionPercentage) / 100;
            $db->query("INSERT INTO doctor_commissions (
                            doctor_id, reference_type, reference_id, amount, 
                            commission_percentage, commission_amount, status
                          ) VALUES (
                            $doctorId, 'consultation', $appointmentId, $consultationFee, 
                            $commissionPercentage, $commissionAmount, 'pending'
                          )");
            
            $db->query("UPDATE bills SET balance_amount = total_amount - paid_amount WHERE id = $billId");
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment booked successfully! Serial: ' . $serialNumber,
                'appointment_number' => $appointmentNumber,
                'appointment_id' => $appointmentId,
                'serial_number' => $serialNumber,
                'total_amount' => number_format($totalAmount, 2),
                'bill_id' => $billId,
                'bill_number' => $billNumber,
                'service_name' => $serviceName,
                'service_type' => $serviceType,
                'service_price' => $serviceFee,
                'balance_amount' => number_format($totalAmount, 2)
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // ================================================================
    // API: QUEUE LIST
    // ================================================================
    public function apiQueueList() {
        header('Content-Type: application/json');
        
        try {
            $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
            $shift = isset($_GET['shift']) ? $_GET['shift'] : '';
            
            $db = Database::getInstance()->getConnection();
            
            $where = "a.appointment_date = CURDATE() AND a.status NOT IN ('canceled', 'completed')";
            if($doctorId > 0 && $this->isDoctorActive($doctorId)) {
                $where .= " AND a.doctor_id = $doctorId";
            }
            if(!empty($shift)) {
                $where .= " AND a.session_type = '$shift'";
            }
            
            $query = "SELECT q.*, 
                             a.id as appointment_id, 
                             a.session_type, 
                             a.appointment_date,
                             CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                             p.first_name, 
                             p.last_name,
                             p.patient_code,
                             p.phone,
                             CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                             u.first_name as doctor_fname, 
                             u.last_name as doctor_lname,
                             d.specialization
                      FROM queue q
                      JOIN appointments a ON q.appointment_id = a.id
                      JOIN patients p ON a.patient_id = p.id
                      JOIN doctors d ON a.doctor_id = d.id
                      JOIN users u ON d.user_id = u.id
                      WHERE $where
                      ORDER BY FIELD(q.status, 'waiting', 'in_progress'), q.shift, q.serial_number ASC";
            
            $result = $db->query($query);
            
            if(!$result) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Database error',
                    'queue' => [],
                    'currentServing' => null,
                    'stats' => ['waiting' => 0, 'in_progress' => 0, 'completed' => 0]
                ]);
                exit;
            }
            
            $queue = [];
            $currentServing = null;
            $stats = ['waiting' => 0, 'in_progress' => 0, 'completed' => 0];
            
            if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    if(empty($row['serial_number'])) {
                        $row['serial_number'] = ($row['session_type'] == 'morning' ? 'M' : 'E') . 
                                                str_pad($row['appointment_id'], 3, '0', STR_PAD_LEFT);
                    }
                    $queue[] = $row;
                    
                    if($row['status'] == 'waiting') $stats['waiting']++;
                    elseif($row['status'] == 'in_progress') {
                        $stats['in_progress']++;
                        if(!$currentServing) {
                            $currentServing = $row;
                        }
                    }
                }
            }
            
            $completedQuery = "SELECT COUNT(*) as cnt FROM appointments 
                               WHERE appointment_date = CURDATE() AND status = 'completed'";
            $completedResult = $db->query($completedQuery);
            $stats['completed'] = $completedResult ? (int)$completedResult->fetch_assoc()['cnt'] : 0;
            
            echo json_encode([
                'success' => true,
                'queue' => $queue,
                'currentServing' => $currentServing,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage(),
                'queue' => [],
                'currentServing' => null,
                'stats' => ['waiting' => 0, 'in_progress' => 0, 'completed' => 0]
            ]);
        }
        exit;
    }

    // ================================================================
    // CALL PATIENT
    // ================================================================
    public function callPatient() {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        $queueId = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
        
        if (!$queueId) {
            echo json_encode(['success' => false, 'message' => 'Queue ID required']);
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        
        $queue = $db->query("SELECT * FROM queue WHERE id = $queueId")->fetch_assoc();
        if (!$queue) {
            echo json_encode(['success' => false, 'message' => 'Queue item not found']);
            exit;
        }
        
        // Verify the doctor for this appointment is active
        $appointmentCheck = $db->query("SELECT a.doctor_id FROM appointments a WHERE a.id = {$queue['appointment_id']}");
        if ($appointmentCheck && $appointmentCheck->num_rows > 0) {
            $appt = $appointmentCheck->fetch_assoc();
            if (!$this->isDoctorActive($appt['doctor_id'])) {
                echo json_encode(['success' => false, 'message' => 'Doctor is not active']);
                exit;
            }
        }
        
        $db->query("UPDATE queue SET status = 'in_progress' WHERE id = $queueId");
        $db->query("UPDATE appointments SET status = 'in_progress' WHERE id = {$queue['appointment_id']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Patient called successfully',
            'queue_number' => $queue['serial_number']
        ]);
        exit;
    }

    // ================================================================
    // RECEPTION SEARCH PATIENT
    // ================================================================
    public function searchPatient() {
        header('Content-Type: application/json');
        
        try {
            $term = isset($_GET['term']) ? trim($_GET['term']) : '';
            
            if(empty($term) || strlen($term) < 2) {
                echo json_encode([]);
                exit;
            }
            
            $db = Database::getInstance()->getConnection();
            $term = $db->real_escape_string($term);
            
            $query = "SELECT id, patient_code, first_name, last_name, phone, email 
                      FROM patients 
                      WHERE status = 'active' 
                      AND (first_name LIKE '%$term%' 
                           OR last_name LIKE '%$term%' 
                           OR phone LIKE '%$term%' 
                           OR patient_code LIKE '%$term%'
                           OR CONCAT(first_name, ' ', last_name) LIKE '%$term%')
                      ORDER BY first_name ASC
                      LIMIT 20";
            
            $result = $db->query($query);
            $patients = [];
            while($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
            
            echo json_encode($patients);
            
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

    // ================================================================
    // DO CHECK-IN (POST)
    // ================================================================
    public function doCheckIn() {
        header('Content-Type: application/json');
        
        $appointmentId = (int)$_POST['appointment_id'];
        
        $db = Database::getInstance()->getConnection();
        
        $appointment = $db->query("SELECT * FROM appointments WHERE id = $appointmentId")->fetch_assoc();
        if(!$appointment) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            exit;
        }
        
        // Verify doctor is active
        if (!$this->isDoctorActive($appointment['doctor_id'])) {
            echo json_encode(['success' => false, 'message' => 'Doctor is not active']);
            exit;
        }
        
        $db->query("UPDATE appointments SET status = 'checked_in', updated_at = NOW() WHERE id = $appointmentId");
        
        $serialNumber = ($appointment['session_type'] == 'morning' ? 'M' : 'E') . 
                        str_pad($appointment['id'], 3, '0', STR_PAD_LEFT);
        
        $queueCheck = $db->query("SELECT id FROM queue WHERE appointment_id = $appointmentId");
        if($queueCheck->num_rows == 0) {
            $db->query("INSERT INTO queue (appointment_id, serial_number, shift, status) 
                             VALUES ($appointmentId, '$serialNumber', '{$appointment['session_type']}', 'waiting')");
        } else {
            $db->query("UPDATE queue SET status = 'waiting' WHERE appointment_id = $appointmentId");
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Patient checked in successfully',
            'queue_number' => $serialNumber
        ]);
        exit;
    }

    // ================================================================
    // GET PATIENTS FOR SEARCH
    // ================================================================
    public function getPatients() {
        header('Content-Type: application/json');
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        $db = Database::getInstance()->getConnection();
        
        $query = "SELECT id, patient_code, first_name, last_name, phone, email 
                  FROM patients 
                  WHERE status = 'active'";
        
        if(!empty($search)) {
            $search = $db->real_escape_string($search);
            $query .= " AND (first_name LIKE '%$search%' 
                            OR last_name LIKE '%$search%' 
                            OR phone LIKE '%$search%' 
                            OR patient_code LIKE '%$search%'
                            OR CONCAT(first_name, ' ', last_name) LIKE '%$search%')";
        }
        
        $query .= " ORDER BY first_name ASC LIMIT 20";
        
        $result = $db->query($query);
        $patients = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
        }
        
        echo json_encode($patients);
        exit;
    }

    // ================================================================
    // QUICK PATIENT STORE (AJAX) — FIXED
    // - Returns full patient payload in the shape book.php expects
    // - Supports duplicate phone numbers
    // - Preserves first_name/last_name sent from client
    // ================================================================
    public function quickStore() {
        header('Content-Type: application/json');
        
        try {
            $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
            $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
            $address = isset($_POST['address']) ? trim($_POST['address']) : 'Bangladesh';
            $action = isset($_POST['action']) ? $_POST['action'] : 'save_register';
            
            $errors = [];
            if(empty($fullName) && empty($firstName)) $errors[] = 'Full name is required';
            if(empty($phone)) $errors[] = 'Phone number is required';
            if(empty($gender)) $errors[] = 'Gender is required';
            if(empty($dateOfBirth)) $errors[] = 'Date of birth is required';
            
            if(!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                exit;
            }
            
            // Reconstruct first/last name if missing
            if(empty($firstName) && !empty($fullName)) {
                $parts = explode(' ', $fullName);
                if(count($parts) > 1) {
                    $firstName = implode(' ', array_slice($parts, 0, -1));
                    $lastName = end($parts);
                } else {
                    $firstName = $fullName;
                    $lastName = '';
                }
            }
            if(empty($fullName)) {
                $fullName = trim($firstName . ' ' . $lastName);
            }
            
            $db = Database::getInstance()->getConnection();
            
            // Generate unique patient code with retry
            $patientCode = '';
            $attempts = 0;
            do {
                $patientCode = 'PAT' . date('Ymd') . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                $checkCode = $db->query("SELECT id FROM patients WHERE patient_code = '{$db->real_escape_string($patientCode)}' LIMIT 1");
                $codeExists = ($checkCode && $checkCode->num_rows > 0);
                $attempts++;
            } while ($codeExists && $attempts < 10);
            
            $firstNameEsc = $db->real_escape_string($firstName);
            $lastNameEsc  = $db->real_escape_string($lastName);
            $fullNameEsc  = $db->real_escape_string($fullName);
            $phoneEsc     = $db->real_escape_string($phone);
            $genderEsc    = $db->real_escape_string($gender);
            $addressEsc   = $db->real_escape_string($address);
            $dobEsc       = $db->real_escape_string($dateOfBirth);
            $codeEsc      = $db->real_escape_string($patientCode);
            
            // ================================================================
            // PHONE: Multiple patients CAN share the same phone number.
            // NO uniqueness check on phone — intentional.
            // ================================================================
            $query = "INSERT INTO patients (
                        patient_code, first_name, last_name, full_name, phone, 
                        gender, date_of_birth, address, registration_date, status, created_at
                      ) VALUES (
                        '$codeEsc', '$firstNameEsc', '$lastNameEsc', '$fullNameEsc', '$phoneEsc',
                        '$genderEsc', '$dobEsc', '$addressEsc', CURDATE(), 'active', NOW()
                      )";
            
            if($db->query($query)) {
                $patientId = $db->insert_id;
                
                // Fetch the freshly-created row so we return consistent data
                $result = $db->query("SELECT id, patient_code, first_name, last_name, full_name, phone, email, gender, date_of_birth, address
                                      FROM patients WHERE id = $patientId");
                $patient = $result ? $result->fetch_assoc() : null;
                
                if (!$patient) {
                    $patient = [
                        'id' => $patientId,
                        'patient_code' => $patientCode,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'full_name' => $fullName,
                        'phone' => $phone,
                        'email' => null,
                        'gender' => $gender,
                        'date_of_birth' => $dateOfBirth,
                        'address' => $address
                    ];
                }
                
                echo json_encode([
                    'success'      => true,
                    'message'      => 'Patient registered successfully',
                    'patient_id'   => $patientId,
                    'patient_code' => $patient['patient_code'],
                    'patient_name' => $patient['full_name'],
                    // ★ Critical: return the FULL patient object so book.php
                    //   can immediately select it without any extra request
                    'patient'      => $patient
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to register patient: ' . $db->error]);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // SELECT PATIENT PAGE
    // ================================================================
    public function selectPatient() {
        $this->requireLogin();
        
        if (isset($_GET['patient_id']) && $_GET['patient_id'] > 0) {
            $patientId = (int)$_GET['patient_id'];
            $_SESSION['selected_patient_id'] = $patientId;
            $this->redirect('/appointments/book');
            return;
        }
        
        $db = Database::getInstance()->getConnection();
        
        $recentPatients = $db->query("SELECT id, patient_code, first_name, last_name, phone, email 
                                      FROM patients 
                                      WHERE status = 'active' 
                                      ORDER BY id DESC 
                                      LIMIT 10");
        $patients = [];
        while ($row = $recentPatients->fetch_assoc()) {
            $patients[] = $row;
        }
        
        $this->view('reception/select-patient', [
            'patients' => $patients
        ], 'Select Patient');
    }

    // ================================================================
    // VIEW APPOINTMENT DETAILS
    // ================================================================
    public function viewAppointment($id) {
        $this->requireLogin();
        
        $db = Database::getInstance()->getConnection();
        $id = (int)$id;
        
        $query = "SELECT a.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code,
                         p.phone as patient_phone,
                         p.email as patient_email,
                         p.gender as patient_gender,
                         p.date_of_birth as patient_dob,
                         p.address as patient_address,
                         CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                         u.title as doctor_title,
                         u.email as doctor_email,
                         u.phone as doctor_phone,
                         d.specialization,
                         d.qualification,
                         d.bmdc_number,
                         d.consultation_fee,
                         d.doctor_info_en,
                         d.doctor_info_bn,
                         ds.service_name,
                         ds.service_price as service_fee,
                         ds.service_type,
                         COALESCE(a.total_amount, 0) as total_amount,
                         COALESCE(a.payment_received, 0) as payment_received,
                         COALESCE(a.payment_status, 'pending') as payment_status,
                         COALESCE(a.discount, 0) as discount_amount,
                         COALESCE(a.tax, 0) as tax_amount,
                         (SELECT COUNT(*) FROM queue WHERE appointment_id = a.id) as has_queue
                  FROM appointments a
                  JOIN patients p ON a.patient_id = p.id
                  JOIN doctors d ON a.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  LEFT JOIN doctor_services ds ON a.service_id = ds.id
                  WHERE a.id = $id";
        
        $result = $db->query($query);
        
        if (!$result || $result->num_rows == 0) {
            $_SESSION['error'] = 'Appointment not found';
            $this->redirect('/reception/appointments');
            return;
        }
        
        $appointment = $result->fetch_assoc();
        
        $billQuery = "SELECT b.*, 
                             CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                             COUNT(bi.id) as item_count
                      FROM bills b
                      LEFT JOIN patients p ON b.patient_id = p.id
                      LEFT JOIN bill_items bi ON b.id = bi.bill_id
                      WHERE b.reference_type = 'appointment' 
                      AND b.reference_id = $id
                      GROUP BY b.id";
        $billResult = $db->query($billQuery);
        $bill = $billResult->fetch_assoc();
        
        $billItems = [];
        if ($bill) {
            $itemsQuery = "SELECT * FROM bill_items WHERE bill_id = {$bill['id']}";
            $itemsResult = $db->query($itemsQuery);
            while ($row = $itemsResult->fetch_assoc()) {
                $billItems[] = $row;
            }
            $bill['items'] = $billItems;
        }
        
        $paymentsQuery = "SELECT pt.*, 
                                 CONCAT(u.first_name, ' ', u.last_name) as received_by_name
                          FROM payment_transactions pt
                          LEFT JOIN users u ON pt.received_by = u.id
                          WHERE pt.appointment_id = $id
                          ORDER BY pt.payment_date DESC";
        $paymentsResult = $db->query($paymentsQuery);
        $payments = [];
        while ($row = $paymentsResult->fetch_assoc()) {
            $payments[] = $row;
        }
        
        $queueQuery = "SELECT * FROM queue WHERE appointment_id = $id";
        $queueResult = $db->query($queueQuery);
        $queue = $queueResult->fetch_assoc();
        
        $userRole = $_SESSION['role_slug'] ?? 'guest';
        $userId = $_SESSION['user_id'] ?? 0;
        $canEdit = in_array($userRole, ['super_admin', 'admin', 'receptionist']);
        
        if ($userRole == 'doctor') {
            $doctorId = $this->getDoctorId();
            if ($doctorId == $appointment['doctor_id']) {
                $canEdit = true;
            }
        }
        
        $appointment['can_edit'] = $canEdit;
        $appointment['can_edit_status'] = $canEdit && in_array($appointment['status'], ['scheduled', 'confirmed', 'checked_in', 'in_progress']);
        
        $doctorInfoEn = $appointment['doctor_info_en'] ?? '';
        $doctorInfoBn = $appointment['doctor_info_bn'] ?? '';
        
        $this->view('appointments/view', [
            'title' => 'Appointment #' . $appointment['appointment_number'],
            'appointment' => $appointment,
            'bill' => $bill,
            'payments' => $payments,
            'queue' => $queue,
            'userRole' => $userRole,
            'canEdit' => $canEdit,
            'doctorInfoEn' => $doctorInfoEn,
            'doctorInfoBn' => $doctorInfoBn
        ]);
    }
    
    // ================================================================
    // GET APPOINTMENT DETAILS (AJAX)
    // ================================================================
    public function getDetails($id) {
        header('Content-Type: application/json');
        $this->requireLogin();
        
        $db = Database::getInstance()->getConnection();
        $id = (int)$id;
        
        $query = "SELECT a.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code,
                         CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                         d.specialization,
                         ds.service_name,
                         ds.service_price
                  FROM appointments a
                  JOIN patients p ON a.patient_id = p.id
                  JOIN doctors d ON a.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  LEFT JOIN doctor_services ds ON a.service_id = ds.id
                  WHERE a.id = $id";
        
        $result = $db->query($query);
        $appointment = $result->fetch_assoc();
        
        if ($appointment) {
            echo json_encode(['success' => true, 'data' => $appointment]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
        }
        exit;
    }
    
    // ================================================================
    // UPDATE APPOINTMENT STATUS
    // ================================================================
    public function updateStatus() {
        header('Content-Type: application/json');
        $this->requireLogin();
        
        $appointmentId = (int)$_POST['appointment_id'];
        $status = $_POST['status'];
        
        $validStatuses = ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed', 'canceled', 'no_show'];
        if (!in_array($status, $validStatuses)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            exit;
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Verify doctor is active
        $apptCheck = $db->query("SELECT doctor_id FROM appointments WHERE id = $appointmentId");
        if ($apptCheck && $apptCheck->num_rows > 0) {
            $appt = $apptCheck->fetch_assoc();
            if (!$this->isDoctorActive($appt['doctor_id'])) {
                echo json_encode(['success' => false, 'message' => 'Doctor is not active']);
                exit;
            }
        }
        
        $result = $db->query("UPDATE appointments SET status = '$status', updated_at = NOW() WHERE id = $appointmentId");
        
        if ($status == 'completed') {
            $db->query("UPDATE queue SET status = 'completed' WHERE appointment_id = $appointmentId");
        }
        if ($status == 'in_progress') {
            $db->query("UPDATE queue SET status = 'in_progress' WHERE appointment_id = $appointmentId");
        }
        if ($status == 'checked_in') {
            $queueCheck = $db->query("SELECT id FROM queue WHERE appointment_id = $appointmentId");
            if ($queueCheck->num_rows == 0) {
                $serialNumber = 'M' . str_pad($appointmentId, 3, '0', STR_PAD_LEFT);
                $db->query("INSERT INTO queue (appointment_id, serial_number, shift, status) 
                                 VALUES ($appointmentId, '$serialNumber', 'morning', 'waiting')");
            } else {
                $db->query("UPDATE queue SET status = 'waiting' WHERE appointment_id = $appointmentId");
            }
        }
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $db->error]);
        }
        exit;
    }
    
    // ================================================================
    // CANCEL APPOINTMENT
    // ================================================================
    public function cancel() {
        header('Content-Type: application/json');
        $this->requireLogin();
        
        $appointmentId = (int)$_POST['appointment_id'];
        $cancelReason = isset($_POST['cancel_reason']) ? $_POST['cancel_reason'] : 'Cancelled by staff';
        
        $db = Database::getInstance()->getConnection();
        
        $result = $db->query("UPDATE appointments SET 
                                  status = 'canceled', 
                                  cancellation_reason = '$cancelReason',
                                  updated_at = NOW() 
                                  WHERE id = $appointmentId");
        
        if ($result) {
            $db->query("UPDATE queue SET status = 'canceled' WHERE appointment_id = $appointmentId");
            
            $bill = $db->query("SELECT id FROM bills WHERE reference_type = 'appointment' AND reference_id = $appointmentId");
            if ($bill->num_rows > 0) {
                $billData = $bill->fetch_assoc();
                $db->query("UPDATE bills SET payment_status = 'canceled' WHERE id = {$billData['id']}");
            }
            
            echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $db->error]);
        }
        exit;
    }
    
    // ================================================================
    // RESCHEDULE APPOINTMENT
    // ================================================================
    public function reschedule() {
        header('Content-Type: application/json');
        $this->requireLogin();
        
        $appointmentId = (int)$_POST['appointment_id'];
        $newDate = $_POST['new_date'];
        $newShift = $_POST['new_shift'];
        $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
        
        $db = Database::getInstance()->getConnection();
        
        // Verify doctor is active
        $apptCheck = $db->query("SELECT doctor_id FROM appointments WHERE id = $appointmentId");
        if ($apptCheck && $apptCheck->num_rows > 0) {
            $appt = $apptCheck->fetch_assoc();
            if (!$this->isDoctorActive($appt['doctor_id'])) {
                echo json_encode(['success' => false, 'message' => 'Doctor is not active']);
                exit;
            }
        }
        
        $startTime = $newShift == 'morning' ? '09:00:00' : '14:00:00';
        $endTime = $newShift == 'morning' ? '13:00:00' : '18:00:00';
        
        $result = $db->query("UPDATE appointments SET 
                                  appointment_date = '$newDate',
                                  session_type = '$newShift',
                                  start_time = '$startTime',
                                  end_time = '$endTime',
                                  updated_at = NOW()
                                  WHERE id = $appointmentId");
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $db->error]);
        }
        exit;
    }
    
    // ================================================================
    // GET DOCTOR ID HELPER
    // ================================================================
    private function getDoctorId($userId = null) {
        $db = Database::getInstance()->getConnection();
        
        if ($userId === null) {
            $userId = $_SESSION['user_id'] ?? 0;
        }
        
        if ($userId > 0) {
            $result = $db->query("SELECT d.id FROM doctors d 
                                  JOIN users u ON d.user_id = u.id 
                                  WHERE d.user_id = $userId 
                                    AND d.status = 'active' 
                                    AND u.status = 'active'");
            if ($result && $result->num_rows > 0) {
                return (int)$result->fetch_assoc()['id'];
            }
        }
        return 0;
    }

    // ================================================================
    // SYNC APPOINTMENT PAYMENT STATUS
    // ================================================================
    public function syncAppointmentPayment() {
        header('Content-Type: application/json');
        
        try {
            $appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
            
            if (!$appointmentId) {
                echo json_encode(['success' => false, 'message' => 'Appointment ID required']);
                exit;
            }
            
            $db = Database::getInstance()->getConnection();
            
            $query = "SELECT a.*, b.id as bill_id, b.payment_status as bill_payment_status, 
                             b.total_amount as bill_total, b.paid_amount as bill_paid,
                             b.balance_amount as bill_balance
                      FROM appointments a
                      LEFT JOIN bills b ON b.reference_type = 'appointment' AND b.reference_id = a.id
                      WHERE a.id = $appointmentId";
            
            $result = $db->query($query);
            if (!$result || $result->num_rows == 0) {
                echo json_encode(['success' => false, 'message' => 'Appointment not found']);
                exit;
            }
            
            $appointment = $result->fetch_assoc();
            
            if ($appointment['bill_id']) {
                $billPaid = (float)$appointment['bill_paid'];
                $billTotal = (float)$appointment['bill_total'];
                $billStatus = $appointment['bill_payment_status'];
                
                $appPaymentStatus = 'pending';
                if ($billPaid >= $billTotal) {
                    $appPaymentStatus = 'paid';
                } elseif ($billPaid > 0 && $billPaid < $billTotal) {
                    $appPaymentStatus = 'partial';
                }
                
                $updateQuery = "UPDATE appointments SET 
                                payment_status = '$appPaymentStatus',
                                payment_received = $billPaid,
                                updated_at = NOW()
                                WHERE id = $appointmentId";
                
                if ($db->query($updateQuery)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Appointment payment status synced',
                        'appointment_id' => $appointmentId,
                        'payment_status' => $appPaymentStatus,
                        'payment_received' => $billPaid
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update appointment: ' . $db->error]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'No bill found for this appointment',
                    'appointment_id' => $appointmentId
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>