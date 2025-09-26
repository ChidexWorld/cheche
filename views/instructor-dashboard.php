<?php
require_once "../config/database.php";
require_once "../config/session.php";

requireInstructor();

$database = new Database();
$db = $database->getConnection();

// Initialize variables
$success_message = $_SESSION["success_message"] ?? "";
$error_message = $_SESSION["error_message"] ?? "";
// Get instructor's quizzes
$instructor_quizzes = [];
foreach ($courses as $course) {
    try {
        $stmt = $db->prepare("SELECT * FROM quizzes WHERE course_id = ? ORDER BY created_at DESC");
        $stmt->execute([$course['id']]);
        $quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($quizzes as $quiz) {
            $quiz['course_title'] = $course['title'];

            // Get quiz statistics
            $stmt = $db->prepare("SELECT COUNT(*) as total_attempts FROM quiz_attempts WHERE quiz_id = ?");
            $stmt->execute([$quiz['id']]);
            $attempt_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $quiz['total_attempts'] = (int)$attempt_stats['total_attempts'];

            $stmt = $db->prepare("SELECT COUNT(DISTINCT student_id) as unique_students FROM quiz_attempts WHERE quiz_id = ?");
            $stmt->execute([$quiz['id']]);
            $student_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $quiz['unique_students'] = (int)$student_stats['unique_students'];

            $stmt = $db->prepare("SELECT AVG(score) as avg_score FROM quiz_attempts WHERE quiz_id = ? AND completed_at IS NOT NULL");
            $stmt->execute([$quiz['id']]);
            $score_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            $quiz['avg_score'] = floatval($score_stats['avg_score'] ?? 0);

            $instructor_quizzes[] = $quiz;
        }
    } catch (Exception $e) {
        // Skip if tables don't exist yet
    }
}

