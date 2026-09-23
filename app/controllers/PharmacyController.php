<?php
// app/controllers/PharmacyController.php
// COMPLETE VERSION with per-item discount, lab accessories auto-add, overall discount cap (max 20%)

class PharmacyController extends Controller {

    private $prescriptionModel;
    private $patientModel;
    private $doctorModel;
    
    public function __construct() {
        parent::__construct();
        
        // Initialize models
        $this->prescriptionModel = new Prescription();
        $this->patientModel = new Patient();
        $this->doctorModel = new Doctor();
    }
    
    // ==================== DASHBOARD ====================
    public function dashboard() {
        $this->checkAuth();
        
        $prescriptionList = [];
        $todaySales = ['total' => 0];
        $lowStockItems = [];
        $expiringItems = [];
        
        $pendingQuery = "SELECT p.*, pat.first_name, pat.last_name, pat.phone,
                        u.first_name as doctor_fname, u.last_name as doctor_lname,
                        (SELECT COUNT(*) FROM prescription_items WHERE prescription_id = p.id) as item_count
                        FROM prescriptions p
                        JOIN patients pat ON p.patient_id = pat.id
                        JOIN doctors d ON p.doctor_id = d.id
                        JOIN users u ON d.user_id = u.id
                        WHERE p.status = 'issued'
                        ORDER BY p.created_at DESC
                        LIMIT 10";
        
        $pendingPrescriptions = $this->db->query($pendingQuery);
        if($pendingPrescriptions && $pendingPrescriptions->num_rows > 0) {
            while($row = $pendingPrescriptions->fetch_assoc()) {
                $row['patient_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $row['doctor_name'] = $row['doctor_fname'] . ' ' . $row['doctor_lname'];
                $prescriptionList[] = $row;
            }
        }
        
        $salesResult = $this->db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM pharmacy_sales WHERE DATE(created_at) = CURDATE()");
        if($salesResult && $salesResult->num_rows > 0) {
            $todaySales = $salesResult->fetch_assoc();
        }
        
        $lowStockQuery = "SELECT ms.*, m.medicine_name as item_name, m.medicine_code
                         FROM medicine_stock ms
                         JOIN medicines m ON ms.medicine_id = m.id
                         WHERE ms.quantity <= 10 AND ms.expiry_date > CURDATE()
                         LIMIT 10";
        
        $lowStock = $this->db->query($lowStockQuery);
        if($lowStock && $lowStock->num_rows > 0) {
            while($row = $lowStock->fetch_assoc()) {
                $row['current_stock'] = $row['quantity'];
                $lowStockItems[] = $row;
            }
        }
        
        $expiringQuery = "SELECT ms.*, m.medicine_name as item_name
                         FROM medicine_stock ms
                         JOIN medicines m ON ms.medicine_id = m.id
                         WHERE ms.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                         AND ms.expiry_date > CURDATE()
                         LIMIT 10";
        
        $expiring = $this->db->query($expiringQuery);
        if($expiring && $expiring->num_rows > 0) {
            while($row = $expiring->fetch_assoc()) {
                $expiringItems[] = $row;
            }
        }
        
        $content = $this->renderView('pharmacy/dashboard', [
            'pendingPrescriptions' => $prescriptionList,
            'todaySales' => $todaySales,
            'lowStockItems' => $lowStockItems,
            'expiringItems' => $expiringItems
        ]);
        
        $this->renderLayout('Pharmacy Dashboard', $content);
    }

    // ==================== DASHBOARD STATS (AJAX) ====================
    public function dashboardStats() {
        header('Content-Type: application/json');
        
        // Get low stock count
        $lowStockQuery = "SELECT COUNT(DISTINCT ms.id) as count 
                          FROM medicine_stock ms
                          WHERE ms.quantity <= 10 AND ms.expiry_date > CURDATE()";
        $lowStockResult = $this->db->query($lowStockQuery);
        $lowStockCount = $lowStockResult ? $lowStockResult->fetch_assoc()['count'] : 0;
        
        // Get expiring soon count
        $expiringQuery = "SELECT COUNT(*) as count 
                          FROM medicine_stock 
                          WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
                          AND expiry_date > CURDATE()";
        $expiringResult = $this->db->query($expiringQuery);
        $expiringCount = $expiringResult ? $expiringResult->fetch_assoc()['count'] : 0;
        
        // Get today's sales
        $salesQuery = "SELECT COALESCE(SUM(total_amount), 0) as total 
                       FROM pharmacy_sales 
                       WHERE DATE(created_at) = CURDATE()";
        $salesResult = $this->db->query($salesQuery);
        $todaySales = $salesResult ? $salesResult->fetch_assoc()['total'] : 0;
        
        echo json_encode([
            'success' => true,
            'low_stock_count' => $lowStockCount,
            'expiring_count' => $expiringCount,
            'today_sales' => $todaySales
        ]);
        exit;
    }
    
    // ==================== MEDICINES MANAGEMENT WITH PAGINATION ====================
    public function medicines() {
        $this->checkAuth();
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $where = "WHERE m.status = 'active'";
        if(!empty($search)) {
            $search = $this->db->real_escape_string($search);
            $where .= " AND (m.medicine_name LIKE '%$search%' OR m.generic_name LIKE '%$search%' OR m.medicine_code LIKE '%$search%')";
        }
        if($categoryId > 0) {
            $where .= " AND m.category_id = $categoryId";
        }
        
        $countQuery = "SELECT COUNT(*) as total FROM medicines m $where";
        $countResult = $this->db->query($countQuery);
        $totalRecords = $countResult->fetch_assoc()['total'];
        $totalPages = ceil($totalRecords / $limit);
        
        $query = "SELECT m.*, c.name as category_name,
                         COALESCE(SUM(ms.quantity), 0) as current_stock
                  FROM medicines m
                  JOIN medicine_categories c ON m.category_id = c.id
                  LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                  $where
                  GROUP BY m.id
                  ORDER BY m.medicine_name ASC
                  LIMIT $offset, $limit";
        
        $result = $this->db->query($query);
        $medicineList = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $medicineList[] = $row;
            }
        }
        
        $categories = $this->db->query("SELECT * FROM medicine_categories WHERE status = 'active' ORDER BY name");
        $categoryList = [];
        if($categories) {
            while($row = $categories->fetch_assoc()) { 
                $categoryList[] = $row; 
            }
        }
        
