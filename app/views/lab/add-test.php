<?php
// app/views/lab/add-test.php - Add New Lab Test Page
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Lab Test - UniDia HMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        .card-custom { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header-custom { background: white; border-bottom: 2px solid #3b82f6; padding: 10px 15px; font-weight: 600; font-size: 14px; }
        .form-group { margin-bottom: 14px; }
        .form-group label { font-size: 12px; font-weight: 600; color: #475569; display: block; margin-bottom: 3px; }
        .form-group label .required { color: #ef4444; }
        .form-group .form-control { border-radius: 6px; border: 1px solid #e2e8f0; padding: 6px 12px; font-size: 13px; width: 100%; }
        .form-group .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); outline: none; }
        .form-group .form-control[readonly] { background: #f8fafc; }
        .form-group textarea.form-control { min-height: 60px; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
        .toast-container { position: fixed; top: 20px; right: 20px; z-index: 10000; }
        .toast { padding: 12px 20px; border-radius: 8px; margin-bottom: 8px; min-width: 250px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); animation: toastSlideIn 0.3s ease; color: white; font-size: 13px; }
        .toast-success { background: #10b981; }
        .toast-error { background: #ef4444; }
        .toast-warning { background: #f59e0b; }
        .toast-info { background: #3b82f6; }
        @keyframes toastSlideIn { from { transform: translateX(100px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        .section-divider { border-top: 2px solid #e5e7eb; margin: 16px 0; }
        .section-subtitle { font-size: 13px; font-weight: 600; color: #1e293b; margin: 10px 0 6px 0; display: flex; align-items: center; gap: 8px; }
        .badge-count-sm { background: #e2e8f0; color: #475569; padding: 1px 8px; border-radius: 10px; font-size: 10px; font-weight: 500; }
        .selected-pill { background: #e2e8f0; color: #475569; padding: 2px 10px; border-radius: 12px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px; }
        .selected-pill .remove-btn { background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 10px; padding: 0 2px; }
        .selected-pill .remove-btn:hover { color: #dc2626; }
        .selected-pill-primary { background: #dbeafe; color: #2563eb; }
        .selected-pill-required { background: #fef3c7; color: #92400e; }
        .selected-items-list { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; min-height: 30px; padding: 4px 0; }
        .selection-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 6px; }
        .selection-row select { flex: 1; min-width: 150px; padding: 4px 8px; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 12px; background: white; height: 32px; }
        .selection-row input[type="number"] { width: 60px; padding: 4px 6px; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 12px; }
        .selection-row input[type="text"] { width: 120px; padding: 4px 6px; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 12px; }
        .btn-add-selection { background: #dbeafe; color: #2563eb; border: none; padding: 4px 12px; border-radius: 4px; font-size: 11px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px; }
        .btn-add-selection:hover { background: #2563eb; color: white; }
        .form-check-input { margin-top: 2px; }
        .form-check-label { font-size: 13px; }
        .btn-primary { background: #3b82f6; border: none; padding: 8px 20px; border-radius: 6px; color: white; cursor: pointer; }
        .btn-primary:hover { background: #2563eb; }
        .btn-secondary { background: #e5e7eb; border: none; padding: 8px 20px; border-radius: 6px; color: #374151; text-decoration: none; display: inline-block; }
        .btn-secondary:hover { background: #d1d5db; color: #374151; text-decoration: none; }
        .btn-sm { padding: 4px 12px; font-size: 12px; }
        .alert { border-radius: 6px; padding: 8px 12px; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert .btn-close { font-size: 10px; padding: 4px; }
    </style>
</head>
<body>
    <div class="container-fluid py-2">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color:#1f2937;margin:0;"><i class="fas fa-plus-circle text-success me-2"></i> Add New Lab Test</h5>
                <p class="text-muted" style="font-size:11px;">Create a new laboratory test definition</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/lab/manage-tests" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Tests
                </a>
                <a href="<?php echo BASE_URL; ?>/lab/dashboard" class="btn btn-secondary btn-sm">
                    <i class="fas fa-home"></i>
                </a>
            </div>
        </div>

        <!-- ALERTS -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2" style="font-size:13px;">
                <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:10px;"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show py-2" style="font-size:13px;">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size:10px;"></button>
            </div>
        <?php endif; ?>

        <!-- MAIN FORM -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-flask text-primary"></i> Test Details
            </div>
            <div class="card-body p-3">
                <form id="testForm" method="POST" action="<?php echo BASE_URL; ?>/lab/api/save-test">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="instruments_data" id="instrumentsData" value="">
                    <input type="hidden" name="accessories_data" id="accessoriesData" value="">
                    <input type="hidden" name="primary_instrument_id" id="primaryInstrumentHidden" value="">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Test Name <span class="required">*</span></label>
                            <input type="text" name="test_name" id="testName" class="form-control" placeholder="e.g. Complete Blood Count" required>
                        </div>
                        <div class="form-group">
                            <label>Test Code</label>
                            <input type="text" name="test_code" id="testCode" class="form-control" placeholder="e.g. CBC001" readonly>
                            <small class="text-muted">Auto-generated if left blank</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Category <span class="required">*</span></label>
                            <select name="category_id" id="testCategory" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Price (BDT) <span class="required">*</span></label>
                            <input type="number" name="price" id="testPrice" class="form-control" placeholder="e.g. 500" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Specimen Type</label>
                            <input type="text" name="specimen_type" id="testSpecimen" class="form-control" placeholder="e.g. Blood, Urine, Serum">
                        </div>
                        <div class="form-group">
                            <label>Container Type</label>
                            <input type="text" name="container_type" id="testContainer" class="form-control" placeholder="e.g. EDTA Tube, Plain Tube">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Normal Range</label>
                            <input type="text" name="normal_range" id="testNormalRange" class="form-control" placeholder="e.g. 4.5-11.0">
                        </div>
                        <div class="form-group">
                            <label>Unit</label>
                            <input type="text" name="unit" id="testUnit" class="form-control" placeholder="e.g. cells/uL, mg/dL">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Turnaround Time (hours)</label>
                            <input type="number" name="turnaround_time" id="testTurnaround" class="form-control" placeholder="e.g. 24">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="testStatus" class="form-control">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" id="testDescription" class="form-control" placeholder="Enter test description..." rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Preparation Instructions</label>
                        <textarea name="preparation_instructions" id="testPrepInstructions" class="form-control" placeholder="e.g. Fasting required for 8-12 hours" rows="2"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_package" id="testIsPackage" class="form-check-input" value="1">
                                <label class="form-check-label" for="testIsPackage">This is a package test</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="requires_fasting" id="testRequiresFasting" class="form-check-input" value="1">
                                <label class="form-check-label" for="testRequiresFasting">Requires fasting</label>
                            </div>
                        </div>
                    </div>

                    <!-- INSTRUMENTS SECTION -->
                    <div class="section-divider"></div>
                    <div class="section-subtitle">
                        <i class="fas fa-microscope text-primary"></i> Instruments
                        <span class="badge-count-sm">Select instruments used for this test</span>
                    </div>
                    
                    <div class="selection-row">
                        <select id="instrumentSelect" class="form-control" style="flex:1;min-width:150px;height:32px;">
                            <option value="">-- Select Instrument --</option>
                        </select>
                        <button type="button" class="btn-add-selection" id="addInstrumentBtn">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    
                    <div id="selectedInstruments" class="selected-items-list">
                        <span class="text-muted" style="font-size:11px;">No instruments selected</span>
                    </div>

                    <!-- ACCESSORIES SECTION -->
                    <div class="section-divider"></div>
                    <div class="section-subtitle">
                        <i class="fas fa-tools text-primary"></i> Accessories
                        <span class="badge-count-sm">Select accessories required for this test</span>
                    </div>
                    
                    <div class="selection-row">
                        <select id="accessorySelect" class="form-control" style="flex:1;min-width:150px;height:32px;">
                            <option value="">-- Select Accessory --</option>
                        </select>
                        <input type="number" id="accessoryQty" value="1" min="1" style="width:60px;padding:4px 6px;border-radius:4px;border:1px solid #e2e8f0;font-size:12px;">
                        <label style="font-size:11px;margin:0;display:flex;align-items:center;gap:3px;">
                            <input type="checkbox" id="accessoryRequired" checked> Required
                        </label>
                        <input type="text" id="accessoryNotes" placeholder="Notes" style="width:120px;padding:4px 6px;border-radius:4px;border:1px solid #e2e8f0;font-size:12px;">
                        <button type="button" class="btn-add-selection" id="addAccessoryBtn">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                    
                    <div id="selectedAccessories" class="selected-items-list">
                        <span class="text-muted" style="font-size:11px;">No accessories selected</span>
                    </div>

                    <div class="d-flex gap-2 mt-3 pt-2 border-top">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Save Test
                        </button>
                        <a href="<?php echo BASE_URL; ?>/lab/manage-tests" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TOAST CONTAINER -->
    <div class="toast-container" id="toastContainer"></div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    (function() {
        // ================================================================
        // CONFIGURATION
        // ================================================================
        var BASE_URL = '<?php echo BASE_URL; ?>';
        
        console.log('Add Test Page initialized');
        
        // ================================================================
        // STATE
        // ================================================================
        var allInstruments = [];
        var allAccessories = [];
        var selectedInstruments = [];
        var selectedAccessories = [];
        var primaryInstrumentId = null;

        // ================================================================
        // TOAST NOTIFICATION
        // ================================================================
        function showToast(message, type) {
            type = type || 'success';
            var container = document.getElementById('toastContainer');
            if (!container) return;
            var toast = document.createElement('div');
            toast.className = 'toast toast-' + type;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(50px)';
                setTimeout(function() { toast.remove(); }, 300);
            }, 3000);
        }

        // ================================================================
        // LOAD DROPDOWN DATA
        // ================================================================
        function loadDropdownData() {
            console.log('Loading dropdown data for add page...');
            
            // Load instruments
            $.ajax({
                url: BASE_URL + '/lab/api/instruments',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    allInstruments = response || [];
                    populateInstrumentDropdown();
                    console.log('Instruments loaded:', allInstruments.length);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading instruments:', status, error);
                    showToast('Error loading instruments', 'error');
                }
            });

            // Load accessories
            $.ajax({
                url: BASE_URL + '/lab/api/accessories',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    allAccessories = response || [];
                    populateAccessoryDropdown();
                    console.log('Accessories loaded:', allAccessories.length);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading accessories:', status, error);
                    showToast('Error loading accessories', 'error');
                }
            });
        }

        // ================================================================
        // POPULATE DROPDOWNS
        // ================================================================
        function populateInstrumentDropdown() {
            var select = document.getElementById('instrumentSelect');
            if (!select) return;
            select.innerHTML = '<option value="">-- Select Instrument --</option>';
            for (var i = 0; i < allInstruments.length; i++) {
                var inst = allInstruments[i];
                var found = false;
                for (var j = 0; j < selectedInstruments.length; j++) {
                    if (selectedInstruments[j].id == inst.id) {
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    var option = document.createElement('option');
                    option.value = inst.id;
                    option.textContent = inst.instrument_name + ' (' + inst.instrument_code + ')';
                    select.appendChild(option);
                }
            }
        }

        function populateAccessoryDropdown() {
            var select = document.getElementById('accessorySelect');
            if (!select) return;
            select.innerHTML = '<option value="">-- Select Accessory --</option>';
            for (var i = 0; i < allAccessories.length; i++) {
                var acc = allAccessories[i];
                var found = false;
                for (var j = 0; j < selectedAccessories.length; j++) {
                    if (selectedAccessories[j].id == acc.id) {
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    var option = document.createElement('option');
                    option.value = acc.id;
                    option.textContent = acc.accessory_name + ' (' + acc.accessory_code + ')';
                    select.appendChild(option);
                }
            }
        }

        // ================================================================
        // ADD INSTRUMENT
        // ================================================================
        document.getElementById('addInstrumentBtn').addEventListener('click', function() {
            var select = document.getElementById('instrumentSelect');
            var instrumentId = parseInt(select.value);
            if (!instrumentId) {
                showToast('Please select an instrument', 'warning');
                return;
            }
            
            var instrument = null;
            for (var i = 0; i < allInstruments.length; i++) {
                if (allInstruments[i].id == instrumentId) {
                    instrument = allInstruments[i];
                    break;
                }
            }
            if (!instrument) {
                showToast('Instrument not found', 'error');
                return;
            }
            
            // Check if already added
            for (var i = 0; i < selectedInstruments.length; i++) {
                if (selectedInstruments[i].id == instrumentId) {
                    showToast('Instrument already added', 'warning');
                    return;
                }
            }
            
            selectedInstruments.push({
                id: instrument.id,
                name: instrument.instrument_name,
                code: instrument.instrument_code,
                is_primary: selectedInstruments.length === 0
            });
            
            if (selectedInstruments.length === 1) {
                primaryInstrumentId = instrument.id;
            }
            
            renderSelectedInstruments();
            populateInstrumentDropdown();
            updateHiddenFields();
            document.getElementById('instrumentSelect').value = '';
            showToast('Instrument added successfully', 'success');
        });

        // ================================================================
        // REMOVE INSTRUMENT
        // ================================================================
        window.removeInstrument = function(id) {
            var newList = [];
            for (var i = 0; i < selectedInstruments.length; i++) {
                if (selectedInstruments[i].id != id) {
                    newList.push(selectedInstruments[i]);
                }
            }
            selectedInstruments = newList;
            
            if (primaryInstrumentId == id) {
                primaryInstrumentId = selectedInstruments.length > 0 ? selectedInstruments[0].id : null;
                if (selectedInstruments.length > 0) {
                    selectedInstruments[0].is_primary = true;
                }
            }
            
            renderSelectedInstruments();
            populateInstrumentDropdown();
            updateHiddenFields();
        };

        // ================================================================
        // SET PRIMARY INSTRUMENT
        // ================================================================
        window.setPrimaryInstrument = function(id) {
            primaryInstrumentId = id;
            for (var i = 0; i < selectedInstruments.length; i++) {
                selectedInstruments[i].is_primary = (selectedInstruments[i].id == id);
            }
            renderSelectedInstruments();
            updateHiddenFields();
        };

        // ================================================================
        // ADD ACCESSORY
        // ================================================================
        document.getElementById('addAccessoryBtn').addEventListener('click', function() {
            var select = document.getElementById('accessorySelect');
            var accessoryId = parseInt(select.value);
            if (!accessoryId) {
                showToast('Please select an accessory', 'warning');
                return;
            }
            
            var qty = parseInt(document.getElementById('accessoryQty').value) || 1;
            var isRequired = document.getElementById('accessoryRequired').checked;
            var notes = document.getElementById('accessoryNotes').value.trim();
            
            var accessory = null;
            for (var i = 0; i < allAccessories.length; i++) {
                if (allAccessories[i].id == accessoryId) {
                    accessory = allAccessories[i];
                    break;
                }
            }
            if (!accessory) {
                showToast('Accessory not found', 'error');
                return;
            }
            
            // Check if already added
            for (var i = 0; i < selectedAccessories.length; i++) {
                if (selectedAccessories[i].id == accessoryId) {
                    showToast('Accessory already added', 'warning');
                    return;
                }
            }
            
            selectedAccessories.push({
                id: accessory.id,
                name: accessory.accessory_name,
                code: accessory.accessory_code,
                quantity: qty,
                is_required: isRequired,
                notes: notes
            });
            
            renderSelectedAccessories();
            populateAccessoryDropdown();
            updateHiddenFields();
            
            document.getElementById('accessoryQty').value = 1;
            document.getElementById('accessoryRequired').checked = true;
            document.getElementById('accessoryNotes').value = '';
            document.getElementById('accessorySelect').value = '';
            showToast('Accessory added successfully', 'success');
        });

        // ================================================================
        // REMOVE ACCESSORY
        // ================================================================
        window.removeAccessory = function(id) {
            var newList = [];
            for (var i = 0; i < selectedAccessories.length; i++) {
                if (selectedAccessories[i].id != id) {
                    newList.push(selectedAccessories[i]);
                }
            }
            selectedAccessories = newList;
            renderSelectedAccessories();
            populateAccessoryDropdown();
            updateHiddenFields();
        };

        // ================================================================
        // RENDER SELECTED ITEMS
        // ================================================================
        function renderSelectedInstruments() {
            var container = document.getElementById('selectedInstruments');
            if (!container) return;
            
            if (selectedInstruments.length === 0) {
                container.innerHTML = '<span class="text-muted" style="font-size:11px;">No instruments selected</span>';
                return;
            }
            
            var html = '';
            for (var i = 0; i < selectedInstruments.length; i++) {
                var inst = selectedInstruments[i];
                var primaryClass = inst.is_primary ? ' selected-pill-primary' : '';
                var primaryLabel = inst.is_primary ? ' ★' : '';
                html += '<span class="selected-pill' + primaryClass + '">';
                html += '<i class="fas fa-microscope"></i> ' + inst.name + ' (' + inst.code + ')' + primaryLabel;
                html += '<button type="button" class="remove-btn" onclick="removeInstrument(' + inst.id + ')" title="Remove"><i class="fas fa-times"></i></button>';
                if (!inst.is_primary) {
                    html += '<button type="button" class="remove-btn" onclick="setPrimaryInstrument(' + inst.id + ')" title="Set as Primary" style="color:#3b82f6;"><i class="fas fa-star"></i></button>';
                }
                html += '</span>';
            }
            container.innerHTML = html;
        }

        function renderSelectedAccessories() {
            var container = document.getElementById('selectedAccessories');
            if (!container) return;
            
            if (selectedAccessories.length === 0) {
                container.innerHTML = '<span class="text-muted" style="font-size:11px;">No accessories selected</span>';
                return;
            }
            
            var html = '';
            for (var i = 0; i < selectedAccessories.length; i++) {
                var acc = selectedAccessories[i];
                var requiredClass = acc.is_required ? ' selected-pill-required' : '';
                html += '<span class="selected-pill' + requiredClass + '">';
                html += '<i class="fas fa-tools"></i> ' + acc.name + ' (' + acc.code + ')';
                html += '<span style="font-size:10px;color:#64748b;">x' + acc.quantity + ' ' + (acc.is_required ? 'Required' : 'Optional') + (acc.notes ? ' | ' + acc.notes : '') + '</span>';
                html += '<button type="button" class="remove-btn" onclick="removeAccessory(' + acc.id + ')" title="Remove"><i class="fas fa-times"></i></button>';
                html += '</span>';
            }
            container.innerHTML = html;
        }

        // ================================================================
        // UPDATE HIDDEN FIELDS
        // ================================================================
        function updateHiddenFields() {
            document.getElementById('instrumentsData').value = JSON.stringify(selectedInstruments);
            document.getElementById('primaryInstrumentHidden').value = primaryInstrumentId || '';
            document.getElementById('accessoriesData').value = JSON.stringify(selectedAccessories);
            
            console.log('Hidden fields updated - Instruments:', selectedInstruments.length, 'Accessories:', selectedAccessories.length);
        }

        // ================================================================
        // FORM SUBMIT
        // ================================================================
        document.getElementById('testForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Update hidden fields before submit
            updateHiddenFields();
            
            console.log('Submitting form for new test');
            console.log('Selected instruments:', selectedInstruments.length);
            console.log('Selected accessories:', selectedAccessories.length);
            
            var formData = new FormData(this);
            
            // Log form data for debugging
            console.log('Form data entries:');
            for (var pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }
            
            // Disable submit button to prevent double submission
            var submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            $.ajax({
                url: BASE_URL + '/lab/api/save-test',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    console.log('Submit response:', response);
                    if (response.success) {
                        showToast(response.message || 'Test saved successfully', 'success');
                        setTimeout(function() {
                            window.location.href = BASE_URL + '/lab/manage-tests';
                        }, 1000);
                    } else {
                        showToast(response.message || 'Error saving test', 'error');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Test';
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response Text:', xhr.responseText);
                    var msg = 'Error saving test. Please try again.';
                    try { 
                        var json = JSON.parse(xhr.responseText); 
                        if (json.message) msg = json.message; 
                    } catch (e) {}
                    showToast(msg, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Save Test';
                }
            });
        });

        // ================================================================
        // INITIALIZE ON PAGE LOAD
        // ================================================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing add page');
            loadDropdownData();
            
            // Auto-generate test code when category is selected
            document.getElementById('testCategory').addEventListener('change', function() {
                var codeField = document.getElementById('testCode');
                if (!codeField.value) {
                    var categoryName = this.options[this.selectedIndex]?.text || '';
                    var prefix = categoryName.substring(0, 3).toUpperCase() || 'TST';
                    var date = new Date();
                    var dateStr = date.getFullYear() + 
                                  String(date.getMonth() + 1).padStart(2, '0') + 
                                  String(date.getDate()).padStart(2, '0');
                    var random = Math.floor(Math.random() * 900) + 100;
                    codeField.value = prefix + dateStr + random;
                }
            });
            
            // Also auto-generate on name change if code is empty
            document.getElementById('testName').addEventListener('input', function() {
                var codeField = document.getElementById('testCode');
                if (!codeField.value && this.value.length > 2) {
                    var prefix = this.value.substring(0, 3).toUpperCase();
                    var date = new Date();
                    var dateStr = date.getFullYear() + 
                                  String(date.getMonth() + 1).padStart(2, '0') + 
                                  String(date.getDate()).padStart(2, '0');
                    var random = Math.floor(Math.random() * 900) + 100;
                    codeField.value = prefix + dateStr + random;
                }
            });
        });
    })();
    </script>
</body>
</html>