<?php
// app/models/Patient.php
require_once dirname(__DIR__, 2) . '/core/Database.php';

class Patient {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // ===== STATIC METHODS =====
    
    /**
     * Calculate age from date of birth
     */
    public static function calculateAge($dob) {
        if (empty($dob) || $dob == '0000-00-00') {
            return ['years' => 0, 'months' => 0, 'days' => 0, 'formatted' => 'N/A'];
        }
        
        try {
            $birthDate = new DateTime($dob);
            $today = new DateTime('today');
            $diff = $birthDate->diff($today);
            
            return [
                'years' => $diff->y,
                'months' => $diff->m,
                'days' => $diff->d,
                'formatted' => $diff->y . 'Y ' . $diff->m . 'M ' . $diff->d . 'D'
            ];
        } catch (Exception $e) {
            return ['years' => 0, 'months' => 0, 'days' => 0, 'formatted' => 'N/A'];
        }
    }
    
    /**
     * Calculate date of birth from age
     */
    public static function calculateDobFromAge($years, $months = 0) {
        if (empty($years) && empty($months)) {
            return null;
        }
        
        try {
            $today = new DateTime('today');
            $today->modify("-$years years");
            $today->modify("-$months months");
            return $today->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }
    
    public static function generatePatientCode() {
        $prefix = 'PAT';
        $date = date('Ymd');
        $random = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
        $code = $prefix . $date . $random;
        
        $db = Database::getInstance()->getConnection();
        $result = $db->query("SELECT COUNT(*) as count FROM patients WHERE patient_code = '$code'");
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            return self::generatePatientCode();
        }
        return $code;
    }
    
