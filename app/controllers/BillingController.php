<?php
// app/controllers/BillingController.php
// COMPLETE FIXED VERSION WITH PROPER DISCOUNT HANDLING AND PAYMENT STATUS UPDATE

// FIX: Controller.php is in core folder
require_once BASE_PATH . '/core/Controller.php';

class BillingController extends Controller {
    
    public function __construct() {
        parent::__construct();
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }
    
    // ==================== BILLS LIST - WITH PROPER BALANCE ====================
    public function bills() {
        $this->checkAuth();
        $this->checkPermission('view_bills');
        
        $db = $this->db;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $billType = isset($_GET['bill_type']) ? $_GET['bill_type'] : '';
        $search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
        
        $where = "WHERE 1=1";
        if (!empty($dateFrom)) $where .= " AND DATE(b.bill_date) >= '$dateFrom'";
        if (!empty($dateTo)) $where .= " AND DATE(b.bill_date) <= '$dateTo'";
        if (!empty($status)) $where .= " AND b.payment_status = '$status'";
        if (!empty($billType)) $where .= " AND b.bill_type = '$billType'";
        
        if (!empty($search)) {
            $where .= " AND (b.bill_number LIKE '%$search%' 
                             OR CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search%'
                             OR p.patient_code LIKE '%$search%')";
        }
        
        $countQuery = "SELECT COUNT(*) as total FROM bills b JOIN patients p ON b.patient_id = p.id $where";
        $countResult = $db->query($countQuery);
        $totalBills = $countResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($totalBills / $limit);
        
        // FIXED: balance = total_amount - paid_amount (discount already in total_amount)
        $query = "SELECT b.*, 
                         p.first_name, p.last_name, p.patient_code, p.phone,
                         COALESCE((SELECT COUNT(*) FROM bill_items WHERE bill_id = b.id), 0) as item_count,
                         COALESCE((SELECT description FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1), 'Consultation') as service_name,
                         COALESCE((SELECT item_type FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1), b.bill_type) as service_type,
                         COALESCE((SELECT unit_price FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1), b.total_amount) as service_price,
                         COALESCE((SELECT total_amount FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1), b.total_amount) as service_total,
                         ROUND(COALESCE(b.total_amount, 0) - COALESCE(b.paid_amount, 0) - COALESCE(b.discount_amount, 0), 2) as balance_amount
                  FROM bills b
                  JOIN patients p ON b.patient_id = p.id
                  $where
                  ORDER BY b.bill_date DESC, b.id DESC
                  LIMIT $limit OFFSET $offset";
        
        $result = $db->query($query);
        $billList = [];
        $totalAmount = 0;
        $totalPaid = 0;
        $totalDue = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $balance = (float)$row['balance_amount'];
                if ($balance < 0.01) $balance = 0;
                $row['balance_amount'] = $balance;
                
                // Determine payment status based on balance
                if ($balance == 0) {
                    $row['payment_status'] = 'paid';
                } elseif ((float)$row['paid_amount'] > 0 && $balance > 0) {
                    $row['payment_status'] = 'partial';
                } else {
                    $row['payment_status'] = 'pending';
                }
                
                if (!isset($row['discount_amount']) || $row['discount_amount'] === null) {
                    $row['discount_amount'] = 0;
                }
                
                $totalAmount += (float)$row['total_amount'];
                $totalPaid += (float)$row['paid_amount'];
                $totalDue += (float)$row['balance_amount'];
                
