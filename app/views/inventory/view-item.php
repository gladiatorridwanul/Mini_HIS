<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Details - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        
        .detail-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
            margin-bottom: 25px;
        }
        .card-header-custom {
            padding: 18px 24px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header-custom h5 { margin: 0; font-weight: 600; }
        .card-body-custom { padding: 24px; }
        
        .info-section {
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
        
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            width: 180px;
            font-weight: 600;
            color: #4b5563;
            font-size: 13px;
        }
        .info-value {
            flex: 1;
            color: #1f2937;
            font-size: 13px;
        }
        
        .stock-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .stock-high { background: #d1fae5; color: #10b981; }
        .stock-medium { background: #fef3c7; color: #d97706; }
        .stock-low { background: #fee2e2; color: #ef4444; }
        .stock-out { background: #f1f5f9; color: #64748b; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-active { background: #d1fae5; color: #10b981; }
        .status-inactive { background: #fee2e2; color: #ef4444; }
        
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
        
        .btn-back {
            background: white;
            border: 1px solid #e2e8f0;
            color: #64748b;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: #f1f5f9;
            color: #1f2937;
        }
        
        .btn-add-stock {
            background: #10b981;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-add-stock:hover {
            background: #059669;
            color: white;
        }
        
        .code-badge {
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
            font-weight: 600;
        }
        
        .price-tag {
            font-size: 18px;
            font-weight: 700;
            color: #10b981;
        }
        
        @media (max-width: 768px) {
            .info-label { width: 130px; }
            .card-header-custom { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="page-title"><i class="fas fa-info-circle text-success me-2"></i>Item Details</h1>
            <p class="page-subtitle">View complete information about this inventory item</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?php echo BASE_URL; ?>/inventory/items" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>Back to List
            </a>
            <?php if(isset($itemType) && $itemType != 'lab_test'): ?>
            <a href="<?php echo BASE_URL; ?>/inventory/add-stock?item_id=<?php echo isset($item['id']) ? $item['id'] : 0; ?>&item_name=<?php echo isset($item['item_name']) ? urlencode($item['item_name']) : ''; ?>&type=<?php echo isset($itemType) ? $itemType : ''; ?>" class="btn-add-stock">
                <i class="fas fa-plus me-2"></i>Add Stock
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Detail Card -->
    <div class="detail-card">
        <div class="card-header-custom">
            <h5><i class="fas fa-cube me-2"></i>
                <?php 
                    if(isset($item['item_name']) && $item['item_name'] != 'N/A') {
                        echo htmlspecialchars($item['item_name']);
                    } elseif(isset($item['medicine_name'])) {
                        echo htmlspecialchars($item['medicine_name']);
                    } elseif(isset($item['test_name'])) {
                        echo htmlspecialchars($item['test_name']);
                    } else {
                        echo 'Item Details';
                    }
                ?>
            </h5>
            <span class="type-badge type-<?php echo isset($itemType) ? $itemType : 'other'; ?>">
                <?php 
                    $typeIcon = '';
                    $typeName = '';
                    if(isset($itemType)) {
                        if($itemType == 'medicine') {
                            $typeIcon = '💊';
                            $typeName = 'Medicine';
                        } elseif($itemType == 'lab_test') {
                            $typeIcon = '🔬';
                            $typeName = 'Lab Test';
                        } elseif($itemType == 'equipment') {
                            $typeIcon = '🔧';
                            $typeName = 'Equipment';
                        } else {
                            $typeIcon = '📋';
                            $typeName = 'Other';
                        }
                    }
                    echo $typeIcon . ' ' . $typeName;
                ?>
            </span>
        </div>
        <div class="card-body-custom">
            
            <!-- Basic Information -->
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-info-circle"></i>
                    <span>Basic Information</span>
                </div>
                <div class="info-row">
                    <div class="info-label">Item Code:</div>
                    <div class="info-value">
                        <span class="code-badge">
                            <?php 
                                if(isset($item['item_code']) && $item['item_code'] != 'N/A') {
                                    echo htmlspecialchars($item['item_code']);
                                } elseif(isset($item['medicine_code'])) {
                                    echo htmlspecialchars($item['medicine_code']);
                                } elseif(isset($item['test_code'])) {
                                    echo htmlspecialchars($item['test_code']);
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Item Name:</div>
                    <div class="info-value">
                        <strong>
                            <?php 
                                if(isset($item['item_name']) && $item['item_name'] != 'N/A') {
                                    echo htmlspecialchars($item['item_name']);
                                } elseif(isset($item['medicine_name'])) {
                                    echo htmlspecialchars($item['medicine_name']);
                                } elseif(isset($item['test_name'])) {
                                    echo htmlspecialchars($item['test_name']);
                                } else {
                                    echo 'N/A';
                                }
                            ?>
                        </strong>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Generic Name:</div>
                    <div class="info-value">
                        <?php 
                            if(isset($item['generic_name']) && $item['generic_name'] != 'N/A') {
                                echo htmlspecialchars($item['generic_name']);
                            } elseif(isset($item['test_name'])) {
                                echo htmlspecialchars($item['test_name']);
                            } else {
                                echo 'N/A';
                            }
                        ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Category:</div>
                    <div class="info-value">
                        <?php 
                            if(isset($item['category_name']) && !empty($item['category_name'])) {
                                echo '<span class="badge bg-secondary">' . htmlspecialchars($item['category_name']) . '</span>';
                            } else {
                                echo 'Uncategorized';
                            }
                        ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <span class="status-badge <?php echo (isset($item['status']) && $item['status'] == 'active') ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo isset($item['status']) ? ucfirst($item['status']) : 'Active'; ?>
                        </span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Created Date:</div>
                    <div class="info-value">
                        <?php 
                            if(isset($item['created_at']) && !empty($item['created_at'])) {
                                echo date('d M Y, h:i A', strtotime($item['created_at']));
                            } else {
                                echo 'N/A';
                            }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Stock Information (for Medicine and Equipment only) -->
            <?php if(isset($itemType) && $itemType != 'lab_test'): ?>
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-chart-line"></i>
                    <span>Stock Information</span>
                </div>
                <div class="info-row">
                    <div class="info-label">Current Stock:</div>
                    <div class="info-value">
                        <?php 
                            $stockLevel = isset($item['current_stock']) ? (int)$item['current_stock'] : 0;
                            $reorderLevel = isset($item['reorder_level']) ? (int)$item['reorder_level'] : 10;
                            $unitOfMeasure = isset($item['unit_of_measure']) ? htmlspecialchars($item['unit_of_measure']) : 'units';
                            
                            if($stockLevel <= 0) {
                                $stockClass = 'stock-out';
                                $stockText = 'Out of Stock';
                            } elseif($stockLevel <= $reorderLevel) {
                                $stockClass = 'stock-low';
                                $stockText = 'Low Stock';
                            } elseif($stockLevel <= $reorderLevel * 2) {
                                $stockClass = 'stock-medium';
                                $stockText = 'Medium Stock';
                            } else {
                                $stockClass = 'stock-high';
                                $stockText = 'Good Stock';
                            }
                        ?>
                        <span class="stock-badge <?php echo $stockClass; ?>">
                            <?php echo $stockLevel; ?> <?php echo $unitOfMeasure; ?>
                        </span>
                        <span class="ms-2 text-muted">(<?php echo $stockText; ?>)</span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Reorder Level:</div>
                    <div class="info-value"><?php echo $reorderLevel; ?> <?php echo $unitOfMeasure; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Unit of Measure:</div>
                    <div class="info-value"><?php echo $unitOfMeasure; ?></div>
                </div>
                <?php if(isset($item['batch_number']) && !empty($item['batch_number']) && $item['batch_number'] != 'N/A'): ?>
                <div class="info-row">
                    <div class="info-label">Batch Number:</div>
                    <div class="info-value"><?php echo htmlspecialchars($item['batch_number']); ?></div>
                </div>
                <?php endif; ?>
                <?php if(isset($item['expiry_date']) && !empty($item['expiry_date']) && $item['expiry_date'] != 'N/A'): ?>
                <div class="info-row">
                    <div class="info-label">Expiry Date:</div>
                    <div class="info-value">
                        <?php echo date('d M Y', strtotime($item['expiry_date'])); ?>
                        <?php if(strtotime($item['expiry_date']) < time()): ?>
                            <span class="badge bg-danger ms-2">Expired</span>
                        <?php elseif(strtotime($item['expiry_date']) < strtotime('+30 days')): ?>
                            <span class="badge bg-warning ms-2">Expiring Soon</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Pricing Information -->
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Pricing Information</span>
                </div>
                <div class="info-row">
                    <div class="info-label">Purchase Price:</div>
                    <div class="info-value">৳ <?php echo isset($item['purchase_price']) ? number_format((float)$item['purchase_price'], 2) : '0.00'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Selling Price:</div>
                    <div class="info-value">
                        <span class="price-tag">৳ <?php echo isset($item['selling_price']) ? number_format((float)$item['selling_price'], 2) : '0.00'; ?></span>
                    </div>
                </div>
                <?php if(isset($item['mrp']) && !empty($item['mrp']) && (float)$item['mrp'] > 0): ?>
                <div class="info-row">
                    <div class="info-label">MRP:</div>
                    <div class="info-value">৳ <?php echo number_format((float)$item['mrp'], 2); ?></div>
                </div>
                <?php endif; ?>
                <?php if(isset($item['tax_percentage']) && !empty($item['tax_percentage']) && (float)$item['tax_percentage'] > 0): ?>
                <div class="info-row">
                    <div class="info-label">Tax Percentage:</div>
                    <div class="info-value"><?php echo (float)$item['tax_percentage']; ?>%</div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Medicine Specific Information -->
            <?php if(isset($itemType) && $itemType == 'medicine'): ?>
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-pills"></i>
                    <span>Medicine Information</span>
                </div>
                <div class="info-row">
                    <div class="info-label">Manufacturer:</div>
                    <div class="info-value"><?php echo isset($item['manufacturer']) && !empty($item['manufacturer']) ? htmlspecialchars($item['manufacturer']) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Strength:</div>
                    <div class="info-value"><?php echo isset($item['strength']) && !empty($item['strength']) ? htmlspecialchars($item['strength']) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Dosage Form:</div>
                    <div class="info-value"><?php echo isset($item['dosage_form']) && !empty($item['dosage_form']) ? htmlspecialchars($item['dosage_form']) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Requires Prescription:</div>
                    <div class="info-value">
                        <?php 
                            $requiresRx = isset($item['requires_prescription']) ? $item['requires_prescription'] : 0;
                            echo $requiresRx ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-success">No</span>';
                        ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Supplier:</div>
                    <div class="info-value"><?php echo isset($item['supplier_name']) && !empty($item['supplier_name']) ? htmlspecialchars($item['supplier_name']) : 'N/A'; ?></div>
                </div>
                <?php if(isset($item['side_effects']) && !empty($item['side_effects'])): ?>
                <div class="info-row">
                    <div class="info-label">Side Effects:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($item['side_effects'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Lab Test Specific Information -->
            <?php if(isset($itemType) && $itemType == 'lab_test'): ?>
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-microscope"></i>
                    <span>Lab Test Information</span>
                </div>
                <div class="info-row">
                    <div class="info-label">Test Code:</div>
                    <div class="info-value"><span class="code-badge"><?php echo isset($item['test_code']) ? htmlspecialchars($item['test_code']) : 'N/A'; ?></span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Specimen Type:</div>
                    <div class="info-value"><?php echo isset($item['specimen_type']) && !empty($item['specimen_type']) ? htmlspecialchars($item['specimen_type']) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Turnaround Time:</div>
                    <div class="info-value"><?php echo isset($item['turnaround_time']) && !empty($item['turnaround_time']) ? $item['turnaround_time'] . ' hours' : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Normal Range:</div>
                    <div class="info-value"><?php echo isset($item['normal_range']) && !empty($item['normal_range']) ? htmlspecialchars($item['normal_range']) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Unit:</div>
                    <div class="info-value"><?php echo isset($item['unit']) && !empty($item['unit']) ? htmlspecialchars($item['unit']) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Requires Fasting:</div>
                    <div class="info-value">
                        <?php 
                            $requiresFasting = isset($item['requires_fasting']) ? $item['requires_fasting'] : 0;
                            echo $requiresFasting ? '<span class="badge bg-warning">Yes</span>' : '<span class="badge bg-success">No</span>';
                        ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Supplier:</div>
                    <div class="info-value"><?php echo isset($item['supplier_name']) && !empty($item['supplier_name']) ? htmlspecialchars($item['supplier_name']) : 'N/A'; ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Equipment/Other Specific Information -->
            <?php if(isset($itemType) && ($itemType == 'equipment' || $itemType == 'other')): ?>
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-tools"></i>
                    <span>Item Specifications</span>
                </div>
                <div class="info-row">
                    <div class="info-label">Brand:</div>
                    <div class="info-value"><?php echo isset($item['brand']) && !empty($item['brand']) ? htmlspecialchars($item['brand']) : 'N/A'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Warranty:</div>
                    <div class="info-value"><?php echo isset($item['warranty']) && $item['warranty'] > 0 ? $item['warranty'] . ' months' : 'No warranty'; ?></div>
                </div>
                <?php if(isset($item['serial_number']) && !empty($item['serial_number'])): ?>
                <div class="info-row">
                    <div class="info-label">Serial Number:</div>
                    <div class="info-value"><span class="code-badge"><?php echo htmlspecialchars($item['serial_number']); ?></span></div>
                </div>
                <?php endif; ?>
                <?php if(isset($item['pack_size']) && !empty($item['pack_size'])): ?>
                <div class="info-row">
                    <div class="info-label">Pack Size:</div>
                    <div class="info-value"><?php echo htmlspecialchars($item['pack_size']); ?></div>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <div class="info-label">Supplier:</div>
                    <div class="info-value"><?php echo isset($item['supplier_name']) && !empty($item['supplier_name']) ? htmlspecialchars($item['supplier_name']) : 'N/A'; ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Storage Information -->
            <?php if(isset($itemType) && $itemType != 'lab_test'): ?>
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-warehouse"></i>
                    <span>Storage Information</span>
                </div>
                <div class="info-row">
                    <div class="info-label">Storage Location:</div>
                    <div class="info-value"><?php echo isset($item['storage_location']) && !empty($item['storage_location']) ? htmlspecialchars($item['storage_location']) : 'Not specified'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Storage Condition:</div>
                    <div class="info-value"><?php echo isset($item['storage_condition']) && !empty($item['storage_condition']) ? htmlspecialchars($item['storage_condition']) : 'Room temperature'; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Requires Refrigeration:</div>
                    <div class="info-value">
                        <?php 
                            $requiresRefrigeration = isset($item['requires_refrigeration']) ? $item['requires_refrigeration'] : 0;
                            echo $requiresRefrigeration ? '<span class="badge bg-info">Yes</span>' : '<span class="badge bg-secondary">No</span>';
                        ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Description -->
            <?php if(isset($item['description']) && !empty($item['description']) && $item['description'] != 'No description available'): ?>
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-file-alt"></i>
                    <span>Description</span>
                </div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($item['description'])); ?></div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>