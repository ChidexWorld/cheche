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

$quiz_id = $data['quiz_id'] ?? 0;
$question_text = trim($data['question_text'] ?? '');
$question_type = $data['question_type'] ?? 'multiple_choice';
$points = floatval($data['points'] ?? 1.0);
$options = $data['options'] ?? [];

if (!$quiz_id || !$question_text) {
    http_response_code(400);
    echo json_encode(['error' => 'Quiz ID and question text are required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Verify instructor owns this quiz
    $stmt = $conn->prepare("
        SELECT q.* FROM quizzes q
        JOIN courses c ON q.course_id = c.id
        WHERE q.id = ? AND c.instructor_id = ?
    ");
    $stmt->execute([$quiz_id, $_SESSION['user_id']]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Get next order number
    $stmt = $conn->prepare("SELECT MAX(order_number) as max_order FROM quiz_questions WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $order_number = ($result['max_order'] ?? 0) + 1;

    // Create question
    $stmt = $conn->prepare("INSERT INTO quiz_questions (quiz_id, question_text, question_type, points, order_number) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$quiz_id, $question_text, $question_type, $points, $order_number]);

    $question_id = $conn->lastInsertId();

    // Add options if provided
    if (!empty($options) && is_array($options)) {
        foreach ($options as $index => $option) {
            if (isset($option['text']) && trim($option['text']) !== '') {
                $stmt = $conn->prepare("INSERT INTO quiz_options (question_id, option_text, is_correct, order_number) VALUES (?, ?, ?, ?)");
                $stmt->execute([$question_id, trim($option['text']), $option['is_correct'] ?? false, $index + 1]);
            }
        }
    }

    echo json_encode(['success' => true, 'question_id' => $question_id]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to add question: ' . $e->getMessage()]);
}
?>