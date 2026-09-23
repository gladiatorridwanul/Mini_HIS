<?php
// /app/models/Prescription.php
// COMPLETE WORKING VERSION WITH PROPER DATABASE CONNECTION
// UPDATED: Combined all functions from both versions, added dropdown options fetching methods and full data retrieval

class Prescription extends Model {

    protected $table = 'prescriptions';

    public function __construct() {
        parent::__construct();
    }

    // ================================================================
    // GET PRESCRIPTION DROPDOWN OPTIONS FROM DATABASE
    // ================================================================
    public function getDropdownOptions($type, $selectedValue = null) {
        try {
            if (!$this->db) return [];
            
            $sql = "SELECT id, option_value, display_label, sort_order 
                    FROM prescription_dropdown_options 
                    WHERE option_type = ? AND is_active = 1 
                    ORDER BY sort_order ASC, id ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $type);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $options = [];
            while ($row = $result->fetch_assoc()) {
                $options[] = $row;
            }
            return $options;
            
        } catch (Exception $e) {
            error_log("getDropdownOptions error: " . $e->getMessage());
            return [];
        }
    }

    public function getAllDropdownOptions() {
        try {
            if (!$this->db) return [];
            
            $sql = "SELECT * FROM prescription_dropdown_options WHERE is_active = 1 ORDER BY option_type, sort_order ASC";
            $result = $this->db->query($sql);
            $options = [];
            while ($row = $result->fetch_assoc()) {
                $options[] = $row;
            }
            return $options;
            
        } catch (Exception $e) {
            error_log("getAllDropdownOptions error: " . $e->getMessage());
            return [];
        }
    }

    // ================================================================
    // GET FULL PRESCRIPTION
    // ================================================================
    public function getFullPrescription($id) {
        $sql = "SELECT p.*,
                CONCAT(pa.first_name, ' ', pa.last_name) as patient_name,
                pa.patient_code, pa.gender, pa.date_of_birth, pa.phone, pa.address,
                pa.occupation as patient_occupation, pa.marital_status as patient_marital,
                CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                u.title as doctor_title,
                d.bmdc_number, d.qualification, d.specialization,
                d.signature_path, d.digital_stamp_path,
                d.doctor_info_en, d.doctor_info_bn,
                d.consultation_fee
                FROM {$this->table} p
                JOIN patients pa ON p.patient_id = pa.id
                JOIN doctors d ON p.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE p.id = ?";
        return $this->queryOne($sql, [$id]);
    }

    // ================================================================
    // GET PRESCRIPTION LAB TESTS
    // ================================================================
    public function getPrescriptionLabTests($prescriptionId) {
        $sql = "SELECT * FROM prescription_lab_tests WHERE prescription_id = ? ORDER BY id ASC";
        return $this->query($sql, [$prescriptionId]);
    }

    // ================================================================
    // GET PRESCRIPTIONS WITH LAB TESTS (FOR LAB-TEST PRESCRIPTION PAGE)
    // ================================================================
    public function getPrescriptionsWithLabTests($filters = []) {
        $sql = "SELECT DISTINCT 
                    p.*,
                    CONCAT(pa.first_name, ' ', pa.last_name) as patient_name,
                    pa.patient_code,
                    pa.phone,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                    u.title as doctor_title,
                    d.specialization,
                    (SELECT COUNT(*) FROM prescription_lab_tests WHERE prescription_id = p.id) as lab_test_count,
                    (SELECT GROUP_CONCAT(test_name SEPARATOR ', ') FROM prescription_lab_tests WHERE prescription_id = p.id) as lab_test_names,
                    (SELECT id FROM lab_test_orders WHERE prescription_id = p.id LIMIT 1) as existing_order_id
                FROM {$this->table} p
                JOIN patients pa ON p.patient_id = pa.id
                JOIN doctors d ON p.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE EXISTS (SELECT 1 FROM prescription_lab_tests WHERE prescription_id = p.id)
                AND p.status IN ('issued', 'completed', 'dispensed')";
        
        $params = [];
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.prescription_number LIKE ? OR CONCAT(pa.first_name, ' ', pa.last_name) LIKE ? OR pa.patient_code LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND p.prescription_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND p.prescription_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['has_order']) && $filters['has_order'] == 'yes') {
            $sql .= " AND EXISTS (SELECT 1 FROM lab_test_orders WHERE prescription_id = p.id)";
        } elseif (!empty($filters['has_order']) && $filters['has_order'] == 'no') {
            $sql .= " AND NOT EXISTS (SELECT 1 FROM lab_test_orders WHERE prescription_id = p.id)";
        }
        
        $sql .= " ORDER BY p.prescription_date DESC, p.id DESC";
        
        return $this->query($sql, $params);
    }

    // ================================================================
    // GET PRINT DATA - RETRIEVES ALL DATA
    // ================================================================
    public function getPrintData($id) {
        $sql = "SELECT 
                    p.*,
                    CONCAT(pa.first_name, ' ', pa.last_name) as patient_name,
                    pa.patient_code,
                    pa.date_of_birth as patient_dob,
                    pa.gender,
                    pa.phone,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                    u.title as doctor_title,
                    d.specialization,
                    d.qualification,
                    d.bmdc_number,
                    d.signature_path,
                    d.doctor_info_en,
                    d.doctor_info_bn
                FROM prescriptions p
                JOIN patients pa ON p.patient_id = pa.id
                JOIN doctors d ON p.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                WHERE p.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Get prescription items with details
            $items = $this->getPrescriptionItems($id);
            $row['items'] = $items;
            
            // Get all related data
            $row['chief_complaints'] = $this->getChiefComplaints($id);
            $row['drug_history'] = $this->getDrugHistory($id);
            $row['disease_history'] = $this->getDiseaseHistory($id);
            $row['investigations'] = $this->getInvestigations($id);
            $row['physical_exam'] = $this->getPhysicalExam($id);
            $row['vital_signs'] = $this->getVitalSigns($id);
            $row['advice'] = $this->getAdvice($id);
            $row['lab_tests'] = $this->getPrescriptionLabTests($id);
            $row['treatment_history'] = $this->getTreatmentHistoryByPrescription($id);
            $row['vaccinations'] = $this->getVaccinations($id);
            
            return $row;
        }
        
        return null;
    }

    // ================================================================
    // GET CHIEF COMPLAINTS
    // ================================================================
    public function getChiefComplaints($prescriptionId) {
        $sql = "SELECT * FROM prescription_chief_complaints WHERE prescription_id = ? ORDER BY id ASC";
        return $this->query($sql, [$prescriptionId]);
    }

    // ================================================================
    // GET DRUG HISTORY
    // ================================================================
    public function getDrugHistory($prescriptionId) {
        $sql = "SELECT * FROM prescription_drug_history WHERE prescription_id = ? ORDER BY id ASC";
        return $this->query($sql, [$prescriptionId]);
    }

    // ================================================================
    // GET DISEASE HISTORY
    // ================================================================
    public function getDiseaseHistory($prescriptionId) {
        $sql = "SELECT * FROM prescription_disease_history WHERE prescription_id = ? ORDER BY id ASC";
        return $this->query($sql, [$prescriptionId]);
    }

    // ================================================================
    // GET INVESTIGATIONS
    // ================================================================
    public function getInvestigations($prescriptionId) {
        $sql = "SELECT * FROM prescription_investigations WHERE prescription_id = ? ORDER BY id ASC";
        return $this->query($sql, [$prescriptionId]);
    }

    // ================================================================
    // GET PHYSICAL EXAMINATION
    // ================================================================
    public function getPhysicalExam($prescriptionId) {
        $sql = "SELECT * FROM prescription_physical_examination WHERE prescription_id = ?";
        $result = $this->queryOne($sql, [$prescriptionId]);
        return $result ?: [];
    }

    // ================================================================
    // GET VITAL SIGNS
    // ================================================================
    public function getVitalSigns($prescriptionId) {
        $sql = "SELECT * FROM prescription_vital_signs WHERE prescription_id = ?";
        $result = $this->queryOne($sql, [$prescriptionId]);
        return $result ?: [];
    }

    // ================================================================
    // GET ADVICE
    // ================================================================
    public function getAdvice($prescriptionId) {
        $sql = "SELECT * FROM prescription_advice WHERE prescription_id = ?";
        $result = $this->queryOne($sql, [$prescriptionId]);
        return $result ?: [];
    }

    // ================================================================
    // GET TREATMENT HISTORY BY PRESCRIPTION
    // ================================================================
    public function getTreatmentHistoryByPrescription($prescriptionId) {
        $sql = "SELECT * FROM treatment_history WHERE prescription_id = ? ORDER BY id ASC";
        return $this->query($sql, [$prescriptionId]);
    }

    // ================================================================
    // GET VACCINATIONS BY PRESCRIPTION
    // ================================================================
    public function getVaccinations($prescriptionId) {
        $sql = "SELECT * FROM patient_vaccinations WHERE prescription_id = ? ORDER BY date_given DESC";
        return $this->query($sql, [$prescriptionId]);
    }

    // ================================================================
    // GET PRESCRIPTION ITEMS WITH DETAILS
    // ================================================================
    public function getPrescriptionItems($prescriptionId) {
        $sql = "SELECT pi.*, 
                       m.medicine_name, m.generic_name, m.strength, m.dosage_form,
                       m.medicine_code
                FROM prescription_items pi
                LEFT JOIN medicines m ON pi.drug_id = m.id
                WHERE pi.prescription_id = ?
                ORDER BY pi.id ASC";
        $items = $this->query($sql, [$prescriptionId]);

        if (empty($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            // Build drug name
            if (!empty($item['medicine_name'])) {
                $name = $item['medicine_name'];
                if (!empty($item['strength'])) {
                    $name .= ' ' . $item['strength'];
                }
                if (!empty($item['generic_name'])) {
                    $name .= ' (' . $item['generic_name'] . ')';
                }
                $item['drug_name'] = $name;
            } elseif (empty($item['drug_name'])) {
                $item['drug_name'] = 'Unknown Medicine';
            }

            // Map route to relation_to_food if not set
            if (empty($item['relation_to_food']) && !empty($item['route'])) {
                $item['relation_to_food'] = $item['route'];
            }

            // Get medicine details
            $detailsSql = "SELECT * FROM prescription_medicine_details 
                          WHERE prescription_item_id = ? 
                          ORDER BY id ASC";
            $details = $this->query($detailsSql, [$item['id']]);
            $item['details'] = $details ?: [];

            $result[] = $item;
        }
        return $result;
    }

    // ================================================================
    // GET VISIT NUMBER
    // ================================================================
    public function getVisitNumber($patientId) {
        $sql = "SELECT MAX(visit_number) as max_visit FROM {$this->table} WHERE patient_id = ?";
        $result = $this->queryOne($sql, [$patientId]);
        return ($result['max_visit'] ?? 0) + 1;
    }

    // ================================================================
    // GET PATIENT PRESCRIPTIONS
    // ================================================================
    public function getPatientPrescriptions($patientId) {
        $sql = "SELECT p.*,
                       CONCAT(u.first_name,' ',u.last_name) as doctor_name,
                       u.title as doctor_title,
                       d.specialization,
                       COUNT(pi.id) as item_count
                FROM {$this->table} p
                JOIN doctors d ON p.doctor_id = d.id
                JOIN users u ON d.user_id = u.id
                LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
                WHERE p.patient_id = ?
                AND p.status IN ('issued', 'completed', 'dispensed')
                GROUP BY p.id
                ORDER BY p.prescription_date DESC";
        return $this->query($sql, [$patientId]);
    }

    // ================================================================
    // GET PRESCRIPTION BY APPOINTMENT
    // ================================================================
    public function getByAppointment($appointmentId) {
        $sql = "SELECT * FROM {$this->table} WHERE appointment_id = ? LIMIT 1";
        return $this->queryOne($sql, [$appointmentId]);
    }

    // ================================================================
    // GET ACTIVE DOCTORS
    // ================================================================
    public function getActiveDoctors() {
        $sql = "SELECT d.*, u.first_name, u.last_name, u.title 
                FROM doctors d 
                JOIN users u ON d.user_id = u.id 
                WHERE d.status = 'active' 
                ORDER BY u.first_name ASC";
        return $this->query($sql);
    }

    // ================================================================
    // BASIC CRUD
    // ================================================================
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->queryOne($sql, [$id]);
    }

    public function update($id, $data) {
        $set = [];
        $values = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = ?";
            $values[] = $value;
        }
        $values[] = $id;
        return $this->execute("UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE id = ?", $values);
    }

    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $result = $this->execute($sql, array_values($data));
        if ($result) {
            return $this->lastInsertId();
        }
        return false;
    }

    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->execute($sql, [$id]);
    }

    // ================================================================
    // SEARCH DRUGS
    // ================================================================
    public function searchDrugs($term) {
        $sql = "SELECT id, medicine_code, medicine_name, generic_name, strength, dosage_form, selling_price
                FROM medicines 
                WHERE status = 'active' 
                AND (medicine_name LIKE ? OR generic_name LIKE ? OR medicine_code LIKE ?)
                ORDER BY medicine_name ASC 
                LIMIT 20";
        $searchTerm = "%{$term}%";
        return $this->query($sql, [$searchTerm, $searchTerm, $searchTerm]);
    }

    // ================================================================
    // GET PATIENT BY ID
    // ================================================================
    public function getPatient($patientId) {
        $sql = "SELECT * FROM patients WHERE id = ?";
        return $this->queryOne($sql, [$patientId]);
    }

    // ================================================================
    // GET TREATMENT HISTORY FOR PATIENT (UPDATED VERSION)
    // ================================================================
    public function getTreatmentHistory($patientId, $prescriptionId = null) {
        $sql = "SELECT * FROM treatment_history WHERE patient_id = ?";
        $params = [$patientId];
        
        if ($prescriptionId !== null && $prescriptionId > 0) {
            $sql .= " AND prescription_id = ?";
            $params[] = $prescriptionId;
        }
        
        $sql .= " ORDER BY created_at DESC";
        return $this->query($sql, $params);
    }

    // ================================================================
    // GET VACCINATIONS FOR PATIENT (NEW METHOD)
    // ================================================================
    public function getPatientVaccinations($patientId, $prescriptionId = null) {
        $sql = "SELECT * FROM patient_vaccinations WHERE patient_id = ? AND is_active = 1";
        $params = [$patientId];
        
        if ($prescriptionId !== null && $prescriptionId > 0) {
            $sql .= " AND prescription_id = ?";
            $params[] = $prescriptionId;
        }
        
        $sql .= " ORDER BY date_given DESC, id DESC";
        return $this->query($sql, $params);
    }

    // ================================================================
    // GET LAB HISTORY FOR PATIENT (NEW METHOD)
    // ================================================================
    public function getLabHistory($patientId) {
        try {
            if (!$this->db) return ['test_names' => [], 'results' => []];
            
            $testNamesQuery = "SELECT DISTINCT t.test_name 
                               FROM lab_tests t
                               JOIN lab_test_order_items i ON i.test_id = t.id
                               JOIN lab_test_orders o ON i.order_id = o.id
                               WHERE o.patient_id = ? AND i.status = 'completed' AND i.result_value IS NOT NULL
                               UNION
                               SELECT DISTINCT test_name 
                               FROM manual_lab_results 
                               WHERE patient_id = ? AND result_value IS NOT NULL AND result_value != ''
                               ORDER BY test_name ASC";
            
            $testNames = $this->query($testNamesQuery, [$patientId, $patientId]);
            $testNames = array_column($testNames, 'test_name');

            if (empty($testNames)) {
                return ['test_names' => [], 'results' => []];
            }

            $query = "SELECT 
                        DATE(o.order_date) as test_date,
                        DATE_FORMAT(o.order_date, '%d-%m-%Y') as formatted_date,
                        o.order_number,
                        t.test_name,
                        i.result_value,
                        i.is_abnormal,
                        t.unit,
                        'lab_order' as source
                      FROM lab_test_orders o
                      JOIN lab_test_order_items i ON o.id = i.order_id
                      JOIN lab_tests t ON i.test_id = t.id
                      WHERE o.patient_id = ? 
                        AND i.status = 'completed'
                        AND i.result_value IS NOT NULL
                        AND i.result_value != ''
                      UNION
                      SELECT 
                        DATE(m.report_date) as test_date,
                        DATE_FORMAT(m.report_date, '%d-%m-%Y') as formatted_date,
                        'Manual' as order_number,
                        m.test_name,
                        m.result_value,
                        m.is_abnormal,
                        m.unit,
                        'manual' as source
                      FROM manual_lab_results m
                      WHERE m.patient_id = ? 
                        AND m.result_value IS NOT NULL
                        AND m.result_value != ''
                      ORDER BY test_date DESC, test_name ASC";
            
            $rows = $this->query($query, [$patientId, $patientId]);

            $results = [];
            foreach ($rows as $row) {
                $dateKey = $row['test_date'];
                if (!isset($results[$dateKey])) {
                    $results[$dateKey] = [
                        'date' => $row['formatted_date'],
                        'order_number' => $row['order_number'],
                        'source' => $row['source'],
                        'tests' => []
                    ];
                }
                $results[$dateKey]['tests'][$row['test_name']] = [
                    'value' => $row['result_value'],
                    'is_abnormal' => $row['is_abnormal'],
                    'unit' => $row['unit']
                ];
            }

            return ['test_names' => $testNames, 'results' => $results];
        } catch (Exception $e) {
            error_log("getLabHistory error: " . $e->getMessage());
            return ['test_names' => [], 'results' => []];
        }
    }
}