<?php
// app/controllers/DoctorController.php

require_once BASE_PATH . '/core/Controller.php';
require_once __DIR__ . '/../models/Doctor.php';

class DoctorController extends Controller {
    
    // ==================== DOCTOR LISTING ====================
    public function index() {
        $this->checkAuth();
        
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        // Get doctors with pagination - ONLY ACTIVE DOCTORS (both doctor and user status must be active)
        $db = $this->db;
        
        // Build search condition
        $searchCondition = "";
        if (!empty($search)) {
            $search = $db->real_escape_string($search);
            $searchCondition = " AND (u.first_name LIKE '%$search%' 
                                   OR u.last_name LIKE '%$search%' 
                                   OR d.bmdc_number LIKE '%$search%' 
                                   OR d.specialization LIKE '%$search%')";
        }
        
        // Get total count
        $countQuery = "SELECT COUNT(*) as total 
                       FROM doctors d 
                       JOIN users u ON d.user_id = u.id 
                       WHERE d.status = 'active' AND u.status = 'active' $searchCondition";
        $countResult = $db->query($countQuery);
        $totalDoctors = $countResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($totalDoctors / $limit);
        
        // Get doctors data
        $query = "SELECT d.*, u.first_name, u.last_name, u.title, u.email, u.phone, u.employee_id,
                         dep.name as department_name
                  FROM doctors d 
                  JOIN users u ON d.user_id = u.id 
                  LEFT JOIN departments dep ON d.department_id = dep.id
                  WHERE d.status = 'active' AND u.status = 'active' $searchCondition
                  ORDER BY u.first_name ASC 
                  LIMIT $limit OFFSET $offset";
        $result = $db->query($query);
        
