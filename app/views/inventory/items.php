<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Items - UniDia Healthcare</title>
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
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
        }
        .filter-label { font-size: 12px; font-weight: 600; color: #4b5563; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control-sm, .form-select-sm { border-radius: 10px; border: 1px solid #e2e8f0; padding: 8px 12px; font-size: 13px; }
        .form-control-sm:focus, .form-select-sm:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
        
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
        .inventory-table { width: 100%; border-collapse: collapse; }
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
        .type-accessory { background: #fed7aa; color: #9a3412; }
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
        .action-btn.stock:hover { border-color: #10b981; color: #10b981; background: #ecfdf5; }
        .action-btn.barcode:hover { border-color: #8b5cf6; color: #8b5cf6; background: #f5f3ff; }
        
        /* Pagination */
        .pagination-container { margin-top: 25px; display: flex; justify-content: center; }
        .pagination { gap: 5px; flex-wrap: wrap; }
        .page-link {
            border-radius: 10px !important;
            border: 1px solid #e2e8f0;
            color: #4b5563;
            font-size: 13px;
            padding: 8px 14px;
            transition: all 0.2s;
        }
        .page-link:hover { background: #10b981; color: white; border-color: #10b981; }
        .page-item.active .page-link { background: #10b981; border-color: #10b981; }
        
        /* Empty State */
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 64px; color: #cbd5e1; margin-bottom: 20px; }
        .empty-title { font-size: 18px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .empty-text { color: #94a3b8; font-size: 13px; margin-bottom: 20px; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .table-header { flex-direction: column; align-items: flex-start; }
            .inventory-table { font-size: 11px; }
            .inventory-table th, .inventory-table td { padding: 10px 6px; }
            .action-btns { gap: 3px; }
            .action-btn { width: 26px; height: 26px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-boxes text-success me-2"></i>Inventory Items</h1>
            <p class="page-subtitle">Manage all medicines, lab tests, accessories and inventory items</p>
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
                <div class="stat-number text-primary" id="totalItems"><?php echo $totalRecords ?? 0; ?></div>
                <div class="stat-label">Total Items</div>
                <i class="fas fa-cubes stat-icon"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card warning">
                <div class="stat-number text-warning" id="lowStockCount"><?php echo $lowStockCount ?? 0; ?></div>
                <div class="stat-label">Low Stock Items</div>
                <i class="fas fa-exclamation-triangle stat-icon"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card danger">
                <div class="stat-number text-danger" id="expiringCount"><?php echo $expiringCount ?? 0; ?></div>
                <div class="stat-label">Expiring Soon</div>
                <i class="fas fa-clock stat-icon"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card success">
                <div class="stat-number text-success" id="totalValue">৳ <?php echo number_format($totalValue ?? 0, 2); ?></div>
                <div class="stat-label">Total Stock Value</div>
                <i class="fas fa-dollar-sign stat-icon"></i>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="filter-label"><i class="fas fa-search me-1"></i> Search</div>
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search by name or code..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <div class="filter-label"><i class="fas fa-tag me-1"></i> Item Type</div>
                <select id="typeFilter" class="form-select form-select-sm">
                    <option value="all" <?php echo ($selectedType ?? 'all') == 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="medicine" <?php echo ($selectedType ?? '') == 'medicine' ? 'selected' : ''; ?>>💊 Medicine</option>
                    <option value="lab_test" <?php echo ($selectedType ?? '') == 'lab_test' ? 'selected' : ''; ?>>🔬 Lab Test</option>
                    <option value="Accessory_Equipment" <?php echo ($selectedType ?? '') == 'Accessory_Equipment' ? 'selected' : ''; ?>>📦 Accessory / Equipment</option>
                    <option value="Other" <?php echo ($selectedType ?? '') == 'Other' ? 'selected' : ''; ?>>📋 Other</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="filter-label"><i class="fas fa-folder me-1"></i> Category</div>
                <select id="categoryFilter" class="form-select form-select-sm">
                    <option value="0">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="filter-label"><i class="fas fa-chart-line me-1"></i> Stock Status</div>
                <select id="stockFilter" class="form-select form-select-sm">
                    <option value="all">All Stock</option>
                    <option value="low">Low Stock (≤ Reorder)</option>
                    <option value="normal">Normal Stock</option>
                    <option value="expiring">Expiring Soon</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()">
                    <i class="fas fa-search me-2"></i>Apply Filters
                </button>
                <button class="btn btn-outline-secondary btn-sm ms-2" onclick="resetFilters()">
                    <i class="fas fa-undo-alt me-2"></i>Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="items-table-container">
        <div class="table-header">
            <h6 class="table-title"><i class="fas fa-list me-2"></i>Items List</h6>
            <span class="record-badge" id="recordCount"><?php echo $totalRecords ?? 0; ?> records</span>
        </div>
        <div class="table-responsive">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Item Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Selling Price</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="itemsTableBody">
                    <?php if(!empty($items) && is_array($items)): ?>
                        <?php foreach($items as $item): ?>
                        <tr data-item-id="<?php echo $item['id']; ?>" data-item-type="<?php echo $item['item_type']; ?>">
                            <td>
                                <span class="badge bg-secondary"><?php echo !empty($item['item_code']) ? htmlspecialchars($item['item_code']) : ($item['item_type'] == 'lab_test' ? 'LAB' . str_pad($item['id'], 5, '0', STR_PAD_LEFT) : 'N/A'); ?></span>
                             </small>
                            <td>
                                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                <?php if(!empty($item['generic_name']) && $item['generic_name'] != $item['item_name']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($item['generic_name']); ?></small>
                                <?php endif; ?>
                                <?php if($item['item_type'] == 'medicine' && !empty($item['strength'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($item['strength']); ?></small>
                                <?php endif; ?>
                                <?php if($item['item_type'] == 'lab_test' && !empty($item['strength'])): ?>
                                <br><small class="text-muted">Specimen: <?php echo htmlspecialchars($item['strength']); ?></small>
                                <?php endif; ?>
                             </small>
                            <td>
                                <?php 
                                    $typeIcon = '';
                                    $typeClass = '';
                                    $typeName = '';
                                    if($item['item_type'] == 'medicine') {
                                        $typeClass = 'type-medicine';
                                        $typeIcon = '💊';
                                        $typeName = 'Medicine';
                                    } elseif($item['item_type'] == 'lab_test') {
                                        $typeClass = 'type-lab_test';
                                        $typeIcon = '🔬';
                                        $typeName = 'Lab Test';
                                    } elseif($item['item_type'] == 'Accessory_Equipment') {
                                        $typeClass = 'type-accessory';
                                        $typeIcon = '📦';
                                        $typeName = 'Accessory';
                                    } elseif($item['item_type'] == 'Other') {
                                        $typeClass = 'type-other';
                                        $typeIcon = '📋';
                                        $typeName = 'Other';
                                    } else {
                                        $typeClass = 'type-other';
                                        $typeIcon = '📋';
                                        $typeName = ucfirst($item['item_type']);
                                    }
                                    ?>
                                <span class="type-badge <?php echo $typeClass; ?>">
                                    <?php echo $typeIcon; ?> <?php echo $typeName; ?>
                                </span>
                             </small>
                            <td><?php echo !empty($item['category_name']) ? htmlspecialchars($item['category_name']) : 'Uncategorized'; ?></small>
                            <td>
                                <?php 
                                $stockLevel = $item['current_stock'] ?? 0;
                                $reorderLevel = $item['reorder_level'] ?? 10;
                                $stockClass = $stockLevel <= $reorderLevel ? 'stock-low' : ($stockLevel <= $reorderLevel * 2 ? 'stock-medium' : 'stock-high');
                                $stockText = $stockLevel <= $reorderLevel ? 'Low Stock' : ($stockLevel <= $reorderLevel * 2 ? 'Medium Stock' : 'Good Stock');
                                ?>
                                <div class="stock-badge <?php echo $stockClass; ?>">
                                    <?php echo $stockLevel; ?> <?php echo htmlspecialchars($item['unit_of_measure'] ?? 'units'); ?>
                                </div>
                                <small class="text-muted"><?php echo $stockText; ?></small>
                             </small>
                            <td><strong class="text-success">৳ <?php echo number_format($item['selling_price'] ?? 0, 2); ?></strong></small>
                            <td><?php echo $reorderLevel; ?> <?php echo htmlspecialchars($item['unit_of_measure'] ?? 'units'); ?></small>
                            <td>
                                <span class="status-badge <?php echo ($item['status'] ?? 'active') == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo ucfirst($item['status'] ?? 'active'); ?>
                                </span>
                             </small>
                            <td>
                                <div class="action-btns">
                                    <a href="<?php echo BASE_URL; ?>/inventory/view-item?id=<?php echo $item['id']; ?>&type=<?php echo $item['item_type']; ?>" class="action-btn view" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="action-btn stock" onclick="addStock(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>', '<?php echo $item['item_type']; ?>')" title="Add Stock">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="action-btn barcode" onclick="generateBarcode(<?php echo $item['id']; ?>, '<?php echo $item['item_type']; ?>')" title="Generate Barcode">
                                        <i class="fas fa-barcode"></i>
                                    </button>
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
    
    <!-- Pagination -->
    <?php if(isset($totalPages) && $totalPages > 1): ?>
    <div class="pagination-container">
        <nav>
            <ul class="pagination">
                <?php if($currentPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&type=<?php echo $selectedType ?? ''; ?>">
                        <i class="fas fa-chevron-left me-1"></i> Previous
                    </a>
                </li>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if($i == $currentPage): ?>
                    <li class="page-item active"><span class="page-link"><?php echo $i; ?></span></li>
                    <?php else: ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&type=<?php echo $selectedType ?? ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if($currentPage < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&type=<?php echo $selectedType ?? ''; ?>">
                        Next <i class="fas fa-chevron-right ms-1"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function applyFilters() {
    let search = $('#searchInput').val();
    let type = $('#typeFilter').val();
    let category = $('#categoryFilter').val();
    let stock = $('#stockFilter').val();
    
    let url = BASE_URL + '/inventory/items?';
    if(search) url += 'search=' + encodeURIComponent(search) + '&';
    if(type && type != 'all') url += 'type=' + type + '&';
    if(category && category != '0') url += 'category=' + category + '&';
    if(stock && stock != 'all') url += 'stock_status=' + stock;
    
    window.location.href = url;
}

function resetFilters() {
    window.location.href = BASE_URL + '/inventory/items';
}

function addStock(itemId, itemName, itemType) {
    window.location.href = BASE_URL + '/inventory/add-stock?item_id=' + itemId + '&item_name=' + encodeURIComponent(itemName) + '&type=' + itemType;
}

function generateBarcode(itemId, itemType) {
    $.ajax({
        url: BASE_URL + '/inventory/generate-barcode',
        method: 'POST',
        data: { item_id: itemId, item_type: itemType },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    title: 'Barcode Generated',
                    html: `<div style="font-family: monospace; font-size: 24px; letter-spacing: 2px;">${response.barcode}</div>`,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to generate barcode', 'error');
        }
    });
}

// Enter key search
$('#searchInput').on('keypress', function(e) {
    if(e.which === 13) applyFilters();
});
</script>
</body>
</html>