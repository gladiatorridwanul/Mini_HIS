<?php
// app/controllers/DashboardController.php

class DashboardController extends Controller {
    
    public function __construct() {
        parent::__construct();
        $this->checkAuth();
    }
    
    public function index() {
        $userRole = $_SESSION['role_slug'] ?? 'guest';
        $userId = $_SESSION['user_id'] ?? 0;
        
        // ================================================================
        // FIXED: Get user information from session with proper checks
        // ================================================================
        
        // Get user name - try multiple possible session keys
        $userName = 'Admin';
        if (isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name'])) {
            $userName = $_SESSION['user_full_name'];
        } elseif (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
            $userName = trim($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
            if (empty($userName)) {
                $userName = $_SESSION['first_name'] ?? 'Admin';
            }
        } elseif (isset($_SESSION['first_name'])) {
            $userName = $_SESSION['first_name'];
        } elseif (isset($_SESSION['user_name'])) {
            $userName = $_SESSION['user_name'];
        }
        
        // Get user email
        $userEmail = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
        
        // Get user phone
        $userPhone = $_SESSION['user_phone'] ?? $_SESSION['phone'] ?? '';
        
        // Get role ID
        $roleId = $_SESSION['role_id'] ?? 0;
        
        // Get role label
        $roleLabel = $this->getRoleLabel($userRole, $roleId);
        
        // Get role-specific dashboard data
        $stats = $this->getRoleBasedStats($userRole, $userId);
        
        // Add user information to stats
        $stats['userName'] = $userName;
        $stats['userEmail'] = $userEmail;
        $stats['userPhone'] = $userPhone;
        $stats['roleLabel'] = $roleLabel;
        $stats['roleId'] = $roleId;
        $stats['todayDate'] = date('l, d M Y');
        $stats['currentTime'] = date('h:i:s A');
        
        $this->view('dashboard/index', [
            'stats' => $stats,
            'pageTitle' => 'Dashboard',
            'userRole' => $userRole
        ]);
    }

    /**
     * JSON API endpoint for dashboard data (for AJAX refresh)
     */
    public function apiStats() {
        // Check authentication
        $this->checkAuth();
        
        // Set JSON header
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        try {
            $userRole = $_SESSION['role_slug'] ?? 'guest';
            $userId = $_SESSION['user_id'] ?? 0;
            
            // ================================================================
            // FIXED: Get user information from session with proper checks
            // ================================================================
            
            // Get user name - try multiple possible session keys
            $userName = 'Admin';
            if (isset($_SESSION['user_full_name']) && !empty($_SESSION['user_full_name'])) {
                $userName = $_SESSION['user_full_name'];
            } elseif (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) {
                $userName = trim($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
                if (empty($userName)) {
                    $userName = $_SESSION['first_name'] ?? 'Admin';
                }
            } elseif (isset($_SESSION['first_name'])) {
                $userName = $_SESSION['first_name'];
            } elseif (isset($_SESSION['user_name'])) {
                $userName = $_SESSION['user_name'];
            }
            
            // Get user email
            $userEmail = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';
            
            // Get user phone
            $userPhone = $_SESSION['user_phone'] ?? $_SESSION['phone'] ?? '';
            
            // Get role ID
            $roleId = $_SESSION['role_id'] ?? 0;
            
            // Get role label
            $roleLabel = $this->getRoleLabel($userRole, $roleId);
            
            // Get role-based stats
            $stats = $this->getRoleBasedStats($userRole, $userId);
            
            // Add user information to stats
            $stats['userName'] = $userName;
            $stats['userEmail'] = $userEmail;
            $stats['userPhone'] = $userPhone;
            $stats['roleLabel'] = $roleLabel;
            $stats['roleId'] = $roleId;
            $stats['todayDate'] = date('l, d M Y');
            $stats['currentTime'] = date('h:i:s A');
            
            echo json_encode([
                'success' => true,
                'data' => $stats,
                'message' => 'Dashboard refreshed successfully'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }
    
    /**
     * Get role label from role slug or role ID
     */
    private function getRoleLabel($role, $roleId = 0) {
        // First try to get from role ID
        $roleLabels = [
            1 => 'Super Admin',
            2 => 'Admin',
            3 => 'Doctor',
            4 => 'Receptionist',
            5 => 'Pharmacist',
            6 => 'Lab Technician',
            7 => 'Accountant',
            8 => 'Nurse'
        ];
        
        if ($roleId > 0 && isset($roleLabels[$roleId])) {
            return $roleLabels[$roleId];
        }
        
        // Then try from role slug
        $roleMap = [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'doctor' => 'Doctor',
            'receptionist' => 'Receptionist',
            'pharmacist' => 'Pharmacist',
            'lab_technician' => 'Lab Technician',
            'accountant' => 'Accountant',
            'nurse' => 'Nurse'
        ];
        
        return $roleMap[$role] ?? ucfirst($role);
    }
    
    /**
     * Get role-based statistics
     */
    private function getRoleBasedStats($role, $userId) {
        $db = $this->db;
        $stats = [];
        
        // ============================================================
        // COMMON STATS FOR ALL ROLES
        // ============================================================
        
        // Today's date
        $today = date('Y-m-d');
        $currentMonth = date('Y-m');
        
        // ============================================================
        // SUPER ADMIN & ADMIN - Full Dashboard
        // ============================================================
        if ($role == 'super_admin' || $role == 'admin') {
            // Total Patients
            $result = $db->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active'");
            $stats['totalPatients'] = $result->fetch_assoc()['total'] ?? 0;
            
            // New Patients This Month
            $result = $db->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
            $stats['newPatients'] = $result->fetch_assoc()['total'] ?? 0;
            
            // New Patients Today
            $result = $db->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active' AND DATE(created_at) = '$today'");
            $stats['newPatientsToday'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Doctors
            $result = $db->query("SELECT COUNT(*) as total FROM doctors WHERE status = 'active'");
            $stats['totalDoctors'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Active Doctors
            $result = $db->query("SELECT COUNT(*) as total FROM doctors WHERE status = 'active'");
            $stats['activeDoctors'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Users
            $result = $db->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
            $stats['totalUsers'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Today's Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status != 'canceled'");
            $stats['todayAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Morning Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND session_type = 'morning' AND status != 'canceled'");
            $stats['morningAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Evening Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND session_type = 'evening' AND status != 'canceled'");
            $stats['eveningAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Today's Total Appointments (including canceled)
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today'");
            $stats['todayTotalAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Pending Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status IN ('scheduled', 'confirmed')");
            $stats['pendingAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Completed Today
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status = 'completed'");
            $stats['completedToday'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Today's Revenue
            $result = $db->query("SELECT SUM(amount) as total FROM payments WHERE payment_date = '$today'");
            $stats['todayRevenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Today's Transactions
            $result = $db->query("SELECT COUNT(*) as total FROM payments WHERE payment_date = '$today'");
            $stats['todayTransactions'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Monthly Revenue
            $result = $db->query("SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
            $stats['monthlyRevenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Pharmacy Sales
            $result = $db->query("SELECT SUM(total_amount) as total, SUM(paid_amount) as paid FROM pharmacy_sales WHERE DATE(created_at) = '$today'");
            $sales = $result->fetch_assoc();
            $stats['pharmacySales'] = (float)($sales['total'] ?? 0);
            
            // Pharmacy Items Sold Today
            $result = $db->query("SELECT SUM(quantity) as total FROM pharmacy_sale_items WHERE DATE(created_at) = '$today'");
            $stats['pharmacyItems'] = (int)($result->fetch_assoc()['total'] ?? 0);
            
            // Lab Tests
            $result = $db->query("SELECT COUNT(*) as total FROM lab_test_orders WHERE DATE(created_at) = '$today'");
            $stats['labTests'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Pending Lab Tests
            $result = $db->query("SELECT COUNT(*) as total FROM lab_test_orders WHERE status IN ('ordered', 'sample_collected', 'processing')");
            $stats['pendingTests'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Inventory Items
            $result = $db->query("SELECT COUNT(*) as total FROM inventory_items WHERE status = 'active'");
            $stats['inventoryItems'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Low Stock Items
            $tableCheck = $db->query("SHOW TABLES LIKE 'inventory_stock'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $colCheck = $db->query("SHOW COLUMNS FROM inventory_items LIKE 'reorder_level'");
                if ($colCheck && $colCheck->num_rows > 0) {
                    $result = $db->query("SELECT COUNT(DISTINCT is2.item_id) as total 
                                         FROM inventory_stock is2 
                                         JOIN inventory_items ii ON is2.item_id = ii.id 
                                         WHERE ii.status = 'active' 
                                         AND is2.quantity <= ii.reorder_level 
                                         AND is2.quantity > 0");
                    $stats['lowStock'] = $result->fetch_assoc()['total'] ?? 0;
                } else {
                    $stats['lowStock'] = 0;
                }
            } else {
                $colCheck = $db->query("SHOW COLUMNS FROM inventory_items LIKE 'current_stock'");
                if ($colCheck && $colCheck->num_rows > 0) {
                    $result = $db->query("SELECT COUNT(*) as total FROM inventory_items WHERE status = 'active' AND current_stock <= reorder_level");
                    $stats['lowStock'] = $result->fetch_assoc()['total'] ?? 0;
                } else {
                    $stats['lowStock'] = 0;
                }
            }
            
            // Expiring Soon
            $result = $db->query("SELECT COUNT(*) as total FROM inventory_stock WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND quantity > 0");
            $stats['expiringSoon'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Pending Bills
            $result = $db->query("SELECT COUNT(*) as total FROM bills WHERE payment_status = 'pending'");
            $stats['pendingBills'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Due Amount
            $result = $db->query("SELECT SUM(balance_amount) as total FROM bills WHERE payment_status != 'paid'");
            $stats['totalDue'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Total Prescriptions
            $result = $db->query("SELECT COUNT(*) as total FROM prescriptions WHERE status NOT IN ('draft')");
            $stats['totalPrescriptions'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Prescriptions Today
            $result = $db->query("SELECT COUNT(*) as total FROM prescriptions WHERE DATE(created_at) = '$today' AND status NOT IN ('draft')");
            $stats['prescriptionsToday'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Bills
            $result = $db->query("SELECT COUNT(*) as total FROM bills");
            $stats['totalBills'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Collection Rate
            $result = $db->query("SELECT SUM(total_amount) as total, SUM(paid_amount) as paid FROM bills WHERE payment_status = 'paid'");
            $billsData = $result->fetch_assoc();
            $totalAmount = (float)($billsData['total'] ?? 0);
            $paidAmount = (float)($billsData['paid'] ?? 0);
            $stats['collectionRate'] = $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0;
            
            // Checked In
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status = 'checked_in'");
            $stats['checkedIn'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Queue Status
            $result = $db->query("SELECT COUNT(*) as total FROM queue WHERE status = 'waiting'");
            $stats['queueWaiting'] = $result->fetch_assoc()['total'] ?? 0;
            
            $result = $db->query("SELECT COUNT(*) as total FROM queue WHERE status = 'in_progress'");
            $stats['queueInProgress'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Appointment Status Summary
            $statusData = [];
            $statuses = ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed', 'canceled', 'no_show'];
            foreach ($statuses as $status) {
                $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE status = '$status' AND appointment_date = '$today'");
                $count = $result->fetch_assoc()['total'] ?? 0;
                if ($count > 0) {
                    $displayName = ucfirst(str_replace('_', ' ', $status));
                    $statusData[$displayName] = $count;
                }
            }
            $stats['statusData'] = $statusData;
            
            // Payment Status Summary
            $paymentData = [];
            $paymentStatuses = ['paid', 'pending', 'partial', 'refunded'];
            foreach ($paymentStatuses as $status) {
                $result = $db->query("SELECT COUNT(*) as total FROM bills WHERE payment_status = '$status'");
                $count = $result->fetch_assoc()['total'] ?? 0;
                if ($count > 0) {
                    $displayName = ucfirst($status);
                    $paymentData[$displayName] = $count;
                }
            }
            $stats['paymentData'] = $paymentData;
            
            // Recent Appointments (Last 5)
            $recentAppointments = [];
            $result = $db->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                                  FROM appointments a
                                  JOIN patients p ON a.patient_id = p.id
                                  JOIN doctors d ON a.doctor_id = d.id
                                  JOIN users u ON d.user_id = u.id
                                  ORDER BY a.created_at DESC LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                $recentAppointments[] = $row;
            }
            $stats['recentAppointments'] = $recentAppointments;
            
            // Recent Bills (Last 5)
            $recentBills = [];
            $result = $db->query("SELECT b.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                                  FROM bills b
                                  JOIN patients p ON b.patient_id = p.id
                                  ORDER BY b.created_at DESC LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                $recentBills[] = $row;
            }
            $stats['recentBills'] = $recentBills;
        }
        
        // ============================================================
        // DOCTOR - Doctor Dashboard
        // ============================================================
        elseif ($role == 'doctor') {
            // Get doctor ID from user
            $doctorId = $this->getDoctorId($userId);
            
            // Today's Appointments for this doctor
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctorId AND appointment_date = '$today' AND status != 'canceled'");
            $stats['todayAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Morning Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctorId AND appointment_date = '$today' AND session_type = 'morning' AND status != 'canceled'");
            $stats['morningAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Evening Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctorId AND appointment_date = '$today' AND session_type = 'evening' AND status != 'canceled'");
            $stats['eveningAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Pending Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctorId AND appointment_date = '$today' AND status IN ('scheduled', 'confirmed')");
            $stats['pendingAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Completed Appointments Today
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctorId AND appointment_date = '$today' AND status = 'completed'");
            $stats['completedAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Patients (this doctor's patients)
            $result = $db->query("SELECT COUNT(DISTINCT patient_id) as total FROM appointments WHERE doctor_id = $doctorId");
            $stats['totalPatients'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Prescriptions
            $result = $db->query("SELECT COUNT(*) as total FROM prescriptions WHERE doctor_id = $doctorId AND status NOT IN ('draft')");
            $stats['totalPrescriptions'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Appointment Status Summary
            $statusData = [];
            $statuses = ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed', 'canceled', 'no_show'];
            foreach ($statuses as $status) {
                $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE doctor_id = $doctorId AND status = '$status' AND appointment_date = '$today'");
                $count = $result->fetch_assoc()['total'] ?? 0;
                if ($count > 0) {
                    $displayName = ucfirst(str_replace('_', ' ', $status));
                    $statusData[$displayName] = $count;
                }
            }
            $stats['statusData'] = $statusData;
            
            // Recent Appointments
            $recentAppointments = [];
            $result = $db->query("SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                                  FROM appointments a
                                  JOIN patients p ON a.patient_id = p.id
                                  WHERE a.doctor_id = $doctorId
                                  ORDER BY a.created_at DESC LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                $recentAppointments[] = $row;
            }
            $stats['recentAppointments'] = $recentAppointments;
            
            // Recent Prescriptions
            $recentPrescriptions = [];
            $result = $db->query("SELECT p.*, CONCAT(pa.first_name, ' ', pa.last_name) as patient_name 
                                  FROM prescriptions p
                                  JOIN patients pa ON p.patient_id = pa.id
                                  WHERE p.doctor_id = $doctorId AND p.status NOT IN ('draft')
                                  ORDER BY p.created_at DESC LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                $recentPrescriptions[] = $row;
            }
            $stats['recentPrescriptions'] = $recentPrescriptions;
        }
        
        // ============================================================
        // RECEPTIONIST - Reception Dashboard
        // ============================================================
        elseif ($role == 'receptionist') {
            // Today's Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status != 'canceled'");
            $stats['todayAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Morning Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND session_type = 'morning' AND status != 'canceled'");
            $stats['morningAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Evening Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND session_type = 'evening' AND status != 'canceled'");
            $stats['eveningAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Today's Total Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today'");
            $stats['todayTotalAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Pending Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status IN ('scheduled', 'confirmed')");
            $stats['pendingAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Completed Appointments Today
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status = 'completed'");
            $stats['completedToday'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Patients Registered Today
            $result = $db->query("SELECT COUNT(*) as total FROM patients WHERE DATE(created_at) = '$today'");
            $stats['newPatientsToday'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Patients
            $result = $db->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active'");
            $stats['totalPatients'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Pending Bills
            $result = $db->query("SELECT COUNT(*) as total FROM bills WHERE payment_status = 'pending'");
            $stats['pendingBills'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Due
            $result = $db->query("SELECT SUM(balance_amount) as total FROM bills WHERE payment_status != 'paid'");
            $stats['totalDue'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Appointment Status Summary
            $statusData = [];
            $statuses = ['scheduled', 'confirmed', 'checked_in', 'in_progress', 'completed', 'canceled', 'no_show'];
            foreach ($statuses as $status) {
                $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE status = '$status' AND appointment_date = '$today'");
                $count = $result->fetch_assoc()['total'] ?? 0;
                if ($count > 0) {
                    $displayName = ucfirst(str_replace('_', ' ', $status));
                    $statusData[$displayName] = $count;
                }
            }
            $stats['statusData'] = $statusData;
            
            // Queue Status
            $result = $db->query("SELECT COUNT(*) as total FROM queue WHERE status = 'waiting'");
            $stats['queueWaiting'] = $result->fetch_assoc()['total'] ?? 0;
            
            $result = $db->query("SELECT COUNT(*) as total FROM queue WHERE status = 'in_progress'");
            $stats['queueInProgress'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Recent Appointments
            $recentAppointments = [];
            $result = $db->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                                  FROM appointments a
                                  JOIN patients p ON a.patient_id = p.id
                                  JOIN doctors d ON a.doctor_id = d.id
                                  JOIN users u ON d.user_id = u.id
                                  WHERE a.appointment_date = '$today'
                                  ORDER BY a.created_at DESC LIMIT 10");
            while ($row = $result->fetch_assoc()) {
                $recentAppointments[] = $row;
            }
            $stats['recentAppointments'] = $recentAppointments;
        }
        
        // ============================================================
        // PHARMACIST - Pharmacy Dashboard
        // ============================================================
        elseif ($role == 'pharmacist') {
            // Today's Sales Count
            $result = $db->query("SELECT COUNT(*) as total FROM pharmacy_sales WHERE DATE(created_at) = '$today'");
            $stats['todaySalesCount'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Today's Sales Revenue
            $result = $db->query("SELECT SUM(total_amount) as total FROM pharmacy_sales WHERE DATE(created_at) = '$today'");
            $stats['todaySales'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Today's Revenue (all sources)
            $result = $db->query("SELECT SUM(amount) as total FROM payments WHERE payment_date = '$today'");
            $stats['todayRevenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Pending Prescriptions
            $result = $db->query("SELECT COUNT(*) as total FROM prescriptions WHERE pharmacy_status = 'pending' AND status NOT IN ('draft')");
            $stats['pendingPrescriptions'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Low Stock Items
            $tableCheck = $db->query("SHOW TABLES LIKE 'inventory_stock'");
            if ($tableCheck && $tableCheck->num_rows > 0) {
                $colCheck = $db->query("SHOW COLUMNS FROM inventory_items LIKE 'reorder_level'");
                if ($colCheck && $colCheck->num_rows > 0) {
                    $result = $db->query("SELECT COUNT(DISTINCT is2.item_id) as total 
                                         FROM inventory_stock is2 
                                         JOIN inventory_items ii ON is2.item_id = ii.id 
                                         WHERE ii.status = 'active' 
                                         AND is2.quantity <= ii.reorder_level 
                                         AND is2.quantity > 0");
                    $stats['lowStock'] = $result->fetch_assoc()['total'] ?? 0;
                } else {
                    $stats['lowStock'] = 0;
                }
            } else {
                $stats['lowStock'] = 0;
            }
            
            // Total Medicines
            $result = $db->query("SELECT COUNT(*) as total FROM medicines WHERE status = 'active'");
            $stats['totalMedicines'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Expiring Soon (30 days)
            $result = $db->query("SELECT COUNT(*) as total FROM inventory_stock WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND quantity > 0");
            $stats['expiringSoon'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Dispensed Today
            $result = $db->query("SELECT COUNT(*) as total FROM prescriptions WHERE pharmacy_status = 'dispensed' AND DATE(updated_at) = '$today'");
            $stats['dispensedToday'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Stock Value
            $result = $db->query("SELECT SUM(quantity * selling_price) as total FROM inventory_stock WHERE quantity > 0");
            $stats['stockValue'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Recent Sales
            $recentSales = [];
            $result = $db->query("SELECT s.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                                  FROM pharmacy_sales s
                                  LEFT JOIN patients p ON s.patient_id = p.id
                                  ORDER BY s.created_at DESC LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                $recentSales[] = $row;
            }
            $stats['recentSales'] = $recentSales;
            
            // Prescriptions by Status
            $prescriptionStatus = [];
            $statuses = ['pending', 'processing', 'ready', 'dispensed', 'collected'];
            foreach ($statuses as $status) {
                $result = $db->query("SELECT COUNT(*) as total FROM prescriptions WHERE pharmacy_status = '$status' AND status NOT IN ('draft')");
                $count = $result->fetch_assoc()['total'] ?? 0;
                if ($count > 0) {
                    $displayName = ucfirst($status);
                    $prescriptionStatus[$displayName] = $count;
                }
            }
            $stats['prescriptionStatus'] = $prescriptionStatus;
        }
        
        // ============================================================
        // LAB TECHNICIAN - Lab Dashboard
        // ============================================================
        elseif ($role == 'lab_technician') {
            // Total Orders
            $result = $db->query("SELECT COUNT(*) as total FROM lab_test_orders WHERE DATE(created_at) = '$today'");
            $stats['totalOrders'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Pending Orders
            $result = $db->query("SELECT COUNT(*) as total FROM lab_test_orders WHERE status IN ('ordered', 'sample_collected')");
            $stats['pendingOrders'] = $result->fetch_assoc()['total'] ?? 0;
            
            // In Progress
            $result = $db->query("SELECT COUNT(*) as total FROM lab_test_orders WHERE status = 'processing'");
            $stats['inProgress'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Completed Today
            $result = $db->query("SELECT COUNT(*) as total FROM lab_test_orders WHERE status = 'completed' AND DATE(completed_at) = '$today'");
            $stats['completedToday'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Samples Collected Today
            $result = $db->query("SELECT COUNT(*) as total FROM lab_test_order_items WHERE DATE(sample_collected_at) = '$today' AND status = 'sample_collected'");
            $stats['samplesCollected'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Results Entered Today
            $result = $db->query("SELECT COUNT(*) as total FROM lab_test_order_items WHERE DATE(result_entered_at) = '$today' AND status = 'completed'");
            $stats['resultsEntered'] = $result->fetch_assoc()['total'] ?? 0;
            
            // STAT Orders
            $result = $db->query("SELECT COUNT(*) as total FROM lab_test_orders WHERE priority = 'stat' AND status != 'completed'");
            $stats['statOrders'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Recent Orders
            $recentOrders = [];
            $result = $db->query("SELECT o.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
                                  FROM lab_test_orders o
                                  JOIN patients p ON o.patient_id = p.id
                                  ORDER BY o.created_at DESC LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                $recentOrders[] = $row;
            }
            $stats['recentOrders'] = $recentOrders;
            
            // Orders by Status
            $orderStatus = [];
            $statuses = ['ordered', 'sample_collected', 'processing', 'completed', 'reviewed', 'delivered'];
            foreach ($statuses as $status) {
                $result = $db->query("SELECT COUNT(*) as total FROM lab_test_orders WHERE status = '$status'");
                $count = $result->fetch_assoc()['total'] ?? 0;
                if ($count > 0) {
                    $displayName = ucfirst(str_replace('_', ' ', $status));
                    $orderStatus[$displayName] = $count;
                }
            }
            $stats['orderStatus'] = $orderStatus;
        }
        
        // ============================================================
        // ACCOUNTANT - Accounting Dashboard
        // ============================================================
        elseif ($role == 'accountant') {
            // Today's Revenue
            $result = $db->query("SELECT SUM(amount) as total FROM payments WHERE payment_date = '$today'");
            $stats['todayRevenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Today's Transactions
            $result = $db->query("SELECT COUNT(*) as total FROM payments WHERE payment_date = '$today'");
            $stats['todayTransactions'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Monthly Revenue
            $result = $db->query("SELECT SUM(amount) as total FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())");
            $stats['monthlyRevenue'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Pending Bills
            $result = $db->query("SELECT COUNT(*) as total FROM bills WHERE payment_status = 'pending'");
            $stats['pendingBills'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Due Amount
            $result = $db->query("SELECT SUM(balance_amount) as total FROM bills WHERE payment_status != 'paid'");
            $stats['totalDue'] = (float)($result->fetch_assoc()['total'] ?? 0);
            
            // Total Bills
            $result = $db->query("SELECT COUNT(*) as total FROM bills");
            $stats['totalBills'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Today's Bills
            $result = $db->query("SELECT COUNT(*) as total FROM bills WHERE bill_date = '$today'");
            $stats['todayBills'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Monthly Bills
            $result = $db->query("SELECT COUNT(*) as total FROM bills WHERE MONTH(bill_date) = MONTH(CURDATE()) AND YEAR(bill_date) = YEAR(CURDATE())");
            $stats['monthlyBills'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Payment Status Summary
            $paymentData = [];
            $paymentStatuses = ['paid', 'pending', 'partial', 'refunded'];
            foreach ($paymentStatuses as $status) {
                $result = $db->query("SELECT COUNT(*) as total FROM bills WHERE payment_status = '$status'");
                $count = $result->fetch_assoc()['total'] ?? 0;
                if ($count > 0) {
                    $displayName = ucfirst($status);
                    $paymentData[$displayName] = $count;
                }
            }
            $stats['paymentData'] = $paymentData;
            
            // Recent Transactions
            $recentTransactions = [];
            $result = $db->query("SELECT p.*, CONCAT(pa.first_name, ' ', pa.last_name) as patient_name 
                                  FROM payments p
                                  JOIN patients pa ON p.patient_id = pa.id
                                  ORDER BY p.created_at DESC LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                $recentTransactions[] = $row;
            }
            $stats['recentTransactions'] = $recentTransactions;
        }
        
        // ============================================================
        // NURSE - Nursing Dashboard
        // ============================================================
        elseif ($role == 'nurse') {
            // Today's Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status != 'canceled'");
            $stats['todayAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Morning Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND session_type = 'morning' AND status != 'canceled'");
            $stats['morningAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Evening Appointments
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND session_type = 'evening' AND status != 'canceled'");
            $stats['eveningAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Checked In Patients
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status = 'checked_in'");
            $stats['checkedIn'] = $result->fetch_assoc()['total'] ?? 0;
            
            // In Progress
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status = 'in_progress'");
            $stats['inProgress'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Completed Today
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today' AND status = 'completed'");
            $stats['completedToday'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Total Patients
            $result = $db->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active'");
            $stats['totalPatients'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Vaccines Given Today
            $result = $db->query("SELECT COUNT(*) as total FROM patient_vaccinations WHERE DATE(date_given) = '$today'");
            $stats['vaccinesToday'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Queue Status
            $result = $db->query("SELECT COUNT(*) as total FROM queue WHERE status = 'waiting'");
            $stats['queueWaiting'] = $result->fetch_assoc()['total'] ?? 0;
            
            $result = $db->query("SELECT COUNT(*) as total FROM queue WHERE status = 'in_progress'");
            $stats['queueInProgress'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Recent Appointments
            $recentAppointments = [];
            $result = $db->query("SELECT a.*, 
                                  CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                  CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                                  FROM appointments a
                                  JOIN patients p ON a.patient_id = p.id
                                  JOIN doctors d ON a.doctor_id = d.id
                                  JOIN users u ON d.user_id = u.id
                                  WHERE a.appointment_date = '$today'
                                  ORDER BY a.created_at DESC LIMIT 5");
            while ($row = $result->fetch_assoc()) {
                $recentAppointments[] = $row;
            }
            $stats['recentAppointments'] = $recentAppointments;
        }
        
        // ============================================================
        // GUEST/DEFAULT - Minimal Dashboard
        // ============================================================
        else {
            // Default statistics for guest users
            $result = $db->query("SELECT COUNT(*) as total FROM patients WHERE status = 'active'");
            $stats['totalPatients'] = $result->fetch_assoc()['total'] ?? 0;
            
            $result = $db->query("SELECT COUNT(*) as total FROM doctors WHERE status = 'active'");
            $stats['totalDoctors'] = $result->fetch_assoc()['total'] ?? 0;
            
            $result = $db->query("SELECT COUNT(*) as total FROM appointments WHERE appointment_date = '$today'");
            $stats['todayAppointments'] = $result->fetch_assoc()['total'] ?? 0;
            
            // Set default values for other stats
            $stats['totalUsers'] = 0;
            $stats['todayRevenue'] = 0;
            $stats['monthlyRevenue'] = 0;
            $stats['pharmacySales'] = 0;
            $stats['pharmacyItems'] = 0;
            $stats['labTests'] = 0;
            $stats['pendingTests'] = 0;
            $stats['inventoryItems'] = 0;
            $stats['lowStock'] = 0;
            $stats['pendingBills'] = 0;
            $stats['totalDue'] = 0;
            $stats['totalPrescriptions'] = 0;
            $stats['totalBills'] = 0;
            $stats['recentAppointments'] = [];
            $stats['recentBills'] = [];
            $stats['statusData'] = [];
            $stats['paymentData'] = [];
        }
        
        return $stats;
    }
    
    /**
     * Get Doctor ID from User ID
     */
    private function getDoctorId($userId) {
        $db = $this->db;
        $result = $db->query("SELECT id FROM doctors WHERE user_id = $userId AND status = 'active'");
        if ($result && $result->num_rows > 0) {
            return (int)$result->fetch_assoc()['id'];
        }
        return 0;
    }
}
?>