# Cheche - E-Learning Platform with Igbo Language Support

A modern, multilingual e-learning platform built with PHP, MySQL, HTML, CSS, and JavaScript. Features automatic English-to-Igbo subtitle translation, interactive quizzes, certificate generation, and comprehensive course management. Designed specifically for the Nigerian educational context with support for indigenous languages.

## 🌟 Unique Features
- 🌍 **Automatic Subtitle Translation**: Built-in English-to-Igbo dictionary (no API required)
- 📝 **Interactive Quizzes**: Multiple question types with automatic grading
- 🏆 **Certificate Generation**: Automatic certificates upon course completion
- 📹 **Video Upload**: Up to 500MB with real-time progress tracking
- 🎯 **Multilingual Support**: Toggle between English and Igbo subtitles
- 💾 **Flexible Storage**: Works with file-based storage or MySQL database
- 🔒 **Zero External Dependencies**: All translation happens offline locally

## Features

### For Students
- User registration and authentication
- Browse and enroll in courses
- Watch videos with progress tracking
- Download videos for offline viewing
- Personal dashboard with learning progress
- Resume watching from where you left off
- **Take interactive quizzes** with multiple question types
- **Earn certificates** upon course completion
- Track quiz attempts and scores
- View and print/download certificates
- **Watch videos with Igbo subtitles** for enhanced learning
- **Toggle between English and Igbo subtitles** when available
- **Improved accessibility** through multilingual subtitle support

### For Instructors
- Create and manage courses
- Upload video content with metadata
- Track student progress and enrollment
- Manage course content and structure
- View student analytics
- **Create and manage quizzes** with different question types
- **Monitor certificate issuance** for completed courses
- Set quiz parameters (time limits, attempts, passing scores)
- Track quiz performance and statistics
- **Upload subtitles** with automatic Igbo translation
- **Video-subtitle merging** for embedded multilingual support
- **Subtitle management interface** for processing status tracking

### Platform Features
- Clean, professional design
- Responsive layout for all devices
- Progress tracking and completion status
- Video streaming and download functionality
- Course organization and management
- User role management (Student/Instructor)
- **Interactive quiz system** with automatic grading
- **Certificate generation** with unique verification numbers
- **Multi-attempt quizzes** with time limits
- **Real-time quiz timer** and progress tracking
- **🌍 Automatic subtitle translation** (English → Igbo)
- **🎬 Video-subtitle merging** with embedded multilingual support
- **📝 Advanced subtitle management** for instructors
- **🎯 Multilingual accessibility** features

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
+
   php -d upload_max_filesize=500M -d post_max_size=550M -d max_execution_time=1800 -S localhost:8000
   ```

   **Basic server (2MB upload limit):**
   ```bash
   php -S localhost:8000
   ```

3. **Initialize the platform** (one-time setup)
   - Go to: `http://localhost:8000/initialize-system.php`
   - Click "Initialize Platform" to set up all tables and directories
   - This creates all necessary components for quizzes, certificates, and subtitles

4. **Start using the platform**
   - Go to: `http://localhost:8000`
   - The platform uses file-based storage by default, with automatic MySQL detection

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

#### Option 1: Automatic Setup (Recommended)
1. Run the initialization script: `http://localhost:8000/initialize-system.php`
2. The system will automatically create all necessary tables and directories

#### Option 2: Manual MySQL Setup
1. Configure your database credentials in the `.env` file
2. Create the database using the provided SQL file:
```bash
mysql -h YOUR_HOST -P YOUR_PORT -u YOUR_USERNAME -p < database.sql
```
3. Or run the migration script: `php migrate-database.php`

#### Option 3: File-Based Storage (Default)
The platform works out-of-the-box with file-based storage - no database server required!

