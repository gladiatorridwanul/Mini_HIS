<?php
// /app/views/vaccines/print-card.php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Card - <?php echo htmlspecialchars($patient['patient_code']); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 12px; padding: 20px; background: white; }
        .card { max-width: 800px; margin: 0 auto; border: 2px solid #1e293b; border-radius: 12px; padding: 30px; }
        .header { text-align: center; border-bottom: 2px solid #1e293b; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; color: #1e293b; letter-spacing: 2px; }
        .header .subtitle { color: #64748b; font-size: 14px; }
        .patient-info { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .patient-info .label { font-weight: 600; color: #475569; font-size: 11px; }
        .patient-info .value { font-weight: 500; color: #0f172a; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        thead { background: #1e293b; color: white; }
        th { padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 8px 12px; border-bottom: 1px solid #e5e7eb; }
        tr:hover { background: #f8fafc; }
        .status-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; }
        .status-given { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; color: #94a3b8; font-size: 11px; }
        .no-vaccines { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .no-vaccines i { font-size: 48px; display: block; margin-bottom: 16px; color: #cbd5e1; }
        @media print {
            body { padding: 0; }
            .card { border: none; padding: 20px; }
            .no-print { display: none !important; }
        }
        .print-btn { display: inline-block; padding: 8px 20px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; margin-bottom: 20px; }
        .print-btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="text-center no-print">
        <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Vaccine Card</button>
        <a href="<?php echo BASE_URL; ?>/vaccines?patient_id=<?php echo $patient['id']; ?>" class="print-btn" style="background: #64748b; margin-left: 10px;">Back to List</a>
    </div>

    <div class="card">
        <div class="header">
            <h1>💉 VACCINATION CARD</h1>
            <div class="subtitle">Immunization Record</div>
        </div>

        <div class="patient-info">
            <div><span class="label">Patient ID</span><br><span class="value"><?php echo htmlspecialchars($patient['patient_code']); ?></span></div>
            <div><span class="label">Name</span><br><span class="value"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></span></div>
            <div><span class="label">Date of Birth</span><br><span class="value"><?php echo !empty($patient['date_of_birth']) ? date('d-m-Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?></span></div>
            <div><span class="label">Gender</span><br><span class="value"><?php echo ucfirst($patient['gender'] ?? 'N/A'); ?></span></div>
            <div><span class="label">Phone</span><br><span class="value"><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></span></div>
            <div><span class="label">Address</span><br><span class="value"><?php echo htmlspecialchars($patient['address'] ?? 'N/A'); ?></span></div>
        </div>

        <h3 style="margin-bottom: 10px; color: #1e293b;">Immunization Records</h3>

        <?php if (!empty($vaccines)): ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vaccine</th>
                    <th>Dose</th>
                    <th>Date Given</th>
                    <th>Next Due</th>
                    <th>Batch/Lot</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vaccines as $index => $vac): ?>
                <?php 
                    $status = 'pending';
                    $statusClass = 'status-pending';
                    $statusLabel = 'Pending';
                    
                    if (!empty($vac['date_given']) && $vac['date_given'] != '0000-00-00') {
                        $status = 'given';
                        $statusClass = 'status-given';
                        $statusLabel = 'Given';
                    }
                    
                    if (!empty($vac['next_due']) && $vac['next_due'] != '0000-00-00') {
                        $today = new DateTime();
                        $dueDate = new DateTime($vac['next_due']);
                        if ($today > $dueDate) {
                            $status = 'overdue';
                            $statusClass = 'status-overdue';
                            $statusLabel = 'Overdue';
                        }
                    }
                ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><strong><?php echo htmlspecialchars($vac['vaccine_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($vac['dose'] ?? ''); ?></td>
                    <td><?php echo !empty($vac['date_given']) ? date('d-m-Y', strtotime($vac['date_given'])) : '-'; ?></td>
                    <td><?php echo !empty($vac['next_due']) ? date('d-m-Y', strtotime($vac['next_due'])) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($vac['batch_number'] ?? ''); ?></td>
                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-vaccines">
            <i>💉</i>
            <p>No vaccination records found for this patient.</p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>Generated on: <?php echo date('d-m-Y H:i'); ?> | UniDia Healthcare System</p>
        </div>
    </div>

    <script>
        // Auto print if requested via URL param
        if (window.location.search.includes('auto_print=1')) {
            setTimeout(function() { window.print(); }, 500);
        }
    </script>
</body>
</html>