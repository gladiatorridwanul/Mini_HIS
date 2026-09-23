<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-calendar-check me-2"></i>Appointments</h5>
        <a href="/unidia/public/patient/list" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> New Appointment
        </a>
    </div>
    <div class="card-body">
        <!-- Filter Section -->
        <div class="row mb-3">
            <div class="col-md-3">
                <select id="doctorFilter" class="form-select">
                    <option value="">All Doctors</option>
                    <?php foreach($doctors as $doctor): ?>
                        <option value="<?php echo $doctor['id']; ?>" <?php echo ($selectedDoctor == $doctor['id']) ? 'selected' : ''; ?>>
                            Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="statusFilter" class="form-select">
                    <option value="">All Status</option>
                    <option value="scheduled" <?php echo ($selectedStatus == 'scheduled') ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="confirmed" <?php echo ($selectedStatus == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                    <option value="completed" <?php echo ($selectedStatus == 'completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="canceled" <?php echo ($selectedStatus == 'canceled') ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" id="dateFilter" class="form-control" value="<?php echo $selectedDate; ?>">
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary" onclick="applyFilters()">Filter</button>
                <button class="btn btn-secondary" onclick="resetFilters()">Reset</button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($appointments)): ?>
                        <td><td colspan="8" class="text-center text-muted">No appointments found</td></tr>
                    <?php else: ?>
                        <?php foreach($appointments as $apt): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($apt['start_time'])); ?></td>
                            <td>
                                <strong><?php echo $apt['p_first'] . ' ' . $apt['p_last']; ?></strong><br>
                                <small class="text-muted"><?php echo $apt['patient_code']; ?></small>
                            </td>
                            <td>Dr. <?php echo $apt['d_first'] . ' ' . $apt['d_last']; ?><br>
                                <small><?php echo $apt['specialization']; ?></small>
                            </td>
                            <td><?php echo ucfirst($apt['appointment_type']); ?></td>
                            <td>৳ <?php echo number_format($apt['total_amount'], 2); ?></td>
                            <td>
                                <?php
                                $statusClass = 'primary';
                                if($apt['status'] == 'completed') $statusClass = 'success';
                                if($apt['status'] == 'canceled') $statusClass = 'danger';
                                if($apt['status'] == 'confirmed') $statusClass = 'info';
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>"><?php echo ucfirst($apt['status']); ?></span>
                             </td>
                            <td>
                                <a href="/unidia/public/api/print-serial?appointment_id=<?php echo $apt['id']; ?>" 
                                   class="btn btn-sm btn-secondary" target="_blank" title="Print Slip">
                                    <i class="fas fa-print"></i>
                                </a>
                                <!-- ========== ADD THIS BUTTON ========== -->
                                <a href="<?php echo BASE_URL; ?>/prescriptions/create?appointment_id=<?php echo $apt['id']; ?>" 
                                   class="btn btn-sm btn-primary" title="Create Prescription">
                                    <i class="fas fa-prescription"></i>
                                </a>
                                <!-- ===================================== -->
                                <?php if($apt['status'] != 'canceled' && $apt['status'] != 'completed'): ?>
                                <button onclick="cancelAppointment(<?php echo $apt['id']; ?>)" class="btn btn-sm btn-danger" title="Cancel">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
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
function applyFilters() {
    var doctor = $('#doctorFilter').val();
    var status = $('#statusFilter').val();
    var date = $('#dateFilter').val();
    var url = '/unidia/public/appointments?';
    if(doctor) url += 'doctor_id=' + doctor + '&';
    if(status) url += 'status=' + status + '&';
    if(date) url += 'date=' + date;
    window.location.href = url;
}

function resetFilters() {
    window.location.href = '/unidia/public/appointments';
}

function cancelAppointment(id) {
    if(confirm('Are you sure you want to cancel this appointment?')) {
        $.post('/unidia/public/api/cancel-appointment', {appointment_id: id}, function(response) {
            if(response.success) {
                location.reload();
            } else {
                alert('Failed to cancel appointment');
            }
        });
    }
}
</script>