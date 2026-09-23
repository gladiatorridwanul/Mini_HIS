<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total doctors count
$totalDoctors = isset($totalDoctors) ? $totalDoctors : 0;
$totalPages = ceil($totalDoctors / $limit);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Schedules - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .container-fluid { padding: 20px 25px; }
        
        /* Statistics Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        .stat-icon.bg-success { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-icon.bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-icon.bg-info { background: linear-gradient(135deg, #0ea5e9, #0284c7); }
        .stat-icon.bg-primary { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .stat-info { flex: 1; }
        .stat-number { font-size: 28px; font-weight: 700; color: #1e293b; line-height: 1.2; }
        .stat-label { font-size: 13px; color: #64748b; }
        
        /* Main Table */
        .schedule-main-table {
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
            border-collapse: collapse;
        }
        .schedule-main-table th {
            vertical-align: middle;
            padding: 12px;
        }
        .shift-header-info {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        /* Doctor Row */
        .doctor-row:hover {
            background-color: #f8fafc;
        }
        
        /* Shift Columns */
        .shift-column {
            vertical-align: top;
            padding: 8px !important;
            background: #fefefe;
        }
        .morning-column {
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
        }
        .evening-column {
            background: linear-gradient(135deg, #eff6ff, #dbeafe);
        }
        
        /* Days Container */
        .shift-days-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .day-schedule-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 8px;
            border-radius: 10px;
            transition: all 0.2s;
        }
        .day-schedule-item.active-day {
            background: white;
            border-left: 3px solid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .morning-column .day-schedule-item.active-day {
            border-left-color: #d97706;
        }
        .evening-column .day-schedule-item.active-day {
            border-left-color: #2563eb;
        }
        .day-schedule-item.inactive-day {
            opacity: 0.6;
        }
        .day-name {
            width: 38px;
            font-weight: 700;
            font-size: 12px;
        }
        .morning-column .day-name { color: #d97706; }
        .evening-column .day-name { color: #2563eb; }
        
        /* Shift Info */
        .shift-info {
            flex: 1;
        }
        .time {
            font-size: 11px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .time i {
            font-size: 10px;
            margin-right: 4px;
            color: #64748b;
        }
        .details {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .badge-slot, .badge-patients {
            font-size: 9px;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-slot {
            background: #f1f5f9;
            color: #475569;
        }
        .badge-patients {
            background: #e0e7ff;
            color: #4338ca;
        }
        .no-shift {
            font-size: 11px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .no-shift i {
            font-size: 10px;
        }
        
        /* No Schedule */
        .no-schedule {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .no-schedule i {
            font-size: 24px;
        }
        .no-schedule span {
            font-size: 12px;
        }
        
        /* Shift Summary */
        .shift-summary {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #e2e8f0;
            text-align: center;
        }
        .shift-summary .badge {
            font-size: 11px;
            padding: 4px 10px;
        }
        
        /* Action Row */
        .action-row {
            background-color: #f8fafc;
        }
        .action-row td {
            padding: 8px 12px !important;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .action-buttons .btn {
            padding: 4px 12px;
            font-size: 12px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
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
        
        /* Responsive */
        @media (max-width: 992px) {
            .day-name { width: 30px; font-size: 10px; }
            .time { font-size: 9px; }
            .badge-slot, .badge-patients { font-size: 8px; padding: 2px 6px; }
            .details { gap: 4px; }
        }
        
        @media (max-width: 768px) {
            .stat-card { padding: 15px; }
            .stat-icon { width: 45px; height: 45px; font-size: 20px; }
            .stat-number { font-size: 22px; }
            .shift-days-container { gap: 4px; }
            .day-schedule-item { padding: 4px 6px; }
            .action-buttons { flex-direction: column; gap: 5px; }
            .action-buttons .btn { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-calendar-alt me-2 text-primary"></i>Doctor Schedules</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/doctor/list">Doctors</a></li>
                    <li class="breadcrumb-item active">Schedules</li>
                </ol>
            </nav>
        </div>
        <a href="<?php echo BASE_URL; ?>/doctor/list" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Doctors
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number"><?php echo isset($doctors) ? count($doctors) : 0; ?></div>
                    <div class="stat-label">Total Doctors</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">
                        <?php 
                            $totalSessions = 0;
                            if(isset($doctors)) {
                                foreach($doctors as $d) {
                                    if(isset($d['sessions'])) $totalSessions += count($d['sessions']);
                                }
                            }
                            echo $totalSessions;
                        ?>
                    </div>
                    <div class="stat-label">Active Sessions</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-sun"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">
                        <?php 
                            $morningCount = 0;
                            if(isset($doctors)) {
                                foreach($doctors as $d) {
                                    if(isset($d['sessions'])) {
                                        foreach($d['sessions'] as $s) {
                                            if($s['session_type'] == 'morning') $morningCount++;
                                        }
                                    }
                                }
                            }
                            echo $morningCount;
                        ?>
                    </div>
                    <div class="stat-label">Morning Sessions</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-moon"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-number">
                        <?php 
                            $eveningCount = 0;
                            if(isset($doctors)) {
                                foreach($doctors as $d) {
                                    if(isset($d['sessions'])) {
                                        foreach($d['sessions'] as $s) {
                                            if($s['session_type'] == 'evening') $eveningCount++;
                                        }
                                    }
                                }
                            }
                            echo $eveningCount;
                        ?>
                    </div>
                    <div class="stat-label">Evening Sessions</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctors Schedule Table -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-table me-2"></i>Doctor Schedule List</h5>
            <span class="badge bg-light text-dark">
                Showing <?php echo isset($doctors) ? count($doctors) : 0; ?> of <?php echo $totalDoctors; ?> doctors
            </span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered schedule-main-table">
                    <thead>
                        <tr class="bg-dark text-white">
                            <th width="5%">#</th>
                            <th width="20%">Doctor Name</th>
                            <th width="15%">Specialization</th>
                            <th width="30%" class="text-center bg-warning text-dark">Morning Shift</th>
                            <th width="30%" class="text-center bg-primary">Evening Shift</th>
                        </tr>
                        <tr class="bg-light">
                            <th></th>
                            <th></th>
                            <th></th>
                            <th class="text-center">
                                <div class="shift-header-info">
                                    <i class="fas fa-sun text-warning"></i> Schedule Details
                                </div>
                            </th>
                            <th class="text-center">
                                <div class="shift-header-info">
                                    <i class="fas fa-moon text-primary"></i> Schedule Details
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(isset($doctors) && count($doctors) > 0): ?>
                            <?php $counter = $offset + 1; ?>
                            <?php foreach($doctors as $doctor): ?>
                                <?php
                                    // Organize sessions by day
                                    $morningSessions = [];
                                    $eveningSessions = [];
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    
                                    if(isset($doctor['sessions'])) {
                                        foreach($doctor['sessions'] as $s) {
                                            if($s['session_type'] == 'morning') {
                                                $morningSessions[$s['day_of_week']] = $s;
                                            } else {
                                                $eveningSessions[$s['day_of_week']] = $s;
                                            }
                                        }
                                    }
                                    
                                    // Check if any session exists
                                    $hasMorning = !empty($morningSessions);
                                    $hasEvening = !empty($eveningSessions);
                                    
                                    // Get consultation fee with fallback
                                    $consultationFee = isset($doctor['consultation_fee']) ? (float)$doctor['consultation_fee'] : 0;
                                ?>
                                <tr class="doctor-row">
                                    <td class="text-center"><?php echo $counter++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($doctor['title'] ?? 'Dr.') . ' ' . htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($doctor['bmdc_number'] ?? 'N/A'); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($doctor['specialization'] ?? 'General Medicine'); ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-money-bill-wave"></i> ৳ <?php echo number_format($consultationFee, 2); ?>
                                        </small>
                                    </td>
                                    
                                    <!-- Morning Shift Column -->
                                    <td class="shift-column morning-column">
                                        <?php if($hasMorning): ?>
                                            <div class="shift-days-container">
                                                <?php foreach($days as $day): ?>
                                                    <?php $session = $morningSessions[$day] ?? null; ?>
                                                    <div class="day-schedule-item <?php echo $session ? 'active-day' : 'inactive-day'; ?>">
                                                        <div class="day-name"><?php echo substr($day, 0, 3); ?></div>
                                                        <?php if($session): ?>
                                                            <div class="shift-info">
                                                                <div class="time">
                                                                    <i class="fas fa-clock"></i>
                                                                    <?php echo date('h:i A', strtotime($session['start_time'])); ?> - 
                                                                    <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                                                </div>
                                                                <div class="details">
                                                                    <span class="badge-slot">
                                                                        <i class="fas fa-stopwatch"></i> <?php echo $session['slot_duration']; ?> min
                                                                    </span>
                                                                    <span class="badge-patients">
                                                                        <i class="fas fa-users"></i> <?php echo $session['max_patients']; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="no-shift">
                                                                <i class="fas fa-minus-circle"></i> Off
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="shift-summary">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check-circle"></i> <?php echo count($morningSessions); ?> days active
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-schedule">
                                                <i class="fas fa-calendar-times"></i>
                                                <span>No morning schedule</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <!-- Evening Shift Column -->
                                    <td class="shift-column evening-column">
                                        <?php if($hasEvening): ?>
                                            <div class="shift-days-container">
                                                <?php foreach($days as $day): ?>
                                                    <?php $session = $eveningSessions[$day] ?? null; ?>
                                                    <div class="day-schedule-item <?php echo $session ? 'active-day' : 'inactive-day'; ?>">
                                                        <div class="day-name"><?php echo substr($day, 0, 3); ?></div>
                                                        <?php if($session): ?>
                                                            <div class="shift-info">
                                                                <div class="time">
                                                                    <i class="fas fa-clock"></i>
                                                                    <?php echo date('h:i A', strtotime($session['start_time'])); ?> - 
                                                                    <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                                                </div>
                                                                <div class="details">
                                                                    <span class="badge-slot">
                                                                        <i class="fas fa-stopwatch"></i> <?php echo $session['slot_duration']; ?> min
                                                                    </span>
                                                                    <span class="badge-patients">
                                                                        <i class="fas fa-users"></i> <?php echo $session['max_patients']; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="no-shift">
                                                                <i class="fas fa-minus-circle"></i> Off
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="shift-summary">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-check-circle"></i> <?php echo count($eveningSessions); ?> days active
                                                </span>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-schedule">
                                                <i class="fas fa-calendar-times"></i>
                                                <span>No evening schedule</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                
                                <!-- Action Row -->
                                <tr class="action-row">
                                    <td colspan="5">
                                        <div class="action-buttons">
                                            <a href="<?php echo BASE_URL; ?>/doctor/schedule/<?php echo $doctor['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit me-1"></i> Edit Schedule
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/doctor/view/<?php echo $doctor['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state">
                                        <i class="fas fa-user-md fa-4x text-muted mb-3"></i>
                                        <h4>No Doctors Found</h4>
                                        <p class="text-muted">Please add doctors first to manage their schedules.</p>
                                        <a href="<?php echo BASE_URL; ?>/doctor/create" class="btn btn-success mt-2">
                                            <i class="fas fa-plus me-2"></i>Add Doctor
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <nav aria-label="Doctor schedules pagination">
                        <ul class="pagination justify-content-center">
                            <!-- Previous Page -->
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            // Show first page if not in range
                            if($startPage > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                                if($startPage > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            // Page numbers
                            for($i = $startPage; $i <= $endPage; $i++) {
                                $active = ($i == $page) ? 'active' : '';
                                echo '<li class="page-item ' . $active . '">';
                                echo '<a class="page-link" href="?page=' . $i . '">' . $i . '</a>';
                                echo '</li>';
                            }
                            
                            // Show last page if not in range
                            if($endPage < $totalPages) {
                                if($endPage < $totalPages - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <!-- Next Page -->
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
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
                        (Total <?php echo $totalDoctors; ?> doctors)
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>