    public static function generateIdCardNumber() {
        $prefix = 'UNIDIA';
        $year = date('Y');
        $month = date('m');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        $number = $prefix . $year . $month . $random;
        
        $db = Database::getInstance()->getConnection();
        $result = $db->query("SELECT COUNT(*) as count FROM patients WHERE patient_id_card_number = '$number'");
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            return self::generateIdCardNumber();
        }
        return $number;
    }
    
    public static function create($data) {
        $db = Database::getInstance()->getConnection();
        
        $patient_code = self::generatePatientCode();
        $id_card_number = self::generateIdCardNumber();
        $registration_date = date('Y-m-d');
        
        $full_name = isset($data['full_name']) ? $db->real_escape_string($data['full_name']) : '';
        $name_parts = explode(' ', trim($full_name), 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        $email = isset($data['email']) ? $db->real_escape_string($data['email']) : '';
        $phone = $db->real_escape_string($data['phone']);
        $gender = isset($data['gender']) ? $db->real_escape_string($data['gender']) : '';
        $dob = isset($data['date_of_birth']) && !empty($data['date_of_birth']) ? "'" . $db->real_escape_string($data['date_of_birth']) . "'" : "NULL";
        $blood_group = isset($data['blood_group']) ? $db->real_escape_string($data['blood_group']) : '';
        
        $address = isset($data['address']) ? $db->real_escape_string($data['address']) : '';
        $division_id = isset($data['division_id']) && $data['division_id'] > 0 ? (int)$data['division_id'] : "NULL";
        $district_id = isset($data['district_id']) && $data['district_id'] > 0 ? (int)$data['district_id'] : "NULL";
        $thana_id = isset($data['thana_id']) && $data['thana_id'] > 0 ? (int)$data['thana_id'] : "NULL";
        
        $portal_password = password_hash($phone, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO patients (
            patient_code, patient_id_card_number, full_name, first_name, last_name, email, phone, 
            gender, date_of_birth, blood_group, address, division_id, district_id, thana_id,
            portal_password, registration_date, status, created_at, updated_at
        ) VALUES (
            '$patient_code', '$id_card_number', '$full_name', '$first_name', '$last_name', '$email', '$phone',
            '$gender', $dob, '$blood_group', '$address', $division_id, $district_id, $thana_id,
            '$portal_password', '$registration_date', 'active', NOW(), NOW()
        )";
        
        if ($db->query($sql)) {
            return $db->insert_id;
        }
        return false;
    }
    
    public static function find($id) {
        $db = Database::getInstance()->getConnection();
        $id = (int)$id;
        $result = $db->query("SELECT p.*, 
                              d.name as division_name, 
                              dist.name as district_name, 
                              t.name as thana_name
                              FROM patients p
                              LEFT JOIN divisions d ON p.division_id = d.id
                              LEFT JOIN districts dist ON p.district_id = dist.id
                              LEFT JOIN thanas t ON p.thana_id = t.id
                              WHERE p.id = $id");
        return $result->fetch_assoc();
    }
    
    public static function getAll($limit = 20, $offset = 0, $search = '') {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT p.*, d.name as division_name, dist.name as district_name, t.name as thana_name
                FROM patients p
                LEFT JOIN divisions d ON p.division_id = d.id
                LEFT JOIN districts dist ON p.district_id = dist.id
                LEFT JOIN thanas t ON p.thana_id = t.id
                WHERE p.status='active'";
        
        if (!empty($search)) {
            $search = $db->real_escape_string($search);
            $sql .= " AND (p.full_name LIKE '%$search%' OR p.phone LIKE '%$search%' OR p.patient_code LIKE '%$search%' OR p.email LIKE '%$search%')";
        }
        $sql .= " ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
        
        $result = $db->query($sql);
        $patients = [];
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
        return $patients;
    }
    
    public static function count($search = '') {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT COUNT(*) as total FROM patients WHERE status='active'";
        if (!empty($search)) {
            $search = $db->real_escape_string($search);
            $sql .= " AND (full_name LIKE '%$search%' OR phone LIKE '%$search%' OR patient_code LIKE '%$search%' OR email LIKE '%$search%')";
        }
        
        $result = $db->query($sql);
        $row = $result->fetch_assoc();
        return $row['total'];
    }
    
    public static function update($id, $data) {
        $db = Database::getInstance()->getConnection();
        $id = (int)$id;
        
        $fullName = $db->real_escape_string($data['full_name'] ?? ($data['first_name'] . ' ' . $data['last_name']));
        $firstName = $db->real_escape_string($data['first_name']);
        $lastName = $db->real_escape_string($data['last_name']);
        $email = !empty($data['email']) ? "'" . $db->real_escape_string($data['email']) . "'" : "NULL";
        $phone = $db->real_escape_string($data['phone']);
        $gender = $db->real_escape_string($data['gender']);
        $dateOfBirth = !empty($data['date_of_birth']) ? "'" . $db->real_escape_string($data['date_of_birth']) . "'" : "NULL";
        $bloodGroup = !empty($data['blood_group']) ? "'" . $db->real_escape_string($data['blood_group']) . "'" : "NULL";
        $address = $db->real_escape_string($data['address']);
        $divisionId = !empty($data['division_id']) ? (int)$data['division_id'] : "NULL";
        $districtId = !empty($data['district_id']) ? (int)$data['district_id'] : "NULL";
        $thanaId = !empty($data['thana_id']) ? (int)$data['thana_id'] : "NULL";
        
        $emergencyName = !empty($data['emergency_contact_name']) ? "'" . $db->real_escape_string($data['emergency_contact_name']) . "'" : "NULL";
        $emergencyPhone = !empty($data['emergency_contact_phone']) ? "'" . $db->real_escape_string($data['emergency_contact_phone']) . "'" : "NULL";
        $emergencyRelation = !empty($data['emergency_contact_relation']) ? "'" . $db->real_escape_string($data['emergency_contact_relation']) . "'" : "NULL";
        
        $sql = "UPDATE patients SET 
            full_name = '$fullName',
            first_name = '$firstName',
            last_name = '$lastName',
            email = $email,
            phone = '$phone',
            gender = '$gender',
            date_of_birth = $dateOfBirth,
            blood_group = $bloodGroup,
            address = '$address',
            division_id = $divisionId,
            district_id = $districtId,
            thana_id = $thanaId,
            emergency_contact_name = $emergencyName,
            emergency_contact_phone = $emergencyPhone,
            emergency_contact_relation = $emergencyRelation,
            updated_at = NOW()
        WHERE id = $id";
        
        return $db->query($sql);
    }
    
    // ===== LOCATION HELPER METHODS =====
    
    public static function getDivisions() {
        $db = Database::getInstance()->getConnection();
        $result = $db->query("SELECT * FROM divisions WHERE status = 1 ORDER BY name");
        $divisions = [];
        while ($row = $result->fetch_assoc()) {
            $divisions[] = $row;
        }
        return $divisions;
    }
    
    public static function getDistrictsByDivision($division_id) {
        $db = Database::getInstance()->getConnection();
        $division_id = (int)$division_id;
        $result = $db->query("SELECT * FROM districts WHERE division_id = $division_id AND status = 1 ORDER BY name");
        $districts = [];
        while ($row = $result->fetch_assoc()) {
            $districts[] = $row;
        }
        return $districts;
    }
    
    public static function getThanasByDistrict($district_id) {
        $db = Database::getInstance()->getConnection();
        $district_id = (int)$district_id;
        $result = $db->query("SELECT * FROM thanas WHERE district_id = $district_id AND status = 1 ORDER BY name");
        $thanas = [];
        while ($row = $result->fetch_assoc()) {
            $thanas[] = $row;
        }
        return $thanas;
    }

    // ===== INSTANCE METHODS =====
    
    public function all() {
        $sql = "SELECT id, patient_code, first_name, last_name, full_name, phone, email 
                FROM patients 
                WHERE status = 'active' 
                ORDER BY first_name ASC";
        $result = $this->db->query($sql);
        $patients = [];
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
        return $patients;
    }
    
    public function search($term) {
        $term = "%{$term}%";
        $sql = "SELECT id, patient_code, first_name, last_name, full_name, phone, email 
                FROM patients 
                WHERE status = 'active' 
                AND (first_name LIKE ? OR last_name LIKE ? OR full_name LIKE ? OR patient_code LIKE ? OR phone LIKE ?)
                ORDER BY first_name ASC 
                LIMIT 20";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('sssss', $term, $term, $term, $term, $term);
            $stmt->execute();
            $result = $stmt->get_result();
            $patients = [];
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
            return $patients;
        }
        return [];
    }
    
    public function getPaginated($limit, $offset, $search = '') {
        $sql = "SELECT p.*, d.name as division_name, dist.name as district_name, t.name as thana_name
                FROM patients p
                LEFT JOIN divisions d ON p.division_id = d.id
                LEFT JOIN districts dist ON p.district_id = dist.id
                LEFT JOIN thanas t ON p.thana_id = t.id
                WHERE p.status = 'active'";
        
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $sql .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.full_name LIKE ? OR p.patient_code LIKE ? OR p.phone LIKE ?)";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
            $types = 'sssss';
        }
        
        $sql .= " ORDER BY p.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $patients = [];
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
            return $patients;
        }
        return [];
    }
    
    public function countPatients($search = '') {
        $sql = "SELECT COUNT(*) as total FROM patients WHERE status = 'active'";
        $params = [];
        $types = '';
        
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR full_name LIKE ? OR patient_code LIKE ? OR phone LIKE ?)";
            $params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm];
            $types = 'sssss';
        }
        
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return $row['total'] ?? 0;
        }
        return 0;
    }
    
    public static function getAllForDropdown() {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT id, patient_code, first_name, last_name, full_name, phone 
                FROM patients 
                WHERE status = 'active' 
                ORDER BY first_name ASC";
        $result = $db->query($sql);
        $patients = [];
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
        return $patients;
    }
}
?>