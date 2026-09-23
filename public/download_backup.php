<?php
// public/download_backup.php
// Standalone backup download script

session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    die('Unauthorized access. Please login first.');
}

// Get backup ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($id == 0) {
    die('Invalid backup ID');
}

// Database connection
$host = 'localhost';
$dbname = 'unidia_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get backup record
    $stmt = $pdo->prepare("SELECT * FROM backup_history WHERE id = ?");
    $stmt->execute([$id]);
    $backup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$backup) {
        die('Backup not found');
    }
    
    $basePath = dirname(__DIR__);
    
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
        echo "<h2>Backup File Not Found</h2>";
        echo "<p><strong>Backup Name:</strong> " . $backup['backup_name'] . "</p>";
        echo "<p><strong>File Path:</strong> " . $backup['file_path'] . "</p>";
        echo "<p><a href='/unidia/public/admin/settings/backup-history'>← Back to Backup History</a></p>";
        exit;
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
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>