The database includes the following tables:
   - `users` - User accounts (students and instructors)
   - `courses` - Course information
   - `videos` - Video content
   - `enrollments` - Student course enrollments
   - `video_progress` - Video watching progress
   - `quizzes` - Quiz information and settings
   - `quiz_questions` - Quiz questions with different types
   - `quiz_options` - Answer options for quiz questions
   - `quiz_attempts` - Student quiz attempts and scores
   - `quiz_responses` - Student answers to quiz questions
   - `certificates` - Generated certificates for course completion

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
│   ├── quiz.php          # Interactive quiz interface
│   ├── certificate.php   # Certificate display and verification
│   └── status.php        # System status page
├── config/
│   ├── database.php      # Database connection with .env support
│   ├── database_file.php # File-based database fallback
│   ├── env.php           # Environment variable loader
│   ├── session.php       # Session management
│   └── subtitle-processor.php # Subtitle translation and processing engine
├── api/
│   ├── create-course.php # Create new course
│   ├── upload-video.php  # Upload video files
│   ├── enroll.php        # Student enrollment
│   ├── get-courses.php   # Fetch courses API
│   ├── update-progress.php # Update video progress
│   ├── complete-video.php # Mark video complete
│   ├── delete-video.php  # Delete video
│   ├── create-quiz.php   # Create new quiz
│   ├── add-quiz-question.php # Add questions to quiz
│   ├── get-quiz.php      # Fetch quiz data
│   ├── submit-quiz.php   # Submit quiz answers
│   ├── generate-certificate.php # Generate course certificate
│   └── get-certificates.php # Fetch certificates
├── assets/
│   ├── css/
│   │   └── style.css     # Main stylesheet
│   └── js/
│       └── main.js       # JavaScript functionality
├── uploads/
│   ├── videos/           # Uploaded video files
│   ├── subtitles/        # Original and translated subtitle files
│   └── merged_videos/    # Videos with embedded subtitles (if using FFmpeg)
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
4. **Upload subtitles** along with videos for automatic Igbo translation
5. **Create quizzes** from the "Quizzes" tab with multiple question types
6. **Add questions** to your quizzes with different formats
7. **Manage subtitles** via the subtitle management interface
8. Monitor student progress and **certificate issuance** from the "Certificates" tab
9. Track subtitle translation and video merging progress

### For Students
1. After login, you'll be redirected to the student dashboard
2. Browse available courses from the "Browse Courses" tab
3. Enroll in courses that interest you
4. Watch videos and track your progress
5. **Enjoy videos with Igbo subtitles** for better understanding
6. **Toggle between English and Igbo subtitles** when available
7. **Take quizzes** from the course page or Certificates tab
8. **Generate certificates** after completing courses and passing quizzes
9. View and download your earned certificates

### Video Features
- **Streaming**: Videos play directly in the browser
- **Download**: Students can download videos for offline viewing
- **Progress Tracking**: System tracks watch time and completion
- **Resume Playback**: Students can resume from where they left off

### Quiz & Certificate Features
- **Multiple Question Types**: Support for multiple choice, true/false, and short answer questions
- **Timed Quizzes**: Configurable time limits with real-time countdown timer
- **Attempt Limits**: Set maximum number of quiz attempts per student
- **Automatic Grading**: Instant scoring for objective question types
- **Passing Requirements**: Configurable minimum passing scores
- **Certificate Generation**: Automatic certificate creation after course completion
- **Verification**: Unique certificate numbers for verification
- **Progress Tracking**: Monitor quiz attempts and scores
- **Certificate Management**: View, print, and download certificates
- **Instructor Analytics**: Track student performance and certificate issuance

### 🌍 Subtitle & Translation Features
- **Multi-format Support**: Upload SRT, VTT, ASS, SSA subtitle files (10MB max)
- **Built-in Translation Engine**: Dictionary-based English-to-Igbo translation (no external API)
- **100+ Word Dictionary**: Comprehensive coverage of education and technical terms
- **Smart Processing**: Automatic SRT to VTT conversion for HTML5 compatibility
- **Video Integration**: Optional subtitle toggle for students and instructors
- **Dual Language Support**: Toggle Igbo subtitles on/off during video playback
- **Word-by-Word Translation**: Preserves punctuation and formatting
- **User Preference Memory**: Remembers subtitle preference per video
- **HTML5 Compatible**: Uses WebVTT format for universal browser support
- **Inline Subtitle Display**: Subtitles overlay directly on video player
- **Automatic Processing**: Translation and conversion happen during upload
- **File Organization**: Structured storage for original (SRT) and translated (VTT) files
- **Zero Cost Translation**: No API fees or subscriptions required
- **Offline Capability**: Works completely without internet for translation

## Technical Details

### Authentication
- Secure password hashing using PHP's `password_hash()`
- Session-based authentication
- Role-based access control (Student/Instructor)

### Video Upload
- Supports MP4, AVI, MOV, WMV, MKV, WebM, FLV formats
- File size limit: 500MB (configurable)
- **Real-time upload progress bar** with percentage and size display
- **Client-side validation** before upload starts
- Automatic file validation and security checks
- Unique filename generation to prevent conflicts
- **Error handling** with user-friendly messages

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

