<?php
// app/views/prescriptions/patient-prescriptions.php - Standalone like patient/list.php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Prescriptions - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        
        .card-custom {
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .card-header-custom {
            background: white;
            border-bottom: 2px solid #3b82f6;
            padding: 10px 15px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .table-modern {
            width: 100%;
            background: white;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .table-modern thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 8px 10px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
            text-align: left;
        }
        
        .table-modern tbody td {
            padding: 8px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        
        .table-modern tbody tr:hover {
            background: #f8fafc;
        }
        
        .badge-prescription {
            background: #3b82f6;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }
        
        .action-icons {
            display: flex;
            gap: 3px;
            flex-wrap: nowrap;
        }
        
        .action-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 11px;
            text-decoration: none;
            border: none;
            flex-shrink: 0;
        }
        .action-icon:hover { transform: scale(1.08); }
        
        .icon-view { background: #eff6ff; color: #3b82f6; }
        .icon-view:hover { background: #3b82f6; color: white; }
        
        .icon-print { background: #f3f4f6; color: #6b7280; }
        .icon-print:hover { background: #6b7280; color: white; }
        
        .icon-edit { background: #fffbeb; color: #f59e0b; }
        .icon-edit:hover { background: #f59e0b; color: white; }
        
        .icon-prescription { background: #dbeafe; color: #2563eb; }
        .icon-prescription:hover { background: #2563eb; color: white; }
        
        .empty-state {
            padding: 40px 20px;
            text-align: center;
        }
        .empty-state i {
            font-size: 40px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }
        .empty-state h6 {
            color: #475569;
            margin-bottom: 4px;
        }
        .empty-state p {
            color: #94a3b8;
            font-size: 13px;
        }

        .patient-info-card {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .patient-info-card .info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #475569;
        }
        .patient-info-card .info-item i {
            color: #3b82f6;
            width: 16px;
        }
        .patient-info-card .info-item strong {
            color: #1e293b;
        }

        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 10px 14px;
            font-size: 13px;
        }
        .alert-success-custom {
            background: #ecfdf5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-danger-custom {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- ===== HEADER - SAME AS PATIENT LIST ===== -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-prescription" style="color: #3b82f6;"></i> Patient Prescriptions</h5>
                <p class="text-muted" style="font-size: 11px;">Manage prescriptions for this patient</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/prescriptions/create?patient_id=<?php echo $patient['id']; ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> New Prescription
                </a>
                <a href="<?php echo BASE_URL; ?>/patient/list" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success-custom mb-3">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert" style="font-size: 10px;"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['errors']) && count($_SESSION['errors']) > 0): ?>
            <div class="alert-custom alert-danger-custom mb-3">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert" style="font-size: 10px;"></button>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <!-- Patient Info Card -->
        <div class="patient-info-card">
            <div class="d-flex flex-wrap gap-3">
                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <span><strong><?php echo htmlspecialchars($patient['full_name'] ?? $patient['first_name'] . ' ' . $patient['last_name']); ?></strong></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-id-card"></i>
                    <span><strong><?php echo htmlspecialchars($patient['patient_code']); ?></strong></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <span><strong><?php echo htmlspecialchars($patient['phone']); ?></strong></span>
                </div>
                <?php if (!empty($patient['gender'])): ?>
                    <div class="info-item">
                        <i class="fas fa-venus-mars"></i>
                        <span><strong><?php echo ucfirst($patient['gender']); ?></strong></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($patient['date_of_birth']) && $patient['date_of_birth'] != '0000-00-00'): ?>
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <span><strong>
                            <?php 
                                $dob = new DateTime($patient['date_of_birth']);
                                $today = new DateTime('today');
                                $age = $dob->diff($today);
                                echo $age->y . 'Y ' . $age->m . 'M';
                            ?>
                        </strong></span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($patient['address'])): ?>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><strong><?php echo htmlspecialchars($patient['address']); ?></strong></span>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <span class="badge bg-<?php echo $patient['status'] == 'active' ? 'success' : 'secondary'; ?>">
                    <?php echo ucfirst($patient['status']); ?>
                </span>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-list text-primary"></i> Prescription Records
                <span class="badge bg-secondary ms-2"><?php echo count($prescriptions); ?> records</span>
            </div>
            <div class="table-responsive">
                <table class="table-modern" id="prescriptionsTable">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th width="140">Prescription No</th>
                            <th>Doctor</th>
                            <th width="100">Date</th>
                            <th width="70">Items</th>
                            <th width="100">Status</th>
                            <th width="110">Pharmacy</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prescriptions) && count($prescriptions) > 0): ?>
                            <?php $counter = 1; ?>
                            <?php foreach ($prescriptions as $prescription): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <span class="badge-prescription">
                                            <?php echo htmlspecialchars($prescription['prescription_number']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($prescription['doctor_title'] ?? 'Dr.') . ' ' . htmlspecialchars($prescription['doctor_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($prescription['specialization'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($prescription['prescription_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $prescription['item_count'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $statusClass = 'secondary';
                                            if ($prescription['status'] == 'issued') $statusClass = 'primary';
                                            elseif ($prescription['status'] == 'dispensed') $statusClass = 'info';
                                            elseif ($prescription['status'] == 'completed') $statusClass = 'success';
                                            elseif ($prescription['status'] == 'canceled') $statusClass = 'danger';
                                            elseif ($prescription['status'] == 'draft') $statusClass = 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo ucfirst($prescription['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $pharmacyClass = 'secondary';
                                            if ($prescription['pharmacy_status'] == 'pending') $pharmacyClass = 'warning';
                                            elseif ($prescription['pharmacy_status'] == 'processing') $pharmacyClass = 'info';
                                            elseif ($prescription['pharmacy_status'] == 'ready') $pharmacyClass = 'primary';
                                            elseif ($prescription['pharmacy_status'] == 'dispensed') $pharmacyClass = 'success';
                                            elseif ($prescription['pharmacy_status'] == 'collected') $pharmacyClass = 'dark';
                                        ?>
                                        <span class="badge bg-<?php echo $pharmacyClass; ?>">
                                            <?php echo ucfirst($prescription['pharmacy_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-icons">
                                            <a href="<?php echo BASE_URL; ?>/prescriptions/show/<?php echo $prescription['id']; ?>" 
                                               class="action-icon icon-view" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/prescriptions/print/<?php echo $prescription['id']; ?>" 
                                               class="action-icon icon-print" target="_blank" title="Print">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if ($prescription['status'] != 'canceled'): ?>
                                                <a href="<?php echo BASE_URL; ?>/prescriptions/edit/<?php echo $prescription['id']; ?>" 
                                                   class="action-icon icon-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-prescription"></i>
                                        <h6>No Prescriptions Found</h6>
                                        <p>This patient has no prescriptions yet.</p>
                                        <a href="<?php echo BASE_URL; ?>/prescriptions/create?patient_id=<?php echo $patient['id']; ?>" 
                                           class="btn btn-success btn-sm mt-2">
                                            <i class="fas fa-plus"></i> Create First Prescription
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';

    $(document).ready(function() {
        // Initialize DataTable if records exist
        <?php if (!empty($prescriptions) && count($prescriptions) > 0): ?>
        if (typeof $.fn.DataTable !== 'undefined') {
            $('#prescriptionsTable').DataTable({
                responsive: true,
                order: [[3, 'desc']],
                pageLength: 10,
                language: {
                    search: "Filter:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "No entries found",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    zeroRecords: "No matching records found"
                }
            });
        }
        <?php endif; ?>
    });

    // Refresh page function
    function refreshPage() {
        location.reload();
    }
    </script>
</body>
</html>