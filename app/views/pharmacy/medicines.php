<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .stock-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
    .stock-high { background: #d1fae5; color: #10b981; }
    .stock-medium { background: #fef3c7; color: #f59e0b; }
    .stock-low { background: #fee2e2; color: #ef4444; }
    .stock-out { background: #e5e7eb; color: #6b7280; }
    .modal-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
    .modal-header .btn-close { filter: brightness(0) invert(1); }
    .form-section { background: #f8fafc; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
    .form-section h6 { color: #1f2937; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #10b981; }
    .required-field::after { content: " *"; color: #ef4444; }
    .table-actions { display: flex; gap: 5px; flex-wrap: wrap; }
    .category-badge { background: #e0e7ff; color: #4338ca; padding: 3px 10px; border-radius: 15px; font-size: 11px; }
    .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; flex-wrap: wrap; }
    .pagination a, .pagination span { padding: 6px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #10b981; }
    .pagination .active { background: #10b981; color: white; border-color: #10b981; }
    .pagination .disabled { color: #999; cursor: not-allowed; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-pills text-success me-2"></i>Medicine Management</h2>
            <p class="text-muted small mb-0">Manage medicines, stock, and inventory</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-info" onclick="openCategoryModal()">
                <i class="fas fa-tags me-2"></i>Manage Categories
            </button>
            <button class="btn btn-success" onclick="openAddMedicineModal()">
                <i class="fas fa-plus me-2"></i>Add New Medicine
            </button>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="" id="filterForm" class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search by medicine name, generic name, or code..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="0">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo (isset($selectedCategory) && $selectedCategory == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Medicines Table -->
    <div class="card shadow">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Medicines List (<?php echo $totalRecords ?? 0; ?> records)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Medicine Name</th>
                            <th>Generic Name</th>
                            <th>Category</th>
                            <th>Strength</th>
                            <th>Selling Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="medicinesTableBody">
                        <?php if(!empty($medicines)): ?>
                            <?php foreach($medicines as $med): ?>
                            <tr>
                                <td><code><?php echo $med['medicine_code']; ?></code></td>
                                <td><strong><?php echo htmlspecialchars($med['medicine_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($med['generic_name'] ?? '-'); ?></td>
                                <td><span class="category-badge"><?php echo htmlspecialchars($med['category_name']); ?></span></td>
                                <td><?php echo htmlspecialchars($med['strength'] ?? '-'); ?></td>
                                <td class="text-success fw-bold">৳ <?php echo number_format($med['selling_price'], 2); ?></td>
                                <td>
                                    <?php 
                                    $stock = $med['current_stock'];
                                    $stockClass = $stock > 100 ? 'stock-high' : ($stock > 20 ? 'stock-medium' : ($stock > 0 ? 'stock-low' : 'stock-out'));
                                    ?>
                                    <span class="stock-badge <?php echo $stockClass; ?>">
                                        <?php echo $stock; ?> units
                                    </span>
                                 </small></td>
                                <td>
                                    <span class="badge bg-<?php echo $med['status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($med['status']); ?>
                                    </span>
                                 </small></td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-sm btn-outline-info" onclick="viewMedicine(<?php echo $med['id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-primary" onclick="editMedicine(<?php echo $med['id']; ?>)" title="Edit Medicine">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="openAddStockModal(<?php echo $med['id']; ?>, '<?php echo addslashes($med['medicine_name']); ?>')" title="Add Stock">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="viewStockHistory(<?php echo $med['id']; ?>)" title="Stock History">
                                            <i class="fas fa-history"></i>
                                        </button>
                                    </div>
                                 </small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">No medicines found</small><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if(isset($totalPages) && $totalPages > 1): ?>
    <div class="pagination">
        <?php if($currentPage > 1): ?>
            <a href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&category=<?php echo $selectedCategory ?? 0; ?>">&laquo; Previous</a>
        <?php else: ?>
            <span class="disabled">&laquo; Previous</span>
        <?php endif; ?>
        
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <?php if($i == $currentPage): ?>
                <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&category=<?php echo $selectedCategory ?? 0; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if($currentPage < $totalPages): ?>
            <a href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&category=<?php echo $selectedCategory ?? 0; ?>">Next &raquo;</a>
        <?php else: ?>
            <span class="disabled">Next &raquo;</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ==================== ADD MEDICINE MODAL ==================== -->
<div id="addMedicineModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Medicine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addMedicineForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required-field">Medicine Name</label>
                                <input type="text" name="medicine_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Generic Name</label>
                                <input type="text" name="generic_name" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" name="manufacturer" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Strength</label>
                                <input type="text" name="strength" class="form-control" placeholder="e.g., 500mg">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dosage Form</label>
                                <select name="dosage_form" class="form-select">
                                    <option value="Tablet">Tablet</option>
                                    <option value="Capsule">Capsule</option>
                                    <option value="Syrup">Syrup</option>
                                    <option value="Injection">Injection</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Unit of Measure</label>
                                <select name="unit_of_measure" class="form-select" required>
                                    <option value="Strip">Strip</option>
                                    <option value="Box">Box</option>
                                    <option value="Bottle">Bottle</option>
                                    <option value="Piece">Piece</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Purchase Price</label>
                                <input type="number" step="0.01" name="purchase_price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required-field">Selling Price</label>
                                <input type="number" step="0.01" name="selling_price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="requires_prescription" class="form-check-input" value="1">
                                    <label class="form-check-label">Requires Prescription</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6>Initial Stock (Optional)</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Initial Quantity</label>
                            <input type="number" name="initial_quantity" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Batch Number</label>
                            <input type="text" name="batch_number" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Storage Location</label>
                            <input type="text" name="storage_location" class="form-control">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitAddMedicine()">Save Medicine</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== EDIT MEDICINE MODAL ==================== -->
<div id="editMedicineModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Medicine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editMedicineForm">
                    <input type="hidden" name="medicine_id" id="edit_medicine_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Medicine Name</label>
                                <input type="text" name="medicine_name" id="edit_medicine_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Generic Name</label>
                                <input type="text" name="generic_name" id="edit_generic_name" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category_id" id="edit_category_id" class="form-select" required>
                                    <option value="">Select Category</option>
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Manufacturer</label>
                                <input type="text" name="manufacturer" id="edit_manufacturer" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Strength</label>
                                <input type="text" name="strength" id="edit_strength" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dosage Form</label>
                                <select name="dosage_form" id="edit_dosage_form" class="form-select">
                                    <option value="Tablet">Tablet</option>
                                    <option value="Capsule">Capsule</option>
                                    <option value="Syrup">Syrup</option>
                                    <option value="Injection">Injection</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Unit of Measure</label>
                                <select name="unit_of_measure" id="edit_unit_of_measure" class="form-select" required>
                                    <option value="Strip">Strip</option>
                                    <option value="Box">Box</option>
                                    <option value="Bottle">Bottle</option>
                                    <option value="Piece">Piece</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Purchase Price</label>
                                <input type="number" step="0.01" name="purchase_price" id="edit_purchase_price" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Selling Price</label>
                                <input type="number" step="0.01" name="selling_price" id="edit_selling_price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="requires_prescription" class="form-check-input" value="1" id="edit_requires_prescription">
                                    <label class="form-check-label">Requires Prescription</label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="discontinued">Discontinued</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h6>Additional Information</h6>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Side Effects</label>
                        <textarea name="side_effects" id="edit_side_effects" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Storage Condition</label>
                        <input type="text" name="storage_condition" id="edit_storage_condition" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitEditMedicine()">Update Medicine</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== ADD STOCK MODAL ==================== -->
<div id="addStockModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add Stock to <span id="stockMedicineNameDisplay" style="color: #ffd700;"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStockForm">
                    <input type="hidden" name="medicine_id" id="stock_medicine_id">
                    <div class="mb-3">
                        <label class="form-label">Medicine</label>
                        <input type="text" id="stock_medicine_name" class="form-control" readonly style="background:#e8f5e9; font-weight: bold; color: #2e7d32;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required-field">Batch Number</label>
                        <input type="text" name="batch_number" id="batch_number" class="form-control" required placeholder="e.g., BCH001">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Expiry Date</label>
                            <input type="date" name="expiry_date" id="expiry_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Manufacturing Date</label>
                            <input type="date" name="manufacturing_date" id="manufacturing_date" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Quantity</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" required min="1" value="1">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Purchase Price</label>
                            <input type="number" step="0.01" name="purchase_price" id="purchase_price" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Selling Price</label>
                            <input type="number" step="0.01" name="selling_price" id="selling_price" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Storage Location</label>
                            <input type="text" name="location" id="storage_location" class="form-control" placeholder="e.g., Shelf A-1">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitAddStock()">Add Stock</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== VIEW MEDICINE MODAL ==================== -->
<div id="viewMedicineModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Medicine Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="viewMedicineContent" class="text-center py-4">Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== STOCK HISTORY MODAL ==================== -->
<div id="stockHistoryModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-history me-2"></i>Stock History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="stockHistoryContent" class="text-center py-4">Loading...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== CATEGORY MANAGEMENT MODAL ==================== -->
<div id="categoryModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tags me-2"></i>Manage Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="input-group">
                        <input type="text" id="newCategoryName" class="form-control" placeholder="New category name">
                        <button class="btn btn-success" onclick="addCategory()">Add Category</button>
                    </div>
                </div>
                <hr>
                <h6>Existing Categories</h6>
                <div id="categoryList" class="mt-2">
                    <?php foreach($categories as $cat): ?>
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom">
                        <span><?php echo htmlspecialchars($cat['name']); ?></span>
                        <div>
                            <button class="btn btn-sm btn-warning me-1" onclick="editCategory(<?php echo $cat['id']; ?>, '<?php echo htmlspecialchars($cat['name']); ?>')">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $cat['id']; ?>)">Delete</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== EDIT CATEGORY MODAL ==================== -->
<div id="editCategoryModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="mb-3">
                        <label class="form-label required-field">Category Name</label>
                        <input type="text" name="name" id="edit_category_name" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitEditCategory()">Update Category</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

console.log('BASE_URL:', BASE_URL);

// ==================== ADD MEDICINE ====================
function openAddMedicineModal() {
    $('#addMedicineForm')[0].reset();
    $('#addMedicineModal').modal('show');
}

function submitAddMedicine() {
    $.ajax({
        url: BASE_URL + '/pharmacy/add-medicine',
        method: 'POST',
        data: $('#addMedicineForm').serialize(),
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire('Success', res.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function(xhr) { 
            console.error(xhr.responseText);
            Swal.fire('Error', 'Failed to add medicine', 'error'); 
        }
    });
}

// ==================== VIEW MEDICINE ====================
function viewMedicine(id) {
    console.log('View medicine ID:', id);
    $('#viewMedicineContent').html('<div class="spinner-border"></div> Loading...');
    $('#viewMedicineModal').modal('show');
    
    $.ajax({
        url: BASE_URL + '/pharmacy/get-medicine?id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                var m = res.medicine;
                var html = '<div class="row">' +
                    '<div class="col-md-6"><strong>Code:</strong> ' + (m.medicine_code || '-') + '</div>' +
                    '<div class="col-md-6"><strong>Name:</strong> ' + (m.medicine_name || '-') + '</div>' +
                    '<div class="col-md-6"><strong>Generic:</strong> ' + (m.generic_name || '-') + '</div>' +
                    '<div class="col-md-6"><strong>Category:</strong> ' + (m.category_name || '-') + '</div>' +
                    '<div class="col-md-6"><strong>Manufacturer:</strong> ' + (m.manufacturer || '-') + '</div>' +
                    '<div class="col-md-6"><strong>Strength:</strong> ' + (m.strength || '-') + '</div>' +
                    '<div class="col-md-6"><strong>Dosage Form:</strong> ' + (m.dosage_form || '-') + '</div>' +
                    '<div class="col-md-6"><strong>Unit:</strong> ' + (m.unit_of_measure || '-') + '</div>' +
                    '<div class="col-md-6"><strong>Purchase Price:</strong> ৳ ' + parseFloat(m.purchase_price || 0).toFixed(2) + '</div>' +
                    '<div class="col-md-6"><strong>Selling Price:</strong> ৳ ' + parseFloat(m.selling_price || 0).toFixed(2) + '</div>' +
                    '<div class="col-md-6"><strong>Current Stock:</strong> ' + (m.current_stock || 0) + ' units</div>' +
                    '<div class="col-md-6"><strong>Status:</strong> ' + (m.status || '-') + '</div>' +
                    '<div class="col-12 mt-2"><strong>Description:</strong> ' + (m.description || '-') + '</div>' +
                    '<div class="col-12 mt-2"><strong>Side Effects:</strong> ' + (m.side_effects || '-') + '</div>' +
                    '<div class="col-12 mt-2"><strong>Storage:</strong> ' + (m.storage_condition || '-') + '</div>' +
                    '</div>';
                $('#viewMedicineContent').html(html);
            } else {
                $('#viewMedicineContent').html('<div class="text-danger">' + (res.message || 'Medicine not found') + '</div>');
            }
        },
        error: function(xhr) { 
            console.error(xhr.responseText);
            $('#viewMedicineContent').html('<div class="text-danger">Error loading medicine details</div>'); 
        }
    });
}

// ==================== EDIT MEDICINE ====================
function editMedicine(id) {
    console.log('Edit medicine ID:', id);
    $.ajax({
        url: BASE_URL + '/pharmacy/get-medicine?id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                var m = res.medicine;
                $('#edit_medicine_id').val(m.id);
                $('#edit_medicine_name').val(m.medicine_name);
                $('#edit_generic_name').val(m.generic_name || '');
                $('#edit_category_id').val(m.category_id);
                $('#edit_manufacturer').val(m.manufacturer || '');
                $('#edit_strength').val(m.strength || '');
                $('#edit_dosage_form').val(m.dosage_form || 'Tablet');
                $('#edit_unit_of_measure').val(m.unit_of_measure);
                $('#edit_purchase_price').val(m.purchase_price);
                $('#edit_selling_price').val(m.selling_price);
                $('#edit_description').val(m.description || '');
                $('#edit_side_effects').val(m.side_effects || '');
                $('#edit_storage_condition').val(m.storage_condition || '');
                $('#edit_status').val(m.status || 'active');
                $('#edit_requires_prescription').prop('checked', m.requires_prescription == 1);
                $('#editMedicineModal').modal('show');
            } else {
                Swal.fire('Error', res.message || 'Medicine not found', 'error');
            }
        },
        error: function(xhr) { 
            console.error(xhr.responseText);
            Swal.fire('Error', 'Failed to load medicine data', 'error'); 
        }
    });
}

function submitEditMedicine() {
    $.ajax({
        url: BASE_URL + '/pharmacy/update-medicine',
        method: 'POST',
        data: $('#editMedicineForm').serialize(),
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire('Success', res.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function() { Swal.fire('Error', 'Failed to update medicine', 'error'); }
    });
}

// ==================== ADD STOCK - COMPLETELY FIXED ====================
function openAddStockModal(id, name) {
    console.log('Open add stock - ID:', id, 'Name:', name);
    
    if(!id || id <= 0) {
        Swal.fire('Error', 'Invalid medicine ID', 'error');
        return;
    }
    
    if(!name || name === '') {
        Swal.fire('Error', 'Medicine name not found', 'error');
        return;
    }
    
    // Clear all form fields first
    $('#addStockForm')[0].reset();
    
    // Set the values in the modal
    $('#stock_medicine_id').val(id);
    $('#stock_medicine_name').val(name);
    $('#stockMedicineNameDisplay').text(name);
    
    // Set default expiry date to 1 year from now
    var today = new Date();
    var oneYearLater = new Date(today);
    oneYearLater.setFullYear(today.getFullYear() + 1);
    var expiryDate = oneYearLater.toISOString().split('T')[0];
    $('#expiry_date').val(expiryDate);
    
    // Show the modal
    $('#addStockModal').modal('show');
}

function submitAddStock() {
    console.log('=== Submitting Add Stock ===');
    
    // Get form values using IDs (more reliable)
    var medicineId = $('#stock_medicine_id').val();
    var medicineName = $('#stock_medicine_name').val();
    var batchNumber = $('#batch_number').val();
    var expiryDate = $('#expiry_date').val();
    var quantity = $('#quantity').val();
    var purchasePrice = $('#purchase_price').val();
    var sellingPrice = $('#selling_price').val();
    var location = $('#storage_location').val();
    
    // Also try getting by name as fallback
    if(!batchNumber) {
        batchNumber = $('input[name="batch_number"]').val();
    }
    if(!expiryDate) {
        expiryDate = $('input[name="expiry_date"]').val();
    }
    if(!quantity) {
        quantity = $('input[name="quantity"]').val();
    }
    if(!purchasePrice) {
        purchasePrice = $('input[name="purchase_price"]').val();
    }
    if(!sellingPrice) {
        sellingPrice = $('input[name="selling_price"]').val();
    }
    
    console.log('Form Values:', {
        medicineId: medicineId,
        medicineName: medicineName,
        batchNumber: batchNumber,
        expiryDate: expiryDate,
        quantity: quantity,
        purchasePrice: purchasePrice,
        sellingPrice: sellingPrice,
        location: location
    });
    
    // Validation
    if(!medicineId || medicineId <= 0) {
        Swal.fire('Error', 'Invalid medicine ID', 'error');
        return;
    }
    
    if(!batchNumber || batchNumber.trim() === '') {
        Swal.fire('Error', 'Please enter batch number', 'error');
        return;
    }
    
    if(!expiryDate) {
        Swal.fire('Error', 'Please select expiry date', 'error');
        return;
    }
    
    if(!quantity || quantity <= 0) {
        Swal.fire('Error', 'Please enter valid quantity', 'error');
        return;
    }
    
    if(!purchasePrice || purchasePrice <= 0) {
        Swal.fire('Error', 'Please enter valid purchase price', 'error');
        return;
    }
    
    if(!sellingPrice || sellingPrice <= 0) {
        Swal.fire('Error', 'Please enter valid selling price', 'error');
        return;
    }
    
    // Create FormData object for reliable submission
    var formData = new FormData();
    formData.append('medicine_id', medicineId);
    formData.append('batch_number', batchNumber);
    formData.append('expiry_date', expiryDate);
    formData.append('quantity', quantity);
    formData.append('purchase_price', purchasePrice);
    formData.append('selling_price', sellingPrice);
    formData.append('location', location || '');
    
    Swal.fire({
        title: 'Adding Stock...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: BASE_URL + '/pharmacy/add-stock',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            console.log('Response:', res);
            if(res.success) {
                Swal.fire({
                    title: 'Success!',
                    text: res.message,
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function(xhr) {
            console.error('AJAX Error:', xhr.responseText);
            Swal.fire('Error', 'Failed to add stock. Please try again.', 'error');
        }
    });
}

// ==================== STOCK HISTORY ====================
function viewStockHistory(id) {
    console.log('Stock history ID:', id);
    $('#stockHistoryContent').html('<div class="spinner-border"></div> Loading...');
    $('#stockHistoryModal').modal('show');
    
    $.ajax({
        url: BASE_URL + '/pharmacy/stock-history?id=' + id,
        method: 'GET',
        dataType: 'json',
        success: function(res) {
            if(res.success && res.history && res.history.length > 0) {
                var html = '<div class="table-responsive"><table class="table table-sm table-bordered">' +
                    '<thead class="table-light"><tr><th>Date</th><th>Batch</th><th>Quantity</th><th>Purchase Price</th><th>Selling Price</th><th>Expiry Date</th><th>Location</th></tr></thead><tbody>';
                res.history.forEach(function(h) {
                    html += '</td>' +
                        '<td>' + (h.created_at || '-') + '</td>' +
                        '<td>' + (h.batch_number || '-') + '</td>' +
                        '<td>' + (h.quantity || 0) + '</td>' +
                        '<td>৳ ' + parseFloat(h.purchase_price || 0).toFixed(2) + '</td>' +
                        '<td>৳ ' + parseFloat(h.selling_price || 0).toFixed(2) + '</td>' +
                        '<td>' + (h.expiry_date || '-') + '</td>' +
                        '<td>' + (h.location || '-') + '</td>' +
                        '</tr>';
                });
                html += '</tbody></table></div>';
                $('#stockHistoryContent').html(html);
            } else {
                $('#stockHistoryContent').html('<div class="text-muted">No stock history found for this medicine.</div>');
            }
        },
        error: function(xhr) { 
            console.error(xhr.responseText);
            $('#stockHistoryContent').html('<div class="text-danger">Error loading stock history</div>'); 
        }
    });
}

// ==================== CATEGORY MANAGEMENT ====================
function openCategoryModal() {
    $('#categoryModal').modal('show');
}

function addCategory() {
    var name = $('#newCategoryName').val().trim();
    if(!name) { 
        Swal.fire('Error', 'Enter category name', 'error'); 
        return; 
    }
    
    Swal.fire({
        title: 'Adding Category...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/pharmacy/add-category',
        method: 'POST',
        data: { name: name },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire('Success', res.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function(xhr) { 
            console.error(xhr.responseText);
            Swal.fire('Error', 'Failed to add category', 'error'); 
        }
    });
}

function editCategory(id, name) {
    console.log('Edit category ID:', id, 'Name:', name);
    $('#edit_category_id').val(id);
    $('#edit_category_name').val(name);
    $('#editCategoryModal').modal('show');
}

function submitEditCategory() {
    var id = $('#edit_category_id').val();
    var name = $('#edit_category_name').val().trim();
    
    if(!id || id <= 0) {
        Swal.fire('Error', 'Invalid category ID', 'error');
        return;
    }
    
    if(!name) {
        Swal.fire('Error', 'Please enter category name', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Updating Category...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/pharmacy/update-category',
        method: 'POST',
        data: { category_id: id, name: name },
        dataType: 'json',
        success: function(res) {
            if(res.success) {
                Swal.fire('Success', res.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function(xhr) { 
            console.error(xhr.responseText);
            Swal.fire('Error', 'Failed to update category', 'error'); 
        }
    });
}

function deleteCategory(id) {
    Swal.fire({
        title: 'Delete Category?',
        text: 'This will remove the category',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: BASE_URL + '/pharmacy/delete-category',
                method: 'POST',
                data: { category_id: id },
                dataType: 'json',
                success: function(res) {
                    if(res.success) {
                        Swal.fire('Deleted', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                },
                error: function(xhr) { 
                    console.error(xhr.responseText);
                    Swal.fire('Error', 'Failed to delete category', 'error'); 
                }
            });
        }
    });
}
</script>