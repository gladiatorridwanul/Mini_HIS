<?php
require_once BASE_PATH . '/core/Controller.php';

class InventoryController extends Controller {
    
    // ==================== DASHBOARD ====================
    public function dashboard() {
        $this->checkAuth();
        
        try {
            // Get total items count from all tables
            $totalItems = 0;
            
            $medCount = $this->db->query("SELECT COUNT(*) as count FROM medicines WHERE status = 'active'");
            $totalItems += $medCount ? (int)$medCount->fetch_assoc()['count'] : 0;
            
            $labCount = $this->db->query("SELECT COUNT(*) as count FROM lab_tests WHERE status = 'active'");
            $totalItems += $labCount ? (int)$labCount->fetch_assoc()['count'] : 0;
            
            $invCount = $this->db->query("SELECT COUNT(*) as count FROM inventory_items WHERE status = 'active'");
            $totalItems += $invCount ? (int)$invCount->fetch_assoc()['count'] : 0;
            
            // Get low stock items count
            $lowStock = $this->getLowStockCount();
            
            // Get expiring soon count
            $expiringSoon = $this->getExpiringCount();
            
            // Get total stock value
            $totalValue = $this->getTotalStockValue();
            
            // Get low stock items details
            $lowStockItems = $this->getLowStockItems();
            
            $content = $this->renderView('inventory/dashboard', [
                'totalItems' => $totalItems,
                'lowStock' => $lowStock,
                'expiringSoon' => $expiringSoon,
                'totalValue' => $totalValue,
                'lowStockItems' => $lowStockItems
            ]);
            $this->renderLayout('Inventory Dashboard', $content);
            
        } catch (Exception $e) {
            error_log("Inventory Dashboard Error: " . $e->getMessage());
            $content = $this->renderView('inventory/dashboard', [
                'totalItems' => 0,
                'lowStock' => 0,
                'expiringSoon' => 0,
                'totalValue' => 0,
                'lowStockItems' => []
            ]);
            $this->renderLayout('Inventory Dashboard', $content);
        }
    }
    
    // ==================== ITEMS MANAGEMENT ====================
    public function items() {
        $this->checkAuth();
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? $this->sanitize($_GET['search']) : '';
        $typeFilter = isset($_GET['type']) ? $this->sanitize($_GET['type']) : 'all';
        $stockStatus = isset($_GET['stock_status']) ? $this->sanitize($_GET['stock_status']) : 'all';
        $categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $limit = 15;
        $offset = ($page - 1) * $limit;
        
        $allItems = [];
        
        // 1. Get Medicines from medicines table
        $medicineWhere = "WHERE m.status = 'active'";
        if(!empty($search)) {
            $medicineWhere .= " AND (m.medicine_name LIKE '%$search%' OR m.medicine_code LIKE '%$search%' OR m.generic_name LIKE '%$search%')";
        }
        if($categoryFilter > 0 && $typeFilter == 'medicine') {
            $medicineWhere .= " AND m.category_id = $categoryFilter";
        }
        
        $medicineQuery = "SELECT 
                            m.id, 
                            m.medicine_code as item_code, 
                            m.medicine_name as item_name, 
                            m.generic_name,
                            m.category_id,
                            c.name as category_name,
                            'medicine' as item_type,
                            m.unit_of_measure,
                            m.purchase_price,
                            m.selling_price,
                            COALESCE(m.reorder_level, 10) as reorder_level,
                            m.status,
                            COALESCE(SUM(ms.quantity), 0) as current_stock,
                            m.strength,
                            m.manufacturer,
                            m.dosage_form,
                            MIN(ms.expiry_date) as expiry_date,
                            'medicines' as table_source
                          FROM medicines m
                          LEFT JOIN medicine_categories c ON m.category_id = c.id
                          LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                          $medicineWhere
                          GROUP BY m.id
                          ORDER BY m.medicine_name ASC";
        
        $medicineResult = $this->db->query($medicineQuery);
        if($medicineResult) {
            while($row = $medicineResult->fetch_assoc()) {
                $row['current_stock'] = (int)$row['current_stock'];
                $row['stock_status'] = $this->getStockStatus($row['current_stock'], $row['reorder_level']);
                $row['unit_of_measure'] = $row['unit_of_measure'] ?? 'Strip';
                $row['selling_price'] = (float)$row['selling_price'];
                $row['status'] = $row['status'] ?? 'active';
                $allItems[] = $row;
            }
        }
        
        // 2. Get Lab Tests from lab_tests table
        $labWhere = "WHERE lt.status = 'active'";
        if(!empty($search)) {
            $labWhere .= " AND (lt.test_name LIKE '%$search%' OR lt.test_code LIKE '%$search%')";
        }
        if($categoryFilter > 0 && $typeFilter == 'lab_test') {
            $labWhere .= " AND lt.category_id = $categoryFilter";
        }
        
        $labQuery = "SELECT 
                        lt.id, 
                        lt.test_code as item_code, 
                        lt.test_name as item_name, 
                        lt.test_name as generic_name,
                        lt.category_id,
                        ltc.name as category_name,
                        'lab_test' as item_type,
                        'Test' as unit_of_measure,
                        lt.price as purchase_price,
                        lt.price as selling_price,
                        10 as reorder_level,
                        lt.status,
                        0 as current_stock,
                        lt.specimen_type as strength,
                        NULL as manufacturer,
                        lt.normal_range as dosage_form,
                        NULL as expiry_date,
                        'normal' as stock_status,
                        'lab_tests' as table_source
                      FROM lab_tests lt
                      LEFT JOIN lab_test_categories ltc ON lt.category_id = ltc.id
                      $labWhere
                      ORDER BY lt.test_name ASC";
        
        $labResult = $this->db->query($labQuery);
        if($labResult) {
            while($row = $labResult->fetch_assoc()) {
                if(empty($row['item_code'])) {
                    $row['item_code'] = 'LAB' . str_pad($row['id'], 5, '0', STR_PAD_LEFT);
                }
                $row['current_stock'] = 0;
                $row['selling_price'] = (float)$row['selling_price'];
                $row['unit_of_measure'] = 'Test';
                $row['status'] = $row['status'] ?? 'active';
                $allItems[] = $row;
            }
        }
        
        // 3. Get Equipment/Other from inventory_items table
        $inventoryWhere = "WHERE i.status = 'active' AND i.item_type IN ('equipment', 'other', 'consumable', 'surgical')";
        if(!empty($search)) {
            $inventoryWhere .= " AND (i.item_name LIKE '%$search%' OR i.item_code LIKE '%$search%' OR i.generic_name LIKE '%$search%')";
        }
        if($categoryFilter > 0 && ($typeFilter == 'equipment' || $typeFilter == 'other')) {
            $inventoryWhere .= " AND i.category_id = $categoryFilter";
        }
        
        $inventoryQuery = "SELECT 
                            i.id, 
                            i.item_code, 
                            i.item_name, 
                            i.generic_name,
                            i.category_id,
                            c.name as category_name,
                            i.item_type,
                            i.unit_of_measure,
                            i.purchase_price,
                            i.selling_price,
                            COALESCE(i.reorder_level, 10) as reorder_level,
                            i.status,
                            COALESCE(SUM(s.quantity), 0) as current_stock,
                            i.strength,
                            i.manufacturer,
                            i.brand,
                            i.warranty,
                            MIN(s.expiry_date) as expiry_date,
                            'inventory_items' as table_source
                          FROM inventory_items i
                          LEFT JOIN inventory_categories c ON i.category_id = c.id
                          LEFT JOIN inventory_stock s ON i.id = s.item_id AND s.expiry_date > CURDATE()
                          $inventoryWhere
                          GROUP BY i.id
                          ORDER BY i.item_name ASC";
        
        $inventoryResult = $this->db->query($inventoryQuery);
        if($inventoryResult) {
            while($row = $inventoryResult->fetch_assoc()) {
                $row['current_stock'] = (int)$row['current_stock'];
                $row['stock_status'] = $this->getStockStatus($row['current_stock'], $row['reorder_level']);
                $row['unit_of_measure'] = $row['unit_of_measure'] ?? 'Piece';
                $row['selling_price'] = (float)$row['selling_price'];
                $row['status'] = $row['status'] ?? 'active';
                // Map item_type for display
                if($row['item_type'] == 'equipment') {
                    $row['display_type'] = 'equipment';
                    $row['type_icon'] = '🔧';
                    $row['type_name'] = 'Equipment';
                } elseif($row['item_type'] == 'other') {
                    $row['display_type'] = 'other';
                    $row['type_icon'] = '📋';
                    $row['type_name'] = 'Other';
                } else {
                    $row['display_type'] = $row['item_type'];
                    $row['type_icon'] = '📦';
                    $row['type_name'] = ucfirst($row['item_type']);
                }
                $allItems[] = $row;
            }
        }
        
        // Apply type filter
        if($typeFilter != 'all') {
            $allItems = array_filter($allItems, function($item) use ($typeFilter) {
                return $item['item_type'] == $typeFilter;
            });
        }
        
        // Apply stock status filter
        if($stockStatus != 'all') {
            $allItems = array_filter($allItems, function($item) use ($stockStatus) {
                return $item['stock_status'] == $stockStatus;
            });
        }
        
        // Sort by name
        usort($allItems, function($a, $b) {
            return strcmp($a['item_name'], $b['item_name']);
        });
        
        $totalRecords = count($allItems);
        $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
        $paginatedItems = array_slice($allItems, $offset, $limit);
        
        // Calculate statistics
        $stats = $this->calculateInventoryStats($allItems);
        
        // Get categories for filter
        $categoryList = $this->getAllCategories();
        
        // Get suppliers for filter
        $supplierList = $this->getAllSuppliers();
        
        $content = $this->renderView('inventory/items', [
            'items' => $paginatedItems,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'search' => $search,
            'selectedType' => $typeFilter,
            'selectedStockStatus' => $stockStatus,
            'selectedCategory' => $categoryFilter,
            'categories' => $categoryList,
            'suppliers' => $supplierList,
            'lowStockCount' => $stats['lowStockCount'],
            'expiringCount' => $stats['expiringCount'],
            'totalValue' => $stats['totalValue']
        ]);
        $this->renderLayout('Inventory Items', $content);
    }
    
    // ==================== ADD ITEM FORM ====================
    public function addItemForm() {
        $this->checkAuth();
        
        // Get all categories based on type
        $medCategoryList = $this->getMedicineCategories();
        $labCategoryList = $this->getLabCategories();
        $invCategoryList = $this->getInventoryCategories();
        
        // Get suppliers based on type
        $medSupplierList = $this->getMedicineSuppliers();
        $labSupplierList = $this->getLabSuppliers();
        $invSupplierList = $this->getInventorySuppliers();
        
        $content = $this->renderView('inventory/add-item', [
            'medCategories' => $medCategoryList,
            'labCategories' => $labCategoryList,
            'invCategories' => $invCategoryList,
            'medSuppliers' => $medSupplierList,
            'labSuppliers' => $labSupplierList,
            'invSuppliers' => $invSupplierList
        ]);
        $this->renderLayout('Add Inventory Item', $content);
    }
    
    // ==================== ADD ITEM (POST) ====================
    public function addItem() {
        header('Content-Type: application/json');
        
        $itemType = isset($_POST['item_type']) ? $this->sanitize($_POST['item_type']) : '';
        
        if(empty($itemType)) {
            echo json_encode(['success' => false, 'message' => 'Item type is required']);
            exit;
        }
        
        try {
            $itemId = null;
            $message = '';
            
            switch($itemType) {
                case 'medicine':
                    $itemId = $this->addMedicine();
                    $message = 'Medicine added successfully';
                    break;
                case 'lab_test':
                    $itemId = $this->addLabTest();
                    $message = 'Lab Test added successfully';
                    break;
                case 'equipment':
                    $itemId = $this->addEquipment();
                    $message = 'Equipment/Accessory added successfully';
                    break;
                case 'other':
                    $itemId = $this->addOtherItem();
                    $message = 'Other item added successfully';
                    break;
                default:
                    throw new Exception('Invalid item type: ' . $itemType);
            }
            
            if($itemId) {
                echo json_encode(['success' => true, 'message' => $message, 'item_id' => $itemId]);
            } else {
                throw new Exception('Failed to add item');
            }
            
        } catch(Exception $e) {
            error_log("Add Item Error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // ==================== PRIVATE ADD METHODS ====================
    
    private function addMedicine() {
        // Generate unique medicine code
        $medicineCode = '';
        $isUnique = false;
        $maxAttempts = 10;
        $attempts = 0;
        
        while(!$isUnique && $attempts < $maxAttempts) {
            $medicineCode = 'MED' . date('Ymd') . rand(100, 999);
            $checkQuery = "SELECT id FROM medicines WHERE medicine_code = '$medicineCode'";
            $checkResult = $this->db->query($checkQuery);
            if($checkResult && $checkResult->num_rows == 0) {
                $isUnique = true;
                break;
            }
            $attempts++;
        }
        
        if(!$isUnique) {
            $medicineCode = 'MED' . date('YmdHis') . rand(10, 99);
        }
        
        $medicineName = $this->sanitize($_POST['item_name']);
        $genericName = $this->sanitize($_POST['generic_name'] ?? '');
        $categoryId = (int)$_POST['med_category_id'];
        $manufacturer = $this->sanitize($_POST['manufacturer'] ?? '');
        $supplierId = isset($_POST['supplier_id']) && !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $strength = $this->sanitize($_POST['strength'] ?? '');
        $dosageForm = $this->sanitize($_POST['dosage_form'] ?? 'Tablet');
        $unitOfMeasure = $this->sanitize($_POST['unit_of_measure'] ?? 'Strip');
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $mrp = (float)($_POST['mrp'] ?? 0);
        $taxPercentage = (float)($_POST['tax_percentage'] ?? 0);
        $requiresPrescription = isset($_POST['requires_prescription']) ? 1 : 0;
        $reorderLevel = (int)($_POST['reorder_level'] ?? 10);
        $reorderQuantity = (int)($_POST['reorder_quantity'] ?? 50);
        $description = $this->sanitize($_POST['description'] ?? '');
        $sideEffects = $this->sanitize($_POST['side_effects'] ?? '');
        $storageCondition = $this->sanitize($_POST['storage_condition'] ?? '');
        
        $query = "INSERT INTO medicines (
                    medicine_code, medicine_name, generic_name, category_id, manufacturer, supplier_id,
                    strength, dosage_form, unit_of_measure, purchase_price, selling_price, 
                    mrp, tax_percentage, requires_prescription, reorder_level, reorder_quantity, 
                    description, side_effects, storage_condition, status
                  ) VALUES (
                    '$medicineCode', '$medicineName', '$genericName', $categoryId, '$manufacturer', " . ($supplierId ? $supplierId : "NULL") . ",
                    '$strength', '$dosageForm', '$unitOfMeasure', $purchasePrice, $sellingPrice, 
                    $mrp, $taxPercentage, $requiresPrescription, $reorderLevel, $reorderQuantity, 
                    '$description', '$sideEffects', '$storageCondition', 'active'
                  )";
        
        if($this->db->query($query)) {
            $itemId = $this->db->insert_id;
            
            // Add initial stock if provided
            if(isset($_POST['batch_number']) && !empty($_POST['batch_number']) && 
               isset($_POST['initial_quantity']) && (int)$_POST['initial_quantity'] > 0) {
                $batchNumber = $this->sanitize($_POST['batch_number']);
                $expiryDate = $this->sanitize($_POST['expiry_date']);
                $quantity = (int)$_POST['initial_quantity'];
                $location = $this->sanitize($_POST['location'] ?? '');
                
                $stockQuery = "INSERT INTO medicine_stock (
                                medicine_id, batch_number, expiry_date, quantity, 
                                purchase_price, selling_price, location, status, created_at
                              ) VALUES (
                                $itemId, '$batchNumber', '$expiryDate', $quantity, 
                                $purchasePrice, $sellingPrice, '$location', 'in_stock', NOW()
                              )";
                $this->db->query($stockQuery);
            }
            return $itemId;
        }
        throw new Exception($this->db->error);
    }
    
    private function addLabTest() {
        // Generate a unique test code
        $testCode = '';
        $isUnique = false;
        $maxAttempts = 10;
        $attempts = 0;
        
        // Check if custom test code was provided
        $customTestCode = isset($_POST['test_code']) ? $this->sanitize($_POST['test_code']) : '';
        
        if(!empty($customTestCode)) {
            // Verify custom test code is unique
            $checkQuery = "SELECT id FROM lab_tests WHERE test_code = '$customTestCode'";
            $checkResult = $this->db->query($checkQuery);
            if($checkResult && $checkResult->num_rows == 0) {
                $testCode = $customTestCode;
                $isUnique = true;
            } else {
                // Custom code exists, generate a unique one
                $isUnique = false;
            }
        }
        
        // Generate a unique test code if needed
        while(!$isUnique && $attempts < $maxAttempts) {
            $testCode = 'LAB' . date('Ymd') . rand(100, 999);
            $checkQuery = "SELECT id FROM lab_tests WHERE test_code = '$testCode'";
            $checkResult = $this->db->query($checkQuery);
            if($checkResult && $checkResult->num_rows == 0) {
                $isUnique = true;
                break;
            }
            $attempts++;
        }
        
        // If still not unique after attempts, add timestamp
        if(!$isUnique) {
            $testCode = 'LAB' . date('YmdHis') . rand(10, 99);
        }
        
        $testName = $this->sanitize($_POST['item_name']);
        $categoryId = (int)$_POST['lab_category_id'];
        $supplierId = isset($_POST['supplier_id']) && !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $price = (float)($_POST['selling_price'] ?? 0);
        $specimenType = $this->sanitize($_POST['specimen_type'] ?? '');
        $turnaroundTime = (int)($_POST['turnaround_time'] ?? 24);
        $normalRange = $this->sanitize($_POST['normal_range'] ?? '');
        $unit = $this->sanitize($_POST['unit'] ?? '');
        $requiresFasting = isset($_POST['requires_fasting']) ? 1 : 0;
        $description = $this->sanitize($_POST['description'] ?? '');
        $preparationInstructions = $this->sanitize($_POST['preparation_instructions'] ?? '');
        
        $query = "INSERT INTO lab_tests (
                    test_code, test_name, category_id, supplier_id, price, specimen_type,
                    turnaround_time, normal_range, unit, requires_fasting, 
                    description, preparation_instructions, status
                  ) VALUES (
                    '$testCode', '$testName', $categoryId, " . ($supplierId ? $supplierId : "NULL") . ", $price, '$specimenType',
                    $turnaroundTime, '$normalRange', '$unit', $requiresFasting, 
                    '$description', '$preparationInstructions', 'active'
                  )";
        
        if($this->db->query($query)) {
            return $this->db->insert_id;
        }
        throw new Exception($this->db->error);
    }
    
