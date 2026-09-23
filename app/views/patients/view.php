<?php
// Data from controller: $patient
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; }
        .content-wrapper { padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 1px solid #e9ecef; padding: 20px; border-radius: 15px 15px 0 0; }
        .info-label { font-weight: 600; color: #555; width: 150px; display: inline-block; }
        .info-value { color: #333; }
        .info-row { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; }
        .profile-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .profile-icon i { font-size: 50px; color: white; }
        .blood-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
        .blood-Ap { background: #e8f5e9; color: #2e7d32; }
        .blood-Am { background: #ffebee; color: #c62828; }
        .blood-Bp { background: #e3f2fd; color: #1565c0; }
        .blood-Bm { background: #ffebee; color: #c62828; }
        .blood-Op { background: #fff3e0; color: #e65100; }
        .blood-Om { background: #ffebee; color: #c62828; }
        .blood-ABp { background: #f3e5f5; color: #6a1b9a; }
        .blood-ABm { background: #ffebee; color: #c62828; }
        .stat-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-box h4 { margin: 0; font-size: 24px; font-weight: 700; }
        .stat-box small { color: #6c757d; font-size: 12px; }
        
        /* Tabs styling */
        .nav-tabs .nav-link {
            color: #475569;
            font-weight: 500;
            border: none;
            padding: 12px 20px;
            border-radius: 0;
            border-bottom: 3px solid transparent;
        }
        .nav-tabs .nav-link:hover {
            color: #1e293b;
            background: #f8fafc;
        }
        .nav-tabs .nav-link.active {
            color: #2563eb;
            background: transparent;
            border-bottom: 3px solid #2563eb;
        }
        .nav-tabs .nav-link i {
            margin-right: 8px;
        }
        
        /* Vaccine table styling */
        .vaccine-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .vaccine-table thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; padding: 8px 10px; border-bottom: 2px solid #e2e8f0; text-align: left; position: sticky; top: 0; z-index: 2; }
        .vaccine-table tbody td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .vaccine-table tbody tr:hover { background: #f8fafc; }
        .scrollable-table { max-height: 300px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; }
        .scrollable-table::-webkit-scrollbar { width: 4px; }
        .scrollable-table::-webkit-scrollbar-track { background: #f1f5f9; }
        .scrollable-table::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        
        .vaccine-image { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; }
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; }
        .status-given { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }
        .empty-state h6 { color: #64748b; font-weight: 600; }
        
        .btn-action { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; }
        .btn-action:hover { background: #e2e8f0; }
        .btn-action.primary { background: #dbeafe; color: #2563eb; border-color: #bfdbfe; }
        .btn-action.primary:hover { background: #2563eb; color: white; }
        .btn-action.success { background: #d1fae5; color: #059669; border-color: #a7f3d0; }
        .btn-action.success:hover { background: #059669; color: white; }
        
        /* Prescription status badges */
        .prescription-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
        }
        .prescription-badge.issued { background: #dbeafe; color: #2563eb; }
        .prescription-badge.dispensed { background: #d1fae5; color: #059669; }
        .prescription-badge.completed { background: #d1fae5; color: #059669; }
        .prescription-badge.canceled { background: #fee2e2; color: #dc2626; }
        .prescription-badge.draft { background: #e2e8f0; color: #475569; }

        /* Prescription status badges */
.prescription-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 500;
}
.prescription-badge.issued { background: #dbeafe; color: #2563eb; }
.prescription-badge.dispensed { background: #d1fae5; color: #059669; }
.prescription-badge.completed { background: #d1fae5; color: #059669; }
.prescription-badge.canceled { background: #fee2e2; color: #dc2626; }
.prescription-badge.draft { background: #e2e8f0; color: #475569; display: none; } /* Hide draft badges */
    </style>
</head>
<body>
    <div class="content-wrapper">
        <div class="container">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fas fa-user-circle me-2 text-primary"></i> Patient Details</h4>
                <div>
                    <a href="/unidia/public/patient/list" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-2"></i> Back to List
                    </a>
                    <a href="/unidia/public/patient/id-card?id=<?php echo $patient['id']; ?>" class="btn btn-info btn-sm text-white">
                        <i class="fas fa-id-card me-2"></i> ID Card
                    </a>
                    <a href="<?php echo BASE_URL; ?>/prescriptions/create?patient_id=<?php echo $patient['id']; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-prescription me-2"></i> Prescription
                    </a>
                    <a href="/unidia/public/patient/edit?id=<?php echo $patient['id']; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit me-2"></i> Edit
                    </a>
                </div>
            </div>
            
            <!-- Patient Information Card -->
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="profile-icon">
                                <i class="fas fa-user-circle"></i>
                            </div>
                        </div>
                        <div class="col-md-10">
                            <h2 class="mb-1"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h2>
                            <p class="text-muted mb-2">
                                <span class="badge bg-primary me-2"><?php echo $patient['patient_code']; ?></span>
                                <?php if ($patient['patient_id_card_number']): ?>
                                    <span class="badge bg-secondary">Card: <?php echo $patient['patient_id_card_number']; ?></span>
                                <?php endif; ?>
                            </p>
                            <div class="mt-2">
                                <?php if ($patient['blood_group']): ?>
                                    <span class="blood-badge blood-<?php echo str_replace('+', 'p', str_replace('-', 'm', $patient['blood_group'])); ?> me-2">
                                        <i class="fas fa-tint me-1"></i> Blood: <?php echo $patient['blood_group']; ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($patient['status'] == 'active'): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i> Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs Navigation -->
                <div class="card-body p-0">
                    <ul class="nav nav-tabs px-3 pt-2" id="patientTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                                <i class="fas fa-info-circle"></i> Information
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="vaccines-tab" data-bs-toggle="tab" data-bs-target="#vaccines" type="button" role="tab">
                                <i class="fas fa-syringe"></i> Vaccines
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button" role="tab">
                                <i class="fas fa-prescription"></i> Prescriptions
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="appointments-tab" data-bs-toggle="tab" data-bs-target="#appointments" type="button" role="tab">
                                <i class="fas fa-calendar-check"></i> Appointments
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content p-4">
                        <!-- ============================================================ -->
                        <!-- TAB 1: INFORMATION -->
                        <!-- ============================================================ -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i> Personal Information</h6>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Patient ID:</span>
                                        <span class="info-value"><?php echo $patient['patient_code']; ?></span>
                                    </div>
                                    
                                    <?php if ($patient['patient_id_card_number']): ?>
                                    <div class="info-row">
                                        <span class="info-label">Card Number:</span>
                                        <span class="info-value"><?php echo $patient['patient_id_card_number']; ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Full Name:</span>
                                        <span class="info-value"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></span>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Phone:</span>
                                        <span class="info-value">
                                            <a href="tel:<?php echo $patient['phone']; ?>"><?php echo $patient['phone']; ?></a>
                                        </span>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Email:</span>
                                        <span class="info-value">
                                            <?php if ($patient['email']): ?>
                                                <a href="mailto:<?php echo $patient['email']; ?>"><?php echo $patient['email']; ?></a>
                                            <?php else: ?>
                                                <span class="text-muted">Not provided</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3"><i class="fas fa-heartbeat me-2"></i> Medical Information</h6>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Gender:</span>
                                        <span class="info-value">
                                            <?php if ($patient['gender']): ?>
                                                <i class="fas fa-<?php echo $patient['gender'] == 'male' ? 'mars' : ($patient['gender'] == 'female' ? 'venus' : 'genderless'); ?> me-1"></i>
                                                <?php echo ucfirst($patient['gender']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Date of Birth:</span>
                                        <span class="info-value">
                                            <?php if ($patient['date_of_birth']): ?>
                                                <?php echo date('d F Y', strtotime($patient['date_of_birth'])); ?>
                                                <?php 
                                                    $dob = new DateTime($patient['date_of_birth']);
                                                    $today = new DateTime();
                                                    $age = $today->diff($dob)->y;
                                                    echo "<small class='text-muted'> ($age years)</small>";
                                                ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not specified</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Blood Group:</span>
                                        <span class="info-value"><?php echo $patient['blood_group'] ?: '<span class="text-muted">Not specified</span>'; ?></span>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Registration Date:</span>
                                        <span class="info-value"><?php echo date('d F Y', strtotime($patient['registration_date'])); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="text-primary mb-3"><i class="fas fa-map-marker-alt me-2"></i> Address Information</h6>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Address:</span>
                                        <span class="info-value"><?php echo nl2br(htmlspecialchars($patient['address'] ?? 'Not provided')); ?></span>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="info-row">
                                                <span class="info-label">Thana:</span>
                                                <span class="info-value"><?php echo $patient['thana'] ?: '<span class="text-muted">Not specified</span>'; ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-row">
                                                <span class="info-label">District:</span>
                                                <span class="info-value"><?php echo $patient['district'] ?: '<span class="text-muted">Not specified</span>'; ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-row">
                                                <span class="info-label">Division:</span>
                                                <span class="info-value"><?php echo $patient['division'] ?: '<span class="text-muted">Not specified</span>'; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($patient['emergency_contact_name'] || $patient['emergency_contact_phone']): ?>
                            <hr>
                            <div class="row">
                                <div class="col-md-12">
                                    <h6 class="text-primary mb-3"><i class="fas fa-ambulance me-2"></i> Emergency Contact</h6>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="info-row">
                                                <span class="info-label">Contact Person:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($patient['emergency_contact_name'] ?? 'Not provided'); ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-row">
                                                <span class="info-label">Contact Number:</span>
                                                <span class="info-value"><?php echo $patient['emergency_contact_phone'] ?? 'Not provided'; ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="info-row">
                                                <span class="info-label">Relation:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($patient['emergency_contact_relation'] ?? 'Not provided'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Quick Actions -->
                            <div class="row mt-4">
                                <div class="col-md-4">
                                    <div class="card text-center h-100">
                                        <div class="card-body">
                                            <i class="fas fa-calendar-plus fa-3x text-primary mb-3"></i>
                                            <h6>Book Appointment</h6>
                                            <p class="small text-muted">Schedule a new appointment</p>
                                            <button class="btn btn-sm btn-primary" onclick="bookAppointment(<?php echo $patient['id']; ?>)">
                                                Book Now
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-center h-100">
                                        <div class="card-body">
                                            <i class="fas fa-prescription-bottle fa-3x text-success mb-3"></i>
                                            <h6>New Prescription</h6>
                                            <p class="small text-muted">Create a new prescription</p>
                                            <button class="btn btn-sm btn-success" onclick="createPrescription(<?php echo $patient['id']; ?>)">
                                                Create Prescription
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card text-center h-100">
                                        <div class="card-body">
                                            <i class="fas fa-flask fa-3x text-info mb-3"></i>
                                            <h6>Lab Test</h6>
                                            <p class="small text-muted">Order laboratory tests</p>
                                            <button class="btn btn-sm btn-info text-white" onclick="orderLabTest(<?php echo $patient['id']; ?>)">
                                                Order Test
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ============================================================ -->
                        <!-- TAB 2: VACCINES -->
                        <!-- ============================================================ -->
                        <div class="tab-pane fade" id="vaccines" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0"><i class="fas fa-syringe me-2"></i> Vaccination Records</h6>
                                <div>
                                    <a href="<?php echo BASE_URL; ?>/vaccines?patient_id=<?php echo $patient['id']; ?>" class="btn-action primary me-2">
                                        <i class="fas fa-list"></i> View All
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/vaccines/create?patient_id=<?php echo $patient['id']; ?>" class="btn-action success">
                                        <i class="fas fa-plus"></i> Add Vaccine
                                    </a>
                                </div>
                            </div>
                            
                            <div id="vaccineListContainer">
                                <!-- Vaccines will be loaded via AJAX -->
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2 text-muted">Loading vaccines...</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ============================================================ -->
                        <!-- TAB 3: PRESCRIPTIONS - ONLY FULL PRESCRIPTIONS -->
                        <!-- ============================================================ -->
                        <div class="tab-pane fade" id="prescriptions" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0">
                                    <i class="fas fa-prescription me-2"></i> Prescription History
                                    <span class="badge bg-success ms-2" style="font-size:9px;">
                                        <i class="fas fa-check-circle"></i> Full Prescriptions Only
                                    </span>
                                </h6>
                                <a href="<?php echo BASE_URL; ?>/prescriptions/create?patient_id=<?php echo $patient['id']; ?>" class="btn-action success">
                                    <i class="fas fa-plus"></i> New Prescription
                                </a>
                            </div>
                            <div id="prescriptionListContainer">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2 text-muted">Loading prescriptions...</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ============================================================ -->
                        <!-- TAB 4: APPOINTMENTS -->
                        <!-- ============================================================ -->
                        <div class="tab-pane fade" id="appointments" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0"><i class="fas fa-calendar-check me-2"></i> Appointment History</h6>
                                <button class="btn-action success" onclick="bookAppointment(<?php echo $patient['id']; ?>)">
                                    <i class="fas fa-plus"></i> Book Appointment
                                </button>
                            </div>
                            <div id="appointmentListContainer">
                                <div class="text-center py-3">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <span class="ms-2 text-muted">Loading appointments...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const BASE_URL = '<?php echo defined('BASE_URL') ? BASE_URL : '/unidia/public'; ?>';
        const patientId = <?php echo $patient['id']; ?>;
        
        // ================================================================
// FUNCTION: Load Vaccines
// ================================================================
function loadVaccines() {
    const container = document.getElementById('vaccineListContainer');
    
    // Show loading
    container.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="ms-2 text-muted">Loading vaccines...</span>
        </div>
    `;
    
    fetch(BASE_URL + '/api/get-patient-vaccines?patient_id=' + patientId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Vaccines data:', data);
            
            if (data.success && data.data && data.data.length > 0) {
                let html = `
                    <div class="scrollable-table">
                        <table class="vaccine-table">
                            <thead>
                                <tr>
                                    <th>Vaccine</th>
                                    <th>Dose</th>
                                    <th>Date Given</th>
                                    <th>Next Due</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.data.forEach(function(vac) {
                    let status = 'pending';
                    let statusClass = 'status-pending';
                    let statusLabel = 'Pending';
                    
                    if (vac.date_given && vac.date_given !== '0000-00-00') {
                        status = 'given';
                        statusClass = 'status-given';
                        statusLabel = 'Given';
                    }
                    
                    if (vac.next_due && vac.next_due !== '0000-00-00') {
                        const today = new Date();
                        const dueDate = new Date(vac.next_due);
                        if (today > dueDate) {
                            status = 'overdue';
                            statusClass = 'status-overdue';
                            statusLabel = 'Overdue';
                        }
                    }
                    
                    const dateGiven = vac.date_given ? new Date(vac.date_given).toLocaleDateString('en-GB') : '-';
                    const nextDue = vac.next_due ? new Date(vac.next_due).toLocaleDateString('en-GB') : '-';
                    
                    html += `
                        <tr>
                            <td><strong>${escapeHtml(vac.vaccine_name)}</strong></td>
                            <td>${escapeHtml(vac.dose || '')}</td>
                            <td>${dateGiven}</td>
                            <td>${nextDue}</td>
                            <td><span class="status-badge ${statusClass}">${statusLabel}</span></td>
                            <td>
                                <a href="${BASE_URL}/vaccines/edit/${vac.id}?patient_id=${patientId}" class="btn-action primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2 text-end">
                                <a href="${BASE_URL}/vaccines?patient_id=${patientId}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-arrow-right me-1"></i> View All Vaccines
                                </a>
                                <a href="${BASE_URL}/vaccines/print-card/${patientId}" class="btn btn-sm btn-info text-white" target="_blank">
                                    <i class="fas fa-print me-1"></i> Print Card
                                </a>
                            </div>
                    `;
                    
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-syringe"></i>
                            <h6>No Vaccines Added</h6>
                            <p style="font-size:12px;">Click the "Add Vaccine" button to add a new vaccine for this patient.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading vaccines:', error);
                container.innerHTML = `
                    <div class="alert alert-danger py-2">
                        <i class="fas fa-exclamation-circle me-2"></i> Error loading vaccines. Please refresh.
                    </div>
                `;
            });
}
        
        // ================================================================
// FUNCTION: Load Prescriptions - ONLY FULL PRESCRIPTIONS
// ================================================================
function loadPrescriptions() {
    const container = document.getElementById('prescriptionListContainer');
    
    // Show loading
    container.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="ms-2 text-muted">Loading prescriptions...</span>
        </div>
    `;
    
    // Use the correct API endpoint
    fetch(BASE_URL + '/api/get-patient-prescriptions?patient_id=' + patientId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Prescriptions data:', data);
            
            // Filter out any draft prescriptions (double safety)
            let filteredData = [];
            if (data.success && data.data) {
                filteredData = data.data.filter(function(p) {
                    // Only keep non-draft prescriptions
                    return p.status !== 'draft';
                });
            }
            
            if (filteredData.length > 0) {
                let html = `
                    <div class="scrollable-table">
                        <table class="vaccine-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Prescription</th>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                filteredData.forEach(function(p, index) {
                    const statusColors = {
                        'issued': 'issued',
                        'dispensed': 'dispensed',
                        'completed': 'completed',
                        'canceled': 'canceled'
                    };
                    const statusClass = statusColors[p.status] || 'secondary';
                    
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${escapeHtml(p.prescription_number)}</strong></td>
                            <td>${escapeHtml(p.prescription_date)}</td>
                            <td>${escapeHtml(p.doctor_name || 'N/A')}</td>
                            <td><span class="badge bg-info">${p.item_count || 0}</span></td>
                            <td><span class="prescription-badge ${statusClass}">${escapeHtml(p.status)}</span></td>
                            <td>
                                <a href="${BASE_URL}/prescriptions/show/${p.id}" class="btn-action primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="${BASE_URL}/prescriptions/print/${p.id}" class="btn-action" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2 text-muted small">
                            <i class="fas fa-info-circle me-1"></i> Showing ${filteredData.length} full prescription(s)
                        </div>
                `;
                
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-prescription"></i>
                        <h6>No Full Prescriptions Found</h6>
                        <p style="font-size:12px;">
                            <i class="fas fa-info-circle text-primary me-1"></i> 
                            Only completed prescriptions are shown here. Draft prescriptions are hidden.
                        </p>
                        <a href="${BASE_URL}/prescriptions/create?patient_id=${patientId}" class="btn btn-success btn-sm mt-2">
                            <i class="fas fa-plus me-1"></i> Create New Prescription
                        </a>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading prescriptions:', error);
            container.innerHTML = `
                <div class="alert alert-danger py-2">
                    <i class="fas fa-exclamation-circle me-2"></i> Error loading prescriptions. Please refresh.
                    <br><small class="text-muted">${error.message}</small>
                </div>
            `;
        });
}
        
        // ================================================================
// FUNCTION: Load Appointments
// ================================================================
function loadAppointments() {
    const container = document.getElementById('appointmentListContainer');
    
    // Show loading
    container.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="ms-2 text-muted">Loading appointments...</span>
        </div>
    `;
    
    fetch(BASE_URL + '/api/get-patient-appointments?patient_id=' + patientId)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Appointments data:', data);
            
            if (data.success && data.data && data.data.length > 0) {
                let html = `
                    <div class="scrollable-table">
                        <table class="vaccine-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Appointment</th>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                data.data.forEach(function(app, index) {
                    const statusColors = {
                        'scheduled': 'secondary',
                        'confirmed': 'primary',
                        'checked_in': 'info',
                        'in_progress': 'warning',
                        'completed': 'success',
                        'canceled': 'danger',
                        'no_show': 'dark'
                    };
                    const statusColor = statusColors[app.status] || 'secondary';
                    
                    const paymentColors = {
                        'pending': 'warning',
                        'paid': 'success',
                        'partial': 'info',
                        'refunded': 'danger'
                    };
                    const paymentColor = paymentColors[app.payment_status] || 'secondary';
                    
                    html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${escapeHtml(app.appointment_number)}</strong></td>
                            <td>${new Date(app.appointment_date).toLocaleDateString('en-GB')}</td>
                            <td>${escapeHtml(app.doctor_name || 'N/A')}</td>
                            <td><span class="badge bg-${statusColor}">${escapeHtml(app.status)}</span></td>
                            <td><span class="badge bg-${paymentColor}">${escapeHtml(app.payment_status)}</span></td>
                            <td>
                                <a href="${BASE_URL}/reception/appointments?view=${app.id}" class="btn-action primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
                
                html += `
                                    </tbody>
                                </table>
                            </div>
                    `;
                    
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-calendar-check"></i>
                            <h6>No Appointments</h6>
                            <p style="font-size:12px;">Click the "Book Appointment" button to schedule one.</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading appointments:', error);
                container.innerHTML = `
                    <div class="alert alert-danger py-2">
                        <i class="fas fa-exclamation-circle me-2"></i> Error loading appointments. Please refresh.
                    </div>
                `;
            });
}
        
        // ================================================================
        // HELPER FUNCTIONS
        // ================================================================
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function bookAppointment(patientId) {
            window.location.href = BASE_URL + '/patient/book-appointment?patient_id=' + patientId;
        }
        
        function createPrescription(patientId) {
            window.location.href = BASE_URL + '/prescriptions/create?patient_id=' + patientId;
        }
        
        function orderLabTest(patientId) {
            window.location.href = BASE_URL + '/lab/create-order?patient_id=' + patientId;
        }
        
        // ================================================================
        // INITIALIZE
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            // Load data when tabs are shown
            document.querySelector('#vaccines-tab').addEventListener('shown.bs.tab', function() {
                loadVaccines();
            });
            
            document.querySelector('#prescriptions-tab').addEventListener('shown.bs.tab', function() {
                loadPrescriptions();
            });
            
            document.querySelector('#appointments-tab').addEventListener('shown.bs.tab', function() {
                loadAppointments();
            });
            
            // Load initial data if prescriptions tab is visible
            // (first tab is active, so we load on demand)
        });
    </script>
</body>
</html>