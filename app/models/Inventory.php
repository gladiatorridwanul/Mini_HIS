<?php
class Inventory extends Model {
    protected $table = 'inventory_items';
    
    public function getLowStockItems() {
        $sql = "SELECT * FROM inventory_items 
                WHERE current_stock <= reorder_level AND is_active = 1 
                ORDER BY current_stock ASC";
        return $this->query($sql);
    }
    
    public function getExpiringItems($days = 30) {
        $sql = "SELECT * FROM inventory_items 
                WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY) 
                AND is_active = 1 
                ORDER BY expiry_date ASC";
        return $this->query($sql, ['days' => $days]);
    }
    
    public function reduceStock($itemId, $quantity) {
        $sql = "UPDATE inventory_items 
                SET current_stock = current_stock - :quantity 
                WHERE id = :id AND current_stock >= :quantity2";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'quantity' => $quantity,
            'quantity2' => $quantity,
            'id' => $itemId
        ]);
    }
    
    public function getItemByBarcode($barcode) {
        $sql = "SELECT * FROM inventory_items WHERE barcode = :barcode";
        $result = $this->query($sql, ['barcode' => $barcode]);
        return $result[0] ?? null;
    }
    
    public function updateStock($itemId, $quantity, $type = 'in') {
        if($type == 'in') {
            $sql = "UPDATE inventory_items SET current_stock = current_stock + :quantity WHERE id = :id";
        } else {
            $sql = "UPDATE inventory_items SET current_stock = current_stock - :quantity WHERE id = :id";
        }
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['quantity' => $quantity, 'id' => $itemId]);
    }
    
    public function recordTransaction($data) {
        $sql = "INSERT INTO inventory_transactions 
                (item_id, transaction_type, quantity, unit_price, total_amount, 
                batch_number, expiry_date, transaction_date, performed_by, notes) 
                VALUES 
                (:item_id, :transaction_type, :quantity, :unit_price, :total_amount, 
                :batch_number, :expiry_date, :transaction_date, :performed_by, :notes)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }

    public function getItemById($id) {
    return $this->findById($id);
}

public function getAllItems() {
    $sql = "SELECT ii.*, ic.name as category_name 
            FROM inventory_items ii 
            LEFT JOIN inventory_categories ic ON ii.category_id = ic.id 
            ORDER BY ii.item_name";
    return $this->query($sql);
}

public function createPurchaseOrder($data) {
    $sql = "INSERT INTO purchase_orders (po_number, supplier_id, order_date, expected_delivery_date, created_by, status) 
            VALUES (:po_number, :supplier_id, :order_date, :expected_delivery_date, :created_by, :status)";
    $stmt = $this->db->prepare($sql);
    $stmt->execute($data);
    return $this->db->lastInsertId();
}

public function addPOItem($data) {
    $sql = "INSERT INTO purchase_order_items (purchase_order_id, item_id, quantity, unit_price, total_price) 
            VALUES (:purchase_order_id, :item_id, :quantity, :unit_price, :total_price)";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute($data);
}

public function updatePOTotal($poId, $total) {
    $this->update($poId, ['total_amount' => $total, 'grand_total' => $total], 'purchase_orders');
}

public function getPurchaseOrders() {
    $sql = "SELECT po.*, s.company_name as supplier_name 
            FROM purchase_orders po 
            JOIN suppliers s ON po.supplier_id = s.id 
            ORDER BY po.order_date DESC";
    return $this->query($sql);
}
}