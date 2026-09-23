<?php
class Bill extends Model {
    protected $table = 'bills';
    
    public function generateBillNumber() {
        $prefix = 'BILL';
        $year = date('Y');
        $month = date('m');
        $sql = "SELECT MAX(CAST(SUBSTRING(bill_number, 10) AS UNSIGNED)) as max_num 
                FROM bills WHERE bill_number LIKE '{$prefix}{$year}{$month}%'";
        $result = $this->query($sql);
        $nextNum = ($result[0]['max_num'] ?? 0) + 1;
        return $prefix . $year . $month . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    public function getBillDetails($id) {
        $sql = "SELECT b.*, p.first_name, p.last_name, p.patient_code 
                FROM bills b 
                JOIN patients p ON b.patient_id = p.id 
                WHERE b.id = :id";
        $result = $this->query($sql, ['id' => $id]);
        return $result[0] ?? null;
    }
    
    public function getBillItems($billId) {
        $sql = "SELECT * FROM bill_items WHERE bill_id = :bill_id";
        return $this->query($sql, ['bill_id' => $billId]);
    }
    
    public function addBillItem($data) {
        $sql = "INSERT INTO bill_items (bill_id, item_type, item_id, description, quantity, 
                unit_price, discount_percentage, discount_amount, tax_percentage, tax_amount, total_amount) 
                VALUES (:bill_id, :item_type, :item_id, :description, :quantity, 
                :unit_price, :discount_percentage, :discount_amount, :tax_percentage, :tax_amount, :total_amount)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function updateBillTotals($billId, $subtotal, $discount, $tax) {
        $total = $subtotal - $discount + $tax;
        return $this->update($billId, [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'balance_amount' => $total
        ]);
    }
    
    public function processPayment($data) {
        $sql = "INSERT INTO payments (payment_number, bill_id, patient_id, amount, 
                payment_method, transaction_id, payment_date, received_by) 
                VALUES (:payment_number, :bill_id, :patient_id, :amount, 
                :payment_method, :transaction_id, :payment_date, :received_by)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function updatePaymentStatus($billId, $amount) {
        $bill = $this->findById($billId);
        $newPaid = $bill['paid_amount'] + $amount;
        $newBalance = $bill['total_amount'] - $newPaid;
        $status = $newBalance <= 0 ? 'paid' : ($newPaid > 0 ? 'partial' : 'pending');
        
        $this->update($billId, [
            'paid_amount' => $newPaid,
            'balance_amount' => $newBalance,
            'payment_status' => $status
        ]);
    }
    
    public function getTodayRevenue() {
        $sql = "SELECT COALESCE(SUM(paid_amount), 0) as total 
                FROM payments WHERE DATE(payment_date) = CURDATE()";
        $result = $this->query($sql);
        return $result[0]['total'] ?? 0;
    }
    
    public function getPendingPayments() {
        $sql = "SELECT COALESCE(SUM(balance_amount), 0) as total 
                FROM bills WHERE balance_amount > 0";
        $result = $this->query($sql);
        return $result[0]['total'] ?? 0;
    }
    
    public function getMonthlyRevenue() {
        $sql = "SELECT COALESCE(SUM(paid_amount), 0) as total 
                FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) 
                AND YEAR(payment_date) = YEAR(CURDATE())";
        $result = $this->query($sql);
        return $result[0]['total'] ?? 0;
    }
    
    public function getTodayExpenses() {
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as total 
                FROM inventory_transactions 
                WHERE transaction_type = 'purchase' 
                AND DATE(transaction_date) = CURDATE()";
        $result = $this->query($sql);
        return $result[0]['total'] ?? 0;
    }
    
