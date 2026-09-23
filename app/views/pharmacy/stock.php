<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .expiring-soon { background-color: #fff3cd !important; }
        .expired { background-color: #f8d7da !important; text-decoration: line-through; opacity: 0.7; }
        .stock-status { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
        .stock-status.in-stock { background: #d1fae5; color: #10b981; }
        .stock-status.low-stock { background: #fee2e2; color: #ef4444; }
        .stock-status.expired { background: #e5e7eb; color: #6b7280; }
        .stock-status.expiring-soon { background: #fef3c7; color: #f59e0b; }
        .stock-status.moved { background: #e0e7ff; color: #4338ca; }
        .modal-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
        .modal-header .btn-close { filter: brightness(0) invert(1); }
        .required-field::after { content: " *"; color: #ef4444; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: nowrap; }
        .action-buttons .btn { white-space: nowrap; }
        .filter-bar { background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .stat-card { transition: transform 0.2s; border: none; border-radius: 15px; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        .table-actions { display: flex; gap: 5px; flex-wrap: nowrap; }
        .table-actions .btn { margin: 0; padding: 4px 8px; font-size: 11px; white-space: nowrap; }
        .stock-unit { font-size: 11px; color: #6c757d; }
        .price-value { font-weight: 600; }
        .filter-buttons .btn { margin-right: 5px; margin-bottom: 5px; }
        .table th, .table td { vertical-align: middle; white-space: nowrap; }
        .pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 6px 12px; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #374151; font-size: 12px; }
        .pagination a:hover { background: #f3f4f6; }
        .pagination .active { background: #10b981; color: white; border-color: #10b981; }
        .pagination .disabled { color: #9ca3af; cursor: not-allowed; }
    </style>
</head>
<body>
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-boxes text-success me-2"></i>Stock Management</h2>
            <p class="text-muted small mb-0">Manage medicine stock, batches, expiry alerts, and move expired products</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <button class="btn btn-outline-info" onclick="location.reload()">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
            <button class="btn btn-success" onclick="openAddStockModal()">
                <i class="fas fa-plus me-2"></i>Add Stock
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Stock Batches</h6>
                            <h3 class="mb-0 text-success" id="totalBatches">0</h3>
                        </div>
                        <div class="stat-icon bg-success bg-opacity-10">
                            <i class="fas fa-cubes fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total Units</h6>
                            <h3 class="mb-0 text-primary" id="totalUnits">0</h3>
                        </div>
                        <div class="stat-icon bg-primary bg-opacity-10">
                            <i class="fas fa-pills fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Low Stock</h6>
                            <h3 class="mb-0 text-warning" id="lowStockCount">0</h3>
                            <small class="text-muted" id="lowStockDetail">-</small>
                        </div>
                        <div class="stat-icon bg-warning bg-opacity-10">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Expired/Moved</h6>
                            <h3 class="mb-0 text-danger" id="expiredCount">0</h3>
                            <small class="text-muted" id="expiredDetail">-</small>
                        </div>
                        <div class="stat-icon bg-danger bg-opacity-10">
                            <i class="fas fa-trash-alt fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar shadow-sm">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted">Search Medicine</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Search by medicine name, code, or batch...">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Status Filter</label>
                <select id="statusFilter" class="form-select">
                    <option value="all">All Status</option>
                    <option value="in_stock">In Stock</option>
                    <option value="low_stock">Low Stock</option>
                    <option value="expiring_soon">Expiring Soon</option>
                    <option value="expired">Expired</option>
                    <option value="moved">Moved to Expiry</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Sort By</label>
                <select id="sortBy" class="form-select">
                    <option value="expiry_asc">Expiry Date (Soonest First)</option>
                    <option value="expiry_desc">Expiry Date (Latest First)</option>
                    <option value="name_asc">Medicine Name (A-Z)</option>
                    <option value="stock_desc">Stock (High to Low)</option>
                    <option value="stock_asc">Stock (Low to High)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Expiry Date Range</label>
                <div class="d-flex gap-2">
                    <input type="date" id="expiryFromDate" class="form-control form-control-sm" placeholder="From">
                    <input type="date" id="expiryToDate" class="form-control form-control-sm" placeholder="To">
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                    <i class="fas fa-undo-alt me-2"></i>Reset
                </button>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12 filter-buttons">
                <button class="btn btn-sm btn-outline-secondary" onclick="filterByStatus('all')">All</button>
                <button class="btn btn-sm btn-outline-success" onclick="filterByStatus('in_stock')">In Stock</button>
                <button class="btn btn-sm btn-outline-warning" onclick="filterByStatus('low_stock')">Low Stock</button>
                <button class="btn btn-sm btn-outline-info" onclick="filterByStatus('expiring_soon')">Expiring Soon</button>
                <button class="btn btn-sm btn-outline-danger" onclick="filterByStatus('expired')">Expired</button>
                <button class="btn btn-sm btn-outline-primary" onclick="filterByStatus('moved')">Moved to Expiry</button>
            </div>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="card shadow">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Stock Batches</h6>
                <div class="d-flex gap-2">
                    <span class="badge bg-secondary" id="stockCount">0 records</span>
                    <button class="btn btn-sm btn-danger" onclick="showBulkMoveModal()" id="bulkMoveBtn" style="display: none;">
                        <i class="fas fa-arrow-right me-1"></i>Move Selected to Expiry
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="3%"><input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll()"></th>
                            <th width="22%">Medicine</th>
                            <th width="10%">Batch #</th>
                            <th width="10%">Expiry Date</th>
                            <th width="8%">Quantity</th>
                            <th width="8%">Unit</th>
                            <th width="10%">Purchase Price</th>
                            <th width="10%">Selling Price</th>
                            <th width="9%">Status</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">
                        <tr id="loadingRow">
                            <td colspan="10" class="text-center py-5">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading stock data...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
    <div id="paginationContainer" class="d-flex justify-content-center mt-4"></div>
</div>

<!-- ==================== ADD STOCK MODAL ==================== -->
<div id="addStockModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStockForm">
                    <div class="mb-3">
                        <label class="form-label required-field">Select Medicine</label>
                        <select name="medicine_id" id="addStockMedicineId" class="form-select" required>
                            <option value="">-- Select Medicine --</option>
                            <?php if(!empty($medicines) && is_array($medicines)): ?>
                                <?php foreach($medicines as $med): ?>
                                <option value="<?php echo $med['id']; ?>">
                                    <?php 
                                    echo htmlspecialchars($med['medicine_name']); 
                                    if(!empty($med['medicine_code'])) {
                                        echo ' (' . htmlspecialchars($med['medicine_code']) . ')';
                                    }
                                    ?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>No medicines available. Please add medicines first.</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Batch Number</label>
                        <input type="text" name="batch_number" id="addStockBatchNumber" class="form-control" required placeholder="e.g., BCH001">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Expiry Date</label>
                            <input type="date" name="expiry_date" id="addStockExpiryDate" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Manufacturing Date</label>
                            <input type="date" name="manufacturing_date" id="addStockManufacturingDate" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Quantity</label>
                            <input type="number" name="quantity" id="addStockQuantity" class="form-control" required min="1" value="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Unit of Measure</label>
                            <select name="unit_of_measure" id="addStockUnit" class="form-select" required>
                                <option value="Strip">Strip</option>
                                <option value="Box">Box</option>
                                <option value="Bottle">Bottle</option>
                                <option value="Tablet">Tablet</option>
                                <option value="Capsule">Capsule</option>
                                <option value="Injection">Injection</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Purchase Price (৳)</label>
                            <input type="number" step="0.01" name="purchase_price" id="addStockPurchasePrice" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Selling Price (৳)</label>
                            <input type="number" step="0.01" name="selling_price" id="addStockSellingPrice" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Storage Location</label>
                            <input type="text" name="location" id="addStockLocation" class="form-control" placeholder="e.g., Shelf A-1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" name="reorder_level" id="addStockReorderLevel" class="form-control" placeholder="Default: 10" value="10">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="submitAddStockBtn">Add Stock</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MOVE TO EXPIRY MODAL ==================== -->
<div id="moveToExpiryModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Move to Expiry Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="moveToExpiryForm">
                    <input type="hidden" name="stock_id" id="moveStockId">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> Moving this stock to expiry will remove it from active stock.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Medicine</label>
                        <input type="text" id="moveMedicineName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Batch Number</label>
                        <input type="text" id="moveBatchNumber" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Available Quantity</label>
                        <input type="text" id="moveAvailableQty" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity to Move *</label>
                        <input type="number" name="quantity" id="moveQuantity" class="form-control" required min="1">
                        <small class="text-muted" id="maxQuantityMsg"></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Move *</label>
                        <select name="reason" id="moveReason" class="form-select" required>
                            <option value="expired">Expired</option>
                            <option value="damaged">Damaged</option>
                            <option value="recalled">Recalled by Manufacturer</option>
                            <option value="returned">Returned to Supplier</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" id="moveRemarks" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmMoveBtn">Move to Expiry</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== DELETE CONFIRM MODAL ==================== -->
<div id="deleteStockModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i>Delete Stock</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this stock record?</p>
                <p class="text-danger small mb-0">This action cannot be undone.</p>
                <input type="hidden" id="deleteStockId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let allStocks = [];
let currentPage = 1;
let totalPages = 1;
const itemsPerPage = 10;

$(document).ready(function() {
    loadStocks();
    setupEventListeners();
    setDefaultExpiryDate();
});

function setupEventListeners() {
    $('#searchInput').on('keyup', function() { currentPage = 1; filterAndRenderStocks(); });
    $('#statusFilter').on('change', function() { currentPage = 1; filterAndRenderStocks(); });
    $('#sortBy').on('change', filterAndRenderStocks);
    $('#expiryFromDate, #expiryToDate').on('change', function() { currentPage = 1; filterAndRenderStocks(); });
    $('#submitAddStockBtn').on('click', submitAddStock);
    $('#confirmDeleteBtn').on('click', confirmDeleteStock);
    $('#confirmMoveBtn').on('click', confirmMoveToExpiry);
}

function setDefaultExpiryDate() {
    let today = new Date();
    let oneYearLater = new Date(today);
    oneYearLater.setFullYear(today.getFullYear() + 1);
    $('#addStockExpiryDate').val(oneYearLater.toISOString().split('T')[0]);
}

function loadStocks() {
    $('#loadingRow').show();
    
    $.ajax({
        url: BASE_URL + '/pharmacy/stock-data',
        method: 'GET',
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            $('#loadingRow').hide();
            if(response.success && response.stocks) {
                allStocks = response.stocks;
                updateStats();
                filterAndRenderStocks();
            } else {
                showError('Failed to load stock data');
                renderEmptyState();
            }
        },
        error: function() {
            $('#loadingRow').hide();
            if(typeof phpStocks !== 'undefined' && phpStocks && phpStocks.length > 0) {
                allStocks = phpStocks;
                updateStats();
                filterAndRenderStocks();
            } else {
                renderEmptyState();
                showError('Could not load stock data');
            }
        }
    });
}

function renderEmptyState() {
    $('#stockTableBody').html('<tr><td colspan="10" class="text-center py-5"><i class="fas fa-box-open fa-3x text-muted mb-3 d-block"></i><p class="text-muted">No stock records found</p><button class="btn btn-sm btn-success" onclick="openAddStockModal()"><i class="fas fa-plus me-2"></i>Add Stock</button></td></tr>');
}

function updateStats() {
    let totalBatches = allStocks.length;
    let totalUnits = allStocks.reduce((sum, s) => sum + (parseInt(s.quantity) || 0), 0);
    
    // Low Stock calculation - count only active stocks (not expired or moved) with low quantity
    let lowStockItems = allStocks.filter(s => {
        let reorderLevel = s.reorder_level || 10;
        return parseInt(s.quantity) <= reorderLevel && s.status !== 'expired' && s.status !== 'moved' && s.status !== 'low_stock';
    });
    let lowStockCount = lowStockItems.length;
    
    // Expired/Moved calculation - count both expired and moved status
    let expiredItems = allStocks.filter(s => s.status === 'expired' || s.status === 'moved');
    let expiredCount = expiredItems.length;
    
    // Also count expiring soon
    let today = new Date().toISOString().split('T')[0];
    let thirtyDaysLater = new Date();
    thirtyDaysLater.setDate(thirtyDaysLater.getDate() + 30);
    let thirtyDaysLaterStr = thirtyDaysLater.toISOString().split('T')[0];
    
    let expiringSoonCount = allStocks.filter(s => {
        return s.expiry_date > today && s.expiry_date <= thirtyDaysLaterStr && s.status !== 'expired' && s.status !== 'moved';
    }).length;
    
    // Update the display
    $('#totalBatches').text(totalBatches);
    $('#totalUnits').text(totalUnits);
    $('#lowStockCount').text(lowStockCount);
    $('#expiredCount').text(expiredCount);
    
    // Update the small detail text
    if(lowStockCount > 0) {
        let lowStockNames = lowStockItems.slice(0, 3).map(s => s.medicine_name).join(', ');
        $('#lowStockDetail').html(`<span class="text-warning">⚠️ ${lowStockNames}${lowStockCount > 3 ? '...' : ''}</span>`);
    } else {
        $('#lowStockDetail').html('<span class="text-success">✓ All good</span>');
    }
    
    if(expiredCount > 0) {
        let expiredNames = expiredItems.slice(0, 3).map(s => s.medicine_name).join(', ');
        $('#expiredDetail').html(`<span class="text-danger">⚠️ ${expiredNames}${expiredCount > 3 ? '...' : ''}</span>`);
    } else {
        $('#expiredDetail').html('<span class="text-success">✓ No expired items</span>');
    }
}

function filterByStatus(status) {
    $('#statusFilter').val(status);
    currentPage = 1;
    filterAndRenderStocks();
}

function filterAndRenderStocks() {
    let searchTerm = $('#searchInput').val().toLowerCase();
    let statusFilter = $('#statusFilter').val();
    let sortBy = $('#sortBy').val();
    let expiryFrom = $('#expiryFromDate').val();
    let expiryTo = $('#expiryToDate').val();
    
    let filtered = [...allStocks];
    
    if(searchTerm) {
        filtered = filtered.filter(s => 
            (s.medicine_name && s.medicine_name.toLowerCase().includes(searchTerm)) ||
            (s.medicine_code && s.medicine_code.toLowerCase().includes(searchTerm)) ||
            (s.batch_number && s.batch_number.toLowerCase().includes(searchTerm))
        );
    }
    
    if(statusFilter !== 'all') {
        filtered = filtered.filter(s => s.status === statusFilter);
    }
    
    if(expiryFrom) {
        filtered = filtered.filter(s => s.expiry_date >= expiryFrom);
    }
    if(expiryTo) {
        filtered = filtered.filter(s => s.expiry_date <= expiryTo);
    }
    
    filtered.sort((a, b) => {
        switch(sortBy) {
            case 'expiry_asc': return new Date(a.expiry_date) - new Date(b.expiry_date);
            case 'expiry_desc': return new Date(b.expiry_date) - new Date(a.expiry_date);
            case 'name_asc': return (a.medicine_name || '').localeCompare(b.medicine_name || '');
            case 'stock_desc': return (parseInt(b.quantity) || 0) - (parseInt(a.quantity) || 0);
            case 'stock_asc': return (parseInt(a.quantity) || 0) - (parseInt(b.quantity) || 0);
            default: return 0;
        }
    });
    
    totalPages = Math.ceil(filtered.length / itemsPerPage);
    let start = (currentPage - 1) * itemsPerPage;
    let paginated = filtered.slice(start, start + itemsPerPage);
    
    renderStockTable(paginated);
    renderPagination(filtered.length);
    $('#stockCount').text(filtered.length + ' records');
}

function renderPagination(totalRecords) {
    if(totalPages <= 1) {
        $('#paginationContainer').empty();
        return;
    }
    
    let html = '<nav><ul class="pagination">';
    
    if(currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${currentPage - 1})">&laquo; Previous</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">&laquo; Previous</span></li>`;
    }
    
    let start = Math.max(1, currentPage - 2);
    let end = Math.min(totalPages, currentPage + 2);
    
    for(let i = start; i <= end; i++) {
        if(i === currentPage) {
            html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${i})">${i}</a></li>`;
        }
    }
    
    if(currentPage < totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${currentPage + 1})">Next &raquo;</a></li>`;
    } else {
        html += `<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>`;
    }
    
    html += '</ul></nav>';
    $('#paginationContainer').html(html);
}

function goToPage(page) {
    currentPage = page;
    filterAndRenderStocks();
    $('html, body').animate({ scrollTop: 0 }, 300);
}

function renderStockTable(stocks) {
    let tbody = $('#stockTableBody');
    
    if(!stocks || stocks.length === 0) {
        tbody.html('<tr><td colspan="10" class="text-center py-5"><i class="fas fa-search fa-3x text-muted mb-3 d-block"></i><p class="text-muted">No matching stock records found</p></td></tr>');
        return;
    }
    
    let html = '';
    let today = new Date().toISOString().split('T')[0];
    let thirtyDaysLater = new Date();
    thirtyDaysLater.setDate(thirtyDaysLater.getDate() + 30);
    let thirtyDaysLaterStr = thirtyDaysLater.toISOString().split('T')[0];
    
    for(let stock of stocks) {
        let isExpired = stock.expiry_date < today;
        let isExpiringSoon = !isExpired && stock.expiry_date <= thirtyDaysLaterStr;
        let isMoved = stock.status === 'moved';
        let reorderLevel = stock.reorder_level || 10;
        let isLowStock = !isExpired && !isMoved && parseInt(stock.quantity) <= reorderLevel;
        
        let rowClass = '';
        if(isMoved) rowClass = 'expired';
        else if(isExpired) rowClass = 'expired';
        else if(isExpiringSoon) rowClass = 'expiring-soon';
        
        let statusBadge = '';
        if(isMoved) {
            statusBadge = '<span class="stock-status moved">Moved to Expiry</span>';
        } else if(isExpired) {
            statusBadge = '<span class="stock-status expired">Expired</span>';
        } else if(isExpiringSoon) {
            statusBadge = '<span class="stock-status expiring-soon">Expiring Soon</span>';
        } else if(isLowStock) {
            statusBadge = '<span class="stock-status low-stock">Low Stock</span>';
        } else {
            statusBadge = '<span class="stock-status in-stock">In Stock</span>';
        }
        
        let expiryDateFormatted = stock.expiry_date ? new Date(stock.expiry_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
        let medicineDisplay = stock.medicine_name || 'N/A';
        if(stock.medicine_code) {
            medicineDisplay += `<br><small class="text-muted">${escapeHtml(stock.medicine_code)}</small>`;
        }
        
        let unit = stock.unit_of_measure || 'Strip';
        
        html += `<tr class="${rowClass}">
            <td class="text-center">
                ${!isMoved && !isExpired ? `<input type="checkbox" class="stock-checkbox" value="${stock.id}" onchange="updateSelectedStocks()">` : ''}
             </td>
            <td>${medicineDisplay}</td>
            <td><code>${escapeHtml(stock.batch_number || 'N/A')}</code></td>
            <td>
                ${expiryDateFormatted}
                ${isExpiringSoon && !isExpired && !isMoved ? '<span class="badge bg-warning ms-1">Soon</span>' : ''}
                ${isExpired && !isMoved ? '<span class="badge bg-danger ms-1">Expired</span>' : ''}
              </td>
            <td>
                <span class="fw-bold ${isLowStock && !isExpired && !isMoved ? 'text-danger' : ''}">
                    ${parseInt(stock.quantity) || 0}
                </span>
                <br><small class="stock-unit">${unit}</small>
              </td>
            <td><small class="stock-unit">${escapeHtml(unit)}</small></td>
            <td><small class="price-value">৳ ${parseFloat(stock.purchase_price || 0).toFixed(2)}</small></td>
            <td><small class="price-value">৳ ${parseFloat(stock.selling_price || 0).toFixed(2)}</small></td>
            <td>${statusBadge}</td>
            <td>
                <div class="table-actions">
                    <button class="btn btn-sm btn-outline-info" onclick="viewStockDetails(${stock.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" onclick="viewStockHistory(${stock.medicine_id})" title="Stock History">
                        <i class="fas fa-history"></i>
                    </button>
                    ${!isMoved && !isExpired ? `<button class="btn btn-sm btn-outline-warning" onclick="openMoveToExpiryModal(${stock.id}, '${escapeHtml(stock.medicine_name)}', '${escapeHtml(stock.batch_number)}', ${stock.quantity})" title="Move to Expiry">
                        <i class="fas fa-trash-alt"></i>
                    </button>` : ''}
                    ${!isMoved ? `<button class="btn btn-sm btn-outline-danger" onclick="deleteStock(${stock.id})" title="Delete Stock">
                        <i class="fas fa-times"></i>
                    </button>` : ''}
                </div>
              </td>
         </tr>`;
    }
    
    tbody.html(html);
}

function updateSelectedStocks() {
    let selectedStocks = [];
    $('.stock-checkbox:checked').each(function() {
        selectedStocks.push($(this).val());
    });
    
    if(selectedStocks.length > 0) {
        $('#bulkMoveBtn').show();
    } else {
        $('#bulkMoveBtn').hide();
    }
}

function toggleSelectAll() {
    let isChecked = $('#selectAllCheckbox').is(':checked');
    $('.stock-checkbox').prop('checked', isChecked);
    updateSelectedStocks();
}

function showBulkMoveModal() {
    let selectedStocks = [];
    $('.stock-checkbox:checked').each(function() {
        selectedStocks.push($(this).val());
    });
    
    if(selectedStocks.length === 0) {
        Swal.fire('Warning', 'No items selected', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Bulk Move to Expiry',
        html: `Are you sure you want to move ${selectedStocks.length} stock items to expiry?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, move all',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Processing...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            $.ajax({
                url: BASE_URL + '/pharmacy/bulk-move-expiry',
                method: 'POST',
                data: { stock_ids: JSON.stringify(selectedStocks) },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success', response.message, 'success').then(() => {
                            loadStocks();
                            $('#selectAllCheckbox').prop('checked', false);
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Failed to move stocks', 'error'); }
            });
        }
    });
}

function openMoveToExpiryModal(stockId, medicineName, batchNumber, quantity) {
    $('#moveStockId').val(stockId);
    $('#moveMedicineName').val(medicineName);
    $('#moveBatchNumber').val(batchNumber);
    $('#moveAvailableQty').val(quantity + ' units');
    $('#moveQuantity').val(quantity);
    $('#moveQuantity').attr('max', quantity);
    $('#maxQuantityMsg').text(`Max quantity: ${quantity}`);
    $('#moveReason').val('expired');
    $('#moveRemarks').val('');
    
    $('#moveToExpiryModal').modal('show');
}

function confirmMoveToExpiry() {
    let stockId = $('#moveStockId').val();
    let quantity = $('#moveQuantity').val();
    let reason = $('#moveReason').val();
    let remarks = $('#moveRemarks').val();
    let maxQty = parseInt($('#moveQuantity').attr('max'));
    
    console.log('Move to Expiry - Stock ID:', stockId, 'Quantity:', quantity, 'Reason:', reason);
    
    if(!stockId || stockId <= 0) {
        Swal.fire('Error', 'Invalid stock ID', 'error');
        return;
    }
    
    if(!quantity || quantity <= 0) {
        Swal.fire('Error', 'Please enter a valid quantity', 'error');
        return;
    }
    
    if(quantity > maxQty) {
        Swal.fire('Error', `Quantity cannot exceed ${maxQty} units`, 'error');
        return;
    }
    
    Swal.fire({
        title: 'Processing...',
        text: 'Moving stock to expiry',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/pharmacy/move-to-expiry',
        method: 'POST',
        data: {
            stock_id: stockId,
            quantity: quantity,
            reason: reason,
            remarks: remarks
        },
        dataType: 'json',
        timeout: 30000,
        success: function(response) {
            console.log('Response:', response);
            if(response.success) {
                Swal.fire('Success', response.message, 'success').then(() => {
                    $('#moveToExpiryModal').modal('hide');
                    loadStocks();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.error('Response Text:', xhr.responseText);
            let errorMsg = 'Failed to move stock: ' + xhr.statusText;
            try {
                let response = JSON.parse(xhr.responseText);
                if(response.message) errorMsg = response.message;
            } catch(e) {
                errorMsg = 'Server error: ' + xhr.status;
            }
            Swal.fire('Error', errorMsg, 'error');
        }
    });
}

function resetFilters() {
    $('#searchInput').val('');
    $('#statusFilter').val('all');
    $('#sortBy').val('expiry_asc');
    $('#expiryFromDate').val('');
    $('#expiryToDate').val('');
    currentPage = 1;
    filterAndRenderStocks();
}

function openAddStockModal(medicineId = null, medicineName = null) {
    $('#addStockForm')[0].reset();
    setDefaultExpiryDate();
    $('#addStockReorderLevel').val(10);
    $('#addStockUnit').val('Strip');
    
    if(medicineId && medicineName) {
        $('#addStockMedicineId').val(medicineId);
        $('#addStockMedicineId').prop('disabled', true);
    } else {
        $('#addStockMedicineId').prop('disabled', false);
    }
    
    $('#addStockModal').modal('show');
}

function submitAddStock() {
    let medicineId = $('#addStockMedicineId').val();
    let batchNumber = $('#addStockBatchNumber').val().trim();
    let expiryDate = $('#addStockExpiryDate').val();
    let quantity = $('#addStockQuantity').val();
    let purchasePrice = $('#addStockPurchasePrice').val();
    let sellingPrice = $('#addStockSellingPrice').val();
    let location = $('#addStockLocation').val();
    let manufacturingDate = $('#addStockManufacturingDate').val();
    let reorderLevel = $('#addStockReorderLevel').val();
    let unitOfMeasure = $('#addStockUnit').val();
    
    if(!medicineId || !batchNumber || !expiryDate || !quantity || !purchasePrice || !sellingPrice) {
        Swal.fire('Error', 'Please fill all required fields', 'error');
        return;
    }
    
    Swal.fire({ title: 'Adding Stock...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    
    let formData = new FormData();
    formData.append('medicine_id', medicineId);
    formData.append('batch_number', batchNumber);
    formData.append('expiry_date', expiryDate);
    formData.append('quantity', quantity);
    formData.append('purchase_price', purchasePrice);
    formData.append('selling_price', sellingPrice);
    formData.append('location', location || '');
    formData.append('reorder_level', reorderLevel || 10);
    formData.append('unit_of_measure', unitOfMeasure);
    if(manufacturingDate) formData.append('manufacturing_date', manufacturingDate);
    
    $.ajax({
        url: BASE_URL + '/pharmacy/add-stock',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire('Success!', res.message, 'success').then(() => {
                    $('#addStockModal').modal('hide');
                    loadStocks();
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function() { Swal.fire('Error', 'Failed to add stock', 'error'); }
    });
}

function viewStockDetails(stockId) {
    let stock = allStocks.find(s => s.id == stockId);
    if(!stock) {
        Swal.fire('Error', 'Stock record not found', 'error');
        return;
    }
    
    let expiryDateFormatted = stock.expiry_date ? new Date(stock.expiry_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
    let createdDateFormatted = stock.created_at ? new Date(stock.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A';
    let unit = stock.unit_of_measure || 'Strip';
    
    let html = `
        <div class="row">
            <div class="col-12 mb-3">
                <div class="border-bottom pb-2">
                    <h6 class="text-success mb-0">${escapeHtml(stock.medicine_name)}</h6>
                    <small class="text-muted">Code: ${escapeHtml(stock.medicine_code || 'N/A')}</small>
                </div>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-muted d-block">Batch Number</small>
                <strong>${escapeHtml(stock.batch_number || 'N/A')}</strong>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-muted d-block">Expiry Date</small>
                <strong class="${stock.expiry_date < new Date().toISOString().split('T')[0] ? 'text-danger' : ''}">${expiryDateFormatted}</strong>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-muted d-block">Quantity</small>
                <strong>${parseInt(stock.quantity) || 0} ${unit}</strong>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-muted d-block">Unit of Measure</small>
                <strong>${escapeHtml(unit)}</strong>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-muted d-block">Reorder Level</small>
                <strong>${stock.reorder_level || 10} ${unit}</strong>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-muted d-block">Purchase Price</small>
                <strong>৳ ${parseFloat(stock.purchase_price || 0).toFixed(2)}</strong>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-muted d-block">Selling Price</small>
                <strong>৳ ${parseFloat(stock.selling_price || 0).toFixed(2)}</strong>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-muted d-block">Storage Location</small>
                <strong>${escapeHtml(stock.location || 'Not specified')}</strong>
            </div>
            <div class="col-md-6 mb-2">
                <small class="text-muted d-block">Date Added</small>
                <strong>${createdDateFormatted}</strong>
            </div>
        </div>
    `;
    
    $('#viewStockContent').html(html);
    $('#viewStockModal').modal('show');
}

function viewStockHistory(medicineId) {
    $('#stockHistoryModal').modal('show');
    $('#stockHistoryContent').html('<div class="text-center py-4"><div class="spinner-border text-success"></div><p class="mt-2">Loading history...</p></div>');
    
    $.ajax({
        url: BASE_URL + '/pharmacy/stock-history?id=' + medicineId,
        method: 'GET',
        dataType: 'json',
        success: function(res) {
            if(res.success && res.history && res.history.length > 0) {
                let html = '<div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-light"><tr><th>Date Added</th><th>Batch Number</th><th>Quantity</th><th>Unit</th><th>Purchase Price</th><th>Selling Price</th><th>Expiry Date</th><th>Location</th></tr></thead><tbody>';
                
                for(let h of res.history) {
                    let addedDate = h.created_at ? new Date(h.created_at).toLocaleDateString() : 'N/A';
                    let expDate = h.expiry_date ? new Date(h.expiry_date).toLocaleDateString() : 'N/A';
                    let unit = h.unit_of_measure || 'Strip';
                    html += `<tr>
                        <td>${addedDate}</td>
                        <td><code>${escapeHtml(h.batch_number || 'N/A')}</code></td>
                        <td>${parseInt(h.quantity) || 0} ${unit}</td>
                        <td>${unit}</td>
                        <td>৳ ${parseFloat(h.purchase_price || 0).toFixed(2)}</small></td>
                        <td>৳ ${parseFloat(h.selling_price || 0).toFixed(2)}</small></td>
                        <td>${expDate}</small></td>
                        <td>${escapeHtml(h.location || '-')}</small></td>
                    </tr>`;
                }
                html += '</tbody></table></div>';
                $('#stockHistoryContent').html(html);
            } else {
                $('#stockHistoryContent').html('<div class="text-center py-4 text-muted">No stock history found.</div>');
            }
        },
        error: function() { $('#stockHistoryContent').html('<div class="text-center py-4 text-danger">Error loading stock history.</div>'); }
    });
}

function deleteStock(stockId) {
    $('#deleteStockId').val(stockId);
    $('#deleteStockModal').modal('show');
}

function confirmDeleteStock() {
    let stockId = $('#deleteStockId').val();
    
    Swal.fire({ title: 'Deleting...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
    
    $.ajax({
        url: BASE_URL + '/pharmacy/delete-stock',
        method: 'POST',
        data: { stock_id: stockId },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire('Deleted!', 'Stock record deleted successfully', 'success').then(() => {
                    $('#deleteStockModal').modal('hide');
                    loadStocks();
                });
            } else {
                Swal.fire('Error', res.message || 'Failed to delete stock', 'error');
            }
        },
        error: function() { Swal.fire('Error', 'Failed to delete stock', 'error'); }
    });
}

function escapeHtml(str) {
    if(!str) return '';
    return String(str).replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}

function showError(message) {
    Swal.fire({ icon: 'error', title: 'Error', text: message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
}
</script>

<!-- Pass PHP data to JavaScript -->
<script>
var phpStocks = <?php 
    $processedStocks = [];
    if(!empty($stocks) && is_array($stocks)) {
        foreach($stocks as $stock) {
            $stock['status'] = $stock['status'] ?? 'in_stock';
            if($stock['expiry_date'] < date('Y-m-d')) {
                $stock['status'] = 'expired';
            }
            $processedStocks[] = $stock;
        }
    }
    echo json_encode($processedStocks); 
?>;
if(phpStocks && phpStocks.length > 0 && allStocks.length === 0) {
    allStocks = phpStocks;
    updateStats();
    filterAndRenderStocks();
}
</script>

<!-- Hidden modal for view stock details -->
<div id="viewStockModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Stock Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewStockContent">
                <div class="text-center py-4">Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden modal for stock history -->
<div id="stockHistoryModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i>Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="stockHistoryContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>