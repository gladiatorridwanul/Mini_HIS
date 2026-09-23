<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-user-md me-2 text-primary"></i>Doctor Details</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/unidia/public/admin/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="/unidia/public/doctor/list">Doctors</a></li>
                    <li class="breadcrumb-item active">Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="/unidia/public/doctor/edit/<?php echo $doctor['id']; ?>" class="btn btn-warning me-2">
                <i class="fas fa-edit me-1"></i> Edit Doctor
            </a>
            <a href="/unidia/public/doctor/list" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Left Column - Profile & Basic Info -->
        <div class="col-md-4">
            <!-- Profile Card -->
            <div class="card shadow mb-4">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <div class="avatar-circle">
                            <i class="fas fa-user-md fa-4x"></i>
                        </div>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($doctor['title'] . ' ' . $doctor['first_name'] . ' ' . $doctor['last_name']); ?></h4>
                    <p class="text-primary mb-2"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                    <span class="badge bg-success mb-3">Active</span>
                    
                    <hr>
                    
                    <div class="text-start">
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-id-card me-2"></i>BMDC No.</div>
                            <div class="info-value"><?php echo htmlspecialchars($doctor['bmdc_number']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-building me-2"></i>Department</div>
                            <div class="info-value"><?php echo htmlspecialchars($doctor['department_name']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-graduation-cap me-2"></i>Qualification</div>
                            <div class="info-value"><?php echo htmlspecialchars($doctor['qualification']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-calendar-alt me-2"></i>Experience</div>
                            <div class="info-value"><?php echo $doctor['experience_years']; ?> years</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="fas fa-user me-2"></i>Employee ID</div>
                            <div class="info-value"><?php echo htmlspecialchars($doctor['employee_id']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Card -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-phone-alt me-2"></i>Phone</div>
                        <div class="info-value">
                            <a href="tel:<?php echo $doctor['phone']; ?>"><?php echo htmlspecialchars($doctor['phone']); ?></a>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-envelope me-2"></i>Email</div>
                        <div class="info-value">
                            <?php if($doctor['email']): ?>
                                <a href="mailto:<?php echo $doctor['email']; ?>"><?php echo htmlspecialchars($doctor['email']); ?></a>
                            <?php else: ?>
                                <span class="text-muted">Not provided</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Consultation Fee Card -->
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Consultation Fee</h6>
                </div>
                <div class="card-body text-center">
                    <h2 class="text-success mb-0">৳ <?php echo number_format($doctor['consultation_fee'], 2); ?></h2>
                    <small class="text-muted">Per Consultation</small>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Services -->
        <div class="col-md-8">
            <!-- Services Card -->
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-list-alt me-2"></i>Services & Price List with Commission</h6>
                </div>
                <div class="card-body">
                    <?php if(isset($doctor['services']) && count($doctor['services']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="45%">Service Name</th>
                                        <th width="20%">Price (৳)</th>
                                        <th width="15%">Commission (%)</th>
                                        <th width="15%">Commission Amount (৳)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $serviceCounter = 1; ?>
                                    <?php foreach($doctor['services'] as $service): ?>
                                        <tr>
                                            <td class="text-center"><?php echo $serviceCounter++; ?></td>
                                            <td><strong><?php echo htmlspecialchars($service['service_name']); ?></strong></td>
                                            <td class="text-end">৳ <?php echo number_format($service['service_price'], 2); ?></td>
                                            <td class="text-end"><?php echo $service['commission_percentage']; ?>%</td>
                                            <td class="text-end text-success">
                                                ৳ <?php echo number_format(($service['service_price'] * $service['commission_percentage']) / 100, 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-secondary">
                                    <tr>
                                        <th colspan="4" class="text-end">Total Services:</th>
                                        <th class="text-end"><?php echo count($doctor['services']); ?> Services</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No additional services added for this doctor.</p>
                            <a href="/unidia/public/doctor/edit/<?php echo $doctor['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Add Services
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <a href="/unidia/public/doctor/schedule/<?php echo $doctor['id']; ?>" class="text-decoration-none">
                        <div class="card shadow text-center hover-card">
                            <div class="card-body py-3">
                                <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                                <h6 class="mb-0">Manage Schedule</h6>
                                <small class="text-muted">Set working hours</small>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="/unidia/public/doctor/commissions" class="text-decoration-none">
                        <div class="card shadow text-center hover-card">
                            <div class="card-body py-3">
                                <i class="fas fa-percent fa-2x text-success mb-2"></i>
                                <h6 class="mb-0">Commission History</h6>
                                <small class="text-muted">View earnings</small>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-avatar {
        position: relative;
    }
    .avatar-circle {
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .avatar-circle i {
        color: white;
        font-size: 48px;
    }
    .info-row {
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f0f0f0;
    }
    .info-row:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .info-label {
        font-weight: 600;
        color: #555;
        font-size: 12px;
        margin-bottom: 5px;
    }
    .info-value {
        color: #333;
        font-size: 14px;
    }
    .hover-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .card-header {
        border-bottom: none;
    }
    .table td {
        vertical-align: middle;
    }
</style>