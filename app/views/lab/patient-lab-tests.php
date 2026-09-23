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
    .filter-section {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }
    .test-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .test-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        padding: 10px 12px;
        border-bottom: 2px solid #e2e8f0;
        text-align: center;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .test-table td {
        padding: 8px 12px;
        border-bottom: 1px solid #f1f5f9;
        text-align: center;
        vertical-align: middle;
    }
    .test-table tbody tr:hover {
        background: #f8fafc;
    }
    .test-table .date-cell {
        font-weight: 600;
        color: #1e293b;
        text-align: left;
        white-space: nowrap;
    }
    .test-table .order-cell {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
    }
    .test-table .result-normal {
        color: #10b981;
        font-weight: 500;
    }
    .test-table .result-abnormal {
        color: #ef4444;
        font-weight: 700;
        background: #fef2f2;
        border-radius: 4px;
        padding: 2px 8px;
        display: inline-block;
    }
    .test-table .no-result {
        color: #94a3b8;
        font-size: 12px;
    }
    .test-table .result-cell {
        min-width: 60px;
    }
    .scroll-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-height: 600px;
        overflow-y: auto;
    }
    .scroll-wrapper::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .scroll-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .scroll-wrapper::-webkit-scrollbar-thumb {
        background: #c1c7cd;
        border-radius: 3px;
    }
    .scroll-wrapper::-webkit-scrollbar-thumb:hover {
        background: #a0a7ae;
    }
    .patient-select {
        max-width: 300px;
    }
    .btn-sm {
        padding: 5px 12px;
        font-size: 12px;
    }
    .empty-state {
        padding: 40px 20px;
        text-align: center;
    }
    .empty-state i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 15px;
    }
    .empty-state h5 {
        color: #475569;
        margin-bottom: 5px;
    }
    .empty-state p {
        color: #94a3b8;
        font-size: 13px;
    }
    .abnormal-badge {
        display: inline-block;
        background: #ef4444;
        color: white;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: 9px;
        margin-left: 4px;
    }
    .normal-badge {
        display: inline-block;
        background: #10b981;
        color: white;
        padding: 1px 8px;
        border-radius: 10px;
        font-size: 9px;
        margin-left: 4px;
    }
    .table-container {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }
    .table-header {
        background: #f8fafc;
        padding: 15px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .table-header h6 {
        margin: 0;
        font-weight: 600;
        color: #1e293b;
    }
    .table-header .badge-count {
        background: #3b82f6;
        color: white;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 11px;
    }
    .summary-bar {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
        padding: 10px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .summary-bar .item {
        font-size: 12px;
        color: #475569;
    }
    .summary-bar .item strong {
        color: #1e293b;
    }
    .summary-bar .item .count {
        font-weight: 700;
        color: #3b82f6;
    }
    @media print {
        .no-print { display: none; }
        .filter-section { border: 1px solid #ddd; }
        .test-table th { background: #f5f5f5; }
        .scroll-wrapper { max-height: none; overflow: visible; }
    }
    @media (max-width: 768px) {
        .test-table { font-size: 11px; }
        .test-table th, .test-table td { padding: 5px 8px; }
        .filter-section .row .col-md-4 { margin-bottom: 10px; }
        .patient-select { max-width: 100%; }
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-flask text-success me-2"></i>Patient Lab Tests</h2>
            <p class="text-muted small mb-0">View all lab test results for a patient</p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
                <i class="fas fa-print me-2"></i>Print Report
            </button>
            <button onclick="exportLabTests()" class="btn btn-success btn-sm">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number text-primary" id="totalTests">0</div>
                <div class="stat-label">Total Tests</div>
                <i class="fas fa-vial text-primary mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number text-success" id="totalNormal">0</div>
                <div class="stat-label">Normal Results</div>
                <i class="fas fa-check-circle text-success mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number text-danger" id="totalAbnormal">0</div>
                <div class="stat-label">Abnormal Results</div>
                <i class="fas fa-exclamation-triangle text-danger mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number text-info" id="totalDates">0</div>
                <div class="stat-label">Test Dates</div>
                <i class="fas fa-calendar text-info mt-2"></i>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section no-print">
        <form method="GET" action="<?php echo BASE_URL; ?>/lab/patient-tests" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold small">Select Patient</label>
                <select name="patient_id" class="form-select form-select-sm patient-select" onchange="this.form.submit()">
                    <option value="">-- Select Patient --</option>
                    <?php foreach($patients as $patient): ?>
                    <option value="<?php echo $patient['id']; ?>" <?php echo ($patientId == $patient['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($patient['full_name']); ?> (<?php echo $patient['patient_code']; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo $dateFrom; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo $dateTo; ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fas fa-search me-1"></i>Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Results Table -->
    <?php if($patientId > 0): ?>
        <?php if(!empty($results)): ?>
            <div class="table-container">
                <div class="table-header">
                    <div>
                        <h6><i class="fas fa-table me-2"></i>Test Results</h6>
                    </div>
                    <div>
                        <span class="badge-count">
                            <i class="fas fa-calendar me-1"></i> <?php echo count($results); ?> Dates
                        </span>
                        <span class="badge-count ms-2">
                            <i class="fas fa-vial me-1"></i> <?php echo count($testNames); ?> Tests
                        </span>
                    </div>
                </div>
                
                <!-- Summary Bar -->
                <div class="summary-bar">
                    <span class="item"><i class="fas fa-user me-1"></i> <strong><?php echo htmlspecialchars($patients[array_search($patientId, array_column($patients, 'id'))]['full_name'] ?? ''); ?></strong></span>
                    <span class="item"><i class="fas fa-calendar me-1"></i> Total Dates: <span class="count" id="summaryDates"><?php echo count($results); ?></span></span>
                    <span class="item"><i class="fas fa-vial me-1"></i> Total Tests: <span class="count" id="summaryTests"><?php echo count($testNames); ?></span></span>
                    <span class="item"><i class="fas fa-check-circle text-success me-1"></i> Normal: <span class="count text-success" id="summaryNormal">0</span></span>
                    <span class="item"><i class="fas fa-exclamation-triangle text-danger me-1"></i> Abnormal: <span class="count text-danger" id="summaryAbnormal">0</span></span>
                </div>
                
                <div class="scroll-wrapper">
                    <table class="test-table" id="testResultsTable">
                        <thead>
                            <tr>
                                <th style="min-width: 100px; text-align: left;">Date</th>
                                <th style="min-width: 120px; text-align: left;">Order #</th>
                                <?php foreach($testNames as $testName): ?>
                                <th class="result-cell" style="min-width: 80px;" title="<?php echo htmlspecialchars($testName); ?>">
                                    <?php echo htmlspecialchars(strlen($testName) > 20 ? substr($testName, 0, 18) . '...' : $testName); ?>
                                </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalNormal = 0;
                            $totalAbnormal = 0;
                            foreach($results as $dateKey => $row): 
                            ?>
                            <tr>
                                <td class="date-cell">
                                    <strong><?php echo $row['date']; ?></strong>
                                </td>
                                <td class="order-cell">
                                    <span class="badge bg-secondary"><?php echo $row['order_number']; ?></span>
                                </td>
                                <?php foreach($testNames as $testName): ?>
                                <td class="result-cell">
                                    <?php if(isset($row['tests'][$testName])): 
                                        $test = $row['tests'][$testName];
                                        if($test['is_abnormal'] == 1):
                                            $totalAbnormal++;
                                    ?>
                                        <span class="result-abnormal">
                                            <?php echo htmlspecialchars($test['value']); ?>
                                            <?php if($test['unit']): ?>
                                            <small><?php echo htmlspecialchars($test['unit']); ?></small>
                                            <?php endif; ?>
                                        </span>
                                        <span class="abnormal-badge">Abnormal</span>
                                    <?php else: 
                                        $totalNormal++;
                                    ?>
                                        <span class="result-normal">
                                            <?php echo htmlspecialchars($test['value']); ?>
                                            <?php if($test['unit']): ?>
                                            <small><?php echo htmlspecialchars($test['unit']); ?></small>
                                            <?php endif; ?>
                                        </span>
                                        <span class="normal-badge">Normal</span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="no-result">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <script>
                // Update statistics
                document.addEventListener('DOMContentLoaded', function() {
                    let totalTests = <?php echo count($testNames) * count($results); ?>;
                    let totalNormal = <?php echo $totalNormal; ?>;
                    let totalAbnormal = <?php echo $totalAbnormal; ?>;
                    let totalDates = <?php echo count($results); ?>;
                    
                    document.getElementById('totalTests').textContent = totalTests;
                    document.getElementById('totalNormal').textContent = totalNormal;
                    document.getElementById('totalAbnormal').textContent = totalAbnormal;
                    document.getElementById('totalDates').textContent = totalDates;
                    document.getElementById('summaryNormal').textContent = totalNormal;
                    document.getElementById('summaryAbnormal').textContent = totalAbnormal;
                });
            </script>
            
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-flask"></i>
                <h5>No Test Results Found</h5>
                <p>No completed lab test results available for this patient.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-search"></i>
            <h5>Select a Patient</h5>
            <p>Please select a patient from the dropdown above to view their lab test results.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function exportLabTests() {
    let patientId = '<?php echo $patientId; ?>';
    let dateFrom = '<?php echo $dateFrom; ?>';
    let dateTo = '<?php echo $dateTo; ?>';
    
    if(!patientId) {
        Swal.fire('Warning', 'Please select a patient first', 'warning');
        return;
    }
    
    window.location.href = BASE_URL + '/lab/export-patient-tests?patient_id=' + patientId + '&date_from=' + dateFrom + '&date_to=' + dateTo;
}

// Print only the table
function printTable() {
    window.print();
}

// Auto-submit on patient selection
$(document).ready(function() {
    $('select[name="patient_id"]').on('change', function() {
        $(this).closest('form').submit();
    });
});
</script>