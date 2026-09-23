<?php
// /app/controllers/ManualLabController.php

require_once BASE_PATH . '/core/Controller.php';

class ManualLabController extends Controller {
    
    // FIX: Change from private to protected (matches parent Controller class)
    protected $db;
    
    public function __construct() {
        parent::__construct();
        // Use the parent's db connection or get from Database singleton
        if ($this->db === null) {
            $this->db = Database::getInstance()->getConnection();
        }
        $this->checkAuth();
    }
    
    // ================================================================
    // INDEX - List all manual results
    // ================================================================
    public function index() {
        $this->checkPermission('view_lab');
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        $search = isset($_GET['search']) ? $this->escapeString($_GET['search']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $where = "WHERE 1=1";
        if($patientId > 0) {
            $where .= " AND m.patient_id = $patientId";
        }
        if(!empty($search)) {
            $where .= " AND (m.test_name LIKE '%$search%' 
                            OR CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search%'
                            OR m.result_value LIKE '%$search%')";
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total 
                       FROM manual_lab_results m
                       JOIN patients p ON m.patient_id = p.id
                       $where";
        $countResult = $this->db->query($countQuery);
        $totalRecords = $countResult ? $countResult->fetch_assoc()['total'] : 0;
        $totalPages = ceil($totalRecords / $limit);
        
        // Get results
        $query = "SELECT m.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code,
                         p.phone,
                         CONCAT(u.first_name, ' ', u.last_name) as entered_by_name
                  FROM manual_lab_results m
                  JOIN patients p ON m.patient_id = p.id
                  LEFT JOIN users u ON m.entered_by = u.id
                  $where
                  ORDER BY m.entered_at DESC
                  LIMIT $offset, $limit";
        
        $result = $this->db->query($query);
        $results = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $row['entered_at_formatted'] = date('d-m-Y H:i', strtotime($row['entered_at']));
                $results[] = $row;
            }
        }
        
        // Get patients for filter
        $patients = $this->getPatients();
        
