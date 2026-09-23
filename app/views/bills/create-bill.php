<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');

$hasMedicines = isset($medicinesList) && count($medicinesList) > 0;
$hasLabTests = isset($labTestsList) && count($labTestsList) > 0;
$hasDoctors = isset($doctorsData) && count($doctorsData) > 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Bill - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ----- GLOBAL ----- */
        * { box-sizing: border-box; }
        body { 
            background: #f4f6f9; 
            font-family: 'Cambria', 'Times New Roman', serif; 
        }
        .container-fluid { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* ----- CARDS ----- */
        .card { border: none; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); background: white; margin-bottom: 20px; }
        .card-header { background: transparent; border-bottom: 1px solid #edf2f7; padding: 12px 18px; font-weight: 600; }
        .card-body { padding: 16px 18px; }

        /* ----- SUMMARY CARD (sticky) ----- */
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid #edf2f7;
            position: sticky;
            top: 20px;
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
        .search-result-item .result-price { font-weight: 700; color: #10b981; }

        /* ----- ITEM ROW ----- */
        .item-row {
            background: #f8fafc;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 12px;
            border: 1px solid #e9edf4;
        }
        .item-row .row > div { display: flex; flex-direction: column; justify-content: center; }

        /* ----- BADGES ----- */
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

        /* ----- INPUTS ----- */
        .form-control-sm, .form-select-sm { 
            font-size: 0.875rem; 
            padding: 0.35rem 0.7rem; 
            font-family: 'Cambria', 'Times New Roman', serif;
        }
        .tax-input-group { display: flex; align-items: center; gap: 6px; }
        .tax-input-group input { width: 72px; text-align: center; }

        /* ----- HIDE LABELS FOR SEARCH INPUTS (cleaner) ----- */
        .search-input-label { display: none; }
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
            min-width: 200px;
        }
        .referred-by-wrapper .btn-add-referred {
            white-space: nowrap;
            padding: 4px 12px;
            font-size: 12px;
            border-radius: 6px;
        }
        .referred-type-select {
            min-width: 120px;
        }

        /* ----- FONT OVERRIDES ----- */
        label, .form-label, .fw-bold, .small, .text-muted, 
        .card-header, .summary-card, .btn, .form-control, .form-select {
            font-family: 'Cambria', 'Times New Roman', serif;
        }
        h4, h6, h5, strong, .fw-bold {
            font-family: 'Cambria', 'Times New Roman', serif;
        }

        /* ----- RESPONSIVE ----- */
        @media (max-width: 768px) {
            .summary-card { position: relative; top: 0; }
            .item-row .row > div { margin-bottom: 6px; }
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
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-plus-circle text-success me-2"></i>Generate New Bill</h4>
            <p class="text-muted small">Create invoice for medicines, lab tests, or doctor services</p>
        </div>
        <div>
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
                                       placeholder="Type name, code, or phone..." autocomplete="off">
                                <input type="hidden" id="patient_id">
                                <div id="patientResults" class="search-results"></div>
                            </div>
                            <div id="patientSelectedInfo" class="selected-item-info"></div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Bill Date</label>
                            <input type="date" id="bill_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Payment Method</label>
                            <select id="payment_method" class="form-select form-select-sm">
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="mobile_banking">Mobile Banking</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- ========== REFERRED BY SECTION ========== -->
                    <div class="row g-2 align-items-end mt-2">
                        <div class="col-md-12">
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
                                    <i class="fas fa-plus"></i> Add New
                                </button>
                            </div>
                            <div id="referredBySelectedInfo" class="selected-item-info"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== ADD ITEMS CARD ========== -->
            <div class="card">
                <div class="card-header"><i class="fas fa-cart-plus me-2"></i>Add Items</div>
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
                            <!-- Doctor Search (for services) -->
                            <div id="doctorSelectWrapper" style="display:none;">
                                <div class="search-wrapper">
                                    <input type="text" id="doctorSearchInput" class="form-control form-control-sm" 
                                           placeholder="Search doctor..." autocomplete="off" oninput="filterDoctors(this.value)">
                                    <input type="hidden" id="doctor_id">
                                    <div id="doctorResults" class="search-results"></div>
                                </div>
                                <div id="doctorSelectedInfo" class="selected-item-info"></div>
                            </div>
                            <!-- Service Search -->
                            <div id="serviceSelectWrapper" style="display:none;">
                                <div class="search-wrapper">
                                    <input type="text" id="serviceSearchInput" class="form-control form-control-sm" 
                                           placeholder="Search service..." autocomplete="off" oninput="filterServices(this.value)" disabled>
                                    <input type="hidden" id="service_id">
                                    <div id="serviceResults" class="search-results"></div>
                                </div>
                                <div id="serviceSelectedInfo" class="selected-item-info"></div>
                            </div>
                            <!-- Medicine Search -->
                            <div id="medicineSelectWrapper" style="display:none;">
                                <div class="search-wrapper">
                                    <input type="text" id="medicineSearchInput" class="form-control form-control-sm" 
                                           placeholder="Search medicine..." autocomplete="off" oninput="filterMedicines(this.value)">
                                    <input type="hidden" id="medicine_id">
                                    <div id="medicineResults" class="search-results"></div>
                                </div>
                                <div id="medicineSelectedInfo" class="selected-item-info"></div>
                            </div>
                            <!-- Lab Test Search -->
                            <div id="labSelectWrapper" style="display:none;">
                                <div class="search-wrapper">
                                    <input type="text" id="labSearchInput" class="form-control form-control-sm" 
                                           placeholder="Search lab test..." autocomplete="off" oninput="filterLabTests(this.value)">
                                    <input type="hidden" id="lab_id">
                                    <div id="labResults" class="search-results"></div>
                                </div>
                                <div id="labSelectedInfo" class="selected-item-info"></div>
                            </div>
                            <!-- Other Selection -->
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
                    <!-- Items List -->
                    <div id="itemsContainer">
                        <div class="text-center py-5 text-muted">No items added. Add items to generate bill.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== SUMMARY (RIGHT) ========== -->
        <div class="col-lg-4">
            <div class="summary-card">
                <h6 class="mb-3">Bill Summary</h6>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Bill Type</label>
                    <select id="bill_type" class="form-select form-select-sm">
                        <option value="pharmacy">Pharmacy</option>
                        <option value="lab_test">Lab Test</option>
                        <option value="service">Service</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Discount (%)</label>
                    <input type="number" id="discount_percent" class="form-control form-control-sm" value="0" step="0.5" min="0" max="100">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tax (%)</label>
                    <div class="tax-input-group">
                        <input type="number" id="tax_percent" class="form-control form-control-sm" value="0" step="0.5" min="0" max="100">
                        <small>%</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Notes</label>
                    <textarea id="notes" class="form-control form-control-sm" rows="2" placeholder="Additional notes..."></textarea>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Subtotal:</span>
                    <strong id="subtotal">৳ 0.00</strong>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Discount (<span id="discount_percent_display">0</span>%):</span>
                    <strong id="discount_amount">- ৳ 0.00</strong>
                </div>
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Tax (<span id="tax_percent_display">0</span>%):</span>
                    <strong id="tax_amount">৳ 0.00</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold">Total Amount:</span>
                    <strong class="text-success fs-5" id="total_amount">৳ 0.00</strong>
                </div>
                <button class="btn btn-success btn-sm w-100" onclick="generateBill()">
                    <i class="fas fa-save me-2"></i>Generate Bill
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let items = [];
let itemCounter = 0;
let selectedPatientName = '';
let currentSubtotal = 0;
let currentDiscountAmount = 0;
let currentTotalAmount = 0;

// Pre-loaded data from PHP
const allMedicines = <?php echo json_encode($medicinesList); ?>;
const allLabTests = <?php echo json_encode($labTestsList); ?>;
const allDoctors = <?php echo json_encode($doctorsData); ?>;
const SERVICES_DATA = <?php 
$servicesJson = [];
foreach($servicesData as $doctorId => $services) {
    $servicesJson[$doctorId] = $services;
}
echo json_encode($servicesJson); 
?>;

// ============================================================
// 1. PATIENT SEARCH (AJAX)
// ============================================================
let searchTimeout;
$('#patientSearch').on('input', function() {
    clearTimeout(searchTimeout);
    let search = $(this).val().trim();
    if(search.length < 2) { $('#patientResults').hide(); return; }
    searchTimeout = setTimeout(() => {
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
                let html = '';
                patients.forEach(p => {
                    html += `<div class="search-result-item" onclick="selectPatient(${p.id}, '${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}', '${p.patient_code}', '${p.phone}')">
                                <div>
                                    <div class="result-name">${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</div>
                                    <div class="result-meta">${p.patient_code} | ${p.phone}</div>
                                </div>
                            </div>`;
                });
                $('#patientResults').html(html).show();
            }
        });
    }, 300);
});

