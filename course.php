<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireLogin();

$course_id = $_GET['id'] ?? 0;
$database = new Database();
$conn = $database->getConnection();

// Get course details
$course = $conn->selectOne('courses', ['id' => $course_id]);
if (!$course) {
    header('Location: student-dashboard.php');
    exit();
}

// Get instructor details
$instructor = $conn->selectOne('users', ['id' => $course['instructor_id']]);
$course['instructor_name'] = $instructor ? $instructor['full_name'] : 'Unknown Instructor';

// Check if user is enrolled (for students) or owns the course (for instructors)
$can_access = false;
if (isInstructor() && $course['instructor_id'] == $_SESSION['user_id']) {
    $can_access = true;
} elseif (isStudent()) {
    $enrollment = $conn->selectOne('enrollments', ['student_id' => $_SESSION['user_id'], 'course_id' => $course_id]);
    $can_access = $enrollment !== null;
}

if (!$can_access) {
    header('Location: student-dashboard.php?tab=browse');
    exit();
}

// Get course videos
$videos = $conn->select('videos', ['course_id' => $course_id], 'order_number ASC');

// Get video progress (for students)
$video_progress = [];
if (isStudent()) {
    $all_progress = $conn->select('video_progress', ['student_id' => $_SESSION['user_id']]);
    foreach ($all_progress as $progress) {
        // Check if this progress is for a video in this course
        $video_check = $conn->selectOne('videos', ['id' => $progress['video_id'], 'course_id' => $course_id]);
        if ($video_check) {
            $video_progress[$progress['video_id']] = $progress;
        }
    }
}

