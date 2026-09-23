<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');

/**
 * Check if current user has a specific permission
 * @param string $permissionSlug - The permission slug to check
 * @return bool
 */
function hasPermission($permissionSlug) {
    // Super Admin has all permissions
    if (isset($_SESSION['role_slug']) && $_SESSION['role_slug'] == 'super_admin') {
        return true;
    }
    
    // Admin has all permissions
    if (isset($_SESSION['role_slug']) && $_SESSION['role_slug'] == 'admin') {
        return true;
    }
    
    // Check user permissions from session
    if (isset($_SESSION['permissions']) && is_array($_SESSION['permissions'])) {
        return in_array($permissionSlug, $_SESSION['permissions']);
    }
    
    return false;
}
?>

<style>
    /* ===== ADD EDIT BUTTON STYLES ===== */
    .action-buttons .btn-edit {
        background: #f59e0b;
        color: white;
        border: none;
        transition: all 0.2s;
    }
    .action-buttons .btn-edit:hover {
        background: #d97706;
        transform: scale(1.05);
        color: white;
    }
    .action-buttons .btn-edit i {
        color: white;
    }
    
    /* ===== EXISTING STYLES REMAIN UNCHANGED ===== */
    :root {
        --primary: #10b981;
        --primary-dark: #059669;
        --primary-light: #d1fae5;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-600: #6b7280;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
        --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
        --radius: 12px;
        --transition: 0.2s ease-in-out;
    }

    .page-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .page-header h5 {
        font-weight: 700;
        color: var(--gray-800);
        margin: 0;
    }
    .page-header .text-muted { font-size: 0.875rem; }

    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .stat-card {
        background: white;
        border-radius: var(--radius);
        padding: 1.25rem 1rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        transition: var(--transition);
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }
    .stat-card:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
    }
    .stat-card .stat-number {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--gray-800);
        line-height: 1.2;
    }
    .stat-card .stat-label {
        font-size: 0.75rem;
        color: var(--gray-600);
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-top: 0.25rem;
    }
    .stat-card .stat-icon {
        position: absolute;
        right: 1rem;
        top: 1rem;
        opacity: 0.15;
        font-size: 2rem;
    }
    .stat-card.total .stat-number { color: #3b82f6; }
    .stat-card.paid .stat-number { color: var(--primary); }
    .stat-card.due .stat-number { color: #f59e0b; }
    .stat-card.count .stat-number { color: #8b5cf6; }

    .filter-card {
        background: white;
        border-radius: var(--radius);
        padding: 1.25rem 1.25rem 1rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
    }
    .filter-card .form-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--gray-600);
        margin-bottom: 0.2rem;
    }
    .filter-card .form-control,
    .filter-card .form-select {
        border-radius: 8px;
        font-size: 0.875rem;
        border-color: var(--gray-200);
        transition: var(--transition);
        height: 38px;
        padding: 0.375rem 0.75rem;
    }
    .filter-card .form-control:focus,
    .filter-card .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
    }
    .filter-card .btn-sm {
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .table-card {
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-200);
        overflow: hidden;
    }
    .table-card .card-header {
        background: var(--gray-50);
        padding: 0.75rem 1.25rem;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .table-card .card-header h6 {
        margin: 0;
        font-weight: 600;
        color: var(--gray-700);
        font-size: 0.9rem;
    }

    .table {
        margin-bottom: 0;
        font-size: 0.85rem;
    }
    .table thead th {
        background: var(--gray-50);
        color: var(--gray-700);
        font-weight: 600;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.7rem 0.5rem;
        border-bottom: 2px solid var(--gray-200);
        white-space: nowrap;
        vertical-align: middle;
    }
    .table tbody td {
        padding: 0.6rem 0.5rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--gray-100);
    }
    .table tbody tr:hover { background-color: var(--gray-50); }
    .table .col-bill { min-width: 110px; }
    .table .col-patient { min-width: 130px; }
    .table .col-date { min-width: 80px; }
    .table .col-type { min-width: 140px; max-width: 200px; }
    .table .col-items { text-align: center; min-width: 50px; }
    .table .col-total { min-width: 90px; }
    .table .col-paid { min-width: 90px; }
    .table .col-discount { min-width: 90px; }
    .table .col-roundoff { min-width: 90px; }
    .table .col-due { min-width: 90px; }
    .table .col-status { min-width: 80px; }
    .table .col-actions { min-width: 160px; text-align: center; }

    .badge-bill-type {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.65rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-consultation { background: #dbeafe; color: #1e40af; }
    .badge-pharmacy { background: #d1fae5; color: #065f46; }
    .badge-lab_test { background: #f3e8ff; color: #6b21a5; }
    .badge-service { background: #fed7aa; color: #9a3412; }
    .badge-procedure { background: #fef3c7; color: #92400e; }
    .badge-other { background: var(--gray-200); color: var(--gray-700); }

    .status-badge {
        padding: 0.2rem 0.7rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-block;
        text-transform: capitalize;
    }
    .status-paid { background: var(--primary-light); color: var(--primary-dark); }
    .status-partial { background: #fef3c7; color: #d97706; }
    .status-pending { background: #fee2e2; color: #dc2626; }
    .status-refunded { background: #e5e7eb; color: #6b7280; }

    .action-buttons {
        display: flex;
        gap: 4px;
        flex-wrap: nowrap;
        justify-content: center;
    }
    .action-buttons .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
        border-radius: 6px;
        line-height: 1.4;
        min-width: 28px;
        text-align: center;
        transition: var(--transition);
    }
    .action-buttons .btn i { font-size: 0.8rem; }
    .action-buttons .btn:hover { transform: scale(1.05); }

    /* ===== REST OF EXISTING STYLES REMAIN UNCHANGED ===== */
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: 1.5rem;
    }
    .pagination {
        gap: 4px;
        flex-wrap: wrap;
    }
    .pagination a, .pagination span {
        padding: 0.4rem 0.8rem;
        border-radius: 8px;
        border: 1px solid var(--gray-200);
        color: var(--gray-700);
        font-size: 0.8rem;
        text-decoration: none;
        transition: var(--transition);
    }
    .pagination a:hover { background: var(--gray-100); }
    .pagination .active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    .pagination .disabled {
        color: var(--gray-300);
        pointer-events: none;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1050;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
        backdrop-filter: blur(2px);
    }
    .modal.show { display: block; }
    .modal-dialog {
        position: relative;
        width: auto;
        margin: 1.75rem auto;
        max-width: 500px;
        animation: slideDown 0.25s ease;
    }
    .modal-dialog.modal-lg { max-width: 800px; }
    @keyframes slideDown {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .modal-content {
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        border: none;
        overflow: hidden;
    }
    .modal-header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .modal-header .modal-title {
        font-weight: 600;
        font-size: 1rem;
        margin: 0;
    }
    .modal-body { padding: 1.25rem; }
    .modal-footer {
        padding: 0.75rem 1.25rem;
        border-top: 1px solid var(--gray-200);
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .btn-close {
        background: transparent;
        border: none;
        font-size: 1.25rem;
        line-height: 1;
        opacity: 0.5;
        cursor: pointer;
        padding: 0.25rem;
    }
    .btn-close:hover { opacity: 0.8; }

    .payment-history { max-height: 400px; overflow-y: auto; }
    .refresh-btn { cursor: pointer; transition: transform 0.3s; }
    .refresh-btn:hover { transform: rotate(180deg); }

    .filter-row .form-control,
    .filter-row .form-select,
    .filter-row .btn {
        height: 38px;
        font-size: 0.875rem;
    }
    .filter-row .btn {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .filter-row .col-md-1 {
        display: flex;
        align-items: flex-end;
    }
    .filter-row .col-md-1 .btn {
        width: 100%;
    }
    .filter-row .form-label {
        margin-bottom: 0.2rem;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--gray-600);
        display: block;
    }

    @media (max-width: 992px) {
        .table thead th, .table tbody td { font-size: 0.75rem; padding: 0.4rem 0.3rem; }
        .col-bill, .col-patient, .col-type, .col-total, .col-paid, .col-discount, .col-roundoff, .col-due, .col-status, .col-actions {
            min-width: auto !important;
        }
        .action-buttons .btn { padding: 0.1rem 0.3rem; font-size: 0.65rem; min-width: 22px; }
    }
    @media (max-width: 768px) {
        .stat-grid { grid-template-columns: repeat(2, 1fr); }
        .filter-card .row > div { margin-bottom: 0.5rem; }
        .page-header { flex-direction: column; align-items: stretch; gap: 0.5rem; }
        .table-card .card-header { flex-wrap: wrap; }
        .table { font-size: 0.7rem; }
        .table thead th, .table tbody td { padding: 0.3rem 0.2rem; }
        .action-buttons .btn { min-width: 18px; padding: 0.1rem 0.2rem; font-size: 0.6rem; }
        .action-buttons .btn i { font-size: 0.6rem; }
        .filter-row .form-control,
        .filter-row .form-select,
        .filter-row .btn {
            height: 34px;
            font-size: 0.8rem;
        }
    }
    @media (max-width: 576px) {
        .stat-grid { grid-template-columns: 1fr; }
        .table-responsive { overflow-x: auto; }
        .modal-dialog { margin: 0.5rem; }
        .filter-row .col-md-1 {
            align-items: stretch;
        }
    }
</style>

<div class="container-fluid">
    <div class="page-header">
        <div>
            <h5><i class="fas fa-file-invoice-dollar text-success me-2"></i>All Bills</h5>
            <p class="text-muted small mb-0">Manage all invoices including POS, Consultation & Manual bills</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/bills/create" class="btn btn-success btn-sm me-2">
                <i class="fas fa-plus me-1"></i>New Bill
            </a>
            <button onclick="exportBills()" class="btn btn-info btn-sm me-2">
                <i class="fas fa-file-excel me-1"></i>Export
            </button>
            <a href="<?php echo BASE_URL; ?>/bills/fix-balances" class="btn btn-warning btn-sm me-2" onclick="return confirm('This will recalculate all bill balances. Continue?')">
                <i class="fas fa-calculator me-1"></i>Fix Balances
            </a>
            <button onclick="location.reload()" class="btn btn-secondary btn-sm refresh-btn" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stat-grid">
        <div class="stat-card count">
            <div class="stat-number"><?php echo number_format($totalBillsAll ?? $totalBills); ?></div>
            <div class="stat-label">Total Bills</div>
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
        </div>
        <div class="stat-card total">
            <div class="stat-number">৳ <?php echo number_format($totalAmountAll ?? $totalAmount, 2); ?></div>
            <div class="stat-label">Total Gross Amount</div>
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
        </div>
        <div class="stat-card paid">
            <div class="stat-number">৳ <?php echo number_format($totalPaidAll ?? $totalPaid, 2); ?></div>
            <div class="stat-label">Total Paid</div>
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="stat-card due">
            <div class="stat-number">৳ <?php echo number_format($totalDueAll ?? $totalDue, 2); ?></div>
            <div class="stat-label">Total Due</div>
            <div class="stat-icon"><i class="fas fa-clock"></i></div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="row g-2 filter-row">
            <div class="col-md-2">
                <label class="form-label">From Date</label>
                <input type="date" id="filterDateFrom" class="form-control form-control-sm" value="<?php echo $dateFrom ?? ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">To Date</label>
                <input type="date" id="filterDateTo" class="form-control form-control-sm" value="<?php echo $dateTo ?? ''; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Bill Type</label>
                <select id="filterBillType" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="pharmacy" <?php echo ($billTypeFilter ?? '') == 'pharmacy' ? 'selected' : ''; ?>>Pharmacy (POS)</option>
                    <option value="consultation" <?php echo ($billTypeFilter ?? '') == 'consultation' ? 'selected' : ''; ?>>Consultation</option>
                    <option value="lab_test" <?php echo ($billTypeFilter ?? '') == 'lab_test' ? 'selected' : ''; ?>>Lab Test</option>
                    <option value="service" <?php echo ($billTypeFilter ?? '') == 'service' ? 'selected' : ''; ?>>Service</option>
                    <option value="other" <?php echo ($billTypeFilter ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo ($statusFilter ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="partial" <?php echo ($statusFilter ?? '') == 'partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="paid" <?php echo ($statusFilter ?? '') == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="refunded" <?php echo ($statusFilter ?? '') == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" id="filterSearch" class="form-control form-control-sm" placeholder="Bill # or Patient" value="<?php echo $search ?? ''; ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-12">
                <button class="btn btn-secondary btn-sm" onclick="resetFilters()"><i class="fas fa-undo me-1"></i>Reset Filters</button>
            </div>
        </div>
    </div>

    <!-- Bills Table -->
    <div class="table-card">
        <div class="card-header">
            <h6><i class="fas fa-list me-2"></i>Bills List</h6>
            <span class="badge bg-secondary"><?php echo count($bills); ?> records</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th class="col-bill">Bill Number</th>
                        <th class="col-patient">Patient</th>
                        <th class="col-date">Date</th>
                        <th class="col-type">Service / Bill Type</th>
                        <th class="col-items">Items</th>
                        <th class="col-total">Gross (৳)</th>
                        <th class="col-discount">Discount (৳)</th>
                        <th class="col-roundoff">Round Off (৳)</th>
                        <th class="col-paid">Paid (৳)</th>
                        <th class="col-due">Due (৳)</th>
                        <th class="col-status">Status</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($bills)): ?>
                        <?php foreach($bills as $bill): 
                            // Calculate values
                            $grossAmount = (float)($bill['subtotal'] ?? 0);
                            $totalDiscount = (float)($bill['discount_amount'] ?? 0);
                            $paidAmount = (float)($bill['paid_amount'] ?? 0);
                            
                            // Net Amount Raw = Gross - Discount
                            $netAmountRaw = $grossAmount - $totalDiscount;
                            if ($netAmountRaw < 0) $netAmountRaw = 0;
                            
                            // Round Off = difference between raw and rounded
                            $netAmount = round($netAmountRaw);
                            $roundOff = $netAmountRaw - $netAmount;
                            
                            // Due = Net Amount - Paid Amount
                            $dueAmount = $netAmount - $paidAmount;
                            if ($dueAmount < 0) $dueAmount = 0;
                            
                            // Determine payment status
                            if ($dueAmount == 0 && $paidAmount > 0) {
                                $status = 'paid';
                            } elseif ($paidAmount > 0 && $dueAmount > 0) {
                                $status = 'partial';
                            } else {
                                $status = 'pending';
                            }
                            
                            // Check if user has edit permission using the function defined above
                            $canEdit = hasPermission('edit_bills');
                        ?>
                        <tr>
                            <td class="col-bill">
                                <strong><?php echo $bill['bill_number']; ?></strong>
                                <br>
                                <?php 
                                $refType = $bill['reference_type'] ?? '';
                                if($refType == 'pharmacy_sale'): ?>
                                    <span class="badge-bill-type badge-pharmacy" style="font-size: 0.6rem;">POS</span>
                                <?php elseif($refType == 'appointment'): ?>
                                    <span class="badge-bill-type badge-consultation" style="font-size: 0.6rem;">Appointment</span>
                                <?php elseif($refType == 'lab_order'): ?>
                                    <span class="badge-bill-type badge-lab_test" style="font-size: 0.6rem;">Lab</span>
                                <?php else: ?>
                                    <span class="badge-bill-type badge-other" style="font-size: 0.6rem;">Manual</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-patient">
                                <strong><?php echo $bill['first_name'] . ' ' . $bill['last_name']; ?></strong>
                                <br><small class="text-muted"><?php echo $bill['patient_code']; ?></small>
                            </td>
                            <td class="col-date"><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></td>
                            
                            <!-- Service Column -->
                            <td class="col-type">
                                <?php 
                                $serviceName = $bill['service_name'] ?? 'Consultation';
                                $serviceType = $bill['service_type'] ?? 'consultation';
                                $servicePrice = $bill['service_price'] ?? 0;
                                
                                $typeClass = 'badge-consultation';
                                $displayType = ucfirst($serviceType);
                                
                                if($serviceType == 'pharmacy' || $serviceType == 'medicine') {
                                    $typeClass = 'badge-pharmacy';
                                    $displayType = 'Pharmacy';
                                } elseif($serviceType == 'lab_test') {
                                    $typeClass = 'badge-lab_test';
                                    $displayType = 'Lab Test';
                                } elseif($serviceType == 'service' || $serviceType == 'procedure') {
                                    $typeClass = 'badge-service';
                                    $displayType = 'Service';
                                } elseif($serviceType == 'consultation') {
                                    $typeClass = 'badge-consultation';
                                    $displayType = 'Consultation';
                                } else {
                                    $typeClass = 'badge-other';
                                    $displayType = ucfirst($serviceType);
                                }
                                ?>
                                <span class="badge-bill-type <?php echo $typeClass; ?>" 
                                      title="Service Type: <?php echo $displayType; ?>">
                                    <?php echo htmlspecialchars($serviceName); ?>
                                </span>
                                <br>
                                <small class="text-muted">Type: <?php echo $displayType; ?></small>
                                <?php if($servicePrice > 0): ?>
                                    <br><small class="text-muted">Price: ৳ <?php echo number_format($servicePrice, 2); ?></small>
                                <?php endif; ?>
                            </td>
                            
                            <td class="col-items text-center">
                                <?php 
                                $itemCount = isset($bill['item_count']) ? (int)$bill['item_count'] : 0;
                                ?>
                                <?php if($itemCount > 0): ?>
                                    <span class="badge bg-secondary"><?php echo $itemCount; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-total"><strong>৳ <?php echo number_format($grossAmount, 2); ?></strong></td>
                            <!-- Discount Column -->
                            <td class="col-discount" style="background: #fef3c7;">
                                <strong style="color: #92400e;">৳ <?php echo number_format($totalDiscount, 2); ?></strong>
                                <?php 
                                $discountPct = 0;
                                $grossAmount = (float)($bill['subtotal'] ?? 0);
                                if ($grossAmount > 0 && $totalDiscount > 0) {
                                    $discountPct = round(($totalDiscount / $grossAmount) * 100, 2);
                                }
                                
                                $itemDisc = (float)($bill['total_item_discount'] ?? 0);
                                $globalDisc = (float)($bill['global_discount'] ?? 0);
                                ?>
                                <br>
                                <small style="color: #92400e;">
                                    (<?php echo number_format($discountPct, 2); ?>%)
                                    <?php if($itemDisc > 0 && $globalDisc > 0): ?>
                                        <span title="Item Discount: ৳ <?php echo number_format($itemDisc, 2); ?>, Global Discount: ৳ <?php echo number_format($globalDisc, 2); ?>">
                                            <i class="fas fa-info-circle" style="cursor: help;"></i>
                                        </span>
                                    <?php elseif($itemDisc > 0): ?>
                                        <span title="Item Discount: ৳ <?php echo number_format($itemDisc, 2); ?>">
                                            <i class="fas fa-info-circle" style="cursor: help;"></i>
                                        </span>
                                    <?php elseif($globalDisc > 0): ?>
                                        <span title="Global Discount: ৳ <?php echo number_format($globalDisc, 2); ?>">
                                            <i class="fas fa-info-circle" style="cursor: help;"></i>
                                        </span>
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td class="col-roundoff"><?php echo ($roundOff != 0) ? number_format($roundOff, 2) : '0.00'; ?></td>
                            <td class="col-paid">৳ <?php echo number_format($paidAmount, 2); ?></td>
                            <td class="col-due">
                                <?php if($dueAmount > 0): ?>
                                    <span class="text-danger fw-bold">৳ <?php echo number_format($dueAmount, 2); ?></span>
                                <?php else: ?>
                                    <span class="text-success">৳ 0.00</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-status">
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                            
                            <!-- ===== ACTIONS COLUMN WITH EDIT BUTTON ===== -->
                            <td class="col-actions">
                                <div class="action-buttons">
                                    <a href="<?php echo BASE_URL; ?>/bills/view/<?php echo $bill['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Invoice">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- ===== EDIT BUTTON - Only show if user has permission ===== -->
                                    <?php if($canEdit): ?>
                                    <a href="<?php echo BASE_URL; ?>/bills/edit/<?php echo $bill['id']; ?>" class="btn btn-sm btn-edit" title="Edit Bill">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="printBill(<?php echo $bill['id']; ?>)" class="btn btn-sm btn-outline-secondary" title="Print Invoice">
                                        <i class="fas fa-print"></i>
                                    </button>
                                    <?php if($dueAmount > 0): ?>
                                    <button onclick="showPaymentModal(<?php echo $bill['id']; ?>, <?php echo $dueAmount; ?>, <?php echo $bill['patient_id']; ?>)" class="btn btn-sm btn-outline-success" title="Receive Payment">
                                        <i class="fas fa-credit-card"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button onclick="showPaymentHistory(<?php echo $bill['id']; ?>)" class="btn btn-sm btn-outline-info" title="Payment History">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="12" class="text-center py-4 text-muted">No bills found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="pagination-wrapper">
        <div class="pagination">
            <?php if($currentPage > 1): ?>
                <a href="?page=<?php echo $currentPage - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">&laquo; Previous</a>
            <?php else: ?>
                <span class="disabled">&laquo; Previous</span>
            <?php endif; ?>
            
            <?php
            $start = max(1, $currentPage - 2);
            $end = min($totalPages, $currentPage + 2);
            
            if($start > 1): ?>
                <a href="?page=1&<?php echo http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">1</a>
                <?php if($start > 2): ?><span>...</span><?php endif; ?>
            <?php endif; ?>
            
            <?php for($i = $start; $i <= $end; $i++): ?>
                <?php if($i == $currentPage): ?>
                    <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if($end < $totalPages): ?>
                <?php if($end < $totalPages - 1): ?><span>...</span><?php endif; ?>
                <a href="?page=<?php echo $totalPages; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"><?php echo $totalPages; ?></a>
            <?php endif; ?>
            
            <?php if($currentPage < $totalPages): ?>
                <a href="?page=<?php echo $currentPage + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return $key != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">Next &raquo;</a>
            <?php else: ?>
                <span class="disabled">Next &raquo;</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title"><i class="fas fa-credit-card me-2"></i>Receive Payment</h6>
                <button type="button" class="btn-close btn-close-white" onclick="closePaymentModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="paymentForm">
                    <input type="hidden" name="bill_id" id="paymentBillId">
                    <input type="hidden" name="patient_id" id="paymentPatientId">
                    
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Due Amount</label>
                        <input type="text" id="dueAmount" class="form-control form-control-sm" readonly>
                    </div>
                    
                    <div class="mb-2">
                        <div class="form-check">
                            <input type="checkbox" id="fullDiscountCheck" class="form-check-input">
                            <label class="form-check-label small" for="fullDiscountCheck">
                                <strong>✅ Full Discount</strong> – Mark as Paid without receiving cash
                            </label>
                        </div>
                    </div>
                    
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Discount Applied (%)</label>
                            <input type="number" step="0.01" min="0" max="100" id="discountPercent" class="form-control form-control-sm" placeholder="e.g. 10">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Discount Amount (৳)</label>
                            <input type="number" step="0.01" min="0" id="discountAmount" class="form-control form-control-sm" placeholder="e.g. 50">
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Receive Amount (after discount) *</label>
                        <input type="number" step="0.01" name="amount" id="paymentAmount" class="form-control form-control-sm" required>
                        <small class="text-muted">Enter <strong>0</strong> if discount covers the full due amount</small>
                        <div id="discountCoversDue" class="alert alert-success mt-1" style="display:none; font-size:0.8rem;">
                            ✅ This discount covers the full due amount. Click <strong>Process Payment</strong> to mark as Paid.
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Payment Method *</label>
                        <select name="payment_method" id="paymentMethodSelect" class="form-select form-select-sm" required>
                            <option value="cash">💵 Cash</option>
                            <option value="card">💳 Card</option>
                            <option value="mobile_banking">📱 Mobile Banking</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Transaction ID</label>
                        <input type="text" name="transaction_id" id="transactionId" class="form-control form-control-sm" placeholder="Optional">
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Notes</label>
                        <textarea name="notes" id="paymentNotes" class="form-control form-control-sm" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closePaymentModal()">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" onclick="submitPayment()">Process Payment</button>
            </div>
        </div>
    </div>
</div>

<!-- Payment History Modal -->
<div id="paymentHistoryModal" class="modal">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h6 class="modal-title"><i class="fas fa-history me-2"></i>Payment History</h6>
                <button type="button" class="btn-close btn-close-white" onclick="closePaymentHistoryModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="paymentHistoryContent" class="payment-history">
                    <div class="text-center py-4 text-muted">Loading payment history...</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" onclick="closePaymentHistoryModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

let currentBillId = null;
let currentDueAmount = null;
let currentPatientId = null;

// ============================================================
// SHOW PAYMENT MODAL
// ============================================================
function showPaymentModal(billId, dueAmount, patientId) {
    currentBillId = billId;
    currentDueAmount = parseFloat(dueAmount) || 0;
    currentPatientId = patientId;
    
    $('#paymentBillId').val(billId);
    $('#paymentPatientId').val(patientId);
    $('#dueAmount').val('৳ ' + currentDueAmount.toFixed(2));
    $('#paymentAmount').val(currentDueAmount);
    $('#discountPercent').val('');
    $('#discountAmount').val('');
    $('#paymentMethodSelect').val('cash');
    $('#transactionId').val('');
    $('#paymentNotes').val('');
    $('#discountCoversDue').hide();
    $('#fullDiscountCheck').prop('checked', false);
    
    document.getElementById('paymentModal').style.display = 'block';
    document.getElementById('paymentModal').classList.add('show');
}

function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
    document.getElementById('paymentModal').classList.remove('show');
}

// ============================================================
// FULL DISCOUNT
// ============================================================
$(document).on('change', '#fullDiscountCheck', function() {
    let due = currentDueAmount || 0;
    if ($(this).is(':checked')) {
        $('#discountAmount').val(due.toFixed(2));
        $('#discountPercent').val('');
        $('#paymentAmount').val(0);
        $('#discountCoversDue').show();
        $('#discountPercent').prop('disabled', true);
    } else {
        $('#discountAmount').val('');
        $('#discountPercent').val('');
        $('#discountPercent').prop('disabled', false);
        $('#paymentAmount').val(due.toFixed(2));
        $('#discountCoversDue').hide();
    }
    $('#discountAmount').trigger('input');
});

// ============================================================
// UPDATE RECEIVE AMOUNT on discount change
// ============================================================
$(document).on('input', '#discountPercent, #discountAmount', function() {
    let due = currentDueAmount;
    let discPercent = parseFloat($('#discountPercent').val()) || 0;
    let discAmount = parseFloat($('#discountAmount').val()) || 0;
    
    let discount = 0;
    if ($('#discountAmount').val() !== '') {
        discount = discAmount;
    } else if (discPercent > 0) {
        discount = due * (discPercent / 100);
    }
    if (discount > due) discount = due;
    
    let receiveAmount = due - discount;
    $('#paymentAmount').val(receiveAmount.toFixed(2));
    
    if (discount > 0 && Math.abs(discount - due) < 0.01) {
        $('#discountCoversDue').show();
    } else {
        $('#discountCoversDue').hide();
    }
});

// ============================================================
// PAYMENT HISTORY
// ============================================================
function showPaymentHistory(billId) {
    $('#paymentHistoryContent').html(`
        <div class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-2x text-info mb-3"></i>
            <p>Loading payment history...</p>
        </div>
    `);
    
    document.getElementById('paymentHistoryModal').style.display = 'block';
    document.getElementById('paymentHistoryModal').classList.add('show');
    
    $.ajax({
        url: BASE_URL + '/bills/get-payment-history?bill_id=' + billId,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success && response.payments && response.payments.length > 0) {
                let html = `
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Payment #</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Transaction ID</th>
                                    <th>Received By</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                response.payments.forEach(function(p) {
                    let paymentDate = p.payment_date ? p.payment_date : (p.created_at ? p.created_at.split(' ')[0] : '-');
                    let methodDisplay = '';
                    if(p.payment_method == 'cash') methodDisplay = '💵 Cash';
                    else if(p.payment_method == 'card') methodDisplay = '💳 Card';
                    else if(p.payment_method == 'mobile_banking') methodDisplay = '📱 Mobile Banking';
                    else if(p.payment_method == 'bank_transfer') methodDisplay = '🏦 Bank Transfer';
                    else methodDisplay = p.payment_method;
                    
                    html += `
                        <tr>
                            <td>${paymentDate}</td>
                            <td><small>${p.payment_number || '-'}</small></td>
                            <td class="text-success fw-bold">৳ ${parseFloat(p.amount).toFixed(2)}</td>
                            <td>${methodDisplay}</td>
                            <td><small>${p.transaction_id || '-'}</small></td>
                            <td><small>${p.received_by_name || '-'}</small></td>
                            <td><small>${p.notes || '-'}</small></td>
                        </tr>
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                $('#paymentHistoryContent').html(html);
            } else {
                $('#paymentHistoryContent').html(`
                    <div class="text-center py-5">
                        <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No payment records found for this bill.</p>
                        <p class="small text-muted">Click the payment button to receive payment.</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            $('#paymentHistoryContent').html(`
                <div class="text-center py-5 text-danger">
                    <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                    <p>Failed to load payment history. Please try again.</p>
                    <button class="btn btn-sm btn-outline-danger mt-2" onclick="showPaymentHistory(${billId})">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </div>
            `);
        }
    });
}

function closePaymentHistoryModal() {
    document.getElementById('paymentHistoryModal').style.display = 'none';
    document.getElementById('paymentHistoryModal').classList.remove('show');
}

// ============================================================
// FILTERS & EXPORT
// ============================================================
function applyFilters() {
    let params = new URLSearchParams();
    if($('#filterDateFrom').val()) params.append('date_from', $('#filterDateFrom').val());
    if($('#filterDateTo').val()) params.append('date_to', $('#filterDateTo').val());
    if($('#filterStatus').val()) params.append('status', $('#filterStatus').val());
    if($('#filterBillType').val()) params.append('bill_type', $('#filterBillType').val());
    if($('#filterSearch').val()) params.append('search', $('#filterSearch').val());
    
    window.location.href = BASE_URL + '/bills?' + params.toString();
}

function resetFilters() {
    window.location.href = BASE_URL + '/bills';
}

function exportBills() {
    let params = new URLSearchParams();
    if($('#filterDateFrom').val()) params.append('date_from', $('#filterDateFrom').val());
    if($('#filterDateTo').val()) params.append('date_to', $('#filterDateTo').val());
    if($('#filterStatus').val()) params.append('status', $('#filterStatus').val());
    if($('#filterBillType').val()) params.append('bill_type', $('#filterBillType').val());
    if($('#filterSearch').val()) params.append('search', $('#filterSearch').val());
    
    window.location.href = BASE_URL + '/bills/export?' + params.toString();
}

function printBill(billId) {
    var printWindow = window.open(BASE_URL + '/bills/print/' + billId, '_blank', 'width=800,height=600');
    if (!printWindow) {
        Swal.fire({
            icon: 'warning',
            title: 'Popup Blocked',
            text: 'Please allow popups for this site or click the link below.',
            footer: '<a href="' + BASE_URL + '/bills/print/' + billId + '" target="_blank">Open Invoice in New Tab</a>'
        });
    }
}

// ============================================================
// SUBMIT PAYMENT
// ============================================================
function submitPayment() {
    let amount = parseFloat($('#paymentAmount').val());
    let dueAmount = currentDueAmount;
    let discountPercent = parseFloat($('#discountPercent').val()) || 0;
    let discountAmount = parseFloat($('#discountAmount').val()) || 0;
    let fullDiscount = $('#fullDiscountCheck').is(':checked') ? 1 : 0;
    
    if (fullDiscount) {
        amount = 0;
        discountAmount = dueAmount;
        $('#paymentAmount').val(0);
        $('#discountAmount').val(dueAmount);
    }
    
    if (isNaN(amount) || amount < 0) {
        Swal.fire('Error', 'Please enter a valid receive amount (0 or more)', 'error');
        return;
    }
    
    let totalApplied = discountAmount + amount;
    if (totalApplied > dueAmount + 0.01) {
        Swal.fire('Error', 'Total of discount and receive amount cannot exceed due amount of ৳ ' + dueAmount.toFixed(2), 'error');
        return;
    }
    
    let formData = $('#paymentForm').serialize();
    formData += '&discount_percent=' + discountPercent;
    formData += '&discount_amount=' + discountAmount;
    formData += '&full_discount=' + fullDiscount;
    
    Swal.fire({
        title: 'Processing Payment...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/api/process-bill-payment',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let message = response.message;
                if (response.new_balance == 0) {
                    message += ' Bill is now fully paid!';
                }
                Swal.fire({
                    title: 'Success!',
                    text: message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    closePaymentModal();
                    location.reload();
                });
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            let errorMsg = 'Payment processing failed. Please try again.';
            try {
                let response = JSON.parse(xhr.responseText);
                if(response.message) errorMsg = response.message;
            } catch(e) {}
            Swal.fire('Error', errorMsg, 'error');
        }
    });
}

// ============================================================
// KEYBOARD SHORTCUTS & CLOSE MODALS ON CLICK OUTSIDE
// ============================================================
$('#filterSearch').on('keypress', function(e) {
    if(e.which === 13) applyFilters();
});

window.onclick = function(event) {
    let paymentModal = document.getElementById('paymentModal');
    let historyModal = document.getElementById('paymentHistoryModal');
    if (event.target == paymentModal) {
        closePaymentModal();
    }
    if (event.target == historyModal) {
        closePaymentHistoryModal();
    }
}
</script>