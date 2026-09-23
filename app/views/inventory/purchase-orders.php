<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        .po-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
        }
        .card-header-custom {
            padding: 18px 24px;
            background: #f8fafc;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header-custom h5 { margin: 0; font-weight: 600; color: #1f2937; }
        .btn-create-po {
            background: #10b981;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
        }
        .btn-create-po:hover { background: #059669; color: white; }
        .po-table {
            width: 100%;
            border-collapse: collapse;
        }
        .po-table th {
            padding: 14px 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }
        .po-table td {
            padding: 14px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .po-table tr:hover { background: #fafbfc; }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pending_approval { background: #fef3c7; color: #d97706; }
        .status-ordered { background: #dbeafe; color: #2563eb; }
        .status-partial { background: #e0e7ff; color: #4f46e5; }
        .status-received { background: #d1fae5; color: #10b981; }
        .status-cancelled { background: #fee2e2; color: #ef4444; }
        .code-badge {
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 11px;
            font-weight: 600;
        }
        .btn-sm-custom {
            padding: 5px 10px;
            font-size: 11px;
            border-radius: 6px;
            margin: 2px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-icon { font-size: 64px; color: #cbd5e1; margin-bottom: 20px; }
        .empty-title { font-size: 18px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .empty-text { color: #94a3b8; font-size: 13px; }
        .source-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
        }
        .source-manual { background: #e0e7ff; color: #4338ca; }
        .source-reorder_alert { background: #fed7aa; color: #9a3412; }
        .item-type-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 500;
        }
        .item-type-medicine { background: #d1fae5; color: #065f46; }
        .item-type-lab_test { background: #f3e8ff; color: #6b21a5; }
        .item-type-inventory { background: #fed7aa; color: #9a3412; }
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .po-table th, .po-table td { padding: 10px 6px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-shopping-cart text-success me-2"></i>Purchase Orders</h1>
            <p class="page-subtitle">Manage supplier purchase orders - pending approval orders can be edited before approval</p>
        </div>
        <div>
            <button class="btn-create-po" onclick="openCreatePOModal()">
                <i class="fas fa-plus me-2"></i>Create Purchase Order
            </button>
        </div>
    </div>

    <!-- Purchase Orders Table -->
    <div class="po-card">
        <div class="card-header-custom">
            <h5><i class="fas fa-list me-2"></i>Purchase Order List</h5>
            <span class="badge bg-secondary"><?php echo isset($orders) ? count($orders) : 0; ?> orders</span>
        </div>
        <div class="table-responsive">
            <table class="po-table">
                <thead>
                    <tr>
                        <th>PO #</th>
                        <th>Source</th>
                        <th>Supplier</th>
                        <th>Store</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($orders) && !empty($orders)): ?>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td><span class="code-badge"><?php echo htmlspecialchars($order['po_number']); ?></span></small>
                            <td>
                                <span class="source-badge source-<?php echo $order['source'] ?? 'manual'; ?>">
                                    <?php echo ($order['source'] ?? 'manual') == 'reorder_alert' ? '🔄 Reorder Alert' : '📝 Manual'; ?>
                                </span>
                             </small>
                            <td><?php echo htmlspecialchars($order['supplier_name'] ?? 'N/A'); ?></small>
                            <td><?php echo htmlspecialchars($order['store_name'] ?? 'N/A'); ?></small>
                            <td><?php echo date('d M Y', strtotime($order['order_date'])); ?></small>
                            <td><strong class="text-success">৳ <?php echo number_format($order['total_amount'], 2); ?></strong></small>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                        $statusLabels = [
                                            'pending_approval' => '⏳ Pending Approval',
                                            'ordered' => '📦 Ordered',
                                            'partial' => '📦 Partial',
                                            'received' => '✅ Received',
                                            'cancelled' => '❌ Cancelled'
                                        ];
                                        echo $statusLabels[$order['status']] ?? ucfirst($order['status']);
                                    ?>
                                </span>
                             </small>
                            <td>
                                <button class="btn btn-info btn-sm-custom" onclick="viewPO(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                                <?php if($order['status'] == 'pending_approval'): ?>
                                <button class="btn btn-warning btn-sm-custom" onclick="editPO(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-success btn-sm-custom" onclick="approvePO(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-check me-1"></i>Approve
                                </button>
                                <button class="btn btn-danger btn-sm-custom" onclick="cancelPO(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </button>
                                <?php endif; ?>
                                <?php if($order['status'] == 'ordered'): ?>
                                <button class="btn btn-primary btn-sm-custom" onclick="receivePO(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-check me-1"></i>Receive
                                </button>
                                <?php endif; ?>
                             </small>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-shopping-cart empty-icon"></i>
                                    <div class="empty-title">No Purchase Orders Found</div>
                                    <div class="empty-text">Click "Create Purchase Order" to place an order with a supplier.</div>
                                </div>
                             </small>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create/Edit Purchase Order Modal -->
<div class="modal fade" id="createPOModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create Purchase Order</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createPOForm">
                    <input type="hidden" name="po_id" id="edit_po_id" value="0">
                    <input type="hidden" name="source" id="po_source" value="manual">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Item Type *</label>
                            <select id="item_type_select" class="form-select" required onchange="loadItemsByType()">
                                <option value="">Select Item Type</option>
                                <option value="medicine">💊 Medicine</option>
                                <option value="lab_test">🔬 Lab Test</option>
                                <option value="inventory">📦 Equipment/Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Supplier *</label>
                            <select name="supplier_id" id="supplier_id" class="form-select" required>
                                <option value="">Select Item Type First</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Store *</label>
                            <select name="store_id" id="store_id" class="form-select" required>
                                <option value="">Select Store</option>
                                <?php if(isset($stores) && !empty($stores)): ?>
                                    <?php foreach($stores as $store): ?>
                                    <option value="<?php echo $store['id']; ?>"><?php echo htmlspecialchars($store['store_name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Order Date *</label>
                            <input type="date" name="order_date" id="order_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Expected Delivery Date</label>
                            <input type="date" name="expected_delivery" id="expected_delivery" class="form-control">
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="mb-3"><i class="fas fa-boxes me-2"></i>Order Items</h6>
                    <div id="poItemsContainer"></div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addPOItem()">
                        <i class="fas fa-plus me-1"></i>Add Another Item
                    </button>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Optional notes about this purchase order..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitPurchaseOrder()">Save as Draft (Pending Approval)</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let itemCounter = 1;

// Data passed from PHP
var allMedicineItems = <?php echo json_encode(isset($medicineItems) ? $medicineItems : []); ?>;
var allLabTestItems = <?php echo json_encode(isset($labTestItems) ? $labTestItems : []); ?>;
var allInventoryItems = <?php echo json_encode(isset($inventoryItems) ? $inventoryItems : []); ?>;

var medicineSuppliers = <?php echo json_encode(isset($medicineSuppliers) ? $medicineSuppliers : []); ?>;
var labTestSuppliers = <?php echo json_encode(isset($labTestSuppliers) ? $labTestSuppliers : []); ?>;
var inventorySuppliers = <?php echo json_encode(isset($inventorySuppliers) ? $inventorySuppliers : []); ?>;

function loadItemsByType() {
    let itemType = $('#item_type_select').val();
    let itemsHtml = '<option value="">Select Item</option>';
    
    if (itemType === 'medicine') {
        allMedicineItems.forEach(function(item) {
            itemsHtml += `<option value="${item.id}" data-unit-price="${item.selling_price || item.price || 0}">
                💊 ${escapeHtml(item.item_name)} (${escapeHtml(item.item_code)})
            </option>`;
        });
    } else if (itemType === 'lab_test') {
        allLabTestItems.forEach(function(item) {
            itemsHtml += `<option value="${item.id}" data-unit-price="${item.price || 0}">
                🔬 ${escapeHtml(item.item_name)} (${escapeHtml(item.item_code)})
            </option>`;
        });
    } else if (itemType === 'inventory') {
        allInventoryItems.forEach(function(item) {
            let icon = item.item_type === 'equipment' ? '🔧' : '📋';
            itemsHtml += `<option value="${item.id}" data-unit-price="${item.selling_price || 0}">
                ${icon} ${escapeHtml(item.item_name)} (${escapeHtml(item.item_code)})
            </option>`;
        });
    }
    
    // Update existing item selects
    $('.item-select').each(function() {
        $(this).html(itemsHtml);
    });
    
    // Load suppliers based on item type
    loadSuppliersByType(itemType);
}

function loadSuppliersByType(itemType) {
    let supplierHtml = '<option value="">Select Supplier</option>';
    let suppliers = [];
    
    if (itemType === 'medicine') {
        suppliers = medicineSuppliers;
    } else if (itemType === 'lab_test') {
        suppliers = labTestSuppliers;
    } else if (itemType === 'inventory') {
        suppliers = inventorySuppliers;
    }
    
    suppliers.forEach(function(supplier) {
        supplierHtml += `<option value="${supplier.id}">${escapeHtml(supplier.company_name)}${supplier.contact_person ? ' - ' + escapeHtml(supplier.contact_person) : ''}</option>`;
    });
    
    $('#supplier_id').html(supplierHtml);
}

function openCreatePOModal() {
    $('#createPOForm')[0].reset();
    $('#edit_po_id').val(0);
    $('#po_source').val('manual');
    $('#item_type_select').val('');
    $('#supplier_id').html('<option value="">Select Item Type First</option>');
    itemCounter = 0;
    $('#poItemsContainer').empty();
    // Don't add empty row - let user select item type first
    $('#createPOModal').modal('show');
}

function addPOItem() {
    let itemType = $('#item_type_select').val();
    
    if (!itemType) {
        Swal.fire('Error', 'Please select Item Type first', 'error');
        return;
    }
    
    let itemsHtml = '<option value="">Select Item</option>';
    
    if (itemType === 'medicine') {
        allMedicineItems.forEach(function(item) {
            itemsHtml += `<option value="${item.id}" data-unit-price="${item.selling_price || 0}">
                💊 ${escapeHtml(item.item_name)} (${escapeHtml(item.item_code)})
            </option>`;
        });
    } else if (itemType === 'lab_test') {
        allLabTestItems.forEach(function(item) {
            itemsHtml += `<option value="${item.id}" data-unit-price="${item.price || 0}">
                🔬 ${escapeHtml(item.item_name)} (${escapeHtml(item.item_code)})
            </option>`;
        });
    } else if (itemType === 'inventory') {
        allInventoryItems.forEach(function(item) {
            let icon = item.item_type === 'equipment' ? '🔧' : '📋';
            itemsHtml += `<option value="${item.id}" data-unit-price="${item.selling_price || 0}">
                ${icon} ${escapeHtml(item.item_name)} (${escapeHtml(item.item_code)})
            </option>`;
        });
    }
    
    let html = `
        <div class="row mb-2 po-item-row">
            <div class="col-md-5">
                <select name="items[${itemCounter}][item_id]" class="form-select item-select" required onchange="updateItemPrice(this)">
                    ${itemsHtml}
                </select>
            </div>
            <div class="col-md-2">
                <input type="number" name="items[${itemCounter}][quantity]" class="form-control item-quantity" placeholder="Quantity" min="1" value="1" required>
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="items[${itemCounter}][unit_price]" class="form-control item-price" placeholder="Unit Price" value="0" required>
            </div>
            <div class="col-md-2">
                <input type="hidden" name="items[${itemCounter}][source_type]" value="${itemType}">
                <span class="badge bg-secondary item-type-badge">${itemType}</span>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-danger btn-sm" onclick="removePOItem(this)">×</button>
            </div>
        </div>
    `;
    $('#poItemsContainer').append(html);
    itemCounter++;
}

function removePOItem(btn) {
    $(btn).closest('.po-item-row').remove();
    
    // Renumber remaining items
    let counter = 0;
    $('.po-item-row').each(function() {
        $(this).find('select').attr('name', 'items[' + counter + '][item_id]');
        $(this).find('.item-quantity').attr('name', 'items[' + counter + '][quantity]');
        $(this).find('.item-price').attr('name', 'items[' + counter + '][unit_price]');
        $(this).find('input[type="hidden"]').attr('name', 'items[' + counter + '][source_type]');
        counter++;
    });
    itemCounter = counter;
}

function updateItemPrice(selectElement) {
    let selectedOption = $(selectElement).find('option:selected');
    let unitPrice = selectedOption.data('unit-price') || 0;
    let row = $(selectElement).closest('.po-item-row');
    row.find('.item-price').val(unitPrice);
}

function removePOItem(btn) {
    $(btn).closest('.po-item-row').remove();
}

function submitPurchaseOrder() {
    let poId = $('#edit_po_id').val();
    let source = $('#po_source').val();
    let selectedItemType = $('#item_type_select').val();
    
    if (!selectedItemType) {
        Swal.fire('Error', 'Please select Item Type', 'error');
        return;
    }
    
    if (!$('#supplier_id').val()) {
        Swal.fire('Error', 'Please select a Supplier', 'error');
        return;
    }
    
    if (!$('#store_id').val()) {
        Swal.fire('Error', 'Please select a Store', 'error');
        return;
    }
    
    // Build items array - MORE ROBUST DETECTION
    let items = [];
    
    // Get all item rows
    let itemRows = document.querySelectorAll('.po-item-row');
    console.log('Found item rows:', itemRows.length);
    
    for (let i = 0; i < itemRows.length; i++) {
        let row = itemRows[i];
        
        // Find the select element for item_id
        let selectElement = row.querySelector('select');
        let itemId = selectElement ? selectElement.value : null;
        
        // Find quantity input (any input with type number or name containing quantity)
        let quantityInput = row.querySelector('input[type="number"]');
        if (!quantityInput) {
            quantityInput = row.querySelector('input[name*="quantity"]');
        }
        let quantity = quantityInput ? quantityInput.value : null;
        
        // Find unit price input (input with step 0.01 or name containing price)
        let priceInput = row.querySelector('input[step="0.01"]');
        if (!priceInput) {
            priceInput = row.querySelector('input[name*="price"]');
        }
        let unitPrice = priceInput ? priceInput.value : null;
        
        console.log(`Row ${i}: Item ID=${itemId}, Qty=${quantity}, Price=${unitPrice}`);
        
        // Validate - itemId should be a number (not empty, not "0", not NaN)
        if (itemId && itemId !== '' && itemId !== '0' && !isNaN(parseInt(itemId))) {
            let qtyNum = parseInt(quantity);
            let priceNum = parseFloat(unitPrice);
            
            if (!isNaN(qtyNum) && qtyNum > 0 && !isNaN(priceNum) && priceNum > 0) {
                items.push({
                    item_id: parseInt(itemId),
                    quantity: qtyNum,
                    unit_price: priceNum,
                    source_type: selectedItemType
                });
            }
        }
    }
    
    console.log('Valid items found:', items.length);
    
    if (items.length === 0) {
        Swal.fire('Error', 'Please add at least one valid item. Make sure item, quantity, and unit price are filled correctly.', 'error');
        return;
    }
    
    let postData = {
        po_id: poId,
        source: source,
        supplier_id: $('#supplier_id').val(),
        store_id: $('#store_id').val(),
        order_date: $('#order_date').val(),
        expected_delivery: $('#expected_delivery').val(),
        notes: $('#notes').val(),
        items: items
    };
    
    console.log('Sending data:', postData);
    
    let url = poId > 0 ? BASE_URL + '/inventory/update-po' : BASE_URL + '/inventory/create-po';
    
    Swal.fire({
        title: poId > 0 ? 'Updating Purchase Order...' : 'Creating Purchase Order...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: url,
        method: 'POST',
        data: JSON.stringify(postData),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function(xhr) {
            console.log('AJAX Error - Status:', xhr.status);
            console.log('AJAX Error - Response:', xhr.responseText);
            
            let errorMsg = 'Failed to process request';
            try {
                let response = JSON.parse(xhr.responseText);
                if (response.message) errorMsg = response.message;
            } catch(e) {
                if (xhr.status === 404) {
                    errorMsg = 'Endpoint not found. Please check the URL.';
                } else if (xhr.status === 500) {
                    errorMsg = 'Server error. Please check the logs.';
                } else {
                    errorMsg = xhr.statusText || 'Unknown error';
                }
            }
            Swal.fire('Error!', errorMsg, 'error');
        }
    });
}

function openCreatePOModalWithData(data) {
    $('#createPOForm')[0].reset();
    $('#edit_po_id').val(0);
    $('#po_source').val('reorder_alert');
    
    // Set item type
    let itemType = data.item_type === 'medicine' ? 'medicine' : (data.item_type === 'lab_test' ? 'lab_test' : 'inventory');
    $('#item_type_select').val(itemType);
    
    // Load items and suppliers
    loadItemsByType();
    loadSuppliersByType(itemType);
    
    setTimeout(function() {
        if (data.supplier_id && data.supplier_id > 0) {
            $('#supplier_id').val(data.supplier_id);
        }
        if (data.expected_date) {
            $('#expected_delivery').val(data.expected_date);
        }
        if (data.notes) {
            $('#notes').val(data.notes);
        }
        
        // Clear and add the item from reorder alert
        $('#poItemsContainer').empty();
        itemCounter = 0;
        
        let itemsHtml = `<option value="${data.item_id}" selected data-unit-price="${data.unit_price}">
            ${escapeHtml(data.item_name)} (${escapeHtml(data.item_code)})
        </option>`;
        
        let html = `
            <div class="row mb-2 po-item-row">
                <div class="col-md-5">
                    <select name="items[0][item_id]" class="form-select item-select" required onchange="updateItemPrice(this)">
                        ${itemsHtml}
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="items[0][quantity]" class="form-control item-quantity" placeholder="Quantity" min="1" value="${data.quantity}" required>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" name="items[0][unit_price]" class="form-control item-price" placeholder="Unit Price" value="${data.unit_price}" required>
                </div>
                <div class="col-md-2">
                    <input type="hidden" name="items[0][source_type]" value="${itemType}">
                    <span class="badge bg-secondary item-type-badge">${itemType}</span>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removePOItem(this)">×</button>
                </div>
            </div>
        `;
        $('#poItemsContainer').append(html);
        itemCounter = 1;
        
        $('#createPOModal .modal-title').html('<i class="fas fa-plus me-2"></i>Create Purchase Order (from Reorder Alert)');
        $('#createPOModal').modal('show');
    }, 300);
}


function viewPO(id) {
    window.location.href = BASE_URL + '/inventory/view-po/' + id;
}

function editPO(id) {
    $.ajax({
        url: BASE_URL + '/inventory/get-po-details/' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let po = response.po;
                let poItems = response.items;
                
                $('#edit_po_id').val(po.id);
                $('#po_source').val(po.source || 'manual');
                $('#store_id').val(po.store_id);
                $('#order_date').val(po.order_date);
                $('#expected_delivery').val(po.expected_delivery_date);
                $('#notes').val(po.notes);
                
                // Determine item type from first item
                if (poItems.length > 0) {
                    let firstItem = poItems[0];
                    let itemType = firstItem.source_type || 'inventory';
                    $('#item_type_select').val(itemType);
                    loadItemsByType();
                    loadSuppliersByType(itemType);
                    $('#supplier_id').val(po.supplier_id);
                    
                    // Clear and rebuild items
                    $('#poItemsContainer').empty();
                    itemCounter = 0;
                    
                    poItems.forEach(function(item, index) {
                        let itemValue = `${item.source_type || 'inventory'}_${item.item_id}`;
                        let itemsHtml = '<option value="">Select Item</option>';
                        
                        if (item.source_type === 'medicine') {
                            allMedicineItems.forEach(function(medItem) {
                                let selected = medItem.id == item.item_id ? 'selected' : '';
                                itemsHtml += `<option value="medicine_${medItem.id}" data-item-type="medicine" data-item-id="${medItem.id}" data-item-name="${escapeHtml(medItem.item_name)}" data-item-code="${escapeHtml(medItem.item_code)}" data-unit-price="${medItem.selling_price || 0}" ${selected}>
                                    💊 ${escapeHtml(medItem.item_name)} (${escapeHtml(medItem.item_code)})
                                </option>`;
                            });
                        } else if (item.source_type === 'lab_test') {
                            allLabTestItems.forEach(function(labItem) {
                                let selected = labItem.id == item.item_id ? 'selected' : '';
                                itemsHtml += `<option value="lab_test_${labItem.id}" data-item-type="lab_test" data-item-id="${labItem.id}" data-item-name="${escapeHtml(labItem.item_name)}" data-item-code="${escapeHtml(labItem.item_code)}" data-unit-price="${labItem.price || 0}" ${selected}>
                                    🔬 ${escapeHtml(labItem.item_name)} (${escapeHtml(labItem.item_code)})
                                </option>`;
                            });
                        } else {
                            allInventoryItems.forEach(function(invItem) {
                                let selected = invItem.id == item.item_id ? 'selected' : '';
                                let icon = invItem.item_type === 'equipment' ? '🔧' : '📋';
                                itemsHtml += `<option value="inventory_${invItem.id}" data-item-type="inventory" data-item-id="${invItem.id}" data-item-name="${escapeHtml(invItem.item_name)}" data-item-code="${escapeHtml(invItem.item_code)}" data-unit-price="${invItem.selling_price || 0}" ${selected}>
                                    ${icon} ${escapeHtml(invItem.item_name)} (${escapeHtml(invItem.item_code)})
                                </option>`;
                            });
                        }
                        
                        let html = `
                            <div class="row mb-2 po-item-row">
                                <div class="col-md-5">
                                    <select name="items[${index}][item_id]" class="form-select item-select" required onchange="updateItemPrice(this)">
                                        ${itemsHtml}
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" name="items[${index}][quantity]" class="form-control item-quantity" placeholder="Qty" min="1" value="${item.quantity}" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" step="0.01" name="items[${index}][unit_price]" class="form-control item-price" placeholder="Unit Price" value="${item.unit_price}" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="text" name="items[${index}][item_type]" class="form-control item-type-hidden" readonly value="${item.source_type || 'inventory'}">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removePOItem(this)">×</button>
                                </div>
                            </div>
                        `;
                        $('#poItemsContainer').append(html);
                        itemCounter = index + 1;
                    });
                }
                
                $('#createPOModal .modal-title').html('<i class="fas fa-edit me-2"></i>Edit Purchase Order (Pending Approval)');
                $('#createPOModal').modal('show');
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to load PO details', 'error');
        }
    });
}

function approvePO(id) {
    Swal.fire({
        title: 'Approve Purchase Order',
        text: 'Are you sure you want to approve this purchase order? This will move it to "Ordered" status.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#10b981'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/approve-po',
                method: 'POST',
                data: { po_id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Approved!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                }
            });
        }
    });
}

function receivePO(id) {
    Swal.fire({
        title: 'Receive Purchase Order?',
        text: 'This will add all items to inventory stock.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Receive',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#10b981'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/receive-po',
                method: 'POST',
                data: { po_id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Received!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                }
            });
        }
    });
}

function cancelPO(id) {
    Swal.fire({
        title: 'Cancel Purchase Order',
        text: 'Are you sure you want to cancel this purchase order?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'No',
        confirmButtonColor: '#ef4444'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/cancel-po',
                method: 'POST',
                data: { po_id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Cancelled!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                }
            });
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// Pre-filled data from reorder alert
$(document).ready(function() {
    <?php if (isset($prefillData) && $prefillData): ?>
    setTimeout(function() {
        let data = <?php echo json_encode($prefillData); ?>;
        openCreatePOModalWithData(data);
    }, 500);
    <?php endif; ?>
});

function openCreatePOModalWithData(data) {
    $('#createPOForm')[0].reset();
    $('#edit_po_id').val(0);
    $('#po_source').val('reorder_alert');
    
    // Set item type
    let itemType = data.item_type === 'medicine' ? 'medicine' : (data.item_type === 'lab_test' ? 'lab_test' : 'inventory');
    $('#item_type_select').val(itemType);
    
    // Load items and suppliers
    loadItemsByType();
    loadSuppliersByType(itemType);
    
    setTimeout(function() {
        if (data.supplier_id && data.supplier_id > 0) {
            $('#supplier_id').val(data.supplier_id);
        }
        if (data.store_id) {
            $('#store_id').val(data.store_id);
        }
        if (data.expected_date) {
            $('#expected_delivery').val(data.expected_date);
        }
        if (data.notes) {
            $('#notes').val(data.notes);
        }
        
        // Add the item from reorder alert
        $('#poItemsContainer').empty();
        itemCounter = 0;
        
        let itemValue = `${data.item_type}_${data.item_id}`;
        let itemsHtml = '<option value="">Select Item</option>';
        
        if (data.item_type === 'medicine') {
            allMedicineItems.forEach(function(item) {
                let selected = item.id == data.item_id ? 'selected' : '';
                itemsHtml += `<option value="medicine_${item.id}" data-item-type="medicine" data-item-id="${item.id}" data-item-name="${escapeHtml(item.item_name)}" data-item-code="${escapeHtml(item.item_code)}" data-unit-price="${item.selling_price || 0}" ${selected}>
                    💊 ${escapeHtml(item.item_name)} (${escapeHtml(item.item_code)})
                </option>`;
            });
        } else if (data.item_type === 'lab_test') {
            allLabTestItems.forEach(function(item) {
                let selected = item.id == data.item_id ? 'selected' : '';
                itemsHtml += `<option value="lab_test_${item.id}" data-item-type="lab_test" data-item-id="${item.id}" data-item-name="${escapeHtml(item.item_name)}" data-item-code="${escapeHtml(item.item_code)}" data-unit-price="${item.price || 0}" ${selected}>
                    🔬 ${escapeHtml(item.item_name)} (${escapeHtml(item.item_code)})
                </option>`;
            });
        } else {
            allInventoryItems.forEach(function(item) {
                let selected = item.id == data.item_id ? 'selected' : '';
                let icon = item.item_type === 'equipment' ? '🔧' : '📋';
                itemsHtml += `<option value="inventory_${item.id}" data-item-type="inventory" data-item-id="${item.id}" data-item-name="${escapeHtml(item.item_name)}" data-item-code="${escapeHtml(item.item_code)}" data-unit-price="${item.selling_price || 0}" ${selected}>
                    ${icon} ${escapeHtml(item.item_name)} (${escapeHtml(item.item_code)})
                </option>`;
            });
        }
        
        let html = `
            <div class="row mb-2 po-item-row">
                <div class="col-md-5">
                    <select name="items[0][item_id]" class="form-select item-select" required onchange="updateItemPrice(this)">
                        ${itemsHtml}
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" name="items[0][quantity]" class="form-control item-quantity" placeholder="Qty" min="1" value="${data.quantity}" required>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" name="items[0][unit_price]" class="form-control item-price" placeholder="Unit Price" value="${data.unit_price}" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="items[0][item_type]" class="form-control item-type-hidden" readonly value="${data.item_type}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removePOItem(this)">×</button>
                </div>
            </div>
        `;
        $('#poItemsContainer').append(html);
        itemCounter = 1;
        
        $('#createPOModal .modal-title').html('<i class="fas fa-plus me-2"></i>Create Purchase Order (from Reorder Alert)');
        $('#createPOModal').modal('show');
    }, 300);
}
</script>
</body>
</html>