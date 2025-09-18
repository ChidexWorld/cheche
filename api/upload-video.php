<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/env.php';

requireInstructor();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$course_id = intval($_POST['course_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$order_number = intval($_POST['order_number'] ?? 1);

if (empty($title) || empty($course_id)) {
    echo json_encode(['success' => false, 'message' => 'Title and course are required']);
    exit();
}

if (!isset($_FILES['video_file'])) {
    echo json_encode(['success' => false, 'message' => 'Please select a video file']);
    exit();
}

$upload_error = $_FILES['video_file']['error'];
if ($upload_error !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'File is too large (exceeds PHP upload_max_filesize)',
        UPLOAD_ERR_FORM_SIZE => 'File is too large (exceeds HTML form MAX_FILE_SIZE)',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension'
    ];

    $message = isset($error_messages[$upload_error]) ? $error_messages[$upload_error] : 'Unknown upload error';
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verify course belongs to instructor
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$course_id, $_SESSION['user_id']]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        echo json_encode(['success' => false, 'message' => 'Course not found or access denied']);
        exit();
    }
    
    // Handle file upload
    $uploaded_file = $_FILES['video_file'];
    $file_size = $uploaded_file['size'];
    $file_tmp = $uploaded_file['tmp_name'];
    $file_name = $uploaded_file['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Validate file
    $allowed_extensions = Env::getArray('ALLOWED_VIDEO_EXTENSIONS', ['mp4', 'avi', 'mov', 'wmv', 'mkv']);
    if (!in_array($file_ext, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a video file.']);
        exit();
    }

    $max_size = Env::getInt('UPLOAD_MAX_SIZE', 100 * 1024 * 1024); // Default 100MB
    if ($file_size > $max_size) {
        $max_mb = round($max_size / (1024 * 1024));
        echo json_encode(['success' => false, 'message' => "File too large. Maximum size is {$max_mb}MB."]);
        exit();
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../' . Env::get('UPLOAD_DIR', 'uploads/videos/');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $new_filename = 'video_' . time() . '_' . uniqid() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    $relative_path = Env::get('UPLOAD_DIR', 'uploads/videos/') . $new_filename;
    
    if (!move_uploaded_file($file_tmp, $upload_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
        exit();
    }
    
    // Get video duration (if possible)
    $duration = 0;
    $ffprobe_path = Env::get('FFPROBE_PATH', '/usr/bin/ffprobe');
    if (function_exists('shell_exec') && file_exists($ffprobe_path) && $file_ext === 'mp4') {
        $ffmpeg_duration = shell_exec("\"$ffprobe_path\" -v quiet -show_entries format=duration -of csv=\"p=0\" \"$upload_path\"");
        if ($ffmpeg_duration) {
            $duration = intval(floatval(trim($ffmpeg_duration)));
        }
    }
    
    // Save to database
    $stmt = $conn->prepare("
        INSERT INTO videos (course_id, title, description, video_path, duration, order_number, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([
        $course_id,
        $title,
        $description,
        $relative_path,
        $duration,
        $order_number
    ])) {
        $video_id = $conn->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Video uploaded successfully', 'video_id' => $video_id]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Video uploaded successfully', 'video_id' => $video_id]);
    } else {
        // Clean up uploaded file if database insert fails
        unlink($upload_path);
        echo json_encode(['success' => false, 'message' => 'Failed to save video information']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Upload failed: ' . $e->getMessage()]);
}
?>