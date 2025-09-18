<?php
require_once '../config/database.php';
require_once '../config/env.php';

header('Content-Type: application/json');

$limit = intval($_GET['limit'] ?? 10);
$offset = intval($_GET['offset'] ?? 0);

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get all courses ordered by created_at DESC
    $stmt = $conn->prepare("SELECT * FROM courses ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enrich each course with additional data
    $enriched_courses = [];
    foreach ($courses as $course) {
        // Get instructor info
        $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $stmt->execute([$course['instructor_id']]);
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['instructor_name'] = $instructor['full_name'] ?? 'Unknown';

        // Count videos for this course
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM videos WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['video_count'] = (int)$result['count'];

        // Count enrollments for this course
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
        $stmt->execute([$course['id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $course['enrollment_count'] = (int)$result['count'];

        $enriched_courses[] = $course;
    }

    echo json_encode([
        'success' => true,
        'courses' => $enriched_courses,
        'count' => count($enriched_courses)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch courses',
        'error' => $e->getMessage()
    ]);
}
?>