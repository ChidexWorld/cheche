<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->getConnection();

echo "=== Subtitle Records ===\n\n";

if ($conn === $db) {
    // File-based database
    $subtitles = $db->select('subtitles');
    print_r($subtitles);
} else {
    // MySQL database
    try {
        $stmt = $conn->query("SELECT * FROM subtitles LIMIT 10");
        $subtitles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($subtitles);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
