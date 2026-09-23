<?php
// app/helpers/AuditHelper.php
// COMPLETE AUDIT HELPER

class AuditHelper {
    
    private $db;
    
    public function __construct() {
        global $conn;
        $this->db = $conn;
        
        // Ensure audit_logs table exists
        $this->ensureTableExists();
    }
    
    /**
     * Ensure audit_logs table exists
     */
    private function ensureTableExists() {
        $sql = "CREATE TABLE IF NOT EXISTS `audit_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `action` varchar(100) NOT NULL,
            `module` varchar(50) NOT NULL,
            `record_id` int(11) DEFAULT NULL,
            `old_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
            `new_value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `description` text DEFAULT NULL,
            `user_role` varchar(50) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `user_id` (`user_id`),
            KEY `action` (`action`),
            KEY `module` (`module`),
            KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->query($sql);
    }
    
    /**
     * Log an action
     */
    public function log($action, $module, $recordId = null, $oldValue = null, $newValue = null, $description = null) {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        $userRole = isset($_SESSION['role_slug']) ? $_SESSION['role_slug'] : 'system';
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        
        $oldValueJson = $oldValue ? json_encode($oldValue) : null;
        $newValueJson = $newValue ? json_encode($newValue) : null;
        
        $sql = "INSERT INTO audit_logs (user_id, user_role, action, module, record_id, old_value, new_value, ip_address, user_agent, description, created_at) 
                VALUES ($userId, '$userRole', '$action', '$module', " . ($recordId ? $recordId : "NULL") . ", 
                " . ($oldValueJson ? "'" . $this->db->real_escape_string($oldValueJson) . "'" : "NULL") . ", 
                " . ($newValueJson ? "'" . $this->db->real_escape_string($newValueJson) . "'" : "NULL") . ", 
                " . ($ipAddress ? "'" . $this->db->real_escape_string($ipAddress) . "'" : "NULL") . ", 
                " . ($userAgent ? "'" . $this->db->real_escape_string($userAgent) . "'" : "NULL") . ", 
                " . ($description ? "'" . $this->db->real_escape_string($description) . "'" : "NULL") . ", 
                NOW())";
        
        return $this->db->query($sql);
    }
    
    /**
     * Get audit logs with filters (paginated)
     */
    public function getAuditLogsPaginated($filters = [], $limit = 20, $offset = 0) {
        $where = "WHERE 1=1";
        
        if(isset($filters['user_id']) && $filters['user_id'] > 0) {
            $where .= " AND a.user_id = " . (int)$filters['user_id'];
        }
        if(isset($filters['module']) && !empty($filters['module'])) {
            $where .= " AND a.module = '" . $this->db->real_escape_string($filters['module']) . "'";
        }
        if(isset($filters['action']) && !empty($filters['action'])) {
            $where .= " AND a.action = '" . $this->db->real_escape_string($filters['action']) . "'";
        }
        if(isset($filters['date_from']) && !empty($filters['date_from'])) {
            $where .= " AND DATE(a.created_at) >= '" . $this->db->real_escape_string($filters['date_from']) . "'";
        }
        if(isset($filters['date_to']) && !empty($filters['date_to'])) {
            $where .= " AND DATE(a.created_at) <= '" . $this->db->real_escape_string($filters['date_to']) . "'";
        }
        if(isset($filters['search']) && !empty($filters['search'])) {
            $search = $this->db->real_escape_string($filters['search']);
            $where .= " AND (a.action LIKE '%$search%' OR a.module LIKE '%$search%' OR a.description LIKE '%$search%' 
                        OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%')";
        }
        
        $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as user_name 
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.id
                $where
                ORDER BY a.created_at DESC
                LIMIT $offset, $limit";
        
        $result = $this->db->query($sql);
        $logs = [];
        while($row = $result->fetch_assoc()) {
            // Decode JSON values if needed
            if($row['old_value']) {
                $row['old_value_decoded'] = json_decode($row['old_value'], true);
            }
            if($row['new_value']) {
                $row['new_value_decoded'] = json_decode($row['new_value'], true);
            }
            $logs[] = $row;
        }
        return $logs;
    }
    
