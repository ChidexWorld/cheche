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
                // Try PDO first
                if (class_exists('PDO') && in_array('mysql', PDO::getAvailableDrivers())) {
                    $this->conn = new PDO(
                        "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name,
                        $this->username,
                        $this->password
                    );
                    $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                } elseif (class_exists('mysqli')) {
                    // Fallback to mysqli
                    $this->conn = new MySQLiPDOWrapper(
                        $this->host,
                        $this->username,
                        $this->password,
                        $this->db_name,
                        $this->port
                    );
                } else {
                    // Fallback to file storage for development
                    error_log("MySQL not available, falling back to file storage");
                    return $this; // Use existing file-based methods
                }
            } catch(Exception $exception) {
                // If MySQL connection fails, fallback to file storage
                error_log("MySQL connection failed: " . $exception->getMessage() . ", falling back to file storage");
                return $this; // Use existing file-based methods
            }
        }
        return $this->conn;
    }
    public function prepare($query) {
        $conn = $this->getConnection();
        if ($conn === $this) {
            return new FileStatementWrapper($query, $this);
        }
        return $conn->prepare($query);
    }

    public function lastInsertId() {
        $conn = $this->getConnection();
        if ($conn === $this) {
            return $this->last_insert_id ?? 0;
        }
        return $conn->lastInsertId();
    }

    public function setAttribute($attribute, $value) {
        $conn = $this->getConnection();
        if ($conn === $this) {
            return true; // File-based system
        }
        return $conn->setAttribute($attribute, $value);
    }

    // File-based database methods (fallback)
    private function loadTable($table) {
        $data_dir = __DIR__ . '/../data/';
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0755, true);
        }

        $file = $data_dir . $table . '.json';
        if (file_exists($file)) {
            $data = file_get_contents($file);
            return json_decode($data, true) ?: [];
        }
        return [];
    }

    private function saveTable($table, $data) {
        $data_dir = __DIR__ . '/../data/';
        $file = $data_dir . $table . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function insertRecord($table, $data) {
        $records = $this->loadTable($table);
        $data['id'] = count($records) + 1;
        $data['created_at'] = date('Y-m-d H:i:s');
        $records[] = $data;
        $this->saveTable($table, $records);
        $this->last_insert_id = $data['id'];
        return $data['id'];
    }

    public function selectRecords($table, $where = []) {
        $records = $this->loadTable($table);

        if (!empty($where)) {
            $records = array_filter($records, function($record) use ($where) {
                foreach ($where as $field => $value) {
                    if (!isset($record[$field]) || $record[$field] != $value) {
                        return false;
                    }
                }
                return true;
            });
        }

        return array_values($records);
    }
}

// MySQLi wrapper to mimic PDO interface
class MySQLiPDOWrapper {
    private $mysqli;

    public function __construct($host, $username, $password, $database, $port) {
        $this->mysqli = new mysqli($host, $username, $password, $database, $port);
        if ($this->mysqli->connect_error) {
            throw new Exception("Connection failed: " . $this->mysqli->connect_error);
        }
        $this->mysqli->set_charset("utf8");
    }

    public function prepare($query) {
        $stmt = $this->mysqli->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->mysqli->error);
        }
        return new MySQLiStatementWrapper($stmt, $this->mysqli);
    }

    public function lastInsertId() {
        return $this->mysqli->insert_id;
    }

    public function setAttribute($attribute, $value) {
        // PDO compatibility - ignore for mysqli
        return true;
    }
}

class MySQLiStatementWrapper {
    private $stmt;
    private $mysqli;
    private $result;

    public function __construct($stmt, $mysqli) {
        $this->stmt = $stmt;
        $this->mysqli = $mysqli;
    }

    public function execute($params = []) {
        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // Assume all strings for simplicity
            $this->stmt->bind_param($types, ...$params);
        }

        $result = $this->stmt->execute();
        if (!$result) {
            throw new Exception("Execute failed: " . $this->stmt->error);
        }

        $this->result = $this->stmt->get_result();
        return $result;
    }

    public function fetch() {
        if ($this->result) {
            return $this->result->fetch_assoc();
        }
        return false;
    }

    public function fetchAll() {
        if ($this->result) {
            return $this->result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function fetchColumn() {
        if ($this->result) {
            $row = $this->result->fetch_row();
            return $row ? $row[0] : false;
        }
        return false;
    }
}

// File-based statement wrapper for fallback
class FileStatementWrapper {
    private $query;
    private $db;
    private $params = [];

    public function __construct($query, $db) {
        $this->query = $query;
        $this->db = $db;
    }

    public function execute($params = []) {
        $this->params = $params;
        return true;
    }

    public function fetch() {
        return null;
    }

    public function fetchAll() {
        return [];
    }

    public function fetchColumn() {
        return null;
    }
}