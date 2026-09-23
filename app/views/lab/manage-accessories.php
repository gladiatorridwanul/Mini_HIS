<?php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lab Accessories - UniDia HMS</title>
    <!-- Use CDN links instead of local files to avoid 404 errors -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- All styles are inline to avoid 404 errors -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        .card-custom { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header-custom { background: white; border-bottom: 2px solid #3b82f6; padding: 10px 15px; font-weight: 600; font-size: 14px; }
        .card-header-custom .badge-count { background: #dbeafe; color: #2563eb; padding: 2px 10px; border-radius: 12px; font-size: 11px; font-weight: 500; margin-left: 8px; }
        .table-custom { font-size: 13px; }
        .table-custom thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; padding: 10px 12px; border-bottom: 2px solid #e2e8f0; }
        .table-custom tbody td { padding: 8px 12px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
        .table-custom tbody tr:hover { background: #f8fafc; }
        .status-badge { padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .status-discontinued { background: #e2e8f0; color: #475569; }
        .btn-action { padding: 4px 8px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 3px; }
        .btn-action:hover { transform: scale(1.05); }
        .btn-edit { background: #dbeafe; color: #2563eb; }
        .btn-edit:hover { background: #2563eb; color: white; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #dc2626; color: white; }
        .btn-stock { background: #fef3c7; color: #92400e; }
        .btn-stock:hover { background: #f59e0b; color: white; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: none; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 700px; width: 92%; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: modalSlideIn 0.3s ease; }
        @keyframes modalSlideIn { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 14px; border-bottom: 1px solid #e5e7eb; margin-bottom: 18px; }
        .modal-header h5 { margin: 0; font-size: 18px; font-weight: 600; }
        .modal-close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 0 8px; line-height: 1; }
        .modal-close-btn:hover { color: #1e293b; }
        .form-group { margin-bottom: 14px; }
        .form-group label { font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 3px; }
        .form-group label .required { color: #ef4444; }
        .form-group .form-control { border-radius: 6px; border: 1px solid #e2e8f0; padding: 6px 12px; font-size: 13px; width: 100%; }
        .form-group .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 10000; }
        .toast { padding: 12px 20px; border-radius: 8px; margin-bottom: 8px; min-width: 250px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: toastSlideIn 0.3s ease; color: white; font-size: 13px; }
        .toast-success { background: #10b981; }
        .toast-error { background: #ef4444; }
        .toast-warning { background: #f59e0b; }
        .toast-info { background: #3b82f6; }
        @keyframes toastSlideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        .empty-state h5 { color: #475569; margin-bottom: 5px; }
        .accessory-code-badge { background: #e2e8f0; color: #475569; padding: 1px 8px; border-radius: 4px; font-size: 11px; font-family: monospace; }
        .stock-low { color: #dc2626; font-weight: 600; }
        .stock-ok { color: #059669; }
        .stock-warning { color: #d97706; }
        .btn-primary { background: #3b82f6; border: none; padding: 8px 20px; border-radius: 6px; color: white; cursor: pointer; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #e5e7eb; border: none; padding: 8px 20px; border-radius: 6px; color: #374151; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #d1d5db; color: #374151; text-decoration: none; }
        .btn-success { background: #10b981; border: none; padding: 6px 16px; border-radius: 6px; color: white; cursor: pointer; font-size: 12px; }
        .btn-success:hover { background: #059669; color: white; }
        .btn-sm { padding: 4px 12px; font-size: 12px; }
        .btn-danger { background: #ef4444; border: none; padding: 8px 20px; border-radius: 6px; color: white; cursor: pointer; }
        .btn-danger:hover { background: #dc2626; }
        .fade-in { animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .stock-updated { background: #d1fae5 !important; transition: background 0.5s; }
        .modal-footer-buttons { display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb; }
        /* Reload overlay */
        .reload-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.85);
            z-index: 99999;
            display: none;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        .reload-overlay.active { display: flex; }
        .reload-overlay .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .reload-overlay .reload-text {
            margin-top: 15px;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
        }
        .reload-overlay .reload-icon {
            font-size: 40px;
            color: #3b82f6;
            margin-bottom: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- RELOAD OVERLAY -->
    <div class="reload-overlay" id="reloadOverlay">
        <div class="reload-icon"><i class="fas fa-sync-alt fa-spin"></i></div>
        <div class="spinner"></div>
        <div class="reload-text">Refreshing page...</div>
    </div>

    <div class="container-fluid py-2">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color:#1f2937;margin:0;"><i class="fas fa-tools" style="color:#3b82f6;"></i> Manage Lab Accessories</h5>
                <p class="text-muted" style="font-size:11px;">Manage laboratory accessories and consumables</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/lab/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button class="btn btn-success" id="addAccessoryBtn">
                    <i class="fas fa-plus"></i> Add Accessory
                </button>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>
                    <i class="fas fa-tools text-primary"></i> Lab Accessories
                    <span class="badge-count" id="accessoryCount"><?php echo isset($accessories) ? count($accessories) : 0; ?> items</span>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th style="width:4%;">#</th>
                                <th style="width:18%;">Accessory Name</th>
                                <th style="width:10%;">Code</th>
                                <th style="width:12%;">Category</th>
                                <th style="width:10%;">Unit Price</th>
                                <th style="width:10%;">Stock</th>
                                <th style="width:10%;">Reorder Level</th>
                                <th style="width:10%;">Status</th>
                                <th style="width:16%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="accessoriesTableBody">
                            <?php if (!empty($accessories)): ?>
                                <?php foreach ($accessories as $index => $acc): ?>
                                    <tr id="acc-row-<?php echo $acc['id']; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo htmlspecialchars($acc['accessory_name']); ?></strong></td>
                                        <td><span class="accessory-code-badge"><?php echo htmlspecialchars($acc['accessory_code']); ?></span></td>
                                        <td><?php echo htmlspecialchars($acc['category'] ?? '-'); ?></td>
                                        <td>৳<?php echo number_format($acc['unit_price'] ?? 0, 2); ?></td>
                                        <td id="stock-<?php echo $acc['id']; ?>">
                                            <?php 
                                            $stock = (int)($acc['current_stock'] ?? 0);
                                            $reorder = (int)($acc['reorder_level'] ?? 10);
                                            if ($stock <= 0) {
                                                echo '<span class="stock-low"><i class="fas fa-exclamation-circle me-1"></i>Out of Stock</span>';
                                            } elseif ($stock <= $reorder) {
                                                echo '<span class="stock-warning">' . $stock . ' (Low)</span>';
                                            } else {
                                                echo '<span class="stock-ok">' . $stock . '</span>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo $reorder; ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = 'status-active';
                                            if (($acc['status'] ?? 'active') == 'inactive') $statusClass = 'status-inactive';
                                            if (($acc['status'] ?? 'active') == 'discontinued') $statusClass = 'status-discontinued';
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($acc['status'] ?? 'active'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <button class="btn-action btn-edit" onclick="editAccessory(<?php echo $acc['id']; ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-action btn-stock" onclick="openStockModal(<?php echo $acc['id']; ?>)" title="Update Stock">
                                                    <i class="fas fa-boxes"></i>
                                                </button>
                                                <button class="btn-action btn-delete" onclick="deleteAccessory(<?php echo $acc['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="emptyRow">
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i class="fas fa-tools"></i>
                                            <h5>No Accessories Found</h5>
                                            <p class="text-muted">Click "Add Accessory" to create your first accessory.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD/EDIT ACCESSORY MODAL -->
    <div class="modal-overlay" id="accessoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalTitle"><i class="fas fa-plus-circle text-primary me-2"></i>Add Accessory</h5>
                <button type="button" class="modal-close-btn" id="closeAccessoryModalBtn">&times;</button>
            </div>
            <form id="accessoryForm">
                <input type="hidden" name="accessory_id" id="accessoryId" value="">
                <input type="hidden" name="action" id="formAction" value="add">

                <div class="form-row">
                    <div class="form-group">
                        <label>Accessory Name <span class="required">*</span></label>
                        <input type="text" name="accessory_name" id="accessoryName" class="form-control" placeholder="e.g. Test Tube" required>
                    </div>
                    <div class="form-group">
                        <label>Accessory Code <span class="required">*</span></label>
                        <input type="text" name="accessory_code" id="accessoryCode" class="form-control" placeholder="e.g. TUB-001" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Category</label>
                        <input type="text" name="category" id="accessoryCategory" class="form-control" placeholder="e.g. Consumables">
                    </div>
                    <div class="form-group">
                        <label>Supplier</label>
                        <input type="text" name="supplier" id="accessorySupplier" class="form-control" placeholder="e.g. Supplier Name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Unit Price (BDT)</label>
                        <input type="number" name="unit_price" id="accessoryPrice" class="form-control" placeholder="e.g. 50" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="accessoryStatus" class="form-control">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="discontinued">Discontinued</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Current Stock</label>
                        <input type="number" name="current_stock" id="accessoryStock" class="form-control" placeholder="e.g. 100" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label>Reorder Level</label>
                        <input type="number" name="reorder_level" id="accessoryReorder" class="form-control" placeholder="e.g. 10" value="10" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="accessoryDescription" class="form-control" placeholder="Enter accessory description..." rows="2"></textarea>
                </div>

                <div class="modal-footer-buttons">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> <span id="submitBtnText">Add Accessory</span>
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelAccessoryBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- STOCK UPDATE MODAL -->
    <div class="modal-overlay" id="stockModal">
        <div class="modal-content" style="max-width:450px;">
            <div class="modal-header">
                <h5><i class="fas fa-boxes text-primary me-2"></i>Update Stock</h5>
                <button type="button" class="modal-close-btn" id="closeStockModalBtn">&times;</button>
            </div>
            <form id="stockForm">
                <input type="hidden" name="id" id="stockAccessoryId" value="">
                <div class="form-group">
                    <label>Accessory Name</label>
                    <input type="text" id="stockAccessoryName" class="form-control" readonly style="background: #f8fafc; font-weight: 600;">
                </div>
                <div class="form-group">
                    <label>Current Stock</label>
                    <input type="text" id="stockCurrentStock" class="form-control" readonly style="background: #f8fafc; font-weight: 600;">
                </div>
                <div class="form-group">
                    <label>Quantity to <span id="stockOperationLabel">Add</span></label>
                    <input type="number" name="quantity" id="stockQuantity" class="form-control" placeholder="Enter quantity" required min="1">
                </div>
                <div class="form-group">
                    <label>Operation</label>
                    <select name="operation" id="stockOperation" class="form-control" onchange="updateStockOperationLabel()">
                        <option value="add">Add Stock (+)</option>
                        <option value="subtract">Remove Stock (-)</option>
                    </select>
                </div>
                <div class="modal-footer-buttons">
                    <button type="submit" class="btn btn-primary" id="stockSubmitBtn">
                        <i class="fas fa-save"></i> Update Stock
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelStockBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content" style="max-width:450px;">
            <div class="modal-header">
                <h5><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Delete</h5>
                <button type="button" class="modal-close-btn" id="closeDeleteModalBtn">&times;</button>
            </div>
            <div class="modal-body text-center py-3">
                <p style="font-size:14px;">Are you sure you want to delete this accessory?</p>
                <p class="text-muted" style="font-size:12px;">This action cannot be undone.</p>
                <input type="hidden" id="deleteAccessoryId" value="">
                <div class="modal-footer-buttons" style="justify-content:center;">
                    <button class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash"></i> Yes, Delete
                    </button>
                    <button class="btn btn-secondary" id="cancelDeleteBtn">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Use jQuery from CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    (function() {
        var BASE_URL = '<?php echo BASE_URL; ?>';

        // ================================================================
        // TOAST NOTIFICATION
        // ================================================================
        function showToast(message, type) {
            type = type || 'success';
            var container = document.getElementById('toastContainer');
            if (!container) return;
            var toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(50px)';
                setTimeout(function() { toast.remove(); }, 300);
            }, 3000);
        }

        // ================================================================
        // RELOAD PAGE FUNCTION
        // ================================================================
        function reloadPageWithOverlay() {
            var overlay = document.getElementById('reloadOverlay');
            if (overlay) {
                overlay.classList.add('active');
            }
            setTimeout(function() {
                window.location.reload();
            }, 600);
        }

        // ================================================================
        // MODAL FUNCTIONS - FIXED: Check if elements exist before accessing
        // ================================================================
        function closeAccessoryModal() {
            var modal = document.getElementById('accessoryModal');
            if (modal) modal.classList.remove('active');
            
            var form = document.getElementById('accessoryForm');
            if (form) form.reset();
            
            var idField = document.getElementById('accessoryId');
            if (idField) idField.value = '';
            
            var actionField = document.getElementById('formAction');
            if (actionField) actionField.value = 'add';
            
            var title = document.getElementById('modalTitle');
            if (title) title.innerHTML = '<i class="fas fa-plus-circle text-primary me-2"></i>Add Accessory';
            
            var btnText = document.getElementById('submitBtnText');
            if (btnText) btnText.textContent = 'Add Accessory';
            
            var btn = document.getElementById('submitBtn');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> <span id="submitBtnText">Add Accessory</span>';
            }
        }

        function openAccessoryModal() {
            var modal = document.getElementById('accessoryModal');
            if (modal) modal.classList.add('active');
        }

        function closeStockModal() {
            var modal = document.getElementById('stockModal');
            if (modal) modal.classList.remove('active');
            
            var form = document.getElementById('stockForm');
            if (form) form.reset();
            
            var btn = document.getElementById('stockSubmitBtn');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Update Stock';
            }
        }

        function openStockModal() {
            var modal = document.getElementById('stockModal');
            if (modal) modal.classList.add('active');
        }

        function closeDeleteModal() {
            var modal = document.getElementById('deleteModal');
            if (modal) modal.classList.remove('active');
            
            var idField = document.getElementById('deleteAccessoryId');
            if (idField) idField.value = '';
        }

        function openDeleteModal(id) {
            var idField = document.getElementById('deleteAccessoryId');
            if (idField) idField.value = id;
            
            var modal = document.getElementById('deleteModal');
            if (modal) modal.classList.add('active');
        }

        // ================================================================
        // UPDATE STOCK OPERATION LABEL
        // ================================================================
        window.updateStockOperationLabel = function() {
            var operation = document.getElementById('stockOperation');
            var label = document.getElementById('stockOperationLabel');
            if (!operation || !label) return;
            
            if (operation.value === 'add') {
                label.textContent = 'Add';
                label.style.color = '#059669';
            } else {
                label.textContent = 'Remove';
                label.style.color = '#dc2626';
            }
        }

        // ================================================================
        // BIND MODAL CLOSE BUTTONS - Check if elements exist
        // ================================================================
        var closeAccessoryBtn = document.getElementById('closeAccessoryModalBtn');
        if (closeAccessoryBtn) closeAccessoryBtn.addEventListener('click', closeAccessoryModal);
        
        var cancelAccessoryBtn = document.getElementById('cancelAccessoryBtn');
        if (cancelAccessoryBtn) cancelAccessoryBtn.addEventListener('click', closeAccessoryModal);
        
        var closeStockBtn = document.getElementById('closeStockModalBtn');
        if (closeStockBtn) closeStockBtn.addEventListener('click', closeStockModal);
        
        var cancelStockBtn = document.getElementById('cancelStockBtn');
        if (cancelStockBtn) cancelStockBtn.addEventListener('click', closeStockModal);
        
        var closeDeleteBtn = document.getElementById('closeDeleteModalBtn');
        if (closeDeleteBtn) closeDeleteBtn.addEventListener('click', closeDeleteModal);
        
        var cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);

        // ================================================================
        // OPEN STOCK MODAL
        // ================================================================
        window.openStockModal = function(id) {
            console.log('Opening stock modal for ID:', id);
            
            var idField = document.getElementById('stockAccessoryId');
            if (idField) idField.value = id;
            
            var nameField = document.getElementById('stockAccessoryName');
            if (nameField) nameField.value = 'Loading...';
            
            var stockField = document.getElementById('stockCurrentStock');
            if (stockField) stockField.value = '...';
            
            $.ajax({
                url: BASE_URL + '/lab/api/accessory/' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    console.log('Stock data response:', response);
                    if (response.success && response.accessory) {
                        var acc = response.accessory;
                        var nameField = document.getElementById('stockAccessoryName');
                        if (nameField) nameField.value = acc.accessory_name || 'N/A';
                        
                        var stockField = document.getElementById('stockCurrentStock');
                        if (stockField) stockField.value = acc.current_stock || 0;
                        
                        var qtyField = document.getElementById('stockQuantity');
                        if (qtyField) qtyField.value = '';
                        
                        var opField = document.getElementById('stockOperation');
                        if (opField) opField.value = 'add';
                        
                        updateStockOperationLabel();
                        openStockModal();
                    } else {
                        showToast(response.message || 'Error loading accessory data', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading stock data:', status, error);
                    showToast('Error loading accessory data. Please try again.', 'error');
                }
            });
        };

        // ================================================================
        // ADD ACCESSORY BUTTON
        // ================================================================
        var addBtn = document.getElementById('addAccessoryBtn');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                var form = document.getElementById('accessoryForm');
                if (form) form.reset();
                
                var idField = document.getElementById('accessoryId');
                if (idField) idField.value = '';
                
                var actionField = document.getElementById('formAction');
                if (actionField) actionField.value = 'add';
                
                var title = document.getElementById('modalTitle');
                if (title) title.innerHTML = '<i class="fas fa-plus-circle text-primary me-2"></i>Add Accessory';
                
                var btnText = document.getElementById('submitBtnText');
                if (btnText) btnText.textContent = 'Add Accessory';
                
                var stockField = document.getElementById('accessoryStock');
                if (stockField) stockField.value = 0;
                
                var reorderField = document.getElementById('accessoryReorder');
                if (reorderField) reorderField.value = 10;
                
                var statusField = document.getElementById('accessoryStatus');
                if (statusField) statusField.value = 'active';
                
                openAccessoryModal();
            });
        }

        // ================================================================
        // EDIT ACCESSORY
        // ================================================================
        window.editAccessory = function(id) {
            console.log('Editing accessory ID:', id);
            
            $.ajax({
                url: BASE_URL + '/lab/api/accessory/' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.accessory) {
                        var acc = response.accessory;
                        
                        var idField = document.getElementById('accessoryId');
                        if (idField) idField.value = acc.id;
                        
                        var actionField = document.getElementById('formAction');
                        if (actionField) actionField.value = 'edit';
                        
                        var title = document.getElementById('modalTitle');
                        if (title) title.innerHTML = '<i class="fas fa-edit text-warning me-2"></i>Edit Accessory';
                        
                        var btnText = document.getElementById('submitBtnText');
                        if (btnText) btnText.textContent = 'Update Accessory';
                        
                        var nameField = document.getElementById('accessoryName');
                        if (nameField) nameField.value = acc.accessory_name || '';
                        
                        var codeField = document.getElementById('accessoryCode');
                        if (codeField) codeField.value = acc.accessory_code || '';
                        
                        var catField = document.getElementById('accessoryCategory');
                        if (catField) catField.value = acc.category || '';
                        
                        var supField = document.getElementById('accessorySupplier');
                        if (supField) supField.value = acc.supplier || '';
                        
                        var priceField = document.getElementById('accessoryPrice');
                        if (priceField) priceField.value = acc.unit_price || 0;
                        
                        var statusField = document.getElementById('accessoryStatus');
                        if (statusField) statusField.value = acc.status || 'active';
                        
                        var stockField = document.getElementById('accessoryStock');
                        if (stockField) stockField.value = acc.current_stock || 0;
                        
                        var reorderField = document.getElementById('accessoryReorder');
                        if (reorderField) reorderField.value = acc.reorder_level || 10;
                        
                        var descField = document.getElementById('accessoryDescription');
                        if (descField) descField.value = acc.description || '';
                        
                        openAccessoryModal();
                    } else {
                        showToast(response.message || 'Error loading accessory data', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', status, error);
                    showToast('Error loading accessory data. Please try again.', 'error');
                }
            });
        };

        // ================================================================
        // DELETE ACCESSORY
        // ================================================================
        window.deleteAccessory = function(id) {
            openDeleteModal(id);
        };

        var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                var idField = document.getElementById('deleteAccessoryId');
                var id = idField ? idField.value : null;
                if (!id) return;
                
                $.ajax({
                    url: BASE_URL + '/lab/api/accessory/delete/' + id,
                    type: 'POST',
                    dataType: 'json',
                    success: function(response) {
                        closeDeleteModal();
                        if (response.success) {
                            showToast(response.message, 'success');
                            reloadPageWithOverlay();
                        } else {
                            showToast(response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        closeDeleteModal();
                        console.error('Error:', status, error);
                        showToast('Error deleting accessory. Please try again.', 'error');
                    }
                });
            });
        }

        // ================================================================
        // SUBMIT ACCESSORY FORM
        // ================================================================
        var accessoryForm = document.getElementById('accessoryForm');
        if (accessoryForm) {
            accessoryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                var action = document.getElementById('formAction');
                var id = document.getElementById('accessoryId');
                var data = new FormData(this);
                
                var url = BASE_URL + '/lab/api/accessory';
                if (action && action.value === 'edit' && id && id.value) {
                    url = BASE_URL + '/lab/api/accessory/edit/' + id.value;
                }
                
                var submitBtn = document.getElementById('submitBtn');
                var originalHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                }
                
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log('Accessory save response:', response);
                        if (response.success) {
                            closeAccessoryModal();
                            showToast(response.message, 'success');
                            reloadPageWithOverlay();
                        } else {
                            showToast(response.message, 'error');
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalHtml;
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', status, error);
                        showToast('Error saving accessory. Please try again.', 'error');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        }
                    }
                });
            });
        }

        // ================================================================
        // SUBMIT STOCK FORM
        // ================================================================
        var stockForm = document.getElementById('stockForm');
        if (stockForm) {
            stockForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                var idField = document.getElementById('stockAccessoryId');
                var qtyField = document.getElementById('stockQuantity');
                var opField = document.getElementById('stockOperation');
                
                var id = idField ? idField.value : null;
                var quantity = qtyField ? qtyField.value : null;
                var operation = opField ? opField.value : null;
                
                console.log('=== STOCK UPDATE SUBMIT ===');
                console.log('ID:', id);
                console.log('Quantity:', quantity);
                console.log('Operation:', operation);
                
                if (!quantity || quantity <= 0) {
                    showToast('Please enter a valid quantity', 'warning');
                    return;
                }
                
                var submitBtn = document.getElementById('stockSubmitBtn');
                var originalHtml = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                }
                
                var formData = new FormData();
                formData.append('id', id);
                formData.append('quantity', quantity);
                formData.append('operation', operation);
                
                $.ajax({
                    url: BASE_URL + '/lab/api/accessory/update-stock',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    timeout: 30000,
                    success: function(response) {
                        console.log('Stock update response:', response);
                        
                        if (response.success) {
                            closeStockModal();
                            showToast(response.message + ' (New stock: ' + response.new_stock + ')', 'success');
                            
                            var stockCell = $('#stock-' + id);
                            var newStock = response.new_stock || 0;
                            var row = $('#acc-row-' + id);
                            var reorder = parseInt(row.find('td:eq(6)').text()) || 10;
                            
                            if (newStock <= 0) {
                                stockCell.html('<span class="stock-low"><i class="fas fa-exclamation-circle me-1"></i>Out of Stock</span>');
                            } else if (newStock <= reorder) {
                                stockCell.html('<span class="stock-warning">' + newStock + ' (Low)</span>');
                            } else {
                                stockCell.html('<span class="stock-ok">' + newStock + '</span>');
                            }
                            
                            row.addClass('stock-updated');
                            setTimeout(function() {
                                row.removeClass('stock-updated');
                            }, 1500);
                            
                        } else {
                            showToast(response.message || 'Error updating stock', 'error');
                        }
                        
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Stock update error:', status, error);
                        console.error('Response text:', xhr.responseText);
                        
                        var msg = 'Error updating stock. Please try again.';
                        try {
                            var json = JSON.parse(xhr.responseText);
                            if (json.message) msg = json.message;
                        } catch (e) {}
                        
                        showToast(msg, 'error');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        }
                    }
                });
            });
        }

        // ================================================================
        // UPDATE ROW NUMBERS
        // ================================================================
        function updateRowNumbers() {
            $('#accessoriesTableBody tr').each(function(index) {
                $(this).find('td:first').text(index + 1);
            });
        }

        // ================================================================
        // UPDATE BADGE COUNT
        // ================================================================
        function updateBadgeCount() {
            var count = $('#accessoriesTableBody tr:visible').length;
            $('#accessoryCount').text(count + ' items');
            if (count === 0) {
                $('#accessoriesTableBody').html(`
                    <tr id="emptyRow">
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-tools"></i>
                                <h5>No Accessories Found</h5>
                                <p class="text-muted">Click "Add Accessory" to create your first accessory.</p>
                            </div>
                        </td>
                    </tr>
                `);
            }
        }

        // ================================================================
        // CLOSE MODALS ON ESCAPE KEY
        // ================================================================
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var accessoryModal = document.getElementById('accessoryModal');
                if (accessoryModal && accessoryModal.classList.contains('active')) {
                    closeAccessoryModal();
                }
                var stockModal = document.getElementById('stockModal');
                if (stockModal && stockModal.classList.contains('active')) {
                    closeStockModal();
                }
                var deleteModal = document.getElementById('deleteModal');
                if (deleteModal && deleteModal.classList.contains('active')) {
                    closeDeleteModal();
                }
            }
        });

        // ================================================================
        // CLOSE MODALS ON OVERLAY CLICK
        // ================================================================
        document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    if (this.id === 'accessoryModal') closeAccessoryModal();
                    if (this.id === 'stockModal') closeStockModal();
                    if (this.id === 'deleteModal') closeDeleteModal();
                }
            });
        });

        console.log('Manage Accessories page initialized');
    })();
    </script>
</body>
</html>