    /**
     * Get audit logs count (for pagination)
     */
    public function getAuditLogsCount($filters = []) {
        $where = "WHERE 1=1";
        
        if(isset($filters['user_id']) && $filters['user_id'] > 0) {
            $where .= " AND a.user_id = " . (int)$filters['user_id'];
        }
        if(isset($filters['module']) && !empty($filters['module'])) {
            $where .= " AND a.module = '" . $this->db->real_escape_string($filters['module']) . "'";
        }
        if(isset($filters['action']) && !empty($filters['action'])) {
            $where .= " AND a.action = '" . $this->db->real_escape_string($filters['action']) . "'";
        }
        if(isset($filters['date_from']) && !empty($filters['date_from'])) {
            $where .= " AND DATE(a.created_at) >= '" . $this->db->real_escape_string($filters['date_from']) . "'";
        }
        if(isset($filters['date_to']) && !empty($filters['date_to'])) {
            $where .= " AND DATE(a.created_at) <= '" . $this->db->real_escape_string($filters['date_to']) . "'";
        }
        if(isset($filters['search']) && !empty($filters['search'])) {
            $search = $this->db->real_escape_string($filters['search']);
            $where .= " AND (a.action LIKE '%$search%' OR a.module LIKE '%$search%' OR a.description LIKE '%$search%' 
                        OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%')";
        }
        
        $sql = "SELECT COUNT(*) as total FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id $where";
        $result = $this->db->query($sql);
        return $result ? (int)$result->fetch_assoc()['total'] : 0;
    }
    
    /**
     * Get available modules
     */
    public function getModules() {
        $sql = "SELECT DISTINCT module FROM audit_logs ORDER BY module";
        $result = $this->db->query($sql);
        $modules = [];
        while($row = $result->fetch_assoc()) {
            $modules[] = $row;
        }
        return $modules;
    }
    
    /**
     * Get available actions
     */
    public function getActions() {
        $sql = "SELECT DISTINCT action FROM audit_logs ORDER BY action";
        $result = $this->db->query($sql);
        $actions = [];
        while($row = $result->fetch_assoc()) {
            $actions[] = $row;
        }
        return $actions;
    }
    
    /**
     * Get user activity summary
     */
    public function getUserActivitySummary($userId) {
        $sql = "SELECT action, COUNT(*) as count, MAX(created_at) as last_activity 
                FROM audit_logs 
                WHERE user_id = $userId 
                GROUP BY action 
                ORDER BY count DESC";
        $result = $this->db->query($sql);
        $summary = [];
        while($row = $result->fetch_assoc()) {
            $summary[] = $row;
        }
        return $summary;
    }
    
    /**
     * Log user login
     */
    public function logLogin($userId, $status = 'success') {
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        
        // Get user name
        $sql = "SELECT CONCAT(first_name, ' ', last_name) as name FROM users WHERE id = $userId";
        $result = $this->db->query($sql);
        $userName = $result ? $result->fetch_assoc()['name'] : 'Unknown';
        
        // Log in login_history table
        $sql = "INSERT INTO login_history (user_id, user_name, ip_address, user_agent, login_time, status) 
                VALUES ($userId, '$userName', " . ($ipAddress ? "'" . $this->db->real_escape_string($ipAddress) . "'" : "NULL") . ", 
                " . ($userAgent ? "'" . $this->db->real_escape_string($userAgent) . "'" : "NULL") . ", NOW(), '$status')";
        
        return $this->db->query($sql);
    }
    
    /**
     * Log user logout
     */
    public function logLogout($userId) {
        $sql = "UPDATE login_history SET logout_time = NOW() 
                WHERE user_id = $userId AND logout_time IS NULL 
                ORDER BY login_time DESC LIMIT 1";
        return $this->db->query($sql);
    }
}
?>