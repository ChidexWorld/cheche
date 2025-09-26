<?php
/**
 * Simple Database Setup Script
 * Run this after setting up the platform to ensure all tables exist
 */

require_once 'config/database.php';

echo "Setting up database...\n";

try {
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn === $database) {
        echo "✅ File-based database initialized successfully\n";
        echo "All tables are ready for use.\n";
    } else {
        echo "✅ MySQL database connection established\n";
        echo "All tables created or verified.\n";
    }

    echo "🎉 Database setup complete! You can now use all features.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration.\n";
}
?>