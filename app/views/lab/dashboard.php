<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        transition: transform 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        margin-bottom: 20px;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-number { font-size: 32px; font-weight: 700; margin-bottom: 5px; }
    .stat-label { font-size: 13px; color: #6c757d; }
    .card-custom {
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 24px;
        background: white;
    }
    .card-header-custom {
        background: white;
        border-bottom: 2px solid #10b981;
        padding: 15px 20px;
        font-weight: 600;
        font-size: 18px;
    }
    .badge-stat {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    .badge-stat.ordered { background: #f59e0b; color: white; }
    .badge-stat.sample_collected { background: #3b82f6; color: white; }
    .badge-stat.processing { background: #8b5cf6; color: white; }
    .badge-stat.completed { background: #10b981; color: white; }
    .badge-stat.reviewed { background: #06b6d4; color: white; }
    .badge-stat.delivered { background: #6b7280; color: white; }
    .priority-stat { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; }
    .priority-stat.stat { background: #fee2e2; color: #ef4444; }
    .priority-stat.urgent { background: #fef3c7; color: #d97706; }
    .priority-stat.routine { background: #e2e8f0; color: #475569; }
    .quick-action-btn { transition: all 0.2s; }
    .quick-action-btn:hover { transform: translateY(-2px); }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-microscope text-success me-2"></i>Laboratory Dashboard</h2>
            <p class="text-muted mb-0">Manage lab test orders, sample collection, and results</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <a href="<?php echo BASE_URL; ?>/lab/create-order" class="btn btn-success me-2">
                <i class="fas fa-plus me-2"></i>New Lab Order
            </a>
            <a href="<?php echo BASE_URL; ?>/lab/enter-results" class="btn btn-primary">
                <i class="fas fa-edit me-2"></i>Enter Results
            </a>
            <a href="<?php echo BASE_URL; ?>/lab/sample-collection" class="btn btn-info">
                <i class="fas fa-syringe me-2"></i>Sample Collection
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number text-warning"><?php echo $pendingTests ?? 0; ?></div>
                        <div class="stat-label">Pending Tests</div>
                    </div>
                    <i class="fas fa-hourglass-half fa-2x text-warning opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number text-primary"><?php echo $inProgressTests ?? 0; ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                    <i class="fas fa-spinner fa-pulse fa-2x text-primary opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number text-success"><?php echo $completedToday ?? 0; ?></div>
                        <div class="stat-label">Completed Today</div>
                    </div>
                    <i class="fas fa-check-circle fa-2x text-success opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-number text-info"><?php echo count($testCategories ?? []); ?></div>
                        <div class="stat-label">Test Categories</div>
                    </div>
                    <i class="fas fa-list fa-2x text-info opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Row -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card-custom">
                <div class="card-body py-3">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                        <div class="fw-bold text-muted">Quick Actions:</div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?php echo BASE_URL; ?>/lab/orders" class="btn btn-sm btn-outline-primary quick-action-btn">
                                <i class="fas fa-list me-1"></i>View All Orders
                            </a>
                            <a href="<?php echo BASE_URL; ?>/lab/reports" class="btn btn-sm btn-outline-success quick-action-btn">
                                <i class="fas fa-file-alt me-1"></i>Lab Reports
                            </a>
                            <button onclick="location.reload()" class="btn btn-sm btn-outline-secondary quick-action-btn">
                                <i class="fas fa-sync-alt me-1"></i>Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card-custom">
                <div class="card-header-custom">
                    <i class="fas fa-flask me-2"></i>Recent Lab Orders
                    <span class="badge bg-secondary rounded-pill ms-2" id="orderCount">0</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Tests</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="labOrdersTable">
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="spinner-border text-success" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading orders...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <!-- Quick Sample Collection -->
            <div class="card-custom mb-4">
                <div class="card-header-custom">
                    <i class="fas fa-qrcode me-2"></i>Quick Sample Collection
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="barcodeScan" placeholder="Scan sample barcode" autocomplete="off">
                        <button class="btn btn-primary" onclick="processBarcode()">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    <div id="sampleInfo" class="mt-3"></div>
                    <div class="text-center mt-3">
                        <small class="text-muted">Or</small>
                        <br>
                        <a href="<?php echo BASE_URL; ?>/lab/sample-collection" class="btn btn-sm btn-outline-info mt-2">
                            <i class="fas fa-list me-1"></i>Go to Sample Collection
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- STAT Orders -->
            <div class="card-custom">
                <div class="card-header-custom bg-danger text-white" style="border-bottom-color: white;">
                    <i class="fas fa-exclamation-triangle me-2"></i>STAT Orders
                    <span class="badge bg-light text-danger rounded-pill ms-2" id="statCount">0</span>
                </div>
                <div class="card-body p-0" id="statOrders" style="max-height: 350px; overflow-y: auto;">
                    <div class="text-center py-4">
                        <div class="spinner-border spinner-border-sm text-danger" role="status"></div>
                        <p class="mt-2 text-muted">Loading STAT orders...</p>
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
let autoRefreshInterval;

function loadLabOrders() {
    $.ajax({
        url: BASE_URL + '/api/lab-orders',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let html = '';
            if(data && data.length > 0) {
                data.forEach(function(order) {
                    let statusClass = order.status;
                    let priorityClass = order.priority == 'stat' ? 'stat' : (order.priority == 'urgent' ? 'urgent' : 'routine');
                    let statusBadge = getStatusBadge(order.status);
                    
                    html += `<tr>
                        <td><strong class="text-info">${escapeHtml(order.order_number)}</strong></td>
                        <td>${escapeHtml(order.patient_name)}</td>
                        <td>Dr. ${escapeHtml(order.doctor_name)}</td>
                        <td><span class="badge bg-secondary">${order.test_count} tests</span></td>
                        <td><span class="priority-stat ${priorityClass}">${order.priority.toUpperCase()}</span></td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="${BASE_URL}/lab/orders/${order.id}" class="btn btn-sm btn-outline-primary" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                ${order.status == 'ordered' ? `<button class="btn btn-sm btn-outline-success" onclick="collectSample(${order.id})" title="Collect Sample">
                                    <i class="fas fa-syringe"></i>
                                </button>` : ''}
                                ${order.status == 'sample_collected' || order.status == 'processing' ? `<button class="btn btn-sm btn-outline-info" onclick="enterResultsForOrder(${order.id})" title="Enter Results">
                                    <i class="fas fa-edit"></i>
                                </button>` : ''}
                            </div>
                        </td>
                    </tr>`;
                });
                $('#orderCount').text(data.length);
            } else {
                html = `<tr><td colspan="7" class="text-center py-5">
                    <i class="fas fa-flask fa-3x text-muted mb-3 d-block"></i>
                    <p class="text-muted">No lab orders found</p>
                    <a href="${BASE_URL}/lab/create-order" class="btn btn-sm btn-success">Create First Order</a>
                </td></tr>`;
                $('#orderCount').text(0);
            }
            $('#labOrdersTable').html(html);
        },
        error: function() {
            $('#labOrdersTable').html(`<tr><td colspan="7" class="text-center py-4 text-danger">Error loading orders</td></tr>`);
        }
    });
}

function getStatusBadge(status) {
    const badges = {
        'ordered': '<span class="badge-stat ordered">ORDERED</span>',
        'sample_collected': '<span class="badge-stat sample_collected">SAMPLE COLLECTED</span>',
        'processing': '<span class="badge-stat processing">PROCESSING</span>',
        'completed': '<span class="badge-stat completed">COMPLETED</span>',
        'reviewed': '<span class="badge-stat reviewed">REVIEWED</span>',
        'delivered': '<span class="badge-stat delivered">DELIVERED</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">' + status + '</span>';
}

function loadStatOrders() {
    $.ajax({
        url: BASE_URL + '/api/stat-orders',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let html = '';
            if(data && data.length > 0) {
                data.forEach(function(order) {
                    html += `<div class="alert alert-danger m-2 p-2 border-left border-danger">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${escapeHtml(order.patient_name)}</strong>
                                <br><small class="text-muted">${order.test_names || 'Multiple tests'}</small>
                                <br><small class="text-muted">Ordered: ${order.order_time}</small>
                            </div>
                            <a href="${BASE_URL}/lab/orders/${order.id}" class="btn btn-sm btn-outline-light">View</a>
                        </div>
                    </div>`;
                });
                $('#statCount').text(data.length);
            } else {
                html = '<div class="text-center text-muted py-4"><i class="fas fa-check-circle fa-2x mb-2"></i><p>No STAT orders</p></div>';
                $('#statCount').text(0);
            }
            $('#statOrders').html(html);
        },
        error: function() {
            $('#statOrders').html('<div class="text-center text-danger py-4">Error loading STAT orders</div>');
        }
    });
}

function processBarcode() {
    let barcode = $('#barcodeScan').val().trim();
    if(!barcode) {
        Swal.fire('Info', 'Please scan or enter a barcode', 'info');
        return;
    }
    
    Swal.fire({
        title: 'Processing...',
        text: 'Validating barcode',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/api/validate-barcode',
        method: 'POST',
        data: {barcode: barcode},
        dataType: 'json',
        success: function(response) {
            Swal.close();
            if(response.success) {
                let data = response.data;
                let statusColor = data.status == 'pending' ? 'warning' : (data.status == 'sample_collected' ? 'info' : 'success');
                let statusText = data.status == 'pending' ? 'Pending Collection' : (data.status == 'sample_collected' ? 'Collected' : 'Processed');
                
                $('#sampleInfo').html(`
                    <div class="alert alert-success">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>Sample Found!</strong><br>
                                <span class="text-muted">Patient:</span> ${escapeHtml(data.patient_name)}<br>
                                <span class="text-muted">Test:</span> ${escapeHtml(data.test_name)}<br>
                                <span class="text-muted">Status:</span> <span class="badge bg-${statusColor}">${statusText}</span><br>
                                <span class="text-muted">Order #:</span> ${data.order_number}
                            </div>
                            ${data.status == 'pending' ? `<button class="btn btn-sm btn-primary mt-2" onclick="collectSampleByBarcode('${barcode}')">
                                <i class="fas fa-syringe me-1"></i>Mark as Collected
                            </button>` : ''}
                        </div>
                    </div>
                `);
            } else {
                $('#sampleInfo').html(`<div class="alert alert-danger">Sample not found: ${response.message || 'Invalid barcode'}</div>`);
            }
        },
        error: function() {
            Swal.close();
            $('#sampleInfo').html('<div class="alert alert-danger">Error validating barcode</div>');
        }
    });
    $('#barcodeScan').val('');
}

function collectSample(orderId) {
    Swal.fire({
        title: 'Collect Sample',
        text: 'Generate barcode for sample collection?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, collect',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({
                title: 'Collecting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: BASE_URL + '/lab/collect-sample',
                method: 'POST',
                data: {order_id: orderId},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Sample Collected!',
                            html: 'Barcode: <strong>' + response.barcode + '</strong>',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            loadLabOrders();
                            loadStatOrders();
                        });
                        printBarcode(response.barcode);
                    } else {
                        Swal.fire('Error', response.message || 'Failed to collect sample', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to collect sample', 'error');
                }
            });
        }
    });
}

function collectSampleByBarcode(barcode) {
    Swal.fire({
        title: 'Collecting...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/api/collect-by-barcode',
        method: 'POST',
        data: {barcode: barcode},
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire('Success', 'Sample collected successfully', 'success');
                $('#sampleInfo').html('');
                loadLabOrders();
                loadStatOrders();
            } else {
                Swal.fire('Error', response.message || 'Failed to collect sample', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to collect sample', 'error');
        }
    });
}

function enterResultsForOrder(orderId) {
    window.location.href = BASE_URL + '/lab/enter-results?order_id=' + orderId;
}

function printBarcode(barcode) {
    let win = window.open('', '_blank');
    win.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Barcode</title>
            <style>
                @page { size: 80mm auto; margin: 0; }
                body { font-family: monospace; width: 80mm; margin: 0 auto; padding: 10px; text-align: center; }
                .header { border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 15px; }
                .title { font-size: 14px; font-weight: bold; }
                .barcode-box { border: 2px solid #000; padding: 15px; margin: 15px 0; }
                .barcode { font-size: 24px; font-weight: bold; letter-spacing: 2px; }
                .footer { margin-top: 15px; border-top: 1px dashed #000; padding-top: 8px; font-size: 9px; }
                @media print { .no-print { display: none; } }
                button { margin: 10px; padding: 8px 16px; cursor: pointer; background: #10b981; color: white; border: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">UNIDIA HOSPITAL</div>
                <div>Sample Barcode</div>
            </div>
            <div class="barcode-box">
                <div class="barcode">${barcode}</div>
            </div>
            <div class="footer">Collection Date: ${new Date().toLocaleDateString()}<br>Collector Signature: _________________</div>
            <div class="no-print"><button onclick="window.print()">Print</button><button onclick="window.close()">Close</button></div>
            <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); }<\/script>
        </body>
        </html>
    `);
    win.document.close();
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

$(document).ready(function() {
    loadLabOrders();
    loadStatOrders();
    
    autoRefreshInterval = setInterval(function() {
        loadLabOrders();
        loadStatOrders();
    }, 30000);
    
    $('#barcodeScan').on('keypress', function(e) {
        if(e.which === 13) processBarcode();
    });
});
</script>