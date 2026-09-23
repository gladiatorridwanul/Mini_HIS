<?php
// public/book_appointment_ajax.php
// CORRECTED VERSION - NO FATAL ERRORS

session_start();
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

header('Content-Type: application/json');

// Get POST data
$patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
$additionalServiceId = isset($_POST['additional_service_id']) ? (int)$_POST['additional_service_id'] : 0;
$appointmentDate = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
$shift = isset($_POST['shift']) ? $_POST['shift'] : '';
$appointmentType = isset($_POST['appointment_type']) ? $_POST['appointment_type'] : 'regular';
$symptoms = isset($_POST['symptoms']) ? $_POST['symptoms'] : '';
$specialNote = isset($_POST['special_note']) ? $_POST['special_note'] : '';
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
$discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
$totalAmount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
$discountAmount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0;
$subtotal = isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0;
$userId = 1;

// Validation
$errors = array();
if($patientId == 0) $errors[] = "Patient is required";
if($doctorId == 0) $errors[] = "Doctor is required";
if(empty($appointmentDate)) $errors[] = "Appointment date is required";
if(empty($shift)) $errors[] = "Session/Shift is required";
if($serviceId == 0) $errors[] = "Service is required";

if(!empty($errors)) {
    echo json_encode(array('success' => false, 'message' => implode(', ', $errors)));
    exit;
}

try {
    $host = 'localhost';
    $dbname = 'unidia_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get service details
    $stmt = $pdo->prepare("SELECT id, service_name, service_price, service_type FROM doctor_services WHERE id = ? AND status = 'active'");
    $stmt->execute(array($serviceId));
    $service = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service) {
        echo json_encode(array('success' => false, 'message' => "Service not found"));
        exit;
    }
    
    $serviceFee = (float)$service['service_price'];
    $serviceName = $service['service_name'];
    $serviceType = isset($service['service_type']) ? $service['service_type'] : 'consultation';
    
    // Get doctor
    $stmt = $pdo->prepare("SELECT d.consultation_fee, CONCAT(u.first_name, ' ', u.last_name) as doctor_name 
                           FROM doctors d JOIN users u ON d.user_id = u.id WHERE d.id = ?");
    $stmt->execute(array($doctorId));
    $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
    $doctorName = isset($doctor['doctor_name']) ? $doctor['doctor_name'] : 'Doctor';
    
    // Calculate total
    if($subtotal == 0) {
        $subtotal = $serviceFee;
    }
    if($discountAmount == 0) {
        $discountAmount = ($subtotal * $discountPercent) / 100;
    }
    if($totalAmount == 0) {
        $totalAmount = $subtotal - $discountAmount;
    }
    
    // Generate serial
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments 
                           WHERE doctor_id = ? AND appointment_date = ? AND session_type = ? AND status != 'canceled'");
    $stmt->execute(array($doctorId, $appointmentDate, $shift));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $serialCount = isset($row['count']) ? (int)$row['count'] : 0;
    $serialNumber = ($shift == 'morning' ? 'M' : 'E') . str_pad(($serialCount + 1), 3, '0', STR_PAD_LEFT);
    
    $appointmentNumber = 'APT' . date('Ymd') . rand(1000, 9999);
    $startTime = $shift == 'morning' ? '09:00:00' : '14:00:00';
    $endTime = $shift == 'morning' ? '13:00:00' : '18:00:00';
    
    $pdo->beginTransaction();
    
    // 1. Insert Appointment
    $stmt = $pdo->prepare("INSERT INTO appointments (
        appointment_number, patient_id, doctor_id, appointment_date, 
        start_time, end_time, session_type, appointment_type, 
        symptoms, special_note, status, payment_status, 
        payment_method, total_amount, discount, service_id, created_by
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', 'pending', ?, ?, ?, ?, ?
    )");
    
    $stmt->execute(array(
        $appointmentNumber, $patientId, $doctorId, $appointmentDate,
        $startTime, $endTime, $shift, $appointmentType,
        $symptoms, $specialNote,
        $paymentMethod, $totalAmount, $discountAmount, $serviceId, $userId
    ));
    $appointmentId = $pdo->lastInsertId();
    
    // 2. Insert Queue
    $stmt = $pdo->prepare("INSERT INTO queue (appointment_id, serial_number, shift, status) VALUES (?, ?, ?, 'waiting')");
    $stmt->execute(array($appointmentId, $serialNumber, $shift));
    
    // 3. Insert Bill
    $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
    $stmt = $pdo->prepare("INSERT INTO bills (
        bill_number, patient_id, bill_type, bill_date, 
        subtotal, discount_amount, discount_percentage, tax_amount, total_amount, 
        paid_amount, balance_amount, payment_status, 
        payment_method, created_by, reference_type, reference_id
    ) VALUES (
        ?, ?, 'consultation', CURDATE(), 
        ?, ?, ?, 0, ?, 
        0, ?, 'pending', 
        ?, ?, 'appointment', ?
    )");
    
    $stmt->execute(array(
        $billNumber, $patientId,
        $subtotal, $discountAmount, $discountPercent, $totalAmount,
        $totalAmount,
        $paymentMethod, $userId, $appointmentId
    ));
    $billId = $pdo->lastInsertId();
    
    // 4. INSERT BILL ITEM
    $itemType = $serviceType;
    $description = $serviceName;
    
    $stmt = $pdo->prepare("INSERT INTO bill_items (
        bill_id, item_type, description, quantity, 
        unit_price, discount_percentage, discount_amount,
        tax_percentage, tax_amount, total_amount
    ) VALUES (
        ?, ?, ?, 1, 
        ?, 0, 0, 0, 0, ?
    )");
    
    $stmt->execute(array($billId, $itemType, $description, $serviceFee, $serviceFee));
    $billItemId = $pdo->lastInsertId();
    
    // 5. Insert Commission
    $commissionPercentage = 20;
    $commissionAmount = ($serviceFee * $commissionPercentage) / 100;
    $stmt = $pdo->prepare("INSERT INTO doctor_commissions (
        doctor_id, reference_type, reference_id, amount, 
        commission_percentage, commission_amount, status
    ) VALUES (?, 'consultation', ?, ?, ?, ?, 'pending')");
    $stmt->execute(array($doctorId, $appointmentId, $serviceFee, $commissionPercentage, $commissionAmount));
    
    // 6. Update bill balance
    $stmt = $pdo->prepare("UPDATE bills SET balance_amount = total_amount - paid_amount WHERE id = ?");
    $stmt->execute(array($billId));
    
    $pdo->commit();
    
    // Return success
    echo json_encode(array(
        'success' => true,
        'message' => 'Appointment booked successfully! Serial: ' . $serialNumber,
        'appointment_number' => $appointmentNumber,
        'appointment_id' => $appointmentId,
        'serial_number' => $serialNumber,
        'total_amount' => number_format($totalAmount, 2),
        'bill_id' => $billId,
        'bill_number' => $billNumber,
        'bill_item_id' => $billItemId,
        'service_name' => $serviceName,
        'service_type' => $serviceType,
        'service_price' => $serviceFee,
        'item_count' => 1,
        'balance_amount' => number_format($totalAmount, 2)
    ));
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo json_encode(array('success' => false, 'message' => $e->getMessage()));
}
?>