<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$totalRecords = isset($totalRecords) ? $totalRecords : 0;
$totalPages = ceil($totalRecords / $limit);
$currentPage = $page;
?>

<style>
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        transition: transform 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-number { font-size: 24px; font-weight: 700; }
    .stat-label { font-size: 12px; color: #6c757d; }
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .status-ordered { background: #fef3c7; color: #d97706; }
    .status-sample_collected { background: #dbeafe; color: #1e40af; }
    .status-processing { background: #f3e8ff; color: #6b21a5; }
    .status-completed { background: #d1fae5; color: #10b981; }
    .priority-badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 500;
        display: inline-block;
    }
    .priority-stat { background: #fee2e2; color: #ef4444; }
    .priority-urgent { background: #fef3c7; color: #d97706; }
    .priority-routine { background: #e2e8f0; color: #475569; }
    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }
    .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
    .btn-sm { padding: 3px 8px; font-size: 11px; }
    .table th { font-size: 11px; background: #f8f9fa; padding: 10px 8px; }
    .table td { font-size: 12px; vertical-align: middle; padding: 10px 8px; }
    .refresh-btn { cursor: pointer; transition: all 0.2s; }
    .refresh-btn:hover { transform: rotate(180deg); }
    
    .test-names-list {
        max-width: 180px;
        font-size: 11px;
    }
    .test-names-list .test-name-item {
        display: inline-block;
        background: #f1f5f9;
        padding: 1px 6px;
        border-radius: 4px;
        margin: 1px 2px;
        font-size: 10px;
        white-space: nowrap;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .test-names-list .test-name-item.more {
        background: #e2e8f0;
        color: #475569;
        font-weight: 600;
    }
    .test-names-list .test-name-item:hover {
        background: #dbeafe;
        color: #1e40af;
        cursor: default;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 5px;
        flex-wrap: wrap;
        padding: 10px 0;
    }
    .pagination a, .pagination span {
        padding: 6px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        text-decoration: none;
        color: #374151;
        font-size: 12px;
    }
    .pagination a:hover { background: #f3f4f6; }
    .pagination .active { background: #10b981; color: white; border-color: #10b981; }
    .pagination .disabled { color: #9ca3af; cursor: not-allowed; }
    
    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
    }
    .modal.show { display: block; }
    .modal-dialog { position: relative; width: auto; margin: 1.75rem auto; max-width: 800px; }
    .modal-content { position: relative; display: flex; flex-direction: column; background-color: #fff; border: 1px solid rgba(0,0,0,.2); border-radius: 0.3rem; }
    .modal-header { display: flex; align-items: flex-start; justify-content: space-between; padding: 1rem; border-bottom: 1px solid #e5e7eb; }
    .modal-body { position: relative; flex: 1 1 auto; padding: 1rem; }
    .modal-footer { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; padding: 0.75rem; border-top: 1px solid #e5e7eb; }
    .btn-close { background: transparent url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23000'%3e%3cpath d='M.293.293a1 1 0 011.414 0L8 6.586 14.293.293a1 1 0 111.414 1.414L9.414 8l6.293 6.293a1 1 0 01-1.414 1.414L8 9.414l-6.293 6.293a1 1 0 01-1.414-1.414L6.586 8 .293 1.707a1 1 0 010-1.414z'/%3e%3c/svg%3e") center/1em auto no-repeat; border: 0; opacity: .5; width: 1em; height: 1em; cursor: pointer; }
    .bg-success { background-color: #10b981 !important; }
    .text-white { color: white !important; }
    .bill-link { text-decoration: none; font-size: 11px; color: #6c757d; }
    .bill-link:hover { color: #10b981; text-decoration: underline; }
    .discount-text { color: #ef4444; font-weight: 500; font-size: 11px; }
    .final-amount { font-weight: 700; font-size: 13px; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5><i class="fas fa-flask text-success me-2"></i>Lab Test Orders</h5>
            <p class="text-muted small mb-0">Manage all laboratory test orders and track status</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/lab/create-order" class="btn btn-success btn-sm me-2">
                <i class="fas fa-plus me-1"></i>New Order
            </a>
            <button onclick="exportOrders()" class="btn btn-info btn-sm me-2">
                <i class="fas fa-file-excel me-1"></i>Export
            </button>
            <button onclick="location.reload()" class="btn btn-secondary btn-sm refresh-btn" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number" id="statTotalOrders"><?php echo $totalRecords ?? 0; ?></div>
                <div class="stat-label">Total Orders</div>
                <i class="fas fa-flask text-primary mt-1"></i>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number" id="statPendingOrders">0</div>
                <div class="stat-label">Pending</div>
                <i class="fas fa-hourglass-half text-warning mt-1"></i>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number" id="statCompletedOrders">0</div>
                <div class="stat-label">Completed</div>
                <i class="fas fa-check-circle text-success mt-1"></i>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number" id="statTotalAmount">৳ 0</div>
                <div class="stat-label">Total Amount</div>
                <i class="fas fa-dollar-sign text-info mt-1"></i>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="row g-2">
            <div class="col-md-2">
                <label class="form-label fw-bold small">From Date</label>
                <input type="date" id="filterDateFrom" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small">To Date</label>
                <input type="date" id="filterDateTo" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small">Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="ordered">Ordered</option>
                    <option value="sample_collected">Sample Collected</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fw-bold small">Priority</label>
                <select id="filterPriority" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="routine">Routine</option>
                    <option value="urgent">Urgent</option>
                    <option value="stat">STAT</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">Search</label>
                <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Order #, Patient or Doctor">
            </div>
            <div class="col-md-1">
                <label class="form-label fw-bold small">&nbsp;</label>
                <button class="btn btn-primary btn-sm w-100" onclick="loadOrders()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-12">
                <button class="btn btn-secondary btn-sm" onclick="resetFilters()"><i class="fas fa-undo me-1"></i>Reset Filters</button>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="card shadow">
        <div class="card-header bg-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 small"><i class="fas fa-list me-2"></i>Lab Orders List</h6>
                <span class="badge bg-secondary" id="recordCount"><?php echo isset($orders) ? count($orders) : 0; ?> records</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="13%">Order # / Bill #</th>
                            <th width="15%">Patient</th>
                            <th width="12%">Doctor</th>
                            <th width="8%">Date</th>
                            <th width="20%">Tests</th>
                            <th width="7%">Priority</th>
                            <th width="10%">Status</th>
                            <th width="10%">Bill Amount</th>
                            <th width="5%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <?php if(isset($orders) && !empty($orders)): ?>
    <?php foreach($orders as $order): ?>
    <tr>
        <td>
            <div>
                <strong class="text-info"><?php echo htmlspecialchars($order['order_number']); ?></strong>
                <?php if(isset($order['bill_number']) && $order['bill_number']): ?>
                <br><a href="<?php echo BASE_URL; ?>/bills/view/<?php echo $order['bill_id']; ?>" target="_blank" class="bill-link" title="View Bill">📄 <?php echo htmlspecialchars($order['bill_number']); ?></a>
                <?php else: ?>
                <br><span class="text-muted" style="font-size: 10px;">No bill generated</span>
                <?php endif; ?>
            </div>
        </td>
        <td>
            <strong><?php echo htmlspecialchars($order['patient_name']); ?></strong>
            <br><small class="text-muted"><?php echo htmlspecialchars($order['patient_code'] ?? ''); ?></small>
        </td>
        <td>Dr. <?php echo htmlspecialchars($order['doctor_name']); ?></td>
        <td><?php echo $order['order_date'] ?? '-'; ?></td>
        <td>
            <?php 
            $testNames = isset($order['test_names']) ? $order['test_names'] : '';
            $testCount = isset($order['test_count']) ? (int)$order['test_count'] : 0;
            
            if(!empty($testNames)):
                $testArray = explode(', ', $testNames);
                $testArray = array_filter(array_map('trim', $testArray));
                $displayCount = min(count($testArray), 3);
            ?>
            <div class="test-names-list">
                <?php for($i = 0; $i < $displayCount; $i++): ?>
                    <span class="test-name-item" title="<?php echo htmlspecialchars($testArray[$i]); ?>">
                        <?php echo htmlspecialchars(strlen($testArray[$i]) > 25 ? substr($testArray[$i], 0, 22) . '...' : $testArray[$i]); ?>
                    </span>
                <?php endfor; ?>
                <?php if(count($testArray) > 3): ?>
                    <span class="test-name-item more" title="+ <?php echo (count($testArray) - 3); ?> more tests">
                        +<?php echo (count($testArray) - 3); ?>
                    </span>
                <?php endif; ?>
                <br>
                <small class="text-muted"><?php echo count($testArray); ?> test<?php echo count($testArray) > 1 ? 's' : ''; ?></small>
            </div>
            <?php elseif($testCount > 0): ?>
            <div class="test-names-list">
                <span class="test-name-item"><?php echo $testCount; ?> test<?php echo $testCount > 1 ? 's' : ''; ?></span>
                <br>
                <small class="text-muted"><?php echo $testCount; ?> test<?php echo $testCount > 1 ? 's' : ''; ?></small>
            </div>
            <?php else: ?>
                <span class="text-muted">No tests</span>
            <?php endif; ?>
        </td>
        <td>
            <span class="priority-badge <?php 
                echo $order['priority'] == 'stat' ? 'priority-stat' : 
                    ($order['priority'] == 'urgent' ? 'priority-urgent' : 'priority-routine'); 
            ?>">
                <?php echo strtoupper($order['priority'] ?? 'ROUTINE'); ?>
            </span>
        </td>
        <td>
            <span class="status-badge status-<?php echo $order['status'] ?? 'ordered'; ?>">
                <?php echo strtoupper(str_replace('_', ' ', $order['status'] ?? 'ORDERED')); ?>
            </span>
        </td>
        <td>
            <?php 
                $billAmount = isset($order['bill_amount']) ? (float)$order['bill_amount'] : 0;
                $discountAmount = isset($order['discount_amount']) ? (float)$order['discount_amount'] : 0;
                $discountPercentage = isset($order['discount_percentage']) ? (float)$order['discount_percentage'] : 0;
                $finalAmount = $billAmount; // bill_amount is already the final amount
                $originalAmount = $finalAmount + $discountAmount;
                $paymentStatus = isset($order['payment_status']) ? $order['payment_status'] : 'pending';
            ?>
            <div>
                <?php if($finalAmount > 0): ?>
                <div class="final-amount text-success">৳ <?php echo number_format($finalAmount, 2); ?></div>
                <?php if($discountAmount > 0 || $discountPercentage > 0): ?>
                <div>
                    <span style="text-decoration: line-through; color: #999; font-size: 11px;">৳ <?php echo number_format($originalAmount, 2); ?></span>
                    <span class="discount-text">- ৳ <?php echo number_format($discountAmount, 2); ?></span>
                    <?php if($discountPercentage > 0): ?>
                    <span class="discount-text"> (<?php echo $discountPercentage; ?>% off)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <small class="text-muted"><?php echo strtoupper($paymentStatus); ?></small>
                <?php else: ?>
                <span class="text-muted">No bill</span>
                <?php endif; ?>
            </div>
        </td>
        <td>
            <div class="action-buttons">
                <button onclick="viewOrder(<?php echo $order['id']; ?>)" class="btn btn-sm btn-outline-primary" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <?php if(($order['status'] ?? '') == 'ordered'): ?>
                <button onclick="collectSample(<?php echo $order['id']; ?>)" class="btn btn-sm btn-outline-success" title="Collect Sample">
                    <i class="fas fa-syringe"></i>
                </button>
                <?php endif; ?>
                <?php if(($order['status'] ?? '') == 'completed' && isset($order['bill_id']) && $order['bill_id']): ?>
                <a href="<?php echo BASE_URL; ?>/bills/view/<?php echo $order['bill_id']; ?>" target="_blank" class="btn btn-sm btn-outline-warning" title="View Bill">
                    <i class="fas fa-file-invoice"></i>
                </a>
                <?php endif; ?>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="9" class="text-center py-4 text-muted">No lab orders found. Click "New Order" to create one.</td>
    </tr>
<?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="row mt-3">
        <div class="col-12">
            <nav aria-label="Order pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&date_from=<?php echo urlencode($dateFrom ?? ''); ?>&date_to=<?php echo urlencode($dateTo ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&priority=<?php echo urlencode($priority ?? ''); ?>&search=<?php echo urlencode($search ?? ''); ?>" 
                           aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    $queryParams = '&date_from=' . urlencode($dateFrom ?? '') . '&date_to=' . urlencode($dateTo ?? '') . '&status=' . urlencode($status ?? '') . '&priority=' . urlencode($priority ?? '') . '&search=' . urlencode($search ?? '');
                    
                    if($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1' . $queryParams . '">1</a></li>';
                        if($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    for($i = $startPage; $i <= $endPage; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">';
                        echo '<a class="page-link" href="?page=' . $i . $queryParams . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    
                    if($endPage < $totalPages) {
                        if($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . $queryParams . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $queryParams; ?>" 
                           aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="row mt-2">
        <div class="col-12 text-center">
            <small class="text-muted">
                Page <?php echo $page; ?> of <?php echo $totalPages; ?> 
                (Total <?php echo $totalRecords; ?> orders)
            </small>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title"><i class="fas fa-flask me-2"></i>Order Details</h6>
                <button type="button" class="btn-close btn-close-white" onclick="closeOrderModal()"></button>
            </div>
            <div class="modal-body">
                <div id="orderDetailsContent">
                    <div class="text-center py-4">Loading...</div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closeOrderModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let currentPage = <?php echo $page; ?>;
let totalPages = <?php echo $totalPages; ?>;

$(document).ready(function() {
    loadOrders();
});

function loadOrders() {
    let params = {
        page: currentPage,
        date_from: $('#filterDateFrom').val(),
        date_to: $('#filterDateTo').val(),
        status: $('#filterStatus').val(),
        priority: $('#filterPriority').val(),
        search: $('#filterSearch').val()
    };
    
    $('#ordersTableBody').html(`
        <tr><td colspan="9" class="text-center py-4">
            <div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div>
            <p class="mt-2 text-muted">Loading orders...</p>
        </td></tr>
    `);
    
    $.ajax({
        url: BASE_URL + '/api/lab-orders-list',
        method: 'GET',
        data: params,
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if(response && response.success === true) {
                if(response.orders && Array.isArray(response.orders)) {
                    renderOrdersTable(response.orders);
                } else {
                    renderOrdersTable([]);
                }
                updateStats(response.stats);
                renderPagination(response);
                $('#recordCount').text((response.orders ? response.orders.length : 0) + ' records');
            } else {
                let errorMsg = response && response.message ? response.message : 'Failed to load orders';
                showError(errorMsg);
                $('#ordersTableBody').html(`<tr><td colspan="9" class="text-center py-4 text-danger">${errorMsg}<br><button class="btn btn-sm btn-outline-danger mt-2" onclick="loadOrders()">Retry</button></td></tr>`);
            }
        },
        error: function() {
            let errorMsg = 'Error loading orders. Please refresh the page.';
            $('#ordersTableBody').html(`<tr><td colspan="9" class="text-center py-4 text-danger">${errorMsg}<br><button class="btn btn-sm btn-outline-danger mt-2" onclick="loadOrders()">Retry</button></td></tr>`);
            showError(errorMsg);
        }
    });
}

function renderOrdersTable(orders) {
    if(!orders || orders.length === 0) {
        $('#ordersTableBody').html(`<tr><td colspan="9" class="text-center py-4 text-muted">No lab orders found. Click "New Order" to create one.</td></tr>`);
        return;
    }
    
    let html = '';
    for(let order of orders) {
        let priorityClass = order.priority == 'stat' ? 'priority-stat' : (order.priority == 'urgent' ? 'priority-urgent' : 'priority-routine');
        let priorityText = order.priority ? order.priority.toUpperCase() : 'ROUTINE';
        let statusClass = order.status;
        let statusText = order.status ? order.status.replace('_', ' ').toUpperCase() : 'ORDERED';
        
        // ============================================================
        // FIX: Get amounts correctly from bill data
        // ============================================================
        let billAmount = order.bill_amount ? parseFloat(order.bill_amount) : 0;
        let discountAmount = order.discount_amount ? parseFloat(order.discount_amount) : 0;
        let discountPercentage = order.discount_percentage ? parseFloat(order.discount_percentage) : 0;
        
        // The bill_amount from the bill is already the FINAL amount after discount and tax
        // So we should display it as the final amount
        let finalAmount = billAmount;
        
        // Calculate subtotal (before discount) if we have discount info
        let subtotal = 0;
        if(discountPercentage > 0 && finalAmount > 0) {
            // If we have discount percentage, we can calculate subtotal
            // But we don't have tax info here, so just use bill_amount as final
            subtotal = finalAmount + discountAmount;
        } else if(discountAmount > 0 && finalAmount > 0) {
            subtotal = finalAmount + discountAmount;
        } else {
            subtotal = finalAmount;
        }
        
        let paymentStatus = order.payment_status || 'pending';
        let billNumber = order.bill_number ? order.bill_number : '';
        
        // ============================================================
        // Test names display
        // ============================================================
        let testNamesHtml = '';
        let testNames = order.test_names || '';
        let testCount = order.test_count || 0;
        
        if(testNames && testNames !== '') {
            let testArray = [];
            if(testNames.includes(', ')) {
                testArray = testNames.split(', ');
            } else if(testNames.includes(',')) {
                testArray = testNames.split(',');
            } else {
                testArray = [testNames];
            }
            
            testArray = testArray.map(function(name) {
                return name.trim();
            }).filter(function(name) {
                return name && name !== '';
            });
            
            let displayCount = Math.min(testArray.length, 3);
            let testNameItems = '';
            
            for(let i = 0; i < displayCount; i++) {
                let displayName = testArray[i].length > 25 ? testArray[i].substring(0, 22) + '...' : testArray[i];
                testNameItems += `<span class="test-name-item" title="${escapeHtml(testArray[i])}">${escapeHtml(displayName)}</span> `;
            }
            
            if(testArray.length > 3) {
                testNameItems += `<span class="test-name-item more" title="+ ${testArray.length - 3} more tests">+${testArray.length - 3}</span>`;
            }
            
            testNamesHtml = `
                <div class="test-names-list">
                    ${testNameItems}
                    <br>
                    <small class="text-muted">${testArray.length} test${testArray.length > 1 ? 's' : ''}</small>
                </div>
            `;
        } else {
            if(testCount > 0) {
                testNamesHtml = `
                    <div class="test-names-list">
                        <span class="test-name-item">${testCount} test${testCount > 1 ? 's' : ''}</span>
                        <br>
                        <small class="text-muted">${testCount} test${testCount > 1 ? 's' : ''}</small>
                    </div>
                `;
            } else {
                testNamesHtml = `<span class="text-muted">No tests</span>`;
            }
        }
        
        // ============================================================
        // FIX: Bill amount display - show final amount and discount info
        // ============================================================
        let billDisplayHtml = '';
        if(finalAmount > 0) {
            billDisplayHtml = `<div class="final-amount text-success">৳ ${finalAmount.toFixed(2)}</div>`;
            
            // Show original price and discount if discount exists
            if(discountAmount > 0 || discountPercentage > 0) {
                let originalAmount = finalAmount + discountAmount;
                billDisplayHtml += `
                    <div>
                        <span style="text-decoration: line-through; color: #999; font-size: 11px;">৳ ${originalAmount.toFixed(2)}</span>
                        <span class="discount-text"> - ৳ ${discountAmount.toFixed(2)}</span>
                        ${discountPercentage > 0 ? `<span class="discount-text"> (${discountPercentage}% off)</span>` : ''}
                    </div>
                `;
            }
            billDisplayHtml += `<small class="text-muted">${paymentStatus.toUpperCase()}</small>`;
        } else {
            billDisplayHtml = `<span class="text-muted">No bill</span>`;
        }
        
        html += `<tr>
            <td>
                <div>
                    <strong class="text-info">${escapeHtml(order.order_number)}</strong>
                    ${billNumber ? `<br><a href="${BASE_URL}/bills/view/${order.bill_id}" target="_blank" class="bill-link" title="View Bill">📄 ${escapeHtml(billNumber)}</a>` : '<br><span class="text-muted" style="font-size: 10px;">No bill generated</span>'}
                </div>
            </td>
            <td>
                <strong>${escapeHtml(order.patient_name)}</strong>
                <br><small class="text-muted">${escapeHtml(order.patient_code || '')}</small>
            </td>
            <td>Dr. ${escapeHtml(order.doctor_name)}</td>
            <td>${order.order_date || '-'}</td>
            <td>${testNamesHtml}</td>
            <td><span class="priority-badge ${priorityClass}">${priorityText}</span></td>
            <td><span class="status-badge status-${statusClass}">${statusText}</span></td>
            <td>${billDisplayHtml}</td>
            <td>
                <div class="action-buttons">
                    <button onclick="viewOrder(${order.id})" class="btn btn-sm btn-outline-primary" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${order.status == 'ordered' ? `<button onclick="collectSample(${order.id})" class="btn btn-sm btn-outline-success" title="Collect Sample">
                        <i class="fas fa-syringe"></i>
                    </button>` : ''}
                    ${order.status == 'completed' && order.bill_id ? `<a href="${BASE_URL}/bills/view/${order.bill_id}" target="_blank" class="btn btn-sm btn-outline-warning" title="View Bill">
                        <i class="fas fa-file-invoice"></i>
                    </a>` : ''}
                </div>
            </td>
        </tr>`;
    }
    $('#ordersTableBody').html(html);
}

function updateStats(stats) {
    if(stats) {
        $('#statTotalOrders').text(stats.total_orders || 0);
        $('#statPendingOrders').text(stats.pending_orders || 0);
        $('#statCompletedOrders').text(stats.completed_orders || 0);
        $('#statTotalAmount').text('৳ ' + (parseFloat(stats.total_amount || 0).toFixed(2)));
    }
}

function renderPagination(response) {
    totalPages = response.total_pages || 1;
    let current = response.current_page || currentPage;
    
    if(totalPages <= 1) {
        $('#paginationContainer').hide();
        return;
    }
    
    let html = '';
    
    if(current > 1) {
        html += `<a href="#" onclick="goToPage(${current - 1})">&laquo; Previous</a>`;
    } else {
        html += `<span class="disabled">&laquo; Previous</span>`;
    }
    
    let start = Math.max(1, current - 2);
    let end = Math.min(totalPages, current + 2);
    
    if(start > 1) {
        html += `<a href="#" onclick="goToPage(1)">1</a>`;
        if(start > 2) html += `<span>...</span>`;
    }
    
    for(let i = start; i <= end; i++) {
        if(i == current) {
            html += `<span class="active">${i}</span>`;
        } else {
            html += `<a href="#" onclick="goToPage(${i})">${i}</a>`;
        }
    }
    
    if(end < totalPages) {
        if(end < totalPages - 1) html += `<span>...</span>`;
        html += `<a href="#" onclick="goToPage(${totalPages})">${totalPages}</a>`;
    }
    
    if(current < totalPages) {
        html += `<a href="#" onclick="goToPage(${current + 1})">Next &raquo;</a>`;
    } else {
        html += `<span class="disabled">Next &raquo;</span>`;
    }
    
    $('#paginationContainer').html(html).show();
}

function goToPage(page) {
    currentPage = page;
    loadOrders();
    $('html, body').animate({ scrollTop: 0 }, 300);
}

function resetFilters() {
    $('#filterDateFrom').val('');
    $('#filterDateTo').val('');
    $('#filterStatus').val('');
    $('#filterPriority').val('');
    $('#filterSearch').val('');
    currentPage = 1;
    loadOrders();
}

function exportOrders() {
    let params = new URLSearchParams();
    if($('#filterDateFrom').val()) params.append('date_from', $('#filterDateFrom').val());
    if($('#filterDateTo').val()) params.append('date_to', $('#filterDateTo').val());
    if($('#filterStatus').val()) params.append('status', $('#filterStatus').val());
    if($('#filterPriority').val()) params.append('priority', $('#filterPriority').val());
    if($('#filterSearch').val()) params.append('search', $('#filterSearch').val());
    
    window.location.href = BASE_URL + '/lab/export-orders?' + params.toString();
}

// ============================================================
// FIXED: viewOrder() - Amounts now match the table
// ============================================================
function viewOrder(orderId) {
    $('#orderModal').addClass('show').css('display', 'block');
    $('#orderDetailsContent').html(`<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading order details...</p></div>`);
    
    $.ajax({
        url: BASE_URL + '/api/lab-order-details/' + orderId,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let order = response.order;
                let items = response.items || [];
                let bill = response.bill;
                
                let totalTestPrice = 0;
                let itemsHtml = '';
                
                if(items.length > 0) {
                    items.forEach(function(item) {
                        let price = parseFloat(item.price || 0);
                        totalTestPrice += price;
                        let testName = item.test_name || 'Unknown Test';
                        let categoryName = item.category_name || 'Lab Test';
                        let barcode = item.sample_barcode || '-';
                        let resultValue = item.result_value || 'Pending';
                        
                        itemsHtml += `<tr>
                            <td><strong>${escapeHtml(testName)}</strong></td>
                            <td>${escapeHtml(categoryName)}</td>
                            <td><code>${escapeHtml(barcode)}</code></td>
                            <td>৳ ${price.toFixed(2)}</td>
                            <td class="${resultValue != 'Pending' ? 'fw-bold' : 'text-muted'}">${escapeHtml(resultValue)}</td>
                        </tr>`;
                    });
                } else {
                    itemsHtml = `<tr><td colspan="5" class="text-center text-muted">No test items found</td></tr>`;
                }
                
                // ============================================================
                // FIX: Use bill data for consistent amounts
                // ============================================================
                let subtotal = bill ? parseFloat(bill.subtotal || 0) : totalTestPrice;
                let discountAmount = bill ? parseFloat(bill.discount_amount || 0) : 0;
                let discountPercentage = bill ? parseFloat(bill.discount_percentage || 0) : 0;
                let taxAmount = bill ? parseFloat(bill.tax_amount || 0) : 0;
                let billAmount = bill ? parseFloat(bill.total_amount || 0) : 0;
                let paidAmount = bill ? parseFloat(bill.paid_amount || 0) : 0;
                let dueAmount = bill ? parseFloat(bill.balance_amount || 0) : billAmount;
                let paymentStatus = bill ? bill.payment_status : 'pending';
                let billNumber = bill ? bill.bill_number : 'Not generated';
                let billDate = bill ? bill.bill_date : '-';
                
                // If bill amount is 0 but we have test prices, use calculated total
                if(billAmount == 0 && totalTestPrice > 0) {
                    let calculatedDiscount = totalTestPrice * (discountPercentage / 100);
                    let calculatedTax = (totalTestPrice - calculatedDiscount) * 0.05;
                    billAmount = totalTestPrice - calculatedDiscount + calculatedTax;
                    dueAmount = billAmount;
                }
                
                let finalDisplayAmount = billAmount - discountAmount;
                
                let html = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="border-bottom pb-2 mb-2">
                                <h6 class="text-primary mb-0">Order Information</h6>
                            </div>
                            <table class="table table-sm table-borderless">
                                <tr><td width="40%"><strong>Order #:</strong></td><td><strong>${escapeHtml(order.order_number)}</strong></td></tr>
                                <tr><td><strong>Order Date:</strong></td><td>${order.order_date || '-'}</td></tr>
                                <tr><td><strong>Priority:</strong></td><td>${order.priority ? order.priority.toUpperCase() : 'ROUTINE'}</td></tr>
                                <tr><td><strong>Status:</strong></td><td>${order.status ? order.status.replace('_', ' ').toUpperCase() : 'ORDERED'}</td></tr>
                                <tr><td><strong>Bill #:</strong></td><td>${bill ? `<a href="${BASE_URL}/bills/view/${bill.id}" target="_blank">${bill.bill_number}</a>` : 'Not generated'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <div class="border-bottom pb-2 mb-2">
                                <h6 class="text-primary mb-0">Patient & Doctor</h6>
                            </div>
                            <table class="table table-sm table-borderless">
                                <tr><td width="40%"><strong>Patient:</strong></td><td><strong>${escapeHtml(order.patient_name)}</strong></td></tr>
                                <tr><td><strong>Phone:</strong></td><td>${escapeHtml(order.phone || '-')}</td></tr>
                                <tr><td><strong>Patient Code:</strong></td><td>${escapeHtml(order.patient_code || '-')}</td></tr>
                                <tr><td><strong>Doctor:</strong></td><td>Dr. ${escapeHtml(order.doctor_name)}</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="border-bottom pb-2 mb-2">
                        <h6 class="text-primary mb-0">Test Items</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr><th>Test Name</th><th>Category</th><th>Barcode</th><th>Price</th><th>Result</th></tr>
                            </thead>
                            <tbody>${itemsHtml}</tbody>
                            <tfoot class="table-light">
                                <tr><td colspan="3" class="text-end"><strong>Subtotal:</strong></td><td><strong>৳ ${subtotal.toFixed(2)}</strong></td></tr>
                                ${discountAmount > 0 ? `<tr><td colspan="3" class="text-end"><strong>Discount:</strong></td><td class="text-danger"><strong>- ৳ ${discountAmount.toFixed(2)}</strong>${discountPercentage > 0 ? ` (${discountPercentage}%)` : ''}</td></tr>` : ''}
                                <tr><td colspan="3" class="text-end"><strong>Tax:</strong></td><td><strong>৳ ${taxAmount.toFixed(2)}</strong></td></tr>
                                <tr><td colspan="3" class="text-end"><strong>Grand Total:</strong></td><td><strong class="text-success">৳ ${billAmount.toFixed(2)}</strong></td></tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="border-bottom pb-2 mb-2">
                        <h6 class="text-primary mb-0">Bill Information</h6>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <td width="25%"><strong>Bill Number:</strong></td>
                                    <td>${bill ? bill.bill_number : 'Not generated'}</td>
                                    <td width="25%"><strong>Bill Date:</strong></td>
                                    <td>${bill ? bill.bill_date : '-'}</td>
                                </tr>
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td>৳ ${subtotal.toFixed(2)}</td>
                                    <td><strong>Discount:</strong></td>
                                    <td class="text-danger">- ৳ ${discountAmount.toFixed(2)}${discountPercentage > 0 ? ` (${discountPercentage}%)` : ''}</td>
                                </tr>
                                <tr>
                                    <td><strong>Total Amount:</strong></td>
                                    <td class="text-success fw-bold">৳ ${billAmount.toFixed(2)}</td>
                                    <td><strong>Paid Amount:</strong></td>
                                    <td>৳ ${paidAmount.toFixed(2)}</td>
                                </tr>
                                <tr>
                                    <td><strong>Due Amount:</strong></td>
                                    <td class="text-danger fw-bold">৳ ${dueAmount.toFixed(2)}</td>
                                    <td><strong>Payment Status:</strong></td>
                                    <td><span class="status-badge status-${paymentStatus}">${paymentStatus.toUpperCase()}</span></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                `;
                
                $('#orderDetailsContent').html(html);
            } else {
                $('#orderDetailsContent').html(`<div class="text-center py-4 text-danger">${response.message || 'Failed to load order details'}</div>`);
            }
        },
        error: function() {
            $('#orderDetailsContent').html(`<div class="text-center py-4 text-danger">Error loading order details</div>`);
        }
    });
}

function closeOrderModal() {
    $('#orderModal').removeClass('show').css('display', 'none');
}

function collectSample(orderId) {
    Swal.fire({
        title: 'Collect Sample',
        text: 'Generate barcode for sample collection?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, collect'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Collecting...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            $.ajax({
                url: BASE_URL + '/lab/collect-sample',
                method: 'POST',
                data: { order_id: orderId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire({ title: 'Sample Collected', html: 'Barcode: <strong>' + response.barcode + '</strong>', icon: 'success' });
                        loadOrders();
                    } else {
                        Swal.fire('Error', response.message || 'Failed to collect sample', 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Failed to collect sample', 'error'); }
            });
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

function showError(message) {
    Swal.fire({ icon: 'error', title: 'Error', text: message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
}
</script>
</body>
</html>