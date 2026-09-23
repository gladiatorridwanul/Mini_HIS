<?php
// public/test_api_simple.php
// SIMPLE API TEST

session_start();
$_SESSION['user_id'] = 1;

// Try to call the API directly using file_get_contents
$apiUrl = 'http://localhost/unidia/public/api/book-appointment';

$data = [
    'patient_id' => 1,
    'doctor_id' => 4,
    'service_id' => 8,
    'additional_service_id' => 0,
    'appointment_date' => date('Y-m-d', strtotime('+3 day')),
    'shift' => 'morning',
    'appointment_type' => 'regular',
    'symptoms' => 'Test via API',
    'special_note' => 'Test API note',
    'payment_method' => 'cash',
    'discount_percent' => 0,
    'total_amount' => 800,
    'discount_amount' => 0,
    'subtotal' => 800
];

echo "<h2>=== API TEST ===</h2>";
echo "<p><strong>API URL:</strong> $apiUrl</p>";

// Try using file_get_contents with stream context
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
        'ignore_errors' => true
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($apiUrl, false, $context);

if ($response === false) {
    echo "<p style='color:red'>❌ Failed to connect to API</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>Route not registered in routes/web.php</li>";
    echo "<li>Controller method not found</li>";
    echo "<li>Server configuration issue</li>";
    echo "</ul>";
    
    // Check if the API file exists
    $controllerFile = __DIR__ . '/../app/controllers/AppointmentController.php';
    if (file_exists($controllerFile)) {
        echo "<p>✅ Controller file exists: $controllerFile</p>";
    } else {
        echo "<p style='color:red'>❌ Controller file NOT found: $controllerFile</p>";
    }
} else {
    echo "<p style='color:green'>✅ API responded</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $json = json_decode($response, true);
    if ($json) {
        echo "<h3>Parsed Response:</h3>";
        echo "<pre>" . print_r($json, true) . "</pre>";
        
        if (isset($json['success']) && $json['success']) {
            echo "<p style='color:green'>✅ SUCCESS! Bill ID: " . ($json['bill_id'] ?? 'N/A') . "</p>";
        } else {
            echo "<p style='color:red'>❌ Error: " . ($json['message'] ?? 'Unknown error') . "</p>";
        }
    }
}

// Also check if the controller exists
echo "<hr>";
echo "<h3>File Check:</h3>";
$controllerFile = __DIR__ . '/../app/controllers/AppointmentController.php';
if (file_exists($controllerFile)) {
    echo "<p>✅ AppointmentController.php exists</p>";
    // Check if apiBookAppointment method exists
    $content = file_get_contents($controllerFile);
    if (strpos($content, 'function apiBookAppointment') !== false) {
        echo "<p>✅ apiBookAppointment method found in controller</p>";
    } else {
        echo "<p style='color:red'>❌ apiBookAppointment method NOT found in controller</p>";
    }
} else {
    echo "<p style='color:red'>❌ AppointmentController.php NOT found at: $controllerFile</p>";
}

// Check routes file
echo "<hr>";
echo "<h3>Routes File Check:</h3>";
$routesFile = __DIR__ . '/../routes/web.php';
if (file_exists($routesFile)) {
    echo "<p>✅ routes/web.php exists</p>";
    $content = file_get_contents($routesFile);
    if (strpos($content, 'api/book-appointment') !== false) {
        echo "<p>✅ Route 'api/book-appointment' found in routes file</p>";
    } else {
        echo "<p style='color:red'>❌ Route 'api/book-appointment' NOT found in routes file</p>";
    }
} else {
    echo "<p style='color:red'>❌ routes/web.php NOT found</p>";
}
?>