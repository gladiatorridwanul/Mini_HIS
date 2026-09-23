<?php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lab Test Categories - UniDia HMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        .btn-action { padding: 4px 8px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 3px; }
        .btn-action:hover { transform: scale(1.05); }
        .btn-edit { background: #dbeafe; color: #2563eb; }
        .btn-edit:hover { background: #2563eb; color: white; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #dc2626; color: white; }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: none; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 500px; width: 92%; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: modalSlideIn 0.3s ease; }
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
        .btn-primary { background: #3b82f6; border: none; padding: 8px 20px; border-radius: 6px; color: white; cursor: pointer; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #e5e7eb; border: none; padding: 8px 20px; border-radius: 6px; color: #374151; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #d1d5db; color: #374151; text-decoration: none; }
        .btn-success { background: #10b981; border: none; padding: 6px 16px; border-radius: 6px; color: white; cursor: pointer; font-size: 12px; }
        .btn-success:hover { background: #059669; color: white; }
        .btn-sm { padding: 4px 12px; font-size: 12px; }
        .btn-danger { background: #ef4444; border: none; padding: 8px 20px; border-radius: 6px; color: white; cursor: pointer; }
        .btn-danger:hover { background: #dc2626; }
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
                <h5 style="color:#1f2937;margin:0;"><i class="fas fa-tags" style="color:#3b82f6;"></i> Manage Lab Test Categories</h5>
                <p class="text-muted" style="font-size:11px;">Create, edit, and manage lab test categories</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/lab/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button class="btn btn-success" id="addCategoryBtn">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>
                    <i class="fas fa-tags text-primary"></i> Lab Test Categories
                    <span class="badge-count" id="categoryCount"><?php echo isset($categories) ? count($categories) : 0; ?> categories</span>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th style="width:5%;">#</th>
                                <th style="width:20%;">Name</th>
                                <th style="width:15%;">Code</th>
                                <th style="width:35%;">Description</th>
                                <th style="width:10%;">Status</th>
                                <th style="width:15%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="categoriesTableBody">
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $index => $cat): ?>
                                    <tr id="cat-row-<?php echo $cat['id']; ?>">
                                        <td><?php echo $index + 1; ?></td>
                                        <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($cat['code'] ?? 'N/A'); ?></span></td>
                                        <td><?php echo htmlspecialchars($cat['description'] ?? '-'); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $cat['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($cat['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button class="btn-action btn-edit" onclick="editCategory(<?php echo $cat['id']; ?>)" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-action btn-delete" onclick="deleteCategory(<?php echo $cat['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="emptyRow">
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-tags"></i>
                                            <h5>No Categories Found</h5>
                                            <p class="text-muted">Click "Add Category" to create your first category.</p>
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

    <!-- ADD/EDIT CATEGORY MODAL -->
    <div class="modal-overlay" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 id="modalTitle"><i class="fas fa-plus-circle text-primary me-2"></i>Add Category</h5>
                <button type="button" class="modal-close-btn" id="closeCategoryModalBtn">&times;</button>
            </div>
            <form id="categoryForm">
                <input type="hidden" name="category_id" id="categoryId" value="">
                <input type="hidden" name="action" id="formAction" value="add">

                <div class="form-group">
                    <label>Category Name <span class="required">*</span></label>
                    <input type="text" name="name" id="categoryName" class="form-control" placeholder="e.g. Hematology" required>
                </div>

                <div class="form-group">
                    <label>Code</label>
                    <input type="text" name="code" id="categoryCode" class="form-control" placeholder="e.g. HEMA">
                    <small class="text-muted">Short code for the category</small>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="categoryDescription" class="form-control" placeholder="Enter category description..." rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="categoryStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="modal-footer-buttons">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> <span id="submitBtnText">Add Category</span>
                    </button>
                    <button type="button" class="btn btn-secondary" id="cancelCategoryBtn">Cancel</button>
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
                <p style="font-size:14px;">Are you sure you want to delete this category?</p>
                <p class="text-muted" style="font-size:12px;">This action cannot be undone.</p>
                <input type="hidden" id="deleteCategoryId" value="">
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
        // MODAL FUNCTIONS
        // ================================================================
        function closeCategoryModal() {
            var modal = document.getElementById('categoryModal');
            if (modal) modal.classList.remove('active');
            
            var form = document.getElementById('categoryForm');
            if (form) form.reset();
            
            var idField = document.getElementById('categoryId');
            if (idField) idField.value = '';
            
            var actionField = document.getElementById('formAction');
            if (actionField) actionField.value = 'add';
            
            var title = document.getElementById('modalTitle');
            if (title) title.innerHTML = '<i class="fas fa-plus-circle text-primary me-2"></i>Add Category';
            
            var btnText = document.getElementById('submitBtnText');
            if (btnText) btnText.textContent = 'Add Category';
            
            var btn = document.getElementById('submitBtn');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> <span id="submitBtnText">Add Category</span>';
            }
        }

        function openCategoryModal() {
            var modal = document.getElementById('categoryModal');
            if (modal) modal.classList.add('active');
        }

        function closeDeleteModal() {
            var modal = document.getElementById('deleteModal');
            if (modal) modal.classList.remove('active');
            
            var idField = document.getElementById('deleteCategoryId');
            if (idField) idField.value = '';
        }

        function openDeleteModal(id) {
            var idField = document.getElementById('deleteCategoryId');
            if (idField) idField.value = id;
            
            var modal = document.getElementById('deleteModal');
            if (modal) modal.classList.add('active');
        }

        // ================================================================
        // BIND MODAL CLOSE BUTTONS
        // ================================================================
        var closeCategoryBtn = document.getElementById('closeCategoryModalBtn');
        if (closeCategoryBtn) closeCategoryBtn.addEventListener('click', closeCategoryModal);
        
        var cancelCategoryBtn = document.getElementById('cancelCategoryBtn');
        if (cancelCategoryBtn) cancelCategoryBtn.addEventListener('click', closeCategoryModal);
        
        var closeDeleteBtn = document.getElementById('closeDeleteModalBtn');
        if (closeDeleteBtn) closeDeleteBtn.addEventListener('click', closeDeleteModal);
        
        var cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);

        // ================================================================
        // ADD CATEGORY BUTTON
        // ================================================================
        var addBtn = document.getElementById('addCategoryBtn');
        if (addBtn) {
            addBtn.addEventListener('click', function() {
                var form = document.getElementById('categoryForm');
                if (form) form.reset();
                
                var idField = document.getElementById('categoryId');
                if (idField) idField.value = '';
                
                var actionField = document.getElementById('formAction');
                if (actionField) actionField.value = 'add';
                
                var title = document.getElementById('modalTitle');
                if (title) title.innerHTML = '<i class="fas fa-plus-circle text-primary me-2"></i>Add Category';
                
                var btnText = document.getElementById('submitBtnText');
                if (btnText) btnText.textContent = 'Add Category';
                
                var statusField = document.getElementById('categoryStatus');
                if (statusField) statusField.value = 'active';
                
                openCategoryModal();
            });
        }

        // ================================================================
        // EDIT CATEGORY
        // ================================================================
        window.editCategory = function(id) {
            console.log('Editing category ID:', id);
            
            $.ajax({
                url: BASE_URL + '/lab/api/category/' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.category) {
                        var cat = response.category;
                        
                        var idField = document.getElementById('categoryId');
                        if (idField) idField.value = cat.id;
                        
                        var actionField = document.getElementById('formAction');
                        if (actionField) actionField.value = 'edit';
                        
                        var title = document.getElementById('modalTitle');
                        if (title) title.innerHTML = '<i class="fas fa-edit text-warning me-2"></i>Edit Category';
                        
                        var btnText = document.getElementById('submitBtnText');
                        if (btnText) btnText.textContent = 'Update Category';
                        
                        var nameField = document.getElementById('categoryName');
                        if (nameField) nameField.value = cat.name || '';
                        
                        var codeField = document.getElementById('categoryCode');
                        if (codeField) codeField.value = cat.code || '';
                        
                        var descField = document.getElementById('categoryDescription');
                        if (descField) descField.value = cat.description || '';
                        
                        var statusField = document.getElementById('categoryStatus');
                        if (statusField) statusField.value = cat.status || 'active';
                        
                        openCategoryModal();
                    } else {
                        showToast(response.message || 'Error loading category data', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', status, error);
                    showToast('Error loading category data. Please try again.', 'error');
                }
            });
        };

        // ================================================================
        // DELETE CATEGORY
        // ================================================================
        window.deleteCategory = function(id) {
            openDeleteModal(id);
        };

        var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function() {
                var idField = document.getElementById('deleteCategoryId');
                var id = idField ? idField.value : null;
                if (!id) return;
                
                $.ajax({
                    url: BASE_URL + '/lab/api/category/delete/' + id,
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
                        showToast('Error deleting category. Please try again.', 'error');
                    }
                });
            });
        }

        // ================================================================
        // SUBMIT CATEGORY FORM
        // ================================================================
        var categoryForm = document.getElementById('categoryForm');
        if (categoryForm) {
            categoryForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                var action = document.getElementById('formAction');
                var id = document.getElementById('categoryId');
                var data = new FormData(this);
                
                var url = BASE_URL + '/lab/api/category';
                if (action && action.value === 'edit' && id && id.value) {
                    url = BASE_URL + '/lab/api/category/edit/' + id.value;
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
                        console.log('Category save response:', response);
                        if (response.success) {
                            closeCategoryModal();
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
                        showToast('Error saving category. Please try again.', 'error');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalHtml;
                        }
                    }
                });
            });
        }

        // ================================================================
        // UPDATE BADGE COUNT
        // ================================================================
        function updateBadgeCount() {
            var count = $('#categoriesTableBody tr:visible').length;
            $('#categoryCount').text(count + ' categories');
            if (count === 0) {
                $('#categoriesTableBody').html(`
                    <tr id="emptyRow">
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fas fa-tags"></i>
                                <h5>No Categories Found</h5>
                                <p class="text-muted">Click "Add Category" to create your first category.</p>
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
                var categoryModal = document.getElementById('categoryModal');
                if (categoryModal && categoryModal.classList.contains('active')) {
                    closeCategoryModal();
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
                    if (this.id === 'categoryModal') closeCategoryModal();
                    if (this.id === 'deleteModal') closeDeleteModal();
                }
            });
        });

        console.log('Manage Categories page initialized');
    })();
    </script>
</body>
</html>