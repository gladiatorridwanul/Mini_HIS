<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Management - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        
        .filter-section {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .filter-section label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 4px;
        }
        .filter-section .form-control,
        .filter-section .form-select {
            font-size: 13px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .filter-section .btn-filter {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-section .btn-filter:hover {
            background: #2563eb;
        }
        .filter-section .btn-reset {
            background: #e2e8f0;
            color: #475569;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            margin-left: 8px;
        }
        .filter-section .btn-reset:hover {
            background: #cbd5e1;
        }
        
        .btn-add-store {
            background: #10b981;
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-add-store:hover {
            background: #059669;
            color: white;
        }
        
        .store-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .store-table th {
            padding: 12px 15px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
            white-space: nowrap;
        }
        .store-table td {
            padding: 12px 15px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .store-table tr:hover td {
            background: #fafbfc;
        }
        .store-table .table-header-sticky {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .code-badge {
            background: #eef2ff;
            color: #4338ca;
            padding: 3px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 11px;
            font-weight: 600;
        }
        
        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }
        .type-main { background: #dbeafe; color: #1e40af; }
        .type-branch { background: #d1fae5; color: #065f46; }
        .type-clinic { background: #ede9fe; color: #5b21b6; }
        .type-warehouse { background: #fef3c7; color: #92400e; }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            display: inline-block;
        }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        .btn-action {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 4px;
        }
        .btn-action i { margin-right: 3px; }
        .btn-edit { background: #3b82f6; color: white; }
        .btn-edit:hover { background: #2563eb; color: white; }
        .btn-stock { background: #8b5cf6; color: white; }
        .btn-stock:hover { background: #7c3aed; color: white; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-delete:hover { background: #dc2626; color: white; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }
        .empty-icon { font-size: 48px; color: #cbd5e1; margin-bottom: 15px; }
        .empty-title { font-size: 18px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .empty-text { color: #94a3b8; font-size: 13px; }
        
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pagination-container .page-info {
            font-size: 13px;
            color: #6c757d;
        }
        .pagination .page-item .page-link {
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 6px;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        .pagination .page-item.active .page-link {
            background: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        .pagination .page-item .page-link:hover {
            background: #f1f5f9;
        }
        
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        .modal-header {
            border-radius: 16px 16px 0 0;
        }
        .modal-footer {
            border-radius: 0 0 16px 16px;
        }
        .required-field::after {
            content: " *";
            color: #ef4444;
        }
        
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .store-table th, .store-table td { padding: 8px 10px; font-size: 11px; }
            .btn-action { padding: 4px 8px; font-size: 10px; }
            .pagination-container { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-store text-success me-2"></i>Store Management</h1>
            <p class="page-subtitle">Manage your pharmacy branches, clinics, and warehouses</p>
        </div>
        <div>
            <button class="btn-add-store" onclick="openAddStoreModal()">
                <i class="fas fa-plus me-2"></i>Add New Store
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="<?php echo BASE_URL; ?>/inventory/stores" id="filterForm">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label><i class="fas fa-search me-1"></i>Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Store name, code, manager..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                <div class="col-md-2">
                    <label><i class="fas fa-tag me-1"></i>Store Type</label>
                    <select name="store_type" class="form-select form-select-sm">
                        <option value="all">All Types</option>
                        <option value="main_pharmacy" <?php echo (isset($_GET['store_type']) && $_GET['store_type'] == 'main_pharmacy') ? 'selected' : ''; ?>>🏪 Main Pharmacy</option>
                        <option value="branch" <?php echo (isset($_GET['store_type']) && $_GET['store_type'] == 'branch') ? 'selected' : ''; ?>>🏬 Branch</option>
                        <option value="clinic" <?php echo (isset($_GET['store_type']) && $_GET['store_type'] == 'clinic') ? 'selected' : ''; ?>>🏥 Clinic</option>
                        <option value="warehouse" <?php echo (isset($_GET['store_type']) && $_GET['store_type'] == 'warehouse') ? 'selected' : ''; ?>>📦 Warehouse</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label><i class="fas fa-circle me-1"></i>Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="all">All Status</option>
                        <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label><i class="fas fa-sort me-1"></i>Sort By</label>
                    <select name="sort_by" class="form-select form-select-sm">
                        <option value="name_asc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                        <option value="name_desc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                        <option value="created_desc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'created_desc') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="created_asc" <?php echo (isset($_GET['sort_by']) && $_GET['sort_by'] == 'created_asc') ? 'selected' : ''; ?>>Oldest First</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn-filter w-100"><i class="fas fa-filter me-1"></i>Apply Filters</button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-12">
                    <a href="<?php echo BASE_URL; ?>/inventory/stores" class="btn-reset"><i class="fas fa-undo me-1"></i>Reset Filters</a>
                    <span class="text-muted ms-3" style="font-size: 12px;">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing <?php echo isset($totalRecords) ? number_format($totalRecords) : 0; ?> stores
                    </span>
                </div>
            </div>
        </form>
    </div>

    <!-- Stores Table -->
    <div class="table-responsive">
        <table class="store-table">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Store Name</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Phone</th>
                    <th>Manager</th>
                    <th>Status</th>
                    <th style="width: 160px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(isset($stores) && !empty($stores)): ?>
                    <?php $counter = isset($offset) ? $offset + 1 : 1; ?>
                    <?php foreach($stores as $store): ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($store['store_name']); ?></strong>
                            <?php if(!empty($store['created_at'])): ?>
                                <br><small class="text-muted">Added: <?php echo date('d M Y', strtotime($store['created_at'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="code-badge"><?php echo htmlspecialchars($store['store_code']); ?></span></td>
                        <td>
                            <?php 
                                $typeLabels = [
                                    'main_pharmacy' => ['label' => 'Main Pharmacy', 'class' => 'type-main', 'icon' => '🏪'],
                                    'branch' => ['label' => 'Branch', 'class' => 'type-branch', 'icon' => '🏬'],
                                    'clinic' => ['label' => 'Clinic', 'class' => 'type-clinic', 'icon' => '🏥'],
                                    'warehouse' => ['label' => 'Warehouse', 'class' => 'type-warehouse', 'icon' => '📦']
                                ];
                                $typeInfo = $typeLabels[$store['store_type']] ?? ['label' => ucfirst($store['store_type']), 'class' => 'type-other', 'icon' => '🏪'];
                            ?>
                            <span class="type-badge <?php echo $typeInfo['class']; ?>">
                                <?php echo $typeInfo['icon']; ?> <?php echo $typeInfo['label']; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                                $location = [];
                                if(!empty($store['city'])) $location[] = htmlspecialchars($store['city']);
                                if(!empty($store['address'])) $location[] = htmlspecialchars(substr($store['address'], 0, 30)) . (strlen($store['address']) > 30 ? '...' : '');
                                echo !empty($location) ? implode(', ', $location) : '<span class="text-muted">N/A</span>';
                            ?>
                        </td>
                        <td>
                            <?php if(!empty($store['phone'])): ?>
                                <a href="tel:<?php echo htmlspecialchars($store['phone']); ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($store['phone']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo !empty($store['manager_name']) ? htmlspecialchars($store['manager_name']) : '<span class="text-muted">N/A</span>'; ?></td>
                        <td>
                            <span class="status-badge <?php echo ($store['status'] ?? 'active') == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo isset($store['status']) ? ucfirst($store['status']) : 'Active'; ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <button class="btn-action btn-edit" onclick="editStore(<?php echo $store['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-action btn-stock" onclick="viewStoreStock(<?php echo $store['id']; ?>, '<?php echo htmlspecialchars(addslashes($store['store_name'])); ?>')">
                                <i class="fas fa-boxes"></i>
                            </button>
                            <button class="btn-action btn-delete" onclick="deleteStore(<?php echo $store['id']; ?>, '<?php echo htmlspecialchars(addslashes($store['store_name'])); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="fas fa-store empty-icon"></i>
                                <div class="empty-title">No Stores Found</div>
                                <div class="empty-text">Click "Add New Store" to create your first store location.</div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if(isset($totalPages) && $totalPages > 1): ?>
    <div class="pagination-container">
        <div class="page-info">
            Showing <?php echo isset($offset) ? $offset + 1 : 1; ?> - <?php echo isset($limit) ? min($offset + $limit, $totalRecords) : $totalRecords; ?> of <?php echo $totalRecords ?? 0; ?> stores
        </div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php if($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">&laquo; Prev</a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>
                <?php endif; ?>
                
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <?php if($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k != 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">Next &raquo;</a>
                    </li>
                <?php else: ?>
                    <li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Add Store Modal -->
<div class="modal fade" id="addStoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Store</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStoreForm">
                    <div class="mb-3">
                        <label class="form-label required-field">Store Name</label>
                        <input type="text" name="store_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Store Type</label>
                        <select name="store_type" class="form-select" required>
                            <option value="main_pharmacy">🏪 Main Pharmacy</option>
                            <option value="branch">🏬 Branch Pharmacy</option>
                            <option value="clinic">🏥 Clinic</option>
                            <option value="warehouse">📦 Warehouse</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Phone</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Manager Name</label>
                        <input type="text" name="manager_name" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitAddStore()">Save Store</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Store Modal -->
<div class="modal fade" id="editStoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Store</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editStoreForm">
                    <input type="hidden" name="id" id="edit_store_id">
                    <div class="mb-3">
                        <label class="form-label required-field">Store Name</label>
                        <input type="text" name="store_name" id="edit_store_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Store Type</label>
                        <select name="store_type" id="edit_store_type" class="form-select" required>
                            <option value="main_pharmacy">🏪 Main Pharmacy</option>
                            <option value="branch">🏬 Branch Pharmacy</option>
                            <option value="clinic">🏥 Clinic</option>
                            <option value="warehouse">📦 Warehouse</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" id="edit_city" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Manager Name</label>
                        <input type="text" name="manager_name" id="edit_manager_name" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitEditStore()">Update Store</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

// Auto-submit filter on change
$(document).ready(function() {
    $('.filter-section select').on('change', function() {
        $('#filterForm').submit();
    });
});

function openAddStoreModal() {
    $('#addStoreForm')[0].reset();
    $('#addStoreModal').modal('show');
}

function submitAddStore() {
    let formData = $('#addStoreForm').serialize();
    
    Swal.fire({
        title: 'Saving...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/inventory/add-store',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to add store', 'error');
        }
    });
}

function editStore(id) {
    $.ajax({
        url: BASE_URL + '/inventory/get-store/' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                $('#edit_store_id').val(response.store.id);
                $('#edit_store_name').val(response.store.store_name);
                $('#edit_store_type').val(response.store.store_type);
                $('#edit_address').val(response.store.address);
                $('#edit_city').val(response.store.city);
                $('#edit_phone').val(response.store.phone);
                $('#edit_email').val(response.store.email);
                $('#edit_manager_name').val(response.store.manager_name);
                $('#edit_status').val(response.store.status);
                $('#editStoreModal').modal('show');
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to load store details', 'error');
        }
    });
}

function submitEditStore() {
    let formData = $('#editStoreForm').serialize();
    
    Swal.fire({
        title: 'Updating...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/inventory/update-store',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to update store', 'error');
        }
    });
}

function deleteStore(id, storeName) {
    Swal.fire({
        title: 'Delete Store',
        text: `Are you sure you want to delete "${storeName}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/delete-store',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Deleted!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to delete store', 'error');
                }
            });
        }
    });
}

function viewStoreStock(id, storeName) {
    window.location.href = BASE_URL + '/inventory/store-stock/' + id;
}
</script>
</body>
</html>