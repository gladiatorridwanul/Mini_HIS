<?php
require_once __DIR__ . '/Controller.php';

class AdminController extends Controller {
    
    private $db;
    
    public function __construct() {
        $this->checkAuth();
        $this->checkRole(['super_admin', 'admin']);
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function auditLogs() {
        $page = $_GET['page'] ?? 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT al.*, u.first_name, u.last_name, u.email, r.name as role_name
                  FROM audit_logs al
                  LEFT JOIN users u ON al.user_id = u.id
                  LEFT JOIN roles r ON u.role_id = r.id
                  ORDER BY al.created_at DESC
                  LIMIT $offset, $limit";
        
        $result = $this->db->query($query);
        $logs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total FROM audit_logs";
        $countResult = $this->db->query($countQuery);
        $totalLogs = $countResult->fetch_assoc()['total'] ?? 0;
        
        // Get filter options
        $modulesQuery = "SELECT DISTINCT module FROM audit_logs ORDER BY module";
        $modulesResult = $this->db->query($modulesQuery);
        $modules = [];
        if ($modulesResult && $modulesResult->num_rows > 0) {
            while ($row = $modulesResult->fetch_assoc()) {
                $modules[] = $row['module'];
            }
        }
        
        $actionsQuery = "SELECT DISTINCT action FROM audit_logs ORDER BY action";
        $actionsResult = $this->db->query($actionsQuery);
        $actions = [];
        if ($actionsResult && $actionsResult->num_rows > 0) {
            while ($row = $actionsResult->fetch_assoc()) {
                $actions[] = $row['action'];
            }
        }
        
        $this->view('admin/audit-logs', [
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'currentPage' => $page,
            'totalPages' => ceil($totalLogs / $limit),
            'modules' => $modules,
            'actions' => $actions
        ]);
    }
    
    public function systemInfo() {
        $data = [
            'php_version' => phpversion(),
            'mysql_version' => $this->db->server_info,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'server_protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit')
        ];
        
        $this->json($data);
    }
    
    public function clearCache() {
        $cacheDir = BASE_PATH . '/app/cache';
        if (is_dir($cacheDir)) {
            $files = glob($cacheDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $_SESSION['success'] = "Cache cleared successfully";
        } else {
            $_SESSION['info'] = "Cache directory not found";
        }
        
        $this->redirect('/unidia/public/admin/settings');
    }
    
    public function backupDatabase() {
        $this->checkRole(['super_admin']);
        
        $tables = [];
        $result = $this->db->query("SHOW TABLES");
        
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        $backup = "-- UniDia Database Backup\n";
        $backup .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $backup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            $result = $this->db->query("SELECT * FROM $table");
            $numFields = $result->field_count;
            
            $backup .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $createResult = $this->db->query("SHOW CREATE TABLE $table");
            $createRow = $createResult->fetch_row();
            $backup .= $createRow[1] . ";\n\n";
            
            if ($result->num_rows > 0) {
                $backup .= "INSERT INTO `$table` VALUES ";
                
                $rowCount = 0;
                while ($row = $result->fetch_row()) {
                    $rowCount++;
                    $backup .= "(";
                    for ($i = 0; $i < $numFields; $i++) {
                        $value = $row[$i];
                        $value = addslashes($value);
                        $value = str_replace("\n", "\\n", $value);
                        
                        if ($value == '') {
                            $backup .= "NULL";
                        } else {
                            $backup .= "'" . $value . "'";
                        }
                        
                        if ($i < $numFields - 1) {
                            $backup .= ",";
                        }
                    }
                    $backup .= ")";
                    
                    if ($rowCount < $result->num_rows) {
                        $backup .= ",";
                    }
                }
                $backup .= ";\n\n";
            }
        }
        
        $backup .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        $filename = 'unidia_backup_' . date('Y-m-d_H-i-s') . '.sql';
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $backup;
        exit;
    }
    
