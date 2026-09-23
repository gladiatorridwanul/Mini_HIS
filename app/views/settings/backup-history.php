<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .backup-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .backup-header {
        background: #f8fafc;
        padding: 15px 20px;
        border-bottom: 1px solid #e5e7eb;
    }
    .backup-header h6 {
        margin: 0;
        font-weight: 600;
        color: #1f2937;
    }
    .backup-body {
        padding: 20px;
    }
    .filter-section {
        background: #f8fafc;
        padding: 15px 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }
    .filter-section label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 4px;
    }
    .filter-section .form-control {
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
    .btn-backup {
        background: #10b981;
        border: none;
        color: white;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 13px;
        transition: all 0.2s;
    }
    .btn-backup:hover {
        background: #059669;
        color: white;
    }
    .backup-table {
        width: 100%;
        border-collapse: collapse;
    }
    .backup-table th {
        padding: 10px 12px;
        background: #f8fafc;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }
    .backup-table td {
        padding: 10px 12px;
        font-size: 13px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .backup-table tr:hover td {
        background: #fafbfc;
    }
    .btn-action {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        margin-right: 4px;
        text-decoration: none;
        display: inline-block;
    }
    .btn-download { background: #3b82f6; color: white; }
    .btn-download:hover { background: #2563eb; color: white; }
    .btn-delete { background: #ef4444; color: white; }
    .btn-delete:hover { background: #dc2626; color: white; }
    .btn-download-link {
        background: #3b82f6;
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        text-decoration: none;
        display: inline-block;
    }
    .btn-download-link:hover {
        background: #2563eb;
        color: white;
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    .empty-state i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 15px;
    }
    .empty-state h5 {
        color: #475569;
        margin-bottom: 8px;
    }
    .empty-state p {
        color: #94a3b8;
        font-size: 14px;
    }
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
    .code-badge {
        background: #eef2ff;
        color: #4338ca;
        padding: 3px 8px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 11px;
        font-weight: 600;
    }
    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 1px solid #fecaca;
    }
    @media (max-width: 768px) {
        .backup-body { padding: 15px; }
        .backup-table th, .backup-table td { padding: 8px 6px; font-size: 11px; }
        .btn-action { padding: 3px 6px; font-size: 10px; }
    }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-database text-primary me-2"></i>Backup History</h2>
            <p class="text-muted mb-0" style="font-size: 14px;">View and manage database backups</p>
        </div>
        <div>
            <button class="btn-backup" onclick="createBackup()">
                <i class="fas fa-plus me-2"></i>Create New Backup
            </button>
            <a href="<?php echo BASE_URL; ?>/admin/settings" class="btn btn-secondary btn-sm ms-2">
                <i class="fas fa-arrow-left me-1"></i>Back to Settings
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="<?php echo BASE_URL; ?>/admin/settings/backup-history">
            <div class="row align-items-end">
                <div class="col-md-8">
                    <label><i class="fas fa-search me-1"></i>Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" 
                           placeholder="Search by backup name, type, or size..." 
                           value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn-filter"><i class="fas fa-filter me-1"></i>Search</button>
                    <a href="<?php echo BASE_URL; ?>/admin/settings/backup-history" class="btn-reset">
                        <i class="fas fa-undo me-1"></i>Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Backup Table -->
    <div class="backup-card">
        <div class="backup-header">
            <h6><i class="fas fa-list me-2"></i>Backup History</h6>
        </div>
        <div class="backup-body">
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php 
                        echo htmlspecialchars($_SESSION['error']); 
                        unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert-success" style="background:#d1fae5;color:#065f46;padding:12px 16px;border-radius:8px;margin-bottom:15px;border:1px solid #a7f3d0;">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php 
                        echo htmlspecialchars($_SESSION['success']); 
                        unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="backup-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Backup Name</th>
                            <th>Type</th>
                            <th>Size</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($backups) && !empty($backups)): ?>
                            <?php $counter = isset($offset) ? $offset + 1 : 1; ?>
                            <?php foreach($backups as $backup): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($backup['backup_name']); ?></strong>
                                    <br>
                                    <span class="code-badge"><?php echo htmlspecialchars(basename($backup['file_path'])); ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $backup['backup_type'] == 'database' ? 'primary' : 'secondary'; ?>">
                                        <?php echo ucfirst($backup['backup_type'] ?? 'database'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($backup['file_size'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($backup['created_by_name'] ?? 'System'); ?></td>
                                <td><?php echo date('d M Y, h:i A', strtotime($backup['created_at'])); ?></td>
                                <td style="text-align: center;">
                                    <a href="<?php echo BASE_URL; ?>/admin/settings/download-backup/<?php echo $backup['id']; ?>" 
                                       class="btn-download-link" title="Download">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <button class="btn-action btn-delete" onclick="deleteBackup(<?php echo $backup['id']; ?>)" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-database"></i>
                                        <h5>No Backups Found</h5>
                                        <p>Create your first database backup by clicking "Create New Backup"</p>
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
                    Showing <?php echo isset($offset) ? $offset + 1 : 1; ?> - <?php echo isset($limit) ? min($offset + $limit, $totalRecords) : $totalRecords; ?> of <?php echo $totalRecords ?? 0; ?> backups
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
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function createBackup() {
    Swal.fire({
        title: 'Creating Backup...',
        text: 'Please wait while we create a database backup',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/admin/settings/backup',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Backup Created!',
                    text: response.message,
                    confirmButtonColor: '#10b981'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Backup Failed',
                    text: response.message || 'Failed to create backup'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to create database backup'
            });
        }
    });
}

function deleteBackup(id) {
    Swal.fire({
        title: 'Delete Backup?',
        text: 'This action cannot be undone. The backup file will be permanently deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: BASE_URL + '/admin/settings/delete-backup',
                method: 'POST',
                data: { id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            confirmButtonColor: '#10b981'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to delete backup'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to delete backup'
                    });
                }
            });
        }
    });
}
</script>