<?php
// app/controllers/PatientController.php
require_once BASE_PATH . '/core/Controller.php';
require_once __DIR__ . '/../models/Patient.php';

class PatientController extends Controller {
    
    // ==================== PATIENT LISTING ====================
    public function index() {
        $this->checkAuth();
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $gender = isset($_GET['gender']) ? trim($_GET['gender']) : '';
        $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Escape search values
        $searchEsc = $this->db->real_escape_string($search);
        $genderEsc = $this->db->real_escape_string($gender);
        $dateFromEsc = $this->db->real_escape_string($dateFrom);
        
        // Build WHERE clause
        $where = "WHERE status='active'";
        if (!empty($searchEsc)) {
            $where .= " AND (first_name LIKE '%$searchEsc%' 
                             OR last_name LIKE '%$searchEsc%' 
                             OR phone LIKE '%$searchEsc%' 
                             OR patient_code LIKE '%$searchEsc%'
                             OR full_name LIKE '%$searchEsc%')";
        }
        if (!empty($genderEsc)) {
            $where .= " AND gender = '$genderEsc'";
        }
        if (!empty($dateFromEsc)) {
            $where .= " AND DATE(created_at) >= '$dateFromEsc'";
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM patients $where";
        $countResult = $this->db->query($countQuery);
        $totalPatients = $countResult ? $countResult->fetch_assoc()['total'] : 0;
        $totalPages = ceil($totalPatients / $limit);
        
        // Get patients with LIMIT
        $query = "SELECT * FROM patients $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
        $result = $this->db->query($query);
        $patients = [];
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
        
        $content = $this->renderView('patients/list', [
            'patients' => $patients,
            'search' => $search,
            'gender' => $gender,
            'dateFrom' => $dateFrom,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalPatients' => $totalPatients,
            'offset' => $offset,
            'limit' => $limit
        ]);
        $this->renderLayout('Patient Management', $content);
    }

    // ==================== PATIENT LIST ====================
    public function list() {
        $this->checkAuth();
        
        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $gender = isset($_GET['gender']) ? trim($_GET['gender']) : '';
        $dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Escape search values
        $searchEsc = $this->db->real_escape_string($search);
        $genderEsc = $this->db->real_escape_string($gender);
        $dateFromEsc = $this->db->real_escape_string($dateFrom);
        
        // Build WHERE clause
        $where = "WHERE status='active'";
        if (!empty($searchEsc)) {
            $where .= " AND (first_name LIKE '%$searchEsc%' 
                             OR last_name LIKE '%$searchEsc%' 
                             OR phone LIKE '%$searchEsc%' 
                             OR patient_code LIKE '%$searchEsc%'
                             OR full_name LIKE '%$searchEsc%')";
        }
        if (!empty($genderEsc)) {
            $where .= " AND gender = '$genderEsc'";
        }
        if (!empty($dateFromEsc)) {
            $where .= " AND DATE(created_at) >= '$dateFromEsc'";
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM patients $where";
        $countResult = $this->db->query($countQuery);
        $totalPatients = $countResult ? $countResult->fetch_assoc()['total'] : 0;
        $totalPages = ceil($totalPatients / $limit);
        
        // Ensure page doesn't exceed total pages
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * $limit;
        }
        
        // Get patients with LIMIT and OFFSET
        $query = "SELECT * FROM patients $where ORDER BY id DESC LIMIT $limit OFFSET $offset";
        
        // Debug - log the query
        error_log("=== PATIENT LIST QUERY ===");
        error_log("Query: " . $query);
        error_log("Page: $page, Limit: $limit, Offset: $offset");
        error_log("Total Patients: $totalPatients");
        error_log("===========================");
        
        $result = $this->db->query($query);
        $patients = [];
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
        
        error_log("Patients returned: " . count($patients));
        
