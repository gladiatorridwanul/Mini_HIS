<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplier Management - UniDia Healthcare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background: #f5f7fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { padding: 20px 25px; }
        
        .page-title { font-size: 24px; font-weight: 700; color: #1f2937; margin-bottom: 5px; }
        .page-subtitle { color: #6c757d; font-size: 13px; margin-bottom: 20px; }
        
        .supplier-card {
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
        
        .btn-add-supplier {
            background: #10b981;
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
        }
        .btn-add-supplier:hover { background: #059669; color: white; }
        
        .supplier-table {
            width: 100%;
            border-collapse: collapse;
        }
        .supplier-table th {
            padding: 14px 12px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }
        .supplier-table td {
            padding: 14px 12px;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
        }
        .supplier-table tr:hover { background: #fafbfc; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-active { background: #d1fae5; color: #10b981; }
        .status-inactive { background: #fee2e2; color: #ef4444; }
        
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
            .supplier-table th, .supplier-table td { padding: 10px 6px; }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="page-title"><i class="fas fa-truck text-success me-2"></i>Supplier Management</h1>
            <p class="page-subtitle">Manage your product suppliers and vendors</p>
        </div>
        <div>
            <button class="btn-add-supplier" onclick="openAddSupplierModal()">
                <i class="fas fa-plus me-2"></i>Add Supplier
            </button>
        </div>
    </div>

    <!-- Suppliers Table -->
    <div class="supplier-card">
        <div class="card-header-custom">
            <h5><i class="fas fa-list me-2"></i>Supplier List</h5>
            <span class="badge bg-secondary"><?php echo isset($suppliers) ? count($suppliers) : 0; ?> suppliers</span>
        </div>
        <div class="table-responsive">
            <table class="supplier-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(isset($suppliers) && !empty($suppliers)): ?>
                        <?php foreach($suppliers as $supplier): ?>
                        <tr>
                            <td>
                                <span class="code-badge"><?php echo htmlspecialchars($supplier['supplier_code']); ?></span>
                             </small>
                            <td>
                                <strong><?php echo htmlspecialchars($supplier['company_name']); ?></strong>
                                <?php if(isset($supplier['tax_id']) && !empty($supplier['tax_id'])): ?>
                                    <br><small class="text-muted">Tax ID: <?php echo htmlspecialchars($supplier['tax_id']); ?></small>
                                <?php endif; ?>
                             </small>
                            <td><?php echo isset($supplier['contact_person']) && !empty($supplier['contact_person']) ? htmlspecialchars($supplier['contact_person']) : 'N/A'; ?></small>
                            <td><?php echo htmlspecialchars($supplier['phone']); ?></small>
                            <td><?php echo isset($supplier['email']) && !empty($supplier['email']) ? htmlspecialchars($supplier['email']) : 'N/A'; ?></small>
                            <td>
                                <span class="status-badge <?php echo ($supplier['status'] ?? 'active') == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo isset($supplier['status']) ? ucfirst($supplier['status']) : 'Active'; ?>
                                </span>
                             </small>
                            <td>
                                <button class="btn btn-info btn-sm-custom" onclick="viewSupplier(<?php echo $supplier['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                                <button class="btn btn-primary btn-sm-custom" onclick="editSupplier(<?php echo $supplier['id']; ?>)">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-danger btn-sm-custom" onclick="deleteSupplier(<?php echo $supplier['id']; ?>, '<?php echo htmlspecialchars($supplier['company_name']); ?>')">
                                    <i class="fas fa-trash me-1"></i>Delete
                                </button>
                             </small>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-truck empty-icon"></i>
                                    <div class="empty-title">No Suppliers Found</div>
                                    <div class="empty-text">Click "Add Supplier" to add your first supplier.</div>
                                </div>
                             </small>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Supplier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSupplierForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Company Name *</label>
                            <input type="text" name="company_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Phone *</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control" placeholder="e.g., Net 30 days">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tax ID / VAT Number</label>
                            <input type="text" name="tax_id" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitAddSupplier()">Save Supplier</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Supplier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSupplierForm">
                    <input type="hidden" name="id" id="edit_supplier_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Company Name *</label>
                            <input type="text" name="company_name" id="edit_company_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" id="edit_contact_person" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Phone *</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" id="edit_city" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Payment Terms</label>
                            <input type="text" name="payment_terms" id="edit_payment_terms" class="form-control" placeholder="e.g., Net 30 days">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tax ID / VAT Number</label>
                            <input type="text" name="tax_id" id="edit_tax_id" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitEditSupplier()">Update Supplier</button>
            </div>
        </div>
    </div>
</div>

<!-- View Supplier Modal -->
<div class="modal fade" id="viewSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Supplier Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewSupplierContent">
                <!-- Dynamic content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function openAddSupplierModal() {
    $('#addSupplierForm')[0].reset();
    $('#addSupplierModal').modal('show');
}

function submitAddSupplier() {
    let formData = $('#addSupplierForm').serialize();
    
    Swal.fire({
        title: 'Saving...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/inventory/add-supplier',
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
            Swal.fire('Error!', 'Failed to add supplier', 'error');
        }
    });
}

function viewSupplier(id) {
    $.ajax({
        url: BASE_URL + '/inventory/get-supplier/' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let supplier = response.supplier;
                let html = `
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Supplier Code</label>
                        <p><strong class="code-badge">${supplier.supplier_code}</strong></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Company Name</label>
                        <p><strong>${supplier.company_name}</strong></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Contact Person</label>
                        <p>${supplier.contact_person || 'N/A'}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Email</label>
                        <p>${supplier.email || 'N/A'}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Phone</label>
                        <p>${supplier.phone}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Address</label>
                        <p>${supplier.address || 'N/A'}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">City</label>
                        <p>${supplier.city || 'N/A'}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Payment Terms</label>
                        <p>${supplier.payment_terms || 'N/A'}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Tax ID</label>
                        <p>${supplier.tax_id || 'N/A'}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Status</label>
                        <p><span class="status-badge ${supplier.status == 'active' ? 'status-active' : 'status-inactive'}">${supplier.status.toUpperCase()}</span></p>
                    </div>
                `;
                $('#viewSupplierContent').html(html);
                $('#viewSupplierModal').modal('show');
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to load supplier details', 'error');
        }
    });
}

function editSupplier(id) {
    $.ajax({
        url: BASE_URL + '/inventory/get-supplier/' + id,
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                let supplier = response.supplier;
                $('#edit_supplier_id').val(supplier.id);
                $('#edit_company_name').val(supplier.company_name);
                $('#edit_contact_person').val(supplier.contact_person);
                $('#edit_email').val(supplier.email);
                $('#edit_phone').val(supplier.phone);
                $('#edit_address').val(supplier.address);
                $('#edit_city').val(supplier.city);
                $('#edit_payment_terms').val(supplier.payment_terms);
                $('#edit_tax_id').val(supplier.tax_id);
                $('#edit_status').val(supplier.status);
                $('#editSupplierModal').modal('show');
            } else {
                Swal.fire('Error!', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error!', 'Failed to load supplier details', 'error');
        }
    });
}

function submitEditSupplier() {
    let formData = $('#editSupplierForm').serialize();
    
    Swal.fire({
        title: 'Updating...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/inventory/update-supplier',
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
            Swal.fire('Error!', 'Failed to update supplier', 'error');
        }
    });
}

function deleteSupplier(id, companyName) {
    Swal.fire({
        title: 'Delete Supplier?',
        html: `Are you sure you want to delete <strong>${companyName}</strong>?<br>This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#ef4444'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/inventory/delete-supplier',
                method: 'POST',
                data: { supplier_id: id },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Deleted!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to delete supplier', 'error');
                }
            });
        }
    });
}
</script>
</body>
</html>