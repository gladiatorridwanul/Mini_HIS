<?php
// /app/controllers/PrescriptionController.php
// COMPLETE MERGED VERSION - All functions combined without duplicates

// ================================================================
// ENABLE ERROR LOGGING FOR DEBUGGING
// ================================================================
ini_set('log_errors', 1);
$logDir = BASE_PATH . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
ini_set('error_log', $logDir . '/php_errors.log');

// For AJAX requests, suppress output but still log
if (isset($_POST['ajax']) && $_POST['ajax'] == '1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Log the start of each request
error_log("=== PRESCRIPTION CONTROLLER REQUEST ===");
error_log("POST data: " . print_r($_POST, true));

require_once BASE_PATH . '/app/helpers/DateHelper.php';
require_once BASE_PATH . '/app/helpers/PrescriptionHelper.php';

class PrescriptionController extends Controller {

    private $prescriptionModel;
    private $patientModel;
    private $doctorModel;
    private $appointmentModel;

    public function __construct() {
        parent::__construct();
        
        $this->prescriptionModel = new Prescription();
        $this->patientModel = new Patient();
        $this->doctorModel = new Doctor();
        $this->appointmentModel = new Appointment();
        $this->checkAuth();
    }

    // ================================================================
    // INDEX - List all prescriptions with pagination
    // ONLY SHOWS FULL PRESCRIPTIONS (issued, dispensed, completed)
    // DRAFT PRESCRIPTIONS ARE HIDDEN
    // ================================================================
    public function index() {
        $this->checkAuth();
        $this->checkPermission('view_prescriptions');

        $doctorId = $this->getDoctorId();
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $selected_patient = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        $selected_doctor = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $patients = $this->getAllPatients();
        $doctors = $this->getActiveDoctors();

        // Build the WHERE clause
        $whereClauses = [];
        $params = [];

        // Only show FULL prescriptions (not draft)
        $whereClauses[] = "p.status NOT IN ('draft')";
        
        if ($doctorId > 0) {
            $whereClauses[] = "p.doctor_id = ?";
            $params[] = $doctorId;
        }
        if (!empty($search)) {
            $whereClauses[] = "(p.prescription_number LIKE ? OR pa.first_name LIKE ? OR pa.last_name LIKE ? OR pa.patient_code LIKE ? OR CONCAT(u.first_name,' ',u.last_name) LIKE ?)";
            $s = "%{$search}%";
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }
        if ($selected_patient > 0) { 
            $whereClauses[] = "p.patient_id = ?"; 
            $params[] = $selected_patient; 
        }
        if ($selected_doctor > 0) { 
            $whereClauses[] = "p.doctor_id = ?"; 
            $params[] = $selected_doctor; 
        }
        if (!empty($status)) { 
            $whereClauses[] = "p.status = ?"; 
            $params[] = $status; 
        }
        if (!empty($dateFrom)) { 
            $whereClauses[] = "p.prescription_date >= ?"; 
            $params[] = $dateFrom; 
        }

        $whereSQL = empty($whereClauses) ? "" : "WHERE " . implode(" AND ", $whereClauses);

        // GET TOTAL COUNT FOR PAGINATION
        $countSql = "SELECT COUNT(*) as total 
                     FROM prescriptions p
                     JOIN patients pa ON p.patient_id = pa.id
                     JOIN doctors d ON p.doctor_id = d.id
                     JOIN users u ON d.user_id = u.id
                     {$whereSQL}";
        
        $countResult = $this->queryOne($countSql, $params);
        $totalPrescriptions = $countResult['total'] ?? 0;
        $totalPages = ($totalPrescriptions > 0) ? ceil($totalPrescriptions / $limit) : 1;

        // GET CURRENT PAGE RECORDS
        $sql = "SELECT p.*,
                       CONCAT(pa.first_name, ' ', pa.last_name) as patient_name,
                       pa.patient_code,
                       CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                FROM prescriptions p
                JOIN patients pa ON p.patient_id = pa.id
                JOIN doctors d ON p.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                {$whereSQL}
                ORDER BY p.prescription_date DESC, p.id DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $prescriptions = $this->query($sql, $params);

        error_log("=== PRESCRIPTION PAGINATION (FULL ONLY) ===");
        error_log("Page: " . $page);
        error_log("Total Prescriptions (Full only): " . $totalPrescriptions);
        error_log("Records on this page: " . count($prescriptions));
        error_log("Drafts are EXCLUDED from this list");
        error_log("===============================");

        $this->view('prescriptions/index', [
            'prescriptions' => $prescriptions,
            'patients' => $patients,
            'doctors' => $doctors,
            'search' => $search,
            'selected_patient' => $selected_patient,
            'selected_doctor' => $selected_doctor,
            'status' => $status,
            'date_from' => $dateFrom,
            'totalPrescriptions' => $totalPrescriptions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'title' => 'Prescriptions'
        ]);
    }

    // ================================================================
    // CREATE - Show create form
    // ================================================================
    public function create() {
        $this->checkAuth();
        $this->checkPermission('create_prescriptions');

        $appointmentId = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        $doctorId = $this->getDoctorId();
        $duplicateOf = isset($_GET['duplicate_of']) ? (int)$_GET['duplicate_of'] : 0;

        $patient = null;
        $appointment = null;
        $visitNumber = 1;
        $selectedDoctor = null;
        $treatmentHistory = [];

        // STEP 1: Get appointment and patient
        if ($appointmentId > 0 && $this->appointmentModel) {
            $appointment = $this->appointmentModel->find($appointmentId);
            if ($appointment) {
                $patientId = $appointment['patient_id'];
                $selectedDoctor = $this->getDoctorByAppointment($appointmentId);
            }
        }

        // STEP 2: Get patient
        if ($patientId > 0 && $this->patientModel) {
            $patient = $this->patientModel->find($patientId);
            if ($patient && $this->prescriptionModel) {
                $visitNumber = $this->prescriptionModel->getVisitNumber($patientId);
                $treatmentHistory = $this->getTreatmentHistory($patientId);
            }
        }

        if (!$patient) {
            $_SESSION['error'] = 'Please select a patient first.';
            $this->redirect('/patient/list');
            return;
        }

        // STEP 3: Get doctor information
        if (!$selectedDoctor) {
            $selectedDoctor = $this->getDoctorById($doctorId);
        }
        if (!$selectedDoctor) {
            $selectedDoctor = $this->getFirstActiveDoctor();
        }
        if (!$selectedDoctor) {
            $selectedDoctor = [
                'id' => 0,
                'first_name' => 'Doctor',
                'last_name' => 'Not Assigned',
                'title' => 'Dr.',
                'specialization' => 'General',
                'qualification' => 'MBBS',
                'bmdc_number' => 'N/A',
                'consultation_fee' => 0,
                'doctor_info_en' => '',
                'doctor_info_bn' => ''
            ];
        }

        // Load duplicate data if specified
        $duplicateData = null;
        if ($duplicateOf > 0) {
            $duplicateData = $this->prescriptionModel->getPrintData($duplicateOf);
        }

        $drugs = $this->getMedicines();
        $adviceTemplates = $this->getAdviceTemplates();
        $labHistory = $this->getLabHistory($patientId);
        $vaccinations = $this->getPatientVaccinations($patientId);

        $this->view('prescriptions/create', [
            'title' => 'Create Prescription',
            'patient' => $patient,
            'appointment' => $appointment,
            'visitNumber' => $visitNumber,
            'doctor' => $selectedDoctor,
            'drugs' => $drugs,
            'adviceTemplates' => $adviceTemplates,
            'appointmentId' => $appointmentId,
            'labHistory' => $labHistory,
            'vaccinations' => $vaccinations,
            'treatmentHistory' => $treatmentHistory,
            'duplicateData' => $duplicateData
        ]);
    }

    // ================================================================
    // SAVE - Save new prescription with all tab data
    // ================================================================
    public function save() {
        $this->checkAuth();
        $this->checkPermission('create_prescriptions');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/prescriptions');
            return;
        }

        $data = $_POST;
        $doctorId = $this->getDoctorId();
        $isAjax = isset($data['ajax']) && $data['ajax'] == '1';
        $nextTab = isset($data['next_tab']) ? $data['next_tab'] : '';
        
        // Check if this is an update (prescription_id exists and > 0)
        $prescriptionId = isset($data['prescription_id']) ? (int)$data['prescription_id'] : 0;
        
        // If we have a prescription_id, call update method
        if ($prescriptionId > 0) {
            $this->update($prescriptionId);
            return;
        }

        if (empty($data['patient_id']) || $data['patient_id'] == 0) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
                exit;
            }
            $this->setFlash('error', 'Patient ID is required');
            $this->redirect('/prescriptions/create');
            return;
        }

        if ($doctorId == 0) {
            if (!empty($data['appointment_id'])) {
                $appt = $this->appointmentModel->find($data['appointment_id']);
                if ($appt && $appt['doctor_id']) {
                    $doctorId = $appt['doctor_id'];
                }
            }
            if ($doctorId == 0) {
                $firstDoctor = $this->doctorModel->find(1);
                if ($firstDoctor) {
                    $doctorId = $firstDoctor['id'];
                }
            }
        }

        if ($doctorId == 0) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Doctor ID is required']);
                exit;
            }
            $this->setFlash('error', 'Doctor ID is required.');
            $this->redirect('/prescriptions/create?patient_id=' . $data['patient_id']);
            return;
        }

        try {
            $db = $this->db;
            
            if (!$db) {
                throw new Exception('Database connection not available');
            }
            
            $db->begin_transaction();

            $prescriptionNumber = 'PRX' . date('Ymd') . rand(1000, 9999) . rand(10, 99);
            
            $isComplete = isset($data['complete']) && $data['complete'] == '1';
            $isFinalSave = isset($data['final_save']) && $data['final_save'] == '1';
            $status = ($isComplete || $isFinalSave) ? 'issued' : 'draft';

            error_log("Prescription status: " . $status . " (complete: " . ($isComplete ? 'yes' : 'no') . ", final_save: " . ($isFinalSave ? 'yes' : 'no') . ")");

            // Insert prescription
            $sql = "INSERT INTO prescriptions (
                        prescription_number, patient_id, doctor_id, appointment_id,
                        prescription_date, visit_number, marital_status, occupation,
                        special_note, diagnosis, status, pharmacy_status
                    ) VALUES (
                        ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, ?, ?
                    )";
            
            $stmt = $db->prepare($sql);
            $appointmentId = !empty($data['appointment_id']) ? (int)$data['appointment_id'] : null;
            $maritalStatus = !empty($data['marital_status']) ? $data['marital_status'] : '';
            $occupation = !empty($data['occupation']) ? $data['occupation'] : '';
            $specialNote = !empty($data['special_note']) ? $data['special_note'] : '';
            $diagnosis = !empty($data['diagnosis']) ? $data['diagnosis'] : '';
            $prescriptionDate = !empty($data['prescription_date']) ? $data['prescription_date'] : date('Y-m-d');
            $visitNumber = !empty($data['visit_number']) ? (int)$data['visit_number'] : 1;
            $pharmacyStatus = 'pending';
            
            $stmt->bind_param(
                'siiissssssss',
                $prescriptionNumber,
                $data['patient_id'],
                $doctorId,
                $appointmentId,
                $prescriptionDate,
                $visitNumber,
                $maritalStatus,
                $occupation,
                $specialNote,
                $diagnosis,
                $status,
                $pharmacyStatus
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Insert failed: ' . $stmt->error);
            }
            $prescriptionId = $db->insert_id;
            error_log("Prescription inserted with ID: " . $prescriptionId);

            // Save all related data
            $this->savePrescriptionData($prescriptionId, $data, $db);

            // Update appointment status if completed
            if ($isComplete && !empty($data['appointment_id']) && $data['appointment_id'] > 0) {
                $db->query("UPDATE appointments SET status = 'completed' WHERE id = " . (int)$data['appointment_id']);
            }

            $db->commit();

            $message = ($status == 'issued') 
                ? '✅ Prescription issued successfully! #' . $prescriptionNumber
                : '💾 Prescription saved as draft successfully!';

            if ($isAjax) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                header('Content-Type: application/json');
                header('Cache-Control: no-cache, must-revalidate');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'prescription_id' => $prescriptionId,
                    'next_tab' => $nextTab
                ]);
                exit;
            }

            $this->setFlash('success', $message);
            
            if (!empty($nextTab)) {
                $this->redirect('/prescriptions/edit/' . $prescriptionId . '?tab=' . $nextTab);
                return;
            }
            
            $this->redirect('/prescriptions/show/' . $prescriptionId);

        } catch (Exception $e) {
            if (isset($db) && method_exists($db, 'rollback')) {
                $db->rollback();
            }
            error_log("Prescription save error: " . $e->getMessage());
            
            if ($isAjax) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => '❌ Error saving prescription: ' . $e->getMessage()
                ]);
                exit;
            }
            
            $this->setFlash('error', '❌ Error saving prescription: ' . $e->getMessage());
            $this->redirect('/prescriptions/create?patient_id=' . ($data['patient_id'] ?? 0));
        }
    }

    // ================================================================
    // UPDATE - Update existing prescription - FIXED
    // ================================================================
    public function update($id) {
        $this->checkAuth();
        $this->checkPermission('edit_prescriptions');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid request method']);
                exit;
            }
            $this->redirect('/prescriptions');
            return;
        }

        $data = $_POST;
        $isComplete = isset($data['complete']) && $data['complete'] == '1';
        $isAjax = isset($data['ajax']) && $data['ajax'] == '1';
        $nextTab = isset($data['next_tab']) ? $data['next_tab'] : '';

        try {
            $db = $this->db;
            
            if (!$db) {
                throw new Exception('Database connection not available');
            }
            
            $db->begin_transaction();

            // Check if prescription exists
            $check = $db->query("SELECT id, status FROM prescriptions WHERE id = " . (int)$id);
            if ($check->num_rows == 0) {
                throw new Exception('Prescription not found');
            }
            $current = $check->fetch_assoc();
            $currentStatus = $current['status'] ?? 'draft';

            // Determine new status
            $isFinalSave = isset($data['final_save']) && $data['final_save'] == '1';
            
            if ($isComplete) {
                $newStatus = 'issued';
            } elseif ($isFinalSave) {
                $newStatus = 'issued';
            } elseif ($currentStatus == 'completed') {
                $newStatus = 'completed';
            } elseif ($currentStatus == 'issued' || $currentStatus == 'dispensed') {
                $newStatus = $currentStatus;
            } else {
                $newStatus = 'draft';
            }
            
            error_log("Update status: " . $newStatus . " (current: " . $currentStatus . ")");

            // Update prescription
            $maritalStatus = !empty($data['marital_status']) ? $data['marital_status'] : '';
            $occupation = !empty($data['occupation']) ? $data['occupation'] : '';
            $specialNote = !empty($data['special_note']) ? $data['special_note'] : '';
            $diagnosis = !empty($data['diagnosis']) ? $data['diagnosis'] : '';
            $prescriptionDate = !empty($data['prescription_date']) ? $data['prescription_date'] : date('Y-m-d');
            
            $sql = "UPDATE prescriptions SET 
                        marital_status = ?,
                        occupation = ?,
                        special_note = ?,
                        diagnosis = ?,
                        prescription_date = ?,
                        status = ?,
                        pharmacy_status = 'pending'
                        WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->bind_param('ssssssi', $maritalStatus, $occupation, $specialNote, $diagnosis, $prescriptionDate, $newStatus, $id);
            
            if (!$stmt->execute()) {
                throw new Exception('Update failed: ' . $stmt->error);
            }
            $stmt->close();
            error_log("Prescription updated with status: " . $newStatus);

            // ============================================================
            // DELETE ALL RELATED DATA (with error handling)
            // ============================================================
            $tables = [
                'prescription_chief_complaints',
                'prescription_drug_history',
                'prescription_disease_history',
                'prescription_investigations',
                'prescription_physical_examination',
                'prescription_vital_signs',
                'prescription_advice',
                'prescription_lab_tests',
                'treatment_history',
                'patient_vaccinations'
            ];
            
            foreach ($tables as $table) {
                $deleteSql = "DELETE FROM {$table} WHERE prescription_id = " . (int)$id;
                if (!$db->query($deleteSql)) {
                    error_log("Failed to delete from {$table}: " . $db->error);
                }
            }
            
            // Delete medicine details and items
            $db->query("DELETE pmd FROM prescription_medicine_details pmd 
                        INNER JOIN prescription_items pi ON pmd.prescription_item_id = pi.id 
                        WHERE pi.prescription_id = " . (int)$id);
            $db->query("DELETE FROM prescription_items WHERE prescription_id = " . (int)$id);

            // ============================================================
            // SAVE ALL RELATED DATA
            // ============================================================
            $this->savePrescriptionData($id, $data, $db);

            // Update appointment status if completed
            if ($newStatus == 'issued' && !empty($data['appointment_id']) && $data['appointment_id'] > 0) {
                $db->query("UPDATE appointments SET status = 'completed' WHERE id = " . (int)$data['appointment_id']);
            }

            $db->commit();

            $message = ($newStatus == 'issued' || $newStatus == 'dispensed' || $newStatus == 'completed') 
                ? '✅ Prescription updated successfully!'
                : '💾 Prescription updated successfully!';

            if ($isAjax) {
                while (ob_get_level()) {
                    ob_end_clean();
                }
                
                header('Content-Type: application/json');
                header('Cache-Control: no-cache, must-revalidate');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'prescription_id' => $id,
                    'new_status' => $newStatus,
                    'next_tab' => $nextTab
                ]);
                exit;
            }

            $this->setFlash('success', $message);
            
            if (!empty($nextTab)) {
                $this->redirect('/prescriptions/edit/' . $id . '?tab=' . $nextTab);
                return;
            }

            $this->redirect('/prescriptions/show/' . $id);

        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            error_log("Prescription update error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => '❌ Error updating prescription: ' . $e->getMessage()
                ]);
                exit;
            }
            
            $this->setFlash('error', '❌ Error updating prescription: ' . $e->getMessage());
            $this->redirect('/prescriptions/edit/' . $id);
        }
    }

    // ================================================================
    // SAVE PRESCRIPTION DATA - Helper method for all related tables
    // FIXED: Properly handles medicines from edit form
    // ================================================================
    private function savePrescriptionData($prescriptionId, $data, $db) {
        $patientId = (int)$data['patient_id'];
        
        // ============================================================
        // 1. SAVE CHIEF COMPLAINTS
        // ============================================================
        if (!empty($data['complaints']) && is_array($data['complaints'])) {
            $stmt = $db->prepare("INSERT INTO prescription_chief_complaints (prescription_id, complaint, duration, remarks) VALUES (?, ?, ?, ?)");
            foreach ($data['complaints'] as $complaint) {
                if (!empty($complaint['text'])) {
                    $complaintText = trim($complaint['text']);
                    $duration = isset($complaint['duration']) ? trim($complaint['duration']) : '';
                    $remarks = isset($complaint['remarks']) ? trim($complaint['remarks']) : '';
                    $stmt->bind_param('isss', $prescriptionId, $complaintText, $duration, $remarks);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        // ============================================================
        // 2. SAVE TREATMENT HISTORY
        // ============================================================
        if (!empty($data['treatment_history']) && is_array($data['treatment_history'])) {
            try {
                if (!$this->tableExists('treatment_history')) {
                    $db->query("CREATE TABLE IF NOT EXISTS `treatment_history` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `patient_id` int(11) NOT NULL,
                        `prescription_id` int(11) NOT NULL,
                        `treatment_name` varchar(200) NOT NULL,
                        `duration` varchar(50) DEFAULT NULL,
                        `remarks` text DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        PRIMARY KEY (`id`),
                        KEY `patient_id` (`patient_id`),
                        KEY `prescription_id` (`prescription_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
                }
                
                $db->query("DELETE FROM treatment_history WHERE prescription_id = " . (int)$prescriptionId);
                
                $stmt = $db->prepare("INSERT INTO treatment_history (patient_id, prescription_id, treatment_name, duration, remarks) VALUES (?, ?, ?, ?, ?)");
                
                foreach ($data['treatment_history'] as $treatment) {
                    if (!empty($treatment['name'])) {
                        $treatmentName = trim($treatment['name']);
                        $duration = isset($treatment['duration']) ? trim($treatment['duration']) : '';
                        $remarks = isset($treatment['remarks']) ? trim($treatment['remarks']) : '';
                        $stmt->bind_param('iisss', $patientId, $prescriptionId, $treatmentName, $duration, $remarks);
                        $stmt->execute();
                    }
                }
                $stmt->close();
            } catch (Exception $e) {
                error_log("saveTreatmentHistory error: " . $e->getMessage());
            }
        }

        // ============================================================
        // 3. SAVE DRUG HISTORY
        // ============================================================
        if (!empty($data['drug_history']) && is_array($data['drug_history'])) {
            $stmt = $db->prepare("INSERT INTO prescription_drug_history (prescription_id, drug_name, duration, remarks) VALUES (?, ?, ?, ?)");
            foreach ($data['drug_history'] as $drug) {
                if (!empty($drug['name'])) {
                    $drugName = trim($drug['name']);
                    $duration = isset($drug['duration']) ? trim($drug['duration']) : '';
                    $remarks = isset($drug['remarks']) ? trim($drug['remarks']) : '';
                    $stmt->bind_param('isss', $prescriptionId, $drugName, $duration, $remarks);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        // ============================================================
        // 4. SAVE DISEASE HISTORY
        // ============================================================
        if (!empty($data['disease_history']) && is_array($data['disease_history'])) {
            $stmt = $db->prepare("INSERT INTO prescription_disease_history (prescription_id, disease, duration, remarks) VALUES (?, ?, ?, ?)");
            foreach ($data['disease_history'] as $disease) {
                if (!empty($disease['name'])) {
                    $diseaseName = trim($disease['name']);
                    $duration = isset($disease['duration']) ? trim($disease['duration']) : '';
                    $remarks = isset($disease['remarks']) ? trim($disease['remarks']) : '';
                    $stmt->bind_param('isss', $prescriptionId, $diseaseName, $duration, $remarks);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        // ============================================================
        // 5. SAVE INVESTIGATIONS
        // ============================================================
        if (!empty($data['investigations']) && is_array($data['investigations'])) {
            $stmt = $db->prepare("INSERT INTO prescription_investigations (prescription_id, investigation_name, remarks) VALUES (?, ?, ?)");
            foreach ($data['investigations'] as $inv) {
                if (!empty($inv['name'])) {
                    $investigationName = trim($inv['name']);
                    $remarks = isset($inv['remarks']) ? trim($inv['remarks']) : '';
                    $stmt->bind_param('iss', $prescriptionId, $investigationName, $remarks);
                    $stmt->execute();
                }
            }
            $stmt->close();
        }

        // ============================================================
        // 6. SAVE PHYSICAL EXAM
        // ============================================================
        if (!empty($data['physical_exam'])) {
            $exam = $data['physical_exam'];
            $anaemia = isset($exam['anaemia']) ? 1 : 0;
            $jaundice = isset($exam['jaundice']) ? 1 : 0;
            $cyanosis = isset($exam['cyanosis']) ? 1 : 0;
            $oedema = isset($exam['oedema']) ? 1 : 0;
            $dehydration = isset($exam['dehydration']) ? 1 : 0;
            $abdomen = isset($exam['abdomen']) ? $exam['abdomen'] : '';
            $cvs = isset($exam['cvs']) ? $exam['cvs'] : '';
            $respiratory = isset($exam['respiratory']) ? $exam['respiratory'] : '';
            $lymphoreticular = isset($exam['lymphoreticular']) ? $exam['lymphoreticular'] : '';
            
            $stmt = $db->prepare("INSERT INTO prescription_physical_examination 
                        (prescription_id, anaemia, jaundice, cyanosis, oedema, dehydration, 
                         abdomen, cvs, respiratory, lymphoreticular) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iiiiisssss', 
                $prescriptionId, $anaemia, $jaundice, $cyanosis, $oedema, $dehydration,
                $abdomen, $cvs, $respiratory, $lymphoreticular
            );
            $stmt->execute();
            $stmt->close();
        }

        // ============================================================
        // 7. SAVE VITAL SIGNS
        // ============================================================
        if (!empty($data['vital_signs'])) {
            $v = $data['vital_signs'];
            $pulse = isset($v['pulse']) && $v['pulse'] !== '' ? (int)$v['pulse'] : null;
            $weight = isset($v['weight']) && $v['weight'] !== '' ? (float)$v['weight'] : null;
            $respiratoryRate = isset($v['respiratory_rate']) && $v['respiratory_rate'] !== '' ? (int)$v['respiratory_rate'] : null;
            $length = isset($v['length']) && $v['length'] !== '' ? (float)$v['length'] : null;
            $bpSystolic = isset($v['bp_systolic']) && $v['bp_systolic'] !== '' ? (int)$v['bp_systolic'] : null;
            $bpDiastolic = isset($v['bp_diastolic']) && $v['bp_diastolic'] !== '' ? (int)$v['bp_diastolic'] : null;
            $temperature = isset($v['temperature']) && $v['temperature'] !== '' ? (float)$v['temperature'] : null;
            $oxygenSaturation = isset($v['oxygen_saturation']) && $v['oxygen_saturation'] !== '' ? (int)$v['oxygen_saturation'] : null;
            $bmi = isset($v['bmi']) && $v['bmi'] !== '' ? (float)$v['bmi'] : null;
            $others = isset($v['others']) ? $v['others'] : '';
            
            $stmt = $db->prepare("INSERT INTO prescription_vital_signs 
                        (prescription_id, pulse, weight, respiratory_rate, length,
                         blood_pressure_systolic, blood_pressure_diastolic,
                         temperature, oxygen_saturation, bmi, others) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iiiddddiids',
                $prescriptionId, $pulse, $weight, $respiratoryRate, $length,
                $bpSystolic, $bpDiastolic, $temperature, $oxygenSaturation, $bmi, $others
            );
            $stmt->execute();
            $stmt->close();
        }

        // ============================================================
        // 8. SAVE ADVICE
        // ============================================================
        $adviceText = isset($data['advice']['text']) ? $data['advice']['text'] : '';
        $followUpDays = isset($data['follow_up_days']) && $data['follow_up_days'] !== '' ? (int)$data['follow_up_days'] : 0;
        $appointmentDate = isset($data['appointment_date']) && $data['appointment_date'] !== '' ? $data['appointment_date'] : null;
        
        if (!empty($adviceText) || $followUpDays > 0 || $appointmentDate) {
            $stmt = $db->prepare("INSERT INTO prescription_advice 
                        (prescription_id, advice_text, follow_up_days, follow_up_months, appointment_date) 
                        VALUES (?, ?, ?, ?, ?)");
            $followUpMonths = 0;
            $stmt->bind_param('isiss', $prescriptionId, $adviceText, $followUpDays, $followUpMonths, $appointmentDate);
            $stmt->execute();
            $stmt->close();
        }

        // ============================================================
        // 9. SAVE LAB TESTS
        // ============================================================
        if (!empty($data['lab_tests']) && is_array($data['lab_tests'])) {
            if (!$this->tableExists('prescription_lab_tests')) {
                $db->query("CREATE TABLE IF NOT EXISTS `prescription_lab_tests` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `prescription_id` int(11) NOT NULL,
                    `test_id` int(11) DEFAULT NULL,
                    `test_name` varchar(200) NOT NULL,
                    `priority` enum('routine','urgent','stat') DEFAULT 'routine',
                    `notes` text DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `prescription_id` (`prescription_id`),
                    KEY `test_id` (`test_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
            
            $stmt = $db->prepare("INSERT INTO prescription_lab_tests (prescription_id, test_id, test_name, priority, notes) VALUES (?, ?, ?, ?, ?)");
            foreach ($data['lab_tests'] as $labTest) {
                if (empty($labTest['test_id']) && empty($labTest['test_name'])) continue;
                
                $testId = !empty($labTest['test_id']) ? (int)$labTest['test_id'] : null;
                $testName = isset($labTest['test_name']) ? trim($labTest['test_name']) : '';
                
                if ($testId && empty($testName)) {
                    $testResult = $db->query("SELECT test_name FROM lab_tests WHERE id = $testId");
                    if ($testRow = $testResult->fetch_assoc()) {
                        $testName = $testRow['test_name'];
                    }
                }
                
                if (empty($testName)) continue;
                
                $priority = isset($labTest['priority']) ? $labTest['priority'] : 'routine';
                $notes = isset($labTest['notes']) ? $labTest['notes'] : '';
                
                $stmt->bind_param('iisss', $prescriptionId, $testId, $testName, $priority, $notes);
                $stmt->execute();
            }
            $stmt->close();
        }

        // ============================================================
        // 10. SAVE MEDICINES - FIXED: Properly handles all medicine data
        // ============================================================
        if (!empty($data['medicines']) && is_array($data['medicines'])) {
            error_log("=== SAVING MEDICINES ===");
            error_log("Number of medicines to save: " . count($data['medicines']));
            
            foreach ($data['medicines'] as $medicineIndex => $medicine) {
                // Skip if both drug_name and drug_id are empty
                if (empty($medicine['drug_name']) && empty($medicine['drug_id'])) {
                    error_log("Skipping medicine at index " . $medicineIndex . " - no drug name or ID");
                    continue;
                }
                
                // Get the drug name - prioritize the input value over the hidden ID
                $drugName = isset($medicine['drug_name']) ? trim($medicine['drug_name']) : '';
                $drugId = !empty($medicine['drug_id']) ? (int)$medicine['drug_id'] : null;
                
                // If drug_id is provided but drug_name is empty, fetch the name from database
                if ($drugId > 0 && empty($drugName)) {
                    $checkSql = "SELECT id, medicine_name, strength FROM medicines WHERE id = " . $drugId;
                    $checkResult = $db->query($checkSql);
                    if ($checkResult && $checkResult->num_rows > 0) {
                        $medRow = $checkResult->fetch_assoc();
                        $drugName = $medRow['medicine_name'];
                        if (!empty($medRow['strength'])) {
                            $drugName .= ' ' . $medRow['strength'];
                        }
                    } else {
                        // If drug not found in database, keep the drug_id as null
                        $drugId = null;
                        error_log("Warning: Drug ID {$medicine['drug_id']} not found in medicines table");
                    }
                }
                
                // If still empty, skip this medicine
                if (empty($drugName)) {
                    error_log("Skipping medicine at index " . $medicineIndex . " - empty drug name after lookup");
                    continue;
                }
                
                // Get all medicine details
                $dosage = isset($medicine['dosage']) ? trim($medicine['dosage']) : '';
                $relationToFood = isset($medicine['relation_to_food']) ? trim($medicine['relation_to_food']) : '';
                $quantity = isset($medicine['quantity']) ? (int)$medicine['quantity'] : 1;
                $refills = isset($medicine['refills']) ? (int)$medicine['refills'] : 0;
                
                // Get the first detail for the main fields (backward compatibility)
                $firstDetail = isset($medicine['details'][0]) ? $medicine['details'][0] : [];
                $frequency = isset($firstDetail['frequency']) ? trim($firstDetail['frequency']) : '';
                $duration = isset($firstDetail['duration']) ? trim($firstDetail['duration']) : '';
                $instruction = isset($firstDetail['instruction']) ? trim($firstDetail['instruction']) : '';
                
                // Set defaults if empty
                if (empty($frequency)) $frequency = 'Once daily';
                if (empty($duration)) $duration = '5 days';
                
                // Prepare the insert statement for prescription_items
                $stmt = $db->prepare("INSERT INTO prescription_items 
                            (prescription_id, drug_id, drug_name, dosage, frequency, duration,
                             route, instructions, quantity, refills, relation_to_food)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if (!$stmt) {
                    error_log("Prepare failed for prescription_items: " . $db->error);
                    continue;
                }
                
                $route = $relationToFood; // Use relation_to_food as route
                
                $stmt->bind_param('iissssssiis', 
                    $prescriptionId, $drugId, $drugName, $dosage, $frequency, $duration,
                    $route, $instruction, $quantity, $refills, $relationToFood
                );
                
                if (!$stmt->execute()) {
                    error_log("Execute failed for prescription_items: " . $stmt->error);
                    $stmt->close();
                    continue;
                }
                
                $itemId = $db->insert_id;
                $stmt->close();
                
                error_log("Saved medicine: " . $drugName . " (ID: " . $itemId . ")");
                
                // ============================================================
                // SAVE MEDICINE DETAILS (frequency, duration, instruction)
                // ============================================================
                if (!empty($medicine['details']) && is_array($medicine['details'])) {
                    $detailStmt = $db->prepare("INSERT INTO prescription_medicine_details 
                                (prescription_item_id, frequency, duration, instruction)
                                VALUES (?, ?, ?, ?)");
                    
                    if ($detailStmt) {
                        $detailCount = 0;
                        foreach ($medicine['details'] as $detail) {
                            $freq = isset($detail['frequency']) ? trim($detail['frequency']) : '';
                            $dur = isset($detail['duration']) ? trim($detail['duration']) : '';
                            $inst = isset($detail['instruction']) ? trim($detail['instruction']) : '';
                            
                            // Skip if all empty
                            if (empty($freq) && empty($dur) && empty($inst)) {
                                continue;
                            }
                            
                            // Set defaults
                            if (empty($freq)) $freq = 'Once daily';
                            if (empty($dur)) $dur = '5 days';
                            
                            $detailStmt->bind_param('isss', $itemId, $freq, $dur, $inst);
                            if ($detailStmt->execute()) {
                                $detailCount++;
                            } else {
                                error_log("Execute failed for prescription_medicine_details: " . $detailStmt->error);
                            }
                        }
                        $detailStmt->close();
                        error_log("Saved " . $detailCount . " details for medicine ID " . $itemId);
                    }
                } else {
                    // If no details provided, save at least one default detail
                    $detailStmt = $db->prepare("INSERT INTO prescription_medicine_details 
                                (prescription_item_id, frequency, duration, instruction)
                                VALUES (?, ?, ?, ?)");
                    
                    if ($detailStmt) {
                        $detailStmt->bind_param('isss', $itemId, $frequency, $duration, $instruction);
                        if ($detailStmt->execute()) {
                            error_log("Saved default detail for medicine ID " . $itemId);
                        }
                        $detailStmt->close();
                    }
                }
            }
            error_log("=== FINISHED SAVING MEDICINES ===");
        }

        // ============================================================
        // 11. SAVE VACCINATIONS
        // ============================================================
        if (!empty($data['vaccinations']) && is_array($data['vaccinations'])) {
            $checkCol = $db->query("SHOW COLUMNS FROM patient_vaccinations LIKE 'prescription_id'");
            if ($checkCol && $checkCol->num_rows > 0) {
                $db->query("DELETE FROM patient_vaccinations WHERE prescription_id = " . (int)$prescriptionId);
            }
            
            $columns = [];
            $colResult = $db->query("SHOW COLUMNS FROM patient_vaccinations");
            if ($colResult) {
                while ($col = $colResult->fetch_assoc()) {
                    $columns[] = $col['Field'];
                }
            }
            
            $hasPrescriptionId = in_array('prescription_id', $columns);
            $hasVaccineId = in_array('vaccine_id', $columns);
            $hasDoseNumber = in_array('dose_number', $columns);
            $hasDose = in_array('dose', $columns);
            $hasNextDueDate = in_array('next_due_date', $columns);
            $hasNextDue = in_array('next_due', $columns);
            $hasInjectionSite = in_array('injection_site', $columns);
            $hasSite = in_array('site', $columns);
            
            foreach ($data['vaccinations'] as $vac) {
                if (empty($vac['vaccine_name'])) continue;
                
                $fields = ['patient_id'];
                $placeholders = ['?'];
                $values = [$patientId];
                $types = 'i';
                
                if ($hasPrescriptionId) {
                    $fields[] = 'prescription_id';
                    $placeholders[] = '?';
                    $types .= 'i';
                    $values[] = (int)$prescriptionId;
                }
                
                if ($hasVaccineId) {
                    $fields[] = 'vaccine_id';
                    $placeholders[] = '?';
                    $types .= 'i';
                    $values[] = null;
                }
                
                $fields[] = 'vaccine_name';
                $placeholders[] = '?';
                $types .= 's';
                $values[] = $vac['vaccine_name'];
                
                if ($hasDoseNumber) {
                    $fields[] = 'dose_number';
                } elseif ($hasDose) {
                    $fields[] = 'dose';
                }
                $placeholders[] = '?';
                $types .= 's';
                $values[] = isset($vac['dose']) ? $vac['dose'] : '';
                
                $fields[] = 'date_given';
                $placeholders[] = '?';
                $types .= 's';
                $values[] = isset($vac['date_given']) && $vac['date_given'] !== '' ? $vac['date_given'] : null;
                
                if ($hasNextDueDate) {
                    $fields[] = 'next_due_date';
                } elseif ($hasNextDue) {
                    $fields[] = 'next_due';
                }
                $placeholders[] = '?';
                $types .= 's';
                $values[] = isset($vac['next_due']) && $vac['next_due'] !== '' ? $vac['next_due'] : null;
                
                $fields[] = 'batch_number';
                $placeholders[] = '?';
                $types .= 's';
                $values[] = isset($vac['batch_number']) ? $vac['batch_number'] : '';
                
                if ($hasInjectionSite) {
                    $fields[] = 'injection_site';
                } elseif ($hasSite) {
                    $fields[] = 'site';
                }
                $placeholders[] = '?';
                $types .= 's';
                $values[] = isset($vac['site']) ? $vac['site'] : '';
                
                $fields[] = 'administered_by';
                $placeholders[] = '?';
                $types .= 's';
                $values[] = isset($vac['administered_by']) ? $vac['administered_by'] : '';
                
                $fields[] = 'notes';
                $placeholders[] = '?';
                $types .= 's';
                $values[] = isset($vac['notes']) ? $vac['notes'] : '';
                
                $sql = "INSERT INTO patient_vaccinations (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
                $stmt = $db->prepare($sql);
                $stmt->bind_param($types, ...$values);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // ================================================================
    // EDIT - Show edit form
    // ================================================================
    public function edit($id) {
        $this->checkAuth();
        $this->checkPermission('edit_prescriptions');

        error_log("=== EDIT PRESCRIPTION ID: " . $id . " ===");
        
        $prescriptionData = $this->prescriptionModel->getPrintData($id);
        
        if (!$prescriptionData) {
            $this->setFlash('error', 'Prescription not found');
            $this->redirect('/prescriptions');
            return;
        }

        $treatmentHistory = $this->getTreatmentHistory($prescriptionData['patient_id'], $id);
        $prescriptionData['treatment_history'] = $treatmentHistory;

        $drugs = $this->getMedicines();
        $adviceTemplates = $this->getAdviceTemplates();
        $patient = $this->patientModel->find($prescriptionData['patient_id']);
        
        $vaccinations = $this->getPatientVaccinations($prescriptionData['patient_id'], $id);
        $labHistory = $this->getLabHistory($prescriptionData['patient_id']);
        

        // Inside edit() method, before $this->view()
        // Get all patient vaccinations for the history table
        $allPatientVaccinations = $this->getAllPatientVaccinations($prescriptionData['patient_id']);

        // Get lab history
        $labHistory = $this->getLabHistory($prescriptionData['patient_id']);

        $this->view('prescriptions/edit', [
            'title' => 'Edit Prescription',
            'prescription' => $prescriptionData,
            'patient' => $patient,
            'drugs' => $drugs,
            'adviceTemplates' => $adviceTemplates,
            'labHistory' => $labHistory,
            'vaccinations' => $prescriptionData['vaccinations'] ?? [],
            'allPatientVaccinations' => $allPatientVaccinations,
            'treatmentHistory' => $treatmentHistory
        ]);
    }

    // ================================================================
    // SHOW - View prescription - FIXED: Properly loads all updated data
    // ================================================================
    public function show($id) {
        $this->checkAuth();
        $this->checkPermission('view_prescriptions');

        // Fetch the latest prescription data
        $prescription = $this->prescriptionModel->getPrintData($id);
        if (!$prescription) {
            $this->setFlash('error', 'Prescription not found');
            $this->redirect('/prescriptions');
            return;
        }

        // Get treatment history for this prescription
        $treatmentHistory = $this->getTreatmentHistory($prescription['patient_id'], $id);

        // Get vaccinations for this prescription
        $vaccinations = $this->getPatientVaccinations($prescription['patient_id'], $id);
        $prescription['vaccinations'] = $vaccinations;

        // Get lab history for this patient
        $labHistory = $this->getLabHistory($prescription['patient_id']);

        // Get patient lab tests
        $patientLabTests = $this->getPatientLabTests($prescription['patient_id']);

        // Get patient vaccines
        $patientVaccines = $this->getPatientVaccinations($prescription['patient_id']);

        // Log for debugging
        error_log("=== SHOW PRESCRIPTION ID: " . $id . " ===");
        error_log("Items count: " . count($prescription['items'] ?? []));
        error_log("Treatment History count: " . count($treatmentHistory));
        error_log("Vaccinations count: " . count($vaccinations));
        error_log("Lab History tests: " . count($labHistory['test_names'] ?? []));
        error_log("Patient Lab Tests count: " . count($patientLabTests));
        error_log("Patient Vaccines count: " . count($patientVaccines));

        $this->view('prescriptions/view', [
            'title' => 'View Prescription',
            'prescription' => $prescription,
            'labHistory' => $labHistory,
            'vaccinations' => $vaccinations,
            'treatmentHistory' => $treatmentHistory,
            'patientLabTests' => $patientLabTests,
            'patientVaccines' => $patientVaccines
        ]);
    }

    // ================================================================
    // GET PATIENT LAB TESTS - NEW METHOD
    // ================================================================
    private function getPatientLabTests($patientId) {
        try {
            $sql = "SELECT 
                        lt.test_name,
                        lt.normal_range,
                        lt.unit,
                        lo.order_number,
                        loi.result_value,
                        loi.is_abnormal,
                        loi.status,
                        loi.result_entered_at as result_date,
                        lo.order_date as test_date
                    FROM lab_test_orders lo
                    JOIN lab_test_order_items loi ON lo.id = loi.order_id
                    JOIN lab_tests lt ON loi.test_id = lt.id
                    WHERE lo.patient_id = ? 
                    AND loi.status IN ('completed', 'reviewed')
                    AND loi.result_value IS NOT NULL
                    ORDER BY lo.order_date DESC, loi.id DESC
                    LIMIT 50";
            
            return $this->query($sql, [$patientId]);
        } catch (Exception $e) {
            error_log("getPatientLabTests error: " . $e->getMessage());
            return [];
        }
    }

    // ================================================================
    // PATIENT PRESCRIPTIONS
    // ================================================================
    public function patientPrescriptions($patientId) {
        $this->checkAuth();
        $this->checkPermission('view_prescriptions');
        $patient = $this->patientModel->find($patientId);
        if (!$patient) {
            $this->setFlash('error', 'Patient not found');
            $this->redirect('/patient/list');
            return;
        }
        $prescriptions = $this->prescriptionModel->getPatientPrescriptions($patientId);
        $this->view('prescriptions/patient-prescriptions', [
            'title' => 'Patient Prescriptions',
            'patient' => $patient,
            'prescriptions' => $prescriptions
        ]);
    }

    // ================================================================
    // PRINT METHODS
    // ================================================================
    public function printView($id) {
        $this->checkAuth();
        $this->checkPermission('view_prescriptions');
        
        // Fetch the latest prescription data
        $prescription = $this->prescriptionModel->getPrintData($id);
        
        if (!$prescription) {
            $this->setFlash('error', 'Prescription not found');
            $this->redirect('/prescriptions');
            return;
        }
        
        $treatmentHistory = $this->getTreatmentHistory($prescription['patient_id'], $id);
        
        $doctorId = $prescription['doctor_id'] ?? 0;
        if ($doctorId > 0) {
            $sql = "SELECT doctor_info_en, doctor_info_bn FROM doctors WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $doctorId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $prescription['doctor_info_en'] = $row['doctor_info_en'] ?? '';
                $prescription['doctor_info_bn'] = $row['doctor_info_bn'] ?? '';
            }
        }
        
        if (empty($prescription['doctor_info_en'])) {
            $sql = "SELECT d.*, u.first_name, u.last_name, u.title 
                    FROM doctors d 
                    JOIN users u ON d.user_id = u.id 
                    WHERE d.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $doctorId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $prescription['doctor_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $prescription['doctor_title'] = $row['title'] ?? 'Dr.';
                $prescription['qualification'] = $row['qualification'] ?? '';
                $prescription['specialization'] = $row['specialization'] ?? '';
                $prescription['bmdc_number'] = $row['bmdc_number'] ?? '';
                $prescription['designation'] = $row['designation'] ?? '';
                $prescription['department'] = $row['department'] ?? '';
                $prescription['chamber_address'] = $row['chamber_address'] ?? '';
            }
        }
        
        error_log("=== PRINT VIEW DEBUG ===");
        error_log("Treatment History count: " . count($treatmentHistory));
        error_log("Items count: " . count($prescription['items'] ?? []));
        error_log("=========================");
        
        $this->prescriptionModel->update($id, [
            'printed_count' => ($prescription['printed_count'] ?? 0) + 1,
            'last_printed_at' => date('Y-m-d H:i:s')
        ]);
        
        $viewFile = BASE_PATH . '/app/views/prescriptions/print.php';
        if (file_exists($viewFile)) {
            extract(['prescription' => $prescription, 'treatmentHistory' => $treatmentHistory]);
            ob_start();
            include $viewFile;
            $content = ob_get_clean();
            echo $content;
            exit;
        } else {
            echo "View file not found";
        }
    }

    public function printPad($id) {
        $this->checkAuth();
        $this->checkPermission('view_prescriptions');
        
        // Fetch the latest prescription data
        $prescription = $this->prescriptionModel->getPrintData($id);
        
        if (!$prescription) {
            $this->setFlash('error', 'Prescription not found');
            $this->redirect('/prescriptions');
            return;
        }
        
        $treatmentHistory = $this->getTreatmentHistory($prescription['patient_id'], $id);
        
        $doctorId = $prescription['doctor_id'] ?? 0;
        if ($doctorId > 0 && $this->db) {
            $sql = "SELECT d.*, u.first_name, u.last_name, u.title 
                    FROM doctors d 
                    JOIN users u ON d.user_id = u.id 
                    WHERE d.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $doctorId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $prescription['doctor_info_en'] = $row['doctor_info_en'] ?? '';
                $prescription['doctor_info_bn'] = $row['doctor_info_bn'] ?? '';
                $prescription['doctor_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $prescription['doctor_title'] = $row['title'] ?? 'Dr.';
                $prescription['qualification'] = $row['qualification'] ?? '';
                $prescription['specialization'] = $row['specialization'] ?? '';
                $prescription['bmdc_number'] = $row['bmdc_number'] ?? '';
                $prescription['designation'] = $row['designation'] ?? '';
                $prescription['department'] = $row['department'] ?? '';
                $prescription['chamber_address'] = $row['chamber_address'] ?? '';
                $prescription['chamber_name'] = $row['chamber_name'] ?? '';
                $prescription['chamber_phone'] = $row['chamber_phone'] ?? '';
                $prescription['chamber_time'] = $row['chamber_time'] ?? '';
                $prescription['signature_path'] = $row['signature_path'] ?? '';
            }
        }
        
        error_log("=== PRINT PAD DEBUG ===");
        error_log("Treatment History count: " . count($treatmentHistory));
        error_log("Items count: " . count($prescription['items'] ?? []));
        error_log("=========================");
        
        $viewFile = BASE_PATH . '/app/views/prescriptions/print-pad.php';
        if (file_exists($viewFile)) {
            extract(['prescription' => $prescription, 'treatmentHistory' => $treatmentHistory]);
            ob_start();
            include $viewFile;
            $content = ob_get_clean();
            echo $content;
            exit;
        } else {
            echo "View file not found";
        }
    }

    // ================================================================
    // DUPLICATE PRESCRIPTION
    // ================================================================
    public function duplicate($id) {
        $this->checkAuth();
        $this->checkPermission('create_prescriptions');
        
        $prescription = $this->prescriptionModel->getPrintData($id);
        if (!$prescription) {
            $this->setFlash('error', 'Prescription not found');
            $this->redirect('/prescriptions');
            return;
        }
        
        $patientId = $prescription['patient_id'];
        $appointmentId = $prescription['appointment_id'] ?? 0;
        
        $this->redirect('/prescriptions/create?patient_id=' . $patientId . '&appointment_id=' . $appointmentId . '&duplicate_of=' . $id);
    }

    // ================================================================
    // DELETE PRESCRIPTION
    // ================================================================
    public function delete($id) {
        $this->checkAuth();
        $this->checkPermission('delete_prescriptions');
        
        $prescription = $this->prescriptionModel->find($id);
        if (!$prescription) {
            $this->setFlash('error', 'Prescription not found');
            $this->redirect('/prescriptions');
            return;
        }
        
        try {
            $db = $this->db;
            $db->begin_transaction();
            
            $tables = [
                'prescription_chief_complaints',
                'prescription_drug_history',
                'prescription_disease_history',
                'prescription_investigations',
                'prescription_physical_examination',
                'prescription_vital_signs',
                'prescription_advice',
                'prescription_lab_tests',
                'prescription_items',
                'prescription_medicine_details',
                'patient_vaccinations',
                'treatment_history'
            ];
            
            foreach ($tables as $table) {
                $db->query("DELETE FROM {$table} WHERE prescription_id = " . (int)$id);
            }
            
            $db->query("DELETE FROM prescriptions WHERE id = " . (int)$id);
            
            $db->commit();
            $this->setFlash('success', 'Prescription deleted successfully');
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            $this->setFlash('error', 'Error deleting prescription: ' . $e->getMessage());
        }
        
        $this->redirect('/prescriptions');
    }

    // ================================================================
    // CANCEL PRESCRIPTION
    // ================================================================
    public function cancel($id) {
        $this->checkAuth();
        $this->checkPermission('edit_prescriptions');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/prescriptions');
            return;
        }
        
        $reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : 'No reason provided';
        
        try {
            $db = $this->db;
            $db->begin_transaction();
            
            $sql = "UPDATE prescriptions SET 
                        status = 'canceled',
                        cancellation_reason = ?,
                        cancelled_at = NOW(),
                        cancelled_by = ?
                    WHERE id = ?";
            
            $stmt = $db->prepare($sql);
            $userId = $_SESSION['user_id'] ?? 0;
            $stmt->bind_param('sii', $reason, $userId, $id);
            $stmt->execute();
            
            $prescription = $this->prescriptionModel->find($id);
            if ($prescription && !empty($prescription['appointment_id'])) {
                $db->query("UPDATE appointments SET status = 'canceled' WHERE id = " . (int)$prescription['appointment_id']);
            }
            
            $db->commit();
            $this->setFlash('success', 'Prescription canceled successfully');
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            $this->setFlash('error', 'Error canceling prescription: ' . $e->getMessage());
        }
        
        $this->redirect('/prescriptions/show/' . $id);
    }

    // ================================================================
    // EXPORT PDF
    // ================================================================
    public function exportPdf($id) {
        $this->checkAuth();
        $this->checkPermission('view_prescriptions');
        
        $prescription = $this->prescriptionModel->getPrintData($id);
        if (!$prescription) {
            $this->setFlash('error', 'Prescription not found');
            $this->redirect('/prescriptions');
            return;
        }
        
        $this->redirect('/prescriptions/print/' . $id);
    }

    // ================================================================
    // API METHODS
    // ================================================================
    public function apiMedicines() {
        header('Content-Type: application/json');
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        if (strlen($query) < 1) {
            echo json_encode([]);
            exit;
        }

        try {
            if (!$this->db) {
                echo json_encode([]);
                exit;
            }

            $term = mysqli_real_escape_string($this->db, $query);
            $sql = "SELECT id, medicine_code, medicine_name, generic_name, strength, dosage_form, selling_price
                    FROM medicines WHERE status='active'
                    AND (medicine_name LIKE '%$term%' OR generic_name LIKE '%$term%' OR medicine_code LIKE '%$term%')
                    ORDER BY medicine_name ASC LIMIT 20";
            $result = $this->db->query($sql);

            $medicines = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $name = $row['medicine_name'];
                    if ($row['strength']) $name .= ' ' . $row['strength'];
                    $medicines[] = [
                        'id' => (int)$row['id'],
                        'medicine_code' => $row['medicine_code'] ?? '',
                        'name' => $name,
                        'display_name' => $name,
                        'generic_name' => $row['generic_name'] ?? '',
                        'strength' => $row['strength'] ?? '',
                        'dosage_form' => $row['dosage_form'] ?? '',
                        'price' => (float)($row['selling_price'] ?? 0)
                    ];
                }
            }
            echo json_encode($medicines);
        } catch (Exception $e) {
            echo json_encode([]);
        }
        exit;
    }

    // ================================================================
    // API: GET DROPDOWN OPTIONS
    // ================================================================
    public function apiDropdownOptions() {
        header('Content-Type: application/json');
        
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        if (empty($type)) {
            http_response_code(400);
            echo json_encode(['error' => 'Type parameter required']);
            exit;
        }
        
        $validTypes = ['food_relation', 'frequency', 'duration', 'instruction', 'dosage_form'];
        if (!in_array($type, $validTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid type']);
            exit;
        }
        
        try {
            $options = $this->prescriptionModel->getDropdownOptions($type);
            echo json_encode($options);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // API: GET ALL DROPDOWN OPTIONS
    // ================================================================
    public function apiAllDropdownOptions() {
        header('Content-Type: application/json');
        
        try {
            $options = $this->prescriptionModel->getAllDropdownOptions();
            
            $grouped = [];
            foreach ($options as $opt) {
                $type = $opt['option_type'];
                if (!isset($grouped[$type])) {
                    $grouped[$type] = [];
                }
                $grouped[$type][] = $opt;
            }
            
            echo json_encode(['success' => true, 'data' => $grouped]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // API: GET PATIENT PRESCRIPTIONS
    // ================================================================
    public function apiGetPatientPrescriptions() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        
        if ($patientId == 0) {
            echo json_encode(['success' => false, 'error' => 'Patient ID required']);
            exit;
        }
        
        try {
            $sql = "SELECT p.*,
                           CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                           u.title as doctor_title,
                           d.specialization,
                           COUNT(pi.id) as item_count
                    FROM prescriptions p
                    JOIN doctors d ON p.doctor_id = d.id
                    JOIN users u ON d.user_id = u.id
                    LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
                    WHERE p.patient_id = ?
                    AND p.status NOT IN ('draft')
                    GROUP BY p.id
                    ORDER BY p.prescription_date DESC";
            
            $prescriptions = $this->query($sql, [$patientId]);
            
            $data = [];
            foreach ($prescriptions as $p) {
                $data[] = [
                    'id' => $p['id'],
                    'prescription_number' => $p['prescription_number'],
                    'prescription_date' => date('d/m/Y', strtotime($p['prescription_date'])),
                    'doctor_name' => $p['doctor_name'] ?? 'N/A',
                    'status' => $p['status'],
                    'pharmacy_status' => $p['pharmacy_status'] ?? 'pending',
                    'item_count' => $p['item_count'] ?? 0
                ];
            }
            
            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            error_log("API get patient prescriptions error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // API: GET PATIENT VACCINES
    // ================================================================
    public function apiGetPatientVaccines() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        
        if ($patientId == 0) {
            echo json_encode(['success' => false, 'error' => 'Patient ID required']);
            exit;
        }
        
        try {
            $vaccinations = $this->getPatientVaccinations($patientId);
            echo json_encode(['success' => true, 'data' => $vaccinations]);
        } catch (Exception $e) {
            error_log("API get patient vaccines error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // API: GET PATIENT APPOINTMENTS
    // ================================================================
    public function apiGetPatientAppointments() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        
        if ($patientId == 0) {
            echo json_encode(['success' => false, 'error' => 'Patient ID required']);
            exit;
        }
        
        try {
            $sql = "SELECT a.*,
                           CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                    FROM appointments a
                    LEFT JOIN doctors d ON a.doctor_id = d.id
                    LEFT JOIN users u ON d.user_id = u.id
                    WHERE a.patient_id = ?
                    ORDER BY a.appointment_date DESC
                    LIMIT 20";
            
            $appointments = $this->query($sql, [$patientId]);
            echo json_encode(['success' => true, 'data' => $appointments]);
        } catch (Exception $e) {
            error_log("API get patient appointments error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // GET PREVIOUS PRESCRIPTIONS
    // ================================================================
    public function getPreviousPrescriptions() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        if (!$patientId) {
            echo json_encode(['success' => false, 'error' => 'Invalid patient']);
            exit;
        }

        try {
            if (!$this->db) {
                echo json_encode(['success' => false, 'error' => 'DB connection failed']);
                exit;
            }
            
            $sql = "SELECT p.*, 
                           CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                           u.title as doctor_title,
                           d.specialization,
                           COUNT(pi.id) as item_count
                    FROM prescriptions p
                    LEFT JOIN doctors d ON p.doctor_id = d.id
                    LEFT JOIN users u ON d.user_id = u.id
                    LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
                    WHERE p.patient_id = ?
                    AND p.status NOT IN ('draft')
                    GROUP BY p.id
                    ORDER BY p.prescription_date DESC, p.id DESC
                    LIMIT 20";
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'error' => 'DB error: ' . $this->db->error]);
                exit;
            }
            $stmt->bind_param('i', $patientId);
            $stmt->execute();
            $rows = $stmt->get_result();
            $prescriptions = [];
            
            while ($row = $rows->fetch_assoc()) {
                $row['chief_complaints'] = $this->query("SELECT * FROM prescription_chief_complaints WHERE prescription_id = ?", [$row['id']]);
                $row['drug_history'] = $this->query("SELECT * FROM prescription_drug_history WHERE prescription_id = ?", [$row['id']]);
                $row['disease_history'] = $this->query("SELECT * FROM prescription_disease_history WHERE prescription_id = ?", [$row['id']]);
                $row['investigations'] = $this->query("SELECT * FROM prescription_investigations WHERE prescription_id = ?", [$row['id']]);
                $row['physical_exam'] = $this->queryOne("SELECT * FROM prescription_physical_examination WHERE prescription_id = ?", [$row['id']]) ?: [];
                $row['vital_signs'] = $this->queryOne("SELECT * FROM prescription_vital_signs WHERE prescription_id = ?", [$row['id']]) ?: [];
                $row['advice'] = $this->queryOne("SELECT * FROM prescription_advice WHERE prescription_id = ?", [$row['id']]) ?: [];
                
                $medicines = $this->query(
                    "SELECT pi.*, 
                            (SELECT GROUP_CONCAT(CONCAT(frequency, '|', duration, '|', instruction) SEPARATOR '||') 
                             FROM prescription_medicine_details 
                             WHERE prescription_item_id = pi.id) as details_raw
                     FROM prescription_items pi 
                     WHERE pi.prescription_id = ? 
                     ORDER BY pi.id ASC", 
                    [$row['id']]
                );
                
                $row['medicines'] = [];
                foreach ($medicines as $med) {
                    $details = [];
                    if (!empty($med['details_raw'])) {
                        $detailParts = explode('||', $med['details_raw']);
                        foreach ($detailParts as $part) {
                            if (strpos($part, '|') !== false) {
                                list($freq, $dur, $inst) = explode('|', $part);
                                $details[] = ['frequency' => $freq, 'duration' => $dur, 'instruction' => $inst];
                            }
                        }
                    }
                    $row['medicines'][] = [
                        'drug_id' => $med['drug_id'],
                        'drug_name' => $med['drug_name'],
                        'dosage' => $med['dosage'],
                        'relation_to_food' => $med['route'] ?? '',
                        'quantity' => $med['quantity'],
                        'refills' => $med['refills'],
                        'details' => $details
                    ];
                }
                
                $row['lab_tests'] = $this->query("SELECT * FROM prescription_lab_tests WHERE prescription_id = ?", [$row['id']]);
                $row['vaccinations'] = $this->query("SELECT * FROM patient_vaccinations WHERE prescription_id = ?", [$row['id']]);
                $row['treatment_history'] = $this->query("SELECT * FROM treatment_history WHERE prescription_id = ? ORDER BY id ASC", [$row['id']]);
                
                $prescriptions[] = [
                    'id' => $row['id'],
                    'prescription_number' => $row['prescription_number'],
                    'prescription_date' => $row['prescription_date'],
                    'doctor_name' => $row['doctor_name'] ?? 'N/A',
                    'doctor_title' => $row['doctor_title'] ?? 'Dr.',
                    'specialization' => $row['specialization'] ?? '',
                    'status' => $row['status'] ?? 'draft',
                    'pharmacy_status' => $row['pharmacy_status'] ?? 'pending',
                    'item_count' => $row['item_count'] ?? 0,
                    'data' => $row
                ];
            }
            
            $stmt->close();
            echo json_encode(['success' => true, 'data' => $prescriptions]);
            
        } catch (Exception $e) {
            error_log("getPreviousPrescriptions error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // SAVE TAB DATA
    // ================================================================
    public function saveTab() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Invalid request method']);
            exit;
        }

        $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
        $tab = isset($_POST['tab']) ? $_POST['tab'] : 'tab1';
        $prescriptionId = isset($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : 0;

        if (!$patientId) {
            echo json_encode(['success' => false, 'error' => 'Patient ID required']);
            exit;
        }

        try {
            $db = $this->db;
            if (!$db) {
                echo json_encode(['success' => false, 'error' => 'Database connection not available']);
                exit;
            }
            
            if ($prescriptionId == 0) {
                $doctorId = $this->getDoctorId();
                if ($doctorId == 0) {
                    $firstDoctor = $this->doctorModel->find(1);
                    $doctorId = $firstDoctor['id'] ?? 1;
                }
                
                $prescriptionNumber = 'PRX' . date('Ymd') . rand(1000, 9999) . rand(10, 99);
                $sql = "INSERT INTO prescriptions (
                            prescription_number, patient_id, doctor_id,
                            prescription_date, visit_number, status, pharmacy_status
                        ) VALUES (?, ?, ?, ?, ?, 'draft', 'pending')";
                $stmt = $db->prepare($sql);
                $prescriptionDate = date('Y-m-d');
                $visitNumber = $this->prescriptionModel->getVisitNumber($patientId);
                $stmt->bind_param('siisi', $prescriptionNumber, $patientId, $doctorId, $prescriptionDate, $visitNumber);
                $stmt->execute();
                $prescriptionId = $db->insert_id;
                error_log("Created draft prescription via saveTab: " . $prescriptionId);
            }

            echo json_encode(['success' => true, 'prescription_id' => $prescriptionId]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // LOAD TAB DATA
    // ================================================================
    public function loadTabData() {
        header('Content-Type: application/json');
        
        $prescriptionId = isset($_GET['prescription_id']) ? (int)$_GET['prescription_id'] : 0;
        $tab = isset($_GET['tab']) ? $_GET['tab'] : 'tab1';

        if (!$prescriptionId) {
            echo json_encode(['success' => false, 'error' => 'Prescription ID required']);
            exit;
        }

        try {
            if (!$this->db) {
                echo json_encode(['success' => false, 'error' => 'DB connection failed']);
                exit;
            }
            
            $data = [];
            $prescription = $this->prescriptionModel->getPrintData($prescriptionId);
            if (!$prescription) {
                echo json_encode(['success' => false, 'error' => 'Prescription not found']);
                exit;
            }

            switch($tab) {
                case 'tab1':
                    $data = [
                        'marital_status' => isset($prescription['marital_status']) ? $prescription['marital_status'] : '',
                        'occupation' => isset($prescription['occupation']) ? $prescription['occupation'] : '',
                        'special_note' => isset($prescription['special_note']) ? $prescription['special_note'] : '',
                        'complaints' => isset($prescription['chief_complaints']) ? $prescription['chief_complaints'] : [],
                        'drug_history' => isset($prescription['drug_history']) ? $prescription['drug_history'] : [],
                        'disease_history' => isset($prescription['disease_history']) ? $prescription['disease_history'] : [],
                        'treatment_history' => isset($prescription['treatment_history']) ? $prescription['treatment_history'] : []
                    ];
                    break;
                    
                case 'tab2':
                    $data = [
                        'physical_exam' => isset($prescription['physical_exam']) ? $prescription['physical_exam'] : [],
                        'vital_signs' => isset($prescription['vital_signs']) ? $prescription['vital_signs'] : [],
                        'investigations' => isset($prescription['investigations']) ? $prescription['investigations'] : [],
                        'lab_tests' => isset($prescription['lab_tests']) ? $prescription['lab_tests'] : [],
                        'diagnosis' => isset($prescription['diagnosis']) ? $prescription['diagnosis'] : ''
                    ];
                    break;
                    
                case 'tab3':
                    $items = isset($prescription['items']) ? $prescription['items'] : [];
                    $medicines = [];
                    foreach ($items as $item) {
                        $medicines[] = [
                            'drug_id' => isset($item['drug_id']) ? $item['drug_id'] : 0,
                            'drug_name' => isset($item['drug_name']) ? $item['drug_name'] : '',
                            'dosage' => isset($item['dosage']) ? $item['dosage'] : '',
                            'relation_to_food' => isset($item['relation_to_food']) ? $item['relation_to_food'] : '',
                            'quantity' => isset($item['quantity']) ? $item['quantity'] : 1,
                            'refills' => isset($item['refills']) ? $item['refills'] : 0,
                            'details' => isset($item['details']) ? $item['details'] : []
                        ];
                    }
                    $data = ['medicines' => $medicines];
                    break;
                    
                case 'tab4':
                    $data = ['advice' => isset($prescription['advice']) ? $prescription['advice'] : []];
                    break;
                    
                case 'tab5':
                    $data = [
                        'vaccinations' => isset($prescription['vaccinations']) ? $prescription['vaccinations'] : [],
                        'labHistory' => $this->getLabHistory($prescription['patient_id'])
                    ];
                    break;
            }

            echo json_encode(['success' => true, 'data' => $data]);
            
        } catch (Exception $e) {
            error_log("loadTabData error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // HELPER METHODS
    // ================================================================
    
    private function getMedicines() {
        try {
            if (!$this->db) return [];
            $result = $this->db->query("SELECT id, medicine_code, medicine_name, generic_name, strength, dosage_form, selling_price
                                  FROM medicines WHERE status = 'active' ORDER BY medicine_name ASC");
            $medicines = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $medicines[] = $row;
                }
            }
            return $medicines;
        } catch (Exception $e) {
            error_log("getMedicines: " . $e->getMessage());
            return [];
        }
    }

    private function getAllPatients() {
        try {
            if (!$this->db) return [];
            $result = $this->db->query("SELECT id, patient_code, first_name, last_name, full_name, phone FROM patients WHERE status = 'active' ORDER BY first_name ASC");
            $patients = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $patients[] = $row;
                }
            }
            return $patients;
        } catch (Exception $e) {
            error_log("getAllPatients: " . $e->getMessage());
            return [];
        }
    }

    private function getActiveDoctors() {
        try {
            if (!$this->db) return [];
            $query = "SELECT d.id, d.user_id, d.specialization, d.consultation_fee,
                             u.first_name, u.last_name, u.title
                      FROM doctors d
                      JOIN users u ON d.user_id = u.id
                      WHERE d.status = 'active' AND u.status = 'active'
                      ORDER BY u.first_name ASC";
            $result = $this->db->query($query);
            $doctors = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $doctors[] = $row;
                }
            }
            return $doctors;
        } catch (Exception $e) {
            error_log("getActiveDoctors: " . $e->getMessage());
            return [];
        }
    }

    private function getAdviceTemplates() {
        return $this->query("SELECT * FROM advice_templates ORDER BY category, title");
    }

    private function getDoctorId() {
        if (!empty($_SESSION['doctor_id']) && $_SESSION['doctor_id'] > 0) {
            return (int)$_SESSION['doctor_id'];
        }
        if (!empty($_SESSION['user_id'])) {
            try {
                $r = $this->queryOne("SELECT id FROM doctors WHERE user_id = ? AND status = 'active'", [$_SESSION['user_id']]);
                if ($r) {
                    $_SESSION['doctor_id'] = $r['id'];
                    return (int)$r['id'];
                }
            } catch (Exception $e) {}
        }
        try {
            $r = $this->queryOne("SELECT id FROM doctors WHERE status = 'active' LIMIT 1");
            if ($r) {
                $_SESSION['doctor_id'] = $r['id'];
                return (int)$r['id'];
            }
        } catch (Exception $e) {}
        return 0;
    }

    private function getDoctorByAppointment($appointmentId) {
        try {
            if (!$this->db) return null;
            $sql = "SELECT d.*, u.first_name, u.last_name, u.title, u.email, u.phone
                    FROM appointments a
                    JOIN doctors d ON a.doctor_id = d.id
                    JOIN users u ON d.user_id = u.id
                    WHERE a.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $appointmentId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        } catch (Exception $e) {
            error_log("getDoctorByAppointment error: " . $e->getMessage());
        }
        return null;
    }

    private function getDoctorById($doctorId) {
        if (!$doctorId || $doctorId == 0) return null;
        try {
            if (!$this->db) return null;
            $sql = "SELECT d.*, u.first_name, u.last_name, u.title, u.email, u.phone
                    FROM doctors d
                    JOIN users u ON d.user_id = u.id
                    WHERE d.id = ? AND d.status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $doctorId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        } catch (Exception $e) {
            error_log("getDoctorById error: " . $e->getMessage());
        }
        return null;
    }

    private function getFirstActiveDoctor() {
        try {
            if (!$this->db) return null;
            $sql = "SELECT d.*, u.first_name, u.last_name, u.title, u.email, u.phone
                    FROM doctors d
                    JOIN users u ON d.user_id = u.id
                    WHERE d.status = 'active' AND u.status = 'active'
                    ORDER BY u.first_name ASC
                    LIMIT 1";
            $result = $this->db->query($sql);
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
        } catch (Exception $e) {
            error_log("getFirstActiveDoctor error: " . $e->getMessage());
        }
        return null;
    }

    private function getLabHistory($patientId) {
        $testNamesQuery = "SELECT DISTINCT t.test_name 
                           FROM lab_tests t
                           JOIN lab_test_order_items i ON i.test_id = t.id
                           JOIN lab_test_orders o ON i.order_id = o.id
                           WHERE o.patient_id = ? AND i.status = 'completed' AND i.result_value IS NOT NULL
                           UNION
                           SELECT DISTINCT test_name 
                           FROM manual_lab_results 
                           WHERE patient_id = ? AND result_value IS NOT NULL AND result_value != ''
                           ORDER BY test_name ASC";
        
        $testNames = $this->query($testNamesQuery, [$patientId, $patientId]);
        $testNames = array_column($testNames, 'test_name');

        if (empty($testNames)) {
            return ['test_names' => [], 'results' => []];
        }

        $query = "SELECT 
                    DATE(o.order_date) as test_date,
                    DATE_FORMAT(o.order_date, '%d-%m-%Y') as formatted_date,
                    o.order_number,
                    t.test_name,
                    i.result_value,
                    i.is_abnormal,
                    t.unit,
                    'lab_order' as source
                  FROM lab_test_orders o
                  JOIN lab_test_order_items i ON o.id = i.order_id
                  JOIN lab_tests t ON i.test_id = t.id
                  WHERE o.patient_id = ? 
                    AND i.status = 'completed'
                    AND i.result_value IS NOT NULL
                    AND i.result_value != ''
                  UNION
                  SELECT 
                    DATE(m.report_date) as test_date,
                    DATE_FORMAT(m.report_date, '%d-%m-%Y') as formatted_date,
                    'Manual' as order_number,
                    m.test_name,
                    m.result_value,
                    m.is_abnormal,
                    m.unit,
                    'manual' as source
                  FROM manual_lab_results m
                  WHERE m.patient_id = ? 
                    AND m.result_value IS NOT NULL
                    AND m.result_value != ''
                  ORDER BY test_date DESC, test_name ASC";
        
        $rows = $this->query($query, [$patientId, $patientId]);

        $results = [];
        foreach ($rows as $row) {
            $dateKey = $row['test_date'];
            if (!isset($results[$dateKey])) {
                $results[$dateKey] = [
                    'date' => $row['formatted_date'],
                    'order_number' => $row['order_number'],
                    'source' => $row['source'],
                    'tests' => []
                ];
            }
            $results[$dateKey]['tests'][$row['test_name']] = [
                'value' => $row['result_value'],
                'is_abnormal' => $row['is_abnormal'],
                'unit' => $row['unit']
            ];
        }

        return [
            'test_names' => $testNames,
            'results' => $results
        ];
    }

    private function getPatientVaccinations($patientId, $prescriptionId = null) {
        error_log("=== getPatientVaccinations called ===");
        error_log("Patient ID: " . $patientId);
        error_log("Prescription ID: " . ($prescriptionId ?? 'null'));
        
        if (file_exists(BASE_PATH . '/app/models/Vaccine.php')) {
            require_once BASE_PATH . '/app/models/Vaccine.php';
            $vaccineModel = new Vaccine();
            return $vaccineModel->getForPrescription($patientId, $prescriptionId);
        }
        
        $sql = "SELECT 
                    id, 
                    patient_id, 
                    prescription_id,
                    vaccine_id,
                    vaccine_name,
                    dose,
                    date_given,
                    next_due,
                    batch_number,
                    site,
                    administered_by,
                    notes,
                    vaccine_image,
                    created_at,
                    updated_at 
                FROM patient_vaccinations 
                WHERE patient_id = ? AND is_active = 1";
        $params = [$patientId];
        
        if ($prescriptionId !== null && $prescriptionId > 0) {
            $sql .= " AND prescription_id = ?";
            $params[] = $prescriptionId;
        }
        
        $sql .= " ORDER BY date_given DESC, id DESC";
        
        return $this->query($sql, $params);
    }

    private function getTreatmentHistory($patientId, $prescriptionId = null) {
        try {
            if (!$this->db) return [];
            
            if (!$this->tableExists('treatment_history')) {
                return [];
            }
            
            $sql = "SELECT id, patient_id, prescription_id, treatment_name, duration, remarks, created_at 
                    FROM treatment_history 
                    WHERE patient_id = ?";
            $params = [$patientId];
            
            if ($prescriptionId !== null && $prescriptionId > 0) {
                $sql .= " AND prescription_id = ?";
                $params[] = $prescriptionId;
            }
            
            $sql .= " ORDER BY created_at DESC, id DESC";
            
            return $this->query($sql, $params);
        } catch (Exception $e) {
            error_log("getTreatmentHistory error: " . $e->getMessage());
            return [];
        }
    }

    // ================================================================
    // DATABASE HELPERS
    // ================================================================
    
    protected function query($sql, $params = []) {
        try {
            if (!$this->db) return [];
            
            if (empty($params)) {
                $result = $this->db->query($sql);
                if ($result === false) {
                    error_log("Query failed: " . $this->db->error . " - SQL: " . $sql);
                    return [];
                }
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                return $rows;
            }
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->error . " - SQL: " . $sql);
                return [];
            }
            
            $types = '';
            $bindParams = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param) || is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bindParams[] = $param;
            }
            
            if (!empty($types)) {
                $stmt->bind_param($types, ...$bindParams);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            $stmt->close();
            return $rows;
        } catch (Exception $e) {
            error_log("Query error: " . $e->getMessage() . " - SQL: " . $sql);
            return [];
        }
    }

    protected function queryOne($sql, $params = []) {
        $rows = $this->query($sql, $params);
        return !empty($rows) ? $rows[0] : null;
    }

    protected function setFlash($type, $message) {
        if ($type === 'success') {
            $_SESSION['success'] = $message;
        } else {
            $_SESSION['errors'][] = $message;
        }
    }

    private function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    private function tableExists($tableName) {
        try {
            if (!$this->db) return false;
            $result = $this->db->query("SHOW TABLES LIKE '$tableName'");
            return $result && $result->num_rows > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    // ================================================================
    // GET ALL PATIENT VACCINATIONS (FOR HISTORY DISPLAY)
    // ================================================================
    private function getAllPatientVaccinations($patientId) {
        try {
            if (!$this->db) return [];
            
            $sql = "SELECT 
                        id, 
                        patient_id, 
                        prescription_id,
                        vaccine_id,
                        vaccine_name,
                        dose,
                        date_given,
                        next_due,
                        batch_number,
                        site,
                        administered_by,
                        notes,
                        vaccine_image,
                        created_at,
                        updated_at 
                    FROM patient_vaccinations 
                    WHERE patient_id = ? AND is_active = 1
                    ORDER BY date_given DESC, id DESC";
            
            return $this->query($sql, [$patientId]);
        } catch (Exception $e) {
            error_log("getAllPatientVaccinations error: " . $e->getMessage());
            return [];
        }
    }
}
?>