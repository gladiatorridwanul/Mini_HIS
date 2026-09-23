<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Point of Sale</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ----- GLOBAL RESET & LAYOUT ----- */
        * { box-sizing: border-box; }
        body { background: #f4f6f9; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .pos-container { display: flex; gap: 20px; flex-wrap: wrap; max-width: 1400px; margin: 0 auto; padding: 10px; }
        .products-section { flex: 2; min-width: 320px; }
        .cart-section { flex: 1.2; min-width: 320px; }

        /* ----- CARDS ----- */
        .card { border: none; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); background: white; }
        .card-header { background: transparent; border-bottom: 1px solid #edf2f7; padding: 12px 18px; font-weight: 600; }
        .card-body { padding: 16px 18px; }

        /* ----- PRODUCT GRID ----- */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            max-height: 480px;
            overflow-y: auto;
            padding: 4px 0;
        }
        .product-card {
            border: 1px solid #e9edf4;
            border-radius: 12px;
            padding: 10px 6px;
            text-align: center;
            cursor: pointer;
            transition: all 0.15s ease;
            background: white;
        }
        .product-card:hover {
            border-color: #10b981;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.15);
        }
        .product-card .item-type-badge {
            font-size: 9px;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 4px;
        }
        .badge-medicine { background: #dbeafe; color: #1e40af; }
        .badge-service { background: #fed7aa; color: #9a3412; }
        .badge-lab { background: #f3e8ff; color: #6b21a5; }
        .badge-accessory { background: #fef3c7; color: #92400e; }

        /* ----- FILTER & TAB BUTTONS ----- */
        .filter-buttons, .bill-type-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
        .filter-btn, .bill-type-tab {
            padding: 4px 14px;
            border-radius: 30px;
            border: 1px solid #e2e8f0;
            background: white;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.15s;
        }
        .filter-btn.active, .bill-type-tab.active {
            background: #10b981;
            color: white;
            border-color: #10b981;
        }
        .filter-btn:hover, .bill-type-tab:hover { border-color: #10b981; }

        /* ----- CART ITEMS ----- */
        .cart-item {
            border-bottom: 1px solid #edf2f7;
            padding: 10px 0;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-item .item-type-badge {
            font-size: 9px;
            padding: 2px 8px;
            border-radius: 20px;
            display: inline-block;
        }
        .cart-scroll { max-height: 380px; overflow-y: auto; }

        /* ----- DISCOUNT & VAT INPUTS ----- */
        .discount-group, .vat-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .discount-group input, .vat-group input {
            width: 72px;
            padding: 4px 6px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 13px;
            text-align: center;
        }
        .discount-group small, .vat-group small { font-size: 12px; color: #6b7280; }

        /* ----- TOAST ----- */
        .toast-msg {
            position: fixed; top: 20px; right: 20px; z-index: 9999;
            padding: 10px 20px; border-radius: 8px; font-size: 13px;
            animation: slideIn 0.3s ease-out; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

        /* ----- RESPONSIVE ----- */
        @media (max-width: 768px) {
            .pos-container { flex-direction: column; }
            .cart-section, .products-section { width: 100%; min-width: unset; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); }
        }

        /* Service list items */
        .service-list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 10px;
            margin-bottom: 4px;
            border: 1px solid #e9edf4;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .service-list-item:hover {
            border-color: #10b981;
            background: #f0fdf4;
        }

        /* Discount input styling */
        .cart-item-discount input {
            width: 65px;
            padding: 2px 4px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 12px;
            text-align: center;
        }
        .cart-item-discount input:focus {
            border-color: #10b981;
            outline: none;
            box-shadow: 0 0 0 2px rgba(16,185,129,0.2);
        }

        .discount-input-wrapper {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .discount-input-wrapper span {
            font-size: 11px;
            color: #6b7280;
        }

        /* ----- SEARCH WRAPPER ----- */
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
        .selected-item-info {
            font-size: 0.8rem;
            color: #10b981;
            margin-top: 2px;
            display: none;
        }
        .selected-item-info.show { display: block; }

        /* ----- REFERRED BY CUSTOM STYLES ----- */
        .referred-by-wrapper {
            display: flex;
            gap: 8px;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        .referred-by-wrapper .search-wrapper {
            flex: 1;
            min-width: 180px;
        }
        .referred-by-wrapper .btn-add-referred {
            white-space: nowrap;
            padding: 4px 12px;
            font-size: 12px;
            border-radius: 6px;
        }
        .referred-type-select {
            min-width: 100px;
        }

        .add-referred-option {
            background: #f0fdf4 !important;
            border-bottom: none !important;
        }
        .add-referred-option:hover {
            background: #dcfce7 !important;
        }
        .add-referred-option .result-name {
            color: #065f46;
        }

        @media (max-width: 768px) {
            .referred-by-wrapper {
                flex-direction: column;
                align-items: stretch;
            }
            .referred-by-wrapper .search-wrapper {
                min-width: unset;
            }
            .referred-type-select {
                min-width: unset;
            }
        }
    </style>
</head>
<body>
<div class="pos-container">
    <!-- ========== PRODUCTS SECTION ========== -->
    <div class="products-section">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-pills me-2"></i>Quick Billing</span>
                <span id="cartTotalItems" class="badge bg-secondary">0 items</span>
            </div>
            <div class="card-body">
                <!-- Search -->
                <div class="input-group input-group-sm mb-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search items...">
                    <button class="btn btn-primary btn-sm" onclick="searchItems()"><i class="fas fa-search"></i></button>
                </div>

                <!-- Bill Type Tabs -->
                <div class="bill-type-tabs">
                    <button class="bill-type-tab active" data-type="all" onclick="switchBillType('all')">
                        All <span class="badge-count" id="countAll">0</span>
                    </button>
                    <button class="bill-type-tab" data-type="medicine" onclick="switchBillType('medicine')">
                        💊 Medicines <span class="badge-count" id="countMedicine">0</span>
                    </button>
                    <button class="bill-type-tab" data-type="service" onclick="switchBillType('service')">
                        🩺 Services <span class="badge-count" id="countService">0</span>
                    </button>
                    <button class="bill-type-tab" data-type="lab" onclick="switchBillType('lab')">
                        🔬 Lab Tests <span class="badge-count" id="countLab">0</span>
                    </button>
                </div>

                <!-- Filter Buttons -->
                <div class="filter-buttons">
                    <button class="filter-btn active" onclick="filterItems('all')">All</button>
                    <button class="filter-btn" onclick="filterItems('medicine')">💊 Medicines</button>
                    <button class="filter-btn" onclick="filterItems('service')">🩺 Doctor Services</button>
                    <button class="filter-btn" onclick="filterItems('lab')">🔬 Lab Tests</button>
                </div>

                <!-- Doctor Service Section -->
                <div id="doctorServiceSection" class="doctor-service-section" style="display:none; margin-bottom:12px; padding:12px; background:#f8fafc; border-radius:10px;">
                    <label class="form-label small fw-bold">Select Doctor</label>
                    <select id="doctorSelect" class="form-select form-select-sm">
                        <option value="">-- Select Doctor --</option>
                    </select>
                    <div id="doctorServicesList" class="mt-2" style="display:none;"></div>
                </div>

                <!-- Product Grid -->
                <div class="products-grid" id="productsGrid">
                    <?php foreach($medicines as $item): ?>
                    <div class="product-card" data-type="medicine" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['medicine_name']); ?>" data-price="<?php echo $item['selling_price']; ?>" onclick="addToCart(this)">
                        <div class="text-primary"><i class="fas fa-capsules fa-2x"></i></div>
                        <div class="fw-bold small"><?php echo htmlspecialchars(substr($item['medicine_name'], 0, 20)); ?></div>
                        <div class="text-success">৳ <?php echo number_format($item['selling_price'], 2); ?></div>
                        <span class="item-type-badge badge-medicine">Medicine</span>
                        <br><small class="text-muted">Stock: <?php echo $item['current_stock']; ?></small>
                    </div>
                    <?php endforeach; ?>

                    <?php foreach($labTests as $item): ?>
                    <div class="product-card" data-type="lab" data-id="<?php echo $item['id']; ?>" data-name="<?php echo htmlspecialchars($item['test_name']); ?>" data-price="<?php echo $item['price']; ?>" onclick="addToCart(this)">
                        <div class="text-purple"><i class="fas fa-microscope fa-2x"></i></div>
                        <div class="fw-bold small"><?php echo htmlspecialchars(substr($item['test_name'], 0, 20)); ?></div>
                        <div class="text-success">৳ <?php echo number_format($item['price'], 2); ?></div>
                        <span class="item-type-badge badge-lab">Lab Test</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== CART SECTION ========== -->
    <div class="cart-section">
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="fas fa-shopping-cart me-2"></i>Shopping Cart <span id="cartCount" class="badge bg-light text-dark ms-2">0</span>
            </div>
            <div class="card-body p-2">

                <!-- Mixed Items Alert -->
                <div id="mixedItemsAlert" class="alert-mixed-items" style="display:none; background:#fef3c7; border:1px solid #f59e0b; border-radius:8px; padding:8px 12px; margin-bottom:10px; font-size:12px; color:#92400e;">
                    <strong>⚠️ Mixed Items Detected!</strong>
                    <span id="mixedItemsMessage">You have items from different categories. Generate bills separately.</span>
                </div>

                <!-- Cart Items -->
                <div id="cartContainer" class="cart-scroll">
                    <div class="text-center text-muted py-4">Cart is empty</div>
                </div>

                <hr class="my-2">

                <!-- Totals -->
                <div class="d-flex justify-content-between mb-1">
                    <small><strong>Subtotal (after item discounts):</strong></small>
                    <small id="subtotal">৳ 0.00</small>
                </div>
                <div class="d-flex justify-content-between mb-1 align-items-center">
                    <small><strong>Overall Discount (%):</strong></small>
                    <div class="discount-group">
                        <input type="number" id="discountInput" value="0" min="0" max="20" oninput="updateTotal()">
                        <span class="text-danger" id="discountWarning" style="display:none; font-size:11px;">Max 20%</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-1 align-items-center">
                    <small><strong>VAT (%):</strong></small>
                    <div class="vat-group">
                        <input type="number" id="vatPercentInput" value="0" min="0" max="100" oninput="updateTotal()">
                        <small>%</small>
                    </div>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <small><strong>VAT Amount:</strong></small>
                    <small id="vat">৳ 0.00</small>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <strong>Total:</strong>
                    <strong id="total" class="text-success">৳ 0.00</strong>
                </div>

                <!-- Patient Search -->
                <div class="mb-2">
                    <input type="text" id="patientSearch" class="form-control form-control-sm" placeholder="Search patient..." autocomplete="off">
                    <input type="hidden" id="patientId">
                    <div id="patientResults" class="mt-1" style="display:none; background:white; border:1px solid #ddd; border-radius:6px; max-height:200px; overflow-y:auto;"></div>
                </div>

                <!-- ========== REFERRED BY SECTION ========== -->
                <div class="mb-2">
                    <label class="form-label fw-bold small">Referred By (Optional)</label>
                    <div class="referred-by-wrapper">
                        <div class="search-wrapper">
                            <input type="text" id="referredBySearch" class="form-control form-control-sm" 
                                   placeholder="Search doctor, staff, or external referrer..." autocomplete="off">
                            <input type="hidden" id="referred_by_id">
                            <div id="referredByResults" class="search-results"></div>
                        </div>
                        <select id="referred_by_type" class="form-select form-select-sm referred-type-select">
                            <option value="">All Types</option>
                            <option value="doctor">Doctor</option>
                            <option value="staff">Staff</option>
                            <option value="external">External</option>
                            <option value="other">Other</option>
                        </select>
                        <button type="button" class="btn btn-primary btn-sm btn-add-referred" onclick="addNewReferred()">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    <div id="referredBySelectedInfo" class="selected-item-info"></div>
                </div>

                <!-- Payment Method -->
                <select id="paymentMethod" class="form-select form-select-sm mb-2">
                    <option value="cash">💵 Cash</option>
                    <option value="card">💳 Card</option>
                    <option value="mobile_banking">📱 Mobile Banking</option>
                </select>

                <!-- Action Buttons -->
                <button class="btn btn-primary btn-sm w-100 mb-1" onclick="generateBill()">
                    <i class="fas fa-receipt me-1"></i>Generate Bill
                </button>
                <button class="btn btn-outline-danger btn-sm w-100" onclick="clearCart()">
                    <i class="fas fa-trash me-1"></i>Clear Cart
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let currentBillType = 'all';
let cartItems = [];
let isGeneratingBill = false;
let referredTimeout = null;

// Cache-busting
const cacheBust = () => '?t=' + Date.now();

// ============================================================
// REFERRED BY FUNCTIONS - GLOBAL SCOPE
// ============================================================
function selectReferredBy(id, name, type, phone, specialization) {
    document.getElementById('referredBySearch').value = name;
    document.getElementById('referred_by_id').value = id;
    let infoText = 'Selected: ' + name;
    if(type) infoText += ' (' + type.charAt(0).toUpperCase() + type.slice(1) + ')';
    if(phone) infoText += ' - ' + phone;
    if(specialization) infoText += ' - ' + specialization;
    document.getElementById('referredBySelectedInfo').textContent = infoText;
    document.getElementById('referredBySelectedInfo').classList.add('show');
    document.getElementById('referredByResults').style.display = 'none';
    showToast('✓ ' + name + ' selected as referrer', 'success');
}

function addNewReferredFromSearch(name) {
    name = name.trim();
    if(!name) {
        Swal.fire('Error', 'Please enter a name', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Add New Referrer',
        html: `
            <input id="newReferredName" class="swal2-input" value="${escapeHtml(name)}" placeholder="Full Name *">
            <select id="newReferredType" class="swal2-input">
                <option value="doctor">Doctor</option>
                <option value="staff">Staff</option>
                <option value="external">External</option>
                <option value="other">Other</option>
            </select>
            <input id="newReferredPhone" class="swal2-input" placeholder="Phone (Optional)">
            <input id="newReferredSpecialization" class="swal2-input" placeholder="Specialization (Optional)">
        `,
        preConfirm: () => {
            const name = document.getElementById('newReferredName').value.trim();
            const type = document.getElementById('newReferredType').value;
            const phone = document.getElementById('newReferredPhone').value.trim();
            const specialization = document.getElementById('newReferredSpecialization').value.trim();
            if(!name) {
                Swal.showValidationMessage('Name is required');
                return false;
            }
            return { name, type, phone, specialization };
        }
    }).then((result) => {
        if(result.isConfirmed && result.value) {
            const data = result.value;
            $.ajax({
                url: BASE_URL + '/api/add-referred-by',
                method: 'POST',
                data: {
                    name: data.name,
                    type: data.type,
                    phone: data.phone || '',
                    specialization: data.specialization || ''
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        showToast('Referrer added successfully!', 'success');
                        selectReferredBy(response.id, data.name, data.type, data.phone, data.specialization);
                    } else {
                        showToast(response.message || 'Failed to add referrer', 'error');
                    }
                },
                error: function() {
                    showToast('Failed to add referrer', 'error');
                }
            });
        }
    });
}

function addNewReferred() {
    Swal.fire({
        title: 'Add New Referrer',
        html: `
            <input id="newReferredName" class="swal2-input" placeholder="Full Name *">
            <select id="newReferredType" class="swal2-input">
                <option value="doctor">Doctor</option>
                <option value="staff">Staff</option>
                <option value="external">External</option>
                <option value="other">Other</option>
            </select>
            <input id="newReferredPhone" class="swal2-input" placeholder="Phone (Optional)">
            <input id="newReferredSpecialization" class="swal2-input" placeholder="Specialization (Optional)">
        `,
        preConfirm: () => {
            const name = document.getElementById('newReferredName').value.trim();
            const type = document.getElementById('newReferredType').value;
            const phone = document.getElementById('newReferredPhone').value.trim();
            const specialization = document.getElementById('newReferredSpecialization').value.trim();
            if(!name) {
                Swal.showValidationMessage('Name is required');
                return false;
            }
            return { name, type, phone, specialization };
        }
    }).then((result) => {
        if(result.isConfirmed && result.value) {
            const data = result.value;
            $.ajax({
                url: BASE_URL + '/api/add-referred-by',
                method: 'POST',
                data: {
                    name: data.name,
                    type: data.type,
                    phone: data.phone || '',
                    specialization: data.specialization || ''
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        showToast('Referrer added successfully!', 'success');
                        selectReferredBy(response.id, data.name, data.type, data.phone, data.specialization);
                    } else {
                        showToast(response.message || 'Failed to add referrer', 'error');
                    }
                },
                error: function() {
                    showToast('Failed to add referrer', 'error');
                }
            });
        }
    });
}

function resetReferredSelection() {
    document.getElementById('referredBySearch').value = '';
    document.getElementById('referred_by_id').value = '';
    document.getElementById('referredBySelectedInfo').classList.remove('show');
    document.getElementById('referredByResults').style.display = 'none';
    document.getElementById('referredByResults').innerHTML = '';
}

$(document).ready(function() {
    clearCartOnLoad();
    resetPatientSelection();
    resetReferredSelection();
    loadCart();
    $('#searchInput').on('keyup', searchItems);
    loadDoctors();
    updateCounts();

    $('#discountInput').on('input', function() {
        let val = parseFloat($(this).val()) || 0;
        if (val > 20) { $(this).val(20); $('#discountWarning').show(); }
        else { $('#discountWarning').hide(); }
        updateTotal();
    });

    // Patient search
    let searchTimeout;
    $('#patientSearch').on('input', function() {
        clearTimeout(searchTimeout);
        let search = $(this).val().trim();
        if (search.length < 2) { $('#patientResults').hide().empty(); return; }
        searchTimeout = setTimeout(() => {
            $.ajax({
                url: BASE_URL + '/api/search-patients' + cacheBust(),
                data: { search: search },
                dataType: 'json',
                cache: false,
                success: function(patients) {
                    if (!patients || !patients.length) {
                        $('#patientResults').html('<div class="alert alert-warning py-1">No patients found</div>').show();
                        return;
                    }
                    let html = '<div class="list-group">';
                    patients.forEach(p => {
                        html += `<a href="#" class="list-group-item list-group-item-action py-1" onclick="selectPatient(${p.id}, '${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}'); return false;">
                                    <strong>${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</strong><br>
                                    <small>${p.patient_code} | ${p.phone}</small>
                                </a>`;
                    });
                    html += '</div>';
                    $('#patientResults').html(html).show();
                }
            });
        }, 300);
    });

    $('#patientSearch').on('blur', function() {
        setTimeout(() => { $('#patientResults').hide().empty(); }, 200);
    });

    // ========== REFERRED BY SEARCH ==========
    $('#referredBySearch').on('input', function() {
        clearTimeout(referredTimeout);
        let search = $(this).val().trim();
        let type = $('#referred_by_type').val();
        
        if(search.length < 1) { 
            $('#referredByResults').hide(); 
            return; 
        }
        
        referredTimeout = setTimeout(() => {
            $.ajax({
                url: BASE_URL + '/api/search-referred-by',
                method: 'GET',
                data: { search: search, type: type },
                dataType: 'json',
                cache: false,
                success: function(response) {
                    if(!response.success || !response.data || !response.data.length) {
                        $('#referredByResults').html(`
                            <div class="search-result-item add-referred-option" onclick="addNewReferredFromSearch('${escapeHtml(search)}')">
                                <div>
                                    <div class="result-name"><i class="fas fa-plus-circle text-success"></i> Add "${escapeHtml(search)}" as new referrer</div>
                                    <div class="result-meta">Click to add this name to the referrers list</div>
                                </div>
                            </div>
                        `).show();
                        return;
                    }
                    let html = '';
                    response.data.forEach(r => {
                        let typeBadge = '';
                        if(r.type === 'doctor') typeBadge = '<span class="badge bg-primary">Doctor</span>';
                        else if(r.type === 'staff') typeBadge = '<span class="badge bg-info">Staff</span>';
                        else if(r.type === 'external') typeBadge = '<span class="badge bg-warning">External</span>';
                        else typeBadge = '<span class="badge bg-secondary">Other</span>';
                        
                        html += `<div class="search-result-item" onclick="selectReferredBy(${r.id}, '${escapeHtml(r.name)}', '${r.type}', '${escapeHtml(r.phone || '')}', '${escapeHtml(r.specialization || '')}')">
                                    <div>
                                        <div class="result-name">${escapeHtml(r.name)}</div>
                                        <div class="result-meta">${typeBadge} ${r.phone ? '| ' + r.phone : ''} ${r.specialization ? '| ' + r.specialization : ''}</div>
                                    </div>
                                </div>`;
                    });
                    html += `
                        <div class="search-result-item add-referred-option" onclick="addNewReferredFromSearch('${escapeHtml(search)}')">
                            <div>
                                <div class="result-name"><i class="fas fa-plus-circle text-success"></i> Add "${escapeHtml(search)}" as new</div>
                                <div class="result-meta">Click to add this name to the referrers list</div>
                            </div>
                        </div>
                    `;
                    $('#referredByResults').html(html).show();
                },
                error: function() {
                    $('#referredByResults').html('<div class="search-result-item">Error loading referrers</div>').show();
                }
            });
        }, 300);
    });

    $('#referred_by_type').on('change', function() {
        let search = $('#referredBySearch').val().trim();
        if(search.length >= 1) {
            $('#referredBySearch').trigger('input');
        } else {
            $('#referredBySearch').val(' ');
            $('#referredBySearch').trigger('input');
            setTimeout(() => {
                if($('#referredBySearch').val() === ' ') {
                    $('#referredBySearch').val('');
                }
            }, 100);
        }
    });

    $(document).click(function(e) {
        if(!$(e.target).closest('#referredBySearch, #referredByResults, #referred_by_type, .btn-add-referred').length) {
            $('#referredByResults').hide();
        }
    });
});

// ---------- Clear cart on load ----------
function clearCartOnLoad() {
    $.post(BASE_URL + '/pharmacy/clear-cart' + cacheBust(), function(res) {
        if (res.success) { cartItems = []; renderCart(); updateCounts(); }
    });
}

function resetPatientSelection() {
    $('#patientSearch').val(''); $('#patientId').val(''); $('#patientResults').hide().empty();
}

// ---------- Load Doctors (AJAX) ----------
function loadDoctors() {
    $.ajax({
        url: BASE_URL + '/api/get-doctors' + cacheBust(),
        method: 'GET',
        dataType: 'json',
        cache: false,
        success: function(response) {
            if(response.success && response.doctors) {
                let html = '<option value="">-- Select Doctor --</option>';
                response.doctors.forEach(d => {
                    html += `<option value="${d.id}" data-name="${escapeHtml(d.name)}">Dr. ${escapeHtml(d.name)}</option>`;
                });
                $('#doctorSelect').html(html);
            }
        }
    });
}

$('#doctorSelect').on('change', function() {
    let doctorId = $(this).val();
    let doctorName = $(this).find('option:selected').data('name') || '';
    if(!doctorId) { $('#doctorServicesList').hide().empty(); return; }
    $.ajax({
        url: BASE_URL + '/api/get-doctor-services' + cacheBust(),
        data: { doctor_id: doctorId },
        dataType: 'json',
        cache: false,
        success: function(response) {
            if(response.services && response.services.length > 0) {
                let html = '<div class="fw-bold small mb-2">Services for Dr. ' + escapeHtml(doctorName) + '</div>';
                response.services.forEach(s => {
                    html += `
                        <div class="service-list-item" onclick="addServiceToCart(${s.id}, '${escapeHtml(s.service_name)}', ${s.service_price}, ${doctorId}, '${escapeHtml(doctorName)}')">
                            <strong>${escapeHtml(s.service_name)}</strong>
                            <span class="text-success fw-bold">৳ ${parseFloat(s.service_price).toFixed(2)}</span>
                        </div>
                    `;
                });
                $('#doctorServicesList').html(html).show();
            } else {
                $('#doctorServicesList').html('<div class="text-center py-2 text-muted">No services available</div>').show();
            }
        }
    });
});

function addServiceToCart(serviceId, serviceName, servicePrice, doctorId, doctorName) {
    $.ajax({
        url: BASE_URL + '/pharmacy/add-to-cart' + cacheBust(),
        type: 'POST',
        data: {
            item_type: 'service',
            item_id: serviceId,
            item_name: serviceName + ' (Dr. ' + doctorName + ')',
            item_price: servicePrice,
            doctor_id: doctorId,
            doctor_name: doctorName,
            quantity: 1
        },
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (response.success) { loadCart(); showToast('✓ ' + serviceName + ' added', 'success'); }
            else showToast(response.message || 'Error', 'error');
        }
    });
}

// ---------- Cart Operations ----------
function switchBillType(type) {
    currentBillType = type;
    $('.bill-type-tab').removeClass('active');
    $(`.bill-type-tab[data-type="${type}"]`).addClass('active');
    updateCounts();
    renderCart();
    checkMixedItems();
}

function updateCounts() {
    $.ajax({
        url: BASE_URL + '/pharmacy/get-cart' + cacheBust(),
        method: 'GET',
        dataType: 'json',
        cache: false,
        success: function(data) {
            if (data.cart) {
                cartItems = data.cart;
                let total = cartItems.length;
                let med = cartItems.filter(i => i.type === 'medicine').length;
                let svc = cartItems.filter(i => i.type === 'service').length;
                let lab = cartItems.filter(i => i.type === 'lab' || i.type === 'lab_accessory').length;
                $('#countAll').text(total);
                $('#countMedicine').text(med);
                $('#countService').text(svc);
                $('#countLab').text(lab);
                $('#cartTotalItems').text(total + ' items');
            }
        }
    });
}

function filterItems(type) {
    $('.filter-btn').removeClass('active');
    $(event.target).addClass('active');
    let grid = $('#productsGrid');
    let docSection = $('#doctorServiceSection');
    if(type === 'all') { grid.show(); docSection.hide(); $('.product-card').show(); }
    else if(type === 'medicine') { grid.show(); docSection.hide(); $('.product-card').hide(); $('.product-card[data-type="medicine"]').show(); }
    else if(type === 'lab') { grid.show(); docSection.hide(); $('.product-card').hide(); $('.product-card[data-type="lab"]').show(); }
    else if(type === 'service') { grid.hide(); docSection.show(); $('#doctorSelect').val(''); $('#doctorServicesList').hide().empty(); }
}

function searchItems() {
    let q = $('#searchInput').val().toLowerCase();
    $('.product-card').each(function() {
        let name = $(this).data('name').toLowerCase();
        $(this).toggle(name.includes(q));
    });
}

function addToCart(element) {
    let type = $(element).data('type');
    let id = $(element).data('id');
    let name = $(element).data('name');
    let price = $(element).data('price');
    $.ajax({
        url: BASE_URL + '/pharmacy/add-to-cart' + cacheBust(),
        type: 'POST',
        data: { item_type: type, item_id: id, item_name: name, item_price: price, quantity: 1 },
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (response.success) { loadCart(); showToast('✓ ' + name + ' added', 'success'); }
            else showToast(response.message || 'Error', 'error');
        }
    });
}

function loadCart() {
    $.ajax({
        url: BASE_URL + '/pharmacy/get-cart' + cacheBust(),
        type: 'GET',
        dataType: 'json',
        cache: false,
        success: function(response) {
            if (response.success && response.cart) { 
                cartItems = response.cart; 
                renderCart(); 
                updateCounts(); 
                checkMixedItems(); 
            }
            else { cartItems = []; renderCart(); }
        }
    });
}

function checkMixedItems() {
    let med = cartItems.filter(i => i.type === 'medicine').length;
    let svc = cartItems.filter(i => i.type === 'service').length;
    let lab = cartItems.filter(i => i.type === 'lab' || i.type === 'lab_accessory').length;
    let types = (med>0?1:0) + (svc>0?1:0) + (lab>0?1:0);
    if(types > 1) {
        let msg = '';
        if(med && svc && lab) msg = 'You have Medicines, Services, and Lab Tests. Please generate each bill separately.';
        else if(med && svc) msg = 'You have Medicines and Services. Please generate each bill separately.';
        else if(med && lab) msg = 'You have Medicines and Lab Tests. Please generate each bill separately.';
        else if(svc && lab) msg = 'You have Services and Lab Tests. Please generate each bill separately.';
        $('#mixedItemsMessage').text(msg);
        $('#mixedItemsAlert').show();
    } else {
        $('#mixedItemsAlert').hide();
    }
}

function renderCart() {
    let filtered = cartItems;
    if(currentBillType !== 'all') {
        if(currentBillType === 'lab') filtered = cartItems.filter(i => i.type === 'lab' || i.type === 'lab_accessory');
        else filtered = cartItems.filter(i => i.type === currentBillType);
    }
    let html = '', subtotal = 0;
    if(!filtered || filtered.length === 0) {
        html = (cartItems.length && currentBillType!=='all') ? `<div class="text-center text-muted py-4">No ${currentBillType} items</div>` : '<div class="text-center text-muted py-4">Cart is empty</div>';
        $('#cartContainer').html(html);
        $('#cartCount').text(cartItems.length);
        $('#subtotal').text('৳ 0.00'); $('#vat').text('৳ 0.00'); $('#total').text('৳ 0.00');
        return;
    }
    filtered.forEach((item, idx) => {
        let actualIndex = cartItems.indexOf(item);
        let qty = parseInt(item.quantity) || 0;
        let price = parseFloat(item.price) || 0;
        let discPct = parseFloat(item.discount_percent) || 0;
        let itemSub = price * qty;
        let discAmt = itemSub * (discPct / 100);
        let afterDisc = itemSub - discAmt;
        subtotal += afterDisc;

        let typeLabel = item.type === 'medicine' ? 'Medicine' : (item.type === 'service' ? 'Service' : (item.type === 'lab' ? 'Lab Test' : 'Accessory'));
        let badgeClass = item.type === 'medicine' ? 'badge-medicine' : (item.type === 'service' ? 'badge-service' : (item.type === 'lab' ? 'badge-lab' : 'badge-accessory'));

        html += `
            <div class="cart-item" data-index="${actualIndex}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <span class="item-type-badge ${badgeClass}">${typeLabel}</span>
                        <strong>${escapeHtml(item.name.substring(0, 35))}</strong>
                        ${item.lab_test_id ? '<small class="text-muted">(accessory)</small>' : ''}
                    </div>
                    <button class="btn btn-sm btn-danger" onclick="removeItem(${actualIndex})"><i class="fas fa-times"></i></button>
                </div>
                ${item.doctor_name ? `<div><small class="text-muted">Doctor: Dr. ${escapeHtml(item.doctor_name)}</small></div>` : ''}
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <small>৳ ${price.toFixed(2)} each</small>
                    <div class="input-group input-group-sm" style="width:90px;">
                        <button class="btn btn-outline-secondary" onclick="updateQuantity(${actualIndex}, -1)">-</button>
                        <input type="text" class="form-control text-center" value="${qty}" readonly style="width:30px; font-size:11px;">
                        <button class="btn btn-outline-secondary" onclick="updateQuantity(${actualIndex}, 1)">+</button>
                    </div>
                    <strong>৳ ${afterDisc.toFixed(2)}</strong>
                </div>
                <div class="cart-item-discount" style="display:flex; gap:6px; margin-top:4px; align-items:center; flex-wrap:wrap;">
                    <span style="font-size:11px; color:#6b7280;">Item Discount:</span>
                    <div class="discount-input-wrapper">
                        <input type="number" class="form-control form-control-sm" style="width:65px;" value="${discPct}" min="0" max="100" 
                               data-index="${actualIndex}" onchange="updateItemDiscount(${actualIndex}, this.value)">
                        <span>%</span>
                    </div>
                    <span style="font-size:11px; color:#6b7280;">(৳ ${discAmt.toFixed(2)})</span>
                    ${discPct > 0 ? `<button class="btn btn-sm btn-outline-secondary" onclick="clearItemDiscount(${actualIndex})" style="padding:0 6px; font-size:10px;"><i class="fas fa-times"></i></button>` : ''}
                </div>
            </div>
        `;
    });
    $('#cartContainer').html(html);
    $('#cartCount').text(cartItems.length);

    let overallDisc = parseFloat($('#discountInput').val()) || 0;
    if(overallDisc > 20) overallDisc = 20;
    let overallAmt = subtotal * (overallDisc / 100);
    let afterOverall = subtotal - overallAmt;
    let vatPct = parseFloat($('#vatPercentInput').val()) || 0;
    let vatAmt = afterOverall * (vatPct / 100);
    let total = afterOverall + vatAmt;

    $('#subtotal').text('৳ ' + subtotal.toFixed(2));
    $('#vat').text('৳ ' + vatAmt.toFixed(2));
    $('#total').text('৳ ' + total.toFixed(2));
    window.currentSubtotal = subtotal;
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) { if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m; });
}

// ---------- Update Quantity ----------
function updateQuantity(index, change) {
    $.ajax({
        url: BASE_URL + '/pharmacy/get-cart' + cacheBust(),
        type: 'GET',
        dataType: 'json',
        cache: false,
        success: function(data) {
            if (data.cart && data.cart[index]) {
                let newQty = data.cart[index].quantity + change;
                if (newQty < 1) newQty = 1;
                $.post(BASE_URL + '/pharmacy/update-cart' + cacheBust(), { index: index, quantity: newQty }, function(res) {
                    if (res.success) loadCart();
                });
            }
        }
    });
}

// ---------- Update Item Discount ----------
function updateItemDiscount(index, value) {
    let disc = parseFloat(value) || 0;
    if(disc < 0) disc = 0;
    if(disc > 100) disc = 100;
    
    $(`input[data-index="${index}"]`).val(disc);
    
    let inputEl = $(`input[data-index="${index}"]`);
    inputEl.css('background', '#fef3c7');
    
    $.ajax({
        url: BASE_URL + '/pharmacy/update-item-discount' + cacheBust(),
        type: 'POST',
        data: { 
            index: index, 
            discount_percent: disc 
        },
        dataType: 'json',
        cache: false,
        success: function(response) {
            inputEl.css('background', '');
            if(response.success) {
                loadCart();
                showToast('✓ Discount updated to ' + disc + '%', 'success');
            } else {
                showToast('Failed to update discount: ' + (response.message || 'Error'), 'error');
                loadCart();
            }
        },
        error: function() {
            inputEl.css('background', '');
            showToast('Error updating discount', 'error');
            loadCart();
        }
    });
}

// ---------- Clear Item Discount ----------
function clearItemDiscount(index) {
    $.ajax({
        url: BASE_URL + '/pharmacy/update-item-discount' + cacheBust(),
        type: 'POST',
        data: { 
            index: index, 
            discount_percent: 0 
        },
        dataType: 'json',
        cache: false,
        success: function(response) {
            if(response.success) {
                loadCart();
                showToast('✓ Discount removed', 'success');
            }
        }
    });
}

// ---------- Remove Item (with accessories) ----------
function removeItem(index) {
    Swal.fire({
        title: 'Remove Item?',
        text: 'This item will be removed from cart',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes'
    }).then((result) => {
        if (result.isConfirmed) {
            $.getJSON(BASE_URL + '/pharmacy/get-cart' + cacheBust(), function(data) {
                if (data.cart && data.cart[index]) {
                    let item = data.cart[index];
                    let isLabTest = (item.type === 'lab');
                    let labTestId = item.id;

                    $.post(BASE_URL + '/pharmacy/remove-from-cart' + cacheBust(), { index: index }, function(res) {
                        if (res.success) {
                            if (isLabTest) {
                                $.getJSON(BASE_URL + '/pharmacy/get-cart' + cacheBust(), function(updated) {
                                    if (updated.cart) {
                                        let accessoryIndices = [];
                                        updated.cart.forEach((item, idx) => {
                                            if (item.type === 'lab_accessory' && item.lab_test_id == labTestId) {
                                                accessoryIndices.push(idx);
                                            }
                                        });
                                        accessoryIndices.sort((a,b) => b - a);
                                        accessoryIndices.forEach(idx => {
                                            $.post(BASE_URL + '/pharmacy/remove-from-cart' + cacheBust(), { index: idx }, function() {});
                                        });
                                        setTimeout(loadCart, 300);
                                    } else {
                                        loadCart();
                                    }
                                });
                            } else {
                                loadCart();
                            }
                        } else {
                            showToast('Error removing item', 'error');
                        }
                    });
                } else {
                    loadCart();
                }
            });
        }
    });
}

function updateTotal() { renderCart(); }

// ---------- Clear Cart ----------
function clearCart() {
    Swal.fire({
        title: 'Clear Cart?', text: 'All items will be removed', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Yes'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post(BASE_URL + '/pharmacy/clear-cart' + cacheBust(), function(res) {
                if (res.success) loadCart();
            });
        }
    });
}

