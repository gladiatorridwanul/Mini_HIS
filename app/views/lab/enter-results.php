<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<style>
    .result-card {
        transition: all 0.3s;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        margin-bottom: 20px;
    }
    .result-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .status-processing { background: #f3e8ff; color: #6b21a5; }
    .status-sample_collected { background: #dbeafe; color: #1e40af; }
    .status-completed { background: #d1fae5; color: #10b981; }
    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }
    .btn-sm { padding: 5px 10px; font-size: 12px; }
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        border: 1px solid #e5e7eb;
    }
    .stat-number { font-size: 28px; font-weight: 700; }
    .stat-label { font-size: 12px; color: #6c757d; }
    .patient-group-header {
        cursor: pointer;
        transition: background 0.2s;
    }
    .patient-group-header:hover { background: #f8fafc; }
    .test-item {
        border-bottom: 1px solid #f1f5f9;
        padding: 8px 0;
    }
    .test-item:last-child { border-bottom: none; }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-edit text-success me-2"></i>Enter Test Results</h2>
            <p class="text-muted small mb-0">Enter and manage laboratory test results</p>
        </div>
        <div>
            <button onclick="location.reload()" class="btn btn-secondary btn-sm">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="stat-card bg-warning bg-opacity-10">
                <div class="stat-number text-warning"><?php echo isset($pendingResults) ? $pendingResults : 0; ?></div>
                <div class="stat-label">Awaiting Results Entry</div>
                <i class="fas fa-hourglass-half text-warning mt-2"></i>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="stat-card bg-primary bg-opacity-10">
                <div class="stat-number text-primary"><?php echo isset($processingCount) ? $processingCount : 0; ?></div>
                <div class="stat-label">In Progress</div>
                <i class="fas fa-spinner fa-pulse text-primary mt-2"></i>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="stat-card bg-success bg-opacity-10">
                <div class="stat-number text-success"><?php echo isset($completedCount) ? $completedCount : 0; ?></div>
                <div class="stat-label">Completed Today</div>
                <i class="fas fa-check-circle text-success mt-2"></i>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Search</label>
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search by patient or test...">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="all">All</option>
                    <option value="sample_collected">Awaiting Results</option>
                    <option value="processing">In Progress</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Date</label>
                <input type="date" id="filterDate" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">&nbsp;</label>
                <button class="btn btn-primary btn-sm w-100" onclick="filterResults()">
                    <i class="fas fa-search me-2"></i>Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Results List Grouped by Patient -->
    <div id="resultsContainer">
        <?php if(!empty($items) && is_array($items) && count($items) > 0): ?>
            <?php 
            // Group items by patient
            $groupedItems = [];
            foreach($items as $item) {
                $patientKey = $item['patient_id'] ?? $item['patient_name'] ?? 'unknown';
                if(!isset($groupedItems[$patientKey])) {
                    $groupedItems[$patientKey] = [
                        'patient_name' => $item['patient_name'] ?? 'Unknown',
                        'patient_phone' => $item['phone'] ?? '',
                        'patient_id' => $item['patient_id'] ?? 0,
                        'tests' => []
                    ];
                }
                $groupedItems[$patientKey]['tests'][] = $item;
            }
            ?>
            
            <?php foreach($groupedItems as $patientKey => $group): ?>
            <div class="card mb-3 result-group" data-patient="<?php echo strtolower($group['patient_name']); ?>">
                <div class="card-header bg-light patient-group-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            <strong><?php echo htmlspecialchars($group['patient_name']); ?></strong>
                            <span class="text-muted ms-2">(<?php echo htmlspecialchars($group['patient_phone']); ?>)</span>
                            <span class="badge bg-secondary ms-2"><?php echo count($group['tests']); ?> tests</span>
                        </h6>
                    </div>
                    <div>
                        <?php 
                        $hasPending = false;
                        foreach($group['tests'] as $test) {
                            if($test['status'] == 'sample_collected' || $test['status'] == 'processing') { 
                                $hasPending = true; 
                                break; 
                            }
                        }
                        ?>
                        <?php if($hasPending): ?>
                        <span class="badge bg-warning text-dark">Pending Results</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Test Name</th>
                                    <th>Order #</th>
                                    <th>Barcode</th>
                                    <th>Normal Range</th>
                                    <th>Result</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($group['tests'] as $item): ?>
                                <?php 
                                    $status = isset($item['status']) ? $item['status'] : 'sample_collected';
                                    $statusText = ($status == 'sample_collected') ? 'AWAITING RESULTS' : (($status == 'processing') ? 'IN PROGRESS' : strtoupper($status));
                                    $statusClass = ($status == 'sample_collected') ? 'sample_collected' : (($status == 'processing') ? 'processing' : 'completed');
                                    $btnClass = ($status == 'sample_collected') ? 'btn-primary' : (($status == 'processing') ? 'btn-warning' : 'btn-success');
                                    $btnText = ($status == 'sample_collected') ? 'Enter Results' : (($status == 'processing') ? 'Continue' : 'View');
                                ?>
                                <tr class="result-item" 
                                    data-status="<?php echo $status; ?>" 
                                    data-patient="<?php echo strtolower($group['patient_name']); ?>"
                                    data-test="<?php echo strtolower($item['test_name'] ?? ''); ?>">
                                    <td><strong><?php echo htmlspecialchars($item['test_name'] ?? 'Lab Test'); ?></strong></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item['order_number'] ?? 'N/A'); ?></span></td>
                                    <td>
                                        <?php if(!empty($item['sample_barcode'])): ?>
                                        <code><?php echo htmlspecialchars($item['sample_barcode']); ?></code>
                                        <?php else: ?>
                                        <span class="text-muted">Not collected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($item['normal_range'])): ?>
                                        <small><?php echo htmlspecialchars($item['normal_range']); ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?></small>
                                        <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($item['result_value'])): ?>
                                        <span class="<?php echo ($item['is_abnormal'] ?? 0) ? 'text-danger fw-bold' : 'text-success'; ?>">
                                            <?php echo htmlspecialchars($item['result_value']); ?>
                                            <?php if($item['is_abnormal'] ?? 0): ?>
                                            <span class="badge bg-danger ms-1">Abnormal</span>
                                            <?php endif; ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">Not entered</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($status == 'completed'): ?>
                                        <a href="<?php echo BASE_URL; ?>/lab/enter-result-form/<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        <?php else: ?>
                                        <a href="<?php echo BASE_URL; ?>/lab/enter-result-form/<?php echo $item['id']; ?>" class="btn btn-sm <?php echo $btnClass; ?>">
                                            <i class="fas fa-edit me-1"></i><?php echo $btnText; ?>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <h5>No pending results to enter</h5>
                <p class="mb-3">Samples need to be collected and received in the lab before results can be entered.</p>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/lab/sample-collection" class="btn btn-primary">
                        <i class="fas fa-syringe me-2"></i>Go to Sample Collection
                    </a>
                    <a href="<?php echo BASE_URL; ?>/lab/create-order" class="btn btn-success ms-2">
                        <i class="fas fa-plus me-2"></i>Create New Order
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function filterResults() {
    var status = $('#filterStatus').val();
    var search = $('#searchInput').val().toLowerCase();
    var date = $('#filterDate').val();
    
    $('.result-group').each(function() {
        var patientName = $(this).data('patient') || '';
        var showGroup = false;
        
        $(this).find('.result-item').each(function() {
            var itemStatus = $(this).data('status');
            var itemPatient = $(this).data('patient') || '';
            var itemTest = $(this).data('test') || '';
            
            var statusMatch = (status === 'all' || itemStatus === status);
            var searchMatch = (search === '' || itemPatient.indexOf(search) > -1 || itemTest.indexOf(search) > -1);
            
            if(statusMatch && searchMatch) {
                $(this).show();
                showGroup = true;
            } else {
                $(this).hide();
            }
        });
        
        if(showGroup) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

$(document).ready(function() {
    $('#searchInput').on('keyup', filterResults);
    $('#filterStatus').on('change', filterResults);
    $('#filterDate').on('change', filterResults);
});
</script>