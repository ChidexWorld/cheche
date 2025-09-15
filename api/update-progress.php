<?php
require_once '../config/database.php';
require_once '../config/session.php';

requireStudent();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Accept both JSON and form data
$input = $_POST;
if (empty($input)) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

$video_id = intval($input['video_id'] ?? 0);
$watched_duration = intval($input['watched_duration'] ?? $input['progress'] ?? 0);
$progress = floatval($input['progress'] ?? $input['watched_duration'] ?? 0);

if (!$video_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verify video exists and get course ID
    $video = $conn->selectOne('videos', ['id' => $video_id]);
    if (!$video) {
        echo json_encode(['success' => false, 'message' => 'Video not found']);
        exit();
    }

    // Verify student is enrolled in the course containing this video
    $enrollment = $conn->selectOne('enrollments', [
        'student_id' => $_SESSION['user_id'],
        'course_id' => $video['course_id']
    ]);

    if (!$enrollment) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    // Check if progress record exists
    $existing_progress = $conn->selectOne('video_progress', [
        'student_id' => $_SESSION['user_id'],
        'video_id' => $video_id
    ]);

    if ($existing_progress) {
        // Update only if new progress is greater
        if ($watched_duration > $existing_progress['watched_duration']) {
            $conn->update('video_progress', [
                'watched_duration' => $watched_duration,
                'last_watched' => date('Y-m-d H:i:s')
            ], [
                'student_id' => $_SESSION['user_id'],
                'video_id' => $video_id
            ]);
        }
    } else {
        // Insert new progress record
        $conn->insert('video_progress', [
            'student_id' => $_SESSION['user_id'],
            'video_id' => $video_id,
            'watched_duration' => $watched_duration,
            'last_watched' => date('Y-m-d H:i:s')
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Progress updated']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

?>