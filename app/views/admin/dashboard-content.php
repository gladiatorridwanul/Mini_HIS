<!-- Flash Messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Dashboard Statistics Cards -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Doctors</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['totalDoctors']); ?></h2>
                    </div>
                    <i class="fas fa-user-md fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Total Patients</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['totalPatients']); ?></h2>
                    </div>
                    <i class="fas fa-procedures fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Today's Appointments</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['todayAppointments']); ?></h2>
                    </div>
                    <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Today's Revenue</h6>
                        <h2 class="mb-0">$<?php echo number_format($stats['todayRevenue'], 2); ?></h2>
                    </div>
                    <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="/unidia/public/patient/register" class="btn btn-primary me-2 mb-2">
                    <i class="fas fa-user-plus me-1"></i> Register Patient
                </a>
                <a href="/unidia/public/patient/list" class="btn btn-info me-2 mb-2 text-white">
                    <i class="fas fa-list me-1"></i> View Patients
                </a>
                <a href="/unidia/public/admin/users" class="btn btn-secondary me-2 mb-2">
                    <i class="fas fa-users-cog me-1"></i> Manage Users
                </a>
                <a href="/unidia/public/reception/appointments/create" class="btn btn-success me-2 mb-2">
                    <i class="fas fa-calendar-plus me-1"></i> Book Appointment
                </a>
                <a href="/unidia/public/pharmacy/pos" class="btn btn-dark me-2 mb-2">
                    <i class="fas fa-cash-register me-1"></i> POS
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Patients & Appointments -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-user-plus text-primary me-2"></i> Recent Patients</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentPatients)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr><th>Code</th><th>Name</th><th>Phone</th><th>Date</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentPatients as $patient): ?>
                                <tr>
                                    <td><span class="badge bg-primary"><?php echo $patient['patient_code']; ?></span></td>
                                    <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($patient['registration_date'])); ?></td>
                                    <td><a href="/unidia/public/patient/view?id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No patients registered yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt text-success me-2"></i> Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentAppointments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr><th>Date</th><th>Patient</th><th>Doctor</th><th>Time</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentAppointments as $appt): ?>
                                <tr>
                                    <td><?php echo date('d M', strtotime($appt['appointment_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']); ?></td>
                                    <td>Dr. <?php echo htmlspecialchars($appt['doctor_first'] . ' ' . $appt['doctor_last']); ?></td>
                                    <td><?php echo date('h:i A', strtotime($appt['start_time'])); ?></td>
                                    <td><?php
                                        $statusClass = $appt['status'] == 'scheduled' ? 'primary' : ($appt['status'] == 'completed' ? 'success' : 'secondary');
                                        echo '<span class="badge bg-' . $statusClass . '">' . ucfirst($appt['status']) . '</span>';
                                    ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No upcoming appointments.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-info-circle text-info me-2"></i> System Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Application:</strong> UniDia HMS V-1.0.1
                    </div>
                    <div class="col-md-3">
                        <strong>PHP Version:</strong> <?php echo phpversion(); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Status:</strong> <span class="text-success">Running</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>