<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$video_id = $_GET['video_id'] ?? 0;

if (!$video_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Video ID is required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get subtitle information for the video
    if ($conn === $database) {
        $subtitle = $database->selectOne('subtitles', ['video_id' => $video_id]);
    } else {
        $stmt = $conn->prepare("SELECT * FROM subtitles WHERE video_id = ?");
        $stmt->execute([$video_id]);
        $subtitle = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$subtitle) {
        echo json_encode([
            'success' => true,
            'has_subtitle' => false,
            'message' => 'No subtitle found for this video'
        ]);
        exit;
    }

    // Check access permissions
    $can_access = false;
    if (isInstructor()) {
        // Check if instructor owns the video
        if ($conn === $database) {
            $video = $database->selectOne('videos', ['id' => $video_id]);
            if ($video) {
                $course = $database->selectOne('courses', ['id' => $video['course_id']]);
                $can_access = $course && $course['instructor_id'] == $_SESSION['user_id'];
            }
        } else {
            $stmt = $conn->prepare("
                SELECT v.* FROM videos v
                JOIN courses c ON v.course_id = c.id
                WHERE v.id = ? AND c.instructor_id = ?
            ");
            $stmt->execute([$video_id, $_SESSION['user_id']]);
            $video = $stmt->fetch(PDO::FETCH_ASSOC);
            $can_access = $video !== false;
        }
    } elseif (isStudent()) {
        // Check if student is enrolled in the course
        if ($conn === $database) {
            $video = $database->selectOne('videos', ['id' => $video_id]);
            if ($video) {
                $enrollment = $database->selectOne('enrollments', [
                    'student_id' => $_SESSION['user_id'],
                    'course_id' => $video['course_id']
                ]);
                $can_access = $enrollment !== false;
            }
        } else {
            $stmt = $conn->prepare("
                SELECT e.* FROM enrollments e
                JOIN videos v ON e.course_id = v.course_id
                WHERE e.student_id = ? AND v.id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $video_id]);
            $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
            $can_access = $enrollment !== false;
        }
    }

    if (!$can_access) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Return subtitle information
    echo json_encode([
        'success' => true,
        'has_subtitle' => true,
        'subtitle' => [
            'id' => $subtitle['id'],
            'translation_status' => $subtitle['translation_status'],
            'merge_status' => $subtitle['merge_status'],
            'has_original' => !empty($subtitle['original_file_path']),
            'has_translated' => !empty($subtitle['translated_file_path']),
            'has_merged' => !empty($subtitle['merged_video_path']),
            'language_from' => $subtitle['language_from'] ?? 'en',
            'language_to' => $subtitle['language_to'] ?? 'ig',
            'created_at' => $subtitle['created_at'],
            'updated_at' => $subtitle['updated_at'] ?? $subtitle['created_at']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get subtitle information: ' . $e->getMessage()]);
}
?>