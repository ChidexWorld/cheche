# 🔧 Troubleshooting Guide

## Database Table Errors

### Error: "Table 'cheche.quizzes' doesn't exist"

This error occurs when the quiz and certificate system tables haven't been created yet.

**Solutions (choose one):**

### 1. 🚀 Quick Fix - Use Initialization Script
```
1. Open your browser
2. Go to: http://localhost:8000/initialize-system.php
3. Click "Initialize Platform"
4. Wait for completion message
```

### 2. 🔧 Manual Fix - Run Migration
```bash
# Option A: Through web browser
http://localhost:8000/migrate-database.php

# Option B: Command line
php migrate-database.php

# Option C: Simple setup
php setup-database.php
```

### 3. 📝 Manual MySQL Setup
```sql
-- Connect to MySQL and run:
USE cheche;
SOURCE database.sql;
```

## Common Issues

### File-Based Database (Default Mode)

**Issue:** Permission errors when creating data files
```
Solution:
- Ensure the project directory is writable
- Check that PHP can create files in the /data directory
- On Linux/Mac: chmod 755 data/
- On Windows: Ensure the folder isn't read-only
```

**Issue:** Data not persisting
```
Solution:
- Check if /data/*.json files are being created
- Verify file permissions
- Look for errors in PHP error log
```

### MySQL Database Mode

**Issue:** "Database 'cheche' doesn't exist"
```
Solution:
1. Create database: CREATE DATABASE cheche;
2. Import schema: mysql -u root -p cheche < database.sql
3. Or use initialization script
```

**Issue:** "Access denied for user"
```
Solution:
1. Check .env file for correct credentials
2. Ensure MySQL user has CREATE, INSERT, UPDATE, DELETE permissions
3. Test connection: php -r "new PDO('mysql:host=localhost;dbname=cheche', 'user', 'pass');"
```

**Issue:** Foreign key constraint errors
```
Solution:
1. Ensure base tables (users, courses, videos) exist first
2. Run: SET FOREIGN_KEY_CHECKS=0; before importing
3. Use the initialization script which handles dependencies
```

## Feature-Specific Issues

### Quiz System
- **Error creating quiz:** Run database initialization
- **Quiz not loading:** Check browser console for JavaScript errors
- **Questions not saving:** Verify database tables exist

### Certificate System
- **Certificate generation fails:** Ensure certificates table exists
- **PDF download issues:** Check write permissions in uploads directory

### Subtitle System
- **Upload fails:** Check uploads/subtitles directory permissions
- **Translation not working:** Verify subtitles table exists
- **Video merge errors:** Install FFmpeg for full functionality

## Verification Steps

### Check Database Status
```php
// Run this in test-quiz-system.php or test-subtitle-system.php
require_once 'config/database.php';
$db = new Database();
$conn = $db->getConnection();

if ($conn === $db) {
    echo "File-based database active\n";
} else {
    echo "MySQL database active\n";
}
```

### Verify Tables Exist (MySQL)
```sql
SHOW TABLES LIKE 'quiz%';
SHOW TABLES LIKE 'certificate%';
SHOW TABLES LIKE 'subtitle%';
```

### Check File Storage
```bash
ls -la data/
# Should show: quizzes.json, certificates.json, subtitles.json, etc.
```

## Reset Everything

### Complete Reset
```bash
# Backup first if needed
cp -r data/ data-backup/
cp -r uploads/ uploads-backup/

# Clean slate
rm -rf data/
rm -rf uploads/subtitles/
rm -rf uploads/merged_videos/

# Re-initialize
php setup-database.php
# OR visit: http://localhost:8000/initialize-system.php
```

### MySQL Reset Only
```sql
DROP DATABASE IF EXISTS cheche;
CREATE DATABASE cheche;
USE cheche;
SOURCE database.sql;
```

## Getting Help

1. **Check PHP Error Log:** Look for detailed error messages
2. **Browser Console:** Check for JavaScript errors
3. **Test Scripts:** Run `test-quiz-system.php` and `test-subtitle-system.php`
4. **Verification:** Use `initialize-system.php` to verify setup

## Quick Start Checklist

✅ Run `http://localhost:8000/initialize-system.php`
✅ Verify green checkmarks for all components
✅ Test quiz creation in instructor dashboard
✅ Test video upload with subtitles
✅ Check student can view content

If all steps pass, your platform is fully functional!