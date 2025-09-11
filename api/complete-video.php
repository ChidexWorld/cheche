<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireStudent();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$video_id = intval($input['video_id'] ?? 0);
$completed = $input['completed'] ?? false;

if (!$video_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid video ID']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Verify student is enrolled in the course containing this video
    $stmt = $conn->prepare("
        SELECT v.id, v.duration 
        FROM videos v 
        JOIN enrollments e ON v.course_id = e.course_id 
        WHERE v.id = ? AND e.student_id = ?
    ");
    $stmt->execute([$video_id, $_SESSION['user_id']]);
    $video = $stmt->fetch();
    
    if (!$video) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
    
    // Mark video as completed
    $stmt = $conn->prepare("
        INSERT INTO video_progress (student_id, video_id, watched_duration, completed) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        completed = VALUES(completed),
        watched_duration = CASE 
            WHEN VALUES(completed) = 1 THEN GREATEST(watched_duration, ?)
            ELSE watched_duration 
        END,
        watched_at = CURRENT_TIMESTAMP
    ");
    
    $watched_duration = $completed ? $video['duration'] : 0;
    
    if ($stmt->execute([$_SESSION['user_id'], $video_id, $watched_duration, $completed, $video['duration']])) {
        // Update course progress
        updateCourseProgress($conn, $_SESSION['user_id'], $video_id);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to mark as completed']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}

function updateCourseProgress($conn, $student_id, $video_id) {
    // Get course ID from video
    $stmt = $conn->prepare("SELECT course_id FROM videos WHERE id = ?");
    $stmt->execute([$video_id]);
    $course_id = $stmt->fetchColumn();
    
    if (!$course_id) return;
    
    // Calculate overall progress for the course
    $stmt = $conn->prepare("
        SELECT 
            COUNT(v.id) as total_videos,
            SUM(CASE WHEN vp.completed = 1 THEN 1 ELSE 0 END) as completed_videos
        FROM videos v 
        LEFT JOIN video_progress vp ON v.id = vp.video_id AND vp.student_id = ?
        WHERE v.course_id = ?
    ");
    $stmt->execute([$student_id, $course_id]);
    $stats = $stmt->fetch();
    
    if ($stats['total_videos'] > 0) {
        $progress = ($stats['completed_videos'] / $stats['total_videos']) * 100;
        
        // Update enrollment progress
        $stmt = $conn->prepare("
            UPDATE enrollments 
            SET progress = ? 
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->execute([$progress, $student_id, $course_id]);
    }
}
?>