<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-plus-circle text-success me-2"></i>Add Stock</h2>
        <a href="<?php echo BASE_URL; ?>/inventory/items" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Items
        </a>
    </div>

    <div class="card shadow">
        <div class="card-header bg-white">
            <h6 class="mb-0">Add Stock to: <strong><?php echo htmlspecialchars($itemName); ?></strong></h6>
        </div>
        <div class="card-body">
            <form id="addStockForm">
                <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                <input type="hidden" name="item_type" value="<?php echo $itemType; ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Batch Number</label>
                        <input type="text" name="batch_number" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Quantity</label>
                        <input type="number" name="quantity" class="form-control" required min="1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Purchase Price</label>
                        <input type="number" step="0.01" name="purchase_price" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label required-field">Selling Price</label>
                        <input type="number" step="0.01" name="selling_price" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Storage location">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Manufacturing Date</label>
                        <input type="date" name="manufacturing_date" class="form-control">
                    </div>
                </div>
                
                <div class="text-end">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-success ms-2">Add Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#addStockForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: BASE_URL + '/inventory/add-stock',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire('Success', response.message, 'success').then(() => {
                        window.location.href = BASE_URL + '/inventory/items';
                    });
                } else {
                    Swal.fire('Error', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Error', 'Failed to add stock', 'error');
            }
        });
    });
});
</script>