        $content = $this->renderView('pharmacy/medicines', [
            'medicines' => $medicineList,
            'categories' => $categoryList,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
            'search' => $search,
            'selectedCategory' => $categoryId
        ]);
        $this->renderLayout('Medicine Management', $content);
    }
    
    // ==================== GET MEDICINE (AJAX) ====================
    public function getMedicine($id) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $id = (int)$id;
        
        if($id <= 0 && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
        }
        
        if($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid medicine ID']);
            exit;
        }
        
        $query = "SELECT m.*, c.name as category_name,
                         COALESCE(SUM(ms.quantity), 0) as current_stock
                  FROM medicines m
                  LEFT JOIN medicine_categories c ON m.category_id = c.id
                  LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                  WHERE m.id = $id
                  GROUP BY m.id";
        
        $result = $this->db->query($query);
        
        if($result && $result->num_rows > 0) {
            $medicine = $result->fetch_assoc();
            
            $stockQuery = "SELECT * FROM medicine_stock WHERE medicine_id = $id ORDER BY expiry_date ASC";
            $stockResult = $this->db->query($stockQuery);
            $stockBatches = [];
            if($stockResult) {
                while($row = $stockResult->fetch_assoc()) {
                    $stockBatches[] = $row;
                }
            }
            $medicine['stock_batches'] = $stockBatches;
            
            echo json_encode(['success' => true, 'medicine' => $medicine]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Medicine not found']);
        }
        exit;
    }
    
    // ==================== ADD MEDICINE ====================
    public function addMedicine() {
        header('Content-Type: application/json');
        
        $medicineCode = 'MED' . date('Ymd') . rand(100, 999);
        $medicineName = $this->db->real_escape_string($_POST['medicine_name']);
        $genericName = $this->db->real_escape_string($_POST['generic_name'] ?? '');
        $categoryId = (int)$_POST['category_id'];
        $manufacturer = $this->db->real_escape_string($_POST['manufacturer'] ?? '');
        $strength = $this->db->real_escape_string($_POST['strength'] ?? '');
        $dosageForm = $this->db->real_escape_string($_POST['dosage_form'] ?? 'Tablet');
        $unitOfMeasure = $this->db->real_escape_string($_POST['unit_of_measure']);
        $purchasePrice = (float)$_POST['purchase_price'];
        $sellingPrice = (float)$_POST['selling_price'];
        $mrp = (float)($_POST['mrp'] ?? 0);
        $taxPercentage = (float)($_POST['tax_percentage'] ?? 0);
        $requiresPrescription = isset($_POST['requires_prescription']) ? 1 : 0;
        $description = $this->db->real_escape_string($_POST['description'] ?? '');
        $sideEffects = $this->db->real_escape_string($_POST['side_effects'] ?? '');
        $storageCondition = $this->db->real_escape_string($_POST['storage_condition'] ?? '');
        
        $query = "INSERT INTO medicines (medicine_code, medicine_name, generic_name, category_id, manufacturer,
                  strength, dosage_form, unit_of_measure, purchase_price, selling_price, mrp, tax_percentage,
                  requires_prescription, description, side_effects, storage_condition, status)
                  VALUES ('$medicineCode', '$medicineName', '$genericName', $categoryId, '$manufacturer',
                  '$strength', '$dosageForm', '$unitOfMeasure', $purchasePrice, $sellingPrice, $mrp, $taxPercentage,
                  $requiresPrescription, '$description', '$sideEffects', '$storageCondition', 'active')";
        
        if($this->db->query($query)) {
            $medicineId = $this->db->insert_id;
            
            if(isset($_POST['initial_quantity']) && $_POST['initial_quantity'] > 0) {
                $batchNumber = $this->db->real_escape_string($_POST['batch_number'] ?? 'BCH001');
                $expiryDate = $this->db->real_escape_string($_POST['expiry_date']);
                $quantity = (int)$_POST['initial_quantity'];
                $location = $this->db->real_escape_string($_POST['storage_location'] ?? '');
                
                $stockQuery = "INSERT INTO medicine_stock (medicine_id, batch_number, expiry_date, 
                              quantity, purchase_price, selling_price, location, status, created_at)
                              VALUES ($medicineId, '$batchNumber', '$expiryDate',
                              $quantity, $purchasePrice, $sellingPrice, '$location', 'in_stock', NOW())";
                $this->db->query($stockQuery);
            }
            
            echo json_encode(['success' => true, 'message' => 'Medicine added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }
    
    // ==================== UPDATE MEDICINE ====================
    public function updateMedicine() {
        header('Content-Type: application/json');
        
        $id = (int)$_POST['medicine_id'];
        $medicineName = $this->db->real_escape_string($_POST['medicine_name']);
        $genericName = $this->db->real_escape_string($_POST['generic_name'] ?? '');
        $categoryId = (int)$_POST['category_id'];
        $manufacturer = $this->db->real_escape_string($_POST['manufacturer'] ?? '');
        $strength = $this->db->real_escape_string($_POST['strength'] ?? '');
        $dosageForm = $this->db->real_escape_string($_POST['dosage_form'] ?? '');
        $unitOfMeasure = $this->db->real_escape_string($_POST['unit_of_measure']);
        $purchasePrice = (float)$_POST['purchase_price'];
        $sellingPrice = (float)$_POST['selling_price'];
        $mrp = (float)($_POST['mrp'] ?? 0);
        $taxPercentage = (float)($_POST['tax_percentage'] ?? 0);
        $requiresPrescription = isset($_POST['requires_prescription']) ? 1 : 0;
        $description = $this->db->real_escape_string($_POST['description'] ?? '');
        $sideEffects = $this->db->real_escape_string($_POST['side_effects'] ?? '');
        $storageCondition = $this->db->real_escape_string($_POST['storage_condition'] ?? '');
        $status = $this->db->real_escape_string($_POST['status'] ?? 'active');
        
        $query = "UPDATE medicines SET 
                    medicine_name = '$medicineName',
                    generic_name = '$genericName',
                    category_id = $categoryId,
                    manufacturer = '$manufacturer',
                    strength = '$strength',
                    dosage_form = '$dosageForm',
                    unit_of_measure = '$unitOfMeasure',
                    purchase_price = $purchasePrice,
                    selling_price = $sellingPrice,
                    mrp = $mrp,
                    tax_percentage = $taxPercentage,
                    requires_prescription = $requiresPrescription,
                    description = '$description',
                    side_effects = '$sideEffects',
                    storage_condition = '$storageCondition',
                    status = '$status',
                    updated_at = NOW()
                  WHERE id = $id";
        
        if($this->db->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Medicine updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }
    
    // ==================== STOCK MANAGEMENT ====================
    public function stock() {
        $this->checkAuth();
        
        $stocks = $this->db->query("SELECT ms.*, m.medicine_name, m.medicine_code, m.strength,
                                   c.name as category_name
                                   FROM medicine_stock ms
                                   JOIN medicines m ON ms.medicine_id = m.id
                                   JOIN medicine_categories c ON m.category_id = c.id
                                   ORDER BY ms.expiry_date ASC");
        
        $stockList = [];
        if($stocks && $stocks->num_rows > 0) {
            while($row = $stocks->fetch_assoc()) {
                $reorderLevel = 10;
                $row['status'] = $row['expiry_date'] < date('Y-m-d') ? 'expired' : 
                                ($row['quantity'] <= $reorderLevel ? 'low_stock' : 'in_stock');
                $stockList[] = $row;
            }
        }
        
        $medicines = $this->db->query("SELECT id, medicine_name, medicine_code FROM medicines WHERE status = 'active' ORDER BY medicine_name");
        $medicineList = [];
        if($medicines && $medicines->num_rows > 0) {
            while($row = $medicines->fetch_assoc()) { 
                $medicineList[] = $row; 
            }
        }
        
        $content = $this->renderView('pharmacy/stock', [
            'stocks' => $stockList,
            'medicines' => $medicineList
        ]);
        $this->renderLayout('Stock Management', $content);
    }
    
    // ==================== ADD STOCK ====================
    public function addStock() {
        header('Content-Type: application/json');
        
        $medicineId = (int)$_POST['medicine_id'];
        $batchNumber = isset($_POST['batch_number']) ? $this->db->real_escape_string($_POST['batch_number']) : '';
        $expiryDate = isset($_POST['expiry_date']) ? $this->db->real_escape_string($_POST['expiry_date']) : '';
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
        $purchasePrice = isset($_POST['purchase_price']) ? (float)$_POST['purchase_price'] : 0;
        $sellingPrice = isset($_POST['selling_price']) ? (float)$_POST['selling_price'] : 0;
        $location = isset($_POST['location']) ? $this->db->real_escape_string($_POST['location']) : '';
        $manufacturingDate = isset($_POST['manufacturing_date']) ? $this->db->real_escape_string($_POST['manufacturing_date']) : null;
        
        if($medicineId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid medicine ID']);
            exit;
        }
        
        if(empty($batchNumber)) {
            echo json_encode(['success' => false, 'message' => 'Batch number is required']);
            exit;
        }
        
        if(empty($expiryDate)) {
            echo json_encode(['success' => false, 'message' => 'Expiry date is required']);
            exit;
        }
        
        if($quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
            exit;
        }
        
        if($purchasePrice <= 0) {
            echo json_encode(['success' => false, 'message' => 'Purchase price must be greater than 0']);
            exit;
        }
        
        if($sellingPrice <= 0) {
            echo json_encode(['success' => false, 'message' => 'Selling price must be greater than 0']);
            exit;
        }
        
        $checkMedicine = $this->db->query("SELECT id, medicine_name FROM medicines WHERE id = $medicineId AND status = 'active'");
        if(!$checkMedicine || $checkMedicine->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Medicine not found']);
            exit;
        }
        
        $medicine = $checkMedicine->fetch_assoc();
        
        $query = "INSERT INTO medicine_stock (medicine_id, batch_number, expiry_date, 
                  quantity, purchase_price, selling_price, location, status, created_at";
        
        if($manufacturingDate && $manufacturingDate != '') {
            $query .= ", manufacturing_date";
        }
        
        $query .= ") VALUES ($medicineId, '$batchNumber', '$expiryDate',
                  $quantity, $purchasePrice, $sellingPrice, '$location', 'in_stock', NOW()";
        
        if($manufacturingDate && $manufacturingDate != '') {
            $query .= ", '$manufacturingDate'";
        }
        
        $query .= ")";
        
        if($this->db->query($query)) {
            $this->db->query("UPDATE medicines SET selling_price = $sellingPrice, purchase_price = $purchasePrice WHERE id = $medicineId");
            echo json_encode(['success' => true, 'message' => 'Stock added successfully for ' . $medicine['medicine_name']]);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }
    
    // ==================== DELETE STOCK ====================
    public function deleteStock() {
        header('Content-Type: application/json');
        
        $stockId = (int)$_POST['stock_id'];
        
        if($this->db->query("DELETE FROM medicine_stock WHERE id = $stockId")) {
            echo json_encode(['success' => true, 'message' => 'Stock deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }
    
    // ==================== STOCK HISTORY ====================
    public function stockHistory($id) {
        header('Content-Type: application/json');
        
        $id = (int)$id;
        
        if($id <= 0 && isset($_GET['id'])) {
            $id = (int)$_GET['id'];
        }
        
        if($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid medicine ID']);
            exit;
        }
        
        $query = "SELECT ms.*, m.medicine_name
                  FROM medicine_stock ms
                  JOIN medicines m ON ms.medicine_id = m.id
                  WHERE ms.medicine_id = $id
                  ORDER BY ms.created_at DESC";
        
        $result = $this->db->query($query);
        $history = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'history' => $history]);
        exit;
    }
    
    // ==================== GET STOCK DATA (AJAX) ====================
    public function stockData() {
        header('Content-Type: application/json');
        
        $query = "SELECT ms.id, ms.medicine_id, ms.batch_number, ms.expiry_date, ms.quantity, 
                         ms.purchase_price, ms.selling_price, ms.location, ms.created_at,
                         ms.status, ms.unit_of_measure, ms.reorder_level,
                         m.medicine_name, m.medicine_code, m.strength
                  FROM medicine_stock ms
                  JOIN medicines m ON ms.medicine_id = m.id
                  ORDER BY ms.expiry_date ASC";
        
        $result = $this->db->query($query);
        $stocks = [];
        
        if($result) {
            while($row = $result->fetch_assoc()) {
                // Set default values
                $row['reorder_level'] = $row['reorder_level'] ?? 10;
                $row['unit_of_measure'] = $row['unit_of_measure'] ?? 'Strip';
                
                // Calculate status if not set or if it's in_stock but should be expired
                $today = date('Y-m-d');
                if($row['expiry_date'] < $today) {
                    $row['status'] = 'expired';
                } elseif(empty($row['status']) || $row['status'] == 'in_stock') {
                    if($row['quantity'] <= $row['reorder_level']) {
                        $row['status'] = 'low_stock';
                    } else {
                        $row['status'] = 'in_stock';
                    }
                }
                
                $stocks[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'stocks' => $stocks]);
        exit;
    }
    
    // ==================== ADD CATEGORY ====================
    public function addCategory() {
        header('Content-Type: application/json');
        
        $name = $this->db->real_escape_string($_POST['name']);
        $code = strtoupper(substr($name, 0, 3)) . rand(100, 999);
        
        $check = $this->db->query("SELECT id FROM medicine_categories WHERE name = '$name'");
        if($check && $check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Category already exists']);
            exit;
        }
        
        $query = "INSERT INTO medicine_categories (name, code, status) VALUES ('$name', '$code', 'active')";
        
        if($this->db->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Category added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }
    
    // ==================== UPDATE CATEGORY ====================
    public function updateCategory() {
        header('Content-Type: application/json');
        
        $id = (int)$_POST['category_id'];
        $name = $this->db->real_escape_string(trim($_POST['name']));
        
        if($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid category ID']);
            exit;
        }
        
        if(empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Category name is required']);
            exit;
        }
        
        $checkQuery = "SELECT id FROM medicine_categories WHERE name = '$name' AND id != $id";
        $checkResult = $this->db->query($checkQuery);
        if($checkResult && $checkResult->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Category with this name already exists']);
            exit;
        }
        
        $query = "UPDATE medicine_categories SET name = '$name' WHERE id = $id";
        
        if($this->db->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }
    
    // ==================== DELETE CATEGORY ====================
    public function deleteCategory() {
        header('Content-Type: application/json');
        
        $id = (int)$_POST['category_id'];
        
        $check = $this->db->query("SELECT id FROM medicines WHERE category_id = $id LIMIT 1");
        if($check && $check->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete: Category has medicines']);
            exit;
        }
        
        $query = "DELETE FROM medicine_categories WHERE id = $id";
        
        if($this->db->query($query)) {
            echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => $this->db->error]);
        }
        exit;
    }
    
    // ==================== GET CATEGORIES ====================
    public function getCategories() {
        header('Content-Type: application/json');
        
        $query = "SELECT * FROM medicine_categories WHERE status = 'active' ORDER BY name";
        $result = $this->db->query($query);
        $categories = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'categories' => $categories]);
        exit;
    }
    
    // ==================== POINT OF SALE ====================
    public function pos() {
        $this->checkAuth();
        
        $medicines = $this->db->query("SELECT m.id, m.medicine_name, m.generic_name, m.selling_price,
                                      COALESCE(SUM(ms.quantity), 0) as current_stock
                                      FROM medicines m
                                      LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                                      WHERE m.status = 'active'
                                      GROUP BY m.id
                                      HAVING current_stock > 0
                                      ORDER BY m.medicine_name ASC
                                      LIMIT 100");
        
        $medicineList = [];
        if($medicines && $medicines->num_rows > 0) {
            while($row = $medicines->fetch_assoc()) {
                $medicineList[] = $row;
            }
        }
        
        $doctorServicesQuery = "SELECT ds.*, d.id as doctor_id, CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                               FROM doctor_services ds
                               JOIN doctors d ON ds.doctor_id = d.id
                               JOIN users u ON d.user_id = u.id
                               WHERE ds.status = 'active'
                               ORDER BY u.first_name ASC, ds.service_name ASC";
        
        $doctorServicesResult = $this->db->query($doctorServicesQuery);
        $doctorServicesList = [];
        if($doctorServicesResult && $doctorServicesResult->num_rows > 0) {
            while($row = $doctorServicesResult->fetch_assoc()) {
                $doctorServicesList[] = $row;
            }
        }
        
        $labTests = $this->db->query("SELECT id, test_name, price FROM lab_tests WHERE status = 'active' ORDER BY test_name");
        $testList = [];
        if($labTests && $labTests->num_rows > 0) {
            while($row = $labTests->fetch_assoc()) {
                $testList[] = $row;
            }
        }
        
        if(!isset($_SESSION['pharmacy_cart']) || !is_array($_SESSION['pharmacy_cart'])) {
            $_SESSION['pharmacy_cart'] = [];
        }
        
        $content = $this->renderView('pharmacy/pos', [
            'medicines' => $medicineList,
            'doctorServices' => $doctorServicesList,
            'labTests' => $testList
        ]);
        $this->renderLayout('Point of Sale', $content);
    }
    
    // ==================== PRESCRIPTIONS ====================
    public function prescriptions() {
        $this->checkAuth();
        
        $prescriptions = $this->db->query("SELECT p.*, pat.first_name, pat.last_name, pat.phone,
                                          u.first_name as doctor_fname, u.last_name as doctor_lname
                                          FROM prescriptions p
                                          JOIN patients pat ON p.patient_id = pat.id
                                          JOIN doctors d ON p.doctor_id = d.id
                                          JOIN users u ON d.user_id = u.id
                                          WHERE p.status IN ('issued', 'partially_dispensed')
                                          ORDER BY p.created_at DESC");
        
        $prescriptionList = [];
        if($prescriptions && $prescriptions->num_rows > 0) {
            while($row = $prescriptions->fetch_assoc()) {
                $row['patient_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $row['doctor_name'] = $row['doctor_fname'] . ' ' . $row['doctor_lname'];
                $prescriptionList[] = $row;
            }
        }
        
        $content = $this->renderView('pharmacy/prescriptions', ['prescriptions' => $prescriptionList]);
        $this->renderLayout('E-Prescriptions', $content);
    }
    
    // ==================== SALES HISTORY ====================
    public function sales() {
        $this->checkAuth();
        
        $sales = $this->db->query("SELECT s.*, p.first_name, p.last_name, p.phone,
                                  u.first_name as cashier_name
                                  FROM pharmacy_sales s
                                  LEFT JOIN patients p ON s.patient_id = p.id
                                  JOIN users u ON s.sold_by = u.id
                                  ORDER BY s.created_at DESC
                                  LIMIT 100");
        
        $saleList = [];
        if($sales && $sales->num_rows > 0) {
            while($row = $sales->fetch_assoc()) {
                $row['patient_name'] = ($row['first_name'] ? $row['first_name'] . ' ' . $row['last_name'] : 'Walk-in Customer');
                $saleList[] = $row;
            }
        }
        
        $content = $this->renderView('pharmacy/sales', ['sales' => $saleList]);
        $this->renderLayout('Sales History', $content);
    }
    
/**
 * Dispense prescription - Show dispense form with merged medicines
 * FIXED: Properly merges same medicines while preserving ALL frequency/duration/instruction details
 */
public function dispense($id) {
    $this->checkAuth();
    $this->checkPermission('dispense_prescriptions');
    
    // Get prescription details
    $sql = "SELECT p.*, 
                   CONCAT(pa.first_name, ' ', pa.last_name) as patient_name,
                   pa.patient_code, pa.phone,
                   CONCAT(u.first_name, ' ', u.last_name) as doctor_name
            FROM prescriptions p 
            JOIN patients pa ON p.patient_id = pa.id 
            JOIN doctors d ON p.doctor_id = d.id 
            JOIN users u ON d.user_id = u.id 
            WHERE p.id = ?";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prescription = $result->fetch_assoc();
    $stmt->close();
    
    if (!$prescription) {
        $_SESSION['error'] = 'Prescription not found';
        $this->redirect('/pharmacy/prescriptions');
        return;
    }
    
    // Get prescription items with medicine details
    $sql = "SELECT pi.*, 
                   m.id as medicine_id,
                   m.medicine_name, 
                   m.strength, 
                   m.generic_name,
                   m.selling_price,
                   ms.quantity as stock_quantity,
                   ms.batch_number,
                   ms.expiry_date,
                   ms.id as stock_id
            FROM prescription_items pi 
            LEFT JOIN medicines m ON pi.drug_id = m.id 
            LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id 
            WHERE pi.prescription_id = ?
            ORDER BY m.medicine_name ASC, pi.id ASC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $mergedItems = [];
    
    while ($row = $result->fetch_assoc()) {
        // Use medicine_id as key for merging, or drug_id if no medicine_id
        $mergeKey = (!empty($row['medicine_id']) && $row['medicine_id'] > 0) ? $row['medicine_id'] : 'drug_' . $row['drug_id'];
        
        // Debug logging
        error_log("Processing - ID: {$row['id']}, Medicine: {$row['medicine_name']}, Freq: {$row['frequency']}, Dur: {$row['duration']}");
        
        if (!isset($mergedItems[$mergeKey])) {
            // First occurrence - create base item
            $mergedItems[$mergeKey] = [
                'id' => $row['id'],
                'drug_id' => $row['drug_id'],
                'medicine_id' => $row['medicine_id'] ?? null,
                'drug_name' => $row['drug_name'] ?? '',
                'medicine_name' => $row['medicine_name'] ?? $row['drug_name'] ?? 'Unknown Medicine',
                'strength' => $row['strength'] ?? '',
                'generic_name' => $row['generic_name'] ?? '',
                'selling_price' => $row['selling_price'] ?? 0,
                'dosage' => $row['dosage'] ?? '',
                'quantity' => (int)$row['quantity'],
                'stock_quantity' => $row['stock_quantity'] ?? 0,
                'batch_number' => $row['batch_number'] ?? '',
                'expiry_date' => $row['expiry_date'] ?? '',
                'stock_id' => $row['stock_id'] ?? '',
                'details' => []
            ];
        } else {
            // Merge - add quantities
            $mergedItems[$mergeKey]['quantity'] += (int)$row['quantity'];
            // Update stock if this batch has more stock
            if (($row['stock_quantity'] ?? 0) > ($mergedItems[$mergeKey]['stock_quantity'] ?? 0)) {
                $mergedItems[$mergeKey]['stock_quantity'] = $row['stock_quantity'];
                $mergedItems[$mergeKey]['batch_number'] = $row['batch_number'] ?? '';
                $mergedItems[$mergeKey]['expiry_date'] = $row['expiry_date'] ?? '';
                $mergedItems[$mergeKey]['stock_id'] = $row['stock_id'] ?? '';
            }
        }
        
        // ============================================================
        // ADD FREQUENCY/DURATION/INSTRUCTION DETAILS - PRESERVE ALL
        // ============================================================
        $detail = [];
        
        // Always add frequency if exists
        if (!empty($row['frequency'])) {
            $detail['frequency'] = $row['frequency'];
        }
        
        // Always add duration if exists
        if (!empty($row['duration'])) {
            $detail['duration'] = $row['duration'];
        }
        
        // Always add instruction if exists (check both instruction and instructions fields)
        if (!empty($row['instruction'])) {
            $detail['instruction'] = $row['instruction'];
        } elseif (!empty($row['instructions'])) {
            $detail['instruction'] = $row['instructions'];
        }
        
        // Always add relation_to_food if exists
        if (!empty($row['relation_to_food'])) {
            $detail['relation_to_food'] = $row['relation_to_food'];
        }
        
        // Add the detail to the details array (preserve ALL details)
        $mergedItems[$mergeKey]['details'][] = $detail;
        
        error_log("Added detail for {$mergedItems[$mergeKey]['medicine_name']}: " . print_r($detail, true));
    }
    
    // Convert merged items to array
    $items = array_values($mergedItems);
    
    // Debug - log final merged items
    foreach ($items as $index => $item) {
        error_log("Final Item " . ($index+1) . ": " . $item['medicine_name'] . 
                  " - Quantity: " . $item['quantity'] . 
                  " - Details count: " . count($item['details']));
        foreach ($item['details'] as $dIdx => $detail) {
            error_log("  Detail " . ($dIdx+1) . ": " . print_r($detail, true));
        }
    }
    
    // Check if already dispensed
    if ($prescription['pharmacy_status'] == 'dispensed' || $prescription['pharmacy_status'] == 'collected') {
        $_SESSION['warning'] = 'This prescription has already been dispensed.';
    }
    
    // Get sale items if already dispensed
    $saleItems = [];
    if ($prescription['pharmacy_status'] == 'dispensed' || $prescription['pharmacy_status'] == 'collected') {
        $sql = "SELECT psi.*, 
                       m.medicine_name,
                       m.strength
                FROM pharmacy_sale_items psi 
                LEFT JOIN medicines m ON psi.medicine_id = m.id 
                WHERE psi.sale_id IN (
                    SELECT id FROM pharmacy_sales WHERE prescription_id = ?
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $saleItems[] = $row;
        }
        $stmt->close();
    }
    
    $this->view('pharmacy/dispense', [
        'title' => 'Dispense Prescription',
        'prescription' => $prescription,
        'items' => $items,
        'saleItems' => $saleItems
    ]);
}
    
    // ==================== POS API METHODS ====================
    
    public function addToCart() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    $itemType = $_POST['item_type'] ?? 'medicine';
    $itemId = (int)$_POST['item_id'];
    $itemName = $_POST['item_name'] ?? '';
    $itemPrice = (float)($_POST['item_price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $doctorId = $_POST['doctor_id'] ?? null;
    $doctorName = $_POST['doctor_name'] ?? '';

    // If item_name is empty, fetch from database
    if (empty($itemName) && $itemType == 'medicine') {
        $medicineQuery = $this->db->query("SELECT medicine_name FROM medicines WHERE id = $itemId");
        if ($medicineQuery && $medicineQuery->num_rows > 0) {
            $medicine = $medicineQuery->fetch_assoc();
            $itemName = $medicine['medicine_name'];
        }
    }

    if (!isset($_SESSION['pharmacy_cart'])) $_SESSION['pharmacy_cart'] = [];

    // --- Helper to add/update an item in cart ---
    $addOrUpdateItem = function ($type, $id, $name, $price, $qty, $doctorId = null, $doctorName = '', $labTestId = null) {
        $cart = &$_SESSION['pharmacy_cart'];
        $found = false;
        foreach ($cart as $key => &$item) {
            if ($item['id'] == $id && $item['type'] == $type && 
                ($item['doctor_id'] ?? null) == $doctorId &&
                ($item['lab_test_id'] ?? null) == $labTestId) {
                $item['quantity'] += $qty;
                // Recalculate discount amount
                $itemSubtotal = $item['price'] * $item['quantity'];
                $discPct = isset($item['discount_percent']) ? $item['discount_percent'] : 0;
                $item['discount_amount'] = $itemSubtotal * ($discPct / 100);
                $item['subtotal_after_discount'] = $itemSubtotal - $item['discount_amount'];
                $found = true;
                break;
            }
        }
        if (!$found) {
            $newItem = [
                'id' => $id,
                'type' => $type,
                'name' => $name,
                'price' => $price,
                'quantity' => $qty,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'subtotal_after_discount' => $price * $qty
            ];
            if ($doctorId) { 
                $newItem['doctor_id'] = $doctorId; 
                $newItem['doctor_name'] = $doctorName; 
            }
            if ($labTestId) {
                $newItem['lab_test_id'] = $labTestId;
            }
            $cart[] = $newItem;
        }
    };

    // Add the main item
    $addOrUpdateItem($itemType, $itemId, $itemName, $itemPrice, $quantity, $doctorId, $doctorName);

    // --- If it's a lab test, also add its accessories ---
    if ($itemType == 'lab') {
        $accessoriesQuery = "
            SELECT la.id, la.accessory_name, la.unit_price, lta.quantity_required
            FROM lab_test_accessories lta
            JOIN lab_accessories la ON lta.accessory_id = la.id
            WHERE lta.test_id = $itemId
        ";
        $accessoriesResult = $this->db->query($accessoriesQuery);
        if ($accessoriesResult && $accessoriesResult->num_rows > 0) {
            while ($acc = $accessoriesResult->fetch_assoc()) {
                $accQty = $acc['quantity_required'] * $quantity;
                $addOrUpdateItem(
                    'lab_accessory',
                    $acc['id'],
                    $acc['accessory_name'],
                    $acc['unit_price'],
                    $accQty,
                    null,
                    '',
                    $itemId // lab_test_id reference
                );
            }
        }
    }

    // Re-index array
    $_SESSION['pharmacy_cart'] = array_values($_SESSION['pharmacy_cart']);

    echo json_encode([
        'success' => true,
        'cart' => $_SESSION['pharmacy_cart'],
        'cart_count' => count($_SESSION['pharmacy_cart'])
    ]);
    exit;
}

    // ==================== UPDATE ITEM DISCOUNT ====================
public function updateItemDiscount() {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    header('Content-Type: application/json');

    $index = isset($_POST['index']) ? (int)$_POST['index'] : -1;
    $discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
    
    // Clamp discount between 0 and 100
    $discountPercent = max(0, min(100, $discountPercent));

    // Debug logging
    error_log("updateItemDiscount called - Index: $index, Discount: $discountPercent");
    error_log("Session cart: " . print_r($_SESSION['pharmacy_cart'] ?? 'not set', true));

    // Check if cart exists
    if (!isset($_SESSION['pharmacy_cart']) || !is_array($_SESSION['pharmacy_cart'])) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    // Check if item exists at index
    if (!isset($_SESSION['pharmacy_cart'][$index])) {
        echo json_encode(['success' => false, 'message' => 'Item not found at index ' . $index]);
        exit;
    }

    // Update the discount
    $_SESSION['pharmacy_cart'][$index]['discount_percent'] = $discountPercent;
    
    // Recalculate discount amount for this item
    $item = &$_SESSION['pharmacy_cart'][$index];
    $itemSubtotal = $item['price'] * $item['quantity'];
    $item['discount_amount'] = $itemSubtotal * ($discountPercent / 100);
    $item['subtotal_after_discount'] = $itemSubtotal - $item['discount_amount'];

    error_log("Updated item: " . print_r($item, true));

    echo json_encode([
        'success' => true,
        'message' => 'Discount updated successfully',
        'index' => $index,
        'discount_percent' => $discountPercent,
        'discount_amount' => $item['discount_amount'],
        'subtotal_after_discount' => $item['subtotal_after_discount']
    ]);
    exit;
}

    public function getCart() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');
    $cart = isset($_SESSION['pharmacy_cart']) ? array_values($_SESSION['pharmacy_cart']) : [];
    
    // Ensure each item has discount fields
    foreach ($cart as &$item) {
        if (!isset($item['discount_percent'])) {
            $item['discount_percent'] = 0;
        }
        if (!isset($item['discount_amount'])) {
            $itemSubtotal = $item['price'] * $item['quantity'];
            $item['discount_amount'] = $itemSubtotal * ($item['discount_percent'] / 100);
        }
        if (!isset($item['subtotal_after_discount'])) {
            $itemSubtotal = $item['price'] * $item['quantity'];
            $item['subtotal_after_discount'] = $itemSubtotal - $item['discount_amount'];
        }
    }
    
    echo json_encode(['success' => true, 'cart' => $cart, 'cart_count' => count($cart)]);
    exit;
}
    
    public function clearCart() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');
        $_SESSION['pharmacy_cart'] = [];
        echo json_encode(['success' => true]);
        exit;
    }
    
    public function removeFromCart() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');
        $index = (int)$_POST['index'];
        if (isset($_SESSION['pharmacy_cart'][$index])) {
            array_splice($_SESSION['pharmacy_cart'], $index, 1);
            $_SESSION['pharmacy_cart'] = array_values($_SESSION['pharmacy_cart']);
        }
        echo json_encode(['success' => true]);
        exit;
    }
    
    public function updateCart() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');
        $index = (int)$_POST['index'];
        $quantity = (int)$_POST['quantity'];
        if (isset($_SESSION['pharmacy_cart'][$index])) {
            if ($quantity <= 0) {
                array_splice($_SESSION['pharmacy_cart'], $index, 1);
            } else {
                $_SESSION['pharmacy_cart'][$index]['quantity'] = $quantity;
            }
            $_SESSION['pharmacy_cart'] = array_values($_SESSION['pharmacy_cart']);
        }
        echo json_encode(['success' => true]);
        exit;
    }
    
    // ==================== PROCESS SALE - UPDATED WITH PER-ITEM DISCOUNT, OVERALL CAP 20% ====================
    public function processSale() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    header('Content-Type: application/json');

    if (empty($_SESSION['pharmacy_cart'])) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    $paymentMethod = $this->db->real_escape_string($_POST['payment_method']);
    $overallDiscountPercent = (float)($_POST['discount'] ?? 0);
    // Enforce max overall discount 20%
    if ($overallDiscountPercent > 20) {
        $overallDiscountPercent = 20;
    }
    $vatPercent = (float)($_POST['vat_percent'] ?? 15);
    $patientId = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
    $billType = isset($_POST['bill_type']) ? $this->db->real_escape_string($_POST['bill_type']) : 'pharmacy';
    $serviceName = isset($_POST['service_name']) ? $this->db->real_escape_string($_POST['service_name']) : 'Pharmacy Items';
    $referredBy = isset($_POST['referred_by']) ? (int)$_POST['referred_by'] : 0;

    // Compute subtotal after per-item discounts
    $subtotalAfterItemDiscount = 0;
    $cartItems = [];
    $totalDiscountAmount = 0;

    foreach ($_SESSION['pharmacy_cart'] as $item) {
        $itemPrice = $item['price'];
        $itemQty = $item['quantity'];
        $itemDiscountPercent = isset($item['discount_percent']) ? $item['discount_percent'] : 0;
        $itemSubtotal = $itemPrice * $itemQty;
        $itemDiscountAmount = $itemSubtotal * ($itemDiscountPercent / 100);
        $itemSubtotalAfterDiscount = $itemSubtotal - $itemDiscountAmount;
        $subtotalAfterItemDiscount += $itemSubtotalAfterDiscount;
        $totalDiscountAmount += $itemDiscountAmount;

        // Store item for later use
        $cartItems[] = array_merge($item, [
            'subtotal' => $itemSubtotal,
            'discount_amount' => $itemDiscountAmount,
            'subtotal_after_discount' => $itemSubtotalAfterDiscount
        ]);
    }

    // Apply overall discount (max 20%)
    $overallDiscountAmount = $subtotalAfterItemDiscount * ($overallDiscountPercent / 100);
    $subtotalAfterOverallDiscount = $subtotalAfterItemDiscount - $overallDiscountAmount;
    $totalDiscountAmount += $overallDiscountAmount;

    // Apply VAT
    $vatAmount = $subtotalAfterOverallDiscount * ($vatPercent / 100);
    $totalAmount = $subtotalAfterOverallDiscount + $vatAmount;

    $saleNumber = 'SAL' . date('Ymd') . rand(1000, 9999);

    $this->db->begin_transaction();

    try {
        // Insert pharmacy_sale
        $query = "INSERT INTO pharmacy_sales (sale_number, patient_id, sale_date, subtotal, discount_percentage, discount_amount, tax_amount, total_amount, payment_method, status, sold_by, notes) 
                  VALUES ('$saleNumber', " . ($patientId ? $patientId : "NULL") . ", CURDATE(), $subtotalAfterItemDiscount, $overallDiscountPercent, $totalDiscountAmount, $vatAmount, $totalAmount, '$paymentMethod', 'completed', {$_SESSION['user_id']}, 'Bill Type: $billType | Service: $serviceName')";
        if (!$this->db->query($query)) throw new Exception($this->db->error);

        $saleId = $this->db->insert_id;

        // Insert sale items (with per-item discounts)
        foreach ($cartItems as $item) {
            $itemTotalAfterDiscount = $item['subtotal_after_discount'];
            $itemName = $item['name'];
            $medicineId = null;
            if ($item['type'] == 'medicine') {
                $medicineId = $item['id'];
            }

            $insertSql = "INSERT INTO pharmacy_sale_items (sale_id, item_id, item_type, medicine_id, quantity, unit_price, discount_percentage, discount_amount, total_amount, item_name, created_at)
                          VALUES ($saleId, {$item['id']}, '{$item['type']}', " . ($medicineId ? $medicineId : "NULL") . ", {$item['quantity']}, {$item['price']}, {$item['discount_percent']}, {$item['discount_amount']}, $itemTotalAfterDiscount, '$itemName', NOW())";
            if (!$this->db->query($insertSql)) throw new Exception($this->db->error);
        }

        // Create Bill
        $billNumber = 'INV' . date('Ymd') . rand(1000, 9999);
        $billTypeDisplay = $billType;
        if ($billType == 'pharmacy') $billTypeDisplay = 'pharmacy';
        elseif ($billType == 'consultation') $billTypeDisplay = 'consultation';
        elseif ($billType == 'lab_test') $billTypeDisplay = 'lab_test';
        else $billTypeDisplay = 'other';

        // Add referred_by to the query
        $referredByValue = $referredBy > 0 ? $referredBy : 'NULL';

        $billQuery = "INSERT INTO bills (bill_number, patient_id, bill_type, bill_date, 
                      subtotal, discount_percentage, discount_amount, tax_amount, total_amount, 
                      paid_amount, balance_amount, payment_status, reference_type, reference_id, 
                      notes, referred_by, created_by) 
                      VALUES ('$billNumber', " . ($patientId ? $patientId : "NULL") . ", '$billTypeDisplay', CURDATE(), 
                      $subtotalAfterItemDiscount, $overallDiscountPercent, $totalDiscountAmount, $vatAmount, $totalAmount,
                      0, $totalAmount, 'pending', 'pharmacy_sale', $saleId, 
                      'POS Sale - $saleNumber | Service: $serviceName', $referredByValue, {$_SESSION['user_id']})";
        $this->db->query($billQuery);
        $billId = $this->db->insert_id;

        // Create bill items (with per-item discounts)
        foreach ($cartItems as $item) {
            $itemTotalAfterDiscount = $item['subtotal_after_discount'];
            $itemName = $item['name'];
            $medicineId = null;
            $itemType = $item['type'];
            if ($item['type'] == 'medicine') {
                $medicineId = $item['id'];
            }
            // Map item type for bill_items
            if ($item['type'] == 'medicine') $itemType = 'medicine';
            elseif ($item['type'] == 'service') $itemType = 'consultation';
            elseif ($item['type'] == 'lab' || $item['type'] == 'lab_accessory') $itemType = 'lab_test';
            else $itemType = 'other';

            $billItemSql = "INSERT INTO bill_items (bill_id, item_type, item_id, description, quantity, unit_price, discount_percentage, discount_amount, total_amount)
                           VALUES ($billId, '$itemType', " . ($medicineId ? $medicineId : "NULL") . ", '$itemName', {$item['quantity']}, {$item['price']}, {$item['discount_percent']}, {$item['discount_amount']}, $itemTotalAfterDiscount)";
            $this->db->query($billItemSql);
        }

        // Update sale with bill_id
        $this->db->query("UPDATE pharmacy_sales SET bill_id = $billId WHERE id = $saleId");

        $this->db->commit();
        $_SESSION['pharmacy_cart'] = [];

        echo json_encode(['success' => true, 'sale_id' => $saleId, 'sale_number' => $saleNumber, 'total' => $totalAmount, 'bill_number' => $billNumber, 'bill_type' => $billTypeDisplay]);
    } catch (Exception $e) {
        $this->db->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

    // ==================== GET SALES DATA (AJAX with pagination) ====================
    public function salesData() {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $search = isset($_GET['search']) ? $this->db->real_escape_string($_GET['search']) : '';
            $status = isset($_GET['status']) ? $this->db->real_escape_string($_GET['status']) : 'all';
            $paymentMethod = isset($_GET['payment_method']) ? $this->db->real_escape_string($_GET['payment_method']) : 'all';
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $where = "WHERE 1=1";
            if(!empty($search)) {
                $where .= " AND (s.sale_number LIKE '%$search%' 
                            OR CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search%'
                            OR u.first_name LIKE '%$search%'
                            OR u.last_name LIKE '%$search%')";
            }
            if($status !== 'all') {
                $where .= " AND s.status = '$status'";
            }
            if($paymentMethod !== 'all') {
                $where .= " AND s.payment_method = '$paymentMethod'";
            }
            
            // Count total
            $countQuery = "SELECT COUNT(*) as total 
                           FROM pharmacy_sales s
                           LEFT JOIN patients p ON s.patient_id = p.id
                           LEFT JOIN users u ON s.sold_by = u.id
                           $where";
            $countResult = $this->db->query($countQuery);
            
            $totalRecords = 0;
            if($countResult) {
                $totalRecords = $countResult->fetch_assoc()['total'];
            }
            $totalPages = ceil($totalRecords / $limit);
            
            // Get sales data
            $query = "SELECT s.id, s.sale_number, s.sale_date, s.subtotal, s.discount_percentage, 
                             s.discount_amount, s.total_amount, s.payment_method, s.status, s.created_at,
                             CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                             p.phone,
                             CONCAT(u.first_name, ' ', u.last_name) as cashier_name,
                             (SELECT COUNT(*) FROM pharmacy_sale_items WHERE sale_id = s.id) as item_count,
                             'pos' as sale_type
                      FROM pharmacy_sales s
                      LEFT JOIN patients p ON s.patient_id = p.id
                      LEFT JOIN users u ON s.sold_by = u.id
                      $where
                      ORDER BY s.created_at DESC
                      LIMIT $offset, $limit";
            
            $result = $this->db->query($query);
            $sales = [];
            
            if($result) {
                while($row = $result->fetch_assoc()) {
                    $sales[] = $row;
                }
            }
            
            echo json_encode([
                'success' => true,
                'sales' => $sales,
                'total' => $totalRecords,
                'total_pages' => $totalPages,
                'current_page' => $page,
                'per_page' => $limit
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'sales' => [],
                'total' => 0,
                'total_pages' => 0,
                'current_page' => 1,
                'per_page' => 10
            ]);
        }
        exit;
    }

    // ==================== GET SALE DETAILS (AJAX) ====================
    public function saleDetails($id) {
        header('Content-Type: application/json');
        
        $id = (int)$id;
        
        $saleQuery = "SELECT s.*, 
                             CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                             p.phone,
                             CONCAT(u.first_name, ' ', u.last_name) as cashier_name
                      FROM pharmacy_sales s
                      LEFT JOIN patients p ON s.patient_id = p.id
                      LEFT JOIN users u ON s.sold_by = u.id
                      WHERE s.id = $id";
        
        $saleResult = $this->db->query($saleQuery);
        if(!$saleResult) {
            echo json_encode(['success' => false, 'message' => 'Sale not found']);
            exit;
        }
        $sale = $saleResult->fetch_assoc();
        
        if(!$sale) {
            echo json_encode(['success' => false, 'message' => 'Sale not found']);
            exit;
        }
        
        $itemsQuery = "SELECT si.*, 
                              m.medicine_name as medicine_name,
                              m.strength,
                              m.medicine_code
                       FROM pharmacy_sale_items si
                       LEFT JOIN medicines m ON si.medicine_id = m.id
                       WHERE si.sale_id = $id";
        
        $items = [];
        $itemsResult = $this->db->query($itemsQuery);
        if($itemsResult) {
            while($row = $itemsResult->fetch_assoc()) {
                // Use stored item_name or fallback to medicine_name
                if(!empty($row['item_name'])) {
                    $row['item_name_display'] = $row['item_name'];
                } else if(!empty($row['medicine_name'])) {
                    $row['item_name_display'] = $row['medicine_name'];
                    if($row['strength']) {
                        $row['item_name_display'] .= ' ' . $row['strength'];
                    }
                } else {
                    $row['item_name_display'] = 'Medicine';
                }
                $items[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'sale' => $sale,
            'items' => $items
        ]);
        exit;
    }

    // ==================== GET RECENT SALES (for dashboard) ====================
    public function recentSales() {
        header('Content-Type: application/json');
        
        $query = "SELECT s.*, 
                         CONCAT(p.first_name, ' ', p.last_name) as patient_name
                  FROM pharmacy_sales s
                  LEFT JOIN patients p ON s.patient_id = p.id
                  ORDER BY s.created_at DESC
                  LIMIT 5";
        
        $result = $this->db->query($query);
        $sales = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $sales[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'sales' => $sales]);
        exit;
    }

    // ==================== GENERATE INVOICE ====================
    public function invoice($id) {
        $this->checkAuth();
        
        $id = (int)$id;
        
        $saleQuery = "SELECT s.*, 
                             CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                             p.phone,
                             p.address,
                             CONCAT(u.first_name, ' ', u.last_name) as cashier_name
                      FROM pharmacy_sales s
                      LEFT JOIN patients p ON s.patient_id = p.id
                      LEFT JOIN users u ON s.sold_by = u.id
                      WHERE s.id = $id";
        
        $saleResult = $this->db->query($saleQuery);
        
        if(!$saleResult || $saleResult->num_rows == 0) {
            echo "Sale not found";
            exit;
        }
        
        $sale = $saleResult->fetch_assoc();
        
        $itemsQuery = "SELECT si.*, 
                              m.medicine_name as item_name,
                              m.medicine_code
                       FROM pharmacy_sale_items si
                       LEFT JOIN medicines m ON si.medicine_id = m.id
                       WHERE si.sale_id = $id";
        
        $itemsResult = $this->db->query($itemsQuery);
        $items = [];
        if($itemsResult) {
            while($row = $itemsResult->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        // Output HTML directly
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Invoice - <?php echo $sale['sale_number']; ?></title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
                .invoice-container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { text-align: center; border-bottom: 2px solid #10b981; padding-bottom: 15px; margin-bottom: 20px; }
                .company-name { font-size: 24px; font-weight: bold; color: #10b981; }
                .invoice-title { font-size: 20px; font-weight: bold; margin-top: 5px; }
                .info-row { margin: 5px 0; display: flex; justify-content: space-between; }
                .info-label { font-weight: bold; width: 120px; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #f5f5f5; }
                .text-right { text-align: right; }
                .total-row { background: #d1fae5; font-weight: bold; }
                .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
                @media print {
                    body { background: white; padding: 0; margin: 0; }
                    .invoice-container { box-shadow: none; padding: 15px; }
                    .no-print { display: none; }
                }
                .btn-print { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; margin-right: 10px; }
                .btn-close { background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
            </style>
        </head>
        <body>
            <div class="invoice-container">
                <div class="header">
                    <div class="company-name">UNIDIA HOSPITAL</div>
                    <div class="invoice-title">PHARMACY INVOICE</div>
                    <div class="text-muted">Sale #: <?php echo $sale['sale_number']; ?></div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-label">Invoice Date:</span>
                            <span><?php echo date('d M Y, h:i A', strtotime($sale['created_at'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Method:</span>
                            <span><?php echo strtoupper($sale['payment_method']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="text-success"><?php echo strtoupper($sale['status']); ?></span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <span class="info-label">Patient Name:</span>
                            <span><?php echo htmlspecialchars($sale['patient_name'] ?? 'Walk-in Customer'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span><?php echo htmlspecialchars($sale['phone'] ?? '-'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Cashier:</span>
                            <span><?php echo htmlspecialchars($sale['cashier_name']); ?></span>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h5>Items Purchased</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Medicine Name</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; $subtotal = 0; ?>
                            <?php foreach($items as $item): ?>
                            <?php 
                                $itemTotal = $item['quantity'] * $item['unit_price'];
                                $subtotal += $itemTotal;
                            ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><strong><?php echo htmlspecialchars($item['item_name'] ?? 'Medicine'); ?></strong>
                                    <?php if($item['medicine_code']): ?><br><small class="text-muted">Code: <?php echo $item['medicine_code']; ?></small><?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-right">৳ <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-right">৳ <?php echo number_format($itemTotal, 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-light">
                                <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                                <td class="text-right">৳ <?php echo number_format($subtotal, 2); ?></small></td>
                            </tr>
                            <?php if($sale['discount_percentage'] > 0): ?>
                            <tr class="table-light">
                                <td colspan="4" class="text-right"><strong>Discount (<?php echo $sale['discount_percentage']; ?>%):</strong></td>
                                <td class="text-right text-danger">- ৳ <?php echo number_format($sale['discount_amount'], 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td colspan="4" class="text-right"><strong>Total Amount:</strong></td>
                                <td class="text-right"><strong>৳ <?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="footer">
                    <p>Thank you for choosing UNIDIA Hospital</p>
                    <p>This is a computer generated invoice</p>
                </div>
                
                <div class="text-center no-print mt-3">
                    <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print Invoice</button>
                    <button class="btn-close" onclick="window.close()">Close</button>
                </div>
            </div>
            
            <script src="https://kit.fontawesome.com/a81368914c.js"></script>
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

    // ==================== SHOW BILL (ADDITIONAL FUNCTION) ====================
    public function showBill($id) {
        $this->checkAuth();
        
        $id = (int)$id;
        
        // Get bill/sale with patient details
        $saleQuery = "SELECT s.*, 
                             CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                             p.phone,
                             p.address,
                             p.patient_code,
                             CONCAT(u.first_name, ' ', u.last_name) as cashier_name
                      FROM pharmacy_sales s
                      LEFT JOIN patients p ON s.patient_id = p.id
                      LEFT JOIN users u ON s.sold_by = u.id
                      WHERE s.id = $id";
        
        $saleResult = $this->db->query($saleQuery);
        
        if(!$saleResult || $saleResult->num_rows == 0) {
            $_SESSION['error'] = "Sale not found";
            $this->redirect('/pharmacy/sales');
            return;
        }
        
        $sale = $saleResult->fetch_assoc();
        
        // Get sale items
        $itemsQuery = "SELECT si.*, 
                              m.medicine_name as item_name,
                              m.medicine_code,
                              m.strength
                       FROM pharmacy_sale_items si
                       LEFT JOIN medicines m ON si.medicine_id = m.id
                       WHERE si.sale_id = $id";
        
        $itemsResult = $this->db->query($itemsQuery);
        $items = [];
        if($itemsResult) {
            while($row = $itemsResult->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        // Get related bill if exists
        $billQuery = "SELECT * FROM bills WHERE reference_type = 'pharmacy_sale' AND reference_id = $id";
        $billResult = $this->db->query($billQuery);
        $bill = $billResult ? $billResult->fetch_assoc() : null;
        
        $content = $this->renderView('pharmacy/sale-details', [
            'sale' => $sale,
            'items' => $items,
            'bill' => $bill
        ]);
        $this->renderLayout('Sale Details', $content);
    }

    // ==================== GET SALES LIST (POS + Manual Bills) ====================
    public function salesList() {
        header('Content-Type: application/json');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? $this->db->real_escape_string($_GET['search']) : '';
        $status = isset($_GET['status']) ? $this->db->real_escape_string($_GET['status']) : 'all';
        $type = isset($_GET['type']) ? $this->db->real_escape_string($_GET['type']) : 'all';
        $date = isset($_GET['date']) ? $this->db->real_escape_string($_GET['date']) : '';
        $limit = 15;
        $offset = ($page - 1) * $limit;
        
        $sales = [];
        
        // Get POS Sales
        if($type == 'all' || $type == 'pos') {
            $posWhere = "WHERE 1=1";
            if($search) {
                $posWhere .= " AND (s.sale_number LIKE '%$search%' OR CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search%')";
            }
            if($status != 'all') {
                $posWhere .= " AND s.status = '$status'";
            }
            if($date) {
                $posWhere .= " AND DATE(s.sale_date) = '$date'";
            }
            
            $posQuery = "SELECT s.id, s.sale_number as document_number, s.sale_date, s.subtotal, 
                                s.discount_percentage, s.discount_amount, s.total_amount, 
                                s.payment_method, s.status, s.created_at,
                                'pos' as sale_type,
                                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                p.phone,
                                CONCAT(u.first_name, ' ', u.last_name) as cashier_name,
                                (SELECT COUNT(*) FROM pharmacy_sale_items WHERE sale_id = s.id) as item_count
                         FROM pharmacy_sales s
                         LEFT JOIN patients p ON s.patient_id = p.id
                         LEFT JOIN users u ON s.sold_by = u.id
                         $posWhere";
            $posResult = $this->db->query($posQuery);
            if($posResult) {
                while($row = $posResult->fetch_assoc()) {
                    $row['sale_number'] = $row['document_number'];
                    $sales[] = $row;
                }
            }
        }
        
        // Get Manual Bills (Pharmacy type)
        if($type == 'all' || $type == 'manual') {
            $billWhere = "WHERE b.bill_type = 'pharmacy'";
            if($search) {
                $billWhere .= " AND (b.bill_number LIKE '%$search%' OR CONCAT(p.first_name, ' ', p.last_name) LIKE '%$search%')";
            }
            if($status != 'all') {
                $billWhere .= " AND b.payment_status = '$status'";
            }
            if($date) {
                $billWhere .= " AND DATE(b.bill_date) = '$date'";
            }
            
            $billQuery = "SELECT b.id, b.bill_number as document_number, b.bill_date, b.subtotal, 
                                 b.discount_percentage, b.discount_amount, b.total_amount,
                                 b.payment_method, b.payment_status as status, b.created_at,
                                 'manual' as sale_type,
                                 CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                 p.phone,
                                 CONCAT(u.first_name, ' ', u.last_name) as cashier_name,
                                 (SELECT COUNT(*) FROM bill_items WHERE bill_id = b.id) as item_count
                          FROM bills b
                          LEFT JOIN patients p ON b.patient_id = p.id
                          LEFT JOIN users u ON b.created_by = u.id
                          $billWhere";
            $billResult = $this->db->query($billQuery);
            if($billResult) {
                while($row = $billResult->fetch_assoc()) {
                    $row['sale_number'] = $row['document_number'];
                    $sales[] = $row;
                }
            }
        }
        
        // Sort by created_at descending
        usort($sales, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        $totalRecords = count($sales);
        $totalPages = ceil($totalRecords / $limit);
        $sales = array_slice($sales, $offset, $limit);
        
        echo json_encode([
            'success' => true,
            'sales' => $sales,
            'total' => $totalRecords,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $limit
        ]);
        exit;
    }

    // ==================== MOVE TO EXPIRY STOCK ====================
    public function moveToExpiry() {
        header('Content-Type: application/json');
        
        $stockId = (int)$_POST['stock_id'];
        $quantity = (int)$_POST['quantity'];
        $reason = $this->db->real_escape_string($_POST['reason']);
        $remarks = $this->db->real_escape_string($_POST['remarks'] ?? '');
        
        // Validate inputs
        if($stockId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid stock ID']);
            exit;
        }
        
        if($quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
            exit;
        }
        
        // Get stock details
        $stockQuery = "SELECT ms.*, m.medicine_name, m.medicine_code, m.id as medicine_id 
                       FROM medicine_stock ms 
                       JOIN medicines m ON ms.medicine_id = m.id 
                       WHERE ms.id = $stockId";
        $stockResult = $this->db->query($stockQuery);
        
        if(!$stockResult || $stockResult->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Stock record not found']);
            exit;
        }
        
        $stock = $stockResult->fetch_assoc();
        
        if($quantity > $stock['quantity']) {
            echo json_encode(['success' => false, 'message' => 'Quantity exceeds available stock. Available: ' . $stock['quantity']]);
            exit;
        }
        
        // Start transaction
        $this->db->begin_transaction();
        
        try {
            // Create expired_stock_movement table if not exists
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'expired_stock_movement'");
            if($tableCheck->num_rows == 0) {
                $createTable = "CREATE TABLE `expired_stock_movement` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `stock_id` INT(11) NOT NULL,
                    `medicine_id` INT(11) NOT NULL,
                    `batch_number` VARCHAR(50) NOT NULL,
                    `quantity` INT(11) NOT NULL,
                    `reason` VARCHAR(50) NOT NULL,
                    `remarks` TEXT NULL,
                    `moved_by` INT(11) NOT NULL,
                    `moved_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                $this->db->query($createTable);
            }
            
            // Record movement
            $insertQuery = "INSERT INTO expired_stock_movement (stock_id, medicine_id, batch_number, quantity, reason, remarks, moved_by) 
                            VALUES ({$stock['id']}, {$stock['medicine_id']}, '{$this->db->real_escape_string($stock['batch_number'])}', $quantity, '$reason', '$remarks', {$_SESSION['user_id']})";
            
            if(!$this->db->query($insertQuery)) {
                throw new Exception("Failed to record movement: " . $this->db->error);
            }
            
            // Update stock quantity
            $newQuantity = $stock['quantity'] - $quantity;
            if($newQuantity <= 0) {
                $updateQuery = "UPDATE medicine_stock SET status = 'moved', quantity = 0 WHERE id = {$stock['id']}";
            } else {
                $updateQuery = "UPDATE medicine_stock SET quantity = $newQuantity WHERE id = {$stock['id']}";
            }
            
            if(!$this->db->query($updateQuery)) {
                throw new Exception("Failed to update stock: " . $this->db->error);
            }
            
            $this->db->commit();
            
            echo json_encode(['success' => true, 'message' => "{$quantity} units of {$stock['medicine_name']} moved to expiry stock"]);
            
        } catch(Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== BULK MOVE TO EXPIRY ====================
    public function bulkMoveExpiry() {
        header('Content-Type: application/json');
        
        $stockIds = json_decode($_POST['stock_ids'], true);
        
        if(empty($stockIds) || !is_array($stockIds)) {
            echo json_encode(['success' => false, 'message' => 'No stocks selected']);
            exit;
        }
        
        // Create table if not exists
        $tableCheck = $this->db->query("SHOW TABLES LIKE 'expired_stock_movement'");
        if($tableCheck->num_rows == 0) {
            $createTable = "CREATE TABLE `expired_stock_movement` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `stock_id` INT(11) NOT NULL,
                `medicine_id` INT(11) NOT NULL,
                `batch_number` VARCHAR(50) NOT NULL,
                `quantity` INT(11) NOT NULL,
                `reason` VARCHAR(50) NOT NULL,
                `remarks` TEXT NULL,
                `moved_by` INT(11) NOT NULL,
                `moved_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $this->db->query($createTable);
        }
        
        $this->db->begin_transaction();
        $moved = 0;
        
        try {
            foreach($stockIds as $stockId) {
                $stockId = (int)$stockId;
                $stockQuery = "SELECT ms.*, m.medicine_name 
                               FROM medicine_stock ms 
                               JOIN medicines m ON ms.medicine_id = m.id 
                               WHERE ms.id = $stockId AND ms.status = 'in_stock'";
                $stockResult = $this->db->query($stockQuery);
                
                if($stockResult && $stockResult->num_rows > 0) {
                    $stock = $stockResult->fetch_assoc();
                    $quantity = $stock['quantity'];
                    
                    $insertQuery = "INSERT INTO expired_stock_movement (stock_id, medicine_id, batch_number, quantity, reason, remarks, moved_by) 
                                    VALUES ($stockId, {$stock['medicine_id']}, '{$this->db->real_escape_string($stock['batch_number'])}', $quantity, 'expired', 'Bulk move - Expired stock', {$_SESSION['user_id']})";
                    
                    if($this->db->query($insertQuery)) {
                        $this->db->query("UPDATE medicine_stock SET status = 'moved', quantity = 0 WHERE id = $stockId");
                        $moved++;
                    }
                }
            }
            
            $this->db->commit();
            echo json_encode(['success' => true, 'message' => "$moved stock items moved to expiry successfully"]);
            
        } catch(Exception $e) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }


/**
 * Process the dispense of a prescription with discount handling
 * Redirects to bills page with pending status filter
 */
public function dispenseProcess() {
    $this->checkAuth();
    $this->checkPermission('dispense_prescriptions');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $this->redirect('/pharmacy/prescriptions');
        return;
    }

    $prescriptionId = isset($_POST['prescription_id']) ? (int)$_POST['prescription_id'] : 0;
    $patientId = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $discountPercent = isset($_POST['discount_percent']) ? (float)$_POST['discount_percent'] : 0;
    $dispenseQtys = isset($_POST['dispense_qty']) ? $_POST['dispense_qty'] : [];
    $itemIds = isset($_POST['item_id']) ? $_POST['item_id'] : [];
    $stockIds = isset($_POST['stock_id']) ? $_POST['stock_id'] : [];
    $medicineIds = isset($_POST['medicine_id']) ? $_POST['medicine_id'] : [];
    $unitPrices = isset($_POST['unit_price']) ? $_POST['unit_price'] : [];
    $dispenseNotes = isset($_POST['dispense_notes']) ? trim($_POST['dispense_notes']) : '';

    if (!$prescriptionId || !$patientId) {
        $_SESSION['error'] = 'Invalid prescription or patient.';
        $this->redirect('/pharmacy/prescriptions');
        return;
    }

    try {
        $db = $this->db;
        
        if (!$db) {
            throw new Exception('Database connection not available');
        }
        
        $db->begin_transaction();

        // Get the prescription details
        $prescriptionQuery = "SELECT * FROM prescriptions WHERE id = " . (int)$prescriptionId;
        $prescriptionResult = $db->query($prescriptionQuery);
        $prescription = $prescriptionResult->fetch_assoc();
        
        if (!$prescription) {
            throw new Exception('Prescription not found.');
        }

        // Check if already dispensed
        if ($prescription['pharmacy_status'] == 'dispensed' || $prescription['pharmacy_status'] == 'collected') {
            throw new Exception('This prescription has already been dispensed.');
        }

        // Prepare sale data
        $saleItems = [];
        $totalAmount = 0;
        $subtotal = 0;
        $saleNumber = 'SAL' . date('Ymd') . rand(1000, 9999) . rand(10, 99);

        // Process each item
        foreach ($dispenseQtys as $itemId => $dispenseQty) {
            $dispenseQty = (int)$dispenseQty;
            if ($dispenseQty <= 0) {
                continue;
            }

            // Find the corresponding item data
            $itemIndex = array_search($itemId, $itemIds);
            if ($itemIndex === false) {
                continue;
            }

            $stockId = isset($stockIds[$itemIndex]) ? $stockIds[$itemIndex] : null;
            $medicineId = isset($medicineIds[$itemIndex]) ? $medicineIds[$itemIndex] : null;
            $unitPrice = isset($unitPrices[$itemIndex]) ? (float)$unitPrices[$itemIndex] : 0;

            // Get the prescription item details with medicine price
            $itemSql = "SELECT pi.*, m.medicine_name, m.strength, m.generic_name, m.selling_price 
                       FROM prescription_items pi
                       LEFT JOIN medicines m ON pi.drug_id = m.id
                       WHERE pi.id = " . (int)$itemId;
            $itemResult = $db->query($itemSql);
            $itemData = $itemResult->fetch_assoc();

            if (!$itemData) {
                continue;
            }

            $medicineName = $itemData['drug_name'] ?? $itemData['medicine_name'] ?? 'Unknown Medicine';
            $strength = $itemData['strength'] ?? '';
            $batchNumber = null;
            
            // Get selling price from medicine or use provided unit price
            if ($unitPrice <= 0 && isset($itemData['selling_price']) && $itemData['selling_price'] > 0) {
                $unitPrice = (float)$itemData['selling_price'];
            }
            
            // If still no price, try to get from stock
            if ($unitPrice <= 0 && $stockId) {
                $stockSql = "SELECT selling_price FROM medicine_stock WHERE id = " . (int)$stockId;
                $stockResult = $db->query($stockSql);
                $stockData = $stockResult->fetch_assoc();
                
                if ($stockData && $stockData['selling_price'] > 0) {
                    $unitPrice = (float)$stockData['selling_price'];
                }
            }

            // Check stock if stock_id is provided
            if ($stockId) {
                $stockSql = "SELECT quantity, selling_price, batch_number, expiry_date 
                            FROM medicine_stock WHERE id = " . (int)$stockId;
                $stockResult = $db->query($stockSql);
                $stockData = $stockResult->fetch_assoc();
                
                if ($stockData) {
                    $availableQty = (int)$stockData['quantity'];
                    if ($unitPrice <= 0) {
                        $unitPrice = (float)$stockData['selling_price'];
                    }
                    $batchNumber = $stockData['batch_number'];
                    
                    // Update stock quantity
                    $newQty = $availableQty - $dispenseQty;
                    if ($newQty < 0) {
                        $newQty = 0;
                        $_SESSION['warning'] = 'Stock for ' . $medicineName . ' is low. Please restock.';
                    }
                    
                    $updateSql = "UPDATE medicine_stock SET quantity = " . (int)$newQty . " WHERE id = " . (int)$stockId;
                    $db->query($updateSql);
                }
            }

            // If unit price is still 0, set a default warning
            if ($unitPrice <= 0) {
                $unitPrice = 0;
                error_log("Warning: No price found for medicine: " . $medicineName . " (ID: " . $medicineId . ")");
            }

            // Calculate item total
            $itemTotal = $dispenseQty * $unitPrice;
            
            // Add to sale items
            $saleItems[] = [
                'item_id' => $itemId,
                'medicine_id' => $medicineId,
                'stock_id' => $stockId,
                'medicine_name' => $medicineName,
                'strength' => $strength,
                'quantity' => $dispenseQty,
                'unit_price' => $unitPrice,
                'total' => $itemTotal,
                'batch_number' => $batchNumber
            ];
            
            $subtotal += $itemTotal;
        }

        if (empty($saleItems)) {
            throw new Exception('No items selected for dispense.');
        }

        // Calculate discount
        $discountAmount = 0;
        if ($discountPercent > 0) {
            $discountAmount = $subtotal * ($discountPercent / 100);
        }
        
        // Calculate tax (if applicable)
        $taxPercentage = 0;
        $taxAmount = ($subtotal - $discountAmount) * ($taxPercentage / 100);
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        $userId = $_SESSION['user_id'];
        $saleDate = date('Y-m-d');
        $paidAmount = 0;
        $paymentMethod = 'cash';
        $status = 'completed';

        // ============================================================
        // INSERT PHARMACY SALE - Using direct query
        // ============================================================
        $saleSql = "INSERT INTO pharmacy_sales 
                   (sale_number, patient_id, prescription_id, sale_date, 
                    subtotal, discount_percentage, discount_amount, tax_amount, total_amount, paid_amount, 
                    payment_method, status, sold_by, notes)
                   VALUES (
                       '$saleNumber', 
                       $patientId, 
                       $prescriptionId, 
                       '$saleDate', 
                       $subtotal, 
                       $discountPercent, 
                       $discountAmount, 
                       $taxAmount, 
                       $totalAmount, 
                       $paidAmount, 
                       '$paymentMethod', 
                       '$status', 
                       $userId, 
                       '" . mysqli_real_escape_string($db, $dispenseNotes) . "'
                   )";
        
        if (!$db->query($saleSql)) {
            throw new Exception('Failed to insert sale: ' . $db->error);
        }
        $saleId = $db->insert_id;

        // ============================================================
        // INSERT SALE ITEMS
        // ============================================================
        foreach ($saleItems as $item) {
            $itemName = $item['medicine_name'] . ($item['strength'] ? ' ' . $item['strength'] : '');
            $itemName = mysqli_real_escape_string($db, $itemName);
            $batchNumber = $item['batch_number'] ? "'" . mysqli_real_escape_string($db, $item['batch_number']) . "'" : "NULL";
            $medicineId = $item['medicine_id'] ? $item['medicine_id'] : "NULL";
            
            $itemSql = "INSERT INTO pharmacy_sale_items 
                       (sale_id, medicine_id, item_name, quantity, unit_price, total_amount, batch_number)
                       VALUES (
                           $saleId, 
                           $medicineId, 
                           '$itemName', 
                           {$item['quantity']}, 
                           {$item['unit_price']}, 
                           {$item['total']}, 
                           $batchNumber
                       )";
            
            if (!$db->query($itemSql)) {
                throw new Exception('Failed to insert sale item: ' . $db->error);
            }
        }

        // ============================================================
        // CREATE BILL
        // ============================================================
        $billNumber = 'INV' . date('Ymd') . rand(1000, 9999) . rand(10, 99);
        $balanceAmount = $totalAmount - $paidAmount;
        $paymentStatus = 'pending';
        $referenceType = 'pharmacy_sale';
        $billNotes = mysqli_real_escape_string($db, $dispenseNotes . ' (Sale #' . $saleNumber . ')');
        
        $billSql = "INSERT INTO bills 
                   (bill_number, patient_id, bill_type, bill_date, 
                    subtotal, discount_percentage, discount_amount, tax_amount, total_amount, paid_amount, balance_amount,
                    payment_status, reference_type, reference_id, created_by, notes)
                   VALUES (
                       '$billNumber', 
                       $patientId, 
                       'pharmacy', 
                       '$saleDate', 
                       $subtotal, 
                       $discountPercent, 
                       $discountAmount, 
                       $taxAmount, 
                       $totalAmount, 
                       $paidAmount, 
                       $balanceAmount, 
                       '$paymentStatus', 
                       '$referenceType', 
                       $saleId, 
                       $userId, 
                       '$billNotes'
                   )";
        
        if (!$db->query($billSql)) {
            throw new Exception('Failed to insert bill: ' . $db->error);
        }
        $billId = $db->insert_id;

        // ============================================================
        // CREATE BILL ITEMS
        // ============================================================
        foreach ($saleItems as $item) {
            $itemName = $item['medicine_name'] . ($item['strength'] ? ' ' . $item['strength'] : '');
            $itemName = mysqli_real_escape_string($db, $itemName);
            $medicineId = $item['medicine_id'] ? $item['medicine_id'] : "NULL";
            
            // Calculate per-item discount
            $itemDiscountAmount = 0;
            if ($discountPercent > 0 && $subtotal > 0) {
                $itemDiscountAmount = $item['total'] * ($discountPercent / 100);
            }
            $itemTaxAmount = 0;
            $itemGrandTotal = $item['total'] - $itemDiscountAmount + $itemTaxAmount;
            
            $billItemSql = "INSERT INTO bill_items 
                           (bill_id, item_type, item_id, description, quantity, 
                            unit_price, discount_percentage, discount_amount, 
                            tax_percentage, tax_amount, total_amount)
                           VALUES (
                               $billId, 
                               'medicine', 
                               $medicineId, 
                               '$itemName', 
                               {$item['quantity']}, 
                               {$item['unit_price']}, 
                               $discountPercent, 
                               $itemDiscountAmount, 
                               0, 
                               $itemTaxAmount, 
                               $itemGrandTotal
                           )";
            
            if (!$db->query($billItemSql)) {
                throw new Exception('Failed to insert bill item: ' . $db->error);
            }
        }

        // Update bill reference in pharmacy_sale
        $updateBillSql = "UPDATE pharmacy_sales SET bill_id = $billId WHERE id = $saleId";
        $db->query($updateBillSql);

        // Update prescription status
        $updatePrescriptionSql = "UPDATE prescriptions 
                                 SET status = 'dispensed', 
                                     pharmacy_status = 'dispensed',
                                     updated_at = NOW()
                                 WHERE id = $prescriptionId";
        $db->query($updatePrescriptionSql);

        // Update appointment if exists
        if ($prescription['appointment_id']) {
            $updateApptSql = "UPDATE appointments SET status = 'completed' WHERE id = " . (int)$prescription['appointment_id'];
            $db->query($updateApptSql);
        }

        $db->commit();

        $_SESSION['success'] = '✅ Prescription dispensed successfully! Bill #' . $billNumber . ' created.';
        
        // Redirect to bills page with pending status filter
        $this->redirect('/bills?status=pending');

    } catch (Exception $e) {
        if (isset($db) && method_exists($db, 'rollback')) {
            $db->rollback();
        }
        error_log("Dispense process error: " . $e->getMessage());
        $_SESSION['error'] = '❌ Error processing dispense: ' . $e->getMessage();
        $this->redirect('/pharmacy/dispense/' . $prescriptionId);
    }
}

/**
 * Get batch number from stock ID
 */
private function getBatchNumber($stockId) {
    if (!$stockId) return null;
    $sql = "SELECT batch_number FROM medicine_stock WHERE id = ?";
    $result = $this->queryOne($sql, [$stockId]);
    return $result ? $result['batch_number'] : null;
}

/**
 * Helper method to execute query with parameters
 */
private function execute($sql, $params = []) {
    try {
        if (!$this->db) return false;
        
        if (empty($params)) {
            return $this->db->query($sql);
        }
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        
        $types = '';
        $bindParams = [];
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_double($param) || is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
            $bindParams[] = $param;
        }
        
        if (!empty($types)) {
            $stmt->bind_param($types, ...$bindParams);
        }
        
        return $stmt->execute();
    } catch (Exception $e) {
        error_log("Execute error: " . $e->getMessage() . " - SQL: " . $sql);
        return false;
    }
}
}
?>