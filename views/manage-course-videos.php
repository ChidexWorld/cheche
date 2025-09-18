<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireInstructor();

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$course_id) {
    header('Location: instructor-dashboard.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Verify course belongs to instructor
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: instructor-dashboard.php');
    exit();
}

// Get course videos
$stmt = $conn->prepare("SELECT * FROM videos WHERE course_id = ? ORDER BY order_number ASC");
$stmt->execute([$course_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .course-videos {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }
        .video-item {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .video-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        .btn-preview {
            background: #007bff;
            color: white;
        }
        .btn-download {
            background: #28a745;
            color: white;
        }
        .btn-edit {
            background: #ffc107;
            color: #000;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        .video-stats {
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }
        .add-video-btn {
            background: #007bff;
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            display: inline-block;
        }
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="course-videos">
            <div class="course-header">
                <div>
                    <h1><?php echo htmlspecialchars($course['title']); ?> - Videos</h1>
                    <p>Manage your course videos</p>
                </div>
                <a href="instructor-dashboard.php" class="btn">Back to Dashboard</a>
            </div>

            <a href="upload-video.php?course_id=<?php echo $course_id; ?>" class="add-video-btn">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                    <path d="M12 4v16m8-8H4"/>
                </svg>
                Add New Video
            </a>

            <?php if (empty($videos)): ?>
                <p>No videos have been uploaded to this course yet.</p>
            <?php else: ?>
                <?php foreach ($videos as $video): ?>
                    <div class="video-item">
                        <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                        <div class="video-stats">
                            <span>Duration: <?php echo isset($video['duration']) ? gmdate("H:i:s", $video['duration']) : 'N/A'; ?></span>
                            <span> • Order: <?php echo htmlspecialchars($video['order_number']); ?></span>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                        <div class="video-actions">
                            <a href="watch-video.php?id=<?php echo $video['id']; ?>" class="btn btn-preview">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 3l14 9-14 9V3z"/>
                                </svg>
                                Preview Video
                            </a>
                            <a href="../api/download-video.php?id=<?php echo $video['id']; ?>" class="btn btn-download">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 004.561 21h14.878a2 2 0 001.94-1.515L22 17"/>
                                </svg>
                                Download
                            </a>
                            <a href="edit-video.php?id=<?php echo $video['id']; ?>" class="btn btn-edit">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                </svg>
                                Edit
                            </a>
                            <button onclick="deleteVideo(<?php echo $video['id']; ?>)" class="btn btn-delete">
                                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function deleteVideo(videoId) {
        if (confirm('Are you sure you want to delete this video? This action cannot be undone.')) {
            window.location.href = '../api/delete-video.php?id=' + videoId;
        }
    }
    </script>
</body>
</html>