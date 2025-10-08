<?php
require_once '../config/database.php';
require_once '../config/session.php';

$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$video_id) {
    header('Location: student-dashboard.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get video details
$stmt = $conn->prepare("
    SELECT v.*, c.title as course_title, c.id as course_id, u.full_name as instructor_name
    FROM videos v
    JOIN courses c ON v.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE v.id = ?
");
$stmt->execute([$video_id]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    header('Location: student-dashboard.php');
    exit();
}

// Get subtitle information
$subtitle = null;
try {
    if ($conn === $database) {
        // File-based database
        $subtitle = $database->selectOne('subtitles', ['video_id' => $video_id]);
    } else {
        // MySQL database
        $stmt = $conn->prepare("SELECT * FROM subtitles WHERE video_id = ? AND translation_status = 'completed' LIMIT 1");
        $stmt->execute([$video_id]);
        $subtitle = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    // Subtitles table might not exist
    error_log('Subtitle fetch error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - Watch</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .video-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }
        .video-wrapper {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            margin-bottom: 2rem;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
        }
        .video-wrapper video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .video-info {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .video-info h1 {
            margin: 0 0 1rem 0;
        }
        .meta-info {
            color: #666;
            margin-bottom: 1rem;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-download {
            background: #28a745;
            color: white;
        }
        .btn-back {
            background: #6c757d;
            color: white;
        }
        .subtitle-controls {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .subtitle-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 24px;
            background: #ccc;
            border-radius: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .toggle-switch.active {
            background: #28a745;
        }
        .toggle-slider {
            position: absolute;
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: left 0.3s;
        }
        .toggle-switch.active .toggle-slider {
            left: 28px;
        }
        .subtitle-badge {
            background: #28a745;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="video-container">
        <div class="video-wrapper">
            <video id="videoPlayer" controls controlsList="nodownload" crossorigin="anonymous">
                <source src="../<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
                <?php if ($subtitle && !empty($subtitle['translated_file_path'])): ?>
                    <track id="igboSubtitle" kind="subtitles" src="../<?php echo htmlspecialchars($subtitle['translated_file_path']); ?>" srclang="ig" label="Igbo">
                <?php endif; ?>
                Your browser does not support the video tag.
            </video>
        </div>

        <div class="video-info">
            <h1><?php echo htmlspecialchars($video['title']); ?></h1>
            <div class="meta-info">
                <p>Course: <?php echo htmlspecialchars($video['course_title']); ?></p>
                <p>Instructor: <?php echo htmlspecialchars($video['instructor_name']); ?></p>
            </div>

            <?php if ($subtitle && !empty($subtitle['translated_file_path'])): ?>
            <div class="subtitle-controls">
                <div class="subtitle-toggle">
                    <span>📝 Igbo Subtitles</span>
                    <div id="subtitleToggle" class="toggle-switch active">
                        <div class="toggle-slider"></div>
                    </div>
                    <span id="subtitleStatus" style="font-size: 0.9rem; color: #28a745;">On</span>
                </div>
                <span class="subtitle-badge">Available</span>
            </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <a href="../api/download-video.php?id=<?php echo $video['id']; ?>" class="btn btn-download">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 004.561 21h14.878a2 2 0 001.94-1.515L22 17"/>
                    </svg>
                    Download Video
                </a>
                <a href="course-videos.php?id=<?php echo $video['course_id']; ?>" class="btn btn-back">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/>
                    </svg>
                    Back to Course
                </a>
            </div>
            
            <div class="description">
                <h3>Description</h3>
                <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
            </div>
        </div>
    </div>

    <script>
        // Save video progress
        const video = document.getElementById('videoPlayer');
        const videoId = <?php echo $video_id; ?>;

        // Load saved progress
        video.addEventListener('loadedmetadata', function() {
            const savedTime = localStorage.getItem(`video_progress_${videoId}`);
            if (savedTime) {
                video.currentTime = parseFloat(savedTime);
            }
        });

        // Save progress periodically
        video.addEventListener('timeupdate', function() {
            localStorage.setItem(`video_progress_${videoId}`, video.currentTime);
        });

        <?php if ($subtitle && !empty($subtitle['translated_file_path'])): ?>
        // Subtitle toggle functionality
        const subtitleToggle = document.getElementById('subtitleToggle');
        const subtitleStatus = document.getElementById('subtitleStatus');
        const subtitleTrack = document.getElementById('igboSubtitle');

        // Load subtitle preference
        const subtitlePref = localStorage.getItem(`subtitle_enabled_${videoId}`);
        let subtitlesEnabled = subtitlePref === null ? true : subtitlePref === 'true';

        // Apply initial state
        updateSubtitleState(subtitlesEnabled);

        subtitleToggle.addEventListener('click', function() {
            subtitlesEnabled = !subtitlesEnabled;
            updateSubtitleState(subtitlesEnabled);
            localStorage.setItem(`subtitle_enabled_${videoId}`, subtitlesEnabled);
        });

        function updateSubtitleState(enabled) {
            if (enabled) {
                subtitleToggle.classList.add('active');
                subtitleStatus.textContent = 'On';
                subtitleStatus.style.color = '#28a745';
                if (subtitleTrack) {
                    subtitleTrack.track.mode = 'showing';
                }
            } else {
                subtitleToggle.classList.remove('active');
                subtitleStatus.textContent = 'Off';
                subtitleStatus.style.color = '#6c757d';
                if (subtitleTrack) {
                    subtitleTrack.track.mode = 'hidden';
                }
            }
        }

        // Ensure subtitle track is available
        video.addEventListener('loadedmetadata', function() {
            if (subtitleTrack && subtitleTrack.track) {
                updateSubtitleState(subtitlesEnabled);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>