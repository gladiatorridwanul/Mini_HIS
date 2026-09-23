<?php
/**
 * Daily Attendance View
 */
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h5 class="mb-0 fw-bold">
            <i class="fas fa-calendar-day text-primary me-2"></i> Daily Attendance
            <span class="badge bg-primary ms-2"><?php echo date('l, F j, Y', strtotime($date)); ?></span>
        </h5>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>/admin/users/attendance?view=daily&date=<?php echo date('Y-m-d', strtotime($date . ' -1 day')); ?>" 
               class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-chevron-left"></i> Prev
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/users/attendance?view=daily&date=<?php echo date('Y-m-d'); ?>" 
               class="btn btn-outline-primary btn-sm">
                <i class="fas fa-calendar-day"></i> Today
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/users/attendance?view=daily&date=<?php echo date('Y-m-d', strtotime($date . ' +1 day')); ?>" 
               class="btn btn-outline-secondary btn-sm">
                Next <i class="fas fa-chevron-right"></i>
            </a>
            <button onclick="saveAllAttendance()" class="btn btn-success btn-sm">
                <i class="fas fa-save"></i> Save All
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Employee</th>
                        <th>Role</th>
                        <th style="width: 150px;">Check In</th>
                        <th style="width: 150px;">Check Out</th>
                        <th style="width: 150px;">Status</th>
                        <th style="width: 100px;">Hours</th>
                        <th style="width: 50px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendances)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x d-block mb-2"></i>
                            No employees found
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php $i = 1; foreach ($attendances as $att): ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
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
                        </td>
                        <td>
                            <input type="time" class="form-control form-control-sm check-out" 
                                   data-userid="<?php echo $att['id']; ?>" 
                                   value="<?php echo $att['check_out'] ? date('H:i', strtotime($att['check_out'])) : ''; ?>"
                                   style="width: 130px;">
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
                            echo $hours > 0 ? number_format($hours, 2) : '-';
                            ?>
                        </td>
                        <td>
                            <button onclick="saveAttendance(<?php echo $att['id']; ?>)" 
                                    class="btn btn-sm btn-outline-primary save-btn" 
                                    id="save-<?php echo $att['id']; ?>">
                                <i class="fas fa-save"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
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
</script>