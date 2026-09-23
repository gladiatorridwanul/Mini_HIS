<?php
$monthName = date('F Y', strtotime($month . '-01'));

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$totalRecords = isset($totalRecords) ? $totalRecords : 0;
$totalPages = ceil($totalRecords / $limit);
?>
<style>
    .payroll-stats { transition: transform 0.2s; cursor: pointer; }
    .payroll-stats:hover { transform: translateY(-3px); }
    .salary-input { width: 120px; }
    .payslip-modal { max-width: 800px; }
    .status-badge { font-size: 11px; padding: 5px 12px; }
    .btn-group .btn { padding: 4px 8px; font-size: 11px; }
    .currency-symbol { font-family: 'Arial', sans-serif; }
    
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
        .pagination .page-link {
            padding: 4px 8px;
            font-size: 12px;
        }
    }
</style>

<!-- Statistics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card bg-primary text-white payroll-stats">
            <div class="card-body">
                <h6>Total Employees</h6>
                <h2><?php echo $totalRecords; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-success text-white payroll-stats">
            <div class="card-body">
                <h6>Total Payroll</h6>
                <h2>৳<?php echo number_format($totalNet, 2); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-warning text-white payroll-stats">
            <div class="card-body">
                <h6>Processed</h6>
                <h2><?php 
                    $processed = 0;
                    foreach($payrolls as $p) if($p['status'] == 'processed' || $p['status'] == 'paid') $processed++;
                    echo $processed;
                ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-info text-white payroll-stats">
            <div class="card-body">
                <h6>Pending</h6>
                <h2><?php 
                    $pending = 0;
                    foreach($payrolls as $p) if($p['status'] == 'pending') $pending++;
                    echo $pending;
                ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Controls -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label">Select Month</label>
                <input type="month" id="monthSelect" class="form-control" value="<?php echo $month; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary w-100" onclick="changeMonth()">Go</button>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-success w-100" onclick="generateAllPayroll()">
                    <i class="fas fa-sync-alt me-1"></i> Generate All
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-info w-100" onclick="exportPayroll()">
                    <i class="fas fa-file-excel me-1"></i> Export
                </button>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-secondary w-100" onclick="printPayroll()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payroll Table -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2 text-success"></i>Payroll for <?php echo $monthName; ?></h5>
        <span class="badge bg-secondary">
            Showing <?php echo count($payrolls); ?> of <?php echo $totalRecords; ?> records
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0" id="payrollTable">
                <thead class="table-dark">
                    <tr>
                        <th width="5%">#</th>
                        <th width="10%">Employee ID</th>
                        <th width="15%">Name</th>
                        <th width="12%">Role</th>
                        <th width="12%">Basic Salary</th>
                        <th width="12%">Allowances</th>
                        <th width="12%">Deductions</th>
                        <th width="12%">Commission</th>
                        <th width="12%">Net Salary</th>
                        <th width="10%">Status</th>
                        <th width="10%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($payrolls)): ?>
                        <tr><td colspan="11" class="text-center text-muted py-4">
                            <i class="fas fa-info-circle me-2"></i> No payroll records found for <?php echo $monthName; ?>
                            <br><button class="btn btn-primary btn-sm mt-2" onclick="generateAllPayroll()">Generate Payroll</button>
                        </td></tr>
                    <?php else: ?>
                        <?php $counter = $offset + 1; ?>
                        <?php foreach($payrolls as $p): ?>
                        <tr data-user-id="<?php echo $p['user_id']; ?>">
                            <td class="text-center"><?php echo $counter++; ?></td>
                            <td><?php echo htmlspecialchars($p['employee_id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($p['role_name']); ?></td>
                            <td class="text-end">৳<?php echo number_format($p['basic_salary'], 2); ?></td>
                            <td class="text-end">৳<?php echo number_format($p['allowances'], 2); ?></td>
                            <td class="text-end">৳<?php echo number_format($p['deductions'], 2); ?></td>
                            <td class="text-end">৳<?php echo number_format($p['commission_earned'] ?? 0, 2); ?></td>
                            <td class="text-end"><strong class="text-success">৳<?php echo number_format($p['net_salary'], 2); ?></strong></td>
                            <td>
                                <span class="badge <?php echo $p['status'] == 'paid' ? 'bg-success' : ($p['status'] == 'processed' ? 'bg-info' : 'bg-warning'); ?> status-badge">
                                    <?php echo ucfirst($p['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-primary" onclick="editSalary(<?php echo $p['user_id']; ?>)" title="Edit Salary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-success" onclick="markAsPaid(<?php echo $p['user_id']; ?>)" title="Mark as Paid">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    <button class="btn btn-info" onclick="viewPayslip(<?php echo $p['user_id']; ?>)" title="View Payslip">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <th colspan="4" class="text-end">Total:</th>
                        <th class="text-end">৳<?php echo number_format($totalBasic, 2); ?></th>
                        <th class="text-end">৳<?php echo number_format($totalAllowances, 2); ?></th>
                        <th class="text-end">৳<?php echo number_format($totalDeductions, 2); ?></th>
                        <th class="text-end">-</th>
                        <th class="text-end text-success">৳<?php echo number_format($totalNet, 2); ?></th>
                        <th colspan="2"></th>
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
        <nav aria-label="Payroll pagination">
            <ul class="pagination justify-content-center">
                <!-- Previous Page -->
                <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&month=<?php echo urlencode($month); ?>" 
                       aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <!-- Page Numbers -->
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                // Build query string
                $queryParams = '&month=' . urlencode($month);
                
                // Show first page if not in range
                if($startPage > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1' . $queryParams . '">1</a></li>';
                    if($startPage > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                // Page numbers
                for($i = $startPage; $i <= $endPage; $i++) {
                    $active = ($i == $page) ? 'active' : '';
                    echo '<li class="page-item ' . $active . '">';
                    echo '<a class="page-link" href="?page=' . $i . $queryParams . '">' . $i . '</a>';
                    echo '</li>';
                }
                
                // Show last page if not in range
                if($endPage < $totalPages) {
                    if($endPage < $totalPages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . $queryParams . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <!-- Next Page -->
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

<!-- Page Info -->
<div class="row mt-2">
    <div class="col-12 text-center">
        <small class="text-muted">
            Page <?php echo $page; ?> of <?php echo $totalPages; ?> 
            (Total <?php echo $totalRecords; ?> payroll records)
        </small>
    </div>
</div>

<!-- Edit Salary Modal -->
<div class="modal fade" id="editSalaryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-calculator me-2"></i>Edit Salary Components</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSalaryForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    <input type="hidden" id="editMonth" name="month" value="<?php echo $month; ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">Earnings</div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <label class="form-label">Basic Salary (৳)</label>
                                        <input type="number" step="100" name="basic_salary" id="editBasicSalary" class="form-control" required>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">House Rent Allowance (HRA) (৳)</label>
                                        <input type="number" step="100" name="hra" id="editHra" class="form-control" value="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Medical Allowance (৳)</label>
                                        <input type="number" step="100" name="medical_allowance" id="editMedical" class="form-control" value="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Conveyance Allowance (৳)</label>
                                        <input type="number" step="100" name="conveyance" id="editConveyance" class="form-control" value="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Other Allowances (৳)</label>
                                        <input type="number" step="100" name="other_allowances" id="editOtherAllowances" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-danger text-white">Deductions</div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <label class="form-label">Provident Fund (PF) (৳)</label>
                                        <input type="number" step="100" name="provident_fund" id="editPf" class="form-control" value="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Professional Tax (PT) (৳)</label>
                                        <input type="number" step="100" name="professional_tax" id="editPt" class="form-control" value="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Income Tax (TDS) (৳)</label>
                                        <input type="number" step="100" name="income_tax" id="editTax" class="form-control" value="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Other Deductions (৳)</label>
                                        <input type="number" step="100" name="other_deductions" id="editOtherDeductions" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Net Salary Calculation:</strong> 
                        Basic + HRA + Medical + Conveyance + Other Allowances - (PF + PT + Tax + Other Deductions) = Net Salary
                    </div>
                    
                    <div class="row bg-light p-3 rounded">
                        <div class="col-md-6">
                            <h6>Total Allowances: <span id="totalAllowancesDisplay" class="text-success">৳0</span></h6>
                            <h6>Total Deductions: <span id="totalDeductionsDisplay" class="text-danger">৳0</span></h6>
                        </div>
                        <div class="col-md-6 text-end">
                            <h4>Net Salary: <span id="netSalaryDisplay" class="text-primary">৳0</span></h4>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSalary()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-credit-card me-2"></i>Record Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" id="paymentUserId" name="user_id">
                    <input type="hidden" id="paymentMonth" name="month" value="<?php echo $month; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" id="paymentMethod" class="form-select" required>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transaction ID / Reference</label>
                        <input type="text" name="transaction_id" id="transactionId" class="form-control" placeholder="e.g. TRX-2024-001">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Notes</label>
                        <textarea name="notes" id="paymentNotes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="confirmPayment()">Confirm Payment</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function changeMonth() {
    let month = $('#monthSelect').val();
    window.location.href = BASE_URL + '/admin/users/payroll?month=' + month;
}

function generateAllPayroll() {
    let month = $('#monthSelect').val();
    if(confirm('Generate payroll for all employees for ' + month + '?')) {
        $.ajax({
            url: BASE_URL + '/api/payroll/generate-all',
            method: 'POST',
            data: {month: month},
            success: function(response) {
                if(response.success) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('Server error. Please try again.');
            }
        });
    }
}

function editSalary(userId) {
    let month = $('#monthSelect').val();
    
    $.ajax({
        url: BASE_URL + '/api/payroll/get-details',
        method: 'GET',
        data: {user_id: userId, month: month},
        success: function(response) {
            if(response.success) {
                let data = response.data;
                $('#editUserId').val(userId);
                $('#editBasicSalary').val(data.basic_salary || 0);
                $('#editHra').val(data.hra || 0);
                $('#editMedical').val(data.medical_allowance || 0);
                $('#editConveyance').val(data.conveyance || 0);
                $('#editOtherAllowances').val(data.other_allowances || 0);
                $('#editPf').val(data.provident_fund || 0);
                $('#editPt').val(data.professional_tax || 0);
                $('#editTax').val(data.income_tax || 0);
                $('#editOtherDeductions').val(data.other_deductions || 0);
                calculateNetSalary();
                $('#editSalaryModal').modal('show');
            } else {
                alert('Error fetching salary details: ' + response.message);
            }
        },
        error: function() {
            alert('Server error. Please try again.');
        }
    });
}

function calculateNetSalary() {
    let basic = parseFloat($('#editBasicSalary').val()) || 0;
    let hra = parseFloat($('#editHra').val()) || 0;
    let medical = parseFloat($('#editMedical').val()) || 0;
    let conveyance = parseFloat($('#editConveyance').val()) || 0;
    let otherAllow = parseFloat($('#editOtherAllowances').val()) || 0;
    
    let pf = parseFloat($('#editPf').val()) || 0;
    let pt = parseFloat($('#editPt').val()) || 0;
    let tax = parseFloat($('#editTax').val()) || 0;
    let otherDed = parseFloat($('#editOtherDeductions').val()) || 0;
    
    let totalAllowances = basic + hra + medical + conveyance + otherAllow;
    let totalDeductions = pf + pt + tax + otherDed;
    let netSalary = totalAllowances - totalDeductions;
    
    $('#totalAllowancesDisplay').text('৳' + totalAllowances.toFixed(2));
    $('#totalDeductionsDisplay').text('৳' + totalDeductions.toFixed(2));
    $('#netSalaryDisplay').text('৳' + netSalary.toFixed(2));
}

// Real-time calculation on input change
$('#editBasicSalary, #editHra, #editMedical, #editConveyance, #editOtherAllowances, #editPf, #editPt, #editTax, #editOtherDeductions').on('input', function() {
    calculateNetSalary();
});

function saveSalary() {
    let formData = {
        user_id: $('#editUserId').val(),
        month: $('#editMonth').val(),
        basic_salary: $('#editBasicSalary').val(),
        hra: $('#editHra').val(),
        medical_allowance: $('#editMedical').val(),
        conveyance: $('#editConveyance').val(),
        other_allowances: $('#editOtherAllowances').val(),
        provident_fund: $('#editPf').val(),
        professional_tax: $('#editPt').val(),
        income_tax: $('#editTax').val(),
        other_deductions: $('#editOtherDeductions').val()
    };
    
    $.ajax({
        url: BASE_URL + '/api/payroll/update-salary',
        method: 'POST',
        data: formData,
        success: function(response) {
            if(response.success) {
                alert('Salary updated successfully');
                $('#editSalaryModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Server error. Please try again.');
        }
    });
}

function markAsPaid(userId) {
    $('#paymentUserId').val(userId);
    $('#paymentModal').modal('show');
}

function confirmPayment() {
    let formData = {
        user_id: $('#paymentUserId').val(),
        month: $('#paymentMonth').val(),
        payment_method: $('#paymentMethod').val(),
        transaction_id: $('#transactionId').val(),
        notes: $('#paymentNotes').val()
    };
    
    $.ajax({
        url: BASE_URL + '/api/payroll/mark-paid',
        method: 'POST',
        data: formData,
        success: function(response) {
            if(response.success) {
                alert('Payment recorded successfully');
                $('#paymentModal').modal('hide');
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Server error. Please try again.');
        }
    });
}

function viewPayslip(userId) {
    let month = $('#monthSelect').val();
    window.open(BASE_URL + '/api/payroll/print-slip?user_id=' + userId + '&month=' + month, '_blank');
}

function exportPayroll() {
    let month = $('#monthSelect').val();
    window.location.href = BASE_URL + '/api/payroll/export?month=' + month;
}

function printPayroll() {
    let printContent = document.getElementById('payrollTable').outerHTML;
    let title = 'Payroll Report - ' + $('#monthSelect').val();
    let printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>' + title + '</title>');
    printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">');
    printWindow.document.write('<style>body{padding:20px;font-family:Cambria,Georgia,serif;} @media print{.btn{display:none;}}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h3 class="text-center">' + title + '</h3>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

// Auto-refresh month select on change
$(document).ready(function() {
    $('#monthSelect').on('change', function() {
        changeMonth();
    });
});
</script>