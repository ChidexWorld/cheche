<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Get video ID from URL
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$video_id) {
    header('Location: ../index.php');
    exit();
}

// Get video details
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT v.*, c.title as course_title 
    FROM videos v 
    JOIN courses c ON v.course_id = c.id 
    WHERE v.id = ?
");
$stmt->execute([$video_id]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="video-player-container">
            <h1><?php echo htmlspecialchars($video['title']); ?></h1>
            <p>Course: <?php echo htmlspecialchars($video['course_title']); ?></p>
            
            <div class="video-wrapper">
                <video id="videoPlayer" controls>
                    <source src="../<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
            
            <div class="video-controls">
                <a href="../api/download-video.php?id=<?php echo $video_id; ?>" class="btn-primary">
                    Download Video
                </a>
            </div>
            
            <div class="video-description">
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