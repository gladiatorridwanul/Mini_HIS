<?php
$printMode = isset($_GET['print']) && $_GET['print'] == 'true';
$patientName = $patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient ID Card - <?php echo htmlspecialchars($patientName); ?> | UniDia Hospital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: <?php echo $printMode ? 'white' : 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; ?>;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            font-family: 'Segoe UI', 'Inter', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* ID Card Container - Landscape */
        .id-card-container {
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
        }
        
        /* ID Card - Landscape Style */
        .id-card {
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
            position: relative;
        }
        
        .id-card:hover {
            transform: translateY(-5px);
        }
        
        /* Card Body - Landscape Layout */
        .card-body-landscape {
            display: flex;
            flex-wrap: wrap;
        }
        
        /* Left Section - Photo & Basic Info */
        .card-left {
            width: 32%;
            background: linear-gradient(135deg, #1e5799 0%, #2b7bc1 100%);
            padding: 20px 15px;
            text-align: center;
            color: white;
        }
        
        /* Right Section - Details */
        .card-right {
            width: 68%;
            padding: 20px 20px;
            background: white;
        }
        
        /* Hospital Header */
        .hospital-header {
            text-align: center;
            padding-bottom: 12px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 15px;
        }
        
        .hospital-name {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 1px;
            margin: 0;
        }
        
        .hospital-tagline {
            font-size: 9px;
            opacity: 0.8;
            margin-top: 3px;
        }
        
        /* Patient Photo */
        .patient-photo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin: 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .patient-photo i {
            font-size: 55px;
            color: white;
        }
        
        .patient-name-large {
            font-size: 18px;
            font-weight: 700;
            margin: 10px 0 5px;
            word-wrap: break-word;
        }
        
        .blood-group-badge {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 4px 15px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 700;
            margin: 5px 0;
        }
        
        /* Card Type Badge */
        .card-type {
            background: rgba(255, 255, 255, 0.2);
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            margin-top: 8px;
        }
        
        /* Info Grid - Compact */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 15px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }
        
        .info-icon {
            width: 28px;
            height: 28px;
            background: #f0f2f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .info-icon i {
            font-size: 13px;
            color: #2b7bc1;
        }
        
        .info-content {
            flex: 1;
        }
        
        .info-label {
            font-size: 10px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        
        .info-value {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.3;
            word-break: break-word;
        }
        
        .info-value-small {
            font-size: 11px;
            font-weight: 500;
            color: #475569;
        }
        
        /* Address Section */
        .address-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 10px 12px;
            margin-top: 10px;
        }
        
        .address-title {
            font-size: 10px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .address-text {
            font-size: 11px;
            color: #334155;
            line-height: 1.4;
        }
        
        /* QR Code Section */
        .qr-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #e2e8f0;
        }
        
        .qr-code {
            width: 60px;
            height: 60px;
            background: #f8fafc;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qr-code i {
            font-size: 40px;
            color: #2b7bc1;
        }
        
        .signature-area {
            text-align: right;
        }
        
        .signature-text {
            font-family: 'Brush Script MT', cursive;
            font-size: 16px;
            color: #2c3e50;
            border-top: 1px solid #cbd5e1;
            padding-top: 5px;
            min-width: 120px;
        }
        
        .signature-label {
            font-size: 9px;
            color: #94a3b8;
        }
        
        /* Footer */
        .card-footer {
            background: #f8fafc;
            padding: 10px 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer-text {
            font-size: 9px;
            color: #64748b;
        }
        
        .footer-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 5px;
        }
        
        .footer-icons i {
            font-size: 11px;
            color: #94a3b8;
        }
        
        /* Action Buttons */
        .action-buttons {
            text-align: center;
            margin-top: 25px;
        }
        
        .btn-action {
            margin: 0 8px;
            padding: 10px 24px;
            border-radius: 40px;
            font-weight: 500;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn-action-primary {
            background: #2b7bc1;
            color: white;
            border: none;
        }
        
        .btn-action-primary:hover {
            background: #1e5799;
            transform: translateY(-2px);
        }
        
        .btn-action-secondary {
            background: white;
            color: #475569;
            border: 1px solid #e2e8f0;
        }
        
        .btn-action-secondary:hover {
            background: #f8fafc;
            transform: translateY(-2px);
        }
        
        .btn-action-success {
            background: #10b981;
            color: white;
            border: none;
        }
        
        .btn-action-success:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .action-buttons, .no-print {
                display: none !important;
            }
            .id-card {
                box-shadow: none;
                margin: 0;
                page-break-inside: avoid;
            }
            .card-left {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .blood-group-badge {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 20px; }
            .card-body-landscape { flex-direction: column; }
            .card-left { width: 100%; }
            .card-right { width: 100%; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="id-card-container">
        <!-- ID Card -->
        <div class="id-card">
            <div class="card-body-landscape">
                <!-- Left Section - Photo & Basic Info -->
                <div class="card-left">
                    <div class="hospital-header">
                        <div class="hospital-name">UNIDIA</div>
                        <div class="hospital-tagline">Hospital Management System</div>
                    </div>
                    
                    <div class="patient-photo">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    
                    <div class="patient-name-large">
                        <?php echo htmlspecialchars($patientName); ?>
                    </div>
                    
                    <?php if ($patient['blood_group']): ?>
                        <div class="blood-group-badge">
                            <i class="fas fa-tint me-1"></i> <?php echo $patient['blood_group']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-type">
                        <i class="fas fa-id-card me-1"></i> PATIENT ID CARD
                    </div>
                </div>
                
                <!-- Right Section - Details -->
                <div class="card-right">
                    <!-- ID Numbers Row -->
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Patient ID</div>
                                <div class="info-value"><?php echo $patient['patient_code']; ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Card Number</div>
                                <div class="info-value"><?php echo $patient['patient_id_card_number'] ?: 'N/A'; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Personal Information -->
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Contact Number</div>
                                <div class="info-value"><?php echo $patient['phone']; ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Email Address</div>
                                <div class="info-value info-value-small"><?php echo $patient['email'] ?: 'Not provided'; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-venus-mars"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?php echo ucfirst($patient['gender'] ?? 'Not specified'); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Date of Birth</div>
                                <div class="info-value">
                                    <?php 
                                    if ($patient['date_of_birth']) {
                                        echo date('d M Y', strtotime($patient['date_of_birth']));
                                        $dob = new DateTime($patient['date_of_birth']);
                                        $today = new DateTime();
                                        $age = $today->diff($dob)->y;
                                        echo " <span style='font-size: 10px; color: #64748b;'>($age yrs)</span>";
                                    } else {
                                        echo 'Not specified';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Registration Info -->
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Registration Date</div>
                                <div class="info-value"><?php echo date('d M Y', strtotime($patient['registration_date'])); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Status</div>
                                <div class="info-value">
                                    <span style="color: #10b981;">
                                        <i class="fas fa-circle" style="font-size: 8px;"></i> Active
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Address Section -->
                    <?php if ($patient['address'] || $patient['thana'] || $patient['district']): ?>
                    <div class="address-section">
                        <div class="address-title">
                            <i class="fas fa-map-marker-alt"></i> ADDRESS
                        </div>
                        <div class="address-text">
                            <?php 
                            $addressParts = [];
                            if ($patient['address']) $addressParts[] = $patient['address'];
                            if ($patient['thana']) $addressParts[] = $patient['thana'];
                            if ($patient['district']) $addressParts[] = $patient['district'];
                            if ($patient['division']) $addressParts[] = $patient['division'];
                            echo implode(', ', $addressParts) ?: 'Not provided';
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- QR Code & Signature -->
                    <div class="qr-section">
                        <div class="qr-code">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="signature-area">
                            <div class="signature-text">Authorized Signature</div>
                            <div class="signature-label">Hospital Authority</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="card-footer">
                <div class="footer-text">
                    <i class="fas fa-id-card me-1"></i> This card is property of UNIDIA Hospital • Valid for medical services
                </div>
                <div class="footer-icons">
                    <i class="fas fa-phone-alt"></i> Emergency: 999
                    <i class="fas fa-globe"></i> www.unidia.com
                    <i class="fas fa-envelope"></i> info@unidia.com
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <?php if (!$printMode): ?>
        <div class="action-buttons no-print">
            <a href="/unidia/public/patient/list" class="btn-action btn-action-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <button onclick="window.print()" class="btn-action btn-action-primary">
                <i class="fas fa-print"></i> Print ID Card
            </button>
            <button onclick="downloadPDF()" class="btn-action btn-action-success">
                <i class="fas fa-download"></i> Download PDF
            </button>
            <a href="/unidia/public/patient/register" class="btn-action btn-action-secondary">
                <i class="fas fa-user-plus"></i> Register Another
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function downloadPDF() {
            window.print();
            setTimeout(function() {
                alert('Click "Save as PDF" in the print dialog to download the ID card as PDF.');
            }, 500);
        }
        
        <?php if (isset($_GET['print']) && $_GET['print'] == 'true'): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        <?php endif; ?>
    </script>
</body>
</html>