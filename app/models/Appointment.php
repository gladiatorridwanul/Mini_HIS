<?php
class Appointment extends Model {
    protected $table = 'appointments';
    
    // ================================================================
    // FIX: ADD FIND METHOD - This is what PrescriptionController needs
    // ================================================================
    
    /**
     * Find an appointment by ID
     * This is the method that PrescriptionController is looking for
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->queryOne($sql, [$id]);
    }
    
    /**
     * Find appointment with all details (patient, doctor, service)
     * Enhanced version of getAppointmentWithDetails
     */
    public function findWithDetails($id) {
        $sql = "SELECT a.*, 
                       p.id as patient_id, p.patient_code, p.first_name, p.last_name, 
                       p.phone, p.email, p.gender, p.date_of_birth, p.address,
                       p.occupation, p.marital_status, p.full_name,
                       d.id as doctor_id, d.specialization, d.consultation_fee,
                       u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                       u.title as doctor_title,
                       CONCAT(u.title, ' ', u.first_name, ' ', u.last_name) as doctor_full_name,
                       CONCAT(p.first_name, ' ', p.last_name) as patient_full_name,
                       ds.service_name, ds.service_price, ds.service_type
                FROM {$this->table} a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                LEFT JOIN doctor_services ds ON a.service_id = ds.id
                WHERE a.id = ?";
        return $this->queryOne($sql, [$id]);
    }
    
