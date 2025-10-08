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

                    // Initialize tables if they don't exist
                    $this->initializeTables();

                } elseif (class_exists('mysqli')) {
                    // Fallback to mysqli
                    $this->conn = new MySQLiPDOWrapper(
                        $this->host,
                        $this->username,
                        $this->password,
                        $this->db_name,
                        $this->port
                    );

                    // Initialize tables if they don't exist
                    $this->initializeTables();

                } else {
                    // Fallback to file storage for development
                    error_log("MySQL not available, falling back to file storage");
                    $this->initializeFileStorage();
                    return $this; // Use existing file-based methods
                }
            } catch(Exception $exception) {
                // If MySQL connection fails, fallback to file storage
                error_log("MySQL connection failed: " . $exception->getMessage() . ", falling back to file storage");
                $this->initializeFileStorage();
                return $this; // Use existing file-based methods
            }
        }
        return $this->conn;
    }

    private function initializeTables() {
        try {
            // Check if quiz tables exist, if not create them
            $tables_to_check = [
                'quizzes' => "CREATE TABLE IF NOT EXISTS quizzes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    course_id INT NOT NULL,
                    title VARCHAR(200) NOT NULL,
                    description TEXT,
                    passing_score DECIMAL(5,2) DEFAULT 70.00,
                    max_attempts INT DEFAULT 3,
                    time_limit INT DEFAULT 30,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )",
                'quiz_questions' => "CREATE TABLE IF NOT EXISTS quiz_questions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    quiz_id INT NOT NULL,
                    question_text TEXT NOT NULL,
                    question_type ENUM('multiple_choice', 'true_false', 'short_answer') DEFAULT 'multiple_choice',
                    points DECIMAL(5,2) DEFAULT 1.00,
                    order_number INT DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                'quiz_options' => "CREATE TABLE IF NOT EXISTS quiz_options (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    question_id INT NOT NULL,
                    option_text TEXT NOT NULL,
                    is_correct BOOLEAN DEFAULT FALSE,
                    order_number INT DEFAULT 0
                )",
                'quiz_attempts' => "CREATE TABLE IF NOT EXISTS quiz_attempts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    student_id INT NOT NULL,
                    quiz_id INT NOT NULL,
                    attempt_number INT DEFAULT 1,
                    score DECIMAL(5,2) DEFAULT 0.00,
                    max_score DECIMAL(5,2) DEFAULT 0.00,
                    passed BOOLEAN DEFAULT FALSE,
                    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    completed_at TIMESTAMP NULL,
                    time_taken INT DEFAULT 0,
                    INDEX idx_student_quiz (student_id, quiz_id)
                )",
                'quiz_responses' => "CREATE TABLE IF NOT EXISTS quiz_responses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    attempt_id INT NOT NULL,
                    question_id INT NOT NULL,
                    option_id INT NULL,
                    answer_text TEXT NULL,
                    is_correct BOOLEAN DEFAULT FALSE,
                    points_earned DECIMAL(5,2) DEFAULT 0.00
                )",
                'certificates' => "CREATE TABLE IF NOT EXISTS certificates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    student_id INT NOT NULL,
                    course_id INT NOT NULL,
                    certificate_number VARCHAR(100) UNIQUE NOT NULL,
                    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    completion_percentage DECIMAL(5,2) DEFAULT 100.00,
                    quiz_score DECIMAL(5,2) NULL,
                    certificate_data JSON NULL,
                    UNIQUE KEY unique_certificate (student_id, course_id)
                )",
                'subtitles' => "CREATE TABLE IF NOT EXISTS subtitles (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    video_id INT NOT NULL,
                    original_file_path VARCHAR(500) NULL,
                    translated_file_path VARCHAR(500) NULL,
                    merged_video_path VARCHAR(500) NULL,
                    language_from VARCHAR(10) DEFAULT 'en',
                    language_to VARCHAR(10) DEFAULT 'ig',
                    translation_status ENUM('pending', 'translating', 'completed', 'failed') DEFAULT 'pending',
                    merge_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )",
                'translation_jobs' => "CREATE TABLE IF NOT EXISTS translation_jobs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    subtitle_id INT NOT NULL,
                    job_type ENUM('translate', 'merge') NOT NULL,
                    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
                    error_message TEXT NULL,
                    started_at TIMESTAMP NULL,
                    completed_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )"
            ];

            foreach ($tables_to_check as $table => $sql) {
                try {
                    $this->conn->exec($sql);
                } catch (Exception $e) {
                    // Log the error but continue - table might already exist
                    error_log("Table creation warning for $table: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            error_log("Error initializing tables: " . $e->getMessage());
        }
    }

    private function initializeFileStorage() {
        // Initialize file-based storage directories and files
        $data_dir = __DIR__ . '/../data/';
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0755, true);
        }

        $tables = [
            'users', 'courses', 'videos', 'enrollments', 'video_progress',
            'quizzes', 'quiz_questions', 'quiz_options', 'quiz_attempts',
            'quiz_responses', 'certificates', 'subtitles', 'translation_jobs'
        ];

        foreach ($tables as $table) {
            $file = $data_dir . $table . '.json';
            if (!file_exists($file)) {
                file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
            }
        }

        // Create upload directories
        $upload_dirs = [
            __DIR__ . '/../uploads/subtitles',
            __DIR__ . '/../uploads/merged_videos'
        ];

        foreach ($upload_dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
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

    // API-compatible methods
    public function select($table, $where = [], $orderBy = '', $limit = null) {
        $records = $this->loadTable($table);

        // Apply WHERE conditions
        if (!empty($where)) {
            $records = array_filter($records, function($record) use ($where) {
                foreach ($where as $field => $value) {
                    if (!isset($record[$field])) {
                        return false;
                    }
                    if (is_array($value)) {
                        if (!in_array($record[$field], $value)) {
                            return false;
                        }
                    } else {
                        if ($record[$field] != $value) {
                            return false;
                        }
                    }
                }
                return true;
            });
        }

        // Apply ordering
        if ($orderBy) {
            $orderParts = explode(' ', trim($orderBy));
            $orderField = $orderParts[0];
            $orderDir = isset($orderParts[1]) && strtoupper($orderParts[1]) === 'ASC' ? 'ASC' : 'DESC';

            usort($records, function($a, $b) use ($orderField, $orderDir) {
                $valA = $a[$orderField] ?? '';
                $valB = $b[$orderField] ?? '';
                $result = strcmp($valA, $valB);
                return $orderDir === 'DESC' ? -$result : $result;
            });
        }

        // Apply limit
        if ($limit !== null) {
            $records = array_slice($records, 0, $limit);
        }

        return array_values($records);
    }

    public function selectOne($table, $where = []) {
        $results = $this->select($table, $where, '', 1);
        return !empty($results) ? $results[0] : null;
    }

    public function count($table, $where = []) {
        return count($this->select($table, $where));
    }

    public function insert($table, $data) {
        return $this->insertRecord($table, $data);
    }

    public function update($table, $data, $where) {
        $records = $this->loadTable($table);
        $updated = false;

        foreach ($records as $key => $record) {
            $match = true;
            foreach ($where as $field => $value) {
                if (!isset($record[$field]) || $record[$field] != $value) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                $records[$key] = array_merge($record, $data);
                $records[$key]['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
            }
        }

        if ($updated) {
            $this->saveTable($table, $records);
        }
        return $updated;
    }

    public function delete($table, $where) {
        $records = $this->loadTable($table);

        $records = array_filter($records, function($record) use ($where) {
            // Check if ALL where conditions match this record
            foreach ($where as $field => $value) {
                if (!isset($record[$field]) || $record[$field] != $value) {
                    return true; // Keep this record (doesn't match all conditions)
                }
            }
            return false; // Remove this record (matches all conditions)
        });

        $this->saveTable($table, array_values($records));
        return true;
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