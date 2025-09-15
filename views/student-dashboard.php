<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireStudent();

$database = new Database();
$conn = $database->getConnection();

// Get enrolled courses
$enrollments = $conn->select('enrollments', ['student_id' => $_SESSION['user_id']], 'enrolled_at DESC');
$enrolled_courses = [];

foreach ($enrollments as $enrollment) {
    // Get course info
    $course = $conn->selectOne('courses', ['id' => $enrollment['course_id']]);
    if ($course) {
        // Get instructor info
        $instructor = $conn->selectOne('users', ['id' => $course['instructor_id']]);
        $course['instructor_name'] = $instructor ? $instructor['full_name'] : 'Unknown Instructor';

        // Get video count
        $course['video_count'] = $conn->count('videos', ['course_id' => $course['id']]);

        // Add enrollment info
        $course['progress'] = $enrollment['progress'] ?? 0;
        $course['enrolled_at'] = $enrollment['enrolled_at'];

        $enrolled_courses[] = $course;
    }
}

// Get available courses (not enrolled)
$all_courses = $conn->select('courses', [], 'created_at DESC');
$enrolled_course_ids = array_column($enrollments, 'course_id');
$available_courses = [];

$count = 0;
foreach ($all_courses as $course) {
    if (!in_array($course['id'], $enrolled_course_ids) && $count < 6) {
        // Get instructor info
        $instructor = $conn->selectOne('users', ['id' => $course['instructor_id']]);
        $course['instructor_name'] = $instructor ? $instructor['full_name'] : 'Unknown Instructor';

        // Get video count
        $course['video_count'] = $conn->count('videos', ['course_id' => $course['id']]);

        $available_courses[] = $course;
        $count++;
    }
}

// Get recent video progress
$video_progress_records = $conn->select('video_progress', ['student_id' => $_SESSION['user_id']], 'watched_at DESC');
$recent_videos = [];

$count = 0;
foreach ($video_progress_records as $progress) {
    if ($count >= 5) break;

    $video = $conn->selectOne('videos', ['id' => $progress['video_id']]);
    if ($video) {
        $course = $conn->selectOne('courses', ['id' => $video['course_id']]);
        if ($course) {
            $video['course_title'] = $course['title'];
            $video['watched_duration'] = $progress['watched_duration'];
            $video['completed'] = $progress['completed'];
            $recent_videos[] = $video;
            $count++;
        }
    }
}

