<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        
        /* Header Styles */
        .page-header { margin-bottom: 25px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; }
        
        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            position: relative;
            overflow: hidden;
            height: 100%;
        }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        .stat-card.primary::before { background: #3b82f6; }
        .stat-card.warning::before { background: #f59e0b; }
        .stat-card.danger::before { background: #ef4444; }
        .stat-card.success::before { background: #10b981; }
        .stat-number { font-size: 32px; font-weight: 800; margin-bottom: 5px; }
        .stat-label { font-size: 13px; color: #6c757d; margin-bottom: 0; }
        .stat-icon { position: absolute; bottom: 15px; right: 15px; opacity: 0.15; font-size: 48px; }
        
        /* Table Styles */
        .items-table-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }
        .table-header {
            padding: 18px 20px;
            background: #fafbfc;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .table-title { font-size: 16px; font-weight: 600; color: #1f2937; margin: 0; }
        .record-badge { background: #e5e7eb; color: #4b5563; padding: 4px 12px; border-radius: 30px; font-size: 12px; font-weight: 500; }
        
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
        }
        .inventory-table th {
            padding: 14px 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }
        .inventory-table td {
            padding: 14px 12px;
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .inventory-table tr:hover { background: #fafbfc; }
        
        /* Badge Styles */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .type-medicine { background: #d1fae5; color: #065f46; }
        .type-lab_test { background: #f3e8ff; color: #6b21a5; }
        .type-equipment { background: #fed7aa; color: #9a3412; }
        .type-other { background: #e2e8f0; color: #475569; }
        
        .stock-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .stock-high { background: #d1fae5; color: #10b981; }
        .stock-medium { background: #fef3c7; color: #d97706; }
        .stock-low { background: #fee2e2; color: #ef4444; }
        .stock-out { background: #f1f5f9; color: #64748b; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-active { background: #d1fae5; color: #10b981; }
        .status-inactive { background: #fee2e2; color: #ef4444; }
        
        /* Action Buttons */
        .action-btns { display: flex; gap: 6px; flex-wrap: wrap; }
        .action-btn {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
            background: white;
            color: #64748b;
            cursor: pointer;
            text-decoration: none;
        }
        .action-btn:hover { transform: translateY(-2px); }
        .action-btn.view:hover { border-color: #3b82f6; color: #3b82f6; background: #eff6ff; }
        .action-btn.stock-in:hover { border-color: #10b981; color: #10b981; background: #ecfdf5; }
        .action-btn.stock-out:hover { border-color: #ef4444; color: #ef4444; background: #fee2e2; }
        
        /* Search Bar */
        .search-box {
            position: relative;
            width: 300px;
        }
        .search-box input {
            padding-right: 35px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            font-size: 13px;
        }
        .search-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 64px; color: #cbd5e1; margin-bottom: 20px; }
        .empty-title { font-size: 18px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .empty-text { color: #94a3b8; font-size: 13px; margin-bottom: 20px; }
        
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .table-header { flex-direction: column; align-items: flex-start; }
            .inventory-table { font-size: 11px; }
            .inventory-table th, .inventory-table td { padding: 10px 6px; }
            .action-btns { gap: 3px; }
            .action-btn { width: 26px; height: 26px; }
            .search-box { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-boxes text-success me-2"></i>Inventory Dashboard</h1>
            <p class="page-subtitle">Manage your inventory items, stock levels, and supplies</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <a href="<?php echo BASE_URL; ?>/inventory/items/add" class="btn btn-success">
                <i class="fas fa-plus me-2"></i>Add New Item
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card primary">
                <div class="stat-number text-primary"><?php echo isset($totalItems) ? $totalItems : 0; ?></div>
                <div class="stat-label">Total Items</div>
                <i class="fas fa-cubes stat-icon"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card warning">
                <div class="stat-number text-warning"><?php echo isset($lowStockCount) ? $lowStockCount : 0; ?></div>
                <div class="stat-label">Low Stock Items</div>
                <i class="fas fa-exclamation-triangle stat-icon"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card danger">
                <div class="stat-number text-danger"><?php echo isset($expiringCount) ? $expiringCount : 0; ?></div>
                <div class="stat-label">Expiring Soon</div>
                <i class="fas fa-clock stat-icon"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card success">
                <div class="stat-number text-success">৳ <?php echo isset($totalValue) ? number_format($totalValue, 2) : '0.00'; ?></div>
                <div class="stat-label">Total Stock Value</div>
                <i class="fas fa-dollar-sign stat-icon"></i>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="items-table-container">
        <div class="table-header">
            <h6 class="table-title"><i class="fas fa-list me-2"></i>Inventory Items</h6>
            <div class="search-box">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name or code...">
                <i class="fas fa-search"></i>
            </div>
        </div>
        <div class="table-responsive">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Stock</th>
                        <th>Selling Price</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="itemsTableBody">
                    <?php if(isset($items) && !empty($items) && is_array($items)): ?>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary"><?php echo isset($item['item_code']) ? htmlspecialchars($item['item_code']) : 'N/A'; ?></span>
                             </small>
                            <td>
                                <strong><?php echo isset($item['item_name']) ? htmlspecialchars($item['item_name']) : 'N/A'; ?></strong>
                                <?php if(isset($item['generic_name']) && !empty($item['generic_name']) && $item['generic_name'] != $item['item_name']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($item['generic_name']); ?></small>
                                <?php endif; ?>
                             </small>
                            <td>
                                <?php 
                                    $categoryName = isset($item['category_name']) ? $item['category_name'] : 
                                                   (isset($item['category']) ? $item['category'] : 'Uncategorized');
                                    echo htmlspecialchars($categoryName);
                                ?>
                             </small>
                            <td>
                                <?php 
                                    $itemType = isset($item['item_type']) ? $item['item_type'] : 'other';
                                    $typeClass = '';
                                    $typeIcon = '';
                                    $typeName = '';
                                    if($itemType == 'medicine') {
                                        $typeClass = 'type-medicine';
                                        $typeIcon = '💊';
                                        $typeName = 'Medicine';
                                    } elseif($itemType == 'lab_test') {
                                        $typeClass = 'type-lab_test';
                                        $typeIcon = '🔬';
                                        $typeName = 'Lab Test';
                                    } elseif($itemType == 'equipment') {
                                        $typeClass = 'type-equipment';
                                        $typeIcon = '🔧';
                                        $typeName = 'Equipment';
                                    } else {
                                        $typeClass = 'type-other';
                                        $typeIcon = '📋';
                                        $typeName = 'Other';
                                    }
                                ?>
                                <span class="type-badge <?php echo $typeClass; ?>">
                                    <?php echo $typeIcon; ?> <?php echo $typeName; ?>
                                </span>
                             </small>
                            <td>
                                <?php 
                                    $stockLevel = isset($item['current_stock']) ? (int)$item['current_stock'] : 0;
                                    $reorderLevel = isset($item['reorder_level']) ? (int)$item['reorder_level'] : 10;
                                    $unitOfMeasure = isset($item['unit_of_measure']) ? htmlspecialchars($item['unit_of_measure']) : 'units';
                                    
                                    if($stockLevel <= 0) {
                                        $stockClass = 'stock-out';
                                        $stockText = 'Out of Stock';
                                    } elseif($stockLevel <= $reorderLevel) {
                                        $stockClass = 'stock-low';
                                        $stockText = 'Low Stock';
                                    } elseif($stockLevel <= $reorderLevel * 2) {
                                        $stockClass = 'stock-medium';
                                        $stockText = 'Medium Stock';
                                    } else {
                                        $stockClass = 'stock-high';
                                        $stockText = 'Good Stock';
                                    }
                                ?>
                                <div class="stock-badge <?php echo $stockClass; ?>">
                                    <?php echo $stockLevel; ?> <?php echo $unitOfMeasure; ?>
                                </div>
                                <small class="text-muted d-block mt-1"><?php echo $stockText; ?></small>
                             </small>
                            <td>
                                <strong class="text-success">৳ <?php echo isset($item['selling_price']) ? number_format((float)$item['selling_price'], 2) : '0.00'; ?></strong>
                             </small>
                            <td>
                                <?php 
                                    if(isset($item['expiry_date']) && !empty($item['expiry_date'])) {
                                        echo date('d M Y', strtotime($item['expiry_date']));
                                        if(strtotime($item['expiry_date']) < time()) {
                                            echo ' <span class="badge bg-danger">Expired</span>';
                                        } elseif(strtotime($item['expiry_date']) < strtotime('+30 days')) {
                                            echo ' <span class="badge bg-warning">Soon</span>';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                             </small>
                            <td>
                                <span class="status-badge <?php echo (isset($item['status']) && $item['status'] == 'active') ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo isset($item['status']) ? ucfirst($item['status']) : 'Active'; ?>
                                </span>
                             </small>
                            <td>
                                <div class="action-btns">
                                    <a href="<?php echo BASE_URL; ?>/inventory/view-item?id=<?php echo $item['id']; ?>&type=<?php echo isset($item['item_type']) ? $item['item_type'] : 'other'; ?>" class="action-btn view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if(isset($item['item_type']) && $item['item_type'] != 'lab_test'): ?>
                                    <button class="action-btn stock-in" onclick="addStock(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>', '<?php echo $item['item_type']; ?>')" title="Add Stock">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                             </small>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-box-open empty-icon"></i>
                                    <div class="empty-title">No Items Found</div>
                                    <div class="empty-text">Start by adding your first inventory item</div>
                                    <a href="<?php echo BASE_URL; ?>/inventory/items/add" class="btn btn-success btn-sm">
                                        <i class="fas fa-plus me-2"></i>Add New Item
                                    </a>
                                </div>
                             </small>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function addStock(itemId, itemName, itemType) {
    window.location.href = BASE_URL + '/inventory/add-stock?item_id=' + itemId + '&item_name=' + encodeURIComponent(itemName) + '&type=' + itemType;
}

// Live search functionality
$('#searchInput').on('keyup', function() {
    var searchTerm = $(this).val().toLowerCase();
    $('#itemsTableBody tr').filter(function() {
        $(this).toggle($(this).text().toLowerCase().indexOf(searchTerm) > -1);
    });
});

// Enter key search
$('#searchInput').on('keypress', function(e) {
    if(e.which === 13) {
        var searchTerm = $(this).val();
        if(searchTerm) {
            window.location.href = BASE_URL + '/inventory/items?search=' + encodeURIComponent(searchTerm);
        }
    }
});
</script>
</body>
</html>