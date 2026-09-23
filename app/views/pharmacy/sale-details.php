<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .receipt-container {
        max-width: 400px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        padding: 20px;
    }
    @media print {
        .no-print { display: none; }
        .receipt-container { box-shadow: none; padding: 0; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2><i class="fas fa-receipt text-success me-2"></i>Sale Details</h2>
    <div>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Print Receipt
        </button>
        <a href="<?php echo BASE_URL; ?>/pharmacy/sales" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Sales
        </a>
    </div>
</div>

<div class="receipt-container">
    <div class="text-center mb-4">
        <h4>UNIDIA HOSPITAL</h4>
        <p class="mb-0">Pharmacy Department</p>
        <small class="text-muted">Sale #: <?php echo $sale['sale_number']; ?></small>
    </div>
    
    <hr>
    
    <div class="row mb-3">
        <div class="col-6">
            <small class="text-muted">Date</small>
            <div><?php echo date('d/m/Y h:i A', strtotime($sale['created_at'])); ?></div>
        </div>
        <div class="col-6">
            <small class="text-muted">Cashier</small>
            <div><?php echo $sale['cashier_name']; ?></div>
        </div>
    </div>
    
    <div class="mb-3">
        <small class="text-muted">Patient</small>
        <div><strong><?php echo $sale['patient_name']; ?></strong></div>
        <?php if($sale['phone']): ?>
        <small><?php echo $sale['phone']; ?></small>
        <?php endif; ?>
    </div>
    
    <hr>
    
    <table class="table table-sm">
        <thead>
            <tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td><?php echo $item['medicine_name']; ?><br><small><?php echo $item['strength']; ?></small></td>
                <td>x<?php echo $item['quantity']; ?></td>
                <td>৳ <?php echo number_format($item['unit_price'], 2); ?></td>
                <td>৳ <?php echo number_format($item['total_amount'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="3" class="text-end"><strong>Subtotal:</strong></td><td>৳ <?php echo number_format($sale['subtotal'], 2); ?></td></tr>
            <?php if($sale['discount_percentage'] > 0): ?>
            <tr><td colspan="3" class="text-end"><strong>Discount (<?php echo $sale['discount_percentage']; ?>%):</strong></td><td>- ৳ <?php echo number_format($sale['discount_amount'], 2); ?></td></tr>
            <?php endif; ?>
            <tr class="table-success"><td colspan="3" class="text-end"><strong>Total:</strong></td><td><strong>৳ <?php echo number_format($sale['total_amount'], 2); ?></strong></td></tr>
            <tr><td colspan="3" class="text-end"><strong>Payment Method:</strong></td><td><?php echo ucfirst($sale['payment_method']); ?></td></tr>
        </tfoot>
    </table>
    
    <hr>
    
    <div class="text-center mt-3">
        <small class="text-muted">Thank you for your purchase!</small><br>
        <small class="text-muted">For any queries, please contact pharmacy counter</small>
    </div>
</div>