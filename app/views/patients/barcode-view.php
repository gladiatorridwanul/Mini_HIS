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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .barcode-container h4 {
            color: #1e293b;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .barcode-container .subtitle {
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 20px;
        }
        
        .barcode-wrapper {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
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
            margin-top: 10px;
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
        
        @media (max-width: 576px) {
            .barcode-container {
                padding: 20px;
                margin: 15px;
            }
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            .btn-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <h4><i class="fas fa-barcode text-primary me-2"></i>Patient Barcode</h4>
        <p class="subtitle"><?php echo htmlspecialchars($patientName); ?></p>
        
        <div class="barcode-wrapper">
            <img src="data:image/svg+xml;base64,<?php echo base64_encode($barcodeData); ?>" alt="Barcode">
        </div>
        
        <div class="barcode-text"><?php echo htmlspecialchars($patientCode); ?></div>
        <div class="barcode-info"><?php echo htmlspecialchars($patientName) . ' | ' . htmlspecialchars($phone); ?></div>
        
        <div class="action-buttons">
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="fas fa-print"></i> Print Barcode
            </button>
            <a href="<?php echo BASE_URL; ?>/patient/barcode-download?id=<?php echo $patient['id']; ?>" class="btn-action btn-download">
                <i class="fas fa-download"></i> Download
            </a>
            <a href="<?php echo BASE_URL; ?>/patient/list" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
</body>
</html>