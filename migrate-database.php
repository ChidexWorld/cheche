<?php
/**
 * Database Migration Script
 * Creates missing tables for quiz and subtitle functionality
 */

require_once 'config/database.php';

echo "<h2>🔄 Database Migration - Quiz & Subtitle System</h2>\n";

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "<p>📊 Starting database migration...</p>\n";

    // Check if we're using MySQL or file-based database
    if ($conn === $database) {
        echo "<p>ℹ️ Using file-based database - Tables will be created automatically when needed</p>\n";

        // Initialize empty data files for each table
        $tables = [
            'quizzes', 'quiz_questions', 'quiz_options', 'quiz_attempts',
            'quiz_responses', 'certificates', 'subtitles', 'translation_jobs'
        ];

        $data_dir = __DIR__ . '/data/';
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0755, true);
            echo "<p>✅ Created data directory: $data_dir</p>\n";
        }

        foreach ($tables as $table) {
            $file_path = $data_dir . $table . '.json';
            if (!file_exists($file_path)) {
                file_put_contents($file_path, json_encode([], JSON_PRETTY_PRINT));
                echo "<p>✅ Created file for table: $table</p>\n";
            } else {
                echo "<p>ℹ️ Table file already exists: $table</p>\n";
            }
        }

        echo "<p>✅ File-based database setup complete!</p>\n";

    } else {
        echo "<p>🗄️ Using MySQL database - Creating tables...</p>\n";

        // SQL statements for all new tables
        $sql_statements = [
            // Quizzes table
            "CREATE TABLE IF NOT EXISTS quizzes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                course_id INT NOT NULL,
                title VARCHAR(200) NOT NULL,
                description TEXT,
                passing_score DECIMAL(5,2) DEFAULT 70.00,
                max_attempts INT DEFAULT 3,
                time_limit INT DEFAULT 30,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
            )",

            // Quiz questions table
            "CREATE TABLE IF NOT EXISTS quiz_questions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                quiz_id INT NOT NULL,
                question_text TEXT NOT NULL,
                question_type ENUM('multiple_choice', 'true_false', 'short_answer') DEFAULT 'multiple_choice',
                points DECIMAL(5,2) DEFAULT 1.00,
                order_number INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
            )",

            // Quiz options table
            "CREATE TABLE IF NOT EXISTS quiz_options (
                id INT AUTO_INCREMENT PRIMARY KEY,
                question_id INT NOT NULL,
                option_text TEXT NOT NULL,
                is_correct BOOLEAN DEFAULT FALSE,
                order_number INT DEFAULT 0,
                FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
            )",

            // Quiz attempts table
            "CREATE TABLE IF NOT EXISTS quiz_attempts (
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
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
                INDEX idx_student_quiz (student_id, quiz_id)
            )",

            // Quiz responses table
            "CREATE TABLE IF NOT EXISTS quiz_responses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attempt_id INT NOT NULL,
                question_id INT NOT NULL,
                option_id INT NULL,
                answer_text TEXT NULL,
                is_correct BOOLEAN DEFAULT FALSE,
                points_earned DECIMAL(5,2) DEFAULT 0.00,
                FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
                FOREIGN KEY (option_id) REFERENCES quiz_options(id) ON DELETE CASCADE
            )",

            // Certificates table
            "CREATE TABLE IF NOT EXISTS certificates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                course_id INT NOT NULL,
                certificate_number VARCHAR(100) UNIQUE NOT NULL,
                issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                completion_percentage DECIMAL(5,2) DEFAULT 100.00,
                quiz_score DECIMAL(5,2) NULL,
                certificate_data JSON NULL,
                UNIQUE KEY unique_certificate (student_id, course_id),
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
            )",

            // Subtitles table
            "CREATE TABLE IF NOT EXISTS subtitles (
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
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
            )",

            // Translation jobs table
            "CREATE TABLE IF NOT EXISTS translation_jobs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                subtitle_id INT NOT NULL,
                job_type ENUM('translate', 'merge') NOT NULL,
                status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
                error_message TEXT NULL,
                started_at TIMESTAMP NULL,
                completed_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (subtitle_id) REFERENCES subtitles(id) ON DELETE CASCADE
            )"
        ];

        $tables_created = 0;
        $tables_failed = 0;

        foreach ($sql_statements as $sql) {
            try {
                $conn->exec($sql);
                $table_name = '';
                if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $matches)) {
                    $table_name = $matches[1];
                }
                echo "<p>✅ Table '$table_name' created successfully</p>\n";
                $tables_created++;
            } catch (PDOException $e) {
                $table_name = '';
                if (preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $matches)) {
                    $table_name = $matches[1];
                }
                echo "<p>❌ Failed to create table '$table_name': " . htmlspecialchars($e->getMessage()) . "</p>\n";
                $tables_failed++;
            }
        }

        echo "<p><strong>📊 Migration Summary:</strong></p>\n";
        echo "<p>✅ Tables created: $tables_created</p>\n";
        echo "<p>❌ Tables failed: $tables_failed</p>\n";

        if ($tables_failed > 0) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>\n";
            echo "<strong>⚠️ Some tables failed to create.</strong><br>\n";
            echo "This might be due to missing foreign key references or permission issues.<br>\n";
            echo "Please check your database configuration and try running the migration again.\n";
            echo "</div>\n";
        }
    }

    // Create upload directories
    echo "<h3>📁 Creating Upload Directories</h3>\n";
    $upload_dirs = [
        __DIR__ . '/uploads/subtitles',
        __DIR__ . '/uploads/merged_videos'
    ];

    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p>✅ Created directory: " . basename($dir) . "</p>\n";
            } else {
                echo "<p>❌ Failed to create directory: " . basename($dir) . "</p>\n";
            }
        } else {
            echo "<p>ℹ️ Directory already exists: " . basename($dir) . "</p>\n";
        }
    }

    echo "<div style='background: #d4edda; color: #155724; padding: 1rem; border-radius: 5px; margin: 2rem 0;'>\n";
    echo "<h3>🎉 Migration Complete!</h3>\n";
    echo "<p>The database has been updated with all necessary tables for:</p>\n";
    echo "<ul>\n";
    echo "<li>📝 Interactive Quiz System</li>\n";
    echo "<li>🏆 Certificate Generation</li>\n";
    echo "<li>🌍 Subtitle Translation (English → Igbo)</li>\n";
    echo "<li>🎬 Video-Subtitle Merging</li>\n";
    echo "</ul>\n";
    echo "<p><strong>You can now use all quiz and subtitle features!</strong></p>\n";
    echo "</div>\n";

    echo "<h3>🚀 Next Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Go to Instructor Dashboard → Quizzes tab to create your first quiz</li>\n";
    echo "<li>Upload videos with subtitle files for automatic Igbo translation</li>\n";
    echo "<li>Test the complete quiz and certificate workflow</li>\n";
    echo "<li>Students can now take quizzes and earn certificates</li>\n";
    echo "</ol>\n";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 5px; margin: 1rem 0;'>\n";
    echo "<strong>❌ Migration Error:</strong><br>\n";
    echo htmlspecialchars($e->getMessage()) . "\n";
    echo "</div>\n";

    echo "<h4>🔧 Troubleshooting:</h4>\n";
    echo "<ul>\n";
    echo "<li>Check your database connection settings in the .env file</li>\n";
    echo "<li>Ensure the database user has CREATE TABLE permissions</li>\n";
    echo "<li>Make sure the 'cheche' database exists</li>\n";
    echo "<li>Verify that the basic tables (users, courses, videos) exist first</li>\n";
    echo "</ul>\n";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: #f8f9fa;
}
h2, h3 { color: #333; }
h2 {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    padding: 1rem;
    border-radius: 10px;
}
p { margin: 0.5rem 0; }
ul, ol { margin-left: 2rem; }
</style>