## Subtitle Translation System

### Translation Method: Dictionary-Based (No External API Required)

The platform uses a **built-in dictionary-based translation system** that operates completely offline without requiring any external API calls or internet connectivity for translation. This ensures:
- ✅ **Zero API costs** - No subscription fees or usage limits
- ✅ **Fast processing** - Instant translation without network latency
- ✅ **Privacy** - No data sent to external services
- ✅ **Reliability** - No dependency on third-party service availability
- ✅ **Offline capability** - Works without internet connection

### How It Works
The platform includes an automatic English-to-Igbo subtitle translation system:

1. **Upload**: Instructor uploads a video with an English subtitle file (.srt, .vtt, .ass, .ssa)
2. **Parse**: System parses the subtitle file and extracts text segments
3. **Translate**: Each text segment is translated using a comprehensive **built-in English-Igbo dictionary** (100+ words)
4. **Convert**: Translated subtitles are saved as both SRT and VTT formats
5. **Display**: Students can toggle Igbo subtitles on/off while watching videos

### Built-In Translation Dictionary
The system includes a **native PHP-based dictionary** with translations for:
- **Common words**: the, a, an, and, or, but, in, on, at, to, for, of, with, by, from
- **Education terms**: learning, course, student, instructor, teacher, lesson, study, knowledge, skill
- **Technical terms**: platform, system, design, develop, create, build, modern, scalable, tools
- **Action words**: click, select, save, delete, edit, update, submit, search, view, watch
- **Nigerian context**: Nigeria, Igbo-specific terms, cultural context
- **Verbs & pronouns**: is, was, are, were, be, have, do, will, can, should, I, you, he, she, it

**Total: 100+ word mappings with punctuation preservation**

### Translation Engine
- **Type**: Dictionary-based lookup system
- **Implementation**: Native PHP (no external libraries)
- **Algorithm**: Word-by-word translation with context preservation
- **Format preservation**: Maintains original punctuation and line breaks
- **Case handling**: Preserves original word casing patterns
- **API Required**: None - completely self-contained

### Extending the Dictionary
To add more translations, edit `config/subtitle-processor.php` and add entries to the `$dictionary` array:

```php
'english_word' => 'igbo_translation',
```

Example:
```php
$dictionary = [
    'welcome' => 'nnọọ',
    'goodbye' => 'ka ọ dị',
    'water' => 'mmiri',
    // Add your custom translations here
];
```

### Optional: Upgrading to Google Translate API
While the platform works perfectly with the built-in dictionary, you can optionally integrate **Google Cloud Translation API** for comprehensive translation:

**Setup Steps:**
1. Get API key from [Google Cloud Console](https://console.cloud.google.com)
2. Install Google Translate PHP client: `composer require google/cloud-translate`
3. Modify the `translateText()` method in `config/subtitle-processor.php`:

```php
use Google\Cloud\Translate\V2\TranslateClient;

public function translateText($text, $from_lang = 'en', $to_lang = 'ig') {
    $translate = new TranslateClient([
        'key' => 'YOUR_API_KEY'
    ]);

    $result = $translate->translate($text, [
        'target' => $to_lang,
        'source' => $from_lang
    ]);

    return $result['text'];
}
```

**Note**: Google Translate API costs approximately $20 per million characters translated.

## Video Upload Progress Bar

The platform features a modern upload interface with:
- **Real-time progress tracking**: Shows upload percentage and transferred data
- **Client-side validation**: Checks file size and type before upload
- **Visual feedback**: Progress bar with color indicators
- **Error handling**: Clear error messages for upload failures
- **Large file support**: Handles videos up to 500MB

## Support
This is a functional e-learning platform ready for immediate use. The code is well-structured and documented for easy maintenance and extension.

### Key Features Summary
✅ User authentication and role management
✅ Course creation and management
✅ Video upload with progress tracking (up to 500MB)
✅ **Built-in subtitle translation** (English → Igbo, no API required)
✅ Dictionary-based translation engine (100+ words)
✅ Interactive quizzes with multiple question types
✅ Certificate generation and verification
✅ Student progress tracking
✅ Responsive design for all devices
✅ File-based or MySQL database support
✅ Zero external API dependencies for core features
✅ Completely offline-capable translation system