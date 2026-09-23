<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Reports - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        
        .report-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
            margin-bottom: 25px;
        }
        .card-header-custom {
            padding: 16px 20px;
            color: white;
        }
        .card-header-custom.bg-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .card-header-custom.bg-danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .card-header-custom.bg-info { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .card-header-custom h6 { margin: 0; font-weight: 600; }
        
        .card-body-custom { padding: 20px; }
        
        /* Filter Section */
        .filter-section {
            background: #f8fafc;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }
        .filter-section label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 4px;
        }
        .filter-section .form-control,
        .filter-section .form-select {
            font-size: 13px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .filter-section .btn-filter {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-section .btn-filter:hover {
            background: #2563eb;
        }
        .filter-section .btn-reset {
            background: #e2e8f0;
            color: #475569;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin-left: 8px;
        }
        .filter-section .btn-reset:hover {
            background: #cbd5e1;
        }
        
        .report-table {
            width: 100%;
            border-collapse: collapse;
        }
        .report-table th {
            padding: 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            text-align: left;
        }
        .report-table td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .report-table tr:hover { background: #fafbfc; }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        .stat-card .stat-icon {
            font-size: 28px;
            margin-right: 15px;
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }
        .stat-card .stat-label {
            font-size: 13px;
            color: #6c757d;
            margin: 0;
        }
        .stat-card.good { border-left: 4px solid #10b981; }
        .stat-card.warning { border-left: 4px solid #f59e0b; }
        .stat-card.danger { border-left: 4px solid #ef4444; }
        .stat-card.info { border-left: 4px solid #3b82f6; }
        .stat-card .text-good { color: #10b981; }
        .stat-card .text-warning { color: #f59e0b; }
        .stat-card .text-danger { color: #ef4444; }
        .stat-card .text-info { color: #3b82f6; }
        
        .code-badge {
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 11px;
            font-weight: 600;
        }
        
        .total-row {
            background: #ecfdf5;
            font-weight: 600;
        }
        .total-row td {
            border-top: 2px solid #10b981;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }
        .empty-icon { font-size: 48px; margin-bottom: 15px; }
        
        .badge-status {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }
        .badge-good { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        .btn-export {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }
        .btn-export:hover {
            background: #f1f5f9;
            transform: translateY(-2px);
        }
        .btn-export i { margin-right: 8px; }
        
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .report-table th, .report-table td { padding: 8px 6px; font-size: 11px; }
            .stat-card .stat-number { font-size: 20px; }
            .filter-section .row > div { margin-bottom: 10px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-chart-bar text-success me-2"></i>Inventory Reports</h1>
            <p class="page-subtitle">View stock valuation, expiry analysis and export reports</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/inventory/dashboard" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="<?php echo BASE_URL; ?>/inventory/reports">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label><i class="fas fa-tag me-1"></i>Item Type</label>
                    <select name="item_type" class="form-select form-select-sm">
                        <option value="all">All Types</option>
                        <option value="medicine" <?php echo (isset($_GET['item_type']) && $_GET['item_type'] == 'medicine') ? 'selected' : ''; ?>>💊 Medicines</option>
                        <option value="lab_test" <?php echo (isset($_GET['item_type']) && $_GET['item_type'] == 'lab_test') ? 'selected' : ''; ?>>🔬 Lab Tests</option>
                        <option value="equipment" <?php echo (isset($_GET['item_type']) && $_GET['item_type'] == 'equipment') ? 'selected' : ''; ?>>🔧 Equipment</option>
                        <option value="other" <?php echo (isset($_GET['item_type']) && $_GET['item_type'] == 'other') ? 'selected' : ''; ?>>📋 Other</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label><i class="fas fa-search me-1"></i>Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search items..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label><i class="fas fa-sort me-1"></i>Sort By</label>
                    <select name="sort_by" class="form-select form-select-sm">
                        <option value="value_desc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'value_desc') ? 'selected' : ''; ?>>Highest Value</option>
                        <option value="value_asc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'value_asc') ? 'selected' : ''; ?>>Lowest Value</option>
                        <option value="name_asc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="stock_asc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'stock_asc') ? 'selected' : ''; ?>>Lowest Stock</option>
                        <option value="stock_desc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'stock_desc') ? 'selected' : ''; ?>>Highest Stock</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter me-1"></i>Apply Filters</button>
                    <a href="<?php echo BASE_URL; ?>/inventory/reports" class="btn-reset"><i class="fas fa-undo me-1"></i>Reset</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card info">
                <div class="d-flex align-items-center">
                    <div class="stat-icon text-info"><i class="fas fa-boxes"></i></div>
                    <div>
                        <p class="stat-number text-info"><?php echo number_format($totalItems ?? 0); ?></p>
                        <p class="stat-label">Total Items</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card good">
                <div class="d-flex align-items-center">
                    <div class="stat-icon text-good"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <p class="stat-number text-good">৳ <?php echo number_format($totalValue ?? 0, 2); ?></p>
                        <p class="stat-label">Total Stock Value</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card warning">
                <div class="d-flex align-items-center">
                    <div class="stat-icon text-warning"><i class="fas fa-exclamation-triangle"></i></div>
                    <div>
                        <p class="stat-number text-warning"><?php echo number_format($lowStockCount ?? 0); ?></p>
                        <p class="stat-label">Low Stock Items</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card danger">
                <div class="d-flex align-items-center">
                    <div class="stat-icon text-danger"><i class="fas fa-clock"></i></div>
                    <div>
                        <p class="stat-number text-danger"><?php echo number_format($expiringCount ?? 0); ?></p>
                        <p class="stat-label">Expiring Soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Valuation Table -->
    <div class="report-card">
        <div class="card-header-custom bg-primary">
            <h6><i class="fas fa-chart-line me-2"></i>Stock Valuation Details</h6>
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Unit Price (৳)</th>
                            <th class="text-end">Total Value (৳)</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($valuation) && !empty($valuation)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach($valuation as $item): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                    <?php if(!empty($item['strength'])): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['strength']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="code-badge"><?php echo htmlspecialchars($item['item_code']); ?></span></td>
                                <td>
                                    <?php 
                                    $typeLabel = '';
                                    $typeIcon = '';
                                    switch($item['item_type'] ?? 'other') {
                                        case 'medicine': $typeLabel = 'Medicine'; $typeIcon = '💊'; break;
                                        case 'lab_test': $typeLabel = 'Lab Test'; $typeIcon = '🔬'; break;
                                        case 'equipment': $typeLabel = 'Equipment'; $typeIcon = '🔧'; break;
                                        default: $typeLabel = 'Other'; $typeIcon = '📋';
                                    }
                                    ?>
                                    <span class="badge bg-secondary"><?php echo $typeIcon . ' ' . $typeLabel; ?></span>
                                </td>
                                <td class="text-center"><?php echo number_format($item['quantity']); ?></td>
                                <td class="text-end">৳ <?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
                                <td class="text-end fw-bold text-success">৳ <?php echo number_format($item['total_value'], 2); ?></td>
                                <td class="text-center">
                                    <?php 
                                    $stockStatus = $item['stock_status'] ?? 'normal';
                                    if($stockStatus == 'low' || ($item['quantity'] ?? 0) <= 5):
                                    ?>
                                        <span class="badge-status badge-danger">Low Stock</span>
                                    <?php elseif($stockStatus == 'critical'): ?>
                                        <span class="badge-status badge-warning">Critical</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-good">In Stock</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="6" class="text-end"><strong>Grand Total Value</strong></td>
                                <td class="text-end fw-bold">৳ <?php 
                                    $total = isset($valuation) ? array_sum(array_column($valuation, 'total_value')) : 0;
                                    echo number_format($total, 2);
                                ?></td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-box-open empty-icon"></i>
                                    <p>No inventory items found</p>
                                    <small class="text-muted">Add items to inventory to see valuation</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if(isset($totalPages) && $totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <small class="text-muted">Showing <?php echo isset($offset) ? $offset + 1 : 1; ?> - <?php echo isset($limit) ? min($offset + $limit, $totalRecords) : $totalRecords; ?> of <?php echo $totalRecords ?? 0; ?> items</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if($currentPage > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">&laquo;</a></li>
                        <?php endif; ?>
                        <?php for($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        <?php if($currentPage < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">&raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Expiry Summary Table -->
    <div class="report-card">
        <div class="card-header-custom bg-danger">
            <h6><i class="fas fa-clock me-2"></i>Expiry Summary</h6>
        </div>
        <div class="card-body-custom">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card good">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon text-good"><i class="fas fa-check-circle"></i></div>
                            <div>
                                <p class="stat-number text-good"><?php echo number_format($expirySummary['good_stock'] ?? 0); ?></p>
                                <p class="stat-label">Good Stock</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card warning">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon text-warning"><i class="fas fa-exclamation-triangle"></i></div>
                            <div>
                                <p class="stat-number text-warning"><?php echo number_format($expirySummary['expiring_soon'] ?? 0); ?></p>
                                <p class="stat-label">Expiring Soon (30 days)</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card danger">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon text-danger"><i class="fas fa-skull-crossbones"></i></div>
                            <div>
                                <p class="stat-number text-danger"><?php echo number_format($expirySummary['expired'] ?? 0); ?></p>
                                <p class="stat-label">Expired</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Batch Number</th>
                            <th>Expiry Date</th>
                            <th class="text-center">Qty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Get expiring items from controller data
                        $expiringItems = isset($expiringItems) ? $expiringItems : [];
                        ?>
                        <?php if(!empty($expiringItems)): ?>
                            <?php $counter = 1; ?>
                            <?php foreach($expiringItems as $item): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                                <td><span class="code-badge"><?php echo htmlspecialchars($item['item_code'] ?? 'N/A'); ?></span></td>
                                <td>
                                    <?php 
                                    $typeLabel = $item['item_type'] ?? 'other';
                                    $typeIcon = ($typeLabel == 'medicine') ? '💊' : (($typeLabel == 'lab_test') ? '🔬' : '📦');
                                    ?>
                                    <span class="badge bg-secondary"><?php echo $typeIcon . ' ' . ucfirst($typeLabel); ?></span>
                                </td>
                                <td><small><?php echo htmlspecialchars($item['batch_number'] ?? 'N/A'); ?></small></td>
                                <td>
                                    <?php 
                                    $expiryDate = $item['expiry_date'] ?? '';
                                    $today = date('Y-m-d');
                                    $daysLeft = $expiryDate ? (strtotime($expiryDate) - strtotime($today)) / (60 * 60 * 24) : 999;
                                    $daysLeft = round($daysLeft);
                                    ?>
                                    <span class="<?php echo ($daysLeft < 0) ? 'text-danger' : (($daysLeft < 30) ? 'text-warning' : ''); ?>">
                                        <?php echo $expiryDate ? date('d M Y', strtotime($expiryDate)) : 'N/A'; ?>
                                        <?php if($daysLeft >= 0 && $daysLeft <= 30): ?>
                                            <br><small>(<?php echo $daysLeft; ?> days left)</small>
                                        <?php elseif($daysLeft < 0): ?>
                                            <br><small>(Expired <?php echo abs($daysLeft); ?> days ago)</small>
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="text-center"><?php echo number_format($item['quantity'] ?? 0); ?></td>
                                <td>
                                    <?php if($daysLeft < 0): ?>
                                        <span class="badge-status badge-danger">Expired</span>
                                    <?php elseif($daysLeft <= 7): ?>
                                        <span class="badge-status badge-danger">Critical</span>
                                    <?php elseif($daysLeft <= 30): ?>
                                        <span class="badge-status badge-warning">Expiring Soon</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-good">Good</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-state">
                                    <i class="fas fa-check-circle empty-icon text-success"></i>
                                    <p>No expiring items found</p>
                                    <small class="text-muted">All items have expiry dates beyond 30 days</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Export Section -->
    <div class="report-card">
        <div class="card-header-custom bg-info">
            <h6><i class="fas fa-download me-2"></i>Export Reports</h6>
        </div>
        <div class="card-body-custom">
            <div class="row g-3">
                <div class="col-md-3 col-sm-6">
                    <button class="btn-export w-100" onclick="exportReport('stock')">
                        <i class="fas fa-file-excel text-success"></i> Stock Valuation
                    </button>
                </div>
                <div class="col-md-3 col-sm-6">
                    <button class="btn-export w-100" onclick="exportReport('expiry')">
                        <i class="fas fa-file-excel text-warning"></i> Expiry Report
                    </button>
                </div>
                <div class="col-md-3 col-sm-6">
                    <button class="btn-export w-100" onclick="window.print()">
                        <i class="fas fa-print text-primary"></i> Print Report
                    </button>
                </div>
                <div class="col-md-3 col-sm-6">
                    <button class="btn-export w-100" onclick="exportReport('valuation')">
                        <i class="fas fa-file-pdf text-danger"></i> Valuation Details
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function exportReport(type) {
    Swal.fire({
        title: 'Exporting Report',
        text: 'Please wait while we generate your report...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    window.location.href = BASE_URL + '/inventory/export-report?type=' + type;
    
    setTimeout(() => {
        Swal.close();
        Swal.fire('Success!', 'Report exported successfully', 'success');
    }, 2000);
}

// Auto-submit filter on change
$(document).ready(function() {
    $('.filter-section select').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>
</body>
</html>