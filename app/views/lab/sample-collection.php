<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<style>
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        transition: transform 0.3s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        text-align: center;
    }
    .stat-card:hover { transform: translateY(-3px); }
    .stat-number { font-size: 28px; font-weight: 700; }
    .stat-label { font-size: 12px; color: #6c757d; }
    .sample-card {
        transition: all 0.3s;
        border-left: 4px solid #10b981;
        border-radius: 12px;
    }
    .sample-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .sample-card.pending { border-left-color: #f59e0b; }
    .sample-card.sample_collected { border-left-color: #3b82f6; }
    .sample-card.processing { border-left-color: #8b5cf6; }
    .sample-card.completed { border-left-color: #10b981; }
    .barcode-display {
        font-family: monospace;
        font-size: 16px;
        letter-spacing: 2px;
        background: #f8f9fa;
        padding: 8px;
        border-radius: 8px;
        text-align: center;
    }
    .priority-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .priority-stat { background: #fee2e2; color: #ef4444; }
    .priority-urgent { background: #fef3c7; color: #d97706; }
    .priority-routine { background: #e2e8f0; color: #475569; }
    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-sample_collected { background: #dbeafe; color: #1e40af; }
    .status-processing { background: #f3e8ff; color: #6b21a5; }
    .status-completed { background: #d1fae5; color: #10b981; }
    .btn-sm { padding: 5px 10px; font-size: 12px; }
    .modal-content { border-radius: 12px; }
    .modal-header { border-radius: 12px 12px 0 0; }
    .test-item {
        padding: 5px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .test-item:last-child { border-bottom: none; }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-syringe text-success me-2"></i>Sample Collection</h2>
            <p class="text-muted small mb-0">Manage patient sample collection and tracking</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <button class="btn btn-success" onclick="bulkCollect()" id="bulkCollectBtn" style="display: none;">
                <i class="fas fa-check-double me-2"></i>Bulk Collect Selected
            </button>
            <button onclick="location.reload()" class="btn btn-secondary ms-2">
                <i class="fas fa-sync-alt me-2"></i>Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-warning bg-opacity-10">
                <div class="stat-number text-warning"><?php echo isset($pendingCount) ? $pendingCount : 0; ?></div>
                <div class="stat-label">Pending Collection</div>
                <i class="fas fa-hourglass-half text-warning mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-primary bg-opacity-10">
                <div class="stat-number text-primary"><?php echo isset($collectedCount) ? $collectedCount : 0; ?></div>
                <div class="stat-label">Collected</div>
                <i class="fas fa-check-circle text-primary mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-success bg-opacity-10">
                <div class="stat-number text-success"><?php echo isset($todayCollected) ? $todayCollected : 0; ?></div>
                <div class="stat-label">Today's Collection</div>
                <i class="fas fa-calendar-day text-success mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card bg-info bg-opacity-10">
                <div class="stat-number text-info"><?php echo isset($totalPatients) ? $totalPatients : 0; ?></div>
                <div class="stat-label">Total Patients</div>
                <i class="fas fa-users text-info mt-2"></i>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Filter by Status</label>
                <select id="filterStatus" class="form-select form-select-sm">
                    <option value="all">All</option>
                    <option value="pending">Pending Collection</option>
                    <option value="sample_collected">Collected - Ready for Processing</option>
                    <option value="processing">Processing</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Search</label>
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search by patient name or barcode...">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Date</label>
                <input type="date" id="filterDate" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">&nbsp;</label>
                <button class="btn btn-primary btn-sm w-100" onclick="filterSamples()">
                    <i class="fas fa-search me-2"></i>Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Samples List Grouped by Patient -->
    <div id="samplesContainer">
        <?php if(!empty($samples) && is_array($samples)): ?>
            <?php 
            // Group samples by patient
            $groupedSamples = [];
            foreach($samples as $sample) {
                $patientKey = $sample['patient_id'] ?? $sample['patient_name'] ?? 'unknown';
                if(!isset($groupedSamples[$patientKey])) {
                    $groupedSamples[$patientKey] = [
                        'patient_name' => $sample['patient_name'] ?? 'Unknown',
                        'patient_phone' => $sample['phone'] ?? '',
                        'patient_id' => $sample['patient_id'] ?? 0,
                        'tests' => []
                    ];
                }
                $groupedSamples[$patientKey]['tests'][] = $sample;
            }
            ?>
            
            <?php foreach($groupedSamples as $patientKey => $group): ?>
            <div class="card mb-4 sample-group" data-patient="<?php echo strtolower($group['patient_name']); ?>">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
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
                            if($test['status'] == 'pending') { $hasPending = true; break; }
                        }
                        if($hasPending): 
                        ?>
                        <button class="btn btn-sm btn-success" onclick="collectAllPatientSamples(<?php echo $group['patient_id']; ?>)">
                            <i class="fas fa-syringe me-1"></i>Collect All
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30px;">
                                        <input type="checkbox" class="form-check-input patient-select-all" data-patient="<?php echo $group['patient_id']; ?>">
                                    </th>
                                    <th>Test Name</th>
                                    <th>Order #</th>
                                    <th>Priority</th>
                                    <th>Barcode</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($group['tests'] as $test): ?>
                                <?php 
                                    $sampleStatus = isset($test['status']) ? $test['status'] : 'pending';
                                    $samplePriority = isset($test['priority']) ? $test['priority'] : 'routine';
                                    $sampleBarcode = isset($test['sample_barcode']) ? $test['sample_barcode'] : '';
                                    $sampleId = isset($test['id']) ? $test['id'] : 0;
                                    $sampleOrderNumber = isset($test['order_number']) ? $test['order_number'] : 'N/A';
                                    $sampleTestName = isset($test['test_name']) ? $test['test_name'] : 'Lab Test';
                                    $sampleSpecimenType = isset($test['specimen_type']) ? $test['specimen_type'] : 'N/A';
                                    $sampleDoctorName = isset($test['doctor_name']) ? $test['doctor_name'] : 'Doctor';
                                    $sampleResult = isset($test['result_value']) ? $test['result_value'] : '';
                                    $sampleCollectedAt = isset($test['sample_collected_at']) ? $test['sample_collected_at'] : '';
                                    
                                    $priorityClass = 'priority-routine';
                                    $priorityText = 'ROUTINE';
                                    if($samplePriority == 'stat') {
                                        $priorityClass = 'priority-stat';
                                        $priorityText = 'STAT';
                                    } elseif($samplePriority == 'urgent') {
                                        $priorityClass = 'priority-urgent';
                                        $priorityText = 'URGENT';
                                    }
                                ?>
                                <tr class="sample-row" data-status="<?php echo $sampleStatus; ?>" data-patient="<?php echo $group['patient_id']; ?>">
                                    <td>
                                        <?php if($sampleStatus == 'pending'): ?>
                                        <input type="checkbox" class="form-check-input sample-checkbox" value="<?php echo $sampleId; ?>" onchange="updateBulkButton()">
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($sampleTestName); ?></strong></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($sampleOrderNumber); ?></span></td>
                                    <td><span class="priority-badge <?php echo $priorityClass; ?>"><?php echo $priorityText; ?></span></td>
                                    <td>
                                        <?php if($sampleBarcode): ?>
                                        <code><?php echo htmlspecialchars($sampleBarcode); ?></code>
                                        <button class="btn btn-sm btn-outline-secondary ms-1" onclick="printBarcode(<?php echo $sampleId; ?>)">
                                            <i class="fas fa-print"></i>
                                        </button>
                                        <?php else: ?>
                                        <span class="text-muted">Not generated</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $sampleStatus; ?>">
                                            <?php 
                                            if($sampleStatus == 'sample_collected') echo 'READY FOR PROCESSING';
                                            elseif($sampleStatus == 'completed') echo 'COMPLETED';
                                            else echo strtoupper(str_replace('_', ' ', $sampleStatus)); 
                                            ?>
                                        </span>
                                        <?php if($sampleResult): ?>
                                        <br><small class="text-success">Result: <?php echo htmlspecialchars($sampleResult); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if($sampleStatus == 'pending'): ?>
                                                <?php if(!$sampleBarcode): ?>
                                                <button class="btn btn-warning" onclick="generateBarcode(<?php echo $sampleId; ?>)">
                                                    <i class="fas fa-barcode"></i>
                                                </button>
                                                <?php endif; ?>
                                                <button class="btn btn-success" onclick="collectSample(<?php echo $sampleId; ?>)">
                                                    <i class="fas fa-syringe"></i> Collect
                                                </button>
                                            <?php elseif($sampleStatus == 'sample_collected'): ?>
                                                <button class="btn btn-info" onclick="viewSampleDetails(<?php echo $sampleId; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-primary" onclick="receiveAndProcess(<?php echo $sampleId; ?>)">
                                                    <i class="fas fa-clipboard-list"></i> Process
                                                </button>
                                            <?php elseif($sampleStatus == 'processing'): ?>
                                                <a href="<?php echo BASE_URL; ?>/lab/enter-result-form/<?php echo $sampleId; ?>" class="btn btn-warning">
                                                    <i class="fas fa-edit"></i> Result
                                                </a>
                                                <button class="btn btn-success" onclick="completeTest(<?php echo $sampleId; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php elseif($sampleStatus == 'completed'): ?>
                                                <a href="<?php echo BASE_URL; ?>/lab/enter-result-form/<?php echo $sampleId; ?>" class="btn btn-outline-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            <?php endif; ?>
                                        </div>
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
                <p>No samples found for collection</p>
                <a href="<?php echo BASE_URL; ?>/lab/create-order" class="btn btn-primary">Create Lab Order</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let selectedSamples = [];

$(document).ready(function() {
    // Patient select all
    $('.patient-select-all').on('change', function() {
        var patientId = $(this).data('patient');
        var checked = $(this).is(':checked');
        $('.sample-checkbox[data-patient="' + patientId + '"]').prop('checked', checked);
        updateBulkButton();
    });
    
    $('#filterStatus').on('change', filterSamples);
    $('#searchInput').on('keyup', filterSamples);
    $('#filterDate').on('change', filterSamples);
});

function filterSamples() {
    var status = $('#filterStatus').val();
    var search = $('#searchInput').val().toLowerCase();
    var date = $('#filterDate').val();
    
    $('.sample-group').each(function() {
        var patientName = $(this).data('patient') || '';
        var showGroup = false;
        
        $(this).find('.sample-row').each(function() {
            var itemStatus = $(this).data('status');
            var itemPatient = $(this).data('patient') || '';
            
            var statusMatch = (status === 'all' || itemStatus === status);
            var searchMatch = (search === '' || patientName.indexOf(search) > -1 || itemPatient.indexOf(search) > -1);
            
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

function updateBulkButton() {
    selectedSamples = [];
    $('.sample-checkbox:checked').each(function() {
        selectedSamples.push($(this).val());
    });
    
    if(selectedSamples.length > 0) {
        $('#bulkCollectBtn').show();
    } else {
        $('#bulkCollectBtn').hide();
    }
}

function generateBarcode(itemId) {
    Swal.fire({
        title: 'Generate Barcode',
        text: 'Generate barcode for this sample?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, generate'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Generating...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            $.ajax({
                url: BASE_URL + '/lab/generate-single-barcode',
                method: 'POST',
                data: { item_id: itemId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success', 'Barcode generated: ' + response.sample.sample_barcode, 'success')
                            .then(() => { location.reload(); });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to generate barcode', 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Failed to generate barcode', 'error'); }
            });
        }
    });
}

function collectSample(itemId) {
    Swal.fire({
        title: 'Collect Sample',
        html: `
            <input type="text" id="location" class="swal2-input" placeholder="Collection Location">
            <textarea id="notes" class="swal2-textarea" placeholder="Notes"></textarea>
        `,
        showCancelButton: true,
        confirmButtonText: 'Yes, collect',
        preConfirm: () => {
            return {
                location: document.getElementById('location').value,
                notes: document.getElementById('notes').value
            }
        }
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Collecting...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            $.ajax({
                url: BASE_URL + '/lab/update-sample-status',
                method: 'POST',
                data: { item_id: itemId, action: 'collect', location: result.value.location, notes: result.value.notes },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success', 'Sample collected. Ready for lab processing.', 'success')
                            .then(() => { location.reload(); });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to collect sample', 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Failed to collect sample', 'error'); }
            });
        }
    });
}

function collectAllPatientSamples(patientId) {
    Swal.fire({
        title: 'Collect All Samples',
        text: 'Collect all pending samples for this patient?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, collect all'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Collecting...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            // Get all pending sample IDs for this patient
            var ids = [];
            $('.sample-row[data-patient="' + patientId + '"]').each(function() {
                var status = $(this).data('status');
                if(status === 'pending') {
                    var checkbox = $(this).find('.sample-checkbox');
                    if(checkbox.length) {
                        ids.push(checkbox.val());
                    }
                }
            });
            
            if(ids.length === 0) {
                Swal.fire('Info', 'No pending samples for this patient', 'info');
                return;
            }
            
            $.ajax({
                url: BASE_URL + '/lab/bulk-collect-samples',
                method: 'POST',
                data: { item_ids: JSON.stringify(ids), location: 'Lab Counter' },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success', response.message, 'success').then(() => { location.reload(); });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to collect samples', 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Failed to collect samples', 'error'); }
            });
        }
    });
}

function receiveAndProcess(itemId) {
    Swal.fire({
        title: 'Receive Sample',
        text: 'Mark this sample as received and start processing?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, receive & process',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Processing...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            $.ajax({
                url: BASE_URL + '/lab/update-sample-status',
                method: 'POST',
                data: { item_id: itemId, action: 'receive' },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success', 'Sample received. Ready for result entry.', 'success')
                            .then(() => { location.reload(); });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to receive sample', 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Failed to receive sample', 'error'); }
            });
        }
    });
}

function completeTest(itemId) {
    Swal.fire({
        title: 'Complete Test',
        text: 'Mark this test as completed?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, complete'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Processing...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            $.ajax({
                url: BASE_URL + '/lab/update-sample-status',
                method: 'POST',
                data: { item_id: itemId, action: 'complete' },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success', 'Test completed successfully', 'success')
                            .then(() => { location.reload(); });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to complete test', 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Failed to complete test', 'error'); }
            });
        }
    });
}

function bulkCollect() {
    if(selectedSamples.length === 0) {
        Swal.fire('Warning', 'No samples selected', 'warning');
        return;
    }
    
    Swal.fire({
        title: 'Bulk Collection',
        text: `Collect ${selectedSamples.length} samples?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, collect all'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Processing...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            $.ajax({
                url: BASE_URL + '/lab/bulk-collect-samples',
                method: 'POST',
                data: { item_ids: JSON.stringify(selectedSamples), location: 'Lab Counter' },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success', response.message, 'success').then(() => { location.reload(); });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to collect samples', 'error');
                    }
                },
                error: function() { Swal.fire('Error', 'Failed to collect samples', 'error'); }
            });
        }
    });
}

function printBarcode(itemId) {
    window.open(BASE_URL + '/lab/print-barcode?item_id=' + itemId, '_blank');
}

function viewSampleDetails(itemId) {
    $('#sampleDetailsModal').modal('show');
    $('#sampleDetailsBody').html('<div class="text-center py-4"><div class="spinner-border text-info"></div><p>Loading...</p></div>');
    
    $.ajax({
        url: BASE_URL + '/lab/get-sample-details?item_id=' + itemId,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let sample = response.sample;
                $('#sampleDetailsBody').html(`
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tr><td width="40%"><strong>Patient:</strong></td><td>${escapeHtml(sample.patient_name)}</td></tr>
                            <tr><td><strong>Test:</strong></td><td>${escapeHtml(sample.test_name)}</td></tr>
                            <tr><td><strong>Order #:</strong></td><td>${escapeHtml(sample.order_number)}</td></tr>
                            <tr><td><strong>Barcode:</strong></td><td><code>${escapeHtml(sample.sample_barcode)}</code></td></tr>
                            <tr><td><strong>Status:</strong></td><td><span class="status-badge status-${sample.status}">${sample.status}</span></td></tr>
                            <tr><td><strong>Result:</strong></td><td>${sample.result_value || 'Not entered yet'}</td></tr>
                            <tr><td><strong>Collected At:</strong></td><td>${sample.sample_collected_at ? new Date(sample.sample_collected_at).toLocaleString() : 'Not collected'}</td></tr>
                        </table>
                    </div>
                `);
            }
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
</script>

<!-- Sample Details Modal -->
<div class="modal fade" id="sampleDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Sample Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="sampleDetailsBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="printBarcode(<?php echo isset($sampleId) ? $sampleId : 0; ?>)">
                    <i class="fas fa-print me-1"></i>Print Barcode
                </button>
            </div>
        </div>
    </div>
</div>