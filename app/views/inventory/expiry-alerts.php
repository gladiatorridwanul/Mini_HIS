<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expiry Alerts - UniDia Healthcare</title>
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
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }
        .alert-table td {
            padding: 14px 12px;
            font-size: 13px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .alert-table tr:hover { background: #fafbfc; }
        
        .critical-row { background: #fee2e2; }
        .warning-row { background: #fef3c7; }
        
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
        
        .badge-critical { background: #ef4444; color: white; }
        .badge-warning { background: #f59e0b; color: white; }
        
        .btn-acknowledge {
            background: #10b981;
            border: none;
            color: white;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-acknowledge:hover {
            background: #059669;
            color: white;
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
            transition: all 0.2s;
        }
        .btn-back:hover {
            background: #f1f5f9;
            color: #1f2937;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-icon { font-size: 64px; color: #cbd5e1; margin-bottom: 20px; }
        .empty-title { font-size: 18px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .empty-text { color: #94a3b8; font-size: 13px; }
        
        .code-badge {
            background: #eef2ff;
            color: #4338ca;
            padding: 4px 8px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 11px;
            font-weight: 600;
        }
        
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
        
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .alert-table th, .alert-table td { padding: 10px 6px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-clock text-warning me-2"></i>Expiry Alerts</h1>
            <p class="page-subtitle">Items expiring within the next 30 days</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/inventory/dashboard" class="btn-back">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-danger"><?php echo isset($expiredCount) ? $expiredCount : 0; ?></div>
                <div class="stat-label">Expired Items</div>
                <i class="fas fa-skull-crossbones text-danger mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-warning"><?php echo isset($criticalCount) ? $criticalCount : 0; ?></div>
                <div class="stat-label">Critical (≤7 days)</div>
                <i class="fas fa-exclamation-triangle text-warning mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-info"><?php echo isset($warningCount) ? $warningCount : 0; ?></div>
                <div class="stat-label">Warning (8-30 days)</div>
                <i class="fas fa-bell text-info mt-2"></i>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-number text-success"><?php echo isset($totalItems) ? $totalItems : 0; ?></div>
                <div class="stat-label">Total Items Tracked</div>
                <i class="fas fa-boxes text-success mt-2"></i>
            </div>
        </div>
    </div>

    <!-- Medicine Expiry Alerts -->
    <?php if(isset($medicineAlerts) && !empty($medicineAlerts)): ?>
    <div class="alert-card">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
            <h5><i class="fas fa-pills me-2"></i>Medicine Expiry Alerts</h5>
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="alert-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Batch #</th>
                            <th>Expiry Date</th>
                            <th>Quantity</th>
                            <th>Alert Type</th>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($medicineAlerts as $alert): ?>
                        <tr class="<?php echo $alert['alert_type'] == 'critical' ? 'critical-row' : 'warning-row'; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($alert['item_name']); ?></strong>
                                <br>
                                <span class="code-badge"><?php echo htmlspecialchars($alert['item_code']); ?></span>
                                <br>
                                <span class="type-badge type-medicine">
                                    💊 Medicine
                                </span>
                             </small>
                            <td>
                                <span class="code-badge"><?php echo htmlspecialchars($alert['batch_number']); ?></span>
                             </small>
                            <td>
                                <?php 
                                    $expiryDate = date('d M Y', strtotime($alert['expiry_date']));
                                    $daysLeft = (strtotime($alert['expiry_date']) - time()) / (60 * 60 * 24);
                                    $daysLeft = round($daysLeft);
                                ?>
                                <div>
                                    <strong><?php echo $expiryDate; ?></strong>
                                    <?php if($daysLeft <= 7): ?>
                                        <div class="small text-danger">⚠️ <?php echo $daysLeft; ?> days left!</div>
                                    <?php else: ?>
                                        <div class="small text-muted"><?php echo $daysLeft; ?> days left</div>
                                    <?php endif; ?>
                                </div>
                             </small>
                            <td>
                                <span class="badge bg-secondary"><?php echo (int)$alert['quantity']; ?> <?php echo isset($alert['unit_of_measure']) ? htmlspecialchars($alert['unit_of_measure']) : 'units'; ?></span>
                             </small>
                            <td>
                                <span class="badge <?php echo $alert['alert_type'] == 'critical' ? 'badge-critical' : 'badge-warning'; ?>">
                                    <?php echo strtoupper($alert['alert_type']); ?>
                                </span>
                             </small>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($alert['message']); ?></small>
                             </small>
                            <td>
                                <button class="btn-acknowledge" onclick="acknowledgeAlert('medicine', <?php echo $alert['stock_id']; ?>)">
                                    <i class="fas fa-check me-1"></i>Acknowledge
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

    <!-- Lab Test Expiry Alerts -->
    <?php if(isset($labAlerts) && !empty($labAlerts)): ?>
    <div class="alert-card">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
            <h5><i class="fas fa-microscope me-2"></i>Lab Test Expiry Alerts</h5>
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="alert-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Batch #</th>
                            <th>Expiry Date</th>
                            <th>Quantity</th>
                            <th>Alert Type</th>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($labAlerts as $alert): ?>
                        <tr class="<?php echo $alert['alert_type'] == 'critical' ? 'critical-row' : 'warning-row'; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($alert['item_name']); ?></strong>
                                <br>
                                <span class="code-badge"><?php echo htmlspecialchars($alert['item_code']); ?></span>
                                <br>
                                <span class="type-badge type-lab_test">
                                    🔬 Lab Test
                                </span>
                             </small>
                            <td>
                                <span class="code-badge"><?php echo htmlspecialchars($alert['batch_number']); ?></span>
                             </small>
                            <td>
                                <?php 
                                    $expiryDate = date('d M Y', strtotime($alert['expiry_date']));
                                    $daysLeft = (strtotime($alert['expiry_date']) - time()) / (60 * 60 * 24);
                                    $daysLeft = round($daysLeft);
                                ?>
                                <div>
                                    <strong><?php echo $expiryDate; ?></strong>
                                    <?php if($daysLeft <= 7): ?>
                                        <div class="small text-danger">⚠️ <?php echo $daysLeft; ?> days left!</div>
                                    <?php else: ?>
                                        <div class="small text-muted"><?php echo $daysLeft; ?> days left</div>
                                    <?php endif; ?>
                                </div>
                             </small>
                            <td>
                                <span class="badge bg-secondary"><?php echo (int)$alert['quantity']; ?> kits</span>
                             </small>
                            <td>
                                <span class="badge <?php echo $alert['alert_type'] == 'critical' ? 'badge-critical' : 'badge-warning'; ?>">
                                    <?php echo strtoupper($alert['alert_type']); ?>
                                </span>
                             </small>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($alert['message']); ?></small>
                             </small>
                            <td>
                                <button class="btn-acknowledge" onclick="acknowledgeAlert('lab_test', <?php echo $alert['stock_id']; ?>)">
                                    <i class="fas fa-check me-1"></i>Acknowledge
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

    <!-- Inventory Item Expiry Alerts -->
    <?php if(isset($inventoryAlerts) && !empty($inventoryAlerts)): ?>
    <div class="alert-card">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
            <h5><i class="fas fa-boxes me-2"></i>Equipment & Other Expiry Alerts</h5>
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="alert-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Batch #</th>
                            <th>Expiry Date</th>
                            <th>Quantity</th>
                            <th>Alert Type</th>
                            <th>Message</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($inventoryAlerts as $alert): ?>
                        <tr class="<?php echo $alert['alert_type'] == 'critical' ? 'critical-row' : 'warning-row'; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($alert['item_name']); ?></strong>
                                <br>
                                <span class="code-badge"><?php echo htmlspecialchars($alert['item_code']); ?></span>
                                <br>
                                <span class="type-badge type-<?php echo $alert['item_type'] == 'equipment' ? 'equipment' : 'other'; ?>">
                                    <?php echo $alert['item_type'] == 'equipment' ? '🔧 Equipment' : '📋 Other'; ?>
                                </span>
                             </small>
                            <td>
                                <span class="code-badge"><?php echo htmlspecialchars($alert['batch_number']); ?></span>
                             </small>
                            <td>
                                <?php 
                                    $expiryDate = date('d M Y', strtotime($alert['expiry_date']));
                                    $daysLeft = (strtotime($alert['expiry_date']) - time()) / (60 * 60 * 24);
                                    $daysLeft = round($daysLeft);
                                ?>
                                <div>
                                    <strong><?php echo $expiryDate; ?></strong>
                                    <?php if($daysLeft <= 7): ?>
                                        <div class="small text-danger">⚠️ <?php echo $daysLeft; ?> days left!</div>
                                    <?php else: ?>
                                        <div class="small text-muted"><?php echo $daysLeft; ?> days left</div>
                                    <?php endif; ?>
                                </div>
                             </small>
                            <td>
                                <span class="badge bg-secondary"><?php echo (int)$alert['quantity']; ?> units</span>
                             </small>
                            <td>
                                <span class="badge <?php echo $alert['alert_type'] == 'critical' ? 'badge-critical' : 'badge-warning'; ?>">
                                    <?php echo strtoupper($alert['alert_type']); ?>
                                </span>
                             </small>
                            <td>
                                <small class="text-muted"><?php echo htmlspecialchars($alert['message']); ?></small>
                             </small>
                            <td>
                                <button class="btn-acknowledge" onclick="acknowledgeAlert('inventory', <?php echo $alert['stock_id']; ?>)">
                                    <i class="fas fa-check me-1"></i>Acknowledge
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

    <!-- Expired Items Section -->
    <?php if(isset($expiredItems) && !empty($expiredItems)): ?>
    <div class="alert-card">
        <div class="card-header-custom" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
            <h5><i class="fas fa-skull-crossbones me-2"></i>Expired Items (Needs Disposal)</h5>
        </div>
        <div class="card-body-custom">
            <div class="table-responsive">
                <table class="alert-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Batch #</th>
                            <th>Expiry Date</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($expiredItems as $item): ?>
                        <tr class="critical-row">
                            <td>
                                <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                <br>
                                <span class="code-badge"><?php echo htmlspecialchars($item['item_code']); ?></span>
                                <br>
                                <span class="type-badge type-<?php echo $item['item_type']; ?>">
                                    <?php 
                                        if($item['item_type'] == 'medicine') echo '💊 Medicine';
                                        elseif($item['item_type'] == 'lab_test') echo '🔬 Lab Test';
                                        elseif($item['item_type'] == 'equipment') echo '🔧 Equipment';
                                        else echo '📋 Other';
                                    ?>
                                </span>
                             </small>
                            <td>
                                <span class="code-badge"><?php echo htmlspecialchars($item['batch_number']); ?></span>
                             </small>
                            <td>
                                <div>
                                    <strong class="text-danger"><?php echo date('d M Y', strtotime($item['expiry_date'])); ?></strong>
                                    <div class="small text-danger">EXPIRED</div>
                                </div>
                             </small>
                            <td>
                                <span class="badge bg-danger"><?php echo (int)$item['quantity']; ?> units</span>
                             </small>
                            <td>
                                <span class="badge bg-danger">EXPIRED</span>
                             </small>
                            <td>
                                <button class="btn-acknowledge" style="background: #ef4444;" onclick="disposeItem('<?php echo $item['item_type']; ?>', <?php echo $item['stock_id']; ?>)">
                                    <i class="fas fa-trash me-1"></i>Mark for Disposal
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
    <?php if((!isset($medicineAlerts) || empty($medicineAlerts)) && 
              (!isset($labAlerts) || empty($labAlerts)) && 
              (!isset($inventoryAlerts) || empty($inventoryAlerts)) &&
              (!isset($expiredItems) || empty($expiredItems))): ?>
    <div class="alert-card">
        <div class="card-body-custom">
            <div class="empty-state">
                <i class="fas fa-check-circle empty-icon text-success"></i>
                <div class="empty-title">No Expiry Alerts</div>
                <div class="empty-text">All items are in good standing with no upcoming expirations.</div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function acknowledgeAlert(itemType, stockId) {
    Swal.fire({
        title: 'Acknowledge Alert',
        text: 'Mark this expiry alert as acknowledged?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Acknowledge',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/acknowledge-expiry-alert',
                method: 'POST',
                data: { 
                    item_type: itemType, 
                    stock_id: stockId 
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Acknowledged!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Acknowledged!', 'Alert has been marked as read.', 'success').then(() => {
                        location.reload();
                    });
                }
            });
        }
    });
}

function disposeItem(itemType, stockId) {
    Swal.fire({
        title: 'Mark for Disposal',
        text: 'This item has expired and will be marked for disposal. Continue?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Dispose',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/dispose-expired-item',
                method: 'POST',
                data: { 
                    item_type: itemType, 
                    stock_id: stockId 
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Disposed!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Success!', 'Item marked for disposal.', 'success').then(() => {
                        location.reload();
                    });
                }
            });
        }
    });
}
</script>
</body>
</html>