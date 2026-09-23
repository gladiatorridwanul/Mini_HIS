<?php
// app/views/prescriptions/print.php - OPD TICKET (A4 Fixed Layout)
// ADDED: Treatment History section after Disease History
// FIXED: Visit Date now shows only date (no time)
// FIXED: Treatment History now properly displays data
// UPDATED: Patient info layout - Name(50%), Age(25%), Gender(25%) | Patient ID(50%), Visit Date(50%)
// UPDATED: Medicine table with separate columns for Frequency, Duration, Instruction
// UPDATED: Chief Complaints with sub-titles (G/E, Vital Signs, System Examination) - underlined, bold, larger font
// UPDATED: All titles with 10px top margin
// UPDATED: Left clinical section split into two columns (50% each) with inline items displayed one by one
// UPDATED: Clinical blocks only visible when data exists

// Set timezone (adjust to your local timezone)
date_default_timezone_set('Asia/Dhaka');

// Include helper for age calculation
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 2));
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

function formatDateOnly($date, $format = 'd M Y') {
    if (empty($date)) return '—';
    return date($format, strtotime($date));
}

// Helper function to clean medicine name (remove generic name in parentheses)
function cleanMedicineName($name) {
    if (empty($name)) return '';
    // Remove content in parentheses (generic name)
    $cleaned = preg_replace('/\s*\([^)]*\)\s*/', '', $name);
    // Also handle brackets if any
    $cleaned = preg_replace('/\s*\[[^\]]*\]\s*/', '', $cleaned);
    return trim($cleaned);
}