    public function getDashboardStats() {
        $today = date('Y-m-d');
        
        $stats = [];
        
        // Total patients
        $result = $this->db->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active'");
        $stats['total_patients'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Total doctors
        $result = $this->db->query("SELECT COUNT(*) as total FROM doctors WHERE status = 'active'");
        $stats['total_doctors'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Today's appointments
        $result = $this->db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today'");
        $stats['today_appointments'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Today's revenue
        $result = $this->db->query("SELECT SUM(amount) as total FROM payments WHERE payment_date = '$today'");
        $stats['today_revenue'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Pending bills
        $result = $this->db->query("SELECT COUNT(*) as total FROM bills WHERE payment_status = 'pending'");
        $stats['pending_bills'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Low stock items
        $result = $this->db->query("SELECT COUNT(*) as total FROM inventory_items WHERE current_stock <= reorder_level AND is_active = 1");
        $stats['low_stock_items'] = $result->fetch_assoc()['total'] ?? 0;
        
        $this->json($stats);
    }
    
    public function activityLog() {
        $action = $_GET['action'] ?? '';
        $module = $_GET['module'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';
        
        $query = "SELECT al.*, u.first_name, u.last_name, u.email, r.name as role_name
                  FROM audit_logs al
                  LEFT JOIN users u ON al.user_id = u.id
                  LEFT JOIN roles r ON u.role_id = r.id
                  WHERE 1=1";
        
        if (!empty($action)) {
            $action = $this->db->real_escape_string($action);
            $query .= " AND al.action = '$action'";
        }
        
        if (!empty($module)) {
            $module = $this->db->real_escape_string($module);
            $query .= " AND al.module = '$module'";
        }
        
        if (!empty($dateFrom)) {
            $query .= " AND DATE(al.created_at) >= '$dateFrom'";
        }
        
        if (!empty($dateTo)) {
            $query .= " AND DATE(al.created_at) <= '$dateTo'";
        }
        
        $query .= " ORDER BY al.created_at DESC LIMIT 200";
        
        $result = $this->db->query($query);
        $logs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        
        $this->json($logs);
    }
    
    public function logActivity($userId, $action, $module, $recordId = null, $oldValue = null, $newValue = null) {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $oldValueJson = $oldValue ? json_encode($oldValue) : 'NULL';
        $newValueJson = $newValue ? json_encode($newValue) : 'NULL';
        
        $query = "INSERT INTO audit_logs (user_id, action, module, record_id, old_value, new_value, ip_address, user_agent) 
                  VALUES ($userId, '$action', '$module', " . ($recordId ?: 'NULL') . ", 
                  " . ($oldValueJson ? "'" . $this->db->real_escape_string($oldValueJson) . "'" : "NULL") . ",
                  " . ($newValueJson ? "'" . $this->db->real_escape_string($newValueJson) . "'" : "NULL") . ",
                  '$ipAddress', '" . $this->db->real_escape_string($userAgent) . "')";
        
        return $this->db->query($query);
    }
    
    private function checkAuth() {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/unidia/public/login');
            exit;
        }
    }
    
    private function checkRole($allowedRoles) {
        $userRole = $_SESSION['role_slug'] ?? '';
        if (!in_array($userRole, $allowedRoles)) {
            $this->redirect('/unidia/public/403');
            exit;
        }
    }

    /**
     * Check if user has permission for a specific action
     */
    public function checkAdminPermission($permission) {
        $userPermissions = $this->getUserPermissions();
        $roleSlug = $_SESSION['role_slug'] ?? '';
        
        if ($roleSlug != 'super_admin' && !in_array($permission, $userPermissions)) {
            $_SESSION['error'] = "You don't have permission to access this page.";
            $this->redirect('/unidia/public/admin/dashboard');
            exit;
        }
        return true;
    }
    
    /**
     * Get user permissions
     */
    private function getUserPermissions() {
        $userId = $_SESSION['user_id'] ?? 0;
        if ($userId == 0) return [];
        
        $roleId = $_SESSION['role_id'] ?? 0;
        if ($roleId == 0) return [];
        
        // Check if Super Admin
        $role = $this->db->query("SELECT slug FROM roles WHERE id = $roleId")->fetch_assoc();
        if ($role && $role['slug'] == 'super_admin') {
            $allPerms = $this->db->query("SELECT slug FROM permissions");
            $permissions = [];
            while ($row = $allPerms->fetch_assoc()) {
                $permissions[] = $row['slug'];
            }
            return $permissions;
        }
        
        $result = $this->db->query("SELECT permission_slug FROM role_permissions WHERE role_id = $roleId");
        $permissions = [];
        while ($row = $result->fetch_assoc()) {
            $permissions[] = $row['permission_slug'];
        }
        return $permissions;
    }
}
?>