        $this->view('lab/manual/index', [
            'title' => 'Manual Lab Results',
            'results' => $results,
            'patients' => $patients,
            'selectedPatient' => $patientId,
            'search' => $search,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ]);
    }
    
    // ================================================================
    // CREATE - Show add form
    // ================================================================
    public function create() {
        $this->checkPermission('create_orders');
        
        $patients = $this->getPatients();
        $tests = $this->getTests();
        $selectedPatient = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        
        $this->view('lab/manual/create', [
            'title' => 'Add Manual Lab Result',
            'patients' => $patients,
            'tests' => $tests,
            'selectedPatient' => $selectedPatient
        ]);
    }
    
    // ================================================================
    // STORE - Save manual result
    // ================================================================
    public function store() {
        $this->checkPermission('create_orders');
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/lab/manual');
            return;
        }
        
        $patientId = (int)$_POST['patient_id'];
        $testId = (int)$_POST['test_id'];
        $testName = $this->escapeString($_POST['test_name'] ?? '');
        $resultValue = $this->escapeString($_POST['result_value'] ?? '');
        $normalRange = $this->escapeString($_POST['normal_range'] ?? '');
        $unit = $this->escapeString($_POST['unit'] ?? '');
        $isAbnormal = isset($_POST['is_abnormal']) ? 1 : 0;
        $referenceRange = $this->escapeString($_POST['reference_range'] ?? '');
        $notes = $this->escapeString($_POST['notes'] ?? '');
        $reportDate = !empty($_POST['report_date']) ? $this->escapeString($_POST['report_date']) : date('Y-m-d');
        
        // If test_id is provided but test_name is empty, get from lab_tests
        if($testId > 0 && empty($testName)) {
            $testResult = $this->db->query("SELECT test_name, normal_range, unit FROM lab_tests WHERE id = $testId");
            if($testResult && $testResult->num_rows > 0) {
                $testData = $testResult->fetch_assoc();
                $testName = $testData['test_name'];
                if(empty($normalRange)) $normalRange = $testData['normal_range'];
                if(empty($unit)) $unit = $testData['unit'];
            }
        }
        
        // Validation
        $errors = [];
        if($patientId <= 0) {
            $errors[] = 'Please select a patient';
        }
        if(empty($testName)) {
            $errors[] = 'Test name is required';
        }
        if(empty($resultValue)) {
            $errors[] = 'Result value is required';
        }
        
        if(!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->redirect('/lab/manual/create?patient_id=' . $patientId);
            return;
        }
        
        $enteredBy = $_SESSION['user_id'] ?? 1;
        
        $sql = "INSERT INTO manual_lab_results (
                    patient_id, test_id, test_name, result_value, 
                    normal_range, unit, is_abnormal, reference_range,
                    notes, entered_by, report_date
                ) VALUES (
                    $patientId, " . ($testId > 0 ? $testId : 'NULL') . ", '$testName', '$resultValue',
                    '$normalRange', '$unit', $isAbnormal, '$referenceRange',
                    '$notes', $enteredBy, '$reportDate'
                )";
        
        if($this->db->query($sql)) {
            $_SESSION['success'] = 'Lab result added successfully!';
            $this->redirect('/lab/manual?patient_id=' . $patientId);
        } else {
            $_SESSION['errors'] = ['Error adding result: ' . $this->db->error];
            $this->redirect('/lab/manual/create?patient_id=' . $patientId);
        }
    }
    
    // ================================================================
    // EDIT - Show edit form
    // ================================================================
    public function edit($id) {
        $this->checkPermission('edit_lab');
        
        $id = (int)$id;
        
        $query = "SELECT m.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name
                  FROM manual_lab_results m
                  JOIN patients p ON m.patient_id = p.id
                  WHERE m.id = $id";
        $result = $this->db->query($query);
        
        if(!$result || $result->num_rows == 0) {
            $_SESSION['error'] = 'Result not found';
            $this->redirect('/lab/manual');
            return;
        }
        
        $result = $result->fetch_assoc();
        $patients = $this->getPatients();
        $tests = $this->getTests();
        
        $this->view('lab/manual/edit', [
            'title' => 'Edit Manual Lab Result',
            'result' => $result,
            'patients' => $patients,
            'tests' => $tests
        ]);
    }
    
    // ================================================================
    // UPDATE - Update manual result
    // ================================================================
    public function update($id) {
        $this->checkPermission('edit_lab');
        
        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/lab/manual');
            return;
        }
        
        $id = (int)$id;
        $patientId = (int)$_POST['patient_id'];
        $testId = (int)$_POST['test_id'];
        $testName = $this->escapeString($_POST['test_name'] ?? '');
        $resultValue = $this->escapeString($_POST['result_value'] ?? '');
        $normalRange = $this->escapeString($_POST['normal_range'] ?? '');
        $unit = $this->escapeString($_POST['unit'] ?? '');
        $isAbnormal = isset($_POST['is_abnormal']) ? 1 : 0;
        $referenceRange = $this->escapeString($_POST['reference_range'] ?? '');
        $notes = $this->escapeString($_POST['notes'] ?? '');
        $reportDate = !empty($_POST['report_date']) ? $this->escapeString($_POST['report_date']) : date('Y-m-d');
        
        // If test_id is provided but test_name is empty, get from lab_tests
        if($testId > 0 && empty($testName)) {
            $testResult = $this->db->query("SELECT test_name, normal_range, unit FROM lab_tests WHERE id = $testId");
            if($testResult && $testResult->num_rows > 0) {
                $testData = $testResult->fetch_assoc();
                $testName = $testData['test_name'];
                if(empty($normalRange)) $normalRange = $testData['normal_range'];
                if(empty($unit)) $unit = $testData['unit'];
            }
        }
        
        $sql = "UPDATE manual_lab_results SET 
                    patient_id = $patientId,
                    test_id = " . ($testId > 0 ? $testId : 'NULL') . ",
                    test_name = '$testName',
                    result_value = '$resultValue',
                    normal_range = '$normalRange',
                    unit = '$unit',
                    is_abnormal = $isAbnormal,
                    reference_range = '$referenceRange',
                    notes = '$notes',
                    report_date = '$reportDate'
                WHERE id = $id";
        
        if($this->db->query($sql)) {
            $_SESSION['success'] = 'Result updated successfully!';
            $this->redirect('/lab/manual?patient_id=' . $patientId);
        } else {
            $_SESSION['errors'] = ['Error updating result: ' . $this->db->error];
            $this->redirect('/lab/manual/edit/' . $id);
        }
    }
    
    // ================================================================
    // DELETE - Delete manual result
    // ================================================================
    public function delete($id) {
        $this->checkPermission('edit_lab');
        
        $id = (int)$id;
        
        // Get patient_id for redirect
        $result = $this->db->query("SELECT patient_id FROM manual_lab_results WHERE id = $id");
        $patientId = $result ? $result->fetch_assoc()['patient_id'] : 0;
        
        if($this->db->query("DELETE FROM manual_lab_results WHERE id = $id")) {
            $_SESSION['success'] = 'Result deleted successfully!';
        } else {
            $_SESSION['errors'] = ['Error deleting result'];
        }
        
        $this->redirect('/lab/manual?patient_id=' . $patientId);
    }
    
    // ================================================================
    // PRINT REPORT - Print individual report
    // ================================================================
    public function printReport($id) {
        $this->checkPermission('view_lab');
        
        $id = (int)$id;
        
        $query = "SELECT m.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code,
                         p.phone,
                         p.gender,
                         p.date_of_birth,
                         p.address,
                         CONCAT(u.first_name, ' ', u.last_name) as entered_by_name
                  FROM manual_lab_results m
                  JOIN patients p ON m.patient_id = p.id
                  LEFT JOIN users u ON m.entered_by = u.id
                  WHERE m.id = $id";
        
        $result = $this->db->query($query);
        
        if(!$result || $result->num_rows == 0) {
            echo "Result not found";
            exit;
        }
        
        $result = $result->fetch_assoc();
        
        // Update report generated flag
        $this->db->query("UPDATE manual_lab_results SET is_report_generated = 1 WHERE id = $id");
        
        $viewFile = BASE_PATH . '/app/views/lab/manual/print-report.php';
        if(file_exists($viewFile)) {
            extract(['result' => $result]);
            ob_start();
            include $viewFile;
            $content = ob_get_clean();
            echo $content;
            exit;
        } else {
            // Fallback HTML
            $this->renderFallbackReport($result);
        }
    }
    
    // ================================================================
    // HELPER METHODS
    // ================================================================
    
    private function getPatients() {
        $result = $this->db->query("SELECT id, patient_code, first_name, last_name, phone 
                                   FROM patients WHERE status = 'active' 
                                   ORDER BY first_name ASC");
        $patients = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $patients[] = $row;
            }
        }
        return $patients;
    }
    
    private function getTests() {
        $result = $this->db->query("SELECT t.*, c.name as category_name 
                                   FROM lab_tests t
                                   JOIN lab_test_categories c ON t.category_id = c.id
                                   WHERE t.status = 'active'
                                   ORDER BY c.name, t.test_name");
        $tests = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $tests[] = $row;
            }
        }
        return $tests;
    }
    
    private function escapeString($string) {
        return $this->db->real_escape_string($string);
    }
    
    private function renderFallbackReport($result) {
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Lab Report</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 30px; max-width: 800px; margin: 0 auto; }
            .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 15px; margin-bottom: 20px; }
            .report-title { font-size: 24px; font-weight: bold; color: #10b981; }
            .info-row { margin: 5px 0; }
            .info-label { font-weight: 600; }
            .result-box { background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0; }
            .result-value { font-size: 28px; font-weight: bold; }
            .abnormal { color: #ef4444; }
            .normal { color: #10b981; }
            .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; color: #94a3b8; font-size: 12px; }
            .no-print { margin-top: 15px; }
            .no-print button { padding: 8px 20px; border: none; border-radius: 6px; cursor: pointer; margin: 0 5px; }
            .btn-print { background: #10b981; color: white; }
            .btn-close { background: #e5e7eb; color: #374151; }
            @media print { .no-print { display: none; } }
        </style>
        </head>
        <body>
            <div class="header">
                <div class="report-title">UNIDIA HOSPITAL</div>
                <div>Laboratory Report</div>
            </div>
            
            <div class="info-row"><span class="info-label">Patient:</span> <?php echo $result['patient_name']; ?></div>
            <div class="info-row"><span class="info-label">Patient Code:</span> <?php echo $result['patient_code']; ?></div>
            <div class="info-row"><span class="info-label">Phone:</span> <?php echo $result['phone']; ?></div>
            <div class="info-row"><span class="info-label">Report Date:</span> <?php echo date('d-m-Y', strtotime($result['report_date'])); ?></div>
            
            <div class="result-box">
                <div><strong>Test Name:</strong> <?php echo $result['test_name']; ?></div>
                <div class="result-value <?php echo $result['is_abnormal'] ? 'abnormal' : 'normal'; ?>">
                    <?php echo $result['result_value']; ?> <?php echo $result['unit']; ?>
                </div>
                <div><strong>Normal Range:</strong> <?php echo $result['normal_range']; ?></div>
                <div><strong>Status:</strong> <?php echo $result['is_abnormal'] ? '<span class="abnormal">Abnormal</span>' : '<span class="normal">Normal</span>'; ?></div>
                <?php if($result['notes']): ?>
                <div><strong>Notes:</strong> <?php echo $result['notes']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="footer">
                <p>Generated on: <?php echo date('d-m-Y H:i'); ?></p>
                <p>This is a computer-generated report. No signature required.</p>
            </div>
            
            <div class="no-print text-center">
                <button onclick="window.print()" class="btn-print">Print</button>
                <button onclick="window.close()" class="btn-close">Close</button>
            </div>
            
            <script>
                window.onload = function() {
                    setTimeout(function() { window.print(); }, 500);
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
?>