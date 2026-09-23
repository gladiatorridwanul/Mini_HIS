<?php
// app/controllers/ApiController.php - Fixed version
require_once __DIR__ . '/Controller.php';

class ApiController extends Controller {
    
    public function __construct() {
        parent::__construct();
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        header('Access-Control-Allow-Headers: Content-Type');
    }
    
    // Search drugs API
    public function searchDrugs() {
        $term = $_GET['term'] ?? '';
        $term = trim($term);
        if (strlen($term) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        $prescriptionModel = $this->model('Prescription');
        $drugs = $prescriptionModel->searchDrugs($term);
        echo json_encode(['success' => true, 'data' => $drugs]);
        exit;
    }
    
    // Get drug details
    public function getDrug($id) {
        $sql = "SELECT * FROM drug_database WHERE id = :id";
        $drug = $this->queryOne($sql, ['id' => $id]);
        echo json_encode(['success' => true, 'data' => $drug]);
        exit;
    }
    
    // Get advice templates
    public function getAdviceTemplates() {
        $sql = "SELECT * FROM advice_templates ORDER BY category, title";
        $templates = $this->query($sql);
        echo json_encode(['success' => true, 'data' => $templates]);
        exit;
    }
    
    // Save advice template
    public function saveAdviceTemplate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        $data = $_POST;
        $data['doctor_id'] = $this->getDoctorId();
        $sql = "INSERT INTO prescription_templates (doctor_id, template_name, template_type, content) 
                VALUES (:doctor_id, :template_name, :template_type, :content)";
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'doctor_id' => $data['doctor_id'],
            'template_name' => $data['template_name'],
            'template_type' => $data['template_type'] ?? 'advice',
            'content' => $data['content']
        ]);
        echo json_encode(['success' => $result]);
        exit;
    }
    
    private function getDoctorId() {
        if (isset($_SESSION['user_id'])) {
            $sql = "SELECT id FROM doctors WHERE user_id = :user_id";
            $result = $this->queryOne($sql, ['user_id' => $_SESSION['user_id']]);
            if ($result) {
                return $result['id'];
            }
        }
        return $_SESSION['doctor_id'] ?? 0;
    }

    // Add to ApiController.php
public function medicines() {
        $query = $_GET['q'] ?? '';
        if (strlen($query) < 2) {
            echo json_encode([]);
            exit;
        }
        
        $medicineModel = new Medicine();
        $medicines = $medicineModel->search($query);
        
        $results = [];
        foreach ($medicines as $med) {
            $label = $med['medicine_name'];
            if ($med['strength']) {
                $label .= ' ' . $med['strength'];
            }
            if ($med['generic_name']) {
                $label .= ' (' . $med['generic_name'] . ')';
            }
            $results[] = [
                'id' => $med['id'],
                'label' => $label,
                'name' => $med['medicine_name'],
                'strength' => $med['strength'] ?? '',
                'dosage_form' => $med['dosage_form'] ?? '',
                'price' => $med['selling_price'] ?? 0
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}