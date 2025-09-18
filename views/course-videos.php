<?php
require_once '../config/database.php';
require_once '../config/session.php';

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$course_id) {
    header('Location: student-dashboard.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get course details
$stmt = $conn->prepare("SELECT c.*, u.full_name as instructor_name 
                       FROM courses c 
                       LEFT JOIN users u ON c.instructor_id = u.id 
                       WHERE c.id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: student-dashboard.php');
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
    <title><?php echo htmlspecialchars($course['title']); ?> - Videos</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
        }
        .header {
            background: #fff;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .back-button:hover {
            background: #0056b3;
        }
        .video-list {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 1rem;
        }
        .course-info {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .course-info h1 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        .instructor-info {
            color: #666;
            font-style: italic;
        }
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 2rem;
        }
        .video-item {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            height: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 1px solid #e1e8ed;
        }
        .video-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
            border-color: #007bff;
        }
        .video-item h3 {
            margin: 0 0 1rem 0;
            color: #2c3e50;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .video-info {
            flex-grow: 1;
            margin-bottom: 1rem;
        }
        .video-meta {
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0;
            padding: 0.5rem 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .video-description {
            color: #555;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 1rem 0;
        }
        .video-actions {
            display: flex;
            gap: 1rem;
            margin-top: auto;
        }
        .btn {
            padding: 0.7rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-watch {
            background: #007bff;
            color: white;
        }
        .btn-download {
            background: #28a745;
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        /* Responsive Design */
        @media (max-width: 1200px) {
            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }
        @media (max-width: 768px) {
            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
        }
        @media (max-width: 480px) {
            .video-grid {
                grid-template-columns: 1fr;
            }
            .video-actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                text-align: center;
            }
        }
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .video-item h3 {
            margin: 0 0 1rem 0;
            color: #333;
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
        }
        .btn-watch {
            background: #007bff;
            color: white;
        }
        .btn-download {
            background: #28a745;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        .video-meta {
            color: #666;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="instructor-dashboard.php" class="back-button">
                <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </header>

    <div class="container">
        <div class="video-list">
            <div class="course-info">
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p class="instructor-info">By <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                <?php if (!empty($course['description'])): ?>
                    <div class="course-description">
                        <?php echo nl2br(htmlspecialchars($course['description'])); ?>
                    </div>
                <?php endif; ?>
            
            <?php if (empty($videos)): ?>
                <p>No videos available for this course yet.</p>
            <?php else: ?>
                <div class="video-grid">
                    <?php foreach ($videos as $video): ?>
                        <div class="video-item">
                            <div class="video-info">
                                <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                                <div class="video-meta">
                                    <span>Duration: <?php echo isset($video['duration']) ? gmdate("H:i:s", $video['duration']) : 'N/A'; ?></span>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                            </div>
                            <div class="video-actions">
                                <a href="watch-video.php?id=<?php echo $video['id']; ?>" class="btn btn-watch">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M5 3l14 9-14 9V3z"/>
                                    </svg>
                                    Watch
                                </a>
                                <a href="../api/download-video.php?id=<?php echo $video['id']; ?>" class="btn btn-download">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 004.561 21h14.878a2 2 0 001.94-1.515L22 17"/>
                                    </svg>
                                    Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>