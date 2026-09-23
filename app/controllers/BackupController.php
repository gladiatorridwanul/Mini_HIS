<?php
class BackupController extends Controller {
    public function __construct() {
        $this->requireAuth();
        $this->requireRole(['super_admin', 'admin']);
    }
    
    public function create() {
        $backupDir = ROOT_PATH . '/backups/';
        if(!is_dir($backupDir)) mkdir($backupDir, 0777, true);
        
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backupDir . $filename;
        
        // Get database credentials
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $db = DB_NAME;
        
        // Execute mysqldump
        $command = "mysqldump --host={$host} --user={$user} --password='{$pass}' {$db} > {$filepath}";
        exec($command, $output, $return_var);
        
        if($return_var === 0) {
            // Also create a ZIP archive
            $zip = new ZipArchive();
            $zipPath = $backupDir . str_replace('.sql', '.zip', $filename);
            if($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                $zip->addFile($filepath, $filename);
                $zip->close();
                unlink($filepath); // Remove SQL file, keep ZIP
            }
            
            $this->json(['success' => true, 'filename' => $filename]);
        } else {
            $this->json(['success' => false, 'message' => 'Backup failed']);
        }
    }
    
    public function list() {
        $backupDir = ROOT_PATH . '/backups/';
        $backups = [];
        
        if(is_dir($backupDir)) {
            $files = glob($backupDir . '*.zip');
            rsort($files);
            
            foreach($files as $file) {
                $backups[] = [
                    'filename' => basename($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                    'size' => $this->formatSize(filesize($file)),
                    'download_url' => BASE_URL . '/admin/backup/download/' . basename($file)
                ];
            }
        }
        
        $this->json($backups);
    }
    
    public function download($file) {
        $filepath = ROOT_PATH . '/backups/' . basename($file);
        
        if(file_exists($filepath)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
        
        $_SESSION['error'] = 'File not found';
        $this->redirect('admin/settings');
    }
    
    public function restore() {
        if($this->isPost() && isset($_FILES['backup_file'])) {
            $file = $_FILES['backup_file'];
            
            if($file['type'] == 'application/zip') {
                $zip = new ZipArchive();
                if($zip->open($file['tmp_name']) === TRUE) {
                    $sqlContent = $zip->getFromIndex(0);
                    $zip->close();
                    
                    // Execute SQL
                    $pdo = Database::getInstance()->getConnection();
                    $pdo->exec($sqlContent);
                    
                    $_SESSION['success'] = 'Database restored successfully';
                }
            } elseif($file['type'] == 'application/sql' || pathinfo($file['name'], PATHINFO_EXTENSION) == 'sql') {
                $sqlContent = file_get_contents($file['tmp_name']);
                $pdo = Database::getInstance()->getConnection();
                $pdo->exec($sqlContent);
                
                $_SESSION['success'] = 'Database restored successfully';
            }
            
            $this->redirect('admin/settings');
        }
    }
    
    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    private function requireAuth() {
        if(!isset($_SESSION['user_id'])) $this->redirect('login');
    }
    
    private function requireRole($roles) {
        if(!in_array($_SESSION['role_slug'], $roles)) $this->redirect('unauthorized');
    }
}