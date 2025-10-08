<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';

requireInstructor();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST;
}

$course_id = $data['course_id'] ?? 0;
$title = trim($data['title'] ?? '');
$description = trim($data['description'] ?? '');
$passing_score = floatval($data['passing_score'] ?? 70);
$max_attempts = intval($data['max_attempts'] ?? 3);
$time_limit = intval($data['time_limit'] ?? 30);

if (!$course_id || !$title) {
    http_response_code(400);
    echo json_encode(['error' => 'Course ID and title are required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verify instructor owns this course
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->execute([$course_id, $_SESSION['user_id']]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Create quiz
    $stmt = $conn->prepare("INSERT INTO quizzes (course_id, title, description, passing_score, max_attempts, time_limit) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$course_id, $title, $description, $passing_score, $max_attempts, $time_limit]);

    $quiz_id = $conn->lastInsertId();

    echo json_encode(['success' => true, 'quiz_id' => $quiz_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create quiz: ' . $e->getMessage()]);
}
?>