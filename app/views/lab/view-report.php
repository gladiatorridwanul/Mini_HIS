<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<style>
    .report-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .report-header {
        text-align: center;
        border-bottom: 2px solid #10b981;
        padding-bottom: 20px;
        margin-bottom: 20px;
    }
    .abnormal-result {
        background: #fee2e2;
        color: #ef4444;
        font-weight: bold;
    }
    .result-normal { color: #10b981; font-weight: 500; }
    .result-abnormal { color: #ef4444; font-weight: bold; }
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .status-delivered { background: #d1fae5; color: #10b981; }
    .status-pending { background: #fef3c7; color: #d97706; }
    @media print {
        .no-print { display: none; }
        .report-container { box-shadow: none; padding: 20px; margin: 0; }
        .report-header { margin-bottom: 15px; }
    }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2><i class="fas fa-file-alt text-success me-2"></i>Lab Report</h2>
            <p class="text-muted small mb-0">Patient laboratory test report</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="fas fa-print me-2"></i>Print Report
            </button>
            <?php if(!$report['is_delivered']): ?>
            <button class="btn btn-success btn-sm ms-2" onclick="deliverReport(<?php echo $report['id']; ?>)">
                <i class="fas fa-envelope me-2"></i>Deliver to Patient
            </button>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/lab/reports" class="btn btn-secondary btn-sm ms-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Reports
            </a>
        </div>
    </div>

    <div class="report-container">
        <div class="report-header">
            <h2>UNIDIA HOSPITAL</h2>
            <p>Laboratory Report</p>
            <p class="text-muted">Report #: <?php echo $report['report_number']; ?></p>
            <p class="text-muted small">Generated on: <?php echo date('d M Y, h:i A'); ?></p>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr><td width="40%"><strong>Patient Name:</strong></td><td><strong><?php echo htmlspecialchars($report['patient_name']); ?></strong></td></tr>
                    <tr><td><strong>Gender:</strong></small><td><?php echo ucfirst($report['gender']); ?></small></tr>
                    <tr><td><strong>Date of Birth:</strong></small><td><?php echo date('d M Y', strtotime($report['date_of_birth'])); ?></small></tr>
                    <tr><td><strong>Phone:</strong></small><td><?php echo htmlspecialchars($report['phone']); ?></small></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr><td width="40%"><strong>Order #:</strong></small><td><?php echo $report['order_number']; ?></small></tr>
                    <tr><td><strong>Order Date:</strong></small><td><?php echo date('d M Y', strtotime($report['order_date'])); ?></small></tr>
                    <tr><td><strong>Report Date:</strong></small><td><?php echo date('d M Y', strtotime($report['report_date'])); ?></small></tr>
                    <tr><td><strong>Referring Doctor:</strong></small><td>Dr. <?php echo htmlspecialchars($report['doctor_name']); ?></small></tr>
                </table>
            </div>
        </div>

        <?php if($report['clinical_diagnosis']): ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-stethoscope me-2"></i>
            <strong>Clinical Diagnosis:</strong> <?php echo nl2br(htmlspecialchars($report['clinical_diagnosis'])); ?>
        </div>
        <?php endif; ?>

        <h5 class="mb-3"><i class="fas fa-vial me-2"></i>Test Results</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th width="30%">Test Name</th>
                        <th width="25%">Result</th>
                        <th width="10%">Unit</th>
                        <th width="25%">Normal Range</th>
                        <th width="10%">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($results as $result): ?>
                    <tr class="<?php echo $result['is_abnormal'] ? 'abnormal-result' : ''; ?>">
                        <td><strong><?php echo htmlspecialchars($result['test_name']); ?></strong></td>
                        <td class="<?php echo $result['is_abnormal'] ? 'result-abnormal' : 'result-normal'; ?>">
                            <?php echo htmlspecialchars($result['result_value'] ?: 'Pending'); ?>
                         </small></td>
                        <td><?php echo htmlspecialchars($result['unit']); ?></small></td>
                        <td><small><?php echo nl2br(htmlspecialchars($result['normal_range'])); ?></small></small></td>
                        <td>
                            <?php if($result['is_abnormal']): ?>
                            <span class="badge bg-danger">Abnormal</span>
                            <?php else: ?>
                            <span class="badge bg-success">Normal</span>
                            <?php endif; ?>
                         </small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php 
        $remarks = '';
        foreach($results as $result) {
            if($result['remarks']) {
                $remarks .= $result['remarks'] . ' ';
            }
        }
        if($remarks): 
        ?>
        <div class="mt-4 p-3 bg-light rounded">
            <strong><i class="fas fa-comment me-2"></i>Remarks:</strong>
            <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($remarks)); ?></p>
        </div>
        <?php endif; ?>

        <div class="footer mt-4 pt-3 text-center text-muted">
            <small>This is a computer-generated report. No signature required.</small>
            <br>
            <small>For any queries, please contact the laboratory department.</small>
        </div>
        
        <div class="text-center mt-3">
            <span class="status-badge status-<?php echo $report['is_delivered'] ? 'delivered' : 'pending'; ?>">
                <?php echo $report['is_delivered'] ? 'Delivered to Patient' : 'Pending Delivery'; ?>
            </span>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function deliverReport(reportId) {
    Swal.fire({
        title: 'Deliver Report?',
        text: 'This will mark the report as delivered to the patient.',
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
                        Swal.fire('Delivered!', 'Report marked as delivered to patient', 'success').then(() => {
                            location.reload();
                        });
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
</script>