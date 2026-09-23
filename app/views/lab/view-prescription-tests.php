<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .prescription-info-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        background: white;
    }
    .prescription-info-card .card-header {
        background: white;
        border-bottom: 2px solid #8b5cf6;
        border-radius: 12px 12px 0 0 !important;
        padding: 14px 20px;
        font-weight: 600;
    }
    .info-label {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .info-value {
        font-size: 14px;
        font-weight: 500;
        color: #1e293b;
    }
    .test-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 15px;
        margin-bottom: 8px;
        transition: all 0.2s;
    }
    .test-card:hover {
        background: #f8fafc;
        border-color: #3b82f6;
    }
    .test-card.selected {
        background: #dbeafe;
        border-color: #3b82f6;
    }
    .test-card .form-check-input {
        margin-top: 6px;
        cursor: pointer;
    }
    .test-card .test-name {
        font-weight: 500;
        font-size: 13px;
        color: #1e293b;
    }
    .test-card .test-price {
        font-weight: 600;
        font-size: 13px;
        color: #10b981;
    }
    .test-card .test-status {
        font-size: 11px;
        padding: 2px 10px;
        border-radius: 12px;
    }
    .test-card .test-status.available {
        background: #d1fae5;
        color: #065f46;
    }
    .test-card .test-status.unavailable {
        background: #fee2e2;
        color: #991b1b;
    }
    .test-card .test-details {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 2px;
    }
    .test-card .test-details span {
        margin-right: 12px;
    }
    
    .doctor-info-card {
        padding: 12px 16px;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
    }
    .doctor-info-card .doctor-name {
        font-weight: 600;
        color: #1e40af;
    }
    .doctor-info-card .doctor-specialization {
        font-size: 12px;
        color: #64748b;
    }
    
    .patient-info-card {
        padding: 12px 16px;
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
    }
    .patient-info-card .patient-name {
        font-weight: 600;
        color: #065f46;
    }
    .patient-info-card .patient-code {
        font-size: 12px;
        color: #64748b;
    }
    
    .summary-box {
        background: #f8fafc;
        border-radius: 10px;
        padding: 15px;
        border: 1px solid #e2e8f0;
    }
    .summary-box .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        font-size: 13px;
        border-bottom: 1px solid #e2e8f0;
    }
    .summary-box .summary-row:last-child {
        border-bottom: none;
    }
    .summary-box .summary-row.total {
        font-size: 18px;
        font-weight: 700;
        color: #10b981;
        border-top: 2px solid #10b981;
        padding-top: 10px;
        margin-top: 6px;
    }
    .summary-box .summary-row .label {
        color: #64748b;
    }
    
    .quick-amount-btn {
        padding: 4px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        background: white;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .quick-amount-btn:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    .quick-amount-btn.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .remove-test-btn {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 14px;
        padding: 0 4px;
        transition: all 0.2s;
    }
    .remove-test-btn:hover {
        color: #dc2626;
        transform: scale(1.2);
    }
</style>

<div class="container-fluid py-2">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-prescription-bottle text-primary me-2"></i>Prescription Lab Tests</h5>
            <p class="text-muted" style="font-size: 12px;">
                Prescription #<?php echo htmlspecialchars($prescription['prescription_number']); ?> | 
                Patient: <?php echo htmlspecialchars($prescription['patient_name']); ?>
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?php echo BASE_URL; ?>/lab/prescriptions" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <a href="<?php echo BASE_URL; ?>/lab/dashboard" class="btn btn-secondary btn-sm">
                <i class="fas fa-home"></i>
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($existingOrder)): ?>
        <!-- Existing Order Alert -->
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> 
            <strong>Lab order already created!</strong> 
            Order #: <?php echo htmlspecialchars($existingOrder['order_number']); ?> 
            (Status: <?php echo ucfirst(str_replace('_', ' ', $existingOrder['status'])); ?>)
            <a href="<?php echo BASE_URL; ?>/lab/view-order/<?php echo $existingOrder['id']; ?>" class="btn btn-sm btn-primary ms-2">
                <i class="fas fa-eye"></i> View Order
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Prescription Info Card -->
    <div class="card prescription-info-card mb-4">
        <div class="card-header">
            <i class="fas fa-prescription text-primary me-2"></i>Prescription Details
        </div>
        <div class="card-body">
            <div class="row g-4">
                <!-- Patient Info -->
                <div class="col-md-6">
                    <div class="patient-info-card">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-user-circle fa-2x text-success"></i>
                            </div>
                            <div>
                                <div class="info-label">Patient</div>
                                <div class="patient-name"><?php echo htmlspecialchars($prescription['patient_name']); ?></div>
                                <div class="patient-code"><?php echo htmlspecialchars($prescription['patient_code'] ?? ''); ?></div>
                                <div class="patient-code"><?php echo htmlspecialchars($prescription['phone'] ?? ''); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Doctor Info -->
                <div class="col-md-6">
                    <div class="doctor-info-card">
                        <div class="d-flex align-items-start gap-3">
                            <div class="flex-shrink-0">
                                <i class="fas fa-user-md fa-2x text-primary"></i>
                            </div>
                            <div>
                                <div class="info-label">Doctor</div>
                                <div class="doctor-name"><?php echo htmlspecialchars($prescription['doctor_name']); ?></div>
                                <div class="doctor-specialization"><?php echo htmlspecialchars($prescription['specialization'] ?? ''); ?></div>
                                <div class="doctor-specialization">BMDC: <?php echo htmlspecialchars($prescription['bmdc_number'] ?? ''); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Info -->
                <div class="col-12">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="info-label">Date</div>
                            <div class="info-value"><?php echo date('d/m/Y', strtotime($prescription['prescription_date'])); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="badge bg-<?php 
                                    $statusClass = 'secondary';
                                    if ($prescription['status'] == 'issued') $statusClass = 'primary';
                                    elseif ($prescription['status'] == 'dispensed') $statusClass = 'info';
                                    elseif ($prescription['status'] == 'completed') $statusClass = 'success';
                                    elseif ($prescription['status'] == 'canceled') $statusClass = 'danger';
                                ?>">
                                    <?php echo ucfirst($prescription['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Diagnosis</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($prescription['diagnosis'] ?? 'N/A')); ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($prescription['special_note'])): ?>
                <div class="col-12">
                    <div class="info-label">Special Note</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($prescription['special_note'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (empty($existingOrder)): ?>
    <!-- Create Order Form -->
    <form id="createOrderForm" method="POST" action="<?php echo BASE_URL; ?>/lab/create-order-from-prescription">
        <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
        
        <div class="row g-4">
            <!-- Left Column - Tests Selection -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-flask text-primary me-2"></i>Lab Tests from Prescription</h6>
                    </div>
                    <div class="card-body">
                        <!-- Order Info Row -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Order Date <span class="text-danger">*</span></label>
                                <input type="date" name="order_date" class="form-control form-control-sm" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Priority</label>
                                <select name="priority" class="form-select form-select-sm">
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="stat">STAT</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-muted">Doctor <span class="text-danger">*</span></label>
                                <select name="doctor_id" class="form-select form-select-sm" required>
                                    <option value="">Select Doctor</option>
                                    <?php foreach ($doctors as $doc): ?>
                                        <option value="<?php echo $doc['id']; ?>" <?php echo $doc['id'] == $prescription['doctor_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($doc['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold small text-muted">Clinical Diagnosis</label>
                                <input type="text" name="clinical_diagnosis" class="form-control form-control-sm" 
                                       placeholder="e.g., Fever, Cough..." value="<?php echo htmlspecialchars($prescription['diagnosis'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Lab Tests from Prescription -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Lab Tests from Prescription <span class="text-danger">*</span></label>
                            <div class="border rounded p-2" style="max-height: 350px; overflow-y: auto;">
                                <?php if (!empty($labTests)): ?>
                                    <?php foreach ($labTests as $test): 
                                        // Check if test exists in lab_tests
                                        $testExists = false;
                                        $testPrice = 0;
                                        $testId = $test['test_id'];
                                        $testName = $test['test_name'];
                                        
                                        if ($testId) {
                                            $checkResult = $this->db->query("SELECT id, price, test_name FROM lab_tests WHERE id = $testId AND status = 'active'");
                                            if ($checkResult && $checkResult->num_rows > 0) {
                                                $testExists = true;
                                                $testData = $checkResult->fetch_assoc();
                                                $testPrice = $testData['price'];
                                                $testName = $testData['test_name'];
                                            }
                                        }
                                    ?>
                                        <div class="test-card d-flex justify-content-between align-items-center <?php echo $testExists ? 'selected' : ''; ?>">
                                            <div class="d-flex align-items-start flex-grow-1">
                                                <input type="checkbox" name="test_ids[]" value="<?php echo $testId; ?>" 
                                                       class="form-check-input me-2 mt-1" 
                                                       <?php echo $testExists ? 'checked' : 'disabled'; ?>
                                                       data-price="<?php echo $testPrice; ?>"
                                                       data-name="<?php echo htmlspecialchars($testName); ?>"
                                                       onchange="updateSummary()">
                                                <div>
                                                    <div class="test-name"><?php echo htmlspecialchars($testName); ?></div>
                                                    <?php if ($testExists): ?>
                                                        <div class="test-details">
                                                            <span><i class="fas fa-tag"></i> Price: ₹ <?php echo number_format($testPrice, 2); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div>
                                                <?php if ($testExists): ?>
                                                    <span class="test-status available"><i class="fas fa-check-circle"></i> Available</span>
                                                <?php else: ?>
                                                    <span class="test-status unavailable"><i class="fas fa-times-circle"></i> Not Available</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center mb-0">No lab tests found in this prescription</p>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">Only available tests can be selected</small>
                        </div>

                        <!-- Additional Tests -->
                        <?php if (!empty($allTests)): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">
                                <i class="fas fa-plus-circle text-success me-1"></i>Add Additional Tests (Optional)
                            </label>
                            <div class="d-flex gap-2">
                                <select class="form-select form-select-sm" id="additionalTestSelect">
                                    <option value="">-- Select Additional Test --</option>
                                    <?php foreach ($allTests as $test): ?>
                                        <option value="<?php echo $test['id']; ?>" data-price="<?php echo $test['price']; ?>">
                                            <?php echo htmlspecialchars($test['test_name']); ?> (₹ <?php echo number_format($test['price'], 2); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="addAdditionalTest()">
                                    <i class="fas fa-plus"></i> Add Test
                                </button>
                            </div>
                            <div id="additionalTestsContainer" class="mt-2"></div>
                            <input type="hidden" name="additional_test_ids" id="additionalTestIds" value="">
                        </div>
                        <?php endif; ?>

                        <!-- Order Notes -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Order Notes <span class="text-muted">(Optional)</span></label>
                            <textarea name="notes" class="form-control form-control-sm" rows="2" 
                                      placeholder="Additional notes for this order..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-calculator text-primary me-2"></i>Order Summary</h6>
                    </div>
                    <div class="card-body">
                        <!-- Selected Tests Count -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Selected tests</span>
                                <span class="badge bg-primary" id="selectedCount">0</span>
                            </div>
                        </div>

                        <!-- Selected Tests List -->
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Selected Tests</label>
                            <div id="selectedTestsList" class="small" style="max-height: 200px; overflow-y: auto;">
                                <div class="text-muted text-center py-3" id="emptyTestsMessage">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p class="mb-0">No tests selected</p>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Summary - NO TAX, NO VAT -->
                        <div class="summary-box">
                            <div class="summary-row">
                                <span class="label">Subtotal</span>
                                <span id="subtotalDisplay">₹ 0.00</span>
                            </div>
                            <div class="summary-row total">
                                <span class="label">Total Amount</span>
                                <span id="totalDisplay">₹ 0.00</span>
                            </div>
                        </div>

                        <hr>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="createOrderBtn">
                                <i class="fas fa-plus me-2"></i> Create Order
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="window.location.href='<?php echo BASE_URL; ?>/lab/prescriptions'">
                                <i class="fas fa-times me-2"></i> Cancel
                            </button>
                        </div>

                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> A bill will be automatically generated
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php else: ?>
        <!-- Existing Order Display -->
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-check-circle me-2"></i>Order Already Created</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="info-label">Order Number</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($existingOrder['order_number']); ?></strong></div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="badge bg-<?php 
                                $statusClass = 'secondary';
                                if ($existingOrder['status'] == 'ordered') $statusClass = 'warning';
                                elseif ($existingOrder['status'] == 'sample_collected') $statusClass = 'info';
                                elseif ($existingOrder['status'] == 'processing') $statusClass = 'primary';
                                elseif ($existingOrder['status'] == 'completed') $statusClass = 'success';
                                elseif ($existingOrder['status'] == 'reviewed') $statusClass = 'dark';
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $existingOrder['status'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($existingOrder['order_date'])); ?></div>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <a href="<?php echo BASE_URL; ?>/lab/view-order/<?php echo $existingOrder['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-eye"></i> View Order
                    </a>
                    <a href="<?php echo BASE_URL; ?>/lab/enter-results" class="btn btn-success btn-sm">
                        <i class="fas fa-edit"></i> Enter Results
                    </a>
                    <a href="<?php echo BASE_URL; ?>/lab/prescriptions" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let additionalTests = [];
let selectedTests = [];

// ================================================================
// INITIALIZE SELECTED TESTS
// ================================================================
document.addEventListener('DOMContentLoaded', function() {
    // Get all initially checked tests
    document.querySelectorAll('input[name="test_ids[]"]:checked').forEach(el => {
        const price = parseFloat(el.dataset.price) || 0;
        const name = el.dataset.name || '';
        selectedTests.push({ id: el.value, name: name, price: price });
    });
    updateSummary();
});

// ================================================================
// TEST SELECTION HANDLING
// ================================================================
document.querySelectorAll('input[name="test_ids[]"]').forEach(el => {
    el.addEventListener('change', function() {
        const price = parseFloat(this.dataset.price) || 0;
        const name = this.dataset.name || '';
        if (this.checked) {
            selectedTests.push({ id: this.value, name: name, price: price });
            this.closest('.test-card').classList.add('selected');
        } else {
            selectedTests = selectedTests.filter(t => t.id != this.value);
            this.closest('.test-card').classList.remove('selected');
        }
        updateSummary();
    });
});

// ================================================================
// ADD ADDITIONAL TESTS
// ================================================================
function addAdditionalTest() {
    const select = document.getElementById('additionalTestSelect');
    const testId = select.value;
    const testName = select.options[select.selectedIndex]?.text || '';
    const price = parseFloat(select.dataset.price) || 0;
    
    if (!testId) {
        alert('Please select a test');
        return;
    }
    
    // Check if already added
    if (additionalTests.some(t => t.id == testId) || selectedTests.some(t => t.id == testId)) {
        alert('Test already added');
        return;
    }
    
    additionalTests.push({ id: testId, name: testName, price: price });
    selectedTests.push({ id: testId, name: testName, price: price });
    renderAdditionalTests();
    updateSummary();
    select.value = '';
}

function renderAdditionalTests() {
    const container = document.getElementById('additionalTestsContainer');
    if (additionalTests.length === 0) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="list-group mt-2">';
    additionalTests.forEach((test, index) => {
        html += `
            <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-1 px-2">
                <span><i class="fas fa-plus-circle text-success me-1"></i> ${test.name}</span>
                <div>
                    <span class="badge bg-secondary me-2">₹ ${test.price.toFixed(2)}</span>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeAdditionalTest(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
    document.getElementById('additionalTestIds').value = JSON.stringify(additionalTests.map(t => t.id));
}

function removeAdditionalTest(index) {
    const test = additionalTests[index];
    selectedTests = selectedTests.filter(t => t.id != test.id);
    additionalTests.splice(index, 1);
    renderAdditionalTests();
    updateSummary();
}

// ================================================================
// UPDATE SUMMARY - NO TAX, JUST TOTAL
// ================================================================
function getTotal() {
    let total = 0;
    selectedTests.forEach(test => {
        total += test.price;
    });
    return total;
}

function updateSummary() {
    const total = getTotal();
    
    document.getElementById('selectedCount').textContent = selectedTests.length;
    document.getElementById('subtotalDisplay').textContent = '₹ ' + total.toFixed(2);
    document.getElementById('totalDisplay').textContent = '₹ ' + total.toFixed(2);
    
    // Update selected tests list
    updateSelectedTestsList();
}

function updateSelectedTestsList() {
    const container = document.getElementById('selectedTestsList');
    
    if (selectedTests.length === 0) {
        container.innerHTML = `
            <div class="text-muted text-center py-3" id="emptyTestsMessage">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p class="mb-0">No tests selected</p>
            </div>
        `;
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    selectedTests.forEach((test, index) => {
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                <span style="font-size: 12px;">${escapeHtml(test.name)}</span>
                <div>
                    <span class="text-success" style="font-size: 12px;">₹ ${test.price.toFixed(2)}</span>
                    <button type="button" class="remove-test-btn" onclick="removeTest('${test.id}')" title="Remove test">
                        <i class="fas fa-times-circle"></i>
                    </button>
                </div>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function removeTest(testId) {
    // Remove from selectedTests
    selectedTests = selectedTests.filter(t => t.id != testId);
    
    // Uncheck checkbox if it exists
    const checkbox = document.querySelector(`input[name="test_ids[]"][value="${testId}"]`);
    if (checkbox) {
        checkbox.checked = false;
        checkbox.closest('.test-card').classList.remove('selected');
    }
    
    // Remove from additional tests
    additionalTests = additionalTests.filter(t => t.id != testId);
    renderAdditionalTests();
    
    updateSummary();
}

// ================================================================
// UTILITY FUNCTIONS
// ================================================================
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        if (m === '"') return '&quot;';
        return m;
    });
}

// ================================================================
// FORM SUBMISSION - REDIRECT TO ORDERS PAGE
// ================================================================
document.getElementById('createOrderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get all selected test IDs (from checkboxes + additional)
    const checkboxTests = document.querySelectorAll('input[name="test_ids[]"]:checked');
    const testIds = [];
    checkboxTests.forEach(el => testIds.push(el.value));
    additionalTests.forEach(t => testIds.push(t.id));
    
    if (testIds.length === 0) {
        alert('❌ Please select at least one test');
        return;
    }
    
    // Create hidden input for test_ids
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'test_ids';
    hiddenInput.value = JSON.stringify(testIds);
    this.appendChild(hiddenInput);
    
    // Calculate total (NO TAX)
    const total = getTotal();
    const totalInput = document.createElement('input');
    totalInput.type = 'hidden';
    totalInput.name = 'total_amount';
    totalInput.value = total;
    this.appendChild(totalInput);
    
    const subtotalInput = document.createElement('input');
    subtotalInput.type = 'hidden';
    subtotalInput.name = 'subtotal';
    subtotalInput.value = total;
    this.appendChild(subtotalInput);
    
    // Add payment method (default cash)
    const paymentMethodInput = document.createElement('input');
    paymentMethodInput.type = 'hidden';
    paymentMethodInput.name = 'payment_method';
    paymentMethodInput.value = 'cash';
    this.appendChild(paymentMethodInput);
    
    // Add paid amount (0 for full bill later)
    const paidInput = document.createElement('input');
    paidInput.type = 'hidden';
    paidInput.name = 'paid_amount';
    paidInput.value = '0';
    this.appendChild(paidInput);
    
    const btn = document.getElementById('createOrderBtn');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Creating...';
    btn.disabled = true;
    
    // Submit the form via AJAX
    const formData = new FormData(this);
    
    fetch(BASE_URL + '/lab/create-order-from-prescription', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Order created successfully!\nOrder #: ' + data.order_number);
            // Redirect to orders page
            window.location.href = BASE_URL + '/lab/orders';
        } else {
            alert('❌ Error: ' + data.message);
            btn.innerHTML = '<i class="fas fa-plus me-2"></i> Create Order';
            btn.disabled = false;
        }
    })
    .catch(error => {
        alert('❌ Error: ' + error.message);
        btn.innerHTML = '<i class="fas fa-plus me-2"></i> Create Order';
        btn.disabled = false;
    });
});

// Initialize
updateSummary();
</script>