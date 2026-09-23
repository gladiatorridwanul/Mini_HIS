<?php
// app/views/appointments/view.php
// Three Column Layout - Enhanced Appointment View

if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Appointment - <?php echo htmlspecialchars($appointment['appointment_number'] ?? 'N/A'); ?> - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ================================================================ */
        /* BASE STYLES */
        /* ================================================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #f0f2f5; 
            font-family: 'Inter', sans-serif; 
            font-size: 13px; 
        }
        
        /* ================================================================ */
        /* ANIMATIONS */
        /* ================================================================ */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .animate-fade-up { animation: fadeInUp 0.5s ease forwards; }
        .animate-slide-right { animation: slideInRight 0.5s ease forwards; }
        .animate-delay-1 { animation-delay: 0.1s; opacity: 0; }
        .animate-delay-2 { animation-delay: 0.2s; opacity: 0; }
        .animate-delay-3 { animation-delay: 0.3s; opacity: 0; }
        .animate-delay-4 { animation-delay: 0.4s; opacity: 0; }
        .animate-delay-5 { animation-delay: 0.5s; opacity: 0; }
        .animate-delay-6 { animation-delay: 0.6s; opacity: 0; }
        
        /* ================================================================ */
        /* CARDS */
        /* ================================================================ */
        .card-custom { 
            background: white; 
            border-radius: 16px; 
            margin-bottom: 20px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.06); 
            border: 1px solid #e5e7eb; 
            overflow: hidden;
            transition: box-shadow 0.3s ease;
        }
        .card-custom:hover {
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        
        .card-header-custom { 
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border-bottom: 2px solid #3b82f6; 
            padding: 14px 20px; 
            font-weight: 600; 
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        /* ================================================================ */
        /* STAT CARDS - Mini Dashboard */
        /* ================================================================ */
        .stat-mini-card {
            background: white;
            border-radius: 12px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            height: 100%;
            cursor: default;
        }
        .stat-mini-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-color: #d1d5db;
        }
        .stat-mini-card .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .stat-mini-card .stat-icon.blue { background: #dbeafe; color: #2563eb; }
        .stat-mini-card .stat-icon.green { background: #d1fae5; color: #059669; }
        .stat-mini-card .stat-icon.orange { background: #fef3c7; color: #d97706; }
        .stat-mini-card .stat-icon.red { background: #fee2e2; color: #dc2626; }
        .stat-mini-card .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
        .stat-mini-card .stat-icon.teal { background: #ccfbf1; color: #0d9488; }
        .stat-mini-card .stat-icon.pink { background: #fce7f3; color: #db2777; }
        .stat-mini-card .stat-icon.indigo { background: #e0e7ff; color: #4f46e5; }
        
        .stat-mini-card .stat-content h6 {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .stat-mini-card .stat-content .stat-value {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            line-height: 1.2;
        }
        .stat-mini-card .stat-content .stat-sub {
            font-size: 10px;
            color: #94a3b8;
        }
        
        /* ================================================================ */
        /* THREE COLUMN GRID */
        /* ================================================================ */
        .three-col-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px 20px;
        }
        .three-col-grid .col-item {
            display: flex;
            padding: 6px 0;
            border-bottom: 1px solid #f8fafc;
            align-items: baseline;
        }
        .three-col-grid .col-item .label {
            font-weight: 500;
            color: #64748b;
            min-width: 90px;
            font-size: 12px;
            flex-shrink: 0;
        }
        .three-col-grid .col-item .value {
            color: #1e293b;
            font-weight: 500;
            font-size: 13px;
            word-break: break-word;
            flex: 1;
        }
        .three-col-grid .col-item .value strong { color: #0f172a; }
        
        /* Address specific - preserve line breaks */
        .three-col-grid .col-item .value.address-value {
            
            line-height: 1.6;
        }
        
        /* ================================================================ */
        /* TWO COLUMN GRID FOR MOBILE FALLBACK */
        /* ================================================================ */
        .two-col-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 20px;
        }
        .two-col-grid .col-item {
            display: flex;
            padding: 6px 0;
            border-bottom: 1px solid #f8fafc;
            align-items: baseline;
        }
        .two-col-grid .col-item .label {
            font-weight: 500;
            color: #64748b;
            min-width: 90px;
            font-size: 12px;
            flex-shrink: 0;
        }
        .two-col-grid .col-item .value {
            color: #1e293b;
            font-weight: 500;
            font-size: 13px;
            word-break: break-word;
            flex: 1;
        }
        
        /* ================================================================ */
        /* BADGES & STATUS */
        /* ================================================================ */
        .badge-status { 
            padding: 5px 14px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .badge-status i { font-size: 10px; }
        
        .payment-status-badge { 
            padding: 3px 12px; 
            border-radius: 12px; 
            font-size: 11px; 
            font-weight: 500; 
            display: inline-block;
        }
        .payment-paid { background: #d1fae5; color: #065f46; }
        .payment-pending { background: #fef3c7; color: #92400e; }
        .payment-partial { background: #dbeafe; color: #1d4ed8; }
        .payment-refunded { background: #fee2e2; color: #991b1b; }
        .payment-canceled { background: #f3f4f6; color: #6b7280; }
        
        .badge-tag { 
            background: #f1f5f9; 
            color: #475569; 
            padding: 2px 10px; 
            border-radius: 12px; 
            font-size: 11px; 
            display: inline-block; 
            margin: 2px 4px 2px 0;
        }
        
        /* ================================================================ */
        /* SECTION TITLES */
        /* ================================================================ */
        .section-title-custom {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f1f5f9;
        }
        .section-title-custom .title-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .section-title-custom .title-icon.blue { background: #dbeafe; color: #2563eb; }
        .section-title-custom .title-icon.green { background: #d1fae5; color: #059669; }
        .section-title-custom .title-icon.orange { background: #fef3c7; color: #d97706; }
        .section-title-custom .title-icon.purple { background: #ede9fe; color: #7c3aed; }
        .section-title-custom .title-icon.teal { background: #ccfbf1; color: #0d9488; }
        .section-title-custom .title-icon.red { background: #fee2e2; color: #dc2626; }
        .section-title-custom .title-icon.indigo { background: #e0e7ff; color: #4f46e5; }
        .section-title-custom .title-icon.pink { background: #fce7f3; color: #db2777; }
        .section-title-custom .badge-count {
            font-size: 10px;
            font-weight: 500;
            background: #f1f5f9;
            color: #64748b;
            padding: 1px 10px;
            border-radius: 10px;
        }
        
        /* ================================================================ */
        /* SECTION DIVIDER */
        /* ================================================================ */
        .section-divider { 
            border-top: 2px solid #e5e7eb; 
            margin: 20px 0 16px 0; 
            position: relative;
        }
        .section-divider.dashed { border-top-style: dashed; border-color: #d1d5db; }
        
        /* ================================================================ */
        /* TABLE STYLES */
        /* ================================================================ */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .table-custom thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .table-custom tbody td {
            padding: 8px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .table-custom tbody tr:hover { background: #f8fafc; }
        .table-custom tbody tr:last-child td { border-bottom: none; }
        
        /* ================================================================ */
        /* BUTTONS */
        /* ================================================================ */
        .btn-action { 
            padding: 5px 14px; 
            border-radius: 8px; 
            font-size: 11px; 
            font-weight: 500; 
            border: none; 
            cursor: pointer; 
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-action:hover { 
            transform: translateY(-1px); 
            box-shadow: 0 4px 12px rgba(0,0,0,0.12); 
        }
        .btn-action-primary { background: #3b82f6; color: white; }
        .btn-action-primary:hover { background: #2563eb; }
        .btn-action-success { background: #10b981; color: white; }
        .btn-action-success:hover { background: #059669; }
        .btn-action-danger { background: #ef4444; color: white; }
        .btn-action-danger:hover { background: #dc2626; }
        .btn-action-warning { background: #f59e0b; color: white; }
        .btn-action-warning:hover { background: #d97706; }
        .btn-action-secondary { background: #6b7280; color: white; }
        .btn-action-secondary:hover { background: #4b5563; }
        .btn-action-outline { 
            background: transparent; 
            border: 1px solid #e5e7eb; 
            color: #475569; 
        }
        .btn-action-outline:hover { background: #f8fafc; border-color: #d1d5db; }
        
        /* ================================================================ */
        /* ALERTS */
        /* ================================================================ */
        .alert-custom { 
            border-radius: 12px; 
            border: none; 
            padding: 12px 16px; 
            font-size: 13px; 
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success-custom { background: #ecfdf5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-danger-custom { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
        .alert-warning-custom { background: #fffbeb; color: #92400e; border-left: 4px solid #f59e0b; }
        .alert-info-custom { background: #eff6ff; color: #1e40af; border-left: 4px solid #3b82f6; }
        
        /* ================================================================ */
        /* TIMELINE */
        /* ================================================================ */
        .timeline-item {
            display: flex;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .timeline-item:last-child { border-bottom: none; }
        .timeline-item .timeline-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
        }
        .timeline-item .timeline-icon.blue { background: #dbeafe; color: #2563eb; }
        .timeline-item .timeline-icon.green { background: #d1fae5; color: #059669; }
        .timeline-item .timeline-icon.orange { background: #fef3c7; color: #d97706; }
        .timeline-item .timeline-icon.red { background: #fee2e2; color: #dc2626; }
        .timeline-item .timeline-content { flex: 1; }
        .timeline-item .timeline-content .title { font-weight: 500; color: #0f172a; }
        .timeline-item .timeline-content .desc { font-size: 12px; color: #64748b; }
        .timeline-item .timeline-content .time { font-size: 10px; color: #94a3b8; }
        
        /* ================================================================ */
        /* RESPONSIVE */
        /* ================================================================ */
        @media (max-width: 992px) {
            .stat-mini-card { padding: 12px 14px; }
            .stat-mini-card .stat-content .stat-value { font-size: 16px; }
            .three-col-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 768px) { 
            .stat-mini-card .stat-content .stat-value { font-size: 15px; }
            .stat-mini-card .stat-icon { width: 38px; height: 38px; font-size: 15px; }
            .card-header-custom { flex-direction: column; align-items: flex-start; }
            .table-custom { font-size: 12px; }
            .table-custom thead th, .table-custom tbody td { padding: 6px 8px; }
            .three-col-grid { grid-template-columns: 1fr; }
            .three-col-grid .col-item .label { min-width: 80px; font-size: 11px; }
            .two-col-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 480px) {
            .stat-mini-card { flex-direction: column; text-align: center; align-items: center; }
            .stat-mini-card .stat-content .stat-value { font-size: 14px; }
            .stat-mini-card .stat-icon { width: 34px; height: 34px; font-size: 13px; }
            .section-title-custom { font-size: 13px; }
            .btn-action { font-size: 10px; padding: 4px 10px; }
            .three-col-grid .col-item { flex-wrap: wrap; }
            .three-col-grid .col-item .label { min-width: 100%; margin-bottom: 2px; }
        }
        
        /* Scrollable Table */
        .scrollable-table { 
            max-height: 280px; 
            overflow-y: auto; 
            border: 1px solid #e5e7eb; 
            border-radius: 8px; 
        }
        .scrollable-table::-webkit-scrollbar { width: 4px; }
        .scrollable-table::-webkit-scrollbar-track { background: #f1f5f9; }
        .scrollable-table::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container-fluid py-3">

    <!-- ============================================================ -->
    <!-- HEADER -->
    <!-- ============================================================ -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 animate-fade-up">
        <div>
            <h5 style="color:#0f172a;margin:0;font-weight:700;font-size:18px;">
                <i class="fas fa-calendar-check" style="color:#3b82f6;"></i> Appointment Details
            </h5>
            <p class="text-muted" style="font-size:12px;margin:2px 0 0 0;">
                <i class="fas fa-hashtag me-1"></i><?php echo htmlspecialchars($appointment['appointment_number'] ?? 'N/A'); ?>
                <span class="mx-2">|</span>
                <i class="far fa-clock me-1"></i><?php echo date('d M Y, h:i A', strtotime($appointment['created_at'] ?? 'now')); ?>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?php echo BASE_URL; ?>/reception/appointments" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
            <?php if ($canEdit ?? false): ?>
                <button class="btn btn-primary btn-sm" onclick="editAppointment()">
                    <i class="fas fa-edit me-1"></i> Edit Status
                </button>
                <button class="btn btn-danger btn-sm" onclick="cancelAppointment()">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
            <?php endif; ?>
            <button class="btn btn-info btn-sm text-white" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Print
            </button>
            <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-outline-dark btn-sm">
                <i class="fas fa-home"></i>
            </a>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ALERTS -->
    <!-- ============================================================ -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-custom alert-success-custom mb-3 animate-fade-up animate-delay-1">
            <i class="fas fa-check-circle fa-lg"></i>
            <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size:10px;"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-custom alert-danger-custom mb-3 animate-fade-up animate-delay-1">
            <i class="fas fa-exclamation-circle fa-lg"></i>
            <div>
                <?php foreach ($_SESSION['errors'] as $e): ?>
                    <div><?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; unset($_SESSION['errors']); ?>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" style="font-size:10px;"></button>
        </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- MINI STATS DASHBOARD - 6 STATS -->
    <!-- ============================================================ -->
    <?php 
    $totalAmount = (float)($appointment['total_amount'] ?? 0);
    $paidAmount = (float)($appointment['payment_received'] ?? 0);
    $dueAmount = $totalAmount - $paidAmount;
    $status = $appointment['status'] ?? 'scheduled';
    $statusIcon = [
        'scheduled' => 'fa-clock',
        'confirmed' => 'fa-check-circle',
        'checked_in' => 'fa-user-check',
        'in_progress' => 'fa-spinner',
        'completed' => 'fa-check-double',
        'canceled' => 'fa-times-circle',
        'no_show' => 'fa-user-slash'
    ];
    $statusColor = [
        'scheduled' => 'blue',
        'confirmed' => 'blue',
        'checked_in' => 'orange',
        'in_progress' => 'orange',
        'completed' => 'green',
        'canceled' => 'red',
        'no_show' => 'secondary'
    ];
    $statusColors = [
        'scheduled' => 'primary',
        'confirmed' => 'info',
        'checked_in' => 'warning',
        'in_progress' => 'warning',
        'completed' => 'success',
        'canceled' => 'danger',
        'no_show' => 'secondary'
    ];
    $paymentColors = [
        'paid' => 'payment-paid',
        'pending' => 'payment-pending',
        'partial' => 'payment-partial',
        'refunded' => 'payment-refunded',
        'canceled' => 'payment-canceled'
    ];
    ?>
    
    <div class="row g-3 mb-4">
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <div class="stat-mini-card animate-fade-up animate-delay-1">
                <div class="stat-icon <?php echo $statusColor[$status] ?? 'blue'; ?>">
                    <i class="fas <?php echo $statusIcon[$status] ?? 'fa-clock'; ?>"></i>
                </div>
                <div class="stat-content">
                    <h6>Status</h6>
                    <div class="stat-value" style="font-size:14px;">
                        <span class="badge-status bg-<?php echo $statusColors[$status] ?? 'secondary'; ?> text-white">
                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <div class="stat-mini-card animate-fade-up animate-delay-2">
                <div class="stat-icon purple">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="stat-content">
                    <h6>Total Amount</h6>
                    <div class="stat-value">৳ <?php echo number_format($totalAmount, 2); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <div class="stat-mini-card animate-fade-up animate-delay-3">
                <div class="stat-icon green">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <div class="stat-content">
                    <h6>Paid Amount</h6>
                    <div class="stat-value" style="color:#059669;">৳ <?php echo number_format($paidAmount, 2); ?></div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <div class="stat-mini-card animate-fade-up animate-delay-4">
                <div class="stat-icon <?php echo $dueAmount > 0 ? 'red' : 'green'; ?>">
                    <i class="fas <?php echo $dueAmount > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                </div>
                <div class="stat-content">
                    <h6>Due Amount</h6>
                    <div class="stat-value" style="color:<?php echo $dueAmount > 0 ? '#dc2626' : '#059669'; ?>;">
                        ৳ <?php echo number_format($dueAmount, 2); ?>
                    </div>
                    <?php if ($dueAmount == 0): ?>
                        <div class="stat-sub">Fully Paid</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <div class="stat-mini-card animate-fade-up animate-delay-5">
                <div class="stat-icon teal">
                    <i class="fas fa-credit-card"></i>
                </div>
                <div class="stat-content">
                    <h6>Payment Status</h6>
                    <div class="stat-value" style="font-size:14px;">
                        <span class="payment-status-badge <?php echo $paymentColors[$appointment['payment_status'] ?? 'pending'] ?? 'payment-pending'; ?>">
                            <?php echo ucfirst($appointment['payment_status'] ?? 'Pending'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
            <div class="stat-mini-card animate-fade-up animate-delay-6">
                <div class="stat-icon indigo">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="stat-content">
                    <h6>Service</h6>
                    <div class="stat-value" style="font-size:13px;">
                        <?php echo htmlspecialchars($appointment['service_name'] ?? 'Consultation'); ?>
                    </div>
                    <div class="stat-sub">Fee: ৳ <?php echo number_format($appointment['service_fee'] ?? 0, 2); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MAIN CARD -->
    <!-- ============================================================ -->
    <div class="card-custom animate-fade-up animate-delay-1">
        <div class="card-header-custom">
            <span>
                <i class="fas fa-calendar-check text-primary me-2"></i> 
                Appointment #<?php echo htmlspecialchars($appointment['appointment_number']); ?>
                <span class="badge bg-light text-dark ms-2">
                    <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?>
                </span>
            </span>
            <div class="d-flex gap-2 flex-wrap">
                <span class="badge-status bg-<?php echo $statusColors[$appointment['status'] ?? 'scheduled'] ?? 'secondary'; ?> text-white">
                    <i class="fas <?php echo $statusIcon[$appointment['status'] ?? 'scheduled']; ?>"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $appointment['status'] ?? 'pending')); ?>
                </span>
                <span class="payment-status-badge <?php echo $paymentColors[$appointment['payment_status'] ?? 'pending'] ?? 'payment-pending'; ?>">
                    <i class="fas fa-<?php echo ($appointment['payment_status'] ?? 'pending') == 'paid' ? 'check-circle' : 'clock'; ?>"></i>
                    <?php echo ucfirst($appointment['payment_status'] ?? 'Pending'); ?>
                </span>
            </div>
        </div>
        <div class="card-body p-3 p-md-4">

            <!-- ============================================================ -->
            <!-- PATIENT INFORMATION - THREE COLUMN LAYOUT -->
            <!-- ============================================================ -->
            <div class="section-title-custom">
                <span class="title-icon blue"><i class="fas fa-user"></i></span>
                Patient Information
                <span class="badge-count ms-auto">
                    <i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($appointment['patient_code'] ?? ''); ?>
                </span>
            </div>
            
            <div class="three-col-grid">
                <!-- Column 1 -->
                <div class="col-item">
                    <span class="label">Name</span>
                    <span class="value"><strong><?php echo htmlspecialchars($appointment['patient_name']); ?></strong></span>
                </div>
                <div class="col-item">
                    <span class="label">Gender</span>
                    <span class="value">
                        <?php 
                        $genderIcon = ['male' => 'fa-mars', 'female' => 'fa-venus', 'other' => 'fa-genderless'];
                        $gender = strtolower($appointment['patient_gender'] ?? '');
                        echo '<i class="fas ' . ($genderIcon[$gender] ?? 'fa-user') . ' me-1"></i> ' . ucfirst($appointment['patient_gender'] ?? '');
                        ?>
                    </span>
                </div>
                <div class="col-item">
                    <span class="label">Date of Birth</span>
                    <span class="value">
                        <?php
                        $dob = $appointment['patient_dob'] ?? '';
                        if (!empty($dob) && $dob != '0000-00-00') {
                            $birth = new DateTime($dob);
                            $today = new DateTime('today');
                            $age = $birth->diff($today);
                            echo date('d/m/Y', strtotime($dob)) . ' <span class="text-muted">(' . $age->y . 'Y ' . $age->m . 'M)</span>';
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
                <div class="col-item">
                    <span class="label">Phone</span>
                    <span class="value">
                        <a href="tel:<?php echo htmlspecialchars($appointment['patient_phone'] ?? ''); ?>" class="text-primary text-decoration-none">
                            <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($appointment['patient_phone'] ?? ''); ?>
                        </a>
                    </span>
                </div>
                <div class="col-item">
                    <span class="label">Email</span>
                    <span class="value">
                        <?php if (!empty($appointment['patient_email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($appointment['patient_email']); ?>" class="text-primary text-decoration-none">
                                <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($appointment['patient_email']); ?>
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </span>
                </div>
                <div class="col-item" style="align-items: flex-start; grid-column: span 1;">
                    <span class="label" style="padding-top:2px;">Address</span>
                    <span class="value address-value">
                        <?php 
                        $address = $appointment['patient_address'] ?? 'N/A';
                        $addressLines = array_unique(array_filter(explode("\n", $address)));
                        $address = implode("\n", $addressLines);
                        echo nl2br(htmlspecialchars(trim($address)));
                        ?>
                    </span>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- DOCTOR INFORMATION - THREE COLUMN LAYOUT -->
            <!-- ============================================================ -->
            <div class="section-title-custom mt-4">
                <span class="title-icon green"><i class="fas fa-user-md"></i></span>
                Doctor Information
                <span class="badge-count ms-auto">
                    <i class="fas fa-stethoscope me-1"></i> <?php echo htmlspecialchars($appointment['specialization'] ?? 'General'); ?>
                </span>
            </div>
            
            <div class="three-col-grid">
                <!-- Column 1 -->
                <div class="col-item">
                    <span class="label">Doctor</span>
                    <span class="value"><strong>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></strong></span>
                </div>
                <div class="col-item">
                    <span class="label">Specialization</span>
                    <span class="value"><?php echo htmlspecialchars($appointment['specialization'] ?? 'General'); ?></span>
                </div>
                <div class="col-item">
                    <span class="label">Qualification</span>
                    <span class="value"><?php echo htmlspecialchars($appointment['qualification'] ?? 'MBBS'); ?></span>
                </div>
                <div class="col-item">
                    <span class="label">BMDC Number</span>
                    <span class="value"><?php echo htmlspecialchars($appointment['bmdc_number'] ?? 'N/A'); ?></span>
                </div>
                <div class="col-item">
                    <span class="label">Consultation Fee</span>
                    <span class="value"><strong>৳ <?php echo number_format($appointment['consultation_fee'] ?? 0, 2); ?></strong></span>
                </div>
                <div class="col-item">
                    <span class="label">Phone</span>
                    <span class="value">
                        <?php if (!empty($appointment['doctor_phone'])): ?>
                            <a href="tel:<?php echo htmlspecialchars($appointment['doctor_phone']); ?>" class="text-primary text-decoration-none">
                                <i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($appointment['doctor_phone']); ?>
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </span>
                </div>
                <div class="col-item">
                    <span class="label">Email</span>
                    <span class="value">
                        <?php if (!empty($appointment['doctor_email'])): ?>
                            <a href="mailto:<?php echo htmlspecialchars($appointment['doctor_email']); ?>" class="text-primary text-decoration-none">
                                <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($appointment['doctor_email']); ?>
                            </a>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (!empty($appointment['doctor_info_en']) || !empty($appointment['doctor_info_bn'])): ?>
                <div class="col-item" style="align-items: flex-start; grid-column: span 1;">
                    <span class="label" style="padding-top:2px;">Info</span>
                    <span class="value" style="line-height:1.6;">
                        <?php 
                        if (!empty($appointment['doctor_info_en'])) {
                            echo nl2br(htmlspecialchars($appointment['doctor_info_en']));
                        } elseif (!empty($appointment['doctor_info_bn'])) {
                            echo nl2br(htmlspecialchars($appointment['doctor_info_bn']));
                        }
                        ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <div class="section-divider"></div>

            <!-- ============================================================ -->
            <!-- APPOINTMENT DETAILS - THREE COLUMN LAYOUT -->
            <!-- ============================================================ -->
            <div class="section-title-custom">
                <span class="title-icon orange"><i class="fas fa-info-circle"></i></span>
                Appointment Details
            </div>
            
            <div class="three-col-grid">
                <div class="col-item">
                    <span class="label">Date</span>
                    <span class="value"><strong><?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></strong></span>
                </div>
                <div class="col-item">
                    <span class="label">Session</span>
                    <span class="value">
                        <span class="badge bg-<?php echo ($appointment['session_type'] ?? '') == 'morning' ? 'warning' : 'secondary'; ?>">
                            <i class="fas fa-<?php echo ($appointment['session_type'] ?? '') == 'morning' ? 'sun' : 'moon'; ?> me-1"></i>
                            <?php echo ucfirst($appointment['session_type'] ?? ''); ?>
                        </span>
                    </span>
                </div>
                <div class="col-item">
                    <span class="label">Time</span>
                    <span class="value">
                        <i class="far fa-clock me-1 text-muted"></i>
                        <?php echo date('h:i A', strtotime($appointment['start_time'])); ?> - <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                    </span>
                </div>
                <div class="col-item">
                    <span class="label">Type</span>
                    <span class="value">
                        <span class="badge-tag"><?php echo ucfirst(str_replace('_', ' ', $appointment['appointment_type'] ?? 'Regular')); ?></span>
                    </span>
                </div>
                <div class="col-item">
                    <span class="label">Service</span>
                    <span class="value">
                        <strong><?php echo htmlspecialchars($appointment['service_name'] ?? 'Consultation'); ?></strong>
                        <span class="text-muted ms-1">(৳ <?php echo number_format($appointment['service_fee'] ?? 0, 2); ?>)</span>
                    </span>
                </div>
                <?php if (!empty($queue)): ?>
                <div class="col-item">
                    <span class="label">Queue</span>
                    <span class="value">
                        <span class="badge bg-<?php echo $queue['status'] == 'waiting' ? 'warning' : ($queue['status'] == 'in_progress' ? 'primary' : 'success'); ?>">
                            <i class="fas fa-chart-line me-1"></i>
                            <?php echo htmlspecialchars($queue['serial_number']); ?> - <?php echo ucfirst($queue['status']); ?>
                        </span>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Symptoms & Notes - Three Column -->
            <?php if (!empty($appointment['symptoms']) || !empty($appointment['special_note']) || !empty($appointment['cancellation_reason'])): ?>
            <div class="three-col-grid mt-3">
                <?php if (!empty($appointment['symptoms'])): ?>
                <div class="col-item" style="align-items: flex-start;">
                    <span class="label" style="padding-top:2px;">Symptoms</span>
                    <span class="value"><?php echo htmlspecialchars($appointment['symptoms']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($appointment['special_note'])): ?>
                <div class="col-item" style="align-items: flex-start;">
                    <span class="label" style="padding-top:2px;">Special Note</span>
                    <span class="value"><?php echo htmlspecialchars($appointment['special_note']); ?></span>
                </div>
                <?php endif; ?>
                <?php if (!empty($appointment['cancellation_reason'])): ?>
                <div class="col-item" style="align-items: flex-start;">
                    <span class="label" style="padding-top:2px;color:#dc2626;">Cancel Reason</span>
                    <span class="value" style="color:#dc2626;"><?php echo htmlspecialchars($appointment['cancellation_reason']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="section-divider"></div>

            <!-- ============================================================ -->
            <!-- PAYMENT INFORMATION - THREE COLUMN LAYOUT -->
            <!-- ============================================================ -->
            <div class="section-title-custom">
                <span class="title-icon purple"><i class="fas fa-credit-card"></i></span>
                Payment Information
            </div>
            
            <div class="three-col-grid">
                <div class="col-item">
                    <span class="label">Total Amount</span>
                    <span class="value"><strong>৳ <?php echo number_format($appointment['total_amount'] ?? 0, 2); ?></strong></span>
                </div>
                <div class="col-item">
                    <span class="label">Discount</span>
                    <span class="value">- ৳ <?php echo number_format($appointment['discount_amount'] ?? 0, 2); ?></span>
                </div>
                <div class="col-item">
                    <span class="label">Tax</span>
                    <span class="value">+ ৳ <?php echo number_format($appointment['tax_amount'] ?? 0, 2); ?></span>
                </div>
                <div class="col-item">
                    <span class="label">Paid Amount</span>
                    <span class="value"><strong style="color:#059669;">৳ <?php echo number_format($appointment['payment_received'] ?? 0, 2); ?></strong></span>
                </div>
                <div class="col-item">
                    <span class="label">Due Amount</span>
                    <span class="value"><strong style="color:<?php echo ($appointment['total_amount'] - $appointment['payment_received']) > 0 ? '#dc2626' : '#059669'; ?>;">
                        ৳ <?php echo number_format(($appointment['total_amount'] ?? 0) - ($appointment['payment_received'] ?? 0), 2); ?>
                    </strong></span>
                </div>
                <div class="col-item">
                    <span class="label">Payment Method</span>
                    <span class="value"><?php echo ucfirst($appointment['payment_method'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- BILL INFORMATION - THREE COLUMN LAYOUT -->
            <!-- ============================================================ -->
            <?php if ($bill): ?>
            <div class="section-title-custom mt-4">
                <span class="title-icon teal"><i class="fas fa-file-invoice"></i></span>
                Bill Information
                <span class="badge-count ms-auto">
                    <i class="fas fa-receipt me-1"></i> <?php echo htmlspecialchars($bill['bill_number']); ?>
                </span>
            </div>
            
            <div class="three-col-grid">
                <div class="col-item">
                    <span class="label">Bill Number</span>
                    <span class="value">
                        <a href="<?php echo BASE_URL; ?>/bills/view/<?php echo $bill['id']; ?>" class="text-primary text-decoration-none" target="_blank">
                            <?php echo htmlspecialchars($bill['bill_number']); ?>
                            <i class="fas fa-external-link-alt ms-1" style="font-size:10px;"></i>
                        </a>
                    </span>
                </div>
                <div class="col-item">
                    <span class="label">Bill Date</span>
                    <span class="value"><?php echo date('d M Y', strtotime($bill['bill_date'])); ?></span>
                </div>
                <div class="col-item">
                    <span class="label">Items</span>
                    <span class="value"><?php echo $bill['item_count'] ?? 0; ?></span>
                </div>
                <div class="col-item">
                    <span class="label">Total Amount</span>
                    <span class="value"><strong>৳ <?php echo number_format($bill['total_amount'] ?? 0, 2); ?></strong></span>
                </div>
                <div class="col-item">
                    <span class="label">Payment Status</span>
                    <span class="value">
                        <span class="payment-status-badge <?php echo $paymentColors[$bill['payment_status'] ?? 'pending'] ?? 'payment-pending'; ?>">
                            <i class="fas fa-<?php echo ($bill['payment_status'] ?? 'pending') == 'paid' ? 'check-circle' : 'clock'; ?> me-1"></i>
                            <?php echo ucfirst($bill['payment_status'] ?? 'Pending'); ?>
                        </span>
                    </span>
                </div>
            </div>

            <?php if (!empty($bill['items'])): ?>
            <div class="scrollable-table mt-2">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th style="width:5%;">#</th>
                            <th style="width:45%;">Description</th>
                            <th style="width:10%;">Qty</th>
                            <th style="width:20%;">Unit Price</th>
                            <th style="width:20%;" class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bill['items'] as $idx => $item): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>৳ <?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
                            <td class="text-end"><strong>৳ <?php echo number_format($item['total_amount'] ?? 0, 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="background:#f8fafc;font-weight:700;">
                            <td colspan="4" class="text-end">Total</td>
                            <td class="text-end">৳ <?php echo number_format($bill['total_amount'] ?? 0, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <!-- ============================================================ -->
            <!-- PAYMENT HISTORY - TABLE -->
            <!-- ============================================================ -->
            <?php if (!empty($payments)): ?>
            <div class="section-title-custom mt-4">
                <span class="title-icon indigo"><i class="fas fa-history"></i></span>
                Payment History
                <span class="badge-count ms-auto">
                    <i class="fas fa-coins me-1"></i> <?php echo count($payments); ?> transactions
                </span>
            </div>
            <div class="scrollable-table">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td>
                                <span class="fw-medium"><?php echo date('d M Y', strtotime($payment['payment_date'])); ?></span>
                                <br><span class="text-muted" style="font-size:10px;"><?php echo date('h:i A', strtotime($payment['payment_date'])); ?></span>
                            </td>
                            <td><strong>৳ <?php echo number_format($payment['amount'] ?? 0, 2); ?></strong></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo ucfirst($payment['payment_method'] ?? 'Cash'); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $payment['status'] == 'completed' ? 'success' : ($payment['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($payment['status'] ?? 'pending'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($payment['received_by_name'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- ============================================================ -->
            <!-- TIMELINE / ACTIVITY LOG -->
            <!-- ============================================================ -->
            <div class="section-title-custom mt-4">
                <span class="title-icon pink"><i class="fas fa-clock"></i></span>
                Activity Timeline
            </div>
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-icon green"><i class="fas fa-calendar-plus"></i></div>
                    <div class="timeline-content">
                        <div class="title">Appointment Created</div>
                        <div class="desc">Appointment was booked for <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></div>
                        <div class="time"><i class="far fa-clock me-1"></i> <?php echo date('d M Y, h:i A', strtotime($appointment['created_at'] ?? 'now')); ?></div>
                    </div>
                </div>
                <?php if ($appointment['status'] == 'confirmed' || $appointment['status'] == 'checked_in' || $appointment['status'] == 'in_progress' || $appointment['status'] == 'completed'): ?>
                <div class="timeline-item">
                    <div class="timeline-icon blue"><i class="fas fa-check-circle"></i></div>
                    <div class="timeline-content">
                        <div class="title">Appointment Confirmed</div>
                        <div class="desc">Appointment status changed to <strong><?php echo ucfirst($appointment['status']); ?></strong></div>
                        <div class="time"><i class="far fa-clock me-1"></i> <?php echo date('d M Y, h:i A', strtotime($appointment['updated_at'] ?? 'now')); ?></div>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($appointment['status'] == 'canceled'): ?>
                <div class="timeline-item">
                    <div class="timeline-icon red"><i class="fas fa-times-circle"></i></div>
                    <div class="timeline-content">
                        <div class="title">Appointment Cancelled</div>
                        <div class="desc">Appointment was cancelled: <?php echo htmlspecialchars($appointment['cancellation_reason'] ?? 'No reason provided'); ?></div>
                        <div class="time"><i class="far fa-clock me-1"></i> <?php echo date('d M Y, h:i A', strtotime($appointment['updated_at'] ?? 'now')); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="section-divider"></div>

            <!-- ============================================================ -->
            <!-- FOOTER -->
            <!-- ============================================================ -->
            <div class="row mt-2">
                <div class="col-md-6">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i> Created: <?php echo date('d/m/Y h:i A', strtotime($appointment['created_at'] ?? 'now')); ?>
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <i class="fas fa-edit me-1"></i> Updated: <?php echo date('d/m/Y h:i A', strtotime($appointment['updated_at'] ?? 'now')); ?>
                    </small>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top text-center">
                <small class="text-muted">
                    <i class="fas fa-calendar-check me-1"></i> 
                    Appointment #<?php echo htmlspecialchars($appointment['appointment_number']); ?> | 
                    <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?>
                    <span class="mx-2">•</span>
                    <?php echo date('h:i A', strtotime($appointment['start_time'])); ?> - <?php echo date('h:i A', strtotime($appointment['end_time'])); ?>
                </small>
            </div>

        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODALS -->
<!-- ============================================================ -->

<!-- Cancel Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger me-2"></i> Cancel Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <p class="text-muted">Are you sure you want to cancel this appointment?</p>
                <div class="mb-3">
                    <label for="cancelReason" class="form-label fw-medium">Cancellation Reason</label>
                    <select class="form-select" id="cancelReason" style="border-radius:8px;">
                        <option value="Patient requested">Patient requested</option>
                        <option value="Doctor unavailable">Doctor unavailable</option>
                        <option value="Rescheduled">Rescheduled</option>
                        <option value="No show">No show</option>
                        <option value="Cancelled by staff" selected>Cancelled by staff</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label for="cancelNote" class="form-label fw-medium">Additional Note <span class="text-muted">(Optional)</span></label>
                    <textarea class="form-control" id="cancelNote" rows="2" placeholder="Enter additional details..." style="border-radius:8px;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="confirmCancel()">
                    <i class="fas fa-times me-1"></i> Cancel Appointment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title"><i class="fas fa-edit text-primary me-2"></i> Update Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="mb-3">
                    <label for="statusSelect" class="form-label fw-medium">Select Status</label>
                    <select class="form-select" id="statusSelect" style="border-radius:8px;">
                        <option value="scheduled" <?php echo ($appointment['status'] ?? '') == 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="confirmed" <?php echo ($appointment['status'] ?? '') == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="checked_in" <?php echo ($appointment['status'] ?? '') == 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                        <option value="in_progress" <?php echo ($appointment['status'] ?? '') == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo ($appointment['status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="no_show" <?php echo ($appointment['status'] ?? '') == 'no_show' ? 'selected' : ''; ?>>No Show</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="confirmStatusUpdate()">
                    <i class="fas fa-save me-1"></i> Update Status
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const APPOINTMENT_ID = <?php echo $appointment['id']; ?>;

// ================================================================
// STATUS UPDATE
// ================================================================
function editAppointment() {
    var modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

function confirmStatusUpdate() {
    var status = document.getElementById('statusSelect').value;
    
    if (!status) {
        alert('Please select a status');
        return;
    }
    
    if (status == 'completed' || status == 'no_show') {
        if (!confirm('Are you sure you want to mark this appointment as ' + status + '?')) {
            return;
        }
    }
    
    var btn = document.querySelector('#statusModal .btn-primary');
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Updating...';
    btn.disabled = true;
    
    $.ajax({
        url: BASE_URL + '/api/appointments/update-status',
        type: 'POST',
        data: {
            appointment_id: APPOINTMENT_ID,
            status: status
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        },
        error: function() {
            alert('Error updating status. Please try again.');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
}

// ================================================================
// CANCEL APPOINTMENT
// ================================================================
function cancelAppointment() {
    var modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

function confirmCancel() {
    var reason = document.getElementById('cancelReason').value;
    var note = document.getElementById('cancelNote').value;
    
    var fullReason = reason;
    if (note.trim()) {
        fullReason += ' - ' + note.trim();
    }
    
    if (!confirm('Are you sure you want to cancel this appointment?')) {
        return;
    }
    
    var btn = document.querySelector('#cancelModal .btn-danger');
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Cancelling...';
    btn.disabled = true;
    
    $.ajax({
        url: BASE_URL + '/api/appointments/cancel',
        type: 'POST',
        data: {
            appointment_id: APPOINTMENT_ID,
            cancel_reason: fullReason
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.message);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            }
        },
        error: function() {
            alert('Error cancelling appointment. Please try again.');
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }
    });
}
</script>

</body>
</html>