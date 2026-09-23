<?php
// /app/views/lab/manual/edit.php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Manual Lab Result - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        .card-custom { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header-custom { background: white; border-bottom: 2px solid #3b82f6; padding: 10px 15px; font-weight: 600; font-size: 14px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; font-size: 13px; transition: all 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); outline: none; }
        .form-group textarea { min-height: 60px; resize: vertical; }
        .required-star { color: #ef4444; }
        .btn-tab { padding: 8px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; border: none; text-decoration: none; }
        .btn-update { background: #f59e0b; color: white; }
        .btn-update:hover { background: #d97706; }
        .btn-cancel { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-cancel:hover { background: #e2e8f0; color: #1e293b; }
        .selected-test-info { background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; display: none; }
        .selected-test-info.show { display: block; }
        .test-info-item { display: flex; gap: 8px; padding: 4px 0; font-size: 13px; }
        .test-info-item .label { font-weight: 500; color: #64748b; min-width: 100px; }
        .test-info-item .value { color: #1e293b; }
        
        /* ============================================================ */
        /* REDESIGNED ABNORMAL CHECKLIST - SAME AS CREATE.PHP */
        /* ============================================================ */
        .abnormal-checklist {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 4px;
        }
        .abnormal-checklist .checklist-title {
            font-size: 11px;
            font-weight: 600;
            color: #475569;
            display: block;
            margin-bottom: 6px;
        }
        .abnormal-checklist .checklist-items {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .abnormal-checklist .checklist-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
            user-select: none;
        }
        .abnormal-checklist .checklist-item:hover {
            border-color: #94a3b8;
            background: #f1f5f9;
        }
        .abnormal-checklist .checklist-item input[type="checkbox"] {
            width: 14px;
            height: 14px;
            margin: 0;
            cursor: pointer;
            accent-color: #10b981;
        }
        .abnormal-checklist .checklist-item input[type="checkbox"]:checked {
            accent-color: #dc2626;
        }
        .abnormal-checklist .checklist-item .icon-checked {
            display: none;
            color: #dc2626;
        }
        .abnormal-checklist .checklist-item input:checked ~ .icon-checked {
            display: inline-block;
        }
        .abnormal-checklist .checklist-item input:checked ~ .label-text {
            color: #dc2626;
            font-weight: 600;
        }
        .abnormal-checklist .checklist-item input:checked {
            border-color: #dc2626;
        }
        .abnormal-checklist .checklist-item .label-text {
            font-weight: 500;
            color: #334155;
        }
        .abnormal-checklist .checklist-item input:checked + .label-text {
            color: #dc2626;
            font-weight: 600;
        }
        .abnormal-checklist .checklist-item.high {
            border-color: #fee2e2;
            background: #fef2f2;
        }
        .abnormal-checklist .checklist-item.high input:checked ~ .label-text {
            color: #dc2626;
        }
        .abnormal-checklist .checklist-item.medium {
            border-color: #fef3c7;
            background: #fffbeb;
        }
        .abnormal-checklist .checklist-item.medium input:checked ~ .label-text {
            color: #d97706;
        }
        .abnormal-checklist .checklist-item.low {
            border-color: #dbeafe;
            background: #eff6ff;
        }
        .abnormal-checklist .checklist-item.low input:checked ~ .label-text {
            color: #2563eb;
        }
        .abnormal-checklist .checklist-item .badge-severity {
            font-size: 9px;
            padding: 1px 6px;
            border-radius: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .abnormal-checklist .checklist-item .badge-severity.high-badge {
            background: #fee2e2;
            color: #991b1b;
        }
        .abnormal-checklist .checklist-item .badge-severity.medium-badge {
            background: #fef3c7;
            color: #92400e;
        }
        .abnormal-checklist .checklist-item .badge-severity.low-badge {
            background: #dbeafe;
            color: #1e40af;
        }
        .abnormal-checklist .checklist-item input:checked ~ .badge-severity.high-badge {
            background: #dc2626;
            color: white;
        }
        .abnormal-checklist .checklist-item input:checked ~ .badge-severity.medium-badge {
            background: #d97706;
            color: white;
        }
        .abnormal-checklist .checklist-item input:checked ~ .badge-severity.low-badge {
            background: #2563eb;
            color: white;
        }
        .abnormal-checklist .checklist-note {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 6px;
            display: block;
        }
        
        .abnormal-selected-display {
            display: none;
            margin-top: 8px;
            padding: 8px 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 6px;
            font-size: 12px;
            color: #991b1b;
        }
        .abnormal-selected-display.show {
            display: block;
        }
        .abnormal-selected-display i {
            margin-right: 6px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-flask" style="color: #3b82f6;"></i> Edit Manual Lab Result</h5>
                <p class="text-muted" style="font-size: 11px;">Edit the lab test result</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/lab/manual" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['errors']) && count($_SESSION['errors']) > 0): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <?php unset($_SESSION['errors']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Main Card -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-flask text-primary"></i> Edit Result
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo BASE_URL; ?>/lab/manual/update/<?php echo $result['id']; ?>" id="resultForm">
                    <div class="row">
                        <!-- Patient Selection -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Patient <span class="required-star">*</span></label>
                                <select name="patient_id" id="patient_id" class="form-select" required>
                                    <option value="">Select Patient</option>
                                    <?php foreach ($patients as $p): ?>
                                        <option value="<?php echo $p['id']; ?>" <?php echo ($result['patient_id'] == $p['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($p['full_name']); ?> (<?php echo $p['patient_code']; ?> - <?php echo $p['phone']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Test Selection -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Lab Test <span class="required-star">*</span></label>
                                <select name="test_id" id="test_id" class="form-select">
                                    <option value="">Select Test (or type manually below)</option>
                                    <?php foreach ($tests as $t): ?>
                                        <option value="<?php echo $t['id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($t['test_name']); ?>"
                                                data-normal="<?php echo htmlspecialchars($t['normal_range']); ?>"
                                                data-unit="<?php echo htmlspecialchars($t['unit']); ?>"
                                                data-category="<?php echo htmlspecialchars($t['category_name']); ?>"
                                                <?php echo ($result['test_id'] == $t['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($t['test_name']); ?> (<?php echo $t['category_name']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Test Info -->
                    <div class="selected-test-info <?php echo $result['test_id'] > 0 ? 'show' : ''; ?>" id="testInfo">
                        <div class="test-info-item">
                            <span class="label">Category:</span>
                            <span class="value" id="info_category">-</span>
                        </div>
                        <div class="test-info-item">
                            <span class="label">Normal Range:</span>
                            <span class="value" id="info_normal">-</span>
                        </div>
                        <div class="test-info-item">
                            <span class="label">Unit:</span>
                            <span class="value" id="info_unit">-</span>
                        </div>
                    </div>

                    <hr>

                    <!-- Test Name (Manual) -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Test Name <span class="required-star">*</span></label>
                                <input type="text" name="test_name" id="test_name" class="form-control" value="<?php echo htmlspecialchars($result['test_name']); ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Result Value -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Result Value <span class="required-star">*</span></label>
                                <input type="text" name="result_value" id="result_value" class="form-control" value="<?php echo htmlspecialchars($result['result_value']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Unit</label>
                                <input type="text" name="unit" id="unit" class="form-control" value="<?php echo htmlspecialchars($result['unit']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Normal Range & Reference Range -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Normal Range</label>
                                <input type="text" name="normal_range" id="normal_range" class="form-control" value="<?php echo htmlspecialchars($result['normal_range']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reference Range (Optional)</label>
                                <input type="text" name="reference_range" id="reference_range" class="form-control" value="<?php echo htmlspecialchars($result['reference_range']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- REDESIGNED ABNORMAL CHECKLIST - SAME AS CREATE.PHP -->
                    <!-- ============================================================ -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Result Status</label>
                                <div class="abnormal-checklist">
                                    <span class="checklist-title"><i class="fas fa-clipboard-check me-1"></i> Mark as Abnormal</span>
                                    <div class="checklist-items">
                                        <!-- Normal - Default unchecked -->
                                        <label class="checklist-item low" style="border-color: #d1fae5; background: #ecfdf5;">
                                            <input type="checkbox" name="is_abnormal" id="abnormal_normal" value="0" <?php echo !$result['is_abnormal'] ? 'checked' : ''; ?>>
                                            <span class="label-text" style="color: #059669;">
                                                <i class="fas fa-check-circle me-1"></i> Normal
                                            </span>
                                        </label>
                                        
                                        <!-- Abnormal - High Severity -->
                                        <label class="checklist-item high">
                                            <input type="checkbox" name="is_abnormal" id="abnormal_high" value="1" class="abnormal-check" <?php echo ($result['is_abnormal'] && isset($result['abnormal_severity']) && $result['abnormal_severity'] == 'high') ? 'checked' : ''; ?>>
                                            <span class="badge-severity high-badge">High</span>
                                            <span class="label-text">Abnormal</span>
                                            <span class="icon-checked"><i class="fas fa-exclamation-triangle"></i></span>
                                        </label>
                                        
                                        <!-- Abnormal - Medium Severity -->
                                        <label class="checklist-item medium">
                                            <input type="checkbox" name="is_abnormal" id="abnormal_medium" value="1" class="abnormal-check" <?php echo ($result['is_abnormal'] && isset($result['abnormal_severity']) && $result['abnormal_severity'] == 'medium') ? 'checked' : ''; ?>>
                                            <span class="badge-severity medium-badge">Medium</span>
                                            <span class="label-text">Borderline</span>
                                            <span class="icon-checked"><i class="fas fa-exclamation-circle"></i></span>
                                        </label>
                                        
                                        <!-- Abnormal - Low Severity -->
                                        <label class="checklist-item low">
                                            <input type="checkbox" name="is_abnormal" id="abnormal_low" value="1" class="abnormal-check" <?php echo ($result['is_abnormal'] && isset($result['abnormal_severity']) && $result['abnormal_severity'] == 'low') ? 'checked' : ''; ?>>
                                            <span class="badge-severity low-badge">Low</span>
                                            <span class="label-text">Slightly Elevated</span>
                                            <span class="icon-checked"><i class="fas fa-info-circle"></i></span>
                                        </label>
                                    </div>
                                    <span class="checklist-note">
                                        <i class="fas fa-info-circle me-1"></i> 
                                        Select "Normal" for normal results, or choose severity level for abnormal results
                                    </span>
                                </div>
                                
                                <!-- Status Display -->
                                <div class="abnormal-selected-display <?php echo $result['is_abnormal'] ? 'show' : ''; ?>" id="statusDisplay">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span id="statusDisplayText">
                                        <?php 
                                        if ($result['is_abnormal']) {
                                            $severity = isset($result['abnormal_severity']) ? ucfirst($result['abnormal_severity']) : 'High';
                                            echo 'Marked as Abnormal - ' . $severity . ' severity';
                                        } else {
                                            echo 'Marked as Normal';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Date -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Report Date</label>
                                <input type="date" name="report_date" class="form-control" value="<?php echo $result['report_date'] ?? date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- Empty for spacing -->
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Any additional notes or remarks"><?php echo htmlspecialchars($result['notes']); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn-tab btn-update">
                            <i class="fas fa-save"></i> Update Result
                        </button>
                        <a href="<?php echo BASE_URL; ?>/lab/manual" class="btn-tab btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // ================================================================
        // TEST SELECTION - Auto-fill test details
        // ================================================================
        document.getElementById('test_id').addEventListener('change', function() {
            const select = this;
            const selected = select.options[select.selectedIndex];
            const testInfo = document.getElementById('testInfo');
            
            if (select.value > 0) {
                const testName = selected.dataset.name || '';
                const normalRange = selected.dataset.normal || '';
                const unit = selected.dataset.unit || '';
                const category = selected.dataset.category || '';
                
                document.getElementById('test_name').value = testName;
                document.getElementById('unit').value = unit;
                document.getElementById('normal_range').value = normalRange;
                
                document.getElementById('info_category').textContent = category || '-';
                document.getElementById('info_normal').textContent = normalRange || '-';
                document.getElementById('info_unit').textContent = unit || '-';
                
                testInfo.classList.add('show');
            } else {
                testInfo.classList.remove('show');
            }
        });

        // ================================================================
        // ABNORMAL CHECKLIST LOGIC - SAME AS CREATE.PHP
        // ================================================================
        const abnormalChecks = document.querySelectorAll('.abnormal-check');
        const normalCheck = document.getElementById('abnormal_normal');
        const statusDisplay = document.getElementById('statusDisplay');
        const statusDisplayText = document.getElementById('statusDisplayText');

        // Function to update abnormal status
        function updateAbnormalStatus() {
            // Check if any abnormal is checked
            let isAbnormal = false;
            let severityText = '';
            let severityClass = '';

            abnormalChecks.forEach(function(check) {
                if (check.checked) {
                    isAbnormal = true;
                    const parent = check.closest('.checklist-item');
                    const badge = parent.querySelector('.badge-severity');
                    if (badge) {
                        severityText = badge.textContent.trim();
                        if (badge.classList.contains('high-badge')) {
                            severityClass = 'text-danger';
                            statusDisplay.style.borderColor = '#fecaca';
                            statusDisplay.style.background = '#fef2f2';
                            statusDisplay.style.color = '#991b1b';
                        } else if (badge.classList.contains('medium-badge')) {
                            severityClass = 'text-warning';
                            statusDisplay.style.borderColor = '#fde68a';
                            statusDisplay.style.background = '#fffbeb';
                            statusDisplay.style.color = '#92400e';
                        } else {
                            severityClass = 'text-info';
                            statusDisplay.style.borderColor = '#bfdbfe';
                            statusDisplay.style.background = '#eff6ff';
                            statusDisplay.style.color = '#1e40af';
                        }
                    }
                }
            });

            // If abnormal is checked, uncheck normal
            if (isAbnormal) {
                normalCheck.checked = false;
                statusDisplay.classList.add('show');
                statusDisplayText.textContent = 'Marked as Abnormal - ' + severityText + ' severity';
                statusDisplay.style.borderLeft = '4px solid #dc2626';
                
                // Update icon
                const icon = statusDisplay.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-exclamation-triangle';
                }
            } else {
                // If normal is not checked, check it
                if (!normalCheck.checked) {
                    normalCheck.checked = true;
                }
                statusDisplay.classList.remove('show');
                statusDisplayText.textContent = 'Marked as Normal';
            }
        }

        // Handle abnormal checkbox clicks - only one abnormal can be selected at a time
        abnormalChecks.forEach(function(check) {
            check.addEventListener('change', function() {
                if (this.checked) {
                    // Uncheck other abnormal checks
                    abnormalChecks.forEach(function(other) {
                        if (other !== check) {
                            other.checked = false;
                        }
                    });
                    // Uncheck normal
                    normalCheck.checked = false;
                } else {
                    // If all abnormal are unchecked, check normal
                    let anyChecked = false;
                    abnormalChecks.forEach(function(other) {
                        if (other.checked) anyChecked = true;
                    });
                    if (!anyChecked) {
                        normalCheck.checked = true;
                    }
                }
                updateAbnormalStatus();
            });
        });

        // Handle normal checkbox click - uncheck all abnormal
        normalCheck.addEventListener('change', function() {
            if (this.checked) {
                abnormalChecks.forEach(function(check) {
                    check.checked = false;
                });
                statusDisplay.classList.remove('show');
                statusDisplayText.textContent = 'Marked as Normal';
            } else {
                // If normal is unchecked, check the first abnormal (High)
                if (!abnormalChecks[0].checked) {
                    abnormalChecks[0].checked = true;
                    updateAbnormalStatus();
                }
            }
            updateAbnormalStatus();
        });

        // ================================================================
        // FORM SUBMISSION - Ensure correct value is submitted
        // ================================================================
        document.getElementById('resultForm').addEventListener('submit', function(e) {
            // Determine if abnormal is checked
            let isAbnormal = false;
            let severity = 'high';
            
            abnormalChecks.forEach(function(check) {
                if (check.checked) {
                    isAbnormal = true;
                    const parent = check.closest('.checklist-item');
                    const badge = parent.querySelector('.badge-severity');
                    if (badge) {
                        const text = badge.textContent.trim().toLowerCase();
                        if (text === 'medium') severity = 'medium';
                        else if (text === 'low') severity = 'low';
                        else severity = 'high';
                    }
                }
            });
            
            // Create hidden inputs for is_abnormal and severity
            let abnormalInput = document.querySelector('input[name="is_abnormal"]');
            if (!abnormalInput) {
                abnormalInput = document.createElement('input');
                abnormalInput.type = 'hidden';
                abnormalInput.name = 'is_abnormal';
                this.appendChild(abnormalInput);
            }
            abnormalInput.value = isAbnormal ? '1' : '0';
            
            let severityInput = document.querySelector('input[name="abnormal_severity"]');
            if (!severityInput) {
                severityInput = document.createElement('input');
                severityInput.type = 'hidden';
                severityInput.name = 'abnormal_severity';
                this.appendChild(severityInput);
            }
            severityInput.value = isAbnormal ? severity : 'normal';
        });

        // ================================================================
        // INITIALIZE - Set default state based on existing data
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            // If result is abnormal, ensure the correct severity is checked
            if (<?php echo $result['is_abnormal'] ? 'true' : 'false'; ?>) {
                const severity = '<?php echo isset($result['abnormal_severity']) ? $result['abnormal_severity'] : 'high'; ?>';
                
                // Uncheck normal
                normalCheck.checked = false;
                
                // Check the correct severity
                abnormalChecks.forEach(function(check) {
                    const parent = check.closest('.checklist-item');
                    const badge = parent.querySelector('.badge-severity');
                    if (badge) {
                        const text = badge.textContent.trim().toLowerCase();
                        if (text === severity) {
                            check.checked = true;
                        } else {
                            check.checked = false;
                        }
                    }
                });
                
                // Update display
                updateAbnormalStatus();
            } else {
                // Ensure normal is checked
                normalCheck.checked = true;
                abnormalChecks.forEach(function(check) {
                    check.checked = false;
                });
                statusDisplay.classList.remove('show');
                statusDisplayText.textContent = 'Marked as Normal';
            }
        });
    </script>
</body>
</html>