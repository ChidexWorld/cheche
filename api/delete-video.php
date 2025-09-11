<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireInstructor();

$video_id = $_GET['id'] ?? 0;

if (!$video_id) {
    header('Location: ../instructor-dashboard.php?error=Invalid video ID');
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Get video details and verify ownership
    $stmt = $conn->prepare("
        SELECT v.*, c.instructor_id, c.id as course_id 
        FROM videos v 
        JOIN courses c ON v.course_id = c.id 
        WHERE v.id = ? AND c.instructor_id = ?
    ");
    $stmt->execute([$video_id, $_SESSION['user_id']]);
    $video = $stmt->fetch();
    
    if (!$video) {
        header('Location: ../instructor-dashboard.php?error=Video not found or access denied');
        exit();
    }
    
    // Delete video file
    $file_path = '../' . $video['video_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete video progress records
    $stmt = $conn->prepare("DELETE FROM video_progress WHERE video_id = ?");
    $stmt->execute([$video_id]);
    
    // Delete video record
    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->execute([$video_id]);
    
    header('Location: ../manage-course.php?id=' . $video['course_id'] . '&success=Video deleted successfully');
    
} catch (Exception $e) {
    header('Location: ../instructor-dashboard.php?error=Failed to delete video');
}
?>