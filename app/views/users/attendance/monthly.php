<?php
/**
 * Monthly Attendance View
 */
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-calendar-alt text-primary me-2"></i> Monthly Attendance
            <span class="badge bg-primary ms-2"><?php echo date('F Y', strtotime($month)); ?></span>
        </h5>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>/admin/users/attendance?view=monthly&month=<?php echo date('Y-m', strtotime($month . ' -1 month')); ?>" 
               class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chevron-left"></i>
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/users/attendance?view=monthly&month=<?php echo date('Y-m'); ?>" 
               class="btn btn-outline-primary btn-sm">
                <i class="fas fa-calendar-alt"></i> Current
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/users/attendance?view=monthly&month=<?php echo date('Y-m', strtotime($month . ' +1 month')); ?>" 
               class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chevron-right"></i>
            </a>
            <button onclick="exportMonthlyAttendance()" class="btn btn-success btn-sm">
                <i class="fas fa-file-export"></i> Export CSV
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>Employee</th>
                        <th>Role</th>
                        <?php 
                        $daysInMonth = date('t', strtotime($month));
                        for ($d = 1; $d <= $daysInMonth; $d++): 
                        ?>
                        <th style="text-align: center; font-size: 11px; padding: 4px 2px; min-width: 24px;">
                            <?php echo $d; ?>
                        </th>
                        <?php endfor; ?>
                        <th>P</th>
                        <th>A</th>
                        <th>L</th>
                        <th>Total Hrs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Group attendance by employee
                    $employeeAttendance = [];
                    if (!empty($monthlyData)) {
                        foreach ($monthlyData as $row) {
                            $empId = $row['id'];
                            if (!isset($employeeAttendance[$empId])) {
                                $employeeAttendance[$empId] = [
                                    'name' => $row['first_name'] . ' ' . $row['last_name'],
                                    'employee_id' => $row['employee_id'],
                                    'role' => $row['role_name'] ?? 'N/A',
                                    'attendance' => [],
                                    'total_present' => 0,
                                    'total_absent' => 0,
                                    'total_leave' => 0,
                                    'total_hours' => 0
                                ];
                            }
                            if ($row['attendance_date']) {
                                $dateKey = date('j', strtotime($row['attendance_date']));
                                $employeeAttendance[$empId]['attendance'][$dateKey] = [
                                    'status' => $row['att_status'] ?? '-',
                                    'hours' => floatval($row['working_hours'] ?? 0)
                                ];
                                $status = $row['att_status'] ?? '-';
                                if ($status == 'present' || $status == 'late' || $status == 'half_day') {
                                    $employeeAttendance[$empId]['total_present']++;
                                } elseif ($status == 'absent') {
                                    $employeeAttendance[$empId]['total_absent']++;
                                } elseif ($status == 'leave') {
                                    $employeeAttendance[$empId]['total_leave']++;
                                }
                                $employeeAttendance[$empId]['total_hours'] += floatval($row['working_hours'] ?? 0);
                            }
                        }
                    }
                    
                    if (empty($employeeAttendance)):
                    ?>
                    <tr>
                        <td colspan="<?php echo $daysInMonth + 8; ?>" class="text-center text-muted py-4">
                            <i class="fas fa-calendar-alt fa-2x d-block mb-2"></i>
                            No attendance records found for this month
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php $i = 1; foreach ($employeeAttendance as $empId => $emp): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($emp['name']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($emp['employee_id']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($emp['role']); ?></td>
                        <?php 
                        $daysInMonth = date('t', strtotime($month));
                        for ($d = 1; $d <= $daysInMonth; $d++): 
                        ?>
                        <td style="text-align: center; font-size: 12px;">
                            <?php 
                            $status = $emp['attendance'][$d]['status'] ?? '-';
                            $statusClass = '';
                            if ($status == 'present' || $status == 'late' || $status == 'half_day') {
                                $statusClass = 'text-success';
                            } elseif ($status == 'absent') {
                                $statusClass = 'text-danger';
                            } elseif ($status == 'leave') {
                                $statusClass = 'text-warning';
                            }
                            ?>
                            <span class="<?php echo $statusClass; ?>"><?php echo $status == '-' ? '-' : substr($status, 0, 1); ?></span>
                        </td>
                        <?php endfor; ?>
                        <td class="text-success fw-bold"><?php echo $emp['total_present']; ?></td>
                        <td class="text-danger"><?php echo $emp['total_absent']; ?></td>
                        <td class="text-warning"><?php echo $emp['total_leave']; ?></td>
                        <td><?php echo number_format($emp['total_hours'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>