        $this->view('patients/list', [
            'patients' => $patients,
            'totalPatients' => $totalPatients,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'gender' => $gender,
            'dateFrom' => $dateFrom,
            'offset' => $offset,
            'limit' => $limit
        ], 'Patient List');
    }
    
    // ==================== REGISTER PATIENT ====================
    public function register() {
        $this->checkAuth();
        $divisions = Patient::getDivisions();
        $content = $this->renderView('patients/register', ['divisions' => $divisions]);
        $this->renderLayout('Register Patient', $content);
    }
    
    public function store() {
        $this->checkAuth();
        
        try {
            $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
            $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
            $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
            $email = isset($_POST['email']) ? trim($_POST['email']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
            $bloodGroup = isset($_POST['blood_group']) && $_POST['blood_group'] ? $_POST['blood_group'] : null;
            $address = isset($_POST['address']) ? trim($_POST['address']) : '';
            $divisionId = isset($_POST['division_id']) && $_POST['division_id'] ? (int)$_POST['division_id'] : null;
            $districtId = isset($_POST['district_id']) && $_POST['district_id'] ? (int)$_POST['district_id'] : null;
            $thanaId = isset($_POST['thana_id']) && $_POST['thana_id'] ? (int)$_POST['thana_id'] : null;
            
            if(empty($firstName) && !empty($fullName)) {
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0];
                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
            }
            
            $errors = [];
            if(empty($firstName)) $errors[] = "First name is required";
            if(empty($lastName)) $errors[] = "Last name is required";
            if(empty($phone)) $errors[] = "Phone number is required";
            if(empty($gender)) $errors[] = "Gender is required";
            if(empty($dateOfBirth)) $errors[] = "Date of birth is required";
            
            if(!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }
            
            // FIXED: Allow same phone number for multiple patients
            // Only check if phone is empty or invalid format
            if (!empty($phone) && !preg_match('/^[0-9+\-\(\)\s]+$/', $phone)) {
                $errors[] = "Invalid phone number format";
            }
            
            // Email uniqueness check still applies (optional field)
            if(!empty($email)) {
                $checkEmail = $this->db->query("SELECT id FROM patients WHERE email = '$email'");
                if($checkEmail && $checkEmail->num_rows > 0) {
                    $errors[] = "Email already registered";
                }
            }
            
            if(!empty($errors)) {
                $_SESSION['errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                $this->redirect('/patient/register');
                return;
            }
            
            $patientCode = 'PAT' . date('Ymd') . rand(100, 999);
            $fullNameDb = $firstName . ' ' . $lastName;
            
            $fullNameEsc = $this->db->real_escape_string($fullNameDb);
            $firstNameEsc = $this->db->real_escape_string($firstName);
            $lastNameEsc = $this->db->real_escape_string($lastName);
            $emailEsc = !empty($email) ? "'" . $this->db->real_escape_string($email) . "'" : "NULL";
            $phoneEsc = $this->db->real_escape_string($phone);
            $genderEsc = $this->db->real_escape_string($gender);
            $dateOfBirthEsc = $this->db->real_escape_string($dateOfBirth);
            $addressEsc = $this->db->real_escape_string($address);
            $bloodGroupEsc = $bloodGroup ? "'" . $this->db->real_escape_string($bloodGroup) . "'" : "NULL";
            $divisionIdSql = $divisionId ? $divisionId : "NULL";
            $districtIdSql = $districtId ? $districtId : "NULL";
            $thanaIdSql = $thanaId ? $thanaId : "NULL";
            
            $query = "INSERT INTO patients (
                        patient_code, full_name, first_name, last_name, email, phone, gender, 
                        date_of_birth, blood_group, address, division_id, district_id, thana_id,
                        status, registration_date, created_at
                      ) VALUES (
                        '$patientCode', '$fullNameEsc', '$firstNameEsc', '$lastNameEsc', $emailEsc, 
                        '$phoneEsc', '$genderEsc', '$dateOfBirthEsc', $bloodGroupEsc, 
                        '$addressEsc', $divisionIdSql, $districtIdSql, $thanaIdSql,
                        'active', CURDATE(), NOW()
                      )";
            
            if($this->db->query($query)) {
                $patientId = $this->db->insert_id;
                $action = isset($_POST['action']) ? $_POST['action'] : 'register';
                
                if($action == 'save_register') {
                    $_SESSION['success'] = "Patient registered successfully! Patient ID: $patientCode";
                    $_SESSION['old_input'] = [];
                    $this->redirect('/patient/register');
                } else {
                    $_SESSION['success'] = "Patient registered successfully! Patient ID: $patientCode";
                    $this->redirect('/patient/list');
                }
            } else {
                throw new Exception($this->db->error);
            }
            
        } catch (Exception $e) {
            $_SESSION['errors'] = [$e->getMessage()];
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/patient/register');
        }
    }
    
    // ==================== ID CARD ====================
    public function idCard() {
        $this->checkAuth();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['errors'] = ["Invalid patient ID"];
            $this->redirect('/patient/list');
            return;
        }
        $patient = Patient::find($id);
        if (!$patient) {
            $_SESSION['errors'] = ["Patient not found"];
            $this->redirect('/patient/list');
            return;
        }
        $content = $this->renderView('patients/id-card', ['patient' => $patient]);
        $this->renderLayout('Patient ID Card', $content);
    }
    
    // ==================== ID CARD FRONT ====================
    public function idCardFront() {
        $this->checkAuth();
        $patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$patientId) { die('Patient ID required'); }
        $patient = Patient::find($patientId);
        if (!$patient) { die('Patient not found'); }
        $printMode = isset($_GET['print']) && $_GET['print'] == 'true';
        require_once __DIR__ . '/../views/patients/id-card-front.php';
        exit;
    }
    
    // ==================== ID CARD BACK ====================
    public function idCardBack() {
        $this->checkAuth();
        $patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$patientId) { die('Patient ID required'); }
        $patient = Patient::find($patientId);
        if (!$patient) { die('Patient not found'); }
        $printMode = isset($_GET['print']) && $_GET['print'] == 'true';
        require_once __DIR__ . '/../views/patients/id-card-back.php';
        exit;
    }
    
    // ==================== VIEW PATIENT ====================
    public function show() {
        $this->checkAuth();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = "Invalid patient ID";
            $this->redirect('/patient/list');
            return;
        }
        $patient = Patient::find($id);
        if (!$patient) {
            $_SESSION['error'] = "Patient not found";
            $this->redirect('/patient/list');
            return;
        }
        $content = $this->renderView('patients/view', ['patient' => $patient]);
        $this->renderLayout('Patient Details', $content);
    }
    
    // ==================== EDIT PATIENT ====================
    public function edit() {
        $this->checkAuth();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = "Invalid patient ID";
            $this->redirect('/patient/list');
            return;
        }
        $patient = Patient::find($id);
        $divisions = Patient::getDivisions();
        if (!$patient) {
            $_SESSION['error'] = "Patient not found";
            $this->redirect('/patient/list');
            return;
        }
        $districts = [];
        $thanas = [];
        if ($patient['division_id']) {
            $districts = Patient::getDistrictsByDivision($patient['division_id']);
        }
        if ($patient['district_id']) {
            $thanas = Patient::getThanasByDistrict($patient['district_id']);
        }
        $content = $this->renderView('patients/edit', [
            'patient' => $patient,
            'divisions' => $divisions,
            'districts' => $districts,
            'thanas' => $thanas
        ]);
        $this->renderLayout('Edit Patient', $content);
    }
    
    // ==================== UPDATE PATIENT ====================
    public function update() {
        $this->checkAuth();
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            $_SESSION['errors'] = ["Invalid patient ID"];
            $this->redirect('/patient/list');
            return;
        }
        
        $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
        $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
        $dateOfBirth = isset($_POST['date_of_birth']) ? $_POST['date_of_birth'] : '';
        $bloodGroup = isset($_POST['blood_group']) && $_POST['blood_group'] ? $_POST['blood_group'] : null;
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $divisionId = isset($_POST['division_id']) && $_POST['division_id'] ? (int)$_POST['division_id'] : null;
        $districtId = isset($_POST['district_id']) && $_POST['district_id'] ? (int)$_POST['district_id'] : null;
        $thanaId = isset($_POST['thana_id']) && $_POST['thana_id'] ? (int)$_POST['thana_id'] : null;
        
        $emergencyName = isset($_POST['emergency_contact_name']) ? trim($_POST['emergency_contact_name']) : null;
        $emergencyPhone = isset($_POST['emergency_contact_phone']) ? trim($_POST['emergency_contact_phone']) : null;
        $emergencyRelation = isset($_POST['emergency_contact_relation']) ? trim($_POST['emergency_contact_relation']) : null;
        
        $errors = [];
        if(empty($firstName)) $errors[] = "First name is required";
        if(empty($lastName)) $errors[] = "Last name is required";
        if(empty($phone)) $errors[] = "Phone number is required";
        
        // FIXED: Allow same phone number for multiple patients when editing
        // Only check if another patient (different ID) has the same phone
        if (!empty($phone)) {
            $checkPhone = $this->db->query("SELECT id FROM patients WHERE phone = '$phone' AND id != $id");
            if($checkPhone && $checkPhone->num_rows > 0) {
                // Show warning but allow it - we just inform the user
                // No error, just a notice that will be shown
            }
        }
        
        if(!empty($email)) {
            $checkEmail = $this->db->query("SELECT id FROM patients WHERE email = '$email' AND id != $id");
            if($checkEmail && $checkEmail->num_rows > 0) {
                $errors[] = "Email already registered to another patient";
            }
        }
        
        if(!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->redirect('/patient/edit?id=' . $id);
            return;
        }
        
        $fullName = $firstName . ' ' . $lastName;
        $data = [
            'full_name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'gender' => $gender,
            'date_of_birth' => $dateOfBirth,
            'blood_group' => $bloodGroup,
            'address' => $address,
            'division_id' => $divisionId,
            'district_id' => $districtId,
            'thana_id' => $thanaId,
            'emergency_contact_name' => $emergencyName,
            'emergency_contact_phone' => $emergencyPhone,
            'emergency_contact_relation' => $emergencyRelation
        ];
        
        if (Patient::update($id, $data)) {
            $_SESSION['success'] = "Patient updated successfully!";
        } else {
            $_SESSION['errors'] = ["Failed to update patient"];
        }
        $this->redirect('/patient/show?id=' . $id);
    }
    
    // ==================== DELETE PATIENT ====================
    public function delete() {
        $this->checkAuth();
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            $_SESSION['error'] = "Invalid patient ID";
            $this->redirect('/patient/list');
            return;
        }
        $db = Database::getInstance()->getConnection();
        $db->query("UPDATE patients SET status='inactive' WHERE id=$id");
        $_SESSION['success'] = "Patient deleted successfully";
        $this->redirect('/patient/list');
    }
    
    // ==================== BOOK APPOINTMENT ====================
    public function bookAppointment() {
        $this->checkAuth();
        $patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($patientId <= 0) {
            $_SESSION['error'] = "Invalid patient ID";
            $this->redirect('/patient/list');
            return;
        }
        $patient = Patient::find($patientId);
        if (!$patient) {
            $_SESSION['error'] = "Patient not found";
            $this->redirect('/patient/list');
            return;
        }
        $db = Database::getInstance()->getConnection();
        $doctors = $db->query("SELECT d.id, u.first_name, u.last_name, d.specialization, d.consultation_fee 
                               FROM doctors d 
                               JOIN users u ON d.user_id = u.id 
                               WHERE d.status = 'active'");
        $doctorList = [];
        while($row = $doctors->fetch_assoc()) {
            $doctorList[] = $row;
        }
        $content = $this->renderView('patients/book-appointment', [
            'patient' => $patient,
            'doctors' => $doctorList
        ]);
        $this->renderLayout('Book Appointment', $content);
    }
    
    // ==================== VIEW PATIENT PRESCRIPTIONS ====================
    public function patientPrescriptions($patientId) {
        $this->checkAuth();
        $patient = Patient::find($patientId);
        if (!$patient) {
            $_SESSION['error'] = "Patient not found";
            $this->redirect('/patient/list');
            return;
        }
        require_once __DIR__ . '/../models/Prescription.php';
        $prescriptionModel = new Prescription();
        $prescriptions = $prescriptionModel->getPatientPrescriptions($patientId);
        $content = $this->renderView('patients/prescriptions', [
            'patient' => $patient,
            'prescriptions' => $prescriptions
        ]);
        $this->renderLayout('Patient Prescriptions', $content);
    }
    
    // ==================== QUICK REGISTER PATIENT (AJAX) ====================
    public function quickStore() {
        $this->checkAuth();
        header('Content-Type: application/json');
        
        try {
            $fullName = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
            $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
            $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
            $dateOfBirth = isset($_POST['date_of_birth']) && !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null;
            $address = isset($_POST['address']) ? trim($_POST['address']) : 'Bangladesh';
            $action = isset($_POST['action']) ? $_POST['action'] : 'save_register';
            
            // Prefer explicit first/last name if sent from client
            $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
            $lastName  = isset($_POST['last_name'])  ? trim($_POST['last_name'])  : '';
            
            if (empty($firstName) && !empty($fullName)) {
                $nameParts = explode(' ', $fullName, 2);
                $firstName = $nameParts[0];
                $lastName  = isset($nameParts[1]) ? $nameParts[1] : '';
            }
            if (empty($fullName)) {
                $fullName = trim($firstName . ' ' . $lastName);
            }
            
            $errors = [];
            if (empty($firstName)) $errors[] = "Full name is required";
            if (empty($phone))     $errors[] = "Phone number is required";
            if (empty($dateOfBirth)) $errors[] = "Date of birth is required";
            
            if (!empty($phone) && !preg_match('/^[0-9+\-\(\)\s]+$/', $phone)) {
                $errors[] = "Invalid phone number format";
            }
            
            // PHONE: Multiple patients CAN share the same phone number.
            // NO phone uniqueness check — intentional.
            
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }
            
            // Generate unique patient code with retry
            $patientCode = '';
            $attempts = 0;
            do {
                $patientCode = 'PAT' . date('Ymd') . rand(100, 999);
                $codeEsc = $this->db->real_escape_string($patientCode);
                $check = $this->db->query("SELECT id FROM patients WHERE patient_code = '$codeEsc' LIMIT 1");
                $exists = ($check && $check->num_rows > 0);
                $attempts++;
            } while ($exists && $attempts < 10);
            
            $fullNameEsc   = $this->db->real_escape_string($fullName);
            $firstNameEsc  = $this->db->real_escape_string($firstName);
            $lastNameEsc   = $this->db->real_escape_string($lastName);
            $phoneEsc      = $this->db->real_escape_string($phone);
            $genderEsc     = $this->db->real_escape_string($gender);
            $addressEsc    = $this->db->real_escape_string($address);
            $dateOfBirthSql = !empty($dateOfBirth)
                ? "'" . $this->db->real_escape_string($dateOfBirth) . "'"
                : "NULL";
            $codeEsc = $this->db->real_escape_string($patientCode);
            
            // Email column is UNIQUE — store NULL to avoid empty-string collisions
            $query = "INSERT INTO patients (
                        patient_code, full_name, first_name, last_name, email, phone, gender, 
                        date_of_birth, address, status, registration_date, created_at
                      ) VALUES (
                        '$codeEsc', '$fullNameEsc', '$firstNameEsc', '$lastNameEsc', 
                        NULL, '$phoneEsc', '$genderEsc', $dateOfBirthSql, 
                        '$addressEsc', 'active', CURDATE(), NOW()
                      )";
            
            if ($this->db->query($query)) {
                $patientId = $this->db->insert_id;
                
                // Fetch freshly-created row so we return consistent data
                $result = $this->db->query("SELECT id, patient_code, first_name, last_name, full_name, phone, email, gender, date_of_birth, address
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
                    'patient_id'   => $patientId,
                    'patient_code' => $patient['patient_code'],
                    'patient_name' => $patient['full_name'],
                    'message'      => 'Patient registered successfully',
                    // ★ Critical: same shape as AppointmentController::quickStore()
                    'patient'      => $patient
                ]);
            } else {
                throw new Exception($this->db->error);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'errors'  => [$e->getMessage()]
            ]);
        }
    }
    
    // ==================== BARCODE VIEW ====================
    public function barcodeView() {
        $this->checkAuth();
        $patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$patientId) { die('Patient ID required'); }
        $patient = Patient::find($patientId);
        if (!$patient) { die('Patient not found'); }
        require_once BASE_PATH . '/app/helpers/BarcodeHelper.php';
        $barcodeData = BarcodeHelper::generateBarcodeSVG($patient['patient_code']);
        require_once BASE_PATH . '/app/views/patients/barcode-view.php';
        exit;
    }
    
    // ==================== BARCODE PRINT ====================
    public function barcodePrint() {
        $this->checkAuth();
        $patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$patientId) { die('Patient ID required'); }
        $patient = Patient::find($patientId);
        if (!$patient) { die('Patient not found'); }
        require_once BASE_PATH . '/app/helpers/BarcodeHelper.php';
        $barcodeData = BarcodeHelper::generateBarcodeSVG($patient['patient_code']);
        $barcodeHTML = BarcodeHelper::generateBarcodeHTML($patient);
        $autoPrint = isset($_GET['auto_print']) && $_GET['auto_print'] == 'true';
        require_once BASE_PATH . '/app/views/patients/barcode-print.php';
        exit;
    }
    
    // ==================== BARCODE DOWNLOAD ====================
    public function barcodeDownload() {
        $this->checkAuth();
        $patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$patientId) { die('Patient ID required'); }
        $patient = Patient::find($patientId);
        if (!$patient) { die('Patient not found'); }
        require_once BASE_PATH . '/app/helpers/BarcodeHelper.php';
        $svg = BarcodeHelper::generateBarcodeSVG($patient['patient_code']);
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="barcode_' . $patient['patient_code'] . '.png"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        echo $svg;
        exit;
    }
    
    // ==================== BARCODE DOWNLOAD SVG ====================
    public function barcodeDownloadSvg() {
        $this->checkAuth();
        $patientId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if (!$patientId) { die('Patient ID required'); }
        $patient = Patient::find($patientId);
        if (!$patient) { die('Patient not found'); }
        require_once BASE_PATH . '/app/helpers/BarcodeHelper.php';
        $svg = BarcodeHelper::generateBarcodeSVG($patient['patient_code']);
        header('Content-Type: image/svg+xml');
        header('Content-Disposition: attachment; filename="barcode_' . $patient['patient_code'] . '.svg"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        echo $svg;
        exit;
    }
}
?>