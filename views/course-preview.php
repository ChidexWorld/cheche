<?php
require_once '../config/database.php';
require_once '../config/session.php';

$course_id = $_GET['id'] ?? 0;
$database = new Database();
$conn = $database->getConnection();

// Get course details
$course = $conn->selectOne('courses', ['id' => $course_id]);
if (!$course) {
    header('Location: courses.php');
    exit();
}

// Get instructor details
$instructor = $conn->selectOne('users', ['id' => $course['instructor_id']]);
$course['instructor_name'] = $instructor ? $instructor['full_name'] : 'Unknown Instructor';

// Get course videos (just basic info for preview)
$videos = $conn->select('videos', ['course_id' => $course_id], 'order_number ASC');

// Check if user is logged in and enrolled
$is_enrolled = false;
$can_enroll = true;
if (isset($_SESSION['user_id']) && isStudent()) {
    $enrollment = $conn->selectOne('enrollments', ['student_id' => $_SESSION['user_id'], 'course_id' => $course_id]);
    $is_enrolled = $enrollment !== null;
}

// Get enrollment count
$enrollment_count = $conn->count('enrollments', ['course_id' => $course_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - Course Preview - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/modal.css">
    <style>
        .course-preview {
            margin-top: 100px;
            padding: 2rem 0;
        }
        .course-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        .course-content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        .course-description {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .course-sidebar {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .video-list-preview {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-top: 2rem;
        }
        .video-item-preview {
            padding: 1rem;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .video-item-preview .video-number {
            background: #4a90e2;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .locked-content {
            opacity: 0.6;
            position: relative;
        }
        .locked-content::after {
            content: "🔒";
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
        }
        @media (max-width: 768px) {
            .course-content-grid {
                grid-template-columns: 1fr;
            }
            .course-preview {
                margin-top: 80px;
            }
        }
    </style>
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
                <a href="courses.php">All Courses</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo isInstructor() ? 'instructor-dashboard.php' : 'student-dashboard.php'; ?>">Dashboard</a>
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="logout.php" class="btn-secondary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn-secondary">Login</a>
                    <a href="register.php" class="btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="course-preview">
        <div class="container">
            <div class="course-header">
                <div style="text-align: center;">
                    <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p style="font-size: 1.2rem; margin-bottom: 1rem; opacity: 0.9;">
                        <?php echo htmlspecialchars($course['description'] ?? 'Learn new skills with this comprehensive course.'); ?>
                    </p>
                    <p style="margin-bottom: 0;">
                        <strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?>
                    </p>
                </div>
            </div>

            <div class="course-content-grid">
                <div class="course-description">
                    <h2>About This Course</h2>
                    <p><?php echo htmlspecialchars($course['description'] ?? 'This comprehensive course will help you master new skills and advance your career. Join thousands of students who have already benefited from expert instruction and practical learning.'); ?></p>

                    <h3 style="margin-top: 2rem;">What You'll Learn</h3>
                    <ul style="margin-left: 1.5rem; line-height: 1.8;">
                        <li>Fundamental concepts and principles</li>
                        <li>Practical skills through hands-on exercises</li>
                        <li>Industry best practices and techniques</li>
                        <li>Real-world application of knowledge</li>
                    </ul>

                    <?php if ($course['category']): ?>
                        <div style="margin-top: 2rem;">
                            <h3>Course Category</h3>
                            <span style="background: #4a90e2; color: white; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem;">
                                <?php echo htmlspecialchars($course['category']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="course-sidebar">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <?php if ($course['price'] > 0): ?>
                            <div style="font-size: 2rem; font-weight: bold; color: #4a90e2; margin-bottom: 1rem;">
                                ₦<?php echo number_format($course['price']); ?>
                            </div>
                        <?php else: ?>
                            <div style="font-size: 2rem; font-weight: bold; color: #28a745; margin-bottom: 1rem;">
                                Free
                            </div>
                        <?php endif; ?>

                        <?php if ($is_enrolled): ?>
                            <a href="course.php?id=<?php echo $course['id']; ?>" class="btn-primary" style="width: 100%; margin-bottom: 1rem;">
                                Continue Learning
                            </a>
                        <?php elseif (isset($_SESSION['user_id']) && isStudent()): ?>
                            <button onclick="showModal('enroll-modal-<?php echo $course['id']; ?>')" class="btn-primary" style="width: 100%; margin-bottom: 1rem; border: none; cursor: pointer;">
                                Enroll Now
                            </button>
                        <?php else: ?>
                            <a href="register.php" class="btn-primary" style="width: 100%; margin-bottom: 1rem;">
                                Sign Up to Enroll
                            </a>
                        <?php endif; ?>
                    </div>

                    <div style="text-align: center; color: #666;">
                        <div style="margin-bottom: 1rem;">
                            <strong><?php echo count($videos); ?></strong> video<?php echo count($videos) !== 1 ? 's' : ''; ?>
                        </div>
                        <div style="margin-bottom: 1rem;">
                            <strong><?php echo $enrollment_count; ?></strong> student<?php echo $enrollment_count !== 1 ? 's' : ''; ?> enrolled
                        </div>
                        <div>
                            Created: <?php echo date('M j, Y', strtotime($course['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($videos): ?>
                <div class="video-list-preview">
                    <h2>Course Content</h2>
                    <p style="color: #666; margin-bottom: 2rem;">
                        <?php echo count($videos); ?> video<?php echo count($videos) !== 1 ? 's' : ''; ?> in this course
                        <?php if (!$is_enrolled): ?>
                            <span style="color: #dc3545;">(🔒 Locked - Enroll to access)</span>
                        <?php endif; ?>
                    </p>

                    <div class="video-list">
                        <?php foreach ($videos as $index => $video): ?>
                            <div class="video-item-preview <?php echo !$is_enrolled ? 'locked-content' : ''; ?>">
                                <div class="video-number"><?php echo $index + 1; ?></div>
                                <div style="flex: 1;">
                                    <h4 style="margin: 0; font-size: 1rem;">
                                        <?php echo htmlspecialchars($video['title']); ?>
                                    </h4>
                                    <?php if ($video['description']): ?>
                                        <p style="margin: 5px 0 0 0; color: #666; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($video['description']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($is_enrolled): ?>
                                    <a href="course.php?id=<?php echo $course['id']; ?>&video=<?php echo $video['id']; ?>"
                                       class="btn-secondary" style="font-size: 0.8rem;">
                                        Watch
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="video-list-preview">
                    <h2>Course Content</h2>
                    <p>This course is being developed. Content will be available soon!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Cheche</h3>
                    <p>Your gateway to learning new skills and advancing your career.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <a href="courses.php">All Courses</a>
                    <a href="index.php#about">About</a>
                    <a href="register.php">Sign Up</a>
                    <a href="login.php">Login</a>
                </div>
                <div class="footer-section">
                    <h4>For Instructors</h4>
                    <a href="register.php">Become an Instructor</a>
                    <a href="instructor-dashboard.php">Instructor Portal</a>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Cheche. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Enrollment Modal -->
    <?php if (isset($_SESSION['user_id']) && isStudent() && !$is_enrolled): ?>
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
    <?php endif; ?>


    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/modal.js"></script>
</body>
</html>