        $doctors = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                // Get services count for this doctor
                $servicesQuery = $db->query("SELECT COUNT(*) as count FROM doctor_services WHERE doctor_id = {$row['id']} AND status = 'active'");
                $servicesCount = $servicesQuery ? $servicesQuery->fetch_assoc()['count'] : 0;
                $row['services'] = [];
                $row['services_count'] = $servicesCount;
                $doctors[] = $row;
            }
        }
        
        $this->view('doctors/list', [
            'doctors' => $doctors,
            'search' => $search,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalDoctors' => $totalDoctors,
            'limit' => $limit,
            'offset' => $offset
        ], 'Doctor Management');
    }
    
    // ==================== VIEW DOCTOR DETAILS ====================
    public function show($id) {
        $this->checkAuth();
        
        $doctor = Doctor::find($id);
        
        if (!$doctor) {
            $_SESSION['error'] = "Doctor not found";
            $this->redirect('/doctor/list');
            return;
        }
        
        // Also check if the user is active
        $db = $this->db;
        $userQuery = "SELECT status FROM users WHERE id = " . (int)$doctor['user_id'];
        $userResult = $db->query($userQuery);
        if ($userResult && $userResult->num_rows > 0) {
            $userData = $userResult->fetch_assoc();
            $doctor['user_status'] = $userData['status'];
        }
        
        $this->view('doctors/view', ['doctor' => $doctor], 'Doctor Details');
    }
    
    // ==================== ADD DOCTOR FORM ====================
    public function create() {
        $this->checkAuth();
        $departments = Doctor::getDepartments();
        
        $this->view('doctors/create', ['departments' => $departments], 'Add New Doctor');
    }
    
    // ==================== STORE DOCTOR ====================
    public function store() {
        $this->checkAuth();
        
        // Validate required fields
        $errors = [];
        if (empty($_POST['title'])) $errors[] = "Title is required";
        if (empty($_POST['first_name'])) $errors[] = "First name is required";
        if (empty($_POST['last_name'])) $errors[] = "Last name is required";
        if (empty($_POST['phone'])) $errors[] = "Phone number is required";
        if (empty($_POST['email'])) $errors[] = "Email is required";
        if (empty($_POST['password'])) $errors[] = "Password is required";
        if (empty($_POST['department_id'])) $errors[] = "Department is required";
        if (empty($_POST['specialization'])) $errors[] = "Specialization is required";
        if (empty($_POST['qualification'])) $errors[] = "Qualification is required";
        if (empty($_POST['bmdc_number'])) $errors[] = "BMDC Number is required";
        if (!isset($_POST['consultation_fee']) || $_POST['consultation_fee'] <= 0) $errors[] = "Consultation fee is required";
        
        // Process services
        $services = [];
        if (isset($_POST['service_name']) && is_array($_POST['service_name'])) {
            for ($i = 0; $i < count($_POST['service_name']); $i++) {
                if (!empty($_POST['service_name'][$i]) && !empty($_POST['service_price'][$i])) {
                    $services[] = [
                        'name' => $_POST['service_name'][$i],
                        'price' => $_POST['service_price'][$i],
                        'commission' => $_POST['service_commission'][$i] ?? 0
                    ];
                }
            }
        }
        $_POST['services'] = $services;
        
        if (count($errors) > 0) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/doctor/create');
            return;
        }
        
        $doctorId = Doctor::create($_POST);
        
        if ($doctorId) {
            $_SESSION['success'] = "Doctor added successfully!";
            $this->redirect('/doctor/list');
        } else {
            $_SESSION['errors'] = ["Failed to add doctor. Please try again."];
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/doctor/create');
        }
    }
    
    // ==================== EDIT DOCTOR ====================
    public function edit($id) {
        $this->checkAuth();
        
        $doctor = Doctor::find($id);
        $departments = Doctor::getDepartments();
        
        if (!$doctor) {
            $_SESSION['error'] = "Doctor not found";
            $this->redirect('/doctor/list');
            return;
        }
        
        $this->view('doctors/edit', [
            'doctor' => $doctor,
            'departments' => $departments
        ], 'Edit Doctor');
    }
    
    // ==================== UPDATE DOCTOR ====================
    public function update($id) {
        $this->checkAuth();
        
        // Validate required fields
        $errors = [];
        if (empty($_POST['title'])) $errors[] = "Title is required";
        if (empty($_POST['first_name'])) $errors[] = "First name is required";
        if (empty($_POST['last_name'])) $errors[] = "Last name is required";
        if (empty($_POST['phone'])) $errors[] = "Phone number is required";
        if (empty($_POST['email'])) $errors[] = "Email is required";
        if (empty($_POST['department_id'])) $errors[] = "Department is required";
        if (empty($_POST['specialization'])) $errors[] = "Specialization is required";
        if (empty($_POST['qualification'])) $errors[] = "Qualification is required";
        if (empty($_POST['bmdc_number'])) $errors[] = "BMDC Number is required";
        
        // Process services
        $services = [];
        if (isset($_POST['service_name']) && is_array($_POST['service_name'])) {
            for ($i = 0; $i < count($_POST['service_name']); $i++) {
                if (!empty($_POST['service_name'][$i]) && !empty($_POST['service_price'][$i])) {
                    $services[] = [
                        'name' => $_POST['service_name'][$i],
                        'price' => $_POST['service_price'][$i],
                        'commission' => $_POST['service_commission'][$i] ?? 0
                    ];
                }
            }
        }
        $_POST['services'] = $services;
        
        if (count($errors) > 0) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old_input'] = $_POST;
            $this->redirect('/doctor/edit/' . $id);
            return;
        }
        
        if (Doctor::update($id, $_POST)) {
            $_SESSION['success'] = "Doctor updated successfully!";
        } else {
            $_SESSION['errors'] = ["Failed to update doctor."];
        }
        
        $this->redirect('/doctor/list');
    }
    
    // ==================== DELETE DOCTOR ====================
    public function delete($id) {
        $this->checkAuth();
        
        if (Doctor::delete($id)) {
            $_SESSION['success'] = "Doctor deleted successfully!";
        } else {
            $_SESSION['errors'] = ["Failed to delete doctor."];
        }
        
        $this->redirect('/doctor/list');
    }
    
    // ==================== SCHEDULE MANAGEMENT ====================
    public function scheduleList() {
        $this->checkAuth();
        
        $db = $this->db;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $totalQuery = "SELECT COUNT(*) as total 
                       FROM doctors d 
                       JOIN users u ON d.user_id = u.id 
                       WHERE u.role_id = 3 AND d.status = 'active'";
        $totalResult = $db->query($totalQuery);
        $totalDoctors = $totalResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($totalDoctors / $limit);
        
        $doctorsQuery = "SELECT d.id, d.user_id, u.title, u.first_name, u.last_name, d.specialization, d.bmdc_number, d.consultation_fee, d.status
                         FROM doctors d 
                         JOIN users u ON d.user_id = u.id 
                         WHERE u.role_id = 3 AND d.status = 'active'
                         ORDER BY u.first_name ASC 
                         LIMIT $limit OFFSET $offset";
        
        $doctors = $db->query($doctorsQuery);
        $doctorList = [];
        while ($row = $doctors->fetch_assoc()) {
            $sessions = $db->query("SELECT * FROM doctor_schedule_sessions WHERE doctor_id = {$row['id']} ORDER BY 
                                    FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
            $row['sessions'] = [];
            while ($session = $sessions->fetch_assoc()) {
                $row['sessions'][] = $session;
            }
            $doctorList[] = $row;
        }
        
        $this->view('doctors/schedule-list', [
            'doctors' => $doctorList,
            'totalDoctors' => $totalDoctors,
            'totalPages' => $totalPages,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ], 'Doctor Schedules');
    }
    
    public function schedule($id) {
        $this->checkAuth();
        
        $doctor = Doctor::find($id);
        if (!$doctor) {
            $_SESSION['error'] = "Doctor not found";
            $this->redirect('/doctor/schedule-list');
            return;
        }
        
        $db = $this->db;
        $schedules = $db->query("SELECT * FROM doctor_schedule_sessions WHERE doctor_id = $id");
        
        $sessionsByDay = [];
        while($row = $schedules->fetch_assoc()) {
            $sessionsByDay[$row['day_of_week']][$row['session_type']] = $row;
        }
        
        $this->view('doctors/schedule', [
            'doctor' => $doctor,
            'sessionsByDay' => $sessionsByDay
        ], 'Doctor Schedule');
    }
    
    public function saveSchedule($id) {
        $this->checkAuth();
        
        $db = $this->db;
        $id = (int)$id;
        
        $db->query("DELETE FROM doctor_schedule_sessions WHERE doctor_id = $id");
        
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $inserted = 0;
        $errors = [];
        
        foreach ($days as $day) {
            // Morning session
            $morningActive = isset($_POST["{$day}_morning_active"]) && $_POST["{$day}_morning_active"] == '1';
            if ($morningActive) {
                $start_time = $db->real_escape_string($_POST["{$day}_morning_start"]);
                $end_time = $db->real_escape_string($_POST["{$day}_morning_end"]);
                $slot_duration = (int)$_POST["{$day}_morning_slot"];
                $max_patients = (int)$_POST["{$day}_morning_patients"];
                
                if (empty($start_time) || empty($end_time)) {
                    $errors[] = "Missing time for {$day} morning session";
                    continue;
                }
                
                if ($start_time >= $end_time) {
                    $errors[] = "Start time must be before end time for {$day} morning session";
                    continue;
                }
                
                $query = "INSERT INTO doctor_schedule_sessions 
                          (doctor_id, day_of_week, session_type, start_time, end_time, slot_duration, max_patients, is_available) 
                          VALUES ($id, '$day', 'morning', '$start_time', '$end_time', $slot_duration, $max_patients, 1)";
                
                if ($db->query($query)) {
                    $inserted++;
                } else {
                    $errors[] = "Failed to save {$day} morning session: " . $db->error;
                }
            }
            
            // Evening session
            $eveningActive = isset($_POST["{$day}_evening_active"]) && $_POST["{$day}_evening_active"] == '1';
            if ($eveningActive) {
                $start_time = $db->real_escape_string($_POST["{$day}_evening_start"]);
                $end_time = $db->real_escape_string($_POST["{$day}_evening_end"]);
                $slot_duration = (int)$_POST["{$day}_evening_slot"];
                $max_patients = (int)$_POST["{$day}_evening_patients"];
                
                if (empty($start_time) || empty($end_time)) {
                    $errors[] = "Missing time for {$day} evening session";
                    continue;
                }
                
                if ($start_time >= $end_time) {
                    $errors[] = "Start time must be before end time for {$day} evening session";
                    continue;
                }
                
                $query = "INSERT INTO doctor_schedule_sessions 
                          (doctor_id, day_of_week, session_type, start_time, end_time, slot_duration, max_patients, is_available) 
                          VALUES ($id, '$day', 'evening', '$start_time', '$end_time', $slot_duration, $max_patients, 1)";
                
                if ($db->query($query)) {
                    $inserted++;
                } else {
                    $errors[] = "Failed to save {$day} evening session: " . $db->error;
                }
            }
        }
        
        if (count($errors) > 0) {
            $_SESSION['errors'] = $errors;
            $_SESSION['error'] = "Some sessions failed to save. Please check the form.";
        }
        
        $_SESSION['success'] = "$inserted schedule(s) saved successfully!";
        $this->redirect('/doctor/schedule-list');
    }
    
    // ==================== COMMISSION MANAGEMENT ====================
    public function commissions() {
        $this->checkAuth();
        
        $db = $this->db;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $countQuery = "SELECT COUNT(*) as total FROM doctor_commissions";
        $countResult = $db->query($countQuery);
        $totalRecords = $countResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($totalRecords / $limit);
        
        $commissions = $db->query("SELECT dc.*, 
                                      d.id as doctor_id,
                                      u.first_name, 
                                      u.last_name,
                                      u.title,
                                      p.first_name as patient_fname, 
                                      p.last_name as patient_lname
                               FROM doctor_commissions dc
                               JOIN doctors d ON dc.doctor_id = d.id
                               JOIN users u ON d.user_id = u.id
                               LEFT JOIN appointments a ON dc.reference_id = a.id AND dc.reference_type = 'consultation'
                               LEFT JOIN patients p ON a.patient_id = p.id
                               ORDER BY dc.created_at DESC
                               LIMIT $limit OFFSET $offset");
        
        $commissionList = [];
        while ($row = $commissions->fetch_assoc()) {
            $commissionList[] = $row;
        }
        
        $doctorsQuery = $db->query("SELECT d.id, u.first_name, u.last_name, u.title 
                                    FROM doctors d 
                                    JOIN users u ON d.user_id = u.id 
                                    WHERE d.status = 'active' 
                                    ORDER BY u.first_name ASC");
        $doctors = [];
        while ($row = $doctorsQuery->fetch_assoc()) {
            $doctors[] = $row;
        }
        
        $this->view('doctors/commissions', [
            'commissions' => $commissionList,
            'doctors' => $doctors,
            'selectedDoctor' => isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages
        ], 'Doctor Commissions');
    }
    
    // ==================== COMMISSION API METHODS ====================
    public function apiApproveCommission() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $commissionId = isset($_POST['commission_id']) ? (int)$_POST['commission_id'] : 0;
        
        if($commissionId) {
            $db = $this->db;
            $result = $db->query("UPDATE doctor_commissions SET status = 'approved' WHERE id = $commissionId");
            
            if($result) {
                echo json_encode(['success' => true, 'message' => 'Commission approved successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid commission ID']);
        }
        exit;
    }
    
    public function apiPayCommission() {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $commissionId = isset($_POST['commission_id']) ? (int)$_POST['commission_id'] : 0;
        $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'bank_transfer';
        $transactionId = isset($_POST['transaction_id']) ? $_POST['transaction_id'] : '';
        $paymentDate = isset($_POST['payment_date']) ? $_POST['payment_date'] : date('Y-m-d');
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        if($commissionId) {
            $db = $this->db;
            
            $checkQuery = $db->query("SELECT id FROM doctor_commissions WHERE id = $commissionId");
            if($checkQuery && $checkQuery->num_rows > 0) {
                $paymentMethod = $db->real_escape_string($paymentMethod);
                $transactionId = $db->real_escape_string($transactionId);
                $paymentDate = $db->real_escape_string($paymentDate);
                $notes = $db->real_escape_string($notes);
                
                $db->query("UPDATE doctor_commissions SET 
                           status = 'paid', 
                           paid_at = NOW(),
                           payment_method = '$paymentMethod',
                           transaction_id = '$transactionId',
                           payment_date = '$paymentDate',
                           notes = '$notes'
                           WHERE id = $commissionId");
                
                echo json_encode(['success' => true, 'message' => 'Commission settled successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Commission not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid commission ID']);
        }
        exit;
    }
    
    public function apiCommissionDetails($id) {
        header('Content-Type: application/json');
        $this->checkAuth();
        
        $id = (int)$id;
        $db = $this->db;
        
        $query = $db->query("SELECT dc.*, 
                                    CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) as doctor_name,
                                    d.id as doctor_id
                             FROM doctor_commissions dc
                             JOIN doctors d ON dc.doctor_id = d.id
                             JOIN users u ON d.user_id = u.id
                             WHERE dc.id = $id");
        
        if($query && $query->num_rows > 0) {
            $commission = $query->fetch_assoc();
            echo json_encode(['success' => true, 'data' => $commission]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Commission not found']);
        }
        exit;
    }
    
    public function apiExportCommissions() {
        $this->checkAuth();
        
        $db = $this->db;
        
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
        
        $where = "WHERE 1=1";
        if($doctorId) $where .= " AND dc.doctor_id = $doctorId";
        if($status) $where .= " AND dc.status = '$status'";
        if($type) $where .= " AND dc.reference_type = '$type'";
        if($dateFrom) $where .= " AND DATE(dc.created_at) >= '$dateFrom'";
        if($dateTo) $where .= " AND DATE(dc.created_at) <= '$dateTo'";
        
        $query = $db->query("SELECT dc.*, 
                                    CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) as doctor_name
                             FROM doctor_commissions dc
                             JOIN doctors d ON dc.doctor_id = d.id
                             JOIN users u ON d.user_id = u.id
                             $where
                             ORDER BY dc.created_at DESC");
        
        $filename = "commission_report_" . date('Y-m-d') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['ID', 'Doctor Name', 'Reference Type', 'Service Name', 'Reference ID', 'Amount (BDT)', 'Commission %', 'Commission Amount (BDT)', 'Status', 'Created Date', 'Paid Date']);
        
        while($row = $query->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['doctor_name'],
                $row['reference_type'],
                $row['service_name'] ?? '',
                $row['reference_id'],
                $row['amount'],
                $row['commission_percentage'],
                $row['commission_amount'],
                $row['status'],
                $row['created_at'],
                $row['paid_at'] ?? ''
            ]);
        }
        fclose($output);
        exit;
    }
    
    public function apiGenerateCommissionReport() {
        $this->checkAuth();
        
        $db = $this->db;
        
        $period = isset($_GET['period']) ? $_GET['period'] : 'monthly';
        $doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 0;
        
        if($period == 'weekly') {
            $dateFrom = date('Y-m-d', strtotime('monday this week'));
            $dateTo = date('Y-m-d', strtotime('sunday this week'));
        } elseif($period == 'monthly') {
            $dateFrom = date('Y-m-01');
            $dateTo = date('Y-m-t');
        } else {
            $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
            $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');
        }
        
        $where = "WHERE DATE(dc.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        if($doctorId) $where .= " AND dc.doctor_id = $doctorId";
        
        $summaryQuery = $db->query("SELECT 
                                        d.id as doctor_id,
                                        CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) as doctor_name,
                                        SUM(CASE WHEN dc.status = 'paid' THEN dc.commission_amount ELSE 0 END) as paid_amount,
                                        SUM(CASE WHEN dc.status = 'approved' THEN dc.commission_amount ELSE 0 END) as approved_amount,
                                        SUM(CASE WHEN dc.status = 'pending' THEN dc.commission_amount ELSE 0 END) as pending_amount,
                                        COUNT(*) as total_transactions
                                    FROM doctor_commissions dc
                                    JOIN doctors d ON dc.doctor_id = d.id
                                    JOIN users u ON d.user_id = u.id
                                    $where
                                    GROUP BY d.id
                                    ORDER BY doctor_name ASC");
        
        $detailsQuery = $db->query("SELECT dc.*, 
                                        CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) as doctor_name
                                    FROM doctor_commissions dc
                                    JOIN doctors d ON dc.doctor_id = d.id
                                    JOIN users u ON d.user_id = u.id
                                    $where
                                    ORDER BY dc.created_at DESC");
        
        $html = "<!DOCTYPE html>
        <html>
        <head>
            <title>Commission Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h2 { margin: 0; }
                .header p { margin: 5px 0; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h2>Commission Report</h2>
                <p>Period: " . date('d M Y', strtotime($dateFrom)) . " - " . date('d M Y', strtotime($dateTo)) . "</p>
            </div>
            <hr>
            <h3>Summary by Doctor</h3>
            <table>
                <thead>
                    <tr><th>Doctor</th><th>Transactions</th><th>Pending (BDT)</th><th>Approved (BDT)</th><th>Settled (BDT)</th><th>Total (BDT)</th></tr>
                </thead>
                <tbody>";
        
        while($row = $summaryQuery->fetch_assoc()) {
            $total = $row['paid_amount'] + $row['approved_amount'] + $row['pending_amount'];
            $html .= "<tr>
                        <td>{$row['doctor_name']}</td>
                        <td class='text-center'>{$row['total_transactions']}</td>
                        <td class='text-right'>" . number_format($row['pending_amount'], 2) . "</td>
                        <td class='text-right'>" . number_format($row['approved_amount'], 2) . "</td>
                        <td class='text-right'>" . number_format($row['paid_amount'], 2) . "</td>
                        <td class='text-right'><strong>" . number_format($total, 2) . "</strong></td>
                      </tr>";
        }
        
        $html .= "</tbody>
                  </table>
                 <h3>Transaction Details</h3>
                 <table>
                    <thead>
                        <tr><th>Date</th><th>Doctor</th><th>Reference Type</th><th>Amount (BDT)</th><th>Commission (BDT)</th><th>Status</th></tr>
                    </thead>
                    <tbody>";
        
        while($row = $detailsQuery->fetch_assoc()) {
            $statusText = $row['status'] == 'paid' ? 'Settled' : ($row['status'] == 'approved' ? 'Approved' : 'Pending');
            $html .= "<tr>
                        <td>" . date('d M Y', strtotime($row['created_at'])) . "</td>
                        <td>{$row['doctor_name']}</td>
                        <td>" . ucfirst($row['reference_type']) . "</td>
                        <td class='text-right'>" . number_format($row['amount'], 2) . "</td>
                        <td class='text-right'><strong>" . number_format($row['commission_amount'], 2) . "</strong></td>
                        <td>{$statusText}</td>
                      </tr>";
        }
        
        $html .= "</tbody>
                  </table>
                  <div style='text-align:center;margin-top:20px;'>
                      <button onclick='window.print()'>Print Report</button>
                      <button onclick='window.close()'>Close</button>
                  </div>
                 </body>
                 </html>";
        
        echo $html;
        exit;
    }
    
    public function apiPrintCommission($id) {
        $db = $this->db;
        $id = (int)$id;
        
        $query = $db->query("SELECT dc.*, 
                                    CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) as doctor_name
                             FROM doctor_commissions dc
                             JOIN doctors d ON dc.doctor_id = d.id
                             JOIN users u ON d.user_id = u.id
                             WHERE dc.id = $id");
        
        $commission = $query->fetch_assoc();
        
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Commission Slip</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .slip { border: 1px solid #ddd; padding: 20px; max-width: 500px; margin: auto; border-radius: 10px; }
                .header { text-align: center; border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 15px; }
                .row { padding: 5px 0; display: flex; }
                .label { font-weight: bold; width: 150px; }
                .value { flex: 1; }
                .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; }
                button { padding: 5px 15px; margin-top: 10px; cursor: pointer; }
            </style>
        </head>
        <body>
            <div class='slip'>
                <div class='header'>
                    <h3>Commission Slip</h3>
                    <p>Date: " . date('d M Y') . "</p>
                </div>
                <div class='row'><div class='label'>Commission ID:</div><div class='value'>#" . $commission['id'] . "</div></div>
                <div class='row'><div class='label'>Doctor:</div><div class='value'>Dr. " . $commission['doctor_name'] . "</div></div>
                <div class='row'><div class='label'>Reference Type:</div><div class='value'>" . ucfirst($commission['reference_type']) . "</div></div>
                <div class='row'><div class='label'>Service Name:</div><div class='value'>" . ($commission['service_name'] ?? '-') . "</div></div>
                <div class='row'><div class='label'>Reference ID:</div><div class='value'>#" . $commission['reference_id'] . "</div></div>
                <div class='row'><div class='label'>Amount:</div><div class='value'>৳ " . number_format($commission['amount'], 2) . "</div></div>
                <div class='row'><div class='label'>Commission %:</div><div class='value'>" . $commission['commission_percentage'] . "%</div></div>
                <div class='row'><div class='label'>Commission Amount:</div><div class='value'><strong>৳ " . number_format($commission['commission_amount'], 2) . "</strong></div></div>
                <div class='row'><div class='label'>Status:</div><div class='value'>" . ucfirst($commission['status']) . "</div></div>
                <div class='footer'>
                    <button onclick='window.print()'>Print</button>
                    <button onclick='window.close()'>Close</button>
                </div>
            </div>
            <script>
                setTimeout(function() { window.print(); }, 500);
            </script>
        </body>
        </html>";
        exit;
    }

    public function doctorList() {
    $this->checkAuth();
    
    // Get all active doctors
    $db = Database::getInstance()->getConnection();
    $doctors = $db->query("SELECT d.id, d.specialization, d.consultation_fee, 
                                  u.first_name, u.last_name, u.title
                           FROM doctors d 
                           JOIN users u ON d.user_id = u.id 
                           WHERE d.status = 'active' AND u.role_id = 3
                           ORDER BY u.first_name ASC");
    
    $doctorList = [];
    while ($row = $doctors->fetch_assoc()) {
        $doctorList[] = $row;
    }
    
    $this->view('reception/doctor-list', ['doctors' => $doctorList], 'Doctor Wise Patient List');
}

// ==================== GET DOCTOR BY ID ====================
public static function getDoctorById($id) {
    $db = Database::getInstance()->getConnection();
    $id = (int)$id;
    
    $query = "SELECT d.*, u.first_name, u.last_name, u.title, u.email, u.phone, u.address
              FROM doctors d
              JOIN users u ON d.user_id = u.id
              WHERE d.id = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// ==================== API: ADD NEW DEPARTMENT ====================
public function apiAddDepartment() {
    header('Content-Type: application/json');
    $this->checkAuth();
    
    try {
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        
        // Validate
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Department name is required']);
            exit;
        }
        
        $db = $this->db;
        $name = $db->real_escape_string($name);
        $code = $db->real_escape_string($code);
        $description = $db->real_escape_string($description);
        
        // Check if department already exists
        $check = $db->query("SELECT id FROM departments WHERE name = '$name'");
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            echo json_encode([
                'success' => true, 
                'department_id' => $row['id'],
                'department_name' => $name,
                'message' => 'Department already exists'
            ]);
            exit;
        }
        
        // Check if code is unique
        if (!empty($code)) {
            $checkCode = $db->query("SELECT id FROM departments WHERE code = '$code'");
            if ($checkCode && $checkCode->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Department code already exists']);
                exit;
            }
        }
        
        // Insert new department
        $codeValue = !empty($code) ? "'$code'" : "NULL";
        $descValue = !empty($description) ? "'$description'" : "NULL";
        
        $query = "INSERT INTO departments (name, code, description, status, created_at) 
                  VALUES ('$name', $codeValue, $descValue, 'active', NOW())";
        
        if ($db->query($query)) {
            $departmentId = $db->insert_id;
            echo json_encode([
                'success' => true,
                'department_id' => $departmentId,
                'department_name' => $name,
                'message' => 'Department added successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add department: ' . $db->error]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// ==================== UPDATE USER STATUS (with doctor sync) ====================
public function updateUserStatus($id) {
    $this->checkAuth();
    
    $userId = (int)$id;
    $newStatus = isset($_POST['status']) ? $_POST['status'] : '';
    
    if (empty($newStatus) || !in_array($newStatus, ['active', 'inactive', 'suspended'])) {
        $_SESSION['error'] = "Invalid status value";
        $this->redirect('/doctor/list');
        return;
    }
    
    // Get the user to verify it exists and is a doctor
    $db = $this->db;
    $checkQuery = "SELECT u.id, d.id as doctor_id 
                   FROM users u 
                   LEFT JOIN doctors d ON u.id = d.user_id 
                   WHERE u.id = $userId AND u.role_id = 3";
    $checkResult = $db->query($checkQuery);
    
    if (!$checkResult || $checkResult->num_rows == 0) {
        $_SESSION['error'] = "Doctor not found";
        $this->redirect('/doctor/list');
        return;
    }
    
    $userData = $checkResult->fetch_assoc();
    
    // Update user status and sync doctor status
    if (Doctor::updateUserStatusAndSyncDoctor($userId, $newStatus)) {
        $_SESSION['success'] = "Doctor status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update doctor status";
    }
    
    $this->redirect('/doctor/list');
}

}
?>