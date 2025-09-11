<?php
class Database {
    private $data_dir;
    
    public function __construct() {
        $this->data_dir = __DIR__ . '/../data/';
        if (!is_dir($this->data_dir)) {
            mkdir($this->data_dir, 0755, true);
        }
    }

    public function getConnection() {
        return $this;
    }
    
    public function prepare($query) {
        return new FileStatementWrapper($query, $this->data_dir);
    }
    
    public function lastInsertId() {
        return $this->last_insert_id ?? 0;
    }
    
    public function setAttribute($attribute, $value) {
        return true;
    }
    
    private function loadTable($table) {
        $file = $this->data_dir . $table . '.json';
        if (file_exists($file)) {
            $data = file_get_contents($file);
            return json_decode($data, true) ?: [];
        }
        return [];
    }
    
    private function saveTable($table, $data) {
        $file = $this->data_dir . $table . '.json';
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
    
    public function selectRecords($table, $where = [], $order = '', $limit = null) {
        $records = $this->loadTable($table);
        
        // Apply WHERE conditions
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
        
        // Apply ordering
        if ($order) {
            // Simple ordering by field
            $orderField = str_replace([' ASC', ' DESC'], '', $order);
            $orderDir = strpos($order, 'DESC') !== false ? 'DESC' : 'ASC';
            
            usort($records, function($a, $b) use ($orderField, $orderDir) {
                $result = strcmp($a[$orderField] ?? '', $b[$orderField] ?? '');
                return $orderDir === 'DESC' ? -$result : $result;
            });
        }
        
        // Apply limit
        if ($limit !== null) {
            $records = array_slice($records, 0, $limit);
        }
        
        return $records;
    }
    
    public function updateRecord($table, $data, $where) {
        $records = $this->loadTable($table);
        
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
            }
        }
        
        $this->saveTable($table, $records);
        return true;
    }
    
    public function deleteRecord($table, $where) {
        $records = $this->loadTable($table);
        
        $records = array_filter($records, function($record) use ($where) {
            foreach ($where as $field => $value) {
                if (isset($record[$field]) && $record[$field] == $value) {
                    return false; // Remove this record
                }
            }
            return true; // Keep this record
        });
        
        $this->saveTable($table, array_values($records));
        return true;
    }
}

class FileStatementWrapper {
    private $query;
    private $data_dir;
    private $params = [];
    
    public function __construct($query, $data_dir) {
        $this->query = $query;
        $this->data_dir = $data_dir;
    }
    
    public function execute($params = []) {
        $this->params = $params;
        return true;
    }
    
    public function fetch() {
        // This is a simplified implementation
        // In a real scenario, you'd parse the SQL and execute accordingly
        return null;
    }
    
    public function fetchAll() {
        return [];
    }
    
    public function fetchColumn() {
        return null;
    }
}