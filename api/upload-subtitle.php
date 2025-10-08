<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../config/subtitle-processor.php';

requireInstructor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$video_id = $_POST['video_id'] ?? 0;

if (!$video_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Video ID is required']);
    exit;
}

if (!isset($_FILES['subtitle_file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Subtitle file is required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verify instructor owns this video
    if ($conn === $database) {
        // File-based database
        $video = $database->selectOne('videos', ['id' => $video_id]);
        if ($video) {
            $course = $database->selectOne('courses', ['id' => $video['course_id']]);
            $owns_video = $course && $course['instructor_id'] == $_SESSION['user_id'];
        } else {
            $owns_video = false;
        }
    } else {
        // MySQL database
        $stmt = $conn->prepare("
            SELECT v.* FROM videos v
            JOIN courses c ON v.course_id = c.id
            WHERE v.id = ? AND c.instructor_id = ?
        ");
        $stmt->execute([$video_id, $_SESSION['user_id']]);
        $video = $stmt->fetch(PDO::FETCH_ASSOC);
        $owns_video = $video !== false;
    }

    if (!$owns_video) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Initialize subtitle processor
    $processor = new SubtitleProcessor($database);

    // Upload subtitle file
    $subtitle_id = $processor->uploadSubtitle($video_id, $_FILES['subtitle_file']);

    // Start translation process
    try {
        $translated_path = $processor->translateSubtitleFile($subtitle_id);

        echo json_encode([
            'success' => true,
            'subtitle_id' => $subtitle_id,
            'message' => 'Subtitle uploaded and translated successfully',
            'translated_path' => $translated_path
        ]);

        // Start merge process in background (you might want to implement this as a background job)
        try {
            $merged_path = $processor->mergeSubtitleWithVideo($subtitle_id);
            // Log successful merge (in production, you might update via AJAX or WebSocket)
        } catch (Exception $e) {
            error_log('Subtitle merge failed: ' . $e->getMessage());
        }

    } catch (Exception $e) {
        echo json_encode([
            'success' => true,
            'subtitle_id' => $subtitle_id,
            'message' => 'Subtitle uploaded successfully, translation in progress',
            'translation_error' => $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to upload subtitle: ' . $e->getMessage()]);
}
?>