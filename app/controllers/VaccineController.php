<?php
// /app/controllers/VaccineController.php

require_once BASE_PATH . '/app/models/Vaccine.php';

class VaccineController extends Controller {
    private $vaccineModel;
    private $patientModel;

    public function __construct() {
        parent::__construct();
        $this->vaccineModel = new Vaccine();
        $this->patientModel = new Patient();
        $this->checkAuth();
    }

    // ================================================================
    // INDEX - List all vaccines for a patient
    // ================================================================
    public function index() {
        $this->checkPermission('view_patients');
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        if (!$patientId) {
            $this->setFlash('error', 'Patient ID is required');
            $this->redirect('/patient/list');
            return;
        }

        $patient = $this->patientModel->find($patientId);
        if (!$patient) {
            $this->setFlash('error', 'Patient not found');
            $this->redirect('/patient/list');
            return;
        }

        // Get vaccines with search
        if (!empty($search)) {
            $vaccines = $this->vaccineModel->search($patientId, $search);
            $totalVaccines = count($vaccines);
        } else {
            $vaccines = $this->vaccineModel->getPatientVaccines($patientId, $limit, $offset);
            $totalVaccines = $this->vaccineModel->getTotalCount($patientId);
        }

        $totalPages = ceil($totalVaccines / $limit);

        $this->view('vaccines/index', [
            'title' => 'Vaccination Records',
            'patient' => $patient,
            'vaccines' => $vaccines,
            'search' => $search,
            'totalVaccines' => $totalVaccines,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'patientId' => $patientId
        ]);
    }

    // ================================================================
    // CREATE - Show add vaccine form
    // ================================================================
    public function create() {
        $this->checkPermission('edit_patients');
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        
        if (!$patientId) {
            $this->setFlash('error', 'Patient ID is required');
            $this->redirect('/patient/list');
            return;
        }

        $patient = $this->patientModel->find($patientId);
        if (!$patient) {
            $this->setFlash('error', 'Patient not found');
            $this->redirect('/patient/list');
            return;
        }

        $this->view('vaccines/create', [
            'title' => 'Add Vaccine',
            'patient' => $patient,
            'patientId' => $patientId
        ]);
    }

    // ================================================================
    // SAVE - Save vaccine
    // ================================================================
    public function save() {
        $this->checkPermission('edit_patients');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/patient/list');
            return;
        }

        $data = $_POST;
        $patientId = (int)$data['patient_id'];
        $redirectTo = isset($data['redirect_to']) ? $data['redirect_to'] : '/vaccines?patient_id=' . $patientId;

        // Validate
        if (empty($data['vaccine_name'])) {
            $this->setFlash('error', 'Vaccine name is required');
            $this->redirect('/vaccines/create?patient_id=' . $patientId);
            return;
        }

