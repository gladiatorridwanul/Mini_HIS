<?php
// app/views/dashboard/index.php
// Advanced Role-based Dashboard with Quick Menu Actions
?>

<div class="container-fluid">
    <!-- ============================================================ -->
    <!-- HEADER SECTION -->
    <!-- ============================================================ -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1"><i class="fas fa-chart-pie text-primary me-2"></i>Dashboard</h4>
            <p class="text-muted small mb-0">
                Welcome back, <strong><?php echo htmlspecialchars($stats['userName'] ?? 'Admin'); ?></strong>!
                <span class="badge bg-info ms-2"><?php echo $stats['roleLabel'] ?? 'Guest'; ?></span>
                <span class="badge bg-secondary ms-1"><?php echo $stats['todayDate'] ?? date('l, d M Y'); ?></span>
                <span class="badge bg-success ms-1" id="live-time">
                    <i class="fas fa-clock me-1"></i> <?php echo $stats['currentTime'] ?? date('h:i:s A'); ?>
                </span>
            </p>
            <?php if (isset($stats['userEmail']) && !empty($stats['userEmail'])): ?>
            <small class="text-muted">
                <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($stats['userEmail']); ?>
                <?php if (isset($stats['userPhone']) && !empty($stats['userPhone'])): ?>
                | <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($stats['userPhone']); ?>
                <?php endif; ?>
            </small>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button class="btn btn-sm btn-outline-secondary" onclick="refreshDashboard();">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-sm btn-outline-primary" onclick="window.print();">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <?php 
    // ================================================================
    // ROLE DETECTION - Using both role_slug and role_id for compatibility
    // ================================================================
    $role = $userRole ?? 'guest';
    $roleId = $stats['roleId'] ?? 0;
    
    // If role is 'guest' but we have a role slug from session, use that
    if ($role == 'guest' && isset($_SESSION['role_slug'])) {
        $role = $_SESSION['role_slug'];
    }
    
    // Determine role flags
    $isSuperAdmin = ($role == 'super_admin' || $role == 'Super Admin' || $roleId == 1);
    $isAdmin = ($role == 'admin' || $role == 'Admin' || $roleId == 2 || $isSuperAdmin);
    $isDoctor = ($role == 'doctor' || $role == 'Doctor' || $roleId == 3);
    $isReceptionist = ($role == 'receptionist' || $role == 'Receptionist' || $roleId == 4);
    $isPharmacist = ($role == 'pharmacist' || $role == 'Pharmacist' || $roleId == 5);
    $isLabTech = ($role == 'lab_technician' || $role == 'Lab Technician' || $roleId == 6);
    $isAccountant = ($role == 'accountant' || $role == 'Accountant' || $roleId == 7);
    $isNurse = ($role == 'nurse' || $role == 'Nurse' || $roleId == 8);
    
    // Also check if user is any type of admin
    $isAnyAdmin = ($isAdmin || $isSuperAdmin);
    
    // Display role for debugging if needed (hidden)
    // echo '<!-- Role: ' . $role . ' | Role ID: ' . $roleId . ' -->';
    ?>

    <!-- ============================================================ -->
    <!-- ALERT: Low Stock Warning -->
    <!-- ============================================================ -->
    <?php if ($isAdmin || $isPharmacist || $isSuperAdmin): ?>
        <?php if (($stats['lowStock'] ?? 0) > 0 || ($stats['expiringSoon'] ?? 0) > 0): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Inventory Alert:</strong>
            <?php if (($stats['lowStock'] ?? 0) > 0): ?>
                <span class="badge bg-danger me-2"><?php echo $stats['lowStock']; ?> items low in stock</span>
            <?php endif; ?>
            <?php if (($stats['expiringSoon'] ?? 0) > 0): ?>
                <span class="badge bg-warning text-dark"><?php echo $stats['expiringSoon']; ?> items expiring within 30 days</span>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- QUICK MENU ACTIONS - Role Wise -->
    <!-- ============================================================ -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap gap-1 gap-md-2">
                        
                        <!-- ====== SUPER ADMIN & ADMIN ====== -->
                        <?php if ($isSuperAdmin || $isAdmin): ?>
                        <a href="<?php echo BASE_URL; ?>/patient/register" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-plus me-1"></i> Patient
                        </a>
                        <a href="<?php echo BASE_URL; ?>/reception/appointments" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-calendar-plus me-1"></i> Appointment
                        </a>
                        <a href="<?php echo BASE_URL; ?>/bills/create" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-file-invoice me-1"></i> Bill
                        </a>
                        <a href="<?php echo BASE_URL; ?>/inventory/items" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-box me-1"></i> Inventory
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pharmacy/pos" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-cash-register me-1"></i> POS
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/users/create" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-user me-1"></i> User
                        </a>
                        <a href="<?php echo BASE_URL; ?>/doctor/create" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-user-md me-1"></i> Doctor
                        </a>
                        <?php endif; ?>

                        <!-- ====== RECEPTIONIST ====== -->
                        <?php if ($isReceptionist): ?>
                        <a href="<?php echo BASE_URL; ?>/patient/register" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user-plus me-1"></i> Register Patient
                        </a>
                        <a href="<?php echo BASE_URL; ?>/reception/appointments" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-calendar-check me-1"></i> Today's Appointments
                        </a>
                        <a href="<?php echo BASE_URL; ?>/reception/daily-list" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-list me-1"></i> Daily List
                        </a>
                        <a href="<?php echo BASE_URL; ?>/reception/check-in" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-clipboard-check me-1"></i> Check-in
                        </a>
                        <a href="<?php echo BASE_URL; ?>/reception/queue" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-chart-line me-1"></i> Queue
                        </a>
                        <a href="<?php echo BASE_URL; ?>/bills/create" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-file-invoice me-1"></i> Create Bill
                        </a>
                        <?php endif; ?>

                        <!-- ====== DOCTOR ====== -->
                        <?php if ($isDoctor): ?>
                        <a href="<?php echo BASE_URL; ?>/reception/appointments" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-calendar-check me-1"></i> My Appointments
                        </a>
                        <a href="<?php echo BASE_URL; ?>/prescription/create" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-prescription me-1"></i> New Prescription
                        </a>
                        <a href="<?php echo BASE_URL; ?>/patient/list" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-users me-1"></i> My Patients
                        </a>
                        <a href="<?php echo BASE_URL; ?>/lab/create-order" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-flask me-1"></i> Lab Order
                        </a>
                        <a href="<?php echo BASE_URL; ?>/reception/queue" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-chart-line me-1"></i> Queue Status
                        </a>
                        <?php endif; ?>

                        <!-- ====== PHARMACIST ====== -->
                        <?php if ($isPharmacist): ?>
                        <a href="<?php echo BASE_URL; ?>/pharmacy/pos" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-cash-register me-1"></i> POS
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pharmacy/prescriptions" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-prescription me-1"></i> Dispense
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pharmacy/medicines" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-tablets me-1"></i> Medicines
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pharmacy/stock" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-boxes me-1"></i> Stock
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pharmacy/sales" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-history me-1"></i> Sales History
                        </a>
                        <?php endif; ?>

                        <!-- ====== LAB TECHNICIAN ====== -->
                        <?php if ($isLabTech): ?>
                        <a href="<?php echo BASE_URL; ?>/lab/create-order" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> New Order
                        </a>
                        <a href="<?php echo BASE_URL; ?>/lab/orders" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-list me-1"></i> All Orders
                        </a>
                        <a href="<?php echo BASE_URL; ?>/lab/sample-collection" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-vial me-1"></i> Sample Collection
                        </a>
                        <a href="<?php echo BASE_URL; ?>/lab/enter-results" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-edit me-1"></i> Enter Results
                        </a>
                        <a href="<?php echo BASE_URL; ?>/lab/reports" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-file-download me-1"></i> Reports
                        </a>
                        <?php endif; ?>

                        <!-- ====== ACCOUNTANT ====== -->
                        <?php if ($isAccountant): ?>
                        <a href="<?php echo BASE_URL; ?>/bills/create" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> New Bill
                        </a>
                        <a href="<?php echo BASE_URL; ?>/bills" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-list me-1"></i> All Bills
                        </a>
                        <a href="<?php echo BASE_URL; ?>/bills/payments" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-credit-card me-1"></i> Payments
                        </a>
                        <a href="<?php echo BASE_URL; ?>/account/reports" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-chart-line me-1"></i> Reports
                        </a>
                        <?php endif; ?>

                        <!-- ====== NURSE ====== -->
                        <?php if ($isNurse): ?>
                        <a href="<?php echo BASE_URL; ?>/reception/check-in" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-clipboard-check me-1"></i> Check-in
                        </a>
                        <a href="<?php echo BASE_URL; ?>/reception/daily-list" class="btn btn-outline-success btn-sm">
                            <i class="fas fa-list me-1"></i> Daily List
                        </a>
                        <a href="<?php echo BASE_URL; ?>/vaccines" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-syringe me-1"></i> Vaccines
                        </a>
                        <a href="<?php echo BASE_URL; ?>/reception/appointments" class="btn btn-outline-warning btn-sm">
                            <i class="fas fa-calendar-check me-1"></i> Appointments
                        </a>
                        <?php endif; ?>

                        <!-- ====== COMMON ====== -->
                        <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-home me-1"></i> Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- STATISTICS CARDS - ROLE BASED -->
    <!-- ============================================================ -->
    
    <!-- ====== RECEPTIONIST DASHBOARD ====== -->
    <?php if ($isReceptionist): ?>
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-success text-white">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h6>Total Patients</h6>
                    <h2><?php echo number_format($stats['totalPatients'] ?? 0); ?></h2>
                    <small><i class="fas fa-arrow-up me-1"></i> <?php echo $stats['newPatientsToday'] ?? 0; ?> new today</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-primary text-white">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h6>Today's Appointments</h6>
                    <h2><?php echo number_format($stats['todayAppointments'] ?? 0); ?></h2>
                    <small><?php echo $stats['morningAppointments'] ?? 0; ?> Morning | <?php echo $stats['eveningAppointments'] ?? 0; ?> Evening</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-danger text-white">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h6>Pending</h6>
                    <h2><?php echo number_format($stats['pendingAppointments'] ?? 0); ?></h2>
                    <small>Awaiting action</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-teal text-white">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h6>Completed</h6>
                    <h2><?php echo number_format($stats['completedToday'] ?? 0); ?></h2>
                    <small>Today's completed</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-warning text-white">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-info">
                    <h6>Queue</h6>
                    <h2><?php echo number_format(($stats['queueWaiting'] ?? 0) + ($stats['queueInProgress'] ?? 0)); ?></h2>
                    <small><span class="text-warning"><?php echo $stats['queueWaiting'] ?? 0; ?></span> waiting | <span class="text-success"><?php echo $stats['queueInProgress'] ?? 0; ?></span> in progress</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-blue text-white">
                <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="stat-info">
                    <h6>Pending Bills</h6>
                    <h2><?php echo number_format($stats['pendingBills'] ?? 0); ?></h2>
                    <small>Due: ৳ <?php echo number_format($stats['totalDue'] ?? 0, 0); ?></small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== ADMIN DASHBOARD ====== -->
    <?php if ($isAdmin): ?>
    <!-- Row 1: Core Stats -->
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-success text-white">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h6>Total Patients</h6>
                    <h2><?php echo number_format($stats['totalPatients'] ?? 0); ?></h2>
                    <small><i class="fas fa-arrow-up me-1"></i> <?php echo $stats['newPatientsToday'] ?? 0; ?> new today</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-primary text-white">
                <div class="stat-icon"><i class="fas fa-user-md"></i></div>
                <div class="stat-info">
                    <h6>Total Doctors</h6>
                    <h2><?php echo number_format($stats['totalDoctors'] ?? 0); ?></h2>
                    <small><?php echo $stats['activeDoctors'] ?? 0; ?> active</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-info text-white">
                <div class="stat-icon"><i class="fas fa-user-cog"></i></div>
                <div class="stat-info">
                    <h6>Total Users</h6>
                    <h2><?php echo number_format($stats['totalUsers'] ?? 0); ?></h2>
                    <small>Staff members</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-calendar text-white">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h6>Today's Appointments</h6>
                    <h2><?php echo number_format($stats['todayAppointments'] ?? 0); ?></h2>
                    <small><?php echo $stats['morningAppointments'] ?? 0; ?> Morning | <?php echo $stats['eveningAppointments'] ?? 0; ?> Evening</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-danger text-white">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h6>Pending</h6>
                    <h2><?php echo number_format($stats['pendingAppointments'] ?? 0); ?></h2>
                    <small>Awaiting consultation</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-teal text-white">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h6>Completed Today</h6>
                    <h2><?php echo number_format($stats['completedToday'] ?? 0); ?></h2>
                    <small>Consultations done</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Financial Stats -->
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-money text-white">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <h6>Today's Revenue</h6>
                    <h2>৳ <?php echo number_format($stats['todayRevenue'] ?? 0, 0); ?></h2>
                    <small><i class="fas fa-arrow-up me-1"></i> <?php echo $stats['todayTransactions'] ?? 0; ?> transactions</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-purple text-white">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <h6>Monthly Revenue</h6>
                    <h2>৳ <?php echo number_format($stats['monthlyRevenue'] ?? 0, 0); ?></h2>
                    <small><?php echo date('F Y'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-orange text-white">
                <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="stat-info">
                    <h6>Total Bills</h6>
                    <h2><?php echo number_format($stats['totalBills'] ?? 0); ?></h2>
                    <small>All time</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-pink text-white">
                <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="stat-info">
                    <h6>Pending Bills</h6>
                    <h2><?php echo number_format($stats['pendingBills'] ?? 0); ?></h2>
                    <small>Due: ৳ <?php echo number_format($stats['totalDue'] ?? 0, 0); ?></small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-success text-white">
                <div class="stat-icon"><i class="fas fa-percent"></i></div>
                <div class="stat-info">
                    <h6>Collection Rate</h6>
                    <h2><?php echo number_format($stats['collectionRate'] ?? 0, 1); ?>%</h2>
                    <small>Paid / Total</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-dark text-white">
                <div class="stat-icon"><i class="fas fa-prescription"></i></div>
                <div class="stat-info">
                    <h6>Prescriptions</h6>
                    <h2><?php echo number_format($stats['totalPrescriptions'] ?? 0); ?></h2>
                    <small><?php echo $stats['prescriptionsToday'] ?? 0; ?> today</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Inventory, Pharmacy, Lab -->
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-teal text-white">
                <div class="stat-icon"><i class="fas fa-prescription-bottle"></i></div>
                <div class="stat-info">
                    <h6>Pharmacy Sales</h6>
                    <h2>৳ <?php echo number_format($stats['pharmacySales'] ?? 0, 0); ?></h2>
                    <small><?php echo $stats['pharmacyItems'] ?? 0; ?> items sold</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-secondary text-white">
                <div class="stat-icon"><i class="fas fa-microscope"></i></div>
                <div class="stat-info">
                    <h6>Lab Tests</h6>
                    <h2><?php echo number_format($stats['labTests'] ?? 0); ?></h2>
                    <small><?php echo $stats['pendingTests'] ?? 0; ?> pending</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-dark text-white">
                <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                <div class="stat-info">
                    <h6>Inventory Items</h6>
                    <h2><?php echo number_format($stats['inventoryItems'] ?? 0); ?></h2>
                    <small><span class="text-warning"><?php echo $stats['lowStock'] ?? 0; ?></span> low stock</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-warning text-white">
                <div class="stat-icon"><i class="fas fa-calendar-warning"></i></div>
                <div class="stat-info">
                    <h6>Expiring Soon</h6>
                    <h2><?php echo number_format($stats['expiringSoon'] ?? 0); ?></h2>
                    <small>Within 30 days</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-queue text-white">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-info">
                    <h6>Queue Status</h6>
                    <h2><?php echo number_format(($stats['queueWaiting'] ?? 0) + ($stats['queueInProgress'] ?? 0)); ?></h2>
                    <small><span class="text-warning"><?php echo $stats['queueWaiting'] ?? 0; ?></span> waiting | <span class="text-success"><?php echo $stats['queueInProgress'] ?? 0; ?></span> in progress</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-blue text-white">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h6>Checked In</h6>
                    <h2><?php echo number_format($stats['checkedIn'] ?? 0); ?></h2>
                    <small>Patients in waiting</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin: Detailed Summary -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-chart-simple text-primary me-2"></i>Appointment Status</h6>
                    <a href="<?php echo BASE_URL; ?>/reception/appointments" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['statusData'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Status</th><th>Count</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($stats['statusData'] as $status => $count): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $status == 'Completed' ? 'success' : 
                                                ($status == 'Canceled' ? 'danger' : 
                                                ($status == 'In progress' ? 'warning' : 
                                                ($status == 'Checked in' ? 'info' : 'primary'))); 
                                        ?>"><?php echo $status; ?></span>
                                    </td>
                                    <td><strong><?php echo number_format($count); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                        <p>No status data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-credit-card text-success me-2"></i>Payment Status</h6>
                    <a href="<?php echo BASE_URL; ?>/bills" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['paymentData'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Status</th><th>Count</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($stats['paymentData'] as $status => $count): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $status == 'Paid' ? 'success' : 
                                                ($status == 'Pending' ? 'danger' : 
                                                ($status == 'Partial' ? 'warning' : 'secondary')); 
                                        ?>"><?php echo $status; ?></span>
                                    </td>
                                    <td><strong><?php echo number_format($count); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                        <p>No payment data available</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin: Recent Appointments & Bills -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Recent Appointments</h6>
                    <a href="<?php echo BASE_URL; ?>/reception/appointments" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['recentAppointments'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Patient</th><th>Doctor</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxItems = 8;
                                $counter = 0;
                                foreach($stats['recentAppointments'] as $apt): 
                                    if($counter >= $maxItems) break;
                                    $counter++;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown'); ?></strong></td>
                                    <td>Dr. <?php echo htmlspecialchars($apt['doctor_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $apt['status'] == 'completed' ? 'success' : 
                                                ($apt['status'] == 'canceled' ? 'danger' : 
                                                ($apt['status'] == 'in_progress' ? 'warning' : 'primary')); 
                                        ?>"><?php echo ucfirst(str_replace('_', ' ', $apt['status'] ?? 'pending')); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <p>No recent appointments</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-file-invoice text-success me-2"></i>Recent Bills</h6>
                    <a href="<?php echo BASE_URL; ?>/bills" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['recentBills'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Patient</th><th>Amount</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxItems = 8;
                                $counter = 0;
                                foreach($stats['recentBills'] as $bill): 
                                    if($counter >= $maxItems) break;
                                    $counter++;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($bill['patient_name'] ?? 'Unknown'); ?></td>
                                    <td><strong>৳ <?php echo number_format($bill['total_amount'] ?? 0, 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $bill['payment_status'] == 'paid' ? 'success' : 
                                                ($bill['payment_status'] == 'pending' ? 'danger' : 'warning'); 
                                        ?>"><?php echo ucfirst($bill['payment_status'] ?? 'pending'); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <p>No recent bills</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== DOCTOR DASHBOARD ====== -->
    <?php if ($isDoctor): ?>
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-primary text-white">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h6>My Patients</h6>
                    <h2><?php echo number_format($stats['totalPatients'] ?? 0); ?></h2>
                    <small>Total assigned</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-success text-white">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h6>Today's Appointments</h6>
                    <h2><?php echo number_format($stats['todayAppointments'] ?? 0); ?></h2>
                    <small><?php echo $stats['morningAppointments'] ?? 0; ?> Morning | <?php echo $stats['eveningAppointments'] ?? 0; ?> Evening</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-danger text-white">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h6>Pending</h6>
                    <h2><?php echo number_format($stats['pendingAppointments'] ?? 0); ?></h2>
                    <small>Awaiting consultation</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-teal text-white">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h6>Completed</h6>
                    <h2><?php echo number_format($stats['completedAppointments'] ?? 0); ?></h2>
                    <small>Today's consultations</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Doctor: Recent Appointments & Prescriptions -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Recent Appointments</h6>
                    <a href="<?php echo BASE_URL; ?>/reception/appointments" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['recentAppointments'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Patient</th><th>Date</th><th>Time</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxItems = 8;
                                $counter = 0;
                                foreach($stats['recentAppointments'] as $apt): 
                                    if($counter >= $maxItems) break;
                                    $counter++;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown'); ?></strong></td>
                                    <td><?php echo date('d M Y', strtotime($apt['appointment_date'] ?? 'now')); ?></td>
                                    <td><?php echo date('h:i A', strtotime($apt['start_time'] ?? '00:00:00')); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $apt['status'] == 'completed' ? 'success' : 
                                                ($apt['status'] == 'canceled' ? 'danger' : 'primary'); 
                                        ?>"><?php echo ucfirst(str_replace('_', ' ', $apt['status'] ?? 'pending')); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><p>No recent appointments</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-prescription text-success me-2"></i>Recent Prescriptions</h6>
                    <a href="<?php echo BASE_URL; ?>/prescription/list" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['recentPrescriptions'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Patient</th><th>Date</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxItems = 8;
                                $counter = 0;
                                foreach($stats['recentPrescriptions'] as $rx): 
                                    if($counter >= $maxItems) break;
                                    $counter++;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($rx['patient_name'] ?? 'Unknown'); ?></strong></td>
                                    <td><?php echo date('d M Y', strtotime($rx['prescription_date'] ?? 'now')); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $rx['status'] == 'issued' ? 'primary' : 
                                                ($rx['status'] == 'completed' ? 'success' : 
                                                ($rx['status'] == 'dispensed' ? 'info' : 'secondary')); 
                                        ?>"><?php echo ucfirst($rx['status'] ?? 'draft'); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><p>No recent prescriptions</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== PHARMACIST DASHBOARD ====== -->
    <?php if ($isPharmacist): ?>
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-success text-white">
                <div class="stat-icon"><i class="fas fa-cash-register"></i></div>
                <div class="stat-info">
                    <h6>Today's Sales</h6>
                    <h2><?php echo number_format($stats['todaySalesCount'] ?? 0); ?></h2>
                    <small>৳ <?php echo number_format($stats['todaySales'] ?? 0, 0); ?> revenue</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-danger text-white">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <h6>Today's Revenue</h6>
                    <h2>৳ <?php echo number_format($stats['todayRevenue'] ?? 0, 0); ?></h2>
                    <small>From all sources</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-warning text-white">
                <div class="stat-icon"><i class="fas fa-prescription"></i></div>
                <div class="stat-info">
                    <h6>Pending Prescriptions</h6>
                    <h2><?php echo number_format($stats['pendingPrescriptions'] ?? 0); ?></h2>
                    <small>Awaiting dispensing</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-danger text-white">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <h6>Low Stock Items</h6>
                    <h2><?php echo number_format($stats['lowStock'] ?? 0); ?></h2>
                    <small>Need reorder</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pharmacist: Additional Stats -->
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-info text-white">
                <div class="stat-icon"><i class="fas fa-tablets"></i></div>
                <div class="stat-info">
                    <h6>Total Medicines</h6>
                    <h2><?php echo number_format($stats['totalMedicines'] ?? 0); ?></h2>
                    <small>Active inventory</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-warning text-white">
                <div class="stat-icon"><i class="fas fa-calendar-warning"></i></div>
                <div class="stat-info">
                    <h6>Expiring Soon</h6>
                    <h2><?php echo number_format($stats['expiringSoon'] ?? 0); ?></h2>
                    <small>Within 30 days</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-teal text-white">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h6>Dispensed Today</h6>
                    <h2><?php echo number_format($stats['dispensedToday'] ?? 0); ?></h2>
                    <small>Prescriptions dispensed</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-purple text-white">
                <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                <div class="stat-info">
                    <h6>Stock Value</h6>
                    <h2>৳ <?php echo number_format($stats['stockValue'] ?? 0, 0); ?></h2>
                    <small>Total inventory value</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Pharmacist: Recent Sales & Prescription Status -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-shopping-cart text-primary me-2"></i>Recent Sales</h6>
                    <a href="<?php echo BASE_URL; ?>/pharmacy/sales" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['recentSales'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Patient</th><th>Amount</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxItems = 8;
                                $counter = 0;
                                foreach($stats['recentSales'] as $sale): 
                                    if($counter >= $maxItems) break;
                                    $counter++;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sale['patient_name'] ?? 'Walk-in'); ?></td>
                                    <td><strong>৳ <?php echo number_format($sale['total_amount'] ?? 0, 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo ($sale['status'] ?? '') == 'completed' ? 'success' : 
                                                (($sale['status'] ?? '') == 'canceled' ? 'danger' : 'warning'); 
                                        ?>"><?php echo ucfirst($sale['status'] ?? 'pending'); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><p>No recent sales</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-prescription text-success me-2"></i>Prescription Status</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['prescriptionStatus'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Status</th><th>Count</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($stats['prescriptionStatus'] as $status => $count): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $status == 'Dispensed' ? 'success' : 
                                                ($status == 'Ready' ? 'info' : 
                                                ($status == 'Processing' ? 'warning' : 'secondary')); 
                                        ?>"><?php echo $status; ?></span>
                                    </td>
                                    <td><strong><?php echo number_format($count); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><p>No prescription data</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== LAB TECHNICIAN DASHBOARD ====== -->
    <?php if ($isLabTech): ?>
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-primary text-white">
                <div class="stat-icon"><i class="fas fa-flask"></i></div>
                <div class="stat-info">
                    <h6>Total Orders</h6>
                    <h2><?php echo number_format($stats['totalOrders'] ?? 0); ?></h2>
                    <small>All time</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-warning text-white">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <h6>Pending</h6>
                    <h2><?php echo number_format($stats['pendingOrders'] ?? 0); ?></h2>
                    <small>Awaiting processing</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-info text-white">
                <div class="stat-icon"><i class="fas fa-spinner"></i></div>
                <div class="stat-info">
                    <h6>In Progress</h6>
                    <h2><?php echo number_format($stats['inProgress'] ?? 0); ?></h2>
                    <small>Being processed</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-success text-white">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h6>Completed</h6>
                    <h2><?php echo number_format($stats['completedToday'] ?? 0); ?></h2>
                    <small>Today</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-danger text-white">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <h6>STAT Orders</h6>
                    <h2><?php echo number_format($stats['statOrders'] ?? 0); ?></h2>
                    <small>Urgent priority</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="stat-card bg-gradient-teal text-white">
                <div class="stat-icon"><i class="fas fa-vial"></i></div>
                <div class="stat-info">
                    <h6>Samples Collected</h6>
                    <h2><?php echo number_format($stats['samplesCollected'] ?? 0); ?></h2>
                    <small>Today</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab Tech: Recent Orders & Order Status -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-flask text-primary me-2"></i>Recent Orders</h6>
                    <a href="<?php echo BASE_URL; ?>/lab/orders" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['recentOrders'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Order #</th><th>Patient</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxItems = 8;
                                $counter = 0;
                                foreach($stats['recentOrders'] as $order): 
                                    if($counter >= $maxItems) break;
                                    $counter++;
                                ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($order['order_number'] ?? 'N/A'); ?></span></td>
                                    <td><?php echo htmlspecialchars($order['patient_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $order['status'] == 'completed' ? 'success' : 
                                                ($order['status'] == 'ordered' ? 'warning' : 
                                                ($order['status'] == 'sample_collected' ? 'info' : 
                                                ($order['status'] == 'processing' ? 'primary' : 'secondary'))); 
                                        ?>"><?php echo ucfirst(str_replace('_', ' ', $order['status'] ?? 'ordered')); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><p>No recent orders</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-chart-simple text-success me-2"></i>Order Status Summary</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['orderStatus'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Status</th><th>Count</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($stats['orderStatus'] as $status => $count): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $status == 'Completed' ? 'success' : 
                                                ($status == 'Delivered' ? 'info' : 
                                                ($status == 'Ordered' ? 'warning' : 
                                                ($status == 'Sample collected' ? 'primary' : 'secondary'))); 
                                        ?>"><?php echo $status; ?></span>
                                    </td>
                                    <td><strong><?php echo number_format($count); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><p>No order data</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== ACCOUNTANT DASHBOARD ====== -->
    <?php if ($isAccountant): ?>
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-danger text-white">
                <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                <div class="stat-info">
                    <h6>Today's Revenue</h6>
                    <h2>৳ <?php echo number_format($stats['todayRevenue'] ?? 0, 0); ?></h2>
                    <small><i class="fas fa-arrow-up me-1"></i> <?php echo $stats['todayTransactions'] ?? 0; ?> transactions</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-primary text-white">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <h6>Monthly Revenue</h6>
                    <h2>৳ <?php echo number_format($stats['monthlyRevenue'] ?? 0, 0); ?></h2>
                    <small><?php echo date('F Y'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-warning text-white">
                <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="stat-info">
                    <h6>Pending Bills</h6>
                    <h2><?php echo number_format($stats['pendingBills'] ?? 0); ?></h2>
                    <small>Due: ৳ <?php echo number_format($stats['totalDue'] ?? 0, 0); ?></small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-success text-white">
                <div class="stat-icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <div class="stat-info">
                    <h6>Total Bills</h6>
                    <h2><?php echo number_format($stats['totalBills'] ?? 0); ?></h2>
                    <small>All time</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Accountant: Recent Transactions & Payment Status -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-history text-primary me-2"></i>Recent Transactions</h6>
                    <a href="<?php echo BASE_URL; ?>/bills/payments" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['recentTransactions'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Patient</th><th>Amount</th><th>Method</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxItems = 8;
                                $counter = 0;
                                foreach($stats['recentTransactions'] as $txn): 
                                    if($counter >= $maxItems) break;
                                    $counter++;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($txn['patient_name'] ?? 'Unknown'); ?></td>
                                    <td><strong>৳ <?php echo number_format($txn['amount'] ?? 0, 2); ?></strong></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo ucfirst($txn['payment_method'] ?? 'cash'); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><p>No recent transactions</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-credit-card text-success me-2"></i>Payment Status Summary</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['paymentData'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Status</th><th>Count</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach($stats['paymentData'] as $status => $count): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $status == 'Paid' ? 'success' : 
                                                ($status == 'Pending' ? 'danger' : 
                                                ($status == 'Partial' ? 'warning' : 'secondary')); 
                                        ?>"><?php echo $status; ?></span>
                                    </td>
                                    <td><strong><?php echo number_format($count); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><p>No payment data</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== NURSE DASHBOARD ====== -->
    <?php if ($isNurse): ?>
    <div class="row g-2 g-md-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-primary text-white">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h6>Total Patients</h6>
                    <h2><?php echo number_format($stats['totalPatients'] ?? 0); ?></h2>
                    <small>All time</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-success text-white">
                <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h6>Today's Appointments</h6>
                    <h2><?php echo number_format($stats['todayAppointments'] ?? 0); ?></h2>
                    <small><?php echo $stats['morningAppointments'] ?? 0; ?> Morning | <?php echo $stats['eveningAppointments'] ?? 0; ?> Evening</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-info text-white">
                <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                <div class="stat-info">
                    <h6>Checked In</h6>
                    <h2><?php echo number_format($stats['checkedIn'] ?? 0); ?></h2>
                    <small>Today</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-3 col-xl-3">
            <div class="stat-card bg-gradient-teal text-white">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h6>Completed</h6>
                    <h2><?php echo number_format($stats['completedToday'] ?? 0); ?></h2>
                    <small>Today</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Nurse: Today's Appointments -->
    <div class="row g-4 mb-4">
        <div class="col-md-12">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-calendar-alt text-primary me-2"></i>Today's Appointments</h6>
                    <a href="<?php echo BASE_URL; ?>/reception/appointments" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($stats['recentAppointments'])): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Patient</th><th>Doctor</th><th>Time</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $maxItems = 10;
                                $counter = 0;
                                foreach($stats['recentAppointments'] as $apt): 
                                    if($counter >= $maxItems) break;
                                    $counter++;
                                    if ($apt['appointment_date'] != date('Y-m-d')) continue;
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($apt['patient_name'] ?? 'Unknown'); ?></strong></td>
                                    <td>Dr. <?php echo htmlspecialchars($apt['doctor_name'] ?? 'Unknown'); ?></td>
                                    <td><?php echo date('h:i A', strtotime($apt['start_time'] ?? '00:00:00')); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $apt['status'] == 'completed' ? 'success' : 
                                                ($apt['status'] == 'checked_in' ? 'info' : 
                                                ($apt['status'] == 'in_progress' ? 'warning' : 'primary')); 
                                        ?>"><?php echo ucfirst(str_replace('_', ' ', $apt['status'] ?? 'scheduled')); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-4"><p>No appointments today</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
    /* ============================================================ */
    /* STAT CARDS - Advanced Styling */
    /* ============================================================ */
    .stat-card {
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        cursor: default;
        height: 100%;
        min-height: 80px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
        pointer-events: none;
    }
    .stat-card::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255,255,255,0.05);
        pointer-events: none;
    }
    .stat-card:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    }
    .stat-card .stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        background: rgba(255,255,255,0.2);
        flex-shrink: 0;
        position: relative;
        z-index: 1;
    }
    .stat-card .stat-info {
        flex: 1;
        min-width: 0;
        position: relative;
        z-index: 1;
    }
    .stat-card .stat-info h6 {
        font-size: 10px;
        margin-bottom: 2px;
        opacity: 0.85;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .stat-card .stat-info h2 {
        font-size: 22px;
        font-weight: 700;
        margin: 0;
        line-height: 1.2;
    }
    .stat-card .stat-info small {
        font-size: 10px;
        opacity: 0.8;
        display: block;
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* ============================================================ */
    /* GRADIENTS */
    /* ============================================================ */
    .bg-gradient-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .bg-gradient-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .bg-gradient-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .bg-gradient-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .bg-gradient-danger { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); }
    .bg-gradient-teal { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .bg-gradient-secondary { background: linear-gradient(135deg, #a8a8a8 0%, #636363 100%); }
    .bg-gradient-dark { background: linear-gradient(135deg, #2d3436 0%, #000000 100%); }
    .bg-gradient-purple { background: linear-gradient(135deg, #a29bfe 0%, #6c5ce7 100%); }
    .bg-gradient-pink { background: linear-gradient(135deg, #fd746c 0%, #ff9068 100%); }
    .bg-gradient-orange { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
    .bg-gradient-blue { background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%); }
    .bg-gradient-money { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); }
    .bg-gradient-calendar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .bg-gradient-queue { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    
    /* ============================================================ */
    /* CARD STYLES */
    /* ============================================================ */
    .card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        transition: box-shadow 0.2s;
    }
    .card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }
    .card-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 10px 16px;
        background: white;
        border-radius: 12px 12px 0 0 !important;
    }
    .card-header h6 {
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    .btn-link {
        font-size: 11px;
        color: #3b82f6;
        text-decoration: none;
        padding: 0;
    }
    .btn-link:hover {
        color: #2563eb;
        text-decoration: underline;
    }
    
    /* ============================================================ */
    /* TABLE STYLES */
    /* ============================================================ */
    .table th, .table td {
        font-size: 12px;
        padding: 6px 12px;
        vertical-align: middle;
    }
    .table thead th {
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #e2e8f0;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .table tbody tr:hover {
        background: #f8fafc;
    }
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    /* ============================================================ */
    /* QUICK ACTIONS BUTTONS */
    /* ============================================================ */
    .btn-outline-primary.btn-sm,
    .btn-outline-success.btn-sm,
    .btn-outline-info.btn-sm,
    .btn-outline-warning.btn-sm,
    .btn-outline-danger.btn-sm,
    .btn-outline-secondary.btn-sm,
    .btn-outline-dark.btn-sm {
        font-size: 11px;
        padding: 4px 12px;
        border-radius: 6px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-outline-primary.btn-sm:hover,
    .btn-outline-success.btn-sm:hover,
    .btn-outline-info.btn-sm:hover,
    .btn-outline-warning.btn-sm:hover,
    .btn-outline-danger.btn-sm:hover,
    .btn-outline-secondary.btn-sm:hover,
    .btn-outline-dark.btn-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    /* ============================================================ */
    /* ANIMATIONS */
    /* ============================================================ */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .stat-card {
        animation: fadeInUp 0.5s ease forwards;
        animation-delay: calc(var(--i, 0) * 0.05s);
    }
    .stat-card:nth-child(1) { --i: 0; }
    .stat-card:nth-child(2) { --i: 1; }
    .stat-card:nth-child(3) { --i: 2; }
    .stat-card:nth-child(4) { --i: 3; }
    .stat-card:nth-child(5) { --i: 4; }
    .stat-card:nth-child(6) { --i: 5; }
    .stat-card:nth-child(7) { --i: 6; }
    .stat-card:nth-child(8) { --i: 7; }
    .stat-card:nth-child(9) { --i: 8; }
    .stat-card:nth-child(10) { --i: 9; }
    .stat-card:nth-child(11) { --i: 10; }
    .stat-card:nth-child(12) { --i: 11; }
    
    /* ============================================================ */
    /* RESPONSIVE */
    /* ============================================================ */
    @media (max-width: 768px) {
        .stat-card .stat-info h2 { font-size: 18px; }
        .stat-card .stat-info h6 { font-size: 9px; }
        .stat-card .stat-icon { width: 36px; height: 36px; font-size: 14px; }
        .stat-card { padding: 10px 12px; min-height: 60px; gap: 10px; }
        .btn-outline-primary.btn-sm,
        .btn-outline-success.btn-sm,
        .btn-outline-info.btn-sm,
        .btn-outline-warning.btn-sm,
        .btn-outline-danger.btn-sm,
        .btn-outline-secondary.btn-sm,
        .btn-outline-dark.btn-sm {
            font-size: 10px;
            padding: 3px 10px;
        }
        .card-header h6 { font-size: 12px; }
        .table th, .table td { font-size: 10px; padding: 4px 8px; }
    }
    @media (max-width: 576px) {
        .stat-card .stat-info h2 { font-size: 15px; }
        .stat-card .stat-info h6 { font-size: 8px; }
        .stat-card .stat-icon { width: 30px; height: 30px; font-size: 12px; }
        .stat-card { padding: 8px 10px; min-height: 50px; gap: 8px; }
        .stat-card .stat-info small { font-size: 8px; }
        .btn-outline-primary.btn-sm,
        .btn-outline-success.btn-sm,
        .btn-outline-info.btn-sm,
        .btn-outline-warning.btn-sm,
        .btn-outline-danger.btn-sm,
        .btn-outline-secondary.btn-sm,
        .btn-outline-dark.btn-sm {
            font-size: 9px;
            padding: 2px 8px;
        }
        .col-6 { flex: 0 0 50%; max-width: 50%; }
        .col-sm-4 { flex: 0 0 50%; max-width: 50%; }
    }
</style>

<script>
    // ============================================================ //
    // LIVE CLOCK UPDATE
    // ============================================================ //
    (function() {
        var clockElement = document.getElementById('live-time');
        if (clockElement) {
            function updateClock() {
                var now = new Date();
                var time = now.toLocaleTimeString('en-US', { 
                    hour12: true,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                clockElement.innerHTML = '<i class="fas fa-clock me-1"></i> ' + time;
            }
            updateClock();
            setInterval(updateClock, 1000);
        }
    })();
    
    // ============================================================ //
    // DASHBOARD REFRESH (AJAX)
    // ============================================================ //
    function refreshDashboard() {
        var btn = document.querySelector('[onclick="refreshDashboard();"]');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
            btn.disabled = true;
        }
        
        // Get base URL dynamically
        var baseUrl = window.location.origin + window.location.pathname.replace(/\/admin\/dashboard.*$/, '');
        var apiUrl = baseUrl + '/dashboard/api-stats';
        
        fetch(apiUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to refresh dashboard: ' + (data.message || 'Unknown error'));
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                    btn.disabled = false;
                }
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            // Fallback: reload the page directly
            location.reload();
        });
    }
    
    // ============================================================ //
    // AUTO-REFRESH EVERY 5 MINUTES
    // ============================================================ //
    setTimeout(function() {
        if (document.hidden) return;
        if (!document.querySelector('.stat-card')) return;
        refreshDashboard();
    }, 300000); // 5 minutes
</script>