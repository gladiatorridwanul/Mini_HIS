<?php
// /app/views/lab/manual/print-report.php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Report - <?php echo $result['test_name']; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4; margin: 20px; }
        body { font-family: 'Arial', sans-serif; background: white; padding: 20px; }
        .report-container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 3px solid #10b981; padding-bottom: 15px; margin-bottom: 25px; }
        .header .hospital-name { font-size: 24px; font-weight: 700; color: #1a56db; letter-spacing: 2px; }
        .header .hospital-address { font-size: 12px; color: #64748b; }
        .header .report-title { font-size: 18px; font-weight: 600; color: #0f172a; margin-top: 5px; }
        .patient-info { background: #f8fafc; border-radius: 8px; padding: 15px; margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 5px 30px; }
        .patient-info .label { font-weight: 600; color: #475569; font-size: 12px; }
        .patient-info .value { color: #0f172a; font-size: 14px; font-weight: 500; }
        .result-box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 25px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .result-box .test-name { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 10px; }
        .result-box .result-value { font-size: 32px; font-weight: 700; padding: 10px 0; }
        .result-box .result-value.abnormal { color: #dc2626; }
        .result-box .result-value.normal { color: #059669; }
        .result-box .result-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; }
        .result-box .result-meta .label { color: #64748b; font-size: 12px; }
        .result-box .result-meta .value { color: #0f172a; font-weight: 500; }
        .status-badge { display: inline-block; padding: 4px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .status-badge.abnormal { background: #fee2e2; color: #991b1b; }
        .status-badge.normal { background: #d1fae5; color: #065f46; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; color: #94a3b8; font-size: 11px; }
        .footer .signature { margin-top: 20px; display: flex; justify-content: center; gap: 40px; }
        .footer .signature .sig-item { text-align: center; }
        .footer .signature .sig-item .line { width: 120px; border-bottom: 1px solid #94a3b8; margin: 2px auto 0; }
        .no-print { margin-top: 20px; text-align: center; }
        .no-print button { padding: 8px 24px; border: none; border-radius: 6px; cursor: pointer; margin: 0 5px; font-size: 14px; }
        .btn-print { background: #10b981; color: white; }
        .btn-print:hover { background: #059669; }
        .btn-close { background: #e5e7eb; color: #374151; }
        .btn-close:hover { background: #d1d5db; }
        @media print { .no-print { display: none; } .patient-info { background: #f8fafc; } }
        .notes-section { background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 12px; margin-top: 15px; font-size: 13px; }
        .notes-section .label { font-weight: 600; color: #92400e; }
    </style>
</head>
<body>
    <div class="report-container">
        <!-- Header -->
        <div class="header">
            <div class="hospital-name">UNIDIA HOSPITAL</div>
            <div class="hospital-address">123, Hospital Road, Dhaka | Phone: +880 1234 567890</div>
            <div class="report-title">Laboratory Test Report</div>
        </div>

        <!-- Patient Information -->
        <div class="patient-info">
            <div><span class="label">Patient Name:</span> <span class="value"><?php echo $result['patient_name']; ?></span></div>
            <div><span class="label">Patient Code:</span> <span class="value"><?php echo $result['patient_code']; ?></span></div>
            <div><span class="label">Phone:</span> <span class="value"><?php echo $result['phone']; ?></span></div>
            <div><span class="label">Gender:</span> <span class="value"><?php echo ucfirst($result['gender'] ?? 'N/A'); ?></span></div>
            <div><span class="label">Date of Birth:</span> <span class="value"><?php echo !empty($result['date_of_birth']) ? date('d-m-Y', strtotime($result['date_of_birth'])) : 'N/A'; ?></span></div>
            <div><span class="label">Report Date:</span> <span class="value"><?php echo date('d-m-Y', strtotime($result['report_date'])); ?></span></div>
        </div>

        <!-- Result -->
        <div class="result-box">
            <div class="test-name"><?php echo htmlspecialchars($result['test_name']); ?></div>
            <div class="result-value <?php echo $result['is_abnormal'] ? 'abnormal' : 'normal'; ?>">
                <?php echo htmlspecialchars($result['result_value']); ?> <?php echo htmlspecialchars($result['unit']); ?>
            </div>
            <div>
                <span class="status-badge <?php echo $result['is_abnormal'] ? 'abnormal' : 'normal'; ?>">
                    <?php echo $result['is_abnormal'] ? '⚠ Abnormal' : '✓ Normal'; ?>
                </span>
            </div>
            <div class="result-meta">
                <div><span class="label">Normal Range:</span> <span class="value"><?php echo htmlspecialchars($result['normal_range']); ?></span></div>
                <div><span class="label">Reference Range:</span> <span class="value"><?php echo htmlspecialchars($result['reference_range']); ?></span></div>
                <div><span class="label">Unit:</span> <span class="value"><?php echo htmlspecialchars($result['unit']); ?></span></div>
                <div><span class="label">Entered By:</span> <span class="value"><?php echo htmlspecialchars($result['entered_by_name'] ?? 'System'); ?></span></div>
            </div>
            <?php if(!empty($result['notes'])): ?>
            <div class="notes-section">
                <span class="label">Notes:</span> <?php echo nl2br(htmlspecialchars($result['notes'])); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated report. No signature required.</p>
            <p>Generated on: <?php echo date('d-m-Y H:i'); ?></p>
            <div class="signature">
                <div class="sig-item">
                    <div class="line"></div>
                    <span style="font-size: 11px;">Laboratory Technician</span>
                </div>
                <div class="sig-item">
                    <div class="line"></div>
                    <span style="font-size: 11px;">Reviewed By</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="no-print">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Print
            </button>
            <button onclick="window.close()" class="btn-close">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto-print if requested
            if (window.location.search.includes('auto_print=1')) {
                setTimeout(function() { window.print(); }, 500);
            }
        };
    </script>
</body>
</html>