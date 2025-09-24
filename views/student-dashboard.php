<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireStudent();

$database = new Database();
$conn = $database->getConnection();

// Get enrolled courses
$stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? ORDER BY enrolled_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$enrolled_courses = [];

foreach ($enrollments as $enrollment) {
    // Get course info
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$enrollment['course_id']]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($course) {
        // Get instructor info
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$course['instructor_id']]);
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['instructor_name'] = $instructor ? $instructor['full_name'] : 'Unknown Instructor';

        // Get video count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM videos WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['video_count'] = (int)$result['count'];

        // Add enrollment info
        $course['progress'] = $enrollment['progress'] ?? 0;
        $course['enrolled_at'] = $enrollment['enrolled_at'];

        $enrolled_courses[] = $course;
    }
}

// Get available courses (not enrolled)
$stmt = $conn->prepare("SELECT * FROM courses ORDER BY created_at DESC");
$stmt->execute();
$all_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$enrolled_course_ids = array_column($enrollments, 'course_id');
$available_courses = [];

$count = 0;
foreach ($all_courses as $course) {
    if (!in_array($course['id'], $enrolled_course_ids) && $count < 6) {
        // Get instructor info
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$course['instructor_id']]);
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['instructor_name'] = $instructor ? $instructor['full_name'] : 'Unknown Instructor';

        // Get video count
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM videos WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['video_count'] = (int)$result['count'];

        $available_courses[] = $course;
        $count++;
    }
}

