<?php
class Lab extends Model {
    protected $table = 'lab_test_orders';
    
    public function createOrder($data) {
        return $this->create($data);
    }
    
    public function addOrderItem($data) {
        $sql = "INSERT INTO lab_test_order_items (order_id, test_id, status) 
                VALUES (:order_id, :test_id, :status)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function getPendingTests() {
        $sql = "SELECT COUNT(*) as count FROM lab_test_order_items 
                WHERE status IN ('pending', 'sample_collected')";
        $result = $this->query($sql);
        return $result[0]['count'] ?? 0;
    }
    
    public function getAllTests($filters = []) {
        $sql = "SELECT t.*, c.name as category_name 
                FROM lab_tests t
                LEFT JOIN lab_test_categories c ON t.category_id = c.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (t.test_name LIKE :search OR t.test_code LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['category'])) {
            $sql .= " AND t.category_id = :category";
            $params['category'] = $filters['category'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }
        
        $sql .= " ORDER BY t.test_name ASC";
        return $this->query($sql, $params);
    }
    
    public function getTestById($id) {
        $sql = "SELECT t.*, c.name as category_name 
                FROM lab_tests t
                LEFT JOIN lab_test_categories c ON t.category_id = c.id
                WHERE t.id = :id";
        $result = $this->query($sql, ['id' => $id]);
        return $result[0] ?? null;
    }
    
    public function updateOrderItem($id, $data) {
        return $this->update($id, $data, 'lab_test_order_items');
    }
    
    public function getOrderItem($id) {
        $sql = "SELECT * FROM lab_test_order_items WHERE id = :id";
        $result = $this->query($sql, ['id' => $id]);
        return $result[0] ?? null;
    }
    
    public function getOrderItems($orderId) {
        $sql = "SELECT oi.*, t.test_name, t.normal_range, t.unit 
                FROM lab_test_order_items oi 
                JOIN lab_tests t ON oi.test_id = t.id 
                WHERE oi.order_id = :order_id";
        return $this->query($sql, ['order_id' => $orderId]);
    }
    
    public function updateOrder($id, $data) {
        return $this->update($id, $data);
    }

    public function getCategories() {
        $sql = "SELECT * FROM lab_test_categories WHERE status = 'active'";
        return $this->query($sql);
    }
    
    public function getAllCategories() {
        $sql = "SELECT * FROM lab_test_categories ORDER BY name ASC";
        return $this->query($sql);
    }

    public function getInProgressTests() {
        $sql = "SELECT COUNT(*) as count FROM lab_test_order_items WHERE status = 'processing'";
        $result = $this->query($sql);
        return $result[0]['count'] ?? 0;
    }

    public function getCompletedToday() {
        $sql = "SELECT COUNT(*) as count FROM lab_test_order_items 
                WHERE status = 'completed' AND DATE(result_date) = CURDATE()";
        $result = $this->query($sql);
        return $result[0]['count'] ?? 0;
    }

    public function getPatientLabTests($patientId) {
        $sql = "SELECT o.*, GROUP_CONCAT(t.test_name SEPARATOR ', ') as test_names 
                FROM lab_test_orders o 
                LEFT JOIN lab_test_order_items oi ON o.id = oi.order_id 
                LEFT JOIN lab_tests t ON oi.test_id = t.id 
                WHERE o.patient_id = :pid 
                GROUP BY o.id 
                ORDER BY o.order_date DESC";
        return $this->query($sql, ['pid' => $patientId]);
    }

    public function getPatientReports($patientId) {
        $sql = "SELECT * FROM lab_test_orders 
                WHERE patient_id = :pid AND status = 'completed' 
                ORDER BY order_date DESC LIMIT 5";
        return $this->query($sql, ['pid' => $patientId]);
    }

    public function getOrderDetails($orderId) {
        $sql = "SELECT o.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                FROM lab_test_orders o 
                JOIN patients p ON o.patient_id = p.id 
                JOIN doctors d ON o.doctor_id = d.id 
                JOIN users u ON d.user_id = u.id 
                WHERE o.id = :id";
        $result = $this->query($sql, ['id' => $orderId]);
        return $result[0] ?? null;
    }

    public function getLabReport($orderId) {
        return $this->getOrderDetails($orderId);
    }

    public function getAllOrders($filters = []) {
        $sql = "SELECT o.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                FROM lab_test_orders o 
                JOIN patients p ON o.patient_id = p.id 
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params['status'] = $filters['status'];
        }
        
        $sql .= " ORDER BY o.order_date DESC";
        return $this->query($sql, $params);
    }
    
