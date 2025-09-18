<?php
require_once "../config/database.php";
require_once "../config/session.php";

requireInstructor();

$database = new Database();
$db = $database->getConnection();

// Initialize variables
$success_message = $_SESSION["success_message"] ?? "";
$error_message = $_SESSION["error_message"] ?? "";
$active_tab = $_GET["tab"] ?? "overview";

// Clear session messages
unset($_SESSION["success_message"]);
unset($_SESSION["error_message"]);

// Get instructor info
$stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$instructor = $stmt->fetch(PDO::FETCH_ASSOC);

// Get instructor courses
$stmt = $db->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$stmt->execute([$_SESSION["user_id"]]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent videos
$stmt = $db->prepare("SELECT v.*, c.title as course_title FROM videos v INNER JOIN courses c ON v.course_id = c.id WHERE c.instructor_id = ? ORDER BY v.created_at DESC LIMIT 5");
$stmt->execute([$_SESSION["user_id"]]);
$recent_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .nav-tabs { margin-bottom: 20px; }
        .nav-tabs a { margin-right: 10px; }
        .nav-tabs a.active { font-weight: bold; }
        .course-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .actions { margin-top: 10px; }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cheche</h1>
        <p>Welcome, <?php echo htmlspecialchars($instructor['full_name']); ?></p>
        <a href="../logout.php" class="btn">Logout</a>

        <h2>Instructor Dashboard</h2>
        <p>Manage your courses and track student progress</p>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <nav class="nav-tabs">
            <a href="?tab=overview" <?php echo $active_tab === 'overview' ? 'class="active"' : ''; ?>>Overview</a>
            <a href="?tab=courses" <?php echo $active_tab === 'courses' ? 'class="active"' : ''; ?>>My Courses</a>
            <a href="?tab=upload" <?php echo $active_tab === 'upload' ? 'class="active"' : ''; ?>>Upload Video</a>
            <a href="?tab=create" <?php echo $active_tab === 'create' ? 'class="active"' : ''; ?>>Create Course</a>
        </nav>

        <?php if ($active_tab === 'overview'): ?>
            <section>
                <h3>Recent Videos</h3>
                <?php if (!empty($recent_videos)): ?>
                    <?php foreach ($recent_videos as $video): ?>
                        <div class="course-card">
                            <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                            <p>Course: <?php echo htmlspecialchars($video['course_title']); ?></p>
                            <div class="actions">
                                <a href="watch-video.php?id=<?php echo $video['id']; ?>" class="btn">Watch Video</a>
                                <a href="../api/download-video.php?id=<?php echo $video['id']; ?>" class="btn">Download</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No recent videos.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($active_tab === 'courses'): ?>
            <section>
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                            <div class="actions">
                                <a href="manage-course-videos.php?id=<?php echo $course['id']; ?>" class="btn">Manage Videos</a>
                                <a href="watch-video.php?course_id=<?php echo $course['id']; ?>" class="btn">Watch Videos</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No courses found. <a href="?tab=create">Create your first course</a></p>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <?php if ($active_tab === 'upload'): ?>
            <section>
                <h3>Upload Video</h3>
                <?php include 'upload-video-form.php'; ?>
            </section>
        <?php endif; ?>

        <?php if ($active_tab === 'create'): ?>
            <section>
                <h3>Create New Course</h3>
                <?php include 'create-course-form.php'; ?>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>