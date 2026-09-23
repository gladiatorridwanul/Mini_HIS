<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order Details - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        
        .po-detail-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            overflow: hidden;
            margin-bottom: 25px;
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
            width: 160px;
            font-weight: 600;
            color: #4b5563;
            font-size: 13px;
        }
        .info-value {
            flex: 1;
            color: #1f2937;
            font-size: 13px;
        }
        
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
        
        .po-table {
            width: 100%;
            border-collapse: collapse;
        }
        .po-table th {
            padding: 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
        }
        .po-table td {
            padding: 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .po-table tr:hover { background: #fafbfc; }
        
        .total-row {
            background: #ecfdf5;
            font-weight: 600;
        }
        
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
        
        .btn-action {
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        @media print {
            .no-print { display: none; }
            body { background: white; padding: 0; margin: 0; }
            .container-fluid { padding: 0; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-file-invoice text-success me-2"></i>Purchase Order Details</h1>
            <p class="page-subtitle">View complete purchase order information</p>
        </div>
        <div class="no-print">
            <a href="<?php echo BASE_URL; ?>/inventory/purchase-orders" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
            <button class="btn-back ms-2" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print
            </button>
            <?php if($order['status'] == 'pending_approval'): ?>
            <button class="btn-back btn-action" style="background: #10b981; color: white; border: none;" onclick="approvePO(<?php echo $order['id']; ?>)">
                <i class="fas fa-check me-1"></i>Approve
            </button>
            <?php endif; ?>
            <?php if($order['status'] == 'ordered'): ?>
            <button class="btn-back btn-action" style="background: #3b82f6; color: white; border: none;" onclick="receivePO(<?php echo $order['id']; ?>)">
                <i class="fas fa-boxes me-1"></i>Receive
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- PO Information -->
    <div class="po-detail-card">
        <div class="card-header-custom">
            <h5><i class="fas fa-shopping-cart me-2"></i>Purchase Order: <span class="code-badge"><?php echo htmlspecialchars($order['po_number']); ?></span></h5>
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
        </div>
        <div class="card-body-custom" style="padding: 24px;">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-section">
                        <div class="section-title">
                            <i class="fas fa-building"></i>
                            <span>Supplier Information</span>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Company Name:</div>
                            <div class="info-value"><strong><?php echo htmlspecialchars($order['supplier_name'] ?? 'N/A'); ?></strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Phone:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['supplier_phone'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Email:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['supplier_email'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Address:</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['supplier_address'] ?? 'N/A')); ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="info-section">
                        <div class="section-title">
                            <i class="fas fa-store"></i>
                            <span>Order Information</span>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Store:</div>
                            <div class="info-value"><strong><?php echo htmlspecialchars($order['store_name'] ?? 'N/A'); ?></strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Order Date:</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($order['order_date'])); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Expected Delivery:</div>
                            <div class="info-value"><?php echo $order['expected_delivery_date'] ? date('d M Y', strtotime($order['expected_delivery_date'])) : 'Not specified'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Created By:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['created_by_name'] ?? 'System'); ?></div>
                        </div>
                        <?php if(isset($order['approved_by_name']) && $order['approved_by_name']): ?>
                        <div class="info-row">
                            <div class="info-label">Approved By:</div>
                            <div class="info-value"><?php echo htmlspecialchars($order['approved_by_name']); ?> on <?php echo isset($order['approved_at']) ? date('d M Y', strtotime($order['approved_at'])) : 'N/A'; ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if(isset($order['source']) && $order['source'] == 'reorder_alert'): ?>
                        <div class="info-row">
                            <div class="info-label">Source:</div>
                            <div class="info-value"><span class="source-badge source-reorder_alert">🔄 Reorder Alert</span></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Items Table -->
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-boxes"></i>
                    <span>Order Items</span>
                </div>
                <div class="table-responsive">
                    <table class="po-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th class="text-end">Unit Price (৳)</th>
                                <th class="text-end">Total (৳)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; ?>
                            <?php foreach($poItems as $item): ?>
                            <tr>
                                <td><?php echo $counter++; ?></small>
                                <td><span class="code-badge"><?php echo htmlspecialchars($item['item_code']); ?></span></small>
                                <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></small>
                                <td><?php echo htmlspecialchars($item['unit_of_measure'] ?? 'pcs'); ?></small>
                                <td class="text-center"><?php echo $item['quantity']; ?></small>
                                <td class="text-end">৳ <?php echo number_format($item['unit_price'], 2); ?></small>
                                <td class="text-end">৳ <?php echo number_format($item['total_price'], 2); ?></small>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="6" class="text-end fw-bold">Subtotal:</td>
                                <td class="text-end fw-bold">৳ <?php echo number_format($order['subtotal'], 2); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="6" class="text-end fw-bold">Tax (5%):</td>
                                <td class="text-end fw-bold">৳ <?php echo number_format($order['tax_amount'], 2); ?></td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="6" class="text-end fw-bold">Total Amount:</td>
                                <td class="text-end fw-bold text-success">৳ <?php echo number_format($order['total_amount'], 2); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Notes -->
            <?php if(!empty($order['notes'])): ?>
            <div class="info-section">
                <div class="section-title">
                    <i class="fas fa-pencil-alt"></i>
                    <span>Notes</span>
                </div>
                <div class="info-value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

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
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to approve purchase order', 'error');
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
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to receive purchase order', 'error');
                }
            });
        }
    });
}
</script>
</body>
</html>