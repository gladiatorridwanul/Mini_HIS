<?php
// app/views/prescriptions/index.php - Complete with pagination
// ONLY SHOWS FULL PRESCRIPTIONS (issued, dispensed, completed)
// Draft prescriptions are HIDDEN from this list

if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');

// ============================================================
// ENSURE ALL VARIABLES HAVE DEFAULT VALUES
// ============================================================
$totalPages = isset($totalPages) ? (int)$totalPages : 1;
$currentPage = isset($currentPage) ? (int)$currentPage : 1;
$totalPrescriptions = isset($totalPrescriptions) ? (int)$totalPrescriptions : 0;
$prescriptions = isset($prescriptions) ? $prescriptions : [];
$search = isset($search) ? $search : '';
$status = isset($status) ? $status : '';
$date_from = isset($date_from) ? $date_from : '';
$selected_patient = isset($selected_patient) ? $selected_patient : 0;
$selected_doctor = isset($selected_doctor) ? $selected_doctor : 0;
$patients = isset($patients) ? $patients : [];
$doctors = isset($doctors) ? $doctors : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Prescriptions - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
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
        
        .icon-edit { background: #fffbeb; color: #f59e0b; }
        .icon-edit:hover { background: #f59e0b; color: white; }
        
        .icon-print { background: #f3f4f6; color: #6b7280; }
        .icon-print:hover { background: #6b7280; color: white; }
        
        .icon-print-pad { background: #fef3c7; color: #d97706; }
        .icon-print-pad:hover { background: #d97706; color: white; }
        
        .icon-pdf { background: #fee2e2; color: #dc2626; }
        .icon-pdf:hover { background: #dc2626; color: white; }
        
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

        /* ============================================================ */
        /* PAGINATION STYLING */
        /* ============================================================ */
        .pagination-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 15px 20px;
            border-top: 1px solid #e5e7eb;
            gap: 10px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px;
            margin: 0;
            padding: 0;
        }
        .pagination .page-item {
            list-style: none;
        }
        .pagination .page-link {
            padding: 6px 12px;
            font-size: 12px;
            color: #3b82f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: white;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-block;
            min-width: 34px;
            text-align: center;
        }
        .pagination .page-item.active .page-link {
            background-color: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        .pagination .page-item.disabled .page-link {
            color: #94a3b8;
            cursor: not-allowed;
            background: #f8fafc;
            pointer-events: none;
        }
        .pagination .page-link:hover:not(.disabled) {
            background-color: #eff6ff;
            color: #2563eb;
            border-color: #bfdbfe;
        }
        .pagination .page-item.active .page-link:hover {
            background-color: #2563eb;
            border-color: #2563eb;
            color: white;
        }

        .page-info {
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
        .page-info strong {
            color: #475569;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 500;
            white-space: nowrap;
        }
        .status-issued { background: #dbeafe; color: #2563eb; }
        .status-dispensed { background: #d1fae5; color: #059669; }
        .status-completed { background: #d1fae5; color: #059669; }
        .status-canceled { background: #fee2e2; color: #dc2626; }

        .filter-info {
            font-size: 11px;
            color: #64748b;
            background: #f8fafc;
            padding: 4px 12px;
            border-radius: 4px;
            display: inline-block;
        }
        .filter-info i {
            color: #3b82f6;
        }

        @media (max-width: 992px) {
            .action-icon { width: 26px; height: 26px; font-size: 10px; }
            .action-icons { gap: 2px; }
        }
        @media (max-width: 768px) {
            .action-icon { width: 24px; height: 24px; font-size: 9px; }
            .action-icons { gap: 2px; }
            .table-modern { font-size: 12px; }
            .table-modern thead th { font-size: 10px; padding: 6px 8px; }
            .table-modern tbody td { padding: 6px 8px; }
            .pagination .page-link { padding: 4px 8px; font-size: 11px; min-width: 28px; }
        }
        @media (max-width: 576px) {
            .pagination .page-link { padding: 3px 6px; font-size: 10px; min-width: 24px; }
            .pagination { gap: 2px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- ===== HEADER ===== -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-prescription" style="color: #3b82f6;"></i> Prescriptions</h5>
                <p class="text-muted" style="font-size: 11px;">
                    <i class="fas fa-check-circle text-success"></i> Showing only completed/full prescriptions
                    <span class="filter-info ms-2">
                        <i class="fas fa-info-circle"></i> Draft prescriptions are hidden
                    </span>
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button id="refreshPage" class="btn btn-light btn-sm" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <a href="<?php echo BASE_URL; ?>/prescriptions/create" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> New Prescription
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

        <!-- Filter Section -->
        <div class="filter-card">
            <form method="GET" action="<?php echo BASE_URL; ?>/prescriptions" id="filterForm" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" name="search" id="searchInput" class="form-control" 
                               placeholder="Search by prescription no, patient, doctor..." 
                               value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <button class="btn btn-primary btn-sm" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn btn-secondary btn-sm">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="patient_id" class="form-select">
                        <option value="">All Patients</option>
                        <?php if (!empty($patients)): ?>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo ($selected_patient ?? '') == $p['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['full_name'] ?: $p['first_name'] . ' ' . $p['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="doctor_id" class="form-select">
                        <option value="">All Doctors</option>
                        <?php if (!empty($doctors)): ?>
                            <?php foreach ($doctors as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo ($selected_doctor ?? '') == $d['id'] ? 'selected' : ''; ?>>
                                    Dr. <?php echo htmlspecialchars($d['first_name'] . ' ' . $d['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="issued" <?php echo ($status ?? '') == 'issued' ? 'selected' : ''; ?>>Issued ✓</option>
                        <option value="dispensed" <?php echo ($status ?? '') == 'dispensed' ? 'selected' : ''; ?>>Dispensed</option>
                        <option value="completed" <?php echo ($status ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="canceled" <?php echo ($status ?? '') == 'canceled' ? 'selected' : ''; ?>>Canceled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($date_from ?? ''); ?>" placeholder="Date From">
                </div>
                <div class="col-md-1 text-end">
                    <span class="badge bg-info text-white p-2">
                        Total: <?php echo number_format($totalPrescriptions ?? 0); ?>
                    </span>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-list text-primary"></i> Prescription Records
                <span class="badge bg-secondary ms-2" id="record_count"><?php echo count($prescriptions ?? []); ?> records</span>
                <span class="badge bg-success ms-2">
                    <i class="fas fa-check-circle"></i> Full Prescriptions Only
                </span>
            </div>
            <div class="table-responsive">
                <table class="table-modern" id="prescriptionsTable">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th width="140">Prescription No</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th width="100">Date</th>
                            <th width="100">Status</th>
                            <th width="110">Pharmacy</th>
                            <th width="230">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prescriptions) && count($prescriptions) > 0): ?>
                            <?php $counter = (($currentPage ?? 1) - 1) * 10 + 1; ?>
                            <?php foreach ($prescriptions as $prescription): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <span class="badge-prescription">
                                            <?php echo htmlspecialchars($prescription['prescription_number']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($prescription['patient_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($prescription['patient_code'] ?? ''); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($prescription['doctor_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($prescription['prescription_date'])); ?></td>
                                    <td>
                                        <?php
                                            $statusClass = 'secondary';
                                            $statusText = ucfirst($prescription['status']);
                                            if ($prescription['status'] == 'issued') {
                                                $statusClass = 'primary';
                                                $statusText = 'Issued ✓';
                                            } elseif ($prescription['status'] == 'dispensed') {
                                                $statusClass = 'info';
                                            } elseif ($prescription['status'] == 'completed') {
                                                $statusClass = 'success';
                                            } elseif ($prescription['status'] == 'canceled') {
                                                $statusClass = 'danger';
                                            }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $pharmacyClass = 'secondary';
                                            $pharmacyText = ucfirst($prescription['pharmacy_status'] ?? 'pending');
                                            if ($prescription['pharmacy_status'] == 'pending') {
                                                $pharmacyClass = 'warning';
                                                $pharmacyText = 'Pending';
                                            } elseif ($prescription['pharmacy_status'] == 'processing') {
                                                $pharmacyClass = 'info';
                                            } elseif ($prescription['pharmacy_status'] == 'ready') {
                                                $pharmacyClass = 'primary';
                                            } elseif ($prescription['pharmacy_status'] == 'dispensed') {
                                                $pharmacyClass = 'success';
                                            } elseif ($prescription['pharmacy_status'] == 'collected') {
                                                $pharmacyClass = 'dark';
                                            }
                                        ?>
                                        <span class="badge bg-<?php echo $pharmacyClass; ?>">
                                            <?php echo $pharmacyText; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-icons">
                                            <a href="<?php echo BASE_URL; ?>/prescriptions/show/<?php echo $prescription['id']; ?>" 
                                               class="action-icon icon-view" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($prescription['status'] != 'canceled'): ?>
                                                <a href="<?php echo BASE_URL; ?>/prescriptions/edit/<?php echo $prescription['id']; ?>" 
                                                   class="action-icon icon-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="<?php echo BASE_URL; ?>/prescriptions/print/<?php echo $prescription['id']; ?>" 
                                               class="action-icon icon-print" target="_blank" title="Print">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/prescriptions/print-pad/<?php echo $prescription['id']; ?>" 
                                               class="action-icon icon-print-pad" target="_blank" title="Print with Pad">
                                                <i class="fas fa-clipboard"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/prescriptions/export-pdf/<?php echo $prescription['id']; ?>" 
                                               class="action-icon icon-pdf" title="PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fas fa-prescription"></i>
                                        <h6>No Full Prescriptions Found</h6>
                                        <?php if (!empty($search) || !empty($status) || !empty($date_from) || !empty($selected_patient) || !empty($selected_doctor)): ?>
                                            <p>No results matching your filters</p>
                                            <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-undo"></i> Clear Filters
                                            </a>
                                        <?php else: ?>
                                            <p>No completed/full prescriptions available</p>
                                            <p class="text-muted small">
                                                <i class="fas fa-info-circle"></i> 
                                                Only prescriptions with status <strong>Issued</strong>, <strong>Dispensed</strong>, or <strong>Completed</strong> are shown here.
                                                Draft prescriptions are hidden.
                                            </p>
                                            <a href="<?php echo BASE_URL; ?>/prescriptions/create" class="btn btn-primary btn-sm mt-2">
                                                <i class="fas fa-plus"></i> Create New Prescription
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- ============================================================ -->
            <!-- PAGINATION -->
            <!-- ============================================================ -->
            <div class="pagination-wrapper">
                <?php if ($totalPrescriptions > 0): ?>
                    
                    <?php if ($totalPages > 1): ?>
                        <!-- ===== FULL PAGINATION ===== -->
                        <ul class="pagination">
                            <!-- First Page -->
                            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=1&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&patient_id=<?php echo urlencode($selected_patient ?? ''); ?>&doctor_id=<?php echo urlencode($selected_doctor ?? ''); ?>" title="First Page">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Previous Page -->
                            <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&patient_id=<?php echo urlencode($selected_patient ?? ''); ?>&doctor_id=<?php echo urlencode($selected_doctor ?? ''); ?>" title="Previous Page">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            
                            <!-- Page Numbers -->
                            <?php 
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&patient_id=<?php echo urlencode($selected_patient ?? ''); ?>&doctor_id=<?php echo urlencode($selected_doctor ?? ''); ?>">
                                        1
                                    </a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&patient_id=<?php echo urlencode($selected_patient ?? ''); ?>&doctor_id=<?php echo urlencode($selected_doctor ?? ''); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&patient_id=<?php echo urlencode($selected_patient ?? ''); ?>&doctor_id=<?php echo urlencode($selected_doctor ?? ''); ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Next Page -->
                            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&patient_id=<?php echo urlencode($selected_patient ?? ''); ?>&doctor_id=<?php echo urlencode($selected_doctor ?? ''); ?>" title="Next Page">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            
                            <!-- Last Page -->
                            <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search ?? ''); ?>&status=<?php echo urlencode($status ?? ''); ?>&date_from=<?php echo urlencode($date_from ?? ''); ?>&patient_id=<?php echo urlencode($selected_patient ?? ''); ?>&doctor_id=<?php echo urlencode($selected_doctor ?? ''); ?>" title="Last Page">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                        
                        <!-- Page Info -->
                        <div class="page-info">
                            Page <strong><?php echo $currentPage; ?></strong> of <strong><?php echo $totalPages; ?></strong>
                            <span class="text-muted">|</span>
                            Total <strong><?php echo number_format($totalPrescriptions); ?></strong> full prescriptions
                        </div>
                        
                    <?php else: ?>
                        <!-- ===== SINGLE PAGE ===== -->
                        <div class="page-info">
                            Showing <strong><?php echo count($prescriptions ?? []); ?></strong> of <strong><?php echo number_format($totalPrescriptions); ?></strong> full prescriptions
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- ===== NO RECORDS ===== -->
                    <div class="page-info text-muted">
                        No full prescriptions found
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    let filterTimeout;

    $('#searchInput').on('input', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            $('#filterForm').submit();
        }, 500);
    });

    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            clearTimeout(filterTimeout);
            $('#filterForm').submit();
        }
    });

    $('select[name="patient_id"], select[name="doctor_id"], select[name="status"], input[name="date_from"]').on('change', function() {
        $('#filterForm').submit();
    });

    $('#refreshPage').click(function() { 
        location.reload(); 
    });
    </script>
</body>
</html>