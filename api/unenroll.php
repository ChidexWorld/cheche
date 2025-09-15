<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/env.php';

requireStudent();

// Handle both GET and POST requests
$course_id = intval($_GET['course_id'] ?? $_POST['course_id'] ?? 0);

// If POST request, return JSON response
$is_api = $_SERVER['REQUEST_METHOD'] === 'POST';

if (!$course_id) {
    if ($is_api) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
        exit();
    }
    header('Location: ../views/student-dashboard.php?tab=my-courses&error=Invalid course');
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if enrollment exists
    $enrollment = $conn->selectOne('enrollments', [
        'student_id' => $_SESSION['user_id'],
        'course_id' => $course_id
    ]);

    if (!$enrollment) {
        if ($is_api) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course']);
            exit();
        }
        header('Location: ../views/student-dashboard.php?tab=my-courses&error=Not enrolled in this course');
        exit();
    }

    // Remove enrollment
    $success = $conn->delete('enrollments', [
        'student_id' => $_SESSION['user_id'],
        'course_id' => $course_id
    ]);

    // Also remove video progress for this course
    $videos = $conn->select('videos', ['course_id' => $course_id]);
    foreach ($videos as $video) {
        $conn->delete('video_progress', [
            'student_id' => $_SESSION['user_id'],
            'video_id' => $video['id']
        ]);
    }

    if ($success) {
        if ($is_api) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Successfully unenrolled from course']);
            exit();
        }
        header('Location: ../views/student-dashboard.php?tab=my-courses&success=Successfully unenrolled from course');
    } else {
        if ($is_api) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to unenroll']);
            exit();
        }
        header('Location: ../views/student-dashboard.php?tab=my-courses&error=Failed to unenroll');
    }

} catch (Exception $e) {
    if ($is_api) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unenrollment failed: ' . $e->getMessage()]);
        exit();
    }
    header('Location: ../views/student-dashboard.php?tab=my-courses&error=Unenrollment failed');
}
?>