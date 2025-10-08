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
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        if ($is_api) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course']);
            exit();
        }
        header('Location: ../views/student-dashboard.php?tab=my-courses&error=Not enrolled in this course');
        exit();
    }

    // Start a transaction for consistency
    $conn->beginTransaction();

    try {
        // Remove enrollment
        $stmt = $conn->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");
        $success = $stmt->execute([$_SESSION['user_id'], $course_id]);

        // Get all video IDs for this course
        $stmt = $conn->prepare("SELECT id FROM videos WHERE course_id = ?");
        $stmt->execute([$course_id]);
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Remove video progress for all videos in this course
        if (!empty($videos)) {
            $videoIds = array_column($videos, 'id');
            $placeholders = str_repeat('?,', count($videoIds) - 1) . '?';
            $stmt = $conn->prepare("DELETE FROM video_progress WHERE student_id = ? AND video_id IN ($placeholders)");
            $stmt->execute(array_merge([$_SESSION['user_id']], $videoIds));
        }

        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $success = false;
        error_log($e->getMessage());
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