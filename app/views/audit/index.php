<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .filter-card label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 4px;
    }
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 15px 20px;
        border: 1px solid #e5e7eb;
        text-align: center;
        transition: all 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .stat-card .stat-number {
        font-size: 28px;
        font-weight: 700;
    }
    .stat-card .stat-label {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }
    .stat-card .stat-icon {
        font-size: 24px;
        margin-bottom: 8px;
    }
    .log-details {
        max-width: 300px;
        overflow-x: auto;
        font-size: 12px;
    }
    .action-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    .action-create { background: #d1fae5; color: #065f46; }
    .action-update { background: #fef3c7; color: #92400e; }
    .action-delete { background: #fee2e2; color: #991b1b; }
    .action-login { background: #dbeafe; color: #1e40af; }
    .action-logout { background: #ede9fe; color: #5b21b6; }
    .action-view { background: #e0e7ff; color: #3730a3; }
    .action-other { background: #f3f4f6; color: #374151; }
    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
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
    .btn-export {
        background: #10b981;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 13px;
        transition: all 0.2s;
    }
    .btn-export:hover {
        background: #059669;
        color: white;
    }
    @media (max-width: 768px) {
        .stat-card .stat-number { font-size: 20px; }
        .filter-card .row > div { margin-bottom: 10px; }
    }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-history text-success me-2"></i>Audit Logs</h2>
            <p class="text-muted mb-0" style="font-size: 14px;">Track all system activities and user actions</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/admin/audit/login-history" class="btn btn-info btn-sm me-2">
                <i class="fas fa-sign-in-alt me-1"></i>Login History
            </a>
            <button onclick="exportLogs()" class="btn-export btn-sm">
                <i class="fas fa-file-excel me-1"></i>Export Logs
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon text-primary"><i class="fas fa-database"></i></div>
                <div class="stat-number text-primary"><?php echo number_format($stats['total'] ?? 0); ?></div>
                <div class="stat-label">Total Logs</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon text-success"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-number text-success"><?php echo number_format($stats['today'] ?? 0); ?></div>
                <div class="stat-label">Today's Activity</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon text-warning"><i class="fas fa-calendar-week"></i></div>
                <div class="stat-number text-warning"><?php echo number_format($stats['this_week'] ?? 0); ?></div>
                <div class="stat-label">This Week</div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon text-info"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-number text-info"><?php echo number_format($stats['this_month'] ?? 0); ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="row align-items-end">
            <div class="col-md-2 mb-2">
                <label><i class="fas fa-user me-1"></i>User</label>
                <select id="filterUser" class="form-select form-select-sm">
                    <option value="">All Users</option>
                    <?php foreach($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo ($filters['user_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label><i class="fas fa-cube me-1"></i>Module</label>
                <select id="filterModule" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    <?php foreach($modules as $module): ?>
                    <option value="<?php echo $module['module']; ?>" <?php echo ($filters['module'] ?? '') == $module['module'] ? 'selected' : ''; ?>>
                        <?php echo ucfirst($module['module']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label><i class="fas fa-bolt me-1"></i>Action</label>
                <select id="filterAction" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php foreach($actions as $action): ?>
                    <option value="<?php echo $action['action']; ?>" <?php echo ($filters['action'] ?? '') == $action['action'] ? 'selected' : ''; ?>>
                        <?php echo ucfirst($action['action']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label><i class="fas fa-calendar me-1"></i>From</label>
                <input type="date" id="dateFrom" class="form-control form-control-sm" value="<?php echo $filters['date_from'] ?? ''; ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label><i class="fas fa-calendar me-1"></i>To</label>
                <input type="date" id="dateTo" class="form-control form-control-sm" value="<?php echo $filters['date_to'] ?? ''; ?>">
            </div>
            <div class="col-md-2 mb-2">
                <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()">
                    <i class="fas fa-search me-1"></i>Apply Filters
                </button>
                <button class="btn btn-secondary btn-sm w-100 mt-1" onclick="resetFilters()">
                    <i class="fas fa-undo me-1"></i>Reset
                </button>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size: 12px;">
                        <i class="fas fa-info-circle me-1"></i>
                        Showing <?php echo isset($offset) ? $offset + 1 : 1; ?> - <?php echo isset($limit) ? min($offset + $limit, $totalRecords) : $totalRecords; ?> of <?php echo $totalRecords ?? 0; ?> logs
                    </span>
                    <div>
                        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search logs..." 
                               value="<?php echo $filters['search'] ?? ''; ?>" style="width: 200px; display: inline-block;">
                        <button class="btn btn-primary btn-sm" onclick="searchLogs()"><i class="fas fa-search"></i></button>
                        <button class="btn btn-danger btn-sm ms-2" onclick="clearLogs()">
                            <i class="fas fa-trash me-1"></i>Clear Old Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="card shadow">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Activity Logs</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 160px;">Date & Time</th>
                            <th>User</th>
                            <th style="width: 100px;">Role</th>
                            <th style="width: 100px;">Action</th>
                            <th style="width: 120px;">Module</th>
                            <th style="width: 120px;">IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($auditLogs)): ?>
                            <?php foreach($auditLogs as $log): ?>
                            <tr>
                                <td><small><?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?></small></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['user_name'] ?? 'System'); ?></strong>
                                    <?php if($log['user_id'] == 0): ?>
                                        <br><small class="text-muted">(System)</small>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo htmlspecialchars($log['user_role'] ?? 'system'); ?></small></td>
                                <td>
                                    <?php 
                                    $actionClass = 'action-other';
                                    if(strpos($log['action'], 'create') !== false) $actionClass = 'action-create';
                                    elseif(strpos($log['action'], 'update') !== false || strpos($log['action'], 'edit') !== false) $actionClass = 'action-update';
                                    elseif(strpos($log['action'], 'delete') !== false || strpos($log['action'], 'remove') !== false) $actionClass = 'action-delete';
                                    elseif(strpos($log['action'], 'login') !== false) $actionClass = 'action-login';
                                    elseif(strpos($log['action'], 'logout') !== false) $actionClass = 'action-logout';
                                    elseif(strpos($log['action'], 'view') !== false) $actionClass = 'action-view';
                                    ?>
                                    <span class="action-badge <?php echo $actionClass; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($log['module']); ?></td>
                                <td><small><?php echo $log['ip_address'] ?? '-'; ?></small></td>
                                <td>
                                    <div class="log-details">
                                        <?php if($log['description']): ?>
                                            <small><?php echo htmlspecialchars($log['description']); ?></small>
                                        <?php elseif($log['record_id']): ?>
                                            <small class="text-muted">Record ID: <?php echo $log['record_id']; ?></small>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No audit logs found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if(isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination-container border-top">
            <div class="page-info">
                Showing <?php echo isset($offset) ? $offset + 1 : 1; ?> - <?php echo isset($limit) ? min($offset + $limit, $totalRecords) : $totalRecords; ?> of <?php echo $totalRecords ?? 0; ?> logs
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php if($currentPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&<?php echo http_build_query(array_filter($filters)); ?>">&laquo; Prev</a>
                        </li>
                    <?php else: ?>
                        <li class="page-item disabled"><span class="page-link">&laquo; Prev</span></li>
                    <?php endif; ?>
                    
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($filters)); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if($currentPage < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&<?php echo http_build_query(array_filter($filters)); ?>">Next &raquo;</a>
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function applyFilters() {
    let params = new URLSearchParams();
    if($('#filterUser').val()) params.append('user_id', $('#filterUser').val());
    if($('#filterModule').val()) params.append('module', $('#filterModule').val());
    if($('#filterAction').val()) params.append('action', $('#filterAction').val());
    if($('#dateFrom').val()) params.append('date_from', $('#dateFrom').val());
    if($('#dateTo').val()) params.append('date_to', $('#dateTo').val());
    if($('#searchInput').val()) params.append('search', $('#searchInput').val());
    
    window.location.href = BASE_URL + '/admin/audit-logs?' + params.toString();
}

function resetFilters() {
    window.location.href = BASE_URL + '/admin/audit-logs';
}

function searchLogs() {
    applyFilters();
}

function clearLogs() {
    Swal.fire({
        title: 'Clear Old Logs',
        html: `
            <select id="daysSelect" class="swal2-select">
                <option value="30">Last 30 days</option>
                <option value="60">Last 60 days</option>
                <option value="90">Last 90 days</option>
                <option value="180">Last 180 days</option>
                <option value="365">Last 365 days</option>
            </select>
        `,
        showCancelButton: true,
        confirmButtonText: 'Clear Logs',
        confirmButtonColor: '#ef4444',
        preConfirm: () => {
            return { days: document.getElementById('daysSelect').value }
        }
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({
                title: 'Deleting Logs...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: BASE_URL + '/admin/audit/clear-logs',
                method: 'POST',
                data: { days: result.value.days },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to clear logs', 'error');
                }
            });
        }
    });
}

function exportLogs() {
    let params = new URLSearchParams();
    if($('#filterUser').val()) params.append('user_id', $('#filterUser').val());
    if($('#filterModule').val()) params.append('module', $('#filterModule').val());
    if($('#filterAction').val()) params.append('action', $('#filterAction').val());
    if($('#dateFrom').val()) params.append('date_from', $('#dateFrom').val());
    if($('#dateTo').val()) params.append('date_to', $('#dateTo').val());
    if($('#searchInput').val()) params.append('search', $('#searchInput').val());
    
    window.location.href = BASE_URL + '/admin/audit/export?format=csv&' + params.toString();
}

// Enter key for search
$('#searchInput').on('keypress', function(e) {
    if(e.which === 13) searchLogs();
});
</script>