// Get certificates issued for instructor's courses
$instructor_certificates = [];
foreach ($courses as $course) {
    try {
        $stmt = $db->prepare("
            SELECT c.*, u.full_name as student_name
            FROM certificates c
            JOIN users u ON c.student_id = u.id
            WHERE c.course_id = ?
            ORDER BY c.issued_at DESC
        ");
        $stmt->execute([$course['id']]);
        $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($certificates as $certificate) {
            $certificate['course_title'] = $course['title'];
            $instructor_certificates[] = $certificate;
        }
    } catch (Exception $e) {
        // Skip if tables don't exist yet
    }
}

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
                <a href="?tab=quizzes" class="<?php echo $active_tab === 'quizzes' ? 'active' : ''; ?>" data-translate>📝 Quizzes</a>
                <a href="?tab=certificates" class="<?php echo $active_tab === 'certificates' ? 'active' : ''; ?>" data-translate>🏆 Certificates</a>
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
                                        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <a href="course.php?id=<?php echo $video['course_id']; ?>" class="btn-primary" style="font-size: 0.8rem;">View Course</a>
                                            <a href="manage-subtitles.php?video_id=<?php echo $video['id']; ?>" class="btn-secondary" style="font-size: 0.8rem;">📝 Subtitles</a>
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

                <?php elseif ($active_tab === 'quizzes'): ?>
                    <h2>📝 Quiz Management</h2>

                    <!-- Create New Quiz Section -->
                    <div style="background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                        <h3>Create New Quiz</h3>
                        <form id="create-quiz-form" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <label>Course:</label>
                                <select name="course_id" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                                    <option value="">Select a course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Quiz Title:</label>
                                <input type="text" name="title" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" placeholder="e.g., Final Assessment">
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <label>Description:</label>
                                <textarea name="description" style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;" rows="3" placeholder="Quiz description..."></textarea>
                            </div>
                            <div>
                                <label>Passing Score (%):</label>
                                <input type="number" name="passing_score" value="70" min="0" max="100" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div>
                                <label>Max Attempts:</label>
                                <input type="number" name="max_attempts" value="3" min="1" max="10" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div>
                                <label>Time Limit (minutes):</label>
                                <input type="number" name="time_limit" value="30" min="5" max="180" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <button type="submit" class="btn-primary">Create Quiz</button>
                            </div>
                        </form>
                    </div>

                    <!-- Existing Quizzes -->
                    <?php if ($instructor_quizzes): ?>
                        <h3>Your Quizzes</h3>
                        <div class="quizzes-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem;">
                            <?php foreach ($instructor_quizzes as $quiz): ?>
                                <div class="quiz-card" style="background: white; border-radius: 10px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                                    <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 1rem;">
                                        <div style="flex: 1;">
                                            <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($quiz['title']); ?></h4>
                                            <p style="margin: 0; color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars($quiz['course_title']); ?></p>
                                        </div>
                                        <span style="background: <?php echo $quiz['is_active'] ? '#28a745' : '#6c757d'; ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 15px; font-size: 0.8rem;">
                                            <?php echo $quiz['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>

                                    <div style="background: #f8f9fa; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; font-size: 0.9rem;">
                                            <div><strong>Passing Score:</strong> <?php echo $quiz['passing_score']; ?>%</div>
                                            <div><strong>Time Limit:</strong> <?php echo $quiz['time_limit']; ?> min</div>
                                            <div><strong>Total Attempts:</strong> <?php echo $quiz['total_attempts']; ?></div>
                                            <div><strong>Unique Students:</strong> <?php echo $quiz['unique_students']; ?></div>
                                        </div>
                                        <?php if ($quiz['avg_score'] > 0): ?>
                                            <div style="margin-top: 0.5rem; font-size: 0.9rem;">
                                                <strong>Average Score:</strong> <?php echo number_format($quiz['avg_score'], 1); ?>%
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                                        <button onclick="addQuestionModal(<?php echo $quiz['id']; ?>)" class="btn-primary" style="font-size: 0.9rem;">Add Question</button>
                                        <button onclick="viewQuizStats(<?php echo $quiz['id']; ?>)" class="btn-secondary" style="font-size: 0.9rem;">View Stats</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
                            <h4>No quizzes created yet</h4>
                            <p>Create your first quiz to test your students' knowledge.</p>
                        </div>
                    <?php endif; ?>

                <?php elseif ($active_tab === 'certificates'): ?>
                    <h2>🏆 Certificates Issued</h2>

                    <?php if ($instructor_certificates): ?>
                        <div class="certificates-table" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f8f9fa;">
                                    <tr>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Student</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Course</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Certificate #</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Quiz Score</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Issued Date</th>
                                        <th style="padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($instructor_certificates as $certificate): ?>
                                        <tr style="border-bottom: 1px solid #eee;">
                                            <td style="padding: 1rem;"><?php echo htmlspecialchars($certificate['student_name']); ?></td>
                                            <td style="padding: 1rem;"><?php echo htmlspecialchars($certificate['course_title']); ?></td>
                                            <td style="padding: 1rem; font-family: monospace;"><?php echo htmlspecialchars($certificate['certificate_number']); ?></td>
                                            <td style="padding: 1rem;">
                                                <?php if ($certificate['quiz_score']): ?>
                                                    <?php echo number_format($certificate['quiz_score'], 1); ?>%
                                                <?php else: ?>
                                                    <span style="color: #666;">No quiz</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 1rem;"><?php echo date('M d, Y', strtotime($certificate['issued_at'])); ?></td>
                                            <td style="padding: 1rem;">
                                                <a href="certificate.php?id=<?php echo $certificate['id']; ?>" class="btn-primary" style="font-size: 0.8rem; padding: 0.4rem 0.8rem; text-decoration: none;">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 2rem; padding: 1rem; background: #d1ecf1; border-radius: 8px;">
                            <h4 style="margin: 0 0 0.5rem 0; color: #0c5460;">Certificate Statistics</h4>
                            <p style="margin: 0; color: #0c5460;">
                                <strong><?php echo count($instructor_certificates); ?></strong> certificates issued across
                                <strong><?php echo count(array_unique(array_column($instructor_certificates, 'course_id'))); ?></strong> courses
                            </p>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 2rem; background: #f8f9fa; border-radius: 8px;">
                            <h4>No certificates issued yet</h4>
                            <p>Students will receive certificates when they complete courses and pass quizzes.</p>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/modal.js"></script>
    <script src="../assets/js/language.js"></script>

    <script>
        // Quiz management functions
        document.addEventListener('DOMContentLoaded', function() {
            const createQuizForm = document.getElementById('create-quiz-form');
            if (createQuizForm) {
                createQuizForm.addEventListener('submit', async function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const data = Object.fromEntries(formData.entries());

                    try {
                        const response = await fetch('../api/create-quiz.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(data)
                        });

                        const result = await response.json();

                        if (result.success) {
                            alert('Quiz created successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + result.error);
                        }
                    } catch (error) {
                        console.error('Error creating quiz:', error);
                        alert('Failed to create quiz. Please try again.');
                    }
                });
            }
        });

        async function addQuestionModal(quizId) {
            const questionText = prompt('Enter question text:');
            if (!questionText) return;

            const questionType = prompt('Enter question type (multiple_choice, true_false, or short_answer):', 'multiple_choice');
            if (!questionType) return;

            const points = prompt('Enter points for this question:', '1');
            if (!points) return;

            let options = [];
            if (questionType === 'multiple_choice') {
                const numOptions = parseInt(prompt('How many options?', '4'));
                for (let i = 0; i < numOptions; i++) {
                    const optionText = prompt(`Enter option ${i + 1}:`);
                    if (optionText) {
                        const isCorrect = confirm(`Is "${optionText}" the correct answer?`);
                        options.push({
                            text: optionText,
                            is_correct: isCorrect
                        });
                    }
                }
            } else if (questionType === 'true_false') {
                const correctAnswer = confirm('Is the correct answer TRUE? (Cancel for FALSE)');
                options = [
                    { text: 'True', is_correct: correctAnswer },
                    { text: 'False', is_correct: !correctAnswer }
                ];
            }

            try {
                const response = await fetch('../api/add-quiz-question.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        quiz_id: quizId,
                        question_text: questionText,
                        question_type: questionType,
                        points: parseFloat(points),
                        options: options
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Question added successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                console.error('Error adding question:', error);
                alert('Failed to add question. Please try again.');
            }
        }

        function viewQuizStats(quizId) {
            // Simple implementation - could be expanded to show detailed stats
            alert('Quiz stats feature coming soon!');
        }
    </script>
</body>
</html>