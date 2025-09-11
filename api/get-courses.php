<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$limit = intval($_GET['limit'] ?? 10);
$offset = intval($_GET['offset'] ?? 0);

try {
    $database = new Database();
    $conn = $database->getConnection();
    
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
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$limit, $offset]);
    $courses = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'courses' => $courses,
        'count' => count($courses)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch courses',
        'error' => $e->getMessage()
    ]);
}
?>