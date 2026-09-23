<?php
// app/controllers/AuditController.php
// COMPLETE FIXED VERSION - SHOWS ALL AUDITS AND ACTIVITIES

require_once BASE_PATH . '/core/Controller.php';
require_once BASE_PATH . '/app/helpers/AuditHelper.php';

class AuditController extends Controller {
    
    // ==================== AUDIT LOGS ====================
    public function index() {
        $this->checkAuth();
        $this->checkPermission('view_audit');
        
        $auditHelper = new AuditHelper();
        
        $filters = [];
        if(isset($_GET['user_id']) && $_GET['user_id']) $filters['user_id'] = (int)$_GET['user_id'];
        if(isset($_GET['module']) && $_GET['module']) $filters['module'] = $_GET['module'];
        if(isset($_GET['action']) && $_GET['action']) $filters['action'] = $_GET['action'];
        if(isset($_GET['date_from']) && $_GET['date_from']) $filters['date_from'] = $_GET['date_from'];
        if(isset($_GET['date_to']) && $_GET['date_to']) $filters['date_to'] = $_GET['date_to'];
        if(isset($_GET['search']) && $_GET['search']) $filters['search'] = $_GET['search'];
        
        // Pagination
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $auditLogs = $auditHelper->getAuditLogsPaginated($filters, $limit, $offset);
        $totalRecords = $auditHelper->getAuditLogsCount($filters);
        $totalPages = ceil($totalRecords / $limit);
        
        $modules = $auditHelper->getModules();
        $actions = $auditHelper->getActions();
        
        // Get users for filter
        $users = [];
        $usersResult = $this->db->query("SELECT id, first_name, last_name FROM users WHERE status = 'active' ORDER BY first_name ASC");
        while($row = $usersResult->fetch_assoc()) {
            $users[] = $row;
        }
        
        // Get statistics
        $stats = $this->getAuditStatistics();
        
        $content = $this->renderView('audit/index', [
            'auditLogs' => $auditLogs,
            'modules' => $modules,
            'actions' => $actions,
            'users' => $users,
            'filters' => $filters,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'limit' => $limit,
            'offset' => $offset
        ]);
        $this->renderLayout('Audit Logs', $content);
    }
    
