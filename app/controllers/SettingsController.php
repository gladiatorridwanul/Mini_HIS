<?php
// app/controllers/SettingsController.php
// COMPLETE FIXED VERSION - NO DUPLICATE METHODS

class SettingsController extends Controller {
    
    private $groupMap = [
        'general' => ['label' => 'General', 'icon' => 'globe'],
        'billing' => ['label' => 'Billing', 'icon' => 'file-invoice-dollar'],
        'appointment' => ['label' => 'Appointment', 'icon' => 'calendar-check'],
        'patient' => ['label' => 'Patient', 'icon' => 'user'],
        'doctor' => ['label' => 'Doctor', 'icon' => 'user-md'],
        'notification' => ['label' => 'Notification', 'icon' => 'bell'],
        'email' => ['label' => 'Email', 'icon' => 'envelope'],
        'security' => ['label' => 'Security', 'icon' => 'shield-alt'],
        'pharmacy' => ['label' => 'Pharmacy', 'icon' => 'prescription-bottle']
    ];
    
    private $settingGroups = [
        'site_name' => 'general',
        'site_email' => 'general',
        'site_phone' => 'general',
        'site_address' => 'general',
        'site_logo' => 'general',
        'currency' => 'billing',
        'tax_percentage' => 'billing',
        'appointment_interval' => 'appointment',
        'prescription_validity' => 'patient',
        'low_stock_threshold' => 'pharmacy',
        'expiry_alert_days' => 'pharmacy',
        'smtp_host' => 'email',
        'smtp_port' => 'email',
        'smtp_username' => 'email',
        'smtp_password' => 'email',
        'smtp_encryption' => 'email',
        'enable_registration' => 'patient',
        'enable_portal' => 'patient',
        'maintenance_mode' => 'security'
    ];
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->requireRole(['super_admin', 'admin']);
    }
    
    // ==================== INDEX - SETTINGS PAGE ====================
    public function index() {
        $group = isset($_GET['group']) ? $_GET['group'] : 'general';
        
        $allSettings = $this->getAllSettings();
        
        $groupedSettings = [];
        $allGroups = [];
        
        foreach($allSettings as $setting) {
            $settingKey = $setting['setting_key'];
            $settingGroup = isset($this->settingGroups[$settingKey]) ? $this->settingGroups[$settingKey] : 'general';
            
            if(!isset($groupedSettings[$settingGroup])) {
                $groupedSettings[$settingGroup] = [];
                if(!in_array($settingGroup, $allGroups)) {
                    $allGroups[] = $settingGroup;
                }
            }
            $groupedSettings[$settingGroup][] = $setting;
        }
        
        sort($allGroups);
        
        if(empty($allGroups)) {
            $allGroups = ['general'];
            $groupedSettings['general'] = [];
        }
        
        $currentGroupSettings = isset($groupedSettings[$group]) ? $groupedSettings[$group] : [];
        
        if(empty($currentGroupSettings) && !empty($allGroups)) {
            $this->redirect('/admin/settings?group=' . $allGroups[0]);
            return;
        }
        
        $data = [
            'settings' => $currentGroupSettings,
            'groups' => $allGroups,
            'currentGroup' => $group,
            'groupedSettings' => $groupedSettings,
            'groupMap' => $this->groupMap
        ];
        
        $this->view('settings/index', $data, 'System Settings');
    }
    
    // ==================== UPDATE SETTINGS ====================
    public function update() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
                
                foreach($settings as $key => $value) {
                    $this->saveSetting($key, $value);
                }
                
                if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                    $this->uploadLogo($_FILES['logo']);
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Settings updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid request method'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== BACKUP DATABASE ====================
    public function backup() {
        header('Content-Type: application/json');
        
        try {
            $backupDir = BASE_PATH . '/backups/';
            if(!is_dir($backupDir)) {
                if(!mkdir($backupDir, 0777, true)) {
                    throw new Exception('Cannot create backup directory.');
                }
            }
            
            if(!is_writable($backupDir)) {
                throw new Exception('Backup directory is not writable.');
            }
            
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . $filename;
            
            $dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
            $dbUser = defined('DB_USER') ? DB_USER : 'root';
            $dbPass = defined('DB_PASS') ? DB_PASS : '';
            $dbName = defined('DB_NAME') ? DB_NAME : 'unidia_db';
            
            $this->createPHPBackup($dbHost, $dbUser, $dbPass, $dbName, $filepath);
            
            if(file_exists($filepath) && filesize($filepath) > 0) {
                $backupName = 'Backup_' . date('Y-m-d H:i:s');
                $fileSize = $this->formatFileSize(filesize($filepath));
                
                $relativePath = 'backups/' . $filename;
                
                $this->db->query("INSERT INTO backup_history (backup_name, backup_type, file_size, file_path, created_by, created_at) 
                                 VALUES ('$backupName', 'database', '$fileSize', '$relativePath', {$_SESSION['user_id']}, NOW())");
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Database backup created successfully',
                    'filename' => $filename,
                    'filepath' => $relativePath,
                    'filesize' => $fileSize
                ]);
            } else {
                throw new Exception('Failed to create backup file.');
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // ==================== PHP BACKUP METHOD ====================
    private function createPHPBackup($host, $user, $pass, $dbname, $filepath) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $output = "-- UniDia Database Backup\n";
            $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Database: $dbname\n";
            $output .= "-- Host: $host\n\n";
            $output .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
            
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach($tables as $table) {
                $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $output .= "DROP TABLE IF EXISTS `$table`;\n";
                $output .= $row['Create Table'] . ";\n\n";
                
                $stmt = $pdo->query("SELECT * FROM `$table`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if(empty($rows)) continue;
                
                $output .= "INSERT INTO `$table` VALUES\n";
                $values = [];
                foreach($rows as $row) {
                    $escaped = array_map(function($val) use ($pdo) {
                        if($val === null) return 'NULL';
                        return $pdo->quote($val);
                    }, array_values($row));
                    $values[] = "(" . implode(", ", $escaped) . ")";
                }
                $output .= implode(",\n", $values) . ";\n\n";
            }
            
            $output .= "SET FOREIGN_KEY_CHECKS = 1;\n";
            
            if(file_put_contents($filepath, $output) === false) {
                throw new Exception('Failed to write backup file');
            }
        } catch (Exception $e) {
            throw new Exception('Backup failed: ' . $e->getMessage());
        }
    }
    
    // ==================== BACKUP HISTORY ====================
    public function backupHistory() {
        $this->checkAuth();
        $this->checkPermission('edit_settings');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $where = "WHERE 1=1";
        $search = isset($_GET['search']) ? $this->db->real_escape_string($_GET['search']) : '';
        if(!empty($search)) {
            $where .= " AND (backup_name LIKE '%$search%' OR backup_type LIKE '%$search%' OR file_size LIKE '%$search%')";
        }
        
        $countQuery = "SELECT COUNT(*) as total FROM backup_history $where";
        $countResult = $this->db->query($countQuery);
        $totalRecords = $countResult ? $countResult->fetch_assoc()['total'] : 0;
        $totalPages = ceil($totalRecords / $limit);
        
        $query = "SELECT bh.*, 
                         CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                  FROM backup_history bh
                  LEFT JOIN users u ON bh.created_by = u.id
                  $where
                  ORDER BY bh.created_at DESC
                  LIMIT $offset, $limit";
        
        $result = $this->db->query($query);
        $backups = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $backups[] = $row;
            }
        }
        
        $this->view('settings/backup-history', [
            'backups' => $backups,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'limit' => $limit,
            'offset' => $offset
        ], 'Backup History');
    }
    
    // ==================== DOWNLOAD BACKUP - SINGLE METHOD ====================
    public function downloadBackup($id) {
        $this->checkAuth();
        $this->checkPermission('edit_settings');
        
        $id = (int)$id;
        
        // Get backup record
        $query = "SELECT * FROM backup_history WHERE id = $id";
        $result = $this->db->query($query);
        
        if(!$result || $result->num_rows == 0) {
            $_SESSION['error'] = "Backup not found";
            $this->redirect('/admin/settings/backup-history');
            return;
        }
        
        $backup = $result->fetch_assoc();
        $basePath = BASE_PATH;
        
        // Try multiple possible paths
        $possiblePaths = [
            $basePath . '/' . $backup['file_path'],
            $basePath . '/backups/' . basename($backup['file_path']),
            $basePath . '/backups/' . $backup['backup_name'] . '.sql',
            $basePath . '/' . $backup['backup_name'] . '.sql'
        ];
        
        $filepath = null;
        foreach($possiblePaths as $path) {
            if(file_exists($path)) {
                $filepath = $path;
                break;
            }
        }
        
        // If still not found, scan backups directory
        if(!$filepath) {
            $backupDir = $basePath . '/backups/';
            if(is_dir($backupDir)) {
                $files = scandir($backupDir);
                foreach($files as $file) {
                    if($file != '.' && $file != '..' && strpos($file, '.sql') !== false) {
                        $fullPath = $backupDir . $file;
                        if(strpos($file, date('Y-m-d', strtotime($backup['created_at']))) !== false) {
                            $filepath = $fullPath;
                            break;
                        }
                    }
                }
            }
        }
        
        if(!$filepath || !file_exists($filepath)) {
            $_SESSION['error'] = "Backup file not found. Please create a new backup.";
            $this->redirect('/admin/settings/backup-history');
            return;
        }
        
        // Force download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Clear output buffer
        if(ob_get_level()) {
            ob_end_clean();
        }
        
        readfile($filepath);
        exit;
    }
    
    // ==================== DELETE BACKUP ====================
    public function deleteBackup() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $id = (int)$_POST['id'];
        
        $query = "SELECT * FROM backup_history WHERE id = $id";
        $result = $this->db->query($query);
        
        if(!$result || $result->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Backup not found']);
            exit;
        }
        
        $backup = $result->fetch_assoc();
        
        // Try to delete the file
        $possiblePaths = [
            BASE_PATH . '/' . $backup['file_path'],
            BASE_PATH . '/backups/' . basename($backup['file_path'])
        ];
        
        foreach($possiblePaths as $path) {
            if(file_exists($path)) {
                unlink($path);
                break;
            }
        }
        
        $this->db->query("DELETE FROM backup_history WHERE id = $id");
        
        echo json_encode(['success' => true, 'message' => 'Backup deleted successfully']);
        exit;
    }
    
    // ==================== GET ALL SETTINGS ====================
    private function getAllSettings() {
        $settings = [];
        
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'system_settings'");
        if(!$tableCheck || $tableCheck->num_rows == 0) {
            $this->createSettingsTable();
            return $settings;
        }
        
        $sql = "SELECT * FROM system_settings ORDER BY setting_key ASC";
        $result = $this->db->query($sql);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $row['display_name'] = $this->getDisplayName($row['setting_key']);
                $settings[] = $row;
            }
        }
        return $settings;
    }
    
    // ==================== GET SINGLE SETTING ====================
    private function getSetting($key) {
        $key = $this->db->real_escape_string($key);
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = '$key'";
        $result = $this->db->query($sql);
        if($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['setting_value'];
        }
        return null;
    }
    
    // ==================== SAVE SETTING ====================
    private function saveSetting($key, $value) {
        $key = $this->db->real_escape_string($key);
        $value = $this->db->real_escape_string($value);
        
        $check = $this->db->query("SELECT id FROM system_settings WHERE setting_key = '$key'");
        if($check && $check->num_rows > 0) {
            $sql = "UPDATE system_settings SET setting_value = '$value', updated_at = NOW() WHERE setting_key = '$key'";
        } else {
            $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type, created_at) VALUES ('$key', '$value', 'text', NOW())";
        }
        
        return $this->db->query($sql);
    }
    
    // ==================== GET DISPLAY NAME ====================
    private function getDisplayName($key) {
        $names = [
            'site_name' => 'Site Name',
            'site_email' => 'Site Email',
            'site_phone' => 'Site Phone',
            'site_address' => 'Site Address',
            'site_logo' => 'Site Logo',
            'currency' => 'Currency',
            'tax_percentage' => 'Tax Percentage',
            'appointment_interval' => 'Appointment Interval (minutes)',
            'prescription_validity' => 'Prescription Validity (days)',
            'low_stock_threshold' => 'Low Stock Threshold',
            'expiry_alert_days' => 'Expiry Alert Days',
            'smtp_host' => 'SMTP Host',
            'smtp_port' => 'SMTP Port',
            'smtp_username' => 'SMTP Username',
            'smtp_password' => 'SMTP Password',
            'smtp_encryption' => 'SMTP Encryption',
            'enable_registration' => 'Enable Patient Registration',
            'enable_portal' => 'Enable Patient Portal',
            'maintenance_mode' => 'Maintenance Mode'
        ];
        return isset($names[$key]) ? $names[$key] : ucfirst(str_replace('_', ' ', $key));
    }
    
    // ==================== CREATE SETTINGS TABLE ====================
    private function createSettingsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `setting_key` varchar(100) NOT NULL,
            `setting_value` text DEFAULT NULL,
            `setting_type` enum('text','number','boolean','json','file','color') DEFAULT 'text',
            `description` text DEFAULT NULL,
            `display_name` varchar(100) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $this->db->query($sql);
        
        $defaults = [
            ['site_name', 'UniDia Healthcare', 'text', 'Hospital/Clinic Name', 'Site Name'],
            ['site_email', 'info@unidia.com', 'text', 'Primary Email Address', 'Site Email'],
            ['site_phone', '+1 234 567 8900', 'text', 'Contact Phone Number', 'Site Phone'],
            ['site_address', '123 Healthcare Street, Medical District', 'text', 'Physical Address', 'Site Address'],
            ['currency', 'USD', 'text', 'Currency Code', 'Currency'],
            ['tax_percentage', '5', 'number', 'Default Tax Percentage', 'Tax Percentage'],
            ['appointment_interval', '15', 'number', 'Appointment Slot Duration (minutes)', 'Appointment Interval'],
            ['prescription_validity', '90', 'number', 'Prescription Validity (days)', 'Prescription Validity'],
            ['low_stock_threshold', '10', 'number', 'Low Stock Alert Threshold', 'Low Stock Threshold'],
            ['expiry_alert_days', '30', 'number', 'Days Before Expiry Alert', 'Expiry Alert Days'],
            ['smtp_host', 'smtp.gmail.com', 'text', 'SMTP Host', 'SMTP Host'],
            ['smtp_port', '587', 'number', 'SMTP Port', 'SMTP Port'],
            ['smtp_username', '', 'text', 'SMTP Username', 'SMTP Username'],
            ['smtp_password', '', 'text', 'SMTP Password', 'SMTP Password'],
            ['smtp_encryption', 'tls', 'text', 'SMTP Encryption', 'SMTP Encryption'],
            ['enable_registration', '1', 'boolean', 'Enable Patient Registration', 'Enable Registration'],
            ['enable_portal', '1', 'boolean', 'Enable Patient Portal', 'Enable Portal'],
            ['maintenance_mode', '0', 'boolean', 'Maintenance Mode', 'Maintenance Mode']
        ];
        
        foreach($defaults as $default) {
            $this->db->query("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, display_name) 
                            VALUES ('{$default[0]}', '{$default[1]}', '{$default[2]}', '{$default[3]}', '{$default[4]}')");
        }
    }
    
    // ==================== UPLOAD LOGO ====================
    private function uploadLogo($file) {
        $uploadPath = BASE_PATH . '/public/uploads/logo/';
        if(!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'logo.' . $extension;
        $targetPath = $uploadPath . $fileName;
        
        if(file_exists($targetPath)) {
            unlink($targetPath);
        }
        
        if(move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->saveSetting('site_logo', 'uploads/logo/' . $fileName);
        }
    }
    
    // ==================== FORMAT FILE SIZE ====================
    private function formatFileSize($bytes) {
        if($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
    
    // ==================== AUTHENTICATION HELPERS ====================
    private function requireAuth() {
        if(!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            exit;
        }
    }
    
    private function requireRole($roles) {
        if(!isset($_SESSION['role_slug'])) {
            $this->redirect('/login');
            exit;
        }
        if(!in_array($_SESSION['role_slug'], $roles)) {
            $this->redirect('/unauthorized');
            exit;
        }
    }
}
?>