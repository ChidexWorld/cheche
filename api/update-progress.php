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
    $stmt = $conn->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$video_id]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$video) {
        echo json_encode(['success' => false, 'message' => 'Video not found']);
        exit();
    }

    // Verify student is enrolled in the course containing this video
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$_SESSION['user_id'], $video['course_id']]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    // Check if progress record exists
    $stmt = $conn->prepare("SELECT * FROM video_progress WHERE student_id = ? AND video_id = ?");
    $stmt->execute([$_SESSION['user_id'], $video_id]);
    $existing_progress = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_progress) {
        // Update only if new progress is greater
        if ($watched_duration > $existing_progress['watched_duration']) {
            $stmt = $conn->prepare("
                UPDATE video_progress 
                SET watched_duration = ?, last_watched = ? 
                WHERE student_id = ? AND video_id = ?
            ");
            $stmt->execute([
                $watched_duration,
                date('Y-m-d H:i:s'),
                $_SESSION['user_id'],
                $video_id
            ]);
        }
    } else {
        // Insert new progress record
        $stmt = $conn->prepare("
            INSERT INTO video_progress (student_id, video_id, watched_duration, last_watched) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['user_id'],
            $video_id,
            $watched_duration,
            date('Y-m-d H:i:s')
        ]);
    }

    echo json_encode(['success' => true, 'message' => 'Progress updated']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
}

?>