    // ================================================================
    // TEST INSTRUMENTS
    // ================================================================
    public function getTestInstruments($testId) {
        $sql = "SELECT i.*, lti.is_primary 
                FROM lab_test_instruments lti
                JOIN lab_instruments i ON lti.instrument_id = i.id
                WHERE lti.test_id = :test_id
                ORDER BY lti.is_primary DESC, i.instrument_name ASC";
        return $this->query($sql, ['test_id' => $testId]);
    }
    
    public function getAssignedInstrumentIds($testId) {
        $sql = "SELECT instrument_id FROM lab_test_instruments WHERE test_id = :test_id";
        $result = $this->query($sql, ['test_id' => $testId]);
        $ids = [];
        foreach ($result as $row) {
            $ids[] = $row['instrument_id'];
        }
        return $ids;
    }
    
    public function getPrimaryInstrument($testId) {
        $sql = "SELECT instrument_id FROM lab_test_instruments 
                WHERE test_id = :test_id AND is_primary = 1 LIMIT 1";
        $result = $this->query($sql, ['test_id' => $testId]);
        return $result[0]['instrument_id'] ?? 0;
    }
    
    public function assignInstruments($testId, $instrumentIds, $primaryInstrumentId) {
        // Delete existing
        $this->db->query("DELETE FROM lab_test_instruments WHERE test_id = " . (int)$testId);
        
        foreach ($instrumentIds as $instId) {
            $instId = (int)$instId;
            $isPrimary = ($instId == $primaryInstrumentId) ? 1 : 0;
            $this->db->query("INSERT INTO lab_test_instruments (test_id, instrument_id, is_primary) 
                              VALUES ($testId, $instId, $isPrimary)");
        }
        return true;
    }
    
    // ================================================================
    // TEST ACCESSORIES
    // ================================================================
    public function getTestAccessories($testId) {
        $sql = "SELECT a.*, lta.quantity_required, lta.is_required, lta.notes 
                FROM lab_test_accessories lta
                JOIN lab_accessories a ON lta.accessory_id = a.id
                WHERE lta.test_id = :test_id
                ORDER BY a.accessory_name ASC";
        return $this->query($sql, ['test_id' => $testId]);
    }
    
    public function assignAccessories($testId, $accessoryIds, $quantities, $isRequired, $notes) {
        // Delete existing
        $this->db->query("DELETE FROM lab_test_accessories WHERE test_id = " . (int)$testId);
        
        foreach ($accessoryIds as $accId) {
            $accId = (int)$accId;
            $qty = isset($quantities[$accId]) ? (int)$quantities[$accId] : 1;
            $required = isset($isRequired[$accId]) ? 1 : 0;
            $note = isset($notes[$accId]) ? $this->db->real_escape_string($notes[$accId]) : '';
            $this->db->query("INSERT INTO lab_test_accessories (test_id, accessory_id, quantity_required, is_required, notes) 
                              VALUES ($testId, $accId, $qty, $required, '$note')");
        }
        return true;
    }
    
    // ================================================================
    // LAB INSTRUMENTS
    // ================================================================
    public function getAllInstruments() {
        $sql = "SELECT * FROM lab_instruments WHERE status = 'active' ORDER BY instrument_name ASC";
        return $this->query($sql);
    }
    
    public function getInstrumentById($id) {
        $sql = "SELECT * FROM lab_instruments WHERE id = :id";
        $result = $this->query($sql, ['id' => $id]);
        return $result[0] ?? null;
    }
    
    // ================================================================
    // LAB ACCESSORIES
    // ================================================================
    public function getAllAccessories() {
        $sql = "SELECT * FROM lab_accessories WHERE status = 'active' ORDER BY accessory_name ASC";
        return $this->query($sql);
    }
    
    public function getAccessoryById($id) {
        $sql = "SELECT * FROM lab_accessories WHERE id = :id";
        $result = $this->query($sql, ['id' => $id]);
        return $result[0] ?? null;
    }
}
?>