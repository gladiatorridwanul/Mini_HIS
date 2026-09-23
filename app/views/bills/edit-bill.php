<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bill - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body { 
            background: #f4f6f9; 
            font-family: 'Cambria', 'Times New Roman', serif; 
        }
        .container-fluid { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .card { border: none; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); background: white; margin-bottom: 20px; }
        .card-header { background: transparent; border-bottom: 1px solid #edf2f7; padding: 12px 18px; font-weight: 600; }
        .card-body { padding: 16px 18px; }
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #edf2f7;
            position: sticky;
            top: 20px;
        }
        .search-wrapper { position: relative; }
        .search-results {
            position: absolute;
            z-index: 1000;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            max-height: 260px;
            overflow-y: auto;
            width: 100%;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        .search-result-item {
            padding: 8px 14px;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
        }
        .search-result-item:hover { background: #f0fdf4; }
        .search-result-item .result-name { font-weight: 600; }
        .search-result-item .result-meta { font-size: 0.75rem; color: #6c757d; }
        .search-result-item .result-price { font-weight: 700; color: #10b981; }
        .item-row {
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 12px;
            border: 1px solid #e9edf4;
        }
        .item-row .row > div { display: flex; flex-direction: column; justify-content: center; }
        .item-type-badge {
            font-size: 9px;
            padding: 2px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 2px;
        }
        .badge-medicine { background: #dbeafe; color: #1e40af; }
        .badge-lab { background: #fce7f3; color: #9d174d; }
        .badge-service { background: #dcfce7; color: #166534; }
        .badge-other { background: #fef3c7; color: #92400e; }
        .badge-accessory { background: #fef9c3; color: #854d0e; }
        .badge-consultation { background: #dbeafe; color: #1e40af; }
        .badge-pharmacy { background: #d1fae5; color: #065f46; }
        .badge-lab_test { background: #f3e8ff; color: #6b21a5; }
        .badge-procedure { background: #fef3c7; color: #92400e; }
        .form-control-sm, .form-select-sm { 
            font-size: 0.875rem; 
            padding: 0.35rem 0.7rem; 
            font-family: 'Cambria', 'Times New Roman', serif;
        }
        .tax-input-group { display: flex; align-items: center; gap: 6px; }
        .tax-input-group input { width: 72px; text-align: center; }
        .selected-item-info {
            font-size: 0.8rem;
            color: #10b981;
            margin-top: 2px;
            display: none;
        }
        .selected-item-info.show { display: block; }
        .edit-mode-badge {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .btn-sm { padding: 0.25rem 0.6rem; font-size: 0.8rem; }
        .btn-danger { background: #dc2626; border: none; }
        .btn-danger:hover { background: #b91c1c; }
        .currency-symbol { font-family: 'Cambria', 'Times New Roman', serif; }
        .summary-value { font-weight: 600; }
        .summary-total { font-size: 1.2rem; font-weight: 700; color: #10b981; }
        
        @media (max-width: 768px) {
            .summary-card { position: relative; top: 0; }
            .item-row .row > div { margin-bottom: 6px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-edit text-warning me-2"></i>Edit Bill</h4>
            <p class="text-muted small">Update bill details, add or remove items</p>
        </div>
        <div>
            <span class="edit-mode-badge me-2"><i class="fas fa-pen me-1"></i>Edit Mode</span>
            <a href="<?php echo BASE_URL; ?>/bills" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Bills
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- ========== PATIENT CARD ========== -->
            <div class="card">
                <div class="card-header"><i class="fas fa-user me-2"></i>Patient Information</div>
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Search Patient *</label>
                            <div class="search-wrapper">
                                <input type="text" id="patientSearch" class="form-control form-control-sm" 
                                       placeholder="Type name, code, or phone..." autocomplete="off"
                                       value="<?php echo isset($bill) ? htmlspecialchars($bill['patient_name']) : ''; ?>">
                                <input type="hidden" id="patient_id" value="<?php echo isset($bill) ? $bill['patient_id'] : 0; ?>">
                                <div id="patientResults" class="search-results"></div>
                            </div>
                            <div id="patientSelectedInfo" class="selected-item-info <?php echo isset($bill) && $bill['patient_id'] ? 'show' : ''; ?>">
                                Selected: <?php echo isset($bill) ? htmlspecialchars($bill['patient_name']) . ' (' . htmlspecialchars($bill['patient_code']) . ')' : ''; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Bill Date</label>
                            <input type="date" id="bill_date" class="form-control form-control-sm" 
                                   value="<?php echo isset($bill) ? $bill['bill_date'] : date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Payment Method</label>
                            <select id="payment_method" class="form-select form-select-sm">
                                <option value="cash" <?php echo (isset($bill) && $bill['payment_method'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                                <option value="card" <?php echo (isset($bill) && $bill['payment_method'] == 'card') ? 'selected' : ''; ?>>Card</option>
                                <option value="mobile_banking" <?php echo (isset($bill) && $bill['payment_method'] == 'mobile_banking') ? 'selected' : ''; ?>>Mobile Banking</option>
                                <option value="bank_transfer" <?php echo (isset($bill) && $bill['payment_method'] == 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- ========== HIDDEN FIELDS FOR DOCTOR/SERVICE ========== -->
                    <input type="hidden" name="doctor_id" id="doctor_id" value="<?php echo isset($bill) ? $bill['doctor_id'] ?? 0 : 0; ?>">
                    <input type="hidden" name="service_id" id="service_id" value="<?php echo isset($bill) ? $bill['service_id'] ?? 0 : 0; ?>">
                    <input type="hidden" name="service_name" id="service_name" value="<?php echo isset($bill) ? htmlspecialchars($bill['service_name'] ?? '') : ''; ?>">
                    
                    <div class="row g-2 align-items-end mt-2">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Bill Number</label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="<?php echo isset($bill) ? $bill['bill_number'] : ''; ?>" readonly disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Bill Type</label>
                            <select id="bill_type" class="form-select form-select-sm">
                                <option value="pharmacy" <?php echo (isset($bill) && $bill['bill_type'] == 'pharmacy') ? 'selected' : ''; ?>>Pharmacy</option>
                                <option value="consultation" <?php echo (isset($bill) && $bill['bill_type'] == 'consultation') ? 'selected' : ''; ?>>Consultation</option>
                                <option value="lab_test" <?php echo (isset($bill) && $bill['bill_type'] == 'lab_test') ? 'selected' : ''; ?>>Lab Test</option>
                                <option value="service" <?php echo (isset($bill) && $bill['bill_type'] == 'service') ? 'selected' : ''; ?>>Service</option>
                                <option value="other" <?php echo (isset($bill) && $bill['bill_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Status</label>
                            <input type="text" class="form-control form-control-sm" 
                                   value="<?php echo isset($bill) ? ucfirst($bill['payment_status']) : 'Pending'; ?>" readonly disabled>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== ADD ITEMS CARD ========== -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cart-plus me-2"></i>Add Items
                    <span class="badge bg-info ms-2" id="itemCountBadge">0 items</span>
                </div>
                <div class="card-body">
                    <div class="row g-2 mb-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Item Type</label>
                            <select id="itemType" class="form-select form-select-sm" onchange="onItemTypeChange()">
                                <option value="">-- Select --</option>
                                <option value="medicine">💊 Medicine</option>
                                <option value="lab_test">🔬 Lab Test</option>
                                <option value="service">🩺 Doctor Service</option>
                                <option value="other">📦 Other</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <div id="doctorSelectWrapper" style="display:none;">
                                <div class="search-wrapper">
                                    <input type="text" id="doctorSearchInput" class="form-control form-control-sm" 
                                           placeholder="Search doctor..." autocomplete="off" oninput="filterDoctors(this.value)">
                                    <input type="hidden" id="doctor_id">
                                    <div id="doctorResults" class="search-results"></div>
                                </div>
                                <div id="doctorSelectedInfo" class="selected-item-info"></div>
                            </div>
                            <div id="serviceSelectWrapper" style="display:none;">
                                <div class="search-wrapper">
                                    <input type="text" id="serviceSearchInput" class="form-control form-control-sm" 
                                           placeholder="Search service..." autocomplete="off" oninput="filterServices(this.value)" disabled>
                                    <input type="hidden" id="service_id">
                                    <div id="serviceResults" class="search-results"></div>
                                </div>
                                <div id="serviceSelectedInfo" class="selected-item-info"></div>
                            </div>
                            <div id="medicineSelectWrapper" style="display:none;">
                                <div class="search-wrapper">
                                    <input type="text" id="medicineSearchInput" class="form-control form-control-sm" 
                                           placeholder="Search medicine..." autocomplete="off" oninput="filterMedicines(this.value)">
                                    <input type="hidden" id="medicine_id">
                                    <div id="medicineResults" class="search-results"></div>
                                </div>
                                <div id="medicineSelectedInfo" class="selected-item-info"></div>
                            </div>
                            <div id="labSelectWrapper" style="display:none;">
                                <div class="search-wrapper">
                                    <input type="text" id="labSearchInput" class="form-control form-control-sm" 
                                           placeholder="Search lab test..." autocomplete="off" oninput="filterLabTests(this.value)">
                                    <input type="hidden" id="lab_id">
                                    <div id="labResults" class="search-results"></div>
                                </div>
                                <div id="labSelectedInfo" class="selected-item-info"></div>
                            </div>
                            <div id="otherSelectWrapper" style="display:none;">
                                <select id="otherSelect" class="form-select form-select-sm">
                                    <option value="">-- Select Other --</option>
                                    <option value="custom" data-price="0" data-name="Custom Item">Custom Item - Set Price</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-sm w-100" onclick="addItem()" id="addItemBtn">
                                <i class="fas fa-plus"></i> Add
                            </button>
                        </div>
                    </div>
                    <hr>
                    <!-- Items will be rendered by JavaScript -->
                    <div id="itemsContainer">
                        <div class="text-center py-5 text-muted">Loading items...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== SUMMARY (RIGHT) ========== -->
        <div class="col-lg-4">
            <div class="summary-card">
                <h6 class="mb-3"><i class="fas fa-receipt text-success me-2"></i>Bill Summary</h6>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Discount (%)</label>
                    <input type="number" id="discount_percent" class="form-control form-control-sm" 
                           value="<?php echo isset($bill) ? $bill['discount_percentage'] ?? 0 : 0; ?>" step="0.5" min="0" max="100" oninput="calculateTotal()">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tax (%)</label>
                    <div class="tax-input-group">
                        <input type="number" id="tax_percent" class="form-control form-control-sm" 
                               value="<?php echo isset($bill) ? $bill['tax_percentage'] ?? 0 : 0; ?>" step="0.5" min="0" max="100" oninput="calculateTotal()">
                        <small>%</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Notes</label>
                    <textarea id="notes" class="form-control form-control-sm" rows="2" placeholder="Additional notes..."><?php echo isset($bill) ? htmlspecialchars($bill['notes'] ?? '') : ''; ?></textarea>
                </div>
                <hr>
                
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Subtotal:</span>
                    <strong id="subtotal" class="summary-value">৳ 0.00</strong>
                </div>
                
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Discount (<span id="discount_percent_display">0</span>%):</span>
                    <strong id="discount_amount" class="summary-value text-danger">- ৳ 0.00</strong>
                </div>
                
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Tax (<span id="tax_percent_display">0</span>%):</span>
                    <strong id="tax_amount" class="summary-value">৳ 0.00</strong>
                </div>
                <hr>
                
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Total Amount:</span>
                    <strong class="summary-total" id="total_amount">৳ 0.00</strong>
                </div>
                
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Paid:</span>
                    <span id="paid_amount" class="summary-value">৳ <?php echo isset($bill) ? number_format($bill['paid_amount'] ?? 0, 2) : '0.00'; ?></span>
                </div>
                
                <div class="d-flex justify-content-between mb-3 small">
                    <span>Balance:</span>
                    <span id="balance_amount" class="summary-value text-danger fw-bold">৳ <?php echo isset($bill) ? number_format($bill['balance_amount'] ?? 0, 2) : '0.00'; ?></span>
                </div>
                <hr>
                
                <button class="btn btn-warning btn-sm w-100" onclick="updateBill()" id="updateBillBtn">
                    <i class="fas fa-save me-2"></i>Update Bill
                </button>
                <button class="btn btn-danger btn-sm w-100 mt-2" onclick="deleteBill()" id="deleteBillBtn">
                    <i class="fas fa-trash me-2"></i>Delete Bill
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT - ALL FUNCTIONS DEFINED IN GLOBAL SCOPE -->
<!-- ============================================================ -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ============================================================
// GLOBAL VARIABLES
// ============================================================
var BASE_URL = '<?php echo BASE_URL; ?>';
var BILL_ID = <?php echo isset($bill) ? $bill['id'] : 0; ?>;
var items = [];
var itemCounter = 0;
var selectedPatientName = '<?php echo isset($bill) ? addslashes($bill['patient_name'] ?? '') : ''; ?>';
var selectedPatientId = <?php echo isset($bill) ? $bill['patient_id'] : 0; ?>;
var originalPaidAmount = <?php echo isset($bill) ? $bill['paid_amount'] ?? 0 : 0; ?>;
var originalBalanceAmount = <?php echo isset($bill) ? $bill['balance_amount'] ?? 0 : 0; ?>;

// Pre-loaded data from PHP
var allMedicines = <?php echo json_encode($medicinesList ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
var allLabTests = <?php echo json_encode($labTestsList ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
var allDoctors = <?php echo json_encode($doctorsData ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
var SERVICES_DATA = <?php 
$servicesJson = [];
if(isset($servicesData)) {
    foreach($servicesData as $doctorId => $services) {
        $servicesJson[$doctorId] = $services;
    }
}
echo json_encode($servicesJson, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); 
?>;

// ============================================================
// INITIALIZE ITEMS FROM PHP - WITH DOCTOR_ID
// ============================================================
<?php 
$phpItemCounter = 0;
if(isset($items) && count($items) > 0): 
    foreach($items as $item): 
        $phpItemCounter++;
        $itemId = isset($item['item_id']) && $item['item_id'] ? $item['item_id'] : 'null';
        $billItemId = isset($item['id']) ? $item['id'] : 'null';
        // Check if doctor_id exists in the item
        $itemDoctorId = isset($item['doctor_id']) && $item['doctor_id'] ? $item['doctor_id'] : 'null';
        $itemType = addslashes($item['item_type'] ?? 'other');
        $description = addslashes($item['description'] ?? 'Item');
        $quantity = $item['quantity'] ?? 1;
        $unitPrice = $item['unit_price'] ?? 0;
        $discountPercentage = $item['discount_percentage'] ?? 0;
        $discountAmount = $item['discount_amount'] ?? 0;
        $taxPercentage = $item['tax_percentage'] ?? 0;
        $taxAmount = $item['tax_amount'] ?? 0;
        $totalAmount = $item['total_amount'] ?? 0;
        $isNew = 'false';
        $isLabTest = isset($item['is_lab_test']) && $item['is_lab_test'] ? 'true' : 'false';
        $labTestId = isset($item['lab_test_id']) && $item['lab_test_id'] ? $item['lab_test_id'] : 'null';
        $parentLabItemId = isset($item['parent_lab_item_id']) && $item['parent_lab_item_id'] ? $item['parent_lab_item_id'] : 'null';
    ?>
        items.push({
            uid: <?php echo $phpItemCounter; ?>,
            item_id: <?php echo $itemId; ?>,
            bill_item_id: <?php echo $billItemId; ?>,
            doctor_id: <?php echo $itemDoctorId; ?>,
            item_type: '<?php echo $itemType; ?>',
            description: '<?php echo $description; ?>',
            quantity: <?php echo $quantity; ?>,
            unit_price: <?php echo $unitPrice; ?>,
            discount_percentage: <?php echo $discountPercentage; ?>,
            discount_amount: <?php echo $discountAmount; ?>,
            tax_percentage: <?php echo $taxPercentage; ?>,
            tax_amount: <?php echo $taxAmount; ?>,
            total_amount: <?php echo $totalAmount; ?>,
            is_new: <?php echo $isNew; ?>,
            is_lab_test: <?php echo $isLabTest; ?>,
            lab_test_id: <?php echo $labTestId; ?>,
            parent_lab_item_id: <?php echo $parentLabItemId; ?>
        });
    <?php 
    endforeach; 
endif; 
$jsItemCounter = $phpItemCounter;
?>
// Set the item counter to match the number of items loaded
itemCounter = <?php echo $jsItemCounter; ?>;
// Total items loaded: <?php echo $phpItemCounter; ?>

// ============================================================
// DOCUMENT READY
// ============================================================
$(document).ready(function() {
    // Patient search
    $('#patientSearch').on('input', function() {
        clearTimeout(searchTimeout);
        var search = $(this).val().trim();
        if(search.length < 2) { $('#patientResults').hide(); return; }
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: BASE_URL + '/api/search-patients',
                method: 'GET',
                data: { search: search },
                dataType: 'json',
                success: function(patients) {
                    if(!patients || !patients.length) {
                        $('#patientResults').html('<div class="search-result-item">No patients found</div>').show();
                        return;
                    }
                    var html = '';
                    patients.forEach(function(p) {
                        html += '<div class="search-result-item" onclick="selectPatient(' + p.id + ', \'' + escapeHtml(p.first_name) + ' ' + escapeHtml(p.last_name) + '\', \'' + p.patient_code + '\', \'' + p.phone + '\')">' +
                                    '<div>' +
                                        '<div class="result-name">' + escapeHtml(p.first_name) + ' ' + escapeHtml(p.last_name) + '</div>' +
                                        '<div class="result-meta">' + p.patient_code + ' | ' + p.phone + '</div>' +
                                    '</div>' +
                                '</div>';
                    });
                    $('#patientResults').html(html).show();
                }
            });
        }, 300);
    });
    
    // Click outside to close results
    $(document).click(function(e) {
        if(!$(e.target).closest('#patientSearch, #patientResults').length) {
            $('#patientResults').hide();
        }
        if(!$(e.target).closest('#medicineSearchInput, #medicineResults').length) $('#medicineResults').hide();
        if(!$(e.target).closest('#labSearchInput, #labResults').length) $('#labResults').hide();
        if(!$(e.target).closest('#doctorSearchInput, #doctorResults').length) $('#doctorResults').hide();
        if(!$(e.target).closest('#serviceSearchInput, #serviceResults').length) $('#serviceResults').hide();
    });
    
    // Render items and calculate totals on load
    console.log('Items loaded:', items.length);
    renderItems();
    calculateTotal();
    updateItemCount();
    
    <?php if(isset($bill) && $bill['bill_type']): ?>
        $('#bill_type').val('<?php echo $bill['bill_type']; ?>');
    <?php endif; ?>
});

// ============================================================
// PATIENT SELECTION
// ============================================================
function selectPatient(id, name, code, phone) {
    selectedPatientName = name;
    selectedPatientId = id;
    $('#patientSearch').val(name + ' (' + code + ' - ' + phone + ')');
    $('#patient_id').val(id);
    $('#patientSelectedInfo').text('Selected: ' + name + ' (' + code + ')').addClass('show');
    $('#patientResults').hide();
}

// ============================================================
// ESCAPE HTML
// ============================================================
function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"]/g, function(m) { 
        if(m==='&') return '&amp;'; 
        if(m==='<') return '&lt;'; 
        if(m==='>') return '&gt;'; 
        if(m==='"') return '&quot;'; 
        return m; 
    });
}

// ============================================================
// SEARCH FUNCTIONS
// ============================================================
function filterMedicines(query) {
    var q = query.trim().toLowerCase();
    if(q.length < 2) { $('#medicineResults').hide(); return; }
    var results = allMedicines.filter(function(m) { 
        return m.medicine_name.toLowerCase().indexOf(q) !== -1; 
    });
    if(!results.length) { 
        $('#medicineResults').html('<div class="search-result-item">No medicines found</div>').show(); 
        return; 
    }
    var html = '';
    results.forEach(function(m) {
        html += '<div class="search-result-item" onclick="selectMedicine(' + m.id + ', \'' + escapeHtml(m.medicine_name) + '\', ' + m.selling_price + ')">' +
                    '<div><div class="result-name">' + escapeHtml(m.medicine_name) + '</div><div class="result-meta">Stock: ' + (m.current_stock||'N/A') + '</div></div>' +
                    '<div class="result-price">৳ ' + parseFloat(m.selling_price).toFixed(2) + '</div>' +
                '</div>';
    });
    $('#medicineResults').html(html).show();
}

function selectMedicine(id, name, price) {
    $('#medicineSearchInput').val(name);
    $('#medicine_id').val(id);
    $('#medicineSelectedInfo').text('Selected: ' + name + ' - ৳ ' + parseFloat(price).toFixed(2)).addClass('show');
    $('#medicineResults').hide();
    window.selectedMedicine = { id: id, name: name, price: price };
}

function filterLabTests(query) {
    var q = query.trim().toLowerCase();
    if(q.length < 2) { $('#labResults').hide(); return; }
    var results = allLabTests.filter(function(t) { 
        return t.test_name.toLowerCase().indexOf(q) !== -1; 
    });
    if(!results.length) { 
        $('#labResults').html('<div class="search-result-item">No lab tests found</div>').show(); 
        return; 
    }
    var html = '';
    results.forEach(function(t) {
        html += '<div class="search-result-item" onclick="selectLabTest(' + t.id + ', \'' + escapeHtml(t.test_name) + '\', ' + t.price + ')">' +
                    '<div><div class="result-name">' + escapeHtml(t.test_name) + '</div><div class="result-meta">' + escapeHtml(t.category_name||'General') + '</div></div>' +
                    '<div class="result-price">৳ ' + parseFloat(t.price).toFixed(2) + '</div>' +
                '</div>';
    });
    $('#labResults').html(html).show();
}

function selectLabTest(id, name, price) {
    $('#labSearchInput').val(name);
    $('#lab_id').val(id);
    $('#labSelectedInfo').text('Selected: ' + name + ' - ৳ ' + parseFloat(price).toFixed(2)).addClass('show');
    $('#labResults').hide();
    window.selectedLab = { id: id, name: name, price: price };
}

function filterDoctors(query) {
    var q = query.trim().toLowerCase();
    if(q.length < 2) { $('#doctorResults').hide(); return; }
    var results = allDoctors.filter(function(d) { 
        return d.name.toLowerCase().indexOf(q) !== -1; 
    });
    if(!results.length) { 
        $('#doctorResults').html('<div class="search-result-item">No doctors found</div>').show(); 
        return; 
    }
    var html = '';
    results.forEach(function(d) {
        html += '<div class="search-result-item" onclick="selectDoctor(' + d.id + ', \'' + escapeHtml(d.name) + '\')">' +
                    '<div><div class="result-name">' + escapeHtml(d.name) + '</div></div>' +
                '</div>';
    });
    $('#doctorResults').html(html).show();
}

function selectDoctor(id, name) {
    $('#doctorSearchInput').val(name);
    $('#doctor_id').val(id);
    $('#doctorSelectedInfo').text('Selected: ' + name).addClass('show');
    $('#doctorResults').hide();
    window.selectedDoctor = { id: id, name: name };
    $('#serviceSearchInput').prop('disabled', false);
    $('#serviceSelectWrapper').show();
    $('#serviceSearchInput').val('');
    $('#service_id').val('');
    $('#serviceSelectedInfo').removeClass('show');
    window.selectedService = null;
    filterServices('');
}

function filterServices(query) {
    var doctorId = parseInt($('#doctor_id').val());
    if(!doctorId) { 
        $('#serviceResults').html('<div class="search-result-item">Select a doctor first</div>').show(); 
        return; 
    }
    var services = SERVICES_DATA[doctorId] || [];
    if(!services.length) { 
        $('#serviceResults').html('<div class="search-result-item">No services</div>').show(); 
        return; 
    }
    var q = query.trim().toLowerCase();
    var filtered = services.filter(function(s) { 
        return s.service_name.toLowerCase().indexOf(q) !== -1; 
    });
    if(!filtered.length) { 
        $('#serviceResults').html('<div class="search-result-item">No matching services</div>').show(); 
        return; 
    }
    var html = '';
    filtered.forEach(function(s) {
        html += '<div class="search-result-item" onclick="selectService(' + s.id + ', \'' + escapeHtml(s.service_name) + '\', ' + s.service_price + ')">' +
                    '<div><div class="result-name">' + escapeHtml(s.service_name) + '</div></div>' +
                    '<div class="result-price">৳ ' + parseFloat(s.service_price).toFixed(2) + '</div>' +
                '</div>';
    });
    $('#serviceResults').html(html).show();
}

function selectService(id, name, price) {
    $('#serviceSearchInput').val(name);
    $('#service_id').val(id);
    $('#serviceSelectedInfo').text('Selected: ' + name + ' - ৳ ' + parseFloat(price).toFixed(2)).addClass('show');
    $('#serviceResults').hide();
    window.selectedService = { id: id, name: name, price: price };
}

// ============================================================
// ITEM TYPE CHANGE
// ============================================================
function onItemTypeChange() {
    var type = document.getElementById('itemType').value;
    var wrapperIds = ['doctorSelectWrapper', 'serviceSelectWrapper', 'medicineSelectWrapper', 'labSelectWrapper', 'otherSelectWrapper'];
    for(var i = 0; i < wrapperIds.length; i++) {
        var el = document.getElementById(wrapperIds[i]);
        if(el) el.style.display = 'none';
    }
    window.selectedMedicine = null;
    window.selectedLab = null;
    window.selectedDoctor = null;
    window.selectedService = null;
    document.getElementById('doctorSelectedInfo').className = 'selected-item-info';
    document.getElementById('serviceSelectedInfo').className = 'selected-item-info';
    document.getElementById('medicineSelectedInfo').className = 'selected-item-info';
    document.getElementById('labSelectedInfo').className = 'selected-item-info';
    if(!type) return;

    if(type === 'service') {
        document.getElementById('doctorSelectWrapper').style.display = 'block';
        document.getElementById('serviceSelectWrapper').style.display = 'block';
        document.getElementById('serviceSearchInput').disabled = true;
    } else if(type === 'medicine') {
        document.getElementById('medicineSelectWrapper').style.display = 'block';
    } else if(type === 'lab_test') {
        document.getElementById('labSelectWrapper').style.display = 'block';
    } else if(type === 'other') {
        document.getElementById('otherSelectWrapper').style.display = 'block';
    }
}

// ============================================================
// ADD ITEM - WITH DOCTOR_ID
// ============================================================
function addItem() {
    var type = document.getElementById('itemType').value;
    
    if(type === 'service') {
        if(!window.selectedDoctor || !window.selectedService) {
            Swal.fire('Warning', 'Please select a valid doctor and service', 'warning'); 
            return;
        }
        addItemToList({
            uid: ++itemCounter,
            item_id: window.selectedService.id,
            doctor_id: window.selectedDoctor.id,
            doctor_name: window.selectedDoctor.name,
            item_type: type,
            description: window.selectedService.name + ' (Dr. ' + window.selectedDoctor.name + ')',
            quantity: 1,
            unit_price: window.selectedService.price,
            discount_percentage: 0,
            discount_amount: 0,
            tax_percentage: parseFloat(document.getElementById('tax_percent').value) || 0,
            tax_amount: 0,
            total_amount: window.selectedService.price,
            is_new: true
        });
    } else if(type === 'medicine') {
        if(!window.selectedMedicine) {
            Swal.fire('Warning', 'Please select a valid medicine', 'warning'); 
            return;
        }
        addItemToList({
            uid: ++itemCounter,
            item_id: window.selectedMedicine.id,
            item_type: type,
            description: window.selectedMedicine.name,
            quantity: 1,
            unit_price: window.selectedMedicine.price,
            discount_percentage: 0,
            discount_amount: 0,
            tax_percentage: parseFloat(document.getElementById('tax_percent').value) || 0,
            tax_amount: 0,
            total_amount: window.selectedMedicine.price,
            is_new: true
        });
    } else if(type === 'lab_test') {
        if(!window.selectedLab) {
            Swal.fire('Warning', 'Please select a valid lab test', 'warning'); 
            return;
        }
        var lab = window.selectedLab;
        var mainItemId = ++itemCounter;
        addItemToList({
            uid: mainItemId,
            item_id: lab.id,
            item_type: type,
            description: lab.name,
            quantity: 1,
            unit_price: lab.price,
            discount_percentage: 0,
            discount_amount: 0,
            tax_percentage: parseFloat(document.getElementById('tax_percent').value) || 0,
            tax_amount: 0,
            total_amount: lab.price,
            is_lab_test: true,
            lab_test_id: lab.id,
            is_new: true
        });
        fetchLabTestAccessories(lab.id, mainItemId);
    } else if(type === 'other') {
        var selectedOption = document.getElementById('otherSelect');
        if(selectedOption.value === 'custom') {
            Swal.fire({
                title: 'Custom Item',
                html: '<input id="customName" class="swal2-input" placeholder="Item Name">' +
                       '<input id="customPrice" class="swal2-input" type="number" step="0.01" placeholder="Price">',
                preConfirm: function() {
                    var name = document.getElementById('customName').value;
                    var price = parseFloat(document.getElementById('customPrice').value);
                    if(!name || !price) return false;
                    return { name: name, price: price };
                }
            }).then(function(result) {
                if(result.value) {
                    addItemToList({
                        uid: ++itemCounter,
                        item_id: null,
                        item_type: type,
                        description: result.value.name,
                        quantity: 1,
                        unit_price: result.value.price,
                        discount_percentage: 0,
                        discount_amount: 0,
                        tax_percentage: parseFloat(document.getElementById('tax_percent').value) || 0,
                        tax_amount: 0,
                        total_amount: result.value.price,
                        is_new: true
                    });
                }
            });
            return;
        }
    } else {
        Swal.fire('Warning', 'Please select an item type', 'warning');
    }
}

// ============================================================
// FETCH LAB TEST ACCESSORIES
// ============================================================
function fetchLabTestAccessories(testId, parentItemId) {
    $.ajax({
        url: BASE_URL + '/lab/api/test-accessories/get/' + testId,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success && response.accessories) {
                response.accessories.forEach(function(acc) {
                    addItemToList({
                        uid: ++itemCounter,
                        item_id: acc.id,
                        item_type: 'lab_accessory',
                        description: 'Accessory: ' + acc.accessory_name,
                        quantity: acc.quantity_required || 1,
                        unit_price: parseFloat(acc.unit_price) || 0,
                        discount_percentage: 0,
                        discount_amount: 0,
                        tax_percentage: parseFloat(document.getElementById('tax_percent').value) || 0,
                        tax_amount: 0,
                        total_amount: (acc.quantity_required || 1) * (parseFloat(acc.unit_price) || 0),
                        parent_lab_item_id: parentItemId,
                        lab_test_id: testId,
                        is_new: true
                    });
                });
            }
        }
    });
}

// ============================================================
// ADD ITEM TO LIST - WITH DOCTOR_ID CHECK
// ============================================================
function addItemToList(item) {
    // Ensure doctor_id is set (default to null if not provided)
    if (!item.doctor_id) {
        item.doctor_id = null;
    }
    items.push(item);
    renderItems();
    calculateTotal();
    updateItemCount();
}

// ============================================================
// RENDER ITEMS
// ============================================================
function renderItems() {
    if(!items.length) {
        document.getElementById('itemsContainer').innerHTML = '<div class="text-center py-5 text-muted">No items added. Add items to update bill.</div>';
        return;
    }
    var html = '';
    for(var i = 0; i < items.length; i++) {
        var item = items[i];
        var itemTotal = item.quantity * item.unit_price;
        var discPct = item.discount_percentage || 0;
        var discAmt = itemTotal * (discPct / 100);
        var taxAmt = (itemTotal - discAmt) * (item.tax_percentage / 100);
        var total = itemTotal - discAmt + taxAmt;

        var badgeClass = 'badge-other';
        if(item.item_type === 'medicine') badgeClass = 'badge-medicine';
        else if(item.item_type === 'lab_test') badgeClass = 'badge-lab';
        else if(item.item_type === 'service') badgeClass = 'badge-service';
        else if(item.item_type === 'lab_accessory') badgeClass = 'badge-accessory';
        else if(item.item_type === 'consultation') badgeClass = 'badge-consultation';

        // Use the unique ID (uid) for the onclick handler
        var itemUid = item.uid;
        
        html += '<div class="item-row" data-uid="' + itemUid + '">' +
                    '<div class="row g-2 align-items-center">' +
                        '<div class="col-md-4">' +
                            '<span class="item-type-badge ' + badgeClass + '">' + item.item_type.toUpperCase().replace('_', ' ') + '</span>' +
                            '<strong class="d-block small">' + escapeHtml(item.description) + '</strong>' +
                            (item.parent_lab_item_id ? '<small class="text-muted">(accessory)</small>' : '') +
                            (item.bill_item_id ? '<small class="text-muted">(existing)</small>' : '') +
                        '</div>' +
                        '<div class="col-md-2">' +
                            '<label class="form-label small">Qty</label>' +
                            '<input type="number" class="form-control form-control-sm" value="' + item.quantity + '" min="1" onchange="updateItem(' + itemUid + ', \'quantity\', this.value)">' +
                        '</div>' +
                        '<div class="col-md-2">' +
                            '<label class="form-label small">Price</label>' +
                            '<input type="number" class="form-control form-control-sm" value="' + item.unit_price + '" step="0.01" onchange="updateItem(' + itemUid + ', \'unit_price\', this.value)">' +
                        '</div>' +
                        '<div class="col-md-2">' +
                            '<label class="form-label small">Disc %</label>' +
                            '<input type="number" class="form-control form-control-sm" value="' + item.discount_percentage + '" step="0.5" onchange="updateItem(' + itemUid + ', \'discount_percentage\', this.value)">' +
                        '</div>' +
                        '<div class="col-md-1">' +
                            '<div><strong>৳ ' + total.toFixed(2) + '</strong></div>' +
                        '</div>' +
                        '<div class="col-md-1 text-end">' +
                            '<i class="fas fa-trash-alt text-danger" onclick="removeItem(' + itemUid + ')" style="cursor:pointer; font-size: 1.1rem;"></i>' +
                        '</div>' +
                    '</div>' +
                '</div>';
    }
    document.getElementById('itemsContainer').innerHTML = html;
    updateItemCount();
}

// ============================================================
// UPDATE ITEM
// ============================================================
function updateItem(uid, field, value) {
    // Find item by uid
    for(var i = 0; i < items.length; i++) {
        if(items[i].uid === uid) {
            items[i][field] = parseFloat(value) || 0;
            if(field === 'quantity' && value < 1) items[i].quantity = 1;
            break;
        }
    }
    calculateTotal();
    renderItems();
}

function updateExistingItem(index, field, value) {
    // This function is called from PHP-rendered items with numeric indices
    if(index >= 0 && index < items.length) {
        items[index][field] = parseFloat(value) || 0;
        if(field === 'quantity' && value < 1) items[index].quantity = 1;
        calculateTotal();
        renderItems();
    }
}

function updateItemCount() {
    document.getElementById('itemCountBadge').textContent = items.length + ' items';
}

// ============================================================
// REMOVE ITEM
// ============================================================
function removeItem(uid) {
    Swal.fire({
        title: 'Remove Item?',
        text: 'This item will be removed from the bill',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel'
    }).then(function(result) {
        if(result.isConfirmed) {
            // Find and remove item by uid
            var indexToRemove = -1;
            var itemToRemove = null;
            for(var i = 0; i < items.length; i++) {
                if(items[i].uid === uid) {
                    indexToRemove = i;
                    itemToRemove = items[i];
                    break;
                }
            }
            
            if(indexToRemove === -1) {
                Swal.fire('Error', 'Item not found', 'error');
                return;
            }
            
            // If it's a lab test, remove its accessories too
            if(itemToRemove && itemToRemove.is_lab_test) {
                var parentId = itemToRemove.uid;
                var accessoryIndices = [];
                for(var j = 0; j < items.length; j++) {
                    if(items[j].parent_lab_item_id === parentId) {
                        accessoryIndices.push(j);
                    }
                }
                accessoryIndices.sort(function(a, b) { return b - a; });
                for(var k = 0; k < accessoryIndices.length; k++) {
                    items.splice(accessoryIndices[k], 1);
                }
                // Find the new index of the parent item
                for(var l = 0; l < items.length; l++) {
                    if(items[l].uid === uid) {
                        indexToRemove = l;
                        break;
                    }
                }
            }
            
            if(indexToRemove !== -1) {
                items.splice(indexToRemove, 1);
                renderItems();
                calculateTotal();
                updateItemCount();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Removed!',
                    text: 'Item has been removed.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        }
    });
}

function removeExistingItem(index) {
    if(index >= 0 && index < items.length) {
        var uid = items[index].uid;
        removeItem(uid);
    } else {
        Swal.fire('Error', 'Item not found at index ' + index, 'error');
    }
}

// ============================================================
// CALCULATE TOTAL
// ============================================================
function calculateTotal() {
    // Calculate subtotal from items
    var subtotal = 0;
    for(var i = 0; i < items.length; i++) {
        var item = items[i];
        var itemTotal = item.quantity * item.unit_price;
        var discPct = item.discount_percentage || 0;
        var discAmt = itemTotal * (discPct / 100);
        subtotal += (itemTotal - discAmt);
    }

    // Apply global discount
    var discPct = parseFloat(document.getElementById('discount_percent').value) || 0;
    var taxPct = parseFloat(document.getElementById('tax_percent').value) || 0;
    var discAmt = subtotal * (discPct / 100);
    var afterDisc = subtotal - discAmt;
    var taxAmt = afterDisc * (taxPct / 100);
    var total = afterDisc + taxAmt;

    // Update display with BDT currency
    document.getElementById('subtotal').textContent = '৳ ' + subtotal.toFixed(2);
    document.getElementById('discount_amount').textContent = '- ৳ ' + discAmt.toFixed(2);
    document.getElementById('discount_percent_display').textContent = discPct;
    document.getElementById('tax_amount').textContent = '৳ ' + taxAmt.toFixed(2);
    document.getElementById('tax_percent_display').textContent = taxPct;
    document.getElementById('total_amount').textContent = '৳ ' + total.toFixed(2);
    
    // Update balance - keep original paid amount
    var paidAmount = originalPaidAmount;
    var balance = total - paidAmount;
    if (balance < 0) balance = 0;
    document.getElementById('paid_amount').textContent = '৳ ' + paidAmount.toFixed(2);
    document.getElementById('balance_amount').textContent = '৳ ' + balance.toFixed(2);
}

// ============================================================
// UPDATE BILL - WITH DOCTOR/SERVICE TRACKING
// ============================================================
function updateBill() {
    var patientId = document.getElementById('patient_id').value;
    var billDate = document.getElementById('bill_date').value;
    var discountPercent = parseFloat(document.getElementById('discount_percent').value) || 0;
    var taxPercent = parseFloat(document.getElementById('tax_percent').value) || 0;
    var notes = document.getElementById('notes').value;
    var paymentMethod = document.getElementById('payment_method').value;
    var billType = document.getElementById('bill_type').value;
    var doctorId = document.getElementById('doctor_id').value || 0;
    var serviceId = document.getElementById('service_id').value || 0;
    var serviceName = document.getElementById('service_name').value || '';

    if(!patientId || patientId == 0) { 
        Swal.fire('Error', 'Please select a patient', 'error'); 
        return; 
    }
    if(!items.length) { 
        Swal.fire('Error', 'Please add at least one item', 'error'); 
        return; 
    }

    // Calculate totals
    var subtotal = 0;
    for(var i = 0; i < items.length; i++) {
        var item = items[i];
        var itemTotal = item.quantity * item.unit_price;
        var discPct = item.discount_percentage || 0;
        var discAmt = itemTotal * (discPct / 100);
        subtotal += (itemTotal - discAmt);
    }
    var discAmt = subtotal * (discountPercent / 100);
    var afterDisc = subtotal - discAmt;
    var taxAmt = afterDisc * (taxPercent / 100);
    var total = afterDisc + taxAmt;

    var itemsData = [];
    var foundDoctorId = 0;
    var foundServiceId = 0;
    var foundServiceName = '';

    for(var j = 0; j < items.length; j++) {
        var it = items[j];
        var itemTotal = it.quantity * it.unit_price;
        var discPct = it.discount_percentage || 0;
        var discAmtItem = itemTotal * (discPct / 100);
        var taxAmtItem = (itemTotal - discAmtItem) * ((it.tax_percentage || 0) / 100);
        
        // Track doctor and service info from items
        if (it.item_type === 'service' || it.item_type === 'consultation') {
            if (it.doctor_id) foundDoctorId = it.doctor_id;
            if (it.item_id) foundServiceId = it.item_id;
            foundServiceName = it.description;
        }
        
        itemsData.push({
            bill_item_id: it.bill_item_id || null,
            item_type: it.item_type,
            item_id: it.item_id || null,
            doctor_id: it.doctor_id || null,
            description: it.description,
            quantity: it.quantity,
            unit_price: it.unit_price,
            discount_percentage: discPct,
            discount_amount: discAmtItem,
            tax_percentage: it.tax_percentage || 0,
            tax_amount: taxAmtItem,
            total_amount: itemTotal - discAmtItem + taxAmtItem
        });
    }

    // Use found values or fallback to existing
    var finalDoctorId = foundDoctorId || doctorId;
    var finalServiceId = foundServiceId || serviceId;
    var finalServiceName = foundServiceName || serviceName;

    var formData = new FormData();
    formData.append('bill_id', BILL_ID);
    formData.append('patient_id', patientId);
    formData.append('bill_date', billDate);
    formData.append('bill_type', billType);
    formData.append('discount_percent', discountPercent);
    formData.append('discount_amount', discAmt);
    formData.append('tax_percent', taxPercent);
    formData.append('tax_amount', taxAmt);
    formData.append('subtotal', subtotal);
    formData.append('total_amount', total);
    formData.append('notes', notes);
    formData.append('payment_method', paymentMethod);
    formData.append('items', JSON.stringify(itemsData));
    formData.append('doctor_id', finalDoctorId);
    formData.append('service_id', finalServiceId);
    formData.append('service_name', finalServiceName);

    document.getElementById('updateBillBtn').disabled = true;
    document.getElementById('updateBillBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';

    $.ajax({
        url: BASE_URL + '/bills/update',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        timeout: 30000,
        success: function(response) {
            document.getElementById('updateBillBtn').disabled = false;
            document.getElementById('updateBillBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Bill';
            
            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                } catch(e) {
                    console.error('Invalid JSON response:', response);
                    Swal.fire('Error', 'Server returned invalid response. Please try again.', 'error');
                    return;
                }
            }
            
            console.log('Update response:', response);
            
            if(response.success) {
                // Show doctor/service update info if changed
                var doctorInfo = '';
                if (response.doctor_id && response.doctor_id > 0) {
                    doctorInfo = '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                                 '<span><strong>Doctor ID:</strong></span>' +
                                 '<span>' + response.doctor_id + '</span>' +
                                 '</div>';
                }
                if (response.service_name) {
                    doctorInfo += '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                                  '<span><strong>Service:</strong></span>' +
                                  '<span>' + response.service_name + '</span>' +
                                  '</div>';
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Bill Updated Successfully!',
                    html: '<div style="text-align: left; font-size: 14px; line-height: 2.0;">' +
                          '<div style="border-bottom: 2px solid #f59e0b; padding-bottom: 10px; margin-bottom: 10px;">' +
                          '<strong style="color: #f59e0b; font-size: 18px;">Bill #:</strong> <span style="font-weight: 700; font-size: 16px;">' + (response.bill_number || 'N/A') + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Patient:</strong></span>' +
                          '<span>' + (response.patient_name || 'N/A') + '</span>' +
                          '</div>' +
                          doctorInfo +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Items:</strong></span>' +
                          '<span>' + (response.item_count || 0) + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0; border-top: 1px dashed #e5e7eb; margin-top: 6px; padding-top: 6px;">' +
                          '<span><strong>Subtotal:</strong></span>' +
                          '<span>৳ ' + parseFloat(response.subtotal || 0).toFixed(2) + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Discount:</strong></span>' +
                          '<span style="color: #ef4444;">- ৳ ' + parseFloat(response.discount_amount || 0).toFixed(2) + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Tax:</strong></span>' +
                          '<span>৳ ' + parseFloat(response.tax_amount || 0).toFixed(2) + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0; border-top: 2px solid #f59e0b; padding-top: 6px; margin-top: 4px;">' +
                          '<span style="font-weight: 700; font-size: 15px;"><strong>Total Amount:</strong></span>' +
                          '<span style="font-weight: 700; color: #f59e0b; font-size: 18px;">৳ ' + parseFloat(response.total_amount || 0).toFixed(2) + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Balance:</strong></span>' +
                          '<span style="color: ' + (parseFloat(response.balance_amount || 0) > 0 ? '#dc2626' : '#10b981') + '; font-weight: 600;">৳ ' + parseFloat(response.balance_amount || 0).toFixed(2) + '</span>' +
                          '</div>' +
                          '</div>',
                    confirmButtonText: 'View Bill',
                    confirmButtonColor: '#f59e0b',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(function(result) {
                    if(result.isConfirmed) {
                        window.location.href = BASE_URL + '/bills/view/' + BILL_ID;
                    } else {
                        window.location.href = BASE_URL + '/bills';
                    }
                });
            } else {
                Swal.fire('Error', response.message || 'Failed to update bill', 'error');
            }
        },
        error: function(xhr, status, error) {
            document.getElementById('updateBillBtn').disabled = false;
            document.getElementById('updateBillBtn').innerHTML = '<i class="fas fa-save me-2"></i>Update Bill';
            console.error('AJAX Error:', status, error);
            console.error('Response Text:', xhr.responseText);
            
            var errorMsg = 'Failed to update bill. Please try again.';
            try {
                var response = JSON.parse(xhr.responseText);
                if(response.message) errorMsg = response.message;
            } catch(e) {
                if (xhr.responseText && xhr.responseText.indexOf('<') !== -1) {
                    errorMsg = 'Server error occurred. Please check server logs.';
                } else {
                    errorMsg = 'Server error: ' + xhr.status + ' ' + xhr.statusText;
                }
            }
            Swal.fire('Error', errorMsg, 'error');
        }
    });
}

// ============================================================
// DELETE BILL
// ============================================================
function deleteBill() {
    if (BILL_ID == 0) {
        Swal.fire('Error', 'Invalid bill ID', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Delete Bill?',
        text: 'This action cannot be undone. All bill items and payments will be deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        confirmButtonColor: '#dc2626',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(function(result) {
        if(result.isConfirmed) {
            document.getElementById('deleteBillBtn').disabled = true;
            document.getElementById('deleteBillBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
            
            var formData = new FormData();
            formData.append('bill_id', BILL_ID);
            
            $.ajax({
                url: BASE_URL + '/bills/delete',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    document.getElementById('deleteBillBtn').disabled = false;
                    document.getElementById('deleteBillBtn').innerHTML = '<i class="fas fa-trash me-2"></i>Delete Bill';
                    
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch(e) {
                            console.error('Invalid JSON response:', response);
                            Swal.fire('Error', 'Server returned invalid response. Please try again.', 'error');
                            return;
                        }
                    }
                    
                    console.log('Delete response:', response);
                    
                    if(response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Bill Deleted!',
                            text: response.message || 'Bill has been deleted successfully.',
                            confirmButtonText: 'OK'
                        }).then(function() {
                            window.location.href = BASE_URL + '/bills';
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to delete bill', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    document.getElementById('deleteBillBtn').disabled = false;
                    document.getElementById('deleteBillBtn').innerHTML = '<i class="fas fa-trash me-2"></i>Delete Bill';
                    console.error('AJAX Error:', status, error);
                    console.error('Response Text:', xhr.responseText);
                    
                    var errorMsg = 'Failed to delete bill. Please try again.';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if(response.message) errorMsg = response.message;
                    } catch(e) {
                        if (xhr.responseText && xhr.responseText.indexOf('<') !== -1) {
                            errorMsg = 'Server error occurred. Please check server logs.';
                        } else {
                            errorMsg = 'Server error: ' + xhr.status + ' ' + xhr.statusText;
                        }
                    }
                    Swal.fire('Error', errorMsg, 'error');
                }
            });
        }
    });
}

// ============================================================
// DISCOUNT AND TAX INPUT LISTENERS
// ============================================================
document.addEventListener('DOMContentLoaded', function() {
    var discountInput = document.getElementById('discount_percent');
    var taxInput = document.getElementById('tax_percent');
    if(discountInput) discountInput.addEventListener('input', calculateTotal);
    if(taxInput) taxInput.addEventListener('input', calculateTotal);
});
</script>
</body>
</html>