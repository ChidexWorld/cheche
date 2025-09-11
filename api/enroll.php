<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireStudent();

$course_id = intval($_GET['course_id'] ?? 0);

if (!$course_id) {
    header('Location: ../student-dashboard.php?tab=browse&error=Invalid course');
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Check if course exists
    $stmt = $conn->prepare("SELECT id, title FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        header('Location: ../student-dashboard.php?tab=browse&error=Course not found');
        exit();
    }
    
    // Check if already enrolled
    $stmt = $conn->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    
    if ($stmt->fetch()) {
        header('Location: ../student-dashboard.php?tab=my-courses&error=Already enrolled in this course');
        exit();
    }
    
    // Enroll student
    $stmt = $conn->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
    
    if ($stmt->execute([$_SESSION['user_id'], $course_id])) {
        header('Location: ../course.php?id=' . $course_id . '&success=Successfully enrolled');
    } else {
        header('Location: ../student-dashboard.php?tab=browse&error=Failed to enroll');
    }
    
} catch (Exception $e) {
    header('Location: ../student-dashboard.php?tab=browse&error=Enrollment failed');
}
?>