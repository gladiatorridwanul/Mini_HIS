<?php
// app/views/pharmacy/dispense.php - Dispense Prescription with merged medicines and prices
// UPDATED: Removed "FREQUENCY / DURATION / INSTRUCTION" section, kept only medicine details display
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispense Prescription - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        
        .card-custom {
            background: white;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .card-header-custom {
            background: white;
            border-bottom: 2px solid #3b82f6;
            padding: 10px 15px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .info-row {
            display: flex;
            flex-wrap: wrap;
            padding: 4px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-row .label {
            font-weight: 600;
            color: #64748b;
            width: 140px;
            flex-shrink: 0;
        }
        .info-row .value {
            color: #1e293b;
            flex: 1;
        }
        .info-row:last-child { border-bottom: none; }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            padding: 10px 14px;
            font-size: 13px;
        }
        .alert-success-custom {
            background: #ecfdf5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-danger-custom {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }
        .alert-warning-custom {
            background: #fffbeb;
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }
        
        .table-modern {
            width: 100%;
            background: white;
            border-collapse: collapse;
            font-size: 13px;
        }
        .table-modern thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 8px 10px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
            text-align: left;
        }
        .table-modern tbody td {
            padding: 8px 10px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        .table-modern tbody tr:hover {
            background: #f8fafc;
        }
        
        .detail-pill {
            display: inline-block;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 11px;
            margin: 1px 3px 1px 0;
        }
        .detail-pill-frequency { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .detail-pill-duration { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .detail-pill-instruction { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .detail-pill-relation { background: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
        
        .detail-line {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 3px 4px;
            margin-bottom: 2px;
            padding: 2px 0;
        }
        .detail-line .separator { color: #94a3b8; font-size: 12px; font-weight: bold; }
        .detail-line .instruction-text { font-size: 11px; color: #475569; font-style: italic; background: #f8fafc; padding: 1px 6px; border-radius: 4px; }
        
        .quantity-input {
            width: 70px;
            padding: 4px 6px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            font-size: 13px;
            text-align: center;
        }
        .quantity-input:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .quantity-input.error {
            border-color: #ef4444;
            background: #fef2f2;
        }
        .quantity-input.success {
            border-color: #10b981;
            background: #ecfdf5;
        }
        .quantity-input.warning {
            border-color: #f59e0b;
            background: #fffbeb;
        }
        
        .stock-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
        }
        .stock-badge.available { background: #d1fae5; color: #065f46; }
        .stock-badge.low { background: #fef3c7; color: #92400e; }
        .stock-badge.out { background: #fee2e2; color: #991b1b; }
        
        .summary-box {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
        }
        .summary-box .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .summary-box .summary-item:last-child {
            border-bottom: none;
            font-weight: 600;
        }
        
        .price-display {
            font-weight: 600;
            color: #0f172a;
        }
        
        @media (max-width: 768px) {
            .info-row .label { width: 100px; font-size: 12px; }
            .info-row .value { font-size: 12px; }
            .table-modern { font-size: 12px; }
            .table-modern thead th { font-size: 10px; padding: 6px 8px; }
            .table-modern tbody td { padding: 6px 8px; }
            .quantity-input { width: 55px; font-size: 11px; }
        }
        
        @media (max-width: 576px) {
            .table-modern { font-size: 11px; }
            .table-modern thead th { padding: 4px 6px; }
            .table-modern tbody td { padding: 4px 6px; }
            .quantity-input { width: 45px; font-size: 10px; padding: 2px 4px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-prescription-bottle" style="color: #3b82f6;"></i> Dispense Prescription</h5>
                <p class="text-muted" style="font-size: 11px;">Review and dispense prescription items</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/pharmacy/prescriptions" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Prescriptions
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-custom alert-success-custom mb-3">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert" style="font-size: 10px;"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-custom alert-danger-custom mb-3">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert" style="font-size: 10px;"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert-custom alert-warning-custom mb-3">
                <i class="fas fa-exclamation-triangle me-2"></i> <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert" style="font-size: 10px;"></button>
            </div>
        <?php endif; ?>

        <!-- Prescription Info -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-prescription text-primary"></i> 
                Prescription #<?php echo htmlspecialchars($prescription['prescription_number']); ?>
                <span class="badge bg-<?php 
                    $statusClass = 'secondary';
                    if ($prescription['status'] == 'issued') $statusClass = 'primary';
                    elseif ($prescription['status'] == 'dispensed') $statusClass = 'info';
                    elseif ($prescription['status'] == 'completed') $statusClass = 'success';
                    elseif ($prescription['status'] == 'canceled') $statusClass = 'danger';
                    elseif ($prescription['status'] == 'draft') $statusClass = 'secondary';
                ?> ms-2"><?php echo ucfirst($prescription['status']); ?></span>
            </div>
            <div class="card-body">
                <!-- Patient Information -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6 class="mb-2"><i class="fas fa-user text-primary me-2"></i>Patient Information</h6>
                        <div class="info-row"><span class="label">Patient ID:</span><span class="value"><?php echo htmlspecialchars($prescription['patient_code'] ?? ''); ?></span></div>
                        <div class="info-row"><span class="label">Patient Name:</span><span class="value"><?php echo htmlspecialchars($prescription['patient_name']); ?></span></div>
                        <div class="info-row"><span class="label">Phone:</span><span class="value"><?php echo htmlspecialchars($prescription['phone'] ?? ''); ?></span></div>
                        <input type="hidden" id="patientId" value="<?php echo $prescription['patient_id']; ?>">
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-2"><i class="fas fa-user-md text-primary me-2"></i>Doctor Information</h6>
                        <div class="info-row"><span class="label">Doctor:</span><span class="value"><?php echo htmlspecialchars($prescription['doctor_name']); ?></span></div>
                        <div class="info-row"><span class="label">Date:</span><span class="value"><?php echo date('d/m/Y', strtotime($prescription['prescription_date'])); ?></span></div>
                        <div class="info-row"><span class="label">Pharmacy Status:</span>
                            <span class="value">
                                <?php
                                    $pharmacyClass = 'secondary';
                                    if ($prescription['pharmacy_status'] == 'pending') $pharmacyClass = 'warning';
                                    elseif ($prescription['pharmacy_status'] == 'processing') $pharmacyClass = 'info';
                                    elseif ($prescription['pharmacy_status'] == 'ready') $pharmacyClass = 'primary';
                                    elseif ($prescription['pharmacy_status'] == 'dispensed') $pharmacyClass = 'success';
                                    elseif ($prescription['pharmacy_status'] == 'collected') $pharmacyClass = 'dark';
                                ?>
                                <span class="badge bg-<?php echo $pharmacyClass; ?>">
                                    <?php echo ucfirst($prescription['pharmacy_status']); ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Advice if exists -->
                <?php if (!empty($prescription['advice_text'])): ?>
                    <div class="mb-3">
                        <h6 class="mb-2"><i class="fas fa-comment-medical text-primary me-2"></i>Advice</h6>
                        <div class="p-2 bg-light rounded"><?php echo nl2br(htmlspecialchars($prescription['advice_text'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Prescription Items - Merged -->
        <div class="card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list text-primary"></i> Prescription Items</span>
                <span class="badge bg-secondary"><?php echo count($items); ?> items</span>
            </div>
            <div class="card-body">
                <?php if (!empty($items) && count($items) > 0): ?>
                    <form method="POST" action="<?php echo BASE_URL; ?>/pharmacy/dispense-process" id="dispenseForm">
                        <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                        <input type="hidden" name="patient_id" value="<?php echo $prescription['patient_id']; ?>">
                        <input type="hidden" name="discount_percent" id="hiddenDiscountPercent" value="0">
                        
                        <div class="table-responsive">
                            <table class="table-modern" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th width="30">#</th>
                                        <th>Medicine</th>
                                        <th width="90">Dosage</th>
                                        <th width="60">Prescribed</th>
                                        <th width="80">Unit Price</th>
                                        <th width="80">Total</th>
                                        <th width="100">Stock</th>
                                        <th width="80">Dispense Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $counter = 1; ?>
                                    <?php foreach ($items as $item): ?>
                                        <?php 
                                            $stockQty = $item['stock_quantity'] ?? 0;
                                            $prescribedQty = $item['quantity'] ?? 1;
                                            $available = $stockQty;
                                            $isAvailable = $available > 0;
                                            $isPartial = $available > 0 && $available < $prescribedQty;
                                            $isOut = $available <= 0;
                                            $defaultQty = $isOut ? 0 : min($prescribedQty, $available);
                                            $unitPrice = $item['selling_price'] ?? 0;
                                            $itemTotal = $defaultQty * $unitPrice;
                                            
                                            // Get details - ALL details are preserved
                                            $details = $item['details'] ?? [];
                                        ?>
                                        <tr>
                                            <td><?php echo $counter++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['medicine_name'] ?? $item['drug_name']); ?></strong>
                                                <?php if (!empty($item['strength'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['strength']); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($item['generic_name'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['generic_name']); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($details)): ?>
                                                    <br>
                                                    <?php foreach ($details as $d): ?>
                                                        <?php 
                                                        if (isset($d['empty']) && $d['empty'] === true) continue;
                                                        $hasFreq = !empty($d['frequency']);
                                                        $hasDur = !empty($d['duration']);
                                                        $hasInst = !empty($d['instruction']);
                                                        $hasRelation = !empty($d['relation_to_food']);
                                                        if (!$hasFreq && !$hasDur && !$hasInst && !$hasRelation) continue;
                                                        ?>
                                                        <span style="font-size:10px;color:#64748b;">
                                                            <?php if ($hasFreq): ?>
                                                                <span class="detail-pill detail-pill-frequency" style="font-size:9px;padding:1px 6px;"><?php echo htmlspecialchars($d['frequency']); ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($hasFreq && ($hasDur || $hasInst || $hasRelation)): ?>
                                                                <span class="separator" style="font-size:9px;">•</span>
                                                            <?php endif; ?>
                                                            <?php if ($hasDur): ?>
                                                                <span class="detail-pill detail-pill-duration" style="font-size:9px;padding:1px 6px;"><?php echo htmlspecialchars($d['duration']); ?></span>
                                                            <?php endif; ?>
                                                            <?php if ($hasDur && ($hasInst || $hasRelation)): ?>
                                                                <span class="separator" style="font-size:9px;">•</span>
                                                            <?php endif; ?>
                                                            <?php if ($hasInst): ?>
                                                                <span class="instruction-text" style="font-size:9px;">"<?php echo htmlspecialchars($d['instruction']); ?>"</span>
                                                            <?php endif; ?>
                                                            <?php if ($hasInst && $hasRelation): ?>
                                                                <span class="separator" style="font-size:9px;">•</span>
                                                            <?php endif; ?>
                                                            <?php if ($hasRelation): ?>
                                                                <span class="detail-pill detail-pill-relation" style="font-size:9px;padding:1px 6px;"><?php echo htmlspecialchars($d['relation_to_food']); ?></span>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['dosage'] ?? ''); ?></td>
                                            <td class="text-center"><strong><?php echo $prescribedQty; ?></strong></td>
                                            <td>
                                                <span class="price-display">
                                                    <?php if ($unitPrice > 0): ?>
                                                        ৳ <?php echo number_format($unitPrice, 2); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="price-display" id="total_display_<?php echo $item['id']; ?>">
                                                    ৳ <?php echo number_format($itemTotal, 2); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($isOut): ?>
                                                    <span class="stock-badge out"><i class="fas fa-times-circle"></i> 0 in stock</span>
                                                <?php elseif ($isPartial): ?>
                                                    <span class="stock-badge low"><i class="fas fa-exclamation-triangle"></i> <?php echo $available; ?> available</span>
                                                <?php else: ?>
                                                    <span class="stock-badge available"><i class="fas fa-check-circle"></i> <?php echo $available; ?> in stock</span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['expiry_date'])): ?>
                                                    <br><small class="text-muted">Expires: <?php echo date('d/m/Y', strtotime($item['expiry_date'])); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty($item['batch_number'])): ?>
                                                    <br><small class="text-muted">Batch: <?php echo htmlspecialchars($item['batch_number']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <input type="number" 
                                                       name="dispense_qty[<?php echo $item['id']; ?>]" 
                                                       class="quantity-input"
                                                       value="<?php echo $defaultQty; ?>"
                                                       min="0"
                                                       step="1"
                                                       data-prescribed="<?php echo $prescribedQty; ?>"
                                                       data-available="<?php echo $available; ?>"
                                                       data-unit-price="<?php echo $unitPrice; ?>"
                                                       data-item-id="<?php echo $item['id']; ?>"
                                                       onchange="validateQuantity(this)"
                                                       oninput="updateSummary()">
                                                <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                                                <input type="hidden" name="stock_id[]" value="<?php echo $item['stock_id'] ?? ''; ?>">
                                                <input type="hidden" name="medicine_id[]" value="<?php echo $item['medicine_id'] ?? ''; ?>">
                                                <input type="hidden" name="unit_price[]" value="<?php echo $unitPrice; ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Dispense Summary with Totals -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="summary-box">
                                    <h6 class="mb-2"><i class="fas fa-calculator text-primary"></i> Dispense Summary</h6>
                                    <div class="summary-item">
                                        <span>Total Prescribed Items:</span>
                                        <span><strong id="totalPrescribed"><?php echo count($items); ?></strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Items to Dispense:</span>
                                        <span><strong id="totalDispense">0</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Total Quantity:</span>
                                        <span><strong id="totalQuantity">0</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Subtotal:</span>
                                        <span><strong id="subtotalAmount">৳ 0.00</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Discount (%):</span>
                                        <span>
                                            <input type="number" name="discount_percent_input" id="discountPercent" class="form-control form-control-sm" style="width:80px;display:inline-block;" value="0" min="0" max="100" onchange="updateTotals()" oninput="updateTotals()">
                                            <span>%</span>
                                        </span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Discount Amount:</span>
                                        <span><strong id="discountAmount">৳ 0.00</strong></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Tax (0%):</span>
                                        <span><strong id="taxAmount">৳ 0.00</strong></span>
                                    </div>
                                    <div class="summary-item" style="font-size:16px;font-weight:700;color:#10b981;border-top:2px solid #10b981;padding-top:6px;">
                                        <span>Total Amount:</span>
                                        <span><strong id="grandTotal">৳ 0.00</strong></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-bold small text-muted">Dispense Notes <span class="text-muted">(Optional)</span></label>
                                    <textarea class="form-control" name="dispense_notes" rows="3" placeholder="Add any notes about this dispense..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <hr>
                                <div class="d-flex gap-2 flex-wrap justify-content-between align-items-center">
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="submit" class="btn btn-success" id="dispenseBtn">
                                            <i class="fas fa-check-circle"></i> Dispense
                                        </button>
                                        <button type="button" class="btn btn-info" onclick="printPrescription()">
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>/pharmacy/prescriptions'">
                                            <i class="fas fa-arrow-left"></i> Cancel
                                        </button>
                                    </div>
                                    <div class="text-muted small">
                                        <i class="fas fa-info-circle"></i> 
                                        <span id="totalItemsDisplay">0</span> items to dispense
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-prescription fa-3x text-muted mb-2"></i>
                        <h6>No Items Found</h6>
                        <p class="text-muted">This prescription has no items to dispense.</p>
                        <a href="<?php echo BASE_URL; ?>/pharmacy/prescriptions" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Prescriptions
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- If already dispensed, show dispensed items -->
        <?php if (!empty($saleItems) && count($saleItems) > 0): ?>
            <div class="card-custom">
                <div class="card-header-custom">
                    <i class="fas fa-check-circle text-success"></i> Dispensed Items
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table-modern">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Medicine</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach ($saleItems as $saleItem): ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($saleItem['medicine_name'] ?? $saleItem['item_name']); ?></strong>
                                            <?php if (!empty($saleItem['strength'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($saleItem['strength']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $saleItem['quantity']; ?></td>
                                        <td>৳ <?php echo number_format($saleItem['unit_price'], 2); ?></td>
                                        <td>৳ <?php echo number_format($saleItem['total_amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';

    // ===== VALIDATE QUANTITY =====
    function validateQuantity(input) {
        const val = parseInt(input.value) || 0;
        const available = parseInt(input.dataset.available) || 0;
        const prescribed = parseInt(input.dataset.prescribed) || 0;
        const unitPrice = parseFloat(input.dataset.unitPrice) || 0;
        
        if (val < 0) {
            input.value = 0;
        }
        
        if (val > 0 && val > available && available > 0) {
            input.className = 'quantity-input warning';
        } else if (val > 0 && val > prescribed) {
            input.className = 'quantity-input warning';
        } else if (val === 0) {
            input.className = 'quantity-input error';
        } else {
            input.className = 'quantity-input success';
        }
        
        // Update total for this item
        const itemId = input.dataset.itemId;
        const total = val * unitPrice;
        const totalDisplay = document.getElementById('total_display_' + itemId);
        if (totalDisplay) {
            totalDisplay.textContent = '৳ ' + total.toFixed(2);
        }
        
        updateSummary();
    }

    // ===== UPDATE SUMMARY =====
    function updateSummary() {
        const inputs = document.querySelectorAll('input[name^="dispense_qty"]');
        let totalItems = 0;
        let totalQty = 0;
        let subtotal = 0;
        
        inputs.forEach(function(input) {
            const val = parseInt(input.value) || 0;
            const unitPrice = parseFloat(input.dataset.unitPrice) || 0;
            if (val > 0) {
                totalItems++;
                totalQty += val;
                subtotal += val * unitPrice;
            }
        });
        
        document.getElementById('totalDispense').textContent = totalItems;
        document.getElementById('totalQuantity').textContent = totalQty;
        document.getElementById('totalItemsDisplay').textContent = totalItems;
        document.getElementById('subtotalAmount').textContent = '৳ ' + subtotal.toFixed(2);
        
        // Update hidden discount percent field
        const discountVal = parseFloat(document.getElementById('discountPercent').value) || 0;
        document.getElementById('hiddenDiscountPercent').value = discountVal;
        
        // Update totals with discount
        updateTotals();
    }

    // ===== UPDATE TOTALS WITH DISCOUNT =====
    function updateTotals() {
        const subtotalText = document.getElementById('subtotalAmount').textContent;
        const subtotal = parseFloat(subtotalText.replace('৳ ', '')) || 0;
        const discountPercent = parseFloat(document.getElementById('discountPercent').value) || 0;
        
        // Update hidden field
        document.getElementById('hiddenDiscountPercent').value = discountPercent;
        
        const discountAmount = subtotal * (discountPercent / 100);
        const taxAmount = 0; // Tax is 0% for now
        const grandTotal = subtotal - discountAmount + taxAmount;
        
        document.getElementById('discountAmount').textContent = '৳ ' + discountAmount.toFixed(2);
        document.getElementById('taxAmount').textContent = '৳ ' + taxAmount.toFixed(2);
        document.getElementById('grandTotal').textContent = '৳ ' + grandTotal.toFixed(2);
    }

    // ===== PRINT PRESCRIPTION =====
    function printPrescription() {
        const presId = '<?php echo $prescription['id']; ?>';
        window.open(BASE_URL + '/prescriptions/print/' + presId, '_blank');
    }

    // ===== TOAST NOTIFICATION =====
    function showToast(message, type = 'success') {
        const colors = { 
            success: '#10b981', 
            error: '#ef4444', 
            warning: '#f59e0b', 
            info: '#3b82f6' 
        };
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${colors[type] || '#3b82f6'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 99999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease;
            max-width: 350px;
        `;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ===== ADD SLIDE-IN ANIMATION =====
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    // ===== FORM SUBMISSION =====
    document.getElementById('dispenseForm')?.addEventListener('submit', function(e) {
        const inputs = document.querySelectorAll('input[name^="dispense_qty"]');
        let hasItems = false;
        
        inputs.forEach(function(input) {
            const val = parseInt(input.value) || 0;
            if (val > 0) {
                hasItems = true;
            }
        });
        
        if (!hasItems) {
            e.preventDefault();
            showToast('Please select at least one item to dispense.', 'warning');
            return;
        }
        
        // Update hidden discount field before submit
        const discountVal = parseFloat(document.getElementById('discountPercent').value) || 0;
        document.getElementById('hiddenDiscountPercent').value = discountVal;
        
        const btn = document.getElementById('dispenseBtn');
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
        btn.disabled = true;
    });

    // ===== INITIALIZE ON LOAD =====
    document.addEventListener('DOMContentLoaded', function() {
        updateSummary();
        
        document.querySelectorAll('input[name^="dispense_qty"]').forEach(function(input) {
            input.addEventListener('change', updateSummary);
            input.addEventListener('input', updateSummary);
        });
        
        document.getElementById('discountPercent')?.addEventListener('input', updateTotals);
        document.getElementById('discountPercent')?.addEventListener('change', updateTotals);
    });
    </script>
</body>
</html>