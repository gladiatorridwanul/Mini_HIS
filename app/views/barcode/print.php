<?php
$patientName = $patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name'];
$patientCode = $patient['patient_code'];
$phone = $patient['phone'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode - <?php echo htmlspecialchars($patientName); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Courier New', monospace;
        }
        
        .barcode-container {
            text-align: center;
            padding: 20px;
            background: white;
        }
        
        .barcode-image {
            display: block;
            margin: 0 auto;
            max-width: 100%;
        }
        
        .barcode-text {
            margin-top: 8px;
            font-size: 12px;
            color: #333;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .barcode-info {
            margin-top: 4px;
            font-size: 10px;
            color: #666;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 0;
                min-height: auto;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <img src="<?php echo $barcodeData; ?>" alt="Barcode" class="barcode-image">
        <div class="barcode-text"><?php echo htmlspecialchars($patientCode); ?></div>
        <div class="barcode-info"><?php echo htmlspecialchars($patientName) . ' | ' . htmlspecialchars($phone); ?></div>
        
        <div class="no-print" style="margin-top: 30px;">
            <button onclick="window.print()" style="padding: 10px 30px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px;">
                <i class="fas fa-print"></i> Print Barcode
            </button>
        </div>
    </div>
    
    <script>
        // Auto-print when loaded
        window.onload = function() {
            if (window.location.search.includes('auto_print=true')) {
                setTimeout(function() {
                    window.print();
                }, 500);
            }
        };
    </script>
</body>
</html>