    private function addEquipment() {
        // Generate unique item code
        $itemCode = '';
        $isUnique = false;
        $maxAttempts = 10;
        $attempts = 0;
        
        while(!$isUnique && $attempts < $maxAttempts) {
            $itemCode = 'EQP' . date('Ymd') . rand(100, 999);
            $checkQuery = "SELECT id FROM inventory_items WHERE item_code = '$itemCode'";
            $checkResult = $this->db->query($checkQuery);
            if($checkResult && $checkResult->num_rows == 0) {
                $isUnique = true;
                break;
            }
            $attempts++;
        }
        
        if(!$isUnique) {
            $itemCode = 'EQP' . date('YmdHis') . rand(10, 99);
        }
        
        $itemName = $this->sanitize($_POST['item_name']);
        $genericName = $this->sanitize($_POST['generic_name'] ?? '');
        $categoryId = (int)$_POST['inv_category_id'];
        $supplierId = isset($_POST['supplier_id']) && !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $unitOfMeasure = $this->sanitize($_POST['unit_of_measure'] ?? 'Piece');
        $packSize = $this->sanitize($_POST['pack_size'] ?? '');
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $mrp = (float)($_POST['mrp'] ?? 0);
        $taxPercentage = (float)($_POST['tax_percentage'] ?? 0);
        $reorderLevel = (int)($_POST['reorder_level'] ?? 10);
        $reorderQuantity = (int)($_POST['reorder_quantity'] ?? 50);
        $minStockLevel = (int)($_POST['min_stock_level'] ?? 5);
        $maxStockLevel = (int)($_POST['max_stock_level'] ?? 100);
        $storageLocation = $this->sanitize($_POST['storage_location'] ?? '');
        $storageCondition = $this->sanitize($_POST['storage_condition'] ?? '');
        $requiresRefrigeration = isset($_POST['requires_refrigeration']) ? 1 : 0;
        $description = $this->sanitize($_POST['description'] ?? '');
        
        // Equipment-specific fields
        $brand = isset($_POST['brand']) ? $this->sanitize($_POST['brand']) : '';
        $warranty = isset($_POST['warranty']) ? (int)$_POST['warranty'] : 0;
        $serialNumber = isset($_POST['serial_number']) ? $this->sanitize($_POST['serial_number']) : '';
        
        $query = "INSERT INTO inventory_items (
                    item_code, item_name, generic_name, category_id, item_type, supplier_id,
                    unit_of_measure, pack_size, purchase_price, selling_price, mrp, tax_percentage,
                    reorder_level, reorder_quantity, min_stock_level, max_stock_level,
                    storage_location, storage_condition, requires_refrigeration,
                    brand, warranty, serial_number, description, status
                  ) VALUES (
                    '$itemCode', '$itemName', '$genericName', $categoryId, 'equipment', " . ($supplierId ? $supplierId : "NULL") . ",
                    '$unitOfMeasure', '$packSize', $purchasePrice, $sellingPrice, $mrp, $taxPercentage,
                    $reorderLevel, $reorderQuantity, $minStockLevel, $maxStockLevel,
                    '$storageLocation', '$storageCondition', $requiresRefrigeration,
                    '$brand', $warranty, '$serialNumber', '$description', 'active'
                  )";
        