    // ==================== GET AUDIT STATISTICS ====================
    private function getAuditStatistics() {
        $stats = [
            'total' => 0,
            'today' => 0,
            'this_week' => 0,
            'this_month' => 0,
            'by_action' => [],
            'by_module' => []
        ];
        
        // Total
        $result = $this->db->query("SELECT COUNT(*) as count FROM audit_logs");
        if($result) $stats['total'] = (int)$result->fetch_assoc()['count'];
        
        // Today
        $result = $this->db->query("SELECT COUNT(*) as count FROM audit_logs WHERE DATE(created_at) = CURDATE()");
        if($result) $stats['today'] = (int)$result->fetch_assoc()['count'];
        
        // This week
        $result = $this->db->query("SELECT COUNT(*) as count FROM audit_logs WHERE YEARWEEK(created_at) = YEARWEEK(CURDATE())");
        if($result) $stats['this_week'] = (int)$result->fetch_assoc()['count'];
        
        // This month
        $result = $this->db->query("SELECT COUNT(*) as count FROM audit_logs WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
        if($result) $stats['this_month'] = (int)$result->fetch_assoc()['count'];
        
        // By action
        $result = $this->db->query("SELECT action, COUNT(*) as count FROM audit_logs GROUP BY action ORDER BY count DESC LIMIT 10");
        if($result) {
            while($row = $result->fetch_assoc()) {
                $stats['by_action'][] = $row;
            }
        }
        
        // By module
        $result = $this->db->query("SELECT module, COUNT(*) as count FROM audit_logs GROUP BY module ORDER BY count DESC LIMIT 10");
        if($result) {
            while($row = $result->fetch_assoc()) {
                $stats['by_module'][] = $row;
            }
        }
        
        return $stats;
    }
    
    // ==================== LOGIN HISTORY ====================
    public function loginHistory() {
        $this->checkAuth();
        $this->checkPermission('view_audit');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;
        
        $search = isset($_GET['search']) ? $this->db->real_escape_string($_GET['search']) : '';
        $status = isset($_GET['status']) ? $this->db->real_escape_string($_GET['status']) : '';
        $dateFrom = isset($_GET['date_from']) ? $this->db->real_escape_string($_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? $this->db->real_escape_string($_GET['date_to']) : '';
        
        $where = "WHERE 1=1";
        if(!empty($search)) {
            $where .= " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR l.ip_address LIKE '%$search%')";
        }
        if(!empty($status)) {
            $where .= " AND l.status = '$status'";
        }
        if(!empty($dateFrom)) {
            $where .= " AND DATE(l.login_time) >= '$dateFrom'";
        }
        if(!empty($dateTo)) {
            $where .= " AND DATE(l.login_time) <= '$dateTo'";
        }
        
        $countQuery = "SELECT COUNT(*) as total FROM login_history l JOIN users u ON l.user_id = u.id $where";
        $countResult = $this->db->query($countQuery);
        $totalRecords = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
        $totalPages = ceil($totalRecords / $limit);
        
        $query = "SELECT l.*, u.first_name, u.last_name, u.email 
                  FROM login_history l
                  JOIN users u ON l.user_id = u.id
                  $where
                  ORDER BY l.login_time DESC
                  LIMIT $offset, $limit";
        
        $result = $this->db->query($query);
        $loginHistory = [];
        while($row = $result->fetch_assoc()) {
            $loginHistory[] = $row;
        }
        
        $content = $this->renderView('audit/login-history', [
            'loginHistory' => $loginHistory,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'search' => $search,
            'status' => $status,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'limit' => $limit,
            'offset' => $offset
        ]);
        $this->renderLayout('Login History', $content);
    }
    
    // ==================== CLEAR AUDIT LOGS ====================
    public function clearLogs() {
        header('Content-Type: application/json');
        $this->checkAuth();
        $this->checkPermission('edit_settings');
        
        $days = (int)$_POST['days'];
        
        if($days > 0) {
            $deleted = $this->db->query("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
            if($deleted) {
                $auditHelper = new AuditHelper();
                $auditHelper->log('clear_logs', 'audit', null, null, null, "Cleared audit logs older than $days days");
                echo json_encode(['success' => true, 'message' => "Logs older than $days days cleared successfully"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to clear logs']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid days value']);
        }
        exit;
    }
    
    // ==================== EXPORT AUDIT LOGS ====================
    public function exportLogs() {
        $this->checkAuth();
        $this->checkPermission('view_audit');
        
        $format = isset($_GET['format']) ? $_GET['format'] : 'csv';
        
        $where = "WHERE 1=1";
        if(isset($_GET['date_from']) && $_GET['date_from']) {
            $where .= " AND DATE(a.created_at) >= '" . $this->db->real_escape_string($_GET['date_from']) . "'";
        }
        if(isset($_GET['date_to']) && $_GET['date_to']) {
            $where .= " AND DATE(a.created_at) <= '" . $this->db->real_escape_string($_GET['date_to']) . "'";
        }
        if(isset($_GET['module']) && $_GET['module']) {
            $where .= " AND a.module = '" . $this->db->real_escape_string($_GET['module']) . "'";
        }
        if(isset($_GET['action']) && $_GET['action']) {
            $where .= " AND a.action = '" . $this->db->real_escape_string($_GET['action']) . "'";
        }
        
        $query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.email 
                  FROM audit_logs a
                  LEFT JOIN users u ON a.user_id = u.id
                  $where
                  ORDER BY a.created_at DESC";
        
        $result = $this->db->query($query);
        
        $filename = "audit_logs_" . date('Y-m-d') . ".csv";
        $headers = ['ID', 'User', 'Email', 'Role', 'IP Address', 'Action', 'Module', 'Record ID', 'Description', 'Date'];
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        
        while($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['user_name'] ?? 'System',
                $row['email'] ?? '',
                $row['user_role'] ?? 'system',
                $row['ip_address'] ?? '',
                $row['action'],
                $row['module'],
                $row['record_id'] ?? '',
                $row['description'] ?? '',
                $row['created_at']
            ]);
        }
        fclose($output);
        exit;
    }
    
    // ==================== VIEW LOG DETAIL ====================
    public function viewLog($id) {
        $this->checkAuth();
        $this->checkPermission('view_audit');
        
        $id = (int)$id;
        
        $query = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as user_name, u.email 
                  FROM audit_logs a
                  LEFT JOIN users u ON a.user_id = u.id
                  WHERE a.id = $id";
        
        $result = $this->db->query($query);
        
        if(!$result || $result->num_rows == 0) {
            $_SESSION['error'] = "Log entry not found";
            $this->redirect('/admin/audit-logs');
            return;
        }
        
        $log = $result->fetch_assoc();
        
        // Parse old and new values if available
        if($log['old_value']) {
            $log['old_value_decoded'] = json_decode($log['old_value'], true);
        }
        if($log['new_value']) {
            $log['new_value_decoded'] = json_decode($log['new_value'], true);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'log' => $log]);
        exit;
    }
}
?>