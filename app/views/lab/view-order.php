<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .result-normal { color: #10b981; font-weight: 500; }
    .result-abnormal { color: #ef4444; font-weight: bold; }
    .info-card { border-left: 4px solid; margin-bottom: 20px; }
    .info-card.primary { border-left-color: #3b82f6; }
    .info-card.info { border-left-color: #06b6d4; }
    .info-card.warning { border-left-color: #f59e0b; }
    .info-card.success { border-left-color: #10b981; }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-flask text-success me-2"></i>Lab Order Details</h2>
            <p class="text-muted small mb-0">View and manage order information</p>
        </div>
        <div class="mt-2 mt-sm-0">
            <?php if($order['status'] == 'ordered'): ?>
            <button class="btn btn-success me-2" onclick="collectSample(<?php echo $order['id']; ?>)">
                <i class="fas fa-syringe me-2"></i>Collect Sample
            </button>
            <?php endif; ?>
            <?php if($order['status'] == 'completed'): ?>
            <a href="<?php echo BASE_URL; ?>/lab/report/<?php echo $order['id']; ?>" class="btn btn-info me-2" target="_blank">
                <i class="fas fa-file-alt me-2"></i>View Report
            </a>
            <a href="<?php echo BASE_URL; ?>/lab/invoice/<?php echo $order['id']; ?>" class="btn btn-warning me-2" target="_blank">
                <i class="fas fa-file-invoice me-2"></i>Invoice
            </a>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/lab/orders" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Orders
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow mb-4 info-card primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td style="width: 40%"><strong>Order #:</strong></td><td><?php echo $order['order_number']; ?></td></tr>
                        <tr><td><strong>Order Date:</strong></td><td><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></td></tr>
                        <tr><td><strong>Priority:</strong></td><td>
                            <span class="badge bg-<?php echo $order['priority'] == 'stat' ? 'danger' : ($order['priority'] == 'urgent' ? 'warning' : 'info'); ?>">
                                <?php echo strtoupper($order['priority']); ?>
                            </span>
                         </td></td>
                        <tr><td><strong>Status:</strong></td><td>
                            <span class="status-badge bg-<?php echo $order['status'] == 'completed' ? 'success' : 'warning'; ?> text-white">
                                <?php echo strtoupper(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                         </td> </tr>
                        <tr><td><strong>Ordered By:</strong></td><td><?php echo $order['ordered_by_name']; ?></td> </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow mb-4 info-card info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Patient & Doctor Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td style="width: 40%"><strong>Patient:</strong></td><td><?php echo $order['patient_name']; ?></td> </tr>
                        <tr><td><strong>Phone:</strong></td><td><?php echo $order['phone']; ?></td> </tr>
                        <tr><td><strong>Gender:</strong></td><td><?php echo ucfirst($order['gender']); ?></td> </tr>
                        <tr><td><strong>Date of Birth:</strong></td><td><?php echo date('d M Y', strtotime($order['date_of_birth'])); ?></td> </tr>
                        <tr><td><strong>Doctor:</strong></td><td>Dr. <?php echo $order['doctor_name']; ?></td> </tr>
                        <?php if($order['clinical_diagnosis']): ?>
                        <tr><td><strong>Clinical Diagnosis:</strong></td><td><?php echo nl2br(htmlspecialchars($order['clinical_diagnosis'])); ?></td> </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-vial me-2"></i>Test Items</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Test Name</th>
                            <th>Category</th>
                            <th>Specimen</th>
                            <th>Sample Barcode</th>
                            <th>Normal Range</th>
                            <th>Result</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $item): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['test_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['specimen_type'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if($item['sample_barcode']): ?>
                                <span class="badge bg-info"><?php echo $item['sample_barcode']; ?></span>
                                <br><small class="text-muted">Collected: <?php echo $item['sample_collected_at'] ? date('d M Y', strtotime($item['sample_collected_at'])) : '-'; ?></small>
                                <?php else: ?>
                                <span class="text-muted">Not collected</span>
                                <?php endif; ?>
                              </small></td>
                            <td><small><?php echo nl2br(htmlspecialchars($item['normal_range'] ?? 'N/A')); ?></small></td>
                            <td>
                                <?php if($item['result_value']): ?>
                                    <span class="<?php echo $item['is_abnormal'] ? 'result-abnormal' : 'result-normal'; ?>">
                                        <?php echo htmlspecialchars($item['result_value']); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                    </span>
                                    <?php if($item['is_abnormal']): ?>
                                    <br><span class="badge bg-danger mt-1">Abnormal</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Pending</span>
                                <?php endif; ?>
                              </small></td>
                            <td>
                                <span class="badge bg-<?php echo $item['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $item['status'])); ?>
                                </span>
                             </small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function collectSample(orderId) {
    Swal.fire({
        title: 'Collect Sample',
        text: 'Generate barcode for sample collection?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, collect',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({
                title: 'Collecting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: BASE_URL + '/lab/collect-sample',
                method: 'POST',
                data: {order_id: orderId},
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Sample Collected!',
                            html: 'Barcode: <strong>' + response.barcode + '</strong>',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                        printBarcode(response.barcode);
                    } else {
                        Swal.fire('Error', response.message || 'Failed to collect sample', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to collect sample', 'error');
                }
            });
        }
    });
}

function printBarcode(barcode) {
    let win = window.open('', '_blank');
    win.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Barcode</title>
            <style>
                @page { size: 80mm auto; margin: 0; }
                body { font-family: monospace; width: 80mm; margin: 0 auto; padding: 10px; text-align: center; }
                .header { border-bottom: 1px dashed #000; padding-bottom: 8px; margin-bottom: 15px; }
                .title { font-size: 14px; font-weight: bold; }
                .barcode-box { border: 2px solid #000; padding: 15px; margin: 15px 0; }
                .barcode { font-size: 24px; font-weight: bold; letter-spacing: 2px; }
                .footer { margin-top: 15px; border-top: 1px dashed #000; padding-top: 8px; font-size: 9px; }
                @media print { .no-print { display: none; } }
                button { margin: 10px; padding: 8px 16px; cursor: pointer; background: #10b981; color: white; border: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="title">UNIDIA HOSPITAL</div>
                <div>Sample Barcode</div>
            </div>
            <div class="barcode-box">
                <div class="barcode">${barcode}</div>
            </div>
            <div class="footer">Collection Date: ${new Date().toLocaleDateString()}<br>Collector Signature: _________________</div>
            <div class="no-print"><button onclick="window.print()">Print</button><button onclick="window.close()">Close</button></div>
            <script>window.onload = function() { setTimeout(function() { window.print(); }, 500); }<\/script>
        </body>
        </html>
    `);
    win.document.close();
}
</script>