$active_tab = $_GET['tab'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="logout.php" class="btn-secondary">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1>Student Dashboard</h1>
                <p>Continue your learning journey</p>
            </div>
        </div>

        <div class="container">
            <div class="dashboard-nav">
                <a href="?tab=overview" class="<?php echo $active_tab === 'overview' ? 'active' : ''; ?>">Overview</a>
                <a href="?tab=my-courses" class="<?php echo $active_tab === 'my-courses' ? 'active' : ''; ?>">My Courses</a>
                <a href="?tab=browse" class="<?php echo $active_tab === 'browse' ? 'active' : ''; ?>">Browse Courses</a>
            </div>

            <div class="dashboard-content">
                <?php if ($active_tab === 'overview'): ?>
                    <h2>Learning Overview</h2>
                    <div class="stats" style="display: flex; gap: 2rem; margin-bottom: 2rem;">
                        <div class="stat">
                            <h3><?php echo count($enrolled_courses); ?></h3>
                            <p>Enrolled Courses</p>
                        </div>
                        <div class="stat">
                            <h3><?php echo count($recent_videos); ?></h3>
                            <p>Videos Watched</p>
                        </div>
                        <div class="stat">
                            <h3><?php echo round(array_sum(array_column($enrolled_courses, 'progress')) / max(count($enrolled_courses), 1), 1); ?>%</h3>
                            <p>Average Progress</p>
                        </div>
                    </div>

                    <?php if ($enrolled_courses): ?>
                        <h3>Continue Learning</h3>
                        <div class="courses-grid">
                            <?php foreach (array_slice($enrolled_courses, 0, 3) as $course): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">📚 Course</div>
                                    <div class="course-content">
                                        <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <p>By <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                        <div class="progress-bar" style="background: #f0f0f0; border-radius: 10px; height: 8px; margin: 10px 0;">
                                            <div style="width: <?php echo $course['progress']; ?>%; height: 100%; background: #4a90e2; border-radius: 10px;"></div>
                                        </div>
                                        <div class="course-meta">
                                            <span><?php echo round($course['progress'], 1); ?>% complete</span>
                                            <span><?php echo $course['video_count']; ?> videos</span>
                                        </div>
                                        <a href="course.php?id=<?php echo $course['id']; ?>" class="btn-primary" style="margin-top: 1rem;">Continue</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
                            You haven't enrolled in any courses yet. <a href="?tab=browse">Browse available courses</a> to get started!
                        </div>
                    <?php endif; ?>

                    <?php if ($recent_videos): ?>
                        <h3>Recent Videos</h3>
                        <div class="courses-grid">
                            <?php foreach ($recent_videos as $video): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">
                                        <?php if ($video['completed']): ?>
                                            ✅
                                        <?php else: ?>
                                            📹
                                        <?php endif; ?>
                                    </div>
                                    <div class="course-content">
                                        <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                        <p>Course: <?php echo htmlspecialchars($video['course_title']); ?></p>
                                        <?php if ($video['watched_duration'] > 0): ?>
                                            <small>Watched: <?php echo gmdate("H:i:s", $video['watched_duration']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($active_tab === 'my-courses'): ?>
                    <h2>My Courses</h2>
                    
                    <?php if ($enrolled_courses): ?>
                        <div class="courses-grid">
                            <?php foreach ($enrolled_courses as $course): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">📚 Course</div>
                                    <div class="course-content">
                                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <p><?php echo htmlspecialchars($course['description'] ?? 'No description'); ?></p>
                                        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                        
                                        <div class="progress-bar" style="background: #f0f0f0; border-radius: 10px; height: 10px; margin: 15px 0;">
                                            <div style="width: <?php echo $course['progress']; ?>%; height: 100%; background: #4a90e2; border-radius: 10px;"></div>
                                        </div>
                                        
                                        <div class="course-meta">
                                            <span><?php echo round($course['progress'], 1); ?>% complete</span>
                                            <span><?php echo $course['video_count']; ?> videos</span>
                                        </div>
                                        
                                        <div style="margin-top: 1rem;">
                                            <a href="course.php?id=<?php echo $course['id']; ?>" class="btn-primary">View Course</a>
                                        </div>
                                        
                                        <small style="color: #888; margin-top: 10px; display: block;">
                                            Enrolled: <?php echo date('M j, Y', strtotime($course['enrolled_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
                            You haven't enrolled in any courses yet. <a href="?tab=browse">Browse available courses</a> to get started!
                        </div>
                    <?php endif; ?>

                <?php elseif ($active_tab === 'browse'): ?>
                    <h2>Browse Available Courses</h2>
                    
                    <?php if ($available_courses): ?>
                        <div class="courses-grid">
                            <?php foreach ($available_courses as $course): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">📚 Course</div>
                                    <div class="course-content">
                                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <p><?php echo htmlspecialchars($course['description'] ?? 'No description'); ?></p>
                                        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                        
                                        <div class="course-meta">
                                            <span><?php echo $course['video_count']; ?> videos</span>
                                            <?php if ($course['category']): ?>
                                                <span><?php echo htmlspecialchars($course['category']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="margin-top: 1rem;">
                                            <a href="api/enroll.php?course_id=<?php echo $course['id']; ?>" 
                                               class="btn-primary" 
                                               onclick="return confirm('Are you sure you want to enroll in this course?')">
                                                Enroll Now
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No new courses available at the moment. Check back later for new content!</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>