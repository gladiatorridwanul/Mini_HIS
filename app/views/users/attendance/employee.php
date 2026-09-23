<?php
/**
 * Employee Summary View
 */
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-user text-primary me-2"></i> Employee Summary
        </h5>
        <div class="d-flex gap-2 align-items-center">
            <select id="employeeSelect" class="form-select form-select-sm" style="width: 200px;" onchange="changeEmployee()">
                <option value="">Select Employee</option>
                <?php foreach ($employeeList as $emp): ?>
                <option value="<?php echo $emp['id']; ?>" <?php echo $emp['id'] == $employeeId ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name'] . ' (' . $emp['employee_id'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <input type="month" id="employeeMonth" class="form-control form-control-sm" style="width: 160px;" 
                   value="<?php echo $month; ?>" onchange="changeEmployeeMonth()">
            <button onclick="exportMonthlyAttendance()" class="btn btn-success btn-sm">
                <i class="fas fa-file-export"></i> Export
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if ($employeeId > 0): ?>
            <?php 
            $empData = [];
            $totalPresent = 0;
            $totalAbsent = 0;
            $totalLeave = 0;
            $totalHours = 0;
            
            foreach ($monthlyData as $row) {
                if ($row['id'] != $employeeId) continue;
                $empData = [
                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                    'employee_id' => $row['employee_id'],
                    'role' => $row['role_name'] ?? 'N/A'
                ];
                if ($row['attendance_date']) {
                    $status = $row['att_status'] ?? '-';
                    if ($status == 'present' || $status == 'late' || $status == 'half_day') {
                        $totalPresent++;
                    } elseif ($status == 'absent') {
                        $totalAbsent++;
                    } elseif ($status == 'leave') {
                        $totalLeave++;
                    }
                    $totalHours += floatval($row['working_hours'] ?? 0);
                }
            }
            ?>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center py-3">
                            <h6 class="mb-1"><?php echo htmlspecialchars($empData['name'] ?? 'N/A'); ?></h6>
                            <small><?php echo htmlspecialchars($empData['employee_id'] ?? ''); ?></small>
                            <div class="mt-2"><span class="badge bg-light text-dark"><?php echo htmlspecialchars($empData['role'] ?? ''); ?></span></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center py-3">
                            <h3 class="mb-0"><?php echo $totalPresent; ?></h3>
                            <small>Present</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center py-3">
                            <h3 class="mb-0"><?php echo $totalAbsent; ?></h3>
                            <small>Absent</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center py-3">
                            <h3 class="mb-0"><?php echo $totalLeave; ?></h3>
                            <small>Leave</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center py-3">
                            <h3 class="mb-0"><?php echo number_format($totalHours, 1); ?></h3>
                            <small>Hours</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <h6 class="fw-bold mb-3">Daily Details</h6>
            <div class="table-responsive">
                <table class="table table-hover table-striped table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status</th>
                            <th>Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $daysInMonth = date('t', strtotime($month));
                        for ($d = 1; $d <= $daysInMonth; $d++):
                            $dateStr = date('Y-m-d', strtotime($month . '-' . $d));
                            $dayName = date('l', strtotime($dateStr));
                            $found = false;
                            foreach ($monthlyData as $row) {
                                if ($row['id'] == $employeeId && $row['attendance_date'] == $dateStr) {
                                    $found = true;
                                    ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($dateStr)); ?></td>
                                        <td><?php echo $dayName; ?></td>
                                        <td><?php echo $row['check_in'] ? date('h:i A', strtotime($row['check_in'])) : '-'; ?></td>
                                        <td><?php echo $row['check_out'] ? date('h:i A', strtotime($row['check_out'])) : '-'; ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $row['att_status'] == 'present' ? 'bg-success' : 
                                                    ($row['att_status'] == 'absent' ? 'bg-danger' : 
                                                    ($row['att_status'] == 'leave' ? 'bg-warning' : 
                                                    ($row['att_status'] == 'late' ? 'bg-warning' : 'bg-secondary'))); 
                                            ?>">
                                                <?php echo ucfirst($row['att_status'] ?? '-'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $row['working_hours'] ? number_format($row['working_hours'], 2) : '-'; ?></td>
                                    </tr>
                                    <?php
                                    break;
                                }
                            }
                            if (!$found):
                            ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($dateStr)); ?></td>
                                <td><?php echo $dayName; ?></td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="badge bg-secondary">Not Marked</span></td>
                                <td>-</td>
                            </tr>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-user fa-3x mb-3"></i>
                <p>Please select an employee to view their attendance summary.</p>
            </div>
        <?php endif; ?>
    </div>
</div>