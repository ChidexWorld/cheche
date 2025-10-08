<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../config/session.php';

requireLogin();

$quiz_id = $_GET['id'] ?? 0;

if (!$quiz_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Quiz ID is required']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get quiz details
    $stmt = $conn->prepare("
        SELECT q.*, c.title as course_title, c.instructor_id
        FROM quizzes q
        JOIN courses c ON q.course_id = c.id
        WHERE q.id = ? AND q.is_active = 1
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quiz) {
        http_response_code(404);
        echo json_encode(['error' => 'Quiz not found']);
        exit;
    }

    // Check if user has access to this quiz
    $can_access = false;
    if (isInstructor() && $quiz['instructor_id'] == $_SESSION['user_id']) {
        $can_access = true;
    } elseif (isStudent()) {
        // Check if student is enrolled in the course
        $stmt = $conn->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $quiz['course_id']]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);
        $can_access = $enrollment !== null;
    }

    if (!$can_access) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit;
    }

    // Get questions and options
    $stmt = $conn->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY order_number ASC");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($questions as &$question) {
        $stmt = $conn->prepare("SELECT * FROM quiz_options WHERE question_id = ? ORDER BY order_number ASC");
        $stmt->execute([$question['id']]);
        $question['options'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Hide correct answers for students
        if (isStudent()) {
            foreach ($question['options'] as &$option) {
                unset($option['is_correct']);
            }
        }
    }

    $quiz['questions'] = $questions;

    // Get student's attempts if student
    if (isStudent()) {
        $stmt = $conn->prepare("
            SELECT * FROM quiz_attempts
            WHERE student_id = ? AND quiz_id = ?
            ORDER BY attempt_number DESC
        ");
        $stmt->execute([$_SESSION['user_id'], $quiz_id]);
        $quiz['attempts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if student has remaining attempts
        $attempts_count = count($quiz['attempts']);
        $quiz['remaining_attempts'] = max(0, $quiz['max_attempts'] - $attempts_count);
        $quiz['can_attempt'] = $quiz['remaining_attempts'] > 0;
    }

    echo json_encode(['success' => true, 'quiz' => $quiz]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get quiz: ' . $e->getMessage()]);
}
?>