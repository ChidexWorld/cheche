<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "✅ Database connection successful!<br>";
        
        // Test table existence
        $tables = ['users', 'courses', 'videos', 'enrollments', 'video_progress'];
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->fetch()) {
                echo "✅ Table '$table' exists<br>";
            } else {
                echo "❌ Table '$table' missing<br>";
            }
        }
        
        echo "<br><strong>🎉 Cheche E-Learning Platform is ready!</strong><br>";
        echo "<a href='index.php'>Go to Homepage</a>";
        
    } else {
        echo "❌ Database connection failed";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>