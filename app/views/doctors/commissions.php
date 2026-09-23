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

// Calculate statistics
$totalCommission = 0;
$paidCommission = 0;
$pendingCommission = 0;
$approvedCommission = 0;

if(isset($commissions) && !empty($commissions)) {
    $totalCommission = array_sum(array_column($commissions, 'commission_amount'));
    $paidCommission = array_sum(array_filter($commissions, function($c) { return isset($c['status']) && $c['status'] == 'paid'; }));
    $pendingCommission = array_sum(array_filter($commissions, function($c) { return isset($c['status']) && $c['status'] == 'pending'; }));
    $approvedCommission = array_sum(array_filter($commissions, function($c) { return isset($c['status']) && $c['status'] == 'approved'; }));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Commissions - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        .commission-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }
        .commission-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .bg-gradient-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .bg-gradient-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .bg-gradient-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-approved { background: #dbeafe; color: #1e40af; }
        .status-pending { background: #fed7aa; color: #9a3412; }
        .commission-table th { background: #f8fafc; font-weight: 600; font-size: 13px; }
        .filter-bar { background: #f8fafc; border-radius: 16px; padding: 15px 20px; margin-bottom: 20px; }
        .btn-group .btn { padding: 4px 8px; font-size: 11px; }
        .detail-card { background: white; border-radius: 16px; padding: 20px; margin-bottom: 15px; border: 1px solid #e5e7eb; }
        .detail-label { font-size: 12px; color: #6c757d; font-weight: 600; margin-bottom: 5px; }
        .detail-value { font-size: 14px; font-weight: 500; color: #1f2937; }
        .action-btn { margin: 2px; padding: 5px 10px; font-size: 11px; }
        
        /* Pagination */
        .pagination .page-link {
            padding: 6px 12px;
            font-size: 14px;
        }
        .pagination .active .page-link {
            background-color: #10b981;
            border-color: #10b981;
            color: white;
        }
        .pagination .page-link:hover {
            background-color: #f1f5f9;
        }
        .pagination .active .page-link:hover {
            background-color: #059669;
            border-color: #059669;
            color: white;
        }
        
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .commission-table th, .commission-table td { font-size: 11px; padding: 8px; }
            .pagination .page-link { padding: 4px 8px; font-size: 12px; }
            .action-btn { font-size: 10px; padding: 3px 6px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h4 class="mb-1"><i class="fas fa-percent text-primary me-2"></i>Doctor Commissions</h4>
            <p class="text-muted mb-0">Manage and track doctor commission earnings</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <button class="btn btn-success me-2" onclick="exportCommissions()">
                <i class="fas fa-file-excel me-2"></i>Export
            </button>
            <button class="btn btn-primary" onclick="showReportModal()">
                <i class="fas fa-chart-line me-2"></i>Commission Report
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card commission-card bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Total Commission</h6>
                            <h2 class="mb-0">৳ <?php echo number_format($totalCommission, 2); ?></h2>
                            <small class="opacity-75">All time earnings</small>
                        </div>
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card commission-card bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Settled Commission</h6>
                            <h2 class="mb-0">৳ <?php echo number_format($paidCommission, 2); ?></h2>
                            <small class="opacity-75">Payments completed</small>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card commission-card bg-gradient-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Pending / Approved</h6>
                            <h2 class="mb-0">৳ <?php echo number_format($pendingCommission + $approvedCommission, 2); ?></h2>
                            <small class="opacity-75">Awaiting settlement</small>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card commission-card bg-gradient-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 opacity-75">Transactions</h6>
                            <h2 class="mb-0"><?php echo $totalRecords; ?></h2>
                            <small class="opacity-75">Total records</small>
                        </div>
                        <i class="fas fa-receipt fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="row align-items-end">
            <div class="col-md-2 mb-2 mb-md-0">
                <label class="form-label">Doctor</label>
                <select id="doctorFilter" class="form-select" onchange="filterCommissions()">
                    <option value="">All Doctors</option>
                    <?php if(isset($doctors) && !empty($doctors)): ?>
                        <?php foreach($doctors as $doc): ?>
                            <option value="<?php echo $doc['id']; ?>">Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <label class="form-label">Status</label>
                <select id="statusFilter" class="form-select" onchange="filterCommissions()">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="paid">Settled</option>
                </select>
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <label class="form-label">Date From</label>
                <input type="date" id="dateFrom" class="form-control" onchange="filterCommissions()">
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <label class="form-label">Date To</label>
                <input type="date" id="dateTo" class="form-control" onchange="filterCommissions()">
            </div>
            <div class="col-md-2 mb-2 mb-md-0">
                <label class="form-label">Type</label>
                <select id="typeFilter" class="form-select" onchange="filterCommissions()">
                    <option value="">All</option>
                    <option value="consultation">Consultation</option>
                    <option value="lab_test">Lab Test</option>
                    <option value="pharmacy">Pharmacy</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" onclick="resetFilters()">
                    <i class="fas fa-undo me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Commissions Table -->
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Commission Transactions</h5>
            <span class="badge bg-secondary" id="recordCount"><?php echo isset($commissions) ? count($commissions) : 0; ?> records</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover commission-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">#</th>
                            <th>Doctor</th>
                            <th>Type</th>
                            <th>Service</th>
                            <th>Ref ID</th>
                            <th>Amount (BDT)</th>
                            <th>Commission %</th>
                            <th>Commission (BDT)</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="commissionTableBody">
                        <?php if(empty($commissions)): ?>
                            <tr id="noDataRow">
                                <td colspan="11" class="text-center text-muted py-5">
                                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                    No commission records found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $counter = $offset + 1; ?>
                            <?php foreach($commissions as $c): ?>
                            <tr data-status="<?php echo $c['status'] ?? 'pending'; ?>" 
                                data-type="<?php echo $c['reference_type'] ?? ''; ?>" 
                                data-date="<?php echo isset($c['created_at']) ? date('Y-m-d', strtotime($c['created_at'])) : ''; ?>" 
                                data-doctor="<?php echo $c['doctor_id'] ?? ''; ?>"
                                data-id="<?php echo $c['id']; ?>">
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <strong>Dr. <?php 
                                        $firstName = isset($c['first_name']) ? $c['first_name'] : 'Unknown';
                                        $lastName = isset($c['last_name']) ? $c['last_name'] : '';
                                        echo htmlspecialchars($firstName . ' ' . $lastName); 
                                    ?></strong>
                                    <br><small class="text-muted">ID: <?php echo $c['doctor_id'] ?? 'N/A'; ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        $refType = $c['reference_type'] ?? '';
                                        echo $refType == 'consultation' ? 'primary' : ($refType == 'lab_test' ? 'info' : 'success'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $refType)); ?>
                                    </span>
                                </td>
                                <td><?php echo isset($c['service_name']) ? htmlspecialchars($c['service_name']) : '-'; ?></td>
                                <td>#<?php echo $c['reference_id'] ?? 'N/A'; ?></td>
                                <td class="text-end">৳ <?php echo number_format($c['amount'] ?? 0, 2); ?></td>
                                <td class="text-end"><?php echo $c['commission_percentage'] ?? 0; ?>%</td>
                                <td class="text-end"><strong class="text-success">৳ <?php echo number_format($c['commission_amount'] ?? 0, 2); ?></strong></td>
                                <td><?php echo isset($c['created_at']) ? date('d M Y', strtotime($c['created_at'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $c['status'] ?? 'pending'; ?>">
                                        <?php 
                                            $statusLabels = [
                                                'pending' => 'Pending',
                                                'approved' => 'Approved',
                                                'paid' => 'Settled'
                                            ];
                                            echo $statusLabels[$c['status'] ?? 'pending'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-info action-btn" onclick="viewCommissionDetails(<?php echo $c['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <?php if(($c['status'] ?? '') == 'pending'): ?>
                                            <button class="btn btn-success action-btn" onclick="approveCommission(<?php echo $c['id']; ?>)" title="Approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        <?php elseif(($c['status'] ?? '') == 'approved'): ?>
                                            <button class="btn btn-primary action-btn" onclick="payCommission(<?php echo $c['id']; ?>)" title="Settle Payment">
                                                <i class="fas fa-money-bill"></i> Settle
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-secondary action-btn" onclick="printCommissionSlip(<?php echo $c['id']; ?>)" title="Print">
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end">Total:</th>
                            <th class="text-end" id="totalAmount">৳ 0.00</th>
                            <th></th>
                            <th class="text-end" id="totalCommission">৳ 0.00</th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="row mt-3">
        <div class="col-12">
            <nav aria-label="Commission pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous Page -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Show first page if not in range
                    if($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                        if($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Page numbers
                    for($i = $startPage; $i <= $endPage; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">';
                        echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    
                    // Show last page if not in range
                    if($endPage < $totalPages) {
                        if($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Next Page -->
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Page Info -->
    <div class="row mt-2">
        <div class="col-12 text-center">
            <small class="text-muted">
                Page <?php echo $page; ?> of <?php echo $totalPages; ?> 
                (Total <?php echo $totalRecords; ?> commission records)
            </small>
        </div>
    </div>
</div>

<!-- Commission Details Modal -->
<div class="modal fade" id="commissionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Commission Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="commissionDetails">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printCommissionDetails()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Pay Commission Modal -->
<div class="modal fade" id="payModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Settle Commission</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="payCommissionId">
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select id="paymentMethod" class="form-select" required>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="mobile_banking">Mobile Banking</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transaction ID / Reference</label>
                        <input type="text" id="transactionId" class="form-control" placeholder="Enter transaction reference">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date</label>
                        <input type="date" id="paymentDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea id="paymentNotes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="processPayment()">
                    <i class="fas fa-check me-1"></i> Confirm Settlement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-chart-line me-2"></i>Commission Report</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Report Period</label>
                        <select id="reportPeriod" class="form-select" onchange="updateReportDates()">
                            <option value="weekly">Weekly</option>
                            <option value="monthly" selected>Monthly</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Doctor (Optional)</label>
                        <select id="reportDoctor" class="form-select">
                            <option value="">All Doctors</option>
                            <?php if(isset($doctors) && !empty($doctors)): ?>
                                <?php foreach($doctors as $doc): ?>
                                    <option value="<?php echo $doc['id']; ?>">Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3" id="dateFromDiv" style="display: none;">
                        <label class="form-label">Date From</label>
                        <input type="date" id="reportDateFrom" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    <div class="col-md-6 mb-3" id="dateToDiv" style="display: none;">
                        <label class="form-label">Date To</label>
                        <input type="date" id="reportDateTo" class="form-control" value="<?php echo date('Y-m-t'); ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="generateReport()">
                    <i class="fas fa-download me-1"></i> Generate Report
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

// Filter commissions
function filterCommissions() {
    let doctor = $('#doctorFilter').val();
    let status = $('#statusFilter').val();
    let type = $('#typeFilter').val();
    let dateFrom = $('#dateFrom').val();
    let dateTo = $('#dateTo').val();
    
    let visibleCount = 0;
    $('#commissionTableBody tr').each(function() {
        if($(this).attr('id') === 'noDataRow') return;
        
        let show = true;
        let rowDoctor = $(this).data('doctor');
        let rowStatus = $(this).data('status');
        let rowType = $(this).data('type');
        let rowDate = $(this).data('date');
        
        if(doctor && rowDoctor != doctor) show = false;
        if(status && rowStatus != status) show = false;
        if(type && rowType != type) show = false;
        if(dateFrom && rowDate < dateFrom) show = false;
        if(dateTo && rowDate > dateTo) show = false;
        
        if(show) {
            $(this).show();
            visibleCount++;
        } else {
            $(this).hide();
        }
    });
    
    $('#recordCount').text(visibleCount + ' records');
    calculateTotals();
}

// Calculate totals
function calculateTotals() {
    let totalAmount = 0;
    let totalCommission = 0;
    
    $('#commissionTableBody tr:visible').each(function() {
        if($(this).attr('id') === 'noDataRow') return;
        let amountCell = $(this).find('td:eq(5)').text().replace('৳', '').replace(',', '').trim();
        let commissionCell = $(this).find('td:eq(7)').text().replace('৳', '').replace(',', '').trim();
        let amount = parseFloat(amountCell) || 0;
        let commission = parseFloat(commissionCell) || 0;
        totalAmount += amount;
        totalCommission += commission;
    });
    
    $('#totalAmount').text('৳ ' + totalAmount.toFixed(2));
    $('#totalCommission').text('৳ ' + totalCommission.toFixed(2));
}

// Reset filters
function resetFilters() {
    $('#doctorFilter').val('');
    $('#statusFilter').val('');
    $('#typeFilter').val('');
    $('#dateFrom').val('');
    $('#dateTo').val('');
    $('#commissionTableBody tr').show();
    $('#recordCount').text($('#commissionTableBody tr:not(#noDataRow)').length + ' records');
    calculateTotals();
}

// Approve commission
function approveCommission(id) {
    Swal.fire({
        title: 'Approve Commission?',
        text: 'Do you want to approve this commission?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: BASE_URL + '/doctor/commission/approve',
                method: 'POST',
                data: { commission_id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Approved!', response.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to approve commission', 'error');
                }
            });
        }
    });
}

// Pay commission - show modal
function payCommission(id) {
    $('#payCommissionId').val(id);
    $('#paymentMethod').val('bank_transfer');
    $('#transactionId').val('');
    $('#paymentDate').val(new Date().toISOString().split('T')[0]);
    $('#paymentNotes').val('');
    
    var payModalEl = document.getElementById('payModal');
    if (payModalEl) {
        var payModal = new bootstrap.Modal(payModalEl);
        payModal.show();
    }
}

// Process payment
function processPayment() {
    let commissionId = $('#payCommissionId').val();
    
    if (!commissionId) {
        Swal.fire('Error!', 'No commission selected', 'error');
        return;
    }
    
    let formData = {
        commission_id: commissionId,
        payment_method: $('#paymentMethod').val(),
        transaction_id: $('#transactionId').val(),
        payment_date: $('#paymentDate').val(),
        notes: $('#paymentNotes').val()
    };
    
    Swal.fire({
        title: 'Settle Commission?',
        text: 'Mark this commission as settled?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Settle',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: BASE_URL + '/doctor/commission/pay',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Settled!', response.message, 'success').then(() => {
                            var payModalEl = document.getElementById('payModal');
                            if (payModalEl) {
                                var payModal = bootstrap.Modal.getInstance(payModalEl);
                                if (payModal) payModal.hide();
                            }
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Failed to settle commission';
                    try {
                        let response = JSON.parse(xhr.responseText);
                        if (response.message) errorMsg = response.message;
                    } catch(e) {}
                    Swal.fire('Error!', errorMsg, 'error');
                }
            });
        }
    });
}

// View commission details
function viewCommissionDetails(id) {
    var commissionModalEl = document.getElementById('commissionModal');
    if (commissionModalEl) {
        var commissionModal = new bootstrap.Modal(commissionModalEl);
        commissionModal.show();
    }
    
    $('#commissionDetails').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    
    $.ajax({
        url: BASE_URL + '/doctor/commission/details/' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let data = response.data;
                let statusText = data.status == 'paid' ? 'Settled' : (data.status == 'approved' ? 'Approved' : 'Pending');
                
                let html = `
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-card">
                                <h6 class="mb-3 text-primary"><i class="fas fa-info-circle me-2"></i>Commission Information</h6>
                                <div class="row mb-2"><div class="col-5 detail-label">Commission ID:</div><div class="col-7 detail-value">#${data.id}</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Doctor:</div><div class="col-7 detail-value">Dr. ${data.doctor_name || 'Unknown'}</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Reference Type:</div><div class="col-7 detail-value">${data.reference_type || '-'}</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Service Name:</div><div class="col-7 detail-value">${data.service_name || '-'}</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Reference ID:</div><div class="col-7 detail-value">#${data.reference_id || 'N/A'}</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Amount:</div><div class="col-7 detail-value">৳ ${parseFloat(data.amount || 0).toFixed(2)}</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Commission %:</div><div class="col-7 detail-value">${data.commission_percentage || 0}%</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Commission Amount:</div><div class="col-7 detail-value"><strong class="text-success">৳ ${parseFloat(data.commission_amount || 0).toFixed(2)}</strong></div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Status:</div><div class="col-7"><span class="status-badge status-${data.status}">${statusText}</span></div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Created Date:</div><div class="col-7 detail-value">${data.created_at ? new Date(data.created_at).toLocaleString() : 'N/A'}</div></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-card">
                                <h6 class="mb-3 text-success"><i class="fas fa-credit-card me-2"></i>Payment Information</h6>
                                <div class="row mb-2"><div class="col-5 detail-label">Payment Date:</div><div class="col-7 detail-value">${data.paid_at ? new Date(data.paid_at).toLocaleString() : 'Not Settled'}</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Payment Method:</div><div class="col-7 detail-value">${data.payment_method ? data.payment_method.replace('_', ' ').toUpperCase() : '-'}</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Transaction ID:</div><div class="col-7 detail-value">${data.transaction_id || '-'}</div></div>
                                <div class="row mb-2"><div class="col-5 detail-label">Notes:</div><div class="col-7 detail-value">${data.notes || '-'}</div></div>
                            </div>
                        </div>
                    </div>
                `;
                $('#commissionDetails').html(html);
            } else {
                $('#commissionDetails').html('<div class="text-center py-4 text-danger">' + response.message + '</div>');
            }
        },
        error: function() {
            $('#commissionDetails').html('<div class="text-center py-4 text-danger">Error loading details</div>');
        }
    });
}

// Print commission slip
function printCommissionSlip(id) {
    window.open(BASE_URL + '/doctor/commission/print/' + id, '_blank');
}

function printCommissionDetails() {
    window.print();
}

// Export to Excel
function exportCommissions() {
    let params = new URLSearchParams();
    if($('#doctorFilter').val()) params.append('doctor_id', $('#doctorFilter').val());
    if($('#statusFilter').val()) params.append('status', $('#statusFilter').val());
    if($('#typeFilter').val()) params.append('type', $('#typeFilter').val());
    if($('#dateFrom').val()) params.append('date_from', $('#dateFrom').val());
    if($('#dateTo').val()) params.append('date_to', $('#dateTo').val());
    
    window.location.href = BASE_URL + '/doctor/commission/export?' + params.toString();
}

// Show report modal
function showReportModal() {
    var reportModalEl = document.getElementById('reportModal');
    if (reportModalEl) {
        var reportModal = new bootstrap.Modal(reportModalEl);
        reportModal.show();
    }
}

// Update report dates
function updateReportDates() {
    let period = $('#reportPeriod').val();
    if(period === 'custom') {
        $('#dateFromDiv').show();
        $('#dateToDiv').show();
    } else {
        $('#dateFromDiv').hide();
        $('#dateToDiv').hide();
    }
}

// Generate report
function generateReport() {
    let period = $('#reportPeriod').val();
    let doctorId = $('#reportDoctor').val();
    let dateFrom = $('#reportDateFrom').val();
    let dateTo = $('#reportDateTo').val();
    
    let url = BASE_URL + '/doctor/commission/report?period=' + period;
    if(doctorId) url += '&doctor_id=' + doctorId;
    if(period === 'custom') {
        url += '&date_from=' + dateFrom + '&date_to=' + dateTo;
    }
    
    window.open(url, '_blank');
}

$(document).ready(function() {
    calculateTotals();
    updateReportDates();
});
</script>
</body>
</html>