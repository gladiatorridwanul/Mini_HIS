<?php
// app/views/lab/manage-tests.php - Complete Lab Test Management Page
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Lab Tests - UniDia HMS</title>
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
        .table-custom .status-badge { padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        .filter-section { background: #f8fafc; border-radius: 8px; padding: 15px; margin-bottom: 15px; border: 1px solid #e2e8f0; }
        .filter-label { font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 3px; display: block; }
        .form-control-sm-custom { height: 32px; font-size: 13px; border-radius: 6px; border: 1px solid #e2e8f0; padding: 4px 10px; width: 100%; }
        .form-control-sm-custom:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
        .btn-action { padding: 4px 8px; font-size: 12px; border-radius: 4px; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 3px; }
        .btn-action:hover { transform: scale(1.05); }
        .btn-edit { background: #dbeafe; color: #2563eb; }
        .btn-edit:hover { background: #2563eb; color: white; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #dc2626; color: white; }
        .btn-toggle { background: #f1f5f9; color: #475569; }
        .btn-toggle:hover { background: #e2e8f0; }
        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        .empty-state h5 { color: #475569; margin-bottom: 5px; }
        .search-box { position: relative; }
        .search-box input { padding-right: 35px; }
        .search-box .search-icon { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .badge-category { background: #f1f5f9; color: #475569; padding: 2px 10px; border-radius: 12px; font-size: 11px; }
        .test-code-badge { background: #e2e8f0; color: #475569; padding: 1px 8px; border-radius: 4px; font-size: 11px; font-family: monospace; }
        .action-buttons { display: flex; gap: 4px; flex-wrap: nowrap; align-items: center; }
        .assigned-list { display: flex; flex-wrap: wrap; gap: 4px; margin: 4px 0; }
        .assigned-pill { background: #e2e8f0; color: #475569; padding: 2px 10px; border-radius: 12px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px; }
        .assigned-pill-primary { background: #dbeafe; color: #2563eb; }
        .assigned-pill-required { background: #fef3c7; color: #92400e; }
        .assigned-pill .pill-icon { font-size: 9px; }
        .assigned-pill .pill-label { font-size: 10px; }
        .pagination-custom { display: flex; gap: 4px; flex-wrap: wrap; }
        .pagination-custom .page-item { list-style: none; }
        .pagination-custom .page-link { display: block; padding: 5px 12px; border-radius: 4px; border: 1px solid #e2e8f0; color: #475569; text-decoration: none; font-size: 13px; transition: all 0.2s; background: white; }
        .pagination-custom .page-link:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .pagination-custom .active .page-link { background: #3b82f6; color: white; border-color: #3b82f6; }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 10000; }
        .toast { padding: 12px 20px; border-radius: 8px; margin-bottom: 8px; min-width: 250px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: toastSlideIn 0.3s ease; color: white; font-size: 13px; }
        .toast-success { background: #10b981; }
        .toast-error { background: #ef4444; }
        .toast-warning { background: #f59e0b; }
        .toast-info { background: #3b82f6; }
        @keyframes toastSlideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: none; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; }
        .modal-content { background: white; border-radius: 12px; max-width: 450px; width: 92%; padding: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: modalSlideIn 0.3s ease; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 14px; border-bottom: 1px solid #e5e7eb; margin-bottom: 18px; }
        .modal-header h5 { margin: 0; font-size: 18px; font-weight: 600; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 0 8px; }
        .modal-close:hover { color: #1e293b; }
        .btn-primary { background: #3b82f6; border: none; padding: 8px 20px; border-radius: 6px; color: white; cursor: pointer; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #e5e7eb; border: none; padding: 8px 20px; border-radius: 6px; color: #374151; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #d1d5db; color: #374151; text-decoration: none; }
        .btn-danger { background: #ef4444; border: none; padding: 8px 20px; border-radius: 6px; color: white; cursor: pointer; }
        .btn-danger:hover { background: #dc2626; }
        .btn-sm { padding: 4px 12px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container-fluid py-2">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color:#1f2937;margin:0;"><i class="fas fa-flask" style="color:#3b82f6;"></i> Manage Lab Tests</h5>
                <p class="text-muted" style="font-size:11px;">Create, edit, and manage laboratory test definitions</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/lab/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>

        <!-- FILTER SECTION -->
        <div class="filter-section">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="filter-label">Search</label>
                    <div class="search-box">
                        <input type="text" id="searchInput" class="form-control-sm-custom" placeholder="Search by name or code..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <span class="search-icon"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Category</label>
                    <select id="categoryFilter" class="form-control-sm-custom">
                        <option value="">All Categories</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo (isset($categoryFilter) && $categoryFilter == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="filter-label">Status</label>
                    <select id="statusFilter" class="form-control-sm-custom">
                        <option value="">All Status</option>
                        <option value="active" <?php echo (isset($statusFilter) && $statusFilter == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (isset($statusFilter) && $statusFilter == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="filter-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm" id="applyFiltersBtn" style="font-size:12px;">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <button class="btn btn-secondary btn-sm" id="resetFiltersBtn" style="font-size:12px;">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
                <div class="col-md-2 text-end">
                    <a href="<?php echo BASE_URL; ?>/lab/add-test" class="btn btn-success btn-sm" style="font-size:12px;">
                        <i class="fas fa-plus"></i> Add New Test
                    </a>
                </div>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>
                    <i class="fas fa-flask text-primary"></i> Lab Tests
                    <span class="badge-count"><?php echo $totalTests ?? 0; ?> tests</span>
                </span>
                <span style="font-size:12px; color:#64748b;">
                    <i class="fas fa-check-circle text-success"></i> Active: <?php echo $activeCount ?? 0; ?>
                    <span class="ms-2"><i class="fas fa-times-circle text-danger"></i> Inactive: <?php echo $inactiveCount ?? 0; ?></span>
                </span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom" id="testsTable">
                        <thead>
                            <tr>
                                <th style="width:3%;">#</th>
                                <th style="width:12%;">Test Name</th>
                                <th style="width:7%;">Code</th>
                                <th style="width:10%;">Category</th>
                                <th style="width:7%;">Price</th>
                                <th style="width:7%;">Turnaround</th>
                                <th style="width:18%;">Instruments</th>
                                <th style="width:20%;">Accessories</th>
                                <th style="width:7%;">Status</th>
                                <th style="width:9%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="testsTableBody">
                            <?php if (!empty($tests)): ?>
                                <?php $counter = 1; foreach ($tests as $test): ?>
                                    <?php
                                    // Get instruments for this test
                                    $instQuery = $this->db->query("SELECT i.instrument_name, i.instrument_code, lti.is_primary 
                                                                  FROM lab_test_instruments lti 
                                                                  JOIN lab_instruments i ON lti.instrument_id = i.id 
                                                                  WHERE lti.test_id = " . (int)$test['id']);
                                    $instruments = [];
                                    while ($instRow = $instQuery->fetch_assoc()) {
                                        $instruments[] = $instRow;
                                    }
                                    
                                    // Get accessories for this test
                                    $accQuery = $this->db->query("SELECT a.accessory_name, a.accessory_code, lta.quantity_required, lta.is_required 
                                                                  FROM lab_test_accessories lta 
                                                                  JOIN lab_accessories a ON lta.accessory_id = a.id 
                                                                  WHERE lta.test_id = " . (int)$test['id']);
                                    $accessories = [];
                                    while ($accRow = $accQuery->fetch_assoc()) {
                                        $accessories[] = $accRow;
                                    }
                                    ?>
                                    <tr id="test-row-<?php echo $test['id']; ?>">
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                                            <?php if (!empty($test['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($test['description'], 0, 40)); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="test-code-badge"><?php echo htmlspecialchars($test['test_code'] ?? 'N/A'); ?></span></td>
                                        <td>
                                            <span class="badge-category"><?php echo htmlspecialchars($test['category_name'] ?? 'Uncategorized'); ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($test['price'])): ?>
                                                <span class="fw-bold">৳<?php echo number_format($test['price'], 2); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($test['turnaround_time'])): ?>
                                                <?php echo $test['turnaround_time']; ?> hrs
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="assigned-list">
                                                <?php if (!empty($instruments)): ?>
                                                    <?php foreach ($instruments as $inst): ?>
                                                        <?php 
                                                        $class = $inst['is_primary'] ? 'assigned-pill-primary' : '';
                                                        $label = $inst['is_primary'] ? '★' : '';
                                                        ?>
                                                        <span class="assigned-pill <?php echo $class; ?>" title="<?php echo htmlspecialchars($inst['instrument_code']); ?>">
                                                            <i class="fas fa-microscope pill-icon"></i>
                                                            <span class="pill-label"><?php echo htmlspecialchars(substr($inst['instrument_name'], 0, 14)); ?></span>
                                                            <?php if ($label): ?>
                                                                <span style="color:#f59e0b;font-size:9px;"><?php echo $label; ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size:10px;">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="assigned-list">
                                                <?php if (!empty($accessories)): ?>
                                                    <?php foreach ($accessories as $acc): ?>
                                                        <?php 
                                                        $class = $acc['is_required'] ? 'assigned-pill-required' : '';
                                                        $requiredLabel = $acc['is_required'] ? 'R' : 'O';
                                                        ?>
                                                        <span class="assigned-pill <?php echo $class; ?>" title="<?php echo htmlspecialchars($acc['accessory_code']); ?>">
                                                            <i class="fas fa-tools pill-icon"></i>
                                                            <span class="pill-label"><?php echo htmlspecialchars(substr($acc['accessory_name'], 0, 12)); ?></span>
                                                            <?php if ($acc['quantity_required'] > 1): ?>
                                                                <span style="font-size:9px;color:#64748b;">x<?php echo $acc['quantity_required']; ?></span>
                                                            <?php endif; ?>
                                                            <span style="font-size:8px;color:#64748b;margin-left:2px;">(<?php echo $requiredLabel; ?>)</span>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size:10px;">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo ($test['status'] ?? 'active') == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo ucfirst($test['status'] ?? 'active'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="<?php echo BASE_URL; ?>/lab/edit-test/<?php echo $test['id']; ?>" class="btn-action btn-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn-action btn-toggle" onclick="toggleStatus(<?php echo $test['id']; ?>)" title="Toggle Status">
                                                    <i class="fas <?php echo ($test['status'] ?? 'active') == 'active' ? 'fa-pause' : 'fa-play'; ?>"></i>
                                                </button>
                                                <button class="btn-action btn-delete" onclick="deleteTest(<?php echo $test['id']; ?>)" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10">
                                        <div class="empty-state">
                                            <i class="fas fa-flask"></i>
                                            <h5>No Lab Tests Found</h5>
                                            <p class="text-muted">Click the "Add New Test" button to create your first lab test.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (isset($totalPages) && $totalPages > 1): ?>
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div style="font-size:12px; color:#64748b;">Showing <?php echo count($tests ?? []); ?> of <?php echo $totalTests ?? 0; ?> tests</div>
                        <ul class="pagination-custom">
                            <li class="page-item <?php echo ($page ?? 1) <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="#" data-page="<?php echo ($page ?? 1) - 1; ?>">‹</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i == ($page ?? 1)) ? 'active' : ''; ?>">
                                    <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page ?? 1) >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="#" data-page="<?php echo ($page ?? 1) + 1; ?>">›</a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5><i class="fas fa-exclamation-triangle text-danger me-2"></i>Confirm Delete</h5>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body text-center py-3">
                <p style="font-size:14px;">Are you sure you want to delete this lab test?</p>
                <p class="text-muted" style="font-size:12px;">This action cannot be undone.</p>
                <input type="hidden" id="deleteTestId" value="">
                <div class="d-flex gap-2 justify-content-center mt-3">
                    <button class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash"></i> Yes, Delete
                    </button>
                    <button class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
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

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            document.getElementById('deleteTestId').value = '';
        }

        function openDeleteModal() {
            document.getElementById('deleteModal').classList.add('active');
        }

        window.deleteTest = function(id) {
            document.getElementById('deleteTestId').value = id;
            openDeleteModal();
        };

        window.confirmDelete = function() {
            var id = document.getElementById('deleteTestId').value;
            if (!id) return;
            $.ajax({
                url: BASE_URL + '/api/lab-tests/delete/' + id,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    closeDeleteModal();
                    if (response.success) {
                        showToast(response.message || 'Test deleted successfully', 'success');
                        $('#test-row-' + id).fadeOut(300, function() { $(this).remove(); });
                        setTimeout(function() { location.reload(); }, 500);
                    } else {
                        showToast(response.message || 'Error deleting test', 'error');
                    }
                },
                error: function() {
                    closeDeleteModal();
                    showToast('Error deleting test. Please try again.', 'error');
                }
            });
        };

        window.toggleStatus = function(id) {
            if (!confirm('Toggle the status of this test?')) return;
            $.ajax({
                url: BASE_URL + '/api/lab-tests/toggle/' + id,
                type: 'POST',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showToast(response.message || 'Status toggled successfully', 'success');
                        location.reload();
                    } else {
                        showToast(response.message || 'Error toggling status', 'error');
                    }
                },
                error: function() {
                    showToast('Error toggling status. Please try again.', 'error');
                }
            });
        };

        // Apply Filters
        document.getElementById('applyFiltersBtn').addEventListener('click', function() { applyFilters(); });
        document.getElementById('resetFiltersBtn').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.getElementById('statusFilter').value = '';
            applyFilters();
        });

        function applyFilters() {
            var search = document.getElementById('searchInput').value.trim();
            var category = document.getElementById('categoryFilter').value;
            var status = document.getElementById('statusFilter').value;
            var url = BASE_URL + '/lab/manage-tests?';
            if (search) url += 'search=' + encodeURIComponent(search) + '&';
            if (category) url += 'category=' + encodeURIComponent(category) + '&';
            if (status) url += 'status=' + encodeURIComponent(status) + '&';
            url += 'page=1';
            window.location.href = url;
        }

        // Pagination
        document.querySelectorAll('.pagination-custom .page-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                var page = this.dataset.page;
                if (page && !this.closest('.disabled')) {
                    var url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    window.location.href = url.href;
                }
            });
        });

        // Search on Enter
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') { applyFilters(); }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (document.getElementById('deleteModal').classList.contains('active')) {
                    closeDeleteModal();
                }
            }
        });
    })();
    </script>
</body>
</html>