// Get recent video progress
$stmt = $conn->prepare("SELECT * FROM video_progress WHERE student_id = ? ORDER BY watched_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$video_progress_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
$recent_videos = [];

foreach ($video_progress_records as $progress) {
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$progress['video_id']]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($video) {
        $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
        $stmt->execute([$video['course_id']]);
        $course = $stmt->fetch(PDO::FETCH_ASSOC);
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
                <span><span data-translate>Welcome</span>, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Student'); ?></span>
                <a href="logout.php" class="btn-secondary" data-translate>Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard">
        <div class="dashboard-header">
            <div class="container">
                <h1 data-translate>Student Dashboard</h1>
                <p data-translate>Continue your learning journey</p>
            </div>
        </div>

        <div class="container">
            <div class="dashboard-nav">
                <a href="?tab=overview" class="<?php echo $active_tab === 'overview' ? 'active' : ''; ?>" data-translate>Overview</a>
                <a href="?tab=my-courses" class="<?php echo $active_tab === 'my-courses' ? 'active' : ''; ?>" data-translate>My Courses</a>
                <a href="?tab=browse" class="<?php echo $active_tab === 'browse' ? 'active' : ''; ?>" data-translate>Browse Courses</a>
            </div>

            <div class="dashboard-content">
                <?php if ($active_tab === 'overview'): ?>
                    <h2 data-translate>Learning Overview</h2>
                    <div class="stats" style="display: flex; gap: 2rem; margin-bottom: 2rem;">
                        <div class="stat">
                            <h3><?php echo count($enrolled_courses); ?></h3>
                            <p data-translate>Enrolled Courses</p>
                        </div>
                        <div class="stat">
                            <h3><?php echo count($recent_videos); ?></h3>
                            <p data-translate>Videos Watched</p>
                        </div>
                        <div class="stat">
                            <h3><?php echo round(array_sum(array_column($enrolled_courses, 'progress')) / max(count($enrolled_courses), 1), 1); ?>%</h3>
                            <p data-translate>Average Progress</p>
                        </div>
                    </div>

                    <?php if ($enrolled_courses): ?>
                        <h3 data-translate>Continue Learning</h3>
                        <div class="courses-grid">
                            <?php foreach (array_slice($enrolled_courses, 0, 3) as $course): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">📚 Course</div>
                                    <div class="course-content">
                                        <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <p><span data-translate>By</span> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                        <div class="progress-bar" style="background: #f0f0f0; border-radius: 10px; height: 8px; margin: 10px 0;">
                                            <div style="width: <?php echo $course['progress']; ?>%; height: 100%; background: #4a90e2; border-radius: 10px;"></div>
                                        </div>
                                        <div class="course-meta">
                                            <span><?php echo round($course['progress'], 1); ?>% <span data-translate>complete</span></span>
                                            <span><?php echo $course['video_count']; ?> <span data-translate>videos</span></span>
                                        </div>
                                        <div style="margin-top: 1rem; display: flex; gap: 10px;">
                                            <a href="course.php?id=<?php echo $course['id']; ?>" class="btn-primary" data-translate>Continue</a>
                                            <?php if ($course['video_count'] > 0): ?>
                                                <a href="course-videos.php?id=<?php echo $course['id']; ?>" class="btn-primary" style="background: #28a745;">📹 <span data-translate>Videos</span></a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
                            <span data-translate>You haven't enrolled in any courses yet.</span> <a href="?tab=browse" data-translate>Browse available courses</a> <span data-translate>to get started!</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($recent_videos): ?>
                        <h3 data-translate>Recent Videos</h3>
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
                                        <p><span data-translate>Course:</span> <?php echo htmlspecialchars($video['course_title']); ?></p>
                                        <?php if ($video['watched_duration'] > 0): ?>
                                            <small><span data-translate>Watched:</span> <?php echo gmdate("H:i:s", $video['watched_duration']); ?></small>
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
                                            <span><?php echo round($course['progress'], 1); ?>% <span data-translate>complete</span></span>
                                            <span><?php echo $course['video_count']; ?> <span data-translate>videos</span></span>
                                        </div>
                                        
                                        <div style="margin-top: 1rem;">
                                            <a href="course.php?id=<?php echo $course['id']; ?>" class="btn-primary">View Course</a>
                                            <?php if ($course['video_count'] > 0): ?>
                                                <a href="course-videos.php?id=<?php echo $course['id']; ?>" class="btn-primary" style="margin-left: 10px; background: #28a745;">📹 <span data-translate>Videos</span></a>
                                            <?php endif; ?>
                                            <button onclick="showModal('unenroll-modal-<?php echo $course['id']; ?>')" class="btn-secondary" style="margin-left: 10px;">Leave Course</button>
                                        </div>
                                        
                                        <small style="color: #888; margin-top: 10px; display: block;">
                                            Enrolled: <?php
                                                $enrolled_date = $course['enrolled_at'] ?? $course['created_at'] ?? '';
                                                echo $enrolled_date ? date('M j, Y', strtotime($enrolled_date)) : 'Unknown';
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb;">
                            <span data-translate>You haven't enrolled in any courses yet.</span> <a href="?tab=browse" data-translate>Browse available courses</a> <span data-translate>to get started!</span>
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
                                            <span><?php echo $course['video_count']; ?> <span data-translate>videos</span></span>
                                            <?php if ($course['category']): ?>
                                                <span><?php echo htmlspecialchars($course['category']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="margin-top: 1rem;">
                                            <button onclick="showModal('enroll-modal-<?php echo $course['id']; ?>')" class="btn-primary">
                                                Enroll Now
                                            </button>
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

    <!-- Enrollment Modals for Browse Tab -->
    <?php if ($active_tab === 'browse' && !empty($available_courses)): ?>
        <?php foreach ($available_courses as $course): ?>
        <div id="enroll-modal-<?php echo $course['id']; ?>" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Confirm Enrollment</h3>
                    <a href="#" class="modal-close">&times;</a>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to enroll in "<strong><?php echo htmlspecialchars($course['title']); ?></strong>"?</p>
                    <?php if ($course['price'] > 0): ?>
                        <p>Course Price: <strong>$<?php echo number_format($course['price'], 2); ?></strong></p>
                    <?php else: ?>
                        <p>This is a <strong>free</strong> course.</p>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button onclick="handleEnrollment(<?php echo $course['id']; ?>); hideModal('enroll-modal-<?php echo $course['id']; ?>')" class="btn-primary">Yes, Enroll Me</button>
                    <button onclick="hideModal('enroll-modal-<?php echo $course['id']; ?>')" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Unenrollment Modals for My Courses Tab -->
    <?php if ($active_tab === 'my-courses' && !empty($enrolled_courses)): ?>
        <?php foreach ($enrolled_courses as $course): ?>
        <div id="unenroll-modal-<?php echo $course['id']; ?>" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Leave Course</h3>
                    <a href="#" class="modal-close">&times;</a>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to leave "<strong><?php echo htmlspecialchars($course['title']); ?></strong>"?</p>
                    <p><strong>Warning:</strong> This will remove your enrollment and delete your progress (<?php echo round($course['progress'], 1); ?>% complete).</p>
                    <p>You can re-enroll later, but you'll lose your current progress.</p>
                </div>
                <div class="modal-footer">
                    <button onclick="handleUnenrollment(<?php echo $course['id']; ?>); hideModal('unenroll-modal-<?php echo $course['id']; ?>')" class="btn-danger">Yes, Leave Course</button>
                    <button onclick="hideModal('unenroll-modal-<?php echo $course['id']; ?>')" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>


    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/modal.js"></script>
    <script src="../assets/js/language.js"></script>
</body>
</html>