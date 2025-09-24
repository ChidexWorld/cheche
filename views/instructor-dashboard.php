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
try {
    $stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$_SESSION["user_id"]]);
    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to file-based database for instructor info
    $instructor = $db->selectOne('users', ['id' => $_SESSION["user_id"]]);
}

// Get instructor courses
try {
    $stmt = $db->prepare("SELECT * FROM courses WHERE instructor_id = ?");
    $stmt->execute([$_SESSION["user_id"]]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to file-based database for courses
    $courses = $db->select('courses', ['instructor_id' => $_SESSION["user_id"]]);
}

// Get course statistics
$total_courses = count($courses);
$total_students = 0;
$total_videos = 0;

foreach ($courses as $course) {
    // Count students enrolled in each course
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_students += (int)$result['count'];
    } catch (Exception $e) {
        // Fallback to file-based database
        $enrollments = $db->select('enrollments', ['course_id' => $course['id']]);
        $total_students += count($enrollments);
    }

    // Count videos in each course
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM videos WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_videos += (int)$result['count'];
    } catch (Exception $e) {
        // Fallback to file-based database
        $videos = $db->select('videos', ['course_id' => $course['id']]);
        $total_videos += count($videos);
    }
}

// Get recent videos
try {
    $stmt = $db->prepare("SELECT v.*, c.title as course_title FROM videos v INNER JOIN courses c ON v.course_id = c.id WHERE c.instructor_id = ? ORDER BY v.created_at DESC LIMIT 5");
    $stmt->execute([$_SESSION["user_id"]]);
    $recent_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fallback to file-based database
    $recent_videos = [];
    $all_videos = $db->select('videos');
    $instructor_courses = array_column($courses, 'id');

    foreach ($all_videos as $video) {
        if (in_array($video['course_id'], $instructor_courses)) {
            // Get course title
            $course = $db->selectOne('courses', ['id' => $video['course_id']]);
            $video['course_title'] = $course ? $course['title'] : 'Unknown Course';
            $recent_videos[] = $video;
        }
    }

    // Sort by created_at and limit to 5
    usort($recent_videos, function($a, $b) {
        return strtotime($b['created_at'] ?? '') - strtotime($a['created_at'] ?? '');
    });
    $recent_videos = array_slice($recent_videos, 0, 5);
}

