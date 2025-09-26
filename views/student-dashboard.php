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

// Get student's certificates
$stmt = $conn->prepare("
    SELECT c.*, co.title as course_title, co.description as course_description
    FROM certificates c
    JOIN courses co ON c.course_id = co.id
    WHERE c.student_id = ?
    ORDER BY c.issued_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available quizzes for enrolled courses
$available_quizzes = [];
foreach ($enrolled_courses as $course) {
    $stmt = $conn->prepare("SELECT * FROM quizzes WHERE course_id = ? AND is_active = 1");
    $stmt->execute([$course['id']]);
    $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($quizzes as $quiz) {
        // Get student's attempts for this quiz
        $stmt = $conn->prepare("
            SELECT * FROM quiz_attempts
            WHERE student_id = ? AND quiz_id = ?
            ORDER BY attempt_number DESC
        ");
        $stmt->execute([$_SESSION['user_id'], $quiz['id']]);
        $attempts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quiz['course_title'] = $course['title'];
        $quiz['attempts'] = $attempts;
        $quiz['remaining_attempts'] = max(0, $quiz['max_attempts'] - count($attempts));
        $quiz['best_score'] = $attempts ? max(array_column($attempts, 'score')) : 0;
        $quiz['passed'] = $attempts ? max(array_column($attempts, 'passed')) : false;

        $available_quizzes[] = $quiz;
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
                <a href="?tab=certificates" class="<?php echo $active_tab === 'certificates' ? 'active' : ''; ?>" data-translate>🏆 Certificates</a>
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

                <?php elseif ($active_tab === 'certificates'): ?>
                    <h2 data-translate>🏆 My Certificates</h2>

                    <?php if ($certificates): ?>
                        <div class="certificates-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
                            <?php foreach ($certificates as $certificate): ?>
                                <div class="certificate-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px; padding: 2rem; text-align: center; position: relative; overflow: hidden;">
                                    <div style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.2); border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                        🏆
                                    </div>

                                    <h3 style="margin: 0 0 0.5rem 0; font-size: 1.5rem;">Certificate of Completion</h3>
                                    <h4 style="margin: 0 0 1rem 0; opacity: 0.9;"><?php echo htmlspecialchars($certificate['course_title']); ?></h4>

                                    <div style="background: rgba(255,255,255,0.1); border-radius: 8px; padding: 1rem; margin: 1rem 0;">
                                        <p style="margin: 0; font-size: 0.9rem;">Certificate #<?php echo htmlspecialchars($certificate['certificate_number']); ?></p>
                                        <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">Issued: <?php echo date('M d, Y', strtotime($certificate['issued_at'])); ?></p>
                                        <?php if ($certificate['quiz_score']): ?>
                                            <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">Quiz Score: <?php echo number_format($certificate['quiz_score'], 1); ?>%</p>
                                        <?php endif; ?>
                                    </div>

                                    <a href="certificate.php?id=<?php echo $certificate['id']; ?>" class="btn" style="background: white; color: #667eea; margin-top: 1rem; display: inline-block; text-decoration: none; padding: 0.5rem 1rem; border-radius: 5px; font-weight: bold;">
                                        View Certificate
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Available Quizzes and Certificate Generation -->
                    <?php if ($available_quizzes): ?>
                        <h3 data-translate>📝 Available Quizzes</h3>
                        <div class="quizzes-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                            <?php foreach ($available_quizzes as $quiz): ?>
                                <div class="quiz-card" style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                    <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                                        <div style="flex: 1;">
                                            <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                            <p style="margin: 0; color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($quiz['course_title']); ?></p>
                                        </div>
                                        <?php if ($quiz['passed']): ?>
                                            <span style="background: #28a745; color: white; padding: 0.25rem 0.5rem; border-radius: 15px; font-size: 0.8rem;">✅ Passed</span>
                                        <?php elseif ($quiz['remaining_attempts'] == 0): ?>
                                            <span style="background: #dc3545; color: white; padding: 0.25rem 0.5rem; border-radius: 15px; font-size: 0.8rem;">❌ Failed</span>
                                        <?php endif; ?>
                                    </div>

                                    <div style="background: #f8f9fa; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;">
                                            <div><strong>Passing Score:</strong> <?php echo $quiz['passing_score']; ?>%</div>
                                            <div><strong>Time Limit:</strong> <?php echo $quiz['time_limit']; ?> min</div>
                                            <div><strong>Remaining Attempts:</strong> <?php echo $quiz['remaining_attempts']; ?></div>
                                            <?php if ($quiz['best_score'] > 0): ?>
                                                <div><strong>Best Score:</strong> <?php echo number_format($quiz['best_score'], 1); ?>%</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <?php if ($quiz['remaining_attempts'] > 0): ?>
                                            <a href="quiz.php?id=<?php echo $quiz['id']; ?>" class="btn-primary" style="text-decoration: none;">
                                                <?php echo count($quiz['attempts']) > 0 ? 'Retake Quiz' : 'Take Quiz'; ?>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($quiz['passed']): ?>
                                            <button onclick="generateCertificate(<?php echo $quiz['course_id']; ?>)" class="btn-success" style="background: #28a745;">
                                                🏆 Get Certificate
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Courses Ready for Certificate (completed without quiz) -->
                    <?php
                    $certificate_ready_courses = [];
                    foreach ($enrolled_courses as $course) {
                        if ($course['progress'] >= 80) { // 80% completion required
                            // Check if course has quiz
                            $has_quiz = false;
                            foreach ($available_quizzes as $quiz) {
                                if ($quiz['course_id'] == $course['id']) {
                                    $has_quiz = true;
                                    break;
                                }
                            }

                            // Check if already has certificate
                            $has_certificate = false;
                            foreach ($certificates as $cert) {
                                if ($cert['course_id'] == $course['id']) {
                                    $has_certificate = true;
                                    break;
                                }
                            }

                            if (!$has_quiz && !$has_certificate) {
                                $certificate_ready_courses[] = $course;
                            }
                        }
                    }
                    ?>

                    <?php if ($certificate_ready_courses): ?>
                        <h3 data-translate>🎓 Ready for Certificate</h3>
                        <div class="ready-certificates-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                            <?php foreach ($certificate_ready_courses as $course): ?>
                                <div class="course-card" style="background: white; border: 2px solid #28a745; border-radius: 10px; padding: 1.5rem;">
                                    <h4 style="margin: 0 0 1rem 0; color: #28a745;"><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <p style="margin: 0 0 1rem 0; color: #666;">Course completion: <?php echo number_format($course['progress'], 1); ?>%</p>
                                    <div style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 5px; margin-bottom: 1rem; font-size: 0.9rem;">
                                        🎉 Congratulations! You've completed this course and are eligible for a certificate.
                                    </div>
                                    <button onclick="generateCertificate(<?php echo $course['id']; ?>)" class="btn-success" style="background: #28a745;">
                                        🏆 Generate Certificate
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($certificates) && empty($available_quizzes) && empty($certificate_ready_courses)): ?>
                        <div class="alert alert-info" style="background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; padding: 1rem; border-radius: 5px; text-align: center;">
                            <h4>No certificates available yet</h4>
                            <p>Complete courses and pass quizzes to earn certificates. Enroll in courses and start learning!</p>
                            <a href="?tab=browse" class="btn-primary" style="margin-top: 1rem;">Browse Courses</a>
                        </div>
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

    <script>
        async function generateCertificate(courseId) {
            if (!confirm('Generate your certificate for this course?')) {
                return;
            }

            try {
                const response = await fetch('../api/generate-certificate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        course_id: courseId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message || 'Certificate generated successfully!');
                    location.reload(); // Reload to show the new certificate
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error generating certificate:', error);
                alert('Failed to generate certificate. Please try again.');
            }
        }
    </script>
</body>
</html>