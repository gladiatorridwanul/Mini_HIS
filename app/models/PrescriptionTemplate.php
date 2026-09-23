<?php
// app/models/PrescriptionTemplate.php
require_once BASE_PATH . '/app/core/Model.php';

class PrescriptionTemplate extends Model {
    protected $table = 'prescription_templates';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getByDoctor($doctorId, $type = null) {
        $sql = "SELECT * FROM {$this->table} WHERE doctor_id = ?";
        $params = [$doctorId];
        
        if ($type) {
            $sql .= " AND template_type = ?";
            $params[] = $type;
        }
        
        $sql .= " AND status = 'active' ORDER BY template_name";
        return $this->db->query($sql, $params)->fetchAll();
    }
    
    public function getTemplate($id, $doctorId) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ? AND doctor_id = ? AND status = 'active'";
        return $this->db->query($sql, [$id, $doctorId])->fetch();
    }
    
    public function saveTemplate($data) {
        $sql = "INSERT INTO {$this->table} 
                (doctor_id, template_type, template_name, template_data) 
                VALUES (?, ?, ?, ?)";
        return $this->db->execute($sql, [
            $data['doctor_id'],
            $data['template_type'],
            $data['template_name'],
            $data['template_data']
        ]);
    }
    
    public function updateTemplate($id, $data, $doctorId) {
        $sql = "UPDATE {$this->table} 
                SET template_name = ?, template_data = ? 
                WHERE id = ? AND doctor_id = ?";
        return $this->db->execute($sql, [
            $data['template_name'],
            $data['template_data'],
            $id,
            $doctorId
        ]);
    }
    
    public function deleteTemplate($id, $doctorId) {
        $sql = "DELETE FROM {$this->table} WHERE id = ? AND doctor_id = ?";
        return $this->db->execute($sql, [$id, $doctorId]);
    }
}