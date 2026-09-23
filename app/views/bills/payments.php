<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total records for pagination
$totalRecords = isset($totalPayments) ? $totalPayments : 0;
$totalPages = ceil($totalRecords / $limit);
$currentPage = $page;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            transition: transform 0.3s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        .stat-card.primary::before { background: #3b82f6; }
        .stat-card.success::before { background: #10b981; }
        .stat-card.warning::before { background: #f59e0b; }
        .stat-card.info::before { background: #06b6d4; }
        
        .stat-number { font-size: 28px; font-weight: 700; margin-bottom: 5px; }
        .stat-label { font-size: 13px; color: #6c757d; }
        .stat-icon { position: absolute; bottom: 15px; right: 15px; opacity: 0.1; font-size: 48px; }
        
        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
        }
        
        .payment-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }
        .card-header-custom {
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header-custom h5 { margin: 0; font-weight: 600; color: #1f2937; }
        
        .payment-method-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }
        .method-cash { background: #d1fae5; color: #10b981; }
        .method-card { background: #dbeafe; color: #2563eb; }
        .method-mobile { background: #fef3c7; color: #d97706; }
        .method-bank { background: #e0e7ff; color: #4f46e5; }
        
        .payment-table {
            width: 100%;
            border-collapse: collapse;
        }
        .payment-table th {
            padding: 14px 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }
        .payment-table td {
            padding: 14px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .payment-table tr:hover { background: #fafbfc; }
        
        .code-badge {
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 11px;
            font-weight: 600;
        }
        
        .btn-apply {
            background: #10b981;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
        }
        .btn-apply:hover { background: #059669; }
        
        .btn-reset {
            background: #6c757d;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
        }
        .btn-reset:hover { background: #5a6268; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-icon { font-size: 64px; color: #cbd5e1; margin-bottom: 20px; }
        .empty-title { font-size: 18px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .empty-text { color: #94a3b8; font-size: 13px; }
        
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
            .payment-table th, .payment-table td { padding: 10px 6px; }
            .pagination .page-link { padding: 4px 8px; font-size: 12px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-credit-card text-success me-2"></i>Payments</h1>
            <p class="page-subtitle">Manage all payment transactions</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/bills/export?type=payments&format=csv" class="btn btn-info">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card primary">
                <div class="stat-number"><?php echo isset($totalPayments) ? number_format($totalPayments) : 0; ?></div>
                <div class="stat-label">Total Payments</div>
                <i class="fas fa-receipt stat-icon"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card success">
                <div class="stat-number">৳ <?php echo isset($totalAmountReceived) ? number_format($totalAmountReceived, 2) : '0.00'; ?></div>
                <div class="stat-label">Total Received</div>
                <i class="fas fa-dollar-sign stat-icon"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card warning">
                <div class="stat-number">৳ <?php echo isset($cashAmount) ? number_format($cashAmount, 2) : '0.00'; ?></div>
                <div class="stat-label">Cash</div>
                <i class="fas fa-money-bill stat-icon"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card info">
                <div class="stat-number">৳ <?php 
                    $digitalAmount = (isset($cardAmount) ? $cardAmount : 0) + 
                                    (isset($mobileAmount) ? $mobileAmount : 0) + 
                                    (isset($bankAmount) ? $bankAmount : 0);
                    echo number_format($digitalAmount, 2); 
                ?></div>
                <div class="stat-label">Digital Payments</div>
                <i class="fas fa-mobile-alt stat-icon"></i>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">From Date</label>
                <input type="date" id="dateFrom" class="form-control" value="<?php echo isset($dateFrom) ? $dateFrom : ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">To Date</label>
                <input type="date" id="dateTo" class="form-control" value="<?php echo isset($dateTo) ? $dateTo : ''; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Payment Method</label>
                <select id="methodFilter" class="form-select">
                    <option value="">All Methods</option>
                    <option value="cash" <?php echo (isset($selectedMethod) && $selectedMethod == 'cash') ? 'selected' : ''; ?>>Cash</option>
                    <option value="card" <?php echo (isset($selectedMethod) && $selectedMethod == 'card') ? 'selected' : ''; ?>>Card</option>
                    <option value="mobile_banking" <?php echo (isset($selectedMethod) && $selectedMethod == 'mobile_banking') ? 'selected' : ''; ?>>Mobile Banking</option>
                    <option value="bank_transfer" <?php echo (isset($selectedMethod) && $selectedMethod == 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Search</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Patient name or payment #" value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
            </div>
            <div class="col-md-12">
                <button class="btn-apply" onclick="applyFilters()">
                    <i class="fas fa-search me-2"></i>Apply Filters
                </button>
                <button class="btn-reset ms-2" onclick="resetFilters()">
                    <i class="fas fa-undo me-2"></i>Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="payment-card">
        <div class="card-header-custom">
            <h5><i class="fas fa-list me-2"></i>Payment Transactions</h5>
            <span class="badge bg-secondary">
                Showing <?php echo isset($payments) ? count($payments) : 0; ?> of <?php echo $totalRecords; ?> records
            </span>
        </div>
        <div class="table-responsive">
            <table class="payment-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Payment #</th>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Bill #</th>
                        <th>Amount (BDT)</th>
                        <th>Method</th>
                        <th>Received By</th>
                        <th>Transaction ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($payments) && !empty($payments)): ?>
                        <?php $counter = $offset + 1; ?>
                        <?php foreach($payments as $payment): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td>
                                <span class="code-badge"><?php echo htmlspecialchars($payment['payment_number']); ?></span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></td>
                            <td>
                                <strong><?php echo isset($payment['first_name']) ? htmlspecialchars($payment['first_name']) : 'Unknown'; ?> <?php echo isset($payment['last_name']) ? htmlspecialchars($payment['last_name']) : ''; ?></strong>
                                <br><small class="text-muted"><?php echo isset($payment['patient_code']) ? htmlspecialchars($payment['patient_code']) : 'N/A'; ?></small>
                            </td>
                            <td>
                                <span class="code-badge"><?php echo htmlspecialchars($payment['bill_number']); ?></span>
                            </td>
                            <td><strong class="text-success">৳ <?php echo number_format((float)$payment['amount'], 2); ?></strong></td>
                            <td>
                                <span class="payment-method-badge method-<?php 
                                    echo $payment['payment_method'] == 'cash' ? 'cash' : 
                                        ($payment['payment_method'] == 'card' ? 'card' : 
                                        ($payment['payment_method'] == 'mobile_banking' ? 'mobile' : 'bank')); 
                                ?>">
                                    <?php 
                                        echo $payment['payment_method'] == 'cash' ? '💵 Cash' : 
                                            ($payment['payment_method'] == 'card' ? '💳 Card' : 
                                            ($payment['payment_method'] == 'mobile_banking' ? '📱 Mobile Banking' : '🏦 Bank Transfer'));
                                    ?>
                                </span>
                            </td>
                            <td><?php echo isset($payment['received_by_name']) ? htmlspecialchars($payment['received_by_name']) : 'System'; ?></td>
                            <td>
                                <small class="text-muted"><?php echo isset($payment['transaction_id']) && !empty($payment['transaction_id']) ? htmlspecialchars($payment['transaction_id']) : 'N/A'; ?></small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-credit-card empty-icon"></i>
                                    <div class="empty-title">No Payment Records Found</div>
                                    <div class="empty-text">No payments match your search criteria.</div>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="row mt-4">
        <div class="col-12">
            <nav aria-label="Payment pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous Page -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&date_from=<?php echo urlencode($dateFrom ?? ''); ?>&date_to=<?php echo urlencode($dateTo ?? ''); ?>&method=<?php echo urlencode($selectedMethod ?? ''); ?>&search=<?php echo urlencode($search ?? ''); ?>" 
                           aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Build query string
                    $queryParams = '&date_from=' . urlencode($dateFrom ?? '') . '&date_to=' . urlencode($dateTo ?? '') . '&method=' . urlencode($selectedMethod ?? '') . '&search=' . urlencode($search ?? '');
                    
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
                (Total <?php echo $totalRecords; ?> payment records)
            </small>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function applyFilters() {
    let params = new URLSearchParams();
    if($('#dateFrom').val()) params.append('date_from', $('#dateFrom').val());
    if($('#dateTo').val()) params.append('date_to', $('#dateTo').val());
    if($('#methodFilter').val()) params.append('method', $('#methodFilter').val());
    if($('#searchInput').val()) params.append('search', $('#searchInput').val());
    
    let url = BASE_URL + '/bills/payments';
    let queryString = params.toString();
    if(queryString) {
        url += '?' + queryString;
    }
    window.location.href = url;
}

function resetFilters() {
    window.location.href = BASE_URL + '/bills/payments';
}
</script>
</body>
</html>