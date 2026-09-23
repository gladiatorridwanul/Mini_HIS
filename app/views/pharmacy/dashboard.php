<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<style>
    .dashboard-card {
        transition: transform 0.3s;
        border: none;
        border-radius: 16px;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
    }
    .stat-number {
        font-size: 32px;
        font-weight: 700;
    }
    .prescription-table th {
        background: #f8fafc;
    }
    .prescription-table td {
        vertical-align: middle;
    }
    .alert-card {
        transition: all 0.2s;
    }
    .alert-card:hover {
        transform: translateX(5px);
    }
    .stock-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    .stock-critical {
        background: #fee2e2;
        color: #ef4444;
    }
    .stock-warning {
        background: #fef3c7;
        color: #d97706;
    }
    .stock-normal {
        background: #d1fae5;
        color: #10b981;
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-pills text-success me-2"></i>Pharmacy Dashboard</h2>
            <p class="text-muted small mb-0">Overview of pharmacy operations, prescriptions, and inventory alerts</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <a href="<?php echo BASE_URL; ?>/pharmacy/pos" class="btn btn-success">
                <i class="fas fa-cash-register me-2"></i>Open POS
            </a>
            <a href="<?php echo BASE_URL; ?>/pharmacy/medicines" class="btn btn-outline-primary ms-2">
                <i class="fas fa-pills me-2"></i>Manage Medicines
            </a>
            <a href="<?php echo BASE_URL; ?>/pharmacy/stock" class="btn btn-outline-info ms-2">
                <i class="fas fa-boxes me-2"></i>Stock Management
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4">
        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Pending Prescriptions</h6>
                            <h2 class="stat-number mb-0"><?php echo isset($pendingPrescriptions) ? count($pendingPrescriptions) : 0; ?></h2>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="fas fa-prescription fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3 small">
                        <span class="text-white-50">Ready for dispensing</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Today's Sales</h6>
                            <h2 class="stat-number mb-0">৳ <?php echo number_format(isset($todaySales['total']) ? $todaySales['total'] : 0, 2); ?></h2>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3 small">
                        <span class="text-white-50">Sales from today</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-card bg-warning text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Low Stock Items</h6>
                            <h2 class="stat-number mb-0"><?php echo isset($lowStockItems) ? count($lowStockItems) : 0; ?></h2>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3 small">
                        <span class="text-white-50">Need reordering</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card dashboard-card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Expiring Soon</h6>
                            <h2 class="stat-number mb-0"><?php echo isset($expiringItems) ? count($expiringItems) : 0; ?></h2>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3">
                            <i class="fas fa-hourglass-half fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3 small">
                        <span class="text-white-50">Within 30 days</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Row -->
    <div class="row mt-4 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <div>
                            <span class="fw-bold text-muted">Quick Actions:</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?php echo BASE_URL; ?>/pharmacy/medicines" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-plus me-1"></i>Add Medicine
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pharmacy/stock" class="btn btn-sm btn-outline-info">
                                <i class="fas fa-boxes me-1"></i>Manage Stock
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pharmacy/sales" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-history me-1"></i>Sales History
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pharmacy/prescriptions" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-prescription me-1"></i>E-Prescriptions
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Prescriptions Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-primary">
                    <i class="fas fa-prescription me-2"></i>Pending E-Prescriptions
                </h6>
                <span class="badge bg-info rounded-pill"><?php echo isset($pendingPrescriptions) ? count($pendingPrescriptions) : 0; ?> pending</span>
            </div>
        </div>
        <div class="card-body">
            <?php if(isset($pendingPrescriptions) && !empty($pendingPrescriptions)): ?>
                <div class="table-responsive">
                    <table class="table table-hover prescription-table">
                        <thead>
                            <tr class="table-light">
                                <th>Rx Number</th>
                                <th>Patient Name</th>
                                <th>Phone</th>
                                <th>Doctor</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pendingPrescriptions as $rx): ?>
                            <tr>
                                <td><strong class="text-info"><?php echo htmlspecialchars($rx['prescription_number'] ?? 'N/A'); ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($rx['patient_name'] ?? $rx['first_name'] . ' ' . $rx['last_name']); ?></strong>
                                    <?php if(!empty($rx['patient_code'])): ?>
                                    <br><small class="text-muted">ID: <?php echo htmlspecialchars($rx['patient_code']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($rx['phone'] ?? '-'); ?></td>
                                <td>Dr. <?php echo htmlspecialchars($rx['doctor_name'] ?? ''); ?></td>
                                <td><?php echo isset($rx['prescription_date']) ? date('d M Y', strtotime($rx['prescription_date'])) : date('d M Y', strtotime($rx['created_at'])); ?></td>
                                <td>
                                    <span class="badge bg-secondary rounded-pill">
                                        <?php echo $rx['item_count'] ?? 0; ?> items
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/pharmacy/dispense/<?php echo $rx['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-pills me-1"></i>Dispense
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-4x mb-3 text-success opacity-50"></i>
                    <p class="mb-0">No pending prescriptions</p>
                    <small>All prescriptions have been dispensed</small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Low Stock & Expiring Items -->
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header py-3 bg-warning bg-opacity-10 border-warning">
                    <h6 class="m-0 fw-bold text-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                    </h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if(isset($lowStockItems) && !empty($lowStockItems)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($lowStockItems as $item): ?>
                            <div class="list-group-item alert-card border-start border-warning border-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <strong class="text-dark"><?php echo htmlspecialchars($item['item_name'] ?? $item['medicine_name'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-barcode me-1"></i>Batch: <?php echo htmlspecialchars($item['batch_number'] ?? 'N/A'); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger rounded-pill px-3 py-2">
                                            <?php echo $item['quantity'] ?? 0; ?> units left
                                        </span>
                                        <br>
                                        <small class="text-muted">Reorder: <?php echo $item['reorder_level'] ?? 10; ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="<?php echo BASE_URL; ?>/pharmacy/stock" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-boxes me-1"></i>View All Stock
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-4x mb-3 text-success opacity-50"></i>
                            <p class="mb-0">All items sufficiently stocked</p>
                            <small>No low stock alerts</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header py-3 bg-danger bg-opacity-10 border-danger">
                    <h6 class="m-0 fw-bold text-danger">
                        <i class="fas fa-clock me-2"></i>Expiring Soon (30 days)
                    </h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if(isset($expiringItems) && !empty($expiringItems)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($expiringItems as $item): ?>
                            <div class="list-group-item alert-card border-start border-danger border-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="flex-grow-1">
                                        <strong class="text-dark"><?php echo htmlspecialchars($item['item_name'] ?? $item['medicine_name'] ?? 'N/A'); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-barcode me-1"></i>Batch: <?php echo htmlspecialchars($item['batch_number'] ?? 'N/A'); ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-warning rounded-pill px-3 py-2">
                                            <?php echo date('d M Y', strtotime($item['expiry_date'])); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted"><?php echo $item['quantity'] ?? 0; ?> units left</small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="<?php echo BASE_URL; ?>/pharmacy/stock" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-exclamation-triangle me-1"></i>View Expiring Items
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-calendar-check fa-4x mb-3 text-success opacity-50"></i>
                            <p class="mb-0">No items expiring soon</p>
                            <small>All products have valid expiry dates</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Sales Preview -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold text-primary">
                            <i class="fas fa-receipt me-2"></i>Recent Sales
                        </h6>
                        <a href="<?php echo BASE_URL; ?>/pharmacy/sales" class="btn btn-sm btn-outline-primary">
                            View All <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Sale #</th>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Payment</th>
                                </tr>
                            </thead>
                            <tbody id="recentSalesTable">
                                <tr>
                                    <td colspan="6" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-success" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        Loading recent sales...
                                    </small>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

$(document).ready(function() {
    loadRecentSales();
    
    // Auto refresh every 30 seconds
    setInterval(function() {
        loadRecentSales();
        refreshDashboardStats();
    }, 30000);
});

function loadRecentSales() {
    $.ajax({
        url: BASE_URL + '/pharmacy/recent-sales',
        method: 'GET',
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if(response.success && response.sales && response.sales.length > 0) {
                let html = '';
                for(let sale of response.sales) {
                    let dateStr = new Date(sale.created_at).toLocaleString('en-US', { 
                        day: 'numeric', 
                        month: 'short', 
                        hour: '2-digit', 
                        minute: '2-digit' 
                    });
                    let paymentClass = sale.payment_method === 'cash' ? 'success' : 'info';
                    let itemCount = sale.item_count || 0;
                    
                    html += `<tr>
                        <td><strong class="text-info">${escapeHtml(sale.sale_number)}</strong></td>
                        <td><small>${dateStr}</small></small></td>
                        <td>${escapeHtml(sale.patient_name || 'Walk-in Customer')}</small></td>
                        <td><span class="badge bg-secondary">${itemCount} items</span></small></td>
                        <td class="text-success fw-bold">৳ ${parseFloat(sale.total_amount || 0).toFixed(2)}</small></td>
                        <td><span class="badge bg-${paymentClass}">${(sale.payment_method || 'cash').toUpperCase()}</span></small></td>
                    </tr>`;
                }
                $('#recentSalesTable').html(html);
            } else {
                $('#recentSalesTable').html('<tr><td colspan="6" class="text-center py-3 text-muted">No recent sales found</small></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading recent sales:', error);
            $('#recentSalesTable').html('<tr><td colspan="6" class="text-center py-3 text-muted">Unable to load recent sales</small></tr>');
        }
    });
}

function refreshDashboardStats() {
    // Refresh low stock and expiring items counts
    $.ajax({
        url: BASE_URL + '/pharmacy/dashboard-stats',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                if(response.low_stock_count !== undefined) {
                    $('.bg-warning .stat-number').text(response.low_stock_count);
                }
                if(response.expiring_count !== undefined) {
                    $('.bg-danger .stat-number').text(response.expiring_count);
                }
                if(response.today_sales !== undefined) {
                    $('.bg-success .stat-number').text('৳ ' + response.today_sales.toFixed(2));
                }
            }
        },
        error: function() {
            console.log('Could not refresh dashboard stats');
        }
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
</script>