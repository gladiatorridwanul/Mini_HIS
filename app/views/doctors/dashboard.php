<div class="container-fluid">
    <h4 class="mb-4">
        <i class="fas fa-tachometer-alt me-2"></i> Doctor Dashboard
        <small class="text-muted">Welcome back, Dr. <?php echo $_SESSION['user_name']; ?></small>
    </h4>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">Today's Appointments</h6>
                            <h3 class="mb-0"><?php echo $stats['todayAppointments']; ?></h3>
                        </div>
                        <i class="fas fa-calendar-day fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">Pending Appointments</h6>
                            <h3 class="mb-0"><?php echo $stats['pendingAppointments']; ?></h3>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">Total Patients</h6>
                            <h3 class="mb-0"><?php echo $stats['totalPatients']; ?></h3>
                        </div>
                        <i class="fas fa-users fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">Total Commission</h6>
                            <h3 class="mb-0">$<?php echo number_format($stats['totalCommission'], 2); ?></h3>
                        </div>
                        <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Today's Appointments -->
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Today's Appointments</h5>
            <span class="badge bg-primary"><?php echo date('l, F d, Y'); ?></span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr><th>Time</th><th>Patient Name</th><th>Type</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($appointments)): ?>
                            <?php foreach($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo date('h:i A', strtotime($appointment['start_time'])); ?></td>
                                <td><strong><?php echo $appointment['first_name'] . ' ' . $appointment['last_name']; ?></strong><br><small class="text-muted"><?php echo $appointment['patient_code']; ?></small></td>
                                <td><span class="badge bg-<?php echo $appointment['appointment_type'] == 'emergency' ? 'danger' : 'primary'; ?>"><?php echo ucfirst($appointment['appointment_type']); ?></span></td>
                                <td><span class="badge bg-<?php echo $appointment['status'] == 'checked_in' ? 'success' : 'warning'; ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="/unidia/public/doctor/appointment/<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                        <?php if($appointment['status'] == 'checked_in'): ?>
                                            <a href="/unidia/public/doctor/create-prescription/<?php echo $appointment['id']; ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-prescription"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No appointments for today</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>