                $billList[] = $row;
            }
        }
        
        $this->view('bills/bills', [
            'bills' => $billList,
            'totalBills' => $totalBills,
            'totalAmount' => $totalAmount,
            'totalPaid' => $totalPaid,
            'totalDue' => $totalDue,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statusFilter' => $status,
            'billTypeFilter' => $billType,
            'search' => $search,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'offset' => $offset
        ], 'Bills');
    }
    
    // ==================== PROCESS PAYMENT - FIXED ====================
    public function processPayment() {
        header('Content-Type: application/json');
        try {
            $billId = (int)$_POST['bill_id'];
            $patientId = (int)$_POST['patient_id'];
            $receivedAmount = (float)$_POST['amount'];
            $paymentMethod = $this->db->real_escape_string($_POST['payment_method']);
            $transactionId = $this->db->real_escape_string($_POST['transaction_id'] ?? '');
            $notes = $this->db->real_escape_string($_POST['notes'] ?? '');
            $userId = $_SESSION['user_id'];
            
            $discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
            $discountAmount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0;
            $fullDiscount = isset($_POST['full_discount']) ? (int)$_POST['full_discount'] : 0;
            
            // Fetch current bill
            $billQuery = "SELECT * FROM bills WHERE id = $billId";
            $billResult = $this->db->query($billQuery);
            if (!$billResult || $billResult->num_rows == 0) {
                echo json_encode(['success' => false, 'message' => 'Bill not found']);
                exit;
            }
            $bill = $billResult->fetch_assoc();
            
            $currentTotal = round((float)$bill['total_amount'], 2);
            $currentPaid = round((float)$bill['paid_amount'], 2);
            $currentDiscount = round((float)$bill['discount_amount'], 2);
            
            // CORRECT: Current balance = total - paid - discount
            $currentBalance = round($currentTotal - $currentPaid - $currentDiscount, 2);
            if ($currentBalance < 0.01) $currentBalance = 0;
            
            $appliedDiscount = 0;
            
            if ($fullDiscount == 1) {
                $appliedDiscount = $currentBalance;
                $receivedAmount = 0;
            } elseif ($discountAmount > 0) {
                $appliedDiscount = min($discountAmount, $currentBalance);
            } elseif ($discountPercent > 0) {
                $appliedDiscount = min(round($currentBalance * ($discountPercent / 100), 2), $currentBalance);
            }
            
            $appliedDiscount = round($appliedDiscount, 2);
            
            $newPaid = round($currentPaid + $receivedAmount, 2);
            $newDiscount = round($currentDiscount + $appliedDiscount, 2);
            
            // CORRECT: New balance = total - new_paid - new_discount
            $newBalance = round($currentTotal - $newPaid - $newDiscount, 2);
            if ($newBalance < 0.01) $newBalance = 0;
            
            $paymentStatus = ($newBalance == 0) ? 'paid' : 'partial';
            $paymentNumber = 'PAY' . date('Ymd') . rand(1000, 9999);
            
            $this->db->begin_transaction();
            
            // FIXED: Insert payment - NO payment_status column (doesn't exist in payments table)
            if ($receivedAmount > 0 || $appliedDiscount > 0) {
                $insertPayment = "INSERT INTO payments (payment_number, bill_id, patient_id, amount, payment_method, 
                              transaction_id, notes, payment_date, received_by, created_at) 
                              VALUES ('$paymentNumber', $billId, $patientId, $receivedAmount, '$paymentMethod', 
                              '$transactionId', '$notes', CURDATE(), $userId, NOW())";
                if (!$this->db->query($insertPayment)) {
                    $this->db->rollback();
                    echo json_encode(['success' => false, 'message' => 'Failed to insert payment: ' . $this->db->error]);
                    exit;
                }
            }
            
            // CORRECT: Update bill - balance = total - paid - discount
            $updateBill = "UPDATE bills SET 
                          paid_amount = $newPaid,
                          discount_amount = $newDiscount,
                          balance_amount = $newBalance,
                          payment_status = '$paymentStatus',
                          updated_at = NOW()
                          WHERE id = $billId";
            if (!$this->db->query($updateBill)) {
                $this->db->rollback();
                echo json_encode(['success' => false, 'message' => 'Failed to update bill: ' . $this->db->error]);
                exit;
            }
            
            // Update reference appointment if exists
            if (!empty($bill['reference_type']) && $bill['reference_type'] == 'appointment' && !empty($bill['reference_id'])) {
                $appointmentId = (int)$bill['reference_id'];
                $appPaymentStatus = ($newBalance == 0) ? 'paid' : 'partial';
                $this->db->query("UPDATE appointments SET 
                                  payment_status = '$appPaymentStatus', 
                                  payment_received = $newPaid,
                                  last_payment_date = NOW()
                                  WHERE id = $appointmentId");
            }

            // ================================================================
            // SYNC APPOINTMENT PAYMENT STATUS
            // ================================================================
            if (!empty($bill['reference_type']) && $bill['reference_type'] == 'appointment' && !empty($bill['reference_id'])) {
                $appointmentId = (int)$bill['reference_id'];
                $appPaymentStatus = ($newBalance == 0) ? 'paid' : 'partial';
                $this->db->query("UPDATE appointments SET 
                                  payment_status = '$appPaymentStatus', 
                                  payment_received = $newPaid,
                                  discount = $newDiscount,
                                  total_amount = $currentTotal,
                                  last_payment_date = NOW()
                                  WHERE id = $appointmentId");
            }
            
            if (!empty($bill['reference_type']) && $bill['reference_type'] == 'pharmacy_sale' && !empty($bill['reference_id'])) {
                    $saleId = (int)$bill['reference_id'];
                    $saleStatus = 'completed';
                    $this->db->query("UPDATE pharmacy_sales SET 
                                      status = '$saleStatus',
                                      paid_amount = $newPaid
                                      WHERE id = $saleId");
                }
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Payment: ৳ ' . number_format($receivedAmount, 2) . ' | Discount: ৳ ' . number_format($appliedDiscount, 2),
                'bill_id' => $billId,
                'current_total' => $currentTotal,
                'old_paid' => $currentPaid,
                'old_discount' => $currentDiscount,
                'old_balance' => $currentBalance,
                'new_paid' => $newPaid,
                'new_discount' => $newDiscount,
                'new_balance' => $newBalance,
                'payment_status' => $paymentStatus
            ]);
            
        } catch (Exception $e) {
            if ($this->db->connect_errno == 0) {
                $this->db->rollback();
            }
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // ==================== VIEW INVOICE ====================
    public function viewInvoice($id) {
        $this->checkAuth();
        
        $id = (int)$id;
        
        $billQuery = "SELECT b.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code, 
                         p.phone, 
                         p.address,
                         p.email,
                         p.gender,
                         p.date_of_birth
                  FROM bills b
                  LEFT JOIN patients p ON b.patient_id = p.id
                  WHERE b.id = $id";
        
        $billResult = $this->db->query($billQuery);
        
        if(!$billResult || $billResult->num_rows == 0) {
            $_SESSION['error'] = "Bill not found";
            $this->redirect('/bills');
            return;
        }
        
        $bill = $billResult->fetch_assoc();
        
        // FIXED: balance = total_amount - paid_amount (discount already in total)
        $bill['balance_amount'] = round((float)$bill['total_amount'] - (float)$bill['paid_amount'] - (float)($bill['discount_amount'] ?? 0), 2);
        if ($bill['balance_amount'] < 0.01) {
            $bill['balance_amount'] = 0;
            $bill['payment_status'] = 'paid';
        } elseif ((float)$bill['paid_amount'] > 0 && $bill['balance_amount'] > 0) {
            $bill['payment_status'] = 'partial';
        } else {
            $bill['payment_status'] = 'pending';
        }
        
        // Get referred_by name if exists
        $referredName = 'N/A';
        if (isset($bill['referred_by']) && $bill['referred_by'] > 0) {
            $referredQuery = $this->db->query("SELECT name FROM referred_by WHERE id = " . (int)$bill['referred_by']);
            if ($referredQuery && $referredQuery->num_rows > 0) {
                $referredRow = $referredQuery->fetch_assoc();
                $referredName = $referredRow['name'];
            }
        }
        $bill['referred_by_name'] = $referredName;
        
        $itemsQuery = "SELECT bi.*, 
                          CASE 
                              WHEN bi.item_type = 'medicine' THEN 'Medicine'
                              WHEN bi.item_type = 'lab_test' THEN 'Lab Test'
                              WHEN bi.item_type = 'consultation' THEN 'Consultation'
                              WHEN bi.item_type = 'service' THEN 'Service'
                              WHEN bi.item_type = 'procedure' THEN 'Procedure'
                              ELSE 'Other'
                          END as item_type_display,
                          bi.description as item_name
                   FROM bill_items bi
                   WHERE bi.bill_id = $id
                   ORDER BY bi.id ASC";
        
        $itemsResult = $this->db->query($itemsQuery);
        $items = [];
        
        if($itemsResult) {
            while($row = $itemsResult->fetch_assoc()) {
                $row['turnaround_time'] = null;
                if (isset($row['item_type']) && ($row['item_type'] === 'lab_test' || $row['item_type'] === 'lab')) {
                    $labTestId = isset($row['item_id']) ? (int)$row['item_id'] : 0;
                    if ($labTestId > 0) {
                        $labQuery = $this->db->query("SELECT turnaround_time FROM lab_tests WHERE id = $labTestId");
                        if ($labQuery && $labQuery->num_rows > 0) {
                            $labData = $labQuery->fetch_assoc();
                            $row['turnaround_time'] = $labData['turnaround_time'] ?? null;
                        }
                    }
                }
                
                if(empty($row['description'])) {
                    $row['description'] = $row['item_type_display'] . ' Item';
                }
                $items[] = $row;
            }
        }
        
        $paymentsQuery = "SELECT p.*, u.first_name as received_by_name 
                          FROM payments p
                          LEFT JOIN users u ON p.received_by = u.id
                          WHERE p.bill_id = $id 
                          ORDER BY p.payment_date DESC";
        
        $paymentsResult = $this->db->query($paymentsQuery);
        $payments = [];
        if($paymentsResult) {
            while($row = $paymentsResult->fetch_assoc()) {
                $payments[] = $row;
            }
        }
        
        $viewPath = 'bills/invoice';
        if(!file_exists(dirname(__DIR__) . '/views/' . $viewPath . '.php')) {
            $viewPath = 'billing/invoice';
        }
        
        $content = $this->renderView($viewPath, [
            'bill' => $bill,
            'items' => $items,
            'payments' => $payments,
            'referred_name' => $referredName
        ]);
        $this->renderLayout('Invoice', $content);
    }
    
    // ==================== PRINT INVOICE ====================
public function printInvoice($id) {
    $this->checkAuth();
    
    // Set timezone to Dhaka (GMT+6)
    date_default_timezone_set('Asia/Dhaka');
    
    $id = (int)$id;
    $db = $this->db;
    
    // Get bill with patient details
    $billQuery = "SELECT b.*, 
                         p.first_name, p.last_name, p.patient_code, 
                         p.phone, p.address, p.gender, p.date_of_birth, 
                         p.id as patient_id
                  FROM bills b
                  JOIN patients p ON b.patient_id = p.id
                  WHERE b.id = $id";
    $billResult = $db->query($billQuery);
    if (!$billResult || $billResult->num_rows == 0) {
        echo "Bill not found";
        exit;
    }
    $bill = $billResult->fetch_assoc();
    
    // Get doctor name
    $doctorName = 'N/A';
    if (!empty($bill['reference_type']) && !empty($bill['reference_id'])) {
        if ($bill['reference_type'] == 'appointment') {
            $docQuery = "SELECT CONCAT(u.first_name, ' ', u.last_name) as name 
                         FROM appointments a
                         JOIN doctors d ON a.doctor_id = d.id
                         JOIN users u ON d.user_id = u.id
                         WHERE a.id = " . (int)$bill['reference_id'];
            $docResult = $db->query($docQuery);
            if ($docResult && $docResult->num_rows > 0) {
                $doctorName = $docResult->fetch_assoc()['name'];
            }
        } elseif ($bill['reference_type'] == 'lab_order') {
            $docQuery = "SELECT CONCAT(u.first_name, ' ', u.last_name) as name 
                         FROM lab_test_orders o
                         JOIN doctors d ON o.doctor_id = d.id
                         JOIN users u ON d.user_id = u.id
                         WHERE o.id = " . (int)$bill['reference_id'];
            $docResult = $db->query($docQuery);
            if ($docResult && $docResult->num_rows > 0) {
                $doctorName = $docResult->fetch_assoc()['name'];
            }
        }
    }
    $bill['doctor_name'] = $doctorName;
    $bill['patient_name'] = $bill['first_name'] . ' ' . $bill['last_name'];
    
    // Get referred_by name
    $referredByName = 'N/A';
    if (isset($bill['referred_by']) && $bill['referred_by'] > 0) {
        $referredQuery = $db->query("SELECT name FROM referred_by WHERE id = " . (int)$bill['referred_by']);
        if ($referredQuery && $referredQuery->num_rows > 0) {
            $referredRow = $referredQuery->fetch_assoc();
            $referredByName = $referredRow['name'];
        }
    }
    $bill['referred_by_name'] = $referredByName;
    
    // Get bill items
    $itemsResult = $db->query("SELECT bi.*, 
                               CASE 
                                   WHEN bi.item_type = 'consultation' THEN 'Consultation'
                                   WHEN bi.item_type = 'medicine' THEN 'Medicine'
                                   WHEN bi.item_type = 'lab_test' THEN 'Lab Test'
                                   WHEN bi.item_type = 'procedure' THEN 'Procedure'
                                   WHEN bi.item_type = 'service' THEN 'Service'
                                   WHEN bi.item_type = 'lab_accessory' THEN 'Lab Accessory'
                                   ELSE 'Other'
                               END as item_type_display
                               FROM bill_items bi
                               WHERE bi.bill_id = $id
                               ORDER BY bi.id ASC");
    $items = [];
    if ($itemsResult) {
        while ($row = $itemsResult->fetch_assoc()) {
            // Get turnaround time for lab tests
            $row['turnaround_time'] = null;
            if (isset($row['item_type']) && ($row['item_type'] === 'lab_test' || $row['item_type'] === 'lab')) {
                $labTestId = isset($row['item_id']) ? (int)$row['item_id'] : 0;
                if ($labTestId > 0) {
                    $labQuery = $db->query("SELECT turnaround_time FROM lab_tests WHERE id = $labTestId");
                    if ($labQuery && $labQuery->num_rows > 0) {
                        $labData = $labQuery->fetch_assoc();
                        $row['turnaround_time'] = $labData['turnaround_time'] ?? null;
                    }
                }
            }
            $items[] = $row;
        }
    }
    
    // Separate main items and accessories
    $mainItems = [];
    $accessories = [];
    $hasLabTest = false;
    foreach ($items as $item) {
        if (isset($item['item_type']) && $item['item_type'] === 'lab_accessory') {
            $accessories[] = $item;
        } else {
            $mainItems[] = $item;
        }
        if (isset($item['item_type']) && ($item['item_type'] === 'lab_test' || $item['item_type'] === 'lab')) {
            $hasLabTest = true;
        }
    }
    
    // --- Compute values ---
    $grossAmount = isset($bill['subtotal']) ? (float)$bill['subtotal'] : 0;
    $totalDiscount = isset($bill['discount_amount']) ? (float)$bill['discount_amount'] : 0;
    $discountPercent = isset($bill['discount_percentage']) ? (float)$bill['discount_percentage'] : 0;
    
    // Calculate discount percentage based on gross amount
    if ($grossAmount > 0 && $totalDiscount > 0) {
        $discountPercent = round(($totalDiscount / $grossAmount) * 100, 2);
    }
    
    // Net Amount Raw = Gross - Discount
    $netAmountRaw = $grossAmount - $totalDiscount;
    if ($netAmountRaw < 0) $netAmountRaw = 0;
    
    // Round off to nearest whole number
    $netAmount = round($netAmountRaw);
    $roundOff = $netAmountRaw - $netAmount;
    
    // Amt Received from bill (Paid Amount)
    $amtReceived = isset($bill['paid_amount']) ? (float)$bill['paid_amount'] : 0;
    
    // If amtReceived is 0 but balance is 0, it means fully paid
    if ($amtReceived == 0 && isset($bill['balance_amount']) && $bill['balance_amount'] == 0) {
        $amtReceived = $netAmount;
    }
    
    // If bill is fully paid, update amount received
    if (isset($bill['payment_status']) && $bill['payment_status'] == 'paid') {
        $amtReceived = $netAmount;
    }
    
    // Determine payment status - ONLY "Paid" or "Unpaid"
    $dueAmount = $netAmount - $amtReceived;
    if ($dueAmount < 0) $dueAmount = 0;
    
    if ($dueAmount == 0 && $amtReceived > 0) {
        $paymentStatus = 'paid';
    } else {
        $paymentStatus = 'unpaid';
    }
    
    $inWords = $this->numberToWords($netAmount);
    
    $age = '';
    $dob = $bill['date_of_birth'] ?? '';
    if (!empty($dob) && $dob != '0000-00-00') {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        $ageDiff = $birthDate->diff($today);
        $age = $ageDiff->y . 'Y';
    }
    
    // Format Bill Date & Time for Prepared By section
    $billDateTime = isset($bill['created_at']) ? date('d-M-Y h:i A', strtotime($bill['created_at'])) : date('d-M-Y h:i A');
    
    // Fetch payment history for this bill
    $paymentHistory = [];
    if (isset($bill['id']) && $bill['id'] > 0) {
        $paymentQuery = $db->query("
            SELECT p.*, u.first_name, u.last_name 
            FROM payments p 
            LEFT JOIN users u ON p.received_by = u.id 
            WHERE p.bill_id = " . (int)$bill['id'] . " 
            ORDER BY p.created_at DESC
        ");
        if ($paymentQuery && $paymentQuery->num_rows > 0) {
            while ($row = $paymentQuery->fetch_assoc()) {
                $paymentHistory[] = $row;
            }
        }
    }
    
    // Get current user name for Prepared By
    $currentUserName = isset($_SESSION['user_name']) ? ucwords(strtolower($_SESSION['user_name'])) : 'System Admin';
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invoice - <?php echo htmlspecialchars($bill['bill_number']); ?></title>
        <style>
            /* ================================================================ */
            /* A5 PORTRAIT - 148mm × 210mm - PURE BLACK, CAMBRIA FONT         */
            /* NO WATERMARK, NO HEADER, NO FOOTER                             */
            /* Top margin: 1in, Bottom margin: 0.9in                          */
            /* ================================================================ */
            * { margin: 0; padding: 0; box-sizing: border-box; }
            
            body { 
                font-family: 'Cambria', 'Times New Roman', serif;
                background: #ffffff;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                padding: 20px;
                color: #000000;
            }
            
            .invoice-wrapper {
                width: 148mm;
                height: 210mm;
                background: #ffffff;
                box-shadow: 0 4px 20px rgba(0,0,0,0.15);
                border-radius: 4px;
                overflow: hidden;
                position: relative;
                padding: 1in 5mm 0.9in 5mm;
                display: flex;
                flex-direction: column;
                color: #000000;
            }
            
            .invoice-container {
                padding: 0;
                background: #ffffff;
                position: relative;
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                flex: 1;
                color: #000000;
            }
            
            .invoice-wrapper * {
                font-family: 'Cambria', 'Times New Roman', serif;
                color: #000000;
                background-color: transparent;
            }
            
            /* WATERMARK - COMPLETELY REMOVED */
            .watermark { display: none; }
            
            /* HEADER - COMPLETELY REMOVED */
            .invoice-header { display: none; }
            .header-gap { display: none; }
            
            /* PRINT TIME - COMPLETELY REMOVED */
            .print-time-bar { display: none; }
            
            /* PATIENT INFO */
            .patient-info-section {
                padding: 3px 0;
                border-bottom: 1px solid #e0e0e0;
                font-size: 7.5pt;
                position: relative;
                z-index: 1;
                display: flex;
                flex-wrap: wrap;
                margin: 2px 0 3px 0;
                flex-shrink: 0;
                color: #000000;
            }
            .patient-info-section .info-col {
                display: flex;
                flex-direction: column;
                width: 50%;
                padding-right: 4px;
            }
            .patient-info-section .info-col-right {
                display: flex;
                flex-direction: column;
                width: 50%;
                padding-left: 4px;
            }
            .patient-info-section .info-col .field,
            .patient-info-section .info-col-right .field {
                display: block;
                width: 100%;
                margin-right: 0;
                margin-bottom: 1px;
            }
            .patient-info-section .info-col .field .label,
            .patient-info-section .info-col-right .field .label {
                color: #000000;
                font-weight: 700;
                margin-right: 2px;
                font-size: 7.5pt;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .patient-info-section .info-col .field .value,
            .patient-info-section .info-col-right .field .value {
                color: #000000;
                font-weight: 400;
                font-size: 7.5pt;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .patient-info-section .info-col .field .value-strong,
            .patient-info-section .info-col-right .field .value-strong {
                color: #000000;
                font-weight: 700;
                font-size: 7.5pt;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .patient-info-section .info-col .field .value-phone,
            .patient-info-section .info-col-right .field .value-phone {
                color: #000000;
                font-weight: 600;
                font-size: 7.5pt;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            
            /* ITEMS TABLE - Deliv. column before Particulars */
            .items-table-wrapper {
                padding: 2px 0;
                position: relative;
                z-index: 1;
                margin: 1px 0 2px 0;
                overflow: hidden;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 7.5pt;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .items-table thead th {
                background: #ffffff;
                color: #000000;
                font-weight: 700;
                font-size: 7.5pt;
                letter-spacing: 0.2px;
                padding: 2px 3px;
                border-bottom: 1.5px solid #000000;
                text-align: left;
                font-family: 'Cambria', 'Times New Roman', serif;
                text-transform: none;
            }
            .items-table thead th.text-end { text-align: right; }
            .items-table thead th.text-center { text-align: center; }
            .items-table tbody td {
                padding: 2px 3px;
                border-bottom: 1px solid #f0f0f0;
                vertical-align: middle;
                font-size: 7.5pt;
                font-weight: 400;
                font-family: 'Cambria', 'Times New Roman', serif;
                color: #000000;
                background: #ffffff;
            }
            .items-table tbody td.text-end { text-align: right; }
            .items-table tbody td.text-center { text-align: center; }
            .items-table tbody tr:last-child td { border-bottom: none; }
            
            /* ACCESSORIES SECTION */
            .accessories-section {
                margin-top: 3px;
                padding-top: 3px;
                border-top: 1px dashed #ccc;
                font-size: 7pt;
                flex-shrink: 0;
                color: #000000;
                background: #ffffff;
            }
            .accessories-section .acc-title {
                font-weight: 700;
                color: #000000;
                font-size: 7.5pt;
                margin-bottom: 2px;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .accessories-section .acc-item {
                display: inline-block;
                background: #ffffff;
                padding: 1px 6px;
                border-radius: 0;
                margin: 1px 3px 1px 0;
                font-size: 6.5pt;
                color: #000000;
                font-family: 'Cambria', 'Times New Roman', serif;
                border: 1px solid #e0e0e0;
            }
            .accessories-section .acc-item .acc-name { font-weight: 500; }
            .accessories-section .acc-item .acc-qty { color: #000000; margin-left: 3px; }
            
            /* SUMMARY - Added Due row */
            .summary-section {
                padding: 3px 0;
                position: relative;
                z-index: 1;
                display: flex;
                justify-content: flex-end;
                border-top: 1px solid #e0e0e0;
                margin: 2px 0 2px 0;
                flex-shrink: 0;
                color: #000000;
                background: #ffffff;
            }
            .summary-section .totals {
                flex-shrink: 0;
                min-width: 160px;
                font-size: 7.5pt;
                font-family: 'Cambria', 'Times New Roman', serif;
                width: 100%;
                max-width: 240px;
                color: #000000;
            }
            .summary-section .summary-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: nowrap;
                white-space: nowrap;
                padding: 1.5px 0;
                border-bottom: 1px solid #f0f0f0;
                line-height: 1.2;
                color: #000000;
            }
            .summary-section .summary-row:last-child { border-bottom: none; }
            .summary-section .summary-row .summary-label {
                color: #000000;
                font-size: 7.5pt;
                font-weight: 400;
                flex-shrink: 0;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .summary-section .summary-row .summary-value {
                font-weight: 500;
                font-size: 7.5pt;
                text-align: right;
                flex-shrink: 0;
                margin-left: 15px;
                color: #000000;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .summary-section .summary-row.discount-row .summary-value { color: #000000; }
            .summary-section .summary-row.net-payable {
                font-weight: 700;
                font-size: 8.5pt;
                padding-top: 2px;
                border-top: 1.5px solid #000000;
                border-bottom: 1.5px solid #000000;
                margin-top: 1px;
            }
            .summary-section .summary-row.net-payable .summary-label {
                color: #000000;
                font-weight: 700;
                font-size: 8.5pt;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .summary-section .summary-row.net-payable .summary-value {
                color: #000000;
                font-weight: 700;
                font-size: 8.5pt;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            
            /* PAYMENT STATUS */
            .payment-status-section {
                padding: 2px 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-top: 1px solid #e0e0e0;
                position: relative;
                z-index: 1;
                margin: 2px 0 2px 0;
                flex-shrink: 0;
                min-height: 18px;
                color: #000000;
                background: #ffffff;
            }
            .payment-status-section .status {
                font-size: 14pt;
                font-weight: 900;
                letter-spacing: 2px;
                color: #000000;
                text-transform: uppercase;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .payment-status-section .amount-word {
                font-size: 7pt;
                color: #000000;
                text-align: right;
                line-height: 1.2;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .payment-status-section .amount-word strong { color: #000000; font-weight: 700; }
            
            /* SIGNATURE & LAB NOTE - Same Line */
            .signature-lab-section {
                padding: 2px 0;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-top: 1px solid #e0e0e0;
                margin: 2px 0 2px 0;
                flex-shrink: 0;
                color: #000000;
                background: #ffffff;
                min-height: 16px;
            }
            .signature-lab-section .lab-note-left {
                font-size: 7.5pt;
                font-weight: 600;
                color: #000000;
                font-family: 'Cambria', 'Times New Roman', serif;
                letter-spacing: 0.3px;
                text-align: left;
                flex: 1;
            }
            .signature-lab-section .signature-right {
                text-align: right;
                line-height: 1.3;
                flex-shrink: 0;
                padding-left: 10px;
            }
            .signature-lab-section .signature-right .prepared-by {
                font-size: 7pt;
                color: #000000;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .signature-lab-section .signature-right .prepared-by strong {
                font-weight: 700;
            }
            .signature-lab-section .signature-right .billing-date {
                font-size: 7pt;
                color: #000000;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            
            /* PAYMENT HISTORY TABLE - Compact, Small Font, 50% Width, Left Aligned */
            .payment-history-section {
                padding: 2px 0;
                border-top: 1px solid #e0e0e0;
                margin: 2px 0 2px 0;
                flex-shrink: 0;
                color: #000000;
                background: #ffffff;
                width: 50%;
            }
            .payment-history-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 5.5pt;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .payment-history-table thead th {
                background: #ffffff;
                color: #000000;
                font-weight: 700;
                font-size: 5.5pt;
                padding: 1px 3px;
                border-bottom: 1px solid #000000;
                text-align: left;
                font-family: 'Cambria', 'Times New Roman', serif;
            }
            .payment-history-table tbody td {
                padding: 1px 3px;
                border-bottom: 1px solid #f0f0f0;
                vertical-align: middle;
                font-size: 5.5pt;
                font-weight: 400;
                font-family: 'Cambria', 'Times New Roman', serif;
                color: #000000;
                background: #ffffff;
                text-align: left;
            }
            .payment-history-table tbody tr:last-child td { border-bottom: none; }
            .no-payments {
                text-align: center;
                padding: 2px 0;
                font-size: 6pt;
                color: #888;
                font-style: italic;
            }
            
            /* FOOTER - COMPLETELY REMOVED */
            .invoice-footer { display: none; }
            
            .no-items { text-align: center; padding: 8px 0; color: #000000; font-size: 8pt; }
            
            /* PRINT */
            @page {
                size: A5 portrait;
                margin: 0;
            }
            @media print {
                body { background: white; padding: 0; margin: 0; display: block; min-height: auto; }
                body * { visibility: hidden; }
                .invoice-wrapper, .invoice-wrapper * { visibility: visible; }
                .invoice-wrapper {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 148mm;
                    height: 210mm;
                    box-shadow: none;
                    border-radius: 0;
                    margin: 0;
                    padding: 1in 5mm 0.9in 5mm;
                    page-break-after: avoid;
                    page-break-inside: avoid;
                    background: white;
                    overflow: hidden;
                    display: flex;
                    flex-direction: column;
                }
                .invoice-container {
                    min-height: auto !important;
                    height: 100% !important;
                    max-height: 100% !important;
                    overflow: hidden !important;
                    padding: 0;
                    display: flex;
                    flex-direction: column;
                }
                .print-time-bar { display: none !important; }
                .watermark { display: none !important; }
                .invoice-header { display: none !important; }
                .header-gap { display: none !important; }
                .invoice-footer { display: none !important; }
                .no-print { display: none !important; }
                .action-buttons { display: none !important; }
            }
            
            /* RESPONSIVE */
            @media screen and (max-width: 576px) {
                .invoice-wrapper { width: 100%; height: auto; min-height: auto; padding: 1in 5mm 0.9in 5mm; }
                .patient-info-section .info-col,
                .patient-info-section .info-col-right { width: 100%; padding: 0; }
                .summary-section { justify-content: center; }
                .summary-section .totals { max-width: 100%; }
                .items-table { font-size: 6.5pt; }
                .items-table thead th,
                .items-table tbody td { padding: 1.5px 2px; }
                .payment-status-section {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 2px;
                    min-height: auto;
                }
                .payment-status-section .status { font-size: 12pt; }
                .payment-status-section .amount-word { text-align: left; }
                .accessories-section .acc-item { display: block; margin: 2px 0; }
                .signature-lab-section {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 4px;
                }
                .signature-lab-section .signature-right {
                    text-align: left;
                    padding-left: 0;
                }
                .payment-history-section { width: 100%; }
                .payment-history-table { font-size: 5pt; }
                .payment-history-table thead th,
                .payment-history-table tbody td { padding: 1px 2px; }
            }
        </style>
    </head>
    <body>
        <div class="invoice-wrapper">
            <div class="invoice-container" id="invoicePrint">
                
                <!-- WATERMARK - REMOVED -->
                <!-- HEADER - REMOVED -->
                <!-- HEADER GAP - REMOVED -->
                <!-- PRINT TIME - REMOVED -->
                
                <!-- PATIENT INFORMATION -->
                <div class="patient-info-section">
                    <div class="info-col">
                        <div class="field">
                            <span class="label">Patient Id :</span>
                            <span class="value-strong"><?php echo isset($bill['patient_code']) ? htmlspecialchars($bill['patient_code']) : 'N/A'; ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Bill No. :</span>
                            <span class="value-strong"><?php echo isset($bill['bill_number']) ? htmlspecialchars($bill['bill_number']) : 'N/A'; ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Bill Date :</span>
                            <span class="value"><?php echo $billDateTime; ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Referred By :</span>
                            <span class="value"><?php echo htmlspecialchars($referredByName); ?></span>
                        </div>
                    </div>
                    
                    <div class="info-col-right">
                        <div class="field">
                            <span class="label">Patient Name :</span>
                            <span class="value-strong"><?php echo isset($bill['patient_name']) ? ucwords(strtolower(htmlspecialchars($bill['patient_name']))) : 'N/A'; ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Gender :</span>
                            <span class="value"><?php echo isset($bill['gender']) ? ucwords(strtolower($bill['gender'])) : 'N/A'; ?></span>
                        </div>
                        <div class="field">
                            <span class="label">Age :</span>
                            <span class="value"><?php echo $age ?: 'N/A'; ?></span>
                        </div>
                        <div class="field" style="margin-right:0;">
                            <span class="label">Contact No. :</span>
                            <span class="value-phone"><?php echo isset($bill['phone']) ? htmlspecialchars($bill['phone']) : 'N/A'; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- ITEMS TABLE - Deliv. column before Particulars -->
                <div class="items-table-wrapper">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="width:4%;">Sno.</th>
                                <th style="width:10%;" class="text-center">Deliv.</th>
                                <th style="width:28%;">Particulars</th>
                                <th style="width:11%;" class="text-end">Rate</th>
                                <th style="width:7%;" class="text-center">Unit</th>
                                <th style="width:14%;" class="text-end">Total</th>
                                <th style="width:14%;" class="text-end">Disc. (%)</th>
                                <th style="width:12%;" class="text-end">Net Amt.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($mainItems)): ?>
                                <?php $counter = 1; foreach ($mainItems as $item): 
                                    $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                                    $rate = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
                                    $total = $qty * $rate;
                                    $discount = isset($item['discount_amount']) ? (float)$item['discount_amount'] : 0;
                                    $discountPercentItem = 0;
                                    if ($total > 0 && $discount > 0) {
                                        $discountPercentItem = round(($discount / $total) * 100, 2);
                                    }
                                    $net = $total - $discount;
                                    $desc = isset($item['description']) ? ucwords(strtolower(htmlspecialchars($item['description']))) : 'N/A';
                                    
                                    // Get delivery date (turnaround time) for lab tests
                                    $deliv = '';
                                    if (isset($item['turnaround_time']) && $item['turnaround_time'] > 0) {
                                        $deliv = $item['turnaround_time'] . 'h';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td class="text-center"><?php echo $deliv ?: '-'; ?></td>
                                    <td><?php echo $desc; ?></td>
                                    <td class="text-end"><?php echo number_format($rate, 2); ?></td>
                                    <td class="text-center"><?php echo $qty; ?></td>
                                    <td class="text-end"><?php echo number_format($total, 2); ?></td>
                                    <td class="text-end">
                                        <?php if($discount > 0): ?>
                                            <?php echo number_format($discountPercentItem, 2); ?>% 
                                            <small style="font-size:6pt;">(<?php echo number_format($discount, 2); ?>)</small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><strong><?php echo number_format($net, 2); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center no-items">No items found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- ACCESSORIES -->
                <?php if (!empty($accessories)): ?>
                <div class="accessories-section">
                    <div class="acc-title">Accessories Used:</div>
                    <?php foreach ($accessories as $acc): 
                        $accName = isset($acc['description']) ? ucwords(strtolower(htmlspecialchars($acc['description']))) : (isset($acc['item_name']) ? ucwords(strtolower(htmlspecialchars($acc['item_name']))) : 'Accessory');
                        $accQty = isset($acc['quantity']) ? (int)$acc['quantity'] : 1;
                    ?>
                        <span class="acc-item">
                            <span class="acc-name"><?php echo $accName; ?></span>
                            <span class="acc-qty">x <?php echo $accQty; ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <!-- SUMMARY - Added Due and Amt Received -->
                <div class="summary-section">
                    <div class="totals">
                        <!-- Gross Amount -->
                        <div class="summary-row">
                            <span class="summary-label">Gross Amount</span>
                            <span class="summary-value"><?php echo number_format($grossAmount, 2); ?></span>
                        </div>
                        
                        <!-- Discount(-) with Percentage -->
                        <div class="summary-row discount-row">
                            <span class="summary-label">Discount(-) <?php if($discountPercent > 0): ?>(<?php echo number_format($discountPercent, 2); ?>%)<?php endif; ?></span>
                            <span class="summary-value">- <?php echo number_format($totalDiscount, 2); ?></span>
                        </div>
                        
                        <!-- Round Off Amount -->
                        <div class="summary-row">
                            <span class="summary-label">Round Off Amount</span>
                            <span class="summary-value"><?php 
                                if ($roundOff != 0) {
                                    echo number_format($roundOff, 2);
                                } else {
                                    echo '0.00';
                                }
                            ?></span>
                        </div>
                        
                        <!-- Net Amount (rounded) -->
                        <div class="summary-row net-payable">
                            <span class="summary-label">Net Amount</span>
                            <span class="summary-value"><?php echo number_format($netAmount, 2); ?></span>
                        </div>
                        
                        <!-- Due Amount -->
                        <div class="summary-row">
                            <span class="summary-label">Due</span>
                            <span class="summary-value"><?php echo number_format($dueAmount, 2); ?></span>
                        </div>
                        
                        <!-- Amt Received (Taka) -->
                        <div class="summary-row">
                            <span class="summary-label">Amt Received (Taka)</span>
                            <span class="summary-value"><?php echo number_format($amtReceived, 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- PAYMENT STATUS - Only "Paid" or "Unpaid" -->
                <div class="payment-status-section">
                    <div class="status">
                        <?php echo strtoupper($paymentStatus); ?>
                    </div>
                    <div class="amount-word">
                        <strong>In Word :</strong> <?php echo $inWords; ?>
                    </div>
                </div>
                
                <!-- SIGNATURE & LAB NOTE - Same Line, No Blank Space, No Underline -->
                <div class="signature-lab-section">
                    <div class="lab-note-left">
                        <?php if($hasLabTest): ?>
                            📋 Report collect time 8AM to 10PM. <br>
                            📋 Please collect your report within 30days.
                        <?php else: ?>
                            &nbsp;
                        <?php endif; ?>
                    </div>
                    <div class="signature-right">
                        <div class="prepared-by"><strong>Prepared By :</strong> <?php echo $currentUserName; ?></div>
                        <div class="billing-date"><?php echo $billDateTime; ?></div>
                    </div>
                </div>
                
                <!-- PAYMENT HISTORY - Last Section, Compact, 50% Width, Left Aligned -->
                <?php if(!empty($paymentHistory)): ?>
                <div class="payment-history-section">
                    <table class="payment-history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>User Name</th>
                                <th>Amount</th>
                                <th>Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($paymentHistory as $pay): 
                                // Use created_at for time as it has the full timestamp
                                $payTimestamp = isset($pay['created_at']) && !empty($pay['created_at']) 
                                    ? $pay['created_at'] 
                                    : (isset($pay['payment_date']) ? $pay['payment_date'] : date('Y-m-d H:i:s'));
                                
                                // Format date and time from the actual payment timestamp
                                $payDate = date('d-M-Y', strtotime($payTimestamp));
                                $payTime = date('h:i A', strtotime($payTimestamp));
                                
                                $receivedByName = isset($pay['first_name']) ? $pay['first_name'] . ' ' . $pay['last_name'] : 'System';
                                $methodDisplay = ucfirst($pay['payment_method'] ?? 'cash');
                            ?>
                            <tr>
                                <td><?php echo $payDate; ?></td>
                                <td><?php echo $payTime; ?></td>
                                <td><?php echo htmlspecialchars($receivedByName); ?></td>
                                <td><?php echo number_format($pay['amount'], 2); ?></td>
                                <td><?php echo $methodDisplay; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <!-- FOOTER - REMOVED -->
                
            </div>
            
            <!-- ACTION BUTTONS - No Print -->
            <div class="action-buttons no-print" style="text-align:center; padding:10px 0; border-top:1px solid #e0e0e0; background:#ffffff;">
                <button class="btn btn-primary" onclick="window.print()" style="padding:8px 20px; font-size:10pt; border:none; border-radius:4px; cursor:pointer; background:#000000; color:white; font-family:'Cambria','Times New Roman',serif; margin:0 5px;">
                    <i class="fas fa-print me-2"></i>Print Invoice
                </button>
                <a href="<?php echo BASE_URL; ?>/bills" class="btn btn-secondary" style="padding:8px 20px; font-size:10pt; border:none; border-radius:4px; cursor:pointer; background:#999999; color:white; font-family:'Cambria','Times New Roman',serif; margin:0 5px; text-decoration:none; display:inline-block;">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
                <?php if(isset($bill['reference_type']) && $bill['reference_type'] == 'lab_order' && isset($bill['reference_id'])): ?>
                <a href="<?php echo BASE_URL; ?>/lab/orders/<?php echo $bill['reference_id']; ?>" class="btn btn-outline" style="padding:8px 20px; font-size:10pt; border:1px solid #000000; border-radius:4px; cursor:pointer; background:transparent; color:#000000; font-family:'Cambria','Times New Roman',serif; margin:0 5px; text-decoration:none; display:inline-block;">
                    <i class="fas fa-flask me-2"></i>View Lab Order
                </a>
                <?php endif; ?>
            </div>
            
        </div>
        
        <script>
            setTimeout(function() {
                window.print();
            }, 500);
        </script>
        
    </body>
    </html>
    <?php
    exit;
}

    private function numberToWords($number) {
        $number = round($number, 2);
        $parts = explode('.', number_format($number, 2, '.', ''));
        $integer = (int)$parts[0];
        $decimal = isset($parts[1]) ? (int)$parts[1] : 0;
        $words = $this->convertIntegerToWords($integer);
        if ($decimal > 0) {
            $words .= ' point ' . $this->convertIntegerToWords($decimal);
        }
        return $words;
    }

    private function convertIntegerToWords($number) {
        $number = (int)$number;
        $hyphen      = '-';
        $conjunction = ' and ';
        $separator   = ', ';
        $negative    = 'negative ';
        $dictionary  = array(
            0                   => 'zero',
            1                   => 'one',
            2                   => 'two',
            3                   => 'three',
            4                   => 'four',
            5                   => 'five',
            6                   => 'six',
            7                   => 'seven',
            8                   => 'eight',
            9                   => 'nine',
            10                  => 'ten',
            11                  => 'eleven',
            12                  => 'twelve',
            13                  => 'thirteen',
            14                  => 'fourteen',
            15                  => 'fifteen',
            16                  => 'sixteen',
            17                  => 'seventeen',
            18                  => 'eighteen',
            19                  => 'nineteen',
            20                  => 'twenty',
            30                  => 'thirty',
            40                  => 'forty',
            50                  => 'fifty',
            60                  => 'sixty',
            70                  => 'seventy',
            80                  => 'eighty',
            90                  => 'ninety',
            100                 => 'hundred',
            1000                => 'thousand',
            1000000             => 'million',
            1000000000          => 'billion',
            1000000000000       => 'trillion',
            1000000000000000    => 'quadrillion',
            1000000000000000000 => 'quintillion'
        );
        if (!is_numeric($number)) { return false; }
        if (($number >= 0 && (int)$number < 0) || (int)$number < 0 - PHP_INT_MAX) {
            return $negative . $this->convertIntegerToWords(abs($number));
        }
        $string = $fraction = null;
        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
            $number = (int)$number;
        }
        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) { $string .= $hyphen . $dictionary[$units]; }
                break;
            case $number < 1000:
                $hundreds  = (int)($number / 100);
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) { $string .= $conjunction . $this->convertIntegerToWords($remainder); }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convertIntegerToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convertIntegerToWords($remainder);
                }
                break;
        }
        return ucfirst($string);
    }
    
    // ==================== GET BILL ITEMS ====================
    public function getBillItems($id) {
        header('Content-Type: application/json');
        $id = (int)$id;
        $query = "SELECT bi.*, 
                         CASE 
                             WHEN bi.item_type = 'medicine' THEN 'Medicine'
                             WHEN bi.item_type = 'lab_test' THEN 'Lab Test'
                             WHEN bi.item_type = 'consultation' THEN 'Consultation'
                             WHEN bi.item_type = 'procedure' THEN 'Procedure'
                             WHEN bi.item_type = 'service' THEN 'Service'
                             ELSE 'Other'
                         END as item_type_display,
                         ds.service_name as service_name_from_doctor,
                         ds.service_price as service_price_from_doctor
                  FROM bill_items bi
                  LEFT JOIN doctor_services ds ON bi.item_id = ds.id
                  WHERE bi.bill_id = $id
                  ORDER BY bi.id ASC";
        $result = $this->db->query($query);
        $items = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                if(empty($row['description']) && !empty($row['service_name_from_doctor'])) {
                    $row['description'] = $row['service_name_from_doctor'];
                }
                $items[] = $row;
            }
        }
        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    }
    
    // ==================== GET BILL DETAILS AJAX ====================
    public function getBillDetailsAjax($id) {
        header('Content-Type: application/json');
        $id = (int)$id;
        $billQuery = "SELECT b.*, 
                             CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                             p.phone,
                             p.patient_code,
                             CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                      FROM bills b
                      LEFT JOIN patients p ON b.patient_id = p.id
                      LEFT JOIN users u ON b.created_by = u.id
                      WHERE b.id = $id";
        $billResult = $this->db->query($billQuery);
        if(!$billResult || $billResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
            exit;
        }
        $bill = $billResult->fetch_assoc();
        $itemsQuery = "SELECT bi.*, 
                              CASE 
                                  WHEN bi.item_type = 'medicine' THEN 'Medicine'
                                  WHEN bi.item_type = 'lab_test' THEN 'Lab Test'
                                  WHEN bi.item_type = 'consultation' THEN 'Consultation'
                                  WHEN bi.item_type = 'procedure' THEN 'Procedure'
                                  ELSE 'Other'
                              END as item_type_display
                       FROM bill_items bi
                       WHERE bi.bill_id = $id
                       ORDER BY bi.id ASC";
        $itemsResult = $this->db->query($itemsQuery);
        $items = [];
        if($itemsResult) {
            while($row = $itemsResult->fetch_assoc()) {
                if(empty($row['description'])) {
                    $row['description'] = $row['item_type_display'];
                }
                $items[] = $row;
            }
        }
        echo json_encode(['success' => true, 'sale' => $bill, 'items' => $items]);
        exit;
    }
    
    // ==================== GET BILL SERVICE SUMMARY ====================
    public function getBillServiceSummary($id) {
        header('Content-Type: application/json');
        $id = (int)$id;
        $query = "SELECT 
                    bi.item_type,
                    bi.description,
                    bi.unit_price,
                    bi.total_amount,
                    ds.service_name as doctor_service_name,
                    ds.service_price as doctor_service_price
                  FROM bill_items bi
                  LEFT JOIN doctor_services ds ON bi.item_id = ds.id
                  WHERE bi.bill_id = $id
                  ORDER BY bi.id ASC";
        $result = $this->db->query($query);
        $services = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $services[] = $row;
            }
        }
        echo json_encode(['success' => true, 'services' => $services]);
        exit;
    }
    
    // ==================== CREATE BILL FORM ====================
    public function createBill() {
        try {
            $this->checkAuth();
            
            $patients = [];
            $patientsQuery = "SELECT id, first_name, last_name, patient_code, phone, email 
                             FROM patients 
                             WHERE status = 'active' 
                             ORDER BY first_name ASC";
            $patientsResult = $this->db->query($patientsQuery);
            if ($patientsResult && $patientsResult->num_rows > 0) {
                while ($row = $patientsResult->fetch_assoc()) {
                    $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
                    $patients[] = $row;
                }
            }
            
            $medicinesList = [];
            $medicinesQuery = "SELECT 
                                m.id, 
                                m.medicine_name, 
                                m.selling_price, 
                                m.medicine_code,
                                COALESCE(SUM(ms.quantity), 0) as current_stock
                              FROM medicines m
                              LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                              WHERE m.status = 'active'
                              GROUP BY m.id
                              ORDER BY m.medicine_name ASC";
            $medicinesResult = $this->db->query($medicinesQuery);
            if ($medicinesResult && $medicinesResult->num_rows > 0) {
                while ($row = $medicinesResult->fetch_assoc()) {
                    $medicinesList[] = $row;
                }
            }
            
            $labTestsList = [];
            $labTestsQuery = "SELECT id, test_name, price, test_code FROM lab_tests WHERE status = 'active' ORDER BY test_name ASC";
            $labTestsResult = $this->db->query($labTestsQuery);
            if ($labTestsResult && $labTestsResult->num_rows > 0) {
                while ($row = $labTestsResult->fetch_assoc()) {
                    $labTestsList[] = $row;
                }
            }
            
            $doctorsData = [];
            $servicesData = [];
            $doctorsQuery = "SELECT d.id, CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) as name 
                            FROM doctors d
                            JOIN users u ON d.user_id = u.id
                            WHERE d.status = 'active'
                            ORDER BY u.first_name ASC";
            $doctorsResult = $this->db->query($doctorsQuery);
            if ($doctorsResult && $doctorsResult->num_rows > 0) {
                while ($doctor = $doctorsResult->fetch_assoc()) {
                    $doctorsData[] = $doctor;
                    $servicesQuery = "SELECT id, service_name, service_price 
                                    FROM doctor_services 
                                    WHERE doctor_id = " . $doctor['id'] . " AND status = 'active'";
                    $servicesResult = $this->db->query($servicesQuery);
                    $services = [];
                    if ($servicesResult && $servicesResult->num_rows > 0) {
                        while ($service = $servicesResult->fetch_assoc()) {
                            $services[] = $service;
                        }
                    }
                    $servicesData[$doctor['id']] = $services;
                }
            }
            
            $consultations = [];
            $consultationsQuery = "SELECT id, service_name, default_price FROM services WHERE service_type = 'consultation' AND status = 'active'";
            $consultationsResult = $this->db->query($consultationsQuery);
            if ($consultationsResult && $consultationsResult->num_rows > 0) {
                while ($row = $consultationsResult->fetch_assoc()) {
                    $consultations[] = $row;
                }
            }
            
            $viewPath = 'bills/create-bill';
            if(!file_exists(dirname(__DIR__) . '/views/' . $viewPath . '.php')) {
                $viewPath = 'billing/create-bill';
            }
            
            $content = $this->renderView($viewPath, [
                'patients' => $patients,
                'medicinesList' => $medicinesList,
                'labTestsList' => $labTestsList,
                'consultations' => $consultations,
                'doctorsData' => $doctorsData,
                'servicesData' => $servicesData
            ]);
            $this->renderLayout('Create Bill', $content);
            
        } catch (Exception $e) {
            echo "Error in createBill(): " . $e->getMessage();
            error_log("Create Bill Error: " . $e->getMessage());
        }
    }
    
    // ==================== STORE BILL ====================
    public function storeBill() {
        header('Content-Type: application/json');
        try {
            $patientId = (int)$_POST['patient_id'];
            $billType = isset($_POST['bill_type']) ? $this->db->real_escape_string($_POST['bill_type']) : 'consultation';
            $billDate = isset($_POST['bill_date']) ? $this->db->real_escape_string($_POST['bill_date']) : date('Y-m-d');
            $discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
            $taxPercent = isset($_POST['tax_percent']) ? (float)$_POST['tax_percent'] : 0;
            $notes = isset($_POST['notes']) ? $this->db->real_escape_string($_POST['notes']) : '';
            $itemsJson = isset($_POST['items']) ? $_POST['items'] : '';
            $items = json_decode($itemsJson, true);
            $paymentMethod = isset($_POST['payment_method']) ? $this->db->real_escape_string($_POST['payment_method']) : 'cash';
            $referredBy = isset($_POST['referred_by']) ? (int)$_POST['referred_by'] : 0;
            
            if($patientId == 0) {
                echo json_encode(['success' => false, 'message' => 'Please select a patient']);
                exit;
            }
            if(empty($items)) {
                echo json_encode(['success' => false, 'message' => 'Please add at least one item']);
                exit;
            }
            
            $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
            $subtotal = 0;
            $taxAmount = 0;
            $discountAmount = 0;
            
            foreach($items as $item) {
                $itemTotal = $item['quantity'] * $item['unit_price'];
                $itemDiscount = $itemTotal * (($item['discount_percent'] ?? 0) / 100);
                $itemSubtotal = $itemTotal - $itemDiscount;
                $itemTax = $itemSubtotal * ($taxPercent / 100);
                $subtotal += $itemSubtotal;
                $taxAmount += $itemTax;
            }
            
            $discountAmount = $subtotal * ($discountPercent / 100);
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
            
            $billTypeDisplay = $billType;
            if($billType == 'pharmacy') $billTypeDisplay = 'pharmacy';
            elseif($billType == 'consultation') $billTypeDisplay = 'consultation';
            elseif($billType == 'lab_test') $billTypeDisplay = 'lab_test';
            else $billTypeDisplay = 'other';
            
            // Add referred_by to the query
            $referredByValue = $referredBy > 0 ? $referredBy : 'NULL';
            
            $query = "INSERT INTO bills (bill_number, patient_id, bill_type, bill_date, 
                      subtotal, discount_percentage, discount_amount, tax_amount, total_amount, 
                      paid_amount, balance_amount, payment_status, payment_method, notes, 
                      referred_by, created_by, created_at) 
                      VALUES ('$billNumber', $patientId, '$billTypeDisplay', '$billDate', 
                      $subtotal, $discountPercent, $discountAmount, $taxAmount, $totalAmount,
                      0, $totalAmount, 'pending', '$paymentMethod', '$notes',
                      $referredByValue, {$_SESSION['user_id']}, NOW())";
            
            if($this->db->query($query)) {
                $billId = $this->db->insert_id;
                foreach($items as $item) {
                    $itemType = $this->db->real_escape_string($item['item_type']);
                    $itemId = isset($item['item_id']) && $item['item_id'] ? (int)$item['item_id'] : null;
                    $itemName = $this->db->real_escape_string($item['item_name']);
                    $quantity = (int)$item['quantity'];
                    $unitPrice = (float)$item['unit_price'];
                    $discountPercentItem = (float)($item['discount_percent'] ?? 0);
                    $taxPercentItem = $taxPercent;
                    $itemTotal = $quantity * $unitPrice;
                    $itemDiscount = $itemTotal * ($discountPercentItem / 100);
                    $itemSubtotal = $itemTotal - $itemDiscount;
                    $itemTax = $itemSubtotal * ($taxPercentItem / 100);
                    $itemGrandTotal = $itemSubtotal + $itemTax;
                    $this->db->query("INSERT INTO bill_items (bill_id, item_type, item_id, description,
                                      quantity, unit_price, discount_percentage, discount_amount, 
                                      tax_percentage, tax_amount, total_amount)
                                      VALUES ($billId, '$itemType', " . ($itemId ? $itemId : "NULL") . ", 
                                      '$itemName', $quantity, $unitPrice, $discountPercentItem, 
                                      $itemDiscount, $taxPercentItem, $itemTax, $itemGrandTotal)");
                }
                echo json_encode(['success' => true, 'bill_id' => $billId, 'bill_number' => $billNumber]);
            } else {
                echo json_encode(['success' => false, 'message' => $this->db->error]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // ==================== GET PAYMENT HISTORY ====================
    public function getPaymentHistory() {
        header('Content-Type: application/json');
        $billId = isset($_GET['bill_id']) ? (int)$_GET['bill_id'] : 0;
        if(!$billId) {
            echo json_encode(['success' => false, 'message' => 'Bill ID required']);
            exit;
        }
        $billCheck = $this->db->query("SELECT id, bill_number FROM bills WHERE id = $billId");
        if(!$billCheck || $billCheck->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
            exit;
        }
        $bill = $billCheck->fetch_assoc();
        $query = "SELECT 
                    p.id,
                    p.payment_number,
                    p.amount,
                    p.payment_method,
                    p.transaction_id,
                    p.payment_date,
                    p.created_at,
                    p.notes,
                    CONCAT(u.first_name, ' ', u.last_name) as received_by_name
                  FROM payments p
                  LEFT JOIN users u ON p.received_by = u.id
                  WHERE p.bill_id = $billId
                  ORDER BY p.payment_date DESC, p.created_at DESC";
        $result = $this->db->query($query);
        if(!$result) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $this->db->error]);
            exit;
        }
        $paymentList = [];
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $paymentList[] = [
                    'id' => $row['id'],
                    'payment_number' => $row['payment_number'],
                    'amount' => (float)$row['amount'],
                    'payment_method' => $row['payment_method'],
                    'transaction_id' => $row['transaction_id'],
                    'payment_date' => $row['payment_date'],
                    'created_at' => $row['created_at'],
                    'notes' => $row['notes'],
                    'received_by_name' => $row['received_by_name'] ?: 'System'
                ];
            }
        }
        echo json_encode([
            'success' => true, 
            'payments' => $paymentList,
            'bill_id' => $billId,
            'bill_number' => $bill['bill_number'],
            'count' => count($paymentList)
        ]);
        exit;
    }
    
    // ==================== EXPORT BILLS ====================
    public function exportBills() {
        $dateFrom = isset($_GET['date_from']) ? $this->db->real_escape_string($_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? $this->db->real_escape_string($_GET['date_to']) : '';
        $status = isset($_GET['status']) ? $this->db->real_escape_string($_GET['status']) : '';
        $billType = isset($_GET['bill_type']) ? $this->db->real_escape_string($_GET['bill_type']) : '';
        
        $where = "WHERE 1=1";
        if($dateFrom) $where .= " AND b.bill_date >= '$dateFrom'";
        if($dateTo) $where .= " AND b.bill_date <= '$dateTo'";
        if($status) $where .= " AND b.payment_status = '$status'";
        if($billType) $where .= " AND b.bill_type = '$billType'";
        
        // CORRECT: balance = total_amount - paid_amount - discount_amount
        $query = "SELECT b.*, 
                         p.first_name, p.last_name, p.patient_code, p.phone,
                         COALESCE((SELECT COUNT(*) FROM bill_items WHERE bill_id = b.id), 0) as item_count,
                         COALESCE((SELECT description FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1), 'Consultation') as service_name,
                         COALESCE((SELECT item_type FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1), b.bill_type) as service_type,
                         COALESCE((SELECT unit_price FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1), b.total_amount) as service_price,
                         COALESCE((SELECT total_amount FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1), b.total_amount) as service_total,
                         ROUND(COALESCE(b.total_amount, 0) - COALESCE(b.paid_amount, 0) - COALESCE(b.discount_amount, 0), 2) as balance_amount
                  FROM bills b
                  JOIN patients p ON b.patient_id = p.id
                  $where
                  ORDER BY b.bill_date DESC, b.id DESC
                  LIMIT $limit OFFSET $offset";
        
        $result = $this->db->query($query);
        $filename = "bills_report_" . date('Y-m-d') . ".csv";
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Bill #', 'Patient Name', 'Patient Code', 'Date', 'Service Name', 'Type', 'Subtotal', 'Discount %', 'Discount Amount', 'Tax', 'Total', 'Paid', 'Balance', 'Status', 'Created At']);
        while($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['bill_number'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['patient_code'],
                $row['bill_date'],
                $row['service_name'] ?? 'Consultation',
                $row['bill_type'],
                $row['subtotal'],
                $row['discount_percentage'],
                $row['discount_amount'],
                $row['tax_amount'],
                $row['total_amount'],
                $row['paid_amount'],
                $row['balance_amount'],
                $row['payment_status'],
                $row['created_at']
            ]);
        }
        fclose($output);
        exit;
    }
    
    // ==================== PAYMENTS ====================
    public function payments() {
        $this->checkAuth();
        $db = $this->db;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';
        $method = isset($_GET['method']) ? $_GET['method'] : '';
        $search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
        $where = "WHERE 1=1";
        if (!empty($dateFrom)) { $where .= " AND DATE(p.payment_date) >= '$dateFrom'"; }
        if (!empty($dateTo)) { $where .= " AND DATE(p.payment_date) <= '$dateTo'"; }
        if (!empty($method)) { $where .= " AND p.payment_method = '$method'"; }
        if (!empty($search)) {
            $where .= " AND (p.payment_number LIKE '%$search%' 
                             OR CONCAT(pa.first_name, ' ', pa.last_name) LIKE '%$search%'
                             OR pa.patient_code LIKE '%$search%')";
        }
        $countQuery = "SELECT COUNT(*) as total FROM payments p 
                       JOIN patients pa ON p.patient_id = pa.id 
                       $where";
        $countResult = $db->query($countQuery);
        $totalPayments = $countResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($totalPayments / $limit);
        $query = "SELECT p.*, pa.first_name, pa.last_name, pa.patient_code, 
                         b.bill_number, u.first_name as received_by_name,
                         COALESCE(
                             (SELECT description FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1),
                             'Consultation'
                         ) as service_name
                  FROM payments p
                  JOIN patients pa ON p.patient_id = pa.id
                  LEFT JOIN bills b ON p.bill_id = b.id
                  LEFT JOIN users u ON p.received_by = u.id
                  $where
                  ORDER BY p.payment_date DESC, p.created_at DESC
                  LIMIT $limit OFFSET $offset";
        $result = $db->query($query);
        $paymentList = [];
        while ($row = $result->fetch_assoc()) {
            $paymentList[] = $row;
        }
        $totalAmountReceived = 0;
        $cashAmount = 0;
        $cardAmount = 0;
        $mobileAmount = 0;
        $bankAmount = 0;
        foreach ($paymentList as $p) {
            $totalAmountReceived += $p['amount'];
            if ($p['payment_method'] == 'cash') $cashAmount += $p['amount'];
            elseif ($p['payment_method'] == 'card') $cardAmount += $p['amount'];
            elseif ($p['payment_method'] == 'mobile_banking') $mobileAmount += $p['amount'];
            elseif ($p['payment_method'] == 'bank_transfer') $bankAmount += $p['amount'];
        }
        $this->view('bills/payments', [
            'payments' => $paymentList,
            'totalPayments' => $totalPayments,
            'totalAmountReceived' => $totalAmountReceived,
            'cashAmount' => $cashAmount,
            'cardAmount' => $cardAmount,
            'mobileAmount' => $mobileAmount,
            'bankAmount' => $bankAmount,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'selectedMethod' => $method,
            'search' => $search,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ], 'Payments');
    }
    
    // ==================== FINANCIAL REPORTS - UPDATED ====================
    public function financialReports() {
        $this->checkAuth();
        $this->checkPermission('view_financial');
        
        $reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'transaction_list';
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
        $selectedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $paymentMethodFilter = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
        
        // Get users list for filter
        $usersList = $this->getUsersList();
        
        // Initialize data arrays
        $paymentMethodData = [];
        $userWiseData = [];
        $dailyBillData = [];
        $summaryBillData = [];
        $transactionListData = [];
        $transactionSummaryByDate = [];
        $transactionSummaryByMethod = [];
        $transactionSummaryByUser = [];
        $totalRevenue = 0;
        $totalCollected = 0;
        $totalDue = 0;
        $totalTransactions = 0;
        
        // 1. Payment Method Wise Data
        $paymentMethodData = $this->getPaymentMethodWiseData($dateFrom, $dateTo, $selectedUserId, $paymentMethodFilter);
        
        // 2. User Wise Data
        $userWiseData = $this->getUserWiseData($dateFrom, $dateTo, $selectedUserId);
        
        // 3. Daily Bill Data
        $dailyBillData = $this->getDailyBillData($dateFrom, $dateTo, $selectedUserId);
        
        // 4. Summary Bill Data
        $summaryBillData = $this->getSummaryBillData($dateFrom, $dateTo, $selectedUserId);
        
        // 5. Transaction List Data (Detailed)
        $transactionListData = $this->getTransactionListData($dateFrom, $dateTo, $selectedUserId, $paymentMethodFilter);
        
        // 6. Transaction Summary by Date
        $transactionSummaryByDate = $this->getTransactionSummaryByDate($dateFrom, $dateTo, $selectedUserId, $paymentMethodFilter);
        
        // 7. Transaction Summary by Payment Method
        $transactionSummaryByMethod = $this->getTransactionSummaryByMethod($dateFrom, $dateTo, $selectedUserId, $paymentMethodFilter);
        
        // 8. Transaction Summary by User
        $transactionSummaryByUser = $this->getTransactionSummaryByUser($dateFrom, $dateTo, $selectedUserId, $paymentMethodFilter);
        
        // Calculate totals
        foreach ($paymentMethodData as $row) {
            $totalRevenue += $row['total_amount'];
            $totalTransactions += $row['total_transactions'];
        }
        foreach ($userWiseData as $row) {
            $totalCollected += $row['collected_amount'];
            $totalDue += $row['due_amount'];
        }
        
        // If no data in payment method, try from transaction list
        if (empty($paymentMethodData) && !empty($transactionListData)) {
            $totalTransactions = count($transactionListData);
            $totalRevenue = array_sum(array_column($transactionListData, 'amount'));
        }
        
        $viewPath = 'bills/financial-reports';
        if (!file_exists(dirname(__DIR__) . '/views/' . $viewPath . '.php')) {
            $viewPath = 'billing/financial-reports';
        }
        
        $content = $this->renderView($viewPath, [
            'reportType' => $reportType,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'selectedUserId' => $selectedUserId,
            'usersList' => $usersList,
            'paymentMethodFilter' => $paymentMethodFilter,
            'paymentMethodData' => $paymentMethodData,
            'userWiseData' => $userWiseData,
            'dailyBillData' => $dailyBillData,
            'summaryBillData' => $summaryBillData,
            'transactionListData' => $transactionListData,
            'transactionSummaryByDate' => $transactionSummaryByDate,
            'transactionSummaryByMethod' => $transactionSummaryByMethod,
            'transactionSummaryByUser' => $transactionSummaryByUser,
            'totalRevenue' => $totalRevenue,
            'totalCollected' => $totalCollected,
            'totalDue' => $totalDue,
            'totalTransactions' => $totalTransactions
        ]);
        $this->renderLayout('Financial Reports', $content);
    }

    // ================================================================
    // 1. PAYMENT METHOD WISE DATA (with date range)
    // ================================================================
    private function getPaymentMethodWiseData($dateFrom, $dateTo, $userId, $paymentMethod) {
        $where = "WHERE DATE(p.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        if ($userId > 0) {
            $where .= " AND p.received_by = $userId";
        }
        if (!empty($paymentMethod)) {
            $where .= " AND p.payment_method = '$paymentMethod'";
        }
        
        $query = "SELECT 
                    p.payment_method,
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(p.amount), 0) as total_amount
                  FROM payments p
                  $where
                  GROUP BY p.payment_method
                  ORDER BY total_amount DESC";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // ================================================================
    // 2. USER WISE DATA (with date range)
    // ================================================================
    private function getUserWiseData($dateFrom, $dateTo, $userId) {
        $userWhere = "";
        if ($userId > 0) {
            $userWhere = " AND b.created_by = $userId";
        }
        
        $where = "WHERE DATE(b.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        $where .= $userWhere;
        
        $query = "SELECT 
                    b.created_by as user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    r.name as role_name,
                    COUNT(*) as total_transactions,
                    COALESCE(SUM(b.total_amount), 0) as total_amount,
                    COALESCE(SUM(b.paid_amount), 0) as collected_amount,
                    COALESCE(SUM(b.balance_amount), 0) as due_amount
                  FROM bills b
                  LEFT JOIN users u ON b.created_by = u.id
                  LEFT JOIN roles r ON u.role_id = r.id
                  $where
                  GROUP BY b.created_by
                  ORDER BY total_amount DESC";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // ================================================================
    // 3. DAILY BILL DATA (with date range)
    // ================================================================
    private function getDailyBillData($dateFrom, $dateTo, $userId) {
        $userWhere = "";
        if ($userId > 0) {
            $userWhere = " AND b.created_by = $userId";
        }
        
        $where = "WHERE DATE(b.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        $where .= $userWhere;
        
        $query = "SELECT 
                    b.bill_number,
                    b.bill_date,
                    b.bill_type,
                    b.total_amount,
                    b.paid_amount,
                    COALESCE(b.discount_amount, 0) as discount_amount,
                    b.balance_amount,
                    b.payment_status,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    b.created_by
                  FROM bills b
                  JOIN patients p ON b.patient_id = p.id
                  LEFT JOIN users u ON b.created_by = u.id
                  $where
                  ORDER BY b.bill_date DESC, b.created_at DESC
                  LIMIT 500";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // ================================================================
    // 4. SUMMARY BILL DATA (with date range)
    // ================================================================
    private function getSummaryBillData($dateFrom, $dateTo, $userId) {
        $userWhere = "";
        if ($userId > 0) {
            $userWhere = " AND b.created_by = $userId";
        }
        
        $where = "WHERE DATE(b.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        $where .= $userWhere;
        
        $query = "SELECT 
                    DATE(b.bill_date) as date,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    b.created_by as user_id,
                    COUNT(*) as total_bills,
                    COALESCE(SUM(b.total_amount), 0) as total_amount,
                    COALESCE(SUM(b.discount_amount), 0) as discount_amount,
                    COALESCE(SUM(b.paid_amount), 0) as paid_amount,
                    COALESCE(SUM(b.balance_amount), 0) as due_amount
                  FROM bills b
                  LEFT JOIN users u ON b.created_by = u.id
                  $where
                  GROUP BY DATE(b.bill_date), b.created_by
                  ORDER BY date DESC, total_amount DESC";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // ================================================================
    // 5. TRANSACTION LIST DATA (Detailed - with date range)
    // ================================================================
    private function getTransactionListData($dateFrom, $dateTo, $userId, $paymentMethod) {
        $where = "WHERE DATE(p.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        if ($userId > 0) {
            $where .= " AND p.received_by = $userId";
        }
        if (!empty($paymentMethod)) {
            $where .= " AND p.payment_method = '$paymentMethod'";
        }
        
        $query = "SELECT 
                    p.id,
                    p.payment_number,
                    p.amount,
                    p.payment_method,
                    p.payment_date,
                    p.created_at,
                    p.notes,
                    b.bill_number,
                    b.total_amount as bill_total,
                    b.discount_amount as bill_discount,
                    b.payment_status,
                    CONCAT(pa.first_name, ' ', pa.last_name) as patient_name,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    p.received_by
                  FROM payments p
                  LEFT JOIN bills b ON p.bill_id = b.id
                  LEFT JOIN patients pa ON p.patient_id = pa.id
                  LEFT JOIN users u ON p.received_by = u.id
                  $where
                  ORDER BY p.payment_date DESC, p.created_at DESC
                  LIMIT 500";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // ================================================================
    // 6. TRANSACTION SUMMARY BY DATE
    // ================================================================
    private function getTransactionSummaryByDate($dateFrom, $dateTo, $userId, $paymentMethod) {
        $where = "WHERE DATE(p.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        if ($userId > 0) {
            $where .= " AND p.received_by = $userId";
        }
        if (!empty($paymentMethod)) {
            $where .= " AND p.payment_method = '$paymentMethod'";
        }
        
        $query = "SELECT 
                    DATE(p.created_at) as date,
                    COUNT(*) as count,
                    COALESCE(SUM(p.amount), 0) as total,
                    COALESCE(AVG(p.amount), 0) as avg,
                    COALESCE(MIN(p.amount), 0) as min,
                    COALESCE(MAX(p.amount), 0) as max
                  FROM payments p
                  $where
                  GROUP BY DATE(p.created_at)
                  ORDER BY date DESC";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // ================================================================
    // 7. TRANSACTION SUMMARY BY PAYMENT METHOD
    // ================================================================
    private function getTransactionSummaryByMethod($dateFrom, $dateTo, $userId, $paymentMethod) {
        $where = "WHERE DATE(p.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        if ($userId > 0) {
            $where .= " AND p.received_by = $userId";
        }
        if (!empty($paymentMethod)) {
            $where .= " AND p.payment_method = '$paymentMethod'";
        }
        
        $query = "SELECT 
                    p.payment_method,
                    COUNT(*) as count,
                    COALESCE(SUM(p.amount), 0) as total
                  FROM payments p
                  $where
                  GROUP BY p.payment_method
                  ORDER BY total DESC";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // ================================================================
    // 8. TRANSACTION SUMMARY BY USER
    // ================================================================
    private function getTransactionSummaryByUser($dateFrom, $dateTo, $userId, $paymentMethod) {
        $where = "WHERE DATE(p.created_at) BETWEEN '$dateFrom' AND '$dateTo'";
        if ($userId > 0) {
            $where .= " AND p.received_by = $userId";
        }
        if (!empty($paymentMethod)) {
            $where .= " AND p.payment_method = '$paymentMethod'";
        }
        
        $query = "SELECT 
                    p.received_by as user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    COUNT(*) as count,
                    COALESCE(SUM(p.amount), 0) as total
                  FROM payments p
                  LEFT JOIN users u ON p.received_by = u.id
                  $where
                  GROUP BY p.received_by
                  ORDER BY total DESC";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        return $data;
    }

    // ================================================================
    // GET USERS LIST FOR FILTER
    // ================================================================
    private function getUsersList() {
        $query = "SELECT id, first_name, last_name, role_id 
                  FROM users 
                  WHERE status = 'active' 
                  ORDER BY first_name ASC";
        $result = $this->db->query($query);
        $users = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
        return $users;
    }
    
    // ==================== HELPER METHODS ====================
    private function getMonthlyStats($month) {
        $query = "SELECT 
                    COUNT(*) as total_bills,
                    COALESCE(SUM(total_amount), 0) as total_amount,
                    COALESCE(SUM(paid_amount), 0) as paid_amount,
                    COALESCE(SUM(balance_amount), 0) as due_amount
                  FROM bills 
                  WHERE DATE_FORMAT(created_at, '%Y-%m') = '$month'";
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc() : ['total_bills' => 0, 'total_amount' => 0, 'paid_amount' => 0, 'due_amount' => 0];
    }
    
    private function getDailyStats($year) {
        $stats = [];
        $query = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total_bills,
                    COALESCE(SUM(total_amount), 0) as total_amount,
                    COALESCE(SUM(paid_amount), 0) as paid_amount,
                    COALESCE(SUM(balance_amount), 0) as due_amount
                  FROM bills 
                  WHERE YEAR(created_at) = $year
                  GROUP BY DATE(created_at)
                  ORDER BY date DESC
                  LIMIT 30";
        $result = $this->db->query($query);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
        }
        return $stats;
    }
    
    private function getMonthlyStatsForYear($year) {
        $stats = [];
        $query = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as total_bills,
                    COALESCE(SUM(total_amount), 0) as total_amount,
                    COALESCE(SUM(paid_amount), 0) as paid_amount,
                    COALESCE(SUM(balance_amount), 0) as due_amount
                  FROM bills 
                  WHERE YEAR(created_at) = $year
                  GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                  ORDER BY month ASC";
        $result = $this->db->query($query);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $monthNames = ['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', 
                              '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', 
                              '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'];
                $monthNum = substr($row['month'], 5, 2);
                $row['month_name'] = $monthNames[$monthNum] . ' ' . $year;
                $stats[] = $row;
            }
        }
        return $stats;
    }
    
    private function getRevenueByBillType($year) {
        $stats = [];
        $query = "SELECT 
                    bill_type,
                    COUNT(*) as total_bills,
                    COALESCE(SUM(total_amount), 0) as total_amount,
                    COALESCE(SUM(paid_amount), 0) as paid_amount
                  FROM bills 
                  WHERE YEAR(created_at) = $year
                  GROUP BY bill_type
                  ORDER BY total_amount DESC";
        $result = $this->db->query($query);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
        }
        return $stats;
    }
    
    private function getPaymentStats($year) {
        $stats = [];
        $query = "SELECT 
                    payment_method,
                    COUNT(*) as total_payments,
                    COALESCE(SUM(amount), 0) as total_amount
                  FROM payments 
                  WHERE YEAR(created_at) = $year
                  GROUP BY payment_method
                  ORDER BY total_amount DESC";
        $result = $this->db->query($query);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
        }
        return $stats;
    }
    
    private function getYearlyStats($year) {
        $query = "SELECT 
                    COUNT(*) as total_bills,
                    COALESCE(SUM(total_amount), 0) as total_amount,
                    COALESCE(SUM(paid_amount), 0) as paid_amount,
                    COALESCE(SUM(balance_amount), 0) as due_amount
                  FROM bills 
                  WHERE YEAR(created_at) = $year";
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc() : ['total_bills' => 0, 'total_amount' => 0, 'paid_amount' => 0, 'due_amount' => 0];
    }

    public function fixBillBalances() {
    $this->checkAuth();
    
    // Only allow admin to run this
    if ($_SESSION['role_slug'] != 'super_admin' && $_SESSION['role_slug'] != 'admin') {
        $_SESSION['error'] = "Only administrators can run this fix.";
        $this->redirect('/bills');
        return;
    }
    
    $db = $this->db;
    $fixed = 0;
    $errors = [];
    
    // Get all bills where balance_amount is not correctly calculated
    $query = "SELECT id, total_amount, paid_amount, discount_amount, balance_amount 
              FROM bills 
              WHERE ROUND(total_amount - paid_amount - COALESCE(discount_amount, 0), 2) != ROUND(balance_amount, 2)
              OR balance_amount IS NULL";
    
    $result = $db->query($query);
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $id = $row['id'];
            $total = (float)$row['total_amount'];
            $paid = (float)$row['paid_amount'];
            $discount = (float)($row['discount_amount'] ?? 0);
            $correctBalance = round($total - $paid - $discount, 2);
            if ($correctBalance < 0.01) $correctBalance = 0;
            $currentBalance = $row['balance_amount'] !== null ? (float)$row['balance_amount'] : -1;
            if ($currentBalance < 0.01) $currentBalance = 0;
            
            if ($correctBalance != $currentBalance) {
                $updateQuery = "UPDATE bills SET 
                               balance_amount = $correctBalance,
                               payment_status = CASE 
                                   WHEN $correctBalance = 0 THEN 'paid'
                                   WHEN $paid > 0 AND $correctBalance > 0 THEN 'partial'
                                   ELSE 'pending'
                               END,
                               updated_at = NOW()
                               WHERE id = $id";
                
                if ($db->query($updateQuery)) {
                    $fixed++;
                } else {
                    $errors[] = "Failed to update bill #$id: " . $db->error;
                }
            }
        }
    }
    
    // Also fix appointment balances
    $appQuery = "SELECT a.id, a.total_amount, a.payment_received, a.payment_status,
                        b.id as bill_id, b.discount_amount
                 FROM appointments a
                 LEFT JOIN bills b ON b.reference_type = 'appointment' AND b.reference_id = a.id
                 WHERE a.total_amount > 0
                 AND a.payment_received IS NOT NULL";
    
    $appResult = $db->query($appQuery);
    $appFixed = 0;
    
    if ($appResult && $appResult->num_rows > 0) {
        while ($row = $appResult->fetch_assoc()) {
            $total = (float)$row['total_amount'];
            $paid = (float)$row['payment_received'];
            $discount = (float)($row['discount_amount'] ?? 0);
            $correctBalance = round($total - $paid - $discount, 2);
            if ($correctBalance < 0.01) $correctBalance = 0;
            
            $status = ($correctBalance == 0) ? 'paid' : (($paid > 0) ? 'partial' : 'pending');
            
            $updateApp = "UPDATE appointments SET 
                         payment_status = '$status'
                         WHERE id = {$row['id']}";
            $db->query($updateApp);
            $appFixed++;
        }
    }
    
    $message = "Fixed $fixed bills and $appFixed appointments.";
    if (!empty($errors)) {
        $message .= " Errors: " . implode(', ', $errors);
    }
    
    $_SESSION['success'] = $message;
    $this->redirect('/bills');
}

// ==================== EDIT BILL FORM ====================
public function editBill($id) {
    $this->checkAuth();
    $this->checkPermission('view_bills');
    
    $id = (int)$id;
    $db = $this->db;
    
    // Get bill details
    $billQuery = "SELECT b.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code,
                         p.phone,
                         p.id as patient_id,
                         p.first_name, p.last_name
                  FROM bills b
                  JOIN patients p ON b.patient_id = p.id
                  WHERE b.id = $id";
    $billResult = $db->query($billQuery);
    
    if (!$billResult || $billResult->num_rows == 0) {
        $_SESSION['error'] = "Bill not found";
        $this->redirect('/bills');
        return;
    }
    
    $bill = $billResult->fetch_assoc();
    
    // Get bill items
    $itemsQuery = "SELECT * FROM bill_items WHERE bill_id = $id ORDER BY id ASC";
    $itemsResult = $db->query($itemsQuery);
    $items = [];
    if ($itemsResult) {
        while ($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    // Get medicines list
    $medicinesList = [];
    $medicinesQuery = "SELECT 
                            m.id, 
                            m.medicine_name, 
                            m.selling_price, 
                            m.medicine_code,
                            COALESCE(SUM(ms.quantity), 0) as current_stock
                        FROM medicines m
                        LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                        WHERE m.status = 'active'
                        GROUP BY m.id
                        ORDER BY m.medicine_name ASC";
    $medicinesResult = $db->query($medicinesQuery);
    if ($medicinesResult) {
        while ($row = $medicinesResult->fetch_assoc()) {
            $medicinesList[] = $row;
        }
    }
    
    // Get lab tests list
    $labTestsList = [];
    $labTestsQuery = "SELECT id, test_name, price, test_code FROM lab_tests WHERE status = 'active' ORDER BY test_name ASC";
    $labTestsResult = $db->query($labTestsQuery);
    if ($labTestsResult) {
        while ($row = $labTestsResult->fetch_assoc()) {
            $labTestsList[] = $row;
        }
    }
    
    // Get doctors data
    $doctorsData = [];
    $servicesData = [];
    $doctorsQuery = "SELECT d.id, CONCAT(COALESCE(u.title, ''), ' ', u.first_name, ' ', u.last_name) as name 
                    FROM doctors d
                    JOIN users u ON d.user_id = u.id
                    WHERE d.status = 'active'
                    ORDER BY u.first_name ASC";
    $doctorsResult = $db->query($doctorsQuery);
    if ($doctorsResult) {
        while ($doctor = $doctorsResult->fetch_assoc()) {
            $doctorsData[] = $doctor;
            $servicesQuery = "SELECT id, service_name, service_price 
                            FROM doctor_services 
                            WHERE doctor_id = " . $doctor['id'] . " AND status = 'active'";
            $servicesResult = $db->query($servicesQuery);
            $services = [];
            if ($servicesResult) {
                while ($service = $servicesResult->fetch_assoc()) {
                    $services[] = $service;
                }
            }
            $servicesData[$doctor['id']] = $services;
        }
    }
    
    $viewPath = 'bills/edit-bill';
    if (!file_exists(dirname(__DIR__) . '/views/' . $viewPath . '.php')) {
        $viewPath = 'billing/edit-bill';
    }
    
    $content = $this->renderView($viewPath, [
        'bill' => $bill,
        'items' => $items,
        'medicinesList' => $medicinesList,
        'labTestsList' => $labTestsList,
        'doctorsData' => $doctorsData,
        'servicesData' => $servicesData
    ]);
    $this->renderLayout('Edit Bill', $content);
}

// ==================== UPDATE BILL - FULL VERSION WITH DOCTOR/SERVICE SYNC ====================
public function updateBill() {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        // Log the request for debugging
        error_log("UPDATE BILL REQUEST: " . print_r($_POST, true));
        
        $billId = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
        $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
        $billDate = isset($_POST['bill_date']) ? $this->db->real_escape_string($_POST['bill_date']) : date('Y-m-d');
        $billType = isset($_POST['bill_type']) ? $this->db->real_escape_string($_POST['bill_type']) : 'consultation';
        $discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
        $discountAmount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0;
        $taxAmount = isset($_POST['tax_amount']) ? (float)$_POST['tax_amount'] : 0;
        $subtotal = isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0;
        $totalAmount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
        $notes = isset($_POST['notes']) ? $this->db->real_escape_string($_POST['notes']) : '';
        $paymentMethod = isset($_POST['payment_method']) ? $this->db->real_escape_string($_POST['payment_method']) : 'cash';
        $itemsJson = isset($_POST['items']) ? $_POST['items'] : '';
        $items = json_decode($itemsJson, true);
        
        // New fields for doctor/service update
        $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
        $serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
        $serviceName = isset($_POST['service_name']) ? $this->db->real_escape_string($_POST['service_name']) : '';
        
        // Validate inputs
        if ($billId == 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid bill ID']);
            exit;
        }
        
        if ($patientId == 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a patient']);
            exit;
        }
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Please add at least one item']);
            exit;
        }
        
        // Get current bill to preserve paid amount and reference info
        $currentBillQuery = "SELECT paid_amount, balance_amount, payment_status, bill_number, 
                                   reference_type, reference_id FROM bills WHERE id = $billId";
        $currentBillResult = $this->db->query($currentBillQuery);
        if (!$currentBillResult || $currentBillResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
            exit;
        }
        $currentBill = $currentBillResult->fetch_assoc();
        $currentPaid = (float)$currentBill['paid_amount'];
        $billNumber = $currentBill['bill_number'];
        $referenceType = $currentBill['reference_type'];
        $referenceId = (int)$currentBill['reference_id'];
        
        // Calculate new balance
        $newBalance = $totalAmount - $currentPaid;
        if ($newBalance < 0.01) $newBalance = 0;
        
        $paymentStatus = ($newBalance == 0) ? 'paid' : (($currentPaid > 0) ? 'partial' : 'pending');
        
        $this->db->begin_transaction();
        
        // Update bill
        $updateQuery = "UPDATE bills SET 
                        patient_id = $patientId,
                        bill_date = '$billDate',
                        bill_type = '$billType',
                        subtotal = $subtotal,
                        discount_percentage = $discountPercent,
                        discount_amount = $discountAmount,
                        tax_amount = $taxAmount,
                        total_amount = $totalAmount,
                        balance_amount = $newBalance,
                        payment_status = '$paymentStatus',
                        payment_method = '$paymentMethod',
                        notes = '$notes',
                        updated_at = NOW()
                        WHERE id = $billId";
        
        if (!$this->db->query($updateQuery)) {
            throw new Exception("Failed to update bill: " . $this->db->error);
        }
        
        // Delete existing bill items
        $this->db->query("DELETE FROM bill_items WHERE bill_id = $billId");
        
        // Insert new bill items
        $itemCount = 0;
        $foundDoctorId = 0;
        $foundServiceId = 0;
        $foundServiceName = '';
        $foundServicePrice = 0;
        
        foreach ($items as $item) {
            $itemType = $this->db->real_escape_string($item['item_type']);
            $itemId = isset($item['item_id']) && $item['item_id'] ? (int)$item['item_id'] : null;
            $description = $this->db->real_escape_string($item['description']);
            $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
            $unitPrice = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
            $discountPercentItem = isset($item['discount_percentage']) ? (float)$item['discount_percentage'] : 0;
            $discountAmountItem = isset($item['discount_amount']) ? (float)$item['discount_amount'] : 0;
            $taxAmountItem = isset($item['tax_amount']) ? (float)$item['tax_amount'] : 0;
            $totalAmountItem = isset($item['total_amount']) ? (float)$item['total_amount'] : 0;
            
            // If total amount is not provided, calculate it
            if ($totalAmountItem == 0) {
                $itemTotal = $quantity * $unitPrice;
                $discAmt = $itemTotal * ($discountPercentItem / 100);
                $taxAmt = ($itemTotal - $discAmt) * 0;
                $totalAmountItem = $itemTotal - $discAmt + $taxAmt;
            }
            
            // Track doctor and service info from items
            if ($itemType == 'service' || $itemType == 'consultation') {
                if (isset($item['doctor_id']) && $item['doctor_id'] > 0) {
                    $foundDoctorId = (int)$item['doctor_id'];
                }
                if (isset($item['item_id']) && $item['item_id'] > 0) {
                    $foundServiceId = (int)$item['item_id'];
                }
                $foundServiceName = $description;
                $foundServicePrice = $unitPrice;
            }
            
            $insertQuery = "INSERT INTO bill_items (bill_id, item_type, item_id, description,
                              quantity, unit_price, discount_percentage, discount_amount,
                              tax_percentage, tax_amount, total_amount)
                            VALUES ($billId, '$itemType', " . ($itemId ? $itemId : "NULL") . ", 
                              '$description', $quantity, $unitPrice, $discountPercentItem,
                              $discountAmountItem, 0, $taxAmountItem, $totalAmountItem)";
            
            if ($this->db->query($insertQuery)) {
                $itemCount++;
            }
        }
        
        // Use passed values if found in items, otherwise use passed parameters
        $finalDoctorId = ($foundDoctorId > 0) ? $foundDoctorId : $doctorId;
        $finalServiceId = ($foundServiceId > 0) ? $foundServiceId : $serviceId;
        $finalServiceName = !empty($foundServiceName) ? $foundServiceName : $serviceName;
        
        // ================================================================
        // SYNC WITH APPOINTMENT IF LINKED - Update doctor and service
        // ================================================================
        if ($referenceType == 'appointment' && $referenceId > 0) {
            // Update appointment with new doctor and service
            $updateAppointment = "UPDATE appointments SET 
                                  total_amount = $totalAmount,
                                  payment_received = $currentPaid,
                                  discount = $discountAmount,
                                  payment_status = '$paymentStatus',
                                  updated_at = NOW()";
            
            // Update doctor if changed
            if ($finalDoctorId > 0) {
                $updateAppointment .= ", doctor_id = $finalDoctorId";
            }
            
            // Update service if changed
            if ($finalServiceId > 0) {
                $updateAppointment .= ", service_id = $finalServiceId";
            }
            
            $updateAppointment .= " WHERE id = $referenceId";
            $this->db->query($updateAppointment);
            
            // Log the sync
            error_log("Appointment #$referenceId synced - Doctor: $finalDoctorId, Service: $finalServiceId");
        }
        
        // ================================================================
        // SYNC WITH PHARMACY SALE IF LINKED
        // ================================================================
        if ($referenceType == 'pharmacy_sale' && $referenceId > 0) {
            $saleStatus = ($paymentStatus == 'paid') ? 'completed' : 'partial';
            $this->db->query("UPDATE pharmacy_sales SET 
                              status = '$saleStatus',
                              paid_amount = $currentPaid,
                              total_amount = $totalAmount,
                              updated_at = NOW()
                              WHERE id = $referenceId");
        }
        
        // Get patient name
        $patientQuery = "SELECT CONCAT(first_name, ' ', last_name) as name FROM patients WHERE id = $patientId";
        $patientResult = $this->db->query($patientQuery);
        $patientName = $patientResult ? $patientResult->fetch_assoc()['name'] : 'N/A';
        
        $this->db->commit();
        
        // Log success
        error_log("Bill updated successfully: ID=$billId, Items=$itemCount, Total=$totalAmount, Doctor=$finalDoctorId, Service=$finalServiceId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Bill updated successfully',
            'bill_id' => $billId,
            'bill_number' => $billNumber,
            'patient_name' => $patientName,
            'item_count' => $itemCount,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'balance_amount' => $newBalance,
            'payment_status' => $paymentStatus,
            'paid_amount' => $currentPaid,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'doctor_id' => $finalDoctorId,
            'service_id' => $finalServiceId,
            'service_name' => $finalServiceName
        ]);
        
    } catch (Exception $e) {
        if (isset($this->db) && $this->db->connect_errno == 0) {
            $this->db->rollback();
        }
        error_log("UPDATE BILL ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==================== DELETE BILL - FIXED WITH APPOINTMENT SYNC ====================
public function deleteBill() {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    try {
        $billId = isset($_POST['bill_id']) ? (int)$_POST['bill_id'] : 0;
        
        if ($billId == 0) {
            echo json_encode(['success' => false, 'message' => 'Bill ID required']);
            exit;
        }
        
        // Check if bill exists and get reference info
        $checkQuery = "SELECT id, bill_number, payment_status, reference_type, reference_id, 
                              patient_id, total_amount, paid_amount, discount_amount 
                       FROM bills WHERE id = $billId";
        $checkResult = $this->db->query($checkQuery);
        if (!$checkResult || $checkResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Bill not found']);
            exit;
        }
        $bill = $checkResult->fetch_assoc();
        $billNumber = $bill['bill_number'];
        $referenceType = $bill['reference_type'];
        $referenceId = (int)$bill['reference_id'];
        $totalAmount = (float)$bill['total_amount'];
        $paidAmount = (float)$bill['paid_amount'];
        $discountAmount = (float)$bill['discount_amount'];
        
        // Check if bill has payments
        $paymentCheck = $this->db->query("SELECT COUNT(*) as count FROM payments WHERE bill_id = $billId");
        if ($paymentCheck) {
            $paymentCount = (int)$paymentCheck->fetch_assoc()['count'];
            if ($paymentCount > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete bill with ' . $paymentCount . ' payment(s). Please refund payments first.']);
                exit;
            }
        }
        
        $this->db->begin_transaction();
        
        // Delete bill items
        $this->db->query("DELETE FROM bill_items WHERE bill_id = $billId");
        
        // Delete the bill
        $this->db->query("DELETE FROM bills WHERE id = $billId");
        
        // ================================================================
        // SYNC WITH APPOINTMENT IF LINKED - Reset to pending
        // ================================================================
        if ($referenceType == 'appointment' && $referenceId > 0) {
            // Reset appointment payment data
            $resetQuery = "UPDATE appointments SET 
                           total_amount = 0,
                           payment_received = 0,
                           discount = 0,
                           payment_status = 'pending',
                           updated_at = NOW()
                           WHERE id = $referenceId";
            $this->db->query($resetQuery);
        }
        
        // ================================================================
        // SYNC WITH PHARMACY SALE IF LINKED - Reset
        // ================================================================
        if ($referenceType == 'pharmacy_sale' && $referenceId > 0) {
            $this->db->query("UPDATE pharmacy_sales SET 
                              status = 'canceled',
                              paid_amount = 0,
                              updated_at = NOW()
                              WHERE id = $referenceId");
        }
        
        $this->db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Bill #' . $billNumber . ' deleted successfully',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId
        ]);
        
    } catch (Exception $e) {
        if (isset($this->db) && $this->db->connect_errno == 0) {
            $this->db->rollback();
        }
        error_log("DELETE BILL ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==================== SYNC APPOINTMENT PAYMENT STATUS ====================
private function syncAppointmentPayment($appointmentId, $totalAmount, $paidAmount, $discountAmount) {
    if (!$appointmentId) return;
    
    $db = $this->db;
    
    // Calculate balance
    $balance = $totalAmount - $paidAmount - $discountAmount;
    if ($balance < 0.01) $balance = 0;
    
    $paymentStatus = ($balance == 0) ? 'paid' : (($paidAmount > 0) ? 'partial' : 'pending');
    
    $updateQuery = "UPDATE appointments SET 
                    total_amount = $totalAmount,
                    payment_received = $paidAmount,
                    discount = $discountAmount,
                    payment_status = '$paymentStatus',
                    updated_at = NOW()
                    WHERE id = $appointmentId";
    
    $db->query($updateQuery);
    
    // Also update queue status if fully paid
    if ($balance == 0 && $paymentStatus == 'paid') {
        // Check if appointment is completed, if not, keep as is
        $appCheck = $db->query("SELECT status FROM appointments WHERE id = $appointmentId");
        if ($appCheck && $appCheck->num_rows > 0) {
            $appData = $appCheck->fetch_assoc();
            if ($appData['status'] != 'completed' && $appData['status'] != 'canceled') {
                // Optionally update to completed or keep current
            }
        }
    }
}

}
?>