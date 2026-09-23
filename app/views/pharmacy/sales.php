<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<style>
    .sale-status { 
        padding: 4px 12px; 
        border-radius: 20px; 
        font-size: 12px; 
        font-weight: 500; 
        display: inline-block;
    }
    .status-completed { background: #d1fae5; color: #10b981; }
    .status-canceled { background: #fee2e2; color: #ef4444; }
    .status-refunded { background: #fef3c7; color: #f59e0b; }
    .status-paid { background: #d1fae5; color: #10b981; }
    .status-pending { background: #fee2e2; color: #ef4444; }
    .status-partial { background: #fef3c7; color: #f59e0b; }
    .filter-bar { background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
    .sales-table th { background: #f8fafc; }
    .sales-table td { vertical-align: middle; }
    .sale-type-badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 500;
        display: inline-block;
    }
    .type-pos { background: #10b981; color: white; }
    .type-manual { background: #f59e0b; color: white; }
    .clickable-link { cursor: pointer; color: #0d6efd; text-decoration: none; }
    .clickable-link:hover { text-decoration: underline; }
    .pagination { display: flex; justify-content: center; margin-top: 20px; gap: 5px; flex-wrap: wrap; }
    .pagination a, .pagination span { padding: 6px 12px; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #374151; font-size: 12px; }
    .pagination a:hover { background: #f3f4f6; }
    .pagination .active { background: #10b981; color: white; border-color: #10b981; }
    .pagination .disabled { color: #9ca3af; cursor: not-allowed; }
    .product-list { max-height: 400px; overflow-y: auto; }
    .product-item { padding: 8px; border-bottom: 1px solid #e5e7eb; }
    .product-item:last-child { border-bottom: none; }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-history text-success me-2"></i>Sales History</h2>
            <p class="text-muted small mb-0">View and manage all pharmacy sales transactions (POS & Manual)</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <a href="<?php echo BASE_URL; ?>/pharmacy/pos" class="btn btn-success btn-sm">
                <i class="fas fa-cash-register me-2"></i>New POS Sale
            </a>
            <a href="<?php echo BASE_URL; ?>/bills/create" class="btn btn-info btn-sm ms-2">
                <i class="fas fa-plus me-2"></i>Manual Bill
            </a>
            <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm ms-2">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar shadow-sm">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted">Search</label>
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search by sale/bill number, patient...">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Sale Type</label>
                <select id="typeFilter" class="form-select form-select-sm">
                    <option value="all">All Types</option>
                    <option value="pos">POS Sale</option>
                    <option value="manual">Manual Bill</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">Status</label>
                <select id="statusFilter" class="form-select form-select-sm">
                    <option value="all">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="partial">Partial</option>
                    <option value="canceled">Canceled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Date Range</label>
                <input type="date" id="dateFilter" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-outline-secondary btn-sm w-100" onclick="resetFilters()">
                    <i class="fas fa-undo-alt me-2"></i>Reset
                </button>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-list me-2"></i>Sales Transactions</h6>
                <span class="badge bg-secondary rounded-pill" id="saleCount">0 records</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover sales-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="8%">Type</th>
                            <th width="15%">Sale/Bill #</th>
                            <th width="20%">Patient</th>
                            <th width="15%">Date & Time</th>
                            <th width="8%">Items</th>
                            <th width="10%">Subtotal</th>
                            <th width="8%">Discount</th>
                            <th width="10%">Total</th>
                            <th width="10%">Payment</th>
                            <th width="8%">Status</th>
                            <th width="8%">Action</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        <tr id="loadingRow">
                            <td colspan="11" class="text-center py-5">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading sales data...</p>
                            </small>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
    <div id="paginationContainer" class="pagination" style="display: none;"></div>
</div>

<!-- Sale Details Modal -->
<div class="modal fade" id="saleDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Sale Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="saleDetailsContent">
                <div class="text-center py-4">Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printCurrentSale()">
                    <i class="fas fa-print me-2"></i>Print Receipt/Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let currentPage = 1;
let totalPages = 1;
let currentSaleDetail = null;

$(document).ready(function() {
    loadSales();
    setupEventListeners();
});

function setupEventListeners() {
    $('#searchInput').on('keyup', function() { currentPage = 1; loadSales(); });
    $('#statusFilter').on('change', function() { currentPage = 1; loadSales(); });
    $('#typeFilter').on('change', function() { currentPage = 1; loadSales(); });
    $('#dateFilter').on('change', function() { currentPage = 1; loadSales(); });
}

function loadSales() {
    let search = $('#searchInput').val();
    let status = $('#statusFilter').val();
    let type = $('#typeFilter').val();
    let date = $('#dateFilter').val();
    
    $('#salesTableBody').html(`<tr><td colspan="11" class="text-center py-4"><div class="spinner-border text-success"></div><p class="mt-2">Loading...</p></td>`);
    
    $.ajax({
        url: BASE_URL + '/api/sales-list',
        method: 'GET',
        data: {
            page: currentPage,
            search: search,
            status: status,
            type: type,
            date: date
        },
        dataType: 'json',
        timeout: 15000,
        success: function(response) {
            if(response.success) {
                if(response.sales && response.sales.length > 0) {
                    renderSalesTable(response.sales);
                    updatePagination(response);
                    $('#saleCount').text(response.total + ' records');
                } else {
                    renderEmptyState();
                    $('#saleCount').text('0 records');
                }
            } else {
                renderEmptyState();
                showError(response.message || 'Failed to load sales');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            renderEmptyState();
            showError('Error loading sales. Please refresh the page.');
        }
    });
}

function renderSalesTable(sales) {
    let html = '';
    for(let sale of sales) {
        let dateStr = sale.created_at ? new Date(sale.created_at).toLocaleString() : (sale.sale_date ? new Date(sale.sale_date).toLocaleString() : 'N/A');
        
        let typeBadge = sale.sale_type === 'pos' ? 
            '<span class="sale-type-badge type-pos"><i class="fas fa-cash-register me-1"></i>POS</span>' : 
            '<span class="sale-type-badge type-manual"><i class="fas fa-file-invoice me-1"></i>Manual</span>';
        
        let documentNumber = sale.sale_number || sale.bill_number || 'N/A';
        let patientName = sale.patient_name || 'Walk-in Customer';
        let phone = sale.phone || '';
        let itemCount = sale.item_count || 0;
        let subtotal = parseFloat(sale.subtotal || 0);
        let discountPercent = sale.discount_percentage || 0;
        let totalAmount = parseFloat(sale.total_amount || 0);
        let paymentMethod = sale.payment_method || 'cash';
        let saleStatus = sale.status || (sale.payment_status || 'completed');
        
        let paymentBadgeClass = paymentMethod === 'cash' ? 'success' : (paymentMethod === 'card' ? 'primary' : 'info');
        let statusClass = '';
        if(saleStatus === 'completed' || saleStatus === 'paid') statusClass = 'status-completed';
        else if(saleStatus === 'canceled') statusClass = 'status-canceled';
        else if(saleStatus === 'pending') statusClass = 'status-pending';
        else if(saleStatus === 'partial') statusClass = 'status-partial';
        else statusClass = 'status-refunded';
        
        let statusDisplay = saleStatus === 'paid' ? 'PAID' : (saleStatus === 'completed' ? 'COMPLETED' : saleStatus.toUpperCase());
        
        html += `<tr>
            <td>${typeBadge}</small></td>
            <td>
                <a href="javascript:void(0)" onclick="viewSaleDetails('${sale.sale_type}', ${sale.id})" class="clickable-link fw-bold">
                    ${escapeHtml(documentNumber)}
                </a>
            </small></td>
            <td>
                <strong>${escapeHtml(patientName)}</strong>
                ${phone ? `<br><small class="text-muted">${escapeHtml(phone)}</small>` : ''}
            </small></td>
            <td><small>${dateStr}</small></small></td>
            <td><span class="badge bg-secondary">${itemCount} items</span></small></td>
            <td>৳ ${subtotal.toFixed(2)}</small></td>
            <td>${discountPercent ? discountPercent + '%' : '-'}</small></td>
            <td class="text-success fw-bold">৳ ${totalAmount.toFixed(2)}</small></td>
            <td><span class="badge bg-${paymentBadgeClass}">${paymentMethod.toUpperCase()}</span></small></td>
            <td><span class="sale-status ${statusClass}">${statusDisplay}</span></small></td>
            <td>
                <div class="btn-group" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewSaleDetails('${sale.sale_type}', ${sale.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-info" onclick="printDocument('${sale.sale_type}', ${sale.id})" title="Print">
                        <i class="fas fa-print"></i>
                    </button>
                </div>
            </small>
        </tr>`;
    }
    $('#salesTableBody').html(html);
}

function renderEmptyState() {
    $('#salesTableBody').html(`<tr><td colspan="11" class="text-center py-5">
        <i class="fas fa-receipt fa-4x text-muted mb-3 d-block"></i>
        <p class="text-muted">No sales records found</p>
        <div class="mt-2">
            <a href="${BASE_URL}/pharmacy/pos" class="btn btn-sm btn-success me-2">
                <i class="fas fa-cash-register me-2"></i>New POS Sale
            </a>
            <a href="${BASE_URL}/bills/create" class="btn btn-sm btn-info">
                <i class="fas fa-plus me-2"></i>Manual Bill
            </a>
        </div>
    </small><tr>`);
}

function updatePagination(response) {
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
    loadSales();
    $('html, body').animate({ scrollTop: 0 }, 300);
}

function resetFilters() {
    $('#searchInput').val('');
    $('#statusFilter').val('all');
    $('#typeFilter').val('all');
    $('#dateFilter').val('');
    currentPage = 1;
    loadSales();
}

function viewSaleDetails(type, id) {
    $('#saleDetailsModal').modal('show');
    $('#saleDetailsContent').html('<div class="text-center py-4"><div class="spinner-border text-success"></div><p>Loading sale details...</p></div>');
    
    let url = type === 'pos' ? BASE_URL + '/pharmacy/sale-details/' + id : BASE_URL + '/api/bill-details/' + id;
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                currentSaleDetail = { type: type, id: id, data: response };
                renderSaleDetails(response, type);
            } else {
                $('#saleDetailsContent').html('<div class="text-center py-4 text-danger">Failed to load sale details</div>');
            }
        },
        error: function() {
            $('#saleDetailsContent').html('<div class="text-center py-4 text-danger">Error loading sale details</div>');
        }
    });
}

function renderSaleDetails(data, type) {
    let sale = data.sale || data;
    let items = data.items || [];
    
    let dateStr = sale.created_at ? new Date(sale.created_at).toLocaleString() : (sale.sale_date ? new Date(sale.sale_date).toLocaleString() : 'N/A');
    let subtotal = 0;
    let itemsHtml = '';
    
    for(let item of items) {
        let itemTotal = parseFloat(item.total_amount || (item.quantity * item.unit_price));
        subtotal += itemTotal;
        
        // Get the correct item name
        let itemName = item.item_name_display || item.item_name || item.medicine_name || item.description || 'Product';
        let strength = item.strength || '';
        
        itemsHtml += `<tr>
            <td>
                <strong>${escapeHtml(itemName)}</strong>
                ${strength ? `<br><small class="text-muted">${escapeHtml(strength)}</small>` : ''}
                ${item.medicine_code ? `<br><small class="text-muted">Code: ${escapeHtml(item.medicine_code)}</small>` : ''}
            </small></td>
            <td class="text-center">${item.quantity}</small></td>
            <td class="text-end">৳ ${parseFloat(item.unit_price || 0).toFixed(2)}</small></td>
            <td class="text-end fw-bold">৳ ${itemTotal.toFixed(2)}</small></td>
        </tr>`;
    }
    
    let discountAmount = parseFloat(sale.discount_amount || 0);
    let taxAmount = parseFloat(sale.tax_amount || 0);
    let totalAmount = parseFloat(sale.total_amount || 0);
    let paidAmount = parseFloat(sale.paid_amount || 0);
    let balanceAmount = totalAmount - paidAmount;
    
    let documentNumber = sale.sale_number || sale.bill_number || 'N/A';
    let patientName = sale.patient_name || 'Walk-in Customer';
    let phone = sale.phone || '';
    let cashierName = sale.cashier_name || (sale.created_by_name || 'System');
    let paymentMethod = sale.payment_method || 'cash';
    let saleStatus = sale.status || sale.payment_status || 'completed';
    
    let statusClass = (saleStatus === 'completed' || saleStatus === 'paid') ? 'status-completed' : 
                     (saleStatus === 'canceled' ? 'status-canceled' : 'status-pending');
    let statusDisplay = saleStatus === 'paid' ? 'PAID' : (saleStatus === 'completed' ? 'COMPLETED' : saleStatus.toUpperCase());
    
    let html = `
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="border-bottom pb-2 mb-2">
                    <h6 class="text-success mb-0">${type === 'pos' ? 'Sale' : 'Bill'} Information</h6>
                </div>
                <table class="table table-sm table-borderless">
                    <tr><td width="40%"><strong>${type === 'pos' ? 'Sale #:' : 'Bill #:'}</strong></td><td>${escapeHtml(documentNumber)}</small></tr>
                    <tr><td><strong>Date:</strong></td><td>${dateStr}</small></tr>
                    <tr><td><strong>Status:</strong></td><td><span class="sale-status ${statusClass}">${statusDisplay}</span></small></tr>
                    <tr><td><strong>Payment:</strong></td><td>${paymentMethod.toUpperCase()}</small></tr>
                </table>
            </div>
            <div class="col-md-6">
                <div class="border-bottom pb-2 mb-2">
                    <h6 class="text-success mb-0">Customer Information</h6>
                </div>
                <table class="table table-sm table-borderless">
                    <tr><td width="40%"><strong>Patient:</strong></td><td>${escapeHtml(patientName)}</small></tr>
                    <tr><td><strong>Phone:</strong></td><td>${escapeHtml(phone || '-')}</small></tr>
                    <tr><td><strong>Created By:</strong></td><td>${escapeHtml(cashierName)}</small></tr>
                </table>
            </div>
        </div>
        <div class="border-bottom pb-2 mb-2">
            <h6 class="text-success mb-0">Items Purchased</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr><th>Product Name</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Total</th></tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
                <tfoot class="table-light">
                    <tr><td colspan="3" class="text-end"><strong>Subtotal:</strong></td><td class="text-end">৳ ${subtotal.toFixed(2)}</small></tr>
                    ${discountAmount > 0 ? `<tr><td colspan="3" class="text-end"><strong>Discount:</strong></td><td class="text-end text-danger">- ৳ ${discountAmount.toFixed(2)}</small><tr>` : ''}
                    ${taxAmount > 0 ? `<tr><td colspan="3" class="text-end"><strong>Tax:</strong></td><td class="text-end">+ ৳ ${taxAmount.toFixed(2)}</small></tr>` : ''}
                    <tr class="table-success"><td colspan="3" class="text-end"><strong>Total:</strong></td><td class="text-end fw-bold">৳ ${totalAmount.toFixed(2)}</small></tr>
                    ${paidAmount > 0 ? `<tr><td colspan="3" class="text-end"><strong>Paid Amount:</strong></td><td class="text-end text-success">- ৳ ${paidAmount.toFixed(2)}</small></tr>` : ''}
                    ${balanceAmount > 0 ? `<tr class="table-danger"><td colspan="3" class="text-end"><strong>Balance Due:</strong></td><td class="text-end fw-bold">৳ ${balanceAmount.toFixed(2)}</small></tr>` : ''}
                </tfoot>
            </table>
        </div>
    `;
    
    $('#saleDetailsContent').html(html);
}

function printDocument(type, id) {
    if(type === 'pos') {
        window.open(BASE_URL + '/pharmacy/invoice/' + id, '_blank');
    } else {
        window.open(BASE_URL + '/bills/print/' + id, '_blank');
    }
}

function printCurrentSale() {
    if(currentSaleDetail) {
        printDocument(currentSaleDetail.type, currentSaleDetail.id);
    }
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
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
}
</script>