// Helper function to check if a clinical block has any data
function hasClinicalBlockData($blockData) {
    if (empty($blockData)) return false;
    // Check if it's an array of items
    if (is_array($blockData)) {
        foreach ($blockData as $item) {
            if (!empty($item) && is_array($item)) {
                foreach ($item as $key => $value) {
                    if (!empty($value) && $value !== '—' && $value !== '-') {
                        return true;
                    }
                }
            } elseif (!empty($item) && $item !== '—' && $item !== '-') {
                return true;
            }
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Prescription - <?php echo htmlspecialchars($prescription['prescription_number'] ?? ''); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #e0e0e0;
            font-family: 'Times New Roman', 'Nikosh', Arial, sans-serif;
            color: #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
        }

        /* ===== PRINT CONTROLS (top row, screen only) ===== */
        .no-print {
            width: 100%;
            max-width: 210mm;
            text-align: center;
            margin-bottom: 10px;
            padding: 8px;
            background: #f8f8f8;
            border: 1px solid #ccc;
            border-radius: 6px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
        }
        .no-print .btn {
            padding: 6px 18px;
            font-size: 12px;
            cursor: pointer;
            border: 1px solid #000;
            border-radius: 4px;
            background: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: Arial, sans-serif;
        }
        .no-print .btn:hover { background: #f0f0f0; }
        .no-print .btn-primary { background: #000; color: #fff; }
        .no-print .btn-primary:hover { background: #333; }

        /* ===== A4 PAGE ===== */
        .a4-page {
            background: #fff;
            width: 210mm;
            min-height: 297mm;
            padding: 6mm 8mm;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            border: 1px solid #ccc;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* ===== HEADER ===== */
        .section-one {
            display: flex;
            align-items: center;
            padding-bottom: 1.5mm;
            margin-bottom: 1.5mm;
            flex-shrink: 0;
        }
        .section-one .logo-area {
            flex: 0 0 40%;
            padding-right: 8px;
        }
        .section-one .logo-area img {
            max-height: 80px;
            width: auto;
            display: block;
        }
        .section-one .doctor-info {
            flex: 0 0 60%;
            padding-left: 8px;
            text-align: right;
            font-size: 14px;
        }
        .section-one .doctor-info .doctor-name {
            font-size: 18px;
            font-weight: bold;
        }
        .section-one .doctor-info .doctor-qualification {
            font-style: italic;
        }

        /* ===== PATIENT INFO - 3 Segments ===== */
        .section-two {
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        .section-two .patient-row {
            display: flex;
            padding: 1px 0;
        }
        .section-two .patient-row .info-item {
            display: flex;
            font-size: 13px;
            align-items: center;
        }
        .section-two .patient-row .info-item .label {
            font-weight: bold;
            min-width: 80px;
            flex-shrink: 0;
            text-align: right;
        }
        .section-two .patient-row .info-item .label-colon {
            margin: 0 4px;
            font-weight: bold;
        }
        .section-two .patient-row .info-item .value {
            font-weight: normal;
        }

        /* Row 1: Name - 50%, Age - 25%, Gender - 25% */
        .section-two .patient-row.row1 .info-item.name { flex: 0 0 50%; }
        .section-two .patient-row.row1 .info-item.age { flex: 0 0 25%; }
        .section-two .patient-row.row1 .info-item.gender { flex: 0 0 25%; }

        /* Row 2: Patient ID - 50%, Visit Date - 50% */
        .section-two .patient-row.row2 .info-item.patient-id { flex: 0 0 50%; }
        .section-two .patient-row.row2 .info-item.visit-date { flex: 0 0 50%; }

        /* ===== SEPARATOR LINE AFTER PATIENT INFO ===== */
        .section-separator {
            border: none;
            border-top: 2px solid #000;
            margin: 4px 0 8px 0;
        }

        /* ===== CLINICAL DATA ===== */
        .section-three {
            display: flex;
            flex: 1;
            padding-bottom:4px;
            margin-bottom:4px;
            min-height: 150px;
        }
        .section-three .left-clinical {
            flex:0 0 35%;
            padding-right:8px;
            border-right:1px solid #000;
        }
        .section-three .right-treatment {
            flex:0 0 65%;
            padding-left:8px;
        }

        /* Left Clinical - Two Columns for Items */
        .left-clinical .clinical-block {
            margin-bottom:8px;
            font-size:13px;
        }
        .left-clinical .clinical-block .clinical-title {
            font-weight:bold;
            display:block;
            border-bottom:1px solid #000;
            margin-bottom:3px;
            font-size:14px;
            margin-top: 10px;
            padding-bottom: 2px;
        }
        .left-clinical .clinical-block .clinical-title:first-of-type {
            margin-top: 0;
        }
        .left-clinical .clinical-block .clinical-sub-title {
            font-weight:bold;
            display:block;
            font-size:13px;
            margin-top: 6px;
            margin-bottom: 6px;
            text-decoration: underline;
            color: #000;
        }

        /* Two Column Layout for Inline Items */
        .left-clinical .items-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 2px 0;
        }
        .left-clinical .items-grid .item-col {
            flex: 0 0 50%;
            padding-right: 4px;
        }
        .left-clinical .items-grid .item-col:last-child {
            padding-right: 0;
        }
        .left-clinical .items-grid .clinical-item {
            display: block;
            padding: 2px 0;
            font-size: 13px;
            line-height: 1.4;
        }
        .left-clinical .items-grid .clinical-item .item-label {
            font-weight: bold;
        }
        .left-clinical .items-grid .clinical-item .item-value {
            font-weight: normal;
        }
        .left-clinical .items-grid .clinical-item .item-duration {
            font-style: italic;
            color: #555;
        }

        /* PE specific - keep inline for compatibility */
        .pe-flags, .vital-signs, .pe-additional {
            display: flex;
            flex-wrap: wrap;
            gap: 4px 12px;
            font-size: 13px;
        }
        .pe-flags .flag-item,
        .vital-signs .vital-item,
        .pe-additional .add-item {
            display: inline-block;
        }
        .pe-flags .flag-item .flag-label,
        .vital-signs .vital-item .vital-label,
        .pe-additional .add-item .add-label {
            font-weight: bold;
        }

        .treatment-block { font-size:13px; }
        .treatment-block .treatment-title {
            font-weight:bold;
            display:block;
            border-bottom:1px solid #000;
            margin-bottom:4px;
            font-size:14px;
            margin-top: 10px;
            padding-bottom: 2px;
        }
        .medicine-table {
            width:100%;
            border-collapse:collapse;
            font-size:12px;
            margin-bottom:4px;
        }
        .medicine-table th {
            font-weight:bold;
            text-align:left;
            border-bottom:1px solid #000;
            padding:3px 4px;
            font-size:12px;
        }
        .medicine-table td {
            padding:3px 4px;
            border-bottom:1px solid #000;
            vertical-align:top;
            font-size:12px;
        }
        .medicine-table .med-number { text-align:center; width:5%; }
        .medicine-table .med-name { width:40%; font-weight:bold; }
        .medicine-table .med-frequency { width:25%; }
        .medicine-table .med-duration { width:15%; }
        .medicine-table .med-instruction { width:15%; }

        .medicine-details {
            margin:2px 0 0 0;
            padding-left:4px;
        }
        .medicine-details .detail-line {
            font-size:12px;
            padding:1px 0;
            display:flex;
            flex-wrap:wrap;
            align-items:baseline;
            gap:2px 4px;
        }

        .advice-block {
            margin:4px 0;
            padding:4px 0;
            border-top:1px solid #000;
            border-bottom:1px solid #000;
            font-size:13px;
        }
        .advice-block .advice-label { font-weight:bold; font-size:14px; }

        .next-appointment {
            margin:4px 0;
            font-weight:bold;
            font-size:13px;
        }
        .next-appointment .appointment-value { font-weight:normal; }

        .doctor-signature {
            text-align:right;
            margin-top: 100px;
            padding-top:20px;
            font-size:13px;
        }
        .doctor-signature .signature-label {
            font-weight:bold;
            display:block;
        }
        .doctor-signature .signature-date {
            display:block;
            font-size:12px;
            margin-top:2px;
        }

        /* ===== FOOTER ===== */
        .section-four {
            text-align:center;
            font-size:12px;
            color:#000;
            padding-top:4px;
            border-top:1px solid #000;
            margin-top:auto;
            flex-shrink:0;
        }
        .section-four .footer-hospital-name {
            font-weight:bold;
            font-size:14px;
            margin-bottom:2px;
        }
        .section-four .footer-address, .section-four .footer-contact {
            margin:1px 0;
            font-size:12px;
        }

        /* ===== PRINT STYLES ===== */
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
                display: block;
            }
            .a4-page {
                width: 100%;
                min-height: auto;
                padding: 2mm 2mm;
                box-shadow: none;
                border: none;
                margin: 0;
                display: block;
                position: relative;
                padding-top: 35mm;
                padding-bottom: 20mm;
            }
            .section-one {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #fff;
                padding: 0mm 1.5mm 0mm 1.5mm;
                z-index: 100;
                margin: 0;
                width: 100%;
                box-sizing: border-box;
                page-break-after: avoid;
            }
            .section-four {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #fff;
                padding: 4mm 8mm 4mm 8mm;
                border-top: 1px solid #000;
                z-index: 100;
                margin: 0;
                width: 100%;
                box-sizing: border-box;
                page-break-before: avoid;
            }

            /* Remove extra borders, keep only necessary ones */
            .section-two {
                border-bottom: none !important;
            }
            .section-two .patient-row {
                border-bottom: none !important;
            }
            .medicine-table td {
                border-bottom: none !important;
            }
            .medicine-table th {
                border-bottom: 1px solid #000 !important;
            }
            .advice-block {
                border-top: none !important;
                border-bottom: none !important;
            }
            .section-three .left-clinical {
                border-right: 1px solid #000 !important;
            }
            .section-separator {
                border-top: 2px solid #000 !important;
                margin: 4px 0 8px 0 !important;
            }
            .doctor-signature {
                border-top: none !important;
                margin-top: 100px !important;
                padding-top: 0 !important;
            }
            .next-appointment {
                margin-bottom: 20px !important;
            }
            .no-print {
                display: none !important;
            }

            /* ===== SECTION TITLES: larger & underlined in print ===== */
            .left-clinical .clinical-block .clinical-title {
                font-size: 14px !important;
                text-decoration: underline !important;
                border-bottom: none !important;
                margin-top: 10px !important;
            }
            .left-clinical .clinical-block .clinical-title:first-of-type {
                margin-top: 0 !important;
            }
            .left-clinical .clinical-block .clinical-sub-title {
                font-size: 13px !important;
                font-weight: bold !important;
                text-decoration: underline !important;
                margin-top: 6px !important;
                margin-bottom: 6px !important;
            }
            .treatment-block .treatment-title {
                font-size: 14px !important;
                text-decoration: underline !important;
                border-bottom: none !important;
                margin-top: 10px !important;
            }
            .advice-block .advice-label {
                font-size: 14px !important;
                text-decoration: underline !important;
            }
            .next-appointment {
                font-size: 13px !important;
            }
            .next-appointment .appointment-value {
                font-weight: normal !important;
            }

            @page {
                size: A4;
                margin: 8mm 10mm;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- ===== PRINT CONTROLS (top row, screen only) ===== -->
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
        <button onclick="window.close()" class="btn">✕ Close</button>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/prescriptions/export-pdf/<?php echo $prescription['id'] ?? 0; ?>'" class="btn">📄 PDF</button>
        <button onclick="window.location.href='<?php echo BASE_URL; ?>/prescriptions/print-pad/<?php echo $prescription['id'] ?? 0; ?>'" class="btn">📋 Pad Format</button>
    </div>

    <!-- ===== A4 PAGE ===== -->
    <div class="a4-page">

        <!-- ===== HEADER ===== -->
        <div class="section-one">
            <div class="logo-area">
                <img src="<?php echo BASE_URL; ?>/assets/images/bcpcc-PNG.png" alt="BCPCC">
            </div>
            <div class="doctor-info">
                <?php
                $doctorInfoEn = trim($prescription['doctor_info_en'] ?? '');
                if (!empty($doctorInfoEn)) {
                    $lines = explode("\n", $doctorInfoEn);
                    foreach ($lines as $idx => $line) {
                        $line = trim($line);
                        if ($line === '') continue;
                        $class = ($idx === 0) ? 'doctor-name' : '';
                        echo '<div class="' . $class . '">' . htmlspecialchars($line) . '</div>';
                    }
                } else {
                    $name = $prescription['doctor_name'] ?? 'Dr. Unknown';
                    $qual = $prescription['doctor_qualification'] ?? '';
                    $desig = $prescription['doctor_designation'] ?? '';
                    $bmdc = $prescription['doctor_bmdc'] ?? '';
                    $dept = $prescription['doctor_department'] ?? '';
                    echo '<div class="doctor-name">' . htmlspecialchars($name) . '</div>';
                    if (!empty($qual)) echo '<div class="doctor-qualification">' . htmlspecialchars($qual) . '</div>';
                    if (!empty($desig)) echo '<div>' . htmlspecialchars($desig) . '</div>';
                    if (!empty($bmdc)) echo '<div>BMDC: ' . htmlspecialchars($bmdc) . '</div>';
                    if (!empty($dept)) echo '<div>Department: ' . htmlspecialchars($dept) . '</div>';
                }
                ?>
            </div>
        </div>

        <!-- ===== PATIENT INFO - 3 Segments ===== -->
        <?php
        // Get DOB and Age using the same logic as view.php
        $dob = getPatientDob($prescription);
        $age = calculateAge($dob);
        ?>
        <div class="section-two">
            <!-- Row 1: Name (50%) | Age (25%) | Gender (25%) -->
            <div class="patient-row row1">
                <div class="info-item name">
                    <span class="label">Name</span>
                    <span class="label-colon">:</span>
                    <span class="value"><?php echo htmlspecialchars($prescription['patient_name'] ?? ''); ?></span>
                </div>
                <div class="info-item age">
                    <span class="label">Age</span>
                    <span class="label-colon">:</span>
                    <span class="value"><?php echo $age; ?></span>
                </div>
                <div class="info-item gender">
                    <span class="label">Gender</span>
                    <span class="label-colon">:</span>
                    <span class="value"><?php echo ucfirst($prescription['gender'] ?? ''); ?></span>
                </div>
            </div>
            <!-- Row 2: Patient ID (50%) | Visit Date (50%) -->
            <div class="patient-row row2">
                <div class="info-item patient-id">
                    <span class="label">Patient ID</span>
                    <span class="label-colon">:</span>
                    <span class="value"><?php echo htmlspecialchars($prescription['patient_code'] ?? ''); ?></span>
                </div>
                <div class="info-item visit-date">
                    <span class="label">Visit Date</span>
                    <span class="label-colon">:</span>
                    <span class="value"><?php echo formatDateOnly($prescription['prescription_date'] ?? '', 'd M Y'); ?></span>
                </div>
            </div>
            <!-- Row 3: Prescription & Phone (hidden, used for reference) -->
            <div class="patient-row" style="display:none;">
                <div class="info-item">
                    <span class="label">Prescription</span>
                    <span class="value">: <?php echo htmlspecialchars($prescription['prescription_number'] ?? ''); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Phone</span>
                    <span class="value">: <?php echo htmlspecialchars($prescription['phone'] ?? ''); ?></span>
                </div>
            </div>
        </div>

        <!-- ===== SEPARATOR LINE ===== -->
        <hr class="section-separator">

        <!-- ===== CLINICAL DATA ===== -->
        <div class="section-three">
            <!-- Left Column -->
            <div class="left-clinical">
                <!-- Chief Complaints -->
                <?php if (!empty($prescription['chief_complaints']) && hasClinicalBlockData($prescription['chief_complaints'])): ?>
                <div class="clinical-block">
                    <span class="clinical-title">Chief Complaints</span>
                    <div class="items-grid">
                        <?php 
                        $items = $prescription['chief_complaints'];
                        $half = ceil(count($items) / 2);
                        $leftItems = array_slice($items, 0, $half);
                        $rightItems = array_slice($items, $half);
                        ?>
                        <div class="item-col">
                            <?php foreach ($leftItems as $complaint): ?>
                                <?php if (!empty($complaint['complaint'])): ?>
                                <div class="clinical-item">
                                    <span class="item-value"><?php echo htmlspecialchars($complaint['complaint'] ?? ''); ?></span>
                                    <?php if (!empty($complaint['duration'])): ?>
                                        <span class="item-duration">(<?php echo htmlspecialchars($complaint['duration']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="item-col">
                            <?php foreach ($rightItems as $complaint): ?>
                                <?php if (!empty($complaint['complaint'])): ?>
                                <div class="clinical-item">
                                    <span class="item-value"><?php echo htmlspecialchars($complaint['complaint'] ?? ''); ?></span>
                                    <?php if (!empty($complaint['duration'])): ?>
                                        <span class="item-duration">(<?php echo htmlspecialchars($complaint['duration']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Physical Examination -->
                <?php
                $pe = $prescription['physical_exam'] ?? [];
                $vs = $prescription['vital_signs'] ?? [];
                $hasPeData = false;
                $flags = ['anaemia', 'jaundice', 'cyanosis', 'oedema', 'dehydration'];
                foreach ($flags as $f) {
                    if (isset($pe[$f]) && $pe[$f] !== '') $hasPeData = true;
                }
                $additionalFields = ['abdomen', 'cvs', 'respiratory', 'lymphoreticular'];
                foreach ($additionalFields as $f) {
                    if (!empty($pe[$f])) $hasPeData = true;
                }
                $vitalKeys = ['pulse', 'weight', 'respiratory_rate', 'length', 'blood_pressure_systolic', 'blood_pressure_diastolic', 'temperature', 'oxygen_saturation', 'bmi', 'others'];
                foreach ($vitalKeys as $k) {
                    if (!empty($vs[$k])) $hasPeData = true;
                }

                if ($hasPeData):
                ?>
                <div class="clinical-block">
                    <span class="clinical-title">Physical Examination</span>
                    
                    <!-- G/E (General Examination) -->
                    <?php
                    $hasGeData = false;
                    foreach ($flags as $f) {
                        if (isset($pe[$f]) && $pe[$f] !== '') $hasGeData = true;
                    }
                    if ($hasGeData):
                    ?>
                    <span class="clinical-sub-title">G/E (General Examination)</span>
                    <div class="items-grid">
                        <?php 
                        $geItems = [];
                        foreach ($flags as $f) {
                            if (isset($pe[$f]) && $pe[$f] !== '') {
                                $geItems[] = ['label' => ucfirst($f), 'value' => $pe[$f] ? 'Yes' : 'No'];
                            }
                        }
                        $half = ceil(count($geItems) / 2);
                        $leftItems = array_slice($geItems, 0, $half);
                        $rightItems = array_slice($geItems, $half);
                        ?>
                        <div class="item-col">
                            <?php foreach ($leftItems as $item): ?>
                            <div class="clinical-item">
                                <span class="item-label"><?php echo $item['label']; ?>:</span>
                                <span class="item-value"><?php echo $item['value']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="item-col">
                            <?php foreach ($rightItems as $item): ?>
                            <div class="clinical-item">
                                <span class="item-label"><?php echo $item['label']; ?>:</span>
                                <span class="item-value"><?php echo $item['value']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Vital Signs -->
                    <?php
                    $hasVitalData = false;
                    foreach ($vitalKeys as $k) {
                        if (!empty($vs[$k])) $hasVitalData = true;
                    }
                    if ($hasVitalData):
                    ?>
                    <span class="clinical-sub-title">Vital Signs</span>
                    <div class="items-grid">
                        <?php 
                        $vitalItems = [];
                        if (!empty($vs['pulse'])) $vitalItems[] = ['label' => 'Pulse', 'value' => $vs['pulse'] . ' bpm'];
                        if (!empty($vs['weight'])) $vitalItems[] = ['label' => 'Weight', 'value' => $vs['weight'] . ' Kg'];
                        if (!empty($vs['respiratory_rate'])) $vitalItems[] = ['label' => 'R/R', 'value' => $vs['respiratory_rate'] . ' /min'];
                        if (!empty($vs['length'])) $vitalItems[] = ['label' => 'Length', 'value' => $vs['length'] . ' cm'];
                        if (!empty($vs['blood_pressure_systolic']) || !empty($vs['blood_pressure_diastolic'])) {
                            $vitalItems[] = ['label' => 'BP', 'value' => ($vs['blood_pressure_systolic'] ?? '?') . '/' . ($vs['blood_pressure_diastolic'] ?? '?') . ' mmHg'];
                        }
                        if (!empty($vs['temperature'])) $vitalItems[] = ['label' => 'Temperature', 'value' => $vs['temperature'] . ' °C'];
                        if (!empty($vs['oxygen_saturation'])) $vitalItems[] = ['label' => 'O₂ Sat', 'value' => $vs['oxygen_saturation'] . ' %'];
                        if (!empty($vs['bmi'])) $vitalItems[] = ['label' => 'BMI', 'value' => $vs['bmi']];
                        if (!empty($vs['others'])) $vitalItems[] = ['label' => 'Others', 'value' => htmlspecialchars($vs['others'])];

                        $half = ceil(count($vitalItems) / 2);
                        $leftItems = array_slice($vitalItems, 0, $half);
                        $rightItems = array_slice($vitalItems, $half);
                        ?>
                        <div class="item-col">
                            <?php foreach ($leftItems as $item): ?>
                            <div class="clinical-item">
                                <span class="item-label"><?php echo $item['label']; ?>:</span>
                                <span class="item-value"><?php echo $item['value']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="item-col">
                            <?php foreach ($rightItems as $item): ?>
                            <div class="clinical-item">
                                <span class="item-label"><?php echo $item['label']; ?>:</span>
                                <span class="item-value"><?php echo $item['value']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- System Examination -->
                    <?php
                    $hasSystemData = false;
                    foreach ($additionalFields as $f) {
                        if (!empty($pe[$f])) $hasSystemData = true;
                    }
                    if ($hasSystemData):
                    ?>
                    <span class="clinical-sub-title">System Examination</span>
                    <div class="items-grid">
                        <?php 
                        $systemItems = [];
                        if (!empty($pe['abdomen'])) $systemItems[] = ['label' => 'Abdomen', 'value' => htmlspecialchars($pe['abdomen'])];
                        if (!empty($pe['cvs'])) $systemItems[] = ['label' => 'CVS', 'value' => htmlspecialchars($pe['cvs'])];
                        if (!empty($pe['respiratory'])) $systemItems[] = ['label' => 'Respiratory', 'value' => htmlspecialchars($pe['respiratory'])];
                        if (!empty($pe['lymphoreticular'])) $systemItems[] = ['label' => 'Lymphoreticular', 'value' => htmlspecialchars($pe['lymphoreticular'])];

                        $half = ceil(count($systemItems) / 2);
                        $leftItems = array_slice($systemItems, 0, $half);
                        $rightItems = array_slice($systemItems, $half);
                        ?>
                        <div class="item-col">
                            <?php foreach ($leftItems as $item): ?>
                            <div class="clinical-item">
                                <span class="item-label"><?php echo $item['label']; ?>:</span>
                                <span class="item-value"><?php echo $item['value']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="item-col">
                            <?php foreach ($rightItems as $item): ?>
                            <div class="clinical-item">
                                <span class="item-label"><?php echo $item['label']; ?>:</span>
                                <span class="item-value"><?php echo $item['value']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Lab Test Requests -->
                <?php if (!empty($prescription['lab_tests']) && hasClinicalBlockData($prescription['lab_tests'])): ?>
                <div class="clinical-block">
                    <span class="clinical-title">Lab Test Request</span>
                    <div class="items-grid">
                        <?php 
                        $items = $prescription['lab_tests'];
                        $half = ceil(count($items) / 2);
                        $leftItems = array_slice($items, 0, $half);
                        $rightItems = array_slice($items, $half);
                        ?>
                        <div class="item-col">
                            <?php foreach ($leftItems as $test): ?>
                                <?php if (!empty($test['test_name'])): ?>
                                <div class="clinical-item">
                                    <span class="item-value"><?php echo htmlspecialchars($test['test_name'] ?? ''); ?></span>
                                    <?php if (!empty($test['priority'])): ?>
                                        <span class="item-duration">(<?php echo htmlspecialchars($test['priority']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="item-col">
                            <?php foreach ($rightItems as $test): ?>
                                <?php if (!empty($test['test_name'])): ?>
                                <div class="clinical-item">
                                    <span class="item-value"><?php echo htmlspecialchars($test['test_name'] ?? ''); ?></span>
                                    <?php if (!empty($test['priority'])): ?>
                                        <span class="item-duration">(<?php echo htmlspecialchars($test['priority']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Disease History -->
                <?php if (!empty($prescription['disease_history']) && hasClinicalBlockData($prescription['disease_history'])): ?>
                <div class="clinical-block">
                    <span class="clinical-title">Disease History</span>
                    <div class="items-grid">
                        <?php 
                        $items = $prescription['disease_history'];
                        $half = ceil(count($items) / 2);
                        $leftItems = array_slice($items, 0, $half);
                        $rightItems = array_slice($items, $half);
                        ?>
                        <div class="item-col">
                            <?php foreach ($leftItems as $disease): ?>
                                <?php if (!empty($disease['disease'])): ?>
                                <div class="clinical-item">
                                    <span class="item-value"><?php echo htmlspecialchars($disease['disease'] ?? ''); ?></span>
                                    <?php if (!empty($disease['duration'])): ?>
                                        <span class="item-duration">(<?php echo htmlspecialchars($disease['duration']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="item-col">
                            <?php foreach ($rightItems as $disease): ?>
                                <?php if (!empty($disease['disease'])): ?>
                                <div class="clinical-item">
                                    <span class="item-value"><?php echo htmlspecialchars($disease['disease'] ?? ''); ?></span>
                                    <?php if (!empty($disease['duration'])): ?>
                                        <span class="item-duration">(<?php echo htmlspecialchars($disease['duration']); ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Treatment History -->
                <?php 
                $treatmentHistory = $treatmentHistory ?? [];
                if (!empty($treatmentHistory) && hasClinicalBlockData($treatmentHistory)): 
                ?>
                <div class="clinical-block">
                    <span class="clinical-title">Treatment History</span>
                    <div class="items-grid">
                        <?php 
                        $items = $treatmentHistory;
                        $half = ceil(count($items) / 2);
                        $leftItems = array_slice($items, 0, $half);
                        $rightItems = array_slice($items, $half);
                        ?>
                        <div class="item-col">
                            <?php foreach ($leftItems as $treatment): ?>
                                <?php if (!empty($treatment['treatment_name'])): ?>
                                <div class="clinical-item">
                                    <span class="item-value"><?php echo htmlspecialchars($treatment['treatment_name'] ?? ''); ?></span>
                                    <?php if (!empty($treatment['duration'])): ?>
                                        <span class="item-duration">(<?php echo htmlspecialchars($treatment['duration']); ?>)</span>
                                    <?php endif; ?>
                                    <?php if (!empty($treatment['remarks'])): ?>
                                        <span class="item-duration">- <?php echo htmlspecialchars($treatment['remarks']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="item-col">
                            <?php foreach ($rightItems as $treatment): ?>
                                <?php if (!empty($treatment['treatment_name'])): ?>
                                <div class="clinical-item">
                                    <span class="item-value"><?php echo htmlspecialchars($treatment['treatment_name'] ?? ''); ?></span>
                                    <?php if (!empty($treatment['duration'])): ?>
                                        <span class="item-duration">(<?php echo htmlspecialchars($treatment['duration']); ?>)</span>
                                    <?php endif; ?>
                                    <?php if (!empty($treatment['remarks'])): ?>
                                        <span class="item-duration">- <?php echo htmlspecialchars($treatment['remarks']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Diagnosis -->
                <?php if (!empty($prescription['diagnosis'])): ?>
                <div class="clinical-block">
                    <span class="clinical-title">Diagnosis</span>
                    <div class="items-grid">
                        <?php 
                        $diagnosisLines = explode("\n", $prescription['diagnosis']);
                        $diagnosisItems = [];
                        foreach ($diagnosisLines as $line) {
                            $line = trim($line);
                            if (!empty($line)) {
                                $diagnosisItems[] = ['value' => $line];
                            }
                        }
                        $half = ceil(count($diagnosisItems) / 2);
                        $leftItems = array_slice($diagnosisItems, 0, $half);
                        $rightItems = array_slice($diagnosisItems, $half);
                        ?>
                        <div class="item-col">
                            <?php foreach ($leftItems as $item): ?>
                            <div class="clinical-item">
                                <span class="item-value" style="font-style:italic;"><?php echo htmlspecialchars($item['value']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="item-col">
                            <?php foreach ($rightItems as $item): ?>
                            <div class="clinical-item">
                                <span class="item-value" style="font-style:italic;"><?php echo htmlspecialchars($item['value']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="right-treatment">
                <!-- Treatment / Medicines -->
                <div class="treatment-block">
                    <span class="treatment-title">Treatment / Medicines</span>
                    <?php if (!empty($prescription['items'])): ?>
                        <table class="medicine-table">
                            <thead>
                                <tr>
                                    <th class="med-number">#</th>
                                    <th class="med-name">Medicine & Strength</th>
                                    <th class="med-frequency">Frequency</th>
                                    <th class="med-duration">Duration</th>
                                    <th class="med-instruction">Instruction</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prescription['items'] as $idx => $item): ?>
                                    <tr>
                                        <td class="med-number"><?php echo $idx + 1; ?>.</td>
                                        <td class="med-name"><?php echo htmlspecialchars(cleanMedicineName($item['drug_name'] ?? '')); ?></td>
                                        <td class="med-frequency">
                                            <?php
                                            $hasDetails = false;
                                            if (!empty($item['details']) && is_array($item['details'])) {
                                                foreach ($item['details'] as $d) {
                                                    $freq = trim($d['frequency'] ?? '');
                                                    if ($freq) {
                                                        echo htmlspecialchars($freq) . '<br>';
                                                        $hasDetails = true;
                                                    }
                                                }
                                            }
                                            if (!$hasDetails) {
                                                $freq = trim($item['frequency'] ?? '');
                                                echo !empty($freq) ? htmlspecialchars($freq) : '—';
                                            }
                                            ?>
                                        </td>
                                        <td class="med-duration">
                                            <?php
                                            $hasDetails = false;
                                            if (!empty($item['details']) && is_array($item['details'])) {
                                                foreach ($item['details'] as $d) {
                                                    $dur = trim($d['duration'] ?? '');
                                                    if ($dur) {
                                                        echo htmlspecialchars($dur) . '<br>';
                                                        $hasDetails = true;
                                                    }
                                                }
                                            }
                                            if (!$hasDetails) {
                                                $dur = trim($item['duration'] ?? '');
                                                echo !empty($dur) ? htmlspecialchars($dur) : '—';
                                            }
                                            ?>
                                        </td>
                                        <td class="med-instruction">
                                            <?php
                                            $hasDetails = false;
                                            if (!empty($item['details']) && is_array($item['details'])) {
                                                foreach ($item['details'] as $d) {
                                                    $inst = trim($d['instruction'] ?? '');
                                                    if ($inst) {
                                                        echo htmlspecialchars($inst) . '<br>';
                                                        $hasDetails = true;
                                                    }
                                                }
                                            }
                                            if (!$hasDetails) {
                                                $inst = trim($item['instructions'] ?? '');
                                                echo !empty($inst) ? htmlspecialchars($inst) : '—';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p style="font-style:italic;">No medicines prescribed.</p>
                    <?php endif; ?>
                </div>

                <!-- Advice -->
                <?php 
                $advice = trim($prescription['advice']['advice_text'] ?? '');
                if (!empty($advice)):
                ?>
                <div class="advice-block">
                    <span class="advice-label">Advice:</span><br/>
                    <span class="advice-text">
                        <?php echo nl2br(htmlspecialchars($advice)); ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- Next Appointment -->
                <?php 
                $advice = $prescription['advice'] ?? [];
                $nextApp = null;
                if (!empty($advice['appointment_date'])) {
                    $nextApp = formatDateOnly($advice['appointment_date']);
                } elseif (!empty($advice['follow_up_days']) && $advice['follow_up_days'] > 0) {
                    $nextApp = $advice['follow_up_days'] . ' days after visit';
                }
                if ($nextApp):
                ?>
                <div class="next-appointment">
                    <span style="font-weight:bold;">Next Appointment:</span>
                    <span class="appointment-value"><?php echo htmlspecialchars($nextApp); ?></span>
                </div>
                <?php endif; ?>

                <!-- Doctor Signature -->
                <div class="doctor-signature">
                    <span class="signature-label">Doctor's Signature</span>
                    <span class="signature-date"><?php echo date('d M Y'); ?></span>
                </div>
            </div>
        </div>

        <!-- ===== FOOTER ===== -->
        <div class="section-four">
            <div class="footer-hospital-name">Bangladesh Cancer &amp; Palliative Care Center (BCPCC)</div>
            <div class="footer-address">Address: Green City Square, Lift-5, 750 Shatmosjid Road, Dhanmondi, Dhaka-1209</div>
            <div class="footer-contact">Contact: 01175-313313 | Email: info@bcpc.org</div>
        </div>

    </div>

</body>
</html>