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
    $courses = $conn->select('courses', [], 'created_at DESC', $limit);

    // Enrich each course with additional data
    $enriched_courses = [];
    foreach ($courses as $course) {
        // Get instructor info
        $instructor = $conn->selectOne('users', ['id' => $course['instructor_id']]);
        $course['instructor_name'] = $instructor['full_name'] ?? 'Unknown';

        // Count videos for this course
        $course['video_count'] = $conn->count('videos', ['course_id' => $course['id']]);

        // Count enrollments for this course
        $course['enrollment_count'] = $conn->count('enrollments', ['course_id' => $course['id']]);

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