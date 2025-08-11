<?php
/**
 * Database Base Class for Computer Lab Inventory System
 * Extends the basic database connection with inventory-specific methods
 */

require_once '../config.php';
require_once '../database_connection.php';

class Database extends DatabaseConnection {
    private static $instance = null;
    
    public function __construct() {
        global $config;
        parent::__construct(
            $config['database']['host'],
            $config['database']['name'],
            $config['database']['username'],
            $config['database']['password'],
            $config['database']['charset']
        );
    }
    
    /**
     * Singleton pattern for database connection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Execute a prepared statement with parameters
     */
    public function executeQuery($sql, $params = []) {
        try {
            $pdo = $this->getPdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw new Exception("Database operation failed");
        }
    }
    
    /**
     * Fetch all records from a query
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch single record from a query
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get the last inserted ID
     */
    public function getLastInsertId() {
        return $this->getPdo()->lastInsertId();
    }
    
    /**
     * Begin database transaction
     */
    public function beginTransaction() {
        return $this->getPdo()->beginTransaction();
    }
    
    /**
     * Commit database transaction
     */
    public function commit() {
        return $this->getPdo()->commit();
    }
    
    /**
     * Rollback database transaction
     */
    public function rollback() {
        return $this->getPdo()->rollback();
    }
    
    /**
     * Get total count from a table with optional conditions
     */
    public function getCount($table, $conditions = '', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM $table";
        if (!empty($conditions)) {
            $sql .= " WHERE $conditions";
        }
        
        $result = $this->fetchOne($sql, $params);
        return (int) $result['total'];
    }
    
    /**
     * Generic insert method
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        $columnList = implode(', ', $columns);
        
        $sql = "INSERT INTO $table ($columnList) VALUES ($placeholders)";
        
        // Prepare parameters with colons
        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }
        
        $this->executeQuery($sql, $params);
        return $this->getLastInsertId();
    }
    
    /**
     * Generic update method
     */
    public function update($table, $data, $where, $whereParams = []) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "$column = :$column";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE $table SET $setClause WHERE $where";
        
        // Prepare parameters
        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }
        
        // Add where parameters
        $params = array_merge($params, $whereParams);
        
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Generic delete method
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount();
    }
    
    /**
     * Log audit information
     */
    public function logAudit($tableName, $recordId, $action, $oldValues = null, $newValues = null, $userId = null) {
        $auditData = [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'user_id' => $userId,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        return $this->insert('audit_log', $auditData);
    }
    
    /**
     * Search method with full-text search support
     */
    public function search($table, $searchFields, $searchTerm, $additionalConditions = '', $params = []) {
        $searchConditions = [];
        
        // Create LIKE conditions for each search field
        foreach ($searchFields as $field) {
            $searchConditions[] = "$field LIKE :search";
        }
        
        $searchClause = '(' . implode(' OR ', $searchConditions) . ')';
        
        $sql = "SELECT * FROM $table WHERE $searchClause";
        
        if (!empty($additionalConditions)) {
            $sql .= " AND $additionalConditions";
        }
        
        $params[':search'] = "%$searchTerm%";
        
        return $this->fetchAll($sql, $params);
    }
}
?>