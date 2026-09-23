<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');

// Check if data is passed from controller
$hasPatients = isset($patients) && count($patients) > 0;
$hasTests = isset($tests) && count($tests) > 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Lab Order - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container-fluid { padding: 20px; }
        .card { margin-bottom: 20px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .card-header { background: white; border-bottom: 1px solid #e5e7eb; padding: 12px 20px; }
        .item-row { background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 15px; border: 1px solid #e5e7eb; }
        .summary-card { background: white; border-radius: 16px; padding: 20px; border: 1px solid #e5e7eb; position: sticky; top: 20px; }
        .search-results { position: absolute; z-index: 1000; background: white; border: 1px solid #ddd; border-radius: 8px; max-height: 300px; overflow-y: auto; width: 100%; display: none; }
        .search-result-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
        .search-result-item:hover { background: #f0fdf4; }
        .test-item { transition: background-color 0.2s; cursor: pointer; }
        .test-item:hover { background-color: #f8fafc; }
        .test-checkbox:checked + label { background-color: #e8f5e9; border-radius: 8px; }
        .test-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; margin-bottom: 12px; transition: all 0.2s; }
        .test-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .test-card.selected { border-color: #10b981; background-color: #f0fdf4; }
        .selected-count-badge { background: #10b981; color: white; border-radius: 20px; padding: 5px 12px; font-size: 12px; }
        .priority-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .priority-routine { background: #e2e8f0; color: #475569; }
        .priority-urgent { background: #fef3c7; color: #d97706; }
        .priority-stat { background: #fee2e2; color: #ef4444; }
        
        /* Discount Styles */
        .discount-btn-group { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
        .discount-btn { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 500; border: 1px solid #e5e7eb; background: #f8fafc; cursor: pointer; transition: all 0.2s; }
        .discount-btn:hover { background: #10b981; color: white; border-color: #10b981; }
        .discount-btn.active { background: #10b981; color: white; border-color: #10b981; }
        .discount-input-group { display: flex; gap: 8px; align-items: center; }
        .discount-input-group input { width: 80px; }
        .discount-type-select { width: 100px; }
        
        .required-field::after { content: " *"; color: #ef4444; }
        .test-card { transition: all 0.2s; }
        .test-card:hover { transform: translateX(5px); }
        .test-checkbox:checked + label { background-color: #f0fdf4; }
        #selectedTestsList .list-group-item { font-size: 12px; }
        .btn-sm { padding: 5px 12px; }
        
        /* Doctor Search Styles */
        .doctor-search-container { position: relative; }
        .doctor-search-results { 
            position: absolute; 
            z-index: 1000; 
            background: white; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            max-height: 300px; 
            overflow-y: auto; 
            width: 100%; 
            display: none; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .doctor-search-item { 
            padding: 10px 12px; 
            cursor: pointer; 
            border-bottom: 1px solid #f1f5f9; 
            transition: background 0.2s;
        }
        .doctor-search-item:hover { background: #f0fdf4; }
        .doctor-search-item:last-child { border-bottom: none; }
        .selected-doctor-display {
            display: none;
            background: #f0fdf4;
            border: 1px solid #10b981;
            border-radius: 8px;
            padding: 8px 12px;
            margin-top: 6px;
            align-items: center;
            justify-content: space-between;
        }
        .selected-doctor-display .remove-doctor {
            cursor: pointer;
            color: #ef4444;
            font-size: 18px;
            font-weight: bold;
            padding: 0 4px;
            line-height: 1;
        }
        .selected-doctor-display .remove-doctor:hover { color: #dc2626; }
        
        /* Test Item Styling */
        .test-price-tag {
            font-weight: 600;
            color: #10b981;
            font-size: 14px;
        }
        .test-meta {
            font-size: 11px;
            color: #94a3b8;
        }
        .test-meta i {
            width: 14px;
            margin-right: 2px;
        }
        .test-meta .sep {
            color: #d1d5db;
            margin: 0 4px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4><i class="fas fa-flask text-success me-2"></i>Create Lab Test Order</h4>
            <p class="text-muted small mb-0">Create a new laboratory test order for a patient</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/lab/orders" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Patient & Doctor Information -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Order Information</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small required-field">Patient</label>
                            <div style="position: relative;">
                                <input type="text" id="patientSearch" class="form-control form-control-sm" placeholder="Type patient name, code or phone number...">
                                <input type="hidden" id="patient_id">
                                <div id="patientResults" class="search-results"></div>
                            </div>
                            <div id="selectedPatientDisplay" style="display: none;" class="mt-2">
                                <div style="background: #f0fdf4; border: 1px solid #10b981; border-radius: 8px; padding: 8px 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <span id="selectedPatientName" style="font-weight: 500;"></span>
                                    <span class="remove-patient" onclick="clearPatient()" style="cursor: pointer; color: #ef4444; font-size: 18px; font-weight: bold;">&times;</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small required-field">Referring Doctor</label>
                            <div class="doctor-search-container">
                                <input type="text" id="doctorSearch" class="form-control form-control-sm" placeholder="Type at least 2 characters to search...">
                                <input type="hidden" id="doctor_id">
                                <div id="doctorResults" class="doctor-search-results"></div>
                                <div id="selectedDoctorDisplay" class="selected-doctor-display">
                                    <span id="selectedDoctorName"></span>
                                    <span class="remove-doctor" onclick="clearDoctor()">&times;</span>
                                </div>
                            </div>
                            <small class="text-muted">Type at least 2 characters to search for doctors</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Order Date</label>
                            <input type="date" id="order_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Priority</label>
                            <select id="priority" class="form-select form-select-sm">
                                <option value="routine">Routine</option>
                                <option value="urgent">Urgent</option>
                                <option value="stat">STAT (Emergency)</option>
                            </select>
                            <small class="text-muted">STAT orders will be highlighted for immediate processing</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Select Tests Section -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-vial me-2"></i>Select Tests</h6>
                        <span id="selectedCount" class="selected-count-badge">0 selected</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small">Filter by Category</label>
                            <select id="categoryFilter" class="form-select form-select-sm">
                                <option value="all">All Categories</option>
                                <?php 
                                $categories = [];
                                if($hasTests):
                                    foreach($tests as $test):
                                        if(!in_array($test['category_name'], $categories)):
                                            $categories[] = $test['category_name'];
                                ?>
                                <option value="<?php echo htmlspecialchars($test['category_name']); ?>">
                                    <?php echo htmlspecialchars($test['category_name']); ?>
                                </option>
                                <?php 
                                        endif;
                                    endforeach; 
                                endif; 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Search Test</label>
                            <input type="text" id="testSearch" class="form-control form-control-sm" placeholder="Search by test name...">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearSelection()">
                                <i class="fas fa-times me-1"></i>Clear All
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Tests List -->
                    <div id="testsContainer" style="max-height: 500px; overflow-y: auto;">
                        <?php if($hasTests && count($tests) > 0): ?>
                            <?php foreach($tests as $test): ?>
                            <div class="test-card" data-category="<?php echo htmlspecialchars($test['category_name']); ?>" data-name="<?php echo htmlspecialchars($test['test_name']); ?>">
                                <div class="form-check">
                                    <input class="form-check-input test-checkbox" type="checkbox" 
                                           name="test_<?php echo $test['id']; ?>" 
                                           value="<?php echo $test['id']; ?>" 
                                           id="test_<?php echo $test['id']; ?>"
                                           data-price="<?php echo $test['price']; ?>"
                                           data-name="<?php echo htmlspecialchars($test['test_name']); ?>"
                                           onchange="updateSelectedCount()">
                                    <label class="form-check-label w-100" for="test_<?php echo $test['id']; ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($test['test_name']); ?></strong>
                                                <br>
                                                <span class="test-meta">
                                                    <i class="fas fa-tag"></i><?php echo htmlspecialchars($test['category_name']); ?>
                                                    <?php if($test['specimen_type']): ?>
                                                        <span class="sep">|</span>
                                                        <i class="fas fa-vial"></i><?php echo htmlspecialchars($test['specimen_type']); ?>
                                                    <?php endif; ?>
                                                    <?php if($test['turnaround_time']): ?>
                                                        <span class="sep">|</span>
                                                        <i class="fas fa-clock"></i>TAT: <?php echo $test['turnaround_time']; ?> hrs
                                                    <?php endif; ?>
                                                    <?php if($test['requires_fasting']): ?>
                                                        <span class="sep">|</span>
                                                        <span class="text-warning"><i class="fas fa-utensils"></i>Fasting Required</span>
                                                    <?php endif; ?>
                                                    <?php if($test['normal_range']): ?>
                                                        <br><span class="test-meta">Normal Range: <?php echo htmlspecialchars($test['normal_range']); ?> <?php echo htmlspecialchars($test['unit']); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div class="text-end">
                                                <div class="test-price-tag">
                                                    ৳ <?php echo number_format($test['price'], 2); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-flask fa-3x mb-3 d-block"></i>
                                <p>No lab tests available</p>
                                <small>Please add lab tests first in the system settings</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white py-2">
                    <h6 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Additional Information</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Clinical Diagnosis</label>
                        <textarea id="clinical_diagnosis" class="form-control form-control-sm" rows="3" placeholder="Enter clinical diagnosis or relevant medical history..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Special Instructions</label>
                        <textarea id="notes" class="form-control form-control-sm" rows="2" placeholder="Any special instructions for sample collection or testing..."></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Summary Card -->
            <div class="summary-card">
                <h6 class="mb-3"><i class="fas fa-chart-line me-2"></i>Order Summary</h6>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Selected Tests</label>
                    <div id="selectedTestsList" class="small" style="max-height: 200px; overflow-y: auto;">
                        <div class="text-muted text-center py-3">No tests selected</div>
                    </div>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Subtotal:</span>
                    <strong id="subtotal">৳ 0.00</strong>
                </div>
                
                <!-- Discount Section -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-bold">Discount</span>
                        <span id="discountDisplay" class="text-danger">- ৳ 0.00</span>
                    </div>
                    <div class="discount-input-group mt-1">
                        <input type="number" id="discountAmount" class="form-control form-control-sm" placeholder="Amount" min="0" step="1" onchange="updateDiscount()">
                        <select id="discountType" class="form-select form-select-sm discount-type-select" onchange="updateDiscount()">
                            <option value="fixed">Fixed (৳)</option>
                            <option value="percentage">Percentage (%)</option>
                        </select>
                    </div>
                    <div class="discount-btn-group">
                        <span class="small text-muted me-1">Quick:</span>
                        <button class="discount-btn" onclick="applyQuickDiscount(5)">5%</button>
                        <button class="discount-btn" onclick="applyQuickDiscount(10)">10%</button>
                        <button class="discount-btn" onclick="applyQuickDiscount(15)">15%</button>
                        <button class="discount-btn" onclick="applyQuickDiscount(20)">20%</button>
                        <button class="discount-btn" onclick="applyQuickDiscount(25)">25%</button>
                        <button class="discount-btn" onclick="applyQuickDiscount(50)">50%</button>
                        <button class="discount-btn text-danger" onclick="clearDiscount()">Clear</button>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mb-2 small">
                    <span>Tax (5%):</span>
                    <strong id="tax_amount">৳ 0.00</strong>
                </div>
                
                <hr>
                
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold">Total Amount:</span>
                    <strong class="text-success fs-5" id="total_amount">৳ 0.00</strong>
                </div>
                
                <div class="alert alert-info small">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Note:</strong> A bill will be automatically generated for this order based on the selected tests.
                </div>
                
                <button class="btn btn-success btn-sm w-100" onclick="createOrder()" id="createOrderBtn" disabled>
                    <i class="fas fa-save me-2"></i>Create Order & Generate Bill
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let selectedTests = [];
let discountValue = 0;
let discountType = 'fixed';

// ================================================================
// PATIENT SEARCH
// ================================================================
let patientSearchTimeout;
$('#patientSearch').on('input', function() {
    clearTimeout(patientSearchTimeout);
    let search = $(this).val();
    
    if(search.length < 2) {
        $('#patientResults').hide();
        return;
    }
    
    patientSearchTimeout = setTimeout(() => {
        $.ajax({
            url: BASE_URL + '/api/search-patients',
            method: 'GET',
            data: { search: search },
            dataType: 'json',
            success: function(patients) {
                if(patients.length === 0) {
                    $('#patientResults').html('<div class="search-result-item">No patients found</div>').show();
                    return;
                }
                let html = '';
                patients.forEach(p => {
                    html += `<div class="search-result-item" onclick="selectPatient(${p.id}, '${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}', '${escapeHtml(p.patient_code)}', '${escapeHtml(p.phone)}')">
                                <strong>${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</strong>
                                <br>
                                <small>${escapeHtml(p.patient_code)} | ${escapeHtml(p.phone)}</small>
                            </div>`;
                });
                $('#patientResults').html(html).show();
            },
            error: function() {
                $('#patientResults').html('<div class="search-result-item">Error searching patients</div>').show();
            }
        });
    }, 300);
});

function selectPatient(id, name, code, phone) {
    $('#patientSearch').val(name + ' (' + code + ')');
    $('#patient_id').val(id);
    $('#patientResults').hide();
    
    // Show selected patient
    $('#selectedPatientDisplay').show();
    $('#selectedPatientName').html('<strong>' + name + '</strong> <span class="text-muted ms-2">' + code + ' | ' + phone + '</span>');
}

function clearPatient() {
    $('#patient_id').val('');
    $('#patientSearch').val('');
    $('#selectedPatientDisplay').hide();
}

$(document).click(function(e) {
    if(!$(e.target).closest('#patientSearch, #patientResults, #selectedPatientDisplay').length) {
        $('#patientResults').hide();
    }
});

// ================================================================
// DOCTOR SEARCH - Minimum 2 characters
// ================================================================
let doctorSearchTimeout;

function searchDoctors(search = '') {
    // Don't search if query is less than 2 characters
    if (search.length < 2) {
        $('#doctorResults').hide();
        return;
    }
    
    $.ajax({
        url: BASE_URL + '/api/search-doctors',
        method: 'GET',
        data: { search: search },
        dataType: 'json',
        success: function(doctors) {
            if(doctors.length === 0) {
                $('#doctorResults').html('<div class="doctor-search-item">No doctors found matching "' + escapeHtml(search) + '"</div>').show();
                return;
            }
            let html = '';
            doctors.forEach(d => {
                html += `<div class="doctor-search-item" onclick="selectDoctor(${d.id}, '${escapeHtml(d.first_name)} ${escapeHtml(d.last_name)}', '${escapeHtml(d.specialization)}', '${escapeHtml(d.bmdc_number)}')">
                            <strong>Dr. ${escapeHtml(d.first_name)} ${escapeHtml(d.last_name)}</strong>
                            <br>
                            <small>${escapeHtml(d.specialization)} | BMDC: ${escapeHtml(d.bmdc_number)}</small>
                        </div>`;
            });
            $('#doctorResults').html(html).show();
        },
        error: function() {
            $('#doctorResults').html('<div class="doctor-search-item">Error searching doctors</div>').show();
        }
    });
}

// Doctor search on input - minimum 2 characters
$('#doctorSearch').on('input', function() {
    clearTimeout(doctorSearchTimeout);
    let search = $(this).val().trim();
    
    // If doctor is already selected, don't show search results
    if ($('#doctor_id').val()) {
        $('#doctorResults').hide();
        return;
    }
    
    // Only search if minimum 2 characters
    if (search.length >= 2) {
        doctorSearchTimeout = setTimeout(() => {
            searchDoctors(search);
        }, 300);
    } else {
        $('#doctorResults').hide();
    }
});

// Clear doctor selection
function clearDoctor() {
    $('#doctor_id').val('');
    $('#doctorSearch').val('');
    $('#selectedDoctorDisplay').hide();
    $('#doctorResults').hide();
}

// Select doctor from search results
function selectDoctor(id, name, specialization, bmdc) {
    $('#doctor_id').val(id);
    $('#doctorSearch').val('');
    $('#doctorResults').hide();
    
    // Show selected doctor
    $('#selectedDoctorDisplay').show();
    $('#selectedDoctorName').html(`<strong>Dr. ${name}</strong> <span class="text-muted ms-2">${specialization} | BMDC: ${bmdc}</span>`);
}

// Hide results when clicking outside
$(document).click(function(e) {
    if(!$(e.target).closest('#doctorSearch, #doctorResults, #selectedDoctorDisplay').length) {
        $('#doctorResults').hide();
    }
});

// ================================================================
// TEST FILTERS
// ================================================================
function filterTests() {
    let category = $('#categoryFilter').val();
    let search = $('#testSearch').val().toLowerCase();
    
    $('.test-card').each(function() {
        let card = $(this);
        let testCategory = card.data('category');
        let testName = card.data('name');
        
        let categoryMatch = (category === 'all' || testCategory === category);
        let searchMatch = (search === '' || testName.toLowerCase().includes(search));
        
        if(categoryMatch && searchMatch) {
            card.show();
        } else {
            card.hide();
        }
    });
}

$('#categoryFilter').on('change', filterTests);
$('#testSearch').on('keyup', filterTests);

// ================================================================
// TEST SELECTION
// ================================================================
function updateSelectedCount() {
    selectedTests = [];
    $('.test-checkbox:checked').each(function() {
        let testId = $(this).val();
        let testName = $(this).data('name');
        let testPrice = $(this).data('price');
        selectedTests.push({
            id: testId,
            name: testName,
            price: parseFloat(testPrice)
        });
    });
    
    // Update count badge
    let count = selectedTests.length;
    $('#selectedCount').text(count + ' selected');
    
    // Update selected tests list
    if(selectedTests.length > 0) {
        let html = '<div class="list-group list-group-flush">';
        selectedTests.forEach((test, index) => {
            html += `<div class="list-group-item d-flex justify-content-between align-items-center p-2">
                        <span>${escapeHtml(test.name)}</span>
                        <div>
                            <small class="text-success">৳ ${test.price.toFixed(2)}</small>
                            <i class="fas fa-times-circle text-danger ms-2" style="cursor:pointer" onclick="removeTest('${test.id}')"></i>
                        </div>
                    </div>`;
        });
        html += '</div>';
        $('#selectedTestsList').html(html);
        $('#createOrderBtn').prop('disabled', false);
    } else {
        $('#selectedTestsList').html('<div class="text-muted text-center py-3">No tests selected</div>');
        $('#createOrderBtn').prop('disabled', true);
    }
    
    calculateTotal();
}

function removeTest(testId) {
    $(`input[value="${testId}"]`).prop('checked', false);
    updateSelectedCount();
}

function clearSelection() {
    $('.test-checkbox').prop('checked', false);
    updateSelectedCount();
}

// ================================================================
// DISCOUNT CALCULATION
// ================================================================
function calculateTotal() {
    let subtotal = 0;
    selectedTests.forEach(test => {
        subtotal += test.price;
    });
    
    // Apply discount
    let discountAmount = 0;
    if (discountType === 'fixed') {
        discountAmount = parseFloat($('#discountAmount').val()) || 0;
    } else if (discountType === 'percentage') {
        let percent = parseFloat($('#discountAmount').val()) || 0;
        discountAmount = (subtotal * percent) / 100;
    }
    
    // Ensure discount doesn't exceed subtotal
    if (discountAmount > subtotal) {
        discountAmount = subtotal;
    }
    
    let afterDiscount = subtotal - discountAmount;
    let taxAmount = afterDiscount * 0.05;
    let total = afterDiscount + taxAmount;
    
    $('#subtotal').text('৳ ' + subtotal.toFixed(2));
    $('#discountDisplay').text('- ৳ ' + discountAmount.toFixed(2));
    $('#tax_amount').text('৳ ' + taxAmount.toFixed(2));
    $('#total_amount').text('৳ ' + total.toFixed(2));
    
    // Store discount amount for order creation
    window.discountAmount = discountAmount;
}

function updateDiscount() {
    discountType = $('#discountType').val();
    // Clear active quick discount buttons
    $('.discount-btn').removeClass('active');
    calculateTotal();
}

function applyQuickDiscount(percent) {
    discountType = 'percentage';
    $('#discountType').val('percentage');
    $('#discountAmount').val(percent);
    
    // Highlight active button
    $('.discount-btn').removeClass('active');
    $('.discount-btn').each(function() {
        if ($(this).text().trim() === percent + '%') {
            $(this).addClass('active');
        }
    });
    
    calculateTotal();
}

function clearDiscount() {
    $('#discountAmount').val('');
    $('#discountType').val('fixed');
    $('.discount-btn').removeClass('active');
    discountType = 'fixed';
    window.discountAmount = 0;
    calculateTotal();
}

// ================================================================
// CREATE ORDER
// ================================================================
// In create-order.php, replace the createOrder() function with this:

function createOrder() {
    let patientId = $('#patient_id').val();
    let doctorId = $('#doctor_id').val();
    let orderDate = $('#order_date').val();
    let priority = $('#priority').val();
    let clinicalDiagnosis = $('#clinical_diagnosis').val();
    let notes = $('#notes').val();
    
    // Validation
    if(!patientId) {
        Swal.fire('Error', 'Please select a patient', 'error');
        return;
    }
    
    if(!doctorId) {
        Swal.fire('Error', 'Please select a doctor', 'error');
        return;
    }
    
    if(selectedTests.length === 0) {
        Swal.fire('Error', 'Please select at least one test', 'error');
        return;
    }
    
    // Prepare form data
    let formData = new FormData();
    formData.append('patient_id', patientId);
    formData.append('doctor_id', doctorId);
    formData.append('order_date', orderDate);
    formData.append('priority', priority);
    formData.append('clinical_diagnosis', clinicalDiagnosis);
    formData.append('notes', notes);
    
    // Add discount data
    let discountAmount = parseFloat($('#discountAmount').val()) || 0;
    let discountType = $('#discountType').val();
    let discountPercent = 0;
    
    if (discountType === 'percentage') {
        discountPercent = discountAmount;
        discountAmount = 0; // Will be calculated on server
    }
    
    formData.append('discount_percent', discountPercent);
    formData.append('discount_amount', discountAmount);
    formData.append('discount_type', discountType);
    
    // Calculate subtotal and total
    let subtotal = 0;
    selectedTests.forEach(test => {
        subtotal += test.price;
    });
    let taxAmount = subtotal * 0.05;
    let total = subtotal + taxAmount;
    
    formData.append('subtotal', subtotal);
    formData.append('total_amount', total);
    
    // Send tests as JSON string
    let testsData = selectedTests.map(test => ({
        id: test.id,
        name: test.name,
        price: test.price
    }));
    formData.append('tests', JSON.stringify(testsData));
    
    Swal.fire({
        title: 'Creating Order...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/lab/store-order',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    title: 'Order Created!',
                    html: `Order #: <strong>${response.order_id}</strong><br>A bill has been generated automatically.`,
                    icon: 'success',
                    confirmButtonText: 'View Orders'
                }).then(() => {
                    window.location.href = BASE_URL + '/lab/orders';
                });
            } else {
                Swal.fire('Error', response.message || 'Failed to create order', 'error');
            }
        },
        error: function(xhr) {
            let errorMsg = 'Failed to create order';
            try {
                let response = JSON.parse(xhr.responseText);
                if(response.message) errorMsg = response.message;
            } catch(e) {}
            Swal.fire('Error', errorMsg, 'error');
        }
    });
}

// ================================================================
// UTILITY FUNCTIONS
// ================================================================
function escapeHtml(str) {
    if(!str) return '';
    return String(str).replace(/[&<>"]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        if(m === '"') return '&quot;';
        return m;
    });
}

// Initialize
$(document).ready(function() {
    updateSelectedCount();
    
    // Priority styling on change
    $('#priority').on('change', function() {
        let val = $(this).val();
        $(this).removeClass('priority-routine priority-urgent priority-stat');
        if(val === 'routine') $(this).addClass('priority-routine');
        else if(val === 'urgent') $(this).addClass('priority-urgent');
        else if(val === 'stat') $(this).addClass('priority-stat');
    });
});
</script>

</body>
</html>