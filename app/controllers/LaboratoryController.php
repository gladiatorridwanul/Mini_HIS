<?php
require_once BASE_PATH . '/core/Controller.php';

class LaboratoryController extends Controller {
    
    // ==================== DASHBOARD ====================
    public function dashboard() {
        $this->checkAuth();
        
        $db = $this->db;
        $pendingTests = 0;
        $inProgressTests = 0;
        $completedToday = 0;
        $categoryList = [];
        
        $result = $db->query("SELECT COUNT(*) as count FROM lab_test_orders WHERE status = 'ordered'");
        if($result) $pendingTests = $result->fetch_assoc()['count'];
        
        $result = $db->query("SELECT COUNT(*) as count FROM lab_test_orders WHERE status IN ('sample_collected', 'processing')");
        if($result) $inProgressTests = $result->fetch_assoc()['count'];
        
        $result = $db->query("SELECT COUNT(*) as count FROM lab_test_orders WHERE status = 'completed' AND DATE(created_at) = CURDATE()");
        if($result) $completedToday = $result->fetch_assoc()['count'];
        
        $testCategories = $db->query("SELECT * FROM lab_test_categories WHERE status = 'active'");
        if($testCategories) {
            while($row = $testCategories->fetch_assoc()) { 
                $categoryList[] = $row; 
            }
        }
        
        $this->view('lab/dashboard', [
            'pendingTests' => $pendingTests,
            'inProgressTests' => $inProgressTests,
            'completedToday' => $completedToday,
            'testCategories' => $categoryList
        ], 'Laboratory Dashboard');
    }
    
    // ==================== ORDERS LIST ====================
    public function orders() {
        $this->checkAuth();
        $this->checkPermission('view_lab');
        
        $db = $this->db;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $dateFrom = isset($_GET['date_from']) ? $db->real_escape_string($_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? $db->real_escape_string($_GET['date_to']) : '';
        $status = isset($_GET['status']) ? $db->real_escape_string($_GET['status']) : '';
        $priority = isset($_GET['priority']) ? $db->real_escape_string($_GET['priority']) : '';
        $search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
        
        // Build WHERE clause
        $where = "WHERE 1=1";
        if (!empty($dateFrom)) {
            $where .= " AND DATE(o.order_date) >= '$dateFrom'";
        }
        if (!empty($dateTo)) {
            $where .= " AND DATE(o.order_date) <= '$dateTo'";
        }
        if (!empty($status)) {
            $where .= " AND o.status = '$status'";
        }
        if (!empty($priority)) {
            $where .= " AND o.priority = '$priority'";
        }
        if (!empty($search)) {
            $where .= " AND (o.order_number LIKE '%$search%' 
                             OR CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search%'
                             OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%')";
        }
        
        // Get total orders count
        $countQuery = "SELECT COUNT(*) as total FROM lab_test_orders o 
                       JOIN patients p ON o.patient_id = p.id 
                       JOIN doctors d ON o.doctor_id = d.id 
                       JOIN users u ON d.user_id = u.id 
                       $where";
        $countResult = $db->query($countQuery);
        $totalRecords = $countResult->fetch_assoc()['total'] ?? 0;
        $totalPages = ceil($totalRecords / $limit);
        
        // Get orders with pagination
        $query = "SELECT o.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code,
                         CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                         (SELECT COUNT(*) FROM lab_test_order_items WHERE order_id = o.id) as test_count,
                         b.bill_number,
                         b.total_amount as bill_amount,
                         b.discount_amount,
                         b.payment_status,
                         b.id as bill_id
                  FROM lab_test_orders o
                  JOIN patients p ON o.patient_id = p.id
                  JOIN doctors d ON o.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  LEFT JOIN bills b ON b.reference_id = o.id AND b.reference_type = 'lab_order'
                  $where
                  ORDER BY o.order_date DESC, o.created_at DESC
                  LIMIT $limit OFFSET $offset";
        
        $result = $db->query($query);
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        // Get statistics
        $statsQuery = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN o.status != 'completed' THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                        SUM(b.total_amount) as total_amount
                       FROM lab_test_orders o
                       LEFT JOIN bills b ON b.reference_id = o.id AND b.reference_type = 'lab_order'
                       WHERE 1=1";
        
        $statsResult = $db->query($statsQuery);
        $stats = $statsResult->fetch_assoc() ?: ['total_orders' => 0, 'pending_orders' => 0, 'completed_orders' => 0, 'total_amount' => 0];
        
        $this->view('lab/orders', [
            'orders' => $orders,
            'stats' => $stats,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'status' => $status,
            'priority' => $priority,
            'search' => $search,
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ], 'Lab Orders');
    }
    
    // ==================== CREATE ORDER FORM ====================
    public function createOrder() {
        $this->checkAuth();
        
        $db = $this->db;
        $patientList = [];
        $patients = $db->query("SELECT id, patient_code, first_name, last_name, phone 
                               FROM patients WHERE status = 'active' ORDER BY first_name ASC");
        if($patients) {
            while($row = $patients->fetch_assoc()) { 
                $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $patientList[] = $row; 
            }
        }
        
        $testList = [];
        $tests = $db->query("SELECT t.*, c.name as category_name 
                            FROM lab_tests t
                            LEFT JOIN lab_test_categories c ON t.category_id = c.id
                            WHERE t.status = 'active'
                            ORDER BY c.name, t.test_name");
        if($tests) {
            while($row = $tests->fetch_assoc()) { 
                $row['test_price'] = $row['price'];
                $testList[] = $row; 
            }
        }
        
        $this->view('lab/create-order', [
            'patients' => $patientList,
            'tests' => $testList
        ], 'Create Lab Order');
    }
    
    // ==================== STORE ORDER ====================
public function storeOrder() {
    $this->checkAuth();
    $this->checkPermission('create_orders');
    
    header('Content-Type: application/json');
    
    try {
        $db = $this->db;
        
        $patientId = (int)$_POST['patient_id'];
        $doctorId = (int)$_POST['doctor_id'];
        $orderDate = isset($_POST['order_date']) ? $db->real_escape_string($_POST['order_date']) : date('Y-m-d');
        $priority = isset($_POST['priority']) ? $db->real_escape_string($_POST['priority']) : 'routine';
        $clinicalDiagnosis = isset($_POST['clinical_diagnosis']) ? $db->real_escape_string($_POST['clinical_diagnosis']) : '';
        $notes = isset($_POST['notes']) ? $db->real_escape_string($_POST['notes']) : '';
        
        // Get tests from JSON
        $testsJson = isset($_POST['tests']) ? $_POST['tests'] : '[]';
        $tests = json_decode($testsJson, true);
        
        // If tests is empty or not an array, try to get from tests array
        if (empty($tests) || !is_array($tests)) {
            // Check if tests were sent as individual fields
            $testIds = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'tests') === 0 && is_array($value)) {
                    foreach ($value as $test) {
                        if (isset($test['test_id'])) {
                            $testIds[] = (int)$test['test_id'];
                        }
                    }
                }
            }
            
            // If still empty, try to get from flat array
            if (empty($testIds) && isset($_POST['tests']) && is_array($_POST['tests'])) {
                foreach ($_POST['tests'] as $test) {
                    if (isset($test['test_id'])) {
                        $testIds[] = (int)$test['test_id'];
                    }
                }
            }
            
            // If we have test IDs, fetch test details
            if (!empty($testIds)) {
                $tests = [];
                foreach ($testIds as $testId) {
                    $testResult = $db->query("SELECT id, test_name, price FROM lab_tests WHERE id = $testId AND status = 'active'");
                    if ($testResult && $testResult->num_rows > 0) {
                        $test = $testResult->fetch_assoc();
                        $tests[] = [
                            'id' => $test['id'],
                            'name' => $test['test_name'],
                            'price' => (float)$test['price']
                        ];
                    }
                }
            }
        }
        
        // Get discount info
        $discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
        $discountAmount = isset($_POST['discount_amount']) ? (float)$_POST['discount_amount'] : 0;
        $subtotal = isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0;
        $totalAmount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
        
        if ($patientId == 0) {
            throw new Exception('Please select a patient');
        }
        if ($doctorId == 0) {
            throw new Exception('Please select a doctor');
        }
        if (empty($tests)) {
            throw new Exception('Please select at least one test');
        }
        
        $orderedBy = $_SESSION['user_id'] ?? 1;
        $orderNumber = 'LAB' . date('Ymd') . rand(100, 999);
        
        // Calculate subtotal from tests if not provided
        if ($subtotal == 0) {
            foreach ($tests as $test) {
                $subtotal += (float)$test['price'];
            }
        }
        
        // Calculate tax
        $taxAmount = $subtotal * 0.05;
        
        // Calculate discount if percentage
        if ($discountPercent > 0) {
            $discountAmount = ($subtotal * $discountPercent) / 100;
        }
        
        $totalAmount = $subtotal + $taxAmount - $discountAmount;
        
        $db->begin_transaction();
        
        // Generate bill
        $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
        $billDate = date('Y-m-d');
        
        $sql = "INSERT INTO bills (
                    bill_number, patient_id, bill_type, bill_date,
                    subtotal, discount_amount, discount_percentage,
                    tax_amount, total_amount, paid_amount, balance_amount,
                    payment_status, reference_type,
                    created_by, notes, payment_method
                ) VALUES (
                    '$billNumber', $patientId, 'lab_test', '$billDate',
                    $subtotal, $discountAmount, $discountPercent,
                    $taxAmount, $totalAmount, 0, $totalAmount,
                    'pending', 'lab_order',
                    $orderedBy, 'Lab test order - $orderNumber', 'cash'
                )";
        
        if (!$db->query($sql)) {
            throw new Exception('Failed to generate bill: ' . $db->error);
        }
        
        $billId = $db->insert_id;
        
        // Insert order
        $sql = "INSERT INTO lab_test_orders (
                    order_number, patient_id, doctor_id, order_date, 
                    priority, clinical_diagnosis, notes, status, ordered_by, 
                    bill_id, discount_amount, discount_type, discount_value
                ) VALUES (
                    '$orderNumber', $patientId, $doctorId, '$orderDate',
                    '$priority', '$clinicalDiagnosis', '$notes', 'ordered', $orderedBy,
                    $billId, $discountAmount, " . ($discountPercent > 0 ? "'percentage'" : "'fixed'") . ", " . ($discountPercent > 0 ? $discountPercent : $discountAmount) . "
                )";
        
        if (!$db->query($sql)) {
            throw new Exception('Failed to create order: ' . $db->error);
        }
        
        $orderId = $db->insert_id;
        
        // Update bill with reference_id
        $db->query("UPDATE bills SET reference_id = $orderId WHERE id = $billId");
        