$current_video_id = $_GET['video'] ?? ($videos[0]['id'] ?? 0);
$current_video = null;
foreach ($videos as $video) {
    if ($video['id'] == $current_video_id) {
        $current_video = $video;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Cheche</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .course-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 2rem;
            margin-top: 100px;
            padding: 2rem 0;
        }
        .video-section {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .video-info {
            padding: 1.5rem;
        }
        .playlist {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            max-height: 600px;
            overflow-y: auto;
        }
        .video-item {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #eee;
        }
        .video-item:hover {
            background: #f8f9fa;
        }
        .video-item.active {
            background: #4a90e2;
            color: white;
        }
        .video-item.completed {
            border-left: 4px solid #28a745;
        }
        @media (max-width: 768px) {
            .course-layout {
                grid-template-columns: 1fr;
                margin-top: 80px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php" style="text-decoration: none; color: inherit;">
                    <h2>Cheche</h2>
                </a>
            </div>
            <div class="nav-links">
                <a href="<?php echo isInstructor() ? 'instructor-dashboard.php' : 'student-dashboard.php'; ?>">Dashboard</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="course-layout">
            <div class="video-section">
                <?php if ($current_video): ?>
                    <div class="video-player">
                        <video id="mainVideo"
                               controls
                               preload="metadata"
                               width="100%"
                               height="400"
                               data-video-id="<?php echo $current_video['id']; ?>"
                               <?php if (isStudent()): ?>onloadedmetadata="initializeVideoPlayer(this)"<?php endif; ?>>
                            <?php
                            $video_path = $current_video['video_path'];
                            $extension = strtolower(pathinfo($video_path, PATHINFO_EXTENSION));
                            $mime_type = 'video/mp4'; // default

                            switch($extension) {
                                case 'mp4':
                                    $mime_type = 'video/mp4';
                                    break;
                                case 'avi':
                                    $mime_type = 'video/x-msvideo';
                                    break;
                                case 'webm':
                                    $mime_type = 'video/webm';
                                    break;
                                case 'ogg':
                                    $mime_type = 'video/ogg';
                                    break;
                                case 'mov':
                                    $mime_type = 'video/quicktime';
                                    break;
                            }
                            ?>
                            <source src="<?php echo htmlspecialchars($current_video['video_path']); ?>" type="<?php echo $mime_type; ?>">
                            <p>Your browser does not support the video tag or this video format.</p>
                            <p><a href="<?php echo htmlspecialchars($current_video['video_path']); ?>" target="_blank">Click here to download and watch the video</a></p>
                        </video>
                    </div>
                    
                    <div class="video-info">
                        <h2><?php echo htmlspecialchars($current_video['title']); ?></h2>
                        <?php if ($current_video['description']): ?>
                            <p><?php echo htmlspecialchars($current_video['description']); ?></p>
                        <?php endif; ?>
                        
                        <div style="margin-top: 1rem; display: flex; gap: 1rem; align-items: center;">
                            <?php if (isStudent()): ?>
                                <a href="<?php echo htmlspecialchars($current_video['video_path']); ?>" 
                                   download="<?php echo htmlspecialchars($current_video['title']); ?>.mp4" 
                                   class="btn-primary">
                                    ⬇️ Download Video
                                </a>
                                <?php if (isset($video_progress[$current_video['id']]) && $video_progress[$current_video['id']]['completed']): ?>
                                    <span style="color: #28a745; font-weight: bold;">✅ Completed</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="video-info">
                        <h2>No videos available</h2>
                        <p>This course doesn't have any videos yet.</p>
                        <?php if (isInstructor()): ?>
                            <a href="instructor-dashboard.php?tab=upload&course_id=<?php echo $course['id']; ?>" class="btn-primary">
                                Add Videos
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="playlist">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                    <?php if (isInstructor()): ?>
                        <a href="instructor-dashboard.php?tab=upload&course_id=<?php echo $course['id']; ?>" class="btn-primary" style="font-size: 0.8rem; padding: 8px 16px;">
                            + Add Video
                        </a>
                    <?php endif; ?>
                </div>
                <p style="color: #666; margin-bottom: 1rem;">By <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                
                <?php if ($videos): ?>
                    <div class="video-list">
                        <?php foreach ($videos as $index => $video): ?>
                            <div class="video-item <?php echo $video['id'] == $current_video_id ? 'active' : ''; ?> 
                                        <?php echo (isset($video_progress[$video['id']]) && $video_progress[$video['id']]['completed']) ? 'completed' : ''; ?>"
                                 onclick="window.location.href='course.php?id=<?php echo $course['id']; ?>&video=<?php echo $video['id']; ?>'">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-weight: bold; color: #666;"><?php echo $index + 1; ?>.</span>
                                    <div>
                                        <h4 style="margin: 0; font-size: 0.9rem;"><?php echo htmlspecialchars($video['title']); ?></h4>
                                        <?php if (isset($video_progress[$video['id']]) && $video_progress[$video['id']]['watched_duration'] > 0): ?>
                                            <small style="color: #888;">
                                                Watched: <?php echo gmdate("i:s", $video_progress[$video['id']]['watched_duration']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (isset($video_progress[$video['id']]) && $video_progress[$video['id']]['completed']): ?>
                                        <span style="margin-left: auto; color: #28a745;">✅</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No videos in this course yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.getElementById('mainVideo');
            if (video) {
                // Add error handling
                video.addEventListener('error', function(e) {
                    console.error('Video error:', e);
                    const errorDiv = document.createElement('div');
                    errorDiv.style.cssText = 'background: #ffebee; color: #c62828; padding: 1rem; border-radius: 8px; margin: 1rem 0;';
                    errorDiv.innerHTML = `
                        <strong>Video playback error:</strong><br>
                        This video format may not be supported by your browser.<br>
                        <a href="${video.querySelector('source').src}" target="_blank" style="color: #1976d2;">Click here to download the video</a>
                    `;
                    video.parentNode.insertBefore(errorDiv, video.nextSibling);
                });

                // Add loading event
                video.addEventListener('loadstart', function() {
                    console.log('Video loading started');
                });

                video.addEventListener('canplaythrough', function() {
                    console.log('Video can play through');
                });

                initializeVideoPlayer(video);
            }
        });
    </script>
</body>
</html>