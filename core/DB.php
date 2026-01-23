<?php
/**
 * Database Connection Handler
 * Production-ready PDO wrapper
 */

class DB {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            // Try to find MySQL socket if localhost doesn't work
            $host = DB_HOST;
            $socket = null;
            
            // If using localhost, try to find socket path
            if ($host === 'localhost' || $host === '127.0.0.1') {
                // Common socket paths
                $socketPaths = [
                    '/var/run/mysqld/mysqld.sock',
                    '/var/lib/mysql/mysql.sock',
                    '/tmp/mysql.sock',
                    '/run/mysqld/mysqld.sock'
                ];
                
                foreach ($socketPaths as $path) {
                    if (file_exists($path)) {
                        $socket = $path;
                        break;
                    }
                }
            }
            
            // Build DSN
            if ($socket && ($host === 'localhost' || $host === '127.0.0.1')) {
                $dsn = sprintf(
                    "mysql:unix_socket=%s;dbname=%s;charset=%s",
                    $socket,
                    DB_NAME,
                    DB_CHARSET
                );
            } else {
                // Use 127.0.0.1 instead of localhost to force TCP
                $host = ($host === 'localhost') ? '127.0.0.1' : $host;
                $dsn = sprintf(
                    "mysql:host=%s;dbname=%s;charset=%s",
                    $host,
                    DB_NAME,
                    DB_CHARSET
                );
            }
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
                PDO::ATTR_TIMEOUT => 5
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $errorMsg = "DB Connection Error: " . $e->getMessage();
            error_log($errorMsg);
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("DB Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw $e;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $result = $this->query($sql, $params)->fetch();
        return $result ? $result : null;
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $fieldsStr = implode(', ', $fields);
        
        $sql = "INSERT INTO `{$table}` ({$fieldsStr}) VALUES ({$placeholders})";
        $this->query($sql, $data);
        return $this->pdo->lastInsertId();
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $field) {
            $set[] = "`{$field}` = :{$field}";
        }
        $setStr = implode(', ', $set);
        
        $sql = "UPDATE `{$table}` SET {$setStr} WHERE {$where}";
        $params = array_merge($data, $whereParams);
        return $this->query($sql, $params);
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        return $this->query($sql, $params);
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollBack();
    }
}
