<?php
require_once __DIR__ . '/env.php';

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    private $last_insert_id;
    private static $instance = null;

    public function __construct() {
        $this->host = Env::get('DB_HOST', 'localhost');
        $this->port = Env::get('DB_PORT', '3306');
        $this->db_name = Env::get('DB_NAME', 'cheche');
        $this->username = Env::get('DB_USER', 'root');
        $this->password = Env::get('DB_PASSWORD', '');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Connection failed: " . $e->getMessage());
            }
        }
        return $this->conn;
    }

    // Select multiple records
    public function select($table, $where = [], $orderBy = '', $limit = null) {
        $query = "SELECT * FROM " . $table;
        $params = [];
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    $placeholders = rtrim(str_repeat('?,', count($value)), ',');
                    $conditions[] = "`$key` IN ($placeholders)";
                    foreach ($value as $v) $params[] = $v;
                } else {
                    $conditions[] = "`$key` = ?";
                    $params[] = $value;
                }
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        if ($orderBy) {
            $query .= " ORDER BY " . $orderBy;
        }
        
        if ($limit !== null) {
            $query .= " LIMIT " . intval($limit);
        }

        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Select a single record
    public function selectOne($table, $where = []) {
        $query = "SELECT * FROM " . $table;
        $params = [];
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "`$key` = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $query .= " LIMIT 1";
        
        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    // Count records
    public function count($table, $where = []) {
        $query = "SELECT COUNT(*) as count FROM " . $table;
        $params = [];
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "`$key` = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    // Insert a record
    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = rtrim(str_repeat('?,', count($fields)), ',');
        
        $query = "INSERT INTO " . $table . " (`" . implode('`, `', $fields) . "`) VALUES (" . $placeholders . ")";
        
        $stmt = $this->getConnection()->prepare($query);
        $stmt->execute($values);
        
        return $this->getConnection()->lastInsertId();
    }

    // Update records
    public function update($table, $data, $where) {
        $fields = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "`$key` = ?";
            $values[] = $value;
        }
        
        $query = "UPDATE " . $table . " SET " . implode(', ', $fields);
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "`$key` = ?";
                $values[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $stmt = $this->getConnection()->prepare($query);
        return $stmt->execute($values);
    }

    // Delete records
    public function delete($table, $where) {
        $query = "DELETE FROM " . $table;
        $params = [];
        
        if (!empty($where)) {
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "`$key` = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $stmt = $this->getConnection()->prepare($query);
        return $stmt->execute($params);
    }
}