<?php
// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($limit) ? $limit : 10;
$offset = isset($offset) ? $offset : 0;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$gender = isset($_GET['gender']) ? $_GET['gender'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';

// Get total patients count for pagination
$totalPatients = isset($totalPatients) ? $totalPatients : 0;
$totalPages = ceil($totalPatients / $limit);
$currentPage = $page;
?>

<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient List - UniDia HMS</title>
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
        
        .table-responsive-custom {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table-modern {
            width: 100%;
            background: white;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 650px;
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
        
        .table-modern tbody tr:last-child td {
            border-bottom: none;
        }
        
        .col-sno { width: 38px; text-align: center; }
        .col-code { width: 120px; }
        .col-name { width: 140px; max-width: 140px; }
        .col-phone { width: 120px; }
        .col-gender { width: 75px; }
        .col-status { width: 80px; }
        .col-actions { width: 210px; min-width: 210px; }
        
        .name-text {
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 130px;
        }
        
        .badge-patient-code {
            background: #3b82f6;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }
        
        .badge-status {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }
        .badge-status.active { background: #d1fae5; color: #065f46; }
        .badge-status.inactive { background: #fee2e2; color: #991b1b; }
        
        .badge-gender {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }
        .badge-gender.male { background: #dbeafe; color: #1e40af; }
        .badge-gender.female { background: #fce7f3; color: #9d174d; }
        .badge-gender.other { background: #f3e8ff; color: #6b21a8; }
        
        .action-icons {
            display: flex;
            gap: 3px;
            flex-wrap: nowrap;
            justify-content: flex-start;
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
        
        .icon-idcard { background: #f5f3ff; color: #8b5cf6; }
        .icon-idcard:hover { background: #8b5cf6; color: white; }
        .icon-view { background: #eff6ff; color: #3b82f6; }
        .icon-view:hover { background: #3b82f6; color: white; }
        .icon-edit { background: #fffbeb; color: #f59e0b; }
        .icon-edit:hover { background: #f59e0b; color: white; }
        .icon-prescription { background: #dbeafe; color: #2563eb; }
        .icon-prescription:hover { background: #2563eb; color: white; }
        .icon-appointment { background: #ecfdf5; color: #10b981; }
        .icon-appointment:hover { background: #10b981; color: white; }
        .icon-delete { background: #fee2e2; color: #ef4444; }
        .icon-delete:hover { background: #ef4444; color: white; }
        
        .summary-card {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            transition: transform 0.3s;
            height: 100%;
        }
        .summary-card:hover { transform: translateY(-3px); }
        .summary-card h3 { font-size: 22px; font-weight: 700; margin: 0; }
        .summary-card small { font-size: 10px; opacity: 0.85; }
        .summary-card.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .summary-card.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .summary-card.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
        
        .filter-select, .filter-input {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 6px 10px;
            font-size: 12px;
            height: 38px;
        }
        .filter-select:focus, .filter-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .alert-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 250px;
            animation: slideIn 0.3s ease-out;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 13px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .pagination .page-link {
            padding: 5px 10px;
            font-size: 12px;
            color: #3b82f6;
            border-color: #e5e7eb;
        }
        .pagination .page-item.active .page-link {
            background-color: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }
        .pagination .page-item.disabled .page-link {
            color: #94a3b8;
            pointer-events: none;
        }
        .pagination .page-link:hover {
            background-color: #eff6ff;
            color: #2563eb;
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
        
        /* Age/DOB input group styling */
        .age-dob-group {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .age-dob-group .age-input {
            flex: 1;
        }
        .age-dob-group .dob-input {
            flex: 2;
        }
        .age-dob-group .age-display {
            font-size: 13px;
            color: #6c757d;
            padding-top: 4px;
            min-width: 80px;
        }
        .age-dob-group .age-display .age-label {
            font-size: 11px;
            color: #9ca3af;
        }
        .age-dob-group .age-display .age-value {
            font-weight: 600;
            color: #1e293b;
        }
        
        @media (max-width: 992px) {
            .col-name { width: 110px; max-width: 110px; }
            .name-text { max-width: 100px; }
            .col-actions { width: 190px; min-width: 190px; }
            .action-icon { width: 26px; height: 26px; font-size: 10px; }
        }
        
        @media (max-width: 768px) {
            .table-modern { font-size: 12px; min-width: 600px; }
            .table-modern thead th { font-size: 10px; padding: 6px 8px; }
            .table-modern tbody td { padding: 6px 8px; }
            .col-name { width: 90px; max-width: 90px; }
            .name-text { max-width: 80px; }
            .col-phone { width: 100px; }
            .col-actions { width: 170px; min-width: 170px; }
            .action-icon { width: 24px; height: 24px; font-size: 9px; border-radius: 4px; }
            .action-icons { gap: 2px; }
        }
        
        @media (max-width: 576px) {
            .table-modern { font-size: 11px; min-width: 500px; }
            .table-modern thead th { padding: 5px 6px; }
            .table-modern tbody td { padding: 5px 6px; }
            .col-code { width: 90px; }
            .col-name { width: 70px; max-width: 70px; }
            .name-text { max-width: 60px; }
            .col-phone { width: 85px; }
            .col-actions { width: 150px; min-width: 150px; }
            .action-icon { width: 22px; height: 22px; font-size: 8px; }
            .action-icons { gap: 2px; }
        }

        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }
        .modal-header {
            border-bottom: 2px solid #3b82f6;
            padding: 15px 20px;
        }
        .modal-header .modal-title {
            font-weight: 600;
            font-size: 16px;
            color: #1f2937;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 15px 20px;
        }
        .modal .form-label {
            font-weight: 500;
            font-size: 13px;
            color: #374151;
            margin-bottom: 4px;
        }
        .modal .form-control, .modal .form-select {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 8px 12px;
            font-size: 13px;
        }
        .modal .form-control:focus, .modal .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .modal .required-star {
            color: #ef4444;
        }
        .modal .btn-primary {
            background: #3b82f6;
            border: none;
            padding: 8px 20px;
            font-size: 13px;
            border-radius: 8px;
        }
        .modal .btn-primary:hover {
            background: #2563eb;
        }
        .modal .btn-secondary {
            background: #e5e7eb;
            border: none;
            color: #374151;
            padding: 8px 20px;
            font-size: 13px;
            border-radius: 8px;
        }
        .modal .btn-secondary:hover {
            background: #d1d5db;
        }
        .modal .btn-success {
            background: #10b981;
            border: none;
            padding: 8px 20px;
            font-size: 13px;
            border-radius: 8px;
        }
        .modal .btn-success:hover {
            background: #059669;
        }
        .modal .section-divider {
            border: 0;
            border-top: 1px solid #e5e7eb;
            margin: 12px 0;
        }
        .modal .section-title {
            font-size: 13px;
            font-weight: 600;
            color: #3b82f6;
            margin-bottom: 8px;
        }
        .modal .text-muted-small {
            font-size: 11px;
            color: #9ca3af;
        }
        .modal .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            border-left: 4px solid #ef4444;
        }
        .modal .alert-error ul {
            margin: 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-procedures" style="color: #3b82f6;"></i> Patient Management</h5>
                <p class="text-muted" style="font-size: 11px;">Manage all registered patients</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button id="refreshPage" class="btn btn-light btn-sm" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button id="openRegisterModal" class="btn btn-primary btn-sm">
                    <i class="fas fa-user-plus"></i> Register New
                </button>
                <a href="/unidia/public/admin/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="summary-card"><h3 id="total_count"><?php echo number_format($totalPatients); ?></h3><small>Total Patients</small></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="summary-card green"><h3 id="active_count"><?php echo number_format($totalPatients); ?></h3><small>Active Patients</small></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="summary-card orange"><h3 id="today_count">0</h3><small>Today's Appointments</small></div>
            </div>
            <div class="col-6 col-md-3">
                <div class="summary-card purple"><h3 id="idcard_count">0</h3><small>ID Cards Printed</small></div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control filter-input" placeholder="Search by name, phone, code..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary btn-sm" onclick="applyFilters()" style="background: #3b82f6; border: none;">
                            <i class="fas fa-search"></i>
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="resetFilters()">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="genderFilter" class="form-select filter-select">
                        <option value="">All Genders</option>
                        <option value="male" <?php echo $gender == 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $gender == 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo $gender == 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" id="dateFrom" class="form-control filter-input" value="<?php echo $dateFrom; ?>" placeholder="From Date">
                </div>
                <div class="col-md-2 text-end">
                    <span class="badge bg-info text-white p-2">
                        Total: <?php echo number_format($totalPatients); ?>
                    </span>
                </div>
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

        <!-- Patient Table -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-list text-primary"></i> Patient Records
                <span class="badge bg-secondary ms-2" id="record_count"><?php echo count($patients); ?> records</span>
            </div>
            <div class="table-responsive-custom">
                <table class="table-modern" id="patientTable">
                    <thead>
                        <tr>
                            <th class="col-sno">#</th>
                            <th class="col-code">Patient Code</th>
                            <th class="col-name">Name</th>
                            <th class="col-phone">Phone</th>
                            <th class="col-gender">Gender</th>
                            <th class="col-status">Status</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($patients) && count($patients) > 0): ?>
                            <?php $counter = $offset + 1; ?>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td class="col-sno"><?php echo $counter++; ?></td>
                                    <td class="col-code">
                                        <span class="badge-patient-code">
                                            <?php echo htmlspecialchars($patient['patient_code']); ?>
                                        </span>
                                    </td>
                                    <td class="col-name">
                                        <span class="name-text" title="<?php echo htmlspecialchars($patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name']); ?>">
                                            <?php echo htmlspecialchars($patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name']); ?>
                                        </span>
                                    </td>
                                    <td class="col-phone">
                                        <a href="tel:<?php echo $patient['phone']; ?>" class="text-decoration-none" style="color: #3b82f6; font-size: 11px;">
                                            <i class="fas fa-phone-alt me-1"></i> <?php echo $patient['phone']; ?>
                                        </a>
                                    </td>
                                    <td class="col-gender">
                                        <span class="badge-gender <?php echo $patient['gender'] ?? 'other'; ?>">
                                            <?php echo ucfirst($patient['gender'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="col-status">
                                        <span class="badge-status <?php echo $patient['status'] == 'active' ? 'active' : 'inactive'; ?>">
                                            <?php echo ucfirst($patient['status']); ?>
                                        </span>
                                    </td>
                                    <td class="col-actions">
                                        <div class="action-icons">
                                            <a href="<?php echo BASE_URL; ?>/barcode/print?id=<?php echo $patient['id']; ?>&auto_print=true" target="_blank" class="action-icon" style="background: #ede9fe; color: #7c3aed;" title="View & Print Barcode">
                                                <i class="fas fa-qrcode"></i>
                                            </a>
                                            <a href="/unidia/public/patient/id-card-front?id=<?php echo $patient['id']; ?>&print=true" target="_blank" class="action-icon" style="background: #dbeafe; color: #2563eb;" title="Print ID Card Front">
                                                <i class="fas fa-id-card" style="transform: rotate(0deg);"></i>
                                            </a>
                                            <a href="/unidia/public/patient/id-card-back?id=<?php echo $patient['id']; ?>&print=true" target="_blank" class="action-icon" style="background: #fce7f3; color: #db2777;" title="Print ID Card Back">
                                                <i class="fas fa-id-card" style="transform: rotate(180deg);"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/vaccines?patient_id=<?php echo $patient['id']; ?>" class="action-icon" style="background: #d1fae5; color: #059669;" title="View Vaccination Records">
                                                <i class="fas fa-syringe"></i>
                                            </a>
                                            <a href="/unidia/public/patient/show?id=<?php echo $patient['id']; ?>" class="action-icon icon-view" title="View Patient">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="/unidia/public/patient/edit?id=<?php echo $patient['id']; ?>" class="action-icon icon-edit" title="Edit Patient">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="/unidia/public/prescriptions/patient/<?php echo $patient['id']; ?>" class="action-icon icon-prescription" title="View Prescriptions">
                                                <i class="fas fa-prescription"></i>
                                            </a>
                                            <a href="/unidia/public/appointments/book?patient_id=<?php echo $patient['id']; ?>" class="action-icon icon-appointment" title="Book Appointment">
                                                <i class="fas fa-calendar-plus"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/patient/barcode-print?id=<?php echo $patient['id']; ?>&auto_print=true" target="_blank" class="action-icon" style="background: #ede9fe; color: #7c3aed;" title="View & Print Barcode">
                                                <i class="fas fa-qrcode"></i>
                                            </a>
                                            <a href="javascript:void(0)" onclick="deletePatient(<?php echo $patient['id']; ?>)" class="action-icon icon-delete" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <h6>No Patients Found</h6>
                                        <?php if (!empty($search)): ?>
                                            <p>No results matching "<strong><?php echo htmlspecialchars($search); ?></strong>"</p>
                                            <button class="btn btn-secondary btn-sm" onclick="resetFilters()">Clear Search</button>
                                        <?php else: ?>
                                            <p>Start by registering your first patient</p>
                                            <button class="btn btn-primary btn-sm" id="openRegisterModalEmpty">
                                                <i class="fas fa-user-plus"></i> Register Patient
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div class="p-3 border-top">
                    <ul class="pagination justify-content-center mb-0 flex-wrap">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=1&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender); ?>&date_from=<?php echo urlencode($dateFrom); ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender); ?>&date_from=<?php echo urlencode($dateFrom); ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        
                        <?php 
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender); ?>&date_from=<?php echo urlencode($dateFrom); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender); ?>&date_from=<?php echo urlencode($dateFrom); ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&gender=<?php echo urlencode($gender); ?>&date_from=<?php echo urlencode($dateFrom); ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Page Info -->
            <?php if (isset($totalPages) && $totalPages > 0): ?>
                <div class="px-3 pb-2 text-center">
                    <small class="text-muted">
                        Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?> 
                        (Total <?php echo $totalPatients; ?> patients)
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- QUICK REGISTER MODAL - WITH AGE/DOB CALCULATION -->
    <!-- ============================================ -->
    <div class="modal fade" id="quickRegisterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2 text-primary"></i>Quick Register Patient
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="modalErrors" style="display:none;" class="alert-error mb-3"></div>
                    
                    <!-- FIXED: Added note about same phone number -->
                    <div class="alert alert-info alert-dismissible fade show mb-3" style="font-size: 12px; padding: 8px 12px;">
                        <i class="fas fa-info-circle me-1"></i> 
                        <strong>Note:</strong> Multiple patients can share the same phone number.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 10px;"></button>
                    </div>
                    
                    <form id="quickRegisterForm">
                        <div class="section-title"><i class="fas fa-user-circle me-1"></i>Personal Information</div>
                        <hr class="section-divider">
                        
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="required-star">*</span></label>
                            <input type="text" name="full_name" id="modalFullName" class="form-control" placeholder="Enter patient's full name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number <span class="required-star">*</span></label>
                            <input type="tel" name="phone" id="modalPhone" class="form-control" placeholder="01XXXXXXXXX" required>
                            <small class="text-muted-small">Multiple patients can share the same phone number</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Age / Date of Birth <span class="required-star">*</span></label>
                            <div class="age-dob-group">
                                <div class="age-input">
                                    <div class="input-group">
                                        <input type="number" id="modalAge" class="form-control" placeholder="Age (Years)" min="0" max="150">
                                        <span class="input-group-text">Yrs</span>
                                    </div>
                                    <small class="text-muted-small">Enter age to auto-calculate DOB</small>
                                </div>
                                <div class="dob-input">
                                    <input type="date" name="date_of_birth" id="modalDob" class="form-control">
                                    <small class="text-muted-small">Enter DOB to auto-calculate age</small>
                                </div>
                                <div class="age-display" id="ageDisplay">
                                    <span class="age-label">Age:</span>
                                    <span class="age-value" id="ageDisplayValue">—</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <select name="gender" id="modalGender" class="form-select">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="section-title mt-2"><i class="fas fa-map-marker-alt me-1"></i>Address Information</div>
                        <hr class="section-divider">
                        
                        <div class="mb-3">
                            <label class="form-label">House/Street Address</label>
                            <textarea name="address" id="modalAddress" class="form-control" rows="2" placeholder="House/Flat No, Road/Street, Village/Area">Bangladesh</textarea>
                            <small class="text-muted-small">Optional</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="modalSaveRegister">
                        <i class="fas fa-save me-1"></i> Save & Register
                    </button>
                    <button type="button" class="btn btn-primary" id="modalRegisterPatient">
                        <i class="fas fa-user-plus me-1"></i> Register Patient
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="alert_container"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    let searchTimeout;
    let quickRegisterModal;

    function calculateAgeFromDob(dob) {
        if (!dob) return null;
        try {
            const birthDate = new Date(dob);
            const today = new Date();
            let years = today.getFullYear() - birthDate.getFullYear();
            let months = today.getMonth() - birthDate.getMonth();
            if (months < 0) {
                years--;
                months += 12;
            }
            if (years < 0) return null;
            return { years: years, months: months };
        } catch(e) {
            return null;
        }
    }
    
    function calculateDobFromAge(years) {
        if (!years || years <= 0) return null;
        try {
            const today = new Date();
            const dob = new Date(today);
            dob.setFullYear(dob.getFullYear() - parseInt(years));
            const year = dob.getFullYear();
            const month = String(dob.getMonth() + 1).padStart(2, '0');
            const day = String(dob.getDate()).padStart(2, '0');
            return year + '-' + month + '-' + day;
        } catch(e) {
            return null;
        }
    }
    
    function updateAgeDisplay() {
        const dob = $('#modalDob').val();
        if (dob) {
            const age = calculateAgeFromDob(dob);
            if (age && age.years >= 0) {
                let displayText = age.years + 'Y ';
                if (age.months > 0) displayText += age.months + 'M';
                $('#ageDisplayValue').text(displayText);
                $('#ageDisplay').show();
                return;
            }
        }
        const ageVal = $('#modalAge').val();
        if (ageVal && parseInt(ageVal) > 0) {
            $('#ageDisplayValue').text(ageVal + 'Y');
            $('#ageDisplay').show();
            return;
        }
        $('#ageDisplayValue').text('—');
        $('#ageDisplay').show();
    }
    
    $('#modalDob').on('change input', function() {
        const dob = $(this).val();
        if (dob) {
            const age = calculateAgeFromDob(dob);
            if (age && age.years >= 0) {
                $('#modalAge').val(age.years);
                updateAgeDisplay();
            } else {
                $('#modalAge').val('');
                updateAgeDisplay();
            }
        } else {
            $('#modalAge').val('');
            updateAgeDisplay();
        }
    });
    
    $('#modalAge').on('change input', function() {
        const years = $(this).val();
        if (years && parseInt(years) > 0) {
            const dob = calculateDobFromAge(parseInt(years));
            if (dob) {
                $('#modalDob').val(dob);
                updateAgeDisplay();
            }
        } else {
            updateAgeDisplay();
        }
    });
    
    function resetModalForm() {
        $('#quickRegisterForm')[0].reset();
        $('#modalAddress').val('Bangladesh');
        $('#modalErrors').hide().html('');
        $('#modalFullName').removeClass('is-invalid');
        $('#modalPhone').removeClass('is-invalid');
        $('#modalDob').val('');
        $('#modalAge').val('');
        $('#ageDisplayValue').text('—');
        $('#modalRegisterPatient').prop('disabled', false).html('<i class="fas fa-user-plus me-1"></i> Register Patient');
        $('#modalSaveRegister').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save & Register');
    }

    function showModalErrors(errors) {
        let html = '<ul>';
        if (Array.isArray(errors)) {
            errors.forEach(function(err) {
                html += '<li>' + err + '</li>';
            });
        } else {
            html += '<li>' + errors + '</li>';
        }
        html += '</ul>';
        $('#modalErrors').html(html).show();
        
        if (errors.some(e => e.toLowerCase().includes('full name') || e.toLowerCase().includes('name'))) {
            $('#modalFullName').addClass('is-invalid');
        }
        if (errors.some(e => e.toLowerCase().includes('phone'))) {
            $('#modalPhone').addClass('is-invalid');
        }
        if (errors.some(e => e.toLowerCase().includes('birth') || e.toLowerCase().includes('dob') || e.toLowerCase().includes('age'))) {
            $('#modalDob').addClass('is-invalid');
        }
    }

    function submitQuickRegister(action) {
        $('#modalErrors').hide().html('');
        $('#modalFullName').removeClass('is-invalid');
        $('#modalPhone').removeClass('is-invalid');
        $('#modalDob').removeClass('is-invalid');
        
        let dob = $('#modalDob').val();
        const ageVal = $('#modalAge').val();
        
        if (!dob && ageVal && parseInt(ageVal) > 0) {
            const calculatedDob = calculateDobFromAge(parseInt(ageVal));
            if (calculatedDob) {
                dob = calculatedDob;
                $('#modalDob').val(dob);
            }
        }
        
        var formData = {
            full_name: $('#modalFullName').val().trim(),
            phone: $('#modalPhone').val().trim(),
            gender: $('#modalGender').val(),
            date_of_birth: dob,
            address: $('#modalAddress').val().trim() || 'Bangladesh',
            action: action
        };
        
        var errors = [];
        if (!formData.full_name) errors.push('Full name is required');
        if (!formData.phone) errors.push('Phone number is required');
        if (!formData.date_of_birth) errors.push('Date of birth is required (or enter age)');
        
        // FIXED: No phone uniqueness check - allow multiple patients with same phone
        
        if (errors.length > 0) {
            showModalErrors(errors);
            return;
        }
        
        $('#modalRegisterPatient').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');
        $('#modalSaveRegister').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');
        
        $.ajax({
            url: BASE_URL + '/patient/quick-store',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                $('#modalRegisterPatient').prop('disabled', false).html('<i class="fas fa-user-plus me-1"></i> Register Patient');
                $('#modalSaveRegister').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save & Register');
                
                if (response.success) {
                    if (action === 'register') {
                        quickRegisterModal.hide();
                        window.location.href = BASE_URL + '/appointments/book?patient_id=' + response.patient_id + '&new_patient=1';
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Patient Registered!',
                            text: response.message + ' (ID: ' + response.patient_code + ')',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        resetModalForm();
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    if (response.errors) {
                        showModalErrors(response.errors);
                    } else {
                        showModalErrors([response.message || 'An error occurred']);
                    }
                }
            },
            error: function(xhr, status, error) {
                $('#modalRegisterPatient').prop('disabled', false).html('<i class="fas fa-user-plus me-1"></i> Register Patient');
                $('#modalSaveRegister').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save & Register');
                showModalErrors(['An unexpected error occurred. Please try again.']);
            }
        });
    }

    $(document).ready(function() {
        quickRegisterModal = new bootstrap.Modal(document.getElementById('quickRegisterModal'));
        
        $('#openRegisterModal, #openRegisterModalEmpty').click(function() {
            resetModalForm();
            quickRegisterModal.show();
        });

        $('#quickRegisterModal').on('hidden.bs.modal', function() {
            resetModalForm();
        });
        
        updateAgeDisplay();
    });

    $('#modalRegisterPatient').click(function() {
        submitQuickRegister('register');
    });

    $('#modalSaveRegister').click(function() {
        submitQuickRegister('save_register');
    });

    $('#quickRegisterForm').on('keydown', function(e) {
        if (e.key === 'Enter' && !$(e.target).is('textarea')) {
            e.preventDefault();
            submitQuickRegister('save_register');
        }
    });

    function showAlert(message, type = 'success') {
        const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
        const alertDiv = $(`<div class="alert-toast" style="background: ${colors[type]}; color: white;">${message}</div>`);
        $('#alert_container').html(alertDiv);
        setTimeout(() => { alertDiv.fadeOut(300, function() { $(this).remove(); }); }, 3000);
    }

    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            applyFilters();
        }, 500);
    });

    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            clearTimeout(searchTimeout);
            applyFilters();
        }
    });

    $('#genderFilter, #dateFrom').on('change', function() {
        applyFilters();
    });

    function applyFilters() {
        const search = $('#searchInput').val().trim();
        const gender = $('#genderFilter').val();
        const dateFrom = $('#dateFrom').val();
        
        let url = '/unidia/public/patient/list?';
        if (search) url += 'search=' + encodeURIComponent(search) + '&';
        if (gender) url += 'gender=' + encodeURIComponent(gender) + '&';
        if (dateFrom) url += 'date_from=' + encodeURIComponent(dateFrom) + '&';
        
        window.location.href = url;
    }

    function resetFilters() {
        $('#searchInput').val('');
        $('#genderFilter').val('');
        $('#dateFrom').val('');
        window.location.href = '/unidia/public/patient/list';
    }

    function deletePatient(id) {
        Swal.fire({
            title: 'Delete Patient?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '/unidia/public/patient/delete?id=' + id;
            }
        });
    }

    $('#refreshPage').click(function() { 
        showAlert('Refreshed', 'info'); 
        location.reload(); 
    });

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const search = urlParams.get('search');
        const gender = urlParams.get('gender');
        const dateFrom = urlParams.get('date_from');
        
        if (search) $('#searchInput').val(search);
        if (gender) $('#genderFilter').val(gender);
        if (dateFrom) $('#dateFrom').val(dateFrom);
    });
    </script>
</body>
</html>