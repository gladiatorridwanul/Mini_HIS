<?php
// /core/Database.php
// Database connection class for UniDia Healthcare

class Database {
    
    private static $instance = null;
    private $connection = null;
    private $host = 'localhost';
    private $user = 'root';
    private $password = '<Admin123!@#>';
    private $database = 'unidia_db';
    private $port = 3306;
    
    private function __construct() {
        try {
            $this->connection = new mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->database,
                $this->port
            );
            
            if ($this->connection->connect_error) {
                throw new Exception('Database connection failed: ' . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die('Database Error: ' . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function setConnection($conn) {
        $this->connection = $conn;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            throw new Exception('Query preparation failed: ' . $this->connection->error);
        }
        
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param) || is_double($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 's';
                }
                $bindParams[] = $param;
            }
            $stmt->bind_param($types, ...$bindParams);
        }
        
        $stmt->execute();
        
        if (stripos(trim($sql), 'SELECT') === 0 || 
            stripos(trim($sql), 'SHOW') === 0 || 
            stripos(trim($sql), 'DESCRIBE') === 0 ||
            stripos(trim($sql), 'EXPLAIN') === 0) {
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            return new DatabaseResult($data);
        }
        
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        return $affectedRows;
    }
    
    // core/Database.php
public function execute($sql, $params = []) {
    $stmt = $this->connection->prepare($sql);
    if ($stmt) {
        if (!empty($params)) {
            // Build the types string
            $types = '';
            $bindParams = [];
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $bindParams[] = $param;
            }
            // Bind parameters
            $stmt->bind_param($types, ...$bindParams);
        }
        return $stmt->execute();
    }
    return false;
}
    
    public function lastInsertId() {
        return $this->connection->insert_id;
    }
    
    public function affectedRows() {
        return $this->connection->affected_rows;
    }
    
    public function beginTransaction() {
        $this->connection->begin_transaction();
    }
    
    public function commit() {
        $this->connection->commit();
    }
    
    public function rollback() {
        $this->connection->rollback();
    }
    
    public function escapeString($string) {
        return $this->connection->real_escape_string($string);
    }
}

/**
 * Database Result wrapper for fetch methods
 */
class DatabaseResult {
    private $data;
    private $index = 0;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function fetch() {
        if (isset($this->data[$this->index])) {
            return $this->data[$this->index++];
        }
        return null;
    }
    
    public function fetchAll() {
        return $this->data;
    }
    
    public function rowCount() {
        return count($this->data);
    }
}