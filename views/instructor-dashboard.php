<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireInstructor();

$database = new Database();
$db = $database->getConnection();

// Get instructor's courses
$all_courses = $db->select('courses', ['instructor_id' => $_SESSION['user_id']], 'created_at DESC');
$courses = [];

foreach ($all_courses as $course) {
    $course['video_count'] = $db->count('videos', ['course_id' => $course['id']]);
    $course['student_count'] = $db->count('enrollments', ['course_id' => $course['id']]);
    $courses[] = $course;
}

// Get recent videos
$all_videos = $db->select('videos');
$recent_videos = [];
$count = 0;

// Get videos for this instructor's courses
foreach ($all_videos as $video) {
    if ($count >= 5) break;
    
    $video_course = $db->selectOne('courses', ['id' => $video['course_id']]);
    if ($video_course && $video_course['instructor_id'] == $_SESSION['user_id']) {
        $video['course_title'] = $video_course['title'];
        $recent_videos[] = $video;
        $count++;
    }
}

$active_tab = $_GET['tab'] ?? 'overview';
$success_message = $_GET['success'] ?? '';
$error_message = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Dashboard - Cheche</title>
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
                <h1>Instructor Dashboard</h1>
                <p>Manage your courses and track student progress</p>
            </div>
        </div>

        <div class="container">
            <div class="dashboard-nav">
                <a href="?tab=overview" class="<?php echo $active_tab === 'overview' ? 'active' : ''; ?>">Overview</a>
                <a href="?tab=courses" class="<?php echo $active_tab === 'courses' ? 'active' : ''; ?>">My Courses</a>
                <a href="?tab=upload" class="<?php echo $active_tab === 'upload' ? 'active' : ''; ?>">Upload Video</a>
                <a href="?tab=create-course" class="<?php echo $active_tab === 'create-course' ? 'active' : ''; ?>">Create Course</a>
            </div>

            <div class="dashboard-content">
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <?php if ($active_tab === 'overview'): ?>
                    <h2>Overview</h2>
                    <div class="stats" style="display: flex; gap: 2rem; margin-bottom: 2rem;">
                        <div class="stat">
                            <h3><?php echo count($courses); ?></h3>
                            <p>Total Courses</p>
                        </div>
                        <div class="stat">
                            <h3><?php echo array_sum(array_column($courses, 'video_count')); ?></h3>
                            <p>Total Videos</p>
                        </div>
                        <div class="stat">
                            <h3><?php echo array_sum(array_column($courses, 'student_count')); ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>

                    <h3>Recent Videos</h3>
                    <?php if ($recent_videos): ?>
                        <div class="courses-grid">
                            <?php foreach ($recent_videos as $video): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">📹</div>
                                    <div class="course-content">
                                        <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                                        <p>Course: <?php echo htmlspecialchars($video['course_title']); ?></p>
                                        <small>Uploaded: <?php echo date('M j, Y', strtotime($video['created_at'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No videos uploaded yet. <a href="?tab=upload">Upload your first video</a></p>
                    <?php endif; ?>

                <?php elseif ($active_tab === 'courses'): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                        <h2>My Courses</h2>
                        <a href="?tab=create-course" class="btn-primary">Create New Course</a>
                    </div>

                    <?php if ($courses): ?>
                        <div class="courses-grid">
                            <?php foreach ($courses as $course): ?>
                                <div class="course-card">
                                    <div class="course-thumbnail">📚 Course</div>
                                    <div class="course-content">
                                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                                        <p><?php echo htmlspecialchars($course['description'] ?? 'No description'); ?></p>
                                        <div class="course-meta">
                                            <span><?php echo $course['video_count']; ?> videos</span>
                                            <span><?php echo $course['student_count']; ?> students</span>
                                        </div>
                                        <div style="margin-top: 1rem;">
                                            <a href="manage-course.php?id=<?php echo $course['id']; ?>" class="btn-primary" style="margin-right: 0.5rem;">Manage</a>
                                            <a href="?tab=upload&course_id=<?php echo $course['id']; ?>" class="btn-secondary">Add Video</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>You haven't created any courses yet. <a href="?tab=create-course">Create your first course</a></p>
                    <?php endif; ?>

                <?php elseif ($active_tab === 'create-course'): ?>
                    <h2>Create New Course</h2>
                    <form action="api/create-course.php" method="POST" style="max-width: 600px;">
                        <div class="form-group">
                            <label for="title">Course Title</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Course Description</label>
                            <textarea id="description" name="description" rows="4" 
                                      placeholder="Describe what students will learn..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select id="category" name="category">
                                <option value="">Select a category</option>
                                <option value="Web Development">Web Development</option>
                                <option value="Mobile Development">Mobile Development</option>
                                <option value="Data Science">Data Science</option>
                                <option value="Design">Design</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Business">Business</option>
                                <option value="Photography">Photography</option>
                                <option value="Music">Music</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price (₦ NGN) - Leave empty for free</label>
                            <input type="number" id="price" name="price" min="0" step="1" placeholder="0">
                        </div>
                        
                        <button type="submit" class="btn-primary">Create Course</button>
                    </form>

                <?php elseif ($active_tab === 'upload'): ?>
                    <h2>Upload Video</h2>
                    
                    <?php if (empty($courses)): ?>
                        <div class="alert alert-error">
                            You need to create a course first before uploading videos. 
                            <a href="?tab=create-course">Create a course now</a>
                        </div>
                    <?php else: ?>
                        <form id="videoUploadForm" enctype="multipart/form-data" style="max-width: 600px;">
                            <div class="form-group">
                                <label for="course_id">Select Course</label>
                                <select id="course_id" name="course_id" required>
                                    <option value="">Choose a course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?php echo $course['id']; ?>" 
                                                <?php echo ($_GET['course_id'] ?? '') == $course['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($course['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="video_title">Video Title</label>
                                <input type="text" id="video_title" name="title" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="video_description">Video Description</label>
                                <textarea id="video_description" name="description" rows="3"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="video_file">Video File</label>
                                <input type="file" id="video_file" name="video_file" accept="video/*" required>
                                <small>Supported formats: MP4, AVI, MOV, WMV (Max: 100MB)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="order_number">Lesson Order</label>
                                <input type="number" id="order_number" name="order_number" min="1" value="1">
                            </div>
                            
                            <button type="submit" class="btn-primary">Upload Video</button>
                        </form>
                        
                        <div id="uploadProgress" style="display: none; margin-top: 1rem;">
                            <div style="background: #f0f0f0; border-radius: 10px; overflow: hidden;">
                                <div id="progressBar" style="width: 0%; height: 20px; background: #4a90e2; transition: width 0.3s;"></div>
                            </div>
                            <p id="progressText" style="margin-top: 0.5rem;">Uploading...</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
    <script>
        document.getElementById('videoUploadForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            uploadVideo();
        });

        function uploadVideo() {
            const formData = new FormData(document.getElementById('videoUploadForm'));
            const progressDiv = document.getElementById('uploadProgress');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            
            progressDiv.style.display = 'block';
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                    progressText.textContent = 'Uploading: ' + Math.round(percentComplete) + '%';
                }
            });
            
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        progressText.textContent = 'Upload complete!';
                        setTimeout(() => {
                            window.location.href = '?tab=courses';
                        }, 1500);
                    } else {
                        progressText.textContent = 'Upload failed: ' + response.message;
                        progressBar.style.background = '#dc3545';
                    }
                } else {
                    progressText.textContent = 'Upload failed';
                    progressBar.style.background = '#dc3545';
                }
            });
            
            xhr.addEventListener('error', function() {
                progressText.textContent = 'Upload failed';
                progressBar.style.background = '#dc3545';
            });
            
            xhr.open('POST', 'api/upload-video.php');
            xhr.send(formData);
        }
    </script>
</body>
</html>