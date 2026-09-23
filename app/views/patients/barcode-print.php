<?php
$patientName = $patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name'];
$patientCode = $patient['patient_code'];
$phone = $patient['phone'];
$autoPrint = isset($autoPrint) ? $autoPrint : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode - <?php echo htmlspecialchars($patientName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Segoe UI', 'Inter', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .barcode-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .barcode-container h3 {
            color: #1e293b;
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .barcode-container .subtitle {
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 20px;
        }
        
        .barcode-wrapper {
            background: white;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: auto;
        }
        
        .barcode-wrapper img {
            max-width: 100%;
            height: auto;
        }
        
        .barcode-text {
            margin-top: 8px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 1px;
            color: #1e293b;
            font-family: 'Courier New', monospace;
        }
        
        .barcode-info {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        
        .btn-download {
            background: #10b981;
            color: white;
        }
        
        .btn-download:hover {
            background: #059669;
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
        
        /* ===== PRINT STYLES - ONLY BARCODE ===== */
        @media print {
            html, body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                min-height: auto !important;
                height: auto !important;
                display: block !important;
            }
            
            .action-buttons {
                display: none !important;
            }
            
            .barcode-container {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 20px !important;
                margin: 0 auto !important;
                max-width: 100% !important;
                background: white !important;
            }
            
            .barcode-wrapper {
                border: none !important;
                padding: 10px !important;
                background: white !important;
                min-height: auto !important;
            }
            
            .barcode-container h3,
            .barcode-container .subtitle,
            .barcode-text,
            .barcode-info {
                display: none !important;
            }
            
            .barcode-wrapper img {
                max-width: 100% !important;
                height: auto !important;
            }
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <h3><i class="fas fa-barcode text-primary me-2"></i>Barcode</h3>
        <p class="subtitle"><?php echo htmlspecialchars($patientName); ?></p>
        
        <div class="barcode-wrapper" id="barcodeWrapper">
            <img src="data:image/svg+xml;base64,<?php echo base64_encode($barcodeData); ?>" alt="Barcode" id="barcodeImage">
        </div>
        
        <div class="barcode-text"><?php echo htmlspecialchars($patientCode); ?></div>
        <div class="barcode-info"><?php echo htmlspecialchars($patientName) . ' | ' . htmlspecialchars($phone); ?></div>
        
        <div class="action-buttons">
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="fas fa-print"></i> Print Barcode
            </button>
            <button onclick="downloadBarcode()" class="btn-action btn-download">
                <i class="fas fa-download"></i> Download PNG
            </button>
            <a href="<?php echo BASE_URL; ?>/patient/list" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
    
    <script>
        // Download barcode using canvas
        function downloadBarcode() {
            const img = document.getElementById('barcodeImage');
            
            // Create a canvas
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas size to match image
            canvas.width = img.naturalWidth || 400;
            canvas.height = img.naturalHeight || 100;
            
            // Draw image on canvas
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            
            // Download as PNG
            const link = document.createElement('a');
            link.download = 'barcode_<?php echo $patientCode; ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }
        
        <?php if ($autoPrint): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1500);
        };
        <?php endif; ?>
    </script>
</body>
</html>