        if($this->db->query($query)) {
            $itemId = $this->db->insert_id;
            
            // Add initial stock if provided
            if(isset($_POST['batch_number']) && !empty($_POST['batch_number']) && 
               isset($_POST['initial_quantity']) && (int)$_POST['initial_quantity'] > 0) {
                $batchNumber = $this->sanitize($_POST['batch_number']);
                $expiryDate = $this->sanitize($_POST['expiry_date']);
                $quantity = (int)$_POST['initial_quantity'];
                $location = $this->sanitize($_POST['location'] ?? '');
                
                $stockQuery = "INSERT INTO inventory_stock (
                                item_id, batch_number, expiry_date, quantity, 
                                unit_cost, selling_price, location
                              ) VALUES (
                                $itemId, '$batchNumber', '$expiryDate', $quantity, 
                                $purchasePrice, $sellingPrice, '$location'
                              )";
                $this->db->query($stockQuery);
            }
            return $itemId;
        }
        throw new Exception($this->db->error);
    }
    
    private function addOtherItem() {
        // Generate unique item code
        $itemCode = '';
        $isUnique = false;
        $maxAttempts = 10;
        $attempts = 0;
        
        while(!$isUnique && $attempts < $maxAttempts) {
            $itemCode = 'OTH' . date('Ymd') . rand(100, 999);
            $checkQuery = "SELECT id FROM inventory_items WHERE item_code = '$itemCode'";
            $checkResult = $this->db->query($checkQuery);
            if($checkResult && $checkResult->num_rows == 0) {
                $isUnique = true;
                break;
            }
            $attempts++;
        }
        
        if(!$isUnique) {
            $itemCode = 'OTH' . date('YmdHis') . rand(10, 99);
        }
        
        $itemName = $this->sanitize($_POST['item_name']);
        $genericName = $this->sanitize($_POST['generic_name'] ?? '');
        $categoryId = (int)$_POST['inv_category_id'];
        $supplierId = isset($_POST['supplier_id']) && !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $unitOfMeasure = $this->sanitize($_POST['unit_of_measure'] ?? 'Piece');
        $packSize = $this->sanitize($_POST['pack_size'] ?? '');
        $purchasePrice = (float)($_POST['purchase_price'] ?? 0);
        $sellingPrice = (float)($_POST['selling_price'] ?? 0);
        $mrp = (float)($_POST['mrp'] ?? 0);
        $taxPercentage = (float)($_POST['tax_percentage'] ?? 0);
        $reorderLevel = (int)($_POST['reorder_level'] ?? 10);
        $reorderQuantity = (int)($_POST['reorder_quantity'] ?? 50);
        $minStockLevel = (int)($_POST['min_stock_level'] ?? 5);
        $maxStockLevel = (int)($_POST['max_stock_level'] ?? 100);
        $storageLocation = $this->sanitize($_POST['storage_location'] ?? '');
        $storageCondition = $this->sanitize($_POST['storage_condition'] ?? '');
        $requiresRefrigeration = isset($_POST['requires_refrigeration']) ? 1 : 0;
        $description = $this->sanitize($_POST['description'] ?? '');
        
        $query = "INSERT INTO inventory_items (
                    item_code, item_name, generic_name, category_id, item_type, supplier_id,
                    unit_of_measure, pack_size, purchase_price, selling_price, mrp, tax_percentage,
                    reorder_level, reorder_quantity, min_stock_level, max_stock_level,
                    storage_location, storage_condition, requires_refrigeration,
                    description, status
                  ) VALUES (
                    '$itemCode', '$itemName', '$genericName', $categoryId, 'other', " . ($supplierId ? $supplierId : "NULL") . ",
                    '$unitOfMeasure', '$packSize', $purchasePrice, $sellingPrice, $mrp, $taxPercentage,
                    $reorderLevel, $reorderQuantity, $minStockLevel, $maxStockLevel,
                    '$storageLocation', '$storageCondition', $requiresRefrigeration,
                    '$description', 'active'
                  )";
        
        if($this->db->query($query)) {
            $itemId = $this->db->insert_id;
            
            // Add initial stock if provided
            if(isset($_POST['batch_number']) && !empty($_POST['batch_number']) && 
               isset($_POST['initial_quantity']) && (int)$_POST['initial_quantity'] > 0) {
                $batchNumber = $this->sanitize($_POST['batch_number']);
                $expiryDate = $this->sanitize($_POST['expiry_date']);
                $quantity = (int)$_POST['initial_quantity'];
                $location = $this->sanitize($_POST['location'] ?? '');
                
                $stockQuery = "INSERT INTO inventory_stock (
                                item_id, batch_number, expiry_date, quantity, 
                                unit_cost, selling_price, location
                              ) VALUES (
                                $itemId, '$batchNumber', '$expiryDate', $quantity, 
                                $purchasePrice, $sellingPrice, '$location'
                              )";
                $this->db->query($stockQuery);
            }
            return $itemId;
        }
        throw new Exception($this->db->error);
    }
    
    // ==================== VIEW ITEM DETAILS ====================
    public function viewItem() {
        $this->checkAuth();
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $type = isset($_GET['type']) ? $this->sanitize($_GET['type']) : '';
        
        if($id == 0 || empty($type)) {
            $_SESSION['error'] = "Invalid request";
            $this->redirect('/inventory/items');
            return;
        }
        
        $item = null;
        $stockBatches = [];
        $transactions = [];
        
        switch($type) {
            case 'medicine':
                $item = $this->getMedicineCompleteDetails($id);
                $stockBatches = $this->getMedicineStock($id);
                $transactions = $this->getMedicineTransactions($id);
                break;
            case 'lab_test':
                $item = $this->getLabTestCompleteDetails($id);
                $transactions = $this->getLabTestTransactions($id);
                break;
            case 'equipment':
                $item = $this->getEquipmentCompleteDetails($id);
                $stockBatches = $this->getInventoryStock($id);
                $transactions = $this->getInventoryTransactions($id);
                break;
            case 'other':
                $item = $this->getOtherCompleteDetails($id);
                $stockBatches = $this->getInventoryStock($id);
                $transactions = $this->getInventoryTransactions($id);
                break;
            default:
                $_SESSION['error'] = "Invalid item type";
                $this->redirect('/inventory/items');
                return;
        }
        
        if(!$item || empty($item)) {
            $_SESSION['error'] = "Item not found";
            $this->redirect('/inventory/items');
            return;
        }
        
        // Ensure all required keys exist with proper defaults
        $item = $this->normalizeItemData($item, $type);
        
        $content = $this->renderView('inventory/view-item', [
            'item' => $item,
            'itemType' => $type,
            'stockBatches' => $stockBatches,
            'transactions' => $transactions
        ]);
        $this->renderLayout('Item Details', $content);
    }
    
    // ==================== ADD STOCK FORM ====================
    public function addStockForm() {
        $this->checkAuth();
        
        $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
        $itemName = isset($_GET['item_name']) ? urldecode($_GET['item_name']) : '';
        $itemType = isset($_GET['type']) ? $this->sanitize($_GET['type']) : '';
        
        $content = $this->renderView('inventory/add-stock', [
            'itemId' => $itemId,
            'itemName' => $itemName,
            'itemType' => $itemType
        ]);
        $this->renderLayout('Add Stock', $content);
    }
    
    // ==================== ADD STOCK (POST) ====================
    public function addStock() {
        header('Content-Type: application/json');
        
        $itemId = (int)$_POST['item_id'];
        $itemType = isset($_POST['item_type']) ? $this->sanitize($_POST['item_type']) : '';
        $batchNumber = $this->sanitize($_POST['batch_number']);
        $expiryDate = $this->sanitize($_POST['expiry_date']);
        $quantity = (int)$_POST['quantity'];
        $purchasePrice = (float)$_POST['purchase_price'];
        $sellingPrice = (float)$_POST['selling_price'];
        $location = isset($_POST['location']) ? $this->sanitize($_POST['location']) : '';
        
        if($quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
            exit;
        }
        
        try {
            $success = false;
            
            if($itemType == 'medicine') {
                $query = "INSERT INTO medicine_stock (
                            medicine_id, batch_number, expiry_date, quantity, 
                            purchase_price, selling_price, location, status, created_at
                          ) VALUES (
                            $itemId, '$batchNumber', '$expiryDate', $quantity, 
                            $purchasePrice, $sellingPrice, '$location', 'in_stock', NOW()
                          )";
                $success = $this->db->query($query);
            } else {
                $query = "INSERT INTO inventory_stock (
                            item_id, batch_number, expiry_date, quantity, 
                            unit_cost, selling_price, location
                          ) VALUES (
                            $itemId, '$batchNumber', '$expiryDate', $quantity, 
                            $purchasePrice, $sellingPrice, '$location'
                          )";
                $success = $this->db->query($query);
            }
            
            if($success) {
                echo json_encode(['success' => true, 'message' => 'Stock added successfully']);
            } else {
                throw new Exception($this->db->error);
            }
            
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    // ==================== DELETE STOCK ====================
    public function deleteStock() {
        header('Content-Type: application/json');
        
        $id = (int)$_POST['id'];
        $type = isset($_POST['stock_type']) ? $this->sanitize($_POST['stock_type']) : '';
        
        try {
            $success = false;
            
            if($type == 'medicine') {
                $success = $this->db->query("DELETE FROM medicine_stock WHERE id = $id");
            } else {
                $success = $this->db->query("DELETE FROM inventory_stock WHERE id = $id");
            }
            
            if($success) {
                echo json_encode(['success' => true, 'message' => 'Stock deleted successfully']);
            } else {
                throw new Exception('Failed to delete stock');
            }
            
        } catch(Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ==================== STOCK DETAILS PAGE ====================
public function stock() {
    $this->checkAuth();
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if($id == 0) {
        $_SESSION['error'] = "Invalid item ID";
        $this->redirect('/inventory/items');
        return;
    }
    
    // Get item details
    $itemQuery = "SELECT 
                    i.*,
                    c.name as category_name,
                    s.company_name as supplier_name,
                    COALESCE(SUM(st.quantity), 0) as current_stock
                  FROM inventory_items i
                  LEFT JOIN inventory_categories c ON i.category_id = c.id
                  LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id
                  LEFT JOIN inventory_stock st ON i.id = st.item_id AND st.expiry_date > CURDATE()
                  WHERE i.id = $id
                  GROUP BY i.id";
    
    $itemResult = $this->db->query($itemQuery);
    $item = $itemResult ? $itemResult->fetch_assoc() : null;
    
    if(!$item) {
        $_SESSION['error'] = "Item not found";
        $this->redirect('/inventory/items');
        return;
    }
    
    // Get stock batches
    $stockQuery = "SELECT * FROM inventory_stock WHERE item_id = $id ORDER BY expiry_date ASC";
    $stockResult = $this->db->query($stockQuery);
    $stockBatches = [];
    if($stockResult) {
        while($row = $stockResult->fetch_assoc()) {
            $stockBatches[] = $row;
        }
    }
    
    $content = $this->renderView('inventory/stock', [
        'item' => $item,
        'stockBatches' => $stockBatches
    ]);
    $this->renderLayout('Stock Details', $content);
}
    
    // ==================== PRIVATE HELPER METHODS ====================
    
    private function sanitize($input) {
        if($input === null) return '';
        return $this->db->real_escape_string(trim(htmlspecialchars($input)));
    }
    
    private function generateCode($prefix) {
        return $prefix . date('Ymd') . rand(100, 999);
    }
    
    private function getStockStatus($currentStock, $reorderLevel) {
        if($currentStock <= 0) return 'out';
        if($currentStock <= $reorderLevel) return 'low';
        return 'normal';
    }
    
    private function getLowStockCount() {
        $count = 0;
        
        $medQuery = "SELECT COUNT(*) as cnt FROM (
                        SELECT m.id, COALESCE(SUM(ms.quantity), 0) as stock, COALESCE(m.reorder_level, 10) as reorder
                        FROM medicines m
                        LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                        WHERE m.status = 'active'
                        GROUP BY m.id
                        HAVING stock <= reorder
                    ) as sub";
        $medResult = $this->db->query($medQuery);
        if($medResult) {
            $count += (int)$medResult->fetch_assoc()['cnt'];
        }
        
        $invQuery = "SELECT COUNT(*) as cnt FROM (
                        SELECT i.id, COALESCE(SUM(s.quantity), 0) as stock, COALESCE(i.reorder_level, 10) as reorder
                        FROM inventory_items i
                        LEFT JOIN inventory_stock s ON i.id = s.item_id AND s.expiry_date > CURDATE()
                        WHERE i.status = 'active'
                        GROUP BY i.id
                        HAVING stock <= reorder
                    ) as sub";
        $invResult = $this->db->query($invQuery);
        if($invResult) {
            $count += (int)$invResult->fetch_assoc()['cnt'];
        }
        
        return $count;
    }
    
    private function getExpiringCount() {
        $count = 0;
        
        $medResult = $this->db->query("SELECT COUNT(*) as cnt FROM medicine_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date > CURDATE()");
        if($medResult) $count += (int)$medResult->fetch_assoc()['cnt'];
        
        $invResult = $this->db->query("SELECT COUNT(*) as cnt FROM inventory_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date > CURDATE()");
        if($invResult) $count += (int)$invResult->fetch_assoc()['cnt'];
        
        return $count;
    }
    
    private function getTotalStockValue() {
        $total = 0;
        
        $medResult = $this->db->query("SELECT COALESCE(SUM(quantity * purchase_price), 0) as total FROM medicine_stock WHERE expiry_date > CURDATE()");
        if($medResult) $total += (float)$medResult->fetch_assoc()['total'];
        
        $invResult = $this->db->query("SELECT COALESCE(SUM(quantity * unit_cost), 0) as total FROM inventory_stock WHERE expiry_date > CURDATE()");
        if($invResult) $total += (float)$invResult->fetch_assoc()['total'];
        
        return $total;
    }
    
    private function getLowStockItems() {
        $items = [];
        
        $medQuery = "SELECT m.id, m.medicine_name as item_name, m.medicine_code as item_code, 
                     'medicine' as item_type, COALESCE(SUM(ms.quantity), 0) as current_stock,
                     COALESCE(m.reorder_level, 10) as reorder_level
                     FROM medicines m
                     LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                     WHERE m.status = 'active'
                     GROUP BY m.id
                     HAVING current_stock <= reorder_level
                     ORDER BY current_stock ASC LIMIT 10";
        $medResult = $this->db->query($medQuery);
        if($medResult) {
            while($row = $medResult->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        $invQuery = "SELECT i.id, i.item_name, i.item_code, i.item_type, 
                     COALESCE(SUM(s.quantity), 0) as current_stock, COALESCE(i.reorder_level, 10) as reorder_level
                     FROM inventory_items i
                     LEFT JOIN inventory_stock s ON i.id = s.item_id AND s.expiry_date > CURDATE()
                     WHERE i.status = 'active'
                     GROUP BY i.id
                     HAVING current_stock <= reorder_level
                     ORDER BY current_stock ASC LIMIT 10";
        $invResult = $this->db->query($invQuery);
        if($invResult) {
            while($row = $invResult->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        usort($items, function($a, $b) {
            return $a['current_stock'] - $b['current_stock'];
        });
        
        return array_slice($items, 0, 10);
    }
    
    private function calculateInventoryStats($items) {
        $stats = [
            'lowStockCount' => 0,
            'expiringCount' => 0,
            'totalValue' => 0
        ];
        
        $today = date('Y-m-d');
        $thirtyDaysLater = date('Y-m-d', strtotime('+30 days'));
        
        foreach($items as $item) {
            if($item['stock_status'] == 'low') {
                $stats['lowStockCount']++;
            }
            $stats['totalValue'] += ($item['current_stock'] ?? 0) * ($item['selling_price'] ?? 0);
            
            if(!empty($item['expiry_date']) && $item['expiry_date'] <= $thirtyDaysLater && $item['expiry_date'] > $today) {
                $stats['expiringCount']++;
            }
        }
        
        return $stats;
    }
    
    // ==================== DATA RETRIEVAL METHODS ====================
    
    private function getMedicineWithDetails($id) {
        $result = $this->db->query("SELECT m.*, c.name as category_name, s.company_name as supplier_name
                                    FROM medicines m
                                    LEFT JOIN medicine_categories c ON m.category_id = c.id
                                    LEFT JOIN medicine_suppliers s ON m.supplier_id = s.id
                                    WHERE m.id = $id");
        $item = $result ? $result->fetch_assoc() : null;
        if($item) {
            $stockResult = $this->db->query("SELECT COALESCE(SUM(quantity), 0) as total_stock 
                                            FROM medicine_stock WHERE medicine_id = $id AND expiry_date > CURDATE()");
            $item['current_stock'] = $stockResult ? (int)$stockResult->fetch_assoc()['total_stock'] : 0;
            $item['stock_status'] = $this->getStockStatus($item['current_stock'], $item['reorder_level'] ?? 10);
        }
        return $item;
    }
    
    private function getLabTestWithDetails($id) {
        $result = $this->db->query("SELECT lt.*, ltc.name as category_name, s.company_name as supplier_name
                                    FROM lab_tests lt
                                    LEFT JOIN lab_test_categories ltc ON lt.category_id = ltc.id
                                    LEFT JOIN lab_test_suppliers s ON lt.supplier_id = s.id
                                    WHERE lt.id = $id");
        return $result ? $result->fetch_assoc() : null;
    }
    
    private function getInventoryItemWithDetails($id) {
        $result = $this->db->query("SELECT i.*, c.name as category_name, s.company_name as supplier_name
                                    FROM inventory_items i
                                    LEFT JOIN inventory_categories c ON i.category_id = c.id
                                    LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id
                                    WHERE i.id = $id");
        $item = $result ? $result->fetch_assoc() : null;
        if($item) {
            $stockResult = $this->db->query("SELECT COALESCE(SUM(quantity), 0) as total_stock 
                                            FROM inventory_stock WHERE item_id = $id AND expiry_date > CURDATE()");
            $item['current_stock'] = $stockResult ? (int)$stockResult->fetch_assoc()['total_stock'] : 0;
            $item['stock_status'] = $this->getStockStatus($item['current_stock'], $item['reorder_level'] ?? 10);
        }
        return $item;
    }
    
    // ==================== TRANSACTION METHODS ====================
    
    private function getMedicineTransactions($id) {
        $transactions = [];
        
        // Get purchase/stock additions
        $stockQuery = "SELECT 
                        'Stock Added' as transaction_type,
                        created_at as transaction_date,
                        quantity,
                        purchase_price as unit_price
                       FROM medicine_stock 
                       WHERE medicine_id = $id
                       ORDER BY created_at DESC LIMIT 20";
        $result = $this->db->query($stockQuery);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
        }
        
        // Get sales from pharmacy_sale_items if any
        $saleQuery = "SELECT 
                        'Sale' as transaction_type,
                        ps.created_at as transaction_date,
                        psi.quantity,
                        psi.unit_price
                       FROM pharmacy_sale_items psi
                       JOIN pharmacy_sales ps ON psi.sale_id = ps.id
                       WHERE psi.medicine_id = $id
                       ORDER BY ps.created_at DESC LIMIT 20";
        $result = $this->db->query($saleQuery);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
        }
        
        // Sort by date
        usort($transactions, function($a, $b) {
            return strtotime($b['transaction_date']) - strtotime($a['transaction_date']);
        });
        
        return array_slice($transactions, 0, 20);
    }
    
    private function getLabTestTransactions($id) {
        $transactions = [];
        
        // Get orders for this lab test
        $query = "SELECT 
                    'Ordered' as transaction_type,
                    lo.created_at as transaction_date,
                    1 as quantity,
                    lt.price as unit_price
                   FROM lab_test_order_items loi
                   JOIN lab_test_orders lo ON loi.order_id = lo.id
                   JOIN lab_tests lt ON loi.test_id = lt.id
                   WHERE loi.test_id = $id
                   ORDER BY lo.created_at DESC LIMIT 20";
        $result = $this->db->query($query);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
        }
        
        return $transactions;
    }
    
    private function getInventoryTransactions($id) {
        $transactions = [];
        
        // Get stock additions
        $stockQuery = "SELECT 
                        'Stock Added' as transaction_type,
                        created_at as transaction_date,
                        quantity,
                        unit_cost as unit_price
                       FROM inventory_stock 
                       WHERE item_id = $id
                       ORDER BY created_at DESC LIMIT 20";
        $result = $this->db->query($stockQuery);
        if($result) {
            while($row = $result->fetch_assoc()) {
                $transactions[] = $row;
            }
        }
        
        return $transactions;
    }
    
    // ==================== COMPLETE DETAILS METHODS ====================
    
    private function getMedicineCompleteDetails($id) {
        $query = "SELECT 
                    m.*,
                    c.name as category_name,
                    s.company_name as supplier_name,
                    COALESCE(SUM(ms.quantity), 0) as current_stock,
                    MIN(ms.expiry_date) as expiry_date,
                    GROUP_CONCAT(DISTINCT ms.batch_number) as batch_numbers
                  FROM medicines m
                  LEFT JOIN medicine_categories c ON m.category_id = c.id
                  LEFT JOIN medicine_suppliers s ON m.supplier_id = s.id
                  LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                  WHERE m.id = $id
                  GROUP BY m.id";
        
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    private function getLabTestCompleteDetails($id) {
        $query = "SELECT 
                    lt.*,
                    ltc.name as category_name,
                    s.company_name as supplier_name
                  FROM lab_tests lt
                  LEFT JOIN lab_test_categories ltc ON lt.category_id = ltc.id
                  LEFT JOIN lab_test_suppliers s ON lt.supplier_id = s.id
                  WHERE lt.id = $id";
        
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    private function getEquipmentCompleteDetails($id) {
        $query = "SELECT 
                    i.*,
                    c.name as category_name,
                    s.company_name as supplier_name,
                    COALESCE(SUM(st.quantity), 0) as current_stock,
                    MIN(st.expiry_date) as expiry_date,
                    GROUP_CONCAT(DISTINCT st.batch_number) as batch_numbers
                  FROM inventory_items i
                  LEFT JOIN inventory_categories c ON i.category_id = c.id
                  LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id
                  LEFT JOIN inventory_stock st ON i.id = st.item_id AND st.expiry_date > CURDATE()
                  WHERE i.id = $id AND i.item_type = 'equipment'
                  GROUP BY i.id";
        
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    private function getOtherCompleteDetails($id) {
        $query = "SELECT 
                    i.*,
                    c.name as category_name,
                    s.company_name as supplier_name,
                    COALESCE(SUM(st.quantity), 0) as current_stock,
                    MIN(st.expiry_date) as expiry_date,
                    GROUP_CONCAT(DISTINCT st.batch_number) as batch_numbers
                  FROM inventory_items i
                  LEFT JOIN inventory_categories c ON i.category_id = c.id
                  LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id
                  LEFT JOIN inventory_stock st ON i.id = st.item_id AND st.expiry_date > CURDATE()
                  WHERE i.id = $id AND i.item_type = 'other'
                  GROUP BY i.id";
        
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc() : null;
    }
    
    // ==================== NORMALIZE ITEM DATA METHOD ====================
    
    private function normalizeItemData($item, $type) {
        $normalized = [];
        
        // Set item type first
        $normalized['item_type'] = $type;
        
        // Map common fields based on type
        switch($type) {
            case 'medicine':
                $normalized['id'] = $item['id'] ?? 0;
                $normalized['item_code'] = $item['medicine_code'] ?? $item['item_code'] ?? 'N/A';
                $normalized['item_name'] = $item['medicine_name'] ?? $item['item_name'] ?? 'N/A';
                $normalized['generic_name'] = $item['generic_name'] ?? 'N/A';
                $normalized['category_name'] = $item['category_name'] ?? 'Uncategorized';
                $normalized['status'] = $item['status'] ?? 'active';
                $normalized['created_at'] = $item['created_at'] ?? date('Y-m-d H:i:s');
                $normalized['current_stock'] = isset($item['current_stock']) ? (int)$item['current_stock'] : 0;
                $normalized['reorder_level'] = isset($item['reorder_level']) ? (int)$item['reorder_level'] : 10;
                $normalized['unit_of_measure'] = $item['unit_of_measure'] ?? 'Strip';
                $normalized['purchase_price'] = isset($item['purchase_price']) ? (float)$item['purchase_price'] : 0;
                $normalized['selling_price'] = isset($item['selling_price']) ? (float)$item['selling_price'] : 0;
                $normalized['mrp'] = isset($item['mrp']) ? (float)$item['mrp'] : 0;
                $normalized['tax_percentage'] = isset($item['tax_percentage']) ? (float)$item['tax_percentage'] : 0;
                $normalized['manufacturer'] = $item['manufacturer'] ?? 'N/A';
                $normalized['strength'] = $item['strength'] ?? 'N/A';
                $normalized['dosage_form'] = $item['dosage_form'] ?? 'N/A';
                $normalized['requires_prescription'] = isset($item['requires_prescription']) ? (int)$item['requires_prescription'] : 0;
                $normalized['supplier_name'] = $item['supplier_name'] ?? 'N/A';
                $normalized['storage_location'] = $item['storage_location'] ?? 'N/A';
                $normalized['storage_condition'] = $item['storage_condition'] ?? 'Room temperature';
                $normalized['requires_refrigeration'] = isset($item['requires_refrigeration']) ? (int)$item['requires_refrigeration'] : 0;
                $normalized['description'] = $item['description'] ?? 'No description available';
                $normalized['side_effects'] = $item['side_effects'] ?? '';
                $normalized['batch_number'] = $item['batch_number'] ?? 'N/A';
                $normalized['expiry_date'] = $item['expiry_date'] ?? 'N/A';
                break;
                
            case 'lab_test':
                $normalized['id'] = $item['id'] ?? 0;
                $normalized['item_code'] = $item['test_code'] ?? $item['item_code'] ?? 'N/A';
                $normalized['item_name'] = $item['test_name'] ?? $item['item_name'] ?? 'N/A';
                $normalized['generic_name'] = $item['test_name'] ?? 'N/A';
                $normalized['category_name'] = $item['category_name'] ?? 'Uncategorized';
                $normalized['status'] = $item['status'] ?? 'active';
                $normalized['created_at'] = $item['created_at'] ?? date('Y-m-d H:i:s');
                $normalized['current_stock'] = 0;
                $normalized['reorder_level'] = 10;
                $normalized['unit_of_measure'] = 'Test';
                $normalized['purchase_price'] = isset($item['price']) ? (float)$item['price'] : 0;
                $normalized['selling_price'] = isset($item['price']) ? (float)$item['price'] : 0;
                $normalized['test_code'] = $item['test_code'] ?? $item['item_code'] ?? 'N/A';
                $normalized['specimen_type'] = $item['specimen_type'] ?? 'N/A';
                $normalized['turnaround_time'] = $item['turnaround_time'] ?? 'N/A';
                $normalized['normal_range'] = $item['normal_range'] ?? 'N/A';
                $normalized['unit'] = $item['unit'] ?? 'N/A';
                $normalized['requires_fasting'] = isset($item['requires_fasting']) ? (int)$item['requires_fasting'] : 0;
                $normalized['supplier_name'] = $item['supplier_name'] ?? 'N/A';
                $normalized['description'] = $item['description'] ?? 'No description available';
                $normalized['preparation_instructions'] = $item['preparation_instructions'] ?? '';
                break;
                
            case 'equipment':
            case 'other':
                $normalized['id'] = $item['id'] ?? 0;
                $normalized['item_code'] = $item['item_code'] ?? 'N/A';
                $normalized['item_name'] = $item['item_name'] ?? 'N/A';
                $normalized['generic_name'] = $item['generic_name'] ?? 'N/A';
                $normalized['category_name'] = $item['category_name'] ?? 'Uncategorized';
                $normalized['status'] = $item['status'] ?? 'active';
                $normalized['created_at'] = $item['created_at'] ?? date('Y-m-d H:i:s');
                $normalized['current_stock'] = isset($item['current_stock']) ? (int)$item['current_stock'] : 0;
                $normalized['reorder_level'] = isset($item['reorder_level']) ? (int)$item['reorder_level'] : 10;
                $normalized['unit_of_measure'] = $item['unit_of_measure'] ?? 'Piece';
                $normalized['purchase_price'] = isset($item['purchase_price']) ? (float)$item['purchase_price'] : 0;
                $normalized['selling_price'] = isset($item['selling_price']) ? (float)$item['selling_price'] : 0;
                $normalized['mrp'] = isset($item['mrp']) ? (float)$item['mrp'] : 0;
                $normalized['tax_percentage'] = isset($item['tax_percentage']) ? (float)$item['tax_percentage'] : 0;
                $normalized['brand'] = $item['brand'] ?? 'N/A';
                $normalized['warranty'] = isset($item['warranty']) ? (int)$item['warranty'] : 0;
                $normalized['serial_number'] = $item['serial_number'] ?? 'N/A';
                $normalized['pack_size'] = $item['pack_size'] ?? 'N/A';
                $normalized['supplier_name'] = $item['supplier_name'] ?? 'N/A';
                $normalized['storage_location'] = $item['storage_location'] ?? 'N/A';
                $normalized['storage_condition'] = $item['storage_condition'] ?? 'Room temperature';
                $normalized['requires_refrigeration'] = isset($item['requires_refrigeration']) ? (int)$item['requires_refrigeration'] : 0;
                $normalized['description'] = $item['description'] ?? 'No description available';
                $normalized['batch_number'] = $item['batch_number'] ?? 'N/A';
                $normalized['expiry_date'] = $item['expiry_date'] ?? 'N/A';
                break;
        }
        
        return $normalized;
    }
    
    // ==================== STOCK RETRIEVAL METHODS ====================
    
    private function getMedicineStock($id) {
        $result = $this->db->query("SELECT * FROM medicine_stock WHERE medicine_id = $id ORDER BY expiry_date ASC");
        $stock = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $row['status'] = $row['expiry_date'] < date('Y-m-d') ? 'expired' : 
                                 ($row['quantity'] <= 10 ? 'low_stock' : 'in_stock');
                $stock[] = $row;
            }
        }
        return $stock;
    }
    
    private function getInventoryStock($id) {
        $result = $this->db->query("SELECT * FROM inventory_stock WHERE item_id = $id ORDER BY expiry_date ASC");
        $stock = [];
        if($result) {
            while($row = $result->fetch_assoc()) {
                $stock[] = $row;
            }
        }
        return $stock;
    }
    
    // ==================== CATEGORY RETRIEVAL METHODS ====================
    
    private function getMedicineCategories() {
        $categories = [];
        $result = $this->db->query("SELECT id, name, code, description FROM medicine_categories WHERE status = 'active' ORDER BY name");
        if($result) {
            while($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }
    
    private function getInventoryCategories() {
        $categories = [];
        $result = $this->db->query("SELECT id, name, code, description FROM inventory_categories WHERE status = 'active' ORDER BY name");
        if($result) {
            while($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }
    
    private function getLabCategories() {
        $categories = [];
        $result = $this->db->query("SELECT id, name, code, description FROM lab_test_categories WHERE status = 'active' ORDER BY name");
        if($result) {
            while($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
        }
        return $categories;
    }
    
    private function getAllCategories() {
        $allCategories = [];
        
        $medCats = $this->getMedicineCategories();
        foreach($medCats as $cat) {
            $allCategories[] = ['id' => $cat['id'], 'name' => $cat['name'] . ' (Medicine)', 'type' => 'medicine'];
        }
        
        $invCats = $this->getInventoryCategories();
        foreach($invCats as $cat) {
            $allCategories[] = ['id' => $cat['id'], 'name' => $cat['name'] . ' (Inventory)', 'type' => 'inventory'];
        }
        
        $labCats = $this->getLabCategories();
        foreach($labCats as $cat) {
            $allCategories[] = ['id' => $cat['id'], 'name' => $cat['name'] . ' (Lab)', 'type' => 'lab'];
        }
        
        usort($allCategories, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $allCategories;
    }
    
    // ==================== SUPPLIER RETRIEVAL METHODS ====================
    
    private function getMedicineSuppliers() {
    $suppliers = [];
    $result = $this->db->query("SELECT id, supplier_code, company_name, contact_person, email, phone 
                               FROM medicine_suppliers WHERE status = 'active' ORDER BY company_name");
    if($result) {
        while($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    return $suppliers;
}

private function getLabSuppliers() {
    $suppliers = [];
    $result = $this->db->query("SELECT id, supplier_code, company_name, contact_person, email, phone 
                               FROM lab_test_suppliers WHERE status = 'active' ORDER BY company_name");
    if($result) {
        while($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    return $suppliers;
}

private function getInventorySuppliers() {
    $suppliers = [];
    $result = $this->db->query("SELECT id, supplier_code, company_name, contact_person, email, phone 
                               FROM inventory_suppliers WHERE status = 'active' ORDER BY company_name");
    if($result) {
        while($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    return $suppliers;
}
    
    

 // ==================== EXPIRY ALERTS ====================
public function expiryAlerts() {
    $this->checkAuth();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $medicineAlerts = [];
    $labAlerts = [];
    $inventoryAlerts = [];
    $expiredItems = [];
    
    $criticalCount = 0;
    $warningCount = 0;
    $expiredCount = 0;
    $totalItems = 0;
    
    // 1. Get medicine expiry alerts
    $medQuery = "SELECT 
                    ms.id as stock_id,
                    'medicine' as item_type,
                    m.id as item_id,
                    m.medicine_name as item_name,
                    m.medicine_code as item_code,
                    m.unit_of_measure,
                    ms.batch_number,
                    ms.expiry_date,
                    ms.quantity,
                    CASE 
                        WHEN ms.expiry_date <= CURDATE() THEN 'expired'
                        WHEN ms.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'critical'
                        ELSE 'warning'
                    END as alert_type,
                    CONCAT('Medicine expires on ', DATE_FORMAT(ms.expiry_date, '%d %b %Y')) as message
                 FROM medicine_stock ms
                 JOIN medicines m ON ms.medicine_id = m.id
                 WHERE ms.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 AND ms.quantity > 0
                 ORDER BY ms.expiry_date ASC
                 LIMIT $offset, $limit";
    
    $medResult = $this->db->query($medQuery);
    if($medResult) {
        while($row = $medResult->fetch_assoc()) {
            $daysLeft = (strtotime($row['expiry_date']) - time()) / (60 * 60 * 24);
            $daysLeft = round($daysLeft);
            
            if($row['expiry_date'] <= date('Y-m-d')) {
                $row['alert_type'] = 'expired';
                $row['message'] = 'EXPIRED on ' . date('d M Y', strtotime($row['expiry_date']));
                $expiredItems[] = $row;
                $expiredCount++;
            } elseif($daysLeft <= 7) {
                $row['alert_type'] = 'critical';
                $row['message'] = 'CRITICAL: Expires in ' . $daysLeft . ' days!';
                $medicineAlerts[] = $row;
                $criticalCount++;
            } else {
                $row['alert_type'] = 'warning';
                $row['message'] = 'Warning: Expires in ' . $daysLeft . ' days';
                $medicineAlerts[] = $row;
                $warningCount++;
            }
            $totalItems++;
        }
    }
    
    // 2. Get inventory item expiry alerts (equipment, consumables, etc.)
    $invQuery = "SELECT 
                    s.id as stock_id,
                    i.item_type,
                    i.id as item_id,
                    i.item_name,
                    i.item_code,
                    i.unit_of_measure,
                    s.batch_number,
                    s.expiry_date,
                    s.quantity,
                    CASE 
                        WHEN s.expiry_date <= CURDATE() THEN 'expired'
                        WHEN s.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'critical'
                        ELSE 'warning'
                    END as alert_type,
                    CONCAT('Item expires on ', DATE_FORMAT(s.expiry_date, '%d %b %Y')) as message
                 FROM inventory_stock s
                 JOIN inventory_items i ON s.item_id = i.id
                 WHERE s.expiry_date IS NOT NULL
                 AND s.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 AND s.quantity > 0
                 ORDER BY s.expiry_date ASC
                 LIMIT $offset, $limit";
    
    $invResult = $this->db->query($invQuery);
    if($invResult) {
        while($row = $invResult->fetch_assoc()) {
            $daysLeft = (strtotime($row['expiry_date']) - time()) / (60 * 60 * 24);
            $daysLeft = round($daysLeft);
            
            if($row['expiry_date'] <= date('Y-m-d')) {
                $row['alert_type'] = 'expired';
                $row['message'] = 'EXPIRED on ' . date('d M Y', strtotime($row['expiry_date']));
                $expiredItems[] = $row;
                $expiredCount++;
            } elseif($daysLeft <= 7) {
                $row['alert_type'] = 'critical';
                $row['message'] = 'CRITICAL: Expires in ' . $daysLeft . ' days!';
                $inventoryAlerts[] = $row;
                $criticalCount++;
            } else {
                $row['alert_type'] = 'warning';
                $row['message'] = 'Warning: Expires in ' . $daysLeft . ' days';
                $inventoryAlerts[] = $row;
                $warningCount++;
            }
            $totalItems++;
        }
    }
    
    // Get total counts for pagination
    $totalMedCount = $this->db->query("SELECT COUNT(*) as cnt FROM medicine_stock WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND quantity > 0")->fetch_assoc()['cnt'] ?? 0;
    $totalInvCount = $this->db->query("SELECT COUNT(*) as cnt FROM inventory_stock WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND quantity > 0")->fetch_assoc()['cnt'] ?? 0;
    $totalRecords = $totalMedCount + $totalInvCount;
    $totalPages = ceil($totalRecords / $limit);
    
    $content = $this->renderView('inventory/expiry-alerts', [
        'medicineAlerts' => $medicineAlerts,
        'labAlerts' => $labAlerts,
        'inventoryAlerts' => $inventoryAlerts,
        'expiredItems' => $expiredItems,
        'criticalCount' => $criticalCount,
        'warningCount' => $warningCount,
        'expiredCount' => $expiredCount,
        'totalItems' => $totalItems,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalRecords' => $totalRecords,
        'limit' => $limit
    ]);
    $this->renderLayout('Expiry Alerts', $content);
}

// Acknowledge expiry alert
public function acknowledgeExpiryAlert() {
    header('Content-Type: application/json');
    
    $itemType = isset($_POST['item_type']) ? $this->sanitize($_POST['item_type']) : '';
    $stockId = (int)$_POST['stock_id'];
    
    // You can insert into a notification log table
    // For now, just return success
    
    echo json_encode(['success' => true, 'message' => 'Alert acknowledged']);
    exit;
}

// Dispose expired item
public function disposeExpiredItem() {
    header('Content-Type: application/json');
    
    $itemType = isset($_POST['item_type']) ? $this->sanitize($_POST['item_type']) : '';
    $stockId = (int)$_POST['stock_id'];
    
    if($itemType == 'medicine') {
        $this->db->query("UPDATE medicine_stock SET quantity = 0, status = 'expired' WHERE id = $stockId");
    } elseif($itemType == 'inventory') {
        $this->db->query("UPDATE inventory_stock SET quantity = 0 WHERE id = $stockId");
    }
    
    echo json_encode(['success' => true, 'message' => 'Item marked for disposal']);
    exit;
}

// ==================== MARK ALERT AS READ ====================
public function markAlertRead() {
    header('Content-Type: application/json');
    
    $alertId = isset($_POST['alert_id']) ? (int)$_POST['alert_id'] : 0;
    
    if($alertId) {
        // You can create an expiry_alerts_read table or just return success
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// ==================== REORDER ALERTS ====================
public function reorderAlerts() {
    $this->checkAuth();
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $medicineAlerts = [];
    $labAlerts = [];
    $inventoryAlerts = [];
    $totalAlerts = 0;
    $totalCritical = 0;
    $totalWarning = 0;
    $totalOutOfStock = 0;
    
    // 1. Get medicine reorder alerts with full details
    $medQuery = "SELECT 
                    'medicine' as source_type,
                    m.id,
                    m.medicine_name as item_name,
                    m.medicine_code as item_code,
                    m.unit_of_measure,
                    m.manufacturer,
                    m.strength,
                    m.selling_price,
                    m.purchase_price,
                    10 as reorder_level,
                    50 as reorder_quantity,
                    COALESCE(SUM(ms.quantity), 0) as current_stock,
                    s.company_name as supplier_name,
                    s.id as supplier_id
                 FROM medicines m
                 LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                 LEFT JOIN medicine_suppliers s ON m.supplier_id = s.id
                 WHERE m.status = 'active'
                 GROUP BY m.id
                 HAVING COALESCE(SUM(ms.quantity), 0) <= 10
                 ORDER BY COALESCE(SUM(ms.quantity), 0) ASC";
    
    $medResult = $this->db->query($medQuery);
    if($medResult) {
        while($row = $medResult->fetch_assoc()) {
            $row['current_stock'] = (int)$row['current_stock'];
            $row['selling_price'] = (float)$row['selling_price'];
            $row['purchase_price'] = (float)$row['purchase_price'];
            $row['reorder_level'] = 10;
            $row['reorder_quantity'] = 50;
            
            $stockPercent = ($row['current_stock'] / max(1, $row['reorder_level'])) * 100;
            if($stockPercent < 30 || $row['current_stock'] <= 0) {
                $totalCritical++;
            } else {
                $totalWarning++;
            }
            if($row['current_stock'] <= 0) $totalOutOfStock++;
            $medicineAlerts[] = $row;
            $totalAlerts++;
        }
    }
    
    // 2. Get lab test reorder alerts
    $labQuery = "SELECT 
                    'lab_test' as source_type,
                    lt.id,
                    lt.test_name as item_name,
                    lt.test_code as item_code,
                    'Kit' as unit_of_measure,
                    NULL as manufacturer,
                    lt.specimen_type as strength,
                    lt.price as selling_price,
                    lt.price as purchase_price,
                    10 as reorder_level,
                    25 as reorder_quantity,
                    0 as current_stock,
                    s.company_name as supplier_name,
                    s.id as supplier_id
                 FROM lab_tests lt
                 LEFT JOIN lab_test_suppliers s ON lt.supplier_id = s.id
                 WHERE lt.status = 'active'
                 ORDER BY lt.test_name ASC";
    
    $labResult = $this->db->query($labQuery);
    if($labResult) {
        while($row = $labResult->fetch_assoc()) {
            $row['current_stock'] = 0;
            $row['selling_price'] = (float)$row['selling_price'];
            $row['reorder_level'] = 10;
            $row['reorder_quantity'] = 25;
            $totalCritical++;
            $totalOutOfStock++;
            $labAlerts[] = $row;
            $totalAlerts++;
        }
    }
    
    // 3. Get inventory reorder alerts
    $invQuery = "SELECT 
                    i.item_type as source_type,
                    i.id,
                    i.item_name,
                    i.item_code,
                    i.unit_of_measure,
                    i.manufacturer,
                    i.brand,
                    i.selling_price,
                    i.purchase_price,
                    10 as reorder_level,
                    50 as reorder_quantity,
                    COALESCE(SUM(s.quantity), 0) as current_stock,
                    sup.company_name as supplier_name,
                    sup.id as supplier_id
                 FROM inventory_items i
                 LEFT JOIN inventory_stock s ON i.id = s.item_id AND s.expiry_date > CURDATE()
                 LEFT JOIN inventory_suppliers sup ON i.supplier_id = sup.id
                 WHERE i.status = 'active'
                 GROUP BY i.id
                 HAVING COALESCE(SUM(s.quantity), 0) <= 10
                 ORDER BY COALESCE(SUM(s.quantity), 0) ASC";
    
    $invResult = $this->db->query($invQuery);
    if($invResult) {
        while($row = $invResult->fetch_assoc()) {
            $row['current_stock'] = (int)$row['current_stock'];
            $row['selling_price'] = (float)$row['selling_price'];
            $row['reorder_level'] = 10;
            $row['reorder_quantity'] = 50;
            
            $stockPercent = ($row['current_stock'] / max(1, $row['reorder_level'])) * 100;
            if($stockPercent < 30 || $row['current_stock'] <= 0) {
                $totalCritical++;
            } else {
                $totalWarning++;
            }
            if($row['current_stock'] <= 0) $totalOutOfStock++;
            $inventoryAlerts[] = $row;
            $totalAlerts++;
        }
    }
    
    // Combine all alerts for pagination
    $allAlerts = array_merge($medicineAlerts, $labAlerts, $inventoryAlerts);
    usort($allAlerts, function($a, $b) {
        $percentA = $a['current_stock'] / max(1, $a['reorder_level']);
        $percentB = $b['current_stock'] / max(1, $b['reorder_level']);
        return $percentA - $percentB;
    });
    
    $totalRecords = count($allAlerts);
    $totalPages = $totalRecords > 0 ? ceil($totalRecords / $limit) : 1;
    $paginatedAlerts = array_slice($allAlerts, $offset, $limit);
    
    // Separate paginated alerts by type
    $paginatedMedicine = [];
    $paginatedLab = [];
    $paginatedInventory = [];
    
    foreach($paginatedAlerts as $alert) {
        if($alert['source_type'] == 'medicine') {
            $paginatedMedicine[] = $alert;
        } elseif($alert['source_type'] == 'lab_test') {
            $paginatedLab[] = $alert;
        } else {
            $paginatedInventory[] = $alert;
        }
    }
    
    // Get all suppliers for the modal (pre-load by type)
    $allMedicineSuppliers = $this->getMedicineSuppliers();
    $allLabSuppliers = $this->getLabSuppliers();
    $allInventorySuppliers = $this->getInventorySuppliers();

    $content = $this->renderView('inventory/reorder-alerts', [
        'medicineAlerts' => $paginatedMedicine,
        'labAlerts' => $paginatedLab,
        'inventoryAlerts' => $paginatedInventory,
        'totalAlerts' => $totalAlerts,
        'totalCritical' => $totalCritical,
        'totalWarning' => $totalWarning,
        'totalOutOfStock' => $totalOutOfStock,
        'currentPage' => $page,
        'totalPages' => $totalPages,
        'totalRecords' => $totalRecords,
        'limit' => $limit,
        'allMedicineSuppliers' => $allMedicineSuppliers,
        'allLabSuppliers' => $allLabSuppliers,
        'allInventorySuppliers' => $allInventorySuppliers
    ]);
    $this->renderLayout('Reorder Alerts', $content);
}

// ==================== PROCESS REORDER ====================
public function processReorder() {
    header('Content-Type: application/json');
    
    $alertId = isset($_POST['alert_id']) ? (int)$_POST['alert_id'] : 0;
    $action = isset($_POST['action']) ? $this->sanitize($_POST['action']) : '';
    $itemType = isset($_POST['item_type']) ? $this->sanitize($_POST['item_type']) : 'medicine';
    
    if($action == 'create_po') {
        // Get item details to create purchase order
        if($itemType == 'medicine') {
            $itemQuery = "SELECT m.*, s.company_name as supplier_name 
                         FROM medicines m 
                         LEFT JOIN medicine_suppliers s ON m.supplier_id = s.id 
                         WHERE m.id = $alertId";
        } else {
            $itemQuery = "SELECT i.*, s.company_name as supplier_name 
                         FROM inventory_items i 
                         LEFT JOIN inventory_suppliers s ON i.supplier_id = s.id 
                         WHERE i.id = $alertId";
        }
        
        $itemResult = $this->db->query($itemQuery);
        $item = $itemResult ? $itemResult->fetch_assoc() : null;
        
        if($item) {
            // You can create a purchase order here
            // For now, just return success with item info
            echo json_encode([
                'success' => true, 
                'message' => 'Purchase Order created for ' . $item['item_name'],
                'item' => $item
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
    } elseif($action == 'ignore') {
        // Mark alert as ignored - you can create an ignored_alerts table
        echo json_encode(['success' => true, 'message' => 'Alert ignored']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// ==================== STORE MANAGEMENT ====================
public function stores() {
    $this->checkAuth();
    
    // Get all stores
    $query = "SELECT * FROM stores WHERE status = 'active' ORDER BY store_name ASC";
    $result = $this->db->query($query);
    $stores = [];
    if($result) {
        while($row = $result->fetch_assoc()) {
            $stores[] = $row;
        }
    }
    
    $content = $this->renderView('inventory/stores', [
        'stores' => $stores
    ]);
    $this->renderLayout('Store Management', $content);
}

// ==================== ADD STORE ====================
public function addStore() {
    header('Content-Type: application/json');
    
    $storeCode = $this->generateStoreCode();
    $storeName = $this->sanitize($_POST['store_name']);
    $storeType = $this->sanitize($_POST['store_type']);
    $address = $this->sanitize($_POST['address'] ?? '');
    $city = $this->sanitize($_POST['city'] ?? '');
    $phone = $this->sanitize($_POST['phone']);
    $email = $this->sanitize($_POST['email'] ?? '');
    $managerName = $this->sanitize($_POST['manager_name'] ?? '');
    
    $query = "INSERT INTO stores (store_code, store_name, store_type, address, city, phone, email, manager_name, status) 
              VALUES ('$storeCode', '$storeName', '$storeType', '$address', '$city', '$phone', '$email', '$managerName', 'active')";
    
    if($this->db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Store added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $this->db->error]);
    }
    exit;
}

// ==================== EDIT STORE FORM ====================
public function editStoreForm($id) {
    $this->checkAuth();
    
    $id = (int)$id;
    $query = "SELECT * FROM stores WHERE id = $id";
    $result = $this->db->query($query);
    $store = $result ? $result->fetch_assoc() : null;
    
    if(!$store) {
        $_SESSION['error'] = "Store not found";
        $this->redirect('/inventory/stores');
        return;
    }
    
    $content = $this->renderView('inventory/edit-store', [
        'store' => $store
    ]);
    $this->renderLayout('Edit Store', $content);
}

// ==================== UPDATE STORE ====================
public function updateStore() {
    header('Content-Type: application/json');
    
    $id = (int)$_POST['id'];
    $storeName = $this->sanitize($_POST['store_name']);
    $storeType = $this->sanitize($_POST['store_type']);
    $address = $this->sanitize($_POST['address'] ?? '');
    $city = $this->sanitize($_POST['city'] ?? '');
    $phone = $this->sanitize($_POST['phone']);
    $email = $this->sanitize($_POST['email'] ?? '');
    $managerName = $this->sanitize($_POST['manager_name'] ?? '');
    $status = $this->sanitize($_POST['status'] ?? 'active');
    
    $query = "UPDATE stores SET 
                store_name = '$storeName',
                store_type = '$storeType',
                address = '$address',
                city = '$city',
                phone = '$phone',
                email = '$email',
                manager_name = '$managerName',
                status = '$status'
              WHERE id = $id";
    
    if($this->db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Store updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $this->db->error]);
    }
    exit;
}

// ==================== DELETE STORE ====================
public function deleteStore() {
    header('Content-Type: application/json');
    
    $id = (int)$_POST['id'];
    
    // Soft delete - set status to inactive
    $query = "UPDATE stores SET status = 'inactive' WHERE id = $id";
    
    if($this->db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Store deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $this->db->error]);
    }
    exit;
}

// ==================== VIEW STORE STOCK ====================
public function storeStock($id) {
    $this->checkAuth();
    
    $id = (int)$id;
    
    // Get store details
    $storeQuery = "SELECT * FROM stores WHERE id = $id";
    $storeResult = $this->db->query($storeQuery);
    $store = $storeResult ? $storeResult->fetch_assoc() : null;
    
    if(!$store) {
        $_SESSION['error'] = "Store not found";
        $this->redirect('/inventory/stores');
        return;
    }
    
    // Get stock items for this store
    $stockQuery = "SELECT 
                    s.*,
                    i.item_name,
                    i.item_code,
                    i.item_type,
                    i.unit_of_measure,
                    i.selling_price
                  FROM inventory_stock s
                  JOIN inventory_items i ON s.item_id = i.id
                  WHERE s.location LIKE '%" . $this->db->real_escape_string($store['store_name']) . "%'
                  ORDER BY s.expiry_date ASC";
    
    $stockResult = $this->db->query($stockQuery);
    $stockItems = [];
    if($stockResult) {
        while($row = $stockResult->fetch_assoc()) {
            $stockItems[] = $row;
        }
    }
    
    $content = $this->renderView('inventory/store-stock', [
        'store' => $store,
        'stockItems' => $stockItems
    ]);
    $this->renderLayout('Store Stock', $content);
}

// Helper method to generate store code
private function generateStoreCode() {
    $code = 'STR' . date('Ymd') . rand(100, 999);
    $checkQuery = "SELECT id FROM stores WHERE store_code = '$code'";
    $checkResult = $this->db->query($checkQuery);
    if($checkResult && $checkResult->num_rows > 0) {
        return $this->generateStoreCode();
    }
    return $code;
}
// ==================== STOCK TRANSFERS ====================
public function stockTransfers() {
    $this->checkAuth();
    
    // Get all transfers with store names
    $query = "SELECT st.*, 
                     fs.store_name as from_store_name,
                     ts.store_name as to_store_name,
                     u.first_name as requested_by_name
              FROM stock_transfers st
              LEFT JOIN stores fs ON st.from_store_id = fs.id
              LEFT JOIN stores ts ON st.to_store_id = ts.id
              LEFT JOIN users u ON st.requested_by = u.id
              ORDER BY st.created_at DESC";
    
    $result = $this->db->query($query);
    $transfers = [];
    if($result) {
        while($row = $result->fetch_assoc()) {
            $transfers[] = $row;
        }
    }
    
    // Get all stores for dropdown
    $storesQuery = "SELECT * FROM stores WHERE status = 'active' ORDER BY store_name";
    $storesResult = $this->db->query($storesQuery);
    $stores = [];
    if($storesResult) {
        while($row = $storesResult->fetch_assoc()) {
            $stores[] = $row;
        }
    }
    
    // Get all inventory items for dropdown
    $itemsQuery = "SELECT id, item_name, item_code FROM inventory_items WHERE status = 'active' ORDER BY item_name";
    $itemsResult = $this->db->query($itemsQuery);
    $items = [];
    if($itemsResult) {
        while($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    $content = $this->renderView('inventory/stock-transfers', [
        'transfers' => $transfers,
        'stores' => $stores,
        'items' => $items
    ]);
    $this->renderLayout('Stock Transfers', $content);
}

// ==================== CREATE TRANSFER ====================
public function createTransfer() {
    header('Content-Type: application/json');
    
    $fromStoreId = (int)$_POST['from_store_id'];
    $toStoreId = (int)$_POST['to_store_id'];
    $transferDate = $this->sanitize($_POST['transfer_date']);
    $notes = $this->sanitize($_POST['notes'] ?? '');
    $requestedBy = $_SESSION['user_id'] ?? 1;
    
    // Generate transfer number
    $transferNumber = $this->generateTransferNumber();
    
    // Validate stores are different
    if($fromStoreId == $toStoreId) {
        echo json_encode(['success' => false, 'message' => 'Source and destination stores cannot be the same']);
        exit;
    }
    
    // Start transaction
    $this->db->begin_transaction();
    
    try {
        // Insert transfer record
        $query = "INSERT INTO stock_transfers (transfer_number, from_store_id, to_store_id, transfer_date, status, requested_by, notes, created_at) 
                  VALUES ('$transferNumber', $fromStoreId, $toStoreId, '$transferDate', 'pending', $requestedBy, '$notes', NOW())";
        
        if(!$this->db->query($query)) {
            throw new Exception($this->db->error);
        }
        
        $transferId = $this->db->insert_id;
        
        // Insert transfer items
        $items = $_POST['items'] ?? [];
        foreach($items as $item) {
            $itemId = (int)$item['item_id'];
            $quantity = (int)$item['quantity'];
            
            if($itemId > 0 && $quantity > 0) {
                // Get current stock from source store
                $stockQuery = "SELECT s.id, s.quantity, s.unit_cost, s.selling_price 
                              FROM inventory_stock s
                              WHERE s.item_id = $itemId AND s.location LIKE '%" . $this->db->real_escape_string($fromStoreId) . "%'
                              AND s.quantity >= $quantity
                              LIMIT 1";
                
                $stockResult = $this->db->query($stockQuery);
                if($stockResult && $stockResult->num_rows > 0) {
                    $stock = $stockResult->fetch_assoc();
                    
                    $itemQuery = "INSERT INTO stock_transfer_items (transfer_id, item_id, stock_id, quantity, unit_cost) 
                                  VALUES ($transferId, $itemId, {$stock['id']}, $quantity, {$stock['unit_cost']})";
                    
                    if(!$this->db->query($itemQuery)) {
                        throw new Exception($this->db->error);
                    }
                } else {
                    throw new Exception("Insufficient stock for item ID: $itemId");
                }
            }
        }
        
        $this->db->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer created successfully', 'transfer_number' => $transferNumber]);
        
    } catch(Exception $e) {
        $this->db->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==================== APPROVE TRANSFER ====================
public function approveTransfer() {
    header('Content-Type: application/json');
    
    $transferId = (int)$_POST['transfer_id'];
    $approvedBy = $_SESSION['user_id'] ?? 1;
    
    $query = "UPDATE stock_transfers SET status = 'approved', approved_by = $approvedBy, updated_at = NOW() WHERE id = $transferId";
    
    if($this->db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Transfer approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $this->db->error]);
    }
    exit;
}

// ==================== RECEIVE TRANSFER ====================
public function receiveTransfer() {
    header('Content-Type: application/json');
    
    $transferId = (int)$_POST['transfer_id'];
    $receivedBy = $_SESSION['user_id'] ?? 1;
    
    // Start transaction
    $this->db->begin_transaction();
    
    try {
        // Get transfer details
        $transferQuery = "SELECT * FROM stock_transfers WHERE id = $transferId";
        $transferResult = $this->db->query($transferQuery);
        $transfer = $transferResult ? $transferResult->fetch_assoc() : null;
        
        if(!$transfer) {
            throw new Exception("Transfer not found");
        }
        
        // Get transfer items
        $itemsQuery = "SELECT * FROM stock_transfer_items WHERE transfer_id = $transferId";
        $itemsResult = $this->db->query($itemsQuery);
        
        while($item = $itemsResult->fetch_assoc()) {
            // Get original stock
            $stockQuery = "SELECT * FROM inventory_stock WHERE id = {$item['stock_id']}";
            $stockResult = $this->db->query($stockQuery);
            $stock = $stockResult ? $stockResult->fetch_assoc() : null;
            
            if($stock) {
                // Reduce quantity from source store
                $newQuantity = $stock['quantity'] - $item['quantity'];
                $updateQuery = "UPDATE inventory_stock SET quantity = $newQuantity WHERE id = {$item['stock_id']}";
                $this->db->query($updateQuery);
                
                // Add stock to destination store
                $insertQuery = "INSERT INTO inventory_stock (item_id, batch_number, expiry_date, quantity, unit_cost, selling_price, location) 
                                VALUES ({$item['item_id']}, '{$stock['batch_number']}', '{$stock['expiry_date']}', {$item['quantity']}, {$stock['unit_cost']}, {$stock['selling_price']}, '{$transfer['to_store_id']}')";
                $this->db->query($insertQuery);
            }
        }
        
        // Update transfer status
        $updateTransfer = "UPDATE stock_transfers SET status = 'received', received_by = $receivedBy, updated_at = NOW() WHERE id = $transferId";
        $this->db->query($updateTransfer);
        
        $this->db->commit();
        echo json_encode(['success' => true, 'message' => 'Transfer completed successfully']);
        
    } catch(Exception $e) {
        $this->db->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==================== VIEW TRANSFER DETAILS ====================
public function viewTransfer($id) {
    $this->checkAuth();
    
    $id = (int)$id;
    
    // Get transfer details
    $query = "SELECT st.*, 
                     fs.store_name as from_store_name,
                     ts.store_name as to_store_name,
                     u1.first_name as requested_by_name,
                     u2.first_name as approved_by_name,
                     u3.first_name as received_by_name
              FROM stock_transfers st
              LEFT JOIN stores fs ON st.from_store_id = fs.id
              LEFT JOIN stores ts ON st.to_store_id = ts.id
              LEFT JOIN users u1 ON st.requested_by = u1.id
              LEFT JOIN users u2 ON st.approved_by = u2.id
              LEFT JOIN users u3 ON st.received_by = u3.id
              WHERE st.id = $id";
    
    $result = $this->db->query($query);
    $transfer = $result ? $result->fetch_assoc() : null;
    
    if(!$transfer) {
        $_SESSION['error'] = "Transfer not found";
        $this->redirect('/inventory/stock-transfers');
        return;
    }
    
    // Get transfer items
    $itemsQuery = "SELECT ti.*, i.item_name, i.item_code, i.unit_of_measure
                   FROM stock_transfer_items ti
                   JOIN inventory_items i ON ti.item_id = i.id
                   WHERE ti.transfer_id = $id";
    
    $itemsResult = $this->db->query($itemsQuery);
    $transferItems = [];
    if($itemsResult) {
        while($row = $itemsResult->fetch_assoc()) {
            $transferItems[] = $row;
        }
    }
    
    $content = $this->renderView('inventory/view-transfer', [
        'transfer' => $transfer,
        'transferItems' => $transferItems
    ]);
    $this->renderLayout('Transfer Details', $content);
}

// Helper method to generate transfer number
private function generateTransferNumber() {
    $number = 'TRF' . date('Ymd') . rand(100, 999);
    $checkQuery = "SELECT id FROM stock_transfers WHERE transfer_number = '$number'";
    $checkResult = $this->db->query($checkQuery);
    if($checkResult && $checkResult->num_rows > 0) {
        return $this->generateTransferNumber();
    }
    return $number;
}
// ==================== PURCHASE ORDERS ====================
public function purchaseOrders() {
    $this->checkAuth();
    
    // Check if coming from reorder alert
    $prefillData = null;
    if (isset($_GET['action']) && $_GET['action'] == 'create_from_alert') {
        $prefillData = [
            'action' => 'create_from_alert',
            'source' => isset($_GET['source']) ? $_GET['source'] : 'reorder_alert',
            'item_type' => isset($_GET['item_type']) ? $_GET['item_type'] : '',
            'item_id' => isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0,
            'item_name' => isset($_GET['item_name']) ? urldecode($_GET['item_name']) : '',
            'item_code' => isset($_GET['item_code']) ? urldecode($_GET['item_code']) : '',
            'quantity' => isset($_GET['quantity']) ? (int)$_GET['quantity'] : 0,
            'unit_price' => isset($_GET['unit_price']) ? (float)$_GET['unit_price'] : 0,
            'supplier_id' => isset($_GET['supplier_id']) ? (int)$_GET['supplier_id'] : 0,
            'expected_date' => isset($_GET['expected_date']) ? $_GET['expected_date'] : date('Y-m-d', strtotime('+7 days')),
            'notes' => isset($_GET['notes']) ? urldecode($_GET['notes']) : ''
        ];
    }
    
    // Get all purchase orders with supplier and store names
    $ordersQuery = "SELECT po.*, 
                           s.company_name as supplier_name,
                           st.store_name as store_name
                    FROM purchase_orders po
                    LEFT JOIN suppliers s ON po.supplier_id = s.id
                    LEFT JOIN stores st ON po.store_id = st.id
                    ORDER BY po.created_at DESC";
    
    $ordersResult = $this->db->query($ordersQuery);
    $orders = [];
    if ($ordersResult) {
        while ($row = $ordersResult->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    
    // Get all suppliers for dropdown (main suppliers table)
    $suppliersQuery = "SELECT id, company_name, contact_person FROM suppliers WHERE status = 'active' ORDER BY company_name";
    $suppliersResult = $this->db->query($suppliersQuery);
    $suppliers = [];
    if ($suppliersResult) {
        while ($row = $suppliersResult->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    
    // Get all stores for dropdown
    $storesQuery = "SELECT id, store_name FROM stores WHERE status = 'active' ORDER BY store_name";
    $storesResult = $this->db->query($storesQuery);
    $stores = [];
    if ($storesResult) {
        while ($row = $storesResult->fetch_assoc()) {
            $stores[] = $row;
        }
    }
    
    // Get all inventory items for dropdown (legacy)
    $itemsQuery = "SELECT id, item_name, item_code, unit_of_measure FROM inventory_items WHERE status = 'active' ORDER BY item_name";
    $itemsResult = $this->db->query($itemsQuery);
    $items = [];
    if ($itemsResult) {
        while ($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    // Get all medicine items
    $medicineItemsQuery = "SELECT id, medicine_name as item_name, medicine_code as item_code, selling_price, 'medicine' as source_type FROM medicines WHERE status = 'active' ORDER BY medicine_name";
    $medicineItemsResult = $this->db->query($medicineItemsQuery);
    $medicineItems = [];
    if ($medicineItemsResult) {
        while ($row = $medicineItemsResult->fetch_assoc()) {
            $medicineItems[] = $row;
        }
    }
    
    // Get all lab test items
    $labTestItemsQuery = "SELECT id, test_name as item_name, test_code as item_code, price as selling_price, 'lab_test' as source_type FROM lab_tests WHERE status = 'active' ORDER BY test_name";
    $labTestItemsResult = $this->db->query($labTestItemsQuery);
    $labTestItems = [];
    if ($labTestItemsResult) {
        while ($row = $labTestItemsResult->fetch_assoc()) {
            $labTestItems[] = $row;
        }
    }
    
    // Get all inventory items (for purchase orders)
    $inventoryItemsQuery = "SELECT id, item_name, item_code, selling_price, item_type, 'inventory' as source_type FROM inventory_items WHERE status = 'active' ORDER BY item_name";
    $inventoryItemsResult = $this->db->query($inventoryItemsQuery);
    $inventoryItems = [];
    if ($inventoryItemsResult) {
        while ($row = $inventoryItemsResult->fetch_assoc()) {
            $inventoryItems[] = $row;
        }
    }
    
    // Get suppliers by type
    $medicineSuppliers = $this->getMedicineSuppliers();
    $labTestSuppliers = $this->getLabSuppliers();
    $inventorySuppliers = $this->getInventorySuppliers();
    
    $content = $this->renderView('inventory/purchase-orders', [
        'orders' => $orders,
        'suppliers' => $suppliers,
        'stores' => $stores,
        'items' => $items,
        'prefillData' => $prefillData,
        'medicineItems' => $medicineItems,
        'labTestItems' => $labTestItems,
        'inventoryItems' => $inventoryItems,
        'medicineSuppliers' => $medicineSuppliers,
        'labTestSuppliers' => $labTestSuppliers,
        'inventorySuppliers' => $inventorySuppliers
    ]);
    $this->renderLayout('Purchase Orders', $content);
}

// ==================== CREATE PURCHASE ORDER ====================
public function createPurchaseOrder() {
    header('Content-Type: application/json');
    
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if ($input && is_array($input)) {
        $_POST = $input;
    }
    
    // Debug - log the items received
    error_log("Create PO - Items received: " . print_r(isset($_POST['items']) ? $_POST['items'] : 'No items', true));
    error_log("=== CREATE PURCHASE ORDER CALLED ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("RAW input: " . file_get_contents('php://input'));
    
    // Try to get JSON input if POST is empty
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && is_array($input)) {
        $_POST = array_merge($_POST, $input);
    }
    
    try {
        $supplierId = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        $storeId = isset($_POST['store_id']) ? (int)$_POST['store_id'] : 0;
        $orderDate = isset($_POST['order_date']) ? $this->db->real_escape_string($_POST['order_date']) : date('Y-m-d');
        $expectedDelivery = isset($_POST['expected_delivery']) && !empty($_POST['expected_delivery']) ? $this->db->real_escape_string($_POST['expected_delivery']) : null;
        $notes = isset($_POST['notes']) ? $this->db->real_escape_string($_POST['notes']) : '';
        $source = isset($_POST['source']) ? $this->db->real_escape_string($_POST['source']) : 'manual';
        
        $createdBy = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
        
        // Validate
        if ($supplierId == 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a supplier']);
            exit;
        }
        if ($storeId == 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a store']);
            exit;
        }
        
        // Get items from POST
        $items = [];
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (isset($item['item_id']) && $item['item_id'] > 0 && 
                    isset($item['quantity']) && $item['quantity'] > 0 && 
                    isset($item['unit_price']) && $item['unit_price'] > 0) {
                    $items[] = [
                        'item_id' => (int)$item['item_id'],
                        'quantity' => (int)$item['quantity'],
                        'unit_price' => (float)$item['unit_price']
                    ];
                }
            }
        }
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Please add at least one item']);
            exit;
        }
        
        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['quantity'] * $item['unit_price'];
        }
        $taxAmount = $subtotal * 0.05;
        $totalAmount = $subtotal + $taxAmount;
        
        $status = ($source == 'reorder_alert') ? 'pending_approval' : 'ordered';
        $poNumber = 'PO' . date('Ymd') . rand(100, 999);
        
        // Check if PO number is unique
        $checkSql = "SELECT id FROM purchase_orders WHERE po_number = '$poNumber'";
        $checkResult = $this->db->query($checkSql);
        if ($checkResult && $checkResult->num_rows > 0) {
            $poNumber = 'PO' . date('Ymd') . rand(1000, 9999);
        }
        
        // Start transaction
        $this->db->begin_transaction();
        
        // Insert purchase order
        $sql = "INSERT INTO purchase_orders (po_number, supplier_id, store_id, order_date, expected_delivery_date, 
                subtotal, tax_amount, total_amount, status, source, created_by, notes, created_at) 
                VALUES ('$poNumber', $supplierId, $storeId, '$orderDate', " . ($expectedDelivery ? "'$expectedDelivery'" : "NULL") . ",
                $subtotal, $taxAmount, $totalAmount, '$status', '$source', $createdBy, '$notes', NOW())";
        
        error_log("SQL: " . $sql);
        
        if (!$this->db->query($sql)) {
            $this->db->rollback();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $this->db->error]);
            exit;
        }
        
        $poId = $this->db->insert_id;
        
        // Insert items
        // Inside createPurchaseOrder(), when inserting items:
        foreach ($items as $item) {
            $totalPrice = $item['quantity'] * $item['unit_price'];
            $sourceType = isset($item['source_type']) ? $this->db->real_escape_string($item['source_type']) : 'inventory';
            
            $itemSql = "INSERT INTO purchase_order_items (po_id, item_id, quantity, unit_price, total_price, source_type) 
                        VALUES ($poId, {$item['item_id']}, {$item['quantity']}, {$item['unit_price']}, $totalPrice, '$sourceType')";
            
            if (!$this->db->query($itemSql)) {
                $this->db->rollback();
                echo json_encode(['success' => false, 'message' => 'Error inserting item: ' . $this->db->error]);
                exit;
            }
        }
        
        $this->db->commit();
        echo json_encode(['success' => true, 'message' => 'Purchase Order created successfully', 'po_number' => $poNumber]);
        
    } catch (Exception $e) {
        if (isset($this->db) && $this->db->connect_errno == 0) {
            $this->db->rollback();
        }
        error_log("Exception: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// ==================== UPDATE PURCHASE ORDER (for editing pending approval) ====================
public function updatePurchaseOrder() {
    header('Content-Type: application/json');
    
    $poId = (int)$_POST['po_id'];
    $supplierId = (int)$_POST['supplier_id'];
    $storeId = (int)$_POST['store_id'];
    $orderDate = $this->sanitize($_POST['order_date']);
    $expectedDelivery = !empty($_POST['expected_delivery']) ? $this->sanitize($_POST['expected_delivery']) : null;
    $notes = $this->sanitize($_POST['notes'] ?? '');
    
    // Check if PO is in pending_approval status
    $checkQuery = "SELECT status FROM purchase_orders WHERE id = $poId";
    $checkResult = $this->db->query($checkQuery);
    $po = $checkResult->fetch_assoc();
    
    if($po['status'] != 'pending_approval') {
        echo json_encode(['success' => false, 'message' => 'Only pending approval orders can be edited']);
        exit;
    }
    
    $items = $_POST['items'] ?? [];
    $subtotal = 0;
    foreach($items as $item) {
        $quantity = (int)$item['quantity'];
        $unitPrice = (float)$item['unit_price'];
        $subtotal += $quantity * $unitPrice;
    }
    
    $taxAmount = $subtotal * 0.05;
    $totalAmount = $subtotal + $taxAmount;
    
    $this->db->begin_transaction();
    
    try {
        $updateQuery = "UPDATE purchase_orders SET 
                        supplier_id = $supplierId,
                        store_id = $storeId,
                        order_date = '$orderDate',
                        expected_delivery_date = " . ($expectedDelivery ? "'$expectedDelivery'" : "NULL") . ",
                        subtotal = $subtotal,
                        tax_amount = $taxAmount,
                        total_amount = $totalAmount,
                        notes = '$notes'
                        WHERE id = $poId";
        
        if(!$this->db->query($updateQuery)) {
            throw new Exception($this->db->error);
        }
        
        // Delete existing items
        $this->db->query("DELETE FROM purchase_order_items WHERE po_id = $poId");
        
        // Insert updated items
        foreach($items as $item) {
            $itemId = (int)$item['item_id'];
            $quantity = (int)$item['quantity'];
            $unitPrice = (float)$item['unit_price'];
            $totalPrice = $quantity * $unitPrice;
            
            $itemQuery = "INSERT INTO purchase_order_items (po_id, item_id, quantity, unit_price, total_price) 
                          VALUES ($poId, $itemId, $quantity, $unitPrice, $totalPrice)";
            
            if(!$this->db->query($itemQuery)) {
                throw new Exception($this->db->error);
            }
        }
        
        $this->db->commit();
        echo json_encode(['success' => true, 'message' => 'Purchase Order updated successfully']);
        
    } catch(Exception $e) {
        $this->db->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ==================== APPROVE PURCHASE ORDER ====================
public function approvePurchaseOrder() {
    header('Content-Type: application/json');
    
    $poId = (int)$_POST['po_id'];
    $approvedBy = $_SESSION['user_id'] ?? 1;
    
    $query = "UPDATE purchase_orders SET status = 'ordered', approved_by = $approvedBy, approved_at = NOW() WHERE id = $poId AND status = 'pending_approval'";
    
    if($this->db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Purchase Order approved successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $this->db->error]);
    }
    exit;
}

// ==================== CANCEL PURCHASE ORDER ====================
public function cancelPurchaseOrder() {
    header('Content-Type: application/json');
    
    $poId = (int)$_POST['po_id'];
    
    $query = "UPDATE purchase_orders SET status = 'cancelled' WHERE id = $poId AND status = 'pending_approval'";
    
    if($this->db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Purchase Order cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $this->db->error]);
    }
    exit;
}

// ==================== GET PO DETAILS FOR EDITING ====================
public function getPurchaseOrderDetails($id) {
    header('Content-Type: application/json');
    
    $id = (int)$id;
    
    $poQuery = "SELECT * FROM purchase_orders WHERE id = $id";
    $poResult = $this->db->query($poQuery);
    $po = $poResult ? $poResult->fetch_assoc() : null;
    
    if(!$po) {
        echo json_encode(['success' => false, 'message' => 'Purchase Order not found']);
        exit;
    }
    
    $itemsQuery = "SELECT * FROM purchase_order_items WHERE po_id = $id";
    $itemsResult = $this->db->query($itemsQuery);
    $items = [];
    if($itemsResult) {
        while($row = $itemsResult->fetch_assoc()) {
            $items[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'po' => $po, 'items' => $items]);
    exit;
}

// ==================== VIEW PURCHASE ORDER ====================
public function viewPurchaseOrder($id) {
    $this->checkAuth();
    
    $id = (int)$id;
    
    // Get PO details
    $query = "SELECT po.*, 
                     s.company_name as supplier_name,
                     s.phone as supplier_phone,
                     s.email as supplier_email,
                     s.address as supplier_address,
                     st.store_name as store_name,
                     u.first_name as created_by_name
              FROM purchase_orders po
              LEFT JOIN suppliers s ON po.supplier_id = s.id
              LEFT JOIN stores st ON po.store_id = st.id
              LEFT JOIN users u ON po.created_by = u.id
              WHERE po.id = $id";
    
    $result = $this->db->query($query);
    $order = $result ? $result->fetch_assoc() : null;
    
    if(!$order) {
        $_SESSION['error'] = "Purchase Order not found";
        $this->redirect('/inventory/purchase-orders');
        return;
    }
    
    // Get PO items
    $itemsQuery = "SELECT poi.*, i.item_name, i.item_code, i.unit_of_measure
                   FROM purchase_order_items poi
                   JOIN inventory_items i ON poi.item_id = i.id
                   WHERE poi.po_id = $id";
    
    $itemsResult = $this->db->query($itemsQuery);
    $poItems = [];
    if($itemsResult) {
        while($row = $itemsResult->fetch_assoc()) {
            $poItems[] = $row;
        }
    }
    
    $content = $this->renderView('inventory/view-po', [
        'order' => $order,
        'poItems' => $poItems
    ]);
    $this->renderLayout('Purchase Order Details', $content);
}

// ==================== RECEIVE PURCHASE ORDER ====================
public function receivePurchaseOrder() {
    header('Content-Type: application/json');
    
    $poId = (int)$_POST['po_id'];
    $receivedBy = $_SESSION['user_id'] ?? 1;
    $receivedDate = date('Y-m-d');
    
    // Start transaction
    $this->db->begin_transaction();
    
    try {
        // Get PO details
        $poQuery = "SELECT * FROM purchase_orders WHERE id = $poId";
        $poResult = $this->db->query($poQuery);
        $po = $poResult ? $poResult->fetch_assoc() : null;
        
        if(!$po) {
            throw new Exception("Purchase Order not found");
        }
        
        if($po['status'] != 'ordered') {
            throw new Exception("Purchase Order cannot be received. Current status: " . $po['status']);
        }
        
        // Get PO items
        $itemsQuery = "SELECT * FROM purchase_order_items WHERE po_id = $poId";
        $itemsResult = $this->db->query($itemsQuery);
        
        while($item = $itemsResult->fetch_assoc()) {
            // Generate batch number
            $batchNumber = 'PO' . $po['po_number'] . '-BATCH' . date('Ymd') . rand(100, 999);
            $expiryDate = date('Y-m-d', strtotime('+2 years')); // Default 2 years expiry
            
            // Add stock to inventory
            $stockQuery = "INSERT INTO inventory_stock (item_id, batch_number, expiry_date, quantity, unit_cost, selling_price, location) 
                          VALUES ({$item['item_id']}, '$batchNumber', '$expiryDate', {$item['quantity']}, {$item['unit_price']}, {$item['unit_price']}, '{$po['store_id']}')";
            
            if(!$this->db->query($stockQuery)) {
                throw new Exception($this->db->error);
            }
            
            // Update received quantity
            $updateQuery = "UPDATE purchase_order_items SET received_quantity = {$item['quantity']} WHERE id = {$item['id']}";
            $this->db->query($updateQuery);
        }
        
        // Update PO status
        $updatePo = "UPDATE purchase_orders SET status = 'received', delivery_date = '$receivedDate' WHERE id = $poId";
        $this->db->query($updatePo);
        
        $this->db->commit();
        echo json_encode(['success' => true, 'message' => 'Purchase Order received and stock updated successfully']);
        
    } catch(Exception $e) {
        $this->db->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Helper method to generate PO number
private function generatePONumber() {
    $number = 'PO' . date('Ymd') . rand(100, 999);
    $checkQuery = "SELECT id FROM purchase_orders WHERE po_number = '$number'";
    $checkResult = $this->db->query($checkQuery);
    if($checkResult && $checkResult->num_rows > 0) {
        return $this->generatePONumber();
    }
    return $number;
}

// ==================== SUPPLIER MANAGEMENT ====================

// Get all suppliers (for dropdown and listing)
private function getAllSuppliers() {
    $suppliers = [];
    $result = $this->db->query("SELECT id, supplier_code, company_name, contact_person, email, phone, address, city, payment_terms, tax_id, status 
                               FROM suppliers WHERE status = 'active' ORDER BY company_name");
    if($result) {
        while($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    return $suppliers;
}

// Add new supplier
public function addSupplier() {
    header('Content-Type: application/json');
    
    $supplierCode = $this->generateSupplierCode();
    $companyName = $this->sanitize($_POST['company_name']);
    $contactPerson = $this->sanitize($_POST['contact_person'] ?? '');
    $email = $this->sanitize($_POST['email'] ?? '');
    $phone = $this->sanitize($_POST['phone']);
    $address = $this->sanitize($_POST['address'] ?? '');
    $city = $this->sanitize($_POST['city'] ?? '');
    $paymentTerms = $this->sanitize($_POST['payment_terms'] ?? '');
    $taxId = $this->sanitize($_POST['tax_id'] ?? '');
    
    $query = "INSERT INTO suppliers (supplier_code, company_name, contact_person, email, phone, address, city, payment_terms, tax_id, status) 
              VALUES ('$supplierCode', '$companyName', '$contactPerson', '$email', '$phone', '$address', '$city', '$paymentTerms', '$taxId', 'active')";
    
    if($this->db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Supplier added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $this->db->error]);
    }
    exit;
}

// Get supplier by ID (AJAX)
public function getSupplier($id) {
    header('Content-Type: application/json');
    
    $id = (int)$id;
    $query = "SELECT * FROM suppliers WHERE id = $id";
    $result = $this->db->query($query);
    $supplier = $result ? $result->fetch_assoc() : null;
    
    if($supplier) {
        echo json_encode(['success' => true, 'supplier' => $supplier]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Supplier not found']);
    }
    exit;
}

// Update supplier
public function updateSupplier() {
    header('Content-Type: application/json');
    
    $id = (int)$_POST['id'];
    $companyName = $this->sanitize($_POST['company_name']);
    $contactPerson = $this->sanitize($_POST['contact_person'] ?? '');
    $email = $this->sanitize($_POST['email'] ?? '');
    $phone = $this->sanitize($_POST['phone']);
    $address = $this->sanitize($_POST['address'] ?? '');
    $city = $this->sanitize($_POST['city'] ?? '');
    $paymentTerms = $this->sanitize($_POST['payment_terms'] ?? '');
    $taxId = $this->sanitize($_POST['tax_id'] ?? '');
    $status = $this->sanitize($_POST['status'] ?? 'active');
    
    $query = "UPDATE suppliers SET 
                company_name = '$companyName',
                contact_person = '$contactPerson',
                email = '$email',
                phone = '$phone',
                address = '$address',
                city = '$city',
                payment_terms = '$paymentTerms',
                tax_id = '$taxId',
                status = '$status'
              WHERE id = $id";
    
    if($this->db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Supplier updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $this->db->error]);
    }
    exit;
}

// Delete supplier
public function deleteSupplier() {
    header('Content-Type: application/json');
    
    $id = (int)$_POST['supplier_id'];
    
    // Soft delete - set status to inactive
    $query = "UPDATE suppliers SET status = 'inactive' WHERE id = $id";
    
    if($this->db->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Supplier deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $this->db->error]);
    }
    exit;
}

// Generate supplier code
private function generateSupplierCode() {
    $code = 'SUP' . date('Ymd') . rand(100, 999);
    $checkQuery = "SELECT id FROM suppliers WHERE supplier_code = '$code'";
    $checkResult = $this->db->query($checkQuery);
    if($checkResult && $checkResult->num_rows > 0) {
        return $this->generateSupplierCode();
    }
    return $code;
}

// ==================== SUPPLIERS MANAGEMENT ====================
public function suppliers() {
    $this->checkAuth();
    
    // Get all suppliers from the main suppliers table
    $query = "SELECT * FROM suppliers ORDER BY company_name ASC";
    $result = $this->db->query($query);
    $suppliers = [];
    if($result) {
        while($row = $result->fetch_assoc()) {
            $suppliers[] = $row;
        }
    }
    
    $content = $this->renderView('inventory/suppliers', [
        'suppliers' => $suppliers
    ]);
    $this->renderLayout('Supplier Management', $content);
}


// ==================== INVENTORY REPORTS ====================
public function reports() {
    $this->checkAuth();
    
    // Get stock valuation
    $valuation = $this->getStockValuation();
    
    // Get expiry summary
    $expirySummary = $this->getExpirySummary();
    
    $content = $this->renderView('inventory/reports', [
        'valuation' => $valuation,
        'expirySummary' => $expirySummary
    ]);
    $this->renderLayout('Inventory Reports', $content);
}

// Get stock valuation report
private function getStockValuation() {
    $valuation = [];
    
    // Get medicine stock valuation
    $medQuery = "SELECT 
                    m.id,
                    m.medicine_name as item_name,
                    m.medicine_code as item_code,
                    'medicine' as item_type,
                    COALESCE(SUM(ms.quantity), 0) as quantity,
                    COALESCE(SUM(ms.quantity * ms.purchase_price), 0) as total_value
                 FROM medicines m
                 LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                 WHERE m.status = 'active'
                 GROUP BY m.id
                 HAVING quantity > 0
                 ORDER BY total_value DESC
                 LIMIT 50";
    
    $medResult = $this->db->query($medQuery);
    if($medResult) {
        while($row = $medResult->fetch_assoc()) {
            $valuation[] = $row;
        }
    }
    
    // Get inventory items valuation
    $invQuery = "SELECT 
                    i.id,
                    i.item_name,
                    i.item_code,
                    i.item_type,
                    COALESCE(SUM(s.quantity), 0) as quantity,
                    COALESCE(SUM(s.quantity * s.unit_cost), 0) as total_value
                 FROM inventory_items i
                 LEFT JOIN inventory_stock s ON i.id = s.item_id AND s.expiry_date > CURDATE()
                 WHERE i.status = 'active'
                 GROUP BY i.id
                 HAVING quantity > 0
                 ORDER BY total_value DESC
                 LIMIT 50";
    
    $invResult = $this->db->query($invQuery);
    if($invResult) {
        while($row = $invResult->fetch_assoc()) {
            $valuation[] = $row;
        }
    }
    
    // Sort by total value
    usort($valuation, function($a, $b) {
        return $b['total_value'] - $a['total_value'];
    });
    
    return array_slice($valuation, 0, 50);
}

// Get expiry summary
private function getExpirySummary() {
    $summary = [
        'good_stock' => 0,
        'expiring_soon' => 0,
        'expired' => 0
    ];
    
    // Medicine stock expiry
    $medQuery = "SELECT 
                    CASE 
                        WHEN expiry_date < CURDATE() THEN 'expired'
                        WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                        ELSE 'good_stock'
                    END as status,
                    COUNT(*) as count,
                    SUM(quantity) as total_quantity
                 FROM medicine_stock
                 GROUP BY status";
    
    $medResult = $this->db->query($medQuery);
    if($medResult) {
        while($row = $medResult->fetch_assoc()) {
            if(isset($summary[$row['status']])) {
                $summary[$row['status']] += $row['total_quantity'];
            }
        }
    }
    
    // Inventory stock expiry
    $invQuery = "SELECT 
                    CASE 
                        WHEN expiry_date < CURDATE() THEN 'expired'
                        WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'expiring_soon'
                        ELSE 'good_stock'
                    END as status,
                    COUNT(*) as count,
                    SUM(quantity) as total_quantity
                 FROM inventory_stock
                 GROUP BY status";
    
    $invResult = $this->db->query($invQuery);
    if($invResult) {
        while($row = $invResult->fetch_assoc()) {
            if(isset($summary[$row['status']])) {
                $summary[$row['status']] += $row['total_quantity'];
            }
        }
    }
    
    return $summary;
}

// Export report as CSV/Excel
public function exportReport() {
    $type = isset($_GET['type']) ? $this->sanitize($_GET['type']) : 'stock';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="inventory_report_' . $type . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    if($type == 'stock') {
        // Stock valuation report
        fputcsv($output, ['Item Name', 'Item Code', 'Item Type', 'Quantity', 'Total Value (৳)']);
        
        $valuation = $this->getStockValuation();
        foreach($valuation as $item) {
            fputcsv($output, [
                $item['item_name'],
                $item['item_code'],
                $item['item_type'],
                $item['quantity'],
                number_format($item['total_value'], 2)
            ]);
        }
        
        // Add total row
        $total = array_sum(array_column($valuation, 'total_value'));
        fputcsv($output, []);
        fputcsv($output, ['TOTAL STOCK VALUE', '', '', '', number_format($total, 2)]);
        
    } elseif($type == 'expiry') {
        // Expiry report
        fputcsv($output, ['Item Name', 'Item Code', 'Batch Number', 'Expiry Date', 'Quantity', 'Status']);
        
        // Get medicine expiry details
        $medQuery = "SELECT 
                        m.medicine_name as item_name,
                        m.medicine_code as item_code,
                        ms.batch_number,
                        ms.expiry_date,
                        ms.quantity,
                        CASE 
                            WHEN ms.expiry_date < CURDATE() THEN 'Expired'
                            WHEN ms.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
                            ELSE 'Good'
                        END as status
                     FROM medicine_stock ms
                     JOIN medicines m ON ms.medicine_id = m.id
                     ORDER BY ms.expiry_date ASC";
        
        $medResult = $this->db->query($medQuery);
        if($medResult) {
            while($row = $medResult->fetch_assoc()) {
                fputcsv($output, [
                    $row['item_name'],
                    $row['item_code'],
                    $row['batch_number'],
                    $row['expiry_date'],
                    $row['quantity'],
                    $row['status']
                ]);
            }
        }
        
        // Get inventory expiry details
        $invQuery = "SELECT 
                        i.item_name,
                        i.item_code,
                        s.batch_number,
                        s.expiry_date,
                        s.quantity,
                        CASE 
                            WHEN s.expiry_date < CURDATE() THEN 'Expired'
                            WHEN s.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
                            ELSE 'Good'
                        END as status
                     FROM inventory_stock s
                     JOIN inventory_items i ON s.item_id = i.id
                     ORDER BY s.expiry_date ASC";
        
        $invResult = $this->db->query($invQuery);
        if($invResult) {
            while($row = $invResult->fetch_assoc()) {
                fputcsv($output, [
                    $row['item_name'],
                    $row['item_code'],
                    $row['batch_number'],
                    $row['expiry_date'],
                    $row['quantity'],
                    $row['status']
                ]);
            }
        }
        
    } elseif($type == 'valuation') {
        // Detailed valuation report
        fputcsv($output, ['Item Name', 'Item Code', 'Item Type', 'Purchase Price', 'Selling Price', 'Quantity', 'Total Value (৳)']);
        
        // Medicine valuation details
        $medQuery = "SELECT 
                        m.medicine_name as item_name,
                        m.medicine_code as item_code,
                        'medicine' as item_type,
                        m.purchase_price,
                        m.selling_price,
                        COALESCE(SUM(ms.quantity), 0) as quantity,
                        COALESCE(SUM(ms.quantity * ms.purchase_price), 0) as total_value
                     FROM medicines m
                     LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                     WHERE m.status = 'active'
                     GROUP BY m.id
                     HAVING quantity > 0";
        
        $medResult = $this->db->query($medQuery);
        if($medResult) {
            while($row = $medResult->fetch_assoc()) {
                fputcsv($output, [
                    $row['item_name'],
                    $row['item_code'],
                    $row['item_type'],
                    number_format($row['purchase_price'], 2),
                    number_format($row['selling_price'], 2),
                    $row['quantity'],
                    number_format($row['total_value'], 2)
                ]);
            }
        }
        
        // Inventory valuation details
        $invQuery = "SELECT 
                        i.item_name,
                        i.item_code,
                        i.item_type,
                        i.purchase_price,
                        i.selling_price,
                        COALESCE(SUM(s.quantity), 0) as quantity,
                        COALESCE(SUM(s.quantity * s.unit_cost), 0) as total_value
                     FROM inventory_items i
                     LEFT JOIN inventory_stock s ON i.id = s.item_id AND s.expiry_date > CURDATE()
                     WHERE i.status = 'active'
                     GROUP BY i.id
                     HAVING quantity > 0";
        
        $invResult = $this->db->query($invQuery);
        if($invResult) {
            while($row = $invResult->fetch_assoc()) {
                fputcsv($output, [
                    $row['item_name'],
                    $row['item_code'],
                    $row['item_type'],
                    number_format($row['purchase_price'], 2),
                    number_format($row['selling_price'], 2),
                    $row['quantity'],
                    number_format($row['total_value'], 2)
                ]);
            }
        }
    }
    
    fclose($output);
    exit;
}

// Get stock movement report (optional)
public function stockMovementReport() {
    $this->checkAuth();
    
    $startDate = isset($_GET['start_date']) ? $this->sanitize($_GET['start_date']) : date('Y-m-01');
    $endDate = isset($_GET['end_date']) ? $this->sanitize($_GET['end_date']) : date('Y-m-t');
    
    $movements = [];
    
    // Get stock in movements (purchase orders received)
    $poQuery = "SELECT 
                    'Purchase Order' as movement_type,
                    po.received_date as movement_date,
                    poi.item_id,
                    i.item_name,
                    i.item_code,
                    poi.quantity,
                    poi.unit_price
                 FROM purchase_order_items poi
                 JOIN purchase_orders po ON poi.po_id = po.id
                 JOIN inventory_items i ON poi.item_id = i.id
                 WHERE po.status = 'received'
                 AND po.received_date BETWEEN '$startDate' AND '$endDate'
                 ORDER BY po.received_date DESC";
    
    $poResult = $this->db->query($poQuery);
    if($poResult) {
        while($row = $poResult->fetch_assoc()) {
            $movements[] = $row;
        }
    }
    
    // Get stock out movements (sales)
    $saleQuery = "SELECT 
                    'Sale' as movement_type,
                    ps.created_at as movement_date,
                    psi.item_id,
                    i.item_name,
                    i.item_code,
                    psi.quantity,
                    psi.unit_price
                 FROM pharmacy_sale_items psi
                 JOIN pharmacy_sales ps ON psi.sale_id = ps.id
                 JOIN inventory_items i ON psi.item_id = i.id
                 WHERE DATE(ps.created_at) BETWEEN '$startDate' AND '$endDate'
                 ORDER BY ps.created_at DESC
                 LIMIT 100";
    
    $saleResult = $this->db->query($saleQuery);
    if($saleResult) {
        while($row = $saleResult->fetch_assoc()) {
            $movements[] = $row;
        }
    }
    
    // Sort by date
    usort($movements, function($a, $b) {
        return strtotime($b['movement_date']) - strtotime($a['movement_date']);
    });
    
    $content = $this->renderView('inventory/stock-movement-report', [
        'movements' => $movements,
        'startDate' => $startDate,
        'endDate' => $endDate
    ]);
    $this->renderLayout('Stock Movement Report', $content);
}
// ==================== GET SUPPLIERS BY ITEM TYPE ====================
public function getSuppliersByType() {
    header('Content-Type: application/json');
    
    $itemType = isset($_GET['item_type']) ? $this->sanitize($_GET['item_type']) : '';
    $suppliers = [];
    
    try {
        if ($itemType == 'medicine') {
            $query = "SELECT id, supplier_code, company_name, contact_person, email, phone 
                      FROM medicine_suppliers WHERE status = 'active' ORDER BY company_name";
        } elseif ($itemType == 'lab_test') {
            $query = "SELECT id, supplier_code, company_name, contact_person, email, phone 
                      FROM lab_test_suppliers WHERE status = 'active' ORDER BY company_name";
        } else {
            $query = "SELECT id, supplier_code, company_name, contact_person, email, phone 
                      FROM inventory_suppliers WHERE status = 'active' ORDER BY company_name";
        }
        
        $result = $this->db->query($query);
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $suppliers[] = $row;
            }
        }
        
        echo json_encode(['success' => true, 'suppliers' => $suppliers]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'suppliers' => []]);
    }
    exit;
}
// ==================== GET ITEM DETAILS FOR REORDER ALERT ====================
public function getItemDetails() {
    header('Content-Type: application/json');
    
    $itemType = isset($_GET['item_type']) ? $this->sanitize($_GET['item_type']) : '';
    $itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
    
    if (!$itemType || !$itemId) {
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
        exit;
    }
    
    $item = null;
    
    try {
        if ($itemType == 'medicine') {
            $query = "SELECT 
                        m.id,
                        m.medicine_name as item_name,
                        m.medicine_code as item_code,
                        m.unit_of_measure,
                        m.manufacturer,
                        m.strength,
                        m.selling_price,
                        m.purchase_price,
                        COALESCE(SUM(ms.quantity), 0) as current_stock,
                        s.company_name as supplier_name,
                        s.id as supplier_id
                      FROM medicines m
                      LEFT JOIN medicine_stock ms ON m.id = ms.medicine_id AND ms.expiry_date > CURDATE()
                      LEFT JOIN medicine_suppliers s ON m.supplier_id = s.id
                      WHERE m.id = $itemId AND m.status = 'active'
                      GROUP BY m.id";
            $result = $this->db->query($query);
            if ($result && $result->num_rows > 0) {
                $item = $result->fetch_assoc();
            }
        } elseif ($itemType == 'lab_test') {
            $query = "SELECT 
                        lt.id,
                        lt.test_name as item_name,
                        lt.test_code as item_code,
                        'Kit' as unit_of_measure,
                        NULL as manufacturer,
                        lt.specimen_type as strength,
                        lt.price as selling_price,
                        lt.price as purchase_price,
                        0 as current_stock,
                        s.company_name as supplier_name,
                        s.id as supplier_id
                      FROM lab_tests lt
                      LEFT JOIN lab_test_suppliers s ON lt.supplier_id = s.id
                      WHERE lt.id = $itemId AND lt.status = 'active'";
            $result = $this->db->query($query);
            if ($result && $result->num_rows > 0) {
                $item = $result->fetch_assoc();
            }
        } else {
            // inventory items (equipment, other)
            $query = "SELECT 
                        i.id,
                        i.item_name,
                        i.item_code,
                        i.unit_of_measure,
                        i.manufacturer,
                        i.brand,
                        i.strength,
                        i.selling_price,
                        i.purchase_price,
                        COALESCE(SUM(s.quantity), 0) as current_stock,
                        sup.company_name as supplier_name,
                        sup.id as supplier_id
                      FROM inventory_items i
                      LEFT JOIN inventory_stock s ON i.id = s.item_id AND s.expiry_date > CURDATE()
                      LEFT JOIN inventory_suppliers sup ON i.supplier_id = sup.id
                      WHERE i.id = $itemId AND i.status = 'active'
                      GROUP BY i.id";
            $result = $this->db->query($query);
            if ($result && $result->num_rows > 0) {
                $item = $result->fetch_assoc();
            }
        }
        
        if ($item) {
            // Ensure numeric values are properly formatted
            $item['current_stock'] = (int)$item['current_stock'];
            $item['selling_price'] = (float)$item['selling_price'];
            $item['purchase_price'] = (float)$item['purchase_price'];
            $item['supplier_id'] = $item['supplier_id'] ?? 0;
            $item['supplier_name'] = $item['supplier_name'] ?? 'Not assigned';
            
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
}
?>