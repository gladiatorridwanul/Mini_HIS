<?php
// app/models/Doctor.php

class Doctor {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // ==================== FIND ====================
    public static function find($id) {
        $db = Database::getInstance()->getConnection();
        $id = (int)$id;
        
        $query = "SELECT d.*, u.id as user_id, u.email, u.phone, u.address, u.city, u.state, u.country, u.postal_code,
                         u.first_name, u.last_name, u.title, u.gender, u.date_of_birth, u.status as user_status,
                         u.employee_id, u.created_at as user_created_at, dep.name as department_name
                  FROM doctors d
                  JOIN users u ON d.user_id = u.id
                  LEFT JOIN departments dep ON d.department_id = dep.id
                  WHERE d.id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result && $result->num_rows > 0) {
            $doctor = $result->fetch_assoc();
            // Get services
            $services = $db->query("SELECT * FROM doctor_services WHERE doctor_id = {$doctor['id']} AND status = 'active'");
            $doctor['services'] = [];
            while($row = $services->fetch_assoc()) {
                $doctor['services'][] = $row;
            }
            return $doctor;
        }
        return null;
    }
    
    // ==================== GET ALL PAGINATED (FIXED) ====================
    public static function getAllPaginated($search = '', $limit = 10, $offset = 0) {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT d.*, u.id as user_id, u.first_name, u.last_name, u.title, u.email, u.phone, 
                       u.address, u.city, u.state, u.country, u.postal_code, u.gender, u.date_of_birth,
                       u.employee_id, u.status as user_status,
                       dep.name as department_name
                FROM doctors d
                JOIN users u ON d.user_id = u.id
                LEFT JOIN departments dep ON d.department_id = dep.id
                WHERE d.status != 'deleted'";
        
        if(!empty($search)) {
            $search = $db->real_escape_string($search);
            $sql .= " AND (u.first_name LIKE '%$search%' 
                          OR u.last_name LIKE '%$search%' 
                          OR d.specialization LIKE '%$search%' 
                          OR d.bmdc_number LIKE '%$search%'
                          OR dep.name LIKE '%$search%')";
        }
        
        $sql .= " ORDER BY u.first_name ASC LIMIT $limit OFFSET $offset";
        $result = $db->query($sql);
        $doctors = [];
        while($row = $result->fetch_assoc()) {
            // Get services
            $servicesResult = $db->query("SELECT * FROM doctor_services WHERE doctor_id = {$row['id']} AND status = 'active'");
            $row['services'] = [];
            while($service = $servicesResult->fetch_assoc()) {
                $row['services'][] = $service;
            }
            $doctors[] = $row;
        }
        return $doctors;
    }
    
    // ==================== GET TOTAL COUNT ====================
    public static function getTotalCount($search = '') {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as total FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.status != 'deleted'";
        if(!empty($search)) {
            $search = $db->real_escape_string($search);
            $sql .= " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR d.specialization LIKE '%$search%' OR d.bmdc_number LIKE '%$search%')";
        }
        $result = $db->query($sql);
        return $result->fetch_assoc()['total'] ?? 0;
    }
    
    // ==================== CREATE DOCTOR ====================
    public static function create($data) {
        $db = Database::getInstance()->getConnection();
        $db->begin_transaction();
        
        try {
            // Check if email exists
            if (!empty($data['email'])) {
                $check = $db->query("SELECT id FROM users WHERE email = '{$data['email']}'");
                if ($check && $check->num_rows > 0) {
                    throw new Exception("Email already exists");
                }
            }
            
            // Check for duplicate BMDC
            if (!empty($data['bmdc_number'])) {
                $checkBmdc = $db->query("SELECT id FROM doctors WHERE bmdc_number = '{$data['bmdc_number']}'");
                if ($checkBmdc && $checkBmdc->num_rows > 0) {
                    throw new Exception("BMDC number already exists");
                }
            }
            
            // Generate employee ID
            $employeeId = 'DOC' . date('Ymd') . rand(100, 999);
            
            // Password handling
            $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : password_hash('password123', PASSWORD_DEFAULT);
            
            // ==================== INSERT USER ====================
            $userSql = "INSERT INTO users (
                role_id, employee_id, first_name, title, last_name, email, password, phone, 
                gender, date_of_birth, address, city, state, country, postal_code, status
            ) VALUES (3, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            
            $stmt = $db->prepare($userSql);
            $title = $data['title'] ?? '';
            $first_name = $data['first_name'];
            $last_name = $data['last_name'];
            $email = $data['email'];
            $phone = $data['phone'] ?? '';
            $gender = $data['gender'] ?? '';
            $date_of_birth = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
            $address = $data['address'] ?? '';
            $city = $data['city'] ?? '';
            $state = $data['state'] ?? '';
            $country = $data['country'] ?? 'Bangladesh';
            $postal_code = $data['postal_code'] ?? '';
            
            $stmt->bind_param(
                'ssssssssssssss', 
                $employeeId, $first_name, $title, $last_name, $email, $password, $phone,
                $gender, $date_of_birth, $address, $city, $state, $country, $postal_code
            );
            $stmt->execute();
            $userId = $db->insert_id;
            
            // ==================== INSERT DOCTOR ====================
            $doctorSql = "INSERT INTO doctors (
                user_id, department_id, specialization, qualification, experience_years, 
                bmdc_number, consultation_fee, doctor_info_en, doctor_info_bn, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";
            
            $stmt = $db->prepare($doctorSql);
            $department_id = $data['department_id'] ?? 0;
            $specialization = $data['specialization'];
            $qualification = $data['qualification'];
            $experience_years = $data['experience_years'] ?? 0;
            $bmdc_number = $data['bmdc_number'];
            $consultation_fee = $data['consultation_fee'] ?? 0;
            $doctor_info_en = $data['doctor_info_en'] ?? '';
            $doctor_info_bn = $data['doctor_info_bn'] ?? '';
            
            $stmt->bind_param('iissisiss', 
                $userId, $department_id, $specialization, $qualification, $experience_years, 
                $bmdc_number, $consultation_fee, $doctor_info_en, $doctor_info_bn
            );
            $stmt->execute();
            $doctorId = $db->insert_id;
            
            // ==================== SAVE SERVICES ====================
            if(isset($data['services']) && !empty($data['services'])) {
                $serviceSql = "INSERT INTO doctor_services (doctor_id, service_name, service_price, commission_percentage, status) 
                               VALUES (?, ?, ?, ?, 'active')";
                $stmt = $db->prepare($serviceSql);
                foreach($data['services'] as $service) {
                    if(!empty($service['name']) && !empty($service['price'])) {
                        $stmt->bind_param('isdd', $doctorId, $service['name'], $service['price'], $service['commission']);
                        $stmt->execute();
                    }
                }
            }
            
            // ==================== CREATE PAYROLL (Optional) ====================
            if(!empty($data['basic_salary']) && $data['basic_salary'] > 0) {
                $payrollSql = "INSERT INTO payroll (
                    user_id, payroll_month, basic_salary, hra, medical_allowance, conveyance, 
                    other_allowances, provident_fund, professional_tax, income_tax, other_deductions, 
                    net_salary, status
                ) VALUES (
                    ?, DATE_FORMAT(NOW(), '%Y-%m'), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending'
                )";
                $stmt = $db->prepare($payrollSql);
                $basic_salary = $data['basic_salary'] ?? 0;
                $hra = $data['hra'] ?? 0;
                $medical_allowance = $data['medical_allowance'] ?? 0;
                $conveyance = $data['conveyance'] ?? 0;
                $other_allowances = $data['other_allowances'] ?? 0;
                $provident_fund = $data['provident_fund'] ?? 0;
                $professional_tax = $data['professional_tax'] ?? 0;
                $income_tax = $data['income_tax'] ?? 0;
                $other_deductions = $data['other_deductions'] ?? 0;
                $net_salary = $basic_salary + $hra + $medical_allowance + $conveyance + $other_allowances 
                            - $provident_fund - $professional_tax - $income_tax - $other_deductions;
                
                $stmt->bind_param(
                    'idddddddddd', 
                    $userId, $basic_salary, $hra, $medical_allowance, $conveyance, 
                    $other_allowances, $provident_fund, $professional_tax, $income_tax, 
                    $other_deductions, $net_salary
                );
                $stmt->execute();
            }
            
            $db->commit();
            return $doctorId;
            
        } catch(Exception $e) {
            $db->rollback();
            error_log("Doctor creation error: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== UPDATE DOCTOR ====================
    public static function update($id, $data) {
        $db = Database::getInstance()->getConnection();
        $db->begin_transaction();
        
        try {
            // Get doctor to get user_id
            $doctor = self::find($id);
            if(!$doctor) return false;
            
            $userId = $doctor['user_id'];
            
            // ==================== UPDATE USER ====================
            $userSql = "UPDATE users SET 
                        first_name = ?, 
                        title = ?,
                        last_name = ?, 
                        email = ?, 
                        phone = ?, 
                        gender = ?, 
                        date_of_birth = ?,
                        address = ?,
                        city = ?,
                        state = ?,
                        country = ?,
                        postal_code = ?
                        WHERE id = ?";
            $stmt = $db->prepare($userSql);
            $title = $data['title'] ?? '';
            $first_name = $data['first_name'];
            $last_name = $data['last_name'];
            $email = $data['email'];
            $phone = $data['phone'] ?? '';
            $gender = $data['gender'] ?? '';
            $date_of_birth = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
            $address = $data['address'] ?? '';
            $city = $data['city'] ?? '';
            $state = $data['state'] ?? '';
            $country = $data['country'] ?? 'Bangladesh';
            $postal_code = $data['postal_code'] ?? '';
            
            $stmt->bind_param(
                'ssssssssssssi', 
                $first_name, $title, $last_name, $email, $phone, $gender, 
                $date_of_birth, $address, $city, $state, $country, $postal_code, $userId
            );
            $stmt->execute();
            
            // ==================== UPDATE DOCTOR ====================
            $doctorSql = "UPDATE doctors SET 
                          department_id = ?,
                          specialization = ?,
                          qualification = ?,
                          experience_years = ?,
                          bmdc_number = ?,
                          consultation_fee = ?,
                          doctor_info_en = ?,
                          doctor_info_bn = ?
                          WHERE id = ?";
            $stmt = $db->prepare($doctorSql);
            $department_id = $data['department_id'] ?? 0;
            $specialization = $data['specialization'];
            $qualification = $data['qualification'];
            $experience_years = $data['experience_years'] ?? 0;
            $bmdc_number = $data['bmdc_number'];
            $consultation_fee = $data['consultation_fee'] ?? 0;
            $doctor_info_en = $data['doctor_info_en'] ?? '';
            $doctor_info_bn = $data['doctor_info_bn'] ?? '';
            
            $stmt->bind_param(
                'issisdssi', 
                $department_id, $specialization, $qualification, $experience_years, 
                $bmdc_number, $consultation_fee, $doctor_info_en, $doctor_info_bn, $id
            );
            $stmt->execute();
            
            // ==================== UPDATE PASSWORD (if provided) ====================
            if(!empty($data['password'])) {
                $password = password_hash($data['password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param('si', $password, $userId);
                $stmt->execute();
            }
            
            // ==================== UPDATE SERVICES ====================
            if(isset($data['services']) && !empty($data['services'])) {
                // Delete existing services
                $db->query("DELETE FROM doctor_services WHERE doctor_id = $id");
                
                // Insert new services
                $serviceSql = "INSERT INTO doctor_services (doctor_id, service_name, service_price, commission_percentage, status) 
                               VALUES (?, ?, ?, ?, 'active')";
                $stmt = $db->prepare($serviceSql);
                foreach($data['services'] as $service) {
                    if(!empty($service['name']) && !empty($service['price'])) {
                        $stmt->bind_param('isdd', $id, $service['name'], $service['price'], $service['commission']);
                        $stmt->execute();
                    }
                }
            }
            
            $db->commit();
            return true;
            
        } catch(Exception $e) {
            $db->rollback();
            error_log("Doctor update error: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== DELETE DOCTOR (Soft Delete) ====================
    public static function delete($id) {
        $db = Database::getInstance()->getConnection();
        $doctor = self::find($id);
        if(!$doctor) return false;
        
        $userId = $doctor['user_id'];
        $db->begin_transaction();
        
        try {
            // Soft delete doctor
            $db->query("UPDATE doctors SET status = 'deleted' WHERE id = $id");
            // Deactivate user
            $db->query("UPDATE users SET status = 'inactive' WHERE id = $userId");
            $db->commit();
            return true;
        } catch(Exception $e) {
            $db->rollback();
            error_log("Doctor delete error: " . $e->getMessage());
            return false;
        }
    }
    
    // ==================== GET DEPARTMENTS ====================
    public static function getDepartments() {
        $db = Database::getInstance()->getConnection();
        $result = $db->query("SELECT id, name FROM departments WHERE status = 'active' ORDER BY name");
        $departments = [];
        while($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
        return $departments;
    }
    
    // ==================== GET ACTIVE DOCTORS ====================
    public static function getActiveDoctors() {
        $db = Database::getInstance()->getConnection();
        $result = $db->query("SELECT d.id, d.user_id, u.first_name, u.last_name, u.title, d.specialization, d.consultation_fee 
                              FROM doctors d 
                              JOIN users u ON d.user_id = u.id 
                              WHERE d.status = 'active' AND u.status = 'active'
                              ORDER BY u.first_name");
        $doctors = [];
        while($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        return $doctors;
    }
    
    // ==================== GET DOCTOR SERVICES ====================
    public static function getDoctorServices($doctorId) {
        $db = Database::getInstance()->getConnection();
        $doctorId = (int)$doctorId;
        
        $result = $db->query("SELECT * FROM doctor_services WHERE doctor_id = $doctorId AND status = 'active' ORDER BY id ASC");
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        return $services;
    }

    // ==================== GET DOCTOR BY ID ====================
public static function getDoctorById($id) {
    $db = Database::getInstance()->getConnection();
    $id = (int)$id;
    
    $query = "SELECT d.*, u.first_name, u.last_name, u.title, u.email, u.phone
              FROM doctors d
              JOIN users u ON d.user_id = u.id
              WHERE d.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// app/models/Doctor.php

/**
 * Sync doctor status with user status
 * This should be called whenever a user's status is updated
 * 
 * @param int $userId The user ID
 * @param string $userStatus The new user status ('active', 'inactive', 'suspended')
 * @return bool
 */
public static function syncStatusWithUser($userId, $userStatus) {
    $db = Database::getInstance()->getConnection();
    $userId = (int)$userId;
    
    // Map user status to doctor status
    $doctorStatus = 'active';
    if ($userStatus == 'inactive' || $userStatus == 'suspended') {
        $doctorStatus = 'inactive';
    }
    
    $query = "UPDATE doctors SET status = '$doctorStatus' WHERE user_id = $userId";
    return $db->query($query);
}

/**
 * Update user status and sync doctor status
 * 
 * @param int $userId The user ID
 * @param string $newStatus The new status ('active', 'inactive', 'suspended')
 * @return bool
 */
public static function updateUserStatusAndSyncDoctor($userId, $newStatus) {
    $db = Database::getInstance()->getConnection();
    $userId = (int)$userId;
    $newStatus = $db->real_escape_string($newStatus);
    
    // Begin transaction
    $db->begin_transaction();
    
    try {
        // Update user status
        $userQuery = "UPDATE users SET status = '$newStatus' WHERE id = $userId";
        $userResult = $db->query($userQuery);
        
        if (!$userResult) {
            throw new Exception("Failed to update user status");
        }
        
        // Sync doctor status
        $doctorStatus = ($newStatus == 'active') ? 'active' : 'inactive';
        $doctorQuery = "UPDATE doctors SET status = '$doctorStatus' WHERE user_id = $userId";
        $doctorResult = $db->query($doctorQuery);
        
        if (!$doctorResult) {
            throw new Exception("Failed to sync doctor status");
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollback();
        return false;
    }
}
}
?>