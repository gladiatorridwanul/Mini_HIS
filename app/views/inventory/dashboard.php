<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<style>
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        transition: transform 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-number {
        font-size: 32px;
        font-weight: 700;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 13px;
        color: #6c757d;
    }
    .bg-primary-light { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .bg-success-light { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
    .bg-warning-light { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
    .bg-info-light { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
    .alert-item {
        transition: all 0.2s;
        border-left: 3px solid #f59e0b;
        margin-bottom: 10px;
        padding: 12px;
        background: #fef3c7;
        border-radius: 8px;
    }
    .alert-item:hover {
        transform: translateX(5px);
    }
    .quick-action-btn {
        padding: 12px;
        border-radius: 12px;
        text-align: center;
        transition: all 0.2s;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
    }
    .quick-action-btn:hover {
        background: #f1f5f9;
        transform: translateY(-2px);
    }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-boxes text-success me-2"></i>Inventory Dashboard</h2>
            <p class="text-muted small mb-0">Overview of inventory items, stock levels, and alerts</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <a href="<?php echo BASE_URL; ?>/inventory/items/add" class="btn btn-success btn-sm">
                <i class="fas fa-plus me-2"></i>Add New Item
            </a>
            <a href="<?php echo BASE_URL; ?>/inventory/stock" class="btn btn-outline-primary btn-sm ms-2">
                <i class="fas fa-warehouse me-2"></i>Stock Management
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-primary-light">
                <div class="stat-number"><?php echo number_format($totalItems ?? 0); ?></div>
                <div class="stat-label">Total Items</div>
                <i class="fas fa-cubes mt-2 d-block"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-warning-light">
                <div class="stat-number"><?php echo number_format($lowStock ?? 0); ?></div>
                <div class="stat-label">Low Stock Items</div>
                <i class="fas fa-exclamation-triangle mt-2 d-block"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <div class="stat-number"><?php echo number_format($expiringSoon ?? 0); ?></div>
                <div class="stat-label">Expiring Soon</div>
                <i class="fas fa-clock mt-2 d-block"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-success-light">
                <div class="stat-number">৳ <?php echo number_format($totalValue ?? 0, 2); ?></div>
                <div class="stat-label">Total Stock Value</div>
                <i class="fas fa-dollar-sign mt-2 d-block"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Low Stock Items -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-warning text-white py-3">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert</h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if(!empty($lowStockItems) && is_array($lowStockItems)): ?>
                        <?php foreach($lowStockItems as $item): ?>
                        <div class="alert-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">Code: <?php echo htmlspecialchars($item['item_code']); ?></small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger rounded-pill px-3 py-2">
                                        <?php echo $item['current_stock']; ?> units left
                                    </span>
                                    <br>
                                    <small class="text-muted">Reorder: <?php echo $item['reorder_level']; ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/inventory/items" class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-boxes me-1"></i>View All Items
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-50"></i>
                            <p class="mb-0">All items sufficiently stocked</p>
                            <small>No low stock alerts</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/inventory/items" class="quick-action-btn d-block text-decoration-none text-dark">
                                <i class="fas fa-list fa-2x text-primary mb-2 d-block"></i>
                                <strong>Manage Items</strong>
                                <small class="text-muted d-block">View all inventory items</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/inventory/stock" class="quick-action-btn d-block text-decoration-none text-dark">
                                <i class="fas fa-warehouse fa-2x text-success mb-2 d-block"></i>
                                <strong>Stock Management</strong>
                                <small class="text-muted d-block">Track stock levels</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/inventory/stock-transfers" class="quick-action-btn d-block text-decoration-none text-dark">
                                <i class="fas fa-exchange-alt fa-2x text-info mb-2 d-block"></i>
                                <strong>Stock Transfers</strong>
                                <small class="text-muted d-block">Transfer between stores</small>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="<?php echo BASE_URL; ?>/inventory/reports" class="quick-action-btn d-block text-decoration-none text-dark">
                                <i class="fas fa-chart-line fa-2x text-warning mb-2 d-block"></i>
                                <strong>Reports</strong>
                                <small class="text-muted d-block">Inventory valuation</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Inventory Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-primary">
                            <i class="fas fa-history me-2"></i>Recent Activity
                        </h6>
                        <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody id="recentActivityTable">
                                <tr>
                                    <td colspan="5" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                        Loading activities...
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
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

$(document).ready(function() {
    loadRecentActivity();
    setInterval(loadRecentActivity, 60000);
});

function loadRecentActivity() {
    $.ajax({
        url: BASE_URL + '/inventory/recent-activity',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success && response.activities && response.activities.length > 0) {
                let html = '';
                for(let act of response.activities) {
                    let dateStr = new Date(act.created_at).toLocaleString();
                    let typeBadge = act.transaction_type === 'purchase' ? 'success' : 
                                   (act.transaction_type === 'sale' ? 'info' : 'warning');
                    html += `<tr>
                        <td><small>${dateStr}</small></td>
                        <td><strong>${escapeHtml(act.item_name)}</strong></small></td>
                        <td><span class="badge bg-${typeBadge}">${act.transaction_type}</span></small></td>
                        <td>${act.quantity} units</small></td>
                        <td><small>${act.reference_type || '-'}</small></small></td>
                    </tr>`;
                }
                $('#recentActivityTable').html(html);
            } else {
                $('#recentActivityTable').html('<tr><td colspan="5" class="text-center py-3 text-muted">No recent activities found</small></tr>');
            }
        },
        error: function() {
            $('#recentActivityTable').html('<tr><td colspan="5" class="text-center py-3 text-muted">Unable to load activities</small></tr>');
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