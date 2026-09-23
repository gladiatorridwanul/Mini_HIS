<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 24px;
        border: 1px solid #e5e7eb;
    }
    .filter-card label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 4px;
    }
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
    @media (max-width: 768px) {
        .filter-card .row > div { margin-bottom: 10px; }
    }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-sign-in-alt text-success me-2"></i>Login History</h2>
            <p class="text-muted mb-0" style="font-size: 14px;">Track user login and logout activities</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/admin/audit-logs" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back to Audit Logs
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <div class="row align-items-end">
            <div class="col-md-3 mb-2">
                <label><i class="fas fa-search me-1"></i>Search</label>
                <input type="text" id="searchInput" class="form-control form-control-sm" 
                       placeholder="User or IP" value="<?php echo $search ?? ''; ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label><i class="fas fa-circle me-1"></i>Status</label>
                <select id="statusFilter" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="success" <?php echo ($status ?? '') == 'success' ? 'selected' : ''; ?>>Success</option>
                    <option value="failed" <?php echo ($status ?? '') == 'failed' ? 'selected' : ''; ?>>Failed</option>
                    <option value="locked" <?php echo ($status ?? '') == 'locked' ? 'selected' : ''; ?>>Locked</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label><i class="fas fa-calendar me-1"></i>From</label>
                <input type="date" id="dateFrom" class="form-control form-control-sm" value="<?php echo $dateFrom ?? ''; ?>">
            </div>
            <div class="col-md-2 mb-2">
                <label><i class="fas fa-calendar me-1"></i>To</label>
                <input type="date" id="dateTo" class="form-control form-control-sm" value="<?php echo $dateTo ?? ''; ?>">
            </div>
            <div class="col-md-3 mb-2">
                <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()">
                    <i class="fas fa-search me-1"></i>Apply Filters
                </button>
                <button class="btn btn-secondary btn-sm w-100 mt-1" onclick="resetFilters()">
                    <i class="fas fa-undo me-1"></i>Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Login History Table -->
    <div class="card shadow">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0"><i class="fas fa-history me-2"></i>Login Records</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>IP Address</th>
                            <th>Login Time</th>
                            <th>Logout Time</th>
                            <th>Duration</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($loginHistory)): ?>
                            <?php foreach($loginHistory as $login): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($login['first_name'] . ' ' . $login['last_name']); ?></strong>
                                </td>
                                <td><small><?php echo htmlspecialchars($login['email'] ?? '-'); ?></small></td>
                                <td><small><?php echo htmlspecialchars($login['ip_address'] ?? '-'); ?></small></td>
                                <td><?php echo date('d M Y H:i:s', strtotime($login['login_time'])); ?></td>
                                <td><?php echo $login['logout_time'] ? date('d M Y H:i:s', strtotime($login['logout_time'])) : '<span class="text-muted">-</span>'; ?></td>
                                <td>
                                    <?php 
                                    if($login['logout_time']) {
                                        $duration = strtotime($login['logout_time']) - strtotime($login['login_time']);
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        if($hours > 0) {
                                            echo $hours . 'h ' . $minutes . 'm';
                                        } else {
                                            echo $minutes . ' mins';
                                        }
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $login['status'] == 'success' ? 'success' : ($login['status'] == 'failed' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($login['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No login records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if(isset($totalPages) && $totalPages > 1): ?>
        <div class="pagination-container border-top">
            <div class="page-info">
                Showing <?php echo isset($offset) ? $offset + 1 : 1; ?> - <?php echo isset($limit) ? min($offset + $limit, $totalRecords) : $totalRecords; ?> of <?php echo $totalRecords ?? 0; ?> records
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function applyFilters() {
    let params = new URLSearchParams();
    if($('#searchInput').val()) params.append('search', $('#searchInput').val());
    if($('#statusFilter').val()) params.append('status', $('#statusFilter').val());
    if($('#dateFrom').val()) params.append('date_from', $('#dateFrom').val());
    if($('#dateTo').val()) params.append('date_to', $('#dateTo').val());
    
    window.location.href = BASE_URL + '/admin/audit/login-history?' + params.toString();
}

function resetFilters() {
    window.location.href = BASE_URL + '/admin/audit/login-history';
}

// Enter key for search
$('#searchInput').on('keypress', function(e) {
    if(e.which === 13) applyFilters();
});
</script>