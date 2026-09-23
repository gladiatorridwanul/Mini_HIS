<?php
// test_db.php - Place in public folder
// Simple database connection test - NO dependencies

echo "<h1>Database Test</h1>";

// Try to connect directly
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'unidia_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "✅ Connected successfully<br><br>";

// Check if prescriptions table exists
$result = $conn->query("SHOW TABLES LIKE 'prescriptions'");
if ($result->num_rows > 0) {
    echo "✅ prescriptions table exists<br>";
} else {
    echo "❌ prescriptions table does NOT exist<br>";
}

// Get latest prescription
$result = $conn->query("SELECT * FROM prescriptions ORDER BY id DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $prescription = $result->fetch_assoc();
    echo "<h2>Latest Prescription:</h2>";
    echo "<pre>";
    print_r($prescription);
    echo "</pre>";
    
    echo "<hr>";
    
    // Check related tables
    $tables = [
        'prescription_chief_complaints' => 'Chief Complaints',
        'prescription_drug_history' => 'Drug History',
        'prescription_disease_history' => 'Disease History',
        'prescription_investigations' => 'Investigations',
        'prescription_physical_examination' => 'Physical Examination',
        'prescription_vital_signs' => 'Vital Signs',
        'prescription_advice' => 'Advice',
        'prescription_items' => 'Medicines',
        'prescription_medicine_details' => 'Medicine Details'
    ];
    
    $prescriptionId = $prescription['id'];
    
    foreach ($tables as $table => $label) {
        $result = $conn->query("SELECT * FROM {$table} WHERE prescription_id = {$prescriptionId}");
        echo "<h3>{$label}: " . $result->num_rows . " rows</h3>";
        
        if ($result->num_rows > 0) {
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
            $first = true;
            while ($row = $result->fetch_assoc()) {
                if ($first) {
                    echo "<tr style='background:#f0f0f0;'>";
                    foreach (array_keys($row) as $key) {
                        echo "<th>" . htmlspecialchars($key) . "</th>";
                    }
                    echo "</tr>";
                    $first = false;
                }
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "<br>";
    }
} else {
    echo "❌ No prescriptions found in database<br>";
}

$conn->close();

echo "<hr>";
echo "<h2>Check Your Project Structure:</h2>";
echo "BASE_PATH should be: " . dirname(__DIR__) . "<br>";
?>