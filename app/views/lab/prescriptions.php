<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .prescription-card {
        border-left: 4px solid #8b5cf6;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .prescription-card:hover {
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .prescription-card.has-order {
        border-left-color: #10b981;
    }
    .prescription-card.no-order {
        border-left-color: #f59e0b;
    }
    .status-badge {
        font-size: 11px;
        padding: 3px 10px;
        border-radius: 12px;
    }
    .status-badge.ordered { background: #dbeafe; color: #1d4ed8; }
    .status-badge.completed { background: #d1fae5; color: #065f46; }
    .status-badge.pending { background: #fef3c7; color: #92400e; }
    .status-badge.canceled { background: #fee2e2; color: #991b1b; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2><i class="fas fa-prescription-bottle text-primary me-2"></i>Lab-Test Prescriptions</h2>
        <p class="text-muted small">View prescriptions with lab tests and create lab orders</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/lab/dashboard" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<!-- Statistics -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card bg-light border-0 shadow-sm">
            <div class="card-body text-center py-2">
                <h5 class="mb-0 text-primary"><?php echo $total; ?></h5>
                <small class="text-muted">Total Prescriptions</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-light border-0 shadow-sm">
            <div class="card-body text-center py-2">
                <h5 class="mb-0 text-success"><?php echo $withOrders; ?></h5>
                <small class="text-muted">With Lab Orders</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-light border-0 shadow-sm">
            <div class="card-body text-center py-2">
                <h5 class="mb-0 text-warning"><?php echo $withoutOrders; ?></h5>
                <small class="text-muted">Pending Orders</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card bg-light border-0 shadow-sm">
            <div class="card-body text-center py-2">
                <h5 class="mb-0 text-info"><?php echo $pendingTests; ?></h5>
                <small class="text-muted">Pending Tests</small>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>/lab/prescriptions" class="row g-2">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by Rx #, Patient..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($dateFrom); ?>" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($dateTo); ?>" placeholder="To">
            </div>
            <div class="col-md-2">
                <select name="has_order" class="form-select form-select-sm">
                    <option value="">All Orders</option>
                    <option value="yes" <?php echo $hasOrder == 'yes' ? 'selected' : ''; ?>>With Orders</option>
                    <option value="no" <?php echo $hasOrder == 'no' ? 'selected' : ''; ?>>Without Orders</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="<?php echo BASE_URL; ?>/lab/prescriptions" class="btn btn-secondary btn-sm">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Prescriptions List -->
<div class="row">
    <?php if(!empty($prescriptions)): ?>
        <?php foreach($prescriptions as $rx): 
            $hasOrder = !empty($rx['existing_order_id']);
            $cardClass = $hasOrder ? 'has-order' : 'no-order';
        ?>
        <div class="col-md-6 col-xl-4 mb-4">
            <div class="card prescription-card <?php echo $cardClass; ?> shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1">
                                <a href="<?php echo BASE_URL; ?>/lab/view-prescription-tests/<?php echo $rx['id']; ?>" class="text-decoration-none">
                                    Rx #<?php echo $rx['prescription_number']; ?>
                                </a>
                            </h6>
                            <small class="text-muted"><?php echo date('d M Y', strtotime($rx['prescription_date'])); ?></small>
                        </div>
                        <?php if($hasOrder): ?>
                            <span class="badge bg-success">Order Created</span>
                        <?php else: ?>
                            <span class="badge bg-warning">Pending</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-7">
                            <small class="text-muted">Patient</small>
                            <div><strong><?php echo htmlspecialchars($rx['patient_name']); ?></strong></div>
                            <small><?php echo htmlspecialchars($rx['patient_code']); ?></small>
                        </div>
                        <div class="col-5">
                            <small class="text-muted">Doctor</small>
                            <div><strong><?php echo htmlspecialchars($rx['doctor_name']); ?></strong></div>
                            <small><?php echo htmlspecialchars($rx['specialization'] ?? ''); ?></small>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <small class="text-muted">Lab Tests (<?php echo $rx['lab_test_count']; ?>)</small>
                        <p class="mb-0 small text-truncate"><?php echo htmlspecialchars($rx['lab_test_names'] ?? ''); ?></p>
                    </div>
                    
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-flask text-muted me-1"></i>
                            <small class="text-muted"><?php echo $rx['lab_test_count']; ?> test(s)</small>
                        </div>
                        <div>
                            <?php if($hasOrder): ?>
                                <a href="<?php echo BASE_URL; ?>/lab/view-order/<?php echo $rx['existing_order_id']; ?>" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-eye"></i> View Order
                                </a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>/lab/view-prescription-tests/<?php echo $rx['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus"></i> Create Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-prescription-bottle fa-3x mb-3"></i>
                <p>No prescriptions with lab tests found</p>
                <small class="text-muted">Prescriptions with lab tests will appear here</small>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Pagination -->
<?php if($totalPages > 1): ?>
<div class="d-flex justify-content-between align-items-center mt-4">
    <div>
        <small class="text-muted">Showing <?php echo count($prescriptions); ?> of <?php echo $totalPrescriptions; ?> records</small>
    </div>
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <?php if($currentPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&has_order=<?php echo urlencode($hasOrder); ?>">
                        Previous
                    </a>
                </li>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&has_order=<?php echo urlencode($hasOrder); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
            
            <?php if($currentPage < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>&has_order=<?php echo urlencode($hasOrder); ?>">
                        Next
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
<?php endif; ?>