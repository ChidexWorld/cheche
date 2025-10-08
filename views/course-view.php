<?php
require_once '../config/database.php';
require_once '../config/session.php';

$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$course_id) {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get course details
$stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: ../index.php');
    exit();
}

// Get course videos
$stmt = $db->prepare("SELECT * FROM videos WHERE course_id = ? ORDER BY order_number ASC");
$stmt->execute([$course_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/video-player.css">
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($course['title']); ?></h1>
        <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
        
        <div class="video-list">
            <?php foreach ($videos as $video): ?>
            <div class="video-item">
                <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                <p><?php echo nl2br(htmlspecialchars($video['description'])); ?></p>
                <div class="video-actions">
                    <a href="video-player.php?id=<?php echo $video['id']; ?>" class="btn-primary">Watch Video</a>
                    <a href="../api/download-video.php?id=<?php echo $video['id']; ?>" class="btn-secondary">Download</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>