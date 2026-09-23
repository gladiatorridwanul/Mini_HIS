<?php
// Data from controller: $attendances, $departments, $date, $selectedDepartment
$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Attendance - UniDia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <style>
        .present-badge { background-color: #28a745; color: white; padding: 5px 10px; border-radius: 5px; }
        .absent-badge { background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 5px; }
        .late-badge { background-color: #ffc107; color: black; padding: 5px 10px; border-radius: 5px; }
        .halfday-badge { background-color: #fd7e14; color: white; padding: 5px 10px; border-radius: 5px; }
        .leave-badge { background-color: #6c757d; color: white; padding: 5px 10px; border-radius: 5px; }
        .attendance-card { transition: transform 0.2s; }
        .attendance-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse" style="min-height: 100vh;">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">UniDia</h4>
                        <small class="text-muted">Hospital Management System</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link text-white" href="/unidia/public/admin/dashboard"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="/unidia/public/admin/users"><i class="fas fa-users me-2"></i> Users</a></li>
                        <li class="nav-item"><a class="nav-link active text-white bg-primary" href="/unidia/public/admin/users/attendance"><i class="fas fa-clock me-2"></i> Attendance</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="/unidia/public/admin/users/payroll"><i class="fas fa-money-bill me-2"></i> Payroll</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="/unidia/public/patient/list"><i class="fas fa-procedures me-2"></i> Patients</a></li>
                        <li class="nav-item mt-4"><a class="nav-link text-danger" href="/unidia/public/logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-clock me-2"></i> Staff Attendance Management</h1>
                    <div>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkAttendanceModal">
                            <i class="fas fa-upload me-1"></i> Bulk Mark Attendance
                        </button>
                        <button type="button" class="btn btn-info" onclick="exportAttendance()">
                            <i class="fas fa-file-excel me-1"></i> Export Report
                        </button>
                        <button type="button" class="btn btn-primary" onclick="printAttendance()">
                            <i class="fas fa-print me-1"></i> Print
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white attendance-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Total Staff</h6>
                                        <h2><?php echo count($attendances); ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white attendance-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Present Today</h6>
                                        <h2 id="presentCount">0</h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white attendance-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Late/Leave</h6>
                                        <h2 id="lateCount">0</h2>
                                    </div>
                                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white attendance-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6>Absent</h6>
                                        <h2 id="absentCount">0</h2>
                                    </div>
                                    <i class="fas fa-user-slash fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Date Navigation and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-calendar me-1"></i> Select Date</label>
                                <div class="input-group">
                                    <input type="date" id="attendanceDate" class="form-control" value="<?php echo $date; ?>">
                                    <button class="btn btn-primary" onclick="changeDate()">
                                        <i class="fas fa-search"></i> Go
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><i class="fas fa-filter me-1"></i> Department</label>
                                <select id="deptFilter" class="form-select" onchange="filterByDepartment()">
                                    <option value="">All Departments</option>
                                    <?php foreach($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo ($selectedDepartment ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label"><i class="fas fa-tag me-1"></i> Status Filter</label>
                                <select id="statusFilter" class="form-select" onchange="filterByStatus()">
                                    <option value="">All Status</option>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                    <option value="half_day">Half Day</option>
                                    <option value="leave">Leave</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label"><i class="fas fa-search me-1"></i> Search</label>
                                <input type="text" id="searchInput" class="form-control" placeholder="Search staff..." onkeyup="searchStaff()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-success" onclick="markAllPresent()">
                                <i class="fas fa-check-circle"></i> Mark All Present
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="markAllAbsent()">
                                <i class="fas fa-times-circle"></i> Mark All Absent
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="clearAll()">
                                <i class="fas fa-eraser"></i> Clear All
                            </button>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary float-end" onclick="refreshTable()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-table me-2"></i> Staff Attendance for <?php echo date('F j, Y', strtotime($date)); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="attendanceTable" class="table table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                                        <th>Employee ID</th>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Status</th>
                                        <th>Working Hours</th>
                                        <th>Overtime</th>
                                        <th width="180">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($attendances as $attendance): ?>
                                        <tr data-user-id="<?php echo $attendance['id']; ?>" data-department="<?php echo $attendance['department_id'] ?? ''; ?>">
                                            <td><input type="checkbox" class="userCheckbox" value="<?php echo $attendance['id']; ?>"></td>
                                            <td><?php echo htmlspecialchars($attendance['employee_id'] ?? 'N/A'); ?></td>
                                            <td class="text-center">
                                                <?php if(!empty($attendance['profile_photo'])): ?>
                                                    <img src="/unidia/public/uploads/profiles/<?php echo $attendance['profile_photo']; ?>" width="35" height="35" class="rounded-circle">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded-circle d-inline-flex align-items-center justify-content-center" style="width:35px;height:35px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($attendance['first_name'] . ' ' . $attendance['last_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $attendance['role_slug'] == 'doctor' ? 'info' : 
                                                        ($attendance['role_slug'] == 'admin' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo htmlspecialchars($attendance['role_name'] ?? 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($attendance['department_name'] ?? 'N/A'); ?></td>
                                            <td>
                                                <input type="time" class="form-control form-control-sm checkIn" value="<?php echo $attendance['check_in'] ?? ''; ?>" style="width:100px;" onchange="updateAttendance(<?php echo $attendance['id']; ?>, 'check_in', this.value)">
                                            </td>
                                            <td>
                                                <input type="time" class="form-control form-control-sm checkOut" value="<?php echo $attendance['check_out'] ?? ''; ?>" style="width:100px;" onchange="updateAttendance(<?php echo $attendance['id']; ?>, 'check_out', this.value)">
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm statusSelect" style="width:110px;" onchange="updateAttendance(<?php echo $attendance['id']; ?>, 'status', this.value)">
                                                    <option value="present" <?php echo ($attendance['attendance_status'] ?? '') == 'present' ? 'selected' : ''; ?>>Present</option>
                                                    <option value="absent" <?php echo ($attendance['attendance_status'] ?? '') == 'absent' ? 'selected' : ''; ?>>Absent</option>
                                                    <option value="late" <?php echo ($attendance['attendance_status'] ?? '') == 'late' ? 'selected' : ''; ?>>Late</option>
                                                    <option value="half_day" <?php echo ($attendance['attendance_status'] ?? '') == 'half_day' ? 'selected' : ''; ?>>Half Day</option>
                                                    <option value="leave" <?php echo ($attendance['attendance_status'] ?? '') == 'leave' ? 'selected' : ''; ?>>Leave</option>
                                                </select>
                                            </td>
                                            <td class="workingHours"><?php echo $attendance['working_hours'] ?? '-'; ?></td>
                                            <td class="overtime"><?php echo $attendance['overtime_hours'] ?? '0'; ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="markCheckIn(<?php echo $attendance['id']; ?>)">
                                                    <i class="fas fa-sign-in-alt"></i> Check In
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="markCheckOut(<?php echo $attendance['id']; ?>)">
                                                    <i class="fas fa-sign-out-alt"></i> Check Out
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-pie me-2"></i> Attendance Summary</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="attendanceChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line me-2"></i> Monthly Trend</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="trendChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bulk Attendance Modal -->
    <div class="modal fade" id="bulkAttendanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i> Bulk Mark Attendance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" id="bulkDate" class="form-control" value="<?php echo $date; ?>">
                    </div>
                    <div class="mb-3">
                        <label>Department (Optional)</label>
                        <select id="bulkDepartment" class="form-select">
                            <option value="">All Departments</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select id="bulkStatus" class="form-select">
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                            <option value="leave">Leave</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Default Check In Time</label>
                        <input type="time" id="bulkCheckIn" class="form-control" value="09:00">
                    </div>
                    <div class="mb-3">
                        <label>Default Check Out Time</label>
                        <input type="time" id="bulkCheckOut" class="form-control" value="17:00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="applyBulkAttendance()">Apply to Selected</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Attendance Notes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <textarea id="attendanceNotes" class="form-control" rows="3" placeholder="Enter notes for this attendance record..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveNotes()">Save Notes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        let currentUserId = null;
        let attendanceChart = null;
        let trendChart = null;
        
        // Initialize DataTable
        $(document).ready(function() {
            $('#attendanceTable').DataTable({
                pageLength: 25,
                order: [[3, 'asc']],
                searching: false,
                paging: true,
                info: true
            });
            updateStats();
            loadCharts();
        });
        
        // Update statistics
        function updateStats() {
            let present = 0, absent = 0, late = 0, half = 0, leave = 0;
            $('.statusSelect').each(function() {
                let val = $(this).val();
                if(val == 'present') present++;
                else if(val == 'absent') absent++;
                else if(val == 'late') late++;
                else if(val == 'half_day') half++;
                else if(val == 'leave') leave++;
            });
            $('#presentCount').text(present);
            $('#absentCount').text(absent);
            $('#lateCount').text(late + half + leave);
        }
        
        // Update attendance via AJAX
        function updateAttendance(userId, field, value) {
            let date = $('#attendanceDate').val();
            $.ajax({
                url: '/unidia/public/api/attendance/update',
                method: 'POST',
                data: {
                    user_id: userId,
                    date: date,
                    field: field,
                    value: value
                },
                success: function(response) {
                    if(response.success) {
                        if(field == 'check_in' || field == 'check_out') {
                            calculateWorkingHours(userId);
                        }
                        updateStats();
                        showNotification('Attendance updated successfully', 'success');
                    } else {
                        showNotification(response.message || 'Error updating attendance', 'error');
                    }
                },
                error: function() {
                    showNotification('Server error', 'error');
                }
            });
        }
        
        // Calculate working hours
        function calculateWorkingHours(userId) {
            let row = $(`tr[data-user-id="${userId}"]`);
            let checkIn = row.find('.checkIn').val();
            let checkOut = row.find('.checkOut').val();
            
            if(checkIn && checkOut) {
                let start = new Date(`2000-01-01T${checkIn}:00`);
                let end = new Date(`2000-01-01T${checkOut}:00`);
                let diffHours = (end - start) / (1000 * 60 * 60);
                let workingHours = diffHours.toFixed(2);
                let overtime = Math.max(0, workingHours - 8).toFixed(2);
                
                row.find('.workingHours').text(workingHours);
                row.find('.overtime').text(overtime);
                
                $.ajax({
                    url: '/unidia/public/api/attendance/update',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        date: $('#attendanceDate').val(),
                        field: 'working_hours',
                        value: workingHours
                    }
                });
                
                $.ajax({
                    url: '/unidia/public/api/attendance/update',
                    method: 'POST',
                    data: {
                        user_id: userId,
                        date: $('#attendanceDate').val(),
                        field: 'overtime_hours',
                        value: overtime
                    }
                });
            }
        }
        
        // Mark check in
        function markCheckIn(userId) {
            let now = new Date();
            let time = now.toTimeString().slice(0,5);
            $(`tr[data-user-id="${userId}"] .checkIn`).val(time);
            updateAttendance(userId, 'check_in', time);
        }
        
        // Mark check out
        function markCheckOut(userId) {
            let now = new Date();
            let time = now.toTimeString().slice(0,5);
            $(`tr[data-user-id="${userId}"] .checkOut`).val(time);
            updateAttendance(userId, 'check_out', time);
        }
        
        // Change date
        function changeDate() {
            let date = $('#attendanceDate').val();
            window.location.href = `/unidia/public/admin/users/attendance?date=${date}&department=${$('#deptFilter').val()}`;
        }
        
        // Filter by department
        function filterByDepartment() {
            let dept = $('#deptFilter').val();
            window.location.href = `/unidia/public/admin/users/attendance?date=${$('#attendanceDate').val()}&department=${dept}`;
        }
        
        // Filter by status
        function filterByStatus() {
            let status = $('#statusFilter').val();
            let table = $('#attendanceTable').DataTable();
            if(status) {
                table.column(8).search(status).draw();
            } else {
                table.column(8).search('').draw();
            }
        }
        
        // Search staff
        function searchStaff() {
            let search = $('#searchInput').val();
            $('#attendanceTable').DataTable().search(search).draw();
        }
        
        // Toggle select all
        function toggleSelectAll() {
            $('.userCheckbox').prop('checked', $('#selectAll').prop('checked'));
        }
        
        // Mark all present
        function markAllPresent() {
            $('.statusSelect').val('present').change();
            $('.statusSelect').each(function() {
                updateAttendance($(this).closest('tr').data('user-id'), 'status', 'present');
            });
        }
        
        // Mark all absent
        function markAllAbsent() {
            $('.statusSelect').val('absent').change();
            $('.statusSelect').each(function() {
                updateAttendance($(this).closest('tr').data('user-id'), 'status', 'absent');
            });
        }
        
        // Clear all
        function clearAll() {
            if(confirm('Are you sure you want to clear all attendance for this day?')) {
                $('.statusSelect').val('').change();
                $('.checkIn').val('');
                $('.checkOut').val('');
                $('.statusSelect').each(function() {
                    updateAttendance($(this).closest('tr').data('user-id'), 'status', '');
                });
            }
        }
        
        // Apply bulk attendance
        function applyBulkAttendance() {
            let date = $('#bulkDate').val();
            let department = $('#bulkDepartment').val();
            let status = $('#bulkStatus').val();
            let checkIn = $('#bulkCheckIn').val();
            let checkOut = $('#bulkCheckOut').val();
            
            $.ajax({
                url: '/unidia/public/api/attendance/bulk',
                method: 'POST',
                data: {
                    date: date,
                    department: department,
                    status: status,
                    check_in: checkIn,
                    check_out: checkOut
                },
                success: function(response) {
                    if(response.success) {
                        showNotification(response.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showNotification(response.message, 'error');
                    }
                }
            });
        }
        
        // Export attendance
        function exportAttendance() {
            let date = $('#attendanceDate').val();
            window.location.href = `/unidia/public/api/attendance/export?date=${date}&format=excel`;
        }
        
        // Print attendance
        function printAttendance() {
            let printContents = document.querySelector('.card').cloneNode(true);
            let originalContents = document.body.innerHTML;
            document.body.innerHTML = printContents.outerHTML;
            window.print();
            document.body.innerHTML = originalContents;
            location.reload();
        }
        
        // Refresh table
        function refreshTable() {
            location.reload();
        }
        
        // Show notification
        function showNotification(message, type) {
            let alertDiv = $(`<div class="alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index:9999" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`);
            $('body').append(alertDiv);
            setTimeout(() => alertDiv.fadeOut(), 3000);
        }
        
        // Load charts
        function loadCharts() {
            let date = $('#attendanceDate').val();
            $.ajax({
                url: '/unidia/public/api/attendance/summary',
                method: 'GET',
                data: { date: date },
                success: function(data) {
                    // Attendance pie chart
                    let ctx = document.getElementById('attendanceChart').getContext('2d');
                    if(attendanceChart) attendanceChart.destroy();
                    attendanceChart = new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: ['Present', 'Absent', 'Late', 'Half Day', 'Leave'],
                            datasets: [{
                                data: [data.present, data.absent, data.late, data.half_day, data.leave],
                                backgroundColor: ['#28a745', '#dc3545', '#ffc107', '#fd7e14', '#6c757d']
                            }]
                        },
                        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
                    });
                    
                    // Monthly trend chart
                    let trendCtx = document.getElementById('trendChart').getContext('2d');
                    if(trendChart) trendChart.destroy();
                    trendChart = new Chart(trendCtx, {
                        type: 'line',
                        data: {
                            labels: data.trendLabels || [],
                            datasets: [{
                                label: 'Present %',
                                data: data.trendData || [],
                                borderColor: '#28a745',
                                tension: 0.4,
                                fill: false
                            }]
                        },
                        options: { responsive: true }
                    });
                }
            });
        }
        
        // Save notes
        function saveNotes() {
            if(currentUserId) {
                let notes = $('#attendanceNotes').val();
                updateAttendance(currentUserId, 'notes', notes);
                $('#notesModal').modal('hide');
            }
        }
    </script>
</body>
</html>