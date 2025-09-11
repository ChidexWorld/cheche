<?php
require_once 'config/database.php';
require_once 'config/session.php';

requireInstructor();

$course_id = $_GET['id'] ?? 0;
$database = new Database();
$conn = $database->getConnection();

// Get course details
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch();

if (!$course) {
    header('Location: instructor-dashboard.php');
    exit();
}

// Get course videos
$stmt = $conn->prepare("SELECT * FROM videos WHERE course_id = ? ORDER BY order_number ASC, created_at ASC");
$stmt->execute([$course_id]);
$videos = $stmt->fetchAll();

// Get enrolled students
$stmt = $conn->prepare("
    SELECT u.full_name, u.email, e.enrolled_at, e.progress
    FROM enrollments e 
    JOIN users u ON e.student_id = u.id 
    WHERE e.course_id = ? 
    ORDER BY e.enrolled_at DESC
");
$stmt->execute([$course_id]);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Course - <?php echo htmlspecialchars($course['title']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                <a href="instructor-dashboard.php">Dashboard</a>
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <p>Manage your course content and track student progress</p>
            </div>
        </div>

        <div class="container">
            <div class="dashboard-nav">
                <a href="instructor-dashboard.php">← Back to Dashboard</a>
                <a href="course.php?id=<?php echo $course['id']; ?>">Preview Course</a>
                <a href="instructor-dashboard.php?tab=upload&course_id=<?php echo $course['id']; ?>">Add Video</a>
            </div>

            <div class="dashboard-content">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div class="stat">
                        <h3><?php echo count($videos); ?></h3>
                        <p>Total Videos</p>
                    </div>
                    <div class="stat">
                        <h3><?php echo count($students); ?></h3>
                        <p>Enrolled Students</p>
                    </div>
                </div>

                <h3>Course Videos</h3>
                <?php if ($videos): ?>
                    <div class="courses-grid">
                        <?php foreach ($videos as $video): ?>
                            <div class="course-card">
                                <div class="course-thumbnail">📹</div>
                                <div class="course-content">
                                    <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($video['description'] ?? 'No description'); ?></p>
                                    <div class="course-meta">
                                        <span>Order: <?php echo $video['order_number']; ?></span>
                                        <?php if ($video['duration']): ?>
                                            <span>Duration: <?php echo gmdate("i:s", $video['duration']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="margin-top: 1rem;">
                                        <a href="course.php?id=<?php echo $course['id']; ?>&video=<?php echo $video['id']; ?>" class="btn-primary">View</a>
                                        <a href="api/delete-video.php?id=<?php echo $video['id']; ?>" 
                                           class="btn-secondary" 
                                           onclick="return confirm('Are you sure you want to delete this video?')"
                                           style="margin-left: 0.5rem;">Delete</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>No videos in this course yet. <a href="instructor-dashboard.php?tab=upload&course_id=<?php echo $course['id']; ?>">Add your first video</a></p>
                <?php endif; ?>

                <h3 style="margin-top: 3rem;">Enrolled Students</h3>
                <?php if ($students): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 1rem; border: 1px solid #ddd; text-align: left;">Student Name</th>
                                    <th style="padding: 1rem; border: 1px solid #ddd; text-align: left;">Email</th>
                                    <th style="padding: 1rem; border: 1px solid #ddd; text-align: left;">Progress</th>
                                    <th style="padding: 1rem; border: 1px solid #ddd; text-align: left;">Enrolled Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td style="padding: 1rem; border: 1px solid #ddd;"><?php echo htmlspecialchars($student['full_name']); ?></td>
                                        <td style="padding: 1rem; border: 1px solid #ddd;"><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td style="padding: 1rem; border: 1px solid #ddd;">
                                            <div style="background: #f0f0f0; border-radius: 10px; height: 20px; position: relative;">
                                                <div style="width: <?php echo $student['progress']; ?>%; height: 100%; background: #4a90e2; border-radius: 10px;"></div>
                                                <span style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); line-height: 20px; font-size: 0.8rem; font-weight: bold;">
                                                    <?php echo round($student['progress'], 1); ?>%
                                                </span>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem; border: 1px solid #ddd;"><?php echo date('M j, Y', strtotime($student['enrolled_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No students enrolled in this course yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>