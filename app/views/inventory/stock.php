<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Details - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        
        .info-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
            margin-bottom: 25px;
            height: 100%;
        }
        .card-header-custom {
            padding: 14px 20px;
            font-weight: 600;
            font-size: 14px;
            border-bottom: 1px solid #e9ecef;
            background: #f8fafc;
        }
        .card-body-custom {
            padding: 20px;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 12px;
        }
        
        .badge-success { background: #d1fae5; color: #10b981; }
        .badge-warning { background: #fef3c7; color: #d97706; }
        .badge-danger { background: #fee2e2; color: #ef4444; }
        .badge-info { background: #e0f2fe; color: #0284c7; }
        
        .stock-table {
            width: 100%;
            border-collapse: collapse;
        }
        .stock-table th {
            padding: 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }
        .stock-table td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .stock-table tr:hover { background: #fafbfc; }
        
        .expiring-soon { background: #fef3c7; }
        .expired { background: #fee2e2; text-decoration: line-through; opacity: 0.6; }
        
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
        
        .price-tag {
            font-size: 18px;
            font-weight: 700;
            color: #10b981;
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
        
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-boxes text-success me-2"></i>Stock Details</h1>
            <p class="page-subtitle">View complete stock information for this item</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?php echo BASE_URL; ?>/inventory/items" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>Back to Items
            </a>
            <a href="<?php echo BASE_URL; ?>/inventory/add-stock?item_id=<?php echo isset($item['id']) ? $item['id'] : 0; ?>&item_name=<?php echo isset($item['item_name']) ? urlencode($item['item_name']) : ''; ?>&type=<?php echo isset($item['item_type']) ? $item['item_type'] : 'other'; ?>" class="btn-add-stock">
                <i class="fas fa-plus me-2"></i>Add Stock
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Item Information Column -->
        <div class="col-md-4">
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-info-circle me-2 text-primary"></i>Item Information
                </div>
                <div class="card-body-custom">
                    <div class="info-label">Item Code</div>
                    <div class="info-value">
                        <span class="code-badge"><?php echo isset($item['item_code']) ? htmlspecialchars($item['item_code']) : 'N/A'; ?></span>
                    </div>
                    
                    <div class="info-label">Item Name</div>
                    <div class="info-value"><strong><?php echo isset($item['item_name']) ? htmlspecialchars($item['item_name']) : 'N/A'; ?></strong></div>
                    
                    <div class="info-label">Generic Name</div>
                    <div class="info-value"><?php echo isset($item['generic_name']) && !empty($item['generic_name']) ? htmlspecialchars($item['generic_name']) : 'N/A'; ?></div>
                    
                    <div class="info-label">Category</div>
                    <div class="info-value"><?php echo isset($item['category_name']) ? htmlspecialchars($item['category_name']) : 'Uncategorized'; ?></div>
                    
                    <div class="info-label">Item Type</div>
                    <div class="info-value">
                        <?php 
                            $typeIcon = '';
                            if(isset($item['item_type'])) {
                                if($item['item_type'] == 'medicine') $typeIcon = '💊 Medicine';
                                elseif($item['item_type'] == 'equipment') $typeIcon = '🔧 Equipment';
                                elseif($item['item_type'] == 'other') $typeIcon = '📋 Other';
                                else $typeIcon = ucfirst($item['item_type']);
                            } else {
                                $typeIcon = 'N/A';
                            }
                            echo $typeIcon;
                        ?>
                    </div>
                    
                    <div class="info-label">Manufacturer</div>
                    <div class="info-value"><?php echo isset($item['manufacturer']) && !empty($item['manufacturer']) ? htmlspecialchars($item['manufacturer']) : 'N/A'; ?></div>
                    
                    <div class="info-label">Unit of Measure</div>
                    <div class="info-value"><?php echo isset($item['unit_of_measure']) ? htmlspecialchars($item['unit_of_measure']) : 'N/A'; ?></div>
                    
                    <div class="info-label">Pack Size</div>
                    <div class="info-value"><?php echo isset($item['pack_size']) && !empty($item['pack_size']) ? htmlspecialchars($item['pack_size']) : 'N/A'; ?></div>
                    
                    <div class="info-label">Strength</div>
                    <div class="info-value"><?php echo isset($item['strength']) && !empty($item['strength']) ? htmlspecialchars($item['strength']) : 'N/A'; ?></div>
                    
                    <div class="info-label">Brand</div>
                    <div class="info-value"><?php echo isset($item['brand']) && !empty($item['brand']) ? htmlspecialchars($item['brand']) : 'N/A'; ?></div>
                    
                    <div class="info-label">Supplier</div>
                    <div class="info-value"><?php echo isset($item['supplier_name']) && !empty($item['supplier_name']) ? htmlspecialchars($item['supplier_name']) : 'N/A'; ?></div>
                </div>
            </div>
        </div>
        
        <!-- Pricing & Stock Column -->
        <div class="col-md-4">
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-chart-line me-2 text-success"></i>Pricing & Stock
                </div>
                <div class="card-body-custom">
                    <div class="info-label">Purchase Price</div>
                    <div class="info-value">৳ <?php echo isset($item['purchase_price']) ? number_format((float)$item['purchase_price'], 2) : '0.00'; ?></div>
                    
                    <div class="info-label">Selling Price</div>
                    <div class="info-value"><span class="price-tag">৳ <?php echo isset($item['selling_price']) ? number_format((float)$item['selling_price'], 2) : '0.00'; ?></span></div>
                    
                    <div class="info-label">MRP</div>
                    <div class="info-value">৳ <?php echo isset($item['mrp']) ? number_format((float)$item['mrp'], 2) : '0.00'; ?></div>
                    
                    <div class="info-label">Tax Percentage</div>
                    <div class="info-value"><?php echo isset($item['tax_percentage']) ? (float)$item['tax_percentage'] : '0'; ?>%</div>
                    
                    <div class="info-label">Current Stock</div>
                    <div class="info-value">
                        <?php 
                            $currentStock = isset($item['current_stock']) ? (int)$item['current_stock'] : 0;
                            $unitOfMeasure = isset($item['unit_of_measure']) ? htmlspecialchars($item['unit_of_measure']) : 'units';
                            $stockClass = $currentStock <= 0 ? 'danger' : ($currentStock <= ($item['reorder_level'] ?? 10) ? 'warning' : 'success');
                        ?>
                        <span class="badge bg-<?php echo $stockClass; ?> px-3 py-2">
                            <?php echo $currentStock; ?> <?php echo $unitOfMeasure; ?>
                        </span>
                    </div>
                    
                    <div class="info-label">Reorder Level</div>
                    <div class="info-value"><?php echo isset($item['reorder_level']) ? (int)$item['reorder_level'] : 10; ?> <?php echo $unitOfMeasure; ?></div>
                    
                    <div class="info-label">Reorder Quantity</div>
                    <div class="info-value"><?php echo isset($item['reorder_quantity']) ? (int)$item['reorder_quantity'] : 50; ?> <?php echo $unitOfMeasure; ?></div>
                    
                    <div class="info-label">Min Stock Level</div>
                    <div class="info-value"><?php echo isset($item['min_stock_level']) ? (int)$item['min_stock_level'] : 5; ?> <?php echo $unitOfMeasure; ?></div>
                    
                    <div class="info-label">Max Stock Level</div>
                    <div class="info-value"><?php echo isset($item['max_stock_level']) ? (int)$item['max_stock_level'] : 100; ?> <?php echo $unitOfMeasure; ?></div>
                    
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="badge bg-<?php echo (isset($item['status']) && $item['status'] == 'active') ? 'success' : 'danger'; ?>">
                            <?php echo isset($item['status']) ? ucfirst($item['status']) : 'Active'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Storage Information Column -->
        <div class="col-md-4">
            <div class="info-card">
                <div class="card-header-custom">
                    <i class="fas fa-warehouse me-2 text-info"></i>Storage Information
                </div>
                <div class="card-body-custom">
                    <div class="info-label">Storage Location</div>
                    <div class="info-value"><?php echo isset($item['storage_location']) && !empty($item['storage_location']) ? htmlspecialchars($item['storage_location']) : 'Not specified'; ?></div>
                    
                    <div class="info-label">Storage Condition</div>
                    <div class="info-value"><?php echo isset($item['storage_condition']) && !empty($item['storage_condition']) ? htmlspecialchars($item['storage_condition']) : 'Room temperature'; ?></div>
                    
                    <div class="info-label">Requires Refrigeration</div>
                    <div class="info-value">
                        <?php echo (isset($item['requires_refrigeration']) && $item['requires_refrigeration']) ? '<span class="badge bg-info">Yes</span>' : '<span class="badge bg-secondary">No</span>'; ?>
                    </div>
                    
                    <div class="info-label">Warranty</div>
                    <div class="info-value"><?php echo isset($item['warranty']) && $item['warranty'] > 0 ? $item['warranty'] . ' months' : 'No warranty'; ?></div>
                    
                    <div class="info-label">Serial Number</div>
                    <div class="info-value">
                        <?php if(isset($item['serial_number']) && !empty($item['serial_number'])): ?>
                            <span class="code-badge"><?php echo htmlspecialchars($item['serial_number']); ?></span>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </div>
                    
                    <div class="info-label">Description</div>
                    <div class="info-value"><?php echo isset($item['description']) && !empty($item['description']) ? nl2br(htmlspecialchars($item['description'])) : 'No description available'; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Batches Table -->
    <div class="info-card">
        <div class="card-header-custom">
            <i class="fas fa-boxes me-2 text-warning"></i>Stock Batches
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="stock-table">
                    <thead>
                        <tr>
                            <th>Batch #</th>
                            <th>Expiry Date</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Selling Price</th>
                            <th>Location</th>
                            <th>Status</th>
                        </thead>
                    <tbody>
                        <?php if(isset($stockBatches) && !empty($stockBatches)): ?>
                            <?php foreach($stockBatches as $batch): ?>
                            <tr class="<?php 
                                echo (isset($batch['expiry_date']) && $batch['expiry_date'] < date('Y-m-d')) ? 'expired' : 
                                    ((isset($batch['expiry_date']) && $batch['expiry_date'] <= date('Y-m-d', strtotime('+30 days'))) ? 'expiring-soon' : ''); 
                            ?>">
                                <td><?php echo isset($batch['batch_number']) ? htmlspecialchars($batch['batch_number']) : 'N/A'; ?></td>
                                <td>
                                    <?php 
                                        if(isset($batch['expiry_date']) && !empty($batch['expiry_date'])) {
                                            echo date('d M Y', strtotime($batch['expiry_date']));
                                            if($batch['expiry_date'] < date('Y-m-d')) {
                                                echo ' <span class="badge bg-danger">Expired</span>';
                                            } elseif($batch['expiry_date'] <= date('Y-m-d', strtotime('+30 days'))) {
                                                echo ' <span class="badge bg-warning">Expiring</span>';
                                            }
                                        } else {
                                            echo 'N/A';
                                        }
                                    ?>
                                </td>
                                <td><?php echo isset($batch['quantity']) ? (int)$batch['quantity'] : 0; ?> <?php echo isset($item['unit_of_measure']) ? htmlspecialchars($item['unit_of_measure']) : 'units'; ?></td>
                                <td>৳ <?php echo isset($batch['unit_cost']) ? number_format((float)$batch['unit_cost'], 2) : '0.00'; ?></td>
                                <td>৳ <?php echo isset($batch['selling_price']) ? number_format((float)$batch['selling_price'], 2) : '0.00'; ?></td>
                                <td><?php echo isset($batch['location']) && !empty($batch['location']) ? htmlspecialchars($batch['location']) : 'N/A'; ?></td>
                                <td>
                                    <?php 
                                        $batchQty = isset($batch['quantity']) ? (int)$batch['quantity'] : 0;
                                        $batchStatus = $batchQty <= 0 ? 'Out of Stock' : 'In Stock';
                                        $batchStatusClass = $batchQty <= 0 ? 'danger' : 'success';
                                    ?>
                                    <span class="badge bg-<?php echo $batchStatusClass; ?>">
                                        <?php echo $batchStatus; ?>
                                    </span>
                                 </small>
                             </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                    No stock batches found for this item.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>