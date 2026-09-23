<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Patient Store Debug</h1>";

// Check if PatientController exists
$controllerPath = '../app/controllers/PatientController.php';
if (file_exists($controllerPath)) {
    echo "<p style='color:green'>✓ PatientController.php exists</p>";
    require_once $controllerPath;
    
    if (class_exists('PatientController')) {
        echo "<p style='color:green'>✓ PatientController class exists</p>";
        
        // Check if store method exists
        if (method_exists('PatientController', 'store')) {
            echo "<p style='color:green'>✓ store() method exists</p>";
        } else {
            echo "<p style='color:red'>✗ store() method NOT found in PatientController</p>";
        }
    } else {
        echo "<p style='color:red'>✗ PatientController class NOT found</p>";
    }
} else {
    echo "<p style='color:red'>✗ PatientController.php NOT found at: " . realpath('../app/controllers') . "</p>";
}

// Check database connection
require_once '../app/config/config.php';
$test = $db->query("SELECT 1");
if ($test) {
    echo "<p style='color:green'>✓ Database connected</p>";
} else {
    echo "<p style='color:red'>✗ Database connection failed</p>";
}

// Check if route exists in index.php
echo "<h2>Checking route in index.php</h2>";
$indexContent = file_get_contents('index.php');
if (strpos($indexContent, "patient/store") !== false) {
    echo "<p style='color:green'>✓ Route 'patient/store' found in index.php</p>";
} else {
    echo "<p style='color:red'>✗ Route 'patient/store' NOT found in index.php</p>";
}
?>