        // Insert order items and bill items
        foreach ($tests as $test) {
            $testId = isset($test['id']) ? (int)$test['id'] : 0;
            $testName = isset($test['name']) ? $db->real_escape_string($test['name']) : 'Unknown Test';
            $price = isset($test['price']) ? (float)$test['price'] : 0;
            
            // Insert into lab_test_order_items
            if ($testId > 0) {
                // Check if test exists
                $check = $db->query("SELECT id FROM lab_tests WHERE id = $testId AND status = 'active'");
                if ($check && $check->num_rows > 0) {
                    $db->query("INSERT INTO lab_test_order_items (order_id, test_id, status) 
                               VALUES ($orderId, $testId, 'pending')");
                } else {
                    $db->query("INSERT INTO lab_test_order_items (order_id, test_id, status) 
                               VALUES ($orderId, NULL, 'pending')");
                }
            } else {
                $db->query("INSERT INTO lab_test_order_items (order_id, test_id, status) 
                           VALUES ($orderId, NULL, 'pending')");
            }
            
            // Insert into bill_items
            $db->query("INSERT INTO bill_items (bill_id, item_type, description, quantity, unit_price, total_amount) 
                       VALUES ($billId, 'lab_test', '$testName', 1, $price, $price)");
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'order_id' => $orderNumber,
            'bill_id' => $billNumber,
            'order_db_id' => $orderId,
            'bill_db_id' => $billId,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'message' => 'Order created successfully'
        ]);
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollback();
        }
        error_log("Store Order Error: " . $e->getMessage() . "\n" . print_r($_POST, true));
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
    
    // ==================== VIEW SINGLE ORDER ====================
public function viewOrder($id) {
    $this->checkAuth();
    $this->checkPermission('view_lab');
    
    $id = (int)$id;
    $db = $this->db;
    
    // Get order details with patient and doctor info
    $orderQuery = "SELECT o.*, 
                          CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                          p.patient_code,
                          p.phone,
                          p.gender,
                          p.date_of_birth,
                          CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                          us.first_name as ordered_by_name
                   FROM lab_test_orders o
                   JOIN patients p ON o.patient_id = p.id
                   JOIN doctors d ON o.doctor_id = d.id
                   JOIN users u ON d.user_id = u.id
                   JOIN users us ON o.ordered_by = us.id
                   WHERE o.id = $id";
    
    $orderResult = $db->query($orderQuery);
    
    if (!$orderResult || $orderResult->num_rows == 0) {
        $_SESSION['error'] = "Order not found";
        $this->redirect('/lab/orders');
        return;
    }
    
    $order = $orderResult->fetch_assoc();
    
    // Get test items from lab_test_order_items
    $itemsQuery = "SELECT i.*, 
                          t.test_name, 
                          t.normal_range, 
                          t.unit, 
                          t.specimen_type,
                          t.test_code,
                          t.price,
                          c.name as category_name
                   FROM lab_test_order_items i
                   JOIN lab_tests t ON i.test_id = t.id
                   LEFT JOIN lab_test_categories c ON t.category_id = c.id
                   WHERE i.order_id = $id
                   ORDER BY i.id ASC";
    
    $itemsResult = $db->query($itemsQuery);
    $items = [];
    
    if ($itemsResult) {
        while ($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    // If no items found in lab_test_order_items, get from bill_items
    if (empty($items)) {
        $billItemsQuery = "SELECT 
                              bi.id,
                              bi.description as test_name,
                              bi.unit_price as price,
                              bi.quantity,
                              bi.total_amount,
                              'bill_item' as source,
                              'pending' as status
                           FROM bills b
                           JOIN bill_items bi ON b.id = bi.bill_id
                           WHERE b.reference_type = 'lab_order' AND b.reference_id = $id
                           AND bi.item_type = 'lab_test'
                           ORDER BY bi.id ASC";
        
        $billItemsResult = $db->query($billItemsQuery);
        if ($billItemsResult && $billItemsResult->num_rows > 0) {
            while ($row = $billItemsResult->fetch_assoc()) {
                $items[] = [
                    'id' => $row['id'],
                    'test_id' => null,
                    'sample_barcode' => null,
                    'status' => 'pending',
                    'result_value' => null,
                    'is_abnormal' => 0,
                    'remarks' => null,
                    'sample_collected_at' => null,
                    'test_name' => $row['test_name'],
                    'price' => $row['price'],
                    'unit' => null,
                    'normal_range' => null,
                    'specimen_type' => null,
                    'category_name' => 'Lab Test',
                    'source' => 'bill_item'
                ];
            }
        }
    }
    
    // Get bill details
    $billQuery = "SELECT * FROM bills WHERE reference_type = 'lab_order' AND reference_id = $id LIMIT 1";
    $billResult = $db->query($billQuery);
    $bill = $billResult ? $billResult->fetch_assoc() : null;
    
    // Pass data to view
    $this->view('lab/view-order', [
        'order' => $order,
        'items' => $items,
        'bill' => $bill
    ], 'Lab Order Details');
}
    
    // ==================== COLLECT SAMPLE ====================
    public function collectSample() {
        header('Content-Type: application/json');
        
        $orderId = (int)$_POST['order_id'];
        $db = $this->db;
        
        $items = $db->query("SELECT id, sample_barcode FROM lab_test_order_items 
                            WHERE order_id = $orderId AND status = 'pending'");
        
        if($items && $items->num_rows > 0) {
            while($item = $items->fetch_assoc()) {
                // Generate barcode if not exists
                if(empty($item['sample_barcode'])) {
                    $barcode = 'SMP' . date('Ymd') . rand(10000, 99999);
                    $db->query("UPDATE lab_test_order_items SET sample_barcode = '$barcode' WHERE id = {$item['id']}");
                }
                $db->query("UPDATE lab_test_order_items 
                           SET status = 'sample_collected', 
                               sample_collected_at = NOW(), 
                               sample_collected_by = {$_SESSION['user_id']}
                           WHERE id = {$item['id']}");
            }
        }
        
        $db->query("UPDATE lab_test_orders SET status = 'sample_collected' WHERE id = $orderId");
        
        $barcode = $db->query("SELECT sample_barcode FROM lab_test_order_items WHERE order_id = $orderId LIMIT 1")->fetch_assoc();
        $barcodeValue = $barcode ? $barcode['sample_barcode'] : 'N/A';
        
        echo json_encode(['success' => true, 'barcode' => $barcodeValue]);
        exit;
    }
    
    // ==================== ENTER RESULTS LIST ====================
    public function enterResults() {
        $this->checkAuth();
        
        $db = $this->db;
        $items = $db->query("SELECT 
                                i.id, 
                                i.status, 
                                i.sample_barcode, 
                                i.result_value,
                                i.is_abnormal,
                                o.order_number,
                                o.patient_id,
                                p.first_name, 
                                p.last_name, 
                                p.phone,
                                t.test_name, 
                                t.normal_range, 
                                t.unit
                            FROM lab_test_order_items i
                            JOIN lab_test_orders o ON i.order_id = o.id
                            JOIN patients p ON o.patient_id = p.id
                            JOIN lab_tests t ON i.test_id = t.id
                            WHERE i.status IN ('sample_collected', 'processing', 'completed')
                            ORDER BY o.patient_id, FIELD(i.status, 'sample_collected', 'processing', 'completed'), o.created_at DESC");
        
        $itemList = [];
        $pendingResults = 0;
        $processingCount = 0;
        $completedCount = 0;
        
        if($items) {
            while($row = $items->fetch_assoc()) {
                $row['patient_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $itemList[] = $row;
                
                if($row['status'] == 'sample_collected') $pendingResults++;
                elseif($row['status'] == 'processing') $processingCount++;
                elseif($row['status'] == 'completed') $completedCount++;
            }
        }
        
        $this->view('lab/enter-results', [
            'items' => $itemList,
            'pendingResults' => $pendingResults,
            'processingCount' => $processingCount,
            'completedCount' => $completedCount
        ], 'Enter Results');
    }
    
    // ==================== ENTER RESULT FORM ====================
    public function enterResultForm($itemId) {
        $this->checkAuth();
        
        $itemId = (int)$itemId;
        $db = $this->db;
        
        $orderItem = $db->query("SELECT i.*, t.test_name, t.normal_range, t.unit, o.order_number,
                                p.first_name, p.last_name
                                FROM lab_test_order_items i
                                JOIN lab_tests t ON i.test_id = t.id
                                JOIN lab_test_orders o ON i.order_id = o.id
                                JOIN patients p ON o.patient_id = p.id
                                WHERE i.id = $itemId")->fetch_assoc();
        
        if(!$orderItem) {
            $_SESSION['error'] = "Test item not found";
            $this->redirect('/lab/enter-results');
            return;
        }
        
        $orderItem['patient_name'] = $orderItem['first_name'] . ' ' . $orderItem['last_name'];
        
        $this->view('lab/enter-results-form', [
            'orderItem' => $orderItem
        ], 'Enter Test Results');
    }
    
    // ==================== SAVE RESULT ====================
    public function saveResult() {
        header('Content-Type: application/json');
        
        $itemId = (int)$_POST['item_id'];
        $resultValue = $this->db->real_escape_string($_POST['result']);
        $isAbnormal = isset($_POST['is_abnormal']) ? 1 : 0;
        $remarks = $this->db->real_escape_string($_POST['notes'] ?? '');
        $db = $this->db;
        
        $db->query("UPDATE lab_test_order_items 
                   SET result_value = '$resultValue',
                       remarks = '$remarks',
                       is_abnormal = $isAbnormal,
                       status = 'completed',
                       result_entered_at = NOW(),
                       result_entered_by = {$_SESSION['user_id']}
                   WHERE id = $itemId");
        
        $orderId = $db->query("SELECT order_id FROM lab_test_order_items WHERE id = $itemId")->fetch_assoc()['order_id'];
        $pendingItems = $db->query("SELECT COUNT(*) as count FROM lab_test_order_items 
                                   WHERE order_id = $orderId AND status != 'completed'")->fetch_assoc()['count'];
        
        if($pendingItems == 0) {
            $db->query("UPDATE lab_test_orders SET status = 'completed', completed_at = NOW() WHERE id = $orderId");
            
            $reportNumber = 'RPT' . date('Ymd') . rand(1000, 9999);
            $patientId = $db->query("SELECT patient_id FROM lab_test_orders WHERE id = $orderId")->fetch_assoc()['patient_id'];
            $db->query("INSERT INTO lab_reports (report_number, order_id, patient_id, report_date, generated_by) 
                       VALUES ('$reportNumber', $orderId, $patientId, CURDATE(), {$_SESSION['user_id']})");
            
            $db->query("UPDATE bills SET notes = CONCAT(notes, ' Report generated: $reportNumber') 
                       WHERE reference_type = 'lab_order' AND reference_id = $orderId");
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    // ==================== REPORTS LIST ====================
    public function reports() {
        $this->checkAuth();
        
        $db = $this->db;
        $reports = $db->query("SELECT r.*, p.first_name, p.last_name, o.order_number
                              FROM lab_reports r
                              JOIN patients p ON r.patient_id = p.id
                              JOIN lab_test_orders o ON r.order_id = o.id
                              ORDER BY r.created_at DESC");
        $reportList = [];
        if($reports) {
            while($row = $reports->fetch_assoc()) {
                $row['patient_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $reportList[] = $row;
            }
        }
        
        $this->view('lab/reports', [
            'reports' => $reportList
        ], 'Lab Reports');
    }
    
    // ==================== VIEW SINGLE REPORT ====================
    public function viewReport($id) {
        $this->checkAuth();
        
        $id = (int)$id;
        $db = $this->db;
        
        $report = $db->query("SELECT r.*, p.first_name, p.last_name, p.phone, p.gender, p.date_of_birth,
                             o.order_number, o.order_date, o.clinical_diagnosis,
                             u.first_name as doctor_fname, u.last_name as doctor_lname
                             FROM lab_reports r
                             JOIN patients p ON r.patient_id = p.id
                             JOIN lab_test_orders o ON r.order_id = o.id
                             JOIN doctors d ON o.doctor_id = d.id
                             JOIN users u ON d.user_id = u.id
                             WHERE r.id = $id")->fetch_assoc();
        
        if(!$report) {
            $_SESSION['error'] = "Report not found";
            $this->redirect('/lab/reports');
            return;
        }
        
        $report['patient_name'] = $report['first_name'] . ' ' . $report['last_name'];
        $report['doctor_name'] = $report['doctor_fname'] . ' ' . $report['doctor_lname'];
        
        $results = $db->query("SELECT i.*, t.test_name, t.unit, t.normal_range
                              FROM lab_test_order_items i
                              JOIN lab_tests t ON i.test_id = t.id
                              JOIN lab_test_orders o ON i.order_id = o.id
                              WHERE o.id = {$report['order_id']}");
        $resultList = [];
        if($results) {
            while($row = $results->fetch_assoc()) { $resultList[] = $row; }
        }
        
        $this->view('lab/view-report', [
            'report' => $report,
            'results' => $resultList
        ], 'Lab Report');
    }
    
    // ==================== DELIVER REPORT ====================
    public function deliverReport() {
        header('Content-Type: application/json');
        
        $reportId = (int)$_POST['report_id'];
        $this->db->query("UPDATE lab_reports SET is_delivered = 1, delivered_at = NOW() WHERE id = $reportId");
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    // ==================== API: GET LAB ORDERS ====================
    public function apiLabOrders() {
        header('Content-Type: application/json');
        
        $status = isset($_GET['status']) ? $this->db->real_escape_string($_GET['status']) : '';
        $date = isset($_GET['date']) ? $this->db->real_escape_string($_GET['date']) : date('Y-m-d');
        $db = $this->db;
        
        $query = "SELECT o.id, o.order_number, o.status, o.priority, o.order_date,
                         p.first_name, p.last_name,
                         u.first_name as doctor_fname, u.last_name as doctor_lname,
                         (SELECT COUNT(*) FROM lab_test_order_items WHERE order_id = o.id) as test_count
                  FROM lab_test_orders o
                  JOIN patients p ON o.patient_id = p.id
                  JOIN doctors d ON o.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  WHERE DATE(o.order_date) = '$date'";
        
        if($status) {
            $query .= " AND o.status = '$status'";
        }
        
        $query .= " ORDER BY FIELD(o.priority, 'stat', 'urgent', 'routine'), o.created_at DESC";
        
        $result = $db->query($query);
        $orders = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $row['patient_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $row['doctor_name'] = $row['doctor_fname'] . ' ' . $row['doctor_lname'];
                $orders[] = $row;
            }
        }
        
        echo json_encode($orders);
        exit;
    }
    
    // ==================== API: GET STAT ORDERS ====================
    public function apiStatOrders() {
        header('Content-Type: application/json');
        
        $db = $this->db;
        $query = "SELECT o.id, o.order_number, p.first_name, p.last_name,
                         (SELECT GROUP_CONCAT(t.test_name SEPARATOR ', ') 
                          FROM lab_test_order_items i 
                          JOIN lab_tests t ON i.test_id = t.id 
                          WHERE i.order_id = o.id LIMIT 3) as test_names,
                         DATE_FORMAT(o.created_at, '%h:%i %p') as order_time
                  FROM lab_test_orders o
                  JOIN patients p ON o.patient_id = p.id
                  WHERE o.priority = 'stat' AND o.status != 'completed'
                  ORDER BY o.created_at DESC
                  LIMIT 10";
        
        $result = $db->query($query);
        $statOrders = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $row['patient_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $statOrders[] = $row;
            }
        }
        
        echo json_encode($statOrders);
        exit;
    }
    
    // ==================== API: VALIDATE BARCODE ====================
    public function apiValidateBarcode() {
        header('Content-Type: application/json');
        
        $barcode = $this->db->real_escape_string($_POST['barcode']);
        $db = $this->db;
        
        $item = $db->query("SELECT i.*, o.order_number, p.first_name, p.last_name, t.test_name,
                                   i.status
                            FROM lab_test_order_items i
                            JOIN lab_test_orders o ON i.order_id = o.id
                            JOIN patients p ON o.patient_id = p.id
                            JOIN lab_tests t ON i.test_id = t.id
                            WHERE i.sample_barcode = '$barcode'")->fetch_assoc();
        
        if($item) {
            $item['patient_name'] = $item['first_name'] . ' ' . $item['last_name'];
            echo json_encode(['success' => true, 'data' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sample not found']);
        }
        exit;
    }
    
    // ==================== API: COLLECT BY BARCODE ====================
    public function apiCollectByBarcode() {
        header('Content-Type: application/json');
        
        $barcode = $this->db->real_escape_string($_POST['barcode']);
        $db = $this->db;
        
        $item = $db->query("SELECT id, order_id FROM lab_test_order_items WHERE sample_barcode = '$barcode'")->fetch_assoc();
        
        if($item) {
            $db->query("UPDATE lab_test_order_items 
                       SET status = 'sample_collected', 
                           sample_collected_at = NOW(), 
                           sample_collected_by = {$_SESSION['user_id']}
                       WHERE id = {$item['id']}");
            
            $pendingItems = $db->query("SELECT COUNT(*) as count FROM lab_test_order_items 
                                       WHERE order_id = {$item['order_id']} AND status != 'sample_collected'")->fetch_assoc()['count'];
            
            if($pendingItems == 0) {
                $db->query("UPDATE lab_test_orders SET status = 'sample_collected' WHERE id = {$item['order_id']}");
            }
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
        exit;
    }
    
    // ==================== API: GET DOCTORS ====================
    public function apiDoctors() {
        header('Content-Type: application/json');
        
        $db = $this->db;
        $doctors = $db->query("SELECT d.id, u.first_name, u.last_name, d.specialization
                              FROM doctors d
                              JOIN users u ON d.user_id = u.id
                              WHERE d.status = 'active'");
        $doctorList = [];
        if($doctors) {
            while($row = $doctors->fetch_assoc()) {
                $doctorList[] = [
                    'id' => $row['id'],
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'specialization' => $row['specialization']
                ];
            }
        }
        
        echo json_encode($doctorList);
        exit;
    }

    // ==================== SAMPLE COLLECTION PAGE ====================
    public function sampleCollection() {
        $this->checkAuth();
        
        $db = $this->db;
        
        // Get all samples with patient details
        $samples = $db->query("SELECT 
                                    i.*, 
                                    o.order_number, 
                                    o.priority,
                                    o.patient_id,
                                    p.first_name, 
                                    p.last_name, 
                                    p.phone,
                                    t.test_name, 
                                    t.specimen_type, 
                                    t.container_type,
                                    u.first_name as doctor_fname, 
                                    u.last_name as doctor_lname,
                                    (SELECT COUNT(*) FROM lab_test_order_items WHERE order_id = o.id) as total_tests
                                FROM lab_test_order_items i
                                JOIN lab_test_orders o ON i.order_id = o.id
                                JOIN patients p ON o.patient_id = p.id
                                JOIN lab_tests t ON i.test_id = t.id
                                JOIN doctors d ON o.doctor_id = d.id
                                JOIN users u ON d.user_id = u.id
                                WHERE i.status IN ('pending', 'sample_collected', 'processing')
                                ORDER BY o.patient_id, FIELD(o.priority, 'stat', 'urgent', 'routine'), i.created_at ASC");
        
        $sampleList = [];
        $patientIds = [];
        if($samples) {
            while($row = $samples->fetch_assoc()) {
                $row['patient_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $row['doctor_name'] = $row['doctor_fname'] . ' ' . $row['doctor_lname'];
                $row['priority'] = isset($row['priority']) && $row['priority'] != '' ? $row['priority'] : 'routine';
                $sampleList[] = $row;
                if(!in_array($row['patient_id'], $patientIds)) {
                    $patientIds[] = $row['patient_id'];
                }
            }
        }
        
        $collectedCount = 0;
        $result = $db->query("SELECT COUNT(*) as count FROM lab_test_order_items WHERE status = 'sample_collected'");
        if($result) $collectedCount = $result->fetch_assoc()['count'];
        
        $pendingCount = 0;
        $result = $db->query("SELECT COUNT(*) as count FROM lab_test_order_items WHERE status = 'pending'");
        if($result) $pendingCount = $result->fetch_assoc()['count'];
        
        $todayCollected = 0;
        $result = $db->query("SELECT COUNT(*) as count FROM lab_test_order_items WHERE status = 'sample_collected' AND DATE(created_at) = CURDATE()");
        if($result) $todayCollected = $result->fetch_assoc()['count'];
        
        $totalPatients = count($patientIds);
        
        $this->view('lab/sample-collection', [
            'samples' => $sampleList,
            'collectedCount' => $collectedCount,
            'pendingCount' => $pendingCount,
            'todayCollected' => $todayCollected,
            'totalPatients' => $totalPatients
        ], 'Sample Collection');
    }

    // ==================== UPDATE SAMPLE STATUS ====================
    public function updateSampleStatus() {
        header('Content-Type: application/json');
        
        $itemId = (int)$_POST['item_id'];
        $action = $this->db->real_escape_string($_POST['action']);
        $location = $this->db->real_escape_string($_POST['location'] ?? '');
        $notes = $this->db->real_escape_string($_POST['notes'] ?? '');
        $db = $this->db;
        
        $newStatus = '';
        $updateFields = '';
        
        switch($action) {
            case 'collect':
                $newStatus = 'sample_collected';
                $updateFields = "sample_collected_at = NOW(), sample_collected_by = {$_SESSION['user_id']}";
                break;
            case 'receive':
                $newStatus = 'processing';
                $updateFields = "received_at = NOW(), received_by = {$_SESSION['user_id']}";
                break;
            case 'process':
                $newStatus = 'processing';
                $updateFields = "processed_at = NOW(), processed_by = {$_SESSION['user_id']}";
                break;
            case 'complete':
                $newStatus = 'completed';
                $updateFields = "completed_at = NOW(), completed_by = {$_SESSION['user_id']}";
                
                $orderId = $db->query("SELECT order_id FROM lab_test_order_items WHERE id = $itemId")->fetch_assoc()['order_id'];
                $pendingItems = $db->query("SELECT COUNT(*) as count FROM lab_test_order_items 
                                           WHERE order_id = $orderId AND status != 'completed'")->fetch_assoc()['count'];
                if($pendingItems == 0) {
                    $db->query("UPDATE lab_test_orders SET status = 'completed', completed_at = NOW() WHERE id = $orderId");
                    
                    $reportNumber = 'RPT' . date('Ymd') . rand(1000, 9999);
                    $patientId = $db->query("SELECT patient_id FROM lab_test_orders WHERE id = $orderId")->fetch_assoc()['patient_id'];
                    $db->query("INSERT INTO lab_reports (report_number, order_id, patient_id, report_date, generated_by) 
                               VALUES ('$reportNumber', $orderId, $patientId, CURDATE(), {$_SESSION['user_id']})");
                }
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }
        
        if($newStatus) {
            $query = "UPDATE lab_test_order_items SET status = '$newStatus'";
            if($updateFields) $query .= ", $updateFields";
            $query .= " WHERE id = $itemId";
            $db->query($query);
        }
        
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        exit;
    }

    // ==================== GENERATE SINGLE BARCODE ====================
    public function generateSingleBarcode() {
        header('Content-Type: application/json');
        
        $itemId = (int)$_POST['item_id'];
        $db = $this->db;
        
        $sample = $db->query("SELECT i.*, o.order_number, p.first_name, p.last_name, t.test_name
                             FROM lab_test_order_items i
                             JOIN lab_test_orders o ON i.order_id = o.id
                             JOIN patients p ON o.patient_id = p.id
                             JOIN lab_tests t ON i.test_id = t.id
                             WHERE i.id = $itemId")->fetch_assoc();
        
        if(!$sample) {
            echo json_encode(['success' => false, 'message' => 'Sample not found']);
            exit;
        }
        
        $sample['patient_name'] = $sample['first_name'] . ' ' . $sample['last_name'];
        
        if(!$sample['sample_barcode']) {
            $barcode = 'SMP' . date('Ymd') . rand(10000, 99999);
            $db->query("UPDATE lab_test_order_items SET sample_barcode = '$barcode' WHERE id = $itemId");
            $sample['sample_barcode'] = $barcode;
        }
        
        echo json_encode(['success' => true, 'sample' => $sample]);
        exit;
    }

    // ==================== BULK SAMPLE COLLECTION ====================
    public function bulkCollectSamples() {
        header('Content-Type: application/json');
        
        $itemIds = json_decode($_POST['item_ids'], true);
        $location = $this->db->real_escape_string($_POST['location'] ?? '');
        $db = $this->db;
        
        if(empty($itemIds)) {
            echo json_encode(['success' => false, 'message' => 'No samples selected']);
            exit;
        }
        
        $collected = 0;
        foreach($itemIds as $itemId) {
            $itemId = (int)$itemId;
            
            $db->query("UPDATE lab_test_order_items 
                       SET status = 'sample_collected', 
                           sample_collected_at = NOW(), 
                           sample_collected_by = {$_SESSION['user_id']}
                       WHERE id = $itemId");
            
            $orderId = $db->query("SELECT order_id FROM lab_test_order_items WHERE id = $itemId")->fetch_assoc()['order_id'];
            $pendingItems = $db->query("SELECT COUNT(*) as count FROM lab_test_order_items 
                                       WHERE order_id = $orderId AND status = 'pending'")->fetch_assoc()['count'];
            if($pendingItems == 0) {
                $db->query("UPDATE lab_test_orders SET status = 'sample_collected' WHERE id = $orderId");
            }
            
            $collected++;
        }
        
        echo json_encode(['success' => true, 'message' => "$collected samples collected successfully"]);
        exit;
    }

    // ==================== GET SAMPLE DETAILS ====================
    public function getSampleDetails() {
        header('Content-Type: application/json');
        
        $itemId = (int)$_GET['item_id'];
        $db = $this->db;
        
        $sample = $db->query("SELECT i.*, o.order_number, p.first_name, p.last_name, t.test_name
                             FROM lab_test_order_items i
                             JOIN lab_test_orders o ON i.order_id = o.id
                             JOIN patients p ON o.patient_id = p.id
                             JOIN lab_tests t ON i.test_id = t.id
                             WHERE i.id = $itemId")->fetch_assoc();
        
        if($sample) {
            $sample['patient_name'] = $sample['first_name'] . ' ' . $sample['last_name'];
            echo json_encode(['success' => true, 'sample' => $sample]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Sample not found']);
        }
        exit;
    }

    // ==================== VIEW REPORT AJAX ====================
    public function viewReportAjax() {
        header('Content-Type: application/json');
        
        $id = (int)$_GET['id'];
        $db = $this->db;
        
        $report = $db->query("SELECT r.*, p.first_name, p.last_name, p.phone, p.gender, p.date_of_birth,
                             o.order_number, o.order_date, o.clinical_diagnosis,
                             u.first_name as doctor_fname, u.last_name as doctor_lname
                             FROM lab_reports r
                             JOIN patients p ON r.patient_id = p.id
                             JOIN lab_test_orders o ON r.order_id = o.id
                             JOIN doctors d ON o.doctor_id = d.id
                             JOIN users u ON d.user_id = u.id
                             WHERE r.id = $id")->fetch_assoc();
        
        if(!$report) {
            echo json_encode(['success' => false, 'message' => 'Report not found']);
            exit;
        }
        
        $report['patient_name'] = $report['first_name'] . ' ' . $report['last_name'];
        $report['doctor_name'] = $report['doctor_fname'] . ' ' . $report['doctor_lname'];
        
        $results = $db->query("SELECT i.*, t.test_name, t.unit, t.normal_range
                              FROM lab_test_order_items i
                              JOIN lab_tests t ON i.test_id = t.id
                              JOIN lab_test_orders o ON i.order_id = o.id
                              WHERE o.id = {$report['order_id']}");
        $resultList = [];
        if($results) {
            while($row = $results->fetch_assoc()) {
                $resultList[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'report' => $report, 'results' => $resultList]);
        exit;
    }

    // ==================== PRINT REPORT ====================
    public function printReport() {
        $id = (int)$_GET['id'];
        $db = $this->db;
        
        $report = $db->query("SELECT r.*, p.first_name, p.last_name, p.phone, p.gender, p.date_of_birth,
                             o.order_number, o.order_date, o.clinical_diagnosis,
                             u.first_name as doctor_fname, u.last_name as doctor_lname
                             FROM lab_reports r
                             JOIN patients p ON r.patient_id = p.id
                             JOIN lab_test_orders o ON r.order_id = o.id
                             JOIN doctors d ON o.doctor_id = d.id
                             JOIN users u ON d.user_id = u.id
                             WHERE r.id = $id")->fetch_assoc();
        
        if(!$report) {
            echo "Report not found";
            exit;
        }
        
        $report['patient_name'] = $report['first_name'] . ' ' . $report['last_name'];
        $report['doctor_name'] = $report['doctor_fname'] . ' ' . $report['doctor_lname'];
        
        $results = $db->query("SELECT i.*, t.test_name, t.unit, t.normal_range
                              FROM lab_test_order_items i
                              JOIN lab_tests t ON i.test_id = t.id
                              JOIN lab_test_orders o ON i.order_id = o.id
                              WHERE o.id = {$report['order_id']}");
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Lab Report - <?php echo $report['report_number']; ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 30px; font-family: Arial, sans-serif; }
                .report-container { max-width: 800px; margin: 0 auto; }
                .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 15px; margin-bottom: 20px; }
                .company { font-size: 24px; font-weight: bold; color: #10b981; }
                .abnormal { color: #ef4444; font-weight: bold; }
                @media print {
                    body { padding: 0; margin: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class="report-container">
                <div class="header">
                    <div class="company">UNIDIA HOSPITAL</div>
                    <div>Laboratory Report</div>
                    <div class="text-muted">Report #: <?php echo $report['report_number']; ?></div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Patient Name:</strong> <?php echo $report['patient_name']; ?></p>
                        <p><strong>Gender:</strong> <?php echo ucfirst($report['gender']); ?></p>
                        <p><strong>Date of Birth:</strong> <?php echo date('d M Y', strtotime($report['date_of_birth'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Order #:</strong> <?php echo $report['order_number']; ?></p>
                        <p><strong>Report Date:</strong> <?php echo date('d M Y', strtotime($report['report_date'])); ?></p>
                        <p><strong>Referring Doctor:</strong> Dr. <?php echo $report['doctor_name']; ?></p>
                    </div>
                </div>
                
                <?php if($report['clinical_diagnosis']): ?>
                    <div class="alert alert-info"><strong>Clinical Diagnosis:</strong> <?php echo $report['clinical_diagnosis']; ?></div>
                <?php endif; ?>
                
                <h5 class="mt-4">Test Results</h5>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr><th>Test Name</th><th>Result</th><th>Unit</th><th>Normal Range</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php while($result = $results->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $result['test_name']; ?></strong></td>
                            <td class="<?php echo $result['is_abnormal'] ? 'abnormal' : ''; ?>">
                                <?php echo $result['result_value'] ?: 'Pending'; ?>
                            </td>
                            <td><?php echo $result['unit']; ?></td>
                            <td><small><?php echo $result['normal_range']; ?></small></td>
                            <td>
                                <?php if($result['is_abnormal']): ?>
                                    <span class="badge bg-danger">Abnormal</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Normal</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div class="text-center mt-4 text-muted">
                    <small>This is a computer-generated report. No signature required.</small>
                </div>
                
                <div class="text-center mt-4 no-print">
                    <button onclick="window.print()" class="btn btn-primary">Print</button>
                    <button onclick="window.close()" class="btn btn-secondary">Close</button>
                </div>
            </div>
            <script>
                window.onload = function() {
                    setTimeout(function() { window.print(); }, 500);
                }
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    // ==================== API: GET LAB REPORTS LIST ====================
    public function apiLabReportsList() {
        header('Content-Type: application/json');
        
        $dateRange = isset($_GET['date_range']) ? $this->db->real_escape_string($_GET['date_range']) : 'month';
        $fromDate = isset($_GET['from_date']) ? $this->db->real_escape_string($_GET['from_date']) : '';
        $toDate = isset($_GET['to_date']) ? $this->db->real_escape_string($_GET['to_date']) : '';
        $status = isset($_GET['status']) ? $this->db->real_escape_string($_GET['status']) : 'all';
        $search = isset($_GET['search']) ? $this->db->real_escape_string($_GET['search']) : '';
        $db = $this->db;
        
        $dateCondition = "";
        switch($dateRange) {
            case 'today':
                $dateCondition = "DATE(r.report_date) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "YEARWEEK(r.report_date) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $dateCondition = "MONTH(r.report_date) = MONTH(CURDATE()) AND YEAR(r.report_date) = YEAR(CURDATE())";
                break;
            case 'custom':
                if($fromDate && $toDate) {
                    $dateCondition = "r.report_date BETWEEN '$fromDate' AND '$toDate'";
                }
                break;
        }
        
        $query = "SELECT r.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.phone,
                         o.order_number
                  FROM lab_reports r
                  JOIN patients p ON r.patient_id = p.id
                  JOIN lab_test_orders o ON r.order_id = o.id";
        
        if($dateCondition) {
            $query .= " WHERE " . $dateCondition;
        }
        
        if($status != 'all') {
            $query .= ($dateCondition ? " AND" : " WHERE") . " r.is_delivered = " . ($status == '1' ? 1 : 0);
        }
        
        if($search) {
            $query .= ($dateCondition || $status != 'all' ? " AND" : " WHERE") . " (CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search%' OR r.report_number LIKE '%$search%')";
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        $result = $db->query($query);
        $reports = [];
        
        if($result) {
            while($row = $result->fetch_assoc()) {
                $row['report_date'] = date('d M Y', strtotime($row['report_date']));
                $reports[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'reports' => $reports]);
        exit;
    }

    // ==================== EXPORT REPORTS TO CSV ====================
    public function exportReports() {
        $dateRange = isset($_GET['date_range']) ? $this->db->real_escape_string($_GET['date_range']) : 'month';
        $fromDate = isset($_GET['from_date']) ? $this->db->real_escape_string($_GET['from_date']) : '';
        $toDate = isset($_GET['to_date']) ? $this->db->real_escape_string($_GET['to_date']) : '';
        $status = isset($_GET['status']) ? $this->db->real_escape_string($_GET['status']) : 'all';
        $db = $this->db;
        
        $dateCondition = "";
        switch($dateRange) {
            case 'today':
                $dateCondition = "DATE(r.report_date) = CURDATE()";
                break;
            case 'week':
                $dateCondition = "YEARWEEK(r.report_date) = YEARWEEK(CURDATE())";
                break;
            case 'month':
                $dateCondition = "MONTH(r.report_date) = MONTH(CURDATE()) AND YEAR(r.report_date) = YEAR(CURDATE())";
                break;
            case 'custom':
                if($fromDate && $toDate) {
                    $dateCondition = "r.report_date BETWEEN '$fromDate' AND '$toDate'";
                }
                break;
        }
        
        $query = "SELECT r.report_number, o.order_number, p.first_name, p.last_name, p.phone, 
                         r.report_date, 
                         CASE WHEN r.is_delivered = 1 THEN 'Delivered' ELSE 'Pending' END as status
                  FROM lab_reports r
                  JOIN patients p ON r.patient_id = p.id
                  JOIN lab_test_orders o ON r.order_id = o.id";
        
        if($dateCondition) {
            $query .= " WHERE " . $dateCondition;
        }
        
        if($status != 'all') {
            $query .= ($dateCondition ? " AND" : " WHERE") . " r.is_delivered = " . ($status == '1' ? 1 : 0);
        }
        
        $query .= " ORDER BY r.created_at DESC";
        
        $result = $db->query($query);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="lab_reports_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Report #', 'Order #', 'Patient Name', 'Phone', 'Report Date', 'Status']);
        
        while($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['report_number'],
                $row['order_number'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['phone'],
                $row['report_date'],
                $row['status']
            ]);
        }
        fclose($output);
        exit;
    }

    // ==================== GENERATE INVOICE ====================
    public function generateInvoice($orderId) {
        $this->checkAuth();
        
        $orderId = (int)$orderId;
        $db = $this->db;
        
        $bill = $db->query("SELECT * FROM bills WHERE reference_type = 'lab_order' AND reference_id = $orderId")->fetch_assoc();
        
        if(!$bill) {
            $_SESSION['error'] = "Bill not found for this order";
            $this->redirect('/lab/orders');
            return;
        }
        
        $items = $db->query("SELECT i.*, t.test_name, t.price
                            FROM lab_test_order_items i
                            JOIN lab_tests t ON i.test_id = t.id
                            WHERE i.order_id = $orderId");
        
        $patient = $db->query("SELECT p.first_name, p.last_name, p.phone, p.address
                              FROM lab_test_orders o
                              JOIN patients p ON o.patient_id = p.id
                              WHERE o.id = $orderId")->fetch_assoc();
        
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Lab Order Invoice - <?php echo $bill['bill_number']; ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                .invoice { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
                .header { text-align: center; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #f5f5f5; }
                .text-right { text-align: right; }
                @media print { .no-print { display: none; } }
            </style>
        </head>
        <body>
            <div class="invoice">
                <div class="header">
                    <h2>UNIDIA HOSPITAL</h2>
                    <p>123, Hospital Road, Dhaka | Phone: +880 1234 567890</p>
                    <h3>LABORATORY INVOICE</h3>
                </div>
                <div><strong>Bill No:</strong> <?php echo $bill['bill_number']; ?></div>
                <div><strong>Date:</strong> <?php echo date('d M Y', strtotime($bill['bill_date'])); ?></div>
                <div><strong>Patient:</strong> <?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></div>
                <div><strong>Phone:</strong> <?php echo $patient['phone']; ?></div>
                <hr>
                <table>
                    <thead>
                        <tr><th>#</th><th>Test Name</th><th>Price (৳)</th></tr>
                    </thead>
                    <tbody>
                    <?php $counter = 1; $total = 0; while($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo $item['test_name']; ?></td>
                            <td class="text-right"><?php echo number_format($item['price'], 2); ?></td>
                        </tr>
                        <?php $total += $item['price']; ?>
                    <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="2" class="text-right"><strong>Subtotal:</strong></td><td class="text-right"><?php echo number_format($total, 2); ?></td></tr>
                        <tr><td colspan="2" class="text-right"><strong>Tax (5%):</strong></td><td class="text-right"><?php echo number_format($bill['tax_amount'], 2); ?></td></tr>
                        <tr><td colspan="2" class="text-right"><strong>Total:</strong></td><td class="text-right"><strong><?php echo number_format($bill['total_amount'], 2); ?></strong></td></tr>
                    </tfoot>
                </table>
                <hr>
                <div class="text-center">
                    <p>Thank you for choosing UNIDIA Hospital</p>
                </div>
                <div class="no-print text-center" style="margin-top: 20px;">
                    <button onclick="window.print()" class="btn btn-primary">Print</button>
                    <button onclick="window.close()" class="btn btn-secondary">Close</button>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // ==================== API: GET LAB ORDERS LIST ====================
    public function apiLabOrdersList() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = 15;
            $offset = ($page - 1) * $limit;
            
            $dateFrom = isset($_GET['date_from']) ? $this->db->real_escape_string($_GET['date_from']) : '';
            $dateTo = isset($_GET['date_to']) ? $this->db->real_escape_string($_GET['date_to']) : '';
            $status = isset($_GET['status']) ? $this->db->real_escape_string($_GET['status']) : '';
            $priority = isset($_GET['priority']) ? $this->db->real_escape_string($_GET['priority']) : '';
            $search = isset($_GET['search']) ? $this->db->real_escape_string($_GET['search']) : '';
            $db = $this->db;
            
            $where = "WHERE 1=1";
            if($dateFrom) $where .= " AND DATE(o.order_date) >= '$dateFrom'";
            if($dateTo) $where .= " AND DATE(o.order_date) <= '$dateTo'";
            if($status) $where .= " AND o.status = '$status'";
            if($priority) $where .= " AND o.priority = '$priority'";
            if($search) {
                $where .= " AND (o.order_number LIKE '%$search%' 
                            OR CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search%'
                            OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$search%')";
            }
            
            $countQuery = "SELECT COUNT(*) as total 
                           FROM lab_test_orders o
                           JOIN patients p ON o.patient_id = p.id
                           JOIN doctors d ON o.doctor_id = d.id
                           JOIN users u ON d.user_id = u.id
                           $where";
            $countResult = $db->query($countQuery);
            
            if(!$countResult) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $db->error]);
                exit;
            }
            
            $totalRecords = $countResult->fetch_assoc()['total'];
            $totalPages = ceil($totalRecords / $limit);
            
            $query = "SELECT o.id, o.order_number, o.order_date, o.status as order_status, o.priority, o.created_at,
                             CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                             p.patient_code,
                             p.phone,
                             CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                             (SELECT COUNT(*) FROM lab_test_order_items WHERE order_id = o.id) as test_count,
                             (SELECT GROUP_CONCAT(DISTINCT t.test_name SEPARATOR ', ') 
                              FROM lab_test_order_items i 
                              JOIN lab_tests t ON i.test_id = t.id 
                              WHERE i.order_id = o.id) as test_names,
                             (SELECT id FROM bills WHERE reference_type = 'lab_order' AND reference_id = o.id LIMIT 1) as bill_id,
                             (SELECT bill_number FROM bills WHERE reference_type = 'lab_order' AND reference_id = o.id LIMIT 1) as bill_number,
                             (SELECT total_amount FROM bills WHERE reference_type = 'lab_order' AND reference_id = o.id LIMIT 1) as bill_amount,
                             (SELECT discount_amount FROM bills WHERE reference_type = 'lab_order' AND reference_id = o.id LIMIT 1) as discount_amount,
                             (SELECT payment_status FROM bills WHERE reference_type = 'lab_order' AND reference_id = o.id LIMIT 1) as payment_status
                      FROM lab_test_orders o
                      JOIN patients p ON o.patient_id = p.id
                      JOIN doctors d ON o.doctor_id = d.id
                      JOIN users u ON d.user_id = u.id
                      $where
                      ORDER BY FIELD(o.priority, 'stat', 'urgent', 'routine'), o.created_at DESC
                      LIMIT $offset, $limit";
            
            $result = $db->query($query);
            
            if(!$result) {
                echo json_encode(['success' => false, 'message' => 'Query error: ' . $db->error]);
                exit;
            }
            
            $orders = [];
            while($row = $result->fetch_assoc()) {
                $row['status'] = $row['order_status'];
                
                // If test_names is empty but test_count > 0, try to fetch from bill_items
                if(empty($row['test_names']) && $row['test_count'] > 0) {
                    $billTestQuery = "SELECT GROUP_CONCAT(DISTINCT bi.description SEPARATOR ', ') as names 
                                      FROM bills b
                                      JOIN bill_items bi ON b.id = bi.bill_id
                                      WHERE b.reference_type = 'lab_order' AND b.reference_id = " . (int)$row['id'];
                    $billTestResult = $db->query($billTestQuery);
                    if($billTestResult && $billTestResult->num_rows > 0) {
                        $billTestRow = $billTestResult->fetch_assoc();
                        if(!empty($billTestRow['names'])) {
                            $row['test_names'] = $billTestRow['names'];
                            $testArray = explode(', ', $billTestRow['names']);
                            $row['test_count'] = count($testArray);
                        }
                    }
                }
                $orders[] = $row;
            }
            
            $statsQuery = "SELECT 
                            COUNT(*) as total_orders,
                            SUM(CASE WHEN o.status IN ('ordered', 'sample_collected', 'processing') THEN 1 ELSE 0 END) as pending_orders,
                            SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                            COALESCE((SELECT SUM(total_amount) FROM bills WHERE reference_type = 'lab_order' AND reference_id IN (SELECT id FROM lab_test_orders)), 0) as total_amount
                          FROM lab_test_orders o
                          JOIN patients p ON o.patient_id = p.id
                          $where";
            
            $statsResult = $db->query($statsQuery);
            $stats = ['total_orders' => 0, 'pending_orders' => 0, 'completed_orders' => 0, 'total_amount' => 0];
            
            if($statsResult) {
                $stats = $statsResult->fetch_assoc();
            }
            
            echo json_encode([
                'success' => true,
                'orders' => $orders,
                'stats' => $stats,
                'total' => $totalRecords,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==================== API: GET LAB ORDER DETAILS ====================
    public function apiLabOrderDetails($id) {
        header('Content-Type: application/json');
        
        $id = (int)$id;
        $db = $this->db;
        
        if($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
            exit;
        }
        
        $orderQuery = "SELECT o.*, 
                              CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                              p.patient_code,
                              p.phone,
                              CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                              us.first_name as ordered_by_name
                       FROM lab_test_orders o
                       JOIN patients p ON o.patient_id = p.id
                       JOIN doctors d ON o.doctor_id = d.id
                       JOIN users u ON d.user_id = u.id
                       JOIN users us ON o.ordered_by = us.id
                       WHERE o.id = $id";
        
        $orderResult = $db->query($orderQuery);
        
        if(!$orderResult || $orderResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
        
        $order = $orderResult->fetch_assoc();
        
        // Get test items from lab_test_order_items
        $itemsQuery = "SELECT i.id, i.test_id, i.sample_barcode, i.status as item_status, i.result_value, 
                              i.is_abnormal, i.remarks, i.sample_collected_at,
                              t.test_name, t.price, t.unit, t.normal_range, t.specimen_type,
                              c.name as category_name
                       FROM lab_test_order_items i
                       JOIN lab_tests t ON i.test_id = t.id
                       LEFT JOIN lab_test_categories c ON t.category_id = c.id
                       WHERE i.order_id = $id";
        
        $itemsResult = $db->query($itemsQuery);
        $items = [];
        
        if($itemsResult) {
            while($row = $itemsResult->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        // If no items found in lab_test_order_items, get from bill_items
        if(empty($items)) {
            $billItemsQuery = "SELECT 
                                  bi.id,
                                  bi.description as test_name,
                                  bi.unit_price as price,
                                  bi.quantity,
                                  bi.total_amount,
                                  'bill_item' as source
                               FROM bills b
                               JOIN bill_items bi ON b.id = bi.bill_id
                               WHERE b.reference_type = 'lab_order' AND b.reference_id = $id
                               AND bi.item_type = 'lab_test'
                               ORDER BY bi.id ASC";
            
            $billItemsResult = $db->query($billItemsQuery);
            if($billItemsResult && $billItemsResult->num_rows > 0) {
                while($row = $billItemsResult->fetch_assoc()) {
                    $items[] = [
                        'id' => $row['id'],
                        'test_id' => null,
                        'sample_barcode' => null,
                        'item_status' => 'pending',
                        'result_value' => null,
                        'is_abnormal' => 0,
                        'remarks' => null,
                        'sample_collected_at' => null,
                        'test_name' => $row['test_name'],
                        'price' => $row['price'],
                        'unit' => null,
                        'normal_range' => null,
                        'specimen_type' => null,
                        'category_name' => 'Lab Test',
                        'source' => 'bill_item'
                    ];
                }
            }
        }
        
        $billQuery = "SELECT * FROM bills WHERE reference_type = 'lab_order' AND reference_id = $id LIMIT 1";
        $billResult = $db->query($billQuery);
        $bill = $billResult ? $billResult->fetch_assoc() : null;
        
        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items,
            'bill' => $bill
        ]);
        exit;
    }

    // ==================== EXPORT ORDERS TO CSV ====================
    public function exportOrders() {
        $dateFrom = isset($_GET['date_from']) ? $this->db->real_escape_string($_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? $this->db->real_escape_string($_GET['date_to']) : '';
        $status = isset($_GET['status']) ? $this->db->real_escape_string($_GET['status']) : '';
        $priority = isset($_GET['priority']) ? $this->db->real_escape_string($_GET['priority']) : '';
        $search = isset($_GET['search']) ? $this->db->real_escape_string($_GET['search']) : '';
        $db = $this->db;
        
        $where = "WHERE 1=1";
        if($dateFrom) $where .= " AND DATE(o.order_date) >= '$dateFrom'";
        if($dateTo) $where .= " AND DATE(o.order_date) <= '$dateTo'";
        if($status) $where .= " AND o.status = '$status'";
        if($priority) $where .= " AND o.priority = '$priority'";
        if($search) {
            $where .= " AND (o.order_number LIKE '%$search%' 
                        OR CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search%')";
        }
        
        $query = "SELECT o.order_number, o.order_date, o.status as order_status, o.priority,
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                         p.patient_code, p.phone,
                         CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                         (SELECT COUNT(*) FROM lab_test_order_items WHERE order_id = o.id) as test_count,
                         (SELECT total_amount FROM bills WHERE reference_type = 'lab_order' AND reference_id = o.id LIMIT 1) as bill_amount,
                         (SELECT payment_status FROM bills WHERE reference_type = 'lab_order' AND reference_id = o.id LIMIT 1) as payment_status
                  FROM lab_test_orders o
                  JOIN patients p ON o.patient_id = p.id
                  JOIN doctors d ON o.doctor_id = d.id
                  JOIN users u ON d.user_id = u.id
                  $where
                  ORDER BY o.created_at DESC";
        
        $result = $db->query($query);
        
        if(!$result) {
            $_SESSION['error'] = "No data to export";
            $this->redirect('/lab/orders');
            return;
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="lab_orders_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Order #', 'Date', 'Patient Name', 'Patient Code', 'Phone', 'Doctor', 'Tests', 'Priority', 'Status', 'Bill Amount', 'Payment Status']);
        
        while($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['order_number'],
                $row['order_date'],
                $row['patient_name'],
                $row['patient_code'],
                $row['phone'],
                $row['doctor_name'],
                $row['test_count'],
                strtoupper($row['priority']),
                strtoupper(str_replace('_', ' ', $row['order_status'])),
                $row['bill_amount'] ? '৳ ' . number_format($row['bill_amount'], 2) : '-',
                strtoupper($row['payment_status'] ?? 'N/A')
            ]);
        }
        fclose($output);
        exit;
    }

    // ==================== PRINT BARCODE ====================
    public function printBarcode() {
        $itemId = (int)$_GET['item_id'];
        $db = $this->db;
        
        $sample = $db->query("SELECT i.*, o.order_number, p.first_name, p.last_name, t.test_name
                             FROM lab_test_order_items i
                             JOIN lab_test_orders o ON i.order_id = o.id
                             JOIN patients p ON o.patient_id = p.id
                             JOIN lab_tests t ON i.test_id = t.id
                             WHERE i.id = $itemId")->fetch_assoc();
        
        if(!$sample) {
            echo "Sample not found";
            exit;
        }
        
        $sample['patient_name'] = $sample['first_name'] . ' ' . $sample['last_name'];
        
        // Simple barcode generation (without external library)
        $barcodeValue = $sample['sample_barcode'] ?: 'SMP' . date('Ymd') . rand(10000, 99999);
        if(empty($sample['sample_barcode'])) {
            $db->query("UPDATE lab_test_order_items SET sample_barcode = '$barcodeValue' WHERE id = $itemId");
        }
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sample Barcode - <?php echo $barcodeValue; ?></title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                @page { size: 80mm auto; margin: 10px; }
                body { 
                    font-family: Arial, sans-serif; 
                    width: 80mm; 
                    margin: 0 auto; 
                    padding: 20px 15px; 
                    text-align: center; 
                    background: white;
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .barcode-container {
                    width: 100%;
                    padding: 20px 15px;
                    background: white;
                    border: 2px dashed #e2e8f0;
                    border-radius: 10px;
                }
                .header {
                    border-bottom: 2px solid #10b981;
                    padding-bottom: 12px;
                    margin-bottom: 15px;
                }
                .header .title {
                    font-size: 18px;
                    font-weight: 800;
                    color: #1a56db;
                    letter-spacing: 1px;
                }
                .header .subtitle {
                    font-size: 11px;
                    color: #64748b;
                    margin-top: 2px;
                }
                .barcode-box {
                    padding: 15px 10px;
                    margin: 10px 0 15px;
                    background: white;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                }
                .barcode-code {
                    font-family: 'Courier New', monospace;
                    font-size: 14px;
                    font-weight: 600;
                    letter-spacing: 2px;
                    color: #1e293b;
                    margin-top: 8px;
                }
                .info-grid {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 4px 15px;
                    text-align: left;
                    margin: 10px 0;
                    font-size: 11px;
                }
                .info-grid .label {
                    color: #94a3b8;
                    font-weight: 600;
                }
                .info-grid .value {
                    color: #1e293b;
                    font-weight: 500;
                }
                .footer {
                    margin-top: 15px;
                    padding-top: 12px;
                    border-top: 1px dashed #e2e8f0;
                    font-size: 9px;
                    color: #94a3b8;
                }
                .no-print {
                    margin-top: 15px;
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                }
                .no-print button {
                    padding: 8px 20px;
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 12px;
                    font-weight: 500;
                    transition: all 0.2s;
                }
                .no-print .btn-print {
                    background: #10b981;
                    color: white;
                }
                .no-print .btn-print:hover {
                    background: #059669;
                }
                .no-print .btn-close {
                    background: #e5e7eb;
                    color: #374151;
                }
                .no-print .btn-close:hover {
                    background: #d1d5db;
                }
                @media print {
                    body { padding: 0; margin: 0; display: block; }
                    .barcode-container { border: none; border-radius: 0; padding: 15px; }
                    .no-print { display: none !important; }
                    .barcode-box { border: 1px solid #ddd; }
                }
            </style>
        </head>
        <body>
            <div class="barcode-container">
                <div class="header">
                    <div class="subtitle">Laboratory Sample Barcode</div>
                </div>
                
                <div class="barcode-box">
                    <div style="font-size:40px;letter-spacing:3px;font-family:'Courier New',monospace;">
                        <?php echo $barcodeValue; ?>
                    </div>
                    <div class="barcode-code"><?php echo $barcodeValue; ?></div>
                </div>
                
                <div class="info-grid">
                    <div><span class="label">Patient:</span> <span class="value"><?php echo htmlspecialchars($sample['patient_name']); ?></span></div>
                    <div><span class="label">Test:</span> <span class="value"><?php echo htmlspecialchars($sample['test_name']); ?></span></div>
                    <div><span class="label">Order #:</span> <span class="value"><?php echo $sample['order_number']; ?></span></div>
                    <div><span class="label">Specimen:</span> <span class="value"><?php echo $sample['specimen_type'] ?? 'N/A'; ?></span></div>
                </div>
                
                <div class="footer">
                    Collection Date: <?php echo date('d/m/Y'); ?>
                </div>
                
                <div class="no-print">
                    <button onclick="window.print()" class="btn-print">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="window.close()" class="btn-close">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
            
            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.print();
                    }, 500);
                };
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    // ==================== PATIENT LAB TESTS ====================
    public function patientLabTests() {
        $this->checkAuth();
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        $dateFrom = isset($_GET['date_from']) ? $this->db->real_escape_string($_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? $this->db->real_escape_string($_GET['date_to']) : '';
        $db = $this->db;
        
        // Get all patients for filter dropdown
        $patients = $db->query("SELECT id, patient_code, first_name, last_name, phone 
                              FROM patients WHERE status = 'active' 
                              ORDER BY first_name ASC");
        $patientList = [];
        if($patients) {
            while($row = $patients->fetch_assoc()) {
                $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $patientList[] = $row;
            }
        }
        
        // Get unique test names for columns
        $testNamesQuery = "SELECT DISTINCT t.test_name, t.id 
                           FROM lab_tests t
                           JOIN lab_test_order_items i ON i.test_id = t.id
                           JOIN lab_test_orders o ON i.order_id = o.id
                           WHERE i.status = 'completed'";
        if($patientId > 0) {
            $testNamesQuery .= " AND o.patient_id = $patientId";
        }
        $testNamesQuery .= " ORDER BY t.test_name ASC";
        
        $testNamesResult = $db->query($testNamesQuery);
        $testNames = [];
        if($testNamesResult) {
            while($row = $testNamesResult->fetch_assoc()) {
                $testNames[] = $row['test_name'];
            }
        }
        
        // Get results data
        $results = [];
        if($patientId > 0) {
            $query = "SELECT 
                        DATE(o.order_date) as test_date,
                        o.order_number,
                        DATE_FORMAT(o.order_date, '%d-%m-%Y') as formatted_date,
                        t.test_name,
                        i.result_value,
                        i.is_abnormal,
                        t.unit,
                        t.normal_range
                      FROM lab_test_orders o
                      JOIN lab_test_order_items i ON o.id = i.order_id
                      JOIN lab_tests t ON i.test_id = t.id
                      WHERE o.patient_id = $patientId 
                        AND i.status = 'completed'
                        AND i.result_value IS NOT NULL
                        AND i.result_value != ''";
            
            if($dateFrom) $query .= " AND DATE(o.order_date) >= '$dateFrom'";
            if($dateTo) $query .= " AND DATE(o.order_date) <= '$dateTo'";
            
            $query .= " ORDER BY o.order_date DESC, t.test_name ASC";
            
            $result = $db->query($query);
            
            if($result) {
                while($row = $result->fetch_assoc()) {
                    $dateKey = $row['test_date'];
                    if(!isset($results[$dateKey])) {
                        $results[$dateKey] = [
                            'date' => $row['formatted_date'],
                            'order_number' => $row['order_number'],
                            'tests' => []
                        ];
                    }
                    $results[$dateKey]['tests'][$row['test_name']] = [
                        'value' => $row['result_value'],
                        'is_abnormal' => $row['is_abnormal'],
                        'unit' => $row['unit'],
                        'normal_range' => $row['normal_range']
                    ];
                }
            }
        }
        
        $this->view('lab/patient-lab-tests', [
            'patients' => $patientList,
            'patientId' => $patientId,
            'testNames' => $testNames,
            'results' => $results,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo
        ], 'Patient Lab Tests');
    }

    // ==================== EXPORT PATIENT LAB TESTS ====================
    public function exportPatientTests() {
        $this->checkAuth();
        
        $patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
        $dateFrom = isset($_GET['date_from']) ? $this->db->real_escape_string($_GET['date_from']) : '';
        $dateTo = isset($_GET['date_to']) ? $this->db->real_escape_string($_GET['date_to']) : '';
        $db = $this->db;
        
        if($patientId <= 0) {
            $_SESSION['error'] = "Please select a patient";
            $this->redirect('/lab/patient-tests');
            return;
        }
        
        // Get patient details
        $patient = $db->query("SELECT first_name, last_name, patient_code FROM patients WHERE id = $patientId")->fetch_assoc();
        $patientName = $patient['first_name'] . ' ' . $patient['last_name'];
        
        // Get test names
        $testNamesQuery = "SELECT DISTINCT t.test_name 
                           FROM lab_tests t
                           JOIN lab_test_order_items i ON i.test_id = t.id
                           JOIN lab_test_orders o ON i.order_id = o.id
                           WHERE o.patient_id = $patientId AND i.status = 'completed'";
        $testNamesResult = $db->query($testNamesQuery);
        $testNames = [];
        while($row = $testNamesResult->fetch_assoc()) {
            $testNames[] = $row['test_name'];
        }
        
        // Get results data
        $query = "SELECT 
                    DATE(o.order_date) as test_date,
                    DATE_FORMAT(o.order_date, '%d-%m-%Y') as formatted_date,
                    o.order_number,
                    t.test_name,
                    i.result_value,
                    i.is_abnormal,
                    t.unit
                  FROM lab_test_orders o
                  JOIN lab_test_order_items i ON o.id = i.order_id
                  JOIN lab_tests t ON i.test_id = t.id
                  WHERE o.patient_id = $patientId 
                    AND i.status = 'completed'
                    AND i.result_value IS NOT NULL
                    AND i.result_value != ''";
        
        if($dateFrom) $query .= " AND DATE(o.order_date) >= '$dateFrom'";
        if($dateTo) $query .= " AND DATE(o.order_date) <= '$dateTo'";
        
        $query .= " ORDER BY o.order_date DESC, t.test_name ASC";
        $result = $db->query($query);
        
        // Group results by date
        $results = [];
        while($row = $result->fetch_assoc()) {
            $dateKey = $row['test_date'];
            if(!isset($results[$dateKey])) {
                $results[$dateKey] = [
                    'date' => $row['formatted_date'],
                    'order_number' => $row['order_number'],
                    'tests' => []
                ];
            }
            $results[$dateKey]['tests'][$row['test_name']] = [
                'value' => $row['result_value'],
                'is_abnormal' => $row['is_abnormal'],
                'unit' => $row['unit']
            ];
        }
        
        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="lab_tests_' . $patient['patient_code'] . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Write patient info
        fputcsv($output, ['Patient Lab Test Report']);
        fputcsv($output, ['Patient Name:', $patientName]);
        fputcsv($output, ['Patient Code:', $patient['patient_code']]);
        fputcsv($output, ['Generated On:', date('d-m-Y H:i:s')]);
        fputcsv($output, []);
        
        // Write header
        $header = ['Date', 'Order #'];
        foreach($testNames as $testName) {
            $header[] = $testName;
        }
        fputcsv($output, $header);
        
        // Write data
        foreach($results as $dateKey => $row) {
            $dataRow = [$row['date'], $row['order_number']];
            foreach($testNames as $testName) {
                if(isset($row['tests'][$testName])) {
                    $test = $row['tests'][$testName];
                    $value = $test['value'] . ($test['unit'] ? ' ' . $test['unit'] : '');
                    if($test['is_abnormal']) {
                        $value .= ' (Abnormal)';
                    }
                    $dataRow[] = $value;
                } else {
                    $dataRow[] = '—';
                }
            }
            fputcsv($output, $dataRow);
        }
        
        fclose($output);
        exit;
    }

    // ================================================================
    // API: GET LAB TESTS FOR SEARCH
    // ================================================================
    public function apiLabTests() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        $db = $this->db;
        
        try {
            if (!$db) {
                echo json_encode([]);
                exit;
            }
            
            // Check if we're getting a specific ID
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $id = (int)$_GET['id'];
                $sql = "SELECT lt.id, lt.test_code, lt.test_name, lt.price, lt.normal_range, lt.unit,
                               ltc.name as category_name
                        FROM lab_tests lt
                        LEFT JOIN lab_test_categories ltc ON lt.category_id = ltc.id
                        WHERE lt.id = $id AND lt.status = 'active'";
                $result = $db->query($sql);
                if ($result && $result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    echo json_encode([[
                        'id' => (int)$row['id'],
                        'test_code' => $row['test_code'] ?? '',
                        'test_name' => $row['test_name'],
                        'name' => $row['test_name'],
                        'price' => (float)($row['price'] ?? 0),
                        'test_price' => (float)($row['price'] ?? 0),
                        'category' => $row['category_name'] ?? '',
                        'category_name' => $row['category_name'] ?? '',
                        'normal_range' => $row['normal_range'] ?? '',
                        'unit' => $row['unit'] ?? ''
                    ]]);
                    exit;
                }
            }
            
            $sql = "SELECT lt.id, lt.test_code, lt.test_name, lt.price, lt.normal_range, lt.unit,
                           ltc.name as category_name
                    FROM lab_tests lt
                    LEFT JOIN lab_test_categories ltc ON lt.category_id = ltc.id
                    WHERE lt.status = 'active'";
            
            if (!empty($query) && strlen($query) >= 1) {
                $escapedQuery = $db->real_escape_string($query);
                $sql .= " AND (lt.test_name LIKE '%$escapedQuery%' 
                               OR lt.test_code LIKE '%$escapedQuery%'
                               OR ltc.name LIKE '%$escapedQuery%')";
            }
            
            $sql .= " ORDER BY lt.test_name ASC LIMIT 20";
            
            $result = $db->query($sql);
            $tests = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $tests[] = [
                        'id' => (int)$row['id'],
                        'test_code' => $row['test_code'] ?? '',
                        'test_name' => $row['test_name'],
                        'name' => $row['test_name'],
                        'price' => (float)($row['price'] ?? 0),
                        'test_price' => (float)($row['price'] ?? 0),
                        'category' => $row['category_name'] ?? '',
                        'category_name' => $row['category_name'] ?? '',
                        'normal_range' => $row['normal_range'] ?? '',
                        'unit' => $row['unit'] ?? ''
                    ];
                }
            }
            
            echo json_encode($tests);
            
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    // ================================================================
    // API: GET LAB TEST BY ID
    // ================================================================
    public function apiGetLabTest($id) {
        header('Content-Type: application/json');
        $id = (int)$id;
        $db = $this->db;
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid test ID']);
            exit;
        }
        
        $query = "SELECT * FROM lab_tests WHERE id = $id";
        $result = $db->query($query);
        
        if ($result && $result->num_rows > 0) {
            $test = $result->fetch_assoc();
            echo json_encode(['success' => true, 'test' => $test]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Test not found']);
        }
        exit;
    }

    // ================================================================
    // API: SAVE LAB TEST (Add/Update)
    // ================================================================
    public function apiSaveLabTest() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        try {
            $data = $_POST;
            
            if (empty($data)) {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                if (!empty($data)) {
                    $_POST = $data;
                } else {
                    throw new Exception('No data received');
                }
            }
            
            error_log("=== API SAVE LAB TEST ===");
            error_log("POST data: " . print_r($data, true));
            
            // Get basic test data
            $testName = isset($data['test_name']) ? $this->db->real_escape_string($data['test_name']) : '';
            $testCode = isset($data['test_code']) ? $this->db->real_escape_string($data['test_code']) : '';
            $categoryId = isset($data['category_id']) ? (int)$data['category_id'] : 0;
            $price = isset($data['price']) ? (float)$data['price'] : 0;
            $specimenType = isset($data['specimen_type']) ? $this->db->real_escape_string($data['specimen_type']) : '';
            $containerType = isset($data['container_type']) ? $this->db->real_escape_string($data['container_type']) : '';
            $normalRange = isset($data['normal_range']) ? $this->db->real_escape_string($data['normal_range']) : '';
            $unit = isset($data['unit']) ? $this->db->real_escape_string($data['unit']) : '';
            $turnaroundTime = isset($data['turnaround_time']) ? (int)$data['turnaround_time'] : 0;
            $description = isset($data['description']) ? $this->db->real_escape_string($data['description']) : '';
            $prepInstructions = isset($data['preparation_instructions']) ? $this->db->real_escape_string($data['preparation_instructions']) : '';
            $status = isset($data['status']) ? $this->db->real_escape_string($data['status']) : 'active';
            $isPackage = isset($data['is_package']) && ($data['is_package'] == '1' || $data['is_package'] == 1) ? 1 : 0;
            $requiresFasting = isset($data['requires_fasting']) && ($data['requires_fasting'] == '1' || $data['requires_fasting'] == 1) ? 1 : 0;
            
            // Parse instruments and accessories from JSON strings
            $instruments = [];
            if (isset($data['instruments_data']) && !empty($data['instruments_data'])) {
                if (is_string($data['instruments_data'])) {
                    $instruments = json_decode($data['instruments_data'], true);
                } else {
                    $instruments = $data['instruments_data'];
                }
                if (!is_array($instruments)) {
                    $instruments = [];
                }
            }
            
            $primaryInstrument = isset($data['primary_instrument_id']) ? (int)$data['primary_instrument_id'] : 0;
            
            $accessories = [];
            if (isset($data['accessories_data']) && !empty($data['accessories_data'])) {
                if (is_string($data['accessories_data'])) {
                    $accessories = json_decode($data['accessories_data'], true);
                } else {
                    $accessories = $data['accessories_data'];
                }
                if (!is_array($accessories)) {
                    $accessories = [];
                }
            }
            
            // Validate
            if (empty($testName)) {
                throw new Exception('Test name is required');
            }
            if ($categoryId <= 0) {
                throw new Exception('Category is required');
            }
            
            // Auto-generate test code if not provided
            if (empty($testCode)) {
                $prefix = '';
                if ($categoryId > 0) {
                    $catResult = $this->db->query("SELECT code FROM lab_test_categories WHERE id = $categoryId");
                    if ($catResult && $catResult->num_rows > 0) {
                        $cat = $catResult->fetch_assoc();
                        $prefix = $cat['code'] ?? '';
                    }
                }
                $testCode = $prefix . date('Ymd') . rand(100, 999);
            }
            
            $action = isset($data['action']) ? $data['action'] : 'add';
            $testId = isset($data['test_id']) ? (int)$data['test_id'] : 0;
            
            $this->db->begin_transaction();
            
            if ($action === 'edit' && $testId > 0) {
                // UPDATE
                $sql = "UPDATE lab_tests SET 
                            test_name = '$testName',
                            test_code = '$testCode',
                            category_id = $categoryId,
                            price = $price,
                            specimen_type = '$specimenType',
                            container_type = '$containerType',
                            normal_range = '$normalRange',
                            unit = '$unit',
                            turnaround_time = $turnaroundTime,
                            description = '$description',
                            preparation_instructions = '$prepInstructions',
                            status = '$status',
                            is_package = $isPackage,
                            requires_fasting = $requiresFasting
                        WHERE id = $testId";
                
                if (!$this->db->query($sql)) {
                    throw new Exception('Failed to update test: ' . $this->db->error);
                }
                
                // Delete existing associations
                $this->db->query("DELETE FROM lab_test_instruments WHERE test_id = $testId");
                $this->db->query("DELETE FROM lab_test_accessories WHERE test_id = $testId");
                
                // Insert instruments
                if (!empty($instruments) && is_array($instruments)) {
                    foreach ($instruments as $inst) {
                        $instId = isset($inst['id']) ? (int)$inst['id'] : 0;
                        if ($instId > 0) {
                            $isPrimary = ($instId == $primaryInstrument) ? 1 : 0;
                            $this->db->query("INSERT INTO lab_test_instruments (test_id, instrument_id, is_primary) 
                                             VALUES ($testId, $instId, $isPrimary)");
                        }
                    }
                }
                
                // Insert accessories
                if (!empty($accessories) && is_array($accessories)) {
                    foreach ($accessories as $acc) {
                        $accId = isset($acc['id']) ? (int)$acc['id'] : 0;
                        if ($accId > 0) {
                            $qty = isset($acc['quantity']) ? (int)$acc['quantity'] : 1;
                            $isRequired = isset($acc['is_required']) && $acc['is_required'] ? 1 : 0;
                            $notes = isset($acc['notes']) ? $this->db->real_escape_string($acc['notes']) : '';
                            $this->db->query("INSERT INTO lab_test_accessories (test_id, accessory_id, quantity_required, is_required, notes) 
                                             VALUES ($testId, $accId, $qty, $isRequired, '$notes')");
                        }
                    }
                }
                
                $message = 'Test updated successfully';
                
            } else {
                // INSERT
                $check = $this->db->query("SELECT id FROM lab_tests WHERE test_code = '$testCode'");
                if ($check && $check->num_rows > 0) {
                    $testCode = $testCode . rand(10, 99);
                }
                
                $sql = "INSERT INTO lab_tests (
                            test_name, test_code, category_id, price,
                            specimen_type, container_type, normal_range, unit,
                            turnaround_time, description, preparation_instructions,
                            status, is_package, requires_fasting
                        ) VALUES (
                            '$testName', '$testCode', $categoryId, $price,
                            '$specimenType', '$containerType', '$normalRange', '$unit',
                            $turnaroundTime, '$description', '$prepInstructions',
                            '$status', $isPackage, $requiresFasting
                        )";
                
                if (!$this->db->query($sql)) {
                    throw new Exception('Failed to add test: ' . $this->db->error);
                }
                
                $testId = $this->db->insert_id;
                
                // Insert instruments
                if (!empty($instruments) && is_array($instruments)) {
                    foreach ($instruments as $inst) {
                        $instId = isset($inst['id']) ? (int)$inst['id'] : 0;
                        if ($instId > 0) {
                            $isPrimary = ($instId == $primaryInstrument) ? 1 : 0;
                            $this->db->query("INSERT INTO lab_test_instruments (test_id, instrument_id, is_primary) 
                                             VALUES ($testId, $instId, $isPrimary)");
                        }
                    }
                }
                
                // Insert accessories
                if (!empty($accessories) && is_array($accessories)) {
                    foreach ($accessories as $acc) {
                        $accId = isset($acc['id']) ? (int)$acc['id'] : 0;
                        if ($accId > 0) {
                            $qty = isset($acc['quantity']) ? (int)$acc['quantity'] : 1;
                            $isRequired = isset($acc['is_required']) && $acc['is_required'] ? 1 : 0;
                            $notes = isset($acc['notes']) ? $this->db->real_escape_string($acc['notes']) : '';
                            $this->db->query("INSERT INTO lab_test_accessories (test_id, accessory_id, quantity_required, is_required, notes) 
                                             VALUES ($testId, $accId, $qty, $isRequired, '$notes')");
                        }
                    }
                }
                
                $message = 'Test added successfully';
            }
            
            $this->db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'test_id' => $testId
            ]);
            
        } catch (Exception $e) {
            if (isset($this->db)) {
                $this->db->rollback();
            }
            error_log("API Save Lab Test Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // ================================================================
    // API: TOGGLE LAB TEST STATUS
    // ================================================================
    public function apiToggleLabTest($id) {
        header('Content-Type: application/json');
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        $id = (int)$id;
        $db = $this->db;
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid test ID']);
            exit;
        }
        
        // Get current status
        $result = $db->query("SELECT status FROM lab_tests WHERE id = $id");
        if (!$result || $result->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Test not found']);
            exit;
        }
        
        $row = $result->fetch_assoc();
        $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';
        
        $sql = "UPDATE lab_tests SET status = '$newStatus' WHERE id = $id";
        
        if ($db->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Status changed to ' . ucfirst($newStatus)]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
        exit;
    }

    // ================================================================
    // API: DELETE LAB TEST
    // ================================================================
    public function apiDeleteLabTest($id) {
        header('Content-Type: application/json');
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        $id = (int)$id;
        $db = $this->db;
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid test ID']);
            exit;
        }
        
        // Check if test is used in any orders
        $check = $db->query("SELECT COUNT(*) as count FROM lab_test_order_items WHERE test_id = $id");
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            if ($row['count'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete: This test is used in ' . $row['count'] . ' order(s)']);
                exit;
            }
        }
        
        $sql = "DELETE FROM lab_tests WHERE id = $id";
        
        if ($db->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Test deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete test: ' . $db->error]);
        }
        exit;
    }

    // ================================================================
    // MANAGE LAB TESTS - Full CRUD Page
    // ================================================================
    public function manageTests() {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        $db = $this->db;
        
        // Get filter parameters
        $search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
        $categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $statusFilter = isset($_GET['status']) ? $db->real_escape_string($_GET['status']) : '';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 15;
        $offset = ($page - 1) * $limit;
        
        // Build WHERE clause
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $where .= " AND (test_name LIKE '%$search%' OR test_code LIKE '%$search%')";
        }
        if ($categoryFilter > 0) {
            $where .= " AND category_id = $categoryFilter";
        }
        if (!empty($statusFilter)) {
            $where .= " AND status = '$statusFilter'";
        }
        
        // Get all categories for filter dropdown
        $categoriesResult = $db->query("SELECT id, name, code FROM lab_test_categories WHERE status = 'active' ORDER BY name ASC");
        $categories = [];
        if ($categoriesResult) {
            while ($row = $categoriesResult->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        // Count total tests
        $countQuery = "SELECT COUNT(*) as total FROM lab_tests $where";
        $countResult = $db->query($countQuery);
        $totalTests = $countResult ? (int)$countResult->fetch_assoc()['total'] : 0;
        $totalPages = ceil($totalTests / $limit);
        
        // Get tests with pagination
        $query = "SELECT t.*, c.name as category_name 
                  FROM lab_tests t
                  LEFT JOIN lab_test_categories c ON t.category_id = c.id
                  $where
                  ORDER BY t.test_name ASC
                  LIMIT $limit OFFSET $offset";
        
        $result = $db->query($query);
        $tests = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tests[] = $row;
            }
        }
        
        // Get active/inactive counts
        $activeResult = $db->query("SELECT COUNT(*) as count FROM lab_tests WHERE status = 'active'");
        $activeCount = $activeResult ? (int)$activeResult->fetch_assoc()['count'] : 0;
        $inactiveResult = $db->query("SELECT COUNT(*) as count FROM lab_tests WHERE status = 'inactive'");
        $inactiveCount = $inactiveResult ? (int)$inactiveResult->fetch_assoc()['count'] : 0;
        
        $this->view('lab/manage-tests', [
            'tests' => $tests,
            'categories' => $categories,
            'totalTests' => $totalTests,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'page' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
            'search' => $search,
            'categoryFilter' => $categoryFilter,
            'statusFilter' => $statusFilter,
            'title' => 'Manage Lab Tests'
        ]);
    }

    // ================================================================
    // LAB TEST CATEGORY MANAGEMENT - COMPLETE METHODS
    // ================================================================

    // List all categories
    public function manageCategories() {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        $db = $this->db;
        $result = $db->query("SELECT * FROM lab_test_categories ORDER BY name ASC");
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $this->view('lab/manage-categories', [
            'categories' => $categories,
            'title' => 'Manage Lab Test Categories'
        ]);
    }

    // ================================================================
    // GET SINGLE CATEGORY - API
    // ================================================================
    public function apiGetCategory($id) {
        header('Content-Type: application/json');
        $id = (int)$id;
        $db = $this->db;
        
        $result = $db->query("SELECT * FROM lab_test_categories WHERE id = $id");
        if ($result && $result->num_rows > 0) {
            echo json_encode(['success' => true, 'category' => $result->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Category not found']);
        }
        exit;
    }

    // ================================================================
    // GET ALL CATEGORIES - API
    // ================================================================
    public function apiGetAllCategories() {
        header('Content-Type: application/json');
        $db = $this->db;
        
        $result = $db->query("SELECT * FROM lab_test_categories WHERE status = 'active' ORDER BY name ASC");
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        echo json_encode($categories);
        exit;
    }


    // ================================================================
// ADD CATEGORY - API
// ================================================================
public function addCategory() {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    
    header('Content-Type: application/json');
    
    $db = $this->db;
    
    $name = $db->real_escape_string($_POST['name'] ?? '');
    $code = $db->real_escape_string($_POST['code'] ?? '');
    $description = $db->real_escape_string($_POST['description'] ?? '');
    $status = $db->real_escape_string($_POST['status'] ?? 'active');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit;
    }
    
    // Check if name already exists
    $check = $db->query("SELECT id FROM lab_test_categories WHERE name = '$name'");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Category name already exists']);
        exit;
    }
    
    // Auto-generate code if not provided
    if (empty($code)) {
        $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 4)) . date('Ymd') . rand(100, 999);
    }
    
    $sql = "INSERT INTO lab_test_categories (name, code, description, status) 
            VALUES ('$name', '$code', '$description', '$status')";
    
    if ($db->query($sql)) {
        $newId = $db->insert_id;
        $result = $db->query("SELECT * FROM lab_test_categories WHERE id = $newId");
        $category = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Category added successfully',
            'category' => $category
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add category: ' . $db->error]);
    }
    exit;
}

// ================================================================
// EDIT CATEGORY - API
// ================================================================
public function editCategory($id) {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    $id = (int)$id;
    
    header('Content-Type: application/json');
    
    $db = $this->db;
    
    $name = $db->real_escape_string($_POST['name'] ?? '');
    $code = $db->real_escape_string($_POST['code'] ?? '');
    $description = $db->real_escape_string($_POST['description'] ?? '');
    $status = $db->real_escape_string($_POST['status'] ?? 'active');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Category name is required']);
        exit;
    }
    
    // Check if name already exists for another category
    $check = $db->query("SELECT id FROM lab_test_categories WHERE name = '$name' AND id != $id");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Category name already exists']);
        exit;
    }
    
    // Auto-generate code if not provided
    if (empty($code)) {
        $code = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $name), 0, 4)) . date('Ymd') . rand(100, 999);
    }
    
    $sql = "UPDATE lab_test_categories SET 
                name = '$name',
                code = '$code',
                description = '$description',
                status = '$status'
            WHERE id = $id";
    
    if ($db->query($sql)) {
        $result = $db->query("SELECT * FROM lab_test_categories WHERE id = $id");
        $category = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update category: ' . $db->error]);
    }
    exit;
}

// ================================================================
// DELETE CATEGORY - API
// ================================================================
public function deleteCategory($id) {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    header('Content-Type: application/json');
    
    $id = (int)$id;
    $db = $this->db;
    
    // Check if category has tests
    $check = $db->query("SELECT COUNT(*) as count FROM lab_tests WHERE category_id = $id");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        if ($row['count'] > 0) {
            echo json_encode(['success' => false, 'message' => "Cannot delete: {$row['count']} test(s) are using this category"]);
            exit;
        }
    }
    
    $sql = "DELETE FROM lab_test_categories WHERE id = $id";
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete category: ' . $db->error]);
    }
    exit;
}



    public function apiDeleteCategory($id) {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        header('Content-Type: application/json');
        
        $id = (int)$id;
        $db = $this->db;
        
        // Check if category has tests
        $check = $db->query("SELECT COUNT(*) as count FROM lab_tests WHERE category_id=$id");
        $count = $check->fetch_assoc()['count'];
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => "Cannot delete: $count test(s) are using this category"]);
            exit;
        }
        
        $sql = "DELETE FROM lab_test_categories WHERE id=$id";
        if ($db->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete category: ' . $db->error]);
        }
        exit;
    }

    // ================================================================
    // LAB INSTRUMENT MANAGEMENT - COMPLETE METHODS
    // ================================================================

    // List all instruments
    public function manageInstruments() {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        $db = $this->db;
        $result = $db->query("SELECT * FROM lab_instruments ORDER BY instrument_name ASC");
        $instruments = [];
        while ($row = $result->fetch_assoc()) {
            $instruments[] = $row;
        }
        
        $this->view('lab/manage-instruments', [
            'instruments' => $instruments,
            'title' => 'Manage Lab Instruments'
        ]);
    }

    // ================================================================
    // GET SINGLE INSTRUMENT - API
    // ================================================================
    public function apiGetInstrument($id) {
        header('Content-Type: application/json');
        $id = (int)$id;
        $db = $this->db;
        
        $result = $db->query("SELECT * FROM lab_instruments WHERE id = $id");
        if ($result && $result->num_rows > 0) {
            echo json_encode(['success' => true, 'instrument' => $result->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Instrument not found']);
        }
        exit;
    }

    // ================================================================
// DELETE INSTRUMENT - API
// ================================================================
public function deleteInstrument($id) {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    header('Content-Type: application/json');
    
    $id = (int)$id;
    $db = $this->db;
    
    $check = $db->query("SELECT COUNT(*) as count FROM lab_test_instruments WHERE instrument_id = $id");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        if ($row['count'] > 0) {
            echo json_encode(['success' => false, 'message' => "Cannot delete: {$row['count']} test(s) are using this instrument"]);
            exit;
        }
    }
    
    $sql = "DELETE FROM lab_instruments WHERE id = $id";
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Instrument deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete instrument: ' . $db->error]);
    }
    exit;
}

    public function apiDeleteInstrument($id) {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        header('Content-Type: application/json');
        
        $id = (int)$id;
        $db = $this->db;
        
        // Check if instrument is used in any test
        $check = $db->query("SELECT COUNT(*) as count FROM lab_test_instruments WHERE instrument_id=$id");
        $count = $check->fetch_assoc()['count'];
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => "Cannot delete: $count test(s) are using this instrument"]);
            exit;
        }
        
        $sql = "DELETE FROM lab_instruments WHERE id=$id";
        if ($db->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Instrument deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete instrument: ' . $db->error]);
        }
        exit;
    }

    // ================================================================
    // GET ALL INSTRUMENTS - API
    // ================================================================
    public function apiGetInstruments() {
        header('Content-Type: application/json');
        $db = $this->db;
        
        $result = $db->query("SELECT * FROM lab_instruments WHERE status = 'active' ORDER BY instrument_name ASC");
        $instruments = [];
        while ($row = $result->fetch_assoc()) {
            $instruments[] = $row;
        }
        echo json_encode($instruments);
        exit;
    }

    // ================================================================
// ADD INSTRUMENT - API
// ================================================================
public function addInstrument() {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    
    header('Content-Type: application/json');
    
    $db = $this->db;
    
    $instrument_code = $db->real_escape_string($_POST['instrument_code'] ?? '');
    $instrument_name = $db->real_escape_string($_POST['instrument_name'] ?? '');
    $model = $db->real_escape_string($_POST['model'] ?? '');
    $manufacturer = $db->real_escape_string($_POST['manufacturer'] ?? '');
    $serial_number = $db->real_escape_string($_POST['serial_number'] ?? '');
    $purchase_date = $db->real_escape_string($_POST['purchase_date'] ?? '');
    $warranty_expiry = $db->real_escape_string($_POST['warranty_expiry'] ?? '');
    $calibration_date = $db->real_escape_string($_POST['calibration_date'] ?? '');
    $next_calibration_date = $db->real_escape_string($_POST['next_calibration_date'] ?? '');
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $maintenance_cost = isset($_POST['maintenance_cost']) ? (float)$_POST['maintenance_cost'] : 0;
    $status = $db->real_escape_string($_POST['status'] ?? 'active');
    $description = $db->real_escape_string($_POST['description'] ?? '');
    
    if (empty($instrument_name)) {
        echo json_encode(['success' => false, 'message' => 'Instrument name is required']);
        exit;
    }
    if (empty($instrument_code)) {
        echo json_encode(['success' => false, 'message' => 'Instrument code is required']);
        exit;
    }
    
    $check = $db->query("SELECT id FROM lab_instruments WHERE instrument_code = '$instrument_code'");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Instrument code already exists']);
        exit;
    }
    
    $sql = "INSERT INTO lab_instruments (
        instrument_code, instrument_name, model, manufacturer, serial_number,
        purchase_date, warranty_expiry, calibration_date, next_calibration_date,
        price, maintenance_cost, status, description
    ) VALUES (
        '$instrument_code', '$instrument_name', '$model', '$manufacturer', '$serial_number',
        '$purchase_date', '$warranty_expiry', '$calibration_date', '$next_calibration_date',
        $price, $maintenance_cost, '$status', '$description'
    )";
    
    if ($db->query($sql)) {
        $newId = $db->insert_id;
        $result = $db->query("SELECT * FROM lab_instruments WHERE id = $newId");
        $instrument = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Instrument added successfully',
            'instrument' => $instrument
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add instrument: ' . $db->error]);
    }
    exit;
}


// ================================================================
// EDIT INSTRUMENT - API
// ================================================================
public function editInstrument($id) {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    $id = (int)$id;
    
    header('Content-Type: application/json');
    
    $db = $this->db;
    
    $instrument_code = $db->real_escape_string($_POST['instrument_code'] ?? '');
    $instrument_name = $db->real_escape_string($_POST['instrument_name'] ?? '');
    $model = $db->real_escape_string($_POST['model'] ?? '');
    $manufacturer = $db->real_escape_string($_POST['manufacturer'] ?? '');
    $serial_number = $db->real_escape_string($_POST['serial_number'] ?? '');
    $purchase_date = $db->real_escape_string($_POST['purchase_date'] ?? '');
    $warranty_expiry = $db->real_escape_string($_POST['warranty_expiry'] ?? '');
    $calibration_date = $db->real_escape_string($_POST['calibration_date'] ?? '');
    $next_calibration_date = $db->real_escape_string($_POST['next_calibration_date'] ?? '');
    $price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $maintenance_cost = isset($_POST['maintenance_cost']) ? (float)$_POST['maintenance_cost'] : 0;
    $status = $db->real_escape_string($_POST['status'] ?? 'active');
    $description = $db->real_escape_string($_POST['description'] ?? '');
    
    if (empty($instrument_name)) {
        echo json_encode(['success' => false, 'message' => 'Instrument name is required']);
        exit;
    }
    if (empty($instrument_code)) {
        echo json_encode(['success' => false, 'message' => 'Instrument code is required']);
        exit;
    }
    
    $sql = "UPDATE lab_instruments SET 
        instrument_code = '$instrument_code',
        instrument_name = '$instrument_name',
        model = '$model',
        manufacturer = '$manufacturer',
        serial_number = '$serial_number',
        purchase_date = '$purchase_date',
        warranty_expiry = '$warranty_expiry',
        calibration_date = '$calibration_date',
        next_calibration_date = '$next_calibration_date',
        price = $price,
        maintenance_cost = $maintenance_cost,
        status = '$status',
        description = '$description'
    WHERE id = $id";
    
    if ($db->query($sql)) {
        $result = $db->query("SELECT * FROM lab_instruments WHERE id = $id");
        $instrument = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Instrument updated successfully',
            'instrument' => $instrument
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update instrument: ' . $db->error]);
    }
    exit;
}



    // ================================================================
    // LAB TEST-INSTRUMENT ASSIGNMENT
    // ================================================================

    public function manageTestInstruments($testId) {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        $testId = (int)$testId;
        $db = $this->db;
        
        // Get test details
        $testResult = $db->query("SELECT * FROM lab_tests WHERE id=$testId");
        $test = $testResult->fetch_assoc();
        
        // Get all active instruments
        $instrumentsResult = $db->query("SELECT * FROM lab_instruments WHERE status='active' ORDER BY instrument_name ASC");
        $instruments = [];
        while ($row = $instrumentsResult->fetch_assoc()) {
            $instruments[] = $row;
        }
        
        // Get assigned instruments for this test
        $assignedResult = $db->query("SELECT instrument_id, is_primary FROM lab_test_instruments WHERE test_id=$testId");
        $assignedInstruments = [];
        $primaryInstrument = 0;
        while ($row = $assignedResult->fetch_assoc()) {
            $assignedInstruments[] = $row['instrument_id'];
            if ($row['is_primary'] == 1) {
                $primaryInstrument = $row['instrument_id'];
            }
        }
        
        $this->view('lab/manage-test-instruments', [
            'test' => $test,
            'instruments' => $instruments,
            'assignedInstruments' => $assignedInstruments,
            'primaryInstrument' => $primaryInstrument,
            'title' => 'Manage Test Instruments'
        ]);
    }

    public function apiGetTestInstruments($testId) {
        header('Content-Type: application/json');
        $testId = (int)$testId;
        $db = $this->db;
        
        $result = $db->query("
            SELECT i.*, lti.is_primary 
            FROM lab_test_instruments lti
            JOIN lab_instruments i ON lti.instrument_id = i.id
            WHERE lti.test_id = $testId
            ORDER BY lti.is_primary DESC, i.instrument_name ASC
        ");
        
        $instruments = [];
        while ($row = $result->fetch_assoc()) {
            $instruments[] = $row;
        }
        
        echo json_encode(['success' => true, 'instruments' => $instruments]);
        exit;
    }

    // ================================================================
    // LAB ACCESSORY MANAGEMENT
    // ================================================================

    // List all accessories
    public function manageAccessories() {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        $db = $this->db;
        $result = $db->query("SELECT * FROM lab_accessories ORDER BY accessory_name ASC");
        $accessories = [];
        while ($row = $result->fetch_assoc()) {
            $accessories[] = $row;
        }
        
        $this->view('lab/manage-accessories', [
            'accessories' => $accessories,
            'title' => 'Manage Lab Accessories'
        ]);
    }

    // ================================================================
    // GET SINGLE ACCESSORY - API
    // ================================================================
    public function apiGetAccessory($id) {
        header('Content-Type: application/json');
        $id = (int)$id;
        $db = $this->db;
        
        $result = $db->query("SELECT * FROM lab_accessories WHERE id = $id");
        if ($result && $result->num_rows > 0) {
            echo json_encode(['success' => true, 'accessory' => $result->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Accessory not found']);
        }
        exit;
    }

    public function apiDeleteAccessory($id) {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        header('Content-Type: application/json');
        
        $id = (int)$id;
        $db = $this->db;
        
        // Check if accessory is used in any test
        $check = $db->query("SELECT COUNT(*) as count FROM lab_test_accessories WHERE accessory_id=$id");
        $count = $check->fetch_assoc()['count'];
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => "Cannot delete: $count test(s) are using this accessory"]);
            exit;
        }
        
        $sql = "DELETE FROM lab_accessories WHERE id=$id";
        if ($db->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Accessory deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete accessory: ' . $db->error]);
        }
        exit;
    }

    // ================================================================
    // GET ALL ACCESSORIES - API
    // ================================================================
    public function apiGetAccessories() {
        header('Content-Type: application/json');
        $db = $this->db;
        
        $result = $db->query("SELECT * FROM lab_accessories WHERE status = 'active' ORDER BY accessory_name ASC");
        $accessories = [];
        while ($row = $result->fetch_assoc()) {
            $accessories[] = $row;
        }
        echo json_encode($accessories);
        exit;
    }

    // ================================================================
    // LAB TEST-ACCESSORY ASSIGNMENT
    // ================================================================

    public function manageTestAccessories($testId) {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        $testId = (int)$testId;
        $db = $this->db;
        
        // Get test details
        $testResult = $db->query("SELECT * FROM lab_tests WHERE id=$testId");
        $test = $testResult->fetch_assoc();
        
        // Get all active accessories
        $accessoriesResult = $db->query("SELECT * FROM lab_accessories WHERE status='active' ORDER BY accessory_name ASC");
        $accessories = [];
        while ($row = $accessoriesResult->fetch_assoc()) {
            $accessories[] = $row;
        }
        
        // Get assigned accessories for this test
        $assignedResult = $db->query("SELECT * FROM lab_test_accessories WHERE test_id=$testId");
        $assignedAccessories = [];
        while ($row = $assignedResult->fetch_assoc()) {
            $assignedAccessories[$row['accessory_id']] = $row;
        }
        
        $this->view('lab/manage-test-accessories', [
            'test' => $test,
            'accessories' => $accessories,
            'assignedAccessories' => $assignedAccessories,
            'title' => 'Manage Test Accessories'
        ]);
    }

    public function apiGetTestAccessories($testId) {
        header('Content-Type: application/json');
        $testId = (int)$testId;
        $db = $this->db;
        
        $result = $db->query("
            SELECT a.*, lta.quantity_required, lta.is_required, lta.notes 
            FROM lab_test_accessories lta
            JOIN lab_accessories a ON lta.accessory_id = a.id
            WHERE lta.test_id = $testId
            ORDER BY a.accessory_name ASC
        ");
        
        $accessories = [];
        while ($row = $result->fetch_assoc()) {
            $accessories[] = $row;
        }
        
        echo json_encode(['success' => true, 'accessories' => $accessories]);
        exit;
    }

    // ================================================================
// UPDATE ACCESSORY STOCK - API (FIXED)
// ================================================================
public function updateAccessoryStock() {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    header('Content-Type: application/json');
    
    // Get POST data
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
    $operation = isset($_POST['operation']) ? trim($_POST['operation']) : 'add';
    
    $db = $this->db;
    
    // Validate input
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid accessory ID']);
        exit;
    }
    
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
        exit;
    }
    
    // Check if accessory exists
    $result = $db->query("SELECT current_stock FROM lab_accessories WHERE id = $id");
    if (!$result || $result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Accessory not found']);
        exit;
    }
    
    $current = $result->fetch_assoc();
    $currentStock = (int)$current['current_stock'];
    
    // Calculate new stock
    if ($operation == 'add') {
        $newStock = $currentStock + $quantity;
    } elseif ($operation == 'subtract') {
        $newStock = $currentStock - $quantity;
        if ($newStock < 0) $newStock = 0;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid operation. Use "add" or "subtract"']);
        exit;
    }
    
    // Update the database
    $sql = "UPDATE lab_accessories SET current_stock = $newStock WHERE id = $id";
    
    if ($db->query($sql)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Stock updated successfully',
            'new_stock' => $newStock,
            'old_stock' => $currentStock,
            'id' => $id
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $db->error
        ]);
    }
    exit;
}

    // ================================================================
    // ADD TEST PAGE
    // ================================================================
    public function addTest() {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        $db = $this->db;
        
        // Get all categories for dropdown
        $categoriesResult = $db->query("SELECT id, name, code FROM lab_test_categories WHERE status = 'active' ORDER BY name ASC");
        $categories = [];
        if ($categoriesResult) {
            while ($row = $categoriesResult->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        $this->view('lab/add-test', [
            'categories' => $categories,
            'title' => 'Add Lab Test'
        ]);
    }

    // ================================================================
    // EDIT TEST PAGE
    // ================================================================
    public function editTest($id) {
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        $id = (int)$id;
        $db = $this->db;
        
        // Get test details
        $testResult = $db->query("SELECT * FROM lab_tests WHERE id = $id");
        $test = $testResult ? $testResult->fetch_assoc() : null;
        
        if (!$test) {
            $_SESSION['error'] = "Test not found";
            $this->redirect('/lab/manage-tests');
            return;
        }
        
        // Get all categories for dropdown
        $categoriesResult = $db->query("SELECT id, name, code FROM lab_test_categories WHERE status = 'active' ORDER BY name ASC");
        $categories = [];
        if ($categoriesResult) {
            while ($row = $categoriesResult->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        $this->view('lab/edit-test', [
            'test' => $test,
            'categories' => $categories,
            'title' => 'Edit Lab Test'
        ]);
    }

    // ================================================================
    // SAVE TEST (API) - Combined Add/Edit
    // ================================================================
    public function apiSaveTest() {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        $this->checkAuth();
        $this->checkPermission('manage_tests');
        
        try {
            $data = $_POST;
            
            if (empty($data)) {
                $input = file_get_contents('php://input');
                $data = json_decode($input, true);
                if (!empty($data)) {
                    $_POST = $data;
                } else {
                    throw new Exception('No data received');
                }
            }
            
            $db = $this->db;
            
            // Get basic test data
            $testName = isset($data['test_name']) ? $db->real_escape_string($data['test_name']) : '';
            $testCode = isset($data['test_code']) ? $db->real_escape_string($data['test_code']) : '';
            $categoryId = isset($data['category_id']) ? (int)$data['category_id'] : 0;
            $price = isset($data['price']) ? (float)$data['price'] : 0;
            $specimenType = isset($data['specimen_type']) ? $db->real_escape_string($data['specimen_type']) : '';
            $containerType = isset($data['container_type']) ? $db->real_escape_string($data['container_type']) : '';
            $normalRange = isset($data['normal_range']) ? $db->real_escape_string($data['normal_range']) : '';
            $unit = isset($data['unit']) ? $db->real_escape_string($data['unit']) : '';
            $turnaroundTime = isset($data['turnaround_time']) ? (int)$data['turnaround_time'] : 0;
            $description = isset($data['description']) ? $db->real_escape_string($data['description']) : '';
            $prepInstructions = isset($data['preparation_instructions']) ? $db->real_escape_string($data['preparation_instructions']) : '';
            $status = isset($data['status']) ? $db->real_escape_string($data['status']) : 'active';
            $isPackage = isset($data['is_package']) && ($data['is_package'] == '1' || $data['is_package'] == 1) ? 1 : 0;
            $requiresFasting = isset($data['requires_fasting']) && ($data['requires_fasting'] == '1' || $data['requires_fasting'] == 1) ? 1 : 0;
            
            // Parse instruments and accessories
            $instruments = [];
            if (isset($data['instruments_data']) && !empty($data['instruments_data'])) {
                if (is_string($data['instruments_data'])) {
                    $instruments = json_decode($data['instruments_data'], true);
                } else {
                    $instruments = $data['instruments_data'];
                }
                if (!is_array($instruments)) $instruments = [];
            }
            
            $primaryInstrument = isset($data['primary_instrument_id']) ? (int)$data['primary_instrument_id'] : 0;
            
            $accessories = [];
            if (isset($data['accessories_data']) && !empty($data['accessories_data'])) {
                if (is_string($data['accessories_data'])) {
                    $accessories = json_decode($data['accessories_data'], true);
                } else {
                    $accessories = $data['accessories_data'];
                }
                if (!is_array($accessories)) $accessories = [];
            }
            
            // Validate
            if (empty($testName)) throw new Exception('Test name is required');
            if ($categoryId <= 0) throw new Exception('Category is required');
            if ($price <= 0) throw new Exception('Price is required');
            
            // Auto-generate test code
            if (empty($testCode)) {
                $prefix = '';
                if ($categoryId > 0) {
                    $catResult = $db->query("SELECT code FROM lab_test_categories WHERE id = $categoryId");
                    if ($catResult && $catResult->num_rows > 0) {
                        $cat = $catResult->fetch_assoc();
                        $prefix = $cat['code'] ?? '';
                    }
                }
                $testCode = $prefix . date('Ymd') . rand(100, 999);
            }
            
            $action = isset($data['action']) ? $data['action'] : 'add';
            $testId = isset($data['test_id']) ? (int)$data['test_id'] : 0;
            
            $db->begin_transaction();
            
            if ($action === 'edit' && $testId > 0) {
                // UPDATE
                $sql = "UPDATE lab_tests SET 
                            test_name = '$testName',
                            test_code = '$testCode',
                            category_id = $categoryId,
                            price = $price,
                            specimen_type = '$specimenType',
                            container_type = '$containerType',
                            normal_range = '$normalRange',
                            unit = '$unit',
                            turnaround_time = $turnaroundTime,
                            description = '$description',
                            preparation_instructions = '$prepInstructions',
                            status = '$status',
                            is_package = $isPackage,
                            requires_fasting = $requiresFasting
                        WHERE id = $testId";
                
                if (!$db->query($sql)) {
                    throw new Exception('Failed to update test: ' . $db->error);
                }
                
                // Delete existing associations
                $db->query("DELETE FROM lab_test_instruments WHERE test_id = $testId");
                $db->query("DELETE FROM lab_test_accessories WHERE test_id = $testId");
                
            } else {
                // INSERT
                $check = $db->query("SELECT id FROM lab_tests WHERE test_code = '$testCode'");
                if ($check && $check->num_rows > 0) {
                    $testCode = $testCode . rand(10, 99);
                }
                
                $sql = "INSERT INTO lab_tests (
                            test_name, test_code, category_id, price,
                            specimen_type, container_type, normal_range, unit,
                            turnaround_time, description, preparation_instructions,
                            status, is_package, requires_fasting
                        ) VALUES (
                            '$testName', '$testCode', $categoryId, $price,
                            '$specimenType', '$containerType', '$normalRange', '$unit',
                            $turnaroundTime, '$description', '$prepInstructions',
                            '$status', $isPackage, $requiresFasting
                        )";
                
                if (!$db->query($sql)) {
                    throw new Exception('Failed to add test: ' . $db->error);
                }
                
                $testId = $db->insert_id;
            }
            
            // Insert instruments
            if (!empty($instruments) && is_array($instruments)) {
                foreach ($instruments as $inst) {
                    $instId = isset($inst['id']) ? (int)$inst['id'] : 0;
                    if ($instId > 0) {
                        $isPrimary = ($instId == $primaryInstrument) ? 1 : 0;
                        $db->query("INSERT INTO lab_test_instruments (test_id, instrument_id, is_primary) 
                                   VALUES ($testId, $instId, $isPrimary)");
                    }
                }
            }
            
            // Insert accessories
            if (!empty($accessories) && is_array($accessories)) {
                foreach ($accessories as $acc) {
                    $accId = isset($acc['id']) ? (int)$acc['id'] : 0;
                    if ($accId > 0) {
                        $qty = isset($acc['quantity']) ? (int)$acc['quantity'] : 1;
                        $isRequired = isset($acc['is_required']) && $acc['is_required'] ? 1 : 0;
                        $notes = isset($acc['notes']) ? $db->real_escape_string($acc['notes']) : '';
                        $db->query("INSERT INTO lab_test_accessories (test_id, accessory_id, quantity_required, is_required, notes) 
                                   VALUES ($testId, $accId, $qty, $isRequired, '$notes')");
                    }
                }
            }
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => ($action === 'edit' ? 'Test updated' : 'Test added') . ' successfully',
                'test_id' => $testId
            ]);
            
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollback();
            }
            error_log("API Save Test Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    // ================================================================
// ADD ACCESSORY - API
// ================================================================
public function addAccessory() {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    
    header('Content-Type: application/json');
    
    $db = $this->db;
    
    $accessory_code = $db->real_escape_string($_POST['accessory_code'] ?? '');
    $accessory_name = $db->real_escape_string($_POST['accessory_name'] ?? '');
    $category = $db->real_escape_string($_POST['category'] ?? '');
    $description = $db->real_escape_string($_POST['description'] ?? '');
    $unit_price = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : 0;
    $reorder_level = isset($_POST['reorder_level']) ? (int)$_POST['reorder_level'] : 10;
    $current_stock = isset($_POST['current_stock']) ? (int)$_POST['current_stock'] : 0;
    $supplier = $db->real_escape_string($_POST['supplier'] ?? '');
    $status = $db->real_escape_string($_POST['status'] ?? 'active');
    
    if (empty($accessory_name)) {
        echo json_encode(['success' => false, 'message' => 'Accessory name is required']);
        exit;
    }
    if (empty($accessory_code)) {
        echo json_encode(['success' => false, 'message' => 'Accessory code is required']);
        exit;
    }
    
    $check = $db->query("SELECT id FROM lab_accessories WHERE accessory_code = '$accessory_code'");
    if ($check && $check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Accessory code already exists']);
        exit;
    }
    
    $sql = "INSERT INTO lab_accessories (
        accessory_code, accessory_name, category, description,
        unit_price, reorder_level, current_stock, supplier, status
    ) VALUES (
        '$accessory_code', '$accessory_name', '$category', '$description',
        $unit_price, $reorder_level, $current_stock, '$supplier', '$status'
    )";
    
    if ($db->query($sql)) {
        $newId = $db->insert_id;
        $result = $db->query("SELECT * FROM lab_accessories WHERE id = $newId");
        $accessory = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Accessory added successfully',
            'accessory' => $accessory
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add accessory: ' . $db->error]);
    }
    exit;
}

// ================================================================
// EDIT ACCESSORY - API
// ================================================================
public function editAccessory($id) {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    $id = (int)$id;
    
    header('Content-Type: application/json');
    
    $db = $this->db;
    
    $accessory_code = $db->real_escape_string($_POST['accessory_code'] ?? '');
    $accessory_name = $db->real_escape_string($_POST['accessory_name'] ?? '');
    $category = $db->real_escape_string($_POST['category'] ?? '');
    $description = $db->real_escape_string($_POST['description'] ?? '');
    $unit_price = isset($_POST['unit_price']) ? (float)$_POST['unit_price'] : 0;
    $reorder_level = isset($_POST['reorder_level']) ? (int)$_POST['reorder_level'] : 10;
    $current_stock = isset($_POST['current_stock']) ? (int)$_POST['current_stock'] : 0;
    $supplier = $db->real_escape_string($_POST['supplier'] ?? '');
    $status = $db->real_escape_string($_POST['status'] ?? 'active');
    
    if (empty($accessory_name)) {
        echo json_encode(['success' => false, 'message' => 'Accessory name is required']);
        exit;
    }
    if (empty($accessory_code)) {
        echo json_encode(['success' => false, 'message' => 'Accessory code is required']);
        exit;
    }
    
    $sql = "UPDATE lab_accessories SET 
        accessory_code = '$accessory_code',
        accessory_name = '$accessory_name',
        category = '$category',
        description = '$description',
        unit_price = $unit_price,
        reorder_level = $reorder_level,
        current_stock = $current_stock,
        supplier = '$supplier',
        status = '$status'
    WHERE id = $id";
    
    if ($db->query($sql)) {
        $result = $db->query("SELECT * FROM lab_accessories WHERE id = $id");
        $accessory = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Accessory updated successfully',
            'accessory' => $accessory
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update accessory: ' . $db->error]);
    }
    exit;
}

// ================================================================
// DELETE ACCESSORY - API
// ================================================================
public function deleteAccessory($id) {
    $this->checkAuth();
    $this->checkPermission('manage_tests');
    header('Content-Type: application/json');
    
    $id = (int)$id;
    $db = $this->db;
    
    $check = $db->query("SELECT COUNT(*) as count FROM lab_test_accessories WHERE accessory_id = $id");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        if ($row['count'] > 0) {
            echo json_encode(['success' => false, 'message' => "Cannot delete: {$row['count']} test(s) are using this accessory"]);
            exit;
        }
    }
    
    $sql = "DELETE FROM lab_accessories WHERE id = $id";
    if ($db->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Accessory deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete accessory: ' . $db->error]);
    }
    exit;
}

// ================================================================
// LAB-TEST PRESCRIPTIONS - LIST PRESCRIPTIONS WITH LAB TESTS
// ================================================================
public function prescriptions() {
    $this->checkAuth();
    $this->checkPermission('view_lab');
    
    $db = $this->db;
    $prescriptionModel = new Prescription();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $search = isset($_GET['search']) ? $db->real_escape_string($_GET['search']) : '';
    $dateFrom = isset($_GET['date_from']) ? $db->real_escape_string($_GET['date_from']) : '';
    $dateTo = isset($_GET['date_to']) ? $db->real_escape_string($_GET['date_to']) : '';
    $hasOrder = isset($_GET['has_order']) ? $_GET['has_order'] : '';
    
    $filters = [
        'search' => $search,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'has_order' => $hasOrder
    ];
    
    // Get all prescriptions with lab tests
    $allPrescriptions = $prescriptionModel->getPrescriptionsWithLabTests($filters);
    $totalPrescriptions = count($allPrescriptions);
    $totalPages = ceil($totalPrescriptions / $limit);
    
    // Get current page data
    $prescriptions = array_slice($allPrescriptions, $offset, $limit);
    
    // Get statistics
    $total = 0;
    $withOrders = 0;
    $withoutOrders = 0;
    $pendingTests = 0;
    $completedTests = 0;
    
    foreach ($allPrescriptions as $p) {
        $total++;
        if (!empty($p['existing_order_id'])) {
            $withOrders++;
        } else {
            $withoutOrders++;
        }
    }
    
    $pendingResult = $db->query("SELECT COUNT(*) as count FROM lab_test_orders WHERE status = 'ordered'");
    if ($pendingResult) {
        $pendingTests = $pendingResult->fetch_assoc()['count'] ?? 0;
    }
    
    $completedResult = $db->query("SELECT COUNT(*) as count FROM lab_test_orders WHERE status = 'completed'");
    if ($completedResult) {
        $completedTests = $completedResult->fetch_assoc()['count'] ?? 0;
    }
    
    $this->view('lab/prescriptions', [
        'prescriptions' => $prescriptions,
        'totalPrescriptions' => $totalPrescriptions,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'limit' => $limit,
        'search' => $search,
        'dateFrom' => $dateFrom,
        'dateTo' => $dateTo,
        'hasOrder' => $hasOrder,
        'total' => $total,
        'withOrders' => $withOrders,
        'withoutOrders' => $withoutOrders,
        'pendingTests' => $pendingTests,
        'completedTests' => $completedTests
    ], 'Lab-Test Prescriptions');
}

// ================================================================
// VIEW PRESCRIPTION LAB TESTS - Show details and create order
// ================================================================
public function viewPrescriptionTests($prescriptionId) {
    $this->checkAuth();
    $this->checkPermission('view_lab');
    
    $prescriptionId = (int)$prescriptionId;
    $db = $this->db;
    $prescriptionModel = new Prescription();
    
    // Get prescription data
    $prescription = $prescriptionModel->getPrintData($prescriptionId);
    
    if (!$prescription) {
        $_SESSION['error'] = "Prescription not found";
        $this->redirect('/lab/prescriptions');
        return;
    }
    
    // Get lab tests from prescription
    $labTests = $prescriptionModel->getPrescriptionLabTests($prescriptionId);
    
    // Check if order already exists
    $orderCheck = $db->query("SELECT * FROM lab_test_orders WHERE prescription_id = $prescriptionId LIMIT 1");
    $existingOrder = $orderCheck ? $orderCheck->fetch_assoc() : null;
    
    // Get all lab tests for dropdown (for adding more tests)
    $allTests = [];
    $testsResult = $db->query("SELECT t.*, c.name as category_name 
                              FROM lab_tests t
                              LEFT JOIN lab_test_categories c ON t.category_id = c.id
                              WHERE t.status = 'active'
                              ORDER BY t.test_name ASC");
    if ($testsResult) {
        while ($row = $testsResult->fetch_assoc()) {
            $allTests[] = $row;
        }
    }
    
    // Get doctors for dropdown
    $doctors = [];
    $doctorsResult = $db->query("SELECT d.id, CONCAT(u.title, ' ', u.first_name, ' ', u.last_name) as name 
                                FROM doctors d
                                JOIN users u ON d.user_id = u.id
                                WHERE d.status = 'active'");
    if ($doctorsResult) {
        while ($row = $doctorsResult->fetch_assoc()) {
            $doctors[] = $row;
        }
    }
    
    $this->view('lab/view-prescription-tests', [
        'prescription' => $prescription,
        'labTests' => $labTests,
        'existingOrder' => $existingOrder,
        'allTests' => $allTests,
        'doctors' => $doctors
    ], 'Prescription Lab Tests');
}

public function createOrderFromPrescription() {
    header('Content-Type: application/json');
    
    try {
        $db = $this->db;
        
        $prescriptionId = isset($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : 0;
        $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
        $orderDate = isset($_POST['order_date']) ? $db->real_escape_string($_POST['order_date']) : date('Y-m-d');
        $priority = isset($_POST['priority']) ? $db->real_escape_string($_POST['priority']) : 'routine';
        $clinicalDiagnosis = isset($_POST['clinical_diagnosis']) ? $db->real_escape_string($_POST['clinical_diagnosis']) : '';
        $notes = isset($_POST['notes']) ? $db->real_escape_string($_POST['notes']) : '';
        
        // Get test IDs from JSON
        $testIds = isset($_POST['test_ids']) ? json_decode($_POST['test_ids'], true) : [];
        if (!is_array($testIds)) {
            $testIds = [];
        }
        
        // If no test_ids in JSON, try to get from POST array
        if (empty($testIds) && isset($_POST['test_ids']) && is_array($_POST['test_ids'])) {
            $testIds = $_POST['test_ids'];
        }
        
        // Get payment information
        $paidAmount = isset($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : 0;
        $subtotal = isset($_POST['subtotal']) ? (float)$_POST['subtotal'] : 0;
        $totalAmount = isset($_POST['total_amount']) ? (float)$_POST['total_amount'] : 0;
        $paymentMethod = isset($_POST['payment_method']) ? $db->real_escape_string($_POST['payment_method']) : 'cash';
        
        if ($prescriptionId == 0) {
            echo json_encode(['success' => false, 'message' => 'Prescription ID is required']);
            exit;
        }
        
        if ($doctorId == 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a doctor']);
            exit;
        }
        
        if (empty($testIds)) {
            echo json_encode(['success' => false, 'message' => 'Please select at least one test']);
            exit;
        }
        
        // Get prescription data
        $prescriptionResult = $db->query("SELECT patient_id FROM prescriptions WHERE id = $prescriptionId");
        if (!$prescriptionResult || $prescriptionResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Prescription not found']);
            exit;
        }
        $prescription = $prescriptionResult->fetch_assoc();
        $patientId = $prescription['patient_id'];
        
        // Check if order already exists
        $existing = $db->query("SELECT id FROM lab_test_orders WHERE prescription_id = $prescriptionId LIMIT 1");
        if ($existing && $existing->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'An order already exists for this prescription']);
            exit;
        }
        
        // Get test details
        $testDetails = [];
        $calculatedSubtotal = 0;
        
        foreach ($testIds as $testId) {
            $testId = (int)$testId;
            if ($testId > 0) {
                $testResult = $db->query("SELECT id, test_name, price FROM lab_tests WHERE id = $testId AND status = 'active'");
                if ($testResult && $testResult->num_rows > 0) {
                    $test = $testResult->fetch_assoc();
                    $testDetails[] = $test;
                    $calculatedSubtotal += (float)$test['price'];
                }
            }
        }
        
        if (empty($testDetails)) {
            echo json_encode(['success' => false, 'message' => 'No valid tests selected']);
            exit;
        }
        
        // Use provided subtotal or calculate (NO TAX)
        $subtotal = $subtotal > 0 ? $subtotal : $calculatedSubtotal;
        $totalAmount = $totalAmount > 0 ? $totalAmount : $subtotal; // NO TAX
        $paidAmount = min($paidAmount, $totalAmount);
        $balanceAmount = $totalAmount - $paidAmount;
        
        $paymentStatus = 'pending';
        if ($paidAmount >= $totalAmount) {
            $paymentStatus = 'paid';
        } elseif ($paidAmount > 0) {
            $paymentStatus = 'partial';
        }
        
        $orderedBy = $_SESSION['user_id'] ?? 1;
        $orderNumber = 'LAB' . date('Ymd') . rand(100, 999);
        
        $db->begin_transaction();
        
        // Generate bill (NO TAX)
        $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
        $billDate = date('Y-m-d');
        
        $sql = "INSERT INTO bills (
                    bill_number, patient_id, bill_type, bill_date,
                    subtotal, discount_amount, discount_percentage,
                    tax_amount, total_amount, paid_amount, balance_amount,
                    payment_status, reference_type, payment_method,
                    created_by, notes
                ) VALUES (
                    '$billNumber', $patientId, 'lab_test', '$billDate',
                    $subtotal, 0, 0,
                    0,  -- NO TAX
                    $totalAmount, $paidAmount, $balanceAmount,
                    '$paymentStatus', 'lab_order', '$paymentMethod',
                    $orderedBy, 'Lab test order from prescription #$prescriptionId'
                )";
        
        if (!$db->query($sql)) {
            throw new Exception('Failed to generate bill: ' . $db->error);
        }
        
        $billId = $db->insert_id;
        
        // Insert order
        $sql = "INSERT INTO lab_test_orders (
                    order_number, patient_id, doctor_id, prescription_id,
                    order_date, priority, clinical_diagnosis, notes,
                    status, ordered_by, bill_id,
                    discount_amount, discount_type, discount_value
                ) VALUES (
                    '$orderNumber', $patientId, $doctorId, $prescriptionId,
                    '$orderDate', '$priority', '$clinicalDiagnosis', '$notes',
                    'ordered', $orderedBy, $billId,
                    0, 'fixed', 0
                )";
        
        if (!$db->query($sql)) {
            throw new Exception('Failed to create order: ' . $db->error);
        }
        
        $orderId = $db->insert_id;
        
        // Update bill with reference_id
        $db->query("UPDATE bills SET reference_id = $orderId WHERE id = $billId");
        
        // Insert order items and bill items
        foreach ($testDetails as $test) {
            $testId = $test['id'];
            $testName = $db->real_escape_string($test['test_name']);
            $price = (float)$test['price'];
            
            // Insert into lab_test_order_items
            $db->query("INSERT INTO lab_test_order_items (order_id, test_id, status) 
                       VALUES ($orderId, $testId, 'pending')");
            
            // Insert into bill_items
            $db->query("INSERT INTO bill_items (bill_id, item_type, description, quantity, unit_price, total_amount) 
                       VALUES ($billId, 'lab_test', '$testName', 1, $price, $price)");
        }
        
        // If payment was made, insert payment record
        if ($paidAmount > 0) {
            $paymentNumber = 'PAY' . date('Ymd') . rand(1000, 9999);
            $db->query("INSERT INTO payments (
                            payment_number, bill_id, patient_id, amount, 
                            payment_method, payment_date, received_by, notes
                        ) VALUES (
                            '$paymentNumber', $billId, $patientId, $paidAmount,
                            '$paymentMethod', CURDATE(), $orderedBy, 
                            'Payment for lab order $orderNumber'
                        )");
        }
        
        $db->commit();
        
        // Return JSON with redirect to orders page
        echo json_encode([
            'success' => true,
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'bill_id' => $billId,
            'bill_number' => $billNumber,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'payment_status' => $paymentStatus,
            'message' => 'Lab order created successfully from prescription'
        ]);
        
    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollback();
        }
        error_log("Create order from prescription error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}


// ================================================================
// GET PRESCRIPTION LAB TESTS - API
// ================================================================
public function apiGetPrescriptionLabTests() {
    header('Content-Type: application/json');
    
    $prescriptionId = isset($_GET['prescription_id']) ? (int)$_GET['prescription_id'] : 0;
    
    if ($prescriptionId == 0) {
        echo json_encode(['success' => false, 'message' => 'Prescription ID required']);
        exit;
    }
    
    $prescriptionModel = new Prescription();
    $labTests = $prescriptionModel->getPrescriptionLabTests($prescriptionId);
    
    // Get full test details
    $db = $this->db;
    $testDetails = [];
    foreach ($labTests as $test) {
        $testId = $test['test_id'];
        if ($testId) {
            $result = $db->query("SELECT t.*, c.name as category_name 
                                 FROM lab_tests t
                                 LEFT JOIN lab_test_categories c ON t.category_id = c.id
                                 WHERE t.id = $testId");
            if ($result && $result->num_rows > 0) {
                $details = $result->fetch_assoc();
                $testDetails[] = array_merge($test, $details);
            } else {
                // Test might have been deleted, but we still have the name
                $testDetails[] = $test;
            }
        } else {
            $testDetails[] = $test;
        }
    }
    
    echo json_encode(['success' => true, 'tests' => $testDetails]);
    exit;
}

}
?>