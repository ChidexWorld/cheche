<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Get all courses with instructor info
$stmt = $conn->prepare("
    SELECT c.*, u.full_name as instructor_name, 
           COUNT(v.id) as video_count,
           COUNT(e.id) as enrollment_count
    FROM courses c 
    JOIN users u ON c.instructor_id = u.id 
    LEFT JOIN videos v ON c.id = v.course_id 
    LEFT JOIN enrollments e ON c.id = e.course_id 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
");
$stmt->execute();
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Courses - Cheche</title>
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
                <a href="index.php">Home</a>
                <a href="#about">About</a>
                <a href="login.php" class="btn-secondary">Login</a>
                <a href="register.php" class="btn-primary">Get Started</a>
            </div>
        </div>
    </nav>

    <section class="courses-preview" style="margin-top: 80px;">
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 2rem;">All Courses</h1>
            <p style="text-align: center; margin-bottom: 3rem; color: #666;">
                Discover our complete collection of courses from expert instructors
            </p>
            
            <?php if ($courses): ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-thumbnail">📚 Course</div>
                            <div class="course-content">
                                <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                <p><?php echo htmlspecialchars($course['description'] ?? 'Learn new skills with this comprehensive course.'); ?></p>
                                <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                
                                <div class="course-meta">
                                    <span><?php echo $course['video_count']; ?> videos</span>
                                    <span><?php echo $course['enrollment_count']; ?> students</span>
                                    <?php if ($course['category']): ?>
                                        <span><?php echo htmlspecialchars($course['category']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($course['price'] > 0): ?>
                                    <div style="margin: 1rem 0;">
                                        <strong style="color: #4a90e2; font-size: 1.2rem;">$<?php echo number_format($course['price'], 2); ?></strong>
                                    </div>
                                <?php else: ?>
                                    <div style="margin: 1rem 0;">
                                        <strong style="color: #28a745; font-size: 1.2rem;">Free</strong>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="margin-top: 1rem;">
                                    <a href="register.php" class="btn-primary">Enroll Now</a>
                                </div>
                                
                                <small style="color: #888; margin-top: 10px; display: block;">
                                    Created: <?php echo date('M j, Y', strtotime($course['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem 0;">
                    <h3>No courses available yet</h3>
                    <p>Check back soon for new courses!</p>
                    <a href="register.php" class="btn-primary" style="margin-top: 2rem;">Become an Instructor</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

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
                <p>&copy; 2024 Cheche. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>