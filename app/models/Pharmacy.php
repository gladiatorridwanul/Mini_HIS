<?php
class Pharmacy extends Model {
    protected $table = 'pharmacy_sales';
    
    public function createSale($data) {
        return $this->create($data);
    }
    
    public function addSaleItem($data) {
        $sql = "INSERT INTO pharmacy_sale_items 
                (sale_id, item_id, quantity, unit_price, total_amount) 
                VALUES (:sale_id, :item_id, :quantity, :unit_price, :total_amount)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function updateSaleTotal($saleId, $subtotal, $total) {
        return $this->update($saleId, [
            'subtotal' => $subtotal,
            'total_amount' => $total
        ]);
    }
    
    public function getTodaySales() {
        $sql = "SELECT SUM(total_amount) as total, COUNT(*) as count 
                FROM pharmacy_sales WHERE sale_date = CURDATE() 
                AND status = 'completed'";
        return $this->query($sql)[0];
    }

    public function getPharmacyItems() {
    $sql = "SELECT * FROM inventory_items 
            WHERE item_type = 'medicine' AND is_active = 1 AND current_stock > 0 
            ORDER BY item_name";
    return $this->query($sql);
}

public function searchPharmacyItems($term) {
    $sql = "SELECT * FROM inventory_items 
            WHERE item_type = 'medicine' AND is_active = 1 
            AND (item_name LIKE :term OR generic_name LIKE :term2) 
            AND current_stock > 0 LIMIT 10";
    $searchTerm = "%{$term}%";
    return $this->query($sql, ['term' => $searchTerm, 'term2' => $searchTerm]);
}

public function getSales($filters = []) {
    $sql = "SELECT ps.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name 
            FROM pharmacy_sales ps 
            LEFT JOIN patients p ON ps.patient_id = p.id 
            WHERE 1=1 
            ORDER BY ps.sale_date DESC";
    return $this->query($sql);
}

public function createSaleFromPrescription($prescription) {
    $saleData = [
        'sale_number' => $this->generateSaleNumber(),
        'patient_id' => $prescription['patient_id'],
        'prescription_id' => $prescription['id'],
        'sale_date' => date('Y-m-d'),
        'payment_method' => 'cash',
        'status' => 'completed',
        'sold_by' => $_SESSION['user_id']
    ];
    
    $saleId = $this->create($saleData);
    $subtotal = 0;
    
    foreach ($prescription['items'] as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        $subtotal += $itemTotal;
        
        $this->addSaleItem([
            'sale_id' => $saleId,
            'item_id' => $item['drug_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['price'],
            'total_amount' => $itemTotal
        ]);
    }
    
    $this->updateSaleTotal($saleId, $subtotal, $subtotal);
    return $saleId;
}

private function generateSaleNumber() {
    $prefix = 'SALE';
    $year = date('Y');
    $sql = "SELECT MAX(CAST(SUBSTRING(sale_number, 8) AS UNSIGNED)) as max_num 
            FROM pharmacy_sales WHERE sale_number LIKE '{$prefix}{$year}%'";
    $result = $this->query($sql);
    $nextNum = ($result[0]['max_num'] ?? 0) + 1;
    return $prefix . $year . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
}
}