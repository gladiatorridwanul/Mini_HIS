<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Reports - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        
        .report-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .stat-number-lg {
            font-size: 32px;
            font-weight: 700;
            color: #10b981;
        }
        
        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
        }
        
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
            height: 100%;
            border: 1px solid #e5e7eb;
        }
        .summary-card:hover { transform: translateY(-5px); }
        .summary-card .number { font-size: 28px; font-weight: 700; color: #1f2937; }
        .summary-card .label { font-size: 13px; color: #6c757d; margin-top: 5px; }
        .summary-card i { font-size: 40px; opacity: 0.2; margin-bottom: 10px; }
        
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
            text-transform: uppercase;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        .report-table td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .report-table tr:hover { background: #fafbfc; }
        .report-table tfoot td {
            background: #f8fafc;
            font-weight: 700;
            border-top: 2px solid #e2e8f0;
        }
        
        .btn-print {
            background: #3b82f6;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-print:hover { background: #2563eb; color: white; }
        .btn-pdf {
            background: #ef4444;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-pdf:hover { background: #dc2626; color: white; }
        .btn-success-sm {
            background: #10b981;
            border: none;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-success-sm:hover { background: #059669; color: white; }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }
        .empty-icon { font-size: 48px; margin-bottom: 15px; }
        
        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-partial { background: #fef3c7; color: #92400e; }
        .status-pending { background: #fee2e2; color: #991b1b; }
        .status-refunded { background: #e5e7eb; color: #374151; }
        
        .date-range-input {
            border-radius: 8px;
            border: 1px solid #d1d5db;
            padding: 6px 10px;
            font-size: 13px;
            width: 100%;
        }
        .date-range-input:focus {
            border-color: #10b981;
            outline: none;
            box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
        }
        
        .filter-label {
            font-weight: 600;
            font-size: 12px;
            color: #4b5563;
            margin-bottom: 4px;
            display: block;
        }
        
        .filter-badge {
            background: #f0fdf4;
            border: 1px solid #d1fae5;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            color: #065f46;
            display: inline-block;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .stat-number-lg { font-size: 24px; }
            .report-table { font-size: 11px; }
            .report-table th, .report-table td { padding: 6px 8px; }
        }
        
        @media print {
            .btn-print, .btn-pdf, .filter-bar, .no-print, .btn-success-sm { display: none; }
            body { background: white; padding: 0; margin: 0; }
            .container-fluid { padding: 0; }
            .report-card { box-shadow: none; border: 1px solid #ddd; page-break-inside: avoid; }
            .report-card h5 { page-break-after: avoid; }
            .report-table { page-break-inside: auto; }
            .report-table tbody tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-chart-line text-success me-2"></i>Financial Reports</h1>
            <p class="page-subtitle">View transaction, payment, and billing analytics with detailed transaction lists</p>
        </div>
        <div class="no-print d-flex gap-2 flex-wrap">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print me-2"></i>Print Report
            </button>
            <button onclick="exportPDF()" class="btn-pdf">
                <i class="fas fa-file-pdf me-2"></i>Export PDF
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar no-print">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="filter-label">Report Type</label>
                <select id="reportType" class="form-select" onchange="changeReportType()">
                    <option value="transaction_list" <?php echo (isset($reportType) && $reportType == 'transaction_list') ? 'selected' : ''; ?>>Transaction List</option>
                    <option value="transaction_summary" <?php echo (isset($reportType) && $reportType == 'transaction_summary') ? 'selected' : ''; ?>>Transaction Summary</option>
                    <option value="payment_method" <?php echo (isset($reportType) && $reportType == 'payment_method') ? 'selected' : ''; ?>>Payment Method Wise</option>
                    <option value="user_wise" <?php echo (isset($reportType) && $reportType == 'user_wise') ? 'selected' : ''; ?>>User Wise</option>
                    <option value="daily_bill" <?php echo (isset($reportType) && $reportType == 'daily_bill') ? 'selected' : ''; ?>>Daily Bill Report</option>
                    <option value="summary_bill" <?php echo (isset($reportType) && $reportType == 'summary_bill') ? 'selected' : ''; ?>>Summary Bill Report</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="filter-label">From Date</label>
                <input type="date" id="dateFrom" class="date-range-input" value="<?php echo isset($dateFrom) ? $dateFrom : date('Y-m-01'); ?>">
            </div>
            <div class="col-md-2">
                <label class="filter-label">To Date</label>
                <input type="date" id="dateTo" class="date-range-input" value="<?php echo isset($dateTo) ? $dateTo : date('Y-m-d'); ?>">
            </div>
            <div class="col-md-2">
                <label class="filter-label">User</label>
                <select id="userSelect" class="form-select" onchange="changeUser()">
                    <option value="">All Users</option>
                    <?php if(isset($usersList) && !empty($usersList)): ?>
                        <?php foreach($usersList as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo (isset($selectedUserId) && $selectedUserId == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="filter-label">Payment Method</label>
                <select id="paymentMethodFilter" class="form-select">
                    <option value="">All Methods</option>
                    <option value="cash">Cash</option>
                    <option value="card">Card</option>
                    <option value="mobile_banking">Mobile Banking</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="insurance">Insurance</option>
                </select>
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-2">
                    <button onclick="applyFilters()" class="btn btn-success btn-sm" style="background: #10b981; border: none; padding: 8px 20px;">
                        <i class="fas fa-search me-1"></i> Apply
                    </button>
                    <button onclick="resetFilters()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-undo me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
        <!-- Active Filters Display -->
        <div class="mt-3 pt-2 border-top d-flex flex-wrap gap-2" id="activeFiltersDisplay">
            <?php if(isset($dateFrom) && isset($dateTo)): ?>
                <span class="filter-badge"><i class="fas fa-calendar me-1"></i> <?php echo date('d M Y', strtotime($dateFrom)); ?> - <?php echo date('d M Y', strtotime($dateTo)); ?></span>
            <?php endif; ?>
            <?php if(isset($selectedUserId) && $selectedUserId > 0): 
                $userName = '';
                foreach($usersList as $u) { if($u['id'] == $selectedUserId) { $userName = $u['first_name'] . ' ' . $u['last_name']; break; } }
            ?>
                <span class="filter-badge"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($userName); ?></span>
            <?php endif; ?>
            <?php if(isset($paymentMethodFilter) && !empty($paymentMethodFilter)): 
                $methodLabels = ['cash' => 'Cash', 'card' => 'Card', 'mobile_banking' => 'Mobile Banking', 'bank_transfer' => 'Bank Transfer', 'insurance' => 'Insurance'];
            ?>
                <span class="filter-badge"><i class="fas fa-credit-card me-1"></i> <?php echo $methodLabels[$paymentMethodFilter] ?? ucfirst($paymentMethodFilter); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="summary-card">
                <i class="fas fa-chart-line text-primary"></i>
                <div class="number">৳ <?php echo isset($totalRevenue) ? number_format($totalRevenue, 2) : '0.00'; ?></div>
                <div class="label">Total Revenue</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="summary-card">
                <i class="fas fa-money-bill-wave text-success"></i>
                <div class="number">৳ <?php echo isset($totalCollected) ? number_format($totalCollected, 2) : '0.00'; ?></div>
                <div class="label">Total Collected</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="summary-card">
                <i class="fas fa-hourglass-half text-warning"></i>
                <div class="number">৳ <?php echo isset($totalDue) ? number_format($totalDue, 2) : '0.00'; ?></div>
                <div class="label">Total Outstanding</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="summary-card">
                <i class="fas fa-receipt text-info"></i>
                <div class="number"><?php echo isset($totalTransactions) ? number_format($totalTransactions) : '0'; ?></div>
                <div class="label">Total Transactions</div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- 1. TRANSACTION LIST - Detailed View (with Received Amount & Discount) -->
    <!-- ================================================================ -->
    <div id="report_transaction_list" class="report-card" style="<?php echo (!isset($reportType) || $reportType == 'transaction_list' || $reportType == '') ? '' : 'display: none;'; ?>">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5><i class="fas fa-list me-2 text-primary"></i>Transaction List - Detailed View</h5>
            <div class="no-print">
                <button onclick="exportTablePDF('transaction_list_table', 'Transaction_List_Detailed')" class="btn-pdf" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button onclick="printTable('transaction_list_table')" class="btn-print" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="report-table" id="transaction_list_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Transaction ID</th>
                        <th>Bill Number</th>
                        <th>Patient</th>
                        <th>User</th>
                        <th>Date</th>
                        <th>Payment Method</th>
                        <th>Received (৳)</th>
                        <th>Discount (৳)</th>
                        <th>Bill Total (৳)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($transactionListData) && !empty($transactionListData)): ?>
                        <?php $counter = 1; foreach($transactionListData as $row): 
                            $statusClass = $row['payment_status'] == 'paid' ? 'status-paid' : ($row['payment_status'] == 'partial' ? 'status-partial' : 'status-pending');
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['payment_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['bill_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_name'] ?? 'System'); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['payment_date'])); ?></td>
                            <td>
                                <?php 
                                    $icons = ['cash' => '💵', 'card' => '💳', 'mobile_banking' => '📱', 'bank_transfer' => '🏦', 'insurance' => '🛡️'];
                                    $icon = $icons[$row['payment_method']] ?? '💰';
                                    $labels = ['cash' => 'Cash', 'card' => 'Card', 'mobile_banking' => 'Mobile Banking', 'bank_transfer' => 'Bank Transfer', 'insurance' => 'Insurance'];
                                    echo $icon . ' ' . ($labels[$row['payment_method']] ?? ucfirst($row['payment_method']));
                                ?>
                            </td>
                            <td class="text-success">৳ <?php echo number_format($row['amount'], 2); ?></td>
                            <td class="text-warning">৳ <?php echo number_format($row['bill_discount'] ?? 0, 2); ?></td>
                            <td>৳ <?php echo number_format($row['bill_total'] ?? 0, 2); ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo ucfirst($row['payment_status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="11" class="empty-state"><i class="fas fa-list empty-icon"></i><p>No transactions found for the selected period</p></td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if(isset($transactionListData) && !empty($transactionListData)): ?>
                <tfoot>
                    <tr>
                        <td colspan="7" class="text-end">Total</td>
                        <td class="text-success">৳ <?php echo number_format(array_sum(array_column($transactionListData, 'amount')), 2); ?></td>
                        <td class="text-warning">৳ <?php echo number_format(array_sum(array_column($transactionListData, 'bill_discount')), 2); ?></td>
                        <td>৳ <?php echo number_format(array_sum(array_column($transactionListData, 'bill_total')), 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
        <div class="mt-2 text-muted" style="font-size: 11px;">
            <i class="fas fa-info-circle"></i> Showing <?php echo isset($transactionListData) ? count($transactionListData) : 0; ?> transactions
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- 2. TRANSACTION SUMMARY -->
    <!-- ================================================================ -->
    <div id="report_transaction_summary" class="report-card" style="<?php echo (isset($reportType) && $reportType == 'transaction_summary') ? '' : 'display: none;'; ?>">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5><i class="fas fa-file-alt me-2 text-primary"></i>Transaction Summary</h5>
            <div class="no-print">
                <button onclick="exportTablePDF('transaction_summary_table', 'Transaction_Summary')" class="btn-pdf" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button onclick="printTable('transaction_summary_table')" class="btn-print" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        
        <!-- Summary by Date -->
        <h6 class="mt-2 mb-2"><i class="fas fa-calendar-day me-1 text-primary"></i> Summary by Date</h6>
        <div class="table-responsive mb-4">
            <table class="report-table" id="transaction_summary_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Transactions</th>
                        <th>Total Amount (৳)</th>
                        <th>Avg Amount (৳)</th>
                        <th>Min Amount (৳)</th>
                        <th>Max Amount (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($transactionSummaryByDate) && !empty($transactionSummaryByDate)): ?>
                        <?php $counter = 1; foreach($transactionSummaryByDate as $row): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo date('d M Y', strtotime($row['date'])); ?></strong></td>
                            <td><?php echo number_format($row['count']); ?></td>
                            <td class="text-success">৳ <?php echo number_format($row['total'], 2); ?></td>
                            <td>৳ <?php echo number_format($row['avg'], 2); ?></td>
                            <td>৳ <?php echo number_format($row['min'], 2); ?></td>
                            <td>৳ <?php echo number_format($row['max'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-file-alt empty-icon"></i><p>No summary data found for the selected period</p></td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if(isset($transactionSummaryByDate) && !empty($transactionSummaryByDate)): ?>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">Total</td>
                        <td><?php echo number_format(array_sum(array_column($transactionSummaryByDate, 'count'))); ?></td>
                        <td class="text-success">৳ <?php echo number_format(array_sum(array_column($transactionSummaryByDate, 'total')), 2); ?></td>
                        <td colspan="3"></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Summary by Payment Method -->
        <h6 class="mt-3 mb-2"><i class="fas fa-credit-card me-1 text-primary"></i> Summary by Payment Method</h6>
        <div class="table-responsive">
            <table class="report-table" id="transaction_summary_method_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Payment Method</th>
                        <th>Transactions</th>
                        <th>Total Amount (৳)</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($transactionSummaryByMethod) && !empty($transactionSummaryByMethod)): 
                        $totalMethodAmount = array_sum(array_column($transactionSummaryByMethod, 'total'));
                    ?>
                        <?php $counter = 1; foreach($transactionSummaryByMethod as $row): 
                            $percentage = $totalMethodAmount > 0 ? round(($row['total'] / $totalMethodAmount) * 100, 2) : 0;
                            $icons = ['cash' => '💵', 'card' => '💳', 'mobile_banking' => '📱', 'bank_transfer' => '🏦', 'insurance' => '🛡️'];
                            $icon = $icons[$row['payment_method']] ?? '💰';
                            $labels = ['cash' => 'Cash', 'card' => 'Card', 'mobile_banking' => 'Mobile Banking', 'bank_transfer' => 'Bank Transfer', 'insurance' => 'Insurance'];
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo $icon . ' ' . ($labels[$row['payment_method']] ?? ucfirst($row['payment_method'])); ?></td>
                            <td><?php echo number_format($row['count']); ?></td>
                            <td class="text-success">৳ <?php echo number_format($row['total'], 2); ?></td>
                            <td><?php echo number_format($percentage, 2); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-state"><i class="fas fa-credit-card empty-icon"></i><p>No payment method summary data found</p></td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if(isset($transactionSummaryByMethod) && !empty($transactionSummaryByMethod)): ?>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">Total</td>
                        <td><?php echo number_format(array_sum(array_column($transactionSummaryByMethod, 'count'))); ?></td>
                        <td class="text-success">৳ <?php echo number_format(array_sum(array_column($transactionSummaryByMethod, 'total')), 2); ?></td>
                        <td>100%</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
        
        <!-- Summary by User -->
        <h6 class="mt-3 mb-2"><i class="fas fa-users me-1 text-primary"></i> Summary by User</h6>
        <div class="table-responsive">
            <table class="report-table" id="transaction_summary_user_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Transactions</th>
                        <th>Total Amount (৳)</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($transactionSummaryByUser) && !empty($transactionSummaryByUser)): 
                        $totalUserAmount = array_sum(array_column($transactionSummaryByUser, 'total'));
                    ?>
                        <?php $counter = 1; foreach($transactionSummaryByUser as $row): 
                            $percentage = $totalUserAmount > 0 ? round(($row['total'] / $totalUserAmount) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['user_name']); ?></strong></td>
                            <td><?php echo number_format($row['count']); ?></td>
                            <td class="text-success">৳ <?php echo number_format($row['total'], 2); ?></td>
                            <td><?php echo number_format($percentage, 2); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-state"><i class="fas fa-users empty-icon"></i><p>No user summary data found</p></td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if(isset($transactionSummaryByUser) && !empty($transactionSummaryByUser)): ?>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">Total</td>
                        <td><?php echo number_format(array_sum(array_column($transactionSummaryByUser, 'count'))); ?></td>
                        <td class="text-success">৳ <?php echo number_format(array_sum(array_column($transactionSummaryByUser, 'total')), 2); ?></td>
                        <td>100%</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- 3. PAYMENT METHOD WISE REPORT -->
    <!-- ================================================================ -->
    <div id="report_payment_method" class="report-card" style="<?php echo (isset($reportType) && $reportType == 'payment_method') ? '' : 'display: none;'; ?>">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5><i class="fas fa-credit-card me-2 text-primary"></i>Payment Method Wise Report</h5>
            <div class="no-print">
                <button onclick="exportTablePDF('payment_method_table', 'Payment_Method_Report')" class="btn-pdf" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button onclick="printTable('payment_method_table')" class="btn-print" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="report-table" id="payment_method_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Payment Method</th>
                        <th>Transaction Count</th>
                        <th>Total Amount (৳)</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($paymentMethodData) && !empty($paymentMethodData)): ?>
                        <?php $totalMethodAmount = array_sum(array_column($paymentMethodData, 'total_amount')); ?>
                        <?php $counter = 1; foreach($paymentMethodData as $row): 
                            $percentage = $totalMethodAmount > 0 ? round(($row['total_amount'] / $totalMethodAmount) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td>
                                <?php 
                                    $icons = ['cash' => '💵', 'card' => '💳', 'mobile_banking' => '📱', 'bank_transfer' => '🏦', 'insurance' => '🛡️'];
                                    $icon = $icons[$row['payment_method']] ?? '💰';
                                    $labels = ['cash' => 'Cash', 'card' => 'Card', 'mobile_banking' => 'Mobile Banking', 'bank_transfer' => 'Bank Transfer', 'insurance' => 'Insurance'];
                                    echo $icon . ' ' . ($labels[$row['payment_method']] ?? ucfirst($row['payment_method']));
                                ?>
                            </td>
                            <td><?php echo number_format($row['total_transactions']); ?></td>
                            <td><strong class="text-success">৳ <?php echo number_format($row['total_amount'], 2); ?></strong></td>
                            <td><?php echo number_format($percentage, 2); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="empty-state"><i class="fas fa-credit-card empty-icon"></i><p>No payment method data available</p></td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if(isset($paymentMethodData) && !empty($paymentMethodData)): ?>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end">Total</td>
                        <td><?php echo number_format(array_sum(array_column($paymentMethodData, 'total_transactions'))); ?></td>
                        <td class="text-success">৳ <?php echo number_format(array_sum(array_column($paymentMethodData, 'total_amount')), 2); ?></td>
                        <td>100%</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- 4. USER WISE REPORT -->
    <!-- ================================================================ -->
    <div id="report_user_wise" class="report-card" style="<?php echo (isset($reportType) && $reportType == 'user_wise') ? '' : 'display: none;'; ?>">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5><i class="fas fa-users me-2 text-primary"></i>User Wise Transaction Report</h5>
            <div class="no-print">
                <button onclick="exportTablePDF('user_wise_table', 'User_Wise_Report')" class="btn-pdf" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button onclick="printTable('user_wise_table')" class="btn-print" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="report-table" id="user_wise_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User Name</th>
                        <th>Role</th>
                        <th>Transaction Count</th>
                        <th>Total Amount (৳)</th>
                        <th>Collected (৳)</th>
                        <th>Due (৳)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($userWiseData) && !empty($userWiseData)): ?>
                        <?php $counter = 1; foreach($userWiseData as $row): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['user_name']); ?></strong></td>
                            <td><span class="badge bg-secondary"><?php echo ucfirst($row['role_name'] ?? 'Staff'); ?></span></td>
                            <td><?php echo number_format($row['total_transactions']); ?></td>
                            <td class="text-success">৳ <?php echo number_format($row['total_amount'], 2); ?></td>
                            <td>৳ <?php echo number_format($row['collected_amount'], 2); ?></td>
                            <td class="text-danger">৳ <?php echo number_format($row['due_amount'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-users empty-icon"></i><p>No user wise data available</p></td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if(isset($userWiseData) && !empty($userWiseData)): ?>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end">Total</td>
                        <td><?php echo number_format(array_sum(array_column($userWiseData, 'total_transactions'))); ?></td>
                        <td class="text-success">৳ <?php echo number_format(array_sum(array_column($userWiseData, 'total_amount')), 2); ?></td>
                        <td>৳ <?php echo number_format(array_sum(array_column($userWiseData, 'collected_amount')), 2); ?></td>
                        <td class="text-danger">৳ <?php echo number_format(array_sum(array_column($userWiseData, 'due_amount')), 2); ?></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- 5. DAILY BILL REPORT -->
    <!-- ================================================================ -->
    <div id="report_daily_bill" class="report-card" style="<?php echo (isset($reportType) && $reportType == 'daily_bill') ? '' : 'display: none;'; ?>">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5><i class="fas fa-calendar-day me-2 text-primary"></i>Daily Bill Report</h5>
            <div class="no-print">
                <button onclick="exportTablePDF('daily_bill_table', 'Daily_Bill_Report')" class="btn-pdf" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button onclick="printTable('daily_bill_table')" class="btn-print" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="report-table" id="daily_bill_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bill Number</th>
                        <th>Patient</th>
                        <th>User</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Total (৳)</th>
                        <th>Discount (৳)</th>
                        <th>Paid (৳)</th>
                        <th>Due (৳)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($dailyBillData) && !empty($dailyBillData)): ?>
                        <?php $counter = 1; foreach($dailyBillData as $row): 
                            $balance = (float)$row['total_amount'] - (float)$row['paid_amount'] - (float)$row['discount_amount'];
                            $statusClass = $balance <= 0 ? 'status-paid' : ((float)$row['paid_amount'] > 0 ? 'status-partial' : 'status-pending');
                            $statusText = $balance <= 0 ? 'Paid' : ((float)$row['paid_amount'] > 0 ? 'Partial' : 'Pending');
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['bill_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_name'] ?? 'System'); ?></td>
                            <td><?php echo date('d M Y', strtotime($row['bill_date'])); ?></td>
                            <td><span class="badge bg-info"><?php echo ucfirst($row['bill_type']); ?></span></td>
                            <td class="text-success">৳ <?php echo number_format($row['total_amount'], 2); ?></td>
                            <td>৳ <?php echo number_format($row['discount_amount'] ?? 0, 2); ?></td>
                            <td>৳ <?php echo number_format($row['paid_amount'], 2); ?></td>
                            <td class="text-danger">৳ <?php echo number_format($balance < 0 ? 0 : $balance, 2); ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="11" class="empty-state"><i class="fas fa-calendar-day empty-icon"></i><p>No daily bill data available for the selected period</p></td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if(isset($dailyBillData) && !empty($dailyBillData)): ?>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-end">Total</td>
                        <td class="text-success">৳ <?php echo number_format(array_sum(array_column($dailyBillData, 'total_amount')), 2); ?></td>
                        <td>৳ <?php echo number_format(array_sum(array_column($dailyBillData, 'discount_amount')), 2); ?></td>
                        <td>৳ <?php echo number_format(array_sum(array_column($dailyBillData, 'paid_amount')), 2); ?></td>
                        <td class="text-danger">৳ <?php 
                            $totalDue = 0;
                            foreach($dailyBillData as $d) {
                                $bal = (float)$d['total_amount'] - (float)$d['paid_amount'] - (float)$d['discount_amount'];
                                $totalDue += $bal < 0 ? 0 : $bal;
                            }
                            echo number_format($totalDue, 2);
                        ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ================================================================ -->
    <!-- 6. SUMMARY BILL REPORT -->
    <!-- ================================================================ -->
    <div id="report_summary_bill" class="report-card" style="<?php echo (isset($reportType) && $reportType == 'summary_bill') ? '' : 'display: none;'; ?>">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
            <h5><i class="fas fa-chart-bar me-2 text-primary"></i>Summary Bill Report</h5>
            <div class="no-print">
                <button onclick="exportTablePDF('summary_bill_table', 'Summary_Bill_Report')" class="btn-pdf" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>
                <button onclick="printTable('summary_bill_table')" class="btn-print" style="padding: 4px 12px; font-size: 11px;">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="report-table" id="summary_bill_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>User</th>
                        <th>Total Bills</th>
                        <th>Total Amount (৳)</th>
                        <th>Discount (৳)</th>
                        <th>Collected (৳)</th>
                        <th>Due (৳)</th>
                        <th>Collection Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($summaryBillData) && !empty($summaryBillData)): ?>
                        <?php $counter = 1; foreach($summaryBillData as $row): 
                            $collectionRate = $row['total_amount'] > 0 ? round(($row['paid_amount'] / $row['total_amount']) * 100, 2) : 0;
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['user_name'] ?? 'All'); ?></td>
                            <td><?php echo number_format($row['total_bills']); ?></td>
                            <td class="text-success">৳ <?php echo number_format($row['total_amount'], 2); ?></td>
                            <td>৳ <?php echo number_format($row['discount_amount'] ?? 0, 2); ?></td>
                            <td>৳ <?php echo number_format($row['paid_amount'], 2); ?></td>
                            <td class="text-danger">৳ <?php echo number_format($row['due_amount'], 2); ?></td>
                            <td>
                                <div class="progress" style="height: 18px; background: #e5e7eb; border-radius: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min($collectionRate, 100); ?>%; border-radius: 10px;">
                                        <?php echo number_format($collectionRate, 2); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="empty-state"><i class="fas fa-chart-bar empty-icon"></i><p>No summary bill data available for the selected period</p></td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if(isset($summaryBillData) && !empty($summaryBillData)): ?>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end">Grand Total</td>
                        <td><?php echo number_format(array_sum(array_column($summaryBillData, 'total_bills'))); ?></td>
                        <td class="text-success">৳ <?php echo number_format(array_sum(array_column($summaryBillData, 'total_amount')), 2); ?></td>
                        <td>৳ <?php echo number_format(array_sum(array_column($summaryBillData, 'discount_amount')), 2); ?></td>
                        <td>৳ <?php echo number_format(array_sum(array_column($summaryBillData, 'paid_amount')), 2); ?></td>
                        <td class="text-danger">৳ <?php echo number_format(array_sum(array_column($summaryBillData, 'due_amount')), 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

// ================================================================
// FILTER FUNCTIONS
// ================================================================
function changeReportType() {
    let type = $('#reportType').val();
    let url = new URL(window.location.href);
    url.searchParams.set('report_type', type);
    window.location.href = url.toString();
}

function changeUser() {
    let user = $('#userSelect').val();
    let url = new URL(window.location.href);
    url.searchParams.set('user_id', user);
    let reportType = $('#reportType').val();
    url.searchParams.set('report_type', reportType);
    window.location.href = url.toString();
}

function applyFilters() {
    let type = $('#reportType').val();
    let dateFrom = $('#dateFrom').val();
    let dateTo = $('#dateTo').val();
    let user = $('#userSelect').val();
    let paymentMethod = $('#paymentMethodFilter').val();
    
    let url = new URL(window.location.href);
    url.searchParams.set('report_type', type);
    url.searchParams.set('date_from', dateFrom);
    url.searchParams.set('date_to', dateTo);
    if(user) url.searchParams.set('user_id', user);
    if(paymentMethod) url.searchParams.set('payment_method', paymentMethod);
    window.location.href = url.toString();
}

function resetFilters() {
    let url = new URL(window.location.href);
    url.searchParams.delete('report_type');
    url.searchParams.delete('date_from');
    url.searchParams.delete('date_to');
    url.searchParams.delete('user_id');
    url.searchParams.delete('payment_method');
    window.location.href = url.toString();
}

// ================================================================
// PRINT FUNCTIONS
// ================================================================
function printTable(tableId) {
    var content = document.getElementById(tableId);
    if (!content) {
        alert('Table not found');
        return;
    }
    
    var win = window.open('', '_blank', 'width=1100,height=800');
    if (!win) {
        alert('Please allow popups to print');
        return;
    }
    
    var style = document.querySelector('style').innerHTML;
    var title = tableId.replace(/_/g, ' ').toUpperCase().replace('TABLE', '');
    var reportType = $('#reportType option:selected').text() || 'Financial Report';
    
    win.document.write('<html><head><title>' + reportType + '</title>');
    win.document.write('<style>' + style + '</style>');
    win.document.write('<style>body { padding: 20px; background: white; } .no-print { display: none; } .report-table { width: 100%; border-collapse: collapse; } .report-table th { background: #f0f0f0; padding: 8px; border: 1px solid #ddd; } .report-table td { padding: 8px; border: 1px solid #ddd; } .status-badge { padding: 2px 8px; border-radius: 4px; font-size: 10px; } .status-paid { background: #d1fae5; } .status-partial { background: #fef3c7; } .status-pending { background: #fee2e2; } .text-success { color: #065f46; } .text-danger { color: #991b1b; } .badge { display: inline-block; padding: 2px 6px; font-size: 10px; border-radius: 4px; } .bg-info { background: #e0f2fe; } .bg-secondary { background: #e5e7eb; }</style>');
    win.document.write('</head><body>');
    win.document.write('<h3>UniDia Healthcare - ' + reportType + '</h3>');
    win.document.write('<p>Generated: ' + new Date().toLocaleString() + '</p>');
    win.document.write(content.outerHTML);
    win.document.write('</body></html>');
    win.document.close();
    setTimeout(function() {
        win.print();
        win.close();
    }, 500);
}

// ================================================================
// EXPORT PDF FUNCTIONS
// ================================================================
function exportPDF() {
    window.print();
}

function exportTablePDF(tableId, fileName) {
    var content = document.getElementById(tableId);
    if (!content) {
        alert('Table not found');
        return;
    }
    
    var win = window.open('', '_blank', 'width=1100,height=800');
    if (!win) {
        alert('Please allow popups to export PDF');
        return;
    }
    
    var style = document.querySelector('style').innerHTML;
    var title = (fileName || 'Report').replace(/_/g, ' ');
    
    win.document.write('<html><head><title>' + title + '</title>');
    win.document.write('<style>' + style + '</style>');
    win.document.write('<style>body { padding: 20px; background: white; } .no-print { display: none; } .report-table { width: 100%; border-collapse: collapse; } .report-table th { background: #f0f0f0; padding: 8px; border: 1px solid #ddd; } .report-table td { padding: 8px; border: 1px solid #ddd; } .status-badge { padding: 2px 8px; border-radius: 4px; font-size: 10px; } .status-paid { background: #d1fae5; } .status-partial { background: #fef3c7; } .status-pending { background: #fee2e2; } .text-success { color: #065f46; } .text-danger { color: #991b1b; }</style>');
    win.document.write('</head><body>');
    win.document.write('<h3>UniDia Healthcare - ' + title + '</h3>');
    win.document.write('<p>Generated: ' + new Date().toLocaleString() + '</p>');
    win.document.write(content.outerHTML);
    win.document.write('</body></html>');
    win.document.close();
    setTimeout(function() {
        win.print();
        win.close();
    }, 500);
}

// ================================================================
// INITIALIZE FILTERS
// ================================================================
$(document).ready(function() {
    let urlParams = new URLSearchParams(window.location.search);
    let dateFrom = urlParams.get('date_from');
    let dateTo = urlParams.get('date_to');
    let userId = urlParams.get('user_id');
    let paymentMethod = urlParams.get('payment_method');
    let reportType = urlParams.get('report_type');
    
    if(dateFrom) $('#dateFrom').val(dateFrom);
    if(dateTo) $('#dateTo').val(dateTo);
    if(userId) $('#userSelect').val(userId);
    if(paymentMethod) $('#paymentMethodFilter').val(paymentMethod);
    if(reportType) $('#reportType').val(reportType);
});
</script>
</body>
</html>