// Get courses with student counts and video counts for display
$courses_with_stats = [];
foreach ($courses as $course) {
    // Get student count
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['student_count'] = (int)$result['count'];
    } catch (Exception $e) {
        $enrollments = $db->select('enrollments', ['course_id' => $course['id']]);
        $course['student_count'] = count($enrollments);
    }

    // Get video count
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM videos WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['video_count'] = (int)$result['count'];
    } catch (Exception $e) {
        $videos = $db->select('videos', ['course_id' => $course['id']]);
        $course['video_count'] = count($videos);
    }

    $courses_with_stats[] = $course;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modal.css">
    <link rel="stylesheet" href="../assets/css/language-dropdown.css">
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
                <span><span data-translate>Welcome</span>, <?php echo htmlspecialchars($instructor['full_name'] ?? $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Instructor'); ?></span>
                <a href="../logout.php" class="btn-secondary" data-translate>Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1 data-translate>Instructor Dashboard</h1>
                <p data-translate>Manage your courses and track student progress</p>
            </div>
        </div>

        <div class="container">
            <?php if ($success_message): ?>
                <div class="alert alert-success" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 1rem; margin-bottom: 1rem; border-radius: 5px;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-nav">
                <a href="?tab=overview" class="<?php echo $active_tab === 'overview' ? 'active' : ''; ?>" data-translate>Overview</a>
                <a href="?tab=courses" class="<?php echo $active_tab === 'courses' ? 'active' : ''; ?>" data-translate>My Courses</a>
                <a href="?tab=upload" class="<?php echo $active_tab === 'upload' ? 'active' : ''; ?>" data-translate>Upload Video</a>
                <a href="?tab=create" class="<?php echo $active_tab === 'create' ? 'active' : ''; ?>" data-translate>Create Course</a>
            </div>

            <div class="dashboard-content">

                <?php if ($active_tab === 'overview'): ?>
                    <h2 data-translate>Teaching Overview</h2>
                    <div class="stats" style="display: flex; gap: 2rem; margin-bottom: 2rem;">
                        <div class="stat">
                            <h3><?php echo $total_courses; ?></h3>
                            <p data-translate>Total Courses</p>
                        </div>
                        <div class="stat">
                            <h3><?php echo $total_students; ?></h3>
                            <p data-translate>Total Students</p>
                        </div>
                        <div class="stat">
                            <h3><?php echo $total_videos; ?></h3>
                            <p data-translate>Total Videos</p>
                        </div>
                    </div>

                    <?php if ($courses): ?>
                        <h3>Your Courses</h3>
                        <div class="courses-grid">
                            <?php foreach (array_slice($courses_with_stats, 0, 3) as $course): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">📚 Course</div>
                                    <div class="course-content">
                                        <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <p><?php echo htmlspecialchars(substr($course['description'] ?? 'No description', 0, 100)) . (strlen($course['description'] ?? '') > 100 ? '...' : ''); ?></p>
                                        <div class="course-meta">
                                            <span><?php echo $course['student_count']; ?> students</span>
                                            <span><?php echo $course['video_count']; ?> videos</span>
                                        </div>
                                        <a href="?tab=courses" class="btn-primary" style="margin-top: 1rem;">Manage</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; padding: 1rem; border-radius: 5px;">
                            You haven't created any courses yet. <a href="?tab=create">Create your first course</a> to get started!
                        </div>
                    <?php endif; ?>

                    <?php if ($recent_videos): ?>
                        <h3>Recent Videos</h3>
                        <div class="courses-grid">
                            <?php foreach ($recent_videos as $video): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">🎬</div>
                                    <div class="course-content">
                                        <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                        <p>Course: <?php echo htmlspecialchars($video['course_title']); ?></p>
                                        <div style="margin-top: 1rem;">
                                            <a href="course.php?id=<?php echo $video['course_id']; ?>" class="btn-primary">View Course</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($active_tab === 'courses'): ?>
                    <h2>My Courses</h2>

                    <?php if ($courses_with_stats): ?>
                        <div class="courses-grid">
                            <?php foreach ($courses_with_stats as $course): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">📚 Course</div>
                                    <div class="course-content">
                                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <p><?php echo htmlspecialchars($course['description'] ?? 'No description'); ?></p>

                                        <div class="course-meta">
                                            <span><?php echo $course['student_count']; ?> students enrolled</span>
                                            <span><?php echo $course['video_count']; ?> videos</span>
                                        </div>

                                        <div style="margin-top: 1rem; display: flex; gap: 10px; flex-wrap: wrap;">
                                            <a href="course.php?id=<?php echo $course['id']; ?>" class="btn-primary">View Course</a>
                                            <?php if ($course['video_count'] > 0): ?>
                                                <a href="course-videos.php?id=<?php echo $course['id']; ?>" class="btn-primary" style="background: #28a745;">📹 Videos</a>
                                            <?php endif; ?>
                                            <a href="?tab=upload&course_id=<?php echo $course['id']; ?>" class="btn-secondary">Add Video</a>
                                        </div>

                                        <small style="color: #888; margin-top: 10px; display: block;">
                                            Created: <?php
                                                $created_date = $course['created_at'] ?? '';
                                                echo $created_date ? date('M j, Y', strtotime($created_date)) : 'Unknown';
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; padding: 1rem; border-radius: 5px;">
                            You haven't created any courses yet. <a href="?tab=create">Create your first course</a> to get started!
                        </div>
                    <?php endif; ?>

                <?php elseif ($active_tab === 'upload'): ?>
                    <h2>Upload Video</h2>
                    <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <?php include 'upload-video-form.php'; ?>
                    </div>

                <?php elseif ($active_tab === 'create'): ?>
                    <h2>Create New Course</h2>
                    <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <?php include 'create-course-form.php'; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/modal.js"></script>
    <script src="../assets/js/language.js"></script>
</body>
</html>