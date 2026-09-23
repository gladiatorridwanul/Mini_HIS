<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transfers - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        
        .transfer-card {
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
        
        .btn-add-transfer {
            background: #10b981;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
        }
        .btn-add-transfer:hover { background: #059669; color: white; }
        
        .transfer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .transfer-table th {
            padding: 14px 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }
        .transfer-table td {
            padding: 14px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .transfer-table tr:hover { background: #fafbfc; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dbeafe; color: #2563eb; }
        .status-in_transit { background: #e0e7ff; color: #4f46e5; }
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
        
        @media (max-width: 768px) {
            .container-fluid { padding: 15px; }
            .transfer-table th, .transfer-table td { padding: 10px 6px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-exchange-alt text-success me-2"></i>Stock Transfers</h1>
            <p class="page-subtitle">Manage stock transfers between stores and warehouses</p>
        </div>
        <div>
            <button class="btn-add-transfer" onclick="openCreateTransferModal()">
                <i class="fas fa-plus me-2"></i>New Transfer
            </button>
        </div>
    </div>

    <!-- Transfers Table -->
    <div class="transfer-card">
        <div class="card-header-custom">
            <h5><i class="fas fa-history me-2"></i>Transfer History</h5>
            <span class="badge bg-secondary"><?php echo isset($transfers) ? count($transfers) : 0; ?> transfers</span>
        </div>
        <div class="table-responsive">
            <table class="transfer-table">
                <thead>
                    <tr>
                        <th>Transfer #</th>
                        <th>From Store</th>
                        <th>To Store</th>
                        <th>Transfer Date</th>
                        <th>Status</th>
                        <th>Requested By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($transfers) && !empty($transfers)): ?>
                        <?php foreach($transfers as $transfer): ?>
                        <tr>
                            <td>
                                <span class="code-badge"><?php echo htmlspecialchars($transfer['transfer_number']); ?></span>
                             </small>
                            <td><?php echo htmlspecialchars($transfer['from_store_name'] ?? 'N/A'); ?></small>
                            <td><?php echo htmlspecialchars($transfer['to_store_name'] ?? 'N/A'); ?></small>
                            <td><?php echo date('d M Y', strtotime($transfer['transfer_date'])); ?></small>
                            <td>
                                <span class="status-badge status-<?php echo $transfer['status']; ?>">
                                    <?php 
                                        $statusLabels = [
                                            'pending' => '⏳ Pending',
                                            'approved' => '✓ Approved',
                                            'in_transit' => '🚚 In Transit',
                                            'received' => '✅ Received',
                                            'cancelled' => '❌ Cancelled'
                                        ];
                                        echo $statusLabels[$transfer['status']] ?? ucfirst($transfer['status']);
                                    ?>
                                </span>
                             </small>
                            <td><?php echo htmlspecialchars($transfer['requested_by_name'] ?? 'N/A'); ?></small>
                            <td>
                                <?php if($transfer['status'] == 'pending'): ?>
                                <button class="btn btn-success btn-sm-custom" onclick="approveTransfer(<?php echo $transfer['id']; ?>)">
                                    <i class="fas fa-check me-1"></i>Approve
                                </button>
                                <?php elseif($transfer['status'] == 'approved'): ?>
                                <button class="btn btn-primary btn-sm-custom" onclick="receiveTransfer(<?php echo $transfer['id']; ?>)">
                                    <i class="fas fa-arrow-down me-1"></i>Receive
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-info btn-sm-custom" onclick="viewTransfer(<?php echo $transfer['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                             </small>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-exchange-alt empty-icon"></i>
                                    <div class="empty-title">No Transfers Found</div>
                                    <div class="empty-text">Click "New Transfer" to create a stock transfer between stores.</div>
                                </div>
                             </small>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Transfer Modal -->
<div class="modal fade" id="createTransferModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Create Stock Transfer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createTransferForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">From Store *</label>
                            <select name="from_store_id" class="form-select" required>
                                <option value="">Select Source Store</option>
                                <?php if(isset($stores) && !empty($stores)): ?>
                                    <?php foreach($stores as $store): ?>
                                    <option value="<?php echo $store['id']; ?>"><?php echo htmlspecialchars($store['store_name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">To Store *</label>
                            <select name="to_store_id" class="form-select" required>
                                <option value="">Select Destination Store</option>
                                <?php if(isset($stores) && !empty($stores)): ?>
                                    <?php foreach($stores as $store): ?>
                                    <option value="<?php echo $store['id']; ?>"><?php echo htmlspecialchars($store['store_name']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Transfer Date *</label>
                            <input type="date" name="transfer_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    
                    <hr>
                    <h6 class="mb-3"><i class="fas fa-boxes me-2"></i>Items to Transfer</h6>
                    <div id="transferItemsContainer">
                        <div class="row mb-2 transfer-item-row">
                            <div class="col-md-5">
                                <select name="items[0][item_id]" class="form-select" required>
                                    <option value="">Select Item</option>
                                    <?php if(isset($items) && !empty($items)): ?>
                                        <?php foreach($items as $item): ?>
                                        <option value="<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['item_name']); ?> (<?php echo htmlspecialchars($item['item_code']); ?>)</option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="number" name="items[0][quantity]" class="form-control" placeholder="Quantity" min="1" required>
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeTransferItem(this)">
                                    <i class="fas fa-trash me-1"></i>Remove
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" onclick="addTransferItem()">
                        <i class="fas fa-plus me-1"></i>Add Another Item
                    </button>
                    
                    <div class="mb-3 mt-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about this transfer..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitStockTransfer()">Create Transfer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let transferItemCounter = 1;

function openCreateTransferModal() {
    $('#createTransferForm')[0].reset();
    transferItemCounter = 1;
    $('#transferItemsContainer').html(`
        <div class="row mb-2 transfer-item-row">
            <div class="col-md-5">
                <select name="items[0][item_id]" class="form-select" required>
                    <option value="">Select Item</option>
                    <?php if(isset($items) && !empty($items)): ?>
                        <?php foreach($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>"><?php echo addslashes($item['item_name']); ?> (<?php echo $item['item_code']; ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" name="items[0][quantity]" class="form-control" placeholder="Quantity" min="1" required>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeTransferItem(this)">Remove</button>
            </div>
        </div>
    `);
    $('#createTransferModal').modal('show');
}

function addTransferItem() {
    let html = `
        <div class="row mb-2 transfer-item-row">
            <div class="col-md-5">
                <select name="items[${transferItemCounter}][item_id]" class="form-select" required>
                    <option value="">Select Item</option>
                    <?php if(isset($items) && !empty($items)): ?>
                        <?php foreach($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>"><?php echo addslashes($item['item_name']); ?> (<?php echo $item['item_code']; ?>)</option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-4">
                <input type="number" name="items[${transferItemCounter}][quantity]" class="form-control" placeholder="Quantity" min="1" required>
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeTransferItem(this)">Remove</button>
            </div>
        </div>
    `;
    $('#transferItemsContainer').append(html);
    transferItemCounter++;
}

function removeTransferItem(btn) {
    $(btn).closest('.transfer-item-row').remove();
}

function submitStockTransfer() {
    let fromStore = $('select[name="from_store_id"]').val();
    let toStore = $('select[name="to_store_id"]').val();
    
    if(fromStore == toStore) {
        Swal.fire('Error', 'Source and destination stores cannot be the same', 'error');
        return;
    }
    
    let formData = $('#createTransferForm').serialize();
    
    Swal.fire({
        title: 'Creating Transfer...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/inventory/create-transfer',
        method: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire('Success!', response.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to create transfer', 'error');
        }
    });
}

function approveTransfer(id) {
    Swal.fire({
        title: 'Approve Transfer',
        text: 'Are you sure you want to approve this transfer?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/approve-transfer',
                method: 'POST',
                data: { transfer_id: id },
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

function receiveTransfer(id) {
    Swal.fire({
        title: 'Receive Transfer',
        text: 'Confirm that you have received all items in this transfer?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Receive',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/receive-transfer',
                method: 'POST',
                data: { transfer_id: id },
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

function viewTransfer(id) {
    window.location.href = BASE_URL + '/inventory/view-transfer/' + id;
}
</script>
</body>
</html>