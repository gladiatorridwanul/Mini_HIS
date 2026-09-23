<?php
// app/views/prescriptions/view.php - Complete Working Version
// FIXED: Properly displays all medicines after multiple edits
// FIXED: Shows ALL medicines from ALL edits

if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 2));

// Include helper to avoid redeclaration errors
require_once BASE_PATH . '/app/helpers/DateHelper.php';

// Helper function to get DOB from multiple possible keys (only if not already defined)
if (!function_exists('getPatientDob')) {
    function getPatientDob($data) {
        $keys = ['date_of_birth', 'patient_date_of_birth', 'dob', 'patient_dob'];
        foreach ($keys as $key) {
            if (!empty($data[$key]) && $data[$key] !== '0000-00-00') {
                return $data[$key];
            }
        }
        return null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Prescription - <?php echo htmlspecialchars($prescription['prescription_number']); ?> - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== ALL STYLES ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        .card-custom { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header-custom { background: white; border-bottom: 2px solid #3b82f6; padding: 10px 15px; font-weight: 600; font-size: 14px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 4px 16px; }
        .info-grid .info-item { display: flex; padding: 3px 0; border-bottom: 1px solid #f8fafc; }
        .info-grid .info-item .label { font-weight: 500; color: #64748b; min-width: 90px; font-size: 12px; }
        .info-grid .info-item .value { color: #1e293b; font-weight: 500; font-size: 13px; }
        .section-divider { border-top: 2px solid #e5e7eb; margin: 16px 0; }
        .badge-tag { background: #f1f5f9; color: #475569; padding: 2px 10px; border-radius: 12px; font-size: 11px; display: inline-block; margin: 2px 4px 2px 0; }
        .badge-status { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .vital-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 4px; }
        .vital-item { background: #f8fafc; padding: 4px 8px; border-radius: 4px; border: 1px solid #e9edf2; }
        .vital-item .vital-label { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; display: block; }
        .vital-item .vital-value { font-weight: 600; font-size: 14px; color: #0f172a; }
        .medicine-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .medicine-table thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; padding: 8px 10px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .medicine-table tbody td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        .medicine-table tbody tr:hover { background: #f8fafc; }
        .medicine-table tbody tr:last-child td { border-bottom: none; }
        
        .detail-pill { display: inline-block; border-radius: 20px; padding: 2px 10px; font-size: 11px; margin: 1px 3px 1px 0; }
        .detail-pill-frequency { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .detail-pill-duration { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .detail-pill-instruction { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .detail-pill-relation { background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
        
        .vaccine-pill { display: inline-block; border-radius: 20px; padding: 2px 10px; font-size: 11px; margin: 1px 3px 1px 0; }
        .vaccine-pill-given { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .vaccine-pill-due { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .vaccine-pill-overdue { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .lab-test-pill { display: inline-block; border-radius: 20px; padding: 2px 10px; font-size: 11px; margin: 1px 3px 1px 0; }
        .lab-test-pill-routine { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .lab-test-pill-urgent { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .lab-test-pill-stat { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .detail-line { display: flex; flex-wrap: wrap; align-items: center; gap: 3px 4px; margin-bottom: 3px; padding: 2px 0; }
        .detail-line .separator { color: #94a3b8; font-size: 12px; font-weight: bold; }
        .detail-line .instruction-text { font-size: 11px; color: #475569; font-style: italic; background: #f8fafc; padding: 1px 6px; border-radius: 4px; }
        .detail-line .empty-detail { color: #94a3b8; font-size: 11px; font-style: italic; }
        
        .alert-custom { border-radius: 10px; border: none; padding: 10px 14px; font-size: 13px; }
        .alert-success-custom { background: #ecfdf5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-danger-custom { background: #fef2f2; color: #991b1b; border-left: 4px solid #ef4444; }
        .relation-badge { display: inline-block; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-size: 11px; color: #475569; }
        .relation-badge.empty { background: transparent; color: #94a3b8; }
        
        .lab-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .lab-table thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; padding: 6px 8px; border-bottom: 2px solid #e2e8f0; text-align: left; position: sticky; top: 0; z-index: 1; }
        .lab-table tbody td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .lab-table tbody tr:hover { background: #f8fafc; }
        .lab-table .abnormal { color: #dc2626; font-weight: 600; }
        .lab-table .normal { color: #059669; }
        
        .scrollable-table { max-height: 250px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; }
        .scrollable-table::-webkit-scrollbar { width: 4px; }
        .scrollable-table::-webkit-scrollbar-track { background: #f1f5f9; }
        .scrollable-table::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        
        .section-badge { font-size: 11px; background: #e2e8f0; color: #475569; padding: 2px 10px; border-radius: 12px; font-weight: 500; }
        
        .history-display-item { display: inline-block; background: #f1f5f9; padding: 2px 12px; border-radius: 12px; font-size: 12px; margin: 2px 4px 2px 0; color: #1e293b; border: 1px solid #e2e8f0; }
        .history-display-item .history-duration { color: #64748b; font-size: 11px; }
        .history-display-item .history-remarks { color: #059669; font-size: 11px; font-style: italic; }
        
        .doctor-header {
            display: flex;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 6px;
            margin-bottom: 10px;
            width: 100%;
            background: #f8fafc;
            border-radius: 6px 6px 0 0;
            padding: 10px 14px;
        }
        .doctor-header-left { width: 50%; padding-right: 10px; border-right: 1px solid #cbd5e1; box-sizing: border-box; }
        .doctor-header-right { width: 50%; padding-left: 10px; text-align: right; font-family: 'Nikosh', 'Siyam Rupali', 'Kalpurush', 'Times New Roman', Arial, sans-serif; box-sizing: border-box; }
        .doctor-header .doctor-info-en, .doctor-header .doctor-info-bn { font-size: 11px; line-height: 1.4; word-wrap: break-word; }
        .doctor-header .doctor-info-en .info-line, .doctor-header .doctor-info-bn .info-line { display: block; margin: 0; padding: 0; line-height: 1.4; }
        .doctor-header .doctor-info-en .info-line.doctor-name, .doctor-header .doctor-info-bn .info-line.doctor-name { font-weight: bold; font-size: 14px; line-height: 1.4; }
        .doctor-header .doctor-info-en { font-family: 'Times New Roman', Arial, sans-serif; }
        .doctor-header .doctor-info-bn { font-family: 'Nikosh', 'Siyam Rupali', 'Kalpurush', 'Times New Roman', Arial, sans-serif; text-align: right; }
        
        .lab-test-list-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .lab-test-list-table thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; padding: 6px 8px; border-bottom: 2px solid #e2e8f0; text-align: left; position: sticky; top: 0; z-index: 1; }
        .lab-test-list-table tbody td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .lab-test-list-table tbody tr:hover { background: #f8fafc; }
        
        .prescription-lab-tests-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .prescription-lab-tests-table thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; padding: 6px 8px; border-bottom: 2px solid #e2e8f0; text-align: left; position: sticky; top: 0; z-index: 1; }
        .prescription-lab-tests-table tbody td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .prescription-lab-tests-table tbody tr:hover { background: #f8fafc; }
        
        .vaccine-list-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .vaccine-list-table thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; padding: 6px 8px; border-bottom: 2px solid #e2e8f0; text-align: left; position: sticky; top: 0; z-index: 1; }
        .vaccine-list-table tbody td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .vaccine-list-table tbody tr:hover { background: #f8fafc; }
        
        .vaccine-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .vaccine-table thead th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 10px; text-transform: uppercase; letter-spacing: 0.3px; padding: 6px 8px; border-bottom: 2px solid #e2e8f0; text-align: left; position: sticky; top: 0; z-index: 1; }
        .vaccine-table tbody td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .vaccine-table tbody tr:hover { background: #f8fafc; }
        
        .medicine-count-badge {
            display: inline-block;
            background: #3b82f6;
            color: white;
            padding: 0 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) { 
            .info-grid { grid-template-columns: 1fr; } 
            .vital-grid { grid-template-columns: 1fr 1fr; }
            .doctor-header { flex-direction: column; }
            .doctor-header-left { width: 100%; border-right: none; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 8px; }
            .doctor-header-right { width: 100%; text-align: left; padding-left: 0; }
        }
        @media (max-width: 480px) { 
            .vital-grid { grid-template-columns: 1fr; } 
            .medicine-table { font-size: 11px; } 
            .detail-pill { font-size: 10px; padding: 1px 6px; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-2">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 style="color:#1f2937;margin:0;"><i class="fas fa-prescription" style="color:#3b82f6;"></i> View Prescription</h5>
            <p class="text-muted" style="font-size:11px;">Prescription #<?php echo htmlspecialchars($prescription['prescription_number']); ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
            <a href="<?php echo BASE_URL; ?>/prescriptions/print/<?php echo $prescription['id']; ?>" class="btn btn-info btn-sm" target="_blank"><i class="fas fa-print"></i> Print</a>
            <a href="<?php echo BASE_URL; ?>/prescriptions/print-pad/<?php echo $prescription['id']; ?>" class="btn btn-warning btn-sm" target="_blank"><i class="fas fa-print"></i> Print Pad</a>
            <?php if (in_array($prescription['status'], ['draft','issued'])): ?>
            <a href="<?php echo BASE_URL; ?>/prescriptions/edit/<?php echo $prescription['id']; ?>" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary btn-sm"><i class="fas fa-home"></i></a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-custom alert-success-custom mb-3"><i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['errors'])): ?>
        <div class="alert-custom alert-danger-custom mb-3">
            <?php foreach($_SESSION['errors'] as $e): ?><div><?php echo htmlspecialchars($e); ?></div><?php endforeach; unset($_SESSION['errors']); ?>
        </div>
    <?php endif; ?>

    <!-- PRESCRIPTION CARD -->
    <div class="card-custom">
        <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-prescription text-primary"></i> Prescription #<?php echo htmlspecialchars($prescription['prescription_number']); ?></span>
            <div class="d-flex gap-2 flex-wrap">
                <?php
                    $sc = ['draft'=>'secondary','issued'=>'primary','dispensed'=>'info','completed'=>'success','canceled'=>'danger'];
                    $pc = ['pending'=>'warning','processing'=>'info','ready'=>'primary','dispensed'=>'success','collected'=>'dark'];
                ?>
                <span class="badge-status bg-<?php echo $sc[$prescription['status']] ?? 'secondary'; ?> text-white"><?php echo ucfirst($prescription['status']); ?></span>
                <span class="badge-status bg-<?php echo $pc[$prescription['pharmacy_status']] ?? 'secondary'; ?> text-white"><?php echo ucfirst($prescription['pharmacy_status']); ?></span>
                <?php if (!empty($prescription['printed_count']) && $prescription['printed_count'] > 0): ?>
                    <span class="badge-status bg-secondary text-white"><i class="fas fa-print me-1"></i>Printed: <?php echo $prescription['printed_count']; ?>x</span>
                <?php endif; ?>
                <?php if (!empty($prescription['last_printed_at'])): ?>
                    <span class="badge-status bg-light text-dark border">Last: <?php echo date('d/m/Y H:i', strtotime($prescription['last_printed_at'])); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-3">

            <!-- ============================================================ -->
            <!-- DOCTOR HEADER - 50/50 Split                                  -->
            <!-- ============================================================ -->
            <?php 
            $doctorInfoEn = trim($prescription['doctor_info_en'] ?? '');
            $doctorInfoBn = trim($prescription['doctor_info_bn'] ?? '');
            $hasAppointment = !empty($prescription['appointment_id']);
            
            if (empty($doctorInfoEn)) {
                $enLines = [];
                $title = !empty($prescription['doctor_title']) ? $prescription['doctor_title'] : 'Dr.';
                $name = !empty($prescription['doctor_name']) ? $prescription['doctor_name'] : 'System Admin';
                $enLines[] = $title . ' ' . $name;
                if (!empty($prescription['qualification'])) $enLines[] = $prescription['qualification'];
                if (!empty($prescription['specialization'])) $enLines[] = $prescription['specialization'];
                if (!empty($prescription['bmdc_number'])) $enLines[] = 'BMDC Reg. No: ' . $prescription['bmdc_number'];
                $doctorInfoEn = implode("\n", $enLines);
            }
            
            if (empty($doctorInfoBn)) {
                $bnLines = [];
                $nameBn = !empty($prescription['doctor_name_bn']) ? $prescription['doctor_name_bn'] : (!empty($prescription['doctor_name']) ? $prescription['doctor_name'] : 'System Admin');
                $bnLines[] = 'ডা. ' . $nameBn;
                if (!empty($prescription['qualification_bn'])) $bnLines[] = $prescription['qualification_bn'];
                elseif (!empty($prescription['qualification'])) $bnLines[] = $prescription['qualification'];
                if (!empty($prescription['specialization_bn'])) $bnLines[] = $prescription['specialization_bn'];
                elseif (!empty($prescription['specialization'])) $bnLines[] = $prescription['specialization'];
                if (!empty($prescription['bmdc_number'])) $bnLines[] = 'বিএমডিসি নং: ' . $prescription['bmdc_number'];
                $doctorInfoBn = implode("\n", $bnLines);
            }
            ?>
            
            <?php if (!empty($doctorInfoEn) || !empty($doctorInfoBn)): ?>
                <div class="doctor-header">
                    <div class="doctor-header-left">
                        <div class="doctor-info-en">
                            <?php if ($hasAppointment): ?>
                                <span class="info-line" style="font-size:10px;color:#059669;font-weight:600;">
                                    <i class="fas fa-calendar-check me-1"></i>Appointment Doctor
                                </span>
                            <?php else: ?>
                                <span class="info-line" style="font-size:10px;color:#64748b;font-weight:600;">
                                    <i class="fas fa-user-md me-1"></i>Prescribing Doctor
                                </span>
                            <?php endif; ?>
                            <?php 
                            $lines = explode("\n", $doctorInfoEn);
                            foreach ($lines as $index => $line):
                                $line = trim($line);
                                if (!empty($line)):
                                    $class = ($index === 0) ? 'doctor-name' : '';
                            ?>
                                <span class="info-line <?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                            <?php if ($hasAppointment): ?>
                                <span class="info-line" style="font-size:9px;color:#64748b;margin-top:4px;">
                                    <i class="fas fa-hashtag me-1"></i>Appointment #<?php echo htmlspecialchars($prescription['appointment_id']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="doctor-header-right">
                        <div class="doctor-info-bn">
                            <?php if ($hasAppointment): ?>
                                <span class="info-line" style="font-size:10px;color:#059669;font-weight:600;">
                                    <i class="fas fa-calendar-check me-1"></i>অ্যাপয়েন্টমেন্ট ডাক্তার
                                </span>
                            <?php else: ?>
                                <span class="info-line" style="font-size:10px;color:#64748b;font-weight:600;">
                                    <i class="fas fa-user-md me-1"></i>প্রেসক্রিপশন ডাক্তার
                                </span>
                            <?php endif; ?>
                            <?php 
                            $lines = explode("\n", $doctorInfoBn);
                            foreach ($lines as $index => $line):
                                $line = trim($line);
                                if (!empty($line)):
                                    $class = ($index === 0) ? 'doctor-name' : '';
                            ?>
                                <span class="info-line <?php echo $class; ?>"><?php echo htmlspecialchars($line); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                            <?php if ($hasAppointment): ?>
                                <span class="info-line" style="font-size:9px;color:#64748b;margin-top:4px;">
                                    <i class="fas fa-hashtag me-1"></i>অ্যাপয়েন্টমেন্ট #<?php echo htmlspecialchars($prescription['appointment_id']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ============================================================ -->
            <!-- PATIENT INFO                                                  -->
            <!-- ============================================================ -->
            <?php
            $dob = getPatientDob($prescription);
            $age = calculateAge($dob);
            ?>
            <h6 class="mb-2"><i class="fas fa-user text-primary me-2"></i>Patient Information</h6>
            <div class="info-grid mb-3">
                <div class="info-item"><span class="label">Patient ID</span><span class="value"><?php echo htmlspecialchars($prescription['patient_code'] ?? ''); ?></span></div>
                <div class="info-item"><span class="label">Name</span><span class="value"><?php echo htmlspecialchars($prescription['patient_name']); ?></span></div>
                <div class="info-item"><span class="label">Visit #</span><span class="value"><?php echo $prescription['visit_number'] ?? 1; ?></span></div>
                <div class="info-item"><span class="label">Date of Birth</span><span class="value"><?php echo $dob ? date('d/m/Y', strtotime($dob)) : 'N/A'; ?></span></div>
                <div class="info-item"><span class="label">Age</span><span class="value"><?php echo $age; ?></span></div>
                <div class="info-item"><span class="label">Gender</span><span class="value"><?php echo ucfirst($prescription['gender'] ?? ''); ?></span></div>
                <div class="info-item"><span class="label">Phone</span><span class="value"><?php echo htmlspecialchars($prescription['phone'] ?? ''); ?></span></div>
                <div class="info-item"><span class="label">Marital Status</span><span class="value"><?php echo ucfirst($prescription['marital_status'] ?? 'N/A'); ?></span></div>
                <div class="info-item"><span class="label">Occupation</span><span class="value"><?php echo htmlspecialchars($prescription['occupation'] ?? 'N/A'); ?></span></div>
                <div class="info-item"><span class="label">Rx Date</span><span class="value"><?php echo date('d/m/Y', strtotime($prescription['prescription_date'])); ?></span></div>
                <?php if (!empty($prescription['appointment_id'])): ?>
                <div class="info-item">
                    <span class="label">Appointment</span>
                    <span class="value">
                        <a href="<?php echo BASE_URL; ?>/appointments/view/<?php echo $prescription['appointment_id']; ?>" 
                           class="text-primary" style="text-decoration:none;" 
                           target="_blank" title="View Appointment Details">
                            #<?php echo $prescription['appointment_id']; ?>
                            <i class="fas fa-external-link-alt" style="font-size:9px;"></i>
                        </a>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- ============================================================ -->
            <!-- CHIEF COMPLAINTS & TREATMENT HISTORY - SIDE BY SIDE           -->
            <!-- ============================================================ -->
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <h6 class="mb-2"><i class="fas fa-notes-medical text-primary me-2"></i>Chief Complaints</h6>
                    <?php if (!empty($prescription['chief_complaints'])): ?>
                        <div>
                            <?php foreach ($prescription['chief_complaints'] as $c): ?>
                                <span class="badge-tag">
                                    <?php echo htmlspecialchars($c['complaint']); ?>
                                    <?php if (!empty($c['duration'])): ?><span class="text-muted"> (<?php echo htmlspecialchars($c['duration']); ?>)</span><?php endif; ?>
                                    <?php if (!empty($c['remarks'])): ?><span class="text-muted"> - <?php echo htmlspecialchars($c['remarks']); ?></span><?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">No chief complaints recorded.</span>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-6">
                    <h6 class="mb-2"><i class="fas fa-notes-medical text-primary me-2"></i>Treatment History</h6>
                    <?php 
                    $treatmentHistory = $treatmentHistory ?? [];
                    ?>
                    <?php if (!empty($treatmentHistory)): ?>
                        <div>
                            <?php foreach ($treatmentHistory as $t): ?>
                                <span class="history-display-item">
                                    <?php echo htmlspecialchars($t['treatment_name']); ?>
                                    <?php if (!empty($t['duration'])): ?>
                                        <span class="history-duration">(<?php echo htmlspecialchars($t['duration']); ?>)</span>
                                    <?php endif; ?>
                                    <?php if (!empty($t['remarks'])): ?>
                                        <span class="history-remarks">- <?php echo htmlspecialchars($t['remarks']); ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">No treatment history recorded.</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DRUG & DISEASE HISTORY -->
            <div class="row">
                <?php if (!empty($prescription['drug_history'])): ?>
                    <div class="col-md-6 mb-3">
                        <h6 class="mb-2"><i class="fas fa-pills text-primary me-2"></i>Drug History</h6>
                        <?php foreach ($prescription['drug_history'] as $d): ?>
                            <span class="badge-tag"><?php echo htmlspecialchars($d['drug_name']); ?><?php if (!empty($d['duration'])): ?> <span class="text-muted">(<?php echo htmlspecialchars($d['duration']); ?>)</span><?php endif; ?><?php if (!empty($d['remarks'])): ?> <span class="text-muted">- <?php echo htmlspecialchars($d['remarks']); ?></span><?php endif; ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($prescription['disease_history'])): ?>
                    <div class="col-md-6 mb-3">
                        <h6 class="mb-2"><i class="fas fa-notes-medical text-primary me-2"></i>Disease History</h6>
                        <?php foreach ($prescription['disease_history'] as $d): ?>
                            <span class="badge-tag"><?php echo htmlspecialchars($d['disease']); ?><?php if (!empty($d['duration'])): ?> <span class="text-muted">(<?php echo htmlspecialchars($d['duration']); ?>)</span><?php endif; ?><?php if (!empty($d['remarks'])): ?> <span class="text-muted">- <?php echo htmlspecialchars($d['remarks']); ?></span><?php endif; ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SPECIAL NOTE -->
            <?php if (!empty($prescription['special_note'])): ?>
                <h6 class="mb-2"><i class="fas fa-sticky-note text-primary me-2"></i>Special Note</h6>
                <div class="mb-3 p-2 bg-light rounded"><?php echo nl2br(htmlspecialchars($prescription['special_note'])); ?></div>
            <?php endif; ?>

            <div class="section-divider"></div>

            <!-- PHYSICAL EXAM -->
            <?php if (!empty($prescription['physical_exam']) || !empty($prescription['vital_signs'])): ?>
                <h6 class="mb-2"><i class="fas fa-heartbeat text-primary me-2"></i>Physical Examination</h6>
                <div class="row mb-2">
                    <div class="col-12">
                        <span class="fw-bold" style="font-size:12px;color:#475569;">G/E: </span>
                        <span class="badge-tag">Anaemia: <?php echo ($prescription['physical_exam']['anaemia']??0) ? 'Yes' : 'No'; ?></span>
                        <span class="badge-tag">Jaundice: <?php echo ($prescription['physical_exam']['jaundice']??0) ? 'Yes' : 'No'; ?></span>
                        <span class="badge-tag">Cyanosis: <?php echo ($prescription['physical_exam']['cyanosis']??0) ? 'Yes' : 'No'; ?></span>
                        <span class="badge-tag">Oedema: <?php echo ($prescription['physical_exam']['oedema']??0) ? 'Yes' : 'No'; ?></span>
                        <span class="badge-tag">Dehydration: <?php echo ($prescription['physical_exam']['dehydration']??0) ? 'Yes' : 'No'; ?></span>
                    </div>
                </div>

                <div class="vital-grid mb-2">
                    <?php $vs = $prescription['vital_signs'] ?? []; ?>
                    <?php if (!empty($vs['pulse'])): ?><div class="vital-item"><span class="vital-label">Pulse</span><span class="vital-value"><?php echo $vs['pulse']; ?> bpm</span></div><?php endif; ?>
                    <?php if (!empty($vs['weight'])): ?><div class="vital-item"><span class="vital-label">Weight</span><span class="vital-value"><?php echo $vs['weight']; ?> Kg</span></div><?php endif; ?>
                    <?php if (!empty($vs['respiratory_rate'])): ?><div class="vital-item"><span class="vital-label">R/R</span><span class="vital-value"><?php echo $vs['respiratory_rate']; ?> /min</span></div><?php endif; ?>
                    <?php if (!empty($vs['length'])): ?><div class="vital-item"><span class="vital-label">Length</span><span class="vital-value"><?php echo $vs['length']; ?> cm</span></div><?php endif; ?>
                    <?php if (!empty($vs['blood_pressure_systolic']) || !empty($vs['blood_pressure_diastolic'])): ?>
                        <div class="vital-item"><span class="vital-label">BP</span><span class="vital-value"><?php echo ($vs['blood_pressure_systolic']??'?') . '/' . ($vs['blood_pressure_diastolic']??'?'); ?> mmHg</span></div>
                    <?php endif; ?>
                    <?php if (!empty($vs['temperature'])): ?><div class="vital-item"><span class="vital-label">Temperature</span><span class="vital-value"><?php echo $vs['temperature']; ?> °C</span></div><?php endif; ?>
                    <?php if (!empty($vs['oxygen_saturation'])): ?><div class="vital-item"><span class="vital-label">O₂ Sat</span><span class="vital-value"><?php echo $vs['oxygen_saturation']; ?> %</span></div><?php endif; ?>
                    <?php if (!empty($vs['bmi'])): ?><div class="vital-item"><span class="vital-label">BMI</span><span class="vital-value"><?php echo $vs['bmi']; ?></span></div><?php endif; ?>
                    <?php if (!empty($vs['others'])): ?><div class="vital-item"><span class="vital-label">Others</span><span class="vital-value" style="font-size:12px;"><?php echo htmlspecialchars($vs['others']); ?></span></div><?php endif; ?>
                </div>

                <?php $pe = $prescription['physical_exam'] ?? []; ?>
                <div class="row mb-3">
                    <?php if (!empty($pe['abdomen'])): ?><div class="col-md-3 mb-1"><span class="fw-bold" style="font-size:12px;color:#475569;">Abdomen:</span> <?php echo htmlspecialchars($pe['abdomen']); ?></div><?php endif; ?>
                    <?php if (!empty($pe['cvs'])): ?><div class="col-md-3 mb-1"><span class="fw-bold" style="font-size:12px;color:#475569;">CVS:</span> <?php echo htmlspecialchars($pe['cvs']); ?></div><?php endif; ?>
                    <?php if (!empty($pe['respiratory'])): ?><div class="col-md-3 mb-1"><span class="fw-bold" style="font-size:12px;color:#475569;">Respiratory:</span> <?php echo htmlspecialchars($pe['respiratory']); ?></div><?php endif; ?>
                    <?php if (!empty($pe['lymphoreticular'])): ?><div class="col-md-3 mb-1"><span class="fw-bold" style="font-size:12px;color:#475569;">Lymphoreticular:</span> <?php echo htmlspecialchars($pe['lymphoreticular']); ?></div><?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- INVESTIGATIONS -->
            <?php if (!empty($prescription['investigations'])): ?>
                <h6 class="mb-2"><i class="fas fa-microscope text-primary me-2"></i>Investigations</h6>
                <div class="mb-3">
                    <?php foreach ($prescription['investigations'] as $inv): ?>
                        <span class="badge-tag"><i class="fas fa-flask text-info" style="font-size:10px;"></i> <?php echo htmlspecialchars($inv['investigation_name']); ?><?php if (!empty($inv['remarks'])): ?> <span class="text-muted">- <?php echo htmlspecialchars($inv['remarks']); ?></span><?php endif; ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ============================================================ -->
            <!-- LAB TEST REQUEST SECTION                                      -->
            <!-- ============================================================ -->
            <?php 
            $labTests = $prescription['lab_tests'] ?? [];
            ?>
            
            <?php if (!empty($labTests)): ?>
                <h6 class="mb-2"><i class="fas fa-flask text-primary me-2"></i>Lab Test Request</h6>
                <div class="mb-3">
                    <div class="scrollable-table">
                        <table class="prescription-lab-tests-table">
                            <thead>
                                <tr>
                                    <th style="width:5%;">#</th>
                                    <th style="width:40%;">Test Name</th>
                                    <th style="width:15%;">Priority</th>
                                    <th style="width:40%;">Notes / Instructions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($labTests as $idx => $test): ?>
                                    <tr>
                                        <td><?php echo $idx + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                                            <?php if (!empty($test['test_code'])): ?>
                                                <span class="text-muted" style="font-size:10px;">(<?php echo htmlspecialchars($test['test_code']); ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $priority = $test['priority'] ?? 'routine';
                                            $priorityClass = 'lab-test-pill-routine';
                                            $priorityLabel = 'Routine';
                                            if ($priority == 'urgent') {
                                                $priorityClass = 'lab-test-pill-urgent';
                                                $priorityLabel = 'Urgent';
                                            } elseif ($priority == 'stat') {
                                                $priorityClass = 'lab-test-pill-stat';
                                                $priorityLabel = 'STAT';
                                            }
                                            ?>
                                            <span class="lab-test-pill <?php echo $priorityClass; ?>"><?php echo $priorityLabel; ?></span>
                                        </td>
                                        <td>
                                            <?php if (!empty($test['notes'])): ?>
                                                <span class="text-muted" style="font-size:12px;"><?php echo htmlspecialchars($test['notes']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size:11px;font-style:italic;">No notes</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- DIAGNOSIS -->
            <?php if (!empty($prescription['diagnosis'])): ?>
                <h6 class="mb-2"><i class="fas fa-stethoscope text-primary me-2"></i>Diagnosis</h6>
                <div class="mb-3 p-2 bg-light rounded"><?php echo nl2br(htmlspecialchars($prescription['diagnosis'])); ?></div>
            <?php endif; ?>

            <div class="section-divider"></div>

            <!-- ============================================================ -->
            <!-- MEDICINES - Shows ALL medicines with complete details         -->
            <!-- Shows ALL medicines from ALL edits                           -->
            <!-- ============================================================ -->
            <?php if (!empty($prescription['items'])): ?>
                <h6 class="mb-2">
                    <i class="fas fa-prescription text-primary me-2"></i>Treatment / Medicines 
                    <span class="medicine-count-badge"><?php echo count($prescription['items']); ?></span>
                </h6>
                <div class="table-responsive mb-3">
                    <table class="medicine-table">
                        <thead>
                            <tr>
                                <th style="width:4%;">#</th>
                                <th style="width:22%;">Drug</th>
                                <th style="width:10%;">Dosage Form</th>
                                <th style="width:38%;">Frequency / Duration / Instruction</th>
                                <th style="width:15%;">Relation to Food</th>
                                <th style="width:11%;">Qty / Refills</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Display ALL medicines from the prescription items
                            foreach ($prescription['items'] as $idx => $item): 
                            ?>
                                <tr>
                                    <td><?php echo $idx + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($item['drug_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['dosage'] ?? ''); ?></td>
                                    <td>
                                        <?php 
                                        $hasAnyDetail = false;
                                        if (!empty($item['details']) && is_array($item['details'])): 
                                        ?>
                                            <?php foreach ($item['details'] as $d): ?>
                                                <?php 
                                                $hasFreq = !empty($d['frequency']);
                                                $hasDur = !empty($d['duration']);
                                                $hasInst = !empty($d['instruction']);
                                                if ($hasFreq || $hasDur || $hasInst):
                                                    $hasAnyDetail = true;
                                                ?>
                                                    <div class="detail-line">
                                                        <?php if ($hasFreq): ?>
                                                            <span class="detail-pill detail-pill-frequency"><?php echo htmlspecialchars($d['frequency']); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($hasFreq && ($hasDur || $hasInst)): ?>
                                                            <span class="separator">•</span>
                                                        <?php endif; ?>
                                                        <?php if ($hasDur): ?>
                                                            <span class="detail-pill detail-pill-duration"><?php echo htmlspecialchars($d['duration']); ?></span>
                                                        <?php endif; ?>
                                                        <?php if ($hasDur && $hasInst): ?>
                                                            <span class="separator">•</span>
                                                        <?php endif; ?>
                                                        <?php if ($hasInst): ?>
                                                            <span class="instruction-text">"<?php echo htmlspecialchars($d['instruction']); ?>"</span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <?php if (!$hasAnyDetail):
                                            $itemHasFreq = !empty($item['frequency']);
                                            $itemHasDur = !empty($item['duration']);
                                            $itemHasInst = !empty($item['instructions']);
                                            if ($itemHasFreq || $itemHasDur || $itemHasInst):
                                        ?>
                                                <div class="detail-line">
                                                    <?php if ($itemHasFreq): ?>
                                                        <span class="detail-pill detail-pill-frequency"><?php echo htmlspecialchars($item['frequency']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($itemHasFreq && ($itemHasDur || $itemHasInst)): ?>
                                                        <span class="separator">•</span>
                                                    <?php endif; ?>
                                                    <?php if ($itemHasDur): ?>
                                                        <span class="detail-pill detail-pill-duration"><?php echo htmlspecialchars($item['duration']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($itemHasDur && $itemHasInst): ?>
                                                        <span class="separator">•</span>
                                                    <?php endif; ?>
                                                    <?php if ($itemHasInst): ?>
                                                        <span class="instruction-text">"<?php echo htmlspecialchars($item['instructions']); ?>"</span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="empty-detail">No details specified</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $relation = $item['relation_to_food'] ?? '';
                                        if (!empty($relation)): 
                                            echo '<span class="detail-pill detail-pill-relation">' . htmlspecialchars(ucfirst($relation)) . '</span>';
                                        else:
                                            echo '<span class="relation-badge empty">—</span>';
                                        endif;
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $qty = (int)($item['quantity'] ?? 1);
                                        $refills = (int)($item['refills'] ?? 0);
                                        echo '<span class="badge-tag">Qty: ' . $qty . '</span>';
                                        if ($refills > 0) {
                                            echo ' <span class="badge-tag">Refills: ' . $refills . '</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info py-2" style="font-size:13px;">
                    <i class="fas fa-info-circle me-1"></i> No medicines have been added to this prescription yet.
                </div>
            <?php endif; ?>

            <!-- ===== PATIENT-LEVEL VACCINE LIST ===== -->
            <?php 
            $patientVaccines = $patientVaccines ?? [];
            ?>
            
            <?php if (!empty($patientVaccines)): ?>
                <div class="section-divider"></div>
                <h6 class="mb-2">
                    <i class="fas fa-syringe text-primary me-2"></i>Patient Vaccine History 
                    <span class="section-badge"><?php echo count($patientVaccines); ?> records</span>
                </h6>
                <div class="mb-3">
                    <div class="scrollable-table">
                        <table class="vaccine-list-table">
                            <thead>
                                <tr>
                                    <th>Vaccine Name</th>
                                    <th>Dose</th>
                                    <th>Date Given</th>
                                    <th>Next Due</th>
                                    <th>Batch/Lot</th>
                                    <th>Administered By</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patientVaccines as $vac): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($vac['vaccine_name'] ?? ''); ?></strong></td>
                                        <td><?php echo htmlspecialchars($vac['dose'] ?? $vac['dose_number'] ?? ''); ?></td>
                                        <td><?php echo !empty($vac['date_given']) ? date('d/m/Y', strtotime($vac['date_given'])) : '-'; ?></td>
                                        <td>
                                            <?php 
                                            $nextDue = $vac['next_due'] ?? $vac['next_due_date'] ?? '';
                                            if (!empty($nextDue)):
                                                $today = new DateTime();
                                                $dueDate = new DateTime($nextDue);
                                                $diff = $today->diff($dueDate);
                                                $days = (int)$diff->format('%r%a');
                                                if ($days < 0):
                                                    echo '<span class="vaccine-pill vaccine-pill-overdue">' . date('d/m/Y', strtotime($nextDue)) . ' (Overdue ' . abs($days) . 'd)</span>';
                                                elseif ($days <= 7):
                                                    echo '<span class="vaccine-pill vaccine-pill-due">' . date('d/m/Y', strtotime($nextDue)) . ' (Due in ' . $days . 'd)</span>';
                                                else:
                                                    echo '<span class="vaccine-pill vaccine-pill-given">' . date('d/m/Y', strtotime($nextDue)) . '</span>';
                                                endif;
                                            else:
                                                echo '-';
                                            endif;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($vac['batch_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($vac['administered_by'] ?? ''); ?></td>
                                        <td>
                                            <?php 
                                            $dateGiven = $vac['date_given'] ?? '';
                                            if (!empty($dateGiven)):
                                                echo '<span class="vaccine-pill vaccine-pill-given"><i class="fas fa-check-circle me-1"></i>Given</span>';
                                            else:
                                                echo '<span class="vaccine-pill vaccine-pill-due"><i class="fas fa-clock me-1"></i>Pending</span>';
                                            endif;
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ===== PATIENT-LEVEL LAB TEST LIST ===== -->
            <?php 
            $patientLabTests = $patientLabTests ?? [];
            ?>
            
            <?php if (!empty($patientLabTests)): ?>
                <div class="section-divider"></div>
                <h6 class="mb-2">
                    <i class="fas fa-flask text-primary me-2"></i>Patient Lab Test History 
                    <span class="section-badge"><?php echo count($patientLabTests); ?> records</span>
                </h6>
                <div class="mb-3">
                    <div class="scrollable-table">
                        <table class="lab-test-list-table">
                            <thead>
                                <tr>
                                    <th>Test Name</th>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Result</th>
                                    <th>Normal Range</th>
                                    <th>Unit</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patientLabTests as $test): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($test['test_name'] ?? ''); ?></strong></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($test['order_number'] ?? ''); ?></span></td>
                                        <td><?php echo !empty($test['test_date']) ? date('d/m/Y', strtotime($test['test_date'])) : (!empty($test['result_date']) ? date('d/m/Y', strtotime($test['result_date'])) : '-'); ?></td>
                                        <td>
                                            <?php 
                                            $result = $test['result_value'] ?? $test['result'] ?? '';
                                            $isAbnormal = $test['is_abnormal'] ?? 0;
                                            if (!empty($result)):
                                                $class = $isAbnormal ? 'abnormal' : 'normal';
                                                echo '<span class="' . $class . '">' . htmlspecialchars($result) . '</span>';
                                                if ($isAbnormal):
                                                    echo ' <i class="fas fa-exclamation-triangle text-danger" style="font-size:10px;" title="Abnormal"></i>';
                                                endif;
                                            else:
                                                echo '<span class="text-muted">—</span>';
                                            endif;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($test['normal_range'] ?? $test['reference_range'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($test['unit'] ?? ''); ?></td>
                                        <td>
                                            <?php 
                                            $status = $test['status'] ?? '';
                                            $statusMap = [
                                                'pending' => 'warning',
                                                'sample_collected' => 'info',
                                                'processing' => 'primary',
                                                'completed' => 'success',
                                                'reviewed' => 'dark',
                                                'ordered' => 'secondary'
                                            ];
                                            if (!empty($status)):
                                                echo '<span class="badge bg-' . ($statusMap[$status] ?? 'secondary') . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
                                            endif;
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ===== VACCINATIONS FROM THIS SPECIFIC PRESCRIPTION ===== -->
            <?php 
            $vaccinations = $prescription['vaccinations'] ?? [];
            ?>
            
            <?php if (!empty($vaccinations)): ?>
                <div class="section-divider"></div>
                <h6 class="mb-2">
                    <i class="fas fa-syringe text-primary me-2"></i>This Prescription - Vaccinations 
                    <span class="section-badge"><?php echo count($vaccinations); ?> records</span>
                </h6>
                <div class="mb-3">
                    <div class="scrollable-table">
                        <table class="vaccine-table">
                            <thead>
                                <tr>
                                    <th>Vaccine Name</th>
                                    <th>Dose</th>
                                    <th>Date Given</th>
                                    <th>Next Due</th>
                                    <th>Batch/Lot</th>
                                    <th>Site</th>
                                    <th>Administered By</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vaccinations as $vac): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($vac['vaccine_name'] ?? ''); ?></strong></td>
                                        <td><?php echo htmlspecialchars($vac['dose'] ?? $vac['dose_number'] ?? ''); ?></td>
                                        <td><?php echo !empty($vac['date_given']) ? date('d/m/Y', strtotime($vac['date_given'])) : '-'; ?></td>
                                        <td>
                                            <?php 
                                            $nextDue = $vac['next_due'] ?? $vac['next_due_date'] ?? '';
                                            if (!empty($nextDue)):
                                                $today = new DateTime();
                                                $dueDate = new DateTime($nextDue);
                                                $diff = $today->diff($dueDate);
                                                $days = (int)$diff->format('%r%a');
                                                if ($days < 0):
                                                    echo '<span class="vaccine-pill vaccine-pill-overdue">' . date('d/m/Y', strtotime($nextDue)) . ' (Overdue ' . abs($days) . 'd)</span>';
                                                elseif ($days <= 7):
                                                    echo '<span class="vaccine-pill vaccine-pill-due">' . date('d/m/Y', strtotime($nextDue)) . ' (Due in ' . $days . 'd)</span>';
                                                else:
                                                    echo '<span class="vaccine-pill vaccine-pill-given">' . date('d/m/Y', strtotime($nextDue)) . '</span>';
                                                endif;
                                            else:
                                                echo '-';
                                            endif;
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($vac['batch_number'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($vac['site'] ?? $vac['injection_site'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($vac['administered_by'] ?? ''); ?></td>
                                        <td>
                                            <?php 
                                            $dateGiven = $vac['date_given'] ?? '';
                                            if (!empty($dateGiven)):
                                                echo '<span class="vaccine-pill vaccine-pill-given"><i class="fas fa-check-circle me-1"></i>Given</span>';
                                            else:
                                                echo '<span class="vaccine-pill vaccine-pill-due"><i class="fas fa-clock me-1"></i>Pending</span>';
                                            endif;
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ===== LAB HISTORY PIVOT VIEW ===== -->
            <?php 
            $labHistory = $labHistory ?? [];
            ?>
            
            <?php if (!empty($labHistory['test_names']) && !empty($labHistory['results'])): ?>
                <div class="section-divider"></div>
                <h6 class="mb-2"><i class="fas fa-flask text-primary me-2"></i>Lab History (Pivot View)</h6>
                <div class="mb-3">
                    <div class="scrollable-table">
                        <table class="lab-table">
                            <thead>
                                <tr>
                                    <th style="min-width:100px;">Date</th>
                                    <th style="min-width:80px;">Order #</th>
                                    <?php foreach ($labHistory['test_names'] as $testName): ?>
                                        <th><?php echo htmlspecialchars($testName); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($labHistory['results'] as $dateKey => $row): ?>
                                    <tr>
                                        <td><strong><?php echo $row['date']; ?></strong></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['order_number']); ?></span></td>
                                        <?php foreach ($labHistory['test_names'] as $testName): ?>
                                            <td>
                                                <?php if (isset($row['tests'][$testName])): 
                                                    $test = $row['tests'][$testName];
                                                    $value = $test['value'] . ($test['unit'] ? ' ' . $test['unit'] : '');
                                                    $class = $test['is_abnormal'] ? 'abnormal' : 'normal';
                                                ?>
                                                    <span class="<?php echo $class; ?>"><?php echo htmlspecialchars($value); ?></span>
                                                    <?php if ($test['is_abnormal']): ?>
                                                        <i class="fas fa-exclamation-triangle text-danger" style="font-size:10px;" title="Abnormal"></i>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Showing recent lab results. Abnormal values are highlighted in <span class="text-danger">red</span>.</small>
                </div>
            <?php endif; ?>

            <!-- ADVICE -->
            <?php if (!empty($prescription['advice']['advice_text'])): ?>
                <div class="section-divider"></div>
                <h6 class="mb-2"><i class="fas fa-comment-medical text-primary me-2"></i>Advice</h6>
                <div class="mb-3 p-2 bg-light rounded"><?php echo nl2br(htmlspecialchars($prescription['advice']['advice_text'])); ?></div>
            <?php endif; ?>

            <!-- NEXT APPOINTMENT -->
            <?php if (!empty($prescription['advice']['follow_up_days']) || !empty($prescription['advice']['appointment_date'])): ?>
                <h6 class="mb-2"><i class="fas fa-calendar-alt text-primary me-2"></i>Next Appointment</h6>
                <div class="mb-3">
                    <?php if (!empty($prescription['advice']['follow_up_days']) && $prescription['advice']['follow_up_days'] > 0): ?>
                        <span class="badge bg-info"><?php echo $prescription['advice']['follow_up_days']; ?> days later</span>
                    <?php endif; ?>
                    <?php if (!empty($prescription['advice']['appointment_date'])): ?>
                        <span class="badge bg-secondary ms-1"><?php echo date('d/m/Y', strtotime($prescription['advice']['appointment_date'])); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="section-divider"></div>

            <!-- Footer Information -->
            <div class="row mt-2">
                <div class="col-md-6">
                    <small class="text-muted"><i class="fas fa-calendar me-1"></i> Created: <?php echo date('d/m/Y H:i', strtotime($prescription['created_at'] ?? 'now')); ?></small>
                    <?php if (!empty($prescription['updated_at']) && $prescription['updated_at'] != $prescription['created_at']): ?>
                        <br><small class="text-muted"><i class="fas fa-edit me-1"></i> Last Updated: <?php echo date('d/m/Y H:i', strtotime($prescription['updated_at'])); ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted"><i class="fas fa-print me-1"></i> Printed: <?php echo ($prescription['printed_count']??0); ?> times</small>
                    <?php if (!empty($prescription['last_printed_at'])): ?>
                        <br><small class="text-muted"><i class="fas fa-clock me-1"></i> Last Printed: <?php echo date('d/m/Y H:i', strtotime($prescription['last_printed_at'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-3 pt-2 border-top text-center">
                <small class="text-muted"><i class="fas fa-prescription me-1"></i> Prescription #<?php echo htmlspecialchars($prescription['prescription_number']); ?> | <?php echo date('d/m/Y', strtotime($prescription['prescription_date'])); ?></small>
                <?php if (!empty($prescription['doctor_name'])): ?>
                    <br><small class="text-muted"><i class="fas fa-user-md me-1"></i> Dr. <?php echo htmlspecialchars($prescription['doctor_name']); ?></small>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>