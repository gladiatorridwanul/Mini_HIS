<?php
/**
 * Staff Attendance Management
 * Complete system with login/logout tracking and manual entry
 */

// Ensure variables are defined
$date = $date ?? date('Y-m-d');
$month = $month ?? date('Y-m');
$attendances = $attendances ?? [];
$filterType = $filterType ?? 'daily';
$today = date('Y-m-d');

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$totalRecords = isset($totalRecords) ? $totalRecords : 0;
$totalPages = ceil($totalRecords / $limit);
?>

<style>
    .filter-section {
        background: #fff;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .filter-section .form-control,
    .filter-section .form-select {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        font-size: 14px;
    }
    .filter-section .btn {
        border-radius: 8px;
        padding: 8px 24px;
    }
    .stat-card {
        border-radius: 12px;
        padding: 18px 20px;
        text-align: center;
        color: white;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
    }
    .stat-card h3 {
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .stat-card small {
        opacity: 0.85;
        font-size: 12px;
    }
    .table th {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        background: #f8fafc;
    }
    .table td {
        vertical-align: middle;
        font-size: 13px;
    }
    .badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 11px;
    }
    .avatar-sm {
        width: 32px;
        height: 32px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: white;
    }
    .status-present { background: #10b981; color: white; }
    .status-absent { background: #ef4444; color: white; }
    .status-late { background: #f59e0b; color: white; }
    .status-leave { background: #8b5cf6; color: white; }
    .status-half-day { background: #f97316; color: white; }
    
    .modal-content {
        border-radius: 16px;
        border: none;
    }
    .modal-header {
        border-bottom: 1px solid #e2e8f0;
        padding: 20px 24px;
    }
    .modal-body {
        padding: 24px;
    }
    .modal-footer {
        border-top: 1px solid #e2e8f0;
        padding: 16px 24px;
    }
    .time-input-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .time-input-group input[type="time"] {
        flex: 1;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        padding: 8px 12px;
    }
    .btn-auto {
        background: #e2e8f0;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 12px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-auto:hover {
        background: #cbd5e1;
    }
    .attendance-log {
        max-height: 300px;
        overflow-y: auto;
    }
    .attendance-log .log-item {
        padding: 8px 12px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    .attendance-log .log-item:last-child {
        border-bottom: none;
    }
    .log-time {
        font-weight: 600;
        color: #0f172a;
    }
    .log-status {
        font-size: 11px;
        padding: 2px 10px;
        border-radius: 12px;
    }
    
    /* Action buttons in one line - horizontal */
    .action-buttons {
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }
    .action-buttons .btn {
        padding: 4px 6px;
        font-size: 12px;
        line-height: 1;
        border-radius: 6px;
        min-width: 28px;
        height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .action-buttons .btn i {
        font-size: 12px;
    }
    .action-buttons .btn-sm {
        padding: 3px 5px;
        font-size: 11px;
        min-width: 24px;
        height: 24px;
    }
    
    /* Table action column width */
    .table .action-column {
        width: 120px;
        min-width: 120px;
        max-width: 120px;
    }
    
    /* Pagination */
    .pagination .page-link {
        padding: 6px 12px;
        font-size: 14px;
    }
    .pagination .active .page-link {
        background-color: #10b981;
        border-color: #10b981;
        color: white;
    }
    .pagination .page-link:hover {
        background-color: #f1f5f9;
    }
    .pagination .active .page-link:hover {
        background-color: #059669;
        border-color: #059669;
        color: white;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .action-buttons .btn {
            padding: 3px 4px;
            font-size: 10px;
            min-width: 22px;
            height: 22px;
        }
        .action-buttons .btn i {
            font-size: 10px;
        }
        .table .action-column {
            width: 90px;
            min-width: 90px;
            max-width: 90px;
        }
        .pagination .page-link {
            padding: 4px 8px;
            font-size: 12px;
        }
    }
</style>

<div class="container-fluid px-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">
            <i class="fas fa-clock text-primary me-2"></i> Staff Attendance Management
        </h4>
        <div>
            <button onclick="openManualEntry()" class="btn btn-primary">
                <i class="fas fa-pen me-1"></i> Manual Entry
            </button>
            <button onclick="refreshData()" class="btn btn-outline-secondary">
                <i class="fas fa-sync me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" action="<?php echo BASE_URL; ?>/admin/users/attendance" class="row g-3 align-items-end" id="filterForm">
            <input type="hidden" name="view" value="filtered">
            
            <div class="col-md-2">
                <label class="form-label fw-semibold small text-muted">Filter Type</label>
                <select name="filter_type" class="form-select" id="filterType">
                    <option value="daily" <?php echo ($filterType == 'daily') ? 'selected' : ''; ?>>Daily</option>
                    <option value="monthly" <?php echo ($filterType == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                    <option value="employee" <?php echo ($filterType == 'employee') ? 'selected' : ''; ?>>Employee</option>
                </select>
            </div>

            <div class="col-md-2" id="dateFilter">
                <label class="form-label fw-semibold small text-muted">Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
            </div>

            <div class="col-md-2" id="monthFilter" style="display: none;">
                <label class="form-label fw-semibold small text-muted">Month</label>
                <input type="month" name="month" class="form-control" value="<?php echo $month; ?>">
            </div>

            <div class="col-md-3" id="employeeFilter" style="display: none;">
                <label class="form-label fw-semibold small text-muted">Employee</label>
                <select name="employee_id" class="form-select">
                    <option value="0">All Employees</option>
                    <?php 
                    $employeeList = $employeeList ?? [];
                    foreach ($employeeList as $emp): 
                    ?>
                    <option value="<?php echo $emp['id']; ?>" <?php echo (isset($_GET['employee_id']) && $_GET['employee_id'] == $emp['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold small text-muted">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <?php 
    $totalPresent = 0;
    $totalAbsent = 0;
    $totalLeave = 0;
    $totalLate = 0;
    $totalHalfDay = 0;
    $totalEmployees = count($attendances);
    $totalCheckedIn = 0;
    
    foreach ($attendances as $att) {
        $status = $att['att_status'] ?? '';
        if ($status == 'present') $totalPresent++;
        elseif ($status == 'absent') $totalAbsent++;
        elseif ($status == 'leave') $totalLeave++;
        elseif ($status == 'late') $totalLate++;
        elseif ($status == 'half_day') $totalHalfDay++;
        
        if (!empty($att['check_in'])) $totalCheckedIn++;
    }
    ?>
    
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="stat-card bg-primary" onclick="filterByStatus('all')">
                <h3><?php echo $totalEmployees; ?></h3>
                <small>Total Staff</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card bg-success" onclick="filterByStatus('present')">
                <h3><?php echo $totalPresent; ?></h3>
                <small>Present</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card bg-danger" onclick="filterByStatus('absent')">
                <h3><?php echo $totalAbsent; ?></h3>
                <small>Absent</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card" style="background: #f59e0b;" onclick="filterByStatus('late')">
                <h3><?php echo $totalLate; ?></h3>
                <small>Late</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card" style="background: #8b5cf6;" onclick="filterByStatus('leave')">
                <h3><?php echo $totalLeave; ?></h3>
                <small>Leave</small>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card" style="background: #0ea5e9;">
                <h3><?php echo $totalCheckedIn; ?></h3>
                <small>Checked In Today</small>
            </div>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <h5 class="mb-0 fw-bold">
                <i class="fas fa-users text-primary me-2"></i> Attendance Details
                <span class="badge bg-primary ms-2"><?php echo date('l, F j, Y', strtotime($date)); ?></span>
            </h5>
            <div>
                <span class="text-muted me-2">
                    Showing <?php echo count($attendances); ?> of <?php echo $totalRecords; ?> employees
                </span>
                <button onclick="saveAllAttendance()" class="btn btn-success btn-sm">
                    <i class="fas fa-save"></i> Save All
                </button>
                <button onclick="exportAttendance()" class="btn btn-info btn-sm text-white">
                    <i class="fas fa-file-export"></i> Export
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="attendanceTable">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Employee</th>
                            <th>Role</th>
                            <th style="width: 150px;">Check In</th>
                            <th style="width: 150px;">Check Out</th>
                            <th style="width: 150px;">Status</th>
                            <th style="width: 80px;">Hours</th>
                            <th class="action-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendances)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="fas fa-users fa-3x d-block mb-3 text-muted"></i>
                                <p class="mb-0">No attendance records found for the selected criteria.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php $i = $offset + 1; foreach ($attendances as $att): ?>
                        <tr data-status="<?php echo $att['att_status'] ?? 'none'; ?>">
                            <td><?php echo $i++; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary me-2">
                                        <?php echo strtoupper(substr($att['first_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($att['first_name'] . ' ' . $att['last_name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($att['employee_id'] ?? ''); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($att['role_name'] ?? 'N/A'); ?></td>
                            <td>
                                <input type="time" class="form-control form-control-sm check-in" 
                                       data-userid="<?php echo $att['id']; ?>" 
                                       value="<?php echo $att['check_in'] ? date('H:i', strtotime($att['check_in'])) : ''; ?>"
                                       style="width: 130px;">
                                <?php if (empty($att['check_in'])): ?>
                                <small class="text-muted d-block">Not set</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="time" class="form-control form-control-sm check-out" 
                                       data-userid="<?php echo $att['id']; ?>" 
                                       value="<?php echo $att['check_out'] ? date('H:i', strtotime($att['check_out'])) : ''; ?>"
                                       style="width: 130px;">
                                <?php if (empty($att['check_out'])): ?>
                                <small class="text-muted d-block">Not set</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <select class="form-select form-select-sm status-select" data-userid="<?php echo $att['id']; ?>" style="width: 130px;">
                                    <option value="present" <?php echo ($att['att_status'] ?? '') == 'present' ? 'selected' : ''; ?>>Present</option>
                                    <option value="absent" <?php echo ($att['att_status'] ?? '') == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                    <option value="late" <?php echo ($att['att_status'] ?? '') == 'late' ? 'selected' : ''; ?>>Late</option>
                                    <option value="half_day" <?php echo ($att['att_status'] ?? '') == 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                                    <option value="leave" <?php echo ($att['att_status'] ?? '') == 'leave' ? 'selected' : ''; ?>>Leave</option>
                                </select>
                            </td>
                            <td>
                                <?php 
                                $hours = $att['working_hours'] ?? 0;
                                echo $hours > 0 ? number_format($hours, 2) . 'h' : '-';
                                ?>
                            </td>
                            <td class="action-column">
                                <div class="action-buttons">
                                    <button onclick="saveAttendance(<?php echo $att['id']; ?>)" 
                                            class="btn btn-sm btn-outline-primary save-btn" 
                                            id="save-<?php echo $att['id']; ?>"
                                            title="Save this record">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    <button onclick="openManualEdit(<?php echo $att['id']; ?>, '<?php echo addslashes($att['first_name'] . ' ' . $att['last_name']); ?>')" 
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Manual Edit">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <button onclick="viewHistory(<?php echo $att['id']; ?>)" 
                                            class="btn btn-sm btn-outline-info"
                                            title="View History">
                                        <i class="fas fa-history"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div class="row mt-3">
        <div class="col-12">
            <nav aria-label="Attendance pagination">
                <ul class="pagination justify-content-center">
                    <!-- Previous Page -->
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&date=<?php echo urlencode($date); ?>&month=<?php echo urlencode($month); ?>&filter_type=<?php echo urlencode($filterType); ?><?php echo isset($_GET['employee_id']) ? '&employee_id=' . $_GET['employee_id'] : ''; ?>" 
                           aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    // Build query string
                    $queryParams = '&date=' . urlencode($date) . '&month=' . urlencode($month) . '&filter_type=' . urlencode($filterType);
                    if(isset($_GET['employee_id'])) {
                        $queryParams .= '&employee_id=' . $_GET['employee_id'];
                    }
                    
                    // Show first page if not in range
                    if($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1' . $queryParams . '">1</a></li>';
                        if($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Page numbers
                    for($i = $startPage; $i <= $endPage; $i++) {
                        $active = ($i == $page) ? 'active' : '';
                        echo '<li class="page-item ' . $active . '">';
                        echo '<a class="page-link" href="?page=' . $i . $queryParams . '">' . $i . '</a>';
                        echo '</li>';
                    }
                    
                    // Show last page if not in range
                    if($endPage < $totalPages) {
                        if($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . $queryParams . '">' . $totalPages . '</a></li>';
                    }
                    ?>
                    
                    <!-- Next Page -->
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $queryParams; ?>" 
                           aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Page Info -->
    <div class="row mt-2">
        <div class="col-12 text-center">
            <small class="text-muted">
                Page <?php echo $page; ?> of <?php echo $totalPages; ?> 
                (Total <?php echo $totalRecords; ?> employees)
            </small>
        </div>
    </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualEntryModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-pen text-primary me-2"></i> Manual Attendance Entry
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="manualEntryForm">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Employee</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php foreach ($employeeList as $emp): ?>
                            <option value="<?php echo $emp['id']; ?>">
                                <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="date" class="form-control" value="<?php echo $today; ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Check In Time</label>
                            <div class="time-input-group">
                                <input type="time" name="check_in" class="form-control">
                                <button type="button" class="btn-auto" onclick="setCurrentTime(this, 'check_in')">
                                    <i class="fas fa-clock"></i> Now
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Check Out Time</label>
                            <div class="time-input-group">
                                <input type="time" name="check_out" class="form-control">
                                <button type="button" class="btn-auto" onclick="setCurrentTime(this, 'check_out')">
                                    <i class="fas fa-clock"></i> Now
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                            <option value="leave">Leave</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveManualEntry()">
                    <i class="fas fa-save me-1"></i> Save Entry
                </button>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-history text-primary me-2"></i> Attendance History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="historyContent">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2">Loading history...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const today = '<?php echo $today; ?>';

// Show/hide filters based on filter type
document.addEventListener('DOMContentLoaded', function() {
    const filterType = document.querySelector('#filterType');
    const dateFilter = document.getElementById('dateFilter');
    const monthFilter = document.getElementById('monthFilter');
    const employeeFilter = document.getElementById('employeeFilter');
    
    function toggleFilters() {
        const type = filterType.value;
        dateFilter.style.display = (type == 'daily') ? 'block' : 'none';
        monthFilter.style.display = (type == 'monthly' || type == 'employee') ? 'block' : 'none';
        employeeFilter.style.display = (type == 'employee') ? 'block' : 'none';
    }
    
    filterType.addEventListener('change', toggleFilters);
    toggleFilters();
});

// Filter by status
function filterByStatus(status) {
    const rows = document.querySelectorAll('#attendanceTable tbody tr');
    rows.forEach(row => {
        if (status == 'all') {
            row.style.display = '';
        } else {
            const rowStatus = row.dataset.status || '';
            row.style.display = (rowStatus == status) ? '' : 'none';
        }
    });
}

// Set current time
function setCurrentTime(btn, field) {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const timeStr = hours + ':' + minutes;
    
    const input = btn.closest('.time-input-group').querySelector('input[type="time"]');
    if (input) {
        input.value = timeStr;
    }
}

// Open manual entry modal
function openManualEntry() {
    const modal = new bootstrap.Modal(document.getElementById('manualEntryModal'));
    modal.show();
}

// Open manual edit for specific user
function openManualEdit(userId, userName) {
    const modal = new bootstrap.Modal(document.getElementById('manualEntryModal'));
    const form = document.getElementById('manualEntryForm');
    
    // Pre-select the user
    const select = form.querySelector('select[name="user_id"]');
    for (let option of select.options) {
        if (option.value == userId) {
            option.selected = true;
            break;
        }
    }
    
    // Set date to today
    form.querySelector('input[name="date"]').value = today;
    
    // Clear time fields
    form.querySelector('input[name="check_in"]').value = '';
    form.querySelector('input[name="check_out"]').value = '';
    
    modal.show();
}

// Save manual entry
function saveManualEntry() {
    const form = document.getElementById('manualEntryForm');
    const formData = new FormData(form);
    const btn = document.querySelector('#manualEntryModal .btn-primary');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    
    fetch(BASE_URL + '/api/attendance/manual-entry', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Attendance entry saved successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Save Entry';
    })
    .catch(error => {
        alert('Error saving entry: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Save Entry';
    });
}

// View attendance history
function viewHistory(userId) {
    const modal = new bootstrap.Modal(document.getElementById('historyModal'));
    const content = document.getElementById('historyContent');
    
    content.innerHTML = `
        <div class="text-center text-muted py-4">
            <i class="fas fa-spinner fa-spin fa-2x"></i>
            <p class="mt-2">Loading history...</p>
        </div>
    `;
    
    modal.show();
    
    fetch(BASE_URL + '/api/attendance/history?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.history.length > 0) {
                let html = `
                    <div class="attendance-log">
                        <div class="fw-semibold mb-2">Last 30 days</div>
                `;
                data.history.forEach(item => {
                    const statusClass = 'status-' + (item.status || 'absent');
                    html += `
                        <div class="log-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="log-time">${item.attendance_date}</span>
                                <span class="badge ${statusClass} ms-2">${item.status || 'Not marked'}</span>
                            </div>
                            <div>
                                <span class="text-muted">In: ${item.check_in || '-'}</span>
                                <span class="text-muted ms-2">Out: ${item.check_out || '-'}</span>
                                <span class="ms-2">${item.working_hours ? item.working_hours + 'h' : '-'}</span>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                content.innerHTML = html;
            } else {
                content.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-calendar-alt fa-2x d-block mb-2"></i>
                        <p>No attendance history found for this employee.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-circle fa-2x d-block mb-2"></i>
                    <p>Error loading history: ${error.message}</p>
                </div>
            `;
        });
}

// Save single attendance
function saveAttendance(userId) {
    const row = document.querySelector(`#save-${userId}`).closest('tr');
    const checkIn = row.querySelector('.check-in').value;
    const checkOut = row.querySelector('.check-out').value;
    const status = row.querySelector('.status-select').value;
    const date = '<?php echo $date; ?>';
    const btn = document.getElementById(`save-${userId}`);
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    
    fetch(BASE_URL + '/api/attendance/save', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `user_id=${userId}&date=${date}&check_in=${checkIn}&check_out=${checkOut}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            btn.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-save"></i>';
                btn.disabled = false;
            }, 1500);
        } else {
            alert('Error: ' + data.message);
            btn.innerHTML = '<i class="fas fa-save"></i>';
            btn.disabled = false;
        }
    })
    .catch(error => {
        alert('Error saving attendance');
        btn.innerHTML = '<i class="fas fa-save"></i>';
        btn.disabled = false;
    });
}

// Save all attendance
function saveAllAttendance() {
    const date = '<?php echo $date; ?>';
    const users = [];
    
    document.querySelectorAll('.save-btn').forEach(btn => {
        const row = btn.closest('tr');
        const userId = parseInt(btn.id.replace('save-', ''));
        const checkIn = row.querySelector('.check-in').value;
        const checkOut = row.querySelector('.check-out').value;
        const status = row.querySelector('.status-select').value;
        
        users.push({ user_id: userId, check_in: checkIn, check_out: checkOut, status: status });
    });
    
    if(users.length === 0) {
        alert('No users to save');
        return;
    }
    
    if(!confirm(`Save attendance for ${users.length} employees?`)) return;
    
    const btn = document.querySelector('.btn-success');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    
    fetch(BASE_URL + '/api/attendance/save-all', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `date=${date}&users=${JSON.stringify(users)}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
        btn.innerHTML = '<i class="fas fa-save"></i> Save All';
        btn.disabled = false;
    })
    .catch(error => {
        alert('Error saving attendance');
        btn.innerHTML = '<i class="fas fa-save"></i> Save All';
        btn.disabled = false;
    });
}

// Export attendance
function exportAttendance() {
    let date = '<?php echo $date; ?>';
    let month = '<?php echo $month; ?>';
    let filterType = '<?php echo $filterType; ?>';
    window.location.href = BASE_URL + '/api/attendance/export?date=' + date + '&month=' + month + '&type=' + filterType;
}

// Refresh data
function refreshData() {
    location.reload();
}
</script>