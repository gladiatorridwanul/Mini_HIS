<?php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Test Accessories - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        .card-custom { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header-custom { background: white; border-bottom: 2px solid #3b82f6; padding: 10px 15px; font-weight: 600; font-size: 14px; }
        .card-header-custom .badge-count { background: #dbeafe; color: #2563eb; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; margin-left: 8px; }
        .accessory-item { background: #f8fafc; padding: 12px 15px; border-radius: 6px; border: 1px solid #e5e7eb; margin-bottom: 8px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .accessory-item:hover { background: #f1f5f9; border-color: #3b82f6; }
        .accessory-item .info { flex: 1; min-width: 150px; }
        .accessory-item .info .name { font-weight: 600; font-size: 14px; }
        .accessory-item .info .code { font-size: 11px; color: #64748b; }
        .accessory-item .controls { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .accessory-item .controls input[type="number"] { width: 70px; padding: 4px 6px; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 13px; }
        .accessory-item .controls input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }
        .accessory-item .controls label { font-size: 12px; color: #475569; margin: 0; cursor: pointer; }
        .btn-action { padding: 4px 8px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-action:hover { transform: scale(1.05); }
        .btn-remove { background: #fee2e2; color: #dc2626; }
        .btn-remove:hover { background: #dc2626; color: white; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 10000; }
        .toast { padding: 12px 20px; border-radius: 8px; margin-bottom: 8px; min-width: 250px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: toastSlideIn 0.3s ease; color: white; }
        .toast-success { background: #10b981; }
        .toast-error { background: #ef4444; }
        .toast-warning { background: #f59e0b; }
        .toast-info { background: #3b82f6; }
        @keyframes toastSlideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        .empty-state h5 { color: #475569; margin-bottom: 5px; }
        .assigned-accessories-list { max-height: 400px; overflow-y: auto; }
        .assigned-accessories-list::-webkit-scrollbar { width: 4px; }
        .assigned-accessories-list::-webkit-scrollbar-track { background: #f1f5f9; }
        .assigned-accessories-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .stock-status { font-size: 11px; padding: 2px 8px; border-radius: 12px; }
        .stock-ok { background: #d1fae5; color: #065f46; }
        .stock-low { background: #fef3c7; color: #92400e; }
        .stock-out { background: #fee2e2; color: #991b1b; }
        .accessory-select-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px; margin: 10px 0; }
        .accessory-select-item { background: #f8fafc; padding: 10px 14px; border-radius: 6px; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; transition: all 0.2s; }
        .accessory-select-item:hover { background: #f1f5f9; border-color: #3b82f6; }
        .accessory-select-item input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; flex-shrink: 0; }
        .accessory-select-item .info { flex: 1; }
        .accessory-select-item .info .name { font-weight: 500; font-size: 13px; }
        .accessory-select-item .info .code { font-size: 11px; color: #64748b; }
        .accessory-select-item .info .stock-info { font-size: 11px; }
        .accessory-select-item .controls { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .accessory-select-item .controls input[type="number"] { width: 60px; padding: 3px 6px; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 12px; }
        .accessory-select-item .controls label { font-size: 11px; color: #475569; margin: 0; cursor: pointer; }
        .accessory-select-item .controls input[type="text"] { width: 120px; padding: 3px 6px; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 12px; }
        .accessory-select-item.selected { background: #dbeafe; border-color: #3b82f6; }
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
                <h5 style="color:#1f2937;margin:0;"><i class="fas fa-tools" style="color:#3b82f6;"></i> Manage Test Accessories</h5>
                <p class="text-muted" style="font-size:11px;">Assign accessories to <strong><?php echo htmlspecialchars($test['test_name'] ?? 'Test'); ?></strong></p>
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
                <i class="fas fa-tools text-primary"></i> 
                <?php echo htmlspecialchars($test['test_name'] ?? 'Test'); ?> - Accessory Assignment
                <span class="badge-count"><?php echo count($assignedAccessories ?? []); ?> accessories assigned</span>
            </div>
            <div class="card-body p-3">
                <form id="accessoryAssignmentForm">
                    <input type="hidden" name="test_id" value="<?php echo $test['id'] ?? 0; ?>">
                    
                    <div class="alert alert-info py-2" style="font-size:12px;">
                        <i class="fas fa-info-circle me-1"></i> Select the accessories required for this test. 
                        Specify quantity needed and mark if it's required.
                    </div>

                    <?php if (!empty($accessories)): ?>
                        <div class="accessory-select-grid">
                            <?php foreach ($accessories as $acc): ?>
                                <?php 
                                $assigned = isset($assignedAccessories[$acc['id']]);
                                $qty = $assigned ? $assignedAccessories[$acc['id']]['quantity_required'] : 1;
                                $required = $assigned ? $assignedAccessories[$acc['id']]['is_required'] : 1;
                                $notes = $assigned ? ($assignedAccessories[$acc['id']]['notes'] ?? '') : '';
                                $stock = (int)($acc['current_stock'] ?? 0);
                                $reorderLevel = (int)($acc['reorder_level'] ?? 10);
                                $stockClass = $stock <= 0 ? 'stock-out' : ($stock <= $reorderLevel ? 'stock-low' : 'stock-ok');
                                $stockLabel = $stock <= 0 ? 'Out of Stock' : ($stock <= $reorderLevel ? 'Low Stock' : 'In Stock');
                                ?>
                                <div class="accessory-select-item <?php echo $assigned ? 'selected' : ''; ?>" id="acc-wrapper-<?php echo $acc['id']; ?>">
                                    <input type="checkbox" 
                                           name="accessory_ids[]" 
                                           value="<?php echo $acc['id']; ?>" 
                                           id="acc-<?php echo $acc['id']; ?>"
                                           <?php echo $assigned ? 'checked' : ''; ?>
                                           onchange="toggleAccessory(<?php echo $acc['id']; ?>)">
                                    <div class="info">
                                        <div class="name"><?php echo htmlspecialchars($acc['accessory_name']); ?></div>
                                        <div class="code"><?php echo htmlspecialchars($acc['accessory_code']); ?></div>
                                        <div class="stock-info">
                                            <span class="stock-status <?php echo $stockClass; ?>"><?php echo $stockLabel; ?> (<?php echo $stock; ?>)</span>
                                            <?php if (!empty($acc['unit_price']) && $acc['unit_price'] > 0): ?>
                                                <span class="text-muted ms-2">৳<?php echo number_format($acc['unit_price'], 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="controls">
                                        <label>Qty:</label>
                                        <input type="number" 
                                               name="quantities[<?php echo $acc['id']; ?>]" 
                                               value="<?php echo $qty; ?>" 
                                               min="1" 
                                               <?php echo !$assigned ? 'disabled' : ''; ?>
                                               id="qty-<?php echo $acc['id']; ?>">
                                        <label>
                                            <input type="checkbox" 
                                                   name="is_required[<?php echo $acc['id']; ?>]" 
                                                   value="1" 
                                                   <?php echo ($required && $assigned) ? 'checked' : ''; ?>
                                                   <?php echo !$assigned ? 'disabled' : ''; ?>
                                                   id="req-<?php echo $acc['id']; ?>">
                                            Required
                                        </label>
                                        <input type="text" 
                                               name="notes[<?php echo $acc['id']; ?>]" 
                                               value="<?php echo htmlspecialchars($notes); ?>" 
                                               placeholder="Notes" 
                                               style="width:120px; padding:3px 6px; border-radius:4px; border:1px solid #e2e8f0; font-size:12px;"
                                               <?php echo !$assigned ? 'disabled' : ''; ?>
                                               id="notes-<?php echo $acc['id']; ?>">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-tools"></i>
                            <h5>No Accessories Available</h5>
                            <p class="text-muted">Please add accessories first.</p>
                            <a href="<?php echo BASE_URL; ?>/lab/manage-accessories" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Add Accessories
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($accessories)): ?>
                        <div class="mt-3 pt-2 border-top">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Assignment
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>/lab/manage-tests'">
                                Cancel
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Current Assigned Accessories -->
        <?php if (!empty($assignedAccessories)): ?>
            <div class="card-custom mt-3">
                <div class="card-header-custom">
                    <i class="fas fa-list text-primary"></i> Currently Assigned Accessories
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Accessory Name</th>
                                    <th>Code</th>
                                    <th>Qty Required</th>
                                    <th>Required</th>
                                    <th>Current Stock</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                foreach ($accessories as $acc): 
                                    if (isset($assignedAccessories[$acc['id']])):
                                        $assigned = $assignedAccessories[$acc['id']];
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><strong><?php echo htmlspecialchars($acc['accessory_name']); ?></strong></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($acc['accessory_code']); ?></span></td>
                                        <td><?php echo $assigned['quantity_required']; ?></td>
                                        <td>
                                            <?php if ($assigned['is_required']): ?>
                                                <span class="badge bg-danger">Required</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Optional</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $stock = (int)($acc['current_stock'] ?? 0);
                                            if ($stock <= 0): 
                                                echo '<span class="text-danger">Out of Stock</span>';
                                            elseif ($stock <= ($acc['reorder_level'] ?? 10)): 
                                                echo '<span class="text-warning">' . $stock . ' (Low)</span>';
                                            else: 
                                                echo '<span class="text-success">' . $stock . '</span>';
                                            endif; 
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($assigned['notes'] ?? '-'); ?></td>
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

    function toggleAccessory(accessoryId) {
        const checkbox = document.getElementById('acc-' + accessoryId);
        const wrapper = document.getElementById('acc-wrapper-' + accessoryId);
        const qty = document.getElementById('qty-' + accessoryId);
        const req = document.getElementById('req-' + accessoryId);
        const notes = document.getElementById('notes-' + accessoryId);
        
        if (checkbox.checked) {
            wrapper.classList.add('selected');
            qty.disabled = false;
            req.disabled = false;
            notes.disabled = false;
        } else {
            wrapper.classList.remove('selected');
            qty.disabled = true;
            req.disabled = true;
            notes.disabled = true;
        }
    }

    document.getElementById('accessoryAssignmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const checked = document.querySelectorAll('input[name="accessory_ids[]"]:checked');
        if (checked.length === 0) {
            showToast('Please select at least one accessory', 'warning');
            return;
        }
        
        const formData = new FormData(this);
        
        $.ajax({
            url: BASE_URL + '/lab/api/test-accessories/' + testId,
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

    // Initialize accessory states on page load
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($accessories)): ?>
            <?php foreach ($accessories as $acc): ?>
                toggleAccessory(<?php echo $acc['id']; ?>);
            <?php endforeach; ?>
        <?php endif; ?>
    });
    </script>
</body>
</html>