function selectPatient(id, name, code, phone) {
    selectedPatientName = name;
    $('#patientSearch').val(name + ' (' + code + ' - ' + phone + ')');
    $('#patient_id').val(id);
    $('#patientSelectedInfo').text('Selected: ' + name + ' (' + code + ')').addClass('show');
    $('#patientResults').hide();
}

// ============================================================
// 2. REFERRED BY SEARCH (AJAX)
// ============================================================
let referredTimeout;
let selectedReferredName = '';

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
            success: function(response) {
                if(!response.success || !response.data || !response.data.length) {
                    $('#referredByResults').html(`
                        <div class="search-result-item" onclick="addNewReferredFromSearch('${escapeHtml(search)}')">
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
                    <div class="search-result-item" onclick="addNewReferredFromSearch('${escapeHtml(search)}')" style="border-bottom: none; background: #f0fdf4;">
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

function selectReferredBy(id, name, type, phone, specialization) {
    selectedReferredName = name;
    $('#referredBySearch').val(name);
    $('#referred_by_id').val(id);
    let infoText = 'Selected: ' + name;
    if(type) infoText += ' (' + type.charAt(0).toUpperCase() + type.slice(1) + ')';
    if(phone) infoText += ' - ' + phone;
    if(specialization) infoText += ' - ' + specialization;
    $('#referredBySelectedInfo').text(infoText).addClass('show');
    $('#referredByResults').hide();
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
                        showAlert('Referrer added successfully!', 'success');
                        selectReferredBy(response.id, data.name, data.type, data.phone, data.specialization);
                    } else {
                        showAlert(response.message || 'Failed to add referrer', 'error');
                    }
                },
                error: function() {
                    showAlert('Failed to add referrer', 'error');
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
                        showAlert('Referrer added successfully!', 'success');
                        selectReferredBy(response.id, data.name, data.type, data.phone, data.specialization);
                    } else {
                        showAlert(response.message || 'Failed to add referrer', 'error');
                    }
                },
                error: function() {
                    showAlert('Failed to add referrer', 'error');
                }
            });
        }
    });
}

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

// ============================================================
// 3. LOCAL SEARCH FUNCTIONS
// ============================================================

// ----- Medicine -----
function filterMedicines(query) {
    let q = query.trim().toLowerCase();
    if(q.length < 2) { $('#medicineResults').hide(); return; }
    let results = allMedicines.filter(m => m.medicine_name.toLowerCase().indexOf(q) !== -1);
    if(!results.length) { $('#medicineResults').html('<div class="search-result-item">No medicines found</div>').show(); return; }
    let html = '';
    results.forEach(m => {
        html += `<div class="search-result-item" onclick="selectMedicine(${m.id}, '${escapeHtml(m.medicine_name)}', ${m.selling_price})">
                    <div><div class="result-name">${escapeHtml(m.medicine_name)}</div><div class="result-meta">Stock: ${m.current_stock||'N/A'}</div></div>
                    <div class="result-price">৳ ${parseFloat(m.selling_price).toFixed(2)}</div>
                </div>`;
    });
    $('#medicineResults').html(html).show();
}
function selectMedicine(id, name, price) {
    $('#medicineSearchInput').val(name);
    $('#medicine_id').val(id);
    $('#medicineSelectedInfo').text('Selected: ' + name + ' - ৳ ' + parseFloat(price).toFixed(2)).addClass('show');
    $('#medicineResults').hide();
    window.selectedMedicine = { id, name, price };
}

// ----- Lab Test -----
function filterLabTests(query) {
    let q = query.trim().toLowerCase();
    if(q.length < 2) { $('#labResults').hide(); return; }
    let results = allLabTests.filter(t => t.test_name.toLowerCase().indexOf(q) !== -1);
    if(!results.length) { $('#labResults').html('<div class="search-result-item">No lab tests found</div>').show(); return; }
    let html = '';
    results.forEach(t => {
        html += `<div class="search-result-item" onclick="selectLabTest(${t.id}, '${escapeHtml(t.test_name)}', ${t.price})">
                    <div><div class="result-name">${escapeHtml(t.test_name)}</div><div class="result-meta">${escapeHtml(t.category_name||'General')}</div></div>
                    <div class="result-price">৳ ${parseFloat(t.price).toFixed(2)}</div>
                </div>`;
    });
    $('#labResults').html(html).show();
}
function selectLabTest(id, name, price) {
    $('#labSearchInput').val(name);
    $('#lab_id').val(id);
    $('#labSelectedInfo').text('Selected: ' + name + ' - ৳ ' + parseFloat(price).toFixed(2)).addClass('show');
    $('#labResults').hide();
    window.selectedLab = { id, name, price };
}

// ----- Doctor -----
function filterDoctors(query) {
    let q = query.trim().toLowerCase();
    if(q.length < 2) { $('#doctorResults').hide(); return; }
    let results = allDoctors.filter(d => d.name.toLowerCase().indexOf(q) !== -1);
    if(!results.length) { $('#doctorResults').html('<div class="search-result-item">No doctors found</div>').show(); return; }
    let html = '';
    results.forEach(d => {
        html += `<div class="search-result-item" onclick="selectDoctor(${d.id}, '${escapeHtml(d.name)}')">
                    <div><div class="result-name">${escapeHtml(d.name)}</div></div>
                </div>`;
    });
    $('#doctorResults').html(html).show();
}
function selectDoctor(id, name) {
    $('#doctorSearchInput').val(name);
    $('#doctor_id').val(id);
    $('#doctorSelectedInfo').text('Selected: ' + name).addClass('show');
    $('#doctorResults').hide();
    window.selectedDoctor = { id, name };
    $('#serviceSearchInput').prop('disabled', false);
    $('#serviceSelectWrapper').show();
    $('#serviceSearchInput').val('');
    $('#service_id').val('');
    $('#serviceSelectedInfo').removeClass('show');
    window.selectedService = null;
    filterServices('');
}

// ----- Service -----
function filterServices(query) {
    let doctorId = parseInt($('#doctor_id').val());
    if(!doctorId) { $('#serviceResults').html('<div class="search-result-item">Select a doctor first</div>').show(); return; }
    let services = SERVICES_DATA[doctorId] || [];
    if(!services.length) { $('#serviceResults').html('<div class="search-result-item">No services</div>').show(); return; }
    let q = query.trim().toLowerCase();
    let filtered = services.filter(s => s.service_name.toLowerCase().indexOf(q) !== -1);
    if(!filtered.length) { $('#serviceResults').html('<div class="search-result-item">No matching services</div>').show(); return; }
    let html = '';
    filtered.forEach(s => {
        html += `<div class="search-result-item" onclick="selectService(${s.id}, '${escapeHtml(s.service_name)}', ${s.service_price})">
                    <div><div class="result-name">${escapeHtml(s.service_name)}</div></div>
                    <div class="result-price">৳ ${parseFloat(s.service_price).toFixed(2)}</div>
                </div>`;
    });
    $('#serviceResults').html(html).show();
}
function selectService(id, name, price) {
    $('#serviceSearchInput').val(name);
    $('#service_id').val(id);
    $('#serviceSelectedInfo').text('Selected: ' + name + ' - ৳ ' + parseFloat(price).toFixed(2)).addClass('show');
    $('#serviceResults').hide();
    window.selectedService = { id, name, price };
}

$(document).click(function(e) {
    if(!$(e.target).closest('#medicineSearchInput, #medicineResults').length) $('#medicineResults').hide();
    if(!$(e.target).closest('#labSearchInput, #labResults').length) $('#labResults').hide();
    if(!$(e.target).closest('#doctorSearchInput, #doctorResults').length) $('#doctorResults').hide();
    if(!$(e.target).closest('#serviceSearchInput, #serviceResults').length) $('#serviceResults').hide();
});

// ============================================================
// 4. ITEM TYPE CHANGE
// ============================================================
function onItemTypeChange() {
    let type = $('#itemType').val();
    $('#doctorSelectWrapper, #serviceSelectWrapper, #medicineSelectWrapper, #labSelectWrapper, #otherSelectWrapper').hide().find('input, select').val('');
    window.selectedMedicine = window.selectedLab = window.selectedDoctor = window.selectedService = null;
    $('#doctorSelectedInfo, #serviceSelectedInfo, #medicineSelectedInfo, #labSelectedInfo').removeClass('show');
    if(!type) return;

    if(type === 'service') {
        $('#doctorSelectWrapper, #serviceSelectWrapper').show();
        $('#serviceSearchInput').prop('disabled', true);
        $('#bill_type').val('service');
    } else if(type === 'medicine') {
        $('#medicineSelectWrapper').show();
        $('#bill_type').val('pharmacy');
    } else if(type === 'lab_test') {
        $('#labSelectWrapper').show();
        $('#bill_type').val('lab_test');
    } else if(type === 'other') {
        $('#otherSelectWrapper').show();
        $('#bill_type').val('other');
    }
}

// ============================================================
// 5. ADD ITEM
// ============================================================
function addItem() {
    let type = $('#itemType').val();
    if(type === 'service') {
        if(!window.selectedDoctor || !window.selectedService) {
            Swal.fire('Warning', 'Please select a valid doctor and service', 'warning'); return;
        }
        addItemToList({
            id: itemCounter++,
            item_id: window.selectedService.id,
            doctor_id: window.selectedDoctor.id,
            doctor_name: window.selectedDoctor.name,
            item_type: type,
            item_name: window.selectedService.name + ' (Dr. ' + window.selectedDoctor.name + ')',
            quantity: 1,
            unit_price: window.selectedService.price,
            discount_percent: 0,
            tax_percent: parseFloat($('#tax_percent').val()) || 0
        });
    } else if(type === 'medicine') {
        if(!window.selectedMedicine) {
            Swal.fire('Warning', 'Please select a valid medicine', 'warning'); return;
        }
        addItemToList({
            id: itemCounter++,
            item_id: window.selectedMedicine.id,
            item_type: type,
            item_name: window.selectedMedicine.name,
            quantity: 1,
            unit_price: window.selectedMedicine.price,
            discount_percent: 0,
            tax_percent: parseFloat($('#tax_percent').val()) || 0
        });
    } else if(type === 'lab_test') {
        if(!window.selectedLab) {
            Swal.fire('Warning', 'Please select a valid lab test', 'warning'); return;
        }
        let lab = window.selectedLab;
        let mainItemId = itemCounter++;
        addItemToList({
            id: mainItemId,
            item_id: lab.id,
            item_type: type,
            item_name: lab.name,
            quantity: 1,
            unit_price: lab.price,
            discount_percent: 0,
            tax_percent: parseFloat($('#tax_percent').val()) || 0,
            is_lab_test: true,
            lab_test_id: lab.id
        });
        fetchLabTestAccessories(lab.id, mainItemId);
    } else if(type === 'other') {
        let selectedOption = $('#otherSelect option:selected');
        if(selectedOption.val() === 'custom') {
            Swal.fire({
                title: 'Custom Item',
                html: `<input id="customName" class="swal2-input" placeholder="Item Name">
                       <input id="customPrice" class="swal2-input" type="number" step="0.01" placeholder="Price">`,
                preConfirm: () => {
                    const name = document.getElementById('customName').value;
                    const price = parseFloat(document.getElementById('customPrice').value);
                    if(!name || !price) return false;
                    return { name, price };
                }
            }).then((result) => {
                if(result.value) {
                    addItemToList({
                        id: itemCounter++,
                        item_id: null,
                        item_type: type,
                        item_name: result.value.name,
                        quantity: 1,
                        unit_price: result.value.price,
                        discount_percent: 0,
                        tax_percent: parseFloat($('#tax_percent').val()) || 0
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
// 6. FETCH LAB TEST ACCESSORIES
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
                        id: itemCounter++,
                        item_id: acc.id,
                        item_type: 'lab_accessory',
                        item_name: 'Accessory: ' + acc.accessory_name,
                        quantity: acc.quantity_required || 1,
                        unit_price: parseFloat(acc.unit_price) || 0,
                        discount_percent: 0,
                        tax_percent: parseFloat($('#tax_percent').val()) || 0,
                        parent_lab_item_id: parentItemId,
                        lab_test_id: testId
                    });
                });
            }
        }
    });
}

// ============================================================
// 7. ADD ITEM TO LIST
// ============================================================
function addItemToList(item) {
    items.push(item);
    renderItems();
    calculateTotal();
}

// ============================================================
// 8. RENDER ITEMS
// ============================================================
function renderItems() {
    if(!items.length) {
        $('#itemsContainer').html('<div class="text-center py-5 text-muted">No items added. Add items to generate bill.</div>');
        return;
    }
    let html = '';
    items.forEach((item, index) => {
        let itemTotal = item.quantity * item.unit_price;
        let disc = itemTotal * (item.discount_percent / 100);
        let tax = (itemTotal - disc) * (item.tax_percent / 100);
        let total = itemTotal - disc + tax;

        let badgeClass = 'badge-other';
        if(item.item_type === 'medicine') badgeClass = 'badge-medicine';
        else if(item.item_type === 'lab_test') badgeClass = 'badge-lab';
        else if(item.item_type === 'service') badgeClass = 'badge-service';
        else if(item.item_type === 'lab_accessory') badgeClass = 'badge-accessory';

        html += `
            <div class="item-row" data-index="${index}">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <span class="item-type-badge ${badgeClass}">${item.item_type.toUpperCase().replace('_', ' ')}</span>
                        <strong class="d-block small">${escapeHtml(item.item_name)}</strong>
                        ${item.parent_lab_item_id ? '<small class="text-muted">(accessory)</small>' : ''}
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Qty</label>
                        <input type="number" class="form-control form-control-sm" value="${item.quantity}" min="1" onchange="updateItem(${index}, 'quantity', this.value)">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Price</label>
                        <input type="number" class="form-control form-control-sm" value="${item.unit_price}" step="0.01" onchange="updateItem(${index}, 'unit_price', this.value)">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Disc %</label>
                        <input type="number" class="form-control form-control-sm" value="${item.discount_percent}" step="0.5" onchange="updateItem(${index}, 'discount_percent', this.value)">
                    </div>
                    <div class="col-md-1">
                        <div><strong>৳ ${total.toFixed(2)}</strong></div>
                    </div>
                    <div class="col-md-1 text-end">
                        <i class="fas fa-trash-alt text-danger" onclick="removeItem(${index})" style="cursor:pointer;"></i>
                    </div>
                </div>
            </div>
        `;
    });
    $('#itemsContainer').html(html);
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) { if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m; });
}

function updateItem(index, field, value) {
    items[index][field] = parseFloat(value);
    if(field === 'quantity' && value < 1) items[index].quantity = 1;
    renderItems();
    calculateTotal();
}

// ============================================================
// 9. REMOVE ITEM
// ============================================================
function removeItem(index) {
    Swal.fire({
        title: 'Remove Item?',
        text: 'This item will be removed',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes'
    }).then((result) => {
        if(result.isConfirmed) {
            let item = items[index];
            if(item.is_lab_test) {
                let parentId = item.id;
                let accessoryIndices = [];
                items.forEach((it, idx) => {
                    if(it.parent_lab_item_id === parentId) {
                        accessoryIndices.push(idx);
                    }
                });
                accessoryIndices.sort((a,b) => b - a);
                accessoryIndices.forEach(idx => items.splice(idx, 1));
            }
            items.splice(index, 1);
            renderItems();
            calculateTotal();
        }
    });
}

// ============================================================
// 10. CALCULATE TOTAL - UPDATED to store values globally
// ============================================================
function calculateTotal() {
    let subtotal = 0;
    items.forEach(item => {
        let itemTotal = item.quantity * item.unit_price;
        let disc = itemTotal * (item.discount_percent / 100);
        subtotal += (itemTotal - disc);
    });

    let discPct = parseFloat($('#discount_percent').val()) || 0;
    let taxPct = parseFloat($('#tax_percent').val()) || 0;
    let discAmt = subtotal * (discPct / 100);
    let afterDisc = subtotal - discAmt;
    let taxAmt = afterDisc * (taxPct / 100);
    let total = afterDisc + taxAmt;

    // Store globally for use in popup
    currentSubtotal = subtotal;
    currentDiscountAmount = discAmt;
    currentTotalAmount = total;

    $('#subtotal').text('৳ ' + subtotal.toFixed(2));
    $('#discount_amount').text('- ৳ ' + discAmt.toFixed(2));
    $('#discount_percent_display').text(discPct);
    $('#tax_amount').text('৳ ' + taxAmt.toFixed(2));
    $('#tax_percent_display').text(taxPct);
    $('#total_amount').text('৳ ' + total.toFixed(2));
}

$('#discount_percent, #tax_percent').on('input', calculateTotal);

// ============================================================
// 11. TOAST ALERT
// ============================================================
function showAlert(message, type) {
    const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b' };
    const toast = $(`<div style="position:fixed;top:20px;right:20px;z-index:9999;background:${colors[type]};color:white;padding:10px 20px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);font-size:13px;animation:slideIn 0.3s ease-out;">${message}</div>`);
    $('body').append(toast);
    setTimeout(() => {
        toast.fadeOut(300, function() { $(this).remove(); });
    }, 3000);
}

// ============================================================
// 12. GENERATE BILL - UPDATED with correct amounts from local calculations
// ============================================================
function generateBill() {
    let patientId = $('#patient_id').val();
    let billDate = $('#bill_date').val();
    let discountPercent = parseFloat($('#discount_percent').val()) || 0;
    let taxPercent = parseFloat($('#tax_percent').val()) || 0;
    let notes = $('#notes').val();
    let referredById = $('#referred_by_id').val();
    let paymentMethod = $('#payment_method').val();

    if(!patientId) { 
        Swal.fire('Error', 'Please select a patient', 'error'); 
        return; 
    }
    if(!items.length) { 
        Swal.fire('Error', 'Please add at least one item', 'error'); 
        return; 
    }

    let firstType = items[0].item_type;
    let autoBillType = firstType === 'medicine' ? 'pharmacy' : (firstType === 'lab_test' ? 'lab_test' : (firstType === 'service' ? 'service' : 'other'));

    let itemsData = items.map(item => ({
        item_type: item.item_type,
        item_id: item.item_id,
        doctor_id: item.doctor_id || null,
        item_name: item.item_name,
        quantity: item.quantity,
        unit_price: item.unit_price,
        discount_percent: item.discount_percent,
        tax_percent: item.tax_percent
    }));

    let formData = new FormData();
    formData.append('patient_id', patientId);
    formData.append('bill_date', billDate);
    formData.append('bill_type', autoBillType);
    formData.append('discount_percent', discountPercent);
    formData.append('tax_percent', taxPercent);
    formData.append('notes', notes);
    formData.append('referred_by', referredById || '');
    formData.append('payment_method', paymentMethod);
    formData.append('items', JSON.stringify(itemsData));

    Swal.fire({ 
        title: 'Processing...', 
        allowOutsideClick: false, 
        didOpen: () => Swal.showLoading() 
    });

    $.ajax({
        url: BASE_URL + '/bills/store',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                // Use locally calculated values - they are accurate
                let totalAmount = currentTotalAmount;
                let totalDiscount = currentDiscountAmount;
                let patientName = selectedPatientName || response.patient_name || 'N/A';
                let referredName = selectedReferredName || response.referred_by_name || 'N/A';
                let itemCount = items.length;
                
                // Get the actual discount percentage from the input
                let discPct = parseFloat($('#discount_percent').val()) || 0;
                
                // ============================================================
                // POPUP WITH FULL INFORMATION AND CORRECT AMOUNTS
                // ============================================================
                Swal.fire({
                    icon: 'success',
                    title: 'Bill Generated Successfully!',
                    html: '<div style="text-align: left; font-size: 14px; line-height: 2.0; font-family: Cambria, Times New Roman, serif;">' +
                          '<div style="border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-bottom: 10px;">' +
                          '<strong style="color: #10b981; font-size: 18px;">Bill #:</strong> <span style="font-weight: 700; font-size: 16px;">' + response.bill_number + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Patient:</strong></span>' +
                          '<span style="font-weight: 600;">' + patientName + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Referred By:</strong></span>' +
                          '<span>' + referredName + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0; border-bottom: 1px dashed #e5e7eb; margin-bottom: 4px;">' +
                          '<span><strong>Subtotal:</strong></span>' +
                          '<span>৳ ' + currentSubtotal.toFixed(2) + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Discount (' + discPct + '%):</strong></span>' +
                          '<span style="color: #ef4444;">- ৳ ' + totalDiscount.toFixed(2) + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Tax (0%):</strong></span>' +
                          '<span>৳ 0.00</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0; border-top: 2px solid #10b981; padding-top: 6px; margin-top: 4px;">' +
                          '<span style="font-weight: 700; font-size: 15px;"><strong>Total Amount:</strong></span>' +
                          '<span style="font-weight: 700; color: #10b981; font-size: 18px;">৳ ' + totalAmount.toFixed(2) + '</span>' +
                          '</div>' +
                          '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                          '<span><strong>Items:</strong></span>' +
                          '<span>' + itemCount + '</span>' +
                          '</div>' +
                          (response.reference_type ? '<div style="display: flex; justify-content: space-between; padding: 2px 0;"><span><strong>Type:</strong></span><span>' + response.reference_type + '</span></div>' : '') +
                          (response.bill_id ? '<div style="display: flex; justify-content: space-between; padding: 2px 0;"><span><strong>Bill ID:</strong></span><span>' + response.bill_id + '</span></div>' : '') +
                          '</div>',
                    confirmButtonText: 'Done',
                    confirmButtonColor: '#10b981',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    timer: 30000,
                    timerProgressBar: true,
                    width: '460px'
                }).then((result) => {
                    // Refresh the page to create a new bill
                    window.location.href = BASE_URL + '/bills/create';
                });
            } else {
                Swal.fire('Error', response.message || 'Failed to create bill', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            let errorMsg = 'Failed to create bill. Please try again.';
            try {
                let response = JSON.parse(xhr.responseText);
                if(response.message) errorMsg = response.message;
            } catch(e) {}
            Swal.fire('Error', errorMsg, 'error');
        }
    });
}
</script>
</body>
</html>