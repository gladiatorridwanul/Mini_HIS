<?php
// app/controllers/TemplateController.php
require_once BASE_PATH . '/app/core/Controller.php';
require_once BASE_PATH . '/app/models/PrescriptionTemplate.php';

class TemplateController extends Controller {
    
    public function list() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $type = $_GET['type'] ?? 'advice';
        $doctorId = $this->getDoctorId($_SESSION['user_id']);
        
        if (!$doctorId) {
            echo json_encode(['success' => false, 'error' => 'Doctor not found']);
            exit;
        }
        
        $templateModel = new PrescriptionTemplate();
        $templates = $templateModel->getByDoctor($doctorId, $type);
        
        echo json_encode(['success' => true, 'data' => $templates]);
        exit;
    }
    
    public function get() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $id = $_GET['id'] ?? 0;
        $doctorId = $this->getDoctorId($_SESSION['user_id']);
        
        if (!$id || !$doctorId) {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            exit;
        }
        
        $templateModel = new PrescriptionTemplate();
        $template = $templateModel->getTemplate($id, $doctorId);
        
        if ($template) {
            echo json_encode(['success' => true, 'data' => $template['template_data']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Template not found']);
        }
        exit;
    }
    
    public function save() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $doctorId = $this->getDoctorId($_SESSION['user_id']);
        
        if (!$doctorId) {
            echo json_encode(['success' => false, 'error' => 'Doctor not found']);
            exit;
        }
        
        $templateModel = new PrescriptionTemplate();
        $result = $templateModel->saveTemplate([
            'doctor_id' => $doctorId,
            'template_type' => $data['type'],
            'template_name' => $data['name'],
            'template_data' => $data['data']
        ]);
        
        echo json_encode(['success' => $result]);
        exit;
    }
    
    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }
        
        $id = $_POST['id'] ?? 0;
        $doctorId = $this->getDoctorId($_SESSION['user_id']);
        
        if (!$id || !$doctorId) {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            exit;
        }
        
        $templateModel = new PrescriptionTemplate();
        $result = $templateModel->deleteTemplate($id, $doctorId);
        
        echo json_encode(['success' => $result]);
        exit;
    }
    
    private function getDoctorId($userId) {
        $db = Database::getInstance();
        $sql = "SELECT id FROM doctors WHERE user_id = ?";
        $result = $db->query($sql, [$userId])->fetch();
        return $result ? $result['id'] : 0;
    }
}