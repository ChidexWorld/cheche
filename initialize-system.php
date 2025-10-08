<?php
/**
 * One-time system initialization
 * Run this once to set up all database tables and directories
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Cheche Platform</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: #f8f9fa;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
        }
        .status {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .btn {
            background: #007bff;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #0056b3;
        }
        .feature-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        .feature {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 Initialize Cheche Platform</h1>
        <p>Set up your complete e-learning platform with quizzes, certificates, and Igbo subtitles</p>
    </div>

    <?php
    if (isset($_POST['initialize'])) {
        echo "<div class='info'><strong>🔄 Initializing system...</strong></div>";

        try {
            require_once 'config/database.php';

            $database = new Database();
            $conn = $database->getConnection();

            if ($conn === $database) {
                echo "<div class='success'>✅ File-based database system initialized</div>";
                echo "<div class='info'>📁 All data files have been created in the /data directory</div>";
            } else {
                echo "<div class='success'>✅ MySQL database connection established</div>";
                echo "<div class='info'>🗄️ All database tables have been created or verified</div>";
            }

            // Check directories
            $directories_created = 0;
            $upload_dirs = [
                'uploads/subtitles' => 'Subtitle files storage',
                'uploads/merged_videos' => 'Merged video files storage',
                'uploads/videos' => 'Video files storage'
            ];

            foreach ($upload_dirs as $dir => $description) {
                if (!is_dir($dir)) {
                    if (mkdir($dir, 0755, true)) {
                        echo "<div class='success'>✅ Created directory: $dir ($description)</div>";
                        $directories_created++;
                    } else {
                        echo "<div class='warning'>⚠️ Could not create directory: $dir</div>";
                    }
                } else {
                    echo "<div class='info'>📁 Directory already exists: $dir</div>";
                }
            }

            echo "<div class='success'>";
            echo "<h3>🎉 Initialization Complete!</h3>";
            echo "<p>Your Cheche platform is now fully set up and ready to use.</p>";
            echo "</div>";

            echo "<div class='info'>";
            echo "<h4>🌟 What's Available Now:</h4>";
            echo "<div class='feature-list'>";
            echo "<div class='feature'><strong>📚 Course Management</strong><br>Create and manage courses</div>";
            echo "<div class='feature'><strong>🎬 Video Upload</strong><br>Upload videos with progress tracking</div>";
            echo "<div class='feature'><strong>📝 Interactive Quizzes</strong><br>Create quizzes with multiple question types</div>";
            echo "<div class='feature'><strong>🏆 Certificates</strong><br>Automatic certificate generation</div>";
            echo "<div class='feature'><strong>🌍 Igbo Translation</strong><br>Automatic subtitle translation</div>";
            echo "<div class='feature'><strong>👥 User Management</strong><br>Student and instructor accounts</div>";
            echo "</div>";
            echo "</div>";

            echo "<div style='text-align: center; margin-top: 2rem;'>";
            echo "<a href='views/index.php' class='btn'>🏠 Go to Platform</a> ";
            echo "<a href='test-quiz-system.php' class='btn' style='background: #28a745;'>🧪 Test Quiz System</a> ";
            echo "<a href='test-subtitle-system.php' class='btn' style='background: #6f42c1;'>🎬 Test Subtitle System</a>";
            echo "</div>";

        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "<strong>❌ Initialization Error:</strong><br>";
            echo htmlspecialchars($e->getMessage());
            echo "</div>";

            echo "<div class='info'>";
            echo "<h4>💡 Troubleshooting Tips:</h4>";
            echo "<ul>";
            echo "<li>Make sure the uploads directory is writable</li>";
            echo "<li>Check database connection settings in config/env.php</li>";
            echo "<li>Ensure PHP has necessary extensions (PDO, MySQLi)</li>";
            echo "<li>For MySQL: ensure the database 'cheche' exists</li>";
            echo "</ul>";
            echo "</div>";
        }

    } else {
        ?>
        <div class="info">
            <h3>🎯 What will be initialized?</h3>
            <div class="feature-list">
                <div class="feature">
                    <strong>🗄️ Database Tables</strong><br>
                    Quiz system, certificate management, subtitle processing
                </div>
                <div class="feature">
                    <strong>📁 Directory Structure</strong><br>
                    Upload folders for videos, subtitles, and merged content
                </div>
                <div class="feature">
                    <strong>⚙️ System Configuration</strong><br>
                    Automatic setup for both MySQL and file-based storage
                </div>
                <div class="feature">
                    <strong>🔧 Error Checking</strong><br>
                    Verify all components are working correctly
                </div>
            </div>
        </div>

        <div class="warning">
            <h4>⚠️ Before you start:</h4>
            <ul>
                <li>Ensure your web server has write permissions to the project directory</li>
                <li>If using MySQL, make sure the 'cheche' database exists</li>
                <li>Check that PHP extensions (PDO, MySQLi) are installed</li>
                <li>This process is safe to run multiple times</li>
            </ul>
        </div>

        <form method="POST" style="text-align: center; margin-top: 2rem;">
            <button type="submit" name="initialize" class="btn" style="font-size: 1.2rem; padding: 1rem 2rem;">
                🚀 Initialize Platform
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; color: #666;">
            <small>This will set up all necessary components for the complete e-learning experience</small>
        </div>
        <?php
    }
    ?>

</body>
</html>