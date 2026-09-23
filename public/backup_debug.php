<?php
// public/backup_debug.php
// Debug page to check backups

session_start();

if(!isset($_SESSION['user_id'])) {
    die('Please login first');
}

$host = 'localhost';
$dbname = 'unidia_db';
$username = 'root';
$password = '';

echo "<h1>Backup Debug</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all backups
    $stmt = $pdo->query("SELECT * FROM backup_history ORDER BY id DESC");
    $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Backup Records (" . count($backups) . ")</h2>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Name</th><th>File Path</th><th>Size</th><th>Created</th><th>Download</th></tr>";
    
    $basePath = dirname(__DIR__);
    $backupDir = $basePath . '/backups/';
    
    foreach($backups as $backup) {
        echo "<tr>";
        echo "<td>" . $backup['id'] . "</td>";
        echo "<td>" . $backup['backup_name'] . "</td>";
        echo "<td>" . $backup['file_path'] . "</td>";
        echo "<td>" . $backup['file_size'] . "</td>";
        echo "<td>" . $backup['created_at'] . "</td>";
        
        // Check if file exists
        $filepath = $backupDir . basename($backup['file_path']);
        if(file_exists($filepath)) {
            echo "<td><a href='/unidia/public/download_backup.php?id=" . $backup['id'] . "' target='_blank'>⬇️ Download</a></td>";
        } else {
            // Try alternative path
            $altPath = $basePath . '/' . $backup['file_path'];
            if(file_exists($altPath)) {
                echo "<td><a href='/unidia/public/download_backup.php?id=" . $backup['id'] . "' target='_blank'>⬇️ Download (alt)</a></td>";
            } else {
                echo "<td style='color:red;'>❌ File Missing</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>Backups Directory Contents</h2>";
    echo "<p><strong>Directory:</strong> " . $backupDir . "</p>";
    
    if(is_dir($backupDir)) {
        $files = scandir($backupDir);
        echo "<ul>";
        foreach($files as $file) {
            if($file != '.' && $file != '..') {
                $fullPath = $backupDir . $file;
                $size = is_file($fullPath) ? filesize($fullPath) : 0;
                $sizeStr = $size > 0 ? number_format($size / 1024, 2) . ' KB' : 'Directory';
                echo "<li>" . $file . " - " . $sizeStr . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>⚠️ Backups directory does not exist!</p>";
        echo "<p>Create it manually: <code>mkdir " . $backupDir . "</code></p>";
    }
    
    echo "<h2>Direct Download Links</h2>";
    echo "<p><a href='/unidia/public/download_backup.php?id=1'>Download Backup ID 1</a></p>";
    echo "<p><a href='/unidia/public/download_backup.php?id=2'>Download Backup ID 2</a></p>";
    echo "<p><a href='/unidia/public/admin/settings/backup-history'>Back to Backup History</a></p>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>