    public function getAllBills($filters = []) {
        $sql = "SELECT b.*, p.first_name, p.last_name, p.patient_code 
                FROM bills b 
                JOIN patients p ON b.patient_id = p.id 
                WHERE 1=1";
        $params = [];
        
        if(!empty($filters['date_from'])) {
            $sql .= " AND b.bill_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if(!empty($filters['date_to'])) {
            $sql .= " AND b.bill_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        if(!empty($filters['status'])) {
            $sql .= " AND b.payment_status = :status";
            $params['status'] = $filters['status'];
        }
        
        $sql .= " ORDER BY b.bill_date DESC, b.id DESC";
        return $this->query($sql, $params);
    }
    
    public function getPatientBills($patientId) {
        $sql = "SELECT * FROM bills WHERE patient_id = :patient_id ORDER BY bill_date DESC";
        return $this->query($sql, ['patient_id' => $patientId]);
    }
    
    public function getPatientPendingBills($patientId) {
        $sql = "SELECT * FROM bills WHERE patient_id = :patient_id AND balance_amount > 0";
        return $this->query($sql, ['patient_id' => $patientId]);
    }
    
    public function getPayments($billId) {
        $sql = "SELECT * FROM payments WHERE bill_id = :bill_id ORDER BY payment_date DESC";
        return $this->query($sql, ['bill_id' => $billId]);
    }
    
    public function getDailyReport($date) {
        $sql = "SELECT 
                COUNT(*) as total_bills,
                COALESCE(SUM(total_amount), 0) as total_billed,
                COALESCE(SUM(paid_amount), 0) as total_collected,
                COALESCE(SUM(balance_amount), 0) as total_pending
                FROM bills WHERE bill_date = :date";
        return $this->query($sql, ['date' => $date])[0];
    }
    
    public function createClaim($data) {
        $sql = "INSERT INTO insurance_claims (claim_number, bill_id, patient_id, 
                insurance_provider, policy_number, claim_amount, claim_date, status) 
                VALUES (:claim_number, :bill_id, :patient_id, 
                :insurance_provider, :policy_number, :claim_amount, :claim_date, :status)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
    
    public function getInsuranceClaims() {
        $sql = "SELECT ic.*, b.bill_number, p.first_name, p.last_name 
                FROM insurance_claims ic 
                JOIN bills b ON ic.bill_id = b.id 
                JOIN patients p ON ic.patient_id = p.id 
                ORDER BY ic.claim_date DESC";
        return $this->query($sql);
    }

    public function getPatientByBill($billId) {
    $sql = "SELECT p.* FROM patients p JOIN bills b ON p.id = b.patient_id WHERE b.id = :id";
    $result = $this->query($sql, ['id' => $billId]);
    return $result[0] ?? null;
    }

    /**
     * Delete bill and all related items
     */
    public function deleteBill($billId) {
        $db = Database::getInstance()->getConnection();
        
        try {
            $db->begin_transaction();
            
            // Delete bill items
            $db->query("DELETE FROM bill_items WHERE bill_id = " . (int)$billId);
            
            // Delete payments (optional - or just bill)
            $db->query("DELETE FROM payments WHERE bill_id = " . (int)$billId);
            
            // Delete the bill
            $db->query("DELETE FROM bills WHERE id = " . (int)$billId);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }

    /**
     * Get bill items with details
     */
    public function getBillItemsWithDetails($billId) {
        $sql = "SELECT bi.*, 
                       CASE 
                           WHEN bi.item_type = 'medicine' THEN 'Medicine'
                           WHEN bi.item_type = 'lab_test' THEN 'Lab Test'
                           WHEN bi.item_type = 'consultation' THEN 'Consultation'
                           WHEN bi.item_type = 'service' THEN 'Service'
                           WHEN bi.item_type = 'procedure' THEN 'Procedure'
                           WHEN bi.item_type = 'lab_accessory' THEN 'Lab Accessory'
                           ELSE 'Other'
                       END as item_type_display
                FROM bill_items bi
                WHERE bi.bill_id = ?
                ORDER BY bi.id ASC";
        return $this->query($sql, [$billId]);
    }
}