        try {
            $db = $this->db;
            $db->begin_transaction();

            // Handle image upload
            $imagePath = null;
            if (isset($_FILES['vaccine_image']) && $_FILES['vaccine_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = BASE_PATH . '/public/uploads/vaccines/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $_FILES['vaccine_image']['name']);
                $imagePath = 'uploads/vaccines/' . $fileName;
                move_uploaded_file($_FILES['vaccine_image']['tmp_name'], $uploadDir . $fileName);
            }

            // Prepare data
            $vaccineData = [
                'patient_id' => $patientId,
                'vaccine_name' => $data['vaccine_name'],
                'dose' => $data['dose'] ?? '',
                'date_given' => !empty($data['date_given']) ? $data['date_given'] : null,
                'next_due' => !empty($data['next_due']) ? $data['next_due'] : null,
                'batch_number' => $data['batch_number'] ?? '',
                'site' => $data['site'] ?? '',
                'administered_by' => $data['administered_by'] ?? '',
                'notes' => $data['notes'] ?? '',
                'vaccine_image' => $imagePath,
                'created_by' => $_SESSION['user_id'] ?? null,
                'prescription_id' => !empty($data['prescription_id']) ? (int)$data['prescription_id'] : null
            ];

            $result = $this->vaccineModel->create($vaccineData);

            if (!$result) {
                throw new Exception('Failed to save vaccine');
            }

            $db->commit();

            $this->setFlash('success', 'Vaccine added successfully!');
            
            // If coming from prescription, redirect back to prescription edit
            if (!empty($data['prescription_id'])) {
                $this->redirect('/prescriptions/edit/' . $data['prescription_id'] . '?tab=tab5');
                return;
            }

            $this->redirect($redirectTo);

        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            error_log("Vaccine save error: " . $e->getMessage());
            $this->setFlash('error', 'Error saving vaccine: ' . $e->getMessage());
            $this->redirect('/vaccines/create?patient_id=' . $patientId);
        }
    }

    // ================================================================
    // EDIT - Edit vaccine
    // ================================================================
    public function edit($id) {
        $this->checkPermission('edit_patients');

        $vaccine = $this->vaccineModel->getById($id);
        if (!$vaccine) {
            $this->setFlash('error', 'Vaccine not found');
            $this->redirect('/patient/list');
            return;
        }

        $patient = $this->patientModel->find($vaccine['patient_id']);
        if (!$patient) {
            $this->setFlash('error', 'Patient not found');
            $this->redirect('/patient/list');
            return;
        }

        $this->view('vaccines/edit', [
            'title' => 'Edit Vaccine',
            'vaccine' => $vaccine,
            'patient' => $patient,
            'patientId' => $vaccine['patient_id']
        ]);
    }

    // ================================================================
    // UPDATE - Update vaccine
    // ================================================================
    public function update($id) {
        $this->checkPermission('edit_patients');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/patient/list');
            return;
        }

        $data = $_POST;
        $patientId = (int)$data['patient_id'];

        try {
            $db = $this->db;
            $db->begin_transaction();

            // Get existing vaccine
            $existing = $this->vaccineModel->getById($id);
            if (!$existing) {
                throw new Exception('Vaccine not found');
            }

            $imagePath = $existing['vaccine_image'];

            // Handle image upload
            if (isset($_FILES['vaccine_image']) && $_FILES['vaccine_image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = BASE_PATH . '/public/uploads/vaccines/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Delete old image if exists
                if ($imagePath && file_exists(BASE_PATH . '/public/' . $imagePath)) {
                    unlink(BASE_PATH . '/public/' . $imagePath);
                }
                
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '_', $_FILES['vaccine_image']['name']);
                $imagePath = 'uploads/vaccines/' . $fileName;
                move_uploaded_file($_FILES['vaccine_image']['tmp_name'], $uploadDir . $fileName);
            }

            // Update data
            $updateData = [
                'vaccine_name' => $data['vaccine_name'],
                'dose' => $data['dose'] ?? '',
                'date_given' => !empty($data['date_given']) ? $data['date_given'] : null,
                'next_due' => !empty($data['next_due']) ? $data['next_due'] : null,
                'batch_number' => $data['batch_number'] ?? '',
                'site' => $data['site'] ?? '',
                'administered_by' => $data['administered_by'] ?? '',
                'notes' => $data['notes'] ?? '',
                'vaccine_image' => $imagePath
            ];

            $result = $this->vaccineModel->update($id, $updateData);

            if (!$result) {
                throw new Exception('Failed to update vaccine');
            }

            $db->commit();

            $this->setFlash('success', 'Vaccine updated successfully!');
            
            // If coming from prescription, redirect back
            if (!empty($data['prescription_id'])) {
                $this->redirect('/prescriptions/edit/' . $data['prescription_id'] . '?tab=tab5');
                return;
            }

            $this->redirect('/vaccines?patient_id=' . $patientId);

        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            error_log("Vaccine update error: " . $e->getMessage());
            $this->setFlash('error', 'Error updating vaccine: ' . $e->getMessage());
            $this->redirect('/vaccines/edit/' . $id);
        }
    }

    // ================================================================
    // DELETE - Delete vaccine
    // ================================================================
    public function delete($id) {
        $this->checkPermission('edit_patients');

        try {
            $vaccine = $this->vaccineModel->getById($id);
            if (!$vaccine) {
                throw new Exception('Vaccine not found');
            }

            // Delete image if exists
            if ($vaccine['vaccine_image'] && file_exists(BASE_PATH . '/public/' . $vaccine['vaccine_image'])) {
                unlink(BASE_PATH . '/public/' . $vaccine['vaccine_image']);
            }

            $result = $this->vaccineModel->delete($id);

            if (!$result) {
                throw new Exception('Failed to delete vaccine');
            }

            $this->setFlash('success', 'Vaccine deleted successfully!');
        } catch (Exception $e) {
            error_log("Vaccine delete error: " . $e->getMessage());
            $this->setFlash('error', 'Error deleting vaccine: ' . $e->getMessage());
        }

        $this->redirect('/vaccines?patient_id=' . ($vaccine['patient_id'] ?? 0));
    }

    // ================================================================
    // PRINT CARD - Print vaccine card
    // ================================================================
    public function printCard($patientId) {
        $this->checkPermission('view_patients');

        $patient = $this->patientModel->find($patientId);
        if (!$patient) {
            $this->setFlash('error', 'Patient not found');
            $this->redirect('/patient/list');
            return;
        }

        $vaccines = $this->vaccineModel->getVaccineCardData($patientId);

        $viewFile = BASE_PATH . '/app/views/vaccines/print-card.php';
        if (file_exists($viewFile)) {
            extract(['patient' => $patient, 'vaccines' => $vaccines]);
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
    // API - Get vaccines for patient (AJAX)
    // ================================================================
    public function apiGetVaccines() {
        header('Content-Type: application/json');
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        if (!$patientId) {
            echo json_encode(['success' => false, 'error' => 'Invalid patient']);
            exit;
        }

        $vaccines = $this->vaccineModel->getPatientVaccines($patientId);
        echo json_encode(['success' => true, 'data' => $vaccines]);
        exit;
    }
}