// ---------- Generate Bill ----------
function generateBill() {
    if (isGeneratingBill) return;
    if (!cartItems || cartItems.length === 0) {
        showToast('Cart is empty!', 'error'); return;
    }

    let med = cartItems.filter(i => i.type === 'medicine').length;
    let svc = cartItems.filter(i => i.type === 'service').length;
    let lab = cartItems.filter(i => i.type === 'lab' || i.type === 'lab_accessory').length;
    let types = (med>0?1:0) + (svc>0?1:0) + (lab>0?1:0);
    if(types > 1) {
        Swal.fire({ icon: 'warning', title: 'Mixed Items', html: 'Please generate bills separately using tabs.', confirmButtonText: 'OK' });
        return;
    }

    let billType = 'pharmacy', serviceName = 'Pharmacy Items';
    if(currentBillType === 'medicine' || (currentBillType === 'all' && med > 0)) { billType = 'pharmacy'; serviceName = 'Medicines'; }
    else if(currentBillType === 'service') { billType = 'consultation'; serviceName = 'Doctor Services'; }
    else if(currentBillType === 'lab') { billType = 'lab_test'; serviceName = 'Lab Tests'; }
    else {
        if(med > 0 && svc === 0 && lab === 0) { billType = 'pharmacy'; serviceName = 'Medicines'; }
        else if(svc > 0 && med === 0 && lab === 0) { billType = 'consultation'; serviceName = 'Doctor Services'; }
        else if(lab > 0 && med === 0 && svc === 0) { billType = 'lab_test'; serviceName = 'Lab Tests'; }
    }

    let itemsToBill = cartItems;
    if(currentBillType === 'lab') itemsToBill = cartItems.filter(i => i.type === 'lab' || i.type === 'lab_accessory');
    else if(currentBillType !== 'all') itemsToBill = cartItems.filter(i => i.type === currentBillType);

    if(!itemsToBill || itemsToBill.length === 0) { showToast('No items to bill', 'error'); return; }

    let discount = parseFloat($('#discountInput').val()) || 0;
    if(discount > 20) discount = 20;
    let vat = parseFloat($('#vatPercentInput').val()) || 0;
    let patientId = $('#patientId').val();
    let paymentMethod = $('#paymentMethod').val();
    let referredById = $('#referred_by_id').val();

    let cartWithDiscounts = itemsToBill.map(item => ({
        ...item,
        discount_percent: parseFloat(item.discount_percent) || 0
    }));

    isGeneratingBill = true;
    Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    $.ajax({
        url: BASE_URL + '/pharmacy/process-sale' + cacheBust(),
        type: 'POST',
        data: {
            payment_method: paymentMethod,
            discount: discount,
            vat_percent: vat,
            patient_id: patientId,
            bill_type: billType,
            service_name: serviceName,
            referred_by: referredById || '',
            cart_items: JSON.stringify(cartWithDiscounts)
        },
        dataType: 'json',
        cache: false,
        success: function(response) {
            Swal.close(); isGeneratingBill = false;
            if(response.success) {
                showToast('✅ Bill generated!', 'success');
                window.location.href = BASE_URL + '/bills';
            } else {
                showToast(response.message || 'Error', 'error');
            }
        },
        error: function() {
            Swal.close(); isGeneratingBill = false;
            showToast('Error processing sale', 'error');
        }
    });
}

function showToast(msg, type) {
    let color = type === 'success' ? '#10b981' : '#ef4444';
    let toast = $(`<div class="toast-msg" style="background:${color};color:white;">${msg}</div>`);
    $('body').append(toast);
    setTimeout(() => toast.fadeOut(300, function(){ $(this).remove(); }), 3000);
}

function selectPatient(id, name) {
    $('#patientSearch').val(name);
    $('#patientId').val(id);
    $('#patientResults').hide().empty();
}
</script>
</body>
</html>