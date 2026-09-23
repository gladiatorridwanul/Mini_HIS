<?php
// app/controllers/ReceptionController.php
class ReceptionController extends Controller {
    
    // ==================== RECEPTION DASHBOARD ====================
    public function dashboard() {
        $this->checkAuth();
        
        $today = date('Y-m-d');
        
        // Today's check-ins
        $todayCheckins = $this->db->query("SELECT q.*, p.first_name, p.last_name, u.first_name as doctor_fname, u.last_name as doctor_lname
                                          FROM queue q
                                          JOIN appointments a ON q.appointment_id = a.id
                                          JOIN patients p ON a.patient_id = p.id
                                          JOIN doctors d ON a.doctor_id = d.id
                                          JOIN users u ON d.user_id = u.id
                                          WHERE DATE(q.created_at) = '$today'
                                          ORDER BY q.serial_number ASC");
        $checkins = [];
        while($row = $todayCheckins->fetch_assoc()) {
            $checkins[] = $row;
        }
        
        // Waiting patients count
        $waitingResult = $this->db->query("SELECT COUNT(*) as count FROM queue WHERE status = 'waiting'");
        $waitingPatients = $waitingResult->fetch_assoc()['count'];
        
        // Available doctors
        $availableDoctors = $this->db->query("SELECT COUNT(*) as count FROM doctors WHERE status = 'active'")->fetch_assoc()['count'];
        
        // Total appointments today
        $totalApps = $this->db->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = '$today'")->fetch_assoc()['count'];
        
        // Completed today
        $completedToday = $this->db->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = '$today' AND status = 'completed'")->fetch_assoc()['count'];
        
        // In progress
        $inProgress = $this->db->query("SELECT COUNT(*) as count FROM appointments WHERE appointment_date = '$today' AND status = 'in_progress'")->fetch_assoc()['count'];
        
        // Get all doctors for filter
        $doctors = $this->db->query("SELECT d.id, d.specialization, u.first_name, u.last_name
                                    FROM doctors d 
                                    JOIN users u ON d.user_id = u.id 
                                    WHERE d.status = 'active'");
        $doctorList = [];
        while($row = $doctors->fetch_assoc()) {
            $doctorList[] = $row;
        }
        
        $content = $this->renderView('reception/dashboard', [
            'todayCheckins' => $checkins,
            'waitingPatients' => $waitingPatients,
            'availableDoctors' => $availableDoctors,
            'totalAppointments' => $totalApps,
            'completedToday' => $completedToday,
            'inProgress' => $inProgress,
            'doctors' => $doctorList
        ]);
        $this->renderLayout('Reception Dashboard', $content);
    }
    
    // ==================== SELECT PATIENT FOR NEW APPOINTMENT ====================
    public function selectPatient() {
        $this->checkAuth();
        
        $content = $this->renderView('reception/select-patient');
        $this->renderLayout('Select Patient', $content);
    }
    
    // ==================== CHECK-IN PAGE ====================
    public function checkIn() {
        $this->checkAuth();
        
        // Get all doctors for filter
        $doctors = $this->db->query("SELECT d.id, d.specialization, u.first_name, u.last_name
                                    FROM doctors d 
                                    JOIN users u ON d.user_id = u.id 
                                    WHERE d.status = 'active'");
        $doctorList = [];
        while($row = $doctors->fetch_assoc()) {
            $doctorList[] = $row;
        }
        
        $content = $this->renderView('reception/check-in', ['doctors' => $doctorList]);
        $this->renderLayout('Patient Check-In', $content);
    }
    
    // ==================== DO CHECK-IN ====================
    public function doCheckIn() {
        header('Content-Type: application/json');
        
        $appointmentId = (int)$_POST['appointment_id'];
        
        // Get appointment details
        $apptQuery = "SELECT a.*, d.id as doctor_id 
                      FROM appointments a
                      JOIN doctors d ON a.doctor_id = d.id
                      WHERE a.id = $appointmentId";
        $apptResult = $this->db->query($apptQuery);
        
        if(!$apptResult || $apptResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            exit;
        }
        
        $appointment = $apptResult->fetch_assoc();
        
        // Check if already checked in
        if(in_array($appointment['status'], ['checked_in', 'in_progress', 'completed'])) {
            echo json_encode(['success' => false, 'message' => 'Patient already checked in or completed']);
            exit;
        }
        
        // Calculate serial number for this doctor and session
        $serialQuery = $this->db->query("SELECT COUNT(*) as cnt FROM appointments 
                                        WHERE doctor_id = {$appointment['doctor_id']} 
                                        AND appointment_date = '{$appointment['appointment_date']}' 
                                        AND session_type = '{$appointment['session_type']}'
                                        AND status NOT IN ('canceled')
                                        AND id <= $appointmentId");
        $serialCount = $serialQuery->fetch_assoc()['cnt'];
        $serialNumber = ($appointment['session_type'] == 'morning' ? 'M' : 'E') . str_pad($serialCount, 3, '0', STR_PAD_LEFT);
        
        // Update appointment status
        $this->db->query("UPDATE appointments SET status = 'checked_in' WHERE id = $appointmentId");
        
        // Check if already in queue
        $checkQueue = $this->db->query("SELECT id FROM queue WHERE appointment_id = $appointmentId");
        if($checkQueue->num_rows == 0) {
            // Add to queue
            $insertQueue = "INSERT INTO queue (appointment_id, serial_number, shift, status, check_in_time, created_at) 
                            VALUES ($appointmentId, '$serialNumber', '{$appointment['session_type']}', 'waiting', NOW(), NOW())";
            $this->db->query($insertQueue);
        } else {
            $this->db->query("UPDATE queue SET status = 'waiting', check_in_time = NOW() WHERE appointment_id = $appointmentId");
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
    
    // ==================== QUEUE PAGE ====================
    public function queue() {
        $this->checkAuth();
        
        // Get all doctors for filter
        $doctors = $this->db->query("SELECT d.id, d.specialization, u.first_name, u.last_name
                                    FROM doctors d 
                                    JOIN users u ON d.user_id = u.id 
                                    WHERE d.status = 'active'");
        $doctorList = [];
        while($row = $doctors->fetch_assoc()) {
            $doctorList[] = $row;
        }
        
        $content = $this->renderView('reception/queue', ['doctors' => $doctorList]);
        $this->renderLayout('Queue Management', $content);
    }
    
    // ==================== GET FILTERED QUEUE ====================
    public function getFilteredQueue() {
        header('Content-Type: application/json');
        
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
        $shift = isset($_GET['shift']) ? $this->db->real_escape_string($_GET['shift']) : '';
        $today = date('Y-m-d');
        
        // Only get today's queue records
        $query = "SELECT q.*, a.appointment_number, a.session_type as shift,
                         p.first_name, p.last_name, p.patient_code,
                         u.first_name as doctor_fname, u.last_name as doctor_lname,
                         d.id as doctor_id, a.id as appointment_id
                  FROM queue q
                  JOIN appointments a ON q.appointment_id = a.id
                  JOIN patients p ON a.patient_id = p.id
                  JOIN doctors d ON a.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  WHERE DATE(q.created_at) = '$today' 
                  AND q.status IN ('waiting', 'in_progress')";
        
        if($doctorId > 0) {
            $query .= " AND d.id = $doctorId";
        }
        if($shift != '') {
            $query .= " AND a.session_type = '$shift'";
        }
        
        $query .= " ORDER BY FIELD(q.status, 'in_progress', 'waiting'), q.serial_number ASC";
        
        $result = $this->db->query($query);
        $queueList = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $queueList[] = $row;
            }
        }
        
        // Get currently serving patient for today only
        $servingQuery = "SELECT q.*, p.first_name, p.last_name, u.first_name as doctor_fname, u.last_name as doctor_lname
                        FROM queue q
                        JOIN appointments a ON q.appointment_id = a.id
                        JOIN patients p ON a.patient_id = p.id
                        JOIN doctors d ON a.doctor_id = d.id
                        JOIN users u ON d.user_id = u.id
                        WHERE DATE(q.created_at) = '$today'
                        AND q.status = 'in_progress'";
        
        if($doctorId > 0) {
            $servingQuery .= " AND d.id = $doctorId";
        }
        
        $servingQuery .= " LIMIT 1";
        $servingResult = $this->db->query($servingQuery);
        $currentServing = $servingResult ? $servingResult->fetch_assoc() : null;
        
        // Get statistics for today only
        $statsQuery = "SELECT 
                        SUM(CASE WHEN q.status = 'waiting' THEN 1 ELSE 0 END) as waiting,
                        SUM(CASE WHEN q.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
                      FROM queue q
                      JOIN appointments a ON q.appointment_id = a.id
                      WHERE DATE(q.created_at) = '$today'";
        
        if($doctorId > 0) {
            $statsQuery .= " AND a.doctor_id = $doctorId";
        }
        
        $statsResult = $this->db->query($statsQuery);
        $stats = $statsResult ? $statsResult->fetch_assoc() : ['waiting' => 0, 'in_progress' => 0];
        
        // Completed today - from appointments table
        $completedQuery = "SELECT COUNT(*) as completed FROM appointments 
                          WHERE appointment_date = '$today' AND status = 'completed'";
        if($doctorId > 0) {
            $completedQuery .= " AND doctor_id = $doctorId";
        }
        $completedResult = $this->db->query($completedQuery);
        $completed = $completedResult ? $completedResult->fetch_assoc()['completed'] : 0;
        
        echo json_encode([
            'success' => true,
            'queue' => $queueList,
            'currentServing' => $currentServing,
            'stats' => [
                'waiting' => (int)($stats['waiting'] ?? 0),
                'in_progress' => (int)($stats['in_progress'] ?? 0),
                'completed' => (int)$completed
            ]
        ]);
        exit;
    }
    
    // ==================== CALL PATIENT ====================
    public function callPatient() {
        header('Content-Type: application/json');
        
        $queueId = (int)$_POST['queue_id'];
        
        // Update queue status to in_progress
        $update = $this->db->query("UPDATE queue SET status = 'in_progress', start_time = NOW() WHERE id = $queueId");
        
        if($update) {
            // Get queue details for response
            $queueInfo = $this->db->query("SELECT q.serial_number, a.id as appointment_id
                                          FROM queue q
                                          JOIN appointments a ON q.appointment_id = a.id
                                          WHERE q.id = $queueId")->fetch_assoc();
            
            // Update appointment status
            if($queueInfo) {
                $this->db->query("UPDATE appointments SET status = 'in_progress' WHERE id = {$queueInfo['appointment_id']}");
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Patient called',
                'queue_number' => $queueInfo ? $queueInfo['serial_number'] : null
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to call patient']);
        }
        exit;
    }
    
    // ==================== COMPLETE CONSULTATION ====================
    public function completeConsultation() {
        header('Content-Type: application/json');
        
        $appointmentId = (int)$_POST['appointment_id'];
        
        // Update appointment status
        $updateAppt = $this->db->query("UPDATE appointments SET status = 'completed' WHERE id = $appointmentId");
        
        // Update queue status
        $updateQueue = $this->db->query("UPDATE queue SET status = 'completed', end_time = NOW() WHERE appointment_id = $appointmentId");
        
        if($updateAppt && $updateQueue) {
            echo json_encode(['success' => true, 'message' => 'Consultation completed']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to complete consultation']);
        }
        exit;
    }
    
    // ==================== SEARCH PATIENT ====================
    public function searchPatient() {
        header('Content-Type: application/json');
        
        $term = isset($_GET['term']) ? $this->db->real_escape_string($_GET['term']) : '';
        
        if(strlen($term) < 2) {
            echo json_encode([]);
            exit;
        }
        
        $query = "SELECT id, patient_code, first_name, last_name, phone, email 
                  FROM patients 
                  WHERE status = 'active' 
                  AND (first_name LIKE '%$term%' 
                       OR last_name LIKE '%$term%' 
                       OR patient_code LIKE '%$term%' 
                       OR phone LIKE '%$term%')
                  LIMIT 20";
        
        $result = $this->db->query($query);
        $patients = [];
        
        if($result) {
            while($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
        }
        
        echo json_encode($patients);
        exit;
    }
    
    // ==================== GET PATIENT APPOINTMENTS ====================
    public function getPatientAppointments() {
        header('Content-Type: application/json');
        
        $patientId = (int)$_GET['patient_id'];
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
        $date = isset($_GET['date']) ? $this->db->real_escape_string($_GET['date']) : date('Y-m-d');
        
        $query = "SELECT a.*, u.first_name as doctor_fname, u.last_name as doctor_lname, d.specialization, d.id as doctor_id
                 FROM appointments a
                 JOIN doctors d ON a.doctor_id = d.id
                 JOIN users u ON d.user_id = u.id
                 WHERE a.patient_id = $patientId AND a.appointment_date = '$date'
                 AND a.status NOT IN ('canceled', 'completed')";
        
        if($doctorId > 0) {
            $query .= " AND a.doctor_id = $doctorId";
        }
        
        $query .= " ORDER BY a.start_time ASC";
        
        $result = $this->db->query($query);
        $appointments = [];
        
        if($result) {
            while($row = $result->fetch_assoc()) {
                $row['doctor_name'] = $row['doctor_fname'] . ' ' . $row['doctor_lname'];
                $appointments[] = $row;
            }
        }
        
        echo json_encode($appointments);
        exit;
    }
    
    // ==================== GET QUEUE STATUS (HTML) ====================
    public function getQueueStatus() {
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
        $shift = isset($_GET['shift']) ? $this->db->real_escape_string($_GET['shift']) : '';
        $today = date('Y-m-d');
        
        // Only get today's queue
        $query = "SELECT q.*, a.session_type as shift,
                         p.first_name, p.last_name, p.patient_code,
                         u.first_name as doctor_fname, u.last_name as doctor_lname
                  FROM queue q
                  JOIN appointments a ON q.appointment_id = a.id
                  JOIN patients p ON a.patient_id = p.id
                  JOIN doctors d ON a.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  WHERE DATE(q.created_at) = '$today' 
                  AND q.status IN ('waiting', 'in_progress')";
        
        if($doctorId > 0) {
            $query .= " AND d.id = $doctorId";
        }
        if($shift != '') {
            $query .= " AND a.session_type = '$shift'";
        }
        
        $query .= " ORDER BY FIELD(q.status, 'in_progress', 'waiting'), q.serial_number ASC";
        
        $result = $this->db->query($query);
        
        ob_start();
        ?>
        <div class="list-group">
            <?php if($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <div class="list-group-item queue-item <?php echo $row['status']; ?>">
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
}
?>