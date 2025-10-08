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
    header('Location: ../views/student-dashboard.php?tab=browse&error=Invalid course');
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Check if course exists
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        if ($is_api) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Course not found']);
            exit();
        }
        header('Location: ../views/student-dashboard.php?tab=browse&error=Course not found');
        exit();
    }

    // Check if already enrolled
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    $existing_enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_enrollment) {
        if ($is_api) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Already enrolled in this course']);
            exit();
        }
        header('Location: ../views/student-dashboard.php?tab=my-courses&error=Already enrolled in this course');
        exit();
    }

    // Enroll student
    $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id, enrolled_at, progress) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([
        $_SESSION['user_id'],
        $course_id,
        date('Y-m-d H:i:s'),
        0.00
    ]);
    $enrollment_id = $success ? $conn->lastInsertId() : false;

    if ($enrollment_id) {
        if ($is_api) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Successfully enrolled', 'enrollment_id' => $enrollment_id]);
            exit();
        }
        header('Location: ../views/course.php?id=' . $course_id . '&success=Successfully enrolled');
    } else {
        if ($is_api) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to enroll']);
            exit();
        }
        header('Location: ../views/student-dashboard.php?tab=browse&error=Failed to enroll');
    }

} catch (Exception $e) {
    if ($is_api) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Enrollment failed: ' . $e->getMessage()]);
        exit();
    }
    header('Location: ../views/student-dashboard.php?tab=browse&error=Enrollment failed');
}
?>