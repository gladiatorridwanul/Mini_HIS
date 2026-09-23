<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Item - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; }
        
        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .form-header {
            padding: 18px 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        .form-header h6 { margin: 0; font-weight: 600; font-size: 16px; }
        .form-body { padding: 24px; }
        
        .form-section {
            background: #fafbfc;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px solid #e9ecef;
        }
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 18px;
            padding-bottom: 10px;
            border-bottom: 2px solid #10b981;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title i { color: #10b981; font-size: 16px; }
        
        .form-label { font-size: 12px; font-weight: 600; color: #4b5563; margin-bottom: 6px; }
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            font-size: 13px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
        }
        .required-field::after { content: " *"; color: #ef4444; }
        
        .type-preview {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 10px 15px;
            margin-bottom: 20px;
            display: none;
        }
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        .type-medicine { background: #d1fae5; color: #065f46; }
        .type-lab_test { background: #f3e8ff; color: #6b21a5; }
        .type-equipment { background: #fed7aa; color: #9a3412; }
        .type-other { background: #e2e8f0; color: #475569; }
        
        .action-buttons { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; }
        .btn-cancel { background: white; border: 1px solid #e2e8f0; color: #64748b; padding: 10px 24px; border-radius: 10px; }
        .btn-cancel:hover { background: #f1f5f9; }
        .btn-submit { background: #10b981; border: none; color: white; padding: 10px 28px; border-radius: 10px; font-weight: 600; }
        .btn-submit:hover { background: #059669; }
        
        .info-box {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-plus-circle text-success me-2"></i>Add New Item</h1>
            <p class="page-subtitle">Add medicine, lab test, equipment or other items to inventory</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/inventory/items" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Items
            </a>
        </div>
    </div>

    <div class="form-card">
        <div class="form-header">
            <h6><i class="fas fa-info-circle me-2"></i>Item Information</h6>
        </div>
        <div class="form-body">
            <form id="addItemForm">
                <!-- Item Type Selection -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-tag"></i>
                        <span>Item Type</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label required-field">Select Item Type</label>
                            <select name="item_type" id="itemType" class="form-select" required onchange="toggleItemTypeFields()">
                                <option value="">-- Select Type --</option>
                                <option value="medicine">💊 Medicine</option>
                                <option value="lab_test">🔬 Lab Test</option>
                                <option value="equipment">🔧 Equipment / Accessory</option>
                                <option value="other">📋 Other / Consumable</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <div id="typePreview" class="type-preview">
                                <span id="typeBadge" class="type-badge"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Common Basic Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        <span>Basic Information</span>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label required-field">Item Name</label>
                            <input type="text" name="item_name" id="itemName" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Generic Name / Description</label>
                            <input type="text" name="generic_name" id="genericName" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Medicine Specific Fields -->
                <div id="medicineFields" class="form-section" style="display: none;">
                    <div class="section-title">
                        <i class="fas fa-pills"></i>
                        <span>Medicine Information</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Category</label>
                            <select name="med_category_id" id="medCategory" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach($medCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" id="medSupplier" class="form-select">
                                <option value="">Select Supplier</option>
                                <?php foreach($medSuppliers as $sup): ?>
                                <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['company_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Manufacturer</label>
                            <input type="text" name="manufacturer" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Strength</label>
                            <input type="text" name="strength" class="form-control" placeholder="e.g., 500mg">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Dosage Form</label>
                            <select name="dosage_form" class="form-select">
                                <option value="Tablet">Tablet</option>
                                <option value="Capsule">Capsule</option>
                                <option value="Syrup">Syrup</option>
                                <option value="Injection">Injection</option>
                                <option value="Cream">Cream</option>
                                <option value="Drops">Drops</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unit of Measure</label>
                            <select name="unit_of_measure" class="form-select">
                                <option value="Strip">Strip</option>
                                <option value="Box">Box</option>
                                <option value="Bottle">Bottle</option>
                                <option value="Piece">Piece</option>
                                <option value="Tube">Tube</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="requires_prescription" class="form-check-input" value="1" id="requiresPrescription">
                                <label class="form-check-label" for="requiresPrescription">Requires Prescription</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lab Test Specific Fields -->
                <div id="labTestFields" class="form-section" style="display: none;">
                    <div class="section-title">
                        <i class="fas fa-microscope"></i>
                        <span>Lab Test Information</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Category</label>
                            <select name="lab_category_id" id="labCategory" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach($labCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" id="labSupplier" class="form-select">
                                <option value="">Select Supplier</option>
                                <?php foreach($labSuppliers as $sup): ?>
                                <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['company_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Test Code</label>
                            <input type="text" name="test_code" class="form-control" placeholder="e.g., CBC001">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Specimen Type</label>
                            <select name="specimen_type" class="form-select">
                                <option value="">Select</option>
                                <option value="Blood">Blood</option>
                                <option value="Urine">Urine</option>
                                <option value="Stool">Stool</option>
                                <option value="Tissue">Tissue</option>
                                <option value="Swab">Swab</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Turnaround Time (hours)</label>
                            <input type="number" name="turnaround_time" class="form-control" value="24">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unit of Measure</label>
                            <select name="unit_of_measure" class="form-select">
                                <option value="Test">Test</option>
                                <option value="Kit">Kit</option>
                                <option value="Panel">Panel</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Normal Range</label>
                            <input type="text" name="normal_range" class="form-control" placeholder="e.g., 4.5-11.0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unit (Measurement)</label>
                            <input type="text" name="unit" class="form-control" placeholder="e.g., g/dL, mg/dL">
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="requires_fasting" class="form-check-input" value="1">
                                <label class="form-check-label">Requires Fasting</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Equipment / Accessory Fields -->
                <div id="equipmentFields" class="form-section" style="display: none;">
                    <div class="section-title">
                        <i class="fas fa-tools"></i>
                        <span>Equipment / Accessory Information</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Category</label>
                            <select name="inv_category_id" id="invCategory" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach($invCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" id="invSupplier" class="form-select">
                                <option value="">Select Supplier</option>
                                <?php foreach($invSuppliers as $sup): ?>
                                <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['company_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Brand / Model</label>
                            <input type="text" name="brand" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unit of Measure</label>
                            <select name="unit_of_measure" class="form-select">
                                <option value="Piece">Piece</option>
                                <option value="Box">Box</option>
                                <option value="Set">Set</option>
                                <option value="Kit">Kit</option>
                                <option value="Unit">Unit</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pack Size</label>
                            <input type="text" name="pack_size" class="form-control" placeholder="e.g., 10 pcs">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Warranty (months)</label>
                            <input type="number" name="warranty" class="form-control" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- Other / Consumable Fields -->
                <div id="otherFields" class="form-section" style="display: none;">
                    <div class="section-title">
                        <i class="fas fa-box"></i>
                        <span>Other / Consumable Information</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Category</label>
                            <select name="inv_category_id" id="invCategoryOther" class="form-select">
                                <option value="">Select Category</option>
                                <?php foreach($invCategories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" id="invSupplierOther" class="form-select">
                                <option value="">Select Supplier</option>
                                <?php foreach($invSuppliers as $sup): ?>
                                <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['company_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Unit of Measure</label>
                            <select name="unit_of_measure" class="form-select">
                                <option value="Piece">Piece</option>
                                <option value="Box">Box</option>
                                <option value="Roll">Roll</option>
                                <option value="Pack">Pack</option>
                                <option value="Bottle">Bottle</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pack Size</label>
                            <input type="text" name="pack_size" class="form-control" placeholder="e.g., 100 pcs">
                        </div>
                    </div>
                </div>

                <!-- Pricing Section (Common for all types) -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-chart-line"></i>
                        <span>Pricing Information</span>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label required-field">Purchase Price (৳)</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label required-field">Selling Price (৳)</label>
                            <input type="number" step="0.01" name="selling_price" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">MRP (৳)</label>
                            <input type="number" step="0.01" name="mrp" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tax Percentage (%)</label>
                            <input type="number" step="0.01" name="tax_percentage" class="form-control" value="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control" value="10">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Reorder Quantity</label>
                            <input type="number" name="reorder_quantity" class="form-control" value="50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Min Stock Level</label>
                            <input type="number" name="min_stock_level" class="form-control" value="5">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Max Stock Level</label>
                            <input type="number" name="max_stock_level" class="form-control" value="100">
                        </div>
                    </div>
                </div>

                <!-- Storage Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-warehouse"></i>
                        <span>Storage Information</span>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Storage Location</label>
                            <input type="text" name="storage_location" class="form-control" placeholder="e.g., Shelf A-1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Storage Condition</label>
                            <select name="storage_condition" class="form-select">
                                <option value="Room temperature">Room temperature</option>
                                <option value="Refrigerated">Refrigerated (2-8°C)</option>
                                <option value="Cool dry place">Cool dry place</option>
                                <option value="Protected from light">Protected from light</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="requires_refrigeration" class="form-check-input" value="1">
                                <label class="form-check-label">Requires Refrigeration</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Initial Stock -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-plus-circle"></i>
                        <span>Initial Stock (Optional)</span>
                    </div>
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <small>Fill batch details to add initial stock. Leave empty to add stock later.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Batch Number</label>
                            <input type="text" name="batch_number" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Initial Quantity</label>
                            <input type="number" name="initial_quantity" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="Storage location">
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="form-section">
                    <div class="section-title">
                        <i class="fas fa-file-alt"></i>
                        <span>Additional Information</span>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12 mb-3" id="sideEffectsField">
                            <label class="form-label">Side Effects / Remarks</label>
                            <textarea name="side_effects" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button type="button" class="btn-cancel" onclick="window.history.back()">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save me-2"></i>Save Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function toggleItemTypeFields() {
    let type = $('#itemType').val();
    
    // Hide all type-specific sections
    $('#medicineFields').hide();
    $('#labTestFields').hide();
    $('#equipmentFields').hide();
    $('#otherFields').hide();
    $('#typePreview').hide();
    
    // Remove required attributes
    $('#medCategory').prop('required', false);
    $('#labCategory').prop('required', false);
    $('#invCategory').prop('required', false);
    $('#invCategoryOther').prop('required', false);
    
    if(type === 'medicine') {
        $('#medicineFields').show();
        $('#typePreview').show();
        $('#typeBadge').removeClass().addClass('type-badge type-medicine').html('💊 Medicine Item');
        $('#medCategory').prop('required', true);
        $('#sideEffectsField').show();
        
    } else if(type === 'lab_test') {
        $('#labTestFields').show();
        $('#typePreview').show();
        $('#typeBadge').removeClass().addClass('type-badge type-lab_test').html('🔬 Lab Test Item');
        $('#labCategory').prop('required', true);
        $('#sideEffectsField').hide();
        
    } else if(type === 'equipment') {
        $('#equipmentFields').show();
        $('#typePreview').show();
        $('#typeBadge').removeClass().addClass('type-badge type-equipment').html('🔧 Equipment / Accessory');
        $('#invCategory').prop('required', true);
        $('#sideEffectsField').hide();
        
    } else if(type === 'other') {
        $('#otherFields').show();
        $('#typePreview').show();
        $('#typeBadge').removeClass().addClass('type-badge type-other').html('📋 Other / Consumable');
        $('#invCategoryOther').prop('required', true);
        $('#sideEffectsField').hide();
    }
}

$(document).ready(function() {
    $('#addItemForm').on('submit', function(e) {
        e.preventDefault();
        
        let itemType = $('#itemType').val();
        if(!itemType) {
            Swal.fire('Error', 'Please select item type', 'error');
            return;
        }
        
        // Validate based on type
        if(itemType === 'medicine') {
            if(!$('#medCategory').val()) {
                Swal.fire('Error', 'Please select a category for the medicine', 'error');
                return;
            }
            // Set category field name
            $('<input>').attr({ type: 'hidden', name: 'med_category_id', value: $('#medCategory').val() }).appendTo('#addItemForm');
        }
        
        if(itemType === 'lab_test') {
            if(!$('#labCategory').val()) {
                Swal.fire('Error', 'Please select a category for the lab test', 'error');
                return;
            }
            $('<input>').attr({ type: 'hidden', name: 'lab_category_id', value: $('#labCategory').val() }).appendTo('#addItemForm');
        }
        
        if(itemType === 'equipment') {
            if(!$('#invCategory').val()) {
                Swal.fire('Error', 'Please select a category for the equipment', 'error');
                return;
            }
            $('<input>').attr({ type: 'hidden', name: 'inv_category_id', value: $('#invCategory').val() }).appendTo('#addItemForm');
        }
        
        if(itemType === 'other') {
            if(!$('#invCategoryOther').val()) {
                Swal.fire('Error', 'Please select a category', 'error');
                return;
            }
            $('<input>').attr({ type: 'hidden', name: 'inv_category_id', value: $('#invCategoryOther').val() }).appendTo('#addItemForm');
        }
        
        Swal.fire({
            title: 'Saving Item...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        $.ajax({
            url: BASE_URL + '/inventory/add-item',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire('Success', response.message, 'success').then(() => {
                        window.location.href = BASE_URL + '/inventory/items';
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to add item';
                try {
                    let response = JSON.parse(xhr.responseText);
                    if(response.message) errorMsg = response.message;
                } catch(e) {}
                Swal.fire('Error', errorMsg, 'error');
            }
        });
    });
});
</script>
</body>
</html>