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
    </style>
</head>
<body>
    <div class="video-container">
        <div class="video-wrapper">
            <video id="videoPlayer" controls controlsList="nodownload">
                <source src="../<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        
        <div class="video-info">
            <h1><?php echo htmlspecialchars($video['title']); ?></h1>
            <div class="meta-info">
                <p>Course: <?php echo htmlspecialchars($video['course_title']); ?></p>
                <p>Instructor: <?php echo htmlspecialchars($video['instructor_name']); ?></p>
            </div>
            
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
    </script>
</body>
</html>