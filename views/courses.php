<?php
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Get all courses with instructor info
$stmt = $conn->prepare("SELECT * FROM courses ORDER BY created_at DESC");
$stmt->execute();
$all_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
$courses = [];

foreach ($all_courses as $course) {
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

    // Get enrollment count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
    $stmt->execute([$course['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $course['enrollment_count'] = (int)$result['count'];

    $courses[] = $course;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Courses - Cheche</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <a href="index.php" data-translate>Home</a>
                <a href="#about" data-translate>About</a>
                <a href="login.php" class="btn-secondary" data-translate>Login</a>
                <a href="register.php" class="btn-primary" data-translate>Get Started</a>
            </div>
        </div>
    </nav>

    <section class="courses-preview" style="margin-top: 80px;">
        <div class="container">
            <h1 style="text-align: center; margin-bottom: 2rem;" data-translate>All Courses</h1>
            <p style="text-align: center; margin-bottom: 3rem; color: #666;" data-translate>
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
                                <p><strong data-translate>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                
                                <div class="course-meta">
                                    <span><?php echo $course['video_count']; ?> <span data-translate>videos</span></span>
                                    <span><?php echo $course['enrollment_count']; ?> <span data-translate>students</span></span>
                                    <?php if ($course['category']): ?>
                                        <span><?php echo htmlspecialchars($course['category']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($course['price'] > 0): ?>
                                    <div style="margin: 1rem 0;">
                                        <strong style="color: #4a90e2; font-size: 1.2rem;">₦<?php echo number_format($course['price']); ?></strong>
                                    </div>
                                <?php else: ?>
                                    <div style="margin: 1rem 0;">
                                        <strong style="color: #28a745; font-size: 1.2rem;" data-translate>Free</strong>
                                    </div>
                                <?php endif; ?>
                                
                                <div style="margin-top: 1rem;  display: flex; gap: 1rem;  flex-direction: column; ">
                                    <a href="course-preview.php?id=<?php echo $course['id']; ?>" class="btn-secondary" style="margin-right: 10px;">View Details</a>
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
                <p>&copy; 2025 Cheche. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/language.js"></script>
</body>
</html>