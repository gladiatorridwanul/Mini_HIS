<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<style>
    .stat-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 20px;
        color: white;
        text-align: center;
    }
    .stat-number {
        font-size: 32px;
        font-weight: 700;
    }
    .stat-label {
        font-size: 13px;
        opacity: 0.9;
    }
    .filter-section {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .status-delivered { background: #10b981; color: white; }
    .status-pending { background: #f59e0b; color: white; }
    .status-completed { background: #3b82f6; color: white; }
    .patient-group-header {
        cursor: pointer;
        transition: background 0.2s;
    }
    .patient-group-header:hover { background: #f8fafc; }
    .report-item {
        border-bottom: 1px solid #f1f5f9;
        padding: 8px 0;
    }
    .report-item:last-child { border-bottom: none; }
    .btn-icon {
        padding: 4px 8px;
        margin: 1px;
        border-radius: 6px;
        font-size: 12px;
    }
    @media print {
        .no-print { display: none; }
        .report-card { box-shadow: none; border: 1px solid #ddd; }
    }
    .view-toggle {
        cursor: pointer;
        padding: 8px 16px;
        border-radius: 8px;
        background: #f1f5f9;
        transition: all 0.2s;
    }
    .view-toggle.active {
        background: #3b82f6;
        color: white;
    }
    .view-toggle:hover:not(.active) {
        background: #e2e8f0;
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-file-alt text-success me-2"></i>Lab Reports</h2>
            <p class="text-muted">View and manage laboratory test reports</p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-primary me-2">
                <i class="fas fa-print me-2"></i>Print Page
            </button>
            <button onclick="exportReports()" class="btn btn-success">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-box">
                <div class="stat-number" id="totalReports">0</div>
                <div class="stat-label">Total Reports</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="stat-number" id="pendingReports">0</div>
                <div class="stat-label">Pending Delivery</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="stat-number" id="deliveredReports">0</div>
                <div class="stat-label">Delivered</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                <div class="stat-number" id="totalPatients">0</div>
                <div class="stat-label">Total Patients</div>
            </div>
        </div>
    </div>

    <!-- View Toggle -->
    <div class="d-flex gap-2 mb-3 no-print">
        <span class="view-toggle active" onclick="switchView('patient')" id="viewPatient">
            <i class="fas fa-users me-1"></i> Patient-wise View
        </span>
        <span class="view-toggle" onclick="switchView('date')" id="viewDate">
            <i class="fas fa-calendar me-1"></i> Date-wise View
        </span>
    </div>

    <!-- Filter Section -->
    <div class="filter-section no-print">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Date Range</label>
                <select id="filterDateRange" class="form-select">
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month" selected>This Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div class="col-md-3 mb-3" id="dateRangeDiv" style="display: none;">
                <label class="form-label fw-bold">From Date</label>
                <input type="date" id="fromDate" class="form-control">
            </div>
            <div class="col-md-3 mb-3" id="toDateDiv" style="display: none;">
                <label class="form-label fw-bold">To Date</label>
                <input type="date" id="toDate" class="form-control">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Status</label>
                <select id="filterStatus" class="form-select">
                    <option value="all">All</option>
                    <option value="0">Pending Delivery</option>
                    <option value="1">Delivered</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">Search</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Search by patient name or report #...">
            </div>
            <div class="col-md-12 mt-2">
                <button class="btn btn-primary" onclick="loadReports()">
                    <i class="fas fa-search me-2"></i>Apply Filters
                </button>
                <button class="btn btn-secondary ms-2" onclick="resetFilters()">
                    <i class="fas fa-undo me-2"></i>Reset
                </button>
                <button class="btn btn-info ms-2" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-2"></i>Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Reports Container -->
    <div id="reportsContainer">
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading reports...</p>
        </div>
    </div>
</div>

<!-- View Report Modal -->
<div class="modal fade" id="viewReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-file-alt me-2"></i>Lab Report Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reportModalBody">
                <div class="text-center py-4">Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printCurrentReport()">
                    <i class="fas fa-print me-2"></i>Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let currentView = 'patient';
let currentReportData = null;
let allReports = [];

$(document).ready(function() {
    loadReports();
    setInterval(loadReports, 60000);
    
    $('#filterDateRange').on('change', function() {
        if($(this).val() === 'custom') {
            $('#dateRangeDiv, #toDateDiv').show();
        } else {
            $('#dateRangeDiv, #toDateDiv').hide();
        }
        loadReports();
    });
    
    $('#filterStatus, #searchInput').on('change keyup', function() {
        loadReports();
    });
});

function switchView(view) {
    currentView = view;
    $('#viewPatient').toggleClass('active', view === 'patient');
    $('#viewDate').toggleClass('active', view === 'date');
    renderReports(allReports);
}

function loadReports() {
    let dateRange = $('#filterDateRange').val();
    let fromDate = $('#fromDate').val();
    let toDate = $('#toDate').val();
    let status = $('#filterStatus').val();
    let search = $('#searchInput').val();
    
    $('#reportsContainer').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading reports...</p></div>');
    
    $.ajax({
        url: BASE_URL + '/api/lab-reports-list',
        method: 'GET',
        data: {
            date_range: dateRange,
            from_date: fromDate,
            to_date: toDate,
            status: status,
            search: search
        },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if(response.success && response.reports) {
                allReports = response.reports;
                renderReports(allReports);
                updateStatistics(allReports);
            } else {
                $('#reportsContainer').html('<div class="alert alert-danger text-center py-4">Error loading reports: ' + (response.message || 'Unknown error') + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            $('#reportsContainer').html('<div class="alert alert-danger text-center py-4">Failed to load reports. Please refresh the page.</div>');
        }
    });
}

function renderReports(reports) {
    if (!reports || reports.length === 0) {
        $('#reportsContainer').html(`
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <h5>No reports found</h5>
                <p class="mb-0">Try adjusting your filters or search criteria.</p>
            </div>
        `);
        return;
    }

    if (currentView === 'patient') {
        renderPatientWise(reports);
    } else {
        renderDateWise(reports);
    }
}

function renderPatientWise(reports) {
    // Group reports by patient
    let grouped = {};
    reports.forEach(report => {
        let key = report.patient_name + '_' + (report.patient_id || '');
        if (!grouped[key]) {
            grouped[key] = {
                patient_name: report.patient_name,
                patient_id: report.patient_id || 0,
                phone: report.phone || 'N/A',
                reports: [],
                total: 0,
                delivered: 0,
                pending: 0
            };
        }
        grouped[key].reports.push(report);
        grouped[key].total++;
        if (report.is_delivered == 1) {
            grouped[key].delivered++;
        } else {
            grouped[key].pending++;
        }
    });

    let html = '';
    let patientKeys = Object.keys(grouped).sort((a, b) => {
        return grouped[b].total - grouped[a].total;
    });

    patientKeys.forEach(key => {
        let patient = grouped[key];
        let statusColor = patient.pending > 0 ? 'warning' : 'success';
        let statusText = patient.pending > 0 ? `${patient.pending} Pending` : 'All Delivered';

        html += `
        <div class="card mb-3">
            <div class="card-header bg-light patient-group-header d-flex justify-content-between align-items-center" 
                 onclick="$(this).next().slideToggle()">
                <div>
                    <h6 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        <strong>${escapeHtml(patient.patient_name)}</strong>
                        <span class="text-muted ms-2">(${escapeHtml(patient.phone)})</span>
                        <span class="badge bg-secondary ms-2">${patient.total} Reports</span>
                    </h6>
                </div>
                <div>
                    <span class="badge bg-${statusColor}">${statusText}</span>
                    <i class="fas fa-chevron-down ms-2"></i>
                </div>
            </div>
            <div class="card-body p-0" style="display: block;">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Report #</th>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Tests</th>
                                <th>Status</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        patient.reports.forEach(report => {
            let statusClass = report.is_delivered == 1 ? 'delivered' : 'pending';
            let statusText2 = report.is_delivered == 1 ? 'Delivered' : 'Pending Delivery';
            
            // Count tests in this report
            let testCount = 0;
            if (report.test_names) {
                testCount = report.test_names.split(',').length;
            }

            html += `
            <tr>
                <td><strong>${escapeHtml(report.report_number)}</strong></td>
                <td>${escapeHtml(report.order_number || 'N/A')}</td>
                <td>${report.report_date || 'N/A'}</td>
                <td><span class="badge bg-info">${testCount} tests</span></td>
                <td><span class="status-badge status-${statusClass}">${statusText2}</span></td>
                <td class="no-print">
                    <button class="btn btn-sm btn-outline-primary" onclick="viewReport(${report.id})" title="View Report">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-success" onclick="printReport(${report.id})" title="Print Report">
                        <i class="fas fa-print"></i>
                    </button>
                    ${report.is_delivered == 0 ? 
                        `<button class="btn btn-sm btn-outline-info" onclick="deliverReport(${report.id})" title="Mark as Delivered">
                            <i class="fas fa-envelope"></i>
                        </button>` : ''}
                </td>
            </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        `;
    });

    $('#reportsContainer').html(html);
}

function renderDateWise(reports) {
    // Group reports by date
    let grouped = {};
    reports.forEach(report => {
        let date = report.report_date || 'Unknown';
        if (!grouped[date]) {
            grouped[date] = {
                date: date,
                reports: [],
                total: 0,
                delivered: 0,
                pending: 0
            };
        }
        grouped[date].reports.push(report);
        grouped[date].total++;
        if (report.is_delivered == 1) {
            grouped[date].delivered++;
        } else {
            grouped[date].pending++;
        }
    });

    let dateKeys = Object.keys(grouped).sort((a, b) => {
        return new Date(b) - new Date(a);
    });

    let html = `
    <div class="card">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Date-wise Report Summary</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Total Reports</th>
                            <th>Delivered</th>
                            <th>Pending</th>
                            <th>Patients</th>
                            <th class="no-print">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    dateKeys.forEach(dateKey => {
        let dateGroup = grouped[dateKey];
        let patientCount = new Set(dateGroup.reports.map(r => r.patient_name)).size;
        let statusColor = dateGroup.pending > 0 ? 'warning' : 'success';
        
        html += `
        <tr>
            <td><strong>${dateKey}</strong></td>
            <td><span class="badge bg-secondary">${dateGroup.total}</span></td>
            <td><span class="badge bg-success">${dateGroup.delivered}</span></td>
            <td><span class="badge bg-${statusColor}">${dateGroup.pending}</span></td>
            <td><span class="badge bg-info">${patientCount}</span></td>
            <td class="no-print">
                <button class="btn btn-sm btn-outline-primary" onclick="filterByDate('${dateKey}')">
                    <i class="fas fa-filter me-1"></i>View Details
                </button>
            </td>
        </tr>
        `;
    });

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    `;

    $('#reportsContainer').html(html);
}

function filterByDate(date) {
    $('#filterDateRange').val('custom');
    $('#fromDate').val(date);
    $('#toDate').val(date);
    $('#dateRangeDiv, #toDateDiv').show();
    loadReports();
}

function updateStatistics(reports) {
    let total = reports.length;
    let pending = reports.filter(r => r.is_delivered == 0).length;
    let delivered = reports.filter(r => r.is_delivered == 1).length;
    let patients = new Set(reports.map(r => r.patient_name)).size;
    
    $('#totalReports').text(total);
    $('#pendingReports').text(pending);
    $('#deliveredReports').text(delivered);
    $('#totalPatients').text(patients);
}

function viewReport(reportId) {
    $('#viewReportModal').modal('show');
    $('#reportModalBody').html('<div class="text-center py-4"><div class="spinner-border text-primary"></div><p class="mt-2">Loading report...</p></div>');
    
    $.ajax({
        url: BASE_URL + '/lab/view-report-ajax?id=' + reportId,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                currentReportData = response;
                let report = response.report;
                let results = response.results || [];
                
                let resultsHtml = '';
                for(let result of results) {
                    let abnormalClass = result.is_abnormal ? 'text-danger fw-bold' : '';
                    let statusBadge = result.is_abnormal ? '<span class="badge bg-danger">Abnormal</span>' : '<span class="badge bg-success">Normal</span>';
                    resultsHtml += `<tr>
                        <td><strong>${escapeHtml(result.test_name)}</strong></td>
                        <td><span class="${abnormalClass}">${escapeHtml(result.result_value || 'Pending')}</span></td>
                        <td>${escapeHtml(result.unit || '')}</td>
                        <td><small>${escapeHtml(result.normal_range || 'N/A')}</small></td>
                        <td>${statusBadge}</td>
                    </tr>`;
                }
                
                let diagnosisHtml = report.clinical_diagnosis ? `<div class="alert alert-info mt-3"><strong>Clinical Diagnosis:</strong> ${escapeHtml(report.clinical_diagnosis)}</div>` : '';
                
                $('#reportModalBody').html(`
                    <div class="report-container">
                        <div class="text-center mb-4">
                            <h3>UNIDIA HOSPITAL</h3>
                            <p class="mb-1">Laboratory Report</p>
                            <p class="text-muted mb-0">Report #: ${escapeHtml(report.report_number)}</p>
                            <p class="text-muted">Generated on: ${new Date().toLocaleDateString()}</p>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Patient Name:</strong> ${escapeHtml(report.patient_name)}</p>
                                <p><strong>Gender:</strong> ${escapeHtml(report.gender || 'N/A')}</p>
                                <p><strong>Phone:</strong> ${escapeHtml(report.phone || 'N/A')}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Order #:</strong> ${escapeHtml(report.order_number)}</p>
                                <p><strong>Report Date:</strong> ${report.report_date}</p>
                                <p><strong>Referring Doctor:</strong> Dr. ${escapeHtml(report.doctor_name)}</p>
                            </div>
                        </div>
                        ${diagnosisHtml}
                        <h6 class="mt-3">Test Results</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr><th>Test Name</th><th>Result</th><th>Unit</th><th>Normal Range</th><th>Status</th></tr>
                                </thead>
                                <tbody>${resultsHtml}</tbody>
                            </table>
                        </div>
                        <div class="text-center mt-4 text-muted">
                            <small>This is a computer-generated report. No signature required.</small>
                            <br>
                            <small>For any queries, please contact the laboratory department.</small>
                        </div>
                    </div>
                `);
            } else {
                $('#reportModalBody').html(`<div class="text-center py-4 text-danger">${response.message || 'Failed to load report'}</div>`);
            }
        },
        error: function() {
            $('#reportModalBody').html('<div class="text-center py-4 text-danger">Error loading report</div>');
        }
    });
}

function printCurrentReport() {
    let printContent = $('#reportModalBody').html();
    let printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Lab Report</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                .report-container { max-width: 800px; margin: 0 auto; }
                @media print { body { margin: 0; padding: 0; } .no-print { display: none; } }
            </style>
        </head>
        <body>
            ${printContent}
            <div class="text-center mt-4 no-print">
                <button onclick="window.print()" class="btn btn-primary">Print</button>
                <button onclick="window.close()" class="btn btn-secondary">Close</button>
            </div>
            <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); }<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

function printReport(reportId) {
    window.open(BASE_URL + '/lab/print-report?id=' + reportId, '_blank');
}

function deliverReport(reportId) {
    Swal.fire({
        title: 'Deliver Report?',
        text: 'Mark this report as delivered to patient?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, deliver',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({ title: 'Processing...', text: 'Please wait', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            
            $.ajax({
                url: BASE_URL + '/lab/deliver-report',
                method: 'POST',
                data: { report_id: reportId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success', 'Report marked as delivered', 'success');
                        loadReports();
                    } else {
                        Swal.fire('Error', response.message || 'Failed to deliver report', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to deliver report', 'error');
                }
            });
        }
    });
}

function exportReports() {
    let dateRange = $('#filterDateRange').val();
    let fromDate = $('#fromDate').val();
    let toDate = $('#toDate').val();
    let status = $('#filterStatus').val();
    
    window.location.href = BASE_URL + '/lab/export-reports?date_range=' + dateRange + '&from_date=' + fromDate + '&to_date=' + toDate + '&status=' + status;
}

function resetFilters() {
    $('#filterDateRange').val('month');
    $('#filterStatus').val('all');
    $('#searchInput').val('');
    $('#fromDate').val('');
    $('#toDate').val('');
    $('#dateRangeDiv, #toDateDiv').hide();
    loadReports();
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