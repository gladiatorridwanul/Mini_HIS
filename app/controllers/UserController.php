<?php
// app/controllers/UserController.php

class UserController extends Controller {
    
    // ==================== USER LISTING ====================
    public function index() {
        $this->checkAuth();
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? $this->db->real_escape_string($_GET['search']) : '';
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Build the query with search
        $whereClause = "WHERE 1=1";
        if (!empty($search)) {
            $whereClause .= " AND (u.first_name LIKE '%$search%' 
                                  OR u.last_name LIKE '%$search%' 
                                  OR u.email LIKE '%$search%' 
                                  OR u.employee_id LIKE '%$search%')";
        }
        
        // Get total users count
        $countQuery = "SELECT COUNT(*) as total FROM users u LEFT JOIN roles r ON u.role_id = r.id $whereClause";
        $countResult = $this->db->query($countQuery);
        $totalUsers = $countResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($totalUsers / $limit);
        
        // Get users with pagination
        $query = "SELECT u.*, r.name as role_name, r.slug as role_slug 
                  FROM users u 
                  LEFT JOIN roles r ON u.role_id = r.id 
                  $whereClause
                  ORDER BY u.created_at DESC 
                  LIMIT $limit OFFSET $offset";
        
        $users = $this->db->query($query);
        $userList = [];
        while($row = $users->fetch_assoc()) { 
            $userList[] = $row; 
        }
        
        $roles = $this->db->query("SELECT id, name FROM roles");
        $roleList = [];
        while($r = $roles->fetch_assoc()) { $roleList[] = $r; }
        
        $this->view('users/index', [
            'users' => $userList, 
            'roles' => $roleList,
            'totalUsers' => $totalUsers,
            'totalPages' => $totalPages,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'search' => $search
        ], 'User Management');
    }
    
    // ==================== CREATE USER ====================
    public function create() {
        $this->checkAuth();
        
        $roles = $this->db->query("SELECT * FROM roles");
        $roleList = [];
        while($r = $roles->fetch_assoc()) { $roleList[] = $r; }
        
        $departments = $this->db->query("SELECT id, name FROM departments WHERE status='active'");
        $deptList = [];
        while($d = $departments->fetch_assoc()) { $deptList[] = $d; }
        
        $this->view('users/create', ['roles' => $roleList, 'departments' => $deptList], 'Create User');
    }
    
    public function store() {
        $this->checkAuth();
        
        $roleId = (int)$_POST['role_id'];
        $title = $this->db->real_escape_string($_POST['title'] ?? '');
        $firstName = $this->db->real_escape_string($_POST['first_name']);
        $lastName = $this->db->real_escape_string($_POST['last_name']);
        $email = $this->db->real_escape_string($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $phone = $this->db->real_escape_string($_POST['phone'] ?? '');
        $gender = $this->db->real_escape_string($_POST['gender'] ?? '');
        $dob = $this->db->real_escape_string($_POST['date_of_birth'] ?? '');
        $address = $this->db->real_escape_string($_POST['address'] ?? '');
        $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $employeeId = 'EMP' . date('Ymd') . rand(100, 999);
        
        $this->db->query("INSERT INTO users (role_id, employee_id, first_name, title, last_name, email, password, phone, gender, date_of_birth, address, status) 
                         VALUES ($roleId, '$employeeId', '$firstName', '$title', '$lastName', '$email', '$password', '$phone', '$gender', " . ($dob ? "'$dob'" : "NULL") . ", '$address', 'active')");
        $userId = $this->db->insert_id;
        
        // Save department to user_departments
        if($departmentId) {
            $this->db->query("INSERT INTO user_departments (user_id, department_id) VALUES ($userId, $departmentId)");
        }
        
        // ==================== DOCTOR CREATION ====================
        if($roleId == 3) {
            $specialization = $this->db->real_escape_string($_POST['specialization'] ?? 'General Medicine');
            $qualification = $this->db->real_escape_string($_POST['qualification'] ?? '');
            $experienceYears = (int)($_POST['experience_years'] ?? 0);
            $licenseNumber = $this->db->real_escape_string($_POST['license_number'] ?? '');
            $consultationFee = (float)($_POST['consultation_fee'] ?? 0);
            $commissionPercentage = (float)($_POST['commission_percentage'] ?? 0);
            $doctorInfoEn = $this->db->real_escape_string($_POST['doctor_info_en'] ?? '');
            $doctorInfoBn = $this->db->real_escape_string($_POST['doctor_info_bn'] ?? '');
            
            // Use the same department_id for doctor
            $doctorDeptId = $departmentId ?: 1;
            
            // Insert doctor record with department
            $this->db->query("INSERT INTO doctors (
                user_id, department_id, specialization, qualification, experience_years, 
                bmdc_number, consultation_fee, commission_per_consultation, 
                doctor_info_en, doctor_info_bn, status
            ) VALUES (
                $userId, $doctorDeptId, '$specialization', '$qualification', $experienceYears,
                '$licenseNumber', $consultationFee, $commissionPercentage,
                '$doctorInfoEn', '$doctorInfoBn', 'active'
            )");
            
            $doctorId = $this->db->insert_id;
            
            // ==================== SAVE DOCTOR SERVICES ====================
            $serviceNames = $_POST['service_name'] ?? [];
            $servicePrices = $_POST['service_price'] ?? [];
            $serviceCommissions = $_POST['service_commission'] ?? [];
            
            for($i = 0; $i < count($serviceNames); $i++) {
                $serviceName = $this->db->real_escape_string($serviceNames[$i] ?? '');
                $servicePrice = (float)($servicePrices[$i] ?? 0);
                $serviceCommission = (float)($serviceCommissions[$i] ?? 0);
                
                if(!empty($serviceName) && $servicePrice > 0) {
                    $this->db->query("INSERT INTO doctor_services (
                        doctor_id, service_name, service_type, service_price, commission_percentage, status
                    ) VALUES (
                        $doctorId, '$serviceName', 'consultation', $servicePrice, $serviceCommission, 'active'
                    )");
                }
            }
        }
        
        // ==================== SAVE PAYROLL ====================
        $this->savePayrollData($userId, $roleId);
        
        $_SESSION['success'] = "User created successfully! ID: $employeeId";
        $this->redirect('/admin/users');
    }
    
    // ==================== EDIT USER ====================
    public function edit($id) {
        $this->checkAuth();
        
        $id = (int)$id;
        $user = $this->db->query("SELECT * FROM users WHERE id = $id")->fetch_assoc();
        $roles = $this->db->query("SELECT * FROM roles");
        $roleList = [];
        while($r = $roles->fetch_assoc()) { $roleList[] = $r; }
        
        $departments = $this->db->query("SELECT id, name FROM departments WHERE status='active'");
        $deptList = [];
        while($d = $departments->fetch_assoc()) { $deptList[] = $d; }
        
        // Get department from user_departments
        $userDept = $this->db->query("SELECT department_id FROM user_departments WHERE user_id = $id")->fetch_assoc();
        $userDepartmentId = $userDept['department_id'] ?? null;
        
        $doctorInfo = null;
        $doctorServices = [];
        
        if($user['role_id'] == 3) {
            $doctorInfo = $this->db->query("SELECT * FROM doctors WHERE user_id = $id")->fetch_assoc();
            
            // IMPORTANT: If doctor has a department, use that instead of user_departments
            if($doctorInfo && !empty($doctorInfo['department_id'])) {
                $userDepartmentId = $doctorInfo['department_id'];
            }
            
            // Get doctor services
            if($doctorInfo) {
                $servicesResult = $this->db->query("SELECT * FROM doctor_services WHERE doctor_id = {$doctorInfo['id']} AND status = 'active'");
                while($row = $servicesResult->fetch_assoc()) {
                    $doctorServices[] = $row;
                }
            }
        }
        
        // Get payroll data
        $payrollData = null;
        $payrollQuery = $this->db->query("SELECT * FROM payroll WHERE user_id = $id ORDER BY payroll_month DESC LIMIT 1");
        if($payrollQuery && $payrollQuery->num_rows > 0) {
            $payrollData = $payrollQuery->fetch_assoc();
        }
        
        $this->view('users/edit', [
            'user' => $user, 
            'roles' => $roleList, 
            'departments' => $deptList,
            'userDepartment' => $userDepartmentId,
            'doctorInfo' => $doctorInfo,
            'doctorServices' => $doctorServices,
            'payrollData' => $payrollData
        ], 'Edit User');
    }
    
    public function update($id) {
        $this->checkAuth();
        
        $id = (int)$id;
        $roleId = (int)$_POST['role_id'];
        $title = $this->db->real_escape_string($_POST['title'] ?? '');
        $firstName = $this->db->real_escape_string($_POST['first_name']);
        $lastName = $this->db->real_escape_string($_POST['last_name']);
        $email = $this->db->real_escape_string($_POST['email']);
        $phone = $this->db->real_escape_string($_POST['phone'] ?? '');
        $gender = $this->db->real_escape_string($_POST['gender'] ?? '');
        $dob = $this->db->real_escape_string($_POST['date_of_birth'] ?? '');
        $address = $this->db->real_escape_string($_POST['address'] ?? '');
        $status = $this->db->real_escape_string($_POST['status']);
        $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        
        $this->db->query("UPDATE users SET 
                         role_id = $roleId, 
                         title = '$title',
                         first_name = '$firstName', 
                         last_name = '$lastName', 
                         email = '$email', 
                         phone = '$phone',
                         gender = '$gender',
                         date_of_birth = " . ($dob ? "'$dob'" : "NULL") . ",
                         address = '$address', 
                         status = '$status' 
                         WHERE id = $id");
        
        if(!empty($_POST['password'])) {
            $this->db->query("UPDATE users SET password = '" . password_hash($_POST['password'], PASSWORD_DEFAULT) . "' WHERE id = $id");
        }
        
        // Update department - always sync
        $this->db->query("DELETE FROM user_departments WHERE user_id = $id");
        if($departmentId) {
            $this->db->query("INSERT INTO user_departments (user_id, department_id) VALUES ($id, $departmentId)");
        }
        
        // ==================== UPDATE DOCTOR ====================
        if($roleId == 3) {
            $specialization = $this->db->real_escape_string($_POST['specialization'] ?? 'General Medicine');
            $qualification = $this->db->real_escape_string($_POST['qualification'] ?? '');
            $experienceYears = (int)($_POST['experience_years'] ?? 0);
            $licenseNumber = $this->db->real_escape_string($_POST['license_number'] ?? '');
            $consultationFee = (float)($_POST['consultation_fee'] ?? 0);
            $commissionPercentage = (float)($_POST['commission_percentage'] ?? 0);
            $doctorInfoEn = $this->db->real_escape_string($_POST['doctor_info_en'] ?? '');
            $doctorInfoBn = $this->db->real_escape_string($_POST['doctor_info_bn'] ?? '');
            
            // Use the department_id from form, fallback to 1 if not provided
            $doctorDeptId = $departmentId ?: 1;
            
            $check = $this->db->query("SELECT id FROM doctors WHERE user_id = $id");
            if($check && $check->num_rows > 0) {
                $doctorRow = $check->fetch_assoc();
                $doctorId = $doctorRow['id'];
                
                $this->db->query("UPDATE doctors SET 
                                 department_id = $doctorDeptId,
                                 specialization = '$specialization', 
                                 qualification = '$qualification',
                                 experience_years = $experienceYears,
                                 bmdc_number = '$licenseNumber', 
                                 consultation_fee = $consultationFee,
                                 commission_per_consultation = $commissionPercentage,
                                 doctor_info_en = '$doctorInfoEn',
                                 doctor_info_bn = '$doctorInfoBn'
                                 WHERE user_id = $id");
            } else {
                $this->db->query("INSERT INTO doctors (user_id, department_id, specialization, qualification, experience_years, bmdc_number, consultation_fee, commission_per_consultation, doctor_info_en, doctor_info_bn, status) 
                                 VALUES ($id, $doctorDeptId, '$specialization', '$qualification', $experienceYears, '$licenseNumber', $consultationFee, $commissionPercentage, '$doctorInfoEn', '$doctorInfoBn', 'active')");
                $doctorId = $this->db->insert_id;
            }
            
            // ==================== UPDATE DOCTOR SERVICES ====================
            // ... (keep existing service update code)
            $serviceIds = $_POST['service_id'] ?? [];
            $serviceNames = $_POST['service_name'] ?? [];
            $servicePrices = $_POST['service_price'] ?? [];
            $serviceCommissions = $_POST['service_commission'] ?? [];
            
            // Get existing service IDs
            $existingServices = $this->db->query("SELECT id FROM doctor_services WHERE doctor_id = $doctorId");
            $existingIds = [];
            while($row = $existingServices->fetch_assoc()) {
                $existingIds[] = $row['id'];
            }
            
            $updatedIds = [];
            
            for($i = 0; $i < count($serviceNames); $i++) {
                $serviceName = $this->db->real_escape_string($serviceNames[$i] ?? '');
                $servicePrice = (float)($servicePrices[$i] ?? 0);
                $serviceCommission = (float)($serviceCommissions[$i] ?? 0);
                $serviceId = isset($serviceIds[$i]) ? (int)$serviceIds[$i] : 0;
                
                if(!empty($serviceName) && $servicePrice > 0) {
                    if($serviceId > 0) {
                        $this->db->query("UPDATE doctor_services SET 
                                         service_name = '$serviceName',
                                         service_price = $servicePrice,
                                         commission_percentage = $serviceCommission
                                         WHERE id = $serviceId AND doctor_id = $doctorId");
                        $updatedIds[] = $serviceId;
                    } else {
                        $this->db->query("INSERT INTO doctor_services (
                            doctor_id, service_name, service_type, service_price, commission_percentage, status
                        ) VALUES (
                            $doctorId, '$serviceName', 'consultation', $servicePrice, $serviceCommission, 'active'
                        )");
                        $updatedIds[] = $this->db->insert_id;
                    }
                }
            }
            
            // Delete services that were removed
            $deleteIds = array_diff($existingIds, $updatedIds);
            if(!empty($deleteIds)) {
                $deleteStr = implode(',', $deleteIds);
                $this->db->query("DELETE FROM doctor_services WHERE id IN ($deleteStr) AND doctor_id = $doctorId");
            }
        } else {
            // If not a doctor, delete any existing doctor record
            $this->db->query("DELETE FROM doctors WHERE user_id = $id");
        }
        
        // ==================== UPDATE PAYROLL ====================
        $this->updatePayrollData($id, $roleId);
        
        $_SESSION['success'] = "User updated successfully";
        $this->redirect('/admin/users');
    }
    
    // ==================== DELETE USER ====================
    public function delete($id) {
        $this->checkAuth();
        
        $id = (int)$id;
        if($id == $_SESSION['user_id']) {
            $_SESSION['error'] = "Cannot delete your own account";
            $this->redirect('/admin/users');
        }
        $this->db->query("UPDATE users SET status='inactive' WHERE id=$id");
        $_SESSION['success'] = "User deleted successfully";
        $this->redirect('/admin/users');
    }
    
    // ==================== ROLES & PERMISSIONS ====================
public function roles() {
    $this->checkAuth();
    $this->checkPermission('manage_roles');
    
    // Get all roles with user count
    $roles = $this->db->query("SELECT r.*, COUNT(u.id) as user_count 
                               FROM roles r 
                               LEFT JOIN users u ON r.id = u.role_id 
                               GROUP BY r.id 
                               ORDER BY r.id ASC");
    $roleList = [];
    while($r = $roles->fetch_assoc()) { 
        $roleList[] = $r; 
    }
    
    // Get all permissions grouped by module
    $permissions = $this->db->query("SELECT * FROM permissions ORDER BY module, name");
    $permList = [];
    while($p = $permissions->fetch_assoc()) { 
        $module = $p['module'] ?? 'general';
        if(!isset($permList[$module])) {
            $permList[$module] = [];
        }
        $permList[$module][] = $p;
    }
    
    // Get current permissions for each role - FIXED: Always return array
    $rolePermissions = [];
    foreach($roleList as $role) {
        $perms = $this->db->query("SELECT permission_slug FROM role_permissions WHERE role_id = {$role['id']}");
        $rolePermissions[$role['id']] = [];
        if ($perms) {
            while($row = $perms->fetch_assoc()) {
                $rolePermissions[$role['id']][] = $row['permission_slug'];
            }
        }
    }
    
    $this->view('users/roles', [
        'roles' => $roleList, 
        'permissionsByModule' => $permList,
        'rolePermissions' => $rolePermissions
    ], 'Roles & Permissions');
}
    
    /**
 * Update Role Permissions (AJAX friendly)
 */
public function updateRolePermissions($id) {
    $this->checkAuth();
    $this->checkPermission('manage_roles');
    
    $roleId = (int)$id;
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Start transaction
    $this->db->begin_transaction();
    
    try {
        // Delete existing permissions for this role
        $this->db->query("DELETE FROM role_permissions WHERE role_id = $roleId");
        
        // Insert new permissions
        foreach($permissions as $perm) {
            $perm = $this->db->real_escape_string($perm);
            $this->db->query("INSERT INTO role_permissions (role_id, permission_slug) VALUES ($roleId, '$perm')");
        }
        
        // Update role's permissions JSON (for backward compatibility)
        $permissionsJson = json_encode($permissions);
        $this->db->query("UPDATE roles SET permissions = '$permissionsJson' WHERE id = $roleId");
        
        $this->db->commit();
        
        // If AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Permissions updated successfully for Role: ' . $this->getRoleName($roleId)
            ]);
            exit;
        }
        
        $_SESSION['success'] = "Permissions updated successfully for Role: " . $this->getRoleName($roleId);
        $this->redirect('/admin/users/roles');
        
    } catch (Exception $e) {
        $this->db->rollback();
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update permissions: ' . $e->getMessage()
            ]);
            exit;
        }
        
        $_SESSION['error'] = "Failed to update permissions: " . $e->getMessage();
        $this->redirect('/admin/users/roles');
    }
}
    
    // Helper method to get role name
    private function getRoleName($roleId) {
        $result = $this->db->query("SELECT name FROM roles WHERE id = $roleId");
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['name'];
        }
        return "ID: $roleId";
    }

    // ==================== ATTENDANCE MANAGEMENT ====================
    public function attendance() {
        $this->checkAuth();
        
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
        $filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'daily';
        $employeeId = isset($_GET['employee_id']) ? (int)$_GET['employee_id'] : 0;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Build the base query for counting total
        $countQuery = "SELECT COUNT(DISTINCT u.id) as total 
                       FROM users u
                       WHERE u.status = 'active'";
        
        if ($filterType == 'employee' && $employeeId > 0) {
            $countQuery .= " AND u.id = $employeeId";
        }
        
        $countResult = $this->db->query($countQuery);
        $totalRecords = $countResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($totalRecords / $limit);
        
        // Build the main query based on filter type
        if ($filterType == 'monthly') {
            // For monthly view, get all attendance for the month
            $query = "SELECT u.id, u.first_name, u.last_name, u.employee_id, r.name as role_name,
                      sa.id as attendance_id, sa.check_in, sa.check_out, sa.status as att_status,
                      sa.working_hours, sa.overtime_hours, sa.attendance_date
                      FROM users u
                      LEFT JOIN roles r ON u.role_id = r.id
                      LEFT JOIN staff_attendance sa ON u.id = sa.user_id 
                          AND sa.attendance_date BETWEEN '$month-01' AND '$month-31'
                      WHERE u.status = 'active'";
            
            if ($filterType == 'employee' && $employeeId > 0) {
                $query .= " AND u.id = $employeeId";
            }
            
            $query .= " ORDER BY u.first_name ASC, sa.attendance_date ASC";
        } elseif ($filterType == 'employee' && $employeeId > 0) {
            // For employee view
            $query = "SELECT u.id, u.first_name, u.last_name, u.employee_id, r.name as role_name,
                      sa.id as attendance_id, sa.check_in, sa.check_out, sa.status as att_status,
                      sa.working_hours, sa.overtime_hours, sa.attendance_date
                      FROM users u
                      LEFT JOIN roles r ON u.role_id = r.id
                      LEFT JOIN staff_attendance sa ON u.id = sa.user_id 
                          AND sa.attendance_date BETWEEN '$month-01' AND '$month-31'
                      WHERE u.status = 'active' AND u.id = $employeeId
                      ORDER BY sa.attendance_date ASC";
        } else {
            // Daily view - default with pagination
            $query = "SELECT u.id, u.first_name, u.last_name, u.employee_id, r.name as role_name,
                      sa.id as attendance_id, sa.check_in, sa.check_out, sa.status as att_status,
                      sa.working_hours, sa.overtime_hours
                      FROM users u
                      LEFT JOIN roles r ON u.role_id = r.id
                      LEFT JOIN staff_attendance sa ON u.id = sa.user_id AND sa.attendance_date = '$date'
                      WHERE u.status = 'active'
                      ORDER BY u.first_name ASC
                      LIMIT $limit OFFSET $offset";
        }
        
        $result = $this->db->query($query);
        $attendanceList = [];
        while($row = $result->fetch_assoc()) { 
            $attendanceList[] = $row; 
        }
        
        // Get employee list for dropdown
        $employeeList = [];
        $empResult = $this->db->query("SELECT id, first_name, last_name, employee_id FROM users WHERE status = 'active' ORDER BY first_name ASC");
        while($row = $empResult->fetch_assoc()) {
            $employeeList[] = $row;
        }
        
        // Pass data to the view
        $data = [
            'attendances' => $attendanceList,
            'date' => $date,
            'month' => $month,
            'filterType' => $filterType,
            'employeeId' => $employeeId,
            'employeeList' => $employeeList,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages
        ];
        
        $this->view('users/attendance', $data, 'Staff Attendance');
    }
    
    // ==================== ATTENDANCE API METHODS ====================
    
    public function apiSaveAttendance() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $userId = (int)$_POST['user_id'];
        $date = $this->db->real_escape_string($_POST['date']);
        $checkIn = $this->db->real_escape_string($_POST['check_in'] ?? '');
        $checkOut = $this->db->real_escape_string($_POST['check_out'] ?? '');
        $status = $this->db->real_escape_string($_POST['status']);
        
        $check = $this->db->query("SELECT id FROM staff_attendance WHERE user_id = $userId AND attendance_date = '$date'");
        
        if($check && $check->num_rows > 0) {
            $query = "UPDATE staff_attendance SET check_in=" . ($checkIn ? "'$checkIn'" : "NULL") . ", 
                     check_out=" . ($checkOut ? "'$checkOut'" : "NULL") . ", status='$status' 
                     WHERE user_id=$userId AND attendance_date='$date'";
        } else {
            $query = "INSERT INTO staff_attendance (user_id, attendance_date, check_in, check_out, status) 
                     VALUES ($userId, '$date', " . ($checkIn ? "'$checkIn'" : "NULL") . ", " . ($checkOut ? "'$checkOut'" : "NULL") . ", '$status')";
        }
        
        if($this->db->query($query)) {
            if($checkIn && $checkOut) {
                $this->calculateWorkingHours($userId, $date);
            }
            echo json_encode(['success' => true, 'message' => 'Attendance saved']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }
    
    public function apiSaveAllAttendance() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $date = $this->db->real_escape_string($_POST['date']);
        $users = json_decode($_POST['users'], true);
        
        $count = 0;
        foreach($users as $user) {
            $userId = (int)$user['user_id'];
            $checkIn = $this->db->real_escape_string($user['check_in'] ?? '');
            $checkOut = $this->db->real_escape_string($user['check_out'] ?? '');
            $status = $this->db->real_escape_string($user['status']);
            
            $check = $this->db->query("SELECT id FROM staff_attendance WHERE user_id = $userId AND attendance_date = '$date'");
            
            if($check && $check->num_rows > 0) {
                $query = "UPDATE staff_attendance SET check_in=" . ($checkIn ? "'$checkIn'" : "NULL") . ", 
                         check_out=" . ($checkOut ? "'$checkOut'" : "NULL") . ", status='$status' 
                         WHERE user_id=$userId AND attendance_date='$date'";
            } else {
                $query = "INSERT INTO staff_attendance (user_id, attendance_date, check_in, check_out, status) 
                         VALUES ($userId, '$date', " . ($checkIn ? "'$checkIn'" : "NULL") . ", " . ($checkOut ? "'$checkOut'" : "NULL") . ", '$status')";
            }
            
            if($this->db->query($query)) {
                if($checkIn && $checkOut) {
                    $this->calculateWorkingHours($userId, $date);
                }
                $count++;
            }
        }
        
        echo json_encode(['success' => true, 'message' => "$count attendance records saved"]);
        exit;
    }
    
    public function apiExportMonthlyAttendance() {
        $this->checkAuth();
        
        $month = $_GET['month'] ?? date('Y-m');
        $monthStart = date('Y-m-01', strtotime($month));
        $monthEnd = date('Y-m-t', strtotime($month));
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_' . $month . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        $header = ['Employee ID', 'Employee Name', 'Role'];
        $daysInMonth = date('t', strtotime($month));
        for($i = 1; $i <= $daysInMonth; $i++) {
            $header[] = date('d M', strtotime("$month-$i"));
        }
        $header[] = 'Present';
        $header[] = 'Absent';
        $header[] = 'Leave';
        $header[] = 'Total Hours';
        fputcsv($output, $header);
        
        $employees = $this->db->query("SELECT u.id, u.first_name, u.last_name, u.employee_id, r.name as role_name 
                                      FROM users u 
                                      LEFT JOIN roles r ON u.role_id = r.id 
                                      WHERE u.status = 'active' 
                                      ORDER BY u.first_name ASC");
        
        while($emp = $employees->fetch_assoc()) {
            $row = [
                $emp['employee_id'],
                $emp['first_name'] . ' ' . $emp['last_name'],
                $emp['role_name']
            ];
            
            $attResult = $this->db->query("SELECT attendance_date, status, working_hours 
                                          FROM staff_attendance 
                                          WHERE user_id = {$emp['id']} 
                                          AND attendance_date BETWEEN '$monthStart' AND '$monthEnd'");
            $attData = [];
            while($att = $attResult->fetch_assoc()) {
                $attData[$att['attendance_date']] = $att;
            }
            
            $present = 0;
            $absent = 0;
            $leave = 0;
            $totalHours = 0;
            
            for($i = 1; $i <= $daysInMonth; $i++) {
                $dateStr = date('Y-m-d', strtotime("$month-$i"));
                $att = $attData[$dateStr] ?? null;
                if($att) {
                    $row[] = $att['status'];
                    if($att['status'] == 'present' || $att['status'] == 'late' || $att['status'] == 'half_day') {
                        $present++;
                    } elseif($att['status'] == 'absent') {
                        $absent++;
                    } elseif($att['status'] == 'leave') {
                        $leave++;
                    }
                    $totalHours += floatval($att['working_hours'] ?? 0);
                } else {
                    $row[] = '-';
                }
            }
            
            $row[] = $present;
            $row[] = $absent;
            $row[] = $leave;
            $row[] = number_format($totalHours, 2);
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Manual entry for attendance
     */
    public function apiManualEntry() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $userId = (int)$_POST['user_id'];
        $date = $this->db->real_escape_string($_POST['date']);
        $checkIn = $this->db->real_escape_string($_POST['check_in'] ?? '');
        $checkOut = $this->db->real_escape_string($_POST['check_out'] ?? '');
        $status = $this->db->real_escape_string($_POST['status']);
        
        if ($userId == 0 || empty($date)) {
            echo json_encode(['success' => false, 'message' => 'User and date are required']);
            exit;
        }
        
        // Check if record exists
        $check = $this->db->query("SELECT id FROM staff_attendance WHERE user_id = $userId AND attendance_date = '$date'");
        
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            $query = "UPDATE staff_attendance SET 
                      check_in = " . ($checkIn ? "'$checkIn'" : "NULL") . ",
                      check_out = " . ($checkOut ? "'$checkOut'" : "NULL") . ",
                      status = '$status'
                      WHERE id = {$row['id']}";
        } else {
            $query = "INSERT INTO staff_attendance (user_id, attendance_date, check_in, check_out, status) 
                      VALUES ($userId, '$date', " . ($checkIn ? "'$checkIn'" : "NULL") . ", " . ($checkOut ? "'$checkOut'" : "NULL") . ", '$status')";
        }
        
        if ($this->db->query($query)) {
            // Calculate working hours if both times exist
            if ($checkIn && $checkOut) {
                $this->calculateWorkingHours($userId, $date);
            }
            echo json_encode(['success' => true, 'message' => 'Attendance entry saved']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }
    
    /**
     * Get attendance history for a user
     */
    public function apiHistory() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;
        
        if ($userId == 0) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit;
        }
        
        $query = "SELECT attendance_date, check_in, check_out, status, working_hours 
                  FROM staff_attendance 
                  WHERE user_id = $userId 
                  ORDER BY attendance_date DESC 
                  LIMIT $limit";
        
        $result = $this->db->query($query);
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }
    
    /**
     * Export attendance data
     */
    public function apiExport() {
        $this->checkAuth();
        
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
        $type = isset($_GET['type']) ? $_GET['type'] : 'daily';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="attendance_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Employee', 'Employee ID', 'Role', 'Date', 'Check In', 'Check Out', 'Status', 'Working Hours']);
        
        // Build query based on type
        if ($type == 'monthly') {
            $query = "SELECT CONCAT(u.first_name, ' ', u.last_name) as employee, u.employee_id, r.name as role,
                      sa.attendance_date, sa.check_in, sa.check_out, sa.status, sa.working_hours
                      FROM users u
                      LEFT JOIN roles r ON u.role_id = r.id
                      LEFT JOIN staff_attendance sa ON u.id = sa.user_id 
                          AND sa.attendance_date BETWEEN '$month-01' AND '$month-31'
                      WHERE u.status = 'active'
                      ORDER BY u.first_name ASC, sa.attendance_date ASC";
        } elseif ($type == 'employee' && isset($_GET['employee_id']) && $_GET['employee_id'] > 0) {
            $empId = (int)$_GET['employee_id'];
            $query = "SELECT CONCAT(u.first_name, ' ', u.last_name) as employee, u.employee_id, r.name as role,
                      sa.attendance_date, sa.check_in, sa.check_out, sa.status, sa.working_hours
                      FROM users u
                      LEFT JOIN roles r ON u.role_id = r.id
                      LEFT JOIN staff_attendance sa ON u.id = sa.user_id 
                          AND sa.attendance_date BETWEEN '$month-01' AND '$month-31'
                      WHERE u.status = 'active' AND u.id = $empId
                      ORDER BY sa.attendance_date ASC";
        } else {
            // Daily
            $query = "SELECT CONCAT(u.first_name, ' ', u.last_name) as employee, u.employee_id, r.name as role,
                      '$date' as attendance_date, sa.check_in, sa.check_out, sa.status, sa.working_hours
                      FROM users u
                      LEFT JOIN roles r ON u.role_id = r.id
                      LEFT JOIN staff_attendance sa ON u.id = sa.user_id AND sa.attendance_date = '$date'
                      WHERE u.status = 'active'
                      ORDER BY u.first_name ASC";
        }
        
        $result = $this->db->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['employee'],
                $row['employee_id'],
                $row['role'] ?? 'N/A',
                $row['attendance_date'] ?? $date,
                $row['check_in'] ?? '-',
                $row['check_out'] ?? '-',
                $row['status'] ?? 'Not marked',
                $row['working_hours'] ?? '0'
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    // ==================== PAYROLL MANAGEMENT ====================
    public function payroll() {
        $this->checkAuth();
        
        $month = $_GET['month'] ?? date('Y-m');
        $year = substr($month, 0, 4);
        $monthNum = substr($month, 5, 2);
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Get total payroll count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM payroll p WHERE p.payroll_month = '$month'";
        $countResult = $this->db->query($countQuery);
        $totalRecords = $countResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($totalRecords / $limit);
        
        // Get payroll with pagination
        $payrolls = $this->db->query("SELECT p.*, u.first_name, u.last_name, u.employee_id, r.name as role_name
                                     FROM payroll p
                                     JOIN users u ON p.user_id = u.id
                                     LEFT JOIN roles r ON u.role_id = r.id
                                     WHERE p.payroll_month = '$month'
                                     ORDER BY u.first_name ASC
                                     LIMIT $limit OFFSET $offset");
        $payrollList = [];
        while($row = $payrolls->fetch_assoc()) { 
            // Ensure all fields exist with defaults
            if(!isset($row['hra'])) $row['hra'] = 0;
            if(!isset($row['medical_allowance'])) $row['medical_allowance'] = 0;
            if(!isset($row['conveyance'])) $row['conveyance'] = 0;
            if(!isset($row['other_allowances'])) $row['other_allowances'] = 0;
            if(!isset($row['provident_fund'])) $row['provident_fund'] = 0;
            if(!isset($row['professional_tax'])) $row['professional_tax'] = 0;
            if(!isset($row['income_tax'])) $row['income_tax'] = 0;
            if(!isset($row['other_deductions'])) $row['other_deductions'] = 0;
            if(!isset($row['allowances'])) $row['allowances'] = 0;
            if(!isset($row['deductions'])) $row['deductions'] = 0;
            if(!isset($row['commission_earned'])) $row['commission_earned'] = 0;
            $payrollList[] = $row; 
        }
        
        $totalBasic = 0;
        $totalAllowances = 0;
        $totalDeductions = 0;
        $totalNet = 0;
        foreach($payrollList as $p) {
            $totalBasic += $p['basic_salary'];
            $totalAllowances += $p['allowances'] ?? 0;
            $totalDeductions += $p['deductions'] ?? 0;
            $totalNet += $p['net_salary'];
        }
        
        $this->view('users/payroll', [
            'payrolls' => $payrollList,
            'month' => $month,
            'year' => $year,
            'monthNum' => $monthNum,
            'totalBasic' => $totalBasic,
            'totalAllowances' => $totalAllowances,
            'totalDeductions' => $totalDeductions,
            'totalNet' => $totalNet,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages
        ], 'Payroll Management');
    }
    
    // ==================== PAYROLL HELPER METHODS ====================
    
    /**
     * Save payroll data for a new user
     */
    private function savePayrollData($userId, $roleId) {
        // Get payroll fields from POST
        $basicSalary = isset($_POST['basic_salary']) && $_POST['basic_salary'] !== '' ? (float)$_POST['basic_salary'] : $this->calculateBasicSalaryByRole($roleId);
        $hra = (float)($_POST['hra'] ?? 0);
        $medicalAllowance = (float)($_POST['medical_allowance'] ?? 0);
        $conveyance = (float)($_POST['conveyance'] ?? 0);
        $otherAllowances = (float)($_POST['other_allowances'] ?? 0);
        $providentFund = (float)($_POST['provident_fund'] ?? 0);
        $professionalTax = (float)($_POST['professional_tax'] ?? 0);
        $incomeTax = (float)($_POST['income_tax'] ?? 0);
        $otherDeductions = (float)($_POST['other_deductions'] ?? 0);
        
        // Calculate totals
        $allowances = $hra + $medicalAllowance + $conveyance + $otherAllowances;
        $deductions = $providentFund + $professionalTax + $incomeTax + $otherDeductions;
        $netSalary = $basicSalary + $allowances - $deductions;
        
        $currentMonth = date('Y-m');
        
        $this->db->query("INSERT INTO payroll (
            user_id, payroll_month, basic_salary, hra, medical_allowance, conveyance,
            other_allowances, allowances, provident_fund, professional_tax, income_tax,
            other_deductions, deductions, net_salary, status
        ) VALUES (
            $userId, '$currentMonth', $basicSalary, $hra, $medicalAllowance, $conveyance,
            $otherAllowances, $allowances, $providentFund, $professionalTax, $incomeTax,
            $otherDeductions, $deductions, $netSalary, 'pending'
        )");
    }
    
    /**
     * Update payroll data for an existing user
     */
    private function updatePayrollData($userId, $roleId) {
        // Check if any payroll field is filled
        $basicSalary = isset($_POST['basic_salary']) && $_POST['basic_salary'] !== '' ? (float)$_POST['basic_salary'] : null;
        $hra = (float)($_POST['hra'] ?? 0);
        $medicalAllowance = (float)($_POST['medical_allowance'] ?? 0);
        $conveyance = (float)($_POST['conveyance'] ?? 0);
        $otherAllowances = (float)($_POST['other_allowances'] ?? 0);
        $providentFund = (float)($_POST['provident_fund'] ?? 0);
        $professionalTax = (float)($_POST['professional_tax'] ?? 0);
        $incomeTax = (float)($_POST['income_tax'] ?? 0);
        $otherDeductions = (float)($_POST['other_deductions'] ?? 0);
        
        $hasPayrollData = $basicSalary !== null || $hra > 0 || $medicalAllowance > 0 || $conveyance > 0 || 
                           $otherAllowances > 0 || $providentFund > 0 || $professionalTax > 0 || 
                           $incomeTax > 0 || $otherDeductions > 0;
        
        if ($hasPayrollData) {
            // If basic salary is null, try to get existing or calculate default
            if ($basicSalary === null) {
                $existingPayroll = $this->db->query("SELECT basic_salary FROM payroll WHERE user_id = $userId ORDER BY payroll_month DESC LIMIT 1");
                if ($existingPayroll && $existingPayroll->num_rows > 0) {
                    $row = $existingPayroll->fetch_assoc();
                    $basicSalary = $row['basic_salary'];
                } else {
                    $basicSalary = $this->calculateBasicSalaryByRole($roleId);
                }
            }
            
            $allowances = $hra + $medicalAllowance + $conveyance + $otherAllowances;
            $deductions = $providentFund + $professionalTax + $incomeTax + $otherDeductions;
            $netSalary = $basicSalary + $allowances - $deductions;
            
            $currentMonth = date('Y-m');
            
            // Check if payroll record exists for this month
            $checkPayroll = $this->db->query("SELECT id FROM payroll WHERE user_id = $userId AND payroll_month = '$currentMonth'");
            
            if($checkPayroll && $checkPayroll->num_rows > 0) {
                $this->db->query("UPDATE payroll SET 
                                 basic_salary = $basicSalary,
                                 hra = $hra,
                                 medical_allowance = $medicalAllowance,
                                 conveyance = $conveyance,
                                 other_allowances = $otherAllowances,
                                 allowances = $allowances,
                                 provident_fund = $providentFund,
                                 professional_tax = $professionalTax,
                                 income_tax = $incomeTax,
                                 other_deductions = $otherDeductions,
                                 deductions = $deductions,
                                 net_salary = $netSalary
                                 WHERE user_id = $userId AND payroll_month = '$currentMonth'");
            } else {
                $this->db->query("INSERT INTO payroll (
                                 user_id, payroll_month, basic_salary, hra, medical_allowance, conveyance,
                                 other_allowances, allowances, provident_fund, professional_tax, income_tax,
                                 other_deductions, deductions, net_salary, status
                                 ) VALUES (
                                 $userId, '$currentMonth', $basicSalary, $hra, $medicalAllowance, $conveyance,
                                 $otherAllowances, $allowances, $providentFund, $professionalTax, $incomeTax,
                                 $otherDeductions, $deductions, $netSalary, 'pending'
                                 )");
            }
        }
    }
    
    /**
     * Calculate default basic salary based on role
     */
    private function calculateBasicSalaryByRole($roleId) {
        $salaries = [
            1 => 80000, // Super Admin
            2 => 60000, // Admin
            3 => 50000, // Doctor
            4 => 25000, // Receptionist
            5 => 30000, // Pharmacist
            6 => 28000, // Lab Technician
            7 => 35000, // Accountant
            8 => 22000  // Nurse
        ];
        return $salaries[$roleId] ?? 30000;
    }
    
    /**
     * Calculate working hours for a user on a specific date
     */
    private function calculateWorkingHours($userId, $date) {
        $query = $this->db->query("SELECT check_in, check_out FROM staff_attendance WHERE user_id = $userId AND attendance_date = '$date'");
        $row = $query->fetch_assoc();
        
        if ($row && $row['check_in'] && $row['check_out']) {
            $checkIn = strtotime($row['check_in']);
            $checkOut = strtotime($row['check_out']);
            $diffSeconds = $checkOut - $checkIn;
            $workingHours = round($diffSeconds / 3600, 2);
            $overtime = max(0, $workingHours - 8);
            
            $this->db->query("UPDATE staff_attendance SET working_hours = $workingHours, overtime_hours = $overtime 
                             WHERE user_id = $userId AND attendance_date = '$date'");
        }
    }

    // ==================== PAYROLL API METHODS ====================

    /**
     * Generate payroll for all active employees
     */
    public function apiGenerateAllPayroll() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $month = isset($_POST['month']) ? $this->db->real_escape_string($_POST['month']) : date('Y-m');
        
        // Get all active users
        $users = $this->db->query("SELECT u.id, u.first_name, u.last_name, u.employee_id, r.name as role_name, u.role_id
                                   FROM users u 
                                   LEFT JOIN roles r ON u.role_id = r.id 
                                   WHERE u.status = 'active'");
        
        if (!$users || $users->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'No active employees found']);
            exit;
        }
        
        $generated = 0;
        $updated = 0;
        
        while ($employee = $users->fetch_assoc()) {
            // Check if payroll already exists for this month
            $check = $this->db->query("SELECT id FROM payroll WHERE user_id = {$employee['id']} AND payroll_month = '$month'");
            
            if ($check && $check->num_rows > 0) {
                $updated++;
                continue;
            }
            
            // Calculate basic salary based on role
            $basicSalary = $this->calculateBasicSalaryByRole($employee['role_id']);
            
            // Calculate allowances (40% of basic)
            $hra = $basicSalary * 0.20;
            $medicalAllowance = $basicSalary * 0.10;
            $conveyance = $basicSalary * 0.10;
            $otherAllowances = 0;
            $allowances = $hra + $medicalAllowance + $conveyance + $otherAllowances;
            
            // Calculate deductions (12% of basic)
            $providentFund = $basicSalary * 0.12;
            $professionalTax = 0;
            $incomeTax = 0;
            $otherDeductions = 0;
            $deductions = $providentFund + $professionalTax + $incomeTax + $otherDeductions;
            
            // Get commission for doctors
            $commission = 0;
            if ($employee['role_id'] == 3) { // Doctor role
                $doctorId = $this->db->query("SELECT id FROM doctors WHERE user_id = {$employee['id']}")->fetch_assoc();
                if ($doctorId) {
                    $commissionResult = $this->db->query("SELECT SUM(commission_amount) as total FROM doctor_commissions 
                                                         WHERE doctor_id = {$doctorId['id']} 
                                                         AND DATE_FORMAT(created_at, '%Y-%m') = '$month'");
                    $commission = $commissionResult->fetch_assoc()['total'] ?? 0;
                }
            }
            
            $netSalary = $basicSalary + $allowances + $commission - $deductions;
            
            // Insert new payroll record
            $query = "INSERT INTO payroll (
                user_id, payroll_month, basic_salary, hra, medical_allowance, conveyance,
                other_allowances, allowances, provident_fund, professional_tax, income_tax,
                other_deductions, deductions, commission_earned, net_salary, status
            ) VALUES (
                {$employee['id']}, '$month', $basicSalary, $hra, $medicalAllowance, $conveyance,
                $otherAllowances, $allowances, $providentFund, $professionalTax, $incomeTax,
                $otherDeductions, $deductions, $commission, $netSalary, 'pending'
            )";
            $this->db->query($query);
            $generated++;
        }
        
        echo json_encode([
            'success' => true, 
            'message' => "Generated $generated new records, $updated existing records skipped for $month"
        ]);
        exit;
    }

    /**
     * Get payroll details for editing
     */
    public function apiGetPayrollDetails() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $month = isset($_GET['month']) ? $this->db->real_escape_string($_GET['month']) : date('Y-m');
        
        if ($userId == 0) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit;
        }
        
        // Get payroll record
        $query = "SELECT * FROM payroll WHERE user_id = $userId AND payroll_month = '$month'";
        $result = $this->db->query($query);
        
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            // Return default values
            $employee = $this->db->query("SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = $userId")->fetch_assoc();
            $roleId = $employee['role_id'] ?? 0;
            $basicSalary = $this->calculateBasicSalaryByRole($roleId);
            
            echo json_encode([
                'success' => true, 
                'data' => [
                    'basic_salary' => $basicSalary,
                    'hra' => $basicSalary * 0.20,
                    'medical_allowance' => $basicSalary * 0.10,
                    'conveyance' => $basicSalary * 0.10,
                    'other_allowances' => 0,
                    'provident_fund' => $basicSalary * 0.12,
                    'professional_tax' => 0,
                    'income_tax' => 0,
                    'other_deductions' => 0,
                    'allowances' => $basicSalary * 0.40,
                    'deductions' => $basicSalary * 0.12,
                    'commission_earned' => 0,
                    'net_salary' => $basicSalary + ($basicSalary * 0.40) - ($basicSalary * 0.12)
                ]
            ]);
        }
        exit;
    }

    /**
     * Update salary components
     */
    public function apiUpdateSalary() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $userId = (int)$_POST['user_id'];
        $month = $this->db->real_escape_string($_POST['month']);
        $basicSalary = (float)$_POST['basic_salary'];
        $hra = (float)$_POST['hra'];
        $medical = (float)$_POST['medical_allowance'];
        $conveyance = (float)$_POST['conveyance'];
        $otherAllow = (float)$_POST['other_allowances'];
        $pf = (float)$_POST['provident_fund'];
        $pt = (float)$_POST['professional_tax'];
        $tax = (float)$_POST['income_tax'];
        $otherDed = (float)$_POST['other_deductions'];
        
        $allowances = $hra + $medical + $conveyance + $otherAllow;
        $deductions = $pf + $pt + $tax + $otherDed;
        $netSalary = $basicSalary + $allowances - $deductions;
        
        // Check if record exists
        $check = $this->db->query("SELECT id FROM payroll WHERE user_id = $userId AND payroll_month = '$month'");
        
        if ($check && $check->num_rows > 0) {
            $query = "UPDATE payroll SET 
                      basic_salary = $basicSalary, 
                      hra = $hra, 
                      medical_allowance = $medical, 
                      conveyance = $conveyance, 
                      other_allowances = $otherAllow,
                      allowances = $allowances,
                      provident_fund = $pf,
                      professional_tax = $pt,
                      income_tax = $tax,
                      other_deductions = $otherDed,
                      deductions = $deductions,
                      net_salary = $netSalary
                      WHERE user_id = $userId AND payroll_month = '$month'";
        } else {
            $query = "INSERT INTO payroll (user_id, payroll_month, basic_salary, hra, medical_allowance, conveyance, other_allowances, allowances, provident_fund, professional_tax, income_tax, other_deductions, deductions, net_salary, status) 
                      VALUES ($userId, '$month', $basicSalary, $hra, $medical, $conveyance, $otherAllow, $allowances, $pf, $pt, $tax, $otherDed, $deductions, $netSalary, 'pending')";
        }
        
        if ($this->db->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Salary updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }

    /**
     * Mark payroll as paid
     */
    public function apiMarkPaid() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $userId = (int)$_POST['user_id'];
        $month = $this->db->real_escape_string($_POST['month']);
        $paymentMethod = $this->db->real_escape_string($_POST['payment_method']);
        $transactionId = $this->db->real_escape_string($_POST['transaction_id'] ?? '');
        $notes = $this->db->real_escape_string($_POST['notes'] ?? '');
        
        $query = "UPDATE payroll SET 
                  status = 'paid', 
                  payment_method = '$paymentMethod', 
                  transaction_id = '$transactionId', 
                  payment_date = CURDATE(),
                  notes = '$notes'
                  WHERE user_id = $userId AND payroll_month = '$month'";
        
        if ($this->db->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }

    /**
     * Print payslip
     */
    public function apiPrintPayslip() {
        $this->checkAuth();
        
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $month = isset($_GET['month']) ? $this->db->real_escape_string($_GET['month']) : date('Y-m');
        
        if ($userId == 0) {
            die('User ID required');
        }
        
        $query = "SELECT p.*, u.first_name, u.last_name, u.employee_id, r.name as role_name 
                  FROM payroll p
                  JOIN users u ON p.user_id = u.id
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE p.user_id = $userId AND p.payroll_month = '$month'";
        
        $result = $this->db->query($query);
        $payroll = $result->fetch_assoc();
        
        if (!$payroll) {
            die('Payroll record not found');
        }
        
        // Simple payslip print
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Payslip - <?php echo $payroll['employee_id']; ?></title>
            <style>
                body { font-family: 'Cambria', Georgia, serif; padding: 40px; }
                .payslip { max-width: 800px; margin: 0 auto; border: 2px solid #333; padding: 30px; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
                .header h2 { margin: 0; color: #1abc9c; }
                .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
                .info-item { padding: 5px 0; }
                .info-item label { font-weight: bold; display: inline-block; width: 120px; }
                .table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .table th { background: #2c2c2c; color: white; padding: 10px; text-align: left; }
                .table td { padding: 8px 10px; border-bottom: 1px solid #ddd; }
                .table .text-end { text-align: right; }
                .total-row { font-weight: bold; border-top: 2px solid #333; }
                .total-row td { padding-top: 10px; }
                .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
                .status-badge { display: inline-block; padding: 3px 12px; border-radius: 4px; color: white; }
                .status-paid { background: #28a745; }
                .status-pending { background: #ffc107; }
                .status-processed { background: #17a2b8; }
            </style>
        </head>
        <body>
            <div class="payslip">
                <div class="header">
                    <h2>UniDia HMS</h2>
                    <p>Payslip for <?php echo date('F Y', strtotime($month . '-01')); ?></p>
                </div>
                
                <div class="info-grid">
                    <div class="info-item"><label>Employee ID:</label> <?php echo $payroll['employee_id']; ?></div>
                    <div class="info-item"><label>Name:</label> <?php echo $payroll['first_name'] . ' ' . $payroll['last_name']; ?></div>
                    <div class="info-item"><label>Role:</label> <?php echo $payroll['role_name']; ?></div>
                    <div class="info-item"><label>Status:</label> <span class="status-badge status-<?php echo $payroll['status']; ?>"><?php echo ucfirst($payroll['status']); ?></span></div>
                </div>
                
                <table class="table">
                    <tr>
                        <th>Earnings</th>
                        <th class="text-end">Amount</th>
                        <th>Deductions</th>
                        <th class="text-end">Amount</th>
                    </tr>
                    <tr>
                        <td>Basic Salary</td>
                        <td class="text-end">৳<?php echo number_format($payroll['basic_salary'], 2); ?></td>
                        <td>Provident Fund</td>
                        <td class="text-end">৳<?php echo number_format($payroll['provident_fund'] ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td>HRA</td>
                        <td class="text-end">৳<?php echo number_format($payroll['hra'] ?? 0, 2); ?></td>
                        <td>Professional Tax</td>
                        <td class="text-end">৳<?php echo number_format($payroll['professional_tax'] ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Medical Allowance</td>
                        <td class="text-end">৳<?php echo number_format($payroll['medical_allowance'] ?? 0, 2); ?></td>
                        <td>Income Tax</td>
                        <td class="text-end">৳<?php echo number_format($payroll['income_tax'] ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Conveyance</td>
                        <td class="text-end">৳<?php echo number_format($payroll['conveyance'] ?? 0, 2); ?></td>
                        <td>Other Deductions</td>
                        <td class="text-end">৳<?php echo number_format($payroll['other_deductions'] ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td>Other Allowances</td>
                        <td class="text-end">৳<?php echo number_format($payroll['other_allowances'] ?? 0, 2); ?></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td><strong>Commission</strong></td>
                        <td class="text-end"><strong>৳<?php echo number_format($payroll['commission_earned'] ?? 0, 2); ?></strong></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr class="total-row">
                        <td><strong>Total Earnings</strong></td>
                        <td class="text-end"><strong>৳<?php echo number_format($payroll['basic_salary'] + ($payroll['allowances'] ?? 0) + ($payroll['commission_earned'] ?? 0), 2); ?></strong></td>
                        <td><strong>Total Deductions</strong></td>
                        <td class="text-end"><strong>৳<?php echo number_format($payroll['deductions'] ?? 0, 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td colspan="4" style="text-align: center; font-size: 20px; font-weight: bold; color: #1abc9c; padding: 15px 0;">
                            Net Salary: ৳<?php echo number_format($payroll['net_salary'], 2); ?>
                        </td>
                    </tr>
                </table>
                
                <div class="footer">
                    <p>This is a system-generated payslip. For any queries, please contact HR department.</p>
                    <p>Generated on: <?php echo date('d M Y h:i A'); ?></p>
                </div>
            </div>
            <script>
                window.print();
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Export payroll to CSV
     */
    public function apiExportPayroll() {
        $this->checkAuth();
        
        $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="payroll_' . $month . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Employee ID', 'Name', 'Role', 'Basic Salary', 'Allowances', 'Deductions', 'Commission', 'Net Salary', 'Status']);
        
        $query = "SELECT p.*, u.first_name, u.last_name, u.employee_id, r.name as role_name 
                  FROM payroll p
                  JOIN users u ON p.user_id = u.id
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE p.payroll_month = '$month'
                  ORDER BY u.first_name ASC";
        
        $result = $this->db->query($query);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['employee_id'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['role_name'],
                $row['basic_salary'],
                $row['allowances'] ?? 0,
                $row['deductions'] ?? 0,
                $row['commission_earned'] ?? 0,
                $row['net_salary'],
                $row['status']
            ]);
        }
        
        fclose($output);
        exit;
    }

    // ==================== PERMISSION HELPER METHODS ====================

/**
 * Get user permissions from database
 */
public function getUserPermissions($userId = null) {
    if ($userId === null) {
        $userId = $_SESSION['user_id'] ?? 0;
    }
    
    if ($userId == 0) {
        return [];
    }
    
    // Check if user is Super Admin
    $user = $this->db->query("SELECT role_id FROM users WHERE id = $userId")->fetch_assoc();
    if (!$user) {
        return [];
    }
    
    $roleId = $user['role_id'];
    $role = $this->db->query("SELECT slug FROM roles WHERE id = $roleId")->fetch_assoc();
    if ($role && $role['slug'] == 'super_admin') {
        // Super Admin has all permissions
        $allPerms = $this->db->query("SELECT slug FROM permissions");
        $permissions = [];
        while ($row = $allPerms->fetch_assoc()) {
            $permissions[] = $row['slug'];
        }
        return $permissions;
    }
    
    // Get role permissions
    $result = $this->db->query("SELECT permission_slug FROM role_permissions WHERE role_id = $roleId");
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_slug'];
    }
    return $permissions;
}

/**
 * Check if user has a specific permission
 */
public function hasPermission($permission, $userId = null) {
    $userPermissions = $this->getUserPermissions($userId);
    return in_array($permission, $userPermissions);
}

/**
 * Get menu items with permission check
 */
public function getMenuWithPermissions() {
    $userPermissions = $this->getUserPermissions();
    $roleSlug = $_SESSION['role_slug'] ?? '';
    
    $menus = [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'url' => '/admin/dashboard',
            'permission' => 'view_dashboard'
        ],
        // ... all menu definitions (same as in sidebar.php)
    ];
    
    $filteredMenus = [];
    foreach ($menus as $menu) {
        if ($roleSlug == 'super_admin' || in_array($menu['permission'], $userPermissions)) {
            if (!empty($menu['submenus'])) {
                $filteredSubmenus = [];
                foreach ($menu['submenus'] as $submenu) {
                    if ($roleSlug == 'super_admin' || in_array($submenu['permission'], $userPermissions)) {
                        $filteredSubmenus[] = $submenu;
                    }
                }
                if (!empty($filteredSubmenus)) {
                    $menu['submenus'] = $filteredSubmenus;
                    $filteredMenus[] = $menu;
                }
            } else {
                $filteredMenus[] = $menu;
            }
        }
    }
    return $filteredMenus;
}

/**
 * API: Get current user permissions
 */
public function apiGetUserPermissions() {
    header('Content-Type: application/json');
    $this->checkAuth();
    
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
    $permissions = $this->getUserPermissions($userId);
    
    echo json_encode([
        'success' => true,
        'permissions' => $permissions
    ]);
    exit;
}

/**
 * API: Check if user has a specific permission
 */
public function apiCheckPermission() {
    header('Content-Type: application/json');
    $this->checkAuth();
    
    $permission = isset($_GET['permission']) ? $_GET['permission'] : '';
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];
    
    $hasPermission = $this->hasPermission($permission, $userId);
    
    echo json_encode([
        'success' => true,
        'has_permission' => $hasPermission,
        'permission' => $permission
    ]);
    exit;
}

// ==================== API: ADD NEW DEPARTMENT (For User/Doctor Creation) ====================
public function apiAddDepartment() {
    header('Content-Type: application/json');
    $this->checkAuth();
    
    try {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        
        // Validate
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Department name is required']);
            exit;
        }
        
        $db = $this->db;
        $name = $db->real_escape_string($name);
        $code = $db->real_escape_string($code);
        $description = $db->real_escape_string($description);
        
        // Check if department already exists
        $check = $db->query("SELECT id FROM departments WHERE name = '$name'");
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            echo json_encode([
                'success' => true, 
                'department_id' => $row['id'],
                'department_name' => $name,
                'message' => 'Department already exists'
            ]);
            exit;
        }
        
        // Check if code is unique
        if (!empty($code)) {
            $checkCode = $db->query("SELECT id FROM departments WHERE code = '$code'");
            if ($checkCode && $checkCode->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Department code already exists']);
                exit;
            }
        }
        
        // Insert new department
        $codeValue = !empty($code) ? "'$code'" : "NULL";
        $descValue = !empty($description) ? "'$description'" : "NULL";
        
        $query = "INSERT INTO departments (name, code, description, status, created_at) 
                  VALUES ('$name', $codeValue, $descValue, 'active', NOW())";
        
        if ($db->query($query)) {
            $departmentId = $db->insert_id;
            echo json_encode([
                'success' => true,
                'department_id' => $departmentId,
                'department_name' => $name,
                'message' => 'Department added successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add department: ' . $db->error]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

}
?>