    /**
     * Check if appointment exists
     */
    public function exists($id) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE id = ?";
        $result = $this->queryOne($sql, [$id]);
        return $result && $result['count'] > 0;
    }
    
    /**
     * Get appointment by appointment number
     */
    public function findByNumber($appointmentNumber) {
        $sql = "SELECT * FROM {$this->table} WHERE appointment_number = ? LIMIT 1";
        return $this->queryOne($sql, [$appointmentNumber]);
    }
    
    /**
     * Get appointment with bill details
     */
    public function findWithBill($id) {
        $sql = "SELECT a.*,
                       b.id as bill_id,
                       b.bill_number,
                       b.total_amount as bill_total,
                       b.paid_amount as bill_paid,
                       b.balance_amount as bill_balance,
                       b.payment_status as bill_payment_status,
                       COALESCE(
                           (SELECT COUNT(*) FROM bill_items WHERE bill_id = b.id), 
                           0
                       ) as bill_item_count,
                       COALESCE(
                           (SELECT description FROM bill_items WHERE bill_id = b.id ORDER BY id ASC LIMIT 1),
                           'Consultation'
                       ) as bill_service_name
                FROM {$this->table} a
                LEFT JOIN bills b ON b.reference_type = 'appointment' AND b.reference_id = a.id
                WHERE a.id = ?";
        return $this->queryOne($sql, [$id]);
    }
    
    /**
     * Get appointment by prescription ID (for linking)
     */
    public function findByPrescription($prescriptionId) {
        $sql = "SELECT a.* FROM {$this->table} a
                JOIN prescriptions p ON p.appointment_id = a.id
                WHERE p.id = ? LIMIT 1";
        return $this->queryOne($sql, [$prescriptionId]);
    }
    
    /**
     * Get serial number for appointment
     */
    public function getNextSerial($doctorId, $date, $sessionType) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE doctor_id = ? 
                  AND appointment_date = ? 
                  AND session_type = ?
                  AND status != 'canceled'";
        $result = $this->queryOne($sql, [$doctorId, $date, $sessionType]);
        $count = $result ? (int)$result['count'] : 0;
        return ($sessionType == 'morning' ? 'M' : 'E') . str_pad(($count + 1), 3, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get appointment statistics for dashboard
     */
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'canceled' THEN 1 ELSE 0 END) as canceled,
                    SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show,
                    SUM(CASE WHEN appointment_date = CURDATE() THEN 1 ELSE 0 END) as today,
                    SUM(CASE WHEN appointment_date > CURDATE() AND status NOT IN ('canceled', 'completed') THEN 1 ELSE 0 END) as upcoming
                FROM {$this->table}";
        return $this->queryOne($sql);
    }
    
    // ================================================================
    // EXISTING METHODS (KEPT AS ORIGINAL)
    // ================================================================
    
    /**
     * Get appointment with patient and doctor details - FIXED for PrescriptionController
     */
    public function getAppointmentWithDetails($appointmentId) {
        $sql = "SELECT a.*, 
                       p.id as patient_id, p.patient_code, p.first_name, p.last_name, 
                       p.phone, p.email, p.gender, p.date_of_birth, p.address,
                       p.occupation, p.marital_status,
                       d.id as doctor_id, d.specialization, d.consultation_fee,
                       u.first_name as doctor_first_name, u.last_name as doctor_last_name,
                       u.title as doctor_title
                FROM {$this->table} a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE a.id = ?";
        return $this->queryOne($sql, [$appointmentId]);
    }
    
    public function getTodayAppointments($doctorId) {
        $sql = "SELECT a.*, p.first_name, p.last_name, p.patient_code 
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.id 
                WHERE a.doctor_id = ? 
                AND a.appointment_date = CURDATE() 
                AND a.status NOT IN ('canceled', 'no_show')
                ORDER BY a.start_time";
        
        return $this->query($sql, [$doctorId]);
    }
    
    public function getDoctorAppointments($doctorId, $filters = []) {
        $sql = "SELECT a.*, p.first_name, p.last_name, p.patient_code, p.phone 
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.id 
                WHERE a.doctor_id = ?";
        
        $params = [$doctorId];
        
        if(!empty($filters['date'])) {
            $sql .= " AND a.appointment_date = ?";
            $params[] = $filters['date'];
        }
        
        if(!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        
        $sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";
        
        return $this->query($sql, $params);
    }
    
    public function getAppointmentDetails($id) {
        $sql = "SELECT a.*, p.*, d.id as doctor_id, u.first_name as doctor_first_name, 
                u.last_name as doctor_last_name, d.specialization 
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.id 
                JOIN doctors d ON a.doctor_id = d.id 
                JOIN users u ON d.user_id = u.id 
                WHERE a.id = ?";
        
        return $this->queryOne($sql, [$id]);
    }
    
    public function getPendingAppointments($doctorId) {
        $sql = "SELECT a.*, p.first_name, p.last_name 
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.id 
                WHERE a.doctor_id = ? 
                AND a.status IN ('scheduled', 'confirmed')";
        
        return $this->query($sql, [$doctorId]);
    }

    public function isSlotAvailable($data) {
        $sql = "SELECT COUNT(*) as count FROM appointments 
                WHERE doctor_id = ? 
                AND appointment_date = ? 
                AND start_time = ? 
                AND status NOT IN ('canceled')";
        
        $result = $this->queryOne($sql, [
            $data['doctor_id'],
            $data['appointment_date'],
            $data['start_time']
        ]);
        
        return ($result['count'] ?? 0) == 0;
    }

    public function getPatientAppointments($patientId) {
        $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as doctor_name, d.specialization 
                FROM appointments a 
                JOIN doctors d ON a.doctor_id = d.id 
                JOIN users u ON d.user_id = u.id 
                WHERE a.patient_id = ? 
                ORDER BY a.appointment_date DESC, a.start_time DESC";
        return $this->query($sql, [$patientId]);
    }

    public function getPatientUpcomingAppointments($patientId) {
        $sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as doctor_name, d.specialization 
                FROM appointments a 
                JOIN doctors d ON a.doctor_id = d.id 
                JOIN users u ON d.user_id = u.id 
                WHERE a.patient_id = ? 
                AND a.appointment_date >= CURDATE() 
                AND a.status IN ('scheduled', 'confirmed') 
                ORDER BY a.appointment_date ASC LIMIT 5";
        return $this->query($sql, [$patientId]);
    }

    public function getCompletedAppointments($doctorId) {
        $sql = "SELECT COUNT(*) as count FROM appointments 
                WHERE doctor_id = ? AND status = 'completed'";
        $result = $this->queryOne($sql, [$doctorId]);
        return $result['count'] ?? 0;
    }

    public function getTodayTotal() {
        $sql = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = CURDATE()";
        $result = $this->queryOne($sql);
        return $result['count'] ?? 0;
    }

    public function getAvailableDoctors() {
        $sql = "SELECT COUNT(DISTINCT d.id) as count 
                FROM doctors d 
                JOIN doctor_schedules ds ON d.id = ds.doctor_id 
                WHERE ds.day_of_week = DAYNAME(CURDATE()) 
                AND ds.is_available = 1 
                AND d.status = 'active'";
        $result = $this->queryOne($sql);
        return $result['count'] ?? 0;
    }

    public function getTodayScheduledAppointments() {
        $sql = "SELECT a.*, CONCAT(p.first_name, ' ', p.last_name) as patient_name, p.patient_code 
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.id 
                WHERE a.appointment_date = CURDATE() 
                AND a.status IN ('scheduled', 'confirmed') 
                ORDER BY a.start_time";
        return $this->query($sql);
    }

    /**
     * Update appointment status
     */
    public function updateStatus($appointmentId, $status) {
        return $this->update($appointmentId, ['status' => $status]);
    }

    /**
     * Get appointments by date range
     */
    public function getByDateRange($doctorId, $startDate, $endDate) {
        $sql = "SELECT a.*, p.first_name, p.last_name, p.patient_code 
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.id 
                WHERE a.doctor_id = ? 
                AND a.appointment_date BETWEEN ? AND ?
                AND a.status NOT IN ('canceled', 'no_show')
                ORDER BY a.appointment_date, a.start_time";
        return $this->query($sql, [$doctorId, $startDate, $endDate]);
    }

    /**
     * Get appointment by appointment number
     */
    public function getByAppointmentNumber($appointmentNumber) {
        $sql = "SELECT * FROM appointments WHERE appointment_number = ? LIMIT 1";
        return $this->queryOne($sql, [$appointmentNumber]);
    }

    /**
 * Quick create patient from AJAX form
 * Used in book-appointment for quick patient registration
 */
public function quickCreate($data) {
    // Validate required fields
    if(empty($data['first_name']) || empty($data['last_name']) || empty($data['phone'])) {
        return ['success' => false, 'message' => 'First name, last name, and phone are required'];
    }
    
    // Generate patient code
    $patientCode = 'PAT' . date('Ymd') . rand(10, 99);
    
    // Check for duplicate phone
    $check = $this->query("SELECT id FROM patients WHERE phone = ?", [$data['phone']]);
    if($check && count($check) > 0) {
        return ['success' => false, 'message' => 'Phone number already registered'];
    }
    
    $fullName = $data['first_name'] . ' ' . $data['last_name'];
    
    $sql = "INSERT INTO patients (
                patient_code, first_name, last_name, full_name, phone, email, 
                gender, date_of_birth, address, registration_date, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'active', NOW())";
    
    $result = $this->query($sql, [
        $patientCode,
        $data['first_name'],
        $data['last_name'],
        $fullName,
        $data['phone'],
        $data['email'] ?? '',
        $data['gender'] ?? 'male',
        $data['date_of_birth'] ?? null,
        $data['address'] ?? ''
    ]);
    
    if($result) {
        $id = $this->lastInsertId();
        return [
            'success' => true,
            'patient_id' => $id,
            'patient_code' => $patientCode,
            'patient_name' => $fullName
        ];
    }
    
    return ['success' => false, 'message' => 'Database error'];
}
}
?>