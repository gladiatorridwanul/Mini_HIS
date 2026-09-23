<?php
// app/core/Model.php

class Model {
    protected $db;
    protected $table;

    public function __construct() {
        global $conn;
        $this->db = $conn;
        
        if (!$this->db) {
            error_log("Database connection not available in Model");
        }
    }

    protected function query($sql, $params = []) {
        try {
            if (!$this->db) {
                error_log("Database connection not available for query: " . $sql);
                return [];
            }
            
            if (empty($params)) {
                $result = $this->db->query($sql);
                if ($result === false) {
                    error_log("Query failed: " . $this->db->error . " - SQL: " . $sql);
                    return [];
                }
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
                return $rows;
            }
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->error . " - SQL: " . $sql);
                return [];
            }
            
            $types = '';
            $bindParams = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param) || is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bindParams[] = $param;
            }
            
            if (!empty($types)) {
                $stmt->bind_param($types, ...$bindParams);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $rows = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                }
            }
            $stmt->close();
            return $rows;
        } catch (Exception $e) {
            error_log("Query error: " . $e->getMessage() . " - SQL: " . $sql);
            return [];
        }
    }

    protected function queryOne($sql, $params = []) {
        $rows = $this->query($sql, $params);
        return !empty($rows) ? $rows[0] : null;
    }

    protected function execute($sql, $params = []) {
        try {
            if (!$this->db) {
                error_log("Database connection not available for execute: " . $sql);
                return false;
            }
            
            if (empty($params)) {
                $result = $this->db->query($sql);
                if ($result === false) {
                    error_log("Execute failed: " . $this->db->error . " - SQL: " . $sql);
                    return false;
                }
                return true;
            }
            
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->error . " - SQL: " . $sql);
                return false;
            }
            
            $types = '';
            $bindParams = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_double($param) || is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bindParams[] = $param;
            }
            
            if (!empty($types)) {
                $stmt->bind_param($types, ...$bindParams);
            }
            
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        } catch (Exception $e) {
            error_log("Execute error: " . $e->getMessage() . " - SQL: " . $sql);
            return false;
        }
    }

    public function lastInsertId() {
        return $this->db ? $this->db->insert_id : 0;
    }
}