<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Test Results - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .container-fluid { padding: 20px; }
        .card { border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 1px solid #e5e7eb; padding: 15px 20px; }
        .info-card { background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 20px; }
        .normal-range-box { background: #e2e8f0; border-radius: 8px; padding: 10px; font-family: monospace; }
        .result-input { font-size: 18px; font-family: monospace; }
        .required-field::after { content: " *"; color: #ef4444; }
        .btn { padding: 8px 20px; border-radius: 8px; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; display: inline-block; }
        .status-processing { background: #f3e8ff; color: #6b21a5; }
        .status-sample_collected { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-edit text-success me-2"></i>Enter Test Results</h2>
            <p class="text-muted small mb-0">Enter laboratory test results for the patient</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/lab/enter-results" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-flask me-2"></i>Result Entry Form</h6>
                </div>
                <div class="card-body">
                    <!-- Test Information -->
                    <div class="info-card">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-user me-2"></i>Patient:</strong> <?php echo htmlspecialchars($orderItem['patient_name'] ?? 'N/A'); ?></p>
                                <p><strong><i class="fas fa-hashtag me-2"></i>Order #:</strong> <?php echo htmlspecialchars($orderItem['order_number'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-vial me-2"></i>Test:</strong> <?php echo htmlspecialchars($orderItem['test_name'] ?? 'N/A'); ?></p>
                                <p><strong><i class="fas fa-barcode me-2"></i>Sample Barcode:</strong> 
                                    <code><?php echo htmlspecialchars($orderItem['sample_barcode'] ?? 'Not collected'); ?></code>
                                </p>
                            </div>
                        </div>
                        
                        <?php if(!empty($orderItem['normal_range'])): ?>
                        <div class="normal-range-box mt-2">
                            <strong><i class="fas fa-chart-line me-2"></i>Normal Range:</strong>
                            <?php echo htmlspecialchars($orderItem['normal_range']); ?> 
                            <?php echo htmlspecialchars($orderItem['unit'] ?? ''); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-2">
                            <span class="status-badge status-<?php echo $orderItem['status'] ?? 'processing'; ?>">
                                Status: <?php echo strtoupper(str_replace('_', ' ', $orderItem['status'] ?? 'PROCESSING')); ?>
                            </span>
                        </div>
                    </div>

                    <form id="resultsForm" method="post" action="<?php echo BASE_URL; ?>/lab/save-result">
                        <input type="hidden" name="item_id" value="<?php echo $orderItem['id'] ?? 0; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold required-field">Result Value</label>
                            <textarea name="result" id="resultValue" class="form-control result-input" rows="4" required 
                                      placeholder="Enter test result here..."><?php echo htmlspecialchars($orderItem['result_value'] ?? ''); ?></textarea>
                            <small class="text-muted">Enter the test result value. For abnormal values, check the box below.</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_abnormal" value="1" id="abnormalCheck" <?php echo (isset($orderItem['is_abnormal']) && $orderItem['is_abnormal'] == 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-danger fw-bold" for="abnormalCheck">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Mark as Abnormal
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Remarks / Notes</label>
                            <textarea name="notes" class="form-control" rows="3" 
                                      placeholder="Additional remarks or notes about the result..."><?php echo htmlspecialchars($orderItem['remarks'] ?? ''); ?></textarea>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Save Results
                            </button>
                            <a href="<?php echo BASE_URL; ?>/lab/enter-results" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

$(document).ready(function() {
    $('#resultsForm').submit(function(e) {
        e.preventDefault();
        
        let resultValue = $('#resultValue').val().trim();
        
        if(!resultValue) {
            Swal.fire('Error', 'Please enter the result value', 'error');
            return;
        }
        
        Swal.fire({
            title: 'Saving Results...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        $.ajax({
            url: BASE_URL + '/lab/save-result',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Results saved successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = BASE_URL + '/lab/enter-results';
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to save results', 'error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to save results';
                try {
                    let response = JSON.parse(xhr.responseText);
                    if(response.message) errorMsg = response.message;
                } catch(e) {}
                Swal.fire('Error', errorMsg, 'error');
            }
        });
    });
});
</script>
</body>
</html>