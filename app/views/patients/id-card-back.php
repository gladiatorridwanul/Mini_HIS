<?php
$patientName = $patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name'];
$printMode = isset($printMode) ? $printMode : false;

// ID Card Size: 3.15" × 1.9" (inches)
// Web Standard (96 PPI): 302px × 182px
$cardWidth = 302;
$cardHeight = 182;

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
    <title>ID Card Back - <?php echo htmlspecialchars($patientName); ?></title>
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
        
        .id-card-back {
            width: <?php echo $cardWidth; ?>px;
            height: <?php echo $cardHeight; ?>px;
            background: white;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            position: relative;
            <?php if (!$printMode): ?>
            border-radius: 6px;
            <?php endif; ?>
        }
        
        .back-header {
            text-align: center;
            border-bottom: 2px solid #1a56db;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }
        
        .back-header h4 {
            color: #1a56db;
            font-size: 8px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.3px;
        }
        
        .back-header h4 i {
            font-size: 7px;
            margin-right: 2px;
        }
        
        .back-header small {
            font-size: 5px;
            color: #64748b;
        }
        
        .info-grid-back {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1px 12px;
            margin-bottom: 4px;
        }
        
        .info-item-back {
            display: flex;
            justify-content: space-between;
            padding: 1.5px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .info-item-back .label {
            font-size: 5px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }
        
        .info-item-back .value {
            font-size: 5.5px;
            font-weight: 500;
            color: #1e293b;
            max-width: 80px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .barcode-section {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 2px 0 3px;
            padding: 3px 8px;
            background: #f8fafc;
            border-radius: 4px;
            min-height: 32px;
        }
        
        .barcode-placeholder {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: center;
        }
        
        .barcode-placeholder .barcode-img {
            max-height: 28px;
            max-width: 100px;
            display: block;
        }
        
        .barcode-placeholder .code-text {
            font-size: 7px;
            font-weight: 600;
            color: #1e293b;
            letter-spacing: 1.5px;
        }
        
        .barcode-placeholder .code-sub {
            font-size: 4.5px;
            color: #94a3b8;
        }
        
        .terms-section {
            background: #f8fafc;
            border-radius: 4px;
            padding: 2px 8px;
            margin-top: 2px;
        }
        
        .terms-section .term-item {
            font-size: 4.5px;
            color: #64748b;
            padding: 0.5px 0;
            display: flex;
            align-items: center;
            gap: 3px;
        }
        
        .terms-section .term-item i {
            color: #1a56db;
            font-size: 4px;
        }
        
        .signature-back {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 3px;
            padding-top: 3px;
            border-top: 1px solid #e2e8f0;
        }
        
        .signature-back .sig-line {
            text-align: center;
        }
        
        .signature-back .sig-line .line {
            width: 50px;
            border-bottom: 1px solid #94a3b8;
            margin: 0px auto 0;
        }
        
        .signature-back .sig-line .label {
            font-size: 4.5px;
            color: #94a3b8;
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
        
        .btn-green {
            background: #10b981;
            color: white;
        }
        
        .btn-green:hover {
            background: #059669;
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
            
            .id-card-back {
                width: 3.15in !important;
                height: 1.9in !important;
                border: 1px solid #ccc !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                page-break-inside: avoid !important;
                margin: 0 !important;
                padding: 6px 10px !important;
            }
            
            .action-buttons {
                display: none !important;
            }
            
            .barcode-section {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .terms-section {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .back-header {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            
            .barcode-img {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div>
        <div class="id-card-wrapper">
            <div class="id-card-back" id="idCardBack">
                <div class="back-header">
                    <h4><i class="fas fa-id-card"></i> PATIENT IDENTIFICATION</h4>
                    <small>UNIDIA Hospital Management System</small>
                </div>
                
                <div class="info-grid-back">
                    <div class="info-item-back">
                        <span class="label">Patient ID</span>
                        <span class="value"><?php echo $patient['patient_code']; ?></span>
                    </div>
                    <div class="info-item-back">
                        <span class="label">Card Number</span>
                        <span class="value"><?php echo $patient['patient_id_card_number'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-item-back">
                        <span class="label">Name</span>
                        <span class="value"><?php echo htmlspecialchars($patientName); ?></span>
                    </div>
                    <div class="info-item-back">
                        <span class="label">Emergency Contact</span>
                        <span class="value"><?php echo $patient['emergency_contact_phone'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-item-back">
                        <span class="label">Blood Group</span>
                        <span class="value"><?php echo $patient['blood_group'] ?: 'N/A'; ?></span>
                    </div>
                    <div class="info-item-back">
                        <span class="label">Allergies</span>
                        <span class="value"><?php echo $patient['allergies'] ?: 'None'; ?></span>
                    </div>
                </div>
                
                <!-- Dynamic Barcode Section -->
                <div class="barcode-section">
                    <div class="barcode-placeholder">
                        <img class="barcode-img" src="data:image/svg+xml;base64,<?php echo $barcodeBase64; ?>" alt="Barcode">
                        <div>
                            <div class="code-text"><?php echo $patient['patient_code']; ?></div>
                            <div class="code-sub"><?php echo htmlspecialchars($patientName); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="terms-section">
                    <div class="term-item">
                        <i class="fas fa-check-circle"></i> This card is issued by UNIDIA Hospital.
                    </div>
                    <div class="term-item">
                        <i class="fas fa-check-circle"></i> Present for all medical services.
                    </div>
                    <div class="term-item">
                        <i class="fas fa-check-circle"></i> Emergency: 999 | www.unidia.com
                    </div>
                    <div class="term-item">
                        <i class="fas fa-check-circle"></i> Report loss to administration.
                    </div>
                </div>
                
                <div class="signature-back">
                    <div class="sig-line">
                        <div class="label">Patient Sig.</div>
                        <div class="line"></div>
                    </div>
                    <div class="sig-line">
                        <div class="label">Authority</div>
                        <div class="line"></div>
                    </div>
                    <div class="sig-line">
                        <div class="label">Date</div>
                        <div class="line"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!$printMode): ?>
        <div class="action-buttons">
            <a href="<?php echo BASE_URL; ?>/patient/list" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="fas fa-print"></i> Print Back
            </button>
            <a href="<?php echo BASE_URL; ?>/patient/id-card-front?id=<?php echo $patient['id']; ?>" target="_blank" class="btn-action btn-green">
                <i class="fas fa-rotate-right"></i> View Front
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