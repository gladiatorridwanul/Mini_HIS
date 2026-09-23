<?php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Test Instruments - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        .card-custom { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header-custom { background: white; border-bottom: 2px solid #3b82f6; padding: 10px 15px; font-weight: 600; font-size: 14px; }
        .card-header-custom .badge-count { background: #dbeafe; color: #2563eb; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; margin-left: 8px; }
        .checkbox-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; margin: 10px 0; }
        .checkbox-item { background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 10px; transition: all 0.2s; }
        .checkbox-item:hover { background: #f1f5f9; border-color: #3b82f6; }
        .checkbox-item input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }
        .checkbox-item label { cursor: pointer; margin: 0; flex: 1; }
        .checkbox-item .instrument-code { font-size: 11px; color: #64748b; }
        .checkbox-item.primary-selected { background: #dbeafe; border-color: #3b82f6; }
        .btn-action { padding: 4px 8px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-action:hover { transform: scale(1.05); }
        .btn-edit { background: #dbeafe; color: #2563eb; }
        .btn-edit:hover { background: #2563eb; color: white; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #dc2626; color: white; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 10000; }
        .toast { padding: 12px 20px; border-radius: 8px; margin-bottom: 8px; min-width: 250px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: toastSlideIn 0.3s ease; color: white; }
        .toast-success { background: #10b981; }
        .toast-error { background: #ef4444; }
        .toast-warning { background: #f59e0b; }
        .toast-info { background: #3b82f6; }
        @keyframes toastSlideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .badge-primary { background: #dbeafe; color: #2563eb; padding: 2px 8px; border-radius: 12px; font-size: 10px; }
        .instrument-list { max-height: 400px; overflow-y: auto; }
        .instrument-list::-webkit-scrollbar { width: 4px; }
        .instrument-list::-webkit-scrollbar-track { background: #f1f5f9; }
        .instrument-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .table-custom { font-size: 13px; }
        .table-custom thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
        .table-custom tbody td { padding: 8px 12px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
    </style>
</head>
<body>
    <div class="container-fluid py-2">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color:#1f2937;margin:0;"><i class="fas fa-flask" style="color:#3b82f6;"></i> Manage Test Instruments</h5>
                <p class="text-muted" style="font-size:11px;">Assign instruments to <strong><?php echo htmlspecialchars($test['test_name'] ?? 'Test'); ?></strong></p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/lab/manage-tests" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Tests
                </a>
                <a href="<?php echo BASE_URL; ?>/lab/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-flask text-primary"></i> 
                <?php echo htmlspecialchars($test['test_name'] ?? 'Test'); ?> - Instrument Assignment
                <span class="badge-count"><?php echo count($assignedInstruments ?? []); ?> instruments assigned</span>
            </div>
            <div class="card-body p-3">
                <form id="instrumentAssignmentForm">
                    <input type="hidden" name="test_id" value="<?php echo $test['id'] ?? 0; ?>">
                    
                    <div class="alert alert-info py-2" style="font-size:12px;">
                        <i class="fas fa-info-circle me-1"></i> Select the instruments used for this test. Mark one as <strong>Primary</strong> instrument.
                    </div>

                    <div class="instrument-list">
                        <div class="checkbox-grid">
                            <?php if (!empty($instruments)): ?>
                                <?php foreach ($instruments as $inst): ?>
                                    <?php $isAssigned = in_array($inst['id'], $assignedInstruments ?? []); ?>
                                    <div class="checkbox-item <?php echo $isAssigned ? 'primary-selected' : ''; ?>" id="inst-wrapper-<?php echo $inst['id']; ?>">
                                        <input type="checkbox" 
                                               name="instrument_ids[]" 
                                               value="<?php echo $inst['id']; ?>" 
                                               id="inst-<?php echo $inst['id']; ?>"
                                               <?php echo $isAssigned ? 'checked' : ''; ?>
                                               onchange="togglePrimary(<?php echo $inst['id']; ?>)">
                                        <label for="inst-<?php echo $inst['id']; ?>">
                                            <?php echo htmlspecialchars($inst['instrument_name']); ?>
                                            <br>
                                            <span class="instrument-code"><?php echo htmlspecialchars($inst['instrument_code']); ?></span>
                                            <?php if (!empty($inst['model'])): ?>
                                                <span class="instrument-code"> | <?php echo htmlspecialchars($inst['model']); ?></span>
                                            <?php endif; ?>
                                        </label>
                                        <div>
                                            <input type="radio" 
                                                   name="primary_instrument" 
                                                   value="<?php echo $inst['id']; ?>" 
                                                   id="primary-<?php echo $inst['id']; ?>"
                                                   <?php echo ($primaryInstrument ?? 0) == $inst['id'] ? 'checked' : ''; ?>
                                                   <?php echo !$isAssigned ? 'disabled' : ''; ?>>
                                            <label for="primary-<?php echo $inst['id']; ?>" style="font-size:10px;color:#64748b;cursor:pointer;">
                                                Primary
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-3 text-muted" style="grid-column: 1 / -1;">
                                    <i class="fas fa-info-circle me-1"></i> No instruments available. 
                                    <a href="<?php echo BASE_URL; ?>/lab/manage-instruments">Add instruments first</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3 pt-2 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Assignment
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>/lab/manage-tests'">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Current Assigned Instruments -->
        <?php if (!empty($assignedInstruments)): ?>
            <div class="card-custom mt-3">
                <div class="card-header-custom">
                    <i class="fas fa-list text-primary"></i> Currently Assigned Instruments
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Instrument Name</th>
                                    <th>Code</th>
                                    <th>Model</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                foreach ($instruments as $inst): 
                                    if (in_array($inst['id'], $assignedInstruments ?? [])):
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($inst['instrument_name']); ?></strong></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($inst['instrument_code']); ?></span></td>
                                        <td><?php echo htmlspecialchars($inst['model'] ?? '-'); ?></td>
                                        <td>
                                            <?php if (($primaryInstrument ?? 0) == $inst['id']): ?>
                                                <span class="badge bg-primary">Primary</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Secondary</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn-action btn-delete" onclick="removeInstrument(<?php echo $test['id'] ?? 0; ?>, <?php echo $inst['id']; ?>)" title="Remove">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php 
                                    endif; 
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    const testId = <?php echo $test['id'] ?? 0; ?>;

    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function togglePrimary(instrumentId) {
        const checkbox = document.getElementById('inst-' + instrumentId);
        const primaryRadio = document.getElementById('primary-' + instrumentId);
        const wrapper = document.getElementById('inst-wrapper-' + instrumentId);
        
        if (checkbox.checked) {
            primaryRadio.disabled = false;
            wrapper.classList.add('primary-selected');
        } else {
            primaryRadio.disabled = true;
            primaryRadio.checked = false;
            wrapper.classList.remove('primary-selected');
        }
    }

    function removeInstrument(testId, instrumentId) {
        if (!confirm('Remove this instrument from the test?')) return;
        
        const checkbox = document.getElementById('inst-' + instrumentId);
        if (checkbox) {
            checkbox.checked = false;
            checkbox.dispatchEvent(new Event('change'));
        }
        
        document.getElementById('instrumentAssignmentForm').submit();
    }

    document.getElementById('instrumentAssignmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: BASE_URL + '/lab/api/test-instruments/' + testId,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showToast(response.message, 'success');
                    setTimeout(function() { location.reload(); }, 500);
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function() {
                showToast('Error saving assignments', 'error');
            }
        });
    });

    // Initialize primary radio buttons on page load
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($instruments)): ?>
            <?php foreach ($instruments as $inst): ?>
                togglePrimary(<?php echo $inst['id']; ?>);
            <?php endforeach; ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>