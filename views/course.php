<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$course_id = $_GET['id'] ?? 0;
$database = new Database();
$conn = $database->getConnection();

// Get course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$course) {
    header('Location: student-dashboard.php');
    exit();
}

// Get instructor details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$course['instructor_id']]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);
$course['instructor_name'] = $instructor ? $instructor['full_name'] : 'Unknown Instructor';

// Check if user is enrolled (for students) or owns the course (for instructors)
$can_access = false;
if (isInstructor() && $course['instructor_id'] == $_SESSION['user_id']) {
    $can_access = true;
} elseif (isStudent()) {
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
    $can_access = $enrollment !== null;
}

if (!$can_access) {
    header('Location: student-dashboard.php?tab=browse');
    exit();
}

// Get course videos
$stmt = $conn->prepare("SELECT * FROM videos WHERE course_id = ? ORDER BY order_number ASC");
$stmt->execute([$course_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get video progress (for students)
$video_progress = [];
if (isStudent()) {
    // Get video progress for all videos in this course
    $stmt = $conn->prepare("
        SELECT vp.* 
        FROM video_progress vp
        JOIN videos v ON vp.video_id = v.id
        WHERE vp.student_id = ? AND v.course_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $all_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_progress as $progress) {
        $video_progress[$progress['video_id']] = $progress;
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/language-dropdown.css">
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

        /* Language Dropdown Styles */
        .language-dropdown {
            position: relative;
            display: inline-block;
            margin-right: 1rem;
        }

        .language-toggle {
            background: #4a90e2 !important;
            color: white !important;
            border: none !important;
            padding: 8px 16px !important;
            border-radius: 20px !important;
            cursor: pointer !important;
            font-size: 14px !important;
            transition: all 0.3s ease !important;
            outline: none !important;
        }

        .language-toggle:hover {
            background: #357abd !important;
            transform: translateY(-2px);
        }

        .dropdown-content {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white !important;
            min-width: 120px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.2) !important;
            border-radius: 8px !important;
            z-index: 9999 !important;
            overflow: hidden;
            border: 1px solid #ddd !important;
            margin-top: 5px;
        }

        .dropdown-content a {
            color: #333 !important;
            padding: 12px 16px !important;
            text-decoration: none !important;
            display: block !important;
            transition: background-color 0.3s ease !important;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1 !important;
        }

        .dropdown-content.show {
            display: block !important;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Ensure nav-links has proper flex display */
        .nav-links {
            display: flex !important;
            align-items: center !important;
            gap: 1rem !important;
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
                <div class="language-dropdown">
                    <button class="language-toggle" onclick="toggleDropdown()">
                        🌍 <span id="currentLang">English</span> ▼
                    </button>
                    <div class="dropdown-content" id="languageDropdown">
                        <a href="#" onclick="changeLanguage('en')">English</a>
                        <a href="#" onclick="changeLanguage('ig')">Igbo</a>
                    </div>
                </div>
                <a href="<?php echo isInstructor() ? 'instructor-dashboard.php' : 'student-dashboard.php'; ?>" data-translate>Dashboard</a>
                <span><span data-translate>Welcome</span>, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
                <a href="logout.php" class="btn-secondary" data-translate>Logout</a>
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
                            <p data-translate>Your browser does not support the video tag or this video format.</p>
                            <p><a href="<?php echo htmlspecialchars($current_video['video_path']); ?>" target="_blank" data-translate>Click here to download and watch the video</a></p>
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
                                    ⬇️ <span data-translate>Download Video</span>
                                </a>
                                <?php if (isset($video_progress[$current_video['id']]) && $video_progress[$current_video['id']]['completed']): ?>
                                    <span style="color: #28a745; font-weight: bold;">✅ <span data-translate>Completed</span></span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="video-info">
                        <h2 data-translate>No videos available</h2>
                        <p data-translate>This course doesn't have any videos yet.</p>
                        <?php if (isInstructor()): ?>
                            <a href="instructor-dashboard.php?tab=upload&course_id=<?php echo $course['id']; ?>" class="btn-primary" data-translate>
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
                            + <span data-translate>Add Video</span>
                        </a>
                    <?php endif; ?>
                </div>
                <p style="color: #666; margin-bottom: 1rem;"><span data-translate>By</span> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                
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
                                                <span data-translate>Watched</span>: <?php echo gmdate("i:s", $video_progress[$video['id']]['watched_duration']); ?>
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
                    <p data-translate>No videos in this course yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/language.js"></script>
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
                        <strong data-translate>Video playback error:</strong><br>
                        <span data-translate>This video format may not be supported by your browser.</span><br>
                        <a href="${video.querySelector('source').src}" target="_blank" style="color: #1976d2;" data-translate>Click here to download the video</a>
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