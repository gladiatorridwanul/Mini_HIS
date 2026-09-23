<?php
class User extends Model {
    protected $table = 'users';

    public function authenticate($email, $password) {
        $sql = "SELECT u.*, r.slug as role_slug, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.email = :email AND u.status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    public function logLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $userId]);
    }

    public function getAllUsers() {
        $sql = "SELECT u.*, r.name as role_name, d.name as department_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id 
                LEFT JOIN user_departments ud ON u.id = ud.user_id 
                LEFT JOIN departments d ON ud.department_id = d.id 
                ORDER BY u.created_at DESC";
        return $this->query($sql);
    }

    public function getRoles() {
        $sql = "SELECT * FROM roles ORDER BY name";
        return $this->query($sql);
    }

    public function getAllRoles() {
        return $this->getRoles();
    }

    public function getDepartments() {
        $sql = "SELECT * FROM departments WHERE status = 'active' ORDER BY name";
        return $this->query($sql);
    }

    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->create($data);
    }

    public function assignDepartment($userId, $departmentId) {
        $sql = "INSERT INTO user_departments (user_id, department_id) VALUES (:user_id, :department_id)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['user_id' => $userId, 'department_id' => $departmentId]);
    }

    public function createRole($data) {
        $sql = "INSERT INTO roles (name, slug, description, permissions) VALUES (:name, :slug, :description, :permissions)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function updateRolePermissions($roleId, $permissions) {
        return $this->update($roleId, ['permissions' => $permissions], 'roles');
    }

    public function getPermissions($userId) {
        $sql = "SELECT r.permissions FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = :id";
        $result = $this->query($sql, ['id' => $userId]);
        return $result[0]['permissions'] ?? '{}';
    }

    public function getActiveStaff() {
        $sql = "SELECT u.*, r.name as role_name 
                FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.status = 'active' 
                ORDER BY u.first_name";
        return $this->query($sql);
    }

    public function getAttendanceByDate($date) {
        $sql = "SELECT * FROM staff_attendance WHERE attendance_date = :date";
        $result = $this->query($sql, ['date' => $date]);
        $attendance = [];
        foreach ($result as $row) {
            $attendance[$row['user_id']] = $row;
        }
        return $attendance;
    }

    public function markAttendance($data) {
        $sql = "INSERT INTO staff_attendance (user_id, attendance_date, status, check_in) 
                VALUES (:user_id, :attendance_date, :status, :check_in) 
                ON DUPLICATE KEY UPDATE status = :status2, check_in = :check_in2";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'user_id' => $data['user_id'],
            'attendance_date' => $data['attendance_date'],
            'status' => $data['status'],
            'check_in' => $data['check_in'] ?? null,
            'status2' => $data['status'],
            'check_in2' => $data['check_in'] ?? null
        ]);
    }

    public function getMonthlyAttendance($userId, $month) {
        $sql = "SELECT * FROM staff_attendance 
                WHERE user_id = :user_id 
                AND DATE_FORMAT(attendance_date, '%Y-%m') = :month";
        return $this->query($sql, ['user_id' => $userId, 'month' => $month]);
    }

    public function getMonthlyLeaves($userId, $month) {
        $sql = "SELECT * FROM leaves 
                WHERE user_id = :user_id 
                AND DATE_FORMAT(start_date, '%Y-%m') = :month 
                AND status = 'approved'";
        return $this->query($sql, ['user_id' => $userId, 'month' => $month]);
    }

    public function getPayrollByMonth($month) {
        $sql = "SELECT p.*, u.first_name, u.last_name, u.employee_id 
                FROM payroll p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.payroll_month = :month 
                ORDER BY u.first_name";
        return $this->query($sql, ['month' => $month]);
    }

    public function createPayroll($data) {
        $sql = "INSERT INTO payroll (user_id, payroll_month, basic_salary, allowances, deductions, 
                overtime_pay, commission_earned, net_salary, status) 
                VALUES (:user_id, :payroll_month, :basic_salary, :allowances, :deductions, 
                :overtime_pay, :commission_earned, :net_salary, :status)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function getAuditLogs($filters = [], $page = 1) {
        $sql = "SELECT al.*, CONCAT(u.first_name, ' ', u.last_name) as user_name 
                FROM audit_logs al 
                JOIN users u ON al.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['action'])) {
            $sql .= " AND al.action LIKE :action";
            $params['action'] = '%' . $filters['action'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(al.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(al.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $sql .= " ORDER BY al.created_at DESC LIMIT 50";
        return $this->query($sql, $params);
    }

    public function setResetToken($email, $token) {
        $sql = "UPDATE users SET remember_token = :token WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['token' => $token, 'email' => $email]);
    }
}