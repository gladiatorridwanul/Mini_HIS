<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
        }
        
        .card-custom {
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }
        
        .card-header-custom {
            background: white;
            border-bottom: 2px solid #10b981;
            padding: 10px 15px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .serial-badge {
            background: #10b981;
            color: white;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
            display: inline-block;
        }
        .status-scheduled { background: #f59e0b; color: white; }
        .status-waiting { background: #f59e0b; color: white; }
        .status-in_progress { background: #3b82f6; color: white; }
        .status-completed { background: #10b981; color: white; }
        .status-canceled { background: #ef4444; color: white; }
        
        .payment-badge-paid { background: #10b981; color: white; padding: 2px 6px; border-radius: 10px; font-size: 9px; display: inline-block; }
        .payment-badge-partial { background: #f59e0b; color: white; padding: 2px 6px; border-radius: 10px; font-size: 9px; display: inline-block; }
        .payment-badge-pending { background: #ef4444; color: white; padding: 2px 6px; border-radius: 10px; font-size: 9px; display: inline-block; }
        .payment-badge-refunded { background: #6b7280; color: white; padding: 2px 6px; border-radius: 10px; font-size: 9px; display: inline-block; }
        
        .summary-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 12px;
            padding: 12px;
            text-align: center;
            transition: transform 0.3s;
        }
        .summary-card:hover { transform: translateY(-3px); }
        .summary-card h3 { font-size: 22px; font-weight: 700; margin: 0; }
        .summary-card small { font-size: 11px; opacity: 0.9; }
        
        .action-icons {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .action-icon {
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 12px;
            text-decoration: none;
            border: none;
            background: transparent;
        }
        .action-icon:hover { transform: scale(1.05); }
        .icon-process { background: #e0e7ff; color: #3b82f6; }
        .icon-process:hover { background: #3b82f6; color: white; }
        .icon-complete { background: #d1fae5; color: #10b981; }
        .icon-complete:hover { background: #10b981; color: white; }
        .icon-payment { background: #ede9fe; color: #8b5cf6; }
        .icon-payment:hover { background: #8b5cf6; color: white; }
        .icon-slip { background: #fef3c7; color: #f59e0b; }
        .icon-slip:hover { background: #f59e0b; color: white; }
        .icon-cancel { background: #fee2e2; color: #ef4444; }
        .icon-cancel:hover { background: #ef4444; color: white; }
        .icon-print { background: #d1fae5; color: #10b981; }
        .icon-print:hover { background: #10b981; color: white; }
        .icon-prescription { background: #dbeafe; color: #2563eb; }
        .icon-prescription:hover { background: #2563eb; color: white; }
        .patient-link { cursor: pointer; color: #1f2937; text-decoration: none; }
        .patient-link:hover { color: #10b981; text-decoration: underline; }
        
        .filter-select, .filter-input {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 6px 10px;
            font-size: 12px;
        }
        
        .table th {
            background: #f8fafc;
            font-weight: 600;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            font-size: 11px;
            padding: 8px 10px;
        }
        .table td {
            vertical-align: middle;
            padding: 8px 10px;
            font-size: 12px;
        }
        
        .modal-content {
            border-radius: 12px;
            border: none;
        }
        .modal-header {
            border-bottom: 2px solid #10b981;
            background: #f8fafc;
            border-radius: 12px 12px 0 0;
            padding: 10px 15px;
        }
        .modal-header h5 { font-size: 15px; }
        .modal-body { padding: 15px; font-size: 12px; }
        .modal-footer { padding: 10px 15px; }
        
        .patient-info-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .info-row { margin-bottom: 8px; display: flex; }
        .info-label { width: 100px; font-weight: 600; color: #4b5563; }
        .info-value { flex: 1; color: #1f2937; }
        
        .alert-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 250px;
            animation: slideIn 0.3s ease-out;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
        }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .payment-details {
            background: #f8fafc;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .payment-history-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 0;
            font-size: 11px;
        }
        
        /* Discount input styles - matches bills.php */
        .discount-input-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .discount-input-group .discount-btn {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
            background: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            color: #1f2937;
        }
        .discount-input-group .discount-btn:hover {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        .discount-input-group .discount-btn:active {
            transform: scale(0.95);
        }
        .discount-input-group .discount-input {
            width: 65px;
            text-align: center;
            padding: 4px 2px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        .discount-input-group .discount-input:focus {
            border-color: #10b981;
            outline: none;
            box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
        }
        .discount-input-group .discount-label {
            font-size: 11px;
            color: #6b7280;
        }
        .discount-amount-display {
            font-size: 12px;
            color: #10b981;
            font-weight: 600;
        }
        
        .full-discount-check {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 6px 0;
            padding: 6px 8px;
            background: #f0fdf4;
            border-radius: 6px;
            border: 1px solid #d1fae5;
        }
        .full-discount-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #10b981;
        }
        .full-discount-check label {
            font-size: 12px;
            font-weight: 600;
            color: #065f46;
            cursor: pointer;
            margin: 0;
        }
        
        .discount-covers-due {
            display: none;
            background: #d1fae5;
            border-radius: 6px;
            padding: 6px 10px;
            margin-top: 4px;
            font-size: 11px;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .discount-covers-due.show {
            display: block;
        }
        
        .payment-amount-input {
            font-size: 14px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .action-icons { gap: 4px; }
            .action-icon { width: 24px; height: 24px; font-size: 10px; }
            .table td, .table th { padding: 6px 8px; }
            .discount-input-group .discount-input { width: 50px; font-size: 12px; }
            .discount-input-group .discount-btn { width: 24px; height: 24px; font-size: 12px; }
        }
        
        .btn-sm-custom {
            padding: 4px 10px;
            font-size: 11px;
            border-radius: 6px;
        }

        /* Thermal receipt print styles - matches invoice.php style */
        .thermal-receipt {
            font-family: 'Courier New', monospace;
            width: 280px;
            margin: 0 auto;
            padding: 10px 12px;
            font-size: 10px;
            line-height: 1.4;
            background: white;
            color: #000;
        }
        .thermal-receipt .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
            margin-bottom: 6px;
        }
        .thermal-receipt .header .hospital-name {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .thermal-receipt .header .address {
            font-size: 8px;
            color: #444;
        }
        .thermal-receipt .header .contact {
            font-size: 8px;
            color: #444;
        }
        .thermal-receipt .header .web {
            font-size: 8px;
            color: #444;
        }
        .thermal-receipt .receipt-title {
            text-align: center;
            font-weight: 700;
            font-size: 12px;
            margin: 4px 0;
            letter-spacing: 2px;
        }
        .thermal-receipt .divider {
            border-top: 1px dashed #000;
            margin: 4px 0;
        }
        .thermal-receipt .row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
        }
        .thermal-receipt .row .label {
            font-weight: 600;
        }
        .thermal-receipt .row .value {
            text-align: right;
        }
        .thermal-receipt .row .value-strong {
            font-weight: 700;
            text-align: right;
        }
        .thermal-receipt .serial-number {
            text-align: center;
            font-size: 22px;
            font-weight: 900;
            color: #10b981;
            padding: 4px 0;
            letter-spacing: 3px;
        }
        .thermal-receipt .footer {
            text-align: center;
            border-top: 1px dashed #000;
            padding-top: 6px;
            margin-top: 6px;
            font-size: 8px;
            color: #666;
        }
        .thermal-receipt .footer .thankyou {
            font-size: 10px;
            font-weight: 700;
            color: #000;
        }
        .thermal-receipt .amount-total {
            font-size: 13px;
            font-weight: 700;
            color: #10b981;
        }
        @media print {
            body * { visibility: hidden; }
            .thermal-receipt, .thermal-receipt * { visibility: visible; }
            .thermal-receipt { position: absolute; left: 0; top: 0; width: 280px; padding: 8px 10px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-calendar-check" style="color: #10b981;"></i> Appointments</h5>
                <p class="text-muted" style="font-size: 11px;">Manage appointments & payments</p>
            </div>
            <div class="d-flex gap-2">
                <button id="refreshPage" class="btn btn-light btn-sm" title="Refresh">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <a href="<?php echo BASE_URL; ?>/reception/select-patient" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> New
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-card">
            <div class="row g-2">
                <div class="col-md-3">
                    <select id="filter_doctor" class="form-select filter-select">
                        <option value="">All Doctors</option>
                        <?php if(isset($doctors) && is_array($doctors)): ?>
                            <?php foreach($doctors as $doc): ?>
                            <option value="<?php echo $doc['id']; ?>">Dr. <?php echo $doc['first_name'] . ' ' . $doc['last_name']; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" id="filter_date" class="form-control filter-input" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-2">
                    <select id="filter_shift" class="form-select filter-select">
                        <option value="">All Shifts</option>
                        <option value="morning">Morning</option>
                        <option value="evening">Evening</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filter_status" class="form-select filter-select">
                        <option value="">All Status</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="waiting">Waiting</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="canceled">Canceled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button id="apply_filters" class="btn btn-primary btn-sm" style="background: #10b981; border: none;"><i class="fas fa-search"></i></button>
                        <button id="reset_filters" class="btn btn-secondary btn-sm"><i class="fas fa-undo"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <div class="summary-card"><h3 id="total_count">0</h3><small>Total</small></div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="summary-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                    <h3 id="pending_count">0</h3><small>Pending Payment</small>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="summary-card" style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);">
                    <h3 id="progress_count">0</h3><small>In Progress</small>
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <div class="summary-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    <h3 id="completed_count">0</h3><small>Completed</small>
                </div>
            </div>
        </div>

        <!-- Morning Session Table -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-sun text-warning"></i> Morning
                <span class="badge bg-success ms-2" id="morning_count">0</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Amount</th>
                            <th>Discount</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Payment Status</th>
                            <th>Appointment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="morning_tbody">
                        <tr><td colspan="10" class="text-center text-muted py-3">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Evening Session Table -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-moon text-primary"></i> Evening
                <span class="badge bg-success ms-2" id="evening_count">0</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Amount</th>
                            <th>Discount</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Payment Status</th>
                            <th>Appointment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="evening_tbody">
                        <tr><td colspan="10" class="text-center text-muted py-3">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Patient Details Modal -->
    <div class="modal fade" id="patientModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-circle"></i> Patient Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="patientModalContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="patientViewLink" class="btn btn-primary btn-sm" target="_blank">View Full Profile</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal - Simplified Design Matching 2nd Image -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="background: #10b981; color: white; border-bottom: none;">
                    <h5 class="modal-title" style="color: white;"><i class="fas fa-credit-card me-2"></i>Receive Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="payment_appointment_id">
                    <input type="hidden" id="payment_bill_id">
                    <input type="hidden" id="payment_patient_id">
                    
                    <!-- Due Amount - Clean display -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Due Amount</label>
                        <input type="text" id="dueAmountDisplay" class="form-control form-control-sm" readonly style="font-size:16px; font-weight:700; color:#dc2626;">
                    </div>
                    
                    <!-- Full Discount Checkbox -->
                    <div class="full-discount-check mb-2">
                        <input type="checkbox" id="fullDiscountCheck">
                        <label for="fullDiscountCheck">✅ Full Discount – Mark as Paid without receiving cash</label>
                    </div>
                    
                    <!-- Discount Row -->
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Discount Applied (%)</label>
                            <div class="discount-input-group">
                                <button class="discount-btn" onclick="adjustDiscount(-1)">−</button>
                                <input type="number" id="discountPercent" class="discount-input" value="0" min="0" max="100" 
                                       onchange="updateDiscountCalculation()" oninput="updateDiscountCalculation()">
                                <button class="discount-btn" onclick="adjustDiscount(1)">+</button>
                                <span class="discount-label">%</span>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Discount Amount (৳)</label>
                            <div class="discount-input-group">
                                <button class="discount-btn" onclick="adjustDiscountAmount(-5)">−</button>
                                <input type="number" id="discountAmount" class="discount-input" value="0" min="0" step="0.01" 
                                       onchange="updateDiscountCalculation()" oninput="updateDiscountCalculation()">
                                <button class="discount-btn" onclick="adjustDiscountAmount(5)">+</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Receive Amount -->
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Receive Amount (after discount) *</label>
                        <input type="number" step="0.01" id="payment_amount" class="form-control form-control-sm payment-amount-input" required>
                        <small class="text-muted">Enter <strong>0</strong> if discount covers the full due amount</small>
                        <div id="discountCoversDue" class="discount-covers-due">
                            ✅ This discount covers the full due amount. Click <strong>Process Payment</strong> to mark as Paid.
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Payment Method *</label>
                        <select id="payment_method" class="form-select form-select-sm">
                            <option value="cash">💵 Cash</option>
                            <option value="card">💳 Card</option>
                            <option value="mobile_banking">📱 Mobile Banking</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                        </select>
                    </div>
                    
                    <!-- Transaction ID -->
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Transaction ID</label>
                        <input type="text" id="transaction_id" class="form-control form-control-sm" placeholder="Optional">
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-2">
                        <label class="form-label fw-bold small">Notes</label>
                        <textarea id="payment_notes" class="form-control form-control-sm" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                    
                    <!-- Payment History -->
                    <div id="payment_history_container" style="display: none;">
                        <div class="payment-details">
                            <strong><i class="fas fa-history"></i> Payment History</strong>
                            <div id="payment_history_list" class="mt-1"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success btn-sm" id="confirm_payment">Process Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Slip Modal - Full Company Info like invoice.php -->
    <div class="modal fade" id="receiptModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 320px;">
            <div class="modal-content">
                <div class="modal-header" style="background: #10b981; color: white; border-bottom: none;">
                    <h5 class="modal-title" style="color: white;"><i class="fas fa-receipt"></i> Booking Slip</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="receiptContent" style="background: #fafafa;"></div>
                <div class="modal-footer" style="border-top: none; padding: 8px 12px;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="printReceipt()"><i class="fas fa-print me-1"></i>Print</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Slip Modal -->
    <div class="modal fade" id="paymentSlipModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 320px;">
            <div class="modal-content">
                <div class="modal-header" style="background: #10b981; color: white; border-bottom: none;">
                    <h5 class="modal-title" style="color: white;"><i class="fas fa-receipt"></i> Payment Receipt</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="paymentSlipContent" style="background: #fafafa;"></div>
                <div class="modal-footer" style="border-top: none; padding: 8px 12px;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="printPaymentSlip()"><i class="fas fa-print me-1"></i>Print</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash-alt text-danger"></i> Cancel Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this appointment?</p>
                    <div class="mb-2">
                        <label class="form-label" style="font-size: 12px;">Reason</label>
                        <textarea id="cancel_reason" class="form-control form-control-sm" rows="2" placeholder="Optional..."></textarea>
                    </div>
                    <div id="payment_refund_info" style="display: none;" class="alert alert-warning py-1 px-2" style="font-size: 11px;">
                        <i class="fas fa-info-circle"></i> Any payments made will be refunded
                    </div>
                    <input type="hidden" id="cancel_appointment_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger btn-sm" id="confirm_cancel">Cancel Appointment</button>
                </div>
            </div>
        </div>
    </div>

    <div id="alert_container"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    let currentReceiptHtml = '';
    let currentPaymentSlipHtml = '';
    let currentDueAmount = 0;
    let currentAppointmentId = null;
    let discountTimeout = null;

    function showAlert(message, type = 'success') {
        const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
        const alertDiv = $(`<div class="alert-toast" style="background: ${colors[type]}; color: white;">${message}</div>`);
        $('#alert_container').html(alertDiv);
        setTimeout(() => { alertDiv.fadeOut(300, function() { $(this).remove(); }); }, 3000);
    }

    function formatAmount(amount) {
        return '৳ ' + parseFloat(amount).toFixed(2);
    }

    function getPaymentBadge(paymentStatus, paidAmount, totalAmount) {
        if(paymentStatus === 'refunded') {
            return '<span class="payment-badge-refunded"><i class="fas fa-undo"></i> REFUNDED</span>';
        } else if(paymentStatus === 'paid' || parseFloat(paidAmount) >= parseFloat(totalAmount)) {
            return '<span class="payment-badge-paid"><i class="fas fa-check-circle"></i> PAID</span>';
        } else if(parseFloat(paidAmount) > 0) {
            return '<span class="payment-badge-partial"><i class="fas fa-charging-station"></i> PARTIAL</span>';
        }
        return '<span class="payment-badge-pending"><i class="fas fa-clock"></i> PENDING</span>';
    }

    function getStatusBadge(status) {
        const badges = {
            'scheduled': '<span class="status-badge status-scheduled">SCHEDULED</span>',
            'waiting': '<span class="status-badge status-waiting">WAITING</span>',
            'in_progress': '<span class="status-badge status-in_progress">IN PROGRESS</span>',
            'completed': '<span class="status-badge status-completed">COMPLETED</span>',
            'canceled': '<span class="status-badge status-canceled">CANCELED</span>'
        };
        return badges[status] || badges['scheduled'];
    }

    function viewPatientDetails(patientId) {
        $.ajax({
            url: BASE_URL + '/api/get-patient-details',
            method: 'GET',
            data: { patient_id: patientId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    const p = response.data;
                    let html = `
                        <div class="patient-info-card">
                            <div class="info-row"><div class="info-label">Name:</div><div class="info-value">${p.full_name || p.first_name + ' ' + p.last_name}</div></div>
                            <div class="info-row"><div class="info-label">Patient ID:</div><div class="info-value">${p.patient_code}</div></div>
                            <div class="info-row"><div class="info-label">Phone:</div><div class="info-value">${p.phone}</div></div>
                            <div class="info-row"><div class="info-label">Email:</div><div class="info-value">${p.email || 'N/A'}</div></div>
                            <div class="info-row"><div class="info-label">Gender:</div><div class="info-value">${p.gender ? p.gender.toUpperCase() : 'N/A'}</div></div>
                            <div class="info-row"><div class="info-label">Blood Group:</div><div class="info-value">${p.blood_group || 'N/A'}</div></div>
                            <div class="info-row"><div class="info-label">DOB:</div><div class="info-value">${p.date_of_birth || 'N/A'}</div></div>
                            <div class="info-row"><div class="info-label">Address:</div><div class="info-value">${p.address || 'N/A'}</div></div>
                        </div>
                    `;
                    $('#patientModalContent').html(html);
                    $('#patientViewLink').attr('href', BASE_URL + '/patient/view?id=' + patientId);
                    $('#patientModal').modal('show');
                }
            }
        });
    }

    function loadAppointments() {
        const doctorId = $('#filter_doctor').val();
        const date = $('#filter_date').val();
        const shift = $('#filter_shift').val();
        const status = $('#filter_status').val();
        
        $.ajax({
            url: BASE_URL + '/api/get-filtered-appointments',
            method: 'GET',
            data: { doctor_id: doctorId, date: date, shift: shift, status: status },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    renderAppointments(response.data);
                    updateSummary(response.data);
                }
            },
            error: function() {
                showAlert('Error loading appointments', 'error');
            }
        });
    }

    function renderAppointments(appointments) {
        const morningApps = appointments.filter(a => a.session_type === 'morning');
        const eveningApps = appointments.filter(a => a.session_type === 'evening');
        
        $('#morning_count').text(morningApps.length);
        $('#evening_count').text(eveningApps.length);
        
        renderTable('morning_tbody', morningApps);
        renderTable('evening_tbody', eveningApps);
    }

    function renderTable(tbodyId, appointments) {
        if(appointments.length === 0) {
            $(`#${tbodyId}`).html('<tr><td colspan="10" class="text-center text-muted py-3">No appointments</td></tr>');
            return;
        }
        
        let html = '';
        appointments.forEach(app => {
            const paidAmount = parseFloat(app.payment_received) || 0;
            const totalAmount = parseFloat(app.total_amount) || 0;
            const discountAmount = parseFloat(app.discount_amount) || 0;
            const dueAmount = totalAmount - paidAmount - discountAmount;
            const displayDue = dueAmount < 0 ? 0 : dueAmount;
            
            html += `
                <tr data-appointment-id="${app.id}">
                    <td><span class="serial-badge">${app.serial_number || 'N/A'}</span></td>
                    <td><a href="javascript:void(0)" class="patient-link" onclick="viewPatientDetails(${app.patient_id})">
                        <strong>${app.first_name} ${app.last_name}</strong><br><small>${app.patient_code || ''}</small>
                    </a></td>
                    <td>Dr. ${app.doctor_fname} ${app.doctor_lname}<br><small>${app.specialization || ''}</small></td>
                    <td class="appointment-total">${formatAmount(totalAmount)}</td>
                    <td class="appointment-discount">${formatAmount(discountAmount)}</td>
                    <td class="appointment-paid">${formatAmount(paidAmount)}</td>
                    <td class="appointment-due">${formatAmount(displayDue)}</td>
                    <td class="appointment-payment-status">${getPaymentBadge(app.payment_status, paidAmount, totalAmount)}</td>
                    <td>${getStatusBadge(app.status)}</td>
                    <td>
                        <div class="action-icons">
                            <a href="${BASE_URL}/prescriptions/create?appointment_id=${app.id}" 
                               class="action-icon icon-prescription" title="Create Prescription">
                                <i class="fas fa-prescription"></i>
                            </a>
                            <button class="action-icon icon-process" onclick="updateStatus(${app.id}, 'in_progress')" title="Start Process"><i class="fas fa-play"></i></button>
                            <button class="action-icon icon-complete" onclick="updateStatus(${app.id}, 'completed')" title="Complete"><i class="fas fa-check"></i></button>
                            <button class="action-icon icon-payment" onclick="openPaymentModal(${app.id})" title="Receive Payment"><i class="fas fa-money-bill-wave"></i></button>
                            <button class="action-icon icon-slip" onclick="viewReceipt(${app.id})" title="Booking Slip">
                                <i class="fas fa-receipt"></i>
                            </button>
                            <button class="action-icon icon-print" onclick="printSerialSlip(${app.id})" title="Print Serial"><i class="fas fa-print"></i></button>
                            <button class="action-icon icon-cancel" onclick="openCancelModal(${app.id})" title="Cancel"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        });
        $(`#${tbodyId}`).html(html);
    }

    function updateSummary(appointments) {
        $('#total_count').text(appointments.length);
        const pendingPayment = appointments.filter(a => a.payment_status === 'pending' || a.payment_status === 'partial');
        $('#pending_count').text(pendingPayment.length);
        $('#progress_count').text(appointments.filter(a => a.status === 'in_progress').length);
        $('#completed_count').text(appointments.filter(a => a.status === 'completed').length);
    }

    function updateStatus(appointmentId, status) {
        $.ajax({
            url: BASE_URL + '/api/update-appointment-status',
            method: 'POST',
            data: { appointment_id: appointmentId, status: status },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    showAlert('Status updated successfully', 'success');
                    loadAppointments();
                } else {
                    showAlert(response.message, 'error');
                }
            }
        });
    }

    // ========== DISCOUNT FUNCTIONS - MATCHES bills.php ==========
    function adjustDiscount(change) {
        let input = $('#discountPercent');
        let currentVal = parseFloat(input.val()) || 0;
        let newVal = currentVal + change;
        if (newVal < 0) newVal = 0;
        if (newVal > 100) newVal = 100;
        input.val(newVal);
        setTimeout(function() {
            updateDiscountCalculation();
        }, 50);
    }

    function adjustDiscountAmount(change) {
        let input = $('#discountAmount');
        let currentVal = parseFloat(input.val()) || 0;
        let newVal = currentVal + change;
        if (newVal < 0) newVal = 0;
        input.val(newVal);
        setTimeout(function() {
            updateDiscountCalculation();
        }, 50);
    }

    function updateDiscountCalculation() {
        if (discountTimeout) {
            clearTimeout(discountTimeout);
        }
        
        discountTimeout = setTimeout(function() {
            let dueAmount = currentDueAmount;
            let discPercent = parseFloat($('#discountPercent').val()) || 0;
            let discAmount = parseFloat($('#discountAmount').val()) || 0;
            
            let discount = 0;
            if ($('#discountAmount').val() !== '' && discAmount > 0) {
                discount = Math.min(discAmount, dueAmount);
            } else if (discPercent > 0) {
                discount = dueAmount * (discPercent / 100);
            }
            discount = Math.min(discount, dueAmount);
            
            let receiveAmount = dueAmount - discount;
            if (receiveAmount < 0) receiveAmount = 0;
            
            $('#payment_amount').val(receiveAmount.toFixed(2));
            $('#discountDisplay').text('Discount: ৳ ' + discount.toFixed(2));
            
            if (discount > 0 && Math.abs(discount - dueAmount) < 0.01) {
                $('#discountCoversDue').addClass('show');
            } else {
                $('#discountCoversDue').removeClass('show');
            }
            
            discountTimeout = null;
        }, 300);
    }

    $('#fullDiscountCheck').on('change', function() {
        if ($(this).is(':checked')) {
            let due = currentDueAmount;
            $('#discountAmount').val(due.toFixed(2));
            $('#discountPercent').val(0);
            $('#discountPercent').prop('disabled', true);
            setTimeout(function() {
                updateDiscountCalculation();
            }, 50);
        } else {
            $('#discountAmount').val(0);
            $('#discountPercent').val(0);
            $('#discountPercent').prop('disabled', false);
            setTimeout(function() {
                updateDiscountCalculation();
            }, 50);
        }
    });

    // ========== OPEN PAYMENT MODAL - Simplified ==========
    function openPaymentModal(appointmentId) {
        currentAppointmentId = appointmentId;
        
        $.ajax({
            url: BASE_URL + '/api/get-appointment-details',
            method: 'GET',
            data: { appointment_id: appointmentId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    const data = response.data;
                    const paidAmount = parseFloat(data.payment_received) || 0;
                    const totalAmount = parseFloat(data.total_amount) || 0;
                    const discountAmount = parseFloat(data.discount_amount) || 0;
                    const dueAmount = totalAmount - paidAmount - discountAmount;
                    currentDueAmount = dueAmount < 0 ? 0 : dueAmount;
                    
                    $('#payment_appointment_id').val(data.id);
                    $('#payment_patient_id').val(data.patient_id);
                    $('#dueAmountDisplay').val(formatAmount(currentDueAmount));
                    
                    // Reset discount fields
                    $('#discountPercent').val(0).prop('disabled', false);
                    $('#discountAmount').val(0);
                    $('#fullDiscountCheck').prop('checked', false);
                    $('#discountCoversDue').removeClass('show');
                    $('#payment_amount').val(currentDueAmount > 0 ? currentDueAmount.toFixed(2) : '0.00');
                    
                    if(data.bill_id) {
                        $('#payment_bill_id').val(data.bill_id);
                    } else {
                        $('#payment_bill_id').val('');
                    }
                    
                    if(data.payments && data.payments.length > 0) {
                        let historyHtml = '';
                        data.payments.forEach(p => {
                            historyHtml += `<div class="payment-history-item"><strong>${formatAmount(p.amount)}</strong> - ${p.payment_method} on ${p.payment_date}</div>`;
                        });
                        $('#payment_history_list').html(historyHtml);
                        $('#payment_history_container').show();
                    } else {
                        $('#payment_history_container').hide();
                    }
                    $('#paymentModal').modal('show');
                }
            }
        });
    }

    $('#confirm_payment').click(function() {
        const appointmentId = $('#payment_appointment_id').val();
        const amount = $('#payment_amount').val();
        const paymentMethod = $('#payment_method').val();
        const transactionId = $('#transaction_id').val();
        const notes = $('#payment_notes').val();
        const billId = $('#payment_bill_id').val();
        const discountPercent = $('#discountPercent').val();
        const discountAmount = $('#discountAmount').val();
        const fullDiscount = $('#fullDiscountCheck').is(':checked') ? 1 : 0;
        const patientId = $('#payment_patient_id').val();
        
        if(!amount || parseFloat(amount) < 0) {
            showAlert('Please enter a valid amount', 'error');
            return;
        }
        
        let apiUrl = BASE_URL + '/api/process-payment';
        let postData = {
            appointment_id: appointmentId,
            amount: amount,
            payment_method: paymentMethod,
            transaction_id: transactionId,
            note: notes,
            discount_percent: discountPercent,
            discount_amount: discountAmount,
            full_discount: fullDiscount
        };
        
        if(billId && parseFloat(billId) > 0) {
            apiUrl = BASE_URL + '/api/process-bill-payment';
            postData = {
                bill_id: billId,
                patient_id: patientId || 0,
                amount: amount,
                payment_method: paymentMethod,
                transaction_id: transactionId,
                notes: notes,
                discount_percent: discountPercent,
                discount_amount: discountAmount,
                full_discount: fullDiscount
            };
        }
        
        $.ajax({
            url: apiUrl,
            method: 'POST',
            data: postData,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    showAlert('Payment received successfully!', 'success');
                    $('#paymentModal').modal('hide');
                    loadAppointments();
                    
                    $('#payment_amount').val('');
                    $('#transaction_id').val('');
                    $('#payment_notes').val('');
                    $('#discountPercent').val(0);
                    $('#discountAmount').val(0);
                    $('#fullDiscountCheck').prop('checked', false);
                    $('#discountCoversDue').removeClass('show');
                } else {
                    showAlert(response.message, 'error');
                }
            },
            error: function() {
                showAlert('Error processing payment', 'error');
            }
        });
    });

    // ========== BOOKING SLIP - Full Company Info like invoice.php ==========
    function viewReceipt(appointmentId) {
        showAlert('Loading booking slip...', 'info');
        
        $.ajax({
            url: BASE_URL + '/api/get-booking-receipt',
            method: 'GET',
            data: { appointment_id: appointmentId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    const d = response.data;
                    const printTime = new Date().toLocaleString('en-US', { 
                        day: '2-digit', month: 'short', year: 'numeric', 
                        hour: '2-digit', minute: '2-digit', hour12: true 
                    });
                    
                    let html = `
                    <div class="thermal-receipt">
                        <div class="header">
                            <div class="hospital-name">UNIDIA HOSPITAL</div>
                            <div class="address">123 Healthcare Street, Medical District</div>
                            <div class="contact">📞 +1 234 567 8900 | ✉ info@unidia.com</div>
                            <div class="web">🌐 www.unidia.com</div>
                            <div style="font-size:7px; color:#888; margin-top:2px;">Print: ${printTime}</div>
                        </div>
                        <div class="receipt-title">BOOKING SLIP</div>
                        <div class="divider"></div>
                        <div class="row"><span class="label">Receipt No:</span><span class="value">${d.receipt_no || 'N/A'}</span></div>
                        <div class="row"><span class="label">Date:</span><span class="value">${d.appointment_date || 'N/A'}</span></div>
                        <div class="divider"></div>
                        <div class="row"><span class="label">Patient ID:</span><span class="value">${d.patient_code || 'N/A'}</span></div>
                        <div class="row"><span class="label">Patient Name:</span><span class="value-strong">${d.patient_name || 'N/A'}</span></div>
                        <div class="row"><span class="label">Phone:</span><span class="value">${d.phone || 'N/A'}</span></div>
                        <div class="row"><span class="label">Gender:</span><span class="value">${d.gender ? d.gender.toUpperCase() : 'N/A'}</span></div>
                        <div class="row"><span class="label">Age:</span><span class="value">${d.age || 'N/A'}</span></div>
                        <div class="divider"></div>
                        <div class="row"><span class="label">Doctor:</span><span class="value">Dr. ${d.doctor_name || 'N/A'}</span></div>
                        <div class="row"><span class="label">Department:</span><span class="value">${d.department || 'General'}</span></div>
                        <div class="row"><span class="label">Service:</span><span class="value">${d.service_name || 'Consultation'}</span></div>
                        <div class="row"><span class="label">Session:</span><span class="value">${d.session_type ? d.session_type.toUpperCase() : 'N/A'}</span></div>
                        <div class="row"><span class="label">Time:</span><span class="value">${d.start_time || 'N/A'} - ${d.end_time || 'N/A'}</span></div>
                        <div class="divider"></div>
                        <div class="serial-number"># ${d.serial_number || 'N/A'}</div>
                        <div class="divider"></div>
                        <div class="row"><span class="label">Amount:</span><span class="value amount-total">${formatAmount(d.amount || 0)}</span></div>
                        <div class="row"><span class="label">Payment Status:</span><span class="value">${d.payment_status ? d.payment_status.toUpperCase() : 'PENDING'}</span></div>
                        <div class="row"><span class="label">Appointment Status:</span><span class="value">${d.appointment_status ? d.appointment_status.toUpperCase() : 'SCHEDULED'}</span></div>
                        <div class="footer">
                            <div class="thankyou">Thank You</div>
                            <div>This is a computer generated receipt</div>
                            <div style="font-size:7px; margin-top:2px;">Please bring this slip on your visit</div>
                        </div>
                    </div>
                    `;
                    $('#receiptContent').html(html);
                    currentReceiptHtml = html;
                    $('#receiptModal').modal('show');
                } else {
                    showAlert(response.message || 'Error loading booking slip', 'error');
                }
            },
            error: function(xhr) {
                console.error('AJAX Error:', xhr);
                showAlert('Error loading booking slip. Please try again.', 'error');
            }
        });
    }

    function printReceipt() {
        const content = $('#receiptContent').html();
        if (!content || content.trim() === '') {
            showAlert('No slip to print', 'error');
            return;
        }
        
        const win = window.open('', '_blank', 'width=320,height=600');
        if (!win) {
            showAlert('Please allow popups to print', 'warning');
            return;
        }
        
        win.document.write(`<html><head><title>Booking Slip</title>
            <style>
                body { margin: 0; padding: 0; background: white; }
                .thermal-receipt { 
                    font-family: 'Courier New', monospace; 
                    width: 280px; 
                    margin: 0 auto; 
                    padding: 10px 12px; 
                    font-size: 10px; 
                    line-height: 1.4; 
                    background: white; 
                    color: #000;
                }
                .thermal-receipt .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 6px; }
                .thermal-receipt .header .hospital-name { font-size: 14px; font-weight: 700; letter-spacing: 1px; }
                .thermal-receipt .header .address { font-size: 8px; color: #444; }
                .thermal-receipt .header .contact { font-size: 8px; color: #444; }
                .thermal-receipt .header .web { font-size: 8px; color: #444; }
                .thermal-receipt .receipt-title { text-align: center; font-weight: 700; font-size: 12px; margin: 4px 0; letter-spacing: 2px; }
                .thermal-receipt .divider { border-top: 1px dashed #000; margin: 4px 0; }
                .thermal-receipt .row { display: flex; justify-content: space-between; padding: 1px 0; }
                .thermal-receipt .row .label { font-weight: 600; }
                .thermal-receipt .row .value { text-align: right; }
                .thermal-receipt .row .value-strong { font-weight: 700; text-align: right; }
                .thermal-receipt .serial-number { text-align: center; font-size: 22px; font-weight: 900; color: #10b981; padding: 4px 0; letter-spacing: 3px; }
                .thermal-receipt .footer { text-align: center; border-top: 1px dashed #000; padding-top: 6px; margin-top: 6px; font-size: 8px; color: #666; }
                .thermal-receipt .footer .thankyou { font-size: 10px; font-weight: 700; color: #000; }
                .thermal-receipt .amount-total { font-size: 13px; font-weight: 700; color: #10b981; }
                @media print { body { margin: 0; padding: 0; } .thermal-receipt { padding: 8px 10px; } }
            </style>
        </head><body>${content}
        <script>
            setTimeout(function() { window.print(); }, 500);
            window.onafterprint = function() { window.close(); };
        <\/script></body></html>`);
        win.document.close();
    }

    function printPaymentSlip() {
        const content = $('#paymentSlipContent').html();
        if (!content || content.trim() === '') {
            showAlert('No slip to print', 'error');
            return;
        }
        
        const win = window.open('', '_blank', 'width=320,height=500');
        if (!win) {
            showAlert('Please allow popups to print', 'warning');
            return;
        }
        
        win.document.write(`<html><head><title>Payment Receipt</title>
            <style>
                body { margin: 0; padding: 0; background: white; }
                .thermal-receipt { 
                    font-family: 'Courier New', monospace; 
                    width: 280px; 
                    margin: 0 auto; 
                    padding: 10px 12px; 
                    font-size: 10px; 
                    line-height: 1.4; 
                    background: white; 
                    color: #000;
                }
                .thermal-receipt .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 6px; }
                .thermal-receipt .header .hospital-name { font-size: 14px; font-weight: 700; letter-spacing: 1px; }
                .thermal-receipt .header .address { font-size: 8px; color: #444; }
                .thermal-receipt .header .contact { font-size: 8px; color: #444; }
                .thermal-receipt .header .web { font-size: 8px; color: #444; }
                .thermal-receipt .receipt-title { text-align: center; font-weight: 700; font-size: 12px; margin: 4px 0; letter-spacing: 2px; }
                .thermal-receipt .divider { border-top: 1px dashed #000; margin: 4px 0; }
                .thermal-receipt .row { display: flex; justify-content: space-between; padding: 1px 0; }
                .thermal-receipt .row .label { font-weight: 600; }
                .thermal-receipt .row .value { text-align: right; }
                .thermal-receipt .row .value-strong { font-weight: 700; text-align: right; }
                .thermal-receipt .amount-total { font-size: 13px; font-weight: 700; color: #10b981; }
                .thermal-receipt .footer { text-align: center; border-top: 1px dashed #000; padding-top: 6px; margin-top: 6px; font-size: 8px; color: #666; }
                .thermal-receipt .footer .thankyou { font-size: 10px; font-weight: 700; color: #000; }
                @media print { body { margin: 0; padding: 0; } .thermal-receipt { padding: 8px 10px; } }
            </style>
        </head><body>${content}
        <script>
            setTimeout(function() { window.print(); }, 500);
            window.onafterprint = function() { window.close(); };
        <\/script></body></html>`);
        win.document.close();
    }

    // ========== PRINT SERIAL SLIP - Clean design ==========
    function printSerialSlip(appointmentId) {
        window.open(BASE_URL + '/api/print-serial?appointment_id=' + appointmentId, '_blank');
    }

    function openCancelModal(appointmentId) {
        $.ajax({
            url: BASE_URL + '/api/check-appointment-payments',
            method: 'GET',
            data: { appointment_id: appointmentId },
            dataType: 'json',
            success: function(response) {
                $('#cancel_appointment_id').val(appointmentId);
                if(response.has_payment) {
                    $('#payment_refund_info').show();
                } else {
                    $('#payment_refund_info').hide();
                }
                $('#cancelModal').modal('show');
            }
        });
    }

    $('#confirm_cancel').click(function() {
        const appointmentId = $('#cancel_appointment_id').val();
        const reason = $('#cancel_reason').val();
        
        $.ajax({
            url: BASE_URL + '/api/cancel-appointment',
            method: 'POST',
            data: { appointment_id: appointmentId, cancel_reason: reason },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    showAlert(response.message, 'success');
                    $('#cancelModal').modal('hide');
                    $('#cancel_reason').val('');
                    loadAppointments();
                } else {
                    showAlert(response.message, 'error');
                }
            }
        });
    });

    $('#refreshPage').click(function() { loadAppointments(); showAlert('Refreshed', 'info'); });
    $('#apply_filters').click(function() { loadAppointments(); });
    $('#reset_filters').click(function() {
        $('#filter_doctor').val(''); 
        $('#filter_date').val(new Date().toISOString().split('T')[0]);
        $('#filter_shift').val(''); 
        $('#filter_status').val('');
        loadAppointments();
    });

    // Make functions global for onclick
    window.viewPatientDetails = viewPatientDetails;
    window.updateStatus = updateStatus;
    window.openPaymentModal = openPaymentModal;
    window.viewReceipt = viewReceipt;
    window.printSerialSlip = printSerialSlip;
    window.openCancelModal = openCancelModal;
    window.printReceipt = printReceipt;
    window.printPaymentSlip = printPaymentSlip;
    window.adjustDiscount = adjustDiscount;
    window.adjustDiscountAmount = adjustDiscountAmount;
    window.updateDiscountCalculation = updateDiscountCalculation;

    // Auto refresh every 30 seconds
    setInterval(loadAppointments, 30000);
    
    // Initial load
    loadAppointments();
    </script>
</body>
</html>