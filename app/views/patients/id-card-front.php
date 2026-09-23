<?php
$patientName = $patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name'];
$printMode = isset($printMode) ? $printMode : false;

// ID Card Size: 3.15" × 1.9" (inches)
// Web Standard (96 PPI): 302px × 182px
$cardWidth = 302;
$cardHeight = 182;

// Colors
$primaryColor = '#1a56db';
$secondaryColor = '#2563eb';
$textColor = '#1e293b';
$lightBg = '#f0f4ff';

// Generate dynamic barcode using the patient code
require_once BASE_PATH . '/app/helpers/BarcodeHelper.php';
$barcodeSVG = BarcodeHelper::generateBarcodeSVG($patient['patient_code']);
$barcodeBase64 = base64_encode($barcodeSVG);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Card Front - <?php echo htmlspecialchars($patientName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: <?php echo $printMode ? 'white' : '#f0f2f5'; ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Segoe UI', 'Inter', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .id-card-wrapper {
            display: inline-block;
            <?php if (!$printMode): ?>
            box-shadow: 0 4px 16px rgba(0,0,0,0.10);
            border-radius: 6px;
            <?php endif; ?>
        }
        
        .id-card-front {
            width: <?php echo $cardWidth; ?>px;
            height: <?php echo $cardHeight; ?>px;
            background: white;
            overflow: hidden;
            position: relative;
            border: 1px solid #e2e8f0;
            <?php if (!$printMode): ?>
            border-radius: 6px;
            <?php endif; ?>
        }
        
        .card-header {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?>, <?php echo $secondaryColor; ?>);
            padding: 4px 10px 3px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: white;
            height: 22px;
        }
        
        .card-header .logo {
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }
        
        .card-header .logo i {
            margin-right: 3px;
            font-size: 8px;
        }
        
        .card-header .card-type {
            font-size: 5px;
            background: rgba(255,255,255,0.15);
            padding: 1px 6px;
            border-radius: 10px;
            font-weight: 500;
        }
        
        .card-body {
            display: flex;
            padding: 4px 8px;
            height: calc(100% - 22px - 18px);
        }
        
        .card-left {
            width: 30%;
            text-align: center;
            padding-right: 6px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .card-right {
            width: 70%;
            padding-left: 6px;
            border-left: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 1px;
        }
        
        .patient-photo {
            width: 38px;
            height: 38px;
            background: <?php echo $lightBg; ?>;
            border-radius: 50%;
            margin: 0 auto 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid <?php echo $secondaryColor; ?>;
            flex-shrink: 0;
        }
        
        .patient-photo i {
            font-size: 20px;
            color: <?php echo $secondaryColor; ?>;
        }
        
        .patient-name {
            font-size: 7.5px;
            font-weight: 700;
            color: <?php echo $textColor; ?>;
            margin-bottom: 1px;
            line-height: 1.1;
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .blood-group {
            display: inline-block;
            background: #dc2626;
            color: white;
            padding: 0px 6px;
            border-radius: 10px;
            font-size: 6px;
            font-weight: 700;
            margin: 1px 0;
            line-height: 12px;
        }
        
        .patient-code {
            font-size: 5.5px;
            color: #64748b;
            background: <?php echo $lightBg; ?>;
            padding: 0px 5px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 1px;
            line-height: 12px;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            gap: 3px;
            padding: 1px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-size: 5px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            width: 40px;
            flex-shrink: 0;
            letter-spacing: 0.2px;
        }
        
        .info-label i {
            width: 8px;
            margin-right: 1px;
            font-size: 5px;
        }
        
        .info-value {
            font-size: 6px;
            font-weight: 500;
            color: <?php echo $textColor; ?>;
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Dynamic Barcode on front card - subtle watermark */
        .front-barcode {
            position: absolute;
            bottom: 20px;
            right: 6px;
            width: 70px;
            height: 24px;
            opacity: 0.25;
        }
        
        .front-barcode img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .card-footer-front {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: <?php echo $lightBg; ?>;
            padding: 2px 10px;
            display: flex;
            justify-content: space-between;
            font-size: 4.5px;
            color: #64748b;
            height: 18px;
            align-items: center;
        }
        
        .card-footer-front span i {
            font-size: 4px;
            margin-right: 2px;
        }
        
        .action-buttons {
            margin-top: 16px;
            text-align: center;
        }
        
        .btn-action {
            padding: 8px 18px;
            border-radius: 6px;
            border: none;
            font-weight: 500;
            font-size: 12px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
        }
        
        .btn-print {
            background: #3b82f6;
            color: white;
        }
        
        .btn-print:hover {
            background: #2563eb;
            color: white;
        }
        
        .btn-back {
            background: #e5e7eb;
            color: #374151;
        }
        
        .btn-back:hover {
            background: #d1d5db;
            color: #374151;
        }
        
        .btn-purple {
            background: #8b5cf6;
            color: white;
        }
        
        .btn-purple:hover {
            background: #7c3aed;
            color: white;
        }
        
        /* Print Styles - Fixed 3.15in × 1.9in */
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                min-height: auto !important;
                display: block !important;
            }
            
            .id-card-wrapper {
                display: inline-block !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                page-break-inside: avoid !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .id-card-front {
                width: 3.15in !important;
                height: 1.9in !important;
                border: 1px solid #ccc !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .action-buttons {
                display: none !important;
            }
            
            .card-header {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .blood-group {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .card-footer-front {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .patient-photo {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .front-barcode {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .front-barcode img {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div>
        <div class="id-card-wrapper">
            <div class="id-card-front" id="idCardFront">
                <!-- Card Header -->
                <div class="card-header">
                    <div class="logo">
                        <i class="fas fa-heartbeat"></i> UNIDIA
                    </div>
                    <div class="card-type">
                        <i class="fas fa-id-card"></i> PATIENT ID
                    </div>
                </div>
                
                <!-- Card Body -->
                <div class="card-body">
                    <!-- Left Section -->
                    <div class="card-left">
                        <div class="patient-photo">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="patient-name"><?php echo htmlspecialchars($patientName); ?></div>
                        <?php if ($patient['blood_group']): ?>
                            <div class="blood-group"><?php echo $patient['blood_group']; ?></div>
                        <?php endif; ?>
                        <div class="patient-code">
                            <i class="fas fa-hashtag"></i> <?php echo $patient['patient_code']; ?>
                        </div>
                    </div>
                    
                    <!-- Right Section -->
                    <div class="card-right">
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-phone-alt"></i> Phone</span>
                            <span class="info-value"><?php echo $patient['phone']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-venus-mars"></i> Gender</span>
                            <span class="info-value"><?php echo ucfirst($patient['gender'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-calendar-alt"></i> DOB</span>
                            <span class="info-value">
                                <?php 
                                if ($patient['date_of_birth']) {
                                    echo date('d M Y', strtotime($patient['date_of_birth']));
                                    $dob = new DateTime($patient['date_of_birth']);
                                    $today = new DateTime();
                                    echo ' (' . $today->diff($dob)->y . 'y)';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-calendar-check"></i> Reg</span>
                            <span class="info-value"><?php echo date('d M Y', strtotime($patient['registration_date'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-map-marker-alt"></i> Addr</span>
                            <span class="info-value" style="font-size: 5.5px;">
                                <?php 
                                $addr = $patient['address'] ?: '';
                                $addr = strlen($addr) > 25 ? substr($addr, 0, 25) . '...' : $addr;
                                echo htmlspecialchars($addr ?: 'N/A');
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-user-check"></i> Status</span>
                            <span class="info-value" style="color: #10b981;">
                                <i class="fas fa-circle" style="font-size: 4px;"></i> Active
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Dynamic Barcode on front card -->
                <div class="front-barcode">
                    <img src="data:image/svg+xml;base64,<?php echo $barcodeBase64; ?>" alt="Barcode">
                </div>
                
                <!-- Footer -->
                <div class="card-footer-front">
                    <span><i class="fas fa-id-card"></i> Valid for medical services</span>
                    <span>www.unidia.com</span>
                    <span><i class="fas fa-phone"></i> 999</span>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <?php if (!$printMode): ?>
        <div class="action-buttons">
            <a href="<?php echo BASE_URL; ?>/patient/list" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="fas fa-print"></i> Print Front
            </button>
            <a href="<?php echo BASE_URL; ?>/patient/id-card-back?id=<?php echo $patient['id']; ?>" target="_blank" class="btn-action btn-purple">
                <i class="fas fa-rotate-left"></i> View Back
            </a>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        <?php if ($printMode): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        <?php endif; ?>
    </script>
</body>
</html>