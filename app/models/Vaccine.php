<?php
// /app/models/Vaccine.php

class Vaccine extends Model {
    protected $table = 'patient_vaccinations';

    public function __construct() {
        parent::__construct();
    }

    // Get all vaccines for a patient
    public function getPatientVaccines($patientId, $limit = null, $offset = null) {
        $sql = "SELECT pv.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       DATE_FORMAT(pv.date_given, '%d-%m-%Y') as date_given_formatted,
                       DATE_FORMAT(pv.next_due, '%d-%m-%Y') as next_due_formatted
                FROM {$this->table} pv
                LEFT JOIN users u ON pv.created_by = u.id
                WHERE pv.patient_id = ? AND pv.is_active = 1
                ORDER BY pv.date_given DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            if ($offset !== null) {
                $sql .= " OFFSET ?";
                return $this->query($sql, [$patientId, $limit, $offset]);
            }
            return $this->query($sql, [$patientId, $limit]);
        }
        
        return $this->query($sql, [$patientId]);
    }

    // Get total count for pagination
    public function getTotalCount($patientId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE patient_id = ? AND is_active = 1";
        $result = $this->queryOne($sql, [$patientId]);
        return $result['total'] ?? 0;
    }

    // Get vaccine by ID
    public function getById($id) {
        $sql = "SELECT pv.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name
                FROM {$this->table} pv
                LEFT JOIN users u ON pv.created_by = u.id
                WHERE pv.id = ? AND pv.is_active = 1";
        return $this->queryOne($sql, [$id]);
    }

    // Search vaccines
    public function search($patientId, $searchTerm) {
        $sql = "SELECT pv.*, 
                       CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                       DATE_FORMAT(pv.date_given, '%d-%m-%Y') as date_given_formatted
                FROM {$this->table} pv
                LEFT JOIN users u ON pv.created_by = u.id
                WHERE pv.patient_id = ? 
                AND pv.is_active = 1
                AND (pv.vaccine_name LIKE ? OR pv.batch_number LIKE ? OR pv.dose LIKE ?)
                ORDER BY pv.date_given DESC";
        $searchTerm = "%{$searchTerm}%";
        return $this->query($sql, [$patientId, $searchTerm, $searchTerm, $searchTerm]);
    }

    // Create vaccine
    public function create($data) {
        $fields = ['patient_id', 'vaccine_name', 'dose', 'date_given', 'next_due', 
                   'batch_number', 'site', 'administered_by', 'notes', 'vaccine_image', 
                   'created_by', 'prescription_id'];
        
        $insertFields = [];
        $values = [];
        $types = '';
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $insertFields[] = $field;
                $values[] = $data[$field];
                $types .= is_int($data[$field]) ? 'i' : 's';
            }
        }
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $insertFields) . ") 
                VALUES (" . rtrim(str_repeat('?, ', count($insertFields)), ', ') . ")";
        
        return $this->execute($sql, $values);
    }

    // Update vaccine
    public function update($id, $data) {
        $set = [];
        $values = [];
        
        $allowedFields = ['vaccine_name', 'dose', 'date_given', 'next_due', 
                          'batch_number', 'site', 'administered_by', 'notes', 'vaccine_image'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $set[] = "{$field} = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($set)) {
            return false;
        }
        
        $values[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE id = ?";
        return $this->execute($sql, $values);
    }

    // Soft delete vaccine
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET is_active = 0 WHERE id = ?";
        return $this->execute($sql, [$id]);
    }

    // Get vaccines for prescription view
    public function getForPrescription($patientId, $prescriptionId = null) {
        $sql = "SELECT pv.*, 
                       DATE_FORMAT(pv.date_given, '%d-%m-%Y') as date_given_formatted,
                       DATE_FORMAT(pv.next_due, '%d-%m-%Y') as next_due_formatted
                FROM {$this->table} pv
                WHERE pv.patient_id = ? AND pv.is_active = 1";
        $params = [$patientId];
        
        if ($prescriptionId) {
            $sql .= " AND pv.prescription_id = ?";
            $params[] = $prescriptionId;
        }
        
        $sql .= " ORDER BY pv.date_given DESC";
        return $this->query($sql, $params);
    }

    // Get vaccine card data for printing
    public function getVaccineCardData($patientId) {
        $sql = "SELECT pv.*,
                       DATE_FORMAT(pv.date_given, '%d-%m-%Y') as date_given_formatted,
                       DATE_FORMAT(pv.next_due, '%d-%m-%Y') as next_due_formatted,
                       CONCAT(pa.first_name, ' ', pa.last_name) as patient_name,
                       pa.patient_code,
                       pa.date_of_birth,
                       pa.gender,
                       pa.address,
                       pa.phone
                FROM {$this->table} pv
                JOIN patients pa ON pv.patient_id = pa.id
                WHERE pv.patient_id = ? AND pv.is_active = 1
                ORDER BY pv.date_given DESC";
        return $this->query($sql, [$patientId]);
    }
}