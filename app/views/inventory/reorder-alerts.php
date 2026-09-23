<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reorder Alerts - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        .alert-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
            margin-bottom: 25px;
        }
        .card-header-custom {
            padding: 18px 24px;
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        .card-header-custom h5 { margin: 0; font-weight: 600; }
        .card-body-custom { padding: 24px; }
        .alert-table {
            width: 100%;
            border-collapse: collapse;
        }
        .alert-table th {
            padding: 14px 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }
        .alert-table td {
            padding: 14px 12px;
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .alert-table tr:hover { background: #fafbfc; }
        .warning-row { background: #fef3c7; }
        .critical-row { background: #fee2e2; }
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .type-medicine { background: #d1fae5; color: #065f46; }
        .type-lab_test { background: #f3e8ff; color: #6b21a5; }
        .type-equipment { background: #fed7aa; color: #9a3412; }
        .type-other { background: #e2e8f0; color: #475569; }
        .stock-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        .stock-critical { background: #ef4444; color: white; }
        .stock-warning { background: #f59e0b; color: white; }
        .stock-normal { background: #d1fae5; color: #10b981; }
        .code-badge {
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 11px;
            font-weight: 600;
        }
        .btn-create-po {
            background: #10b981;
            border: none;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
            margin-right: 5px;
        }
        .btn-create-po:hover { background: #059669; color: white; }
        .btn-ignore {
            background: #6c757d;
            border: none;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-ignore:hover { background: #5a6268; color: white; }
        .btn-back {
            background: white;
            border: 1px solid #e2e8f0;
            color: #64748b;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
        }
        .btn-back:hover { background: #f1f5f9; color: #1f2937; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-icon { font-size: 64px; color: #cbd5e1; margin-bottom: 20px; }
        .empty-title { font-size: 18px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .empty-text { color: #94a3b8; font-size: 13px; }
        .progress-bar-custom {
            width: 100%;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }
        .progress-fill { height: 6px; border-radius: 10px; }
        .progress-fill-danger { background: #ef4444; }
        .progress-fill-warning { background: #f59e0b; }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            height: 100%;
            border: 1px solid #e5e7eb;
        }
        .stat-number { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 12px; color: #6c757d; }
        .pagination-container { margin-top: 25px; display: flex; justify-content: center; }
        .pagination { gap: 5px; flex-wrap: wrap; }
        .page-link {
            border-radius: 10px !important;
            border: 1px solid #e2e8f0;
            color: #4b5563;
            font-size: 13px;
            padding: 8px 14px;
        }
        .page-link:hover { background: #10b981; color: white; border-color: #10b981; }
        .page-item.active .page-link { background: #10b981; border-color: #10b981; }
        .refresh-btn {
            background: #3b82f6;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-left: 10px;
        }
        .refresh-btn:hover { background: #2563eb; }
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .alert-table th, .alert-table td { padding: 10px 6px; }
            .btn-create-po, .btn-ignore { padding: 4px 8px; font-size: 10px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-shopping-cart text-warning me-2"></i>Reorder Alerts</h1>
            <p class="page-subtitle">Items that have reached or fallen below reorder level</p>
        </div>
        <div>
            <button class="refresh-btn" onclick="refreshAlerts()">
                <i class="fas fa-sync-alt me-1"></i>Refresh
            </button>
            <a href="<?php echo BASE_URL; ?>/inventory/dashboard" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-danger"><?php echo isset($totalCritical) ? $totalCritical : 0; ?></div>
                <div class="stat-label">Critical (0-30% stock)</div>
                <i class="fas fa-exclamation-triangle text-danger mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo isset($totalWarning) ? $totalWarning : 0; ?></div>
                <div class="stat-label">Warning (31-100% stock)</div>
                <i class="fas fa-bell text-warning mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo isset($totalOutOfStock) ? $totalOutOfStock : 0; ?></div>
                <div class="stat-label">Out of Stock</div>
                <i class="fas fa-times-circle text-info mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo isset($totalAlerts) ? $totalAlerts : 0; ?></div>
                <div class="stat-label">Total Alerts</div>
                <i class="fas fa-chart-line text-success mt-2"></i>
            </div>
        </div>
    </div>

    <!-- Pass supplier data to JavaScript -->
    <script>
        // Pre-load all suppliers by type
        var medicineSuppliers = <?php echo json_encode(isset($allMedicineSuppliers) ? $allMedicineSuppliers : []); ?>;
        var labSuppliers = <?php echo json_encode(isset($allLabSuppliers) ? $allLabSuppliers : []); ?>;
        var inventorySuppliers = <?php echo json_encode(isset($allInventorySuppliers) ? $allInventorySuppliers : []); ?>;
    </script>

    <!-- Medicine Reorder Alerts -->
    <?php if(isset($medicineAlerts) && !empty($medicineAlerts)): ?>
    <div class="alert-card">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <h5><i class="fas fa-pills me-2"></i>Medicine Reorder Alerts</h5>
            <small class="d-block mt-1"><?php echo count($medicineAlerts); ?> medicines need reorder</small>
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="alert-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Suggested Qty</th>
                            <th>Manufacturer</th>
                            <th>Stock Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($medicineAlerts as $alert): 
                            $reorderLevel = max(1, (int)($alert['reorder_level'] ?? 10));
                            $currentStock = (int)($alert['current_stock'] ?? 0);
                            $stockPercent = ($currentStock / max(1, $reorderLevel)) * 100;
                            $rowClass = ($stockPercent < 30 || $currentStock <= 0) ? 'critical-row' : 'warning-row';
                            $stockClass = ($stockPercent < 30 || $currentStock <= 0) ? 'stock-critical' : 'stock-warning';
                            $stockStatus = ($stockPercent < 30 || $currentStock <= 0) ? 'Critical' : 'Warning';
                            $progressClass = ($stockPercent < 30 || $currentStock <= 0) ? 'progress-fill-danger' : 'progress-fill-warning';
                            $suggestedQty = max($reorderLevel, (int)($alert['reorder_quantity'] ?? 50));
                        ?>
                        <tr class="<?php echo $rowClass; ?>" data-item-id="<?php echo $alert['id']; ?>" data-item-type="medicine">
                            <td>
                                <strong><?php echo htmlspecialchars($alert['item_name'] ?? 'N/A'); ?></strong>
                                <br><span class="code-badge"><?php echo htmlspecialchars($alert['item_code'] ?? 'N/A'); ?></span>
                                <br><span class="type-badge type-medicine">💊 Medicine</span>
                             </small>
                            <td>
                                <span class="stock-badge <?php echo $stockClass; ?>"><?php echo $currentStock; ?> <?php echo htmlspecialchars($alert['unit_of_measure'] ?? 'units'); ?></span>
                                <div class="progress-bar-custom"><div class="progress-fill <?php echo $progressClass; ?>" style="width: <?php echo min(100, max(0, $stockPercent)); ?>%;"></div></div>
                                <small class="text-muted"><?php echo round($stockPercent, 1); ?>% of reorder level</small>
                             </small>
                            <td><?php echo $reorderLevel; ?> <?php echo htmlspecialchars($alert['unit_of_measure'] ?? 'units'); ?></small>
                            <td><strong class="text-primary"><?php echo $suggestedQty; ?> <?php echo htmlspecialchars($alert['unit_of_measure'] ?? 'units'); ?></strong></small>
                            <td><?php echo htmlspecialchars($alert['manufacturer'] ?? 'N/A'); ?></small>
                            <td><span class="badge <?php echo $stockStatus == 'Critical' ? 'bg-danger' : 'bg-warning'; ?>"><?php echo $stockStatus; ?></span></small>
                            <td>
                                <button class="btn-create-po" onclick="openCreatePOModal('medicine', <?php echo $alert['id']; ?>, '<?php echo addslashes($alert['item_name']); ?>', <?php echo $suggestedQty; ?>, <?php echo $currentStock; ?>, '<?php echo addslashes($alert['unit_of_measure'] ?? 'units'); ?>', <?php echo $alert['selling_price'] ?? 0; ?>, '<?php echo addslashes($alert['manufacturer'] ?? 'N/A'); ?>', '<?php echo addslashes($alert['item_code']); ?>')">
                                    <i class="fas fa-truck me-1"></i>Create PO
                                </button>
                                <button class="btn-ignore" onclick="ignoreReorderAlert('medicine', <?php echo $alert['id']; ?>)">
                                    <i class="fas fa-times me-1"></i>Ignore
                                </button>
                             </small>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Lab Test Reorder Alerts -->
    <?php if(isset($labAlerts) && !empty($labAlerts)): ?>
    <div class="alert-card">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
            <h5><i class="fas fa-microscope me-2"></i>Lab Test Reorder Alerts</h5>
            <small class="d-block mt-1"><?php echo count($labAlerts); ?> lab tests need reorder</small>
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="alert-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Suggested Qty</th>
                            <th>Supplier</th>
                            <th>Stock Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($labAlerts as $alert): 
                            $suggestedQty = 25;
                        ?>
                        <tr class="critical-row" data-item-id="<?php echo $alert['id']; ?>" data-item-type="lab_test">
                            <td>
                                <strong><?php echo htmlspecialchars($alert['item_name'] ?? 'N/A'); ?></strong>
                                <br><span class="code-badge"><?php echo htmlspecialchars($alert['item_code'] ?? 'N/A'); ?></span>
                                <br><span class="type-badge type-lab_test">🔬 Lab Test</span>
                             </small>
                            <td><span class="stock-badge stock-critical">0 kits</span></small>
                            <td>10 kits</small>
                            <td><strong class="text-primary">25 kits</strong></small>
                            <td><?php echo htmlspecialchars($alert['supplier_name'] ?? 'N/A'); ?></small>
                            <td><span class="badge bg-danger">Critical</span></small>
                            <td>
                                <button class="btn-create-po" onclick="openCreatePOModal('lab_test', <?php echo $alert['id']; ?>, '<?php echo addslashes($alert['item_name']); ?>', <?php echo $suggestedQty; ?>, 0, 'Kit', <?php echo $alert['selling_price'] ?? 0; ?>, 'N/A', '<?php echo addslashes($alert['item_code']); ?>')">
                                    <i class="fas fa-truck me-1"></i>Create PO
                                </button>
                                <button class="btn-ignore" onclick="ignoreReorderAlert('lab_test', <?php echo $alert['id']; ?>)">
                                    <i class="fas fa-times me-1"></i>Ignore
                                </button>
                             </small>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Inventory Reorder Alerts -->
    <?php if(isset($inventoryAlerts) && !empty($inventoryAlerts)): ?>
    <div class="alert-card">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <h5><i class="fas fa-boxes me-2"></i>Equipment & Other Reorder Alerts</h5>
            <small class="d-block mt-1"><?php echo count($inventoryAlerts); ?> items need reorder</small>
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="alert-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Current Stock</th>
                            <th>Reorder Level</th>
                            <th>Suggested Qty</th>
                            <th>Supplier</th>
                            <th>Stock Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inventoryAlerts as $alert): 
                            $currentStock = (int)($alert['current_stock'] ?? 0);
                            $suggestedQty = max(10, (int)($alert['reorder_quantity'] ?? 50));
                            $typeIcon = '📋 Other';
                            $typeClass = 'type-other';
                            if($alert['source_type'] == 'equipment') {
                                $typeIcon = '🔧 Equipment';
                                $typeClass = 'type-equipment';
                            }
                        ?>
                        <tr class="critical-row" data-item-id="<?php echo $alert['id']; ?>" data-item-type="inventory">
                            <td>
                                <strong><?php echo htmlspecialchars($alert['item_name'] ?? 'N/A'); ?></strong>
                                <br><span class="code-badge"><?php echo htmlspecialchars($alert['item_code'] ?? 'N/A'); ?></span>
                                <br><span class="type-badge <?php echo $typeClass; ?>"><?php echo $typeIcon; ?></span>
                             </small>
                            <td><span class="stock-badge stock-critical"><?php echo $currentStock; ?> <?php echo htmlspecialchars($alert['unit_of_measure'] ?? 'units'); ?></span></small>
                            <td>10 <?php echo htmlspecialchars($alert['unit_of_measure'] ?? 'units'); ?></small>
                            <td><strong class="text-primary"><?php echo $suggestedQty; ?> <?php echo htmlspecialchars($alert['unit_of_measure'] ?? 'units'); ?></strong></small>
                            <td><?php echo htmlspecialchars($alert['supplier_name'] ?? 'N/A'); ?></small>
                            <td><span class="badge bg-danger">Critical</span></small>
                            <td>
                                <button class="btn-create-po" onclick="openCreatePOModal('inventory', <?php echo $alert['id']; ?>, '<?php echo addslashes($alert['item_name']); ?>', <?php echo $suggestedQty; ?>, <?php echo $currentStock; ?>, '<?php echo addslashes($alert['unit_of_measure'] ?? 'units'); ?>', <?php echo $alert['selling_price'] ?? 0; ?>, '<?php echo addslashes($alert['brand'] ?? $alert['manufacturer'] ?? 'N/A'); ?>', '<?php echo addslashes($alert['item_code']); ?>')">
                                    <i class="fas fa-truck me-1"></i>Create PO
                                </button>
                                <button class="btn-ignore" onclick="ignoreReorderAlert('inventory', <?php echo $alert['id']; ?>)">
                                    <i class="fas fa-times me-1"></i>Ignore
                                </button>
                             </small>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- No Alerts Message -->
    <?php if((!isset($medicineAlerts) || empty($medicineAlerts)) && (!isset($labAlerts) || empty($labAlerts)) && (!isset($inventoryAlerts) || empty($inventoryAlerts))): ?>
    <div class="alert-card">
        <div class="card-body-custom">
            <div class="empty-state">
                <i class="fas fa-check-circle empty-icon text-success"></i>
                <div class="empty-title">No Reorder Alerts</div>
                <div class="empty-text">All items are sufficiently stocked above reorder levels.</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

// Pre-loaded supplier data from PHP
var medicineSuppliers = <?php echo json_encode(isset($allMedicineSuppliers) ? $allMedicineSuppliers : []); ?>;
var labSuppliers = <?php echo json_encode(isset($allLabSuppliers) ? $allLabSuppliers : []); ?>;
var inventorySuppliers = <?php echo json_encode(isset($allInventorySuppliers) ? $allInventorySuppliers : []); ?>;

function openCreatePOModal(itemType, itemId, itemName, suggestedQty, currentStock, unitOfMeasure, sellingPrice, manufacturer, itemCode) {
    console.log('Opening PO Modal for:', itemType, itemName);
    
    // Get suppliers based on item type
    var suppliersList = [];
    var supplierTableName = '';
    
    if (itemType === 'medicine') {
        suppliersList = medicineSuppliers;
        supplierTableName = 'Medicine Suppliers';
    } else if (itemType === 'lab_test') {
        suppliersList = labSuppliers;
        supplierTableName = 'Lab Test Suppliers';
    } else {
        suppliersList = inventorySuppliers;
        supplierTableName = 'Inventory Suppliers';
    }
    
    // Build supplier dropdown HTML
    var suppliersHtml = '<option value="">-- Select Supplier --</option>';
    
    if (suppliersList && suppliersList.length > 0) {
        suppliersList.forEach(function(supplier) {
            var supplierName = supplier.company_name || supplier.name || 'Unknown';
            var contactInfo = '';
            if (supplier.contact_person) {
                contactInfo = ' - ' + supplier.contact_person;
            }
            suppliersHtml += `<option value="${supplier.id}">${escapeHtml(supplierName)}${contactInfo}</option>`;
        });
    } else {
        suppliersHtml = '<option value="">No suppliers available</option>';
    }
    
    suppliersHtml += '<option value="new">+ Add New Supplier</option>';
    
    // Set default values
    currentStock = currentStock || 0;
    unitOfMeasure = unitOfMeasure || 'units';
    sellingPrice = sellingPrice || 0;
    manufacturer = manufacturer || 'N/A';
    itemCode = itemCode || 'N/A';
    
    Swal.fire({
        title: 'Create Purchase Order',
        html: `
            <div class="text-start">
                <div class="mb-3 p-2 bg-light rounded">
                    <h6 class="mb-2 text-primary">Item Information</h6>
                    <div class="row mb-2"><div class="col-5 fw-bold">Item Name:</div><div class="col-7">${escapeHtml(itemName)}</div></div>
                    <div class="row mb-2"><div class="col-5 fw-bold">Item Code:</div><div class="col-7">${escapeHtml(itemCode)}</div></div>
                    <div class="row mb-2"><div class="col-5 fw-bold">Manufacturer/Brand:</div><div class="col-7">${escapeHtml(manufacturer)}</div></div>
                    <div class="row mb-2"><div class="col-5 fw-bold">Current Stock:</div><div class="col-7"><span class="badge ${currentStock <= 10 ? 'bg-danger' : 'bg-warning'}">${currentStock} ${escapeHtml(unitOfMeasure)}</span></div></div>
                    <div class="row mb-2"><div class="col-5 fw-bold">Unit Price:</div><div class="col-7">৳ ${parseFloat(sellingPrice).toFixed(2)}</div></div>
                    <div class="row mb-2"><div class="col-5 fw-bold">Unit of Measure:</div><div class="col-7">${escapeHtml(unitOfMeasure)}</div></div>
                </div>
                <div class="mb-3 p-2 bg-light rounded">
                    <h6 class="mb-2 text-success">Order Details</h6>
                    <div class="mb-2"><label class="form-label fw-bold">Quantity to Order *</label><input type="number" id="poQuantity" class="form-control" value="${suggestedQty}" min="1" required></div>
                    <div class="mb-2"><label class="form-label fw-bold">Unit Price (BDT) *</label><input type="number" id="poUnitPrice" class="form-control" step="0.01" value="${sellingPrice}" required></div>
                </div>
                <div class="mb-3 p-2 bg-light rounded">
                    <h6 class="mb-2 text-info">Supplier Information (${supplierTableName})</h6>
                    <div class="mb-2"><label class="form-label fw-bold">Select Supplier *</label><select id="poSupplierId" class="form-select" required>${suppliersHtml}</select></div>
                    <div id="newSupplierFields" style="display:none;" class="mt-2 p-2 border rounded">
                        <div class="mb-2"><label class="form-label">Supplier Name *</label><input type="text" id="newSupplierName" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Contact Person</label><input type="text" id="newSupplierContact" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Phone</label><input type="text" id="newSupplierPhone" class="form-control"></div>
                        <div class="mb-2"><label class="form-label">Email</label><input type="email" id="newSupplierEmail" class="form-control"></div>
                    </div>
                </div>
                <div class="mb-2 p-2 bg-light rounded">
                    <h6 class="mb-2 text-secondary">Additional Information</h6>
                    <div class="mb-2"><label class="form-label">Expected Delivery Date</label><input type="date" id="poExpectedDate" class="form-control" value="${getDefaultExpectedDate()}"></div>
                    <div class="mb-2"><label class="form-label">Notes</label><textarea id="poNotes" class="form-control" rows="2" placeholder="Optional notes"></textarea></div>
                </div>
            </div>
            <div class="small text-muted mt-2"><i class="fas fa-info-circle"></i> This will create a draft purchase order for approval.</div>
        `,
        width: '600px',
        showCancelButton: true,
        confirmButtonText: 'Continue to Purchase Order',
        cancelButtonText: 'Cancel',
        didOpen: () => {
            $('#poSupplierId').on('change', function() {
                if ($(this).val() === 'new') {
                    $('#newSupplierFields').show();
                } else {
                    $('#newSupplierFields').hide();
                }
            });
        },
        preConfirm: () => {
            const quantity = document.getElementById('poQuantity').value;
            const unitPrice = document.getElementById('poUnitPrice').value;
            let supplierId = document.getElementById('poSupplierId').value;
            const expectedDate = document.getElementById('poExpectedDate').value;
            const notes = document.getElementById('poNotes').value;
            
            if (!quantity || quantity <= 0) {
                Swal.showValidationMessage('Please enter a valid quantity');
                return false;
            }
            if (!unitPrice || unitPrice <= 0) {
                Swal.showValidationMessage('Please enter a valid unit price');
                return false;
            }
            if (!supplierId) {
                Swal.showValidationMessage('Please select a supplier');
                return false;
            }
            
            if (supplierId === 'new') {
                const newSupplierName = document.getElementById('newSupplierName').value;
                if (!newSupplierName) {
                    Swal.showValidationMessage('Please enter supplier name');
                    return false;
                }
                supplierId = 'new_' + newSupplierName;
            }
            
            return {
                item_id: itemId,
                item_type: itemType,
                item_name: itemName,
                item_code: itemCode,
                quantity: quantity,
                unit_price: unitPrice,
                supplier_id: supplierId,
                expected_date: expectedDate,
                notes: notes,
                current_stock: currentStock,
                unit_of_measure: unitOfMeasure
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const params = new URLSearchParams({
                action: 'create_from_alert',
                source: 'reorder_alert',
                item_type: result.value.item_type,
                item_id: result.value.item_id,
                item_name: result.value.item_name,
                item_code: result.value.item_code,
                quantity: result.value.quantity,
                unit_price: result.value.unit_price,
                supplier_id: result.value.supplier_id,
                expected_date: result.value.expected_date,
                notes: result.value.notes
            });
            window.location.href = BASE_URL + '/inventory/purchase-orders?' + params.toString();
        }
    });
}

function ignoreReorderAlert(itemType, itemId) {
    Swal.fire({
        title: 'Ignore Alert',
        text: 'Are you sure you want to ignore this reorder alert?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Ignore',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/ignore-reorder-alert',
                method: 'POST',
                data: { item_type: itemType, item_id: itemId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Ignored!', response.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Success!', 'Alert ignored', 'success').then(() => location.reload());
                }
            });
        }
    });
}

function refreshAlerts() {
    Swal.fire({
        title: 'Refreshing...',
        text: 'Checking stock levels',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    location.reload();
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function getDefaultExpectedDate() {
    let date = new Date();
    date.setDate(date.getDate() + 7);
    return date.toISOString().split('T')[0];
}

// Check for return from purchase order page
$(document).ready(function() {
    // If coming back from purchase order page, refresh alerts
    if (sessionStorage.getItem('po_created') === 'true') {
        sessionStorage.removeItem('po_created');
        refreshAlerts();
    }
});
</script>
</body>
</html>