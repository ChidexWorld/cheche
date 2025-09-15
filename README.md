# Cheche - E-Learning Platform

A modern, functional e-learning platform built with PHP, MySQL, HTML, CSS, and JavaScript. Instructors can upload videos, and students can watch or download them.

## Features

### For Students
- User registration and authentication
- Browse and enroll in courses
- Watch videos with progress tracking
- Download videos for offline viewing
- Personal dashboard with learning progress
- Resume watching from where you left off

### For Instructors
- Create and manage courses
- Upload video content with metadata
- Track student progress and enrollment
- Manage course content and structure
- View student analytics

### Platform Features
- Clean, professional design
- Responsive layout for all devices
- Progress tracking and completion status
- Video streaming and download functionality
- Course organization and management
- User role management (Student/Instructor)

## Installation

### Prerequisites
- PHP 7.4 or higher
- Web browser (Chrome, Firefox, Safari, etc.)

### Quick Start (Development)
If you've cloned this repository, you can start the platform immediately without any database setup:

1. **Navigate to project directory**
   ```bash
   cd cheche
   ```

2. **Start PHP development server**

   **For large video uploads (recommended):**
   ```bash
   ./start-server.sh
   ```

   **Or manually with increased limits:**
   ```bash
   php -d upload_max_filesize=500M -d post_max_size=550M -d max_execution_time=1800 -S localhost:8000
   ```

   **Basic server (2MB upload limit):**
   ```bash
   php -S localhost:8000
   ```

3. **Open in browser**
   - Go to: `http://localhost:8000`
   - The platform uses file-based storage, so no database setup required for development

### Environment Configuration
The platform includes environment-based configuration:
- Copy `.env.example` to `.env` and customize settings
- Modify upload limits, file types, and security settings as needed
- All configurations are in the `.env` file

### Video Upload Configuration
**Current upload limits:**
- **Maximum file size**: 500MB per video
- **Supported formats**: MP4, AVI, MOV, WMV, MKV, WebM, FLV
- **Upload timeout**: 30 minutes
- **Memory limit**: 512MB

**To change upload limits:**
1. Edit `.env` file: `UPLOAD_MAX_SIZE=524288000` (500MB in bytes)
2. Start server with: `./start-server.sh` or use manual PHP flags
3. Supported extensions: `ALLOWED_VIDEO_EXTENSIONS=mp4,avi,mov,wmv,mkv,webm,flv`

### Production Setup
For production deployment:
- PHP 7.4 or higher
- MySQL 5.7 or higher (optional - platform works with file storage)
- Web server (Apache/Nginx)
- PDO PHP extension

### Database Setup
1. Configure your database credentials in the `.env` file
2. Create the database using the provided SQL file:
```bash
mysql -h YOUR_HOST -P YOUR_PORT -u YOUR_USERNAME -p < database.sql
```

2. The database will be created with the following tables:
   - `users` - User accounts (students and instructors)
   - `courses` - Course information
   - `videos` - Video content
   - `enrollments` - Student course enrollments
   - `video_progress` - Video watching progress

### File Permissions
Make sure the uploads directory is writable:
```bash
chmod 755 uploads/
chmod 755 uploads/videos/
```

### Configuration
Configure your database credentials in the `.env` file:
- Copy `.env.example` to `.env` (if available) or create a new `.env` file
- Set your database credentials in the `.env` file
- The platform will automatically load these settings

## File Structure

```
cheche/
├── index.php              # Root entry point (redirects to views)
├── database.sql          # Database schema
├── views/                 # User interface pages
│   ├── index.php         # Landing page
│   ├── login.php         # User login
│   ├── register.php      # User registration
│   ├── logout.php        # User logout
│   ├── student-dashboard.php # Student dashboard
│   ├── instructor-dashboard.php # Instructor dashboard
│   ├── course.php        # Course viewing page
│   ├── courses.php       # All courses listing
│   ├── manage-course.php # Course management
│   ├── forgot-password.php # Password recovery
│   ├── reset-password.php # Password reset
│   └── status.php        # System status page
├── config/
│   ├── database.php      # Database connection with .env support
│   ├── database_file.php # File-based database fallback
│   ├── env.php           # Environment variable loader
│   └── session.php       # Session management
├── api/
│   ├── create-course.php # Create new course
│   ├── upload-video.php  # Upload video files
│   ├── enroll.php        # Student enrollment
│   ├── get-courses.php   # Fetch courses API
│   ├── update-progress.php # Update video progress
│   ├── complete-video.php # Mark video complete
│   └── delete-video.php  # Delete video
├── assets/
│   ├── css/
│   │   └── style.css     # Main stylesheet
│   └── js/
│       └── main.js       # JavaScript functionality
├── uploads/
│   └── videos/           # Uploaded video files
├── data/                 # JSON data files (file storage mode)
├── .env                  # Environment configuration (not tracked)
└── .env.example          # Environment configuration template
```

## Usage

### Getting Started
1. Start the development server: `php -S localhost:8000`
2. Open `http://localhost:8000` in your web browser
3. Click "Get Started" to register a new account
4. Choose your role: Student (to learn) or Instructor (to teach)
5. Complete registration and login

### For Instructors
1. After login, you'll be redirected to the instructor dashboard
2. Create a new course from the "Create Course" tab
3. Add videos to your course from the "Upload Video" tab
4. Manage your courses and track student progress

### For Students
1. After login, you'll be redirected to the student dashboard
2. Browse available courses from the "Browse Courses" tab
3. Enroll in courses that interest you
4. Watch videos and track your progress

### Video Features
- **Streaming**: Videos play directly in the browser
- **Download**: Students can download videos for offline viewing
- **Progress Tracking**: System tracks watch time and completion
- **Resume Playback**: Students can resume from where they left off

## Technical Details

### Authentication
- Secure password hashing using PHP's `password_hash()`
- Session-based authentication
- Role-based access control (Student/Instructor)

### Video Upload
- Supports MP4, AVI, MOV, WMV, MKV formats
- File size limit: 100MB
- Automatic file validation and security checks
- Unique filename generation to prevent conflicts

### Progress Tracking
- Real-time progress updates every 10 seconds
- Completion status tracking
- Course-level progress calculation
- Resume functionality

### Security Features
- SQL injection prevention using prepared statements
- File upload validation and sanitization
- Access control for course content
- Session management and CSRF protection

## Customization

### Adding New Features
The platform is built with a modular structure that makes it easy to add new features:
- Add new API endpoints in the `api/` folder
- Extend database schema as needed
- Add new pages following the existing structure

### Styling
- Main styles are in `assets/css/style.css`
- Responsive design using CSS Grid and Flexbox
- Easy to customize colors, fonts, and layouts

## Troubleshooting

### Common Issues
1. **Database Connection**: Verify credentials in `.env` file
2. **File Uploads**: Check directory permissions for `uploads/videos/`
3. **Video Playback**: Ensure web server serves video files correctly
4. **Progress Not Saving**: Check database connection and API endpoints

### Development
For development, ensure PHP error reporting is enabled:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Support
This is a functional e-learning platform ready for immediate